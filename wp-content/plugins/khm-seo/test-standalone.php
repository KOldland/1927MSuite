<?php
/**
 * Standalone test for KHM SEO Plugin - Phase 1.1 MetaManager Testing
 * This simulates a WordPress environment to test core functionality
 */

// Simulate WordPress constants
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/../../../');
}

define('KHM_SEO_VERSION', '1.0.0');
define('KHM_SEO_PLUGIN_FILE', __FILE__);
define('KHM_SEO_PLUGIN_URL', 'http://localhost/wp-content/plugins/khm-seo/');
define('KHM_SEO_PLUGIN_PATH', dirname(__FILE__) . '/');
define('KHM_SEO_PLUGIN_DIR', dirname(__FILE__) . '/');

// Mock WordPress functions for testing
if (!function_exists('add_action')) {
    function add_action($hook, $callback, $priority = 10, $args = 1) {
        echo "Hook registered: {$hook} -> " . (is_array($callback) ? get_class($callback[0]) . '::' . $callback[1] : $callback) . "\n";
    }
}

if (!function_exists('add_filter')) {
    function add_filter($hook, $callback, $priority = 10, $args = 1) {
        echo "Filter registered: {$hook} -> " . (is_array($callback) ? get_class($callback[0]) . '::' . $callback[1] : $callback) . "\n";
    }
}

if (!function_exists('get_option')) {
    function get_option($option, $default = false) {
        $mock_options = [
            'khm_seo_general' => [
                'home_title' => 'Test Site - Digital Magazine',
                'home_description' => 'A test digital magazine for content creators',
                'separator' => '|'
            ],
            'khm_seo_titles' => [
                'enable_title_rewrite' => true,
                'post_title_format' => '%title% %sep% %sitename%',
                'page_title_format' => '%title% %sep% %sitename%'
            ],
            'khm_seo_meta' => [
                'enable_og_tags' => true,
                'enable_twitter_cards' => true,
                'twitter_site' => '@testsite'
            ]
        ];
        return isset($mock_options[$option]) ? $mock_options[$option] : $default;
    }
}

if (!function_exists('get_bloginfo')) {
    function get_bloginfo($show = '') {
        $info = [
            'name' => 'Test WordPress Site',
            'description' => 'Just another WordPress site',
            'version' => '6.3.0'
        ];
        return isset($info[$show]) ? $info[$show] : '';
    }
}

if (!function_exists('is_singular')) {
    function is_singular() { return true; }
}

if (!function_exists('is_category')) {
    function is_category() { return false; }
}

if (!function_exists('is_tag')) {
    function is_tag() { return false; }
}

if (!function_exists('is_tax')) {
    function is_tax() { return false; }
}

if (!function_exists('is_home')) {
    function is_home() { return false; }
}

if (!function_exists('is_front_page')) {
    function is_front_page() { return false; }
}

if (!function_exists('is_author')) {
    function is_author() { return false; }
}

if (!function_exists('is_search')) {
    function is_search() { return false; }
}

if (!function_exists('is_404')) {
    function is_404() { return false; }
}

if (!function_exists('get_post_meta')) {
    function get_post_meta($post_id, $key = '', $single = false) {
        // Mock some SEO meta data
        $meta = [
            '_khm_seo_title' => 'Custom SEO Title for Post',
            '_khm_seo_description' => 'Custom SEO description for this test post.'
        ];
        
        if ($key) {
            return isset($meta[$key]) ? ($single ? $meta[$key] : [$meta[$key]]) : ($single ? '' : []);
        }
        
        return $meta;
    }
}

if (!function_exists('get_term_meta')) {
    function get_term_meta($term_id, $key = '', $single = false) {
        return $single ? '' : [];
    }
}

if (!function_exists('get_post')) {
    function get_post($post_id = null) {
        return (object) [
            'ID' => 123,
            'post_title' => 'Test Blog Post Title',
            'post_content' => 'This is a test blog post content with some text to extract for meta description testing.',
            'post_excerpt' => 'This is a test excerpt for the blog post.',
            'post_type' => 'post'
        ];
    }
}

