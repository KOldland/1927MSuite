# Heroku Procfile for GEO Tracker FastAPI Backend
web: uvicorn geo-tracker.app.main:app --host 0.0.0.0 --port $PORT --workers 2
worker: celery -A geo-tracker.app.worker worker -Q collector.perplexity,collector.brave,collector.bing -O fair --concurrency 2
beat: celery -A geo-tracker.app.worker beat --loglevel info
release: cd geo-tracker && alembic upgrade head