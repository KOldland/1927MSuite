"""
Similarity engine for matching AI responses to WordPress content
"""

from typing import List, Dict, Any, Optional, Tuple
import math
from collections import Counter

import openai
from openai import AsyncOpenAI
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select
import structlog

from app import models
from app.core.config import settings

logger = structlog.get_logger(__name__)


class SimilarityEngine:
    """
    Engine for computing similarity between AI responses and WordPress content
    """

    def __init__(self):
        self.client = AsyncOpenAI(api_key=settings.OPENAI_API_KEY)
        self.model = settings.OPENAI_MODEL or "text-embedding-3-small"
        self.similarity_threshold = 0.82  # Cosine similarity threshold
        self.jaccard_threshold = 0.15     # Jaccard n-gram threshold as backup

    async def compute_similarities(
        self,
        db: AsyncSession,
        answer: models.Answer,
    ) -> List[models.Similarity]:
        """
        Compute similarities between an answer and all WordPress posts for its client
        """
        try:
            logger.info(
                "Computing similarities",
                answer_id=answer.id,
                client_id=answer.run.client_id
            )

            # Get all posts for this client
            posts = await self._get_client_posts(db, answer.run.client_id)

            similarities = []

            # Get embedding for the answer
            answer_embedding = await self._get_embedding(answer.raw_response)

            for post in posts:
                # Compute similarity scores
                embedding_similarity = await self._compute_embedding_similarity(
                    answer_embedding, post.content
                )

                jaccard_similarity = self._compute_jaccard_similarity(
                    answer.raw_response, post.content
                )

                # Use embedding similarity as primary, Jaccard as backup
                final_similarity = embedding_similarity
                similarity_type = "embedding"

                if embedding_similarity < self.similarity_threshold:
                    # If embedding similarity is too low, try Jaccard
                    if jaccard_similarity >= self.jaccard_threshold:
                        final_similarity = jaccard_similarity
                        similarity_type = "ngram"
                    else:
                        final_similarity = max(embedding_similarity, jaccard_similarity)
                        similarity_type = "combined"

                # Only store if similarity meets minimum threshold
                if final_similarity >= min(self.similarity_threshold, self.jaccard_threshold):
                    # Find matched text (simple approach)
                    matched_text = self._find_matched_text(
                        answer.raw_response, post.content, similarity_type
                    )

                    similarity = models.Similarity(
                        answer_id=answer.id,
                        post_id=post.id,
                        similarity_score=float(final_similarity),
                        similarity_type=similarity_type,
                        matched_text=matched_text,
                        metadata_json={
                            "embedding_similarity": float(embedding_similarity),
                            "jaccard_similarity": float(jaccard_similarity),
                            "answer_length": len(answer.raw_response),
                            "post_length": len(post.content),
                        }
                    )
                    similarities.append(similarity)

            # Store similarities in database
            for similarity in similarities:
                db.add(similarity)

            await db.commit()

            logger.info(
                "Similarities computed",
                answer_id=answer.id,
                similarities_found=len(similarities)
            )

            return similarities

        except Exception as e:
            logger.error(
                "Similarity computation failed",
                answer_id=answer.id,
                error=str(e),
                exc_info=True
            )
            raise

    async def _get_client_posts(
        self,
        db: AsyncSession,
        client_id: int,
    ) -> List[models.Post]:
        """
        Get all published posts for a client
        """
        result = await db.execute(
            select(models.Post).where(
                models.Post.client_id == client_id,
                models.Post.status == "publish"
            )
        )
        return result.scalars().all()

    async def _get_embedding(self, text: str) -> List[float]:
        """
        Get OpenAI embedding for text
        """
        try:
            # Truncate text if too long (OpenAI has token limits)
            truncated_text = text[:8000]  # Rough character limit

            response = await self.client.embeddings.create(
                input=truncated_text,
                model=self.model
            )

            return response.data[0].embedding

        except Exception as e:
            logger.error(
                "Failed to get embedding",
                error=str(e),
                text_length=len(text)
            )
            raise

    async def _compute_embedding_similarity(
        self,
        answer_embedding: List[float],
        post_content: str,
    ) -> float:
        """
        Compute cosine similarity between answer embedding and post content
        """
        try:
            post_embedding = await self._get_embedding(post_content)
            return self._cosine_similarity(answer_embedding, post_embedding)

        except Exception as e:
            logger.warning(
                "Failed to compute embedding similarity",
                error=str(e)
            )
            return 0.0

    def _cosine_similarity(self, vec1: List[float], vec2: List[float]) -> float:
        """
        Calculate cosine similarity between two vectors
        """
        try:
            dot_product = sum(a * b for a, b in zip(vec1, vec2))
            norm1 = math.sqrt(sum(a * a for a in vec1))
            norm2 = math.sqrt(sum(b * b for b in vec2))

            if norm1 == 0 or norm2 == 0:
                return 0.0

            return dot_product / (norm1 * norm2)

        except Exception as e:
            logger.error("Cosine similarity calculation failed", error=str(e))
            return 0.0

    def _compute_jaccard_similarity(self, text1: str, text2: str) -> float:
        """
        Compute Jaccard similarity using n-grams
        """
        try:
            # Convert to lowercase and split into words
            words1 = set(text1.lower().split())
            words2 = set(text2.lower().split())

            # Compute n-grams (2-grams and 3-grams)
            ngrams1 = self._get_ngrams(text1.lower(), 2) | self._get_ngrams(text1.lower(), 3)
            ngrams2 = self._get_ngrams(text2.lower(), 2) | self._get_ngrams(text2.lower(), 3)

            # Jaccard similarity
            intersection = len(ngrams1 & ngrams2)
            union = len(ngrams1 | ngrams2)

            if union == 0:
                return 0.0

            return intersection / union

        except Exception as e:
            logger.error("Jaccard similarity calculation failed", error=str(e))
            return 0.0

    def _get_ngrams(self, text: str, n: int) -> set:
        """
        Generate n-grams from text
        """
        words = text.split()
        ngrams = set()

        for i in range(len(words) - n + 1):
            ngram = " ".join(words[i:i + n])
            ngrams.add(ngram)

        return ngrams

    def _find_matched_text(
        self,
        answer_text: str,
        post_content: str,
        similarity_type: str,
    ) -> Optional[str]:
        """
        Find the text segment that matched
        """
        try:
            if similarity_type == "ngram":
                # Find common n-grams
                answer_ngrams = self._get_ngrams(answer_text.lower(), 2)
                post_ngrams = self._get_ngrams(post_content.lower(), 2)

                common_ngrams = answer_ngrams & post_ngrams
                if common_ngrams:
                    return " ".join(list(common_ngrams)[:3])  # Return first 3 matches

            # For embedding similarity, return a sample of overlapping words
            answer_words = set(answer_text.lower().split())
            post_words = set(post_content.lower().split())

            common_words = answer_words & post_words
            if len(common_words) > 5:
                return " ".join(list(common_words)[:10])

            return None

        except Exception as e:
            logger.warning("Failed to find matched text", error=str(e))
            return None


# Create service instance
similarity_engine = SimilarityEngine()</content>
<parameter name="filePath">/Users/krisoldland/Documents/GitHub/1927MSuite/geo-tracker/app/services/similarity_service.py