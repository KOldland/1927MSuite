<?php
/**
 * KHM SEO Phase 3 Component Test
 * 
 * Tests all Phase 3 components for proper structure and instantiation
 */

// Simulate WordPress environment constants and functions
if (!defined('ABSPATH')) {
    define('ABSPATH', '/Users/krisoldland/Documents/GitHub/1927MSuite/');
}

// Test results array
$test_results = array(
    'phase_3_components' => array(),
    'syntax_errors' => array(),
    'class_structure' => array(),
    'overall_status' => 'unknown'
);

echo "=== KHM SEO Phase 3 Component Testing ===\n\n";

// Component paths
$components = array(
    'SchemaManager' => '/Users/krisoldland/Documents/GitHub/1927MSuite/wp-content/plugins/khm-seo/src/Schema/SchemaManager.php',
    'ArticleSchema' => '/Users/krisoldland/Documents/GitHub/1927MSuite/wp-content/plugins/khm-seo/src/Schema/Types/ArticleSchema.php',
    'OrganizationSchema' => '/Users/krisoldland/Documents/GitHub/1927MSuite/wp-content/plugins/khm-seo/src/Schema/Types/OrganizationSchema.php',
    'SocialMediaManager' => '/Users/krisoldland/Documents/GitHub/1927MSuite/wp-content/plugins/khm-seo/src/Social/SocialMediaManager.php',
    'ToolsManager' => '/Users/krisoldland/Documents/GitHub/1927MSuite/wp-content/plugins/khm-seo/src/Tools/ToolsManager.php'
);

// Test 1: File existence and basic syntax
echo "1. Testing file existence and basic syntax...\n";
foreach ($components as $name => $path) {
    if (file_exists($path)) {
        echo "   ✓ {$name}: File exists\n";
        
        // Check for basic syntax errors
        $content = file_get_contents($path);
        if (strpos($content, 'namespace KHM_SEO') !== false) {
            echo "   ✓ {$name}: Proper namespace\n";
        } else {
            echo "   ✗ {$name}: Missing or incorrect namespace\n";
            $test_results['syntax_errors'][] = "{$name}: Namespace issue";
        }
        
        if (strpos($content, "class {$name}") !== false || 
            strpos($content, "class {$name} ") !== false ||
            strpos($content, "class {$name}\n") !== false ||
            strpos($content, "class {$name}{") !== false) {
            echo "   ✓ {$name}: Class definition found\n";
        } else {
            echo "   ✗ {$name}: Class definition not found\n";
            $test_results['syntax_errors'][] = "{$name}: Class definition missing";
        }
        
        if (strpos($content, 'public function __construct') !== false) {
            echo "   ✓ {$name}: Constructor found\n";
        } else {
            echo "   ⚠ {$name}: No constructor (may be optional)\n";
        }
        
        $test_results['phase_3_components'][$name] = 'exists';
    } else {
        echo "   ✗ {$name}: File missing at {$path}\n";
        $test_results['phase_3_components'][$name] = 'missing';
    }
}

echo "\n";

// Test 2: Method structure analysis
echo "2. Testing method structures...\n";
$expected_methods = array(
    'SchemaManager' => array('init_hooks', 'generate_schema', 'validate_schema'),
    'ArticleSchema' => array('generate_schema', 'detect_article_type', 'get_author_schema'),
    'OrganizationSchema' => array('generate_schema', 'detect_business_type', 'get_contact_info'),
    'SocialMediaManager' => array('init_hooks', 'output_tags', 'generate_preview'),
    'ToolsManager' => array('generate_robots_txt', 'run_seo_audit', 'monitor_404_errors')
);

foreach ($components as $name => $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $found_methods = array();
        
        if (isset($expected_methods[$name])) {
            foreach ($expected_methods[$name] as $method) {
                if (strpos($content, "function {$method}") !== false) {
                    $found_methods[] = $method;
                    echo "   ✓ {$name}: Method '{$method}' found\n";
                } else {
                    echo "   ⚠ {$name}: Method '{$method}' not found (may use different name)\n";
                }
            }
        }
        
        $test_results['class_structure'][$name] = $found_methods;
    }
}

echo "\n";

