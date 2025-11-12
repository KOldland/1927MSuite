<?php
/**
 * Test Export Manager
 *
 * Comprehensive test suite for GEO export functionality
 *
 * @package KHM_SEO\GEO\Export
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Include required files
require_once dirname( __FILE__ ) . '/../src/GEO/Export/ExportManager.php';
require_once dirname( __FILE__ ) . '/../src/GEO/Export/ExportTables.php';

/**
 * Test Export Manager Class
 */
class TestExportManager {

    /**
     * @var ExportManager Export manager instance
     */
    private $export_manager;

    /**
     * @var ExportTables Export tables instance
     */
    private $export_tables;

    /**
     * @var array Test results
     */
    private $results = array();

    /**
     * Constructor
     */
    public function __construct() {
        $this->init_test_environment();
        $this->run_all_tests();
        $this->display_results();
    }

    /**
     * Initialize test environment
     */
    private function init_test_environment() {
        // Create mock entity manager
        $entity_manager = $this->create_mock_entity_manager();

        // Create mock series manager
        $series_manager = $this->create_mock_series_manager();

        // Create mock measurement manager
        $measurement_manager = $this->create_mock_measurement_manager();

        // Initialize export tables
        $this->export_tables = new KHM_SEO\GEO\Export\ExportTables();

        // Initialize export manager
        $this->export_manager = new KHM_SEO\GEO\Export\ExportManager(
            $entity_manager,
            $series_manager,
            $measurement_manager
        );

        $this->log( 'Test environment initialized', 'info' );
    }

    /**
     * Create mock entity manager
     *
     * @return MockEntityManager
     */
    private function create_mock_entity_manager() {
        return new MockEntityManager();
    }

    /**
     * Create mock series manager
     *
     * @return MockSeriesManager
     */
    private function create_mock_series_manager() {
        return new MockSeriesManager();
    }

    /**
     * Create mock measurement manager
     *
     * @return MockMeasurementManager
     */
    private function create_mock_measurement_manager() {
        return new MockMeasurementManager();
    }

    /**
     * Run all tests
     */
    private function run_all_tests() {
        $this->log( 'Starting export functionality tests', 'info' );

        $this->test_export_formats();
        $this->test_data_collection();
        $this->test_export_processing();
        $this->test_file_operations();
        $this->test_configuration();
        $this->test_error_handling();
        $this->test_database_operations();

        $this->log( 'All tests completed', 'info' );
    }

    /**
     * Test export formats
     */
    private function test_export_formats() {
        $this->log( 'Testing export formats...', 'info' );

        $formats = $this->export_manager->get_supported_formats();

        $this->assert(
            is_array( $formats ),
            'Supported formats should be an array',
            'test_export_formats'
        );

        $this->assert(
            in_array( 'json', array_keys( $formats ) ),
            'JSON format should be supported',
            'test_export_formats'
        );

        $this->assert(
            in_array( 'csv', array_keys( $formats ) ),
            'CSV format should be supported',
            'test_export_formats'
        );

        $this->assert(
            in_array( 'xml', array_keys( $formats ) ),
            'XML format should be supported',
            'test_export_formats'
        );

        $this->log( 'Export formats test completed', 'success' );
    }

    /**
     * Test data collection
     */
    private function test_data_collection() {
        $this->log( 'Testing data collection...', 'info' );

        // Test entity data collection
        $entity_data = $this->export_manager->collect_entities_data( array() );
        $this->assert(
            isset( $entity_data['count'] ),
            'Entity data should include count',
            'test_data_collection'
        );

        $this->assert(
            isset( $entity_data['entities'] ),
            'Entity data should include entities array',
            'test_data_collection'
        );

        // Test series data collection
        $series_data = $this->export_manager->collect_series_data( array() );
        $this->assert(
            isset( $series_data['count'] ),
            'Series data should include count',
            'test_data_collection'
        );

        $this->assert(
            isset( $series_data['series'] ),
            'Series data should include series array',
            'test_data_collection'
        );

        $this->log( 'Data collection test completed', 'success' );
    }

    /**
     * Test export processing
     */
    private function test_export_processing() {
        $this->log( 'Testing export processing...', 'info' );

        $data_types = array( 'entities' );
        $format = 'json';
        $options = array();

        $export_id = $this->export_manager->start_export( $data_types, $format, $options );

        $this->assert(
            ! empty( $export_id ),
            'Export should return a valid ID',
            'test_export_processing'
        );

        $this->assert(
            is_string( $export_id ),
            'Export ID should be a string',
            'test_export_processing'
        );

        $this->log( 'Export processing test completed', 'success' );
    }

