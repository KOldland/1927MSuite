"""
Base collector class for AI engines
"""

import asyncio
import time
from abc import ABC, abstractmethod
from typing import Dict, Any, Optional, List
from datetime import datetime

import httpx
import structlog

from app.core.config import settings

logger = structlog.get_logger(__name__)


class BaseCollector(ABC):
    """
    Abstract base class for AI engine collectors
    """

    def __init__(self, engine_name: str, api_key: str, base_url: str):
        self.engine_name = engine_name
        self.api_key = api_key
        self.base_url = base_url
        self.timeout = settings.COLLECTOR_TIMEOUT
        self.max_retries = settings.COLLECTOR_MAX_RETRIES
        self.backoff_factor = settings.COLLECTOR_BACKOFF_FACTOR

        # Rate limiting
        self.requests_per_minute = 60  # Default, override in subclasses
        self.request_times: List[float] = []

    async def _rate_limit_wait(self) -> None:
        """
        Implement rate limiting with sliding window
        """
        now = time.time()
        # Remove requests older than 1 minute
        self.request_times = [t for t in self.request_times if now - t < 60]

        if len(self.request_times) >= self.requests_per_minute:
            # Wait until the oldest request is more than 1 minute old
            wait_time = 60 - (now - self.request_times[0])
            if wait_time > 0:
                logger.info(
                    "Rate limiting",
                    engine=self.engine_name,
                    wait_time=wait_time
                )
                await asyncio.sleep(wait_time)

        self.request_times.append(now)

    async def _make_request(
        self,
        endpoint: str,
        method: str = "GET",
        data: Optional[Dict[str, Any]] = None,
        headers: Optional[Dict[str, str]] = None,
    ) -> Dict[str, Any]:
        """
        Make HTTP request with retry logic and exponential backoff
        """
        url = f"{self.base_url}{endpoint}"

        default_headers = {
            "Authorization": f"Bearer {self.api_key}",
            "Content-Type": "application/json",
        }
        if headers:
            default_headers.update(headers)

        for attempt in range(self.max_retries):
            try:
                await self._rate_limit_wait()

                async with httpx.AsyncClient(timeout=self.timeout) as client:
                    if method.upper() == "POST":
                        response = await client.post(url, json=data, headers=default_headers)
                    else:
                        response = await client.get(url, params=data, headers=default_headers)

                    response.raise_for_status()
                    return response.json()

            except httpx.HTTPStatusError as e:
                if e.response.status_code == 429:  # Rate limited
                    wait_time = self.backoff_factor ** attempt
                    logger.warning(
                        "Rate limited, backing off",
                        engine=self.engine_name,
                        attempt=attempt + 1,
                        wait_time=wait_time,
                        status_code=e.response.status_code
                    )
                    await asyncio.sleep(wait_time)
                    continue
                elif e.response.status_code >= 500:  # Server error
                    wait_time = self.backoff_factor ** attempt
                    logger.warning(
                        "Server error, retrying",
                        engine=self.engine_name,
                        attempt=attempt + 1,
                        wait_time=wait_time,
                        status_code=e.response.status_code
                    )
                    await asyncio.sleep(wait_time)
                    continue
                else:
                    logger.error(
                        "HTTP error",
                        engine=self.engine_name,
                        status_code=e.response.status_code,
                        response=e.response.text
                    )
                    raise

            except (httpx.TimeoutException, httpx.ConnectError) as e:
                wait_time = self.backoff_factor ** attempt
                logger.warning(
                    "Network error, retrying",
                    engine=self.engine_name,
                    attempt=attempt + 1,
                    wait_time=wait_time,
                    error=str(e)
                )
                await asyncio.sleep(wait_time)
                continue

            except Exception as e:
                logger.error(
                    "Unexpected error",
                    engine=self.engine_name,
                    attempt=attempt + 1,
                    error=str(e)
                )
                if attempt == self.max_retries - 1:
                    raise
                await asyncio.sleep(self.backoff_factor ** attempt)

        raise Exception(f"Failed to make request after {self.max_retries} attempts")

    @abstractmethod
    async def search(self, query: str) -> Dict[str, Any]:
        """
        Search using the AI engine
        """
        pass

    @abstractmethod
    async def get_answer(self, query: str) -> Dict[str, Any]:
        """
        Get direct answer from the AI engine
        """
        pass


class CollectorResult:
    """
    Standardized result from collectors
    """

    def __init__(
        self,
        engine: str,
        query: str,
        raw_response: str,
        citations: List[Dict[str, Any]] = None,
        entities: List[Dict[str, Any]] = None,
        metadata: Dict[str, Any] = None,
    ):
        self.engine = engine
        self.query = query
        self.raw_response = raw_response
        self.citations = citations or []
        self.entities = entities or []
        self.metadata = metadata or {}
        self.timestamp = datetime.utcnow()

    def to_dict(self) -> Dict[str, Any]:
        return {
            "engine": self.engine,
            "query": self.query,
            "raw_response": self.raw_response,
            "citations": self.citations,
            "entities": self.entities,
            "metadata": self.metadata,
            "timestamp": self.timestamp.isoformat(),
        }</content>
<parameter name="filePath">/Users/krisoldland/Documents/GitHub/1927MSuite/geo-tracker/app/services/collectors/base.py