"""
SQLAlchemy database models for GEO Tracker
"""

from datetime import datetime
from typing import Dict, Any, Optional
from sqlalchemy import Column, Integer, String, Text, DateTime, Boolean, Float, JSON, ForeignKey, Index
from sqlalchemy.ext.declarative import declarative_base
from sqlalchemy.orm import relationship

Base = declarative_base()


class Client(Base):
    """
    Registered clients (WordPress sites)
    """
    __tablename__ = "clients"

    id = Column(Integer, primary_key=True, index=True)
    name = Column(String(255), nullable=False)
    domain = Column(String(255), nullable=False, unique=True, index=True)
    wordpress_url = Column(String(500), nullable=False)
    jwt_secret = Column(String(500), nullable=False)  # For JWT token generation
    is_active = Column(Boolean, default=True)
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # Relationships
    queries = relationship("Query", back_populates="client")
    runs = relationship("Run", back_populates="client")

    __table_args__ = (
        Index('idx_client_domain_active', 'domain', 'is_active'),
    )


class Query(Base):
    """
    Search queries monitored per client/topic
    """
    __tablename__ = "queries"

    id = Column(Integer, primary_key=True, index=True)
    client_id = Column(Integer, ForeignKey("clients.id"), nullable=False)
    query_text = Column(String(1000), nullable=False)
    topic = Column(String(255), nullable=False)
    is_active = Column(Boolean, default=True)
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # Relationships
    client = relationship("Client", back_populates="queries")
    runs = relationship("Run", back_populates="query")

    __table_args__ = (
        Index('idx_query_client_topic', 'client_id', 'topic'),
        Index('idx_query_active', 'is_active'),
    )


class Run(Base):
    """
    Log of each synthetic search run
    """
    __tablename__ = "runs"

    id = Column(Integer, primary_key=True, index=True)
    client_id = Column(Integer, ForeignKey("clients.id"), nullable=False)
    query_id = Column(Integer, ForeignKey("queries.id"), nullable=False)
    engine = Column(String(50), nullable=False)  # perplexity, brave, bing, google_sge
    status = Column(String(20), nullable=False, default="pending")  # pending, running, completed, failed
    started_at = Column(DateTime, nullable=True)
    completed_at = Column(DateTime, nullable=True)
    error_message = Column(Text, nullable=True)
    metadata_json = Column(JSON, nullable=True)  # Additional run metadata
    created_at = Column(DateTime, default=datetime.utcnow)

    # Relationships
    client = relationship("Client", back_populates="runs")
    query = relationship("Query", back_populates="runs")
    answers = relationship("Answer", back_populates="run")

    __table_args__ = (
        Index('idx_run_client_query', 'client_id', 'query_id'),
        Index('idx_run_engine_status', 'engine', 'status'),
        Index('idx_run_created_at', 'created_at'),
    )


class Answer(Base):
    """
    Stored AI-generated text responses
    """
    __tablename__ = "answers"

    id = Column(Integer, primary_key=True, index=True)
    run_id = Column(Integer, ForeignKey("runs.id"), nullable=False)
    raw_response = Column(Text, nullable=False)
    normalized_response = Column(Text, nullable=True)
    response_hash = Column(String(64), nullable=False, index=True)  # SHA-256 hash
    metadata_json = Column(JSON, nullable=True)  # Engine-specific metadata
    created_at = Column(DateTime, default=datetime.utcnow)

    # Relationships
    run = relationship("Run", back_populates="answers")
    citations = relationship("Citation", back_populates="answer")
    entities = relationship("Entity", back_populates="answer")

    __table_args__ = (
        Index('idx_answer_run_id', 'run_id'),
        Index('idx_answer_hash', 'response_hash'),
    )


class Citation(Base):
    """
    Domains/URLs cited in responses
    """
    __tablename__ = "citations"

    id = Column(Integer, primary_key=True, index=True)
    answer_id = Column(Integer, ForeignKey("answers.id"), nullable=False)
    url = Column(String(2000), nullable=False)
    domain = Column(String(255), nullable=False, index=True)
    title = Column(String(1000), nullable=True)
    snippet = Column(Text, nullable=True)
    position = Column(Integer, nullable=True)  # Position in the response
    created_at = Column(DateTime, default=datetime.utcnow)

    # Relationships
    answer = relationship("Answer", back_populates="citations")

    __table_args__ = (
        Index('idx_citation_domain', 'domain'),
        Index('idx_citation_answer_id', 'answer_id'),
    )


class Entity(Base):
    """
    Extracted entities from responses (via NER)
    """
    __tablename__ = "entities"

    id = Column(Integer, primary_key=True, index=True)
    answer_id = Column(Integer, ForeignKey("answers.id"), nullable=False)
    entity_text = Column(String(500), nullable=False)
    entity_type = Column(String(50), nullable=False)  # PERSON, ORG, GPE, etc.
    confidence = Column(Float, nullable=True)
    start_position = Column(Integer, nullable=True)
    end_position = Column(Integer, nullable=True)
    created_at = Column(DateTime, default=datetime.utcnow)

    # Relationships
    answer = relationship("Answer", back_populates="entities")

    __table_args__ = (
        Index('idx_entity_type', 'entity_type'),
        Index('idx_entity_answer_id', 'answer_id'),
    )


