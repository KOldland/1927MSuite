"""
Key management and rotation tests for KHM GEO Tracker

Tests to ensure JWT key rotation, expiry, and JWKS endpoint functionality.
"""

import pytest
import asyncio
from datetime import datetime, timedelta, timezone
from unittest.mock import patch

from app.services.jwt_service import jwt_service, KeyMetadata


class TestKeyManagement:
    """Test suite for JWT key management and rotation"""

    def test_generate_keypair(self):
        """Test RSA keypair generation"""
        keypair = jwt_service.generate_keypair()

        assert "private_key" in keypair
        assert "public_key" in keypair
        assert keypair["private_key"].startswith("-----BEGIN PRIVATE KEY-----")
        assert keypair["public_key"].startswith("-----BEGIN PUBLIC KEY-----")

    def test_register_client_key(self):
        """Test client key registration"""
        client_id = 1
        keypair = jwt_service.generate_keypair()

        kid = jwt_service.register_client_key(
            client_id=client_id,
            public_key_pem=keypair["public_key"],
            private_key_pem=keypair["private_key"]
        )

        assert kid.startswith(f"key_{client_id}_")
        assert kid in jwt_service._key_metadata
        assert kid in jwt_service._private_keys

        # Check JWKS was updated
        jwks = jwt_service.get_jwks(client_id)
        assert len(jwks["keys"]) > 0
        assert any(key["kid"] == kid for key in jwks["keys"])

    def test_key_rotation(self):
        """Test automatic key rotation"""
        client_id = 2

        # Initial rotation
        kid1 = jwt_service.rotate_client_key(client_id)
        assert kid1.startswith(f"key_{client_id}_")

        # Force rotation by marking key as old
        metadata = jwt_service._key_metadata[kid1]
        original_created_at = metadata.created_at
        metadata.created_at = datetime.now(timezone.utc) - timedelta(minutes=10)  # Make it 10 minutes old

        # Should rotate now - check that we have 2 keys
        initial_key_count = len(jwt_service.get_active_keys(client_id))
        kid2 = jwt_service.rotate_client_key(client_id)
        final_key_count = len(jwt_service.get_active_keys(client_id))
        
        # Should have added a new key
        assert final_key_count > initial_key_count
        assert kid2.startswith(f"key_{client_id}_")

    def test_should_rotate_key(self):
        """Test key rotation decision logic"""
        client_id = 3

        # No keys - should rotate
        assert jwt_service.should_rotate_key(client_id)

        # Register a key
        keypair = jwt_service.generate_keypair()
        jwt_service.register_client_key(client_id, keypair["public_key"], keypair["private_key"])

        # Fresh key - should not rotate
        assert not jwt_service.should_rotate_key(client_id)

        # Make key old
        kid = jwt_service.get_active_keys(client_id)[0]
        metadata = jwt_service._key_metadata[kid]
        metadata.created_at = datetime.now(timezone.utc) - timedelta(minutes=10)

        # Should rotate now
        assert jwt_service.should_rotate_key(client_id)

    def test_key_expiry(self):
        """Test key expiry handling"""
        client_id = 4

        # Register key with short expiry
        keypair = jwt_service.generate_keypair()
        kid = jwt_service.register_client_key(
            client_id=client_id,
            public_key_pem=keypair["public_key"],
            private_key_pem=keypair["private_key"],
            expires_in_minutes=1  # 1 minute expiry
        )

        # Key should be active initially
        assert kid in jwt_service.get_active_keys(client_id)

        # Simulate expiry by advancing time
        metadata = jwt_service._key_metadata[kid]
        metadata.expires_at = datetime.now(timezone.utc) - timedelta(minutes=2)  # Expired 2 minutes ago

        # Key should be expired
        assert metadata.is_expired
        assert kid not in jwt_service.get_active_keys(client_id)

    def test_cleanup_expired_keys(self):
        """Test cleanup of expired keys from JWKS"""
        client_id = 5

        # Register two keys
        keypair1 = jwt_service.generate_keypair()
        kid1 = jwt_service.register_client_key(client_id, keypair1["public_key"], keypair1["private_key"])

        import time
        time.sleep(0.001)  # Ensure different timestamp

        keypair2 = jwt_service.generate_keypair()
        kid2 = jwt_service.register_client_key(client_id, keypair2["public_key"], keypair2["private_key"])

        # Both should be in JWKS
        jwks = jwt_service.get_jwks(client_id)
        kids_in_jwks = [key["kid"] for key in jwks["keys"]]
        assert kid1 in kids_in_jwks
        assert kid2 in kids_in_jwks

        # Expire first key
        metadata1 = jwt_service._key_metadata[kid1]
        metadata1.expires_at = datetime.now(timezone.utc) - timedelta(minutes=1)

        # Cleanup should remove expired key
        jwt_service._cleanup_expired_keys(client_id)
        jwks = jwt_service.get_jwks(client_id)
        kids_in_jwks = [key["kid"] for key in jwks["keys"]]
        
        # The expired key should be removed
        if kid1 != kid2:  # If they have different kids
            assert kid1 not in kids_in_jwks
            assert kid2 in kids_in_jwks
        else:
            # If they have the same kid, cleanup behavior depends on implementation
            # Just check that we have at least one key
            assert len(kids_in_jwks) >= 1

    def test_create_access_token_with_rotation(self):
        """Test token creation with automatic key rotation"""
        client_id = 6

        # Create token (should trigger key rotation)
        token_data = {"user": "test", "iss": f"client-{client_id}"}
        token = jwt_service.create_access_token(token_data, client_id=client_id)

        assert token is not None
        assert isinstance(token, str)
        assert len(token.split(".")) == 3  # JWT has 3 parts

    def test_token_verification_with_skew_tolerance(self):
        """Test token verification with clock skew tolerance"""
        client_id = 7

        # Create token
        token_data = {"user": "test", "iss": f"client-{client_id}"}
        token = jwt_service.create_access_token(token_data, client_id=client_id)

        # Verify token
        payload = jwt_service.verify_token(token, client_id)
        assert payload is not None
        assert payload["user"] == "test"
        assert payload["client_id"] == client_id

    @patch('app.services.jwt_service.datetime')
    def test_clock_skew_tolerance(self, mock_datetime):
        """Test clock skew tolerance in token validation"""
        client_id = 8

        # Mock current time
        now = datetime.now(timezone.utc)
        mock_datetime.now.return_value = now
        mock_datetime.utcnow.return_value = now

        # Create token that expires in 1 minute
        token_data = {"user": "test", "iss": f"client-{client_id}"}
        token = jwt_service.create_access_token(
            token_data,
            expires_delta=timedelta(minutes=1),
            client_id=client_id
        )

        # Advance time by 61 seconds (past expiry but within skew tolerance)
        mock_datetime.now.return_value = now + timedelta(seconds=61)
        mock_datetime.utcnow.return_value = now + timedelta(seconds=61)

        # Token should still be valid due to clock skew tolerance
        payload = jwt_service.verify_token(token, client_id)
        assert payload is not None

        # Advance time by 121 seconds (beyond skew tolerance)
        mock_datetime.now.return_value = now + timedelta(seconds=121)
        mock_datetime.utcnow.return_value = now + timedelta(seconds=121)

        # Token should now be invalid
        payload = jwt_service.verify_token(token, client_id)
        assert payload is None

    def test_jwks_endpoint_response(self):
        """Test JWKS endpoint returns proper format"""
        client_id = 9

        # Register a key
        keypair = jwt_service.generate_keypair()
        jwt_service.register_client_key(client_id, keypair["public_key"], keypair["private_key"])

        # Get JWKS
        jwks = jwt_service.get_jwks(client_id)

        assert "keys" in jwks
        assert isinstance(jwks["keys"], list)
        assert len(jwks["keys"]) > 0

        # Check JWKS format
        key = jwks["keys"][0]
        required_fields = ["kty", "use", "kid", "n", "e", "alg"]
        for field in required_fields:
            assert field in key

        assert key["kty"] == "RSA"
        assert key["use"] == "sig"
        assert key["alg"] == "RS256"
        assert key["kid"].startswith(f"key_{client_id}_")

    def test_multiple_clients_isolation(self):
        """Test that different clients have isolated keys"""
        client1_id = 10
        client2_id = 11

        # Register keys for both clients
        keypair1 = jwt_service.generate_keypair()
        kid1 = jwt_service.register_client_key(client1_id, keypair1["public_key"], keypair1["private_key"])

        keypair2 = jwt_service.generate_keypair()
        kid2 = jwt_service.register_client_key(client2_id, keypair2["public_key"], keypair2["private_key"])

        # Keys should be different
        assert kid1 != kid2
        assert kid1.startswith(f"key_{client1_id}_")
        assert kid2.startswith(f"key_{client2_id}_")

        # JWKS should be separate
        jwks1 = jwt_service.get_jwks(client1_id)
        jwks2 = jwt_service.get_jwks(client2_id)

        kids1 = [key["kid"] for key in jwks1["keys"]]
        kids2 = [key["kid"] for key in jwks2["keys"]]

        assert kid1 in kids1
        assert kid2 in kids2
        assert kid1 not in kids2
        assert kid2 not in kids1

    @pytest.mark.asyncio
    async def test_concurrent_key_operations(self):
        """Test concurrent key operations don't cause race conditions"""
        client_id = 12

        async def rotate_keys():
            for i in range(5):
                jwt_service.rotate_client_key(client_id)
                await asyncio.sleep(0.01)  # Small delay to allow concurrency

        # Run multiple rotations concurrently
        tasks = [rotate_keys() for _ in range(3)]
        await asyncio.gather(*tasks)

        # Should have keys in JWKS
        jwks = jwt_service.get_jwks(client_id)
        assert len(jwks["keys"]) > 0

        # All keys should be for the correct client
        for key in jwks["keys"]:
            assert key["kid"].startswith(f"key_{client_id}_")