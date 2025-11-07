# TouchPoint MailChimp Integration

A comprehensive WordPress plugin that integrates MailChimp with your WordPress site, providing user synchronization, e-commerce tracking, and subscription management. Built to emulate the familiar MC4WP (MailChimp for WordPress) Premium plugin structure that content teams are comfortable with.

## Features

### Core Functionality
- **MailChimp API Integration**: Full API v3.0 support with robust error handling
- **User Synchronization**: Automatic sync of WordPress users to MailChimp lists
- **E-commerce Tracking**: WooCommerce and Easy Digital Downloads integration
- **Subscription Forms**: Customizable subscription forms with multiple styles
- **Admin Interface**: Clean, user-friendly admin interface matching WordPress standards

### Advanced Features
- **Bulk User Operations**: Sync all users or selected users to MailChimp
- **Real-time Sync**: Automatic synchronization on user registration and updates
- **Interest Groups**: Support for MailChimp interest categories and groups
- **Field Mapping**: Map WordPress user fields to MailChimp merge fields
- **Cart Abandonment**: Track abandoned carts for recovery campaigns
- **Comprehensive Logging**: Detailed activity logs with multiple log levels

### KHM Integration
- **TouchPoint Compatibility**: Seamless integration with existing TouchPoint suite
- **Custom Tracking**: Enhanced tracking for KHM-specific events and conversions
- **Service Integration**: Leverages existing KHM service architecture

## Installation

1. Upload the plugin folder to `/wp-content/plugins/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to TouchPoint > MailChimp in the admin menu
4. Enter your MailChimp API key and configure settings

## Configuration

### API Setup
1. Obtain your MailChimp API key from your MailChimp account
2. Go to TouchPoint > MailChimp > Settings
3. Enter your API key and test the connection
4. Select your default list for subscriptions

### User Synchronization
1. Navigate to the User Sync tab
2. Enable automatic user synchronization
3. Configure field mappings between WordPress and MailChimp
4. Set up user tags and interest group assignments

### E-commerce Integration
1. Go to the E-commerce tab
2. Enable e-commerce tracking
3. Configure store settings and currency
4. Set up order status triggers for synchronization

## Usage

### Subscription Forms

#### Shortcode Usage
```php
// Basic subscription form
[touchpoint_mailchimp_form]

// Form with specific list
[touchpoint_mailchimp_form list_id="abc123"]

// Inline style form
[touchpoint_mailchimp_form style="inline"]

// Form with interest groups
[touchpoint_mailchimp_form show_interests="true"]

// Compact form
[touchpoint_mailchimp_form style="compact"]

// Minimal form (email only)
[touchpoint_mailchimp_form style="minimal"]
```

#### Programmatic Usage
```php
// Get plugin instance
$mailchimp = TouchPoint_MailChimp::instance();

// Subscribe user to list
$result = $mailchimp->get_api()->subscribe_to_list('list_id', array(
    'email_address' => 'user@example.com',
    'status' => 'subscribed',
    'merge_fields' => array(
        'FNAME' => 'John',
        'LNAME' => 'Doe'
    )
));

// Check subscription status
$member = $mailchimp->get_api()->get_list_member('list_id', 'user@example.com');
```

### User Synchronization

#### Bulk Sync
```php
// Sync all users
$user_sync = TouchPoint_MailChimp_User_Sync::instance();
$users = get_users();
foreach ($users as $user) {
    $user_sync->sync_user_to_mailchimp($user->ID);
}
```

#### Automatic Sync
- Users are automatically synced on registration
- Profile updates trigger re-synchronization
- Email changes are handled gracefully
- User deletion removes from MailChimp lists

### E-commerce Tracking

#### Order Tracking
```php
// Track custom order
$ecommerce = TouchPoint_MailChimp_Ecommerce::instance();
$order_data = array(
    'id' => 'order_123',
    'customer' => array(
        'email_address' => 'customer@example.com'
    ),
    'currency_code' => 'USD',
    'order_total' => 99.99,
    'lines' => array(
        array(
            'id' => 'line_1',
            'product_id' => 'product_123',
            'product_title' => 'Product Name',
            'quantity' => 1,
            'price' => 99.99
        )
    )
);
$ecommerce->track_order($order_data);
```

## File Structure

```
MailChimp-Connection/
├── touchpoint-mailchimp.php          # Main plugin file
├── includes/
│   ├── class-autoloader.php          # PSR-4 autoloader
│   ├── class-api.php                 # MailChimp API wrapper
│   ├── class-settings.php            # Settings management
│   ├── class-logger.php              # Logging functionality
│   ├── admin/
│   │   └── class-admin.php           # Admin interface
│   ├── integrations/
│   │   ├── class-khm.php             # KHM integration
│   │   ├── class-woocommerce.php     # WooCommerce integration
│   │   └── class-edd.php             # EDD integration
│   └── modules/
│       ├── class-user-sync.php       # User synchronization
│       └── class-ecommerce.php       # E-commerce tracking
├── assets/
│   ├── css/
│   │   ├── admin.css                 # Admin styles
│   │   └── frontend.css              # Frontend styles
│   └── js/
│       ├── admin.js                  # Admin JavaScript
│       └── frontend.js               # Frontend JavaScript
└── templates/
    └── subscription-form.php         # Form template
