"""
Dependency injection utilities
"""

from typing import Generator
from fastapi import Depends, HTTPException, status, Request
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import settings
from app.db.session import async_session_factory
from app import schemas
from app.services.jwt_service import jwt_service
import structlog

logger = structlog.get_logger(__name__)

security = HTTPBearer()


async def get_db() -> Generator[AsyncSession, None, None]:
    """
    Dependency for database session
    """
    async with async_session_factory() as session:
        try:
            yield session
        finally:
            await session.close()


def get_current_client(
    request: Request,
    credentials: HTTPAuthorizationCredentials = Depends(security),
) -> schemas.Client:
    """
    Dependency for JWT token validation and client retrieval

    Extracts client_id from token and validates against JWKS
    """
    credentials_exception = HTTPException(
        status_code=status.HTTP_401_UNAUTHORIZED,
        detail="Could not validate credentials",
        headers={"WWW-Authenticate": "Bearer"},
    )

    try:
        # Extract token
        token = credentials.credentials

        # Decode token header to get kid (without verification)
        import jwt as pyjwt
        header = pyjwt.get_unverified_header(token)
        kid = header.get("kid")

        if not kid:
            raise credentials_exception

        # Extract client_id from kid (format: key_{client_id}_{version})
        try:
            client_id_str = kid.split("_")[1]
            client_id = int(client_id_str)
        except (IndexError, ValueError):
            logger.error(f"Invalid kid format: {kid}")
            raise credentials_exception

        # Verify token using client's JWKS
        payload = jwt_service.verify_token(token, client_id)

        if not payload:
            raise credentials_exception

        # Validate payload
        if not jwt_service.validate_token_payload(payload, client_id):
            raise credentials_exception

        # Return client info (in production, fetch from database)
        return schemas.Client(
            id=client_id,
            name=payload.get("client_name", f"Client {client_id}"),
            domain=payload.get("iss", "unknown"),
            wordpress_url=payload.get("wordpress_url", ""),
            is_active=True,
            created_at=payload.get("iat"),
            updated_at=payload.get("iat"),
        )

    except Exception as e:
        logger.error(f"Authentication failed: {str(e)}")
        raise credentials_exception