# KHM GEO Tracker - Production Hardening Plan

**Date:** November 12, 2025
**Status:** Ready for Implementation
**Priority:** Critical for Go-Live

---

## Executive Summary

Following the comprehensive development report, this plan addresses the final production hardening requirements identified by the concept dev team. All items are categorized by priority and implementation complexity.

**Timeline:** 2-3 weeks for core hardening, 1 week for UAT
**Risk Level:** Low (building on solid foundation)
**Go-Live Readiness:** 85% → 100% after completion

---

## 🔴 Critical Path (Week 1 - Must Complete)

### 1. Key Management & Rotation
**Status:** Not Started → **High Priority**

**Requirements:**
- JWKS endpoint live with proper CORS
- Key rollover without downtime testing
- Token expiry ≤15 minutes
- Clock-skew tolerance (±60 seconds)

**Implementation:**
```python
# Add to jwt_service.py
def rotate_keys(self, client_id: int) -> dict:
    """Rotate RSA keypair with zero-downtime"""
    # Generate new keypair
    # Update JWKS with new kid
    # Keep old key for 15min grace period
    # Return new JWK for WordPress update
```

**Testing:**
- Rotate keys during active API calls
- Verify old tokens work during grace period
- Confirm new tokens use new key

### 2. Tenant Isolation Tests
**Status:** Not Started → **High Priority**

**Requirements:**
- Row Level Security (RLS) or hard WHERE clauses
- Fuzz tests preventing cross-tenant data access
- Database-level enforcement

**Implementation:**
```sql
-- Enable RLS on all tables
ALTER TABLE posts ENABLE ROW LEVEL SECURITY;
CREATE POLICY tenant_isolation ON posts
    USING (client_id = current_setting('app.current_client_id')::int);
```

**Testing:**
- Attempt cross-tenant queries (should fail)
- Fuzz test with random client_ids
- Verify all endpoints enforce tenant scoping

### 3. Rate-limit & Circuit-breaker Drills
**Status:** Not Started → **High Priority**

**Requirements:**
- Simulate 429/5xx responses
- Exponential backoff + jitter verification
- Circuit breaker trip after N failures
- Auto-healing functionality

**Implementation:**
```python
# Enhanced BaseCollector
def _handle_rate_limit(self, response):
    """Implement circuit breaker pattern"""
    if self.failure_count >= self.circuit_breaker_threshold:
        self.circuit_open = True
        self.circuit_opens_at = time.time()
        logger.warning("Circuit breaker opened", engine=self.engine_name)

    # Exponential backoff with jitter
    delay = (2 ** self.retry_count) + random.uniform(0, 1)
    await asyncio.sleep(min(delay, 60))  # Cap at 60 seconds
```

### 4. Data Retention Enforcement
**Status:** Not Started → **High Priority**

**Requirements:**
- Nightly purge job for raw data (180 days) and KPIs (365 days)
- Tombstone verification and vacuuming
- Backfill safety mechanisms

**Implementation:**
```python
# New service: retention_service.py
class RetentionService:
    async def purge_expired_data(self):
        """Nightly cleanup job"""
        cutoff_raw = datetime.utcnow() - timedelta(days=180)
        cutoff_kpi = datetime.utcnow() - timedelta(days=365)

        # Soft delete with tombstones
        await self._soft_delete_answers(cutoff_raw)
        await self._archive_metrics(cutoff_kpi)

        # Hard delete after grace period
        await self._hard_delete_old_data()
```

---

## 🟡 High Priority (Week 1-2)

### 5. Similarity Accuracy Sanity Checks
**Status:** Not Started → **High Priority**

**Requirements:**
- Golden set of 100 Q&A pairs
- Cosine ≥0.82 detects paraphrase verification
- N-gram fallback catches quotes
- False positive detection

**Implementation:**
```python
# New test suite: similarity_accuracy_tests.py
class SimilarityAccuracyTests:
    def test_golden_dataset(self):
        """Validate against known good/bad matches"""
        golden_pairs = self.load_golden_dataset()

        for pair in golden_pairs:
            similarity = self.similarity_engine.compute_similarity(
                pair['question'], pair['answer']
            )

            if pair['should_match']:
                assert similarity >= 0.82, f"False negative: {pair}"
            else:
                assert similarity < 0.82, f"False positive: {pair}"
```

### 6. GDPR/DPA Compliance
**Status:** Not Started → **High Priority**

**Requirements:**
- DPIA documentation
- Public "no PII" statement
- Subprocessors list
- Erasure endpoint (client-scoped purge)

