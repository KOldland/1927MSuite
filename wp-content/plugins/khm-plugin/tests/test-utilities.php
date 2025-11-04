<?php
/**
 * Test Utilities for Enhanced Email System
 *
 * Helper functions and utilities for testing the enhanced email system
 *
 * @package KHM\Tests
 */

/**
 * Create a test email template
 */
function createTestEmailTemplate(string $name, string $content): string {
    $templateDir = sys_get_temp_dir() . '/khm-test-templates-' . uniqid();
    if (!is_dir($templateDir)) {
        mkdir($templateDir, 0755, true);
    }
    
    $templatePath = $templateDir . '/' . $name . '.html';
    file_put_contents($templatePath, $content);
    
    return $templatePath;
}

/**
 * Generate test email data
 */
function generateTestEmailData(array $overrides = []): array {
    return array_merge([
        'user_name' => 'Test User',
        'user_email' => 'test@example.com',
        'content' => 'This is test email content.',
        'subject' => 'Test Subject',
        'site_name' => 'Test Site',
        'site_url' => 'https://example.com',
        'timestamp' => date('Y-m-d H:i:s'),
        'message' => 'Test message content'
    ], $overrides);
}

/**
 * Create test SMTP settings
 */
function generateTestSMTPSettings(array $overrides = []): array {
    return array_merge([
        'khm_smtp_host' => 'smtp.gmail.com',
        'khm_smtp_port' => 587,
        'khm_smtp_encryption' => 'tls',
        'khm_smtp_username' => 'test@gmail.com',
        'khm_smtp_password' => 'test_password',
        'khm_smtp_from_email' => 'noreply@example.com',
        'khm_smtp_from_name' => 'Test Site'
    ], $overrides);
}

/**
 * Create test API settings
 */
function generateTestAPISettings(string $provider = 'sendgrid', array $overrides = []): array {
    $defaults = [
        'khm_email_api_provider' => $provider,
        'khm_email_from_email' => 'noreply@example.com',
        'khm_email_from_name' => 'Test Site'
    ];
    
    switch ($provider) {
        case 'sendgrid':
            $defaults['khm_email_api_key'] = 'SG.test_api_key_here';
            break;
        case 'mailgun':
            $defaults['khm_email_api_key'] = 'key-test_mailgun_key';
            $defaults['khm_email_api_domain'] = 'mg.example.com';
            break;
    }
    
    return array_merge($defaults, $overrides);
}

/**
 * Assert email was sent with specific parameters
 */
function assertEmailSent(array $expectedParams): void {
    global $mock_wp_mail_calls;
    
    if (empty($mock_wp_mail_calls)) {
        throw new Exception('No emails were sent');
    }
    
    $lastEmail = end($mock_wp_mail_calls);
    
    foreach ($expectedParams as $key => $expectedValue) {
        if (!array_key_exists($key, $lastEmail)) {
            throw new Exception("Email parameter '{$key}' not found");
        }
        
        if ($lastEmail[$key] !== $expectedValue) {
            throw new Exception("Email parameter '{$key}' mismatch. Expected: '{$expectedValue}', Got: '{$lastEmail[$key]}'");
        }
    }
}

/**
 * Assert email contains specific content
 */
function assertEmailContains(string $content): void {
    global $mock_wp_mail_calls;
    
    if (empty($mock_wp_mail_calls)) {
        throw new Exception('No emails were sent');
    }
    
    $lastEmail = end($mock_wp_mail_calls);
    
    if (strpos($lastEmail['message'], $content) === false) {
        throw new Exception("Email does not contain expected content: '{$content}'");
    }
}

/**
 * Assert email does not contain specific content
 */
function assertEmailNotContains(string $content): void {
    global $mock_wp_mail_calls;
    
    if (empty($mock_wp_mail_calls)) {
        return; // No emails sent, so content is definitely not there
    }
    
    $lastEmail = end($mock_wp_mail_calls);
    
    if (strpos($lastEmail['message'], $content) !== false) {
        throw new Exception("Email contains unexpected content: '{$content}'");
    }
}

