"""
KPI aggregation service for computing visibility metrics
"""

from typing import Dict, List, Any, Optional
from datetime import datetime, date, timedelta
from collections import defaultdict

from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, func, and_, or_
import structlog

from app import models
from app.core.config import settings

logger = structlog.get_logger(__name__)


class KpiService:
    """
    Service for computing and aggregating KPI metrics
    """

    def __init__(self):
        self.batch_size = settings.BATCH_SIZE

    async def compute_daily_metrics(
        self,
        db: AsyncSession,
        target_date: Optional[date] = None,
    ) -> List[models.Metric]:
        """
        Compute daily metrics for all clients and engines
        """
        if target_date is None:
            target_date = date.today()

        logger.info("Computing daily metrics", date=target_date)

        try:
            # Get all active clients
            clients = await self._get_active_clients(db)

            metrics = []

            for client in clients:
                client_metrics = await self._compute_client_metrics(
                    db, client, target_date
                )
                metrics.extend(client_metrics)

            # Store metrics in database
            for metric in metrics:
                db.add(metric)

            await db.commit()

            logger.info(
                "Daily metrics computed",
                date=target_date,
                metrics_count=len(metrics)
            )

            return metrics

        except Exception as e:
            logger.error(
                "Daily metrics computation failed",
                date=target_date,
                error=str(e),
                exc_info=True
            )
            raise

    async def _compute_client_metrics(
        self,
        db: AsyncSession,
        client: models.Client,
        target_date: date,
    ) -> List[models.Metric]:
        """
        Compute metrics for a specific client on a specific date
        """
        metrics = []

        # Get all queries for this client
        queries = await self._get_client_queries(db, client.id)

        # Get engines that were active on this date
        engines = await self._get_active_engines(db, client.id, target_date)

        for engine in engines:
            for query in queries:
                metric = await self._compute_query_engine_metric(
                    db, client, query, engine, target_date
                )
                if metric:
                    metrics.append(metric)

        return metrics

    async def _compute_query_engine_metric(
        self,
        db: AsyncSession,
        client: models.Client,
        query: models.Query,
        engine: str,
        target_date: date,
    ) -> Optional[models.Metric]:
        """
        Compute metrics for a specific client/query/engine combination
        """
        try:
            # Get runs for this date range
            start_date = datetime.combine(target_date, datetime.min.time())
            end_date = datetime.combine(target_date, datetime.max.time())

            runs = await self._get_runs_for_date_range(
                db, client.id, query.id, engine, start_date, end_date
            )

            if not runs:
                return None

            # Compute KPIs
            kpis = await self._compute_kpis(db, client, query, engine, runs)

            metric = models.Metric(
                client_id=client.id,
                query_id=query.id,
                engine=engine,
                date=target_date,
                inclusion_rate=kpis["inclusion_rate"],
                extraction_rate=kpis["extraction_rate"],
                presence_rate=kpis["presence_rate"],
                co_visibility_rate=kpis["co_visibility_rate"],
                visibility_index=kpis["visibility_index"],
                total_queries=len(runs),
                successful_runs=len([r for r in runs if r.status == "completed"]),
                total_citations=kpis["total_citations"],
                client_mentions=kpis["client_mentions"],
                metadata_json=kpis["metadata"],
            )

            return metric

        except Exception as e:
            logger.error(
                "Failed to compute metric",
                client_id=client.id,
                query_id=query.id,
                engine=engine,
                date=target_date,
                error=str(e)
            )
            return None

    async def _compute_kpis(
        self,
        db: AsyncSession,
        client: models.Client,
        query: models.Query,
        engine: str,
        runs: List[models.Run],
    ) -> Dict[str, Any]:
        """
        Compute all KPI values for the given runs
        """
        total_runs = len(runs)
        successful_runs = len([r for r in runs if r.status == "completed"])

        if successful_runs == 0:
            return self._empty_kpis()

        # Get answers for successful runs
        answer_ids = [r.answers[0].id for r in runs if r.answers]

        # Inclusion Rate: % of queries where domain appears in citations
        inclusion_rate = await self._compute_inclusion_rate(db, client, answer_ids)

        # Extraction Rate: % with text overlap ≥ threshold
        extraction_rate = await self._compute_extraction_rate(db, client, answer_ids)

        # Presence Rate: % mentioning client/entity by name
        presence_rate = await self._compute_presence_rate(db, client, answer_ids)

        # Co-visibility Rate: % mentioning both client and publisher
        co_visibility_rate = await self._compute_co_visibility_rate(db, client, answer_ids)

        # Visibility Index: Weighted aggregate KPI
        visibility_index = self._compute_visibility_index(
            inclusion_rate, extraction_rate, presence_rate, co_visibility_rate
        )

        # Supporting data
        total_citations = await self._count_total_citations(db, answer_ids)
        client_mentions = await self._count_client_mentions(db, client, answer_ids)

        return {
            "inclusion_rate": inclusion_rate,
            "extraction_rate": extraction_rate,
            "presence_rate": presence_rate,
            "co_visibility_rate": co_visibility_rate,
            "visibility_index": visibility_index,
            "total_citations": total_citations,
            "client_mentions": client_mentions,
            "metadata": {
                "total_runs": total_runs,
                "successful_runs": successful_runs,
                "answer_ids": answer_ids,
            }
        }

    async def _compute_inclusion_rate(
        self,
        db: AsyncSession,
        client: models.Client,
        answer_ids: List[int],
    ) -> float:
        """
        % of queries where client's domain appears in citations
        """
        if not answer_ids:
            return 0.0

        # Count answers that cite the client's domain
        result = await db.execute(
            select(func.count(models.Citation.id)).where(
                and_(
                    models.Citation.answer_id.in_(answer_ids),
                    models.Citation.domain == client.domain
                )
            )
        )
        citing_answers = result.scalar()

        return (citing_answers / len(answer_ids)) * 100.0

    async def _compute_extraction_rate(
        self,
        db: AsyncSession,
        client: models.Client,
        answer_ids: List[int],
    ) -> float:
        """
        % of queries with text overlap ≥ similarity threshold
        """
        if not answer_ids:
            return 0.0

        # Count answers with high similarity matches
        result = await db.execute(
            select(func.count(func.distinct(models.Similarity.answer_id))).where(
                and_(
                    models.Similarity.answer_id.in_(answer_ids),
                    models.Similarity.similarity_score >= 0.82
                )
            )
        )
        similar_answers = result.scalar()

        return (similar_answers / len(answer_ids)) * 100.0

    async def _compute_presence_rate(
        self,
        db: AsyncSession,
        client: models.Client,
        answer_ids: List[int],
    ) -> float:
        """
        % of queries mentioning client/entity by name
        """
        if not answer_ids:
            return 0.0

        # Get client name and common entities
        client_names = [client.name.lower()]
        # TODO: Add entity names from WordPress entities

        # Count answers that mention client name
        mentioning_answers = 0

        for answer_id in answer_ids:
            result = await db.execute(
                select(models.Answer.raw_response).where(
                    models.Answer.id == answer_id
                )
            )
            answer = result.scalar_one_or_none()

            if answer and any(name in answer.lower() for name in client_names):
                mentioning_answers += 1

        return (mentioning_answers / len(answer_ids)) * 100.0

    async def _compute_co_visibility_rate(
        self,
        db: AsyncSession,
        client: models.Client,
        answer_ids: List[int],
    ) -> float:
        """
        % mentioning both client and another publisher
        """
        if not answer_ids:
            return 0.0

        # This is a simplified version - in practice, you'd need to identify
        # competitor/publisher mentions
        co_visible_answers = 0

        for answer_id in answer_ids:
            # Check if answer cites client domain AND at least one other domain
            result = await db.execute(
                select(func.count(func.distinct(models.Citation.domain))).where(
                    models.Citation.answer_id == answer_id
                )
            )
            domain_count = result.scalar()

            # Check if client domain is cited
            client_citation = await db.execute(
                select(func.count(models.Citation.id)).where(
                    and_(
                        models.Citation.answer_id == answer_id,
                        models.Citation.domain == client.domain
                    )
                )
            )
            has_client = client_citation.scalar() > 0

            if has_client and domain_count > 1:
                co_visible_answers += 1

        return (co_visible_answers / len(answer_ids)) * 100.0

    def _compute_visibility_index(
        self,
        inclusion_rate: float,
        extraction_rate: float,
        presence_rate: float,
        co_visibility_rate: float,
    ) -> float:
        """
        Weighted aggregate KPI
        """
        # Weights based on importance (can be adjusted)
        weights = {
            "inclusion": 0.4,
            "extraction": 0.3,
            "presence": 0.2,
            "co_visibility": 0.1,
        }

        return (
            inclusion_rate * weights["inclusion"] +
            extraction_rate * weights["extraction"] +
            presence_rate * weights["presence"] +
            co_visibility_rate * weights["co_visibility"]
        )

    def _empty_kpis(self) -> Dict[str, Any]:
        """
        Return empty KPI values
        """
        return {
            "inclusion_rate": 0.0,
            "extraction_rate": 0.0,
            "presence_rate": 0.0,
            "co_visibility_rate": 0.0,
            "visibility_index": 0.0,
            "total_citations": 0,
            "client_mentions": 0,
            "metadata": {},
        }

    async def _get_active_clients(self, db: AsyncSession) -> List[models.Client]:
        """Get all active clients"""
        result = await db.execute(
            select(models.Client).where(models.Client.is_active == True)
        )
        return result.scalars().all()

    async def _get_client_queries(self, db: AsyncSession, client_id: int) -> List[models.Query]:
        """Get all active queries for a client"""
        result = await db.execute(
            select(models.Query).where(
                and_(
                    models.Query.client_id == client_id,
                    models.Query.is_active == True
                )
            )
        )
        return result.scalars().all()

    async def _get_active_engines(self, db: AsyncSession, client_id: int, target_date: date) -> List[str]:
        """Get engines that had runs on the target date"""
        start_date = datetime.combine(target_date, datetime.min.time())
        end_date = datetime.combine(target_date, datetime.max.time())

        result = await db.execute(
            select(func.distinct(models.Run.engine)).where(
                and_(
                    models.Run.client_id == client_id,
                    models.Run.created_at >= start_date,
                    models.Run.created_at <= end_date
                )
            )
        )
        engines = result.scalars().all()
        return list(engines)

    async def _get_runs_for_date_range(
        self,
        db: AsyncSession,
        client_id: int,
        query_id: int,
        engine: str,
        start_date: datetime,
        end_date: datetime,
    ) -> List[models.Run]:
        """Get runs for the specified parameters and date range"""
        result = await db.execute(
            select(models.Run).where(
                and_(
                    models.Run.client_id == client_id,
                    models.Run.query_id == query_id,
                    models.Run.engine == engine,
                    models.Run.created_at >= start_date,
                    models.Run.created_at <= end_date
                )
            ).options(
                # Eager load answers to avoid N+1 queries
                select.joinedload(models.Run.answers)
            )
        )
        return result.scalars().all()

    async def _count_total_citations(self, db: AsyncSession, answer_ids: List[int]) -> int:
        """Count total citations across all answers"""
        if not answer_ids:
            return 0

        result = await db.execute(
            select(func.count(models.Citation.id)).where(
                models.Citation.answer_id.in_(answer_ids)
            )
        )
        return result.scalar()

    async def _count_client_mentions(self, db: AsyncSession, client: models.Client, answer_ids: List[int]) -> int:
        """Count answers that mention the client"""
        if not answer_ids:
            return 0

        # Simple text search for client name
        client_name_lower = client.name.lower()
        mention_count = 0

        for answer_id in answer_ids:
            result = await db.execute(
                select(models.Answer.raw_response).where(
                    models.Answer.id == answer_id
                )
            )
            answer = result.scalar_one_or_none()

            if answer and client_name_lower in answer.lower():
                mention_count += 1

        return mention_count


# Create service instance
kpi_service = KpiService()</content>
<parameter name="filePath">/Users/krisoldland/Documents/GitHub/1927MSuite/geo-tracker/app/services/kpi_service.py