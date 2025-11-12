"""Initial schema with all models

Revision ID: 001
Revises: 
Create Date: 2025-11-12 14:24:23.594537

"""
from typing import Sequence, Union

from alembic import op
import sqlalchemy as sa


# revision identifiers, used by Alembic.
revision: str = '001'
down_revision: Union[str, None] = None
branch_labels: Union[str, Sequence[str], None] = None
depends_on: Union[str, Sequence[str], None] = None


def upgrade() -> None:
    # Create clients table
    op.create_table('clients',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('name', sa.String(length=255), nullable=False),
        sa.Column('domain', sa.String(length=255), nullable=False),
        sa.Column('wordpress_url', sa.String(length=500), nullable=False),
        sa.Column('jwt_secret', sa.String(length=500), nullable=False),
        sa.Column('is_active', sa.Boolean(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=True),
        sa.Column('updated_at', sa.DateTime(), nullable=True),
        sa.PrimaryKeyConstraint('id'),
        sa.UniqueConstraint('domain')
    )
    op.create_index('idx_client_domain_active', 'clients', ['domain', 'is_active'], unique=False)

    # Create queries table
    op.create_table('queries',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('client_id', sa.Integer(), nullable=False),
        sa.Column('query_text', sa.String(length=1000), nullable=False),
        sa.Column('topic', sa.String(length=255), nullable=False),
        sa.Column('is_active', sa.Boolean(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=True),
        sa.Column('updated_at', sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(['client_id'], ['clients.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index('idx_query_client_topic', 'queries', ['client_id', 'topic'], unique=False)
    op.create_index('idx_query_active', 'queries', ['is_active'], unique=False)

    # Create runs table
    op.create_table('runs',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('client_id', sa.Integer(), nullable=False),
        sa.Column('query_id', sa.Integer(), nullable=False),
        sa.Column('engine', sa.String(length=50), nullable=False),
        sa.Column('status', sa.String(length=20), nullable=False),
        sa.Column('started_at', sa.DateTime(), nullable=True),
        sa.Column('completed_at', sa.DateTime(), nullable=True),
        sa.Column('error_message', sa.Text(), nullable=True),
        sa.Column('metadata_json', sa.JSON(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(['client_id'], ['clients.id'], ),
        sa.ForeignKeyConstraint(['query_id'], ['queries.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index('idx_run_client_query', 'runs', ['client_id', 'query_id'], unique=False)
    op.create_index('idx_run_engine_status', 'runs', ['engine', 'status'], unique=False)
    op.create_index('idx_run_created_at', 'runs', ['created_at'], unique=False)

    # Create answers table
    op.create_table('answers',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('run_id', sa.Integer(), nullable=False),
        sa.Column('raw_response', sa.Text(), nullable=False),
        sa.Column('normalized_response', sa.Text(), nullable=True),
        sa.Column('response_hash', sa.String(length=64), nullable=False),
        sa.Column('metadata_json', sa.JSON(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(['run_id'], ['runs.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index('idx_answer_run_id', 'answers', ['run_id'], unique=False)
    op.create_index('idx_answer_hash', 'answers', ['response_hash'], unique=False)

    # Create citations table
    op.create_table('citations',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('answer_id', sa.Integer(), nullable=False),
        sa.Column('url', sa.String(length=2000), nullable=False),
        sa.Column('domain', sa.String(length=255), nullable=False),
        sa.Column('title', sa.String(length=1000), nullable=True),
        sa.Column('snippet', sa.Text(), nullable=True),
        sa.Column('position', sa.Integer(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(['answer_id'], ['answers.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index('idx_citation_domain', 'citations', ['domain'], unique=False)
    op.create_index('idx_citation_answer_id', 'citations', ['answer_id'], unique=False)

    # Create entities table
    op.create_table('entities',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('answer_id', sa.Integer(), nullable=False),
        sa.Column('entity_text', sa.String(length=500), nullable=False),
        sa.Column('entity_type', sa.String(length=50), nullable=False),
        sa.Column('confidence', sa.Float(), nullable=True),
        sa.Column('start_position', sa.Integer(), nullable=True),
        sa.Column('end_position', sa.Integer(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(['answer_id'], ['answers.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index('idx_entity_type', 'entities', ['entity_type'], unique=False)
    op.create_index('idx_entity_answer_id', 'entities', ['answer_id'], unique=False)

    # Create similarities table
    op.create_table('similarities',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('answer_id', sa.Integer(), nullable=False),
        sa.Column('post_id', sa.String(length=100), nullable=False),
        sa.Column('similarity_score', sa.Float(), nullable=False),
        sa.Column('similarity_type', sa.String(length=20), nullable=False),
        sa.Column('matched_text', sa.Text(), nullable=True),
        sa.Column('metadata_json', sa.JSON(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(['answer_id'], ['answers.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index('idx_similarity_answer_post', 'similarities', ['answer_id', 'post_id'], unique=False)
    op.create_index('idx_similarity_score', 'similarities', ['similarity_score'], unique=False)
    op.create_index('idx_similarity_type', 'similarities', ['similarity_type'], unique=False)

    # Create metrics table
    op.create_table('metrics',
        sa.Column('id', sa.Integer(), nullable=False),
        sa.Column('client_id', sa.Integer(), nullable=False),
        sa.Column('query_id', sa.Integer(), nullable=True),
        sa.Column('engine', sa.String(length=50), nullable=False),
        sa.Column('date', sa.DateTime(), nullable=False),
        sa.Column('inclusion_rate', sa.Float(), nullable=True),
        sa.Column('extraction_rate', sa.Float(), nullable=True),
        sa.Column('presence_rate', sa.Float(), nullable=True),
        sa.Column('co_visibility_rate', sa.Float(), nullable=True),
        sa.Column('visibility_index', sa.Float(), nullable=True),
        sa.Column('total_queries', sa.Integer(), nullable=True),
        sa.Column('successful_runs', sa.Integer(), nullable=True),
        sa.Column('total_citations', sa.Integer(), nullable=True),
        sa.Column('client_mentions', sa.Integer(), nullable=True),
        sa.Column('metadata_json', sa.JSON(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=True),
        sa.Column('updated_at', sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(['client_id'], ['clients.id'], ),
        sa.ForeignKeyConstraint(['query_id'], ['queries.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index('idx_metric_client_date', 'metrics', ['client_id', 'date'], unique=False)
    op.create_index('idx_metric_engine_date', 'metrics', ['engine', 'date'], unique=False)
    op.create_index('idx_metric_query_date', 'metrics', ['query_id', 'date'], unique=False)

    # Create posts table
    op.create_table('posts',
        sa.Column('id', sa.String(length=100), nullable=False),
        sa.Column('client_id', sa.Integer(), nullable=False),
        sa.Column('title', sa.String(length=1000), nullable=False),
        sa.Column('content', sa.Text(), nullable=False),
        sa.Column('excerpt', sa.Text(), nullable=True),
        sa.Column('url', sa.String(length=2000), nullable=False),
        sa.Column('slug', sa.String(length=200), nullable=False),
        sa.Column('status', sa.String(length=20), nullable=False),
        sa.Column('post_type', sa.String(length=20), nullable=False),
        sa.Column('author_id', sa.String(length=100), nullable=True),
        sa.Column('author_name', sa.String(length=255), nullable=True),
        sa.Column('published_at', sa.DateTime(), nullable=True),
        sa.Column('modified_at', sa.DateTime(), nullable=True),
        sa.Column('categories', sa.JSON(), nullable=True),
        sa.Column('tags', sa.JSON(), nullable=True),
        sa.Column('featured_image_url', sa.String(length=2000), nullable=True),
        sa.Column('metadata_json', sa.JSON(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=True),
        sa.Column('updated_at', sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(['client_id'], ['clients.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index('idx_post_client_id', 'posts', ['client_id'], unique=False)
    op.create_index('idx_post_status', 'posts', ['status'], unique=False)
    op.create_index('idx_post_published_at', 'posts', ['published_at'], unique=False)
    op.create_index('idx_post_client_published', 'posts', ['client_id', 'published_at'], unique=False)

    # Create wordpress_entities table
    op.create_table('wordpress_entities',
        sa.Column('id', sa.String(length=100), nullable=False),
        sa.Column('client_id', sa.Integer(), nullable=False),
        sa.Column('post_id', sa.String(length=100), nullable=True),
        sa.Column('name', sa.String(length=500), nullable=False),
        sa.Column('type', sa.String(length=50), nullable=False),
        sa.Column('description', sa.Text(), nullable=True),
        sa.Column('wikipedia_url', sa.String(length=2000), nullable=True),
        sa.Column('confidence', sa.Float(), nullable=True),
        sa.Column('metadata_json', sa.JSON(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=True),
        sa.Column('updated_at', sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(['client_id'], ['clients.id'], ),
        sa.ForeignKeyConstraint(['post_id'], ['posts.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index('idx_wp_entity_client_id', 'wordpress_entities', ['client_id'], unique=False)
    op.create_index('idx_wp_entity_type', 'wordpress_entities', ['type'], unique=False)
    op.create_index('idx_wp_entity_post_id', 'wordpress_entities', ['post_id'], unique=False)
    op.create_index('idx_wp_entity_client_type', 'wordpress_entities', ['client_id', 'type'], unique=False)

    # Create answer_cards table
    op.create_table('answer_cards',
        sa.Column('id', sa.String(length=100), nullable=False),
        sa.Column('client_id', sa.Integer(), nullable=False),
        sa.Column('question', sa.Text(), nullable=False),
        sa.Column('answer', sa.Text(), nullable=False),
        sa.Column('category', sa.String(length=255), nullable=True),
        sa.Column('tags', sa.JSON(), nullable=True),
        sa.Column('priority', sa.Integer(), nullable=True),
        sa.Column('is_active', sa.Boolean(), nullable=True),
        sa.Column('metadata_json', sa.JSON(), nullable=True),
        sa.Column('created_at', sa.DateTime(), nullable=True),
        sa.Column('updated_at', sa.DateTime(), nullable=True),
        sa.ForeignKeyConstraint(['client_id'], ['clients.id'], ),
        sa.PrimaryKeyConstraint('id')
    )
    op.create_index('idx_answer_card_client_id', 'answer_cards', ['client_id'], unique=False)
    op.create_index('idx_answer_card_category', 'answer_cards', ['category'], unique=False)
    op.create_index('idx_answer_card_active', 'answer_cards', ['is_active'], unique=False)
    op.create_index('idx_answer_card_priority', 'answer_cards', ['priority'], unique=False)


def downgrade() -> None:
    # Drop tables in reverse order (due to foreign key constraints)
    op.drop_table('answer_cards')
    op.drop_table('wordpress_entities')
    op.drop_table('posts')
    op.drop_table('metrics')
    op.drop_table('similarities')
    op.drop_table('entities')
    op.drop_table('citations')
    op.drop_table('answers')
    op.drop_table('runs')
    op.drop_table('queries')
    op.drop_table('clients')