    /**
     * Test file operations
     */
    private function test_file_operations() {
        $this->log( 'Testing file operations...', 'info' );

        $test_content = '{"test": "data"}';
        $format = 'json';
        $options = array( 'compress' => false );

        $filename = $this->export_manager->save_export_file( $test_content, $format, $options );

        $this->assert(
            ! empty( $filename ),
            'File save should return a filename',
            'test_file_operations'
        );

        $this->assert(
            file_exists( $this->export_manager->get_config( 'export_path' ) . $filename ),
            'Exported file should exist',
            'test_file_operations'
        );

        $this->log( 'File operations test completed', 'success' );
    }

    /**
     * Test configuration
     */
    private function test_configuration() {
        $this->log( 'Testing configuration...', 'info' );

        $config = $this->export_manager->get_config();

        $this->assert(
            is_array( $config ),
            'Configuration should be an array',
            'test_configuration'
        );

        $this->assert(
            isset( $config['enabled'] ),
            'Configuration should include enabled setting',
            'test_configuration'
        );

        $this->assert(
            isset( $config['default_format'] ),
            'Configuration should include default format',
            'test_configuration'
        );

        $this->log( 'Configuration test completed', 'success' );
    }

    /**
     * Test error handling
     */
    private function test_error_handling() {
        $this->log( 'Testing error handling...', 'info' );

        // Test invalid format
        try {
            $this->export_manager->format_export_data( array(), 'invalid_format', array() );
            $this->assert( false, 'Should throw exception for invalid format', 'test_error_handling' );
        } catch ( Exception $e ) {
            $this->assert( true, 'Exception thrown for invalid format', 'test_error_handling' );
        }

        // Test empty data types
        try {
            $this->export_manager->start_export( array(), 'json', array() );
            $this->assert( false, 'Should throw exception for empty data types', 'test_error_handling' );
        } catch ( Exception $e ) {
            $this->assert( true, 'Exception thrown for empty data types', 'test_error_handling' );
        }

        $this->log( 'Error handling test completed', 'success' );
    }

    /**
     * Test database operations
     */
    private function test_database_operations() {
        $this->log( 'Testing database operations...', 'info' );

        // Test export log
        $log_data = array(
            'export_id' => 'test_' . time(),
            'user_id' => 1,
            'data_types' => array( 'entities' ),
            'format' => 'json',
            'status' => 'completed'
        );

        $log_id = $this->export_tables->log_export( $log_data );

        $this->assert(
            $log_id > 0,
            'Export log should be created successfully',
            'test_database_operations'
        );

        // Test retrieving export log
        $retrieved_log = $this->export_tables->get_export_log( $log_data['export_id'] );

        $this->assert(
            $retrieved_log !== null,
            'Export log should be retrievable',
            'test_database_operations'
        );

        $this->assert(
            $retrieved_log->export_id === $log_data['export_id'],
            'Retrieved log should match original data',
            'test_database_operations'
        );

        $this->log( 'Database operations test completed', 'success' );
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

        echo "<h2>Test Results Summary</h2>";
        echo "<p>Total Tests: {$total}</p>";
        echo "<p style='color: green;'>Passed: {$passed}</p>";
        echo "<p style='color: red;'>Failed: {$failed}</p>";

        if ( $failed > 0 ) {
            echo "<h3>Failed Tests:</h3>";
            echo "<ul>";
            foreach ( $this->results as $result ) {
                if ( $result['status'] === 'fail' ) {
                    echo "<li>{$result['test']}: {$result['message']}</li>";
                }
            }
            echo "</ul>";
        }

        if ( $passed === $total ) {
            echo "<p style='color: green; font-weight: bold;'>All tests passed! ✓</p>";
        } else {
            echo "<p style='color: red; font-weight: bold;'>Some tests failed ✗</p>";
        }
    }
}

/**
 * Mock Entity Manager
 */
class MockEntityManager {
    public function search_entities( $args = array() ) {
        return array(
            array(
                'id' => 1,
                'canonical_url' => 'https://example.com/page1',
                'entity_type' => 'article',
                'data' => array( 'title' => 'Test Article' ),
                'status' => 'active'
            ),
            array(
                'id' => 2,
                'canonical_url' => 'https://example.com/page2',
                'entity_type' => 'product',
                'data' => array( 'title' => 'Test Product' ),
                'status' => 'active'
            )
        );
    }
}

/**
 * Mock Series Manager
 */
class MockSeriesManager {
    public function get_all_series() {
        return array(
            (object) array(
                'id' => 1,
                'title' => 'Test Series 1',
                'description' => 'A test series',
                'type' => 'sequential',
                'auto_progression' => 1
            )
        );
    }

    public function get_series_items( $series_id ) {
        return array(
            (object) array(
                'id' => 1,
                'series_id' => $series_id,
                'entity_id' => 1,
                'position' => 1
            )
        );
    }
}

/**
 * Mock Measurement Manager
 */
class MockMeasurementManager {
    // Placeholder for measurement manager
}

// Run tests if this file is executed directly
if ( basename( __FILE__ ) === basename( $_SERVER['PHP_SELF'] ) ) {
    new TestExportManager();
}