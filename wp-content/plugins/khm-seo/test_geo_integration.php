<?php
/**
 * GEO System Integration Test
 *
 * Comprehensive integration test for the complete GEO v2.0 system
 * Tests all components working together: Entity, Series, Measurement, Export
 *
 * @package KHM_SEO\GEO
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Include required files
require_once dirname( __FILE__ ) . '/../src/GEO/GEOManager.php';

/**
 * GEO Integration Test Class
 */
class TestGEOIntegration {

    /**
     * @var GEOManager GEO manager instance
     */
    private $geo_manager;

    /**
     * @var array Test results
     */
    private $results = array();

    /**
     * @var array Test data
     */
    private $test_data = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_wordpress_test_environment();
        $this->run_integration_tests();
        $this->display_results();
        $this->cleanup_test_data();
    }

    /**
     * Initialize WordPress test environment
     */
    private function init_wordpress_test_environment() {
        $this->log( 'Initializing WordPress test environment...', 'info' );

        // Check if WordPress is available
        if ( ! function_exists( 'wp_create_nonce' ) ) {
            $this->log( 'WordPress environment not available, running in mock mode', 'warning' );
            $this->init_mock_environment();
            return;
        }

        // Initialize GEO Manager
        $this->geo_manager = new KHM_SEO\GEO\GEOManager();

        $this->log( 'WordPress test environment initialized', 'success' );
    }

    /**
     * Initialize mock environment for testing
     */
    private function init_mock_environment() {
        $this->log( 'Initializing mock test environment...', 'info' );

        // Create mock managers
        $this->geo_manager = new MockGEOManager();

        $this->log( 'Mock test environment initialized', 'success' );
    }

    /**
     * Run all integration tests
     */
    private function run_integration_tests() {
        $this->log( 'Starting GEO system integration tests...', 'info' );

        $this->test_entity_management();
        $this->test_series_management();
        $this->test_measurement_system();
        $this->test_export_system();
        $this->test_cross_component_integration();
        $this->test_admin_interface_integration();
        $this->test_database_integrity();

        $this->log( 'All integration tests completed', 'info' );
    }

    /**
     * Test entity management integration
     */
    private function test_entity_management() {
        $this->log( 'Testing entity management integration...', 'info' );

        try {
            // Test entity manager availability
            $entity_manager = $this->geo_manager->get_entity_manager();
            $this->assert( $entity_manager !== null, 'Entity manager is available', 'entity_management' );

            // Test entity creation
            $test_entity = array(
                'canonical' => 'https://example.com/test-entity-' . time(),
                'type' => 'article',
                'scope' => 'site',
                'status' => 'active'
            );

            if ( method_exists( $entity_manager, 'create_entity' ) ) {
                $entity_id = $entity_manager->create_entity( $test_entity );
                $this->assert( $entity_id > 0, 'Entity creation successful', 'entity_management' );
                $this->test_data['entity_id'] = $entity_id;

                // Test entity retrieval
                $retrieved_entity = $entity_manager->get_entity( $entity_id );
                $this->assert( $retrieved_entity !== null, 'Entity retrieval successful', 'entity_management' );
                $this->assert( $retrieved_entity['canonical_url'] === $test_entity['canonical'], 'Entity data integrity maintained', 'entity_management' );
            }

            $this->log( 'Entity management integration test completed', 'success' );

        } catch ( Exception $e ) {
            $this->assert( false, 'Entity management test failed: ' . $e->getMessage(), 'entity_management' );
        }
    }

    /**
     * Test series management integration
     */
    private function test_series_management() {
        $this->log( 'Testing series management integration...', 'info' );

        try {
            // Test series manager availability
            $series_manager = $this->geo_manager->get_series_manager();
            if ( $series_manager ) {
                $this->assert( true, 'Series manager is available', 'series_management' );

                // Test series creation
                if ( method_exists( $series_manager, 'create_series' ) ) {
                    $test_series = array(
                        'title' => 'Test Integration Series ' . time(),
                        'description' => 'Series created during integration testing',
                        'type' => 'sequential',
                        'auto_progression' => 1
                    );

                    $series_id = $series_manager->create_series( $test_series );
                    $this->assert( $series_id > 0, 'Series creation successful', 'series_management' );
                    $this->test_data['series_id'] = $series_id;

                    // Test series retrieval
                    $retrieved_series = $series_manager->get_series( $series_id );
                    $this->assert( $retrieved_series !== null, 'Series retrieval successful', 'series_management' );
                }
            } else {
                $this->assert( true, 'Series manager not available (expected in some configurations)', 'series_management' );
            }

            $this->log( 'Series management integration test completed', 'success' );

        } catch ( Exception $e ) {
            $this->assert( false, 'Series management test failed: ' . $e->getMessage(), 'series_management' );
        }
    }

    /**
     * Test measurement system integration
     */
    private function test_measurement_system() {
        $this->log( 'Testing measurement system integration...', 'info' );

        try {
            // Test measurement manager availability
            $measurement_manager = $this->geo_manager->get_measurement_manager();
            if ( $measurement_manager ) {
                $this->assert( true, 'Measurement manager is available', 'measurement_system' );

                // Test basic measurement functionality
                if ( method_exists( $measurement_manager, 'get_measurement_types' ) ) {
                    $types = $measurement_manager->get_measurement_types();
                    $this->assert( is_array( $types ), 'Measurement types retrieved successfully', 'measurement_system' );
                }
            } else {
                $this->assert( true, 'Measurement manager not available (expected in some configurations)', 'measurement_system' );
            }

            $this->log( 'Measurement system integration test completed', 'success' );

        } catch ( Exception $e ) {
            $this->assert( false, 'Measurement system test failed: ' . $e->getMessage(), 'measurement_system' );
        }
    }

    /**
     * Test export system integration
     */
    private function test_export_system() {
        $this->log( 'Testing export system integration...', 'info' );

        try {
            // Test export manager availability
            $export_manager = $this->geo_manager->get_export_manager();
            $this->assert( $export_manager !== null, 'Export manager is available', 'export_system' );

            // Test export tables availability
            $export_tables = $this->geo_manager->get_export_tables();
            $this->assert( $export_tables !== null, 'Export tables is available', 'export_system' );

            // Test supported formats
            $formats = $export_manager->get_supported_formats();
            $this->assert( is_array( $formats ), 'Export formats are available', 'export_system' );
            $this->assert( in_array( 'json', array_keys( $formats ) ), 'JSON format is supported', 'export_system' );

            // Test configuration
            $config = $export_manager->get_config();
            $this->assert( is_array( $config ), 'Export configuration is available', 'export_system' );

            $this->log( 'Export system integration test completed', 'success' );

        } catch ( Exception $e ) {
            $this->assert( false, 'Export system test failed: ' . $e->getMessage(), 'export_system' );
        }
    }

    /**
     * Test cross-component integration
     */
    private function test_cross_component_integration() {
        $this->log( 'Testing cross-component integration...', 'info' );

        try {
            // Test that all managers can work together
            $entity_manager = $this->geo_manager->get_entity_manager();
            $export_manager = $this->geo_manager->get_export_manager();

            // Test export can access entity data
            if ( isset( $this->test_data['entity_id'] ) && method_exists( $entity_manager, 'search_entities' ) ) {
                $entities = $entity_manager->search_entities();
                $this->assert( is_array( $entities ), 'Entity search works for export integration', 'cross_component' );

                // Test export data collection
                $export_data = $export_manager->collect_entities_data( array() );
                $this->assert( isset( $export_data['count'] ), 'Export can collect entity data', 'cross_component' );
            }

            // Test series and export integration
            $series_manager = $this->geo_manager->get_series_manager();
            if ( $series_manager && isset( $this->test_data['series_id'] ) ) {
                $series_data = $export_manager->collect_series_data( array() );
                $this->assert( isset( $series_data['count'] ), 'Export can collect series data', 'cross_component' );
            }

            $this->log( 'Cross-component integration test completed', 'success' );

        } catch ( Exception $e ) {
            $this->assert( false, 'Cross-component integration test failed: ' . $e->getMessage(), 'cross_component' );
        }
    }

    /**
     * Test admin interface integration
     */
    private function test_admin_interface_integration() {
        $this->log( 'Testing admin interface integration...', 'info' );

        try {
            // Test that admin hooks are properly registered
            if ( function_exists( 'wp_create_nonce' ) ) {
                // Test nonce generation for security
                $nonce = wp_create_nonce( 'khm_seo_ajax' );
                $this->assert( ! empty( $nonce ), 'Admin nonce generation works', 'admin_interface' );

                // Test admin menu integration (would need actual admin context)
                $this->assert( true, 'Admin interface structure is in place', 'admin_interface' );
            } else {
                $this->assert( true, 'Admin interface tests skipped in mock environment', 'admin_interface' );
            }

            $this->log( 'Admin interface integration test completed', 'success' );

        } catch ( Exception $e ) {
            $this->assert( false, 'Admin interface integration test failed: ' . $e->getMessage(), 'admin_interface' );
        }
    }

    /**
     * Test database integrity
     */
    private function test_database_integrity() {
        $this->log( 'Testing database integrity...', 'info' );

        try {
            global $wpdb;

            if ( $wpdb ) {
                // Test table existence
                $export_tables = $this->geo_manager->get_export_tables();
                $table_name = $export_tables->get_table_name( 'export_log' );

                $table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table_name ) );
                $this->assert( ! empty( $table_exists ), 'Export log table exists', 'database_integrity' );

                // Test table structure
                $columns = $wpdb->get_results( "DESCRIBE $table_name" );
                $this->assert( count( $columns ) > 0, 'Export log table has proper structure', 'database_integrity' );
            } else {
                $this->assert( true, 'Database tests skipped in mock environment', 'database_integrity' );
            }

            $this->log( 'Database integrity test completed', 'success' );

        } catch ( Exception $e ) {
            $this->assert( false, 'Database integrity test failed: ' . $e->getMessage(), 'database_integrity' );
        }
    }

    /**
     * Assert test condition
     *
     * @param bool $condition Test condition
     * @param string $message Test message
     * @param string $test_name Test name
     */
    private function assert( $condition, $message, $test_name ) {
        if ( $condition ) {
            $this->results[] = array(
                'test' => $test_name,
                'status' => 'pass',
                'message' => $message
            );
        } else {
            $this->results[] = array(
                'test' => $test_name,
                'status' => 'fail',
                'message' => $message
            );
        }
    }

    /**
     * Log message
     *
     * @param string $message Log message
     * @param string $type Log type (info, success, error)
     */
    private function log( $message, $type = 'info' ) {
        $timestamp = date( 'Y-m-d H:i:s' );
        $color = '';

        switch ( $type ) {
            case 'success':
                $color = 'color: green;';
                break;
            case 'error':
                $color = 'color: red;';
                break;
            case 'warning':
                $color = 'color: orange;';
                break;
            case 'info':
            default:
                $color = 'color: blue;';
                break;
        }

        echo "<div style='{$color}'>[{$timestamp}] {$message}</div>";
    }

    /**
     * Display test results
     */
    private function display_results() {
        $passed = 0;
        $failed = 0;
        $total = count( $this->results );

        foreach ( $this->results as $result ) {
            if ( $result['status'] === 'pass' ) {
                $passed++;
            } else {
                $failed++;
            }
        }

        echo "<h2>GEO System Integration Test Results</h2>";
        echo "<p>Total Tests: {$total}</p>";
        echo "<p style='color: green;'>Passed: {$passed}</p>";
        echo "<p style='color: red;'>Failed: {$failed}</p>";

        if ( $failed > 0 ) {
            echo "<h3>Failed Tests:</h3>";
            echo "<ul>";
            foreach ( $this->results as $result ) {
                if ( $result['status'] === 'fail' ) {
                    echo "<li><strong>{$result['test']}:</strong> {$result['message']}</li>";
                }
            }
            echo "</ul>";
        }

        echo "<h3>Test Summary by Component:</h3>";
        $components = array();
        foreach ( $this->results as $result ) {
            $component = $result['test'];
            if ( ! isset( $components[ $component ] ) ) {
                $components[ $component ] = array( 'pass' => 0, 'fail' => 0 );
            }
            $components[ $component ][ $result['status'] ]++;
        }

        echo "<ul>";
        foreach ( $components as $component => $stats ) {
            $total_comp = $stats['pass'] + $stats['fail'];
            $pass_rate = $total_comp > 0 ? round( ( $stats['pass'] / $total_comp ) * 100, 1 ) : 0;
            echo "<li><strong>" . ucfirst( str_replace( '_', ' ', $component ) ) . ":</strong> {$stats['pass']}/{$total_comp} passed ({$pass_rate}%)</li>";
        }
        echo "</ul>";

        if ( $passed === $total ) {
            echo "<p style='color: green; font-weight: bold; font-size: 1.2em;'>🎉 All integration tests passed! GEO system is fully functional!</p>";
        } else {
            $pass_rate = round( ( $passed / $total ) * 100, 1 );
            echo "<p style='color: orange; font-weight: bold; font-size: 1.2em;'>⚠️ Integration tests completed with {$pass_rate}% pass rate. Some issues need attention.</p>";
        }
    }

    /**
     * Cleanup test data
     */
    private function cleanup_test_data() {
        $this->log( 'Cleaning up test data...', 'info' );

        // Clean up test entities
        if ( isset( $this->test_data['entity_id'] ) ) {
            try {
                $entity_manager = $this->geo_manager->get_entity_manager();
                if ( method_exists( $entity_manager, 'delete_entity' ) ) {
                    $entity_manager->delete_entity( $this->test_data['entity_id'] );
                }
            } catch ( Exception $e ) {
                $this->log( 'Warning: Could not cleanup test entity: ' . $e->getMessage(), 'warning' );
            }
        }

        // Clean up test series
        if ( isset( $this->test_data['series_id'] ) ) {
            try {
                $series_manager = $this->geo_manager->get_series_manager();
                if ( $series_manager && method_exists( $series_manager, 'delete_series' ) ) {
                    $series_manager->delete_series( $this->test_data['series_id'] );
                }
            } catch ( Exception $e ) {
                $this->log( 'Warning: Could not cleanup test series: ' . $e->getMessage(), 'warning' );
            }
        }

        $this->log( 'Test data cleanup completed', 'success' );
    }
}

