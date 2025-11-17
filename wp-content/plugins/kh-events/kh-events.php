<?php
/**
 * Plugin Name: KH-Events
 * Description: Comprehensive event management plugin for 1927MSuite, integrating event creation, bookings, views, and more with suite-wide compatibility.
 * Version: 1.0.0
 * Author: 1927MSuite
 * Text Domain: kh-events
 * Requires at least: 5.6
 * Tested up to: 6.0
 * Requires PHP: 7.1
 * License: GPLv2 or later
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('KH_EVENTS_VERSION', '1.0.0');
define('KH_EVENTS_DIR', plugin_dir_path(__FILE__));
define('KH_EVENTS_URL', plugin_dir_url(__FILE__));
define('KH_EVENTS_BASENAME', plugin_basename(__FILE__));

// Load composer dependencies if available
if (file_exists(KH_EVENTS_DIR . 'vendor/autoload.php')) {
    require_once KH_EVENTS_DIR . 'vendor/autoload.php';
}

// Include core files
require_once KH_EVENTS_DIR . 'includes/interface-kh-events-service-provider.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events-service-provider.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events-container.php';
require_once KH_EVENTS_DIR . 'includes/kh-events-service-providers.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-event-integrations.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-event-analytics.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events-email-marketing.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events-enhanced-api.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-social-media-integration.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-payment-gateways.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-event-bookings.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events-views.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events-admin-settings.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events-woocommerce-bridge.php';
// Include service providers
require_once KH_EVENTS_DIR . 'includes/providers/class-kh-events-database-provider.php';
require_once KH_EVENTS_DIR . 'includes/providers/class-kh-events-admin-provider.php';
require_once KH_EVENTS_DIR . 'includes/providers/class-kh-events-api-provider.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events-table-manager.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events-database.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events-admin.php';
require_once KH_EVENTS_DIR . 'includes/class-kh-events-admin-menus.php';

// Activation hook
register_activation_hook(__FILE__, array('KH_Events', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('KH_Events', 'deactivate'));

// Initialize the plugin with service providers
add_action('plugins_loaded', function() {
    // Register service providers
    kh_events_register_provider('KH_Events_Database_Provider');
    kh_events_register_provider('KH_Events_Admin_Provider');
    kh_events_register_provider('KH_Events_API_Provider');

    // Boot the container and all service providers
    $container = KH_Events_Container::instance();
    $container->boot();

    // Initialize the main plugin class
    KH_Events::instance();
});