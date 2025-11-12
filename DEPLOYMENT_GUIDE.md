# 🚀 GEO Tracker Deployment Guide (Updated)

**Date:** November 12, 2025
**Version:** 1.1.0 (Updated per HQ feedback)
**Status:** Production Ready with Corrections

---

## 📋 Key Updates from HQ Feedback

### ✅ **Corrections Applied**
- **Hosting:** Changed from Ubuntu bare-metal to **Heroku MVP** path
- **Database:** Replaced PostGIS with **pgvector** for embeddings
- **JWT/JWKS:** Added detailed per-site RSA key management specification
- **Rate Limiting:** Added explicit environment knobs and circuit breaker policy
- **Monitoring:** Specified metrics, OpenTelemetry/Prometheus, Grafana dashboards
- **Backups:** Added retention policies (7/30/90 days), encryption, restore drills
- **API Docs:** Added Postman collection links, curl smoke tests
- **WordPress:** Specified RSA keypair generation, cron sync, role capabilities

---

## 🏗️ Infrastructure Requirements (Heroku MVP)

### **Heroku Application**
- **Dyno Type:** Standard-1X ($25/month) - 512MB RAM, 1 vCPU
- **Workers:** 1-2 Celery workers for AI collectors
- **Database:** Heroku Postgres Standard-0 ($50/month) - 64GB storage
- **Cache:** Heroku Redis Mini ($7/month) - 20 connections

### **WordPress Sites** (per site)
- **Hosting:** Any WordPress hosting (SiteGround, WP Engine, etc.)
- **PHP:** 8.1+
- **SSL:** Required for secure JWT communication

---

## 📦 Software Dependencies

### **Heroku Buildpacks**
```yaml
# Add to heroku app
heroku buildpacks:add heroku/python
```

### **Python Packages** (requirements.txt)
```txt
fastapi==0.104.1
uvicorn[standard]==0.24.0
sqlalchemy==2.0.23
psycopg2-binary==2.9.9
pgvector==0.2.4
redis==5.0.1
celery==5.3.4
pydantic==2.5.0
python-jose[cryptography]==3.3.0
openai==1.3.7
requests==2.31.0
alembic==1.12.1
opentelemetry-distro==0.43b0
opentelemetry-exporter-prometheus==0.44b0
```

---

## 🔧 Environment Variables (Heroku Config Vars)

```bash
# Database & Cache (auto-set by Heroku add-ons)
DATABASE_URL=postgres://username:password@host:5432/database
REDIS_URL=redis://h:password@host:port

# JWT Security
JWT_SECRET_KEY=your-256-bit-secret-key-here
JWT_ALGORITHM=RS256
JWT_ACCESS_TOKEN_EXPIRE_MINUTES=15
JWT_AUDIENCE=geo-tracker

# AI API Keys (secure storage required)
OPENAI_API_KEY=sk-your-openai-key
PERPLEXITY_API_KEY=pplx-your-key
BRAVE_API_KEY=your-brave-key
BING_API_KEY=your-bing-key

# Rate Limiting & Circuit Breaker
RATE_LIMITS_PERPLEXITY_PER_MIN=5
RATE_LIMITS_BRAVE_PER_MIN=60
RATE_LIMITS_BING_PER_MIN=60
RATE_LIMITS_OPENAI_PER_MIN=100
CIRCUIT_BREAKER_FAILURE_THRESHOLD=5
CIRCUIT_BREAKER_RECOVERY_TIMEOUT=300

# CORS & Security
DASHBOARD_ORIGINS=https://site1.yourdomain.com,https://site2.yourdomain.com
ALLOWED_ORIGINS=https://site1.yourdomain.com,https://site2.yourdomain.com

# Application
APP_ENV=production
DEBUG=False
LOG_LEVEL=INFO

# Google Services
GOOGLE_ANALYTICS_CREDENTIALS_PATH=/app/ga4-credentials.json
GOOGLE_SEARCH_CONSOLE_CREDENTIALS_PATH=/app/gsc-credentials.json

# Monitoring
OTEL_SERVICE_NAME=geo-tracker
OTEL_TRACES_EXPORTER=console
PROMETHEUS_METRICS_PORT=8001
```

