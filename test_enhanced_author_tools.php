<?php
/**
 * Test Enhanced Author Tools
 */

// Include the plugin files
require_once 'dual-gpt-wordpress-plugin/includes/class-dual-gpt-plugin.php';
require_once 'dual-gpt-wordpress-plugin/includes/tools/class-author-tools.php';

// Mock WordPress functions
if (!function_exists('wp_json_encode')) {
    function wp_json_encode($data) {
        return json_encode($data);
    }
}

if (!function_exists('wp_parse_args')) {
    function wp_parse_args($args, $defaults) {
        return array_merge($defaults, $args);
    }
}

if (!function_exists('sanitize_text_field')) {
    function sanitize_text_field($text) {
        return trim($text);
    }
}

if (!function_exists('wp_kses_post')) {
    function wp_kses_post($content) {
        return $content;
    }
}

if (!function_exists('get_option')) {
    function get_option($key, $default = null) {
        return $default;
    }
}

echo "Testing Enhanced Author Tools...\n\n";

try {
    $author_tools = new Dual_GPT_Author_Tools();

    // Test outline_from_brief
    echo "1. Testing outline_from_brief...\n";
    $outline_result = $author_tools->outline_from_brief(
        "Write a blog post about the benefits of renewable energy",
        "general audience",
        800,
        "educational"
    );
    echo "   Outline generated with " . count($outline_result['sections']) . " sections\n";

    // Test expand_section
    echo "2. Testing expand_section...\n";
    $expand_result = $author_tools->expand_section(
        "Environmental Impact",
        array("Reduces carbon emissions", "Preserves natural resources", "Improves air quality"),
        array('word_count' => 200)
    );
    echo "   Section expanded to " . str_word_count($expand_result['content']) . " words\n";

    // Test style_guard
    echo "3. Testing style_guard...\n";
    $style_content = "This is very really good content. So, you should read it. Just trust me.";
    $style_result = $author_tools->style_guard($style_content);
    echo "   Style check found " . count($style_result['issues']) . " issues\n";
    echo "   Compliance score: " . $style_result['compliance_score'] . "/100\n";

    // Test citation_guard
    echo "4. Testing citation_guard...\n";
    $citation_content = "Renewable energy is growing rapidly [1]. According to Smith (2023), solar power is efficient. Wind energy also contributes significantly [2].

# Bibliography

[1] Johnson, A. (2022). Renewable Energy Trends. Energy Journal.

[2] Brown, B. (2021). Wind Power Advances. Climate Tech Review.";
    $citation_result = $author_tools->citation_guard($citation_content);
    echo "   Citation check found " . count($citation_result['issues']) . " issues\n";
    echo "   Found " . $citation_result['stats']['citation_count'] . " citations\n";

    echo "\nAll tests completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>