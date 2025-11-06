# 🎯 Advanced Attribution System Documentation

## Overview

The Advanced Attribution System is a cutting-edge hybrid tracking solution designed to provide accurate affiliate commission attribution in the modern web environment. It addresses the critical challenges posed by Intelligent Tracking Prevention (ITP), Safari's tracking restrictions, ad blockers, and the move toward cookieless web tracking.

## Architecture

### Hybrid Tracking Approach

Our system employs a multi-layered attribution strategy:

1. **Server-Side Events** - Primary tracking method, immune to client-side restrictions
2. **First-Party Cookies** - Standards-compliant tracking with extended lifespans  
3. **URL Parameters** - Direct attribution through campaign parameters
4. **Local/Session Storage** - Browser-based fallback storage
5. **Fingerprinting** - Optional last-resort identification (privacy-compliant)

### System Components

```
Advanced Attribution System
├── Server-Side Components
│   ├── KHM_Advanced_Attribution_Manager.php
│   ├── REST API Endpoints (/khm/v1/track/*)
│   ├── Database Schema (attribution_events, conversion_tracking)
│   └── Background Processing
├── Client-Side Components
│   ├── attribution-tracker.js
│   ├── jQuery Integration
│   ├── Event Listeners
│   └── Fallback Methods
└── Integration Layer
    ├── WordPress Hooks
    ├── WooCommerce/EDD Support
    └── Third-Party Plugin APIs
```

## Core Features

### 🛡️ Modern Web Tracking Resilience

**Problem**: Apple's ITP, Safari restrictions, and ad blockers break traditional affiliate tracking

**Solution**: Multi-method attribution with intelligent fallbacks
- First-party context preservation
- Server-side event correlation
- Progressive degradation strategy
- Real-time tracking validation

### 🍪 Hybrid Cookie Strategy

**Traditional cookies** are increasingly blocked, so we implement:
- **First-party cookies** with SameSite=Lax for maximum compatibility
- **Extended expiration** (30 days default, configurable)
- **Domain-specific** attribution for multi-site networks
- **Secure/HttpOnly** flags for security

### 📊 Multi-Touch Attribution

**Beyond last-click attribution**:
- **First-touch attribution** - Credit to initial referrer
- **Last-touch attribution** - Credit to final converter
- **Multi-touch fractioning** - Distribute credit across touchpoints
- **Assisted conversions** - Track influence of middle-funnel interactions
- **Lookback windows** - Configurable attribution periods (7-90 days)

### 🔗 UTM Standardization

**Intelligent parameter handling**:
- **Auto-correction** of common typos (gooogle → google, emai → email)
- **Case normalization** for consistent reporting
- **Parameter validation** and sanitization
- **Custom parameter mapping** for non-standard campaigns

### ⚡ Performance Optimization

**Built for scale**:
- **Async tracking** - Non-blocking attribution calls
- **Database optimization** - Indexed queries, efficient schema
- **Caching layer** - Redis/Memcached integration ready
- **Load balancing** - Distributed tracking endpoints
- **Batch processing** - Bulk attribution resolution

### 🔒 Security & Privacy

**Enterprise-grade protection**:
- **Nonce verification** for all API calls
- **Input sanitization** and validation
- **Bot detection** and filtering
- **Rate limiting** to prevent abuse
- **GDPR compliance** with optional tracking
- **Fraud prevention** algorithms

## API Reference

### REST Endpoints

#### Track Click
```http
POST /wp-json/khm/v1/track/click
```

**Parameters:**
- `affiliate_id` (required) - Affiliate identifier
- `product_id` (optional) - Target product/page
- `campaign_data` (optional) - UTM parameters object
- `client_data` (optional) - Browser/device information

**Response:**
```json
{
  "success": true,
  "click_id": "click_abc123def456",
  "attribution_methods": ["server_side_event", "first_party_cookie"],
  "expires": "2024-02-15T10:30:00Z"
}
```

#### Track Conversion
```http
POST /wp-json/khm/v1/track/conversion
```

**Parameters:**
- `order_id` (required) - Transaction identifier
- `order_value` (required) - Commission base amount
- `attribution_data` (optional) - Known attribution hints

**Response:**
```json
{
  "success": true,
  "attribution": {
    "affiliate_id": 123,
    "click_id": "click_abc123def456",
    "attribution_method": "server_side_event",
    "confidence": 0.95,
    "commission_amount": 25.50,
    "attribution_explanation": "Server-side event correlation with 95% confidence"
  }
}
```

#### Attribution Lookup
```http
GET /wp-json/khm/v1/attribution/lookup
```

**Parameters:**
- `session_id` (optional) - Session identifier
- `user_id` (optional) - Logged-in user ID
- `lookback_days` (optional) - Attribution window override

