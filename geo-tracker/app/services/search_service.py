"""
Search service for orchestrating AI engine collectors
"""

from typing import List, Dict, Any, Optional
from datetime import datetime
import hashlib

from sqlalchemy.ext.asyncio import AsyncSession
import structlog

from app import models, schemas
from app.services.collectors import CollectorFactory, CollectorResult
from app.core.config import settings

logger = structlog.get_logger(__name__)


class SearchService:
    """
    Service for managing search runs across multiple AI engines
    """

    def __init__(self):
        self.collector_factory = CollectorFactory()

    async def run_search(
        self,
        db: AsyncSession,
        run: models.Run,
        query: models.Query,
    ) -> models.Run:
        """
        Execute a search run using the specified engine
        """
        try:
            logger.info(
                "Starting search run",
                run_id=run.id,
                engine=run.engine,
                query=query.query_text
            )

            # Update run status to running
            run.status = "running"
            run.started_at = datetime.utcnow()
            db.add(run)
            await db.commit()

            # Get API key for the engine
            api_key = self._get_api_key(run.engine)
            if not api_key:
                raise ValueError(f"No API key configured for engine: {run.engine}")

            # Create collector
            collector = self.collector_factory.create_collector(run.engine, api_key)

            # Execute search
            result = await collector.search(query.query_text)

            # Store the result
            await self._store_result(db, run, result)

            # Update run status to completed
            run.status = "completed"
            run.completed_at = datetime.utcnow()
            db.add(run)
            await db.commit()

            logger.info(
                "Search run completed",
                run_id=run.id,
                engine=run.engine,
                response_length=len(result.raw_response)
            )

            return run

        except Exception as e:
            logger.error(
                "Search run failed",
                run_id=run.id,
                engine=run.engine,
                error=str(e),
                exc_info=True
            )

            # Update run status to failed
            run.status = "failed"
            run.error_message = str(e)
            run.completed_at = datetime.utcnow()
            db.add(run)
            await db.commit()

            raise

    async def _store_result(
        self,
        db: AsyncSession,
        run: models.Run,
        result: CollectorResult,
    ) -> None:
        """
        Store the collector result in the database
        """
        # Create answer record
        response_hash = hashlib.sha256(result.raw_response.encode()).hexdigest()

        answer = models.Answer(
            run_id=run.id,
            raw_response=result.raw_response,
            normalized_response=self._normalize_response(result.raw_response),
            response_hash=response_hash,
            metadata_json=result.metadata,
        )
        db.add(answer)
        await db.flush()  # Get the answer ID

        # Store citations
        for citation_data in result.citations:
            citation = models.Citation(
                answer_id=answer.id,
                url=citation_data["url"],
                domain=citation_data["domain"],
                title=citation_data.get("title", ""),
                snippet=citation_data.get("snippet", ""),
                position=citation_data.get("position", 0),
            )
            db.add(citation)

        # Store entities
        for entity_data in result.entities:
            entity = models.Entity(
                answer_id=answer.id,
                entity_text=entity_data["entity_text"],
                entity_type=entity_data["entity_type"],
                confidence=entity_data.get("confidence"),
                start_position=entity_data.get("start_position"),
                end_position=entity_data.get("end_position"),
            )
            db.add(entity)

        await db.commit()

    def _normalize_response(self, raw_response: str) -> Optional[str]:
        """
        Normalize the response text for comparison
        """
        if not raw_response:
            return None

        # Basic normalization: lowercase, remove extra whitespace
        normalized = raw_response.lower().strip()
        # Remove multiple spaces
        import re
        normalized = re.sub(r'\s+', ' ', normalized)

        return normalized

    def _get_api_key(self, engine: str) -> Optional[str]:
        """
        Get API key for the specified engine
        """
        engine = engine.lower()

        if engine == "perplexity":
            return settings.PERPLEXITY_API_KEY
        elif engine == "brave":
            return settings.BRAVE_API_KEY
        elif engine == "bing":
            return settings.BING_API_KEY
        else:
            return None

    async def get_supported_engines(self) -> List[str]:
        """
        Get list of supported AI engines
        """
        return ["perplexity", "brave", "bing", "google_sge"]


# Create service instance
search_service = SearchService()</content>
<parameter name="filePath">/Users/krisoldland/Documents/GitHub/1927MSuite/geo-tracker/app/services/search_service.py