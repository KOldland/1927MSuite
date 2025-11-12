"""
Posts API endpoints for WordPress integration
"""

from typing import List, Dict, Any
from fastapi import APIRouter, Depends, HTTPException, status
from sqlalchemy.ext.asyncio import AsyncSession

from app import schemas
from app.api import deps
from app.services import post_service
import structlog

router = APIRouter()
logger = structlog.get_logger(__name__)


@router.post("/sync", response_model=schemas.PostSyncResponse)
async def sync_posts(
    sync_data: schemas.PostSyncRequest,
    db: AsyncSession = Depends(deps.get_tenant_db),
    current_client: schemas.Client = Depends(deps.get_current_client),
    _: None = Depends(deps.check_rate_limit),
) -> Any:
    """
    Sync posts, entities, and AnswerCards from WordPress plugin
    """
    try:
        logger.info(
            "Syncing posts from WordPress",
            client_id=current_client.id,
            post_count=len(sync_data.posts) if sync_data.posts else 0,
            entity_count=len(sync_data.entities) if sync_data.entities else 0,
        )

        result = await post_service.sync_posts(
            db=db,
            client_id=current_client.id,
            sync_data=sync_data,
        )

        logger.info(
            "Posts sync completed",
            client_id=current_client.id,
            posts_processed=result.posts_processed,
            entities_processed=result.entities_processed,
        )

        return result

    except Exception as e:
        logger.error(
            "Posts sync failed",
            client_id=current_client.id,
            error=str(e),
            exc_info=True,
        )
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to sync posts",
        )


@router.get("/status")
async def get_sync_status(
    db: AsyncSession = Depends(deps.get_db),
    current_client: schemas.Client = Depends(deps.get_current_client),
) -> Dict[str, Any]:
    """
    Get sync status for the current client
    """
    try:
        status_data = await post_service.get_sync_status(
            db=db,
            client_id=current_client.id,
        )

        return {
            **status_data,
            "client_name": current_client.name,
            "status": "active" if current_client.is_active else "inactive",
        }

    except Exception as e:
        logger.error(
            "Failed to get sync status",
            client_id=current_client.id,
            error=str(e),
            exc_info=True,
        )
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail="Failed to get sync status",
        )