**Response:**
```json
{
  "success": true,
  "attribution_chain": [
    {
      "timestamp": "2024-01-15T14:30:00Z",
      "affiliate_id": 123,
      "utm_source": "google",
      "utm_medium": "cpc",
      "attribution_weight": 0.4
    },
    {
      "timestamp": "2024-01-16T09:15:00Z", 
      "affiliate_id": 456,
      "utm_source": "facebook",
      "utm_medium": "social",
      "attribution_weight": 0.6
    }
  ]
}
```

### JavaScript API

#### Initialize Tracking
```javascript
// Auto-initialization
jQuery(document).ready(function($) {
    KHMAttribution.init({
        debug: false,
        attribution_window: 30,
        enable_fingerprinting: false
    });
});
```

#### Manual Click Tracking
```javascript
// Track specific link clicks
KHMAttribution.trackClick({
    affiliate_id: 123,
    product_id: 'prod_456',
    custom_data: {
        placement: 'sidebar',
        campaign: 'summer_sale'
    }
});
```

#### Conversion Tracking
```javascript
// Track conversions (typically on thank-you page)
KHMAttribution.trackConversion({
    order_id: 'order_789',
    order_value: 125.00,
    commission_rate: 0.10
});
```

## Configuration

### WordPress Admin Settings

Navigate to **KHM Plugin → Attribution Settings**:

#### Tracking Configuration
- **Attribution Window**: 7-90 days (default: 30)
- **Primary Attribution Method**: First-touch, Last-touch, Multi-touch
- **Fallback Methods**: Enable/disable specific tracking methods
- **Cookie Lifetime**: 1-365 days (default: 30)

#### Performance Settings  
- **Async Tracking**: Enable non-blocking attribution calls
- **Batch Processing**: Group attribution events for efficiency
- **Cache Duration**: Set attribution cache lifetime
- **Database Cleanup**: Auto-purge old attribution data

#### Privacy Settings
- **Fingerprinting**: Enable optional device fingerprinting
- **GDPR Compliance**: Respect Do Not Track headers
- **Data Retention**: Automatic data purging schedule
- **Anonymization**: Hash personal identifiers

### Developer Configuration

#### Constants (wp-config.php)
```php
// Attribution system settings
define('KHM_ATTRIBUTION_WINDOW', 30); // Days
define('KHM_ENABLE_FINGERPRINTING', false);
define('KHM_ATTRIBUTION_DEBUG', false);
define('KHM_BATCH_PROCESSING', true);

// Performance settings
define('KHM_ATTRIBUTION_CACHE_TTL', 3600); // Seconds
define('KHM_MAX_ATTRIBUTION_EVENTS', 10000);
define('KHM_ATTRIBUTION_CLEANUP_DAYS', 90);
```

#### Hooks & Filters

**Action Hooks:**
```php
// Before attribution is processed
do_action('khm_before_attribution', $attribution_data);

// After successful attribution
do_action('khm_after_attribution', $attribution_result);

// On attribution failure
do_action('khm_attribution_failed', $error_data);
```

**Filter Hooks:**
```php
// Modify attribution data before processing
$attribution_data = apply_filters('khm_attribution_data', $data);

// Customize attribution window
$window = apply_filters('khm_attribution_window', 30, $affiliate_id);

// Modify commission calculation
$commission = apply_filters('khm_commission_amount', $base_amount, $attribution);
```

## Database Schema

### Attribution Events Table
```sql
CREATE TABLE khm_attribution_events (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    click_id VARCHAR(50) UNIQUE NOT NULL,
    affiliate_id BIGINT NOT NULL,
    session_id VARCHAR(100),
    user_id BIGINT DEFAULT NULL,
    
    -- Attribution data
    utm_source VARCHAR(100),
    utm_medium VARCHAR(100), 
    utm_campaign VARCHAR(200),
    utm_content VARCHAR(200),
    utm_term VARCHAR(200),
    
    -- Client information
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer_url TEXT,
    landing_page TEXT,
    
    -- Technical data
    screen_resolution VARCHAR(20),
    browser_language VARCHAR(10),
    timezone VARCHAR(50),
    fingerprint_hash VARCHAR(64),
    
    -- Metadata
    created_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    attribution_method VARCHAR(50),
    
    INDEX idx_affiliate_id (affiliate_id),
    INDEX idx_session_id (session_id),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_expires_at (expires_at),
    INDEX idx_click_id (click_id)
);
```

### Conversion Tracking Table
```sql
CREATE TABLE khm_conversion_tracking (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    order_id VARCHAR(100) UNIQUE NOT NULL,
    click_id VARCHAR(50),
    affiliate_id BIGINT,
    
    -- Financial data
    order_value DECIMAL(10,2) NOT NULL,
    commission_amount DECIMAL(10,2),
    commission_rate DECIMAL(5,4),
    
    -- Attribution details
    attribution_method VARCHAR(50),
    attribution_confidence DECIMAL(3,2),
    attribution_explanation TEXT,
    multi_touch_data JSON,
    
    -- Metadata
    created_at DATETIME NOT NULL,
    processed_at DATETIME,
    status ENUM('pending', 'attributed', 'failed') DEFAULT 'pending',
    
    INDEX idx_order_id (order_id),
    INDEX idx_affiliate_id (affiliate_id),
    INDEX idx_click_id (click_id),
    INDEX idx_created_at (created_at),
    INDEX idx_status (status)
);
```

