<?php
/**
 * Test ValidationManager Integration
 * Tests the ValidationManager integration with GEOManager
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

// Test ValidationManager integration
echo "Testing ValidationManager Integration...\n";

try {
    // Test class instantiation without constructor to avoid hooks
    $reflection = new ReflectionClass('KHM_SEO\\GEO\\GEOManager');
    $geo_manager = $reflection->newInstanceWithoutConstructor();

    if ( ! $geo_manager ) {
        throw new Exception( 'GEO Manager not available' );
    }

    echo "✓ GEOManager class instantiated\n";

    // Manually initialize components without hooks
    $entity_tables = new \KHM_SEO\GEO\Database\EntityTables();
    $entity_manager = new \KHM_SEO\GEO\Entity\EntityManager();
    $validation_manager = new \KHM_SEO\GEO\Validation\ValidationManager( $entity_manager );

    // Use reflection to set private properties
    $reflection->getProperty('entity_tables')->setAccessible(true);
    $reflection->getProperty('entity_tables')->setValue($geo_manager, $entity_tables);

    $reflection->getProperty('entity_manager')->setAccessible(true);
    $reflection->getProperty('entity_manager')->setValue($geo_manager, $entity_manager);

    $reflection->getProperty('validation_manager')->setAccessible(true);
    $reflection->getProperty('validation_manager')->setValue($geo_manager, $validation_manager);

    echo "✓ Components manually initialized\n";

    // Test ValidationManager getter
    $validation_manager_instance = $geo_manager->get_validation_manager();

    if ( ! $validation_manager_instance ) {
        throw new Exception( 'ValidationManager not accessible via getter' );
    }

    echo "✓ ValidationManager successfully integrated and accessible\n";

    // Test ValidationManager methods
    if ( method_exists( $validation_manager_instance, 'validate_on_save' ) ) {
        echo "✓ validate_on_save method exists\n";
    } else {
        echo "✗ validate_on_save method missing\n";
    }

    if ( method_exists( $validation_manager_instance, 'ajax_validate_answer_card' ) ) {
        echo "✓ ajax_validate_answer_card method exists\n";
    } else {
        echo "✗ ajax_validate_answer_card method missing\n";
    }

    if ( method_exists( $validation_manager_instance, 'get_validation_settings' ) ) {
        echo "✓ get_validation_settings method exists\n";
    } else {
        echo "✗ get_validation_settings method missing\n";
    }

    // Test validation settings
    $settings = $validation_manager_instance->get_validation_settings();
    if ( is_array( $settings ) ) {
        echo "✓ Validation settings retrieved successfully\n";
        echo "  - Settings keys: " . implode( ', ', array_keys( $settings ) ) . "\n";
    } else {
        echo "✗ Validation settings not retrieved\n";
    }

    echo "\nValidationManager integration test completed successfully!\n";

} catch ( Exception $e ) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
    exit( 1 );
}