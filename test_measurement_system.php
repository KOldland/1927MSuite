<?php
/**
 * Test Measurement & Tracking System
 * Tests the analytics and performance tracking functionality
 */

// Simulate WordPress environment
define( 'ABSPATH', __DIR__ . '/wp-content/plugins/khm-seo/' );
define( 'WP_DEBUG', true );

// Define plugin constants
if ( ! defined( 'KHM_SEO_VERSION' ) ) {
    define( 'KHM_SEO_VERSION', '1.0.0' );
}

if ( ! defined( 'KHM_SEO_PLUGIN_FILE' ) ) {
    define( 'KHM_SEO_PLUGIN_FILE', __DIR__ . '/wp-content/plugins/khm-seo/khm-seo.php' );
}

if ( ! defined( 'KHM_SEO_PLUGIN_DIR' ) ) {
    define( 'KHM_SEO_PLUGIN_DIR', __DIR__ . '/wp-content/plugins/khm-seo/' );
}

if ( ! defined( 'KHM_SEO_PLUGIN_URL' ) ) {
    define( 'KHM_SEO_PLUGIN_URL', 'https://example.com/wp-content/plugins/khm-seo/' );
}

if ( ! defined( 'KHM_SEO_PLUGIN_BASENAME' ) ) {
    define( 'KHM_SEO_PLUGIN_BASENAME', 'khm-seo/khm-seo.php' );
}

echo "✓ Plugin constants defined\n";

// Load autoloader
require_once __DIR__ . '/wp-content/plugins/khm-seo/src/Core/Autoloader.php';
spl_autoload_register( array( 'KHM_SEO\Core\Autoloader', 'autoload' ) );
echo "✓ Autoloader registered\n";

// Load WordPress mocks
require_once __DIR__ . '/test-mocks.php';

echo "\n=== Measurement & Tracking System Test ===\n";
echo "Testing analytics and performance tracking functionality\n\n";

try {
    // Initialize components
    $entity_manager = new \KHM_SEO\GEO\Entity\EntityManager();
    $measurement_manager = new \KHM_SEO\GEO\Measurement\MeasurementManager( $entity_manager );
    $measurement_tables = new \KHM_SEO\GEO\Measurement\MeasurementTables();

    echo "✓ Measurement components initialized\n";

    echo "\n1. Testing Database Tables\n";
    echo "===========================\n";

    // Skip table installation in test environment (no WordPress)
    // Just test table name methods
    $metrics_table = $measurement_tables->get_table_name( 'metrics' );
    $search_table = $measurement_tables->get_table_name( 'search_analytics' );
    $performance_table = $measurement_tables->get_table_name( 'performance_history' );

    echo "✓ Table names generated\n";
    echo "  - Metrics table: {$metrics_table}\n";
    echo "  - Search analytics table: {$search_table}\n";
    echo "  - Performance history table: {$performance_table}\n";

    // Test table names array
    $all_tables = $measurement_tables->get_all_table_names();
    echo "✓ All table names retrieved: " . count( $all_tables ) . " tables\n";

    echo "\n2. Testing Measurement Manager\n";
    echo "===============================\n";

    // Test configuration
    $config = $measurement_manager->get_config();
    echo "✓ Configuration loaded\n";
    echo "  - Tracking enabled: " . ($config['tracking_enabled'] ? 'Yes' : 'No') . "\n";
    echo "  - Retention days: {$config['metrics_retention_days']}\n";
    echo "  - Real-time tracking: " . ($config['real_time_tracking'] ? 'Yes' : 'No') . "\n";

    // Test method availability
    $methods_to_test = array(
        'get_analytics',
        'get_performance_summary',
        'ajax_get_analytics',
        'enqueue_admin_scripts',
        'inject_tracking_scripts',
        'track_page_view',
        'ajax_track_engagement'
    );

    echo "\n3. Testing Method Availability\n";
    echo "===============================\n";

    foreach ( $methods_to_test as $method ) {
        if ( method_exists( $measurement_manager, $method ) ) {
            echo "✓ Method '$method' exists\n";
        } else {
            echo "✗ Method '$method' missing\n";
        }
    }

    echo "\nMeasurement & tracking system test completed successfully!\n";

} catch ( Exception $e ) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit( 1 );
}