## Testing & Validation

### Automated Test Suite

Run the comprehensive test suite:
```bash
# From WordPress root
php wp-content/plugins/khm-plugin/tests/test-attribution-system.php
```

### Manual Testing Scenarios

#### Scenario 1: ITP/Safari Resistance
1. Open Safari with ITP enabled
2. Clear all cookies and site data
3. Visit affiliate link with UTM parameters
4. Complete purchase in new session
5. Verify attribution still works via server-side events

#### Scenario 2: AdBlock Resistance  
1. Install uBlock Origin or similar
2. Enable strict blocking lists
3. Test affiliate link tracking
4. Verify fallback methods activate
5. Confirm conversion attribution succeeds

#### Scenario 3: Multi-Touch Attribution
1. Visit via first affiliate link (Affiliate A)
2. Leave site, return via second affiliate link (Affiliate B)  
3. Complete purchase
4. Verify both affiliates receive appropriate attribution credit

#### Scenario 4: Cross-Device Attribution
1. Click affiliate link on mobile device
2. Complete purchase on desktop (same user)
3. Verify attribution bridges devices correctly
4. Check confidence scores and explanation

### Performance Benchmarks

**Target Performance Metrics:**
- **Click tracking**: < 50ms response time
- **Conversion attribution**: < 100ms processing time  
- **Database queries**: < 10ms average
- **Memory usage**: < 5MB per attribution event
- **Concurrent tracking**: 1000+ events/second

## Troubleshooting

### Common Issues

#### Attribution Not Working
**Symptoms**: Conversions not attributed to affiliates

**Diagnostic Steps:**
1. Check if tracking JavaScript loads: `KHMAttribution` object exists
2. Verify REST API endpoints respond: Test `/khm/v1/track/click`
3. Examine browser console for JavaScript errors
4. Review server logs for PHP errors
5. Confirm database tables exist and are populated

**Solutions:**
- Ensure nonce verification passes
- Check WordPress REST API is enabled
- Verify plugin activation and file permissions
- Clear any caching plugins

#### Low Attribution Confidence
**Symptoms**: High percentage of "failed" attributions

**Diagnostic Steps:**
1. Review attribution method priorities
2. Check client-side tracking implementation
3. Analyze user behavior patterns (bot traffic, etc.)
4. Examine attribution window settings

**Solutions:**
- Extend attribution window for longer sales cycles
- Enable additional fallback methods
- Implement better bot detection
- Adjust attribution confidence thresholds

#### Performance Issues
**Symptoms**: Slow page loads, high server resource usage

**Diagnostic Steps:**
1. Profile database query performance
2. Monitor memory usage during attribution
3. Check for blocking API calls
4. Analyze tracking volume and patterns

**Solutions:**
- Enable async tracking mode
- Implement caching for frequently accessed data
- Optimize database indexes
- Consider moving to dedicated tracking server

### Debug Mode

Enable debug mode for detailed logging:
```php
// In wp-config.php
define('KHM_ATTRIBUTION_DEBUG', true);
```

Debug information appears in:
- Browser console (client-side events)
- WordPress debug log (server-side processing)
- Database debug table (detailed attribution flow)

## Best Practices

### Implementation Guidelines

1. **Progressive Enhancement**: Implement core functionality first, add advanced features incrementally
2. **Graceful Degradation**: Ensure basic attribution works even when advanced methods fail
3. **Privacy First**: Respect user privacy settings and comply with regulations
4. **Performance Monitoring**: Regularly audit system performance and optimize bottlenecks
5. **Data Accuracy**: Validate attribution data quality and implement fraud prevention

### Attribution Strategy

1. **Know Your Customer Journey**: Map typical conversion paths to optimize attribution windows
2. **Test Attribution Methods**: A/B test different attribution models for your business
3. **Monitor Attribution Quality**: Track confidence scores and investigate low-confidence attributions
4. **Regular Audits**: Periodically review attribution accuracy against known conversions
5. **Backup Methods**: Always have fallback attribution methods configured

### Security Considerations

1. **Input Validation**: Sanitize all client-provided data before processing
2. **Rate Limiting**: Implement rate limits on tracking endpoints to prevent abuse
3. **Access Control**: Restrict administrative functions to authorized users only
4. **Data Encryption**: Encrypt sensitive attribution data in transit and at rest
5. **Regular Updates**: Keep system updated with latest security patches

## Conclusion

The Advanced Attribution System provides enterprise-grade affiliate tracking that works reliably in the modern web environment. By combining multiple attribution methods with intelligent fallbacks, it ensures accurate commission attribution even as privacy technologies continue to evolve.

The system is designed to scale from small affiliate programs to enterprise-level tracking requirements while maintaining accuracy, performance, and privacy compliance.

For technical support or advanced configuration assistance, refer to the developer documentation or contact the KHM Plugin development team.