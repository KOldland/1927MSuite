"""
Pydantic schemas for API request/response models
"""

from datetime import datetime
from typing import List, Optional, Dict, Any
from pydantic import BaseModel, Field


# Client schemas
class ClientBase(BaseModel):
    name: str = Field(..., max_length=255)
    domain: str = Field(..., max_length=255)
    wordpress_url: str = Field(..., max_length=500)


class ClientCreate(ClientBase):
    pass


class Client(ClientBase):
    id: int
    is_active: bool
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


# Post sync schemas
class PostData(BaseModel):
    id: str
    title: str
    content: str
    excerpt: Optional[str] = None
    url: str
    published_at: Optional[datetime] = None
    modified_at: Optional[datetime] = None
    categories: List[str] = []
    tags: List[str] = []
    meta: Dict[str, Any] = {}


class EntityData(BaseModel):
    id: str
    name: str
    type: str  # Organization, Product, Technology, etc.
    canonical: str
    aliases: List[str] = []
    description: Optional[str] = None
    scope: str = "global"
    status: str = "active"


class AnswerCardData(BaseModel):
    question: str
    answer: str
    confidence: float
    entity_id: str
    sources: List[str] = []
    citations: List[str] = []


class PostSyncRequest(BaseModel):
    posts: List[PostData] = []
    entities: List[EntityData] = []
    answer_cards: List[AnswerCardData] = []
    sync_metadata: Dict[str, Any] = {}


class PostSyncResponse(BaseModel):
    success: bool
    posts_processed: int
    entities_processed: int
    answer_cards_processed: int
    message: str
    sync_timestamp: datetime


# Query schemas
class QueryBase(BaseModel):
    query_text: str = Field(..., max_length=1000)
    topic: str = Field(..., max_length=255)


class QueryCreate(QueryBase):
    pass


class Query(QueryBase):
    id: int
    client_id: int
    is_active: bool
    created_at: datetime
    updated_at: datetime

    class Config:
        from_attributes = True


# Run schemas
class RunBase(BaseModel):
    query_id: int
    engine: str = Field(..., max_length=50)


class RunCreate(RunBase):
    pass


class Run(RunBase):
    id: int
    client_id: int
    status: str
    started_at: Optional[datetime]
    completed_at: Optional[datetime]
    error_message: Optional[str]
    created_at: datetime

    class Config:
        from_attributes = True


# Report schemas
class KPIMetrics(BaseModel):
    inclusion_rate: float
    extraction_rate: float
    presence_rate: float
    co_visibility_rate: float
    visibility_index: float
    total_queries: int
    successful_runs: int
    total_citations: int
    client_mentions: int


class ReportRequest(BaseModel):
    client_id: Optional[int] = None
    date_from: Optional[datetime] = None
    date_to: Optional[datetime] = None
    engines: List[str] = []
    topics: List[str] = []


class ReportResponse(BaseModel):
    client_id: int
    client_name: str
    date_from: datetime
    date_to: datetime
    metrics: KPIMetrics
    engine_breakdown: Dict[str, KPIMetrics]
    topic_breakdown: Dict[str, KPIMetrics]
    top_citations: List[Dict[str, Any]]
    generated_at: datetime


# Token schemas
class Token(BaseModel):
    access_token: str
    token_type: str


class TokenData(BaseModel):
    client_id: Optional[int] = None