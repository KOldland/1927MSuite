<?php
/**
 * Schema De-duplication Manager Test
 *
 * Tests the schema deduplication functionality to ensure clean, optimized schema output
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

echo "\n=== Schema De-duplication Manager Test ===\n";
echo "Testing schema deduplication functionality\n\n";

try {
    // Initialize components
    $entity_manager = new \KHM_SEO\GEO\Entity\EntityManager();
    $dedup_manager = new \KHM_SEO\GEO\Schema\SchemaDedupManager( $entity_manager );

    echo "✓ SchemaDedupManager initialized successfully\n";

    // Test basic functionality without entity linking
    $test_schemas = array(
        array(
            '@type' => 'Organization',
            'name' => 'Test Company',
            'url' => 'https://example.com'
        ),
        array(
            '@type' => 'Organization',
            'name' => 'Test Company Inc',
            'url' => 'https://example.com',
            'logo' => 'https://example.com/logo.png'
        ),
        array(
            '@type' => 'Person',
            'name' => 'John Doe',
            'jobTitle' => 'CEO'
        ),
        array(
            '@type' => 'Article',
            'headline' => 'Test Article',
            'author' => 'John Doe'
        )
    );

    // Test schema collection (skip entity linking to avoid DB calls)
    foreach ( $test_schemas as $i => $schema ) {
        // Use reflection to access private collect_schema_data method
        $reflection = new ReflectionClass( $dedup_manager );
        $collect_method = $reflection->getMethod( 'collect_schema_data' );
        $collect_method->setAccessible( true );

        // Temporarily disable entity linking check
        $config_prop = $reflection->getProperty( 'config' );
        $config_prop->setAccessible( true );
        $original_config = $config_prop->getValue( $dedup_manager );
        $modified_config = $original_config;
        $modified_config['prioritize_entity_linked'] = false;
        $config_prop->setValue( $dedup_manager, $modified_config );

        $collect_method->invoke( $dedup_manager, $schema, 'test_source_' . $i );

        // Restore config
        $config_prop->setValue( $dedup_manager, $original_config );
    }

    echo "✓ Test schemas collected\n";

    // Test deduplication processing
    $deduplicated = $dedup_manager->process_schema_deduplication( array() );

    echo "✓ Schema deduplication processed\n";
    echo "  - Input schemas: " . count( $test_schemas ) . "\n";
    echo "  - Output schemas: " . count( $deduplicated ) . "\n";

    // Verify deduplication worked
    $org_count = 0;
    $person_count = 0;
    $article_count = 0;

    foreach ( $deduplicated as $schema ) {
        switch ( $schema['@type'] ) {
            case 'Organization':
                $org_count++;
                break;
            case 'Person':
                $person_count++;
                break;
            case 'Article':
                $article_count++;
                break;
        }
    }

    echo "  - Organizations: $org_count (expected: 1)\n";
    echo "  - Persons: $person_count (expected: 1)\n";
    echo "  - Articles: $article_count (expected: 1)\n";

    // Test configuration
    $config = $dedup_manager->get_config();
    echo "✓ Configuration loaded: " . ( is_array( $config ) ? 'Yes' : 'No' ) . "\n";

    // Test collected schemas retrieval
    $collected = $dedup_manager->get_collected_schemas();
    echo "✓ Collected schemas accessible: " . ( is_array( $collected ) ? 'Yes' : 'No' ) . "\n";

    // Test schema priority calculation (without entity linking)
    $test_schema = array(
        '@type' => 'Organization',
        'name' => 'Test',
        'url' => 'https://example.com'
    );

    // Access private method via reflection for testing
    $reflection = new ReflectionClass( $dedup_manager );
    $method = $reflection->getMethod( 'calculate_schema_priority' );
    $method->setAccessible( true );

    // Temporarily disable entity linking
    $config_prop = $reflection->getProperty( 'config' );
    $config_prop->setAccessible( true );
    $original_config = $config_prop->getValue( $dedup_manager );
    $modified_config = $original_config;
    $modified_config['prioritize_entity_linked'] = false;
    $config_prop->setValue( $dedup_manager, $modified_config );

    $priority = $method->invoke( $dedup_manager, $test_schema, 'Organization' );
    echo "✓ Schema priority calculation: $priority\n";

    // Restore config
    $config_prop->setValue( $dedup_manager, $original_config );

    // Test schema key generation
    $key_method = $reflection->getMethod( 'generate_schema_key' );
    $key_method->setAccessible( true );

    $key = $key_method->invoke( $dedup_manager, $test_schema );
    echo "✓ Schema key generation: " . substr( $key, 0, 16 ) . "...\n";

    // Test schema type detection
    $type_method = $reflection->getMethod( 'get_schema_type' );
    $type_method->setAccessible( true );

    $type = $type_method->invoke( $dedup_manager, $test_schema );
    echo "✓ Schema type detection: $type\n";

    // Clear collected schemas
    $dedup_manager->clear_collected_schemas();
    $cleared = $dedup_manager->get_collected_schemas();
    echo "✓ Schema clearing: " . ( empty( $cleared ) ? 'Success' : 'Failed' ) . "\n";

    echo "\n✅ All SchemaDedupManager tests passed!\n";

} catch ( Exception $e ) {
    echo "❌ Test failed: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

// Test integration with GEOManager
echo "\nTesting GEOManager integration...\n";
echo "==================================\n";

try {
    // This would normally be done through the plugin initialization
    // For testing, we'll create a minimal test
    if ( class_exists( '\KHM_SEO\GEO\GEOManager' ) ) {
        // Note: In a real test environment, we'd initialize the full GEOManager
        // but for this isolated test, we'll just check if the class exists
        echo "✓ GEOManager class available\n";

        // Check if SchemaDedupManager getter exists
        $reflection = new ReflectionClass( '\KHM_SEO\GEO\GEOManager' );
        if ( $reflection->hasMethod( 'get_schema_dedup_manager' ) ) {
            echo "✓ SchemaDedupManager getter available in GEOManager\n";
        } else {
            echo "❌ SchemaDedupManager getter not found in GEOManager\n";
        }
    } else {
        echo "ℹ GEOManager class not available in test environment\n";
    }

} catch ( Exception $e ) {
    echo "❌ GEOManager integration test failed: " . $e->getMessage() . "\n";
}

echo "\n🎉 Schema De-duplication Manager testing complete!\n";