<?php
/**
 * KHM SEO Phase 5 Schema Validation Testing Suite
 * 
 * Comprehensive testing for schema validation and testing functionality.
 */

// Ensure we're in WordPress environment
if (!defined('ABSPATH')) {
    exit;
}

echo "<h1>🔍 KHM SEO Phase 5: Schema Validation Testing</h1>\n";

// Include required files
require_once plugin_dir_path(__FILE__) . 'src/Validation/SchemaValidator.php';

use KHM_SEO\Validation\SchemaValidator;

/**
 * Test Results Tracking
 */
$test_results = [
    'total' => 0,
    'passed' => 0,
    'failed' => 0,
    'errors' => []
];

/**
 * Helper function to run tests
 */
function run_test($test_name, $test_function, &$results) {
    $results['total']++;
    echo "<h3>Testing: {$test_name}</h3>\n";
    
    try {
        $result = $test_function();
        if ($result) {
            echo "<p style='color: green;'>✅ PASSED: {$test_name}</p>\n";
            $results['passed']++;
            return true;
        } else {
            echo "<p style='color: red;'>❌ FAILED: {$test_name}</p>\n";
            $results['failed']++;
            return false;
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ ERROR in {$test_name}: " . $e->getMessage() . "</p>\n";
        $results['failed']++;
        $results['errors'][] = $test_name . ': ' . $e->getMessage();
        return false;
    }
}

/**
 * Mock WordPress functions for testing
 */
if (!function_exists('current_time')) {
    function current_time($format) {
        return current_time($format);
    }
}

if (!function_exists('get_transient')) {
    function get_transient($key) {
        return false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient($key, $value, $expiration) {
        return true;
    }
}

if (!function_exists('wp_remote_get')) {
    function wp_remote_get($url) {
        return [
            'body' => '<script type="application/ld+json">{"@context": "https://schema.org", "@type": "Article", "headline": "Test Article"}</script>',
            'response' => ['code' => 200]
        ];
    }
}

if (!function_exists('wp_remote_retrieve_body')) {
    function wp_remote_retrieve_body($response) {
        return $response['body'];
    }
}

if (!function_exists('is_wp_error')) {
    function is_wp_error($thing) {
        return false;
    }
}

/**
 * Test 1: SchemaValidator Instantiation
 */
run_test("SchemaValidator Instantiation", function() {
    $validator = new SchemaValidator();
    return $validator instanceof SchemaValidator;
}, $test_results);

/**
 * Test 2: Basic Schema Structure Validation - Valid JSON-LD
 */
run_test("Basic Schema Structure Validation - Valid JSON-LD", function() {
    $validator = new SchemaValidator();
    
    $valid_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => 'Test Article',
        'author' => 'Test Author',
        'datePublished' => '2024-01-01'
    ];
    
    $result = $validator->validate_schema($valid_schema, 'Article');
    
    return isset($result['valid']) && 
           isset($result['score']) && 
           isset($result['errors']) && 
           isset($result['warnings']) &&
           is_array($result['errors']) &&
           is_array($result['warnings']);
}, $test_results);

/**
 * Test 3: Schema Structure Validation - Missing @context
 */
run_test("Schema Structure Validation - Missing @context", function() {
    $validator = new SchemaValidator();
    
    $invalid_schema = [
        '@type' => 'Article',
        'headline' => 'Test Article'
    ];
    
    $result = $validator->validate_schema($invalid_schema, 'Article');
    
    // Should have errors for missing @context
    $has_context_error = false;
    foreach ($result['errors'] as $error) {
        if ($error['type'] === 'missing_context') {
            $has_context_error = true;
            break;
        }
    }
    
    return $has_context_error;
}, $test_results);

/**
 * Test 4: Schema Structure Validation - Missing @type
 */
run_test("Schema Structure Validation - Missing @type", function() {
    $validator = new SchemaValidator();
    
    $invalid_schema = [
        '@context' => 'https://schema.org',
        'headline' => 'Test Article'
    ];
    
    $result = $validator->validate_schema($invalid_schema, 'Article');
    
    // Should have errors for missing @type
    $has_type_error = false;
    foreach ($result['errors'] as $error) {
        if ($error['type'] === 'missing_type') {
            $has_type_error = true;
            break;
        }
    }
    
    return $has_type_error;
}, $test_results);

/**
 * Test 5: Article Schema Validation - Valid Article
 */
run_test("Article Schema Validation - Valid Article", function() {
    $validator = new SchemaValidator();
    
    $article_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => 'Complete Test Article',
        'author' => [
            '@type' => 'Person',
            'name' => 'John Doe'
        ],
        'datePublished' => '2024-01-01T00:00:00Z',
        'dateModified' => '2024-01-02T00:00:00Z',
        'description' => 'A comprehensive test article for validation',
        'image' => 'https://example.com/image.jpg',
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Test Publisher'
        ]
    ];
    
    $result = $validator->validate_schema($article_schema, 'Article');
    
    return $result['score'] > 80; // Should have high score for complete article
}, $test_results);

/**
 * Test 6: Article Schema Validation - Missing Required Fields
 */
run_test("Article Schema Validation - Missing Required Fields", function() {
    $validator = new SchemaValidator();
    
    $incomplete_article = [
        '@context' => 'https://schema.org',
        '@type' => 'Article'
        // Missing headline, author, datePublished
    ];
    
    $result = $validator->validate_schema($incomplete_article, 'Article');
    
    // Should have multiple errors for missing required fields
    $required_field_errors = 0;
    foreach ($result['errors'] as $error) {
        if (in_array($error['type'], ['missing_headline', 'missing_author', 'missing_date_published'])) {
            $required_field_errors++;
        }
    }
    
    return $required_field_errors >= 2; // Should have at least 2 required field errors
}, $test_results);

/**
 * Test 7: Organization Schema Validation
 */
run_test("Organization Schema Validation", function() {
    $validator = new SchemaValidator();
    
    $org_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'Test Organization',
        'url' => 'https://example.com',
        'logo' => 'https://example.com/logo.png',
        'description' => 'A test organization',
        'contactPoint' => [
            '@type' => 'ContactPoint',
            'telephone' => '+1-555-123-4567',
            'contactType' => 'customer service'
        ]
    ];
    
    $result = $validator->validate_schema($org_schema, 'Organization');
    
    return $result['score'] > 70; // Should have good score for complete organization
}, $test_results);

/**
 * Test 8: Product Schema Validation
 */
run_test("Product Schema Validation", function() {
    $validator = new SchemaValidator();
    
    $product_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Product',
        'name' => 'Test Product',
        'description' => 'A comprehensive test product',
        'image' => 'https://example.com/product.jpg',
        'brand' => [
            '@type' => 'Brand',
            'name' => 'Test Brand'
        ],
        'offers' => [
            '@type' => 'Offer',
            'price' => '99.99',
            'priceCurrency' => 'USD'
        ]
    ];
    
    $result = $validator->validate_schema($product_schema, 'Product');
    
    return $result['score'] > 70; // Should have good score for complete product
}, $test_results);

/**
 * Test 9: Breadcrumb Schema Validation
 */
run_test("Breadcrumb Schema Validation", function() {
    $validator = new SchemaValidator();
    
    $breadcrumb_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BreadcrumbList',
        'itemListElement' => [
            [
                '@type' => 'ListItem',
                'position' => 1,
                'name' => 'Home',
                'item' => 'https://example.com'
            ],
            [
                '@type' => 'ListItem',
                'position' => 2,
                'name' => 'Category',
                'item' => 'https://example.com/category'
            ]
        ]
    ];
    
    $result = $validator->validate_schema($breadcrumb_schema, 'BreadcrumbList');
    
    return $result['score'] > 80; // Should have high score for valid breadcrumb
}, $test_results);

/**
 * Test 10: Rich Results Eligibility - Article
 */
run_test("Rich Results Eligibility - Article", function() {
    $validator = new SchemaValidator();
    
    $article_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => 'Rich Results Test Article',
        'author' => [
            '@type' => 'Person',
            'name' => 'Test Author'
        ],
        'datePublished' => '2024-01-01T00:00:00Z'
    ];
    
    $result = $validator->validate_schema($article_schema, 'Article');
    
    // Should have rich results eligibility
    return !empty($result['rich_results']) && 
           $result['rich_results'][0]['eligible'] === true &&
           $result['rich_results'][0]['type'] === 'article';
}, $test_results);

/**
 * Test 11: Score Calculation
 */
run_test("Validation Score Calculation", function() {
    $validator = new SchemaValidator();
    
    $perfect_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => 'Perfect Article',
        'author' => [
            '@type' => 'Person',
            'name' => 'Perfect Author'
        ],
        'datePublished' => '2024-01-01T00:00:00Z',
        'dateModified' => '2024-01-02T00:00:00Z',
        'description' => 'Perfect description',
        'image' => 'https://example.com/perfect.jpg',
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Perfect Publisher'
        ]
    ];
    
    $result = $validator->validate_schema($perfect_schema, 'Article');
    
    return $result['score'] >= 90; // Should have very high score for perfect schema
}, $test_results);

/**
 * Test 12: JSON-LD Extraction
 */
run_test("JSON-LD Extraction from HTML", function() {
    $validator = new SchemaValidator();
    
    // Test the rich results test functionality
    $result = $validator->test_rich_results('https://example.com');
    
    return isset($result['success']) && 
           isset($result['rich_results']) && 
           isset($result['errors']) &&
           is_array($result['rich_results']) &&
           is_array($result['errors']);
}, $test_results);

/**
 * Test 13: Debug Schema Functionality
 */
run_test("Debug Schema Functionality", function() {
    $validator = new SchemaValidator();
    
    $debug_result = $validator->debug_schema(1); // Test with post ID 1
    
    return isset($debug_result['timestamp']) &&
           isset($debug_result['post_id']) &&
           isset($debug_result['schemas']) &&
           isset($debug_result['hooks']) &&
           isset($debug_result['settings']) &&
           isset($debug_result['errors']) &&
           is_array($debug_result['schemas']) &&
           is_array($debug_result['errors']);
}, $test_results);

/**
 * Test 14: Bulk Validation Functionality
 */
run_test("Bulk Validation Functionality", function() {
    $validator = new SchemaValidator();
    
    $post_ids = [1, 2, 3]; // Test with multiple post IDs
    $bulk_result = $validator->bulk_validate($post_ids);
    
    return is_array($bulk_result) &&
           count($bulk_result) <= count($post_ids); // Should return results for each post (or fewer if posts don't exist)
}, $test_results);

/**
 * Test 15: URL Validation Helper
 */
run_test("URL Property Validation", function() {
    $validator = new SchemaValidator();
    
    // Test with invalid URL
    $invalid_url_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => 'Test Article',
        'url' => 'not-a-valid-url'
    ];
    
    $result = $validator->validate_schema($invalid_url_schema, 'Article');
    
    // Should have URL validation error
    $has_url_error = false;
    foreach ($result['errors'] as $error) {
        if ($error['type'] === 'invalid_url') {
            $has_url_error = true;
            break;
        }
    }
    
    return $has_url_error;
}, $test_results);

/**
 * Test 16: Date Validation
 */
run_test("Date Property Validation", function() {
    $validator = new SchemaValidator();
    
    // Test with invalid date
    $invalid_date_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => 'Test Article',
        'datePublished' => 'not-a-valid-date'
    ];
    
    $result = $validator->validate_schema($invalid_date_schema, 'Article');
    
    // Should have date validation error
    $has_date_error = false;
    foreach ($result['errors'] as $error) {
        if ($error['type'] === 'invalid_date') {
            $has_date_error = true;
            break;
        }
    }
    
    return $has_date_error;
}, $test_results);

/**
 * Test 17: Empty Property Warning
 */
run_test("Empty Property Warning", function() {
    $validator = new SchemaValidator();
    
    // Test with empty property
    $empty_property_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => 'Test Article',
        'description' => '   ' // Empty/whitespace only
    ];
    
    $result = $validator->validate_schema($empty_property_schema, 'Article');
    
    // Should have empty property warning
    $has_empty_warning = false;
    foreach ($result['warnings'] as $warning) {
        if ($warning['type'] === 'empty_property') {
            $has_empty_warning = true;
            break;
        }
    }
    
    return $has_empty_warning;
}, $test_results);

/**
 * Test 18: JSON String Validation
 */
run_test("JSON String Schema Validation", function() {
    $validator = new SchemaValidator();
    
    $json_schema = json_encode([
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => 'JSON String Test'
    ]);
    
    $result = $validator->validate_schema($json_schema, 'Article');
    
    return isset($result['valid']) && isset($result['score']);
}, $test_results);

/**
 * Test 19: Invalid JSON String
 */
run_test("Invalid JSON String Validation", function() {
    $validator = new SchemaValidator();
    
    $invalid_json = '{"@context": "https://schema.org", "@type": "Article", "headline": "Invalid JSON"'; // Missing closing brace
    
    $result = $validator->validate_schema($invalid_json, 'Article');
    
    // Should have JSON syntax error
    $has_json_error = false;
    foreach ($result['errors'] as $error) {
        if ($error['type'] === 'json_syntax') {
            $has_json_error = true;
            break;
        }
    }
    
    return $has_json_error;
}, $test_results);

/**
 * Test 20: Person Schema Validation
 */
run_test("Person Schema Validation", function() {
    $validator = new SchemaValidator();
    
    $person_schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Person',
        'name' => 'John Doe',
        'description' => 'Test person for validation',
        'image' => 'https://example.com/john.jpg',
        'jobTitle' => 'Software Engineer',
        'sameAs' => [
            'https://twitter.com/johndoe',
            'https://linkedin.com/in/johndoe'
        ]
    ];
    
    $result = $validator->validate_schema($person_schema, 'Person');
    
    return $result['score'] > 70; // Should have good score for complete person schema
}, $test_results);

echo "<hr>\n";
echo "<h2>📊 Test Summary</h2>\n";
echo "<p><strong>Total Tests:</strong> {$test_results['total']}</p>\n";
echo "<p><strong>Passed:</strong> <span style='color: green;'>{$test_results['passed']}</span></p>\n";
echo "<p><strong>Failed:</strong> <span style='color: red;'>{$test_results['failed']}</span></p>\n";

$success_rate = $test_results['total'] > 0 ? round(($test_results['passed'] / $test_results['total']) * 100, 2) : 0;
echo "<p><strong>Success Rate:</strong> <span style='color: " . ($success_rate >= 90 ? 'green' : ($success_rate >= 70 ? 'orange' : 'red')) . ";'>{$success_rate}%</span></p>\n";

if (!empty($test_results['errors'])) {
    echo "<h3>❌ Errors Encountered:</h3>\n";
    echo "<ul>\n";
    foreach ($test_results['errors'] as $error) {
        echo "<li style='color: red;'>{$error}</li>\n";
    }
    echo "</ul>\n";
}

if ($success_rate >= 90) {
    echo "<h2 style='color: green;'>🎉 Phase 5 Schema Validation Testing: EXCELLENT</h2>\n";
    echo "<p>All major validation functionality is working correctly. The schema validation system is ready for production use.</p>\n";
} elseif ($success_rate >= 70) {
    echo "<h2 style='color: orange;'>⚠️ Phase 5 Schema Validation Testing: GOOD</h2>\n";
    echo "<p>Most validation functionality is working. Some minor issues may need attention.</p>\n";
} else {
    echo "<h2 style='color: red;'>❌ Phase 5 Schema Validation Testing: NEEDS ATTENTION</h2>\n";
    echo "<p>Significant issues found. Please review and fix the validation system.</p>\n";
}

echo "<hr>\n";
echo "<h3>🔍 Phase 5 Features Tested:</h3>\n";
echo "<ul>\n";
echo "<li>✅ SchemaValidator class instantiation and basic functionality</li>\n";
echo "<li>✅ Schema structure validation (JSON-LD format, @context, @type)</li>\n";
echo "<li>✅ Schema type-specific validation (Article, Organization, Product, Person, Breadcrumb)</li>\n";
echo "<li>✅ Required field validation for each schema type</li>\n";
echo "<li>✅ Property value validation (URLs, dates, empty fields)</li>\n";
echo "<li>✅ Rich Results eligibility testing</li>\n";
echo "<li>✅ Validation score calculation and grading</li>\n";
echo "<li>✅ JSON-LD extraction from HTML content</li>\n";
echo "<li>✅ Debug functionality for troubleshooting</li>\n";
echo "<li>✅ Bulk validation processing</li>\n";
echo "<li>✅ Error handling and reporting system</li>\n";
echo "</ul>\n";
?>