---

## 🚀 Heroku Deployment

### **Step 1: Heroku Setup**

```bash
# Create Heroku app
heroku create geo-tracker-yourdomain

# Add buildpacks
heroku buildpacks:add heroku/python

# Add add-ons
heroku addons:create heroku-postgresql:standard-0
heroku addons:create heroku-redis:mini

# Set all config vars (see Environment Variables section)
heroku config:set JWT_SECRET_KEY="your-256-bit-secret"
heroku config:set OPENAI_API_KEY="sk-your-key"
# ... set all required vars
```

### **Step 2: Procfile**

```bash
# Create Procfile in project root
cat > Procfile << EOF
web: uvicorn app.main:app --host 0.0.0.0 --port \$PORT --workers 2
worker: celery -A app.worker worker -Q collector.perplexity,collector.brave,collector.bing -O fair --concurrency 2
beat: celery -A app.worker beat --loglevel info
release: alembic upgrade head
EOF
```

### **Step 3: Database Setup**

```sql
-- Enable pgvector extension (runs in release phase)
CREATE EXTENSION IF NOT EXISTS vector;

-- Create embeddings table with vector column
CREATE TABLE embeddings (
    id SERIAL PRIMARY KEY,
    post_id INTEGER REFERENCES posts(id),
    content_hash VARCHAR(64) NOT NULL,
    embedding vector(1536) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Create optimized index for cosine similarity
CREATE INDEX ON embeddings USING ivfflat (embedding vector_cosine_ops);
```

### **Step 4: Deploy**

```bash
# Deploy to Heroku
git push heroku main

# Scale dynos
heroku ps:scale web=1 worker=1 beat=1

# Check deployment
heroku logs --tail
heroku open  # Opens https://geo-tracker-yourdomain.herokuapp.com
```

---

## 🔐 JWT/JWKS Security Implementation

### **Per-Site RSA Key Generation (WordPress)**

1. **Generate RSA Keypair** in WordPress admin:
   - Go to GEO Tracker → Connection tab
   - Click "Generate RSA Keypair"
   - Creates 2048-bit RSA keys locally

2. **Post JWK Public Key** to Tracker:
   ```php
   // WordPress automatically posts public key to Tracker
   POST /v1/clients/{client_id}/jwks
   {
     "keys": [{
       "kty": "RSA",
       "use": "sig",
       "kid": "wp-site-uuid",
       "n": "public-modulus",
       "e": "AQAB"
     }]
   }
   ```

### **JWT Claims & Validation**

Required JWT claims for WordPress → Tracker communication:

```json
{
  "iss": "https://site1.yourdomain.com",
  "sub": "site-uuid-12345",
  "aud": "geo-tracker",
  "scope": "posts:sync reports:read",
  "exp": 1731446400,  // ≤15 minutes
  "iat": 1731445500,
  "kid": "wp-site-uuid"
}
```

### **Key Rotation Procedure**

1. Generate new keypair in WordPress
2. Post new public key to Tracker (with new `kid`)
3. Wait 15 minutes for old tokens to expire
4. Update WordPress to use new private key
5. Remove old private key

### **JWKS Endpoint**

Tracker exposes public keys at: `https://geo-tracker-yourdomain.herokuapp.com/.well-known/jwks.json`

---

## 📊 Monitoring & Observability

### **Metrics to Monitor**

- **API Performance:** p50/p95/p99 latency by endpoint
- **Error Rates:** 5xx errors, 429 rate limits, per endpoint
- **Worker Health:** Queue depth, success/fail rates, processing time
- **AI Collector Status:** Per-engine failure rate, token consumption
- **Database:** Connection pool usage, slow queries
- **Security:** Failed auth attempts, rate limit hits

