"""
Reports API endpoints for KPI data and analytics
"""

from fastapi import APIRouter

router = APIRouter()


@router.get("/")
async def get_report():
    """Get KPI report"""
    return {"report": "KPI data here"}


@router.get("/export")
async def export_data():
    """Export data as CSV/JSON"""
    return {"export": "Data export here"}