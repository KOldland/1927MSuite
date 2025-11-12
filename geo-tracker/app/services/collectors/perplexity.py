"""
Perplexity AI collector
"""

from typing import Dict, Any, List
import structlog

from .base import BaseCollector, CollectorResult

logger = structlog.get_logger(__name__)


class PerplexityCollector(BaseCollector):
    """
    Collector for Perplexity AI search engine
    """

    def __init__(self, api_key: str):
        super().__init__(
            engine_name="perplexity",
            api_key=api_key,
            base_url="https://api.perplexity.ai"
        )
        # Perplexity rate limits: 5 requests per minute for free tier
        self.requests_per_minute = 5

    async def search(self, query: str) -> CollectorResult:
        """
        Search using Perplexity AI
        """
        try:
            logger.info("Searching with Perplexity", query=query)

            data = {
                "model": "pplx-7b-online",  # Use online model for web search
                "messages": [
                    {
                        "role": "user",
                        "content": f"Please search for and provide information about: {query}. Include sources and citations."
                    }
                ],
                "max_tokens": 1000,
                "temperature": 0.1,  # Low temperature for factual responses
            }

            response = await self._make_request("/chat/completions", method="POST", data=data)

            # Extract response content
            if "choices" in response and len(response["choices"]) > 0:
                raw_response = response["choices"][0]["message"]["content"]
            else:
                raw_response = str(response)

            # Extract citations from response (Perplexity doesn't provide structured citations)
            citations = self._extract_citations(raw_response)

            # Basic entity extraction (placeholder)
            entities = self._extract_entities(raw_response)

            metadata = {
                "model": response.get("model"),
                "usage": response.get("usage", {}),
                "request_id": response.get("id"),
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
                "Perplexity search completed",
                query=query,
                response_length=len(raw_response),
                citations_count=len(citations)
            )

            return result

        except Exception as e:
            logger.error(
                "Perplexity search failed",
                query=query,
                error=str(e),
                exc_info=True
            )
            raise

    async def get_answer(self, query: str) -> CollectorResult:
        """
        Get direct answer from Perplexity (same as search for this engine)
        """
        return await self.search(query)

    def _extract_citations(self, response: str) -> List[Dict[str, Any]]:
        """
        Extract citations from Perplexity response
        Note: Perplexity doesn't provide structured citations, so this is basic URL extraction
        """
        citations = []
        import re

        # Extract URLs from the response
        url_pattern = r'https?://(?:[-\w.])+(?:[:\d]+)?(?:/(?:[\w/_.])*(?:\?(?:[\w&=%.])*)?(?:#(?:\w*))?)?'
        urls = re.findall(url_pattern, response)

        for i, url in enumerate(set(urls)):  # Remove duplicates
            citations.append({
                "url": url,
                "domain": self._extract_domain(url),
                "title": f"Source {i+1}",
                "snippet": "",
                "position": i,
            })

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
<parameter name="filePath">/Users/krisoldland/Documents/GitHub/1927MSuite/geo-tracker/app/services/collectors/perplexity.py