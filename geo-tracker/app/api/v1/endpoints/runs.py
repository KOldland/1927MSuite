"""
Runs API endpoints for triggering and monitoring search runs
"""

from fastapi import APIRouter

router = APIRouter()


@router.post("/")
async def create_run():
    """Trigger a new search run"""
    return {"message": "Run created"}


@router.get("/")
async def list_runs():
    """List search runs"""
    return {"runs": []}


@router.get("/{run_id}")
async def get_run(run_id: int):
    """Get run details"""
    return {"run_id": run_id}