```

## Hooks and Filters

### Actions
```php
// Plugin lifecycle
do_action('tmc_plugin_loaded');
do_action('tmc_settings_saved', $settings);

// User synchronization
do_action('tmc_user_synced', $user_id, $list_id);
do_action('tmc_user_sync_failed', $user_id, $error);

// Form events
do_action('tmc_form_submitted', $form_data);
do_action('tmc_subscription_success', $email, $list_id);
do_action('tmc_subscription_failed', $email, $error);

// E-commerce events
do_action('tmc_order_tracked', $order_id, $order_data);
do_action('tmc_cart_abandoned', $cart_data);
```

### Filters
```php
// Modify user data before sync
apply_filters('tmc_user_merge_fields', $merge_fields, $user);
apply_filters('tmc_user_tags', $tags, $user);

// Customize API requests
apply_filters('tmc_api_request_args', $args, $endpoint);
apply_filters('tmc_api_response', $response, $endpoint);

// Form customization
apply_filters('tmc_form_attributes', $attributes, $form_id);
apply_filters('tmc_form_fields', $fields, $list_id);

// E-commerce data
apply_filters('tmc_order_data', $order_data, $order);
apply_filters('tmc_customer_data', $customer_data, $customer);
```

## Error Handling

The plugin includes comprehensive error handling:

- **API Errors**: Graceful handling of MailChimp API errors with retry logic
- **Rate Limiting**: Automatic rate limit detection and backoff
- **Network Issues**: Timeout handling and connection error recovery
- **Data Validation**: Input sanitization and validation before API calls
- **Logging**: Detailed error logging for debugging and monitoring

## Performance Considerations

- **Caching**: API responses are cached to reduce external requests
- **Batch Processing**: User synchronization uses batch processing for large datasets
- **Background Processing**: Long-running operations use WordPress cron
- **Database Optimization**: Efficient queries and proper indexing
- **CDN Compatibility**: Assets are CDN-friendly with proper cache headers

## Security Features

- **Nonce Verification**: All AJAX requests include nonce verification
- **Capability Checks**: Proper user capability checking for admin functions
- **Data Sanitization**: All input data is sanitized and validated
- **API Key Protection**: Secure storage and handling of API credentials
- **SQL Injection Prevention**: Prepared statements for all database queries

## Support and Troubleshooting

### Common Issues

1. **API Connection Failed**
   - Verify API key is correct
   - Check server firewall settings
   - Ensure cURL is enabled

2. **Users Not Syncing**
   - Check user sync settings
   - Verify list ID is correct
   - Review error logs for details

3. **Forms Not Submitting**
   - Check JavaScript console for errors
   - Verify AJAX URL is correct
   - Ensure nonce is valid

### Debug Mode
Enable debug mode by adding to wp-config.php:
```php
define('TMC_DEBUG', true);
```

### Log Levels
- **Error**: Critical errors that prevent functionality
- **Warning**: Non-critical issues that should be addressed
- **Info**: General information about plugin operations
- **Debug**: Detailed debugging information (only in debug mode)

## Changelog

### Version 1.0.0
- Initial release
- Complete MailChimp API v3.0 integration
- User synchronization module
- E-commerce tracking module
- Subscription forms with multiple styles
- KHM integration
- Comprehensive admin interface
- Logging and error handling system

## License

This plugin is proprietary software developed for the TouchPoint suite. All rights reserved.

## Credits

- Built for the 1927 Magazine Suite
- Inspired by MC4WP Premium plugin structure
- Developed with WordPress coding standards
- Integrated with existing TouchPoint architecture