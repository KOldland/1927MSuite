# Heroku Procfile for GEO Tracker FastAPI Backend
web: cd geo-tracker && uvicorn app.main:app --host 0.0.0.0 --port $PORT --workers 2
worker: cd geo-tracker && celery -A app.worker worker -Q collector.perplexity,collector.brave,collector.bing -O fair --concurrency 2
beat: cd geo-tracker && celery -A app.worker beat --loglevel info
release: cd geo-tracker && alembic upgrade head