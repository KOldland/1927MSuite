# KHM GEO Tracker

**AI Visibility Measurement Platform for Generative Engine Optimization**

The GEO Tracker is a dedicated measurement and intelligence platform that continuously monitors client visibility across AI-driven search ecosystems including Perplexity, Brave Search, Bing Copilot, and Google SGE.

## 🚀 Features

### Core Functionality
- **Real-time AI Engine Monitoring**: Track visibility across multiple AI search platforms
- **Automated Query Execution**: Synthetic search runs with intelligent rotation
- **Content Similarity Analysis**: N-gram and OpenAI embedding-based text matching
- **KPI Computation**: Inclusion, Extraction, Presence, Co-visibility, and Visibility Index metrics
- **WordPress Integration**: Seamless data sync with the KHM SEO GEO plugin

### Technical Features
- **Multi-Format Export**: CSV, JSON, XML, YAML, and SQL dump capabilities
- **Background Processing**: Asynchronous operations with Celery workers
- **Rate Limiting**: Built-in throttling to prevent API blocking
- **Comprehensive Logging**: Structured logging with error tracking
- **API-First Design**: RESTful APIs for all functionality

## 🏗️ Architecture

```
GEO Tracker Architecture
├── FastAPI Backend (Python)
│   ├── PostgreSQL Database
│   ├── Redis Cache/Queue
│   └── Celery Workers
├── AI Engine Collectors
│   ├── Perplexity API
│   ├── Brave Search API
│   ├── Bing Copilot API
│   └── Google SGE (Headless)
├── Similarity Engine
│   ├── OpenAI Embeddings
│   └── N-gram Analysis
└── WordPress Integration
    ├── JWT Authentication
    ├── Data Synchronization
    └── Dashboard Embedding
```

## 📊 Data Model

### Core Tables
- **clients**: Registered WordPress sites with JWT secrets
- **queries**: Search queries monitored per client/topic
- **runs**: Execution logs for each synthetic search
- **answers**: Stored AI-generated responses
- **citations**: Extracted domains/URLs from responses
- **entities**: Named entities from NER processing
- **similarities**: Text similarity matches to client content
- **metrics**: Daily aggregated KPIs per client/engine

## 🛠️ Installation

### Prerequisites
- Python 3.9+
- PostgreSQL 12+
- Redis 6+
- Node.js 16+ (for Next.js dashboard)

### Setup
```bash
# Clone the repository
git clone <repository-url>
cd geo-tracker

# Create virtual environment
python -m venv venv
source venv/bin/activate  # On Windows: venv\Scripts\activate

# Install dependencies
pip install -r requirements.txt

# Copy environment configuration
cp .env.example .env
# Edit .env with your configuration

# Run database migrations
alembic upgrade head

# Start the application
python run.py
```

### Docker Setup (Alternative)
```bash
# Build and run with Docker Compose
docker-compose up -d
```

## 🔧 Configuration

### Environment Variables
```bash
# Database
POSTGRES_SERVER=localhost
POSTGRES_USER=geo_tracker
POSTGRES_PASSWORD=secure_password
POSTGRES_DB=geo_tracker

# AI APIs
OPENAI_API_KEY=sk-...
PERPLEXITY_API_KEY=...
BRAVE_API_KEY=...
BING_API_KEY=...

# Security
SECRET_KEY=your-jwt-secret-key
```

### Rate Limiting
- **Perplexity**: 100 requests/minute
- **Brave**: 50 requests/minute
- **Bing**: 30 requests/minute
- **Google SGE**: 20 requests/minute (headless fallback)

## 📡 API Endpoints

### WordPress Integration
```http
POST /api/v1/posts/sync
# Sync posts, entities, and AnswerCards from WordPress
```

### Search Operations
```http
POST /api/v1/runs/
# Trigger synthetic search runs

GET /api/v1/runs/{run_id}
# Monitor run status and results
```

### Analytics & Reporting
```http
GET /api/v1/reports/
# Retrieve KPI metrics and trends

GET /api/v1/reports/export
# Export data in various formats
```

### Authentication
All endpoints require JWT authentication:
```bash
curl -H "Authorization: Bearer <jwt-token>" \
     https://api.geo-tracker.com/api/v1/posts/sync
```

## 🎯 KPIs & Metrics

### Primary Metrics
- **Inclusion Rate**: % of queries where client domain appears in citations
- **Extraction Rate**: % of answers with significant text overlap (> threshold)
- **Presence Rate**: % of answers mentioning client/entity by name
- **Co-visibility Rate**: % of answers mentioning both client and competitors
- **Visibility Index**: Weighted aggregate of all KPIs

### Aggregation Levels
- **Daily**: Per client/engine combination
- **Weekly/Monthly**: Trend analysis and forecasting
- **Topic-based**: Performance by content category

## 🔒 Security & Compliance

### Data Protection
- **Encryption**: All sensitive data encrypted at rest
- **GDPR Compliance**: Data retention policies and user consent
- **Access Control**: JWT-based authentication with role-based permissions

### API Security
- **Rate Limiting**: Prevents abuse and ensures fair usage
- **Input Validation**: Comprehensive sanitization and validation
- **Audit Logging**: Complete record of all operations

## 📈 Monitoring & Observability

### Application Metrics
- **Prometheus**: System and business metrics
- **Grafana**: Real-time dashboards and alerting
- **Structured Logging**: JSON-formatted logs for analysis

### Health Checks
- **Database Connectivity**: PostgreSQL and Redis health
- **API Endpoints**: Response time and error rate monitoring
- **Collector Status**: AI engine availability and performance

## 🚀 Deployment

### Heroku Deployment
```bash
# Install Heroku CLI
heroku create khm-geo-tracker

# Set environment variables
heroku config:set SECRET_KEY=...
heroku config:set DATABASE_URL=...

# Deploy
git push heroku main
```

### Production Checklist
- [ ] Environment variables configured
- [ ] Database migrations applied
- [ ] SSL certificates installed
- [ ] Monitoring and alerting configured
- [ ] Backup strategy implemented
- [ ] Rate limiting tuned for production load

## 🔄 Development Workflow

### Code Quality
```bash
# Run tests
pytest

# Code formatting
black .
isort .

# Type checking
mypy app/

# Linting
flake8 app/
```

### Database Migrations
```bash
# Create new migration
alembic revision --autogenerate -m "Add new table"

# Apply migrations
alembic upgrade head
```

## 📚 Documentation

### API Documentation
- **Swagger UI**: `http://localhost:8000/docs`
- **ReDoc**: `http://localhost:8000/redoc`

### Architecture Decisions
- **ADR Documents**: `docs/architecture/`
- **API Specifications**: `docs/api/`

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

### Development Guidelines
- Follow PEP 8 style guidelines
- Write comprehensive tests
- Update documentation
- Use type hints
- Keep commits atomic

## 📄 License

Copyright © 2025 KHM Development Team. All rights reserved.

## 🆘 Support

For support and questions:
- **Documentation**: Check the `/docs` directory
- **Issues**: GitHub Issues for bugs and feature requests
- **Discussions**: GitHub Discussions for questions

---

**Built with ❤️ by the KHM Development Team**