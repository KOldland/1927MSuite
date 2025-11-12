"""
Database session configuration
"""

from sqlalchemy.ext.asyncio import AsyncSession, create_async_engine
from sqlalchemy.orm import sessionmaker
from sqlalchemy.pool import StaticPool
from sqlalchemy import text

from app.core.config import settings

# Create async engine
engine = create_async_engine(
    settings.sql_database_url.replace("postgresql://", "postgresql+asyncpg://"),
    poolclass=StaticPool,
    echo=settings.DEBUG,
    future=True,
)

# Create async session factory
async_session_factory = sessionmaker(
    bind=engine,
    class_=AsyncSession,
    expire_on_commit=False,
)


class TenantScopedSession:
    """
    Session factory that sets tenant context for Row Level Security
    """

    def __init__(self, client_id: int = None):
        self.client_id = client_id

    async def __aenter__(self):
        self.session = async_session_factory()
        if self.client_id:
            # Set the tenant context for RLS
            await self.session.execute(
                text("SET LOCAL app.current_client_id = :client_id"),
                {"client_id": self.client_id}
            )
        return self.session

    async def __aexit__(self, exc_type, exc_val, exc_tb):
        if exc_type:
            await self.session.rollback()
        else:
            await self.session.commit()
        await self.session.close()


async def get_tenant_session(client_id: int) -> TenantScopedSession:
    """
    Get a tenant-scoped database session
    """
    return TenantScopedSession(client_id)