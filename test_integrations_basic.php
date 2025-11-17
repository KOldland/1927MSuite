<?php
/**
 * Test script for KH Events Third-Party Integrations
 */

// Define WordPress constants for testing
define('ABSPATH', '/fake/wordpress/path/');
define('KH_EVENTS_DIR', dirname(__FILE__) . '/wp-content/plugins/kh-events/');
define('KH_EVENTS_URL', 'http://localhost/wp-content/plugins/kh-events/');

// Include the integrations class
require_once KH_EVENTS_DIR . 'includes/class-kh-event-integrations.php';

echo "Testing KH Events Third-Party Integrations\n";
echo "==========================================\n\n";

// Test class instantiation
try {
    $integrations = KH_Event_Integrations::instance();
    echo "✓ Integrations class instantiated successfully\n";
} catch (Exception $e) {
    echo "✗ Failed to instantiate integrations class: " . $e->getMessage() . "\n";
    exit(1);
}

// Test Zoom integration methods
echo "\nTesting Zoom Integration:\n";
echo "- is_zoom_connected(): " . ($integrations->is_zoom_connected() ? 'true' : 'false') . "\n";
echo "- get_zoom_auth_url(): " . ($integrations->get_zoom_auth_url() ? 'URL generated' : 'No URL (credentials missing)') . "\n";

// Test Eventbrite integration methods
echo "\nTesting Eventbrite Integration:\n";
echo "- is_eventbrite_connected(): " . ($integrations->is_eventbrite_connected() ? 'true' : 'false') . "\n";
echo "- get_eventbrite_auth_url(): " . ($integrations->get_eventbrite_auth_url() ? 'URL generated' : 'No URL (credentials missing)') . "\n";

// Test Facebook integration methods
echo "\nTesting Facebook Integration:\n";
echo "- is_facebook_connected(): " . ($integrations->is_facebook_connected() ? 'true' : 'false') . "\n";
echo "- get_facebook_auth_url(): " . ($integrations->get_facebook_auth_url() ? 'URL generated' : 'No URL (credentials missing)') . "\n";

echo "\n✓ All basic integration tests completed successfully!\n";
echo "\nNote: OAuth URLs will only be generated when API credentials are configured.\n";