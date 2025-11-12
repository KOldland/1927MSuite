"""
Post synchronization service
"""

from datetime import datetime
from typing import List, Dict, Any
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, update, insert
from sqlalchemy.exc import IntegrityError
from fastapi import HTTPException, status

from app import schemas, models
import structlog

logger = structlog.get_logger(__name__)


class PostService:
    """
    Service for handling post synchronization from WordPress
    """

    @staticmethod
    async def sync_posts(
        db: AsyncSession,
        client_id: int,
        sync_data: schemas.PostSyncRequest,
    ) -> schemas.PostSyncResponse:
        """
        Sync posts, entities, and AnswerCards from WordPress

        Implements tenant scoping - all data is scoped to the client_id
        """
        try:
            posts_processed = len(sync_data.posts) if sync_data.posts else 0
            entities_processed = len(sync_data.entities) if sync_data.entities else 0
            answer_cards_processed = len(sync_data.answer_cards) if sync_data.answer_cards else 0

            logger.info(
                "Processing sync data",
                client_id=client_id,
                posts=posts_processed,
                entities=entities_processed,
                answer_cards=answer_cards_processed,
            )

            # Process posts
            posts_inserted = 0
            posts_updated = 0
            if sync_data.posts:
                for post_data in sync_data.posts:
                    # Check if post exists
                    existing_post = await db.execute(
                        select(models.Post).where(
                            models.Post.id == post_data.id,
                            models.Post.client_id == client_id
                        )
                    )
                    existing_post = existing_post.scalar_one_or_none()

                    if existing_post:
                        # Update existing post
                        await db.execute(
                            update(models.Post).where(
                                models.Post.id == post_data.id,
                                models.Post.client_id == client_id
                            ).values(
                                title=post_data.title,
                                content=post_data.content,
                                excerpt=post_data.excerpt,
                                url=post_data.url,
                                slug=post_data.slug,
                                status=post_data.status,
                                post_type=post_data.post_type,
                                author_id=post_data.author_id,
                                author_name=post_data.author_name,
                                published_at=post_data.published_at,
                                modified_at=post_data.modified_at,
                                categories=post_data.categories,
                                tags=post_data.tags,
                                featured_image_url=post_data.featured_image_url,
                                metadata=post_data.metadata,
                                updated_at=datetime.utcnow(),
                            )
                        )
                        posts_updated += 1
                    else:
                        # Insert new post
                        db.add(models.Post(
                            id=post_data.id,
                            client_id=client_id,
                            title=post_data.title,
                            content=post_data.content,
                            excerpt=post_data.excerpt,
                            url=post_data.url,
                            slug=post_data.slug,
                            status=post_data.status,
                            post_type=post_data.post_type,
                            author_id=post_data.author_id,
                            author_name=post_data.author_name,
                            published_at=post_data.published_at,
                            modified_at=post_data.modified_at,
                            categories=post_data.categories,
                            tags=post_data.tags,
                            featured_image_url=post_data.featured_image_url,
                            metadata=post_data.metadata,
                        ))
                        posts_inserted += 1

            # Process entities
            entities_inserted = 0
            entities_updated = 0
            if sync_data.entities:
                for entity_data in sync_data.entities:
                    # Check if entity exists
                    existing_entity = await db.execute(
                        select(models.WordPressEntity).where(
                            models.WordPressEntity.id == entity_data.id,
                            models.WordPressEntity.client_id == client_id
                        )
                    )
                    existing_entity = existing_entity.scalar_one_or_none()

                    if existing_entity:
                        # Update existing entity
                        await db.execute(
                            update(models.WordPressEntity).where(
                                models.WordPressEntity.id == entity_data.id,
                                models.WordPressEntity.client_id == client_id
                            ).values(
                                name=entity_data.name,
                                type=entity_data.type,
                                description=entity_data.description,
                                wikipedia_url=entity_data.wikipedia_url,
                                confidence=entity_data.confidence,
                                metadata=entity_data.metadata,
                                updated_at=datetime.utcnow(),
                            )
                        )
                        entities_updated += 1
                    else:
                        # Insert new entity
                        db.add(models.WordPressEntity(
                            id=entity_data.id,
                            client_id=client_id,
                            post_id=entity_data.post_id,
                            name=entity_data.name,
                            type=entity_data.type,
                            description=entity_data.description,
                            wikipedia_url=entity_data.wikipedia_url,
                            confidence=entity_data.confidence,
                            metadata=entity_data.metadata,
                        ))
                        entities_inserted += 1

            # Process answer cards
            answer_cards_inserted = 0
            answer_cards_updated = 0
            if sync_data.answer_cards:
                for card_data in sync_data.answer_cards:
                    # Check if answer card exists
                    existing_card = await db.execute(
                        select(models.AnswerCard).where(
                            models.AnswerCard.id == card_data.id,
                            models.AnswerCard.client_id == client_id
                        )
                    )
                    existing_card = existing_card.scalar_one_or_none()

                    if existing_card:
                        # Update existing answer card
                        await db.execute(
                            update(models.AnswerCard).where(
                                models.AnswerCard.id == card_data.id,
                                models.AnswerCard.client_id == client_id
                            ).values(
                                question=card_data.question,
                                answer=card_data.answer,
                                category=card_data.category,
                                tags=card_data.tags,
                                priority=card_data.priority,
                                is_active=card_data.is_active,
                                metadata=card_data.metadata,
                                updated_at=datetime.utcnow(),
                            )
                        )
                        answer_cards_updated += 1
                    else:
                        # Insert new answer card
                        db.add(models.AnswerCard(
                            id=card_data.id,
                            client_id=client_id,
                            question=card_data.question,
                            answer=card_data.answer,
                            category=card_data.category,
                            tags=card_data.tags,
                            priority=card_data.priority,
                            is_active=card_data.is_active,
                            metadata=card_data.metadata,
                        ))
                        answer_cards_inserted += 1

            # Commit all changes
            await db.commit()

            logger.info(
                "Sync completed successfully",
                client_id=client_id,
                posts_inserted=posts_inserted,
                posts_updated=posts_updated,
                entities_inserted=entities_inserted,
                entities_updated=entities_updated,
                answer_cards_inserted=answer_cards_inserted,
                answer_cards_updated=answer_cards_updated,
            )

            return schemas.PostSyncResponse(
                success=True,
                posts_processed=posts_processed,
                entities_processed=entities_processed,
                answer_cards_processed=answer_cards_processed,
                message="Sync completed successfully",
                sync_timestamp=datetime.utcnow(),
            )

        except IntegrityError as e:
            await db.rollback()
            logger.error(
                "Database integrity error during sync",
                client_id=client_id,
                error=str(e),
                exc_info=True,
            )
            raise HTTPException(
                status_code=status.HTTP_400_BAD_REQUEST,
                detail="Data integrity violation during sync",
            )
        except Exception as e:
            await db.rollback()
            logger.error(
                "Post sync failed",
                client_id=client_id,
                error=str(e),
                exc_info=True,
            )
            raise

    @staticmethod
    async def get_sync_status(
        db: AsyncSession,
        client_id: int,
    ) -> Dict[str, Any]:
        """
        Get synchronization status for a client
        """
        try:
            # Count posts
            posts_result = await db.execute(
                select(models.Post).where(models.Post.client_id == client_id)
            )
            total_posts = len(posts_result.scalars().all())

            # Count entities
            entities_result = await db.execute(
                select(models.WordPressEntity).where(models.WordPressEntity.client_id == client_id)
            )
            total_entities = len(entities_result.scalars().all())

            # Count answer cards
            answer_cards_result = await db.execute(
                select(models.AnswerCard).where(models.AnswerCard.client_id == client_id)
            )
            total_answer_cards = len(answer_cards_result.scalars().all())

            # Get last sync timestamp (most recent post update)
            last_sync_result = await db.execute(
                select(models.Post.updated_at).where(
                    models.Post.client_id == client_id
                ).order_by(models.Post.updated_at.desc()).limit(1)
            )
            last_sync = last_sync_result.scalar_one_or_none()

            return {
                "client_id": client_id,
                "last_sync": last_sync.isoformat() if last_sync else None,
                "total_posts": total_posts,
                "total_entities": total_entities,
                "total_answer_cards": total_answer_cards,
                "status": "active",
            }

        except Exception as e:
            logger.error(
                "Failed to get sync status",
                client_id=client_id,
                error=str(e),
                exc_info=True,
            )
            raise


# Create service instance
post_service = PostService()