### **Stack Implementation**

```python
# OpenTelemetry + Prometheus setup
from opentelemetry import metrics
from opentelemetry.exporter.prometheus import PrometheusMetricReader
from opentelemetry.sdk.metrics import MeterProvider

# Grafana dashboards for:
# - API response times (p50/p95)
# - Error rates by endpoint
# - Worker queue depth
# - Collector failure rates
# - Token spend by API key
```

### **Alerting**

- **Slack/Discord webhooks** for critical alerts
- **Email notifications** for weekly reports
- **PagerDuty integration** for production incidents

### **Audit Logging**

Structured JSON logs with:
```json
{
  "timestamp": "2025-11-12T10:30:00Z",
  "level": "INFO",
  "tenant_id": "site-uuid-123",
  "run_id": "run-456",
  "engine": "perplexity",
  "action": "collect",
  "status": "success",
  "tokens_used": 150,
  "response_time_ms": 2500
}
```

---

## 💾 Backup & Retention Strategy

### **Database Backups**

- **Automated:** Daily via Heroku Postgres
- **Retention:** 7 days (daily), 30 days (weekly), 90 days (monthly)
- **Encryption:** AES-256 at rest
- **Restore Testing:** Monthly restore drills

### **Data Retention Policy**

```sql
-- Raw data: 180 days
DELETE FROM answers WHERE created_at < NOW() - INTERVAL '180 days';

-- KPI aggregations: 365 days
DELETE FROM metrics WHERE date < CURRENT_DATE - INTERVAL '365 days';

-- Embeddings: Keep all (for similarity matching)
-- Posts: Keep all (for historical analysis)
```

### **GDPR Compliance**

- **Tenant-scoped deletion:** `DELETE FROM posts WHERE client_id = ?`
- **Data export:** CSV/JSON export for users
- **Audit trail:** All deletion operations logged

---

## 🌐 API Documentation & Testing

### **Live Documentation**

- **OpenAPI/Swagger:** `https://geo-tracker-yourdomain.herokuapp.com/docs`
- **ReDoc:** `https://geo-tracker-yourdomain.herokuapp.com/redoc`
- **OpenAPI JSON:** `https://geo-tracker-yourdomain.herokuapp.com/openapi.json`

### **Postman Collection**

```json
{
  "info": {
    "name": "GEO Tracker API",
    "schema": "https://schema.getpostman.com/json/collection/v2.1.0/collection.json"
  },
  "variable": [
    {"key": "base_url", "value": "https://geo-tracker-yourdomain.herokuapp.com"},
    {"key": "jwt_token", "value": "{{jwt_token}}"}
  ]
}
```

### **Smoke Tests**

```bash
# Health check
curl -f https://geo-tracker-yourdomain.herokuapp.com/health

# JWKS endpoint
curl -f https://geo-tracker-yourdomain.herokuapp.com/.well-known/jwks.json

# API docs
curl -f https://geo-tracker-yourdomain.herokuapp.com/docs

# Protected endpoint (should return 401)
curl -f https://geo-tracker-yourdomain.herokuapp.com/v1/posts/sync

# With valid JWT (should return 200/409)
curl -H "Authorization: Bearer eyJ..." \
     https://geo-tracker-yourdomain.herokuapp.com/v1/posts/sync
```

---

## 🌐 WordPress Connector Details

### **Plugin Features**

- **RSA Keypair Generation:** 2048-bit keys for JWT signing
- **JWKS Discovery:** Automatic retrieval from Tracker
- **Connection Testing:** Real-time validation with detailed error messages
- **Dashboard Iframe:** Signed embedding with automatic token refresh
- **Alert Configuration:** Email/webhook settings with threshold configuration

### **Role Capabilities**

