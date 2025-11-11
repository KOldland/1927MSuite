<?php
/**
 * Test Auto-linking Engine
 *
 * Validates the auto-linking functionality with various content scenarios.
 *
 * @package KHM_SEO\GEO
 */

require_once 'wp-load.php';

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Initialize GEO Manager
$geo_manager = new \KHM_SEO\GEO\GEOManager();

// Create test entities
$test_entities = array(
    array(
        'canonical' => 'WordPress',
        'type' => 'Technology',
        'scope' => 'global',
        'status' => 'active'
    ),
    array(
        'canonical' => 'SEO',
        'type' => 'Term',
        'scope' => 'global',
        'status' => 'active'
    ),
    array(
        'canonical' => 'Google Analytics',
        'type' => 'Product',
        'scope' => 'global',
        'status' => 'active'
    )
);

echo "Creating test entities...\n";
$entity_ids = array();
foreach ( $test_entities as $entity_data ) {
    $id = $geo_manager->get_entity_manager()->create_entity( $entity_data );
    if ( $id ) {
        $entity_ids[] = $id;
        echo "Created entity: {$entity_data['canonical']} (ID: $id)\n";
    }
}

// Add aliases
$geo_manager->get_entity_manager()->set_entity_aliases( $entity_ids[0], array( 'WP', 'WordPress CMS' ) );
$geo_manager->get_entity_manager()->set_entity_aliases( $entity_ids[1], array( 'Search Engine Optimization' ) );

echo "\nTesting auto-linking...\n";

// Test content
$test_content = '
<h1>Welcome to WordPress</h1>
<p>This is a test post about WordPress and SEO. WordPress is a great platform for SEO optimization.</p>
<p>We also use Google Analytics for tracking. Google Analytics provides valuable insights.</p>
<p>In this <a href="#">existing link</a>, we mention WordPress again.</p>
<code>WordPress code here</code>
';

echo "Original content:\n" . $test_content . "\n\n";

// Process content
$processed_content = $geo_manager->get_entity_manager()->auto_link_entities( $test_content );

echo "Processed content:\n" . $processed_content . "\n\n";

// Clean up
echo "Cleaning up test entities...\n";
foreach ( $entity_ids as $id ) {
    $geo_manager->get_entity_manager()->delete_entity( $id );
    echo "Deleted entity ID: $id\n";
}

echo "\nAuto-linking test completed!\n";
