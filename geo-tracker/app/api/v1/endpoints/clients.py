"""
Clients API endpoints for client management
"""

from fastapi import APIRouter, HTTPException, status
from typing import Dict, Any

from app.services.jwt_service import jwt_service

router = APIRouter()


@router.get("/")
async def list_clients():
    """List all clients"""
    return {"clients": []}


@router.post("/")
async def create_client():
    """Create a new client"""
    return {"message": "Client created"}


@router.get("/{client_id}")
async def get_client(client_id: int):
    """Get client details"""
    return {"client_id": client_id}


@router.get("/{client_id}/.well-known/jwks.json")
async def get_client_jwks(client_id: int):
    """
    JWKS endpoint for client public keys

    WordPress sites use this endpoint to retrieve the public keys
    needed to verify JWT tokens issued by the client.
    """
    try:
        jwks = jwt_service.get_jwks(client_id)
        return jwks
    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to retrieve JWKS"
        )


@router.post("/{client_id}/keys")
async def register_client_key(client_id: int, key_data: Dict[str, str]):
    """
    Register a client's public key

    WordPress sites call this endpoint to register their public key
    for JWT verification. This triggers key rotation and management.
    """
    try:
        public_key_pem = key_data.get("public_key")
        private_key_pem = key_data.get("private_key")  # Optional for signing

        if not public_key_pem:
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="public_key is required"
            )

        # Register the key with automatic rotation management
        kid = jwt_service.register_client_key(
            client_id=client_id,
            public_key_pem=public_key_pem,
            private_key_pem=private_key_pem
        )

        return {
            "message": "Key registered successfully",
            "kid": kid,
            "expires_in_minutes": 15
        }

    except Exception as e:
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to register key: {str(e)}"
        )