/**
 * Get count of sent emails
 */
function getEmailCount(): int {
    global $mock_wp_mail_calls;
    return count($mock_wp_mail_calls ?? []);
}

/**
 * Clear sent emails
 */
function clearSentEmails(): void {
    global $mock_wp_mail_calls;
    $mock_wp_mail_calls = [];
}

/**
 * Mock WordPress option functions
 */
function mockWordPressOption(string $option, $value): void {
    global $mock_wordpress_options;
    $mock_wordpress_options = $mock_wordpress_options ?? [];
    $mock_wordpress_options[$option] = $value;
}

/**
 * Get mocked WordPress option
 */
function getMockedOption(string $option, $default = null) {
    global $mock_wordpress_options;
    return $mock_wordpress_options[$option] ?? $default;
}

/**
 * Create test queue entries
 */
function createTestQueueEntries(int $count = 5): array {
    $entries = [];
    
    for ($i = 0; $i < $count; $i++) {
        $entries[] = [
            'id' => $i + 1,
            'recipient' => "test{$i}@example.com",
            'subject' => "Test Subject {$i}",
            'template_name' => 'test_template',
            'template_data' => json_encode(['user_name' => "User {$i}"]),
            'priority' => rand(1, 10),
            'status' => 'pending',
            'retry_count' => 0,
            'created_at' => date('Y-m-d H:i:s', time() - ($i * 60)),
            'next_retry' => null,
            'sent_at' => null,
            'error_message' => null
        ];
    }
    
    return $entries;
}

/**
 * Simulate email delivery failure
 */
function simulateEmailFailure(bool $shouldFail = true): void {
    global $mock_wp_mail_failure;
    $mock_wp_mail_failure = $shouldFail;
}

/**
 * Create performance test data
 */
function createPerformanceTestData(int $size = 1000): array {
    $data = [
        'user_name' => 'Performance Test User',
        'content' => str_repeat('Lorem ipsum dolor sit amet. ', $size / 10),
        'large_array' => array_fill(0, $size, 'data'),
        'json_data' => json_encode(array_fill(0, $size / 10, ['key' => 'value']))
    ];
    
    return $data;
}

/**
 * Measure execution time
 */
function measureExecutionTime(callable $callback): float {
    $startTime = microtime(true);
    $callback();
    return microtime(true) - $startTime;
}

/**
 * Measure memory usage
 */
function measureMemoryUsage(callable $callback): int {
    $startMemory = memory_get_usage();
    $callback();
    return memory_get_usage() - $startMemory;
}

/**
 * Create security test payloads
 */
function createSecurityTestPayloads(): array {
    return [
        'xss_script' => '<script>alert("XSS")</script>',
        'xss_img' => '<img src=x onerror=alert("XSS")>',
        'xss_iframe' => '<iframe src="javascript:alert(\'XSS\')"></iframe>',
        'sql_injection' => "'; DROP TABLE users; --",
        'path_traversal' => '../../../etc/passwd',
        'null_byte' => "test\0.php",
        'php_code' => '<?php system($_GET["cmd"]); ?>',
        'javascript_url' => 'javascript:alert("XSS")',
        'data_url' => 'data:text/html,<script>alert("XSS")</script>'
    ];
}

/**
 * Assert string is properly sanitized
 */
function assertStringSanitized(string $input, string $output): void {
    $dangerousPatterns = [
        '/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi',
        '/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi',
        '/javascript:/i',
        '/on\w+\s*=/i', // Event handlers
        '/<\s*\/?\s*php/i'
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $output)) {
            throw new Exception("Output contains dangerous pattern: {$pattern}");
        }
    }
}

/**
 * Create test file upload
 */
function createTestFileUpload(string $filename, string $content): array {
    $tempFile = tmpfile();
    fwrite($tempFile, $content);
    $tempPath = stream_get_meta_data($tempFile)['uri'];
    
    return [
        'name' => $filename,
        'type' => mime_content_type($tempPath),
        'tmp_name' => $tempPath,
        'error' => UPLOAD_ERR_OK,
        'size' => strlen($content)
    ];
}