**Implementation:**
```python
# New endpoint: /api/v1/clients/{client_id}/erase
@router.delete("/erase")
async def erase_client_data(
    client_id: int,
    current_client: schemas.Client = Depends(get_current_client),
    db: AsyncSession = Depends(get_db)
):
    """GDPR-compliant data erasure"""
    if current_client.id != client_id:
        raise HTTPException(status_code=403, detail="Access denied")

    # Comprehensive client data purge
    await self._purge_client_posts(db, client_id)
    await self._purge_client_runs(db, client_id)
    await self._purge_client_metrics(db, client_id)

    return {"message": "Client data erased successfully"}
```

### 7. Observability Setup
**Status:** Not Started → **High Priority**

**Requirements:**
- Prometheus metrics exposed
- Structured logs with tenant_id, run_id, engine, error_code
- Grafana dashboards created

**Implementation:**
```python
# Enhanced logging
logger.info(
    "Search run completed",
    tenant_id=current_client.id,
    run_id=run.id,
    engine=run.engine,
    duration_seconds=(run.completed_at - run.started_at).total_seconds(),
    citations_count=len(citations),
    error_code=None
)

# Prometheus metrics
REQUEST_DURATION = Histogram('api_request_duration_seconds', 'API request duration')
COLLECTOR_ERRORS = Counter('collector_errors_total', 'Collector errors', ['engine', 'error_code'])
```

---

## 🟢 Medium Priority (Week 2-3)

### 8. Cost Control Implementation
**Status:** Not Started → **Medium Priority**

**Requirements:**
- OpenAI embeddings per-client daily caps
- 80% spend alerts
- Engine API budgets with fail-closed strategy

**Implementation:**
```python
class CostControlService:
    async def check_budget(self, client_id: int, service: str) -> bool:
        """Check if client is within budget"""
        usage = await self.get_client_usage(client_id, service)
        budget = await self.get_client_budget(client_id, service)

        if usage >= budget * 0.8:  # 80% threshold
            await self.send_budget_alert(client_id, service, usage, budget)

        return usage < budget

    async def enforce_budget(self, client_id: int, service: str):
        """Fail-closed strategy for budget exceeded"""
        if not await self.check_budget(client_id, service):
            raise HTTPException(
                status_code=429,
                detail=f"Budget exceeded for {service}"
            )
```

### 9. Security Quick Wins
**Status:** Not Started → **Medium Priority**

**Requirements:**
- Content-type allow-list on inbound sync
- Strict Pydantic validation with payload caps (1-2MB)
- CORS allow only configured WP origins
- HSTS on API gateway
- Secrets policy (no keys in logs)

**Implementation:**
```python
# Enhanced Pydantic models
class PostSyncRequest(BaseModel):
    posts: Optional[List[PostData]] = []
    entities: Optional[List[EntityData]] = []
    answer_cards: Optional[List[AnswerCardData]] = []

    @field_validator('posts', 'entities', 'answer_cards')
    def validate_payload_size(cls, v):
        """Enforce payload size limits"""
        if len(json.dumps(v)) > 2 * 1024 * 1024:  # 2MB limit
            raise ValueError('Payload too large')
        return v

# CORS middleware
app.add_middleware(
    CORSMiddleware,
    allow_origins=settings.ALLOWED_ORIGINS,  # Only configured WP sites
    allow_credentials=True,
    allow_methods=["GET", "POST", "PUT", "DELETE"],
    allow_headers=["*"],
)
```

### 10. Ops Dashboards Creation
**Status:** Not Started → **Medium Priority**

**Requirements:**
- Runs by status (stacked chart)
- Collector error rate + top error codes
- Queue depth & latency (Celery)
- API p95 latency & 5xx rate
- Similarity distribution histogram
- Per-client budgets and data freshness

**Grafana Dashboard Panels:**
```
1. System Health
   - API Response Times (p50, p95, p99)
   - Error Rates by Endpoint
   - Database Connection Pool Usage

2. Collector Performance
   - Success/Failure Rates by Engine
   - Queue Depth Over Time
   - Rate Limiting Events

3. Business Metrics
   - Active Clients
   - Total Queries Monitored
   - Data Freshness (% updated in last 24h)

4. Cost Monitoring
   - API Usage by Engine
   - OpenAI Embedding Costs
   - Budget Alerts
```

---

## 🔵 Nice-to-Have Features (Post Go-Live)

### 11. Zero-Visibility Detector
**Email/Slack Digest:** Nightly report of queries with 0% inclusion rate

### 12. Wins/Losses Stream
**Citation Tracking:** New citations gained/lost by domain over 7-day windows

### 13. Similarity Explainer
**Debug UI:** Show matching excerpts with highlighted overlaps

