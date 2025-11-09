<?php
/**
 * Quick test file to verify MetaManager functionality
 * Load this in WordPress admin to test the plugin
 */

// Only run if WordPress is loaded
if ( ! defined( 'ABSPATH' ) ) {
    die( 'Direct access not permitted.' );
}

// Include the plugin files
require_once dirname( __FILE__ ) . '/khm-seo.php';

// Test basic functionality
if ( class_exists( 'KHM_SEO\Core\Plugin' ) ) {
    echo '<h2>KHM SEO Plugin Test Results</h2>';
    
    // Get plugin instance
    $plugin = KHM_SEO\Core\Plugin::instance();
    
    if ( $plugin ) {
        echo '<p style="color: green;">✓ Plugin instance created successfully</p>';
        
        // Test MetaManager
        $meta_manager = $plugin->get_meta_manager();
        
        if ( $meta_manager instanceof KHM_SEO\Meta\MetaManager ) {
            echo '<p style="color: green;">✓ MetaManager instantiated successfully</p>';
            
            // Test get_title method
            try {
                $title = $meta_manager->get_title();
                echo '<p style="color: green;">✓ get_title() method works: <strong>' . esc_html( $title ) . '</strong></p>';
            } catch ( Exception $e ) {
                echo '<p style="color: red;">✗ get_title() method error: ' . esc_html( $e->getMessage() ) . '</p>';
            }
            
            // Test get_description method
            try {
                $description = $meta_manager->get_description();
                echo '<p style="color: green;">✓ get_description() method works: <strong>' . esc_html( $description ) . '</strong></p>';
            } catch ( Exception $e ) {
                echo '<p style="color: red;">✗ get_description() method error: ' . esc_html( $e->getMessage() ) . '</p>';
            }
            
        } else {
            echo '<p style="color: red;">✗ MetaManager not properly instantiated</p>';
        }
        
    } else {
        echo '<p style="color: red;">✗ Plugin instance not created</p>';
    }
    
} else {
    echo '<p style="color: red;">✗ Plugin class not found</p>';
}

echo '<h3>WordPress Environment Check</h3>';
echo '<p>WordPress Version: ' . get_bloginfo( 'version' ) . '</p>';
echo '<p>Site Name: ' . get_bloginfo( 'name' ) . '</p>';
echo '<p>Site Description: ' . get_bloginfo( 'description' ) . '</p>';

// Test some meta output
echo '<h3>Sample Meta Output</h3>';
echo '<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">';

if ( isset( $meta_manager ) && $meta_manager ) {
    ob_start();
    $meta_manager->output_meta_tags();
    $meta_output = ob_get_clean();
    echo esc_html( $meta_output );
} else {
    echo 'MetaManager not available';
}

echo '</pre>';
?>