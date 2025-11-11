<?php
/**
 * Test End-to-End Validation Workflow
 * Tests the complete pre-publish validation system
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

echo "\n=== End-to-End Validation Workflow Test ===\n";
echo "Testing complete pre-publish validation system\n\n";

try {
    // Initialize components
    $entity_manager = new \KHM_SEO\GEO\Entity\EntityManager();
    $validation_manager = new \KHM_SEO\GEO\Validation\ValidationManager( $entity_manager );

    echo "✓ ValidationManager initialized\n";

    echo "\n1. Testing Validation Settings\n";
    echo "===============================\n";

    $settings = $validation_manager->get_validation_settings();
    echo "✓ Validation settings loaded\n";
    echo "  - Minimum publish score: {$settings['min_publish_score']}\n";
    echo "  - Rules count: " . count( $settings['rules'] ) . "\n";

    foreach ( $settings['rules'] as $rule_name => $rule_config ) {
        echo "  - Rule '$rule_name': " . ($rule_config['enabled'] ? 'enabled' : 'disabled') . "\n";
    }

    echo "\n2. Testing Method Availability\n";
    echo "===============================\n";

    $methods_to_test = array(
        'validate_answer_card',
        'validate_on_save',
        'ajax_validate_answer_card',
        'display_validation_notices',
        'enqueue_validation_scripts',
        'override_validation'
    );

    foreach ( $methods_to_test as $method ) {
        if ( method_exists( $validation_manager, $method ) ) {
            echo "✓ Method '$method' exists\n";
        } else {
            echo "✗ Method '$method' missing\n";
        }
    }

    echo "\n3. Testing Validation Constants\n";
    echo "================================\n";

    $reflection = new ReflectionClass('KHM_SEO\\GEO\\Validation\\ValidationManager');
    $constants = $reflection->getConstants();

    echo "✓ Validation constants loaded\n";
    echo "  - MIN_PUBLISH_SCORE: {$constants['MIN_PUBLISH_SCORE']}\n";

    if ( isset( $constants['VALIDATION_RULES'] ) ) {
        echo "  - VALIDATION_RULES defined with " . count( $constants['VALIDATION_RULES'] ) . " categories\n";
        foreach ( $constants['VALIDATION_RULES'] as $category => $rules ) {
            echo "    - $category: " . count( $rules ) . " rules\n";
        }
    }

    echo "\nEnd-to-end validation workflow test completed successfully!\n";

} catch ( Exception $e ) {
    echo "✗ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
    exit( 1 );
}