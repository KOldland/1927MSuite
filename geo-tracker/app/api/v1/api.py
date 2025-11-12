"""
Main API router for v1 endpoints
"""

from fastapi import APIRouter

from app.api.v1.endpoints import posts, runs, reports, clients

api_router = APIRouter()

# Include all endpoint routers
api_router.include_router(
    posts.router,
    prefix="/posts",
    tags=["posts"]
)
api_router.include_router(
    runs.router,
    prefix="/runs",
    tags=["runs"]
)
api_router.include_router(
    reports.router,
    prefix="/reports",
    tags=["reports"]
)
api_router.include_router(
    clients.router,
    prefix="/clients",
    tags=["clients"]
)