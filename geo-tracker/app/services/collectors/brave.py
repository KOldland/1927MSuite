"""
Brave Search collector
"""

from typing import Dict, Any, List
import structlog

from .base import BaseCollector, CollectorResult

logger = structlog.get_logger(__name__)


class BraveCollector(BaseCollector):
    """
    Collector for Brave Search engine
    """

    def __init__(self, api_key: str):
        super().__init__(
            engine_name="brave",
            api_key=api_key,
            base_url="https://api.search.brave.com/res/v1"
        )
        # Brave Search rate limits: 1 request per second, 1000 per month for free tier
        self.requests_per_minute = 60

    async def search(self, query: str) -> CollectorResult:
        """
        Search using Brave Search API
        """
        try:
            logger.info("Searching with Brave", query=query)

            params = {
                "q": query,
                "count": 10,  # Number of results
                "offset": 0,
                "safesearch": "moderate",
                "format": "json",
            }

            response = await self._make_request("/web/search", method="GET", data=params)

            # Format the response as a readable answer
            raw_response = self._format_brave_response(response)

            # Extract citations from Brave results
            citations = self._extract_brave_citations(response)

            # Extract entities (placeholder)
            entities = self._extract_entities(raw_response)

            metadata = {
                "total_results": response.get("query", {}).get("total", 0),
                "query_time": response.get("query", {}).get("time", 0),
                "query_type": response.get("query", {}).get("type"),
            }

            result = CollectorResult(
                engine=self.engine_name,
                query=query,
                raw_response=raw_response,
                citations=citations,
                entities=entities,
                metadata=metadata,
            )

            logger.info(
                "Brave search completed",
                query=query,
                response_length=len(raw_response),
                citations_count=len(citations)
            )

            return result

        except Exception as e:
            logger.error(
                "Brave search failed",
                query=query,
                error=str(e),
                exc_info=True
            )
            raise

    async def get_answer(self, query: str) -> CollectorResult:
        """
        Get direct answer from Brave (same as search for this engine)
        """
        return await self.search(query)

    def _format_brave_response(self, response: Dict[str, Any]) -> str:
        """
        Format Brave search results into a readable response
        """
        if "web" not in response or "results" not in response["web"]:
            return "No search results found."

        results = response["web"]["results"]
        formatted = f"Search results for: {response.get('query', {}).get('original', 'query')}\n\n"

        for i, result in enumerate(results[:5], 1):  # Limit to top 5
            title = result.get("title", "No title")
            url = result.get("url", "")
            description = result.get("description", "No description")

            formatted += f"{i}. {title}\n"
            formatted += f"   URL: {url}\n"
            formatted += f"   {description}\n\n"

        return formatted

    def _extract_brave_citations(self, response: Dict[str, Any]) -> List[Dict[str, Any]]:
        """
        Extract citations from Brave search results
        """
        citations = []

        if "web" in response and "results" in response["web"]:
            for i, result in enumerate(response["web"]["results"]):
                citation = {
                    "url": result.get("url", ""),
                    "domain": self._extract_domain(result.get("url", "")),
                    "title": result.get("title", ""),
                    "snippet": result.get("description", ""),
                    "position": i,
                }
                citations.append(citation)

        return citations

    def _extract_entities(self, response: str) -> List[Dict[str, Any]]:
        """
        Basic entity extraction from response
        This is a placeholder - in production, use proper NER
        """
        entities = []
        # Simple pattern matching for demonstration
        import re

        # Extract potential person names (basic pattern)
        person_pattern = r'\b[A-Z][a-z]+ [A-Z][a-z]+\b'
        persons = re.findall(person_pattern, response)

        for person in set(persons):
            entities.append({
                "entity_text": person,
                "entity_type": "PERSON",
                "confidence": 0.5,  # Placeholder confidence
            })

        # Extract potential organizations
        org_pattern = r'\b[A-Z][A-Z&\s]+\b'
        orgs = re.findall(org_pattern, response)

        for org in set(orgs):
            if len(org) > 3:  # Filter out short matches
                entities.append({
                    "entity_text": org,
                    "entity_type": "ORG",
                    "confidence": 0.4,  # Placeholder confidence
                })

        return entities

    def _extract_domain(self, url: str) -> str:
        """
        Extract domain from URL
        """
        try:
            from urllib.parse import urlparse
            parsed = urlparse(url)
            return parsed.netloc
        except:
            return url</content>
<parameter name="filePath">/Users/krisoldland/Documents/GitHub/1927MSuite/geo-tracker/app/services/collectors/brave.py