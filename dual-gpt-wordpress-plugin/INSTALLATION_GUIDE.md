# Dual-GPT WordPress Plugin - Installation & Testing Guide

## Quick Start Installation

### 1. Plugin Installation
```bash
# Copy plugin to WordPress
cp -r dual-gpt-wordpress-plugin /path/to/wordpress/wp-content/plugins/

# Or create symlink for development
ln -s /path/to/dual-gpt-wordpress-plugin /path/to/wordpress/wp-content/plugins/dual-gpt-wordpress-plugin
```

### 2. WordPress Configuration
Add to `wp-config.php`:
```php
// Dual-GPT Plugin Configuration
define('DUAL_GPT_OPENAI_API_KEY', 'your-openai-api-key-here');

// Enable debugging for testing
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Optional: Increase PHP limits for AI processing
define('WP_MEMORY_LIMIT', '256M');
define('WP_MAX_MEMORY_LIMIT', '512M');
```

### 3. Plugin Activation
1. Go to WordPress Admin → Plugins
2. Find "Dual-GPT WordPress Plugin for Research + Authoring"
3. Click "Activate"

### 4. Verify Installation
Check that these database tables were created:
- `wp_ai_sessions`
- `wp_ai_jobs`
- `wp_ai_presets`
- `wp_ai_audit`
- `wp_ai_budgets`

## Testing Commands

### Automated Testing
```bash
# Run basic plugin tests
cd /path/to/wordpress/wp-content/plugins/dual-gpt-wordpress-plugin
php test-plugin.php

# Check for PHP syntax errors
find . -name "*.php" -exec php -l {} \;

# Check JavaScript syntax (if Node.js available)
# npm install -g eslint
# eslint assets/js/ admin/js/
```

### Manual Testing Steps

#### 1. Admin Interface Testing
1. Visit `/wp-admin/admin.php?page=dual-gpt-settings`
2. Test API key validation
3. Create/edit/delete presets
4. Check budget management
5. Review audit logs

#### 2. Gutenberg Editor Testing
1. Create new post/page
2. Look for "Dual-GPT Authoring" in sidebar
3. Test research pane with sample prompt
4. Test author pane with sample prompt
5. Verify block insertion

#### 3. REST API Testing
```bash
# Test session creation
curl -X POST /wp-json/dual-gpt/v1/sessions \
  -H "Content-Type: application/json" \
  -d '{"role":"research","title":"Test Session"}'

# Test job creation (replace SESSION_ID)
curl -X POST /wp-json/dual-gpt/v1/jobs \
  -H "Content-Type: application/json" \
  -d '{"session_id":"SESSION_ID","prompt":"Test prompt","model":"gpt-4"}'

# Test preset management
curl /wp-json/dual-gpt/v1/presets
```

## Troubleshooting Common Issues

### Plugin Won't Activate
**Symptoms**: Plugin shows "Plugin file does not exist" or activation fails

**Solutions**:
1. Check file permissions: `chmod -R 755 dual-gpt-wordpress-plugin/`
2. Verify PHP syntax: `php -l dual-gpt-wordpress-plugin.php`
3. Check WordPress error logs
4. Ensure all required files are present

### Database Tables Not Created
**Symptoms**: Missing tables in database

**Solutions**:
1. Deactivate/reactivate plugin
2. Check database user permissions
3. Manually run activation hook:
```php
// In functions.php or via WP-CLI
do_action('activate_dual-gpt-wordpress-plugin/dual-gpt-wordpress-plugin.php');
```

### Gutenberg Sidebar Not Showing
**Symptoms**: No Dual-GPT sidebar in block editor

**Solutions**:
1. Clear browser cache and WordPress cache
2. Check browser console for JavaScript errors
3. Verify user has `edit_posts` capability
4. Check if Gutenberg is enabled

### API Connection Issues
**Symptoms**: "API key not configured" or connection failures

**Solutions**:
1. Verify API key in `wp-config.php`
2. Test API key manually:
```bash
curl -H "Authorization: Bearer YOUR_API_KEY" \
     https://api.openai.com/v1/models
```
3. Check firewall/proxy settings
4. Verify cURL extension is enabled

### JavaScript Errors
**Symptoms**: Console errors, broken functionality

**Solutions**:
1. Check file paths in browser dev tools
2. Verify asset enqueueing in admin
3. Clear all caches (browser, WordPress, CDN)
4. Check for JavaScript minification issues

## Performance Testing

### Load Testing Setup
```bash
# Install WP-CLI if not available
curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
chmod +x wp-cli.phar
sudo mv wp-cli.phar /usr/local/bin/wp

# Test concurrent requests
for i in {1..10}; do
  wp eval "wp_remote_get('http://your-site.com/wp-json/dual-gpt/v1/sessions');" &
done
```

### Memory Usage Monitoring
```php
// Add to wp-config.php for memory monitoring
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);

// Monitor in error log
add_action('shutdown', function() {
    error_log('Memory usage: ' . memory_get_peak_usage(true) / 1024 / 1024 . ' MB');
});
```

## Security Testing Checklist

### Input Validation
- [ ] SQL injection prevention (prepared statements)
- [ ] XSS prevention (output escaping)
- [ ] CSRF protection (nonces)
- [ ] File upload security (if applicable)

### Access Control
- [ ] Admin-only functions protected
- [ ] User capability checks
- [ ] API key server-side only
- [ ] Session ownership validation

### Data Protection
- [ ] Sensitive data not logged
- [ ] API keys encrypted in database
- [ ] Audit logs sanitized
- [ ] Error messages don't leak sensitive info

## Automated Testing Script

Create `test-suite.php` in plugin root:
```php
<?php
// Comprehensive testing script
require_once 'test-plugin.php';

// Additional WordPress-specific tests
function test_wordpress_integration() {
    echo "Testing WordPress integration...\n";

    // Test activation hook
    if (function_exists('dual_gpt_plugin_activation')) {
        dual_gpt_plugin_activation();
        echo "✓ Activation hook works\n";
    }

    // Test admin hooks
    if (has_action('admin_menu', 'Dual_GPT_Plugin::add_admin_menu')) {
        echo "✓ Admin menu hook registered\n";
    }

    // Test REST routes
    global $wp_rest_server;
    if ($wp_rest_server) {
        $routes = $wp_rest_server->get_routes();
        if (isset($routes['dual-gpt/v1'])) {
            echo "✓ REST routes registered\n";
        }
    }
}

test_wordpress_integration();
```

## Deployment Checklist

### Pre-Deployment
- [ ] All tests pass in staging environment
- [ ] Database backup created
- [ ] Rollback plan documented
- [ ] Support team notified

### Post-Deployment
- [ ] Plugin activates successfully
- [ ] Basic functionality verified
- [ ] Error logs monitored for 24 hours
- [ ] User feedback collected
- [ ] Documentation updated

### Rollback Plan
1. Deactivate plugin via admin
2. Drop custom tables (optional, preserves data)
3. Remove plugin files
4. Clear all caches
5. Verify site functionality

## Support Information

For issues during testing:
1. Check WordPress debug logs: `/wp-content/debug.log`
2. Review PHP error logs
3. Test with default WordPress theme
4. Disable other plugins temporarily
5. Use browser dev tools for frontend issues

## Version Information
- Plugin Version: 1.0.0
- Tested WordPress Versions: 5.8 - 6.4
- PHP Requirements: 7.4+
- MySQL Requirements: 5.6+