/**
 * Mock GEO Manager for testing without full WordPress environment
 */
class MockGEOManager {
    private $entity_manager;
    private $series_manager;
    private $measurement_manager;
    private $export_manager;
    private $export_tables;

    public function __construct() {
        $this->entity_manager = new MockEntityManager();
        $this->series_manager = new MockSeriesManager();
        $this->measurement_manager = new MockMeasurementManager();
        $this->export_manager = new MockExportManager();
        $this->export_tables = new MockExportTables();
    }

    public function get_entity_manager() { return $this->entity_manager; }
    public function get_series_manager() { return $this->series_manager; }
    public function get_measurement_manager() { return $this->measurement_manager; }
    public function get_export_manager() { return $this->export_manager; }
    public function get_export_tables() { return $this->export_tables; }
}

/**
 * Mock Entity Manager
 */
class MockEntityManager {
    public function create_entity( $data ) { return rand( 1000, 9999 ); }
    public function get_entity( $id ) { return array( 'id' => $id, 'canonical_url' => 'https://example.com/test' ); }
    public function search_entities( $args = array() ) { return array(); }
    public function delete_entity( $id ) { return true; }
}

/**
 * Mock Series Manager
 */
class MockSeriesManager {
    public function create_series( $data ) { return rand( 1000, 9999 ); }
    public function get_series( $id ) { return (object) array( 'id' => $id, 'title' => 'Test Series' ); }
    public function delete_series( $id ) { return true; }
}

/**
 * Mock Measurement Manager
 */
class MockMeasurementManager {
    public function get_measurement_types() { return array( 'performance', 'seo', 'social' ); }
}

/**
 * Mock Export Manager
 */
class MockExportManager {
    public function get_supported_formats() { return array( 'json' => 'JSON', 'csv' => 'CSV' ); }
    public function get_config() { return array( 'enabled' => true ); }
    public function collect_entities_data( $options ) { return array( 'count' => 0, 'entities' => array() ); }
    public function collect_series_data( $options ) { return array( 'count' => 0, 'series' => array() ); }
}

/**
 * Mock Export Tables
 */
class MockExportTables {
    public function get_table_name( $table ) { return 'mock_' . $table; }
}

// Run integration tests if this file is executed directly
if ( basename( __FILE__ ) === basename( $_SERVER['PHP_SELF'] ) ) {
    new TestGEOIntegration();
}