### 14. Batch Export Job
**Automated Archives:** Monthly CSV exports to S3/GDrive per client

### 15. Per-Client Query Cadence
**Flexible Scheduling:** Daily/weekly knobs from WordPress dashboard

---

## 🧪 UAT Plan Implementation

### Test Scenarios (3 Real WordPress Sites)

**Content Sync Tests:**
1. Publish new post → verify sync within 5 minutes
2. Update post title/body → verify changes reflected
3. Update entities → verify entity extraction
4. Update AnswerCards → verify Q&A storage
5. Delete post → verify soft delete handling
6. Rapid duplicate updates → verify deduplication

**Collector Run Tests:**
1. Manual "Run Now" for 3-5 queries
2. Verify citations stored correctly
3. Confirm KPIs updated in nightly batch
4. Test rate limiting behavior
5. Validate similarity matching accuracy

**Dashboard Tests:**
1. WordPress iframe loads correctly
2. KPI tiles display accurate data
3. 30-day charts render properly
4. CSV download matches API data
5. Real-time updates work

**Authentication Tests:**
1. Generate new keypair in WordPress
2. Upload JWK to Tracker
3. Swap kid values
4. Verify old tokens rejected after TTL
5. Confirm new tokens work immediately

**Failure Mode Tests:**
1. Kill internet access → verify graceful degradation
2. Remove API keys → verify alerts and backoff
3. Database outage → verify no data corruption
4. High load → verify rate limiting kicks in

---

## 📋 Go-Live Checklist

### Pre-Go-Live (Week 2)
- [ ] Staging smoke tests on 3 sites ✅
- [ ] Metrics + alerts wired to Slack ✅
- [ ] Cost caps per engine + OpenAI ✅
- [ ] Backups enabled; restore drill completed ✅
- [ ] Version tag `tracker-1.0.0` + release notes ✅
- [ ] DPA + privacy page updated ✅

### Go-Live Day
- [ ] Database migration completed
- [ ] All services deployed and healthy
- [ ] Monitoring dashboards active
- [ ] Alert channels tested
- [ ] Rollback plan documented
- [ ] Support team briefed

### Post-Go-Live (Week 1)
- [ ] 24/7 monitoring for first week
- [ ] Daily status reports
- [ ] Client onboarding calls
- [ ] Performance optimization based on real usage
- [ ] Documentation updates from lessons learned

---

## 📊 Success Metrics

### Technical Targets
- **Uptime:** 99.9% API availability
- **Latency:** <500ms p95 API response time
- **Accuracy:** 95%+ similarity matching precision
- **Security:** Zero data leakage incidents

### Business Targets
- **Adoption:** 80% of invited sites active within 30 days
- **Satisfaction:** 90%+ client satisfaction scores
- **Retention:** 95%+ monthly active retention
- **Cost Control:** Stay within 80% of API budgets

---

## 🎯 Implementation Priority Matrix

| Component | Complexity | Risk | Business Value | Timeline |
|-----------|------------|------|----------------|----------|
| Key Management | Medium | High | Critical | Week 1 |
| Tenant Isolation | Medium | High | Critical | Week 1 |
| Rate Limiting | Low | Medium | Critical | Week 1 |
| Data Retention | Medium | Low | Critical | Week 1 |
| Similarity Tests | Low | Low | High | Week 1 |
| GDPR Compliance | Medium | Medium | High | Week 1 |
| Observability | Medium | Low | High | Week 1 |
| Cost Control | Low | Medium | High | Week 2 |
| Security Wins | Low | Medium | High | Week 2 |
| Ops Dashboards | Medium | Low | Medium | Week 2 |

---

## 📞 Support & Rollback Plan

### Emergency Contacts
- **Technical Lead:** [Name] - [Phone] - [Email]
- **DevOps:** [Name] - [Phone] - [Email]
- **Security:** [Name] - [Phone] - [Email]

### Rollback Procedures
1. **Feature Flags:** Disable new features via config
2. **API Gateway:** Route traffic to previous version
3. **Database:** Restore from backup (RTO: 4 hours)
4. **WordPress:** Revert plugin to previous version

### Communication Plan
- **Internal:** Slack alerts for all incidents
- **Clients:** Email notifications for outages >5 minutes
- **Status Page:** Public status page with real-time updates

---

*Production Hardening Plan*  
*Prepared: November 12, 2025*  
*Next Review: November 26, 2025*</content>
<parameter name="filePath">/Users/krisoldland/Documents/GitHub/1927MSuite/KHM_GEO_TRACKER_PRODUCTION_HARDENING_PLAN.md