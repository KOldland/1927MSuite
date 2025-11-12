"""
Scheduled job service for data retention and maintenance tasks

Uses APScheduler to run periodic cleanup jobs.
"""

import asyncio
from typing import Dict, Any
import structlog
from apscheduler.schedulers.asyncio import AsyncIOScheduler
from apscheduler.triggers.cron import CronTrigger
from apscheduler.jobstores.memory import MemoryJobStore
from apscheduler.executors.asyncio import AsyncIOExecutor

from app.services.data_retention_service import data_retention_service
from app.core.config import settings

logger = structlog.get_logger(__name__)


class SchedulerService:
    """
    Service for managing scheduled maintenance jobs
    """

    def __init__(self):
        self.scheduler = None
        self.is_running = False

    def initialize(self):
        """
        Initialize the scheduler with job stores and executors
        """
        jobstores = {
            'default': MemoryJobStore()
        }
        executors = {
            'default': AsyncIOExecutor()
        }
        job_defaults = {
            'coalesce': True,
            'max_instances': 1,
            'misfire_grace_time': 30
        }

        self.scheduler = AsyncIOScheduler(
            jobstores=jobstores,
            executors=executors,
            job_defaults=job_defaults,
            timezone=settings.TIMEZONE
        )

        logger.info("Scheduler service initialized")

    def start(self):
        """
        Start the scheduler and add scheduled jobs
        """
        if not self.scheduler:
            self.initialize()

        # Add data retention cleanup job - run daily at 2 AM
        self.scheduler.add_job(
            func=self._run_data_retention_cleanup,
            trigger=CronTrigger(hour=2, minute=0),
            id='data_retention_cleanup',
            name='Data Retention Cleanup',
            replace_existing=True
        )

        # Add a health check job - run every 6 hours
        self.scheduler.add_job(
            func=self._run_health_check,
            trigger=CronTrigger(hour='*/6', minute=0),
            id='health_check',
            name='Health Check',
            replace_existing=True
        )

        self.scheduler.start()
        self.is_running = True

        logger.info("Scheduler service started", job_count=len(self.scheduler.get_jobs()))

    def stop(self):
        """
        Stop the scheduler
        """
        if self.scheduler and self.is_running:
            self.scheduler.shutdown(wait=True)
            self.is_running = False
            logger.info("Scheduler service stopped")

    async def _run_data_retention_cleanup(self):
        """
        Execute the data retention cleanup job
        """
        try:
            logger.info("Starting scheduled data retention cleanup")

            stats = await data_retention_service.cleanup_all_clients()

            logger.info(
                "Scheduled data retention cleanup completed",
                **stats
            )

        except Exception as e:
            logger.error(
                "Scheduled data retention cleanup failed",
                error=str(e)
            )

    async def _run_health_check(self):
        """
        Execute health check maintenance tasks
        """
        try:
            logger.info("Running scheduled health check")

            # Add any health check logic here
            # For now, just log that we're healthy
            logger.info("Health check completed successfully")

        except Exception as e:
            logger.error(
                "Scheduled health check failed",
                error=str(e)
            )

    def get_job_status(self) -> Dict[str, Any]:
        """
        Get status of scheduled jobs

        Returns:
            Dict with job status information
        """
        if not self.scheduler:
            return {"status": "not_initialized"}

        jobs = []
        for job in self.scheduler.get_jobs():
            jobs.append({
                "id": job.id,
                "name": job.name,
                "next_run_time": job.next_run_time.isoformat() if job.next_run_time else None,
                "trigger": str(job.trigger)
            })

        return {
            "status": "running" if self.is_running else "stopped",
            "job_count": len(jobs),
            "jobs": jobs
        }


# Global instance
scheduler_service = SchedulerService()