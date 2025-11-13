"""
Dependency injection utilities
"""

from typing import Generator
import time
from fastapi import Depends, HTTPException, status, Request
from fastapi.security import HTTPBearer, HTTPAuthorizationCredentials
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import settings
from app.db.session import get_tenant_session
from app import schemas
from app.services.rate_limit_service import rate_limiter
from app.services.jwt_service import jwt_service
import structlog

logger = structlog.get_logger(__name__)

security = HTTPBearer()


async def get_db() -> Generator[AsyncSession, None, None]:
    """
    Dependency for database session (legacy - use get_tenant_db for new code)
    """
    async with get_tenant_session() as session:
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


async def get_tenant_db(current_client: schemas.Client = Depends(get_current_client)) -> Generator[AsyncSession, None, None]:
    """
    Dependency for tenant-scoped database session with RLS enabled
    """
    async with get_tenant_session(current_client.id) as session:
        try:
            yield session
        finally:
            await session.close()


async def check_rate_limit(current_client: schemas.Client = Depends(get_current_client)):
    """
    Check rate limit for the current client

    Raises HTTPException if rate limited
    """
    client_key = f"client_{current_client.id}"

    allowed = await rate_limiter.is_allowed(client_key)
    if not allowed:
        reset_time = await rate_limiter.get_reset_time(client_key)
        reset_in = int(reset_time - time.time())

        logger.warning(
            "Rate limit exceeded",
            client_id=current_client.id,
            reset_in_seconds=reset_in,
        )

        raise HTTPException(
            status_code=status.HTTP_429_TOO_MANY_REQUESTS,
            detail=f"Rate limit exceeded. Try again in {reset_in} seconds.",
            headers={"Retry-After": str(reset_in)},
        )