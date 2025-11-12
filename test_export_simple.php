<?php
/**
 * Simple Export Test Runner
 *
 * Runs basic export functionality tests without full WordPress environment
 */

// Define minimal WordPress constants
define('ABSPATH', '/Users/krisoldland/Documents/GitHub/1927MSuite/');
define('WP_PLUGIN_DIR', ABSPATH . 'wp-content/plugins/');
define('KHM_SEO_PLUGIN_DIR', WP_PLUGIN_DIR . 'khm-seo/');

// Include and register autoloader
require_once KHM_SEO_PLUGIN_DIR . 'src/Core/Autoloader.php';
KHM_SEO\Core\Autoloader::register();

// Use the correct namespace
use KHM_SEO\GEO\Export\ExportManager;
use KHM_SEO\GEO\Export\ExportTables;
use KHM_SEO\GEO\Entity\EntityManager;
use KHM_SEO\GEO\Series\SeriesManager;
use KHM_SEO\GEO\Measurement\MeasurementManager;

// Mock WordPress functions
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) {
        return json_encode($data);
    }
}

if (!function_exists('wp_normalize_path')) {
    function wp_normalize_path($path) {
        return str_replace('\\', '/', $path);
    }
}

if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return 1;
    }
}

if (!function_exists('wp_upload_dir')) {
    function wp_upload_dir() {
        return array(
            'basedir' => '/tmp/wp-uploads',
            'baseurl' => 'https://example.com/wp-content/uploads'
        );
    }
}

if (!function_exists('sanitize_file_name')) {
    function sanitize_file_name($filename) {
        return preg_replace('/[^a-zA-Z0-9-_.]/', '', $filename);
    }
}

// Create uploads directory if it doesn't exist
$upload_dir = '/tmp/wp-uploads';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Mock classes for dependencies
class MockEntityManager {
    public function search_entities($args = array()) {
        return array(
            array(
                'id' => 1,
                'canonical_url' => 'https://example.com/page1',
                'entity_type' => 'article',
                'data' => array('title' => 'Test Article'),
                'status' => 'active'
            )
        );
    }
}

class MockSeriesManager {
    // Mock methods as needed
}

class MockMeasurementManager {
    // Mock methods as needed
}

/**
 * Simple Test Runner
 */
class SimpleExportTest {

    private $results = array();

    public function __construct() {
        $this->run_tests();
        $this->display_results();
    }

    private function run_tests() {
        echo "Running Export Functionality Tests...\n\n";

        $this->test_export_manager_creation();
        $this->test_supported_formats();
        $this->test_export_processing();
        $this->test_configuration();
    }

    private function test_export_manager_creation() {
        echo "Testing ExportManager creation...\n";

        try {
            $entity_manager = new MockEntityManager();
            $series_manager = new MockSeriesManager();
            $measurement_manager = new MockMeasurementManager();

            $export_manager = new ExportManager($entity_manager, $series_manager, $measurement_manager);
            $this->assert(true, "ExportManager created successfully", "export_manager_creation");
            echo "✓ ExportManager created successfully\n";
        } catch (Exception $e) {
            $this->assert(false, "ExportManager creation failed: " . $e->getMessage(), "export_manager_creation");
            echo "✗ ExportManager creation failed: " . $e->getMessage() . "\n";
        }
    }

    private function test_supported_formats() {
        echo "Testing supported formats...\n";

        try {
            $entity_manager = new MockEntityManager();
            $export_manager = new ExportManager($entity_manager);

            $formats = $export_manager->get_supported_formats();

            $this->assert(is_array($formats), "Formats returned as array", "supported_formats");
            $this->assert(isset($formats['json']), "JSON format supported", "supported_formats");
            $this->assert(isset($formats['csv']), "CSV format supported", "supported_formats");

            echo "✓ Supported formats: " . implode(', ', array_keys($formats)) . "\n";
        } catch (Exception $e) {
            $this->assert(false, "Supported formats test failed: " . $e->getMessage(), "supported_formats");
            echo "✗ Supported formats test failed: " . $e->getMessage() . "\n";
        }
    }

    private function test_export_processing() {
        echo "Testing export processing...\n";

        try {
            $entity_manager = new MockEntityManager();
            $export_manager = new ExportManager($entity_manager);

            $data_types = array('entities');
            $format = 'json';
            $options = array();

            $export_id = $export_manager->start_export($data_types, $format, $options);

            $this->assert(!empty($export_id), "Export ID generated", "export_processing");
            $this->assert(is_string($export_id), "Export ID is string", "export_processing");

            echo "✓ Export started with ID: $export_id\n";
        } catch (Exception $e) {
            $this->assert(false, "Export processing test failed: " . $e->getMessage(), "export_processing");
            echo "✗ Export processing test failed: " . $e->getMessage() . "\n";
        }
    }

    private function test_configuration() {
        echo "Testing configuration...\n";

        try {
            $entity_manager = new MockEntityManager();
            $export_manager = new ExportManager($entity_manager);

            $config = $export_manager->get_config();

            $this->assert(is_array($config), "Configuration is array", "configuration");
            $this->assert(isset($config['enabled']), "Enabled setting exists", "configuration");

            echo "✓ Configuration loaded successfully\n";
        } catch (Exception $e) {
            $this->assert(false, "Configuration test failed: " . $e->getMessage(), "configuration");
            echo "✗ Configuration test failed: " . $e->getMessage() . "\n";
        }
    }

    private function assert($condition, $message, $test_name) {
        if ($condition) {
            $this->results[] = array('test' => $test_name, 'status' => 'pass', 'message' => $message);
        } else {
            $this->results[] = array('test' => $test_name, 'status' => 'fail', 'message' => $message);
        }
    }

    private function display_results() {
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "TEST RESULTS SUMMARY\n";
        echo str_repeat("=", 50) . "\n";

        $passed = 0;
        $failed = 0;
        $total = count($this->results);

        foreach ($this->results as $result) {
            if ($result['status'] === 'pass') {
                $passed++;
            } else {
                $failed++;
            }
        }

        echo "Total Tests: $total\n";
        echo "Passed: $passed\n";
        echo "Failed: $failed\n\n";

        if ($failed > 0) {
            echo "FAILED TESTS:\n";
            foreach ($this->results as $result) {
                if ($result['status'] === 'fail') {
                    echo "- {$result['test']}: {$result['message']}\n";
                }
            }
        }

        if ($passed === $total) {
            echo "\n🎉 ALL TESTS PASSED! Export functionality is working correctly.\n";
        } else {
            echo "\n❌ SOME TESTS FAILED. Please review the issues above.\n";
        }
    }
}

// Run the tests
new SimpleExportTest();