if (!function_exists('esc_html')) {
    function esc_html($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('esc_attr')) {
    function esc_attr($text) { return htmlspecialchars($text, ENT_QUOTES, 'UTF-8'); }
}

if (!function_exists('esc_url')) {
    function esc_url($url) { return $url; }
}

if (!function_exists('wp_trim_words')) {
    function wp_trim_words($text, $num_words = 55, $more = null) {
        if (null === $more) {
            $more = '…';
        }
        $words = preg_split("/[\n\r\t ]+/", $text, $num_words + 1, PREG_SPLIT_NO_EMPTY);
        if (count($words) > $num_words) {
            array_pop($words);
            $text = implode(' ', $words);
            $text = $text . $more;
        } else {
            $text = implode(' ', $words);
        }
        return $text;
    }
}

if (!function_exists('wp_strip_all_tags')) {
    function wp_strip_all_tags($text) { return strip_tags($text); }
}

if (!function_exists('strip_tags')) {
    // This should exist in PHP but just in case
    if (!function_exists('strip_tags')) {
        function strip_tags($str) { return $str; }
    }
}

if (!function_exists('get_permalink')) {
    function get_permalink($post = 0) { return 'http://testsite.com/sample-post/'; }
}

if (!function_exists('has_post_thumbnail')) {
    function has_post_thumbnail($post_id = null) { return true; }
}

if (!function_exists('get_post_thumbnail_id')) {
    function get_post_thumbnail_id($post_id = null) { return 456; }
}

if (!function_exists('wp_get_attachment_image_src')) {
    function wp_get_attachment_image_src($attachment_id, $size = 'thumbnail') {
        return ['http://testsite.com/wp-content/uploads/test-image.jpg', 800, 600];
    }
}

if (!function_exists('attachment_url_to_postid')) {
    function attachment_url_to_postid($url) { return 456; }
}

if (!function_exists('wp_get_attachment_metadata')) {
    function wp_get_attachment_metadata($attachment_id) {
        return ['width' => 800, 'height' => 600];
    }
}

if (!function_exists('get_user_meta')) {
    function get_user_meta($user_id, $key = '', $single = false) {
        return $single ? '' : [];
    }
}

if (!function_exists('get_the_tags')) {
    function get_the_tags($post_id = 0) {
        return [
            (object)['name' => 'test-tag', 'term_id' => 1],
            (object)['name' => 'another-tag', 'term_id' => 2]
        ];
    }
}

if (!function_exists('get_queried_object')) {
    function get_queried_object() {
        return (object)[
            'ID' => 123,
            'display_name' => 'Test Author',
            'term_id' => 1,
            'name' => 'Test Category'
        ];
    }
}

if (!function_exists('get_term')) {
    function get_term($term_id) {
        return (object)[
            'term_id' => $term_id,
            'name' => 'Test Term',
            'description' => 'Test term description'
        ];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) { return false; }
}

if (!function_exists('get_search_query')) {
    function get_search_query() { return 'test search'; }
}

if (!function_exists('get_the_author')) {
    function get_the_author() { return 'Test Author'; }
}

if (!function_exists('get_the_date')) {
    function get_the_date() { return 'November 8, 2025'; }
}

if (!function_exists('is_date')) {
    function is_date() { return false; }
}

if (!function_exists('get_term_link')) {
    function get_term_link($term) { return 'http://testsite.com/category/test/'; }
}

if (!function_exists('get_author_posts_url')) {
    function get_author_posts_url($author_id) { return 'http://testsite.com/author/test/'; }
}

if (!function_exists('home_url')) {
    function home_url($path = '/') { return 'http://testsite.com' . $path; }
}

if (!function_exists('add_query_arg')) {
    function add_query_arg($args, $uri = false) { return $uri ?: '/'; }
}

// Mock global $post
global $post;
$post = get_post(123);

echo "=== KHM SEO Plugin - Phase 1.1 MetaManager Test ===\n\n";

// Test 1: Load Autoloader
echo "1. Testing Autoloader...\n";
try {
    require_once dirname(__FILE__) . '/src/Core/Autoloader.php';
    $autoloader = new KHM_SEO\Core\Autoloader();
    echo "✓ Autoloader loaded successfully\n\n";
} catch (Exception $e) {
    echo "✗ Autoloader failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 2: Load MetaManager
echo "2. Testing MetaManager instantiation...\n";
try {
    $meta_manager = new KHM_SEO\Meta\MetaManager();
    echo "✓ MetaManager instantiated successfully\n\n";
} catch (Exception $e) {
    echo "✗ MetaManager failed: " . $e->getMessage() . "\n\n";
    exit(1);
}

// Test 3: Test get_title() method
echo "3. Testing get_title() method...\n";
try {
    $title = $meta_manager->get_title();
    echo "✓ get_title() returned: '{$title}'\n\n";
} catch (Exception $e) {
    echo "✗ get_title() failed: " . $e->getMessage() . "\n\n";
}

// Test 4: Test get_description() method
echo "4. Testing get_description() method...\n";
try {
    $description = $meta_manager->get_description();
    echo "✓ get_description() returned: '{$description}'\n\n";
} catch (Exception $e) {
    echo "✗ get_description() failed: " . $e->getMessage() . "\n\n";
}

// Test 5: Test meta output
echo "5. Testing meta tag output...\n";
try {
    ob_start();
    $meta_manager->output_meta_tags();
    $meta_output = ob_get_clean();
    
    if (strlen($meta_output) > 0) {
        echo "✓ Meta tags generated:\n";
        echo $meta_output;
        echo "\n";
    } else {
        echo "⚠ No meta tags output (might be normal)\n\n";
    }
} catch (Exception $e) {
    echo "✗ Meta output failed: " . $e->getMessage() . "\n\n";
}

// Test 6: Test Open Graph output
echo "6. Testing Open Graph output...\n";
try {
    ob_start();
    $meta_manager->output_og_tags();
    $og_output = ob_get_clean();
    
    if (strlen($og_output) > 0) {
        echo "✓ Open Graph tags generated:\n";
        echo $og_output;
        echo "\n";
    } else {
        echo "⚠ No Open Graph tags output\n\n";
    }
} catch (Exception $e) {
    echo "✗ Open Graph output failed: " . $e->getMessage() . "\n\n";
}

// Test 7: Test Twitter Cards output
echo "7. Testing Twitter Cards output...\n";
try {
    ob_start();
    $meta_manager->output_twitter_tags();
    $twitter_output = ob_get_clean();
    
    if (strlen($twitter_output) > 0) {
        echo "✓ Twitter Cards generated:\n";
        echo $twitter_output;
        echo "\n";
    } else {
        echo "⚠ No Twitter Cards output\n\n";
    }
} catch (Exception $e) {
    echo "✗ Twitter Cards output failed: " . $e->getMessage() . "\n\n";
}

// Test 8: Test Plugin class
echo "8. Testing Plugin class...\n";
try {
    $plugin = KHM_SEO\Core\Plugin::instance();
    echo "✓ Plugin instance created successfully\n";
    
    $meta_from_plugin = $plugin->get_meta_manager();
    if ($meta_from_plugin) {
        echo "✓ MetaManager accessible from Plugin instance\n\n";
    } else {
        echo "⚠ MetaManager not initialized in Plugin\n\n";
    }
} catch (Exception $e) {
    echo "✗ Plugin class failed: " . $e->getMessage() . "\n\n";
}

echo "=== Test Summary ===\n";
echo "Phase 1.1 MetaManager core functionality testing completed.\n";
echo "If you see checkmarks (✓) above, the core features are working!\n\n";

echo "Next steps for WordPress testing:\n";
echo "1. Activate the plugin in WordPress admin\n";
echo "2. View source of any page to see meta tags\n";
echo "3. Use browser dev tools to inspect <head> section\n";
echo "4. Test with social media debuggers (Facebook, Twitter)\n";

?>