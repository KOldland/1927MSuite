<?php
/**
 * Export Functionality Validation
 *
 * Validates export functionality works correctly
 *
 * @package KHM_SEO\GEO\Export
 * @since 2.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Export Validation Class
 */
class ExportValidation {

    /**
     * @var array Validation results
     */
    private $results = array();

    /**
     * Constructor - Run validation
     */
    public function __construct() {
        $this->run_validation();
        $this->display_results();
    }

    /**
     * Run validation checks
     */
    private function run_validation() {
        $this->log( 'Starting export functionality validation...', 'info' );

        $this->validate_file_structure();
        $this->validate_class_loading();
        $this->validate_dependencies();
        $this->validate_configuration();
        $this->validate_admin_integration();

        $this->log( 'Export validation completed', 'info' );
    }

    /**
     * Validate file structure
     */
    private function validate_file_structure() {
        $this->log( 'Validating file structure...', 'info' );

        $required_files = array(
            'src/GEO/Export/ExportManager.php',
            'src/GEO/Export/ExportTables.php',
            'assets/js/geo-export-admin.js',
            'assets/css/geo-export-admin.css',
            'test_export_manager.php'
        );

        foreach ( $required_files as $file ) {
            $file_path = dirname( __FILE__ ) . '/' . $file;
            $exists = file_exists( $file_path );
            $this->assert( $exists, "File exists: $file", 'file_structure' );

            if ( $exists ) {
                $readable = is_readable( $file_path );
                $this->assert( $readable, "File readable: $file", 'file_structure' );
            }
        }

        $this->log( 'File structure validation completed', 'success' );
    }

    /**
     * Validate class loading
     */
    private function validate_class_loading() {
        $this->log( 'Validating class loading...', 'info' );

        // Test ExportManager class
        $export_manager_exists = class_exists( 'KHM_SEO\\GEO\\Export\\ExportManager' );
        $this->assert( $export_manager_exists, 'ExportManager class exists', 'class_loading' );

        if ( $export_manager_exists ) {
            // Test constructor
            try {
                $entity_manager = new MockEntityManager();
                $export_manager = new KHM_SEO\GEO\Export\ExportManager( $entity_manager );
                $this->assert( true, 'ExportManager constructor works', 'class_loading' );
            } catch ( Exception $e ) {
                $this->assert( false, 'ExportManager constructor failed: ' . $e->getMessage(), 'class_loading' );
            }
        }

        // Test ExportTables class
        $export_tables_exists = class_exists( 'KHM_SEO\\GEO\\Export\\ExportTables' );
        $this->assert( $export_tables_exists, 'ExportTables class exists', 'class_loading' );

        if ( $export_tables_exists ) {
            try {
                $export_tables = new KHM_SEO\GEO\Export\ExportTables();
                $this->assert( true, 'ExportTables constructor works', 'class_loading' );
            } catch ( Exception $e ) {
                $this->assert( false, 'ExportTables constructor failed: ' . $e->getMessage(), 'class_loading' );
            }
        }

        $this->log( 'Class loading validation completed', 'success' );
    }

    /**
     * Validate dependencies
     */
    private function validate_dependencies() {
        $this->log( 'Validating dependencies...', 'info' );

        // Check PHP version
        $php_version_ok = version_compare( PHP_VERSION, '7.4.0', '>=' );
        $this->assert( $php_version_ok, 'PHP version 7.4+ required (current: ' . PHP_VERSION . ')', 'dependencies' );

        // Check required extensions
        $required_extensions = array( 'json', 'mbstring', 'zip' );
        foreach ( $required_extensions as $ext ) {
            $ext_loaded = extension_loaded( $ext );
            $this->assert( $ext_loaded, "PHP extension loaded: $ext", 'dependencies' );
        }

        // Check WordPress functions availability (in mock mode)
        $wp_functions = array( 'wp_upload_dir', 'wp_create_nonce', 'admin_url' );
        foreach ( $wp_functions as $func ) {
            $func_exists = function_exists( $func );
            $this->assert( $func_exists, "WordPress function available: $func", 'dependencies' );
        }

        $this->log( 'Dependencies validation completed', 'success' );
    }

