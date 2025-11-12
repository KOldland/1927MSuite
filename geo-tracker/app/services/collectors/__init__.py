"""
AI Engine Collectors
"""

from typing import Optional
from .base import BaseCollector, CollectorResult
from .perplexity import PerplexityCollector
from .brave import BraveCollector


class CollectorFactory:
    """
    Factory for creating AI engine collectors
    """

    @staticmethod
    def create_collector(engine: str, api_key: str) -> BaseCollector:
        """
        Create a collector instance for the specified engine
        """
        engine = engine.lower()

        if engine == "perplexity":
            return PerplexityCollector(api_key)
        elif engine == "brave":
            return BraveCollector(api_key)
        else:
            raise ValueError(f"Unsupported engine: {engine}")


__all__ = [
    "BaseCollector",
    "CollectorResult",
    "PerplexityCollector",
    "BraveCollector",
    "CollectorFactory",
]</content>
<parameter name="filePath">/Users/krisoldland/Documents/GitHub/1927MSuite/geo-tracker/app/services/collectors/__init__.py