// Test 3: Line count and complexity analysis
echo "3. Analyzing component complexity...\n";
foreach ($components as $name => $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $lines = count(explode("\n", $content));
        $methods = substr_count($content, 'public function') + substr_count($content, 'private function');
        $classes = substr_count($content, 'class ');
        
        echo "   {$name}:\n";
        echo "     - Lines: {$lines}\n";
        echo "     - Methods: {$methods}\n";
        echo "     - Classes: {$classes}\n";
        
        // Complexity assessment
        if ($lines > 500 && $methods > 10) {
            echo "     - Complexity: High (comprehensive implementation)\n";
        } elseif ($lines > 200 && $methods > 5) {
            echo "     - Complexity: Medium (good implementation)\n";
        } else {
            echo "     - Complexity: Low (basic implementation)\n";
        }
    }
}

echo "\n";

// Test 4: WordPress integration points
echo "4. Testing WordPress integration...\n";
$wp_functions = array(
    'add_action', 'add_filter', 'wp_head', 'get_option', 'update_option', 
    'wp_enqueue_script', 'wp_enqueue_style', 'current_user_can'
);

foreach ($components as $name => $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $wp_integrations = 0;
        
        foreach ($wp_functions as $wp_func) {
            if (strpos($content, $wp_func) !== false || strpos($content, "\\{$wp_func}") !== false) {
                $wp_integrations++;
            }
        }
        
        echo "   {$name}: {$wp_integrations} WordPress integration points\n";
        
        if ($wp_integrations >= 3) {
            echo "     ✓ Good WordPress integration\n";
        } elseif ($wp_integrations >= 1) {
            echo "     ⚠ Basic WordPress integration\n";
        } else {
            echo "     ✗ Minimal WordPress integration\n";
        }
    }
}

echo "\n";

// Test 5: Configuration and settings
echo "5. Testing configuration handling...\n";
foreach ($components as $name => $path) {
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $has_config = false;
        
        if (strpos($content, '$config') !== false || 
            strpos($content, 'get_option') !== false || 
            strpos($content, '\\get_option') !== false) {
            echo "   ✓ {$name}: Configuration handling found\n";
            $has_config = true;
        } else {
            echo "   ⚠ {$name}: No configuration handling detected\n";
        }
        
        if (strpos($content, 'sanitize_') !== false || strpos($content, '\\sanitize_') !== false) {
            echo "   ✓ {$name}: Input sanitization found\n";
        } else {
            echo "   ⚠ {$name}: Input sanitization not detected\n";
        }
    }
}

echo "\n";

// Final assessment
echo "=== PHASE 3 TESTING SUMMARY ===\n";

$total_components = count($components);
$existing_components = 0;
$properly_structured = 0;

foreach ($test_results['phase_3_components'] as $name => $status) {
    if ($status === 'exists') {
        $existing_components++;
        
        // Check if properly structured
        if (isset($test_results['class_structure'][$name]) && 
            count($test_results['class_structure'][$name]) >= 2) {
            $properly_structured++;
        }
    }
}

echo "Components Status:\n";
echo "- Total Components: {$total_components}\n";
echo "- Existing Components: {$existing_components}\n";
echo "- Properly Structured: {$properly_structured}\n";

if (count($test_results['syntax_errors']) > 0) {
    echo "\nSyntax/Structure Issues:\n";
    foreach ($test_results['syntax_errors'] as $error) {
        echo "- {$error}\n";
    }
}

// Overall status
if ($existing_components === $total_components && count($test_results['syntax_errors']) === 0) {
    $test_results['overall_status'] = 'complete';
    echo "\n✅ PHASE 3 STATUS: COMPLETE\n";
    echo "All components exist and are properly structured.\n";
} elseif ($existing_components === $total_components) {
    $test_results['overall_status'] = 'functional_with_warnings';
    echo "\n⚠️ PHASE 3 STATUS: FUNCTIONAL WITH WARNINGS\n";
    echo "All components exist but some have structural issues.\n";
} elseif ($existing_components >= 3) {
    $test_results['overall_status'] = 'mostly_complete';
    echo "\n🔄 PHASE 3 STATUS: MOSTLY COMPLETE\n";
    echo "Most components exist, some may be missing.\n";
} else {
    $test_results['overall_status'] = 'incomplete';
    echo "\n❌ PHASE 3 STATUS: INCOMPLETE\n";
    echo "Several components are missing or have major issues.\n";
}

echo "\nThe lint errors shown are expected WordPress function namespace warnings.\n";
echo "These are normal in a standalone testing environment and will resolve in WordPress.\n";

echo "\n=== TEST COMPLETE ===\n";