    /**
     * Validate configuration
     */
    private function validate_configuration() {
        $this->log( 'Validating configuration...', 'info' );

        try {
            $entity_manager = new MockEntityManager();
            $export_manager = new KHM_SEO\GEO\Export\ExportManager( $entity_manager );

            // Test configuration loading
            $config = $export_manager->get_config();
            $this->assert( is_array( $config ), 'Configuration is array', 'configuration' );

            // Test required config keys
            $required_keys = array( 'enabled', 'default_format', 'export_path', 'max_export_rows' );
            foreach ( $required_keys as $key ) {
                $key_exists = isset( $config[ $key ] );
                $this->assert( $key_exists, "Config key exists: $key", 'configuration' );
            }

            // Test supported formats
            $formats = $export_manager->get_supported_formats();
            $this->assert( is_array( $formats ), 'Supported formats available', 'configuration' );
            $this->assert( count( $formats ) > 0, 'At least one export format supported', 'configuration' );

        } catch ( Exception $e ) {
            $this->assert( false, 'Configuration validation failed: ' . $e->getMessage(), 'configuration' );
        }

        $this->log( 'Configuration validation completed', 'success' );
    }

    /**
     * Validate admin integration
     */
    private function validate_admin_integration() {
        $this->log( 'Validating admin integration...', 'info' );

        // Check admin assets exist
        $admin_js = dirname( __FILE__ ) . '/assets/js/geo-export-admin.js';
        $admin_css = dirname( __FILE__ ) . '/assets/css/geo-export-admin.css';

        $this->assert( file_exists( $admin_js ), 'Admin JavaScript file exists', 'admin_integration' );
        $this->assert( file_exists( $admin_css ), 'Admin CSS file exists', 'admin_integration' );

        // Validate JavaScript structure
        $js_content = file_get_contents( $admin_js );
        $has_ajax_handler = strpos( $js_content, 'khmGeoExport' ) !== false;
        $this->assert( $has_ajax_handler, 'JavaScript contains AJAX handlers', 'admin_integration' );

        // Validate CSS structure
        $css_content = file_get_contents( $admin_css );
        $has_export_styles = strpos( $css_content, 'khm-export' ) !== false;
        $this->assert( $has_export_styles, 'CSS contains export styles', 'admin_integration' );

        $this->log( 'Admin integration validation completed', 'success' );
    }

    /**
     * Assert validation condition
     *
     * @param bool $condition Validation condition
     * @param string $message Validation message
     * @param string $category Validation category
     */
    private function assert( $condition, $message, $category ) {
        if ( $condition ) {
            $this->results[] = array(
                'category' => $category,
                'status' => 'pass',
                'message' => $message
            );
        } else {
            $this->results[] = array(
                'category' => $category,
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
     * Display validation results
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

        echo "<h2>Export Functionality Validation Results</h2>";
        echo "<p>Total Validations: {$total}</p>";
        echo "<p style='color: green;'>Passed: {$passed}</p>";
        echo "<p style='color: red;'>Failed: {$failed}</p>";

        if ( $failed > 0 ) {
            echo "<h3>Failed Validations:</h3>";
            echo "<ul>";
            foreach ( $this->results as $result ) {
                if ( $result['status'] === 'fail' ) {
                    echo "<li><strong>{$result['category']}:</strong> {$result['message']}</li>";
                }
            }
            echo "</ul>";
        }

        echo "<h3>Validation Summary by Category:</h3>";
        $categories = array();
        foreach ( $this->results as $result ) {
            $category = $result['category'];
            if ( ! isset( $categories[ $category ] ) ) {
                $categories[ $category ] = array( 'pass' => 0, 'fail' => 0 );
            }
            $categories[ $category ][ $result['status'] ]++;
        }

        echo "<ul>";
        foreach ( $categories as $category => $stats ) {
            $total_cat = $stats['pass'] + $stats['fail'];
            $pass_rate = $total_cat > 0 ? round( ( $stats['pass'] / $total_cat ) * 100, 1 ) : 0;
            echo "<li><strong>" . ucfirst( str_replace( '_', ' ', $category ) ) . ":</strong> {$stats['pass']}/{$total_cat} passed ({$pass_rate}%)</li>";
        }
        echo "</ul>";

        if ( $passed === $total ) {
            echo "<p style='color: green; font-weight: bold; font-size: 1.2em;'>✅ All validations passed! Export functionality is ready for production!</p>";
        } else {
            $pass_rate = round( ( $passed / $total ) * 100, 1 );
            echo "<p style='color: orange; font-weight: bold; font-size: 1.2em;'>⚠️ Validation completed with {$pass_rate}% pass rate. Review failed items before production.</p>";
        }
    }
}

/**
 * Mock Entity Manager for validation
 */
class MockEntityManager {
    public function search_entities( $args = array() ) {
        return array();
    }
}

// Run validation if this file is executed directly
if ( basename( __FILE__ ) === basename( $_SERVER['PHP_SELF'] ) ) {
    new ExportValidation();
}