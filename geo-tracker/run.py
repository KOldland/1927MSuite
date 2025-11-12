#!/usr/bin/env python3
"""
KHM GEO Tracker - Main entry point
"""

import uvicorn
from app.core.config import settings


def main():
    """Run the FastAPI application with uvicorn"""
    uvicorn.run(
        "app.main:app",
        host="0.0.0.0",
        port=8000,
        reload=settings.DEBUG,
        log_level="info" if not settings.DEBUG else "debug",
    )


if __name__ == "__main__":
    main()