<?php
/**
 * WordPress Plugin Activation Test
 * Run this in WordPress admin to verify plugin is ready for activation
 */

if (!defined('ABSPATH')) {
    echo "This file must be run within WordPress environment.\n";
    echo "Please copy this code to a WordPress page or run through WordPress admin.\n";
    exit;
}

echo '<h1>KHM SEO Plugin - Activation Readiness Check</h1>';

$plugin_dir = WP_PLUGIN_DIR . '/khm-seo/';
$plugin_file = $plugin_dir . 'khm-seo.php';

echo '<h2>Plugin Structure Check</h2>';

// Check main plugin file
if (file_exists($plugin_file)) {
    echo '<p style="color: green;">✓ Main plugin file exists: khm-seo.php</p>';
} else {
    echo '<p style="color: red;">✗ Main plugin file missing: khm-seo.php</p>';
    return;
}

// Check core files
$core_files = [
    'src/Core/Autoloader.php' => 'Autoloader',
    'src/Core/Plugin.php' => 'Main Plugin Class',
    'src/Meta/MetaManager.php' => 'MetaManager',
    'src/Core/Activator.php' => 'Activator',
    'src/Core/Deactivator.php' => 'Deactivator'
];

foreach ($core_files as $file => $description) {
    if (file_exists($plugin_dir . $file)) {
        echo "<p style='color: green;'>✓ {$description}: {$file}</p>";
    } else {
        echo "<p style='color: red;'>✗ Missing {$description}: {$file}</p>";
    }
}

echo '<h2>Plugin Activation Test</h2>';

// Try to read plugin header
$plugin_data = get_plugin_data($plugin_file);
if (!empty($plugin_data['Name'])) {
    echo '<p style="color: green;">✓ Plugin header detected: ' . esc_html($plugin_data['Name']) . '</p>';
    echo '<p>Version: ' . esc_html($plugin_data['Version']) . '</p>';
    echo '<p>Description: ' . esc_html($plugin_data['Description']) . '</p>';
} else {
    echo '<p style="color: red;">✗ Plugin header not properly formatted</p>';
}

// Check if plugin is active
if (is_plugin_active('khm-seo/khm-seo.php')) {
    echo '<p style="color: green;">✓ Plugin is currently ACTIVE</p>';
    
    // Test if classes are loaded
    if (class_exists('KHM_SEO\Core\Plugin')) {
        echo '<p style="color: green;">✓ Plugin classes loaded successfully</p>';
        
        // Test MetaManager
        $plugin = KHM_SEO\Core\Plugin::instance();
        if ($plugin) {
            echo '<p style="color: green;">✓ Plugin instance created</p>';
            
            $meta_manager = $plugin->get_meta_manager();
            if ($meta_manager) {
                echo '<p style="color: green;">✓ MetaManager is working</p>';
                
                // Test on current page
                $title = $meta_manager->get_title();
                $description = $meta_manager->get_description();
                
                echo '<h3>Current Page SEO Test</h3>';
                echo '<p><strong>Generated Title:</strong> ' . esc_html($title) . '</p>';
                echo '<p><strong>Generated Description:</strong> ' . esc_html($description) . '</p>';
                
            } else {
                echo '<p style="color: red;">✗ MetaManager not initialized</p>';
            }
        } else {
            echo '<p style="color: red;">✗ Plugin instance not created</p>';
        }
        
    } else {
        echo '<p style="color: red;">✗ Plugin classes not loaded</p>';
    }
    
} else {
    echo '<p style="color: orange;">⚠ Plugin is not active. <a href="' . admin_url('plugins.php') . '">Activate it here</a></p>';
}

echo '<h2>Manual Activation Instructions</h2>';
echo '<ol>';
echo '<li>Go to <a href="' . admin_url('plugins.php') . '">WordPress Admin → Plugins</a></li>';
echo '<li>Find "KHM SEO" in the plugin list</li>';
echo '<li>Click "Activate"</li>';
echo '<li>Visit any page on your site</li>';
echo '<li>View page source (Ctrl+U or Cmd+U)</li>';
echo '<li>Look for meta tags in the &lt;head&gt; section</li>';
echo '</ol>';

echo '<h2>What to Look For in Page Source</h2>';
echo '<pre style="background: #f5f5f5; padding: 10px; border: 1px solid #ddd;">';
echo htmlentities('
<title>Your Page Title | Site Name</title>
<meta name="description" content="Your page description">
<link rel="canonical" href="https://yoursite.com/current-page/">
<meta property="og:title" content="Your Page Title">
<meta property="og:description" content="Your page description">
<meta name="twitter:card" content="summary">
<meta name="generator" content="KHM SEO 1.0.0">
');
echo '</pre>';

?>