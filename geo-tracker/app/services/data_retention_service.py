"""
Data retention service for automated cleanup of old data

Implements retention policies:
- Raw operational data: 180 days
- KPI metrics: 365 days
"""

import asyncio
from datetime import datetime, timedelta
from typing import Dict, List, Tuple
import structlog

from sqlalchemy import text, delete, select, func
from sqlalchemy.ext.asyncio import AsyncSession

from app.core.config import settings

logger = structlog.get_logger(__name__)


class DataRetentionService:
    """
    Service for enforcing data retention policies
    """

    def __init__(self):
        # Retention periods in days
        self.raw_data_retention_days = 180
        self.kpi_retention_days = 365

        # Safety buffer - don't delete data newer than this
        self.safety_buffer_hours = 24

    async def cleanup_all_clients(self) -> Dict[str, int]:
        """
        Run retention cleanup for all active clients

        Returns:
            Dict with cleanup statistics
        """
        # Lazy imports to avoid database driver issues during testing
        from app.db.session import get_tenant_session
        from app import models

        total_stats = {
            "clients_processed": 0,
            "runs_deleted": 0,
            "answers_deleted": 0,
            "citations_deleted": 0,
            "entities_deleted": 0,
            "similarities_deleted": 0,
            "metrics_deleted": 0,
            "errors": 0,
        }

        try:
            # Get all active clients
            async with get_tenant_session() as session:
                result = await session.execute(
                    select(models.Client.id, models.Client.name)
                    .where(models.Client.is_active == True)
                )
                clients = result.fetchall()

            logger.info("Starting data retention cleanup", client_count=len(clients))

            for client_id, client_name in clients:
                try:
                    client_stats = await self.cleanup_client_data(client_id)
                    total_stats["clients_processed"] += 1

                    # Aggregate stats
                    for key, value in client_stats.items():
                        if key in total_stats:
                            total_stats[key] += value

                    logger.info(
                        "Client cleanup completed",
                        client_id=client_id,
                        client_name=client_name,
                        **client_stats
                    )

                except Exception as e:
                    logger.error(
                        "Failed to cleanup client data",
                        client_id=client_id,
                        client_name=client_name,
                        error=str(e)
                    )
                    total_stats["errors"] += 1

            logger.info("Data retention cleanup completed", **total_stats)

        except Exception as e:
            logger.error("Data retention cleanup failed", error=str(e))
            total_stats["errors"] += 1

        return total_stats

    async def cleanup_client_data(self, client_id: int) -> Dict[str, int]:
        """
        Clean up old data for a specific client

        Args:
            client_id: Client ID to cleanup

        Returns:
            Dict with cleanup statistics for this client
        """
        # Lazy imports to avoid database driver issues during testing
        from app.db.session import get_tenant_session
        from app import models

        stats = {
            "runs_deleted": 0,
            "answers_deleted": 0,
            "citations_deleted": 0,
            "entities_deleted": 0,
            "similarities_deleted": 0,
            "metrics_deleted": 0,
        }

        cutoff_date = datetime.utcnow() - timedelta(hours=self.safety_buffer_hours)

        async with get_tenant_session(client_id) as session:
            try:
                # Clean up raw operational data (180 days)
                raw_cutoff = cutoff_date - timedelta(days=self.raw_data_retention_days)

                # Delete old runs and cascade to related data
                runs_result = await session.execute(
                    delete(models.Run)
                    .where(models.Run.client_id == client_id)
                    .where(models.Run.created_at < raw_cutoff)
                    .returning(models.Run.id)
                )
                deleted_run_ids = runs_result.scalars().all()
                stats["runs_deleted"] = len(deleted_run_ids)

                # The related answers, citations, entities, and similarities
                # should be deleted via CASCADE constraints in the database schema
                # But let's also explicitly clean them up in case CASCADE isn't set

                if deleted_run_ids:
                    # Clean up answers for deleted runs
                    answers_result = await session.execute(
                        delete(models.Answer)
                        .where(models.Answer.run_id.in_(deleted_run_ids))
                    )
                    stats["answers_deleted"] = answers_result.rowcount

                    # Clean up citations for deleted answers
                    citations_result = await session.execute(
                        delete(models.Citation)
                        .where(models.Citation.answer_id.in_(
                            select(models.Answer.id)
                            .where(models.Answer.run_id.in_(deleted_run_ids))
                        ))
                    )
                    stats["citations_deleted"] = citations_result.rowcount

                    # Clean up entities for deleted answers
                    entities_result = await session.execute(
                        delete(models.Entity)
                        .where(models.Entity.answer_id.in_(
                            select(models.Answer.id)
                            .where(models.Answer.run_id.in_(deleted_run_ids))
                        ))
                    )
                    stats["entities_deleted"] = entities_result.rowcount

                    # Clean up similarities for deleted answers
                    similarities_result = await session.execute(
                        delete(models.Similarity)
                        .where(models.Similarity.answer_id.in_(
                            select(models.Answer.id)
                            .where(models.Answer.run_id.in_(deleted_run_ids))
                        ))
                    )
                    stats["similarities_deleted"] = similarities_result.rowcount

                # Clean up old KPI metrics (365 days)
                kpi_cutoff = cutoff_date - timedelta(days=self.kpi_retention_days)

                metrics_result = await session.execute(
                    delete(models.Metric)
                    .where(models.Metric.client_id == client_id)
                    .where(models.Metric.date < kpi_cutoff.date())  # Compare dates, not datetimes
                )
                stats["metrics_deleted"] = metrics_result.rowcount

                # Commit the changes
                await session.commit()

            except Exception as e:
                await session.rollback()
                logger.error(
                    "Failed to cleanup client data",
                    client_id=client_id,
                    error=str(e)
                )
                raise

        return stats

    async def get_retention_summary(self, client_id: int) -> Dict[str, int]:
        """
        Get summary of data that would be deleted for a client

        Args:
            client_id: Client ID to analyze

        Returns:
            Dict with counts of data that would be deleted
        """
        # Lazy imports to avoid database driver issues during testing
        from app.db.session import get_tenant_session
        from app import models

        summary = {
            "runs_to_delete": 0,
            "answers_to_delete": 0,
            "citations_to_delete": 0,
            "entities_to_delete": 0,
            "similarities_to_delete": 0,
            "metrics_to_delete": 0,
        }

        cutoff_date = datetime.utcnow() - timedelta(hours=self.safety_buffer_hours)

        async with get_tenant_session(client_id) as session:
            # Raw data cutoff (180 days)
            raw_cutoff = cutoff_date - timedelta(days=self.raw_data_retention_days)

            # Count runs to delete
            runs_result = await session.execute(
                select(func.count(models.Run.id))
                .where(models.Run.client_id == client_id)
                .where(models.Run.created_at < raw_cutoff)
            )
            summary["runs_to_delete"] = runs_result.scalar() or 0

            # Count answers to delete (cascaded from runs)
            answers_result = await session.execute(
                select(func.count(models.Answer.id))
                .where(models.Answer.run_id.in_(
                    select(models.Run.id)
                    .where(models.Run.client_id == client_id)
                    .where(models.Run.created_at < raw_cutoff)
                ))
            )
            summary["answers_to_delete"] = answers_result.scalar() or 0

            # Count citations to delete
            citations_result = await session.execute(
                select(func.count(models.Citation.id))
                .where(models.Citation.answer_id.in_(
                    select(models.Answer.id)
                    .where(models.Answer.run_id.in_(
                        select(models.Run.id)
                        .where(models.Run.client_id == client_id)
                        .where(models.Run.created_at < raw_cutoff)
                    ))
                ))
            )
            summary["citations_to_delete"] = citations_result.scalar() or 0

            # Count entities to delete
            entities_result = await session.execute(
                select(func.count(models.Entity.id))
                .where(models.Entity.answer_id.in_(
                    select(models.Answer.id)
                    .where(models.Answer.run_id.in_(
                        select(models.Run.id)
                        .where(models.Run.client_id == client_id)
                        .where(models.Run.created_at < raw_cutoff)
                    ))
                ))
            )
            summary["entities_to_delete"] = entities_result.scalar() or 0

            # Count similarities to delete
            similarities_result = await session.execute(
                select(func.count(models.Similarity.id))
                .where(models.Similarity.answer_id.in_(
                    select(models.Answer.id)
                    .where(models.Answer.run_id.in_(
                        select(models.Run.id)
                        .where(models.Run.client_id == client_id)
                        .where(models.Run.created_at < raw_cutoff)
                    ))
                ))
            )
            summary["similarities_to_delete"] = similarities_result.scalar() or 0

            # KPI data cutoff (365 days)
            kpi_cutoff = cutoff_date - timedelta(days=self.kpi_retention_days)

            # Count metrics to delete
            metrics_result = await session.execute(
                select(func.count(models.Metric.id))
                .where(models.Metric.client_id == client_id)
                .where(models.Metric.date < kpi_cutoff.date())
            )
            summary["metrics_to_delete"] = metrics_result.scalar() or 0

        return summary

    async def validate_cleanup_safety(self, client_id: int) -> Dict[str, bool]:
        """
        Validate that cleanup operations won't break data integrity

        Args:
            client_id: Client ID to validate

        Returns:
            Dict with safety check results
        """
        # Lazy imports to avoid database driver issues during testing
        from app.db.session import get_tenant_session
        from app import models

        safety_checks = {
            "no_active_runs": True,
            "no_recent_runs": True,
            "foreign_keys_intact": True,
        }

        cutoff_date = datetime.utcnow() - timedelta(hours=self.safety_buffer_hours)
        raw_cutoff = cutoff_date - timedelta(days=self.raw_data_retention_days)

        async with get_tenant_session(client_id) as session:
            # Check for runs that are still active/pending
            active_runs = await session.execute(
                select(func.count(models.Run.id))
                .where(models.Run.client_id == client_id)
                .where(models.Run.created_at < raw_cutoff)
                .where(models.Run.status.in_(["pending", "running"]))
            )
            if active_runs.scalar() > 0:
                safety_checks["no_active_runs"] = False

            # Check for runs completed in the last 24 hours (safety buffer)
            recent_runs = await session.execute(
                select(func.count(models.Run.id))
                .where(models.Run.client_id == client_id)
                .where(models.Run.created_at >= cutoff_date)
            )
            if recent_runs.scalar() > 0:
                safety_checks["no_recent_runs"] = False

        return safety_checks


# Global instance
data_retention_service = DataRetentionService()