```php
// Define capabilities
'manage_geo' => 'Manage GEO Tracker connection',
'audit_geo' => 'View GEO tracking audit logs',
'view_geo_reports' => 'Access GEO visibility reports'
```

### **Cron Synchronization**

```php
// Nightly sync (wp-cron)
add_action('geo_tracker_nightly_sync', 'sync_posts_to_tracker');

// Retry logic with exponential backoff
// 1s → 2s → 4s → 8s → 16s → 32s → 60s max
```

### **Server-to-Server CSV Export**

```php
// Proxy CSV downloads to avoid token exposure in browser
add_action('wp_ajax_geo_export_csv', 'proxy_csv_export_to_tracker');
```

---

## 🔍 Testing & Validation

### **End-to-End Test Flow**

1. **WordPress Setup:**
   - Install KHM SEO plugin
   - Configure Tracker connection
   - Generate RSA keys
   - Test connection (should succeed)

2. **Content Sync:**
   - Create/publish WordPress post
   - Verify sync to Tracker (check logs)
   - Confirm embedding generation

3. **AI Collection:**
   - Trigger manual collection run
   - Monitor worker logs
   - Verify similarity matching

4. **Dashboard Access:**
   - Open WordPress GEO Tracker → Dashboard
   - Verify iframe loads correctly
   - Check JWT token validation

### **Performance Benchmarks**

- **API Response Time:** <500ms p95
- **Content Sync:** <30s for 100 posts
- **AI Collection:** <5min per engine run
- **Dashboard Load:** <2s initial load

---

## 🚨 Troubleshooting

### **Common Issues**

1. **JWT Authentication Fails**
   - Check key rotation timing (15min expiry)
   - Verify JWKS endpoint accessibility
   - Confirm kid matching between WordPress/Tracker

2. **Rate Limiting**
   - Monitor per-engine limits
   - Check circuit breaker status
   - Implement backoff in WordPress sync

3. **Database Connection**
   - Check Heroku Postgres status
   - Verify connection pool limits
   - Monitor slow query logs

4. **Worker Queue Backlog**
   - Scale additional worker dynos
   - Check Redis connection
   - Monitor queue depth metrics

---

## 📞 Support & Documentation

- **API Docs:** `https://geo-tracker-yourdomain.herokuapp.com/docs`
- **Postman Collection:** Available in `/docs` → Download
- **Logs:** `heroku logs --tail` or Heroku dashboard
- **Metrics:** Grafana dashboards (if configured)
- **Health Checks:** `/health` endpoint

---

## ✅ Deployment Checklist

### **Heroku Backend**
- [ ] Heroku app created with Python buildpack
- [ ] Postgres and Redis add-ons attached
- [ ] All environment variables configured
- [ ] Procfile created with proper dyno types
- [ ] Database migrations run (via release phase)
- [ ] pgvector extension enabled
- [ ] Application deployed and scaled
- [ ] Health checks passing
- [ ] JWKS endpoint accessible

### **WordPress Sites**
- [ ] KHM SEO plugin installed and activated
- [ ] GEO Tracker connection configured
- [ ] RSA keypair generated
- [ ] Connection test successful
- [ ] Dashboard iframe loading
- [ ] Alert settings configured

### **Security & Monitoring**
- [ ] JWT authentication working
- [ ] Rate limiting configured
- [ ] Monitoring dashboards set up
- [ ] Backup procedures tested
- [ ] SSL/TLS verified
- [ ] CORS properly configured

### **Testing & Validation**
- [ ] End-to-end data flow tested
- [ ] Performance benchmarks met
- [ ] Error handling verified
- [ ] Backup/restore tested
- [ ] Security audit completed

---

*Deployment Guide v1.1.0 - Updated per HQ feedback*  
*Date: November 12, 2025*</content>
<parameter name="filePath">/Users/krisoldland/Documents/GitHub/1927MSuite/DEPLOYMENT_GUIDE.md