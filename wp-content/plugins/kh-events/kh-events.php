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

// Include core files
require_once KH_EVENTS_DIR . 'includes/class-kh-events.php';

// Activation hook
register_activation_hook(__FILE__, array('KH_Events', 'activate'));

// Deactivation hook
register_deactivation_hook(__FILE__, array('KH_Events', 'deactivate'));

// Initialize the plugin
KH_Events::instance();