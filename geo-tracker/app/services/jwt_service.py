"""
JWT authentication service with RS256 and JWKS support
"""

import json
from datetime import datetime, timedelta, timezone
from typing import Dict, Any, Optional, List
from cryptography.hazmat.primitives import serialization
from cryptography.hazmat.primitives.asymmetric import rsa
from cryptography.hazmat.backends import default_backend
from joserfc import jwt
from joserfc.jwk import RSAKey
from joserfc.jws import serialize_compact
import structlog

from app.core.config import settings

logger = structlog.get_logger(__name__)


class KeyMetadata:
    """Metadata for a cryptographic key"""

    def __init__(self, kid: str, client_id: int, created_at: datetime, expires_at: datetime):
        self.kid = kid
        self.client_id = client_id
        self.created_at = created_at
        self.expires_at = expires_at

    @property
    def is_expired(self) -> bool:
        """Check if key is expired (with clock skew tolerance)"""
        # Add 60 seconds clock skew tolerance
        return datetime.now(timezone.utc) > (self.expires_at + timedelta(seconds=60))

    @property
    def is_active(self) -> bool:
        """Check if key is currently active"""
        now = datetime.now(timezone.utc)
        return self.created_at <= now <= (self.expires_at + timedelta(seconds=60))


class JWTService:
    """
    JWT service with RS256 signing and JWKS support for multi-tenant authentication
    """

    def __init__(self):
        self._jwks_cache: Dict[str, Dict[str, Any]] = {}
        self._key_cache: Dict[str, RSAKey] = {}
        self._key_metadata: Dict[str, KeyMetadata] = {}
        self._private_keys: Dict[str, str] = {}  # Store private keys for signing
        self._key_metadata: Dict[str, KeyMetadata] = {}
        self._private_keys: Dict[str, str] = {}  # Store private keys for signing

    def generate_keypair(self) -> Dict[str, str]:
        """
        Generate a new RSA keypair for a WordPress site

        Returns:
            Dict with 'private_key' (PEM) and 'public_key' (PEM)
        """
        # Generate RSA keypair
        private_key = rsa.generate_private_key(
            public_exponent=65537,
            key_size=2048,
            backend=default_backend()
        )

        # Serialize private key
        private_pem = private_key.private_bytes(
            encoding=serialization.Encoding.PEM,
            format=serialization.PrivateFormat.PKCS8,
            encryption_algorithm=serialization.NoEncryption()
        ).decode('utf-8')

        # Serialize public key
        public_key = private_key.public_key()
        public_pem = public_key.public_bytes(
            encoding=serialization.Encoding.PEM,
            format=serialization.PublicFormat.SubjectPublicKeyInfo
        ).decode('utf-8')

        return {
            'private_key': private_pem,
            'public_key': public_pem
        }

    def generate_keypair(self) -> Dict[str, str]:
        """
        Generate a new RSA keypair for a WordPress site

        Returns:
            Dict with 'private_key' (PEM) and 'public_key' (PEM)
        """
        # Generate RSA keypair
        private_key = rsa.generate_private_key(
            public_exponent=65537,
            key_size=2048,
            backend=default_backend()
        )

        # Serialize private key
        private_pem = private_key.private_bytes(
            encoding=serialization.Encoding.PEM,
            format=serialization.PrivateFormat.PKCS8,
            encryption_algorithm=serialization.NoEncryption()
        ).decode('utf-8')

        # Serialize public key
        public_key = private_key.public_key()
        public_pem = public_key.public_bytes(
            encoding=serialization.Encoding.PEM,
            format=serialization.PublicFormat.SubjectPublicKeyInfo
        ).decode('utf-8')

        return {
            'private_key': private_pem,
            'public_key': public_pem
        }

    def create_jwks_entry(self, public_key_pem: str, kid: str) -> Dict[str, Any]:
        """
        Create a JWKS entry from a public key PEM

        Args:
            public_key_pem: Public key in PEM format
            kid: Key ID

        Returns:
            JWKS entry dictionary
        """
        # Create RSA key from PEM
        rsa_key = RSAKey.import_key(public_key_pem)
        jwks_entry = rsa_key.as_dict()
        jwks_entry['kid'] = kid
        jwks_entry['use'] = 'sig'
        jwks_entry['alg'] = 'RS256'

        return jwks_entry

    def register_client_key(
        self,
        client_id: int,
        public_key_pem: str,
        private_key_pem: Optional[str] = None,
        expires_in_minutes: int = 15
    ) -> str:
        """
        Register a new key for a client with automatic rotation

        Args:
            client_id: Client ID
            public_key_pem: Public key in PEM format
            private_key_pem: Private key in PEM format (optional, for signing)
            expires_in_minutes: Key expiry time (default 15 minutes)

        Returns:
            Key ID (kid) for the registered key
        """
        # Generate kid with timestamp and counter for uniqueness
        import time
        timestamp = int(time.time() * 1000000)  # Microsecond precision
        kid = f"key_{client_id}_{timestamp}"

        # Calculate expiry
        expires_at = datetime.now(timezone.utc) + timedelta(minutes=expires_in_minutes)

        # Store key metadata
        metadata = KeyMetadata(kid, client_id, datetime.now(timezone.utc), expires_at)
        self._key_metadata[kid] = metadata

        # Store private key if provided (for signing)
        if private_key_pem:
            self._private_keys[kid] = private_key_pem

        # Create JWKS entry
        jwks_entry = self.create_jwks_entry(public_key_pem, kid)

        # Update JWKS cache for this client
        cache_key = f"jwks_{client_id}"
        if cache_key not in self._jwks_cache:
            self._jwks_cache[cache_key] = {"keys": []}

        # Add new key to JWKS (keep old keys for rotation window)
        self._jwks_cache[cache_key]["keys"].append(jwks_entry)

        # Clean up expired keys from JWKS
        self._cleanup_expired_keys(client_id)

        logger.info(
            "Registered new key for client",
            client_id=client_id,
            kid=kid,
            expires_at=expires_at.isoformat()
        )

        return kid

    def _cleanup_expired_keys(self, client_id: int):
        """
        Remove expired keys from JWKS cache

        Args:
            client_id: Client ID to clean up
        """
        cache_key = f"jwks_{client_id}"
        if cache_key not in self._jwks_cache:
            return

        keys = self._jwks_cache[cache_key]["keys"]
        active_keys = []

        for key in keys:
            kid = key.get("kid")
            if kid and kid in self._key_metadata:
                metadata = self._key_metadata[kid]
                if metadata.is_active:
                    active_keys.append(key)
                else:
                    # Remove expired key metadata
                    self._key_metadata.pop(kid, None)
                    self._private_keys.pop(kid, None)
                    logger.info("Cleaned up expired key", kid=kid, client_id=client_id)

        self._jwks_cache[cache_key]["keys"] = active_keys

    def rotate_client_key(self, client_id: int) -> str:
        """
        Rotate a client's key (generate new keypair and register it)

        Args:
            client_id: Client ID

        Returns:
            New key ID
        """
        # Generate new keypair
        keypair = self.generate_keypair()

        # Register the new key
        kid = self.register_client_key(
            client_id=client_id,
            public_key_pem=keypair["public_key"],
            private_key_pem=keypair["private_key"]
        )

        logger.info("Rotated key for client", client_id=client_id, new_kid=kid)

        return kid

    def get_active_keys(self, client_id: int) -> List[str]:
        """
        Get list of active (non-expired) key IDs for a client

        Args:
            client_id: Client ID

        Returns:
            List of active key IDs
        """
        active_kids = []
        for kid, metadata in self._key_metadata.items():
            if metadata.client_id == client_id and metadata.is_active:
                active_kids.append(kid)

        return active_kids

    def should_rotate_key(self, client_id: int) -> bool:
        """
        Check if a client's key should be rotated

        Keys should be rotated if:
        - No active keys exist
        - The newest key is older than half the expiry time

        Args:
            client_id: Client ID

        Returns:
            True if rotation is needed
        """
        active_keys = self.get_active_keys(client_id)

        if not active_keys:
            return True  # No active keys, rotation needed

        # Check if newest key is old enough to rotate
        newest_key_age = None
        for kid in active_keys:
            metadata = self._key_metadata.get(kid)
            if metadata:
                age = datetime.now(timezone.utc) - metadata.created_at
                if newest_key_age is None or age < newest_key_age:
                    newest_key_age = age

        # Rotate if newest key is older than 7.5 minutes (half of 15-minute expiry)
        return newest_key_age and newest_key_age > timedelta(minutes=7.5)

    def get_jwks(self, client_id: int) -> Dict[str, Any]:
        """
        Get JWKS for a client (with active keys only)

        Args:
            client_id: Client ID

        Returns:
            JWKS document with active keys
        """
        # Clean up expired keys first
        self._cleanup_expired_keys(client_id)

        cache_key = f"jwks_{client_id}"

        if cache_key in self._jwks_cache:
            return self._jwks_cache[cache_key]

        # If no cached JWKS, check if we should rotate/create keys
        if self.should_rotate_key(client_id):
            self.rotate_client_key(client_id)

        # Return cached JWKS (should exist after rotation if needed)
        if cache_key in self._jwks_cache:
            return self._jwks_cache[cache_key]

        # Fallback: return empty JWKS (shouldn't happen in production)
        logger.warning("No JWKS available for client", client_id=client_id)
        return {"keys": []}

    def create_access_token(
        self,
        data: Dict[str, Any],
        expires_delta: Optional[timedelta] = None,
        client_id: Optional[int] = None
    ) -> str:
        """
        Create a JWT access token using client's private key

        Args:
            data: Token payload data
            expires_delta: Token expiry time
            client_id: Client ID for key lookup

        Returns:
            JWT token string
        """
        if not client_id:
            raise ValueError("client_id is required for token creation")

        # Get active keys for client
        active_kids = self.get_active_keys(client_id)
        if not active_kids:
            # Auto-rotate if no active keys
            kid = self.rotate_client_key(client_id)
            active_kids = [kid]

        # Use the most recent active key
        kid = active_kids[-1]  # Last in list is most recent

        # Get private key for signing
        private_key_pem = self._private_keys.get(kid)
        if not private_key_pem:
            raise ValueError(f"No private key available for kid: {kid}")

        to_encode = data.copy()

        if expires_delta:
            expire = datetime.now(timezone.utc) + expires_delta
        else:
            expire = datetime.now(timezone.utc) + timedelta(minutes=settings.ACCESS_TOKEN_EXPIRE_MINUTES)

        to_encode.update({
            "exp": expire,
            "iat": datetime.now(timezone.utc),
            "aud": "geo-tracker",
            "iss": data.get("iss", f"client-{client_id}"),
            "client_id": client_id
        })

        # Load private key for signing
        rsa_key = RSAKey.import_key(private_key_pem)

        # Create and sign token
        header = {"alg": "RS256", "typ": "JWT", "kid": kid}
        token = jwt.encode(header, to_encode, rsa_key)

        logger.info(
            "Created JWT token",
            client_id=client_id,
            kid=kid,
            exp=expire.isoformat()
        )

        return token

    def verify_token(self, token: str, client_id: int) -> Optional[Dict[str, Any]]:
        """
        Verify a JWT token using the client's JWKS with clock skew tolerance

        Args:
            token: JWT token to verify
            client_id: Client ID for key lookup

        Returns:
            Decoded payload or None if invalid
        """
        try:
            # Get client's JWKS
            jwks = self.get_jwks(client_id)

            # Find the key by kid from token header
            import jwt as pyjwt
            header = pyjwt.get_unverified_header(token)
            kid = header.get("kid")

            if not kid:
                logger.warning("Token missing kid header")
                return None

            # Find matching key in JWKS
            key_data = None
            for key in jwks.get("keys", []):
                if key.get("kid") == kid:
                    key_data = key
                    break

            if not key_data:
                logger.warning(f"No matching key found for kid: {kid}")
                return None

            # Create RSA key from JWKS data
            rsa_key = RSAKey.import_key(key_data)

            # Verify and decode token with clock skew tolerance
            # joserfc handles clock skew internally, but we can add custom validation
            decoded_token = jwt.decode(token, rsa_key)
            payload = decoded_token.claims

            # Additional validation with clock skew tolerance
            if not self._validate_token_with_skew(payload, client_id):
                return None

            return payload

        except Exception as e:
            logger.error(f"Token verification failed: {str(e)}")
            return None

    def _validate_token_with_skew(self, payload: Dict[str, Any], client_id: int) -> bool:
        """
        Validate token payload with clock skew tolerance (±60 seconds)

        Args:
            payload: Decoded token payload
            client_id: Expected client ID

        Returns:
            True if valid, False otherwise
        """
        try:
            # Check client_id matches
            token_client_id = payload.get("client_id")
            if token_client_id != client_id:
                logger.warning(f"Client ID mismatch: expected {client_id}, got {token_client_id}")
                return False

            # Check expiry with clock skew tolerance
            exp = payload.get("exp")
            if not exp:
                logger.warning("Token missing expiry")
                return False

            # Allow 60 seconds clock skew
            now = datetime.now(timezone.utc).timestamp()
            if now > (exp + 60):  # exp + 60 seconds tolerance
                logger.warning("Token expired (with clock skew tolerance)")
                return False

            # Check issued at is not too far in the future
            iat = payload.get("iat")
            if iat and (now + 60) < iat:  # iat should not be more than 60 seconds in future
                logger.warning("Token issued too far in future")
                return False

            # Check issuer
            iss = payload.get("iss")
            if not iss:
                logger.warning("Token missing issuer")
                return False

            # Validate audience
            aud = payload.get("aud")
            if aud != "geo-tracker":
                logger.warning(f"Invalid token audience: {aud}")
                return False

            return True

        except Exception as e:
            logger.error(f"Payload validation failed: {str(e)}")
            return False


# Create global JWT service instance
jwt_service = JWTService()