class Similarity(Base):
    """
    N-gram and embedding matches to our content
    """
    __tablename__ = "similarities"

    id = Column(Integer, primary_key=True, index=True)
    answer_id = Column(Integer, ForeignKey("answers.id"), nullable=False)
    post_id = Column(String(100), nullable=False)  # WordPress post ID
    similarity_score = Column(Float, nullable=False)  # 0.0 to 1.0
    similarity_type = Column(String(20), nullable=False)  # ngram, embedding, combined
    matched_text = Column(Text, nullable=True)
    metadata_json = Column(JSON, nullable=True)  # Additional similarity data
    created_at = Column(DateTime, default=datetime.utcnow)

    # Relationships
    answer = relationship("Answer", foreign_keys=[answer_id])

    __table_args__ = (
        Index('idx_similarity_answer_post', 'answer_id', 'post_id'),
        Index('idx_similarity_score', 'similarity_score'),
        Index('idx_similarity_type', 'similarity_type'),
    )


class Metric(Base):
    """
    Aggregated KPIs per client/topic/engine/day
    """
    __tablename__ = "metrics"

    id = Column(Integer, primary_key=True, index=True)
    client_id = Column(Integer, ForeignKey("clients.id"), nullable=False)
    query_id = Column(Integer, ForeignKey("queries.id"), nullable=True)
    engine = Column(String(50), nullable=False)
    date = Column(DateTime, nullable=False, index=True)  # Date for the metrics

    # KPI Values
    inclusion_rate = Column(Float, default=0.0)  # % of queries where domain appears
    extraction_rate = Column(Float, default=0.0)  # % with text overlap ≥ threshold
    presence_rate = Column(Float, default=0.0)   # % mentioning client/entity by name
    co_visibility_rate = Column(Float, default=0.0)  # % mentioning both client and publisher
    visibility_index = Column(Float, default=0.0)   # Weighted aggregate KPI

    # Supporting data
    total_queries = Column(Integer, default=0)
    successful_runs = Column(Integer, default=0)
    total_citations = Column(Integer, default=0)
    client_mentions = Column(Integer, default=0)

    metadata_json = Column(JSON, nullable=True)  # Additional metric data
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # Relationships
    client = relationship("Client")

    __table_args__ = (
        Index('idx_metric_client_date', 'client_id', 'date'),
        Index('idx_metric_engine_date', 'engine', 'date'),
        Index('idx_metric_query_date', 'query_id', 'date'),
    )


class Post(Base):
    """
    WordPress posts synced from clients
    """
    __tablename__ = "posts"

    id = Column(String(100), primary_key=True)  # WordPress post ID
    client_id = Column(Integer, ForeignKey("clients.id"), nullable=False)
    title = Column(String(1000), nullable=False)
    content = Column(Text, nullable=False)
    excerpt = Column(Text, nullable=True)
    url = Column(String(2000), nullable=False)
    slug = Column(String(200), nullable=False)
    status = Column(String(20), nullable=False, default="publish")
    post_type = Column(String(20), nullable=False, default="post")
    author_id = Column(String(100), nullable=True)
    author_name = Column(String(255), nullable=True)
    published_at = Column(DateTime, nullable=True)
    modified_at = Column(DateTime, nullable=True)
    categories = Column(JSON, nullable=True)  # List of category IDs/names
    tags = Column(JSON, nullable=True)  # List of tag IDs/names
    featured_image_url = Column(String(2000), nullable=True)
    metadata_json = Column(JSON, nullable=True)  # Additional WordPress metadata
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # Relationships
    client = relationship("Client")
    entities = relationship("WordPressEntity", back_populates="post")

    __table_args__ = (
        Index('idx_post_client_id', 'client_id'),
        Index('idx_post_status', 'status'),
        Index('idx_post_published_at', 'published_at'),
        Index('idx_post_client_published', 'client_id', 'published_at'),
    )


class WordPressEntity(Base):
    """
    Entities extracted from WordPress content (people, organizations, locations, etc.)
    """
    __tablename__ = "wordpress_entities"

    id = Column(String(100), primary_key=True)  # WordPress entity ID
    client_id = Column(Integer, ForeignKey("clients.id"), nullable=False)
    post_id = Column(String(100), ForeignKey("posts.id"), nullable=True)
    name = Column(String(500), nullable=False)
    type = Column(String(50), nullable=False)  # person, organization, location, etc.
    description = Column(Text, nullable=True)
    wikipedia_url = Column(String(2000), nullable=True)
    confidence = Column(Float, nullable=True)
    metadata_json = Column(JSON, nullable=True)  # Additional entity data
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # Relationships
    client = relationship("Client")
    post = relationship("Post", back_populates="entities")

    __table_args__ = (
        Index('idx_wp_entity_client_id', 'client_id'),
        Index('idx_wp_entity_type', 'type'),
        Index('idx_wp_entity_post_id', 'post_id'),
        Index('idx_wp_entity_client_type', 'client_id', 'type'),
    )


class AnswerCard(Base):
    """
    AnswerCard data from WordPress for similarity matching
    """
    __tablename__ = "answer_cards"

    id = Column(String(100), primary_key=True)  # WordPress AnswerCard ID
    client_id = Column(Integer, ForeignKey("clients.id"), nullable=False)
    question = Column(Text, nullable=False)
    answer = Column(Text, nullable=False)
    category = Column(String(255), nullable=True)
    tags = Column(JSON, nullable=True)  # List of tags
    priority = Column(Integer, default=1)  # 1=low, 5=high
    is_active = Column(Boolean, default=True)
    metadata_json = Column(JSON, nullable=True)  # Additional AnswerCard data
    created_at = Column(DateTime, default=datetime.utcnow)
    updated_at = Column(DateTime, default=datetime.utcnow, onupdate=datetime.utcnow)

    # Relationships
    client = relationship("Client")

    __table_args__ = (
        Index('idx_answer_card_client_id', 'client_id'),
        Index('idx_answer_card_category', 'category'),
        Index('idx_answer_card_active', 'is_active'),
        Index('idx_answer_card_priority', 'priority'),
    )