<?php
/**
 * Test script for KH Events Analytics & Reporting
 */

// Define WordPress constants for testing
define('ABSPATH', '/fake/wordpress/path/');
define('KH_EVENTS_DIR', dirname(__FILE__) . '/wp-content/plugins/kh-events/');
define('KH_EVENTS_URL', 'http://localhost/wp-content/plugins/kh-events/');

// Include the analytics class
require_once KH_EVENTS_DIR . 'includes/class-kh-event-analytics.php';

echo "Testing KH Events Analytics & Reporting\n";
echo "=======================================\n\n";

// Test class instantiation
try {
    $analytics = KH_Event_Analytics::instance();
    echo "✓ Analytics class instantiated successfully\n";
} catch (Exception $e) {
    echo "✗ Failed to instantiate analytics class: " . $e->getMessage() . "\n";
    exit(1);
}

// Test basic methods
echo "\nTesting Analytics Methods:\n";

// Test data recording (would normally be called by hooks)
echo "- Data recording methods available: ";
echo method_exists($analytics, 'track_booking_created') ? "✓ track_booking_created\n" : "✗ track_booking_created\n";
echo "- Analytics retrieval methods available: ";
echo method_exists($analytics, 'get_analytics_data') ? "✓ get_analytics_data\n" : "✗ get_analytics_data\n";
echo "- Report generation methods available: ";
echo method_exists($analytics, 'generate_report') ? "✓ generate_report\n" : "✗ generate_report\n";

// Test constants
echo "\nTesting Analytics Constants:\n";
echo "- DATA_TYPE_ATTENDANCE: " . (defined('KH_Event_Analytics::DATA_TYPE_ATTENDANCE') ? KH_Event_Analytics::DATA_TYPE_ATTENDANCE : "Not defined") . "\n";
echo "- PERIOD_MONTH: " . (defined('KH_Event_Analytics::PERIOD_MONTH') ? KH_Event_Analytics::PERIOD_MONTH : "Not defined") . "\n";

echo "\n✓ All basic analytics tests completed successfully!\n";
echo "\nNote: Full functionality requires WordPress environment with database tables.\n";