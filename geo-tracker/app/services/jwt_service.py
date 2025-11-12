"""
JWT authentication service with RS256 and JWKS support
"""

import json
from datetime import datetime, timedelta
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


class JWTService:
    """
    JWT service with RS256 signing and JWKS support for multi-tenant authentication
    """

    def __init__(self):
        self._jwks_cache: Dict[str, Dict[str, Any]] = {}
        self._key_cache: Dict[str, RSAKey] = {}

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
        # Load public key
        public_key = serialization.load_pem_public_key(
            public_key_pem.encode('utf-8'),
            backend=default_backend()
        )

        # Create JWKS entry
        rsa_key = RSAKey.import_key(public_key)
        jwks_entry = rsa_key.as_dict()
        jwks_entry['kid'] = kid
        jwks_entry['use'] = 'sig'
        jwks_entry['alg'] = 'RS256'

        return jwks_entry

    def get_jwks(self, client_id: int) -> Dict[str, Any]:
        """
        Get JWKS for a client (cached)

        Args:
            client_id: Client ID

        Returns:
            JWKS document
        """
        cache_key = f"jwks_{client_id}"

        if cache_key in self._jwks_cache:
            return self._jwks_cache[cache_key]

        # TODO: Load from database
        # For now, return a mock JWKS
        mock_jwks = {
            "keys": [
                {
                    "kty": "RSA",
                    "use": "sig",
                    "kid": f"key_{client_id}_1",
                    "n": "mock_modulus",
                    "e": "AQAB",
                    "alg": "RS256"
                }
            ]
        }

        self._jwks_cache[cache_key] = mock_jwks
        return mock_jwks

    def create_access_token(
        self,
        data: Dict[str, Any],
        expires_delta: Optional[timedelta] = None,
        private_key_pem: Optional[str] = None
    ) -> str:
        """
        Create a JWT access token

        Args:
            data: Token payload data
            expires_delta: Token expiry time
            private_key_pem: Private key for signing (if not using default)

        Returns:
            JWT token string
        """
        to_encode = data.copy()

        if expires_delta:
            expire = datetime.utcnow() + expires_delta
        else:
            expire = datetime.utcnow() + timedelta(minutes=settings.ACCESS_TOKEN_EXPIRE_MINUTES)

        to_encode.update({
            "exp": expire,
            "iat": datetime.utcnow(),
            "aud": "geo-tracker",
            "iss": data.get("iss", "geo-tracker")
        })

        # Use provided private key or default
        if private_key_pem:
            private_key = serialization.load_pem_private_key(
                private_key_pem.encode('utf-8'),
                password=None,
                backend=default_backend()
            )
            rsa_key = RSAKey.import_key(private_key)
        else:
            # Use default key (in production, this should be configurable)
            rsa_key = RSAKey.generate_key()
            logger.warning("Using generated key - configure proper key management")

        # Create and sign token
        header = {"alg": "RS256", "typ": "JWT", "kid": f"key_{data.get('client_id', 'default')}"}
        token = jwt.encode(header, to_encode, rsa_key)

        return token

    def verify_token(self, token: str, client_id: int) -> Optional[Dict[str, Any]]:
        """
        Verify a JWT token using the client's JWKS

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
            header = jwt.get_unverified_header(token)
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
            rsa_key = RSAKey.import_dict(key_data)

            # Verify and decode token
            payload = jwt.decode(token, rsa_key)

            # Additional validation
            if payload.get("aud") != "geo-tracker":
                logger.warning("Invalid token audience")
                return None

            return payload

        except Exception as e:
            logger.error(f"Token verification failed: {str(e)}")
            return None

    def validate_token_payload(self, payload: Dict[str, Any], client_id: int) -> bool:
        """
        Validate token payload claims

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

            # Check expiry
            exp = payload.get("exp")
            if not exp or datetime.utcnow().timestamp() > exp:
                logger.warning("Token expired")
                return False

            # Check issuer
            iss = payload.get("iss")
            if not iss:
                logger.warning("Token missing issuer")
                return False

            return True

        except Exception as e:
            logger.error(f"Payload validation failed: {str(e)}")
            return False


# Create global JWT service instance
jwt_service = JWTService()