/**
 * Mock user capabilities
 */
function mockUserCapabilities(array $capabilities): void {
    global $mock_user_capabilities;
    $mock_user_capabilities = $capabilities;
}

/**
 * Check if user has capability (mock)
 */
function mockCurrentUserCan(string $capability): bool {
    global $mock_user_capabilities;
    return in_array($capability, $mock_user_capabilities ?? ['manage_options']);
}

/**
 * Create test WordPress user
 */
function createTestUser(array $userData = []): array {
    return array_merge([
        'ID' => 1,
        'user_login' => 'testuser',
        'user_email' => 'test@example.com',
        'user_nicename' => 'testuser',
        'display_name' => 'Test User',
        'user_registered' => date('Y-m-d H:i:s'),
        'user_status' => 0,
        'user_role' => 'administrator'
    ], $userData);
}

/**
 * Assert database operation was performed
 */
function assertDatabaseOperation(string $operation, string $table): void {
    global $wpdb;
    
    if (!$wpdb || !property_exists($wpdb, 'last_query')) {
        throw new Exception('No database operations tracked');
    }
    
    $lastQuery = strtoupper($wpdb->last_query);
    $operation = strtoupper($operation);
    
    if (strpos($lastQuery, $operation) === false) {
        throw new Exception("Database operation '{$operation}' not found in last query");
    }
    
    if (strpos($lastQuery, strtoupper($table)) === false) {
        throw new Exception("Table '{$table}' not found in last query");
    }
}

/**
 * Generate random test data
 */
function generateRandomTestData(string $type = 'string', int $length = 10): mixed {
    switch ($type) {
        case 'string':
            return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
        case 'email':
            return 'test' . rand(1000, 9999) . '@example.com';
        case 'number':
            return rand(1, $length);
        case 'array':
            return array_fill(0, $length, 'item');
        default:
            return 'test_data';
    }
}

/**
 * Setup test environment
 */
function setupTestEnvironment(): void {
    // Clear any previous test state
    clearSentEmails();
    
    // Reset global variables
    global $mock_wp_mail_calls, $mock_wordpress_options, $mock_wp_hooks;
    global $mock_current_user_can, $mock_wp_verify_nonce, $mock_user_capabilities;
    
    $mock_wp_mail_calls = [];
    $mock_wordpress_options = [];
    $mock_wp_hooks = [];
    $mock_current_user_can = true;
    $mock_wp_verify_nonce = true;
    $mock_user_capabilities = ['manage_options'];
}

/**
 * Cleanup test environment
 */
function cleanupTestEnvironment(): void {
    // Clear temporary files
    $tempDir = sys_get_temp_dir();
    $pattern = $tempDir . '/khm-*';
    
    foreach (glob($pattern) as $file) {
        if (is_file($file)) {
            unlink($file);
        } elseif (is_dir($file)) {
            removeDirectory($file);
        }
    }
}

/**
 * Remove directory recursively
 */
function removeDirectory(string $dir): void {
    if (!is_dir($dir)) return;
    
    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        is_dir($path) ? removeDirectory($path) : unlink($path);
    }
    rmdir($dir);
}

/**
 * Create test configuration
 */
function createTestConfiguration(): array {
    return [
        'delivery_method' => 'wordpress',
        'enhanced_delivery' => true,
        'use_queue' => false,
        'batch_size' => 10,
        'retry_attempts' => 3,
        'rate_limit_enabled' => false,
        'debug_mode' => true
    ];
}

/**
 * Validate test environment
 */
function validateTestEnvironment(): bool {
    // Check if required functions exist
    $requiredFunctions = ['wp_mail', 'get_option', 'update_option', 'current_user_can'];
    
    foreach ($requiredFunctions as $function) {
        if (!function_exists($function)) {
            echo "Warning: Required function '{$function}' not available\n";
            return false;
        }
    }
    
    return true;
}

// Initialize test environment when file is loaded
setupTestEnvironment();

// Validate environment
if (!validateTestEnvironment()) {
    echo "Warning: Test environment validation failed\n";
}