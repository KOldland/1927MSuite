"""
Data retention management API endpoints
"""

from typing import Dict, Any
from fastapi import APIRouter, Depends, HTTPException, status
import structlog

from app import schemas
from app.api import deps
from app.services.data_retention_service import data_retention_service
from app.services.scheduler_service import scheduler_service

router = APIRouter()
logger = structlog.get_logger(__name__)


@router.post("/cleanup", response_model=Dict[str, Any])
async def run_data_cleanup(
    current_client: schemas.Client = Depends(deps.get_current_client),
) -> Any:
    """
    Manually trigger data retention cleanup for all clients
    Requires admin privileges (for now, any authenticated client can trigger)
    """
    try:
        logger.info(
            "Manual data retention cleanup triggered",
            client_id=current_client.id,
            client_name=current_client.name,
        )

        stats = await data_retention_service.cleanup_all_clients()

        logger.info(
            "Manual data retention cleanup completed",
            client_id=current_client.id,
            **stats
        )

        return {
            "status": "completed",
            "message": "Data retention cleanup completed successfully",
            **stats
        }

    except Exception as e:
        logger.error(
            "Manual data retention cleanup failed",
            client_id=current_client.id,
            error=str(e)
        )
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Data retention cleanup failed: {str(e)}"
        )


@router.get("/summary/{client_id}", response_model=Dict[str, Any])
async def get_retention_summary(
    client_id: int,
    current_client: schemas.Client = Depends(deps.get_current_client),
) -> Any:
    """
    Get summary of data that would be deleted for a specific client
    """
    try:
        # Basic authorization check - client can only see their own data
        if current_client.id != client_id:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Can only view retention summary for your own client"
            )

        logger.info(
            "Getting retention summary",
            client_id=client_id,
            requested_by=current_client.id,
        )

        summary = await data_retention_service.get_retention_summary(client_id)

        return {
            "client_id": client_id,
            "retention_policy": {
                "raw_data_days": data_retention_service.raw_data_retention_days,
                "kpi_data_days": data_retention_service.kpi_retention_days,
                "safety_buffer_hours": data_retention_service.safety_buffer_hours,
            },
            **summary
        }

    except HTTPException:
        raise
    except Exception as e:
        logger.error(
            "Failed to get retention summary",
            client_id=client_id,
            error=str(e)
        )
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to get retention summary: {str(e)}"
        )


@router.get("/safety-check/{client_id}", response_model=Dict[str, Any])
async def validate_cleanup_safety(
    client_id: int,
    current_client: schemas.Client = Depends(deps.get_current_client),
) -> Any:
    """
    Validate that cleanup operations are safe for a specific client
    """
    try:
        # Basic authorization check - client can only check their own data
        if current_client.id != client_id:
            raise HTTPException(
                status_code=status.HTTP_403_FORBIDDEN,
                detail="Can only validate safety for your own client"
            )

        logger.info(
            "Validating cleanup safety",
            client_id=client_id,
            requested_by=current_client.id,
        )

        safety_checks = await data_retention_service.validate_cleanup_safety(client_id)

        all_safe = all(safety_checks.values())

        return {
            "client_id": client_id,
            "all_checks_passed": all_safe,
            "safety_checks": safety_checks,
            "recommendation": "Safe to proceed" if all_safe else "Review safety checks before proceeding"
        }

    except HTTPException:
        raise
    except Exception as e:
        logger.error(
            "Failed to validate cleanup safety",
            client_id=client_id,
            error=str(e)
        )
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to validate cleanup safety: {str(e)}"
        )


@router.get("/scheduler/status", response_model=Dict[str, Any])
async def get_scheduler_status(
    current_client: schemas.Client = Depends(deps.get_current_client),
) -> Any:
    """
    Get status of the scheduled jobs
    """
    try:
        logger.info(
            "Getting scheduler status",
            client_id=current_client.id,
        )

        status_info = scheduler_service.get_job_status()

        return status_info

    except Exception as e:
        logger.error(
            "Failed to get scheduler status",
            client_id=current_client.id,
            error=str(e)
        )
        raise HTTPException(
            status_code=status.HTTP_500_INTERNAL_SERVER_ERROR,
            detail=f"Failed to get scheduler status: {str(e)}"
        )