<?php
/**
 * Enhanced Email Security Tests
 *
 * Tests security features, input validation, sanitization,
 * XSS prevention, and access control
 *
 * @package KHM\Tests
 */

namespace KHM\Tests;

use PHPUnit\Framework\TestCase;
use KHM\Services\EnhancedEmailService;
use KHM\Admin\EnhancedEmailAdmin;

class EnhancedEmailSecurityTest extends TestCase {

    private EnhancedEmailService $emailService;
    private EnhancedEmailAdmin $emailAdmin;
    private string $testPluginDir;

    protected function setUp(): void {
        parent::setUp();
        
        $this->testPluginDir = sys_get_temp_dir() . '/khm-email-security-test-' . uniqid();
        mkdir($this->testPluginDir, 0755, true);
        mkdir($this->testPluginDir . '/email', 0755, true);
        
        $this->createTestTemplates();
        $this->mockWordPressFunctions();
        
        $this->emailService = new EnhancedEmailService($this->testPluginDir);
        $this->emailAdmin = new EnhancedEmailAdmin($this->emailService);
    }

    protected function tearDown(): void {
        $this->removeDirectory($this->testPluginDir);
        parent::tearDown();
    }

    /**
     * Test XSS prevention in template rendering
     */
    public function testXSSPreventionInTemplateRendering(): void {
        $maliciousData = [
            'user_name' => '<script>alert("XSS")</script>',
            'content' => '"><img src=x onerror=alert("XSS")>',
            'message' => '<iframe src="javascript:alert(\'XSS\')"></iframe>',
            'link' => 'javascript:alert("XSS")',
            'style' => 'expression(alert("XSS"))'
        ];
        
        $rendered = $this->emailService->render('security_template', $maliciousData);
        
        $this->assertNotEmpty($rendered);
        
        // Should not contain executable script tags
        $this->assertStringNotContainsString('<script>', $rendered);
        $this->assertStringNotContainsString('javascript:', $rendered);
        $this->assertStringNotContainsString('onerror=', $rendered);
        $this->assertStringNotContainsString('<iframe', $rendered);
        $this->assertStringNotContainsString('expression(', $rendered);
        
        // Should contain escaped content
        $this->assertStringContainsString('&lt;script&gt;', $rendered);
    }

    /**
     * Test SQL injection prevention
     */
    public function testSQLInjectionPrevention(): void {
        $maliciousInputs = [
            "'; DROP TABLE users; --",
            "' UNION SELECT * FROM wp_users --",
            "1' OR '1'='1",
            "'; INSERT INTO wp_options VALUES ('malicious', 'payload'); --"
        ];
        
        foreach ($maliciousInputs as $input) {
            $data = ['user_name' => $input, 'content' => 'Test content'];
            
            // Should not throw database errors or execute malicious SQL
            $rendered = $this->emailService->render('security_template', $data);
            $this->assertNotEmpty($rendered);
            
            // Input should be escaped/sanitized
            $this->assertStringNotContainsString('DROP TABLE', $rendered);
            $this->assertStringNotContainsString('UNION SELECT', $rendered);
            $this->assertStringNotContainsString('INSERT INTO', $rendered);
        }
    }

    /**
     * Test email injection prevention
     */
    public function testEmailInjectionPrevention(): void {
        $maliciousEmails = [
            "test@example.com\nBCC: attacker@evil.com",
            "test@example.com\r\nSubject: Injected Subject",
            "test@example.com\nContent-Type: text/html",
            "test@example.com\r\n\r\nInjected Body Content",
            "test@example.com%0ABCC:attacker@evil.com"
        ];
        
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        foreach ($maliciousEmails as $email) {
            $result = $this->emailService
                ->setSubject('Test Subject')
                ->send('security_template', $email, ['user_name' => 'Test User']);
            
            // Should either sanitize the email or reject it
            if ($result) {
                $lastCall = end($mock_wp_mail_calls);
                
                // Headers should not contain injected content
                $headers = is_array($lastCall['headers']) ? implode('', $lastCall['headers']) : $lastCall['headers'];
                $this->assertStringNotContainsString('BCC:', $headers);
                $this->assertStringNotContainsString('Content-Type:', $headers);
                
                // Recipient should be sanitized
                $this->assertStringNotContainsString("\n", $lastCall['to']);
                $this->assertStringNotContainsString("\r", $lastCall['to']);
            }
        }
    }

    /**
     * Test template path traversal prevention
     */
    public function testTemplatePathTraversalPrevention(): void {
        $maliciousTemplates = [
            '../../../etc/passwd',
            '..\\..\\windows\\system32\\hosts',
            '/etc/passwd',
            'C:\\Windows\\System32\\drivers\\etc\\hosts',
            '../config.php',
            '../../wp-config.php'
        ];
        
        foreach ($maliciousTemplates as $template) {
            $rendered = $this->emailService->render($template, ['user_name' => 'Test']);
            
            // Should return empty or error, not system file contents
            $this->assertEmpty($rendered);
        }
    }

    /**
     * Test file inclusion prevention
     */
    public function testFileInclusionPrevention(): void {
        // Test template with potential file inclusion
        $maliciousTemplate = '<!DOCTYPE html>
<html>
<body>
    <h1>Hello !!user_name!!</h1>
    <?php include("/etc/passwd"); ?>
    <script src="!!malicious_script!!"></script>
</body>
</html>';
        
        file_put_contents($this->testPluginDir . '/email/malicious_template.html', $maliciousTemplate);
        
        $data = [
            'user_name' => 'Test User',
            'malicious_script' => 'http://evil.com/malicious.js'
        ];
        
        $rendered = $this->emailService->render('malicious_template', $data);
        
        // PHP code should not be executed
        $this->assertStringNotContainsString('root:x:0:0:', $rendered); // /etc/passwd content
        
        // Script should be escaped or removed
        $this->assertStringNotContainsString('http://evil.com/', $rendered);
    }

    /**
     * Test input validation for admin forms
     */
    public function testAdminFormInputValidation(): void {
        global $mock_current_user_can, $mock_wp_verify_nonce;
        $mock_current_user_can = true;
        $mock_wp_verify_nonce = true;
        
        // Test malicious SMTP settings
        $maliciousSettings = [
            'khm_smtp_host' => '<script>alert("XSS")</script>smtp.gmail.com',
            'khm_smtp_port' => '587; DROP TABLE wp_options;',
            'khm_smtp_username' => 'test@gmail.com<script>alert("XSS")</script>',
            'khm_email_from_name' => '"><script>alert("XSS")</script>'
        ];
        
        foreach ($maliciousSettings as $key => $value) {
            $_POST[$key] = $value;
        }
        
        $_POST['action'] = 'update_smtp_settings';
        $_POST['khm_email_settings_nonce'] = 'valid_nonce';
        
        ob_start();
        $this->emailAdmin->handle_settings_update();
        $output = ob_get_clean();
        
        // Settings should be sanitized
        global $mock_saved_options;
        foreach ($maliciousSettings as $key => $value) {
            if (isset($mock_saved_options[$key])) {
                $this->assertStringNotContainsString('<script>', $mock_saved_options[$key]);
                $this->assertStringNotContainsString('DROP TABLE', $mock_saved_options[$key]);
            }
        }
    }

    /**
     * Test authorization and capability checks
     */
    public function testAuthorizationAndCapabilityChecks(): void {
        global $mock_current_user_can;
        
        // Test without proper capabilities
        $mock_current_user_can = false;
        
        ob_start();
        $this->emailAdmin->render_admin_page();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('You do not have sufficient permissions', $output);
        
        // Test AJAX endpoints without capabilities
        $_POST['action'] = 'khm_test_email';
        $_POST['email'] = 'test@example.com';
        
        ob_start();
        $this->emailAdmin->ajax_send_test_email();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertFalse($response['success']);
        $this->assertStringContainsString('permission', strtolower($response['message']));
    }

    /**
     * Test nonce verification
     */
    public function testNonceVerification(): void {
        global $mock_current_user_can, $mock_wp_verify_nonce, $mock_wp_mail_calls;
        $mock_current_user_can = true;
        $mock_wp_mail_calls = [];
        
        // Test with invalid nonce
        $mock_wp_verify_nonce = false;
        
        $_POST['test_email'] = 'test@example.com';
        $_POST['test_subject'] = 'Test Subject';
        $_POST['khm_email_test_nonce'] = 'invalid_nonce';
        
        $result = $this->emailAdmin->handle_test_email();
        
        $this->assertFalse($result);
        $this->assertEmpty($mock_wp_mail_calls);
        
        // Test AJAX with invalid nonce
        $_POST['nonce'] = 'invalid_nonce';
        $_POST['email'] = 'test@example.com';
        
        ob_start();
        $this->emailAdmin->ajax_send_test_email();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertFalse($response['success']);
    }

    /**
     * Test CSRF protection
     */
    public function testCSRFProtection(): void {
        global $mock_wp_verify_nonce;
        
        // Simulate CSRF attack (no nonce)
        unset($_POST['khm_email_test_nonce']);
        $_POST['test_email'] = 'test@example.com';
        $_POST['action'] = 'send_test_email';
        
        $mock_wp_verify_nonce = false;
        
        $result = $this->emailAdmin->handle_test_email();
        
        $this->assertFalse($result);
        
        // Simulate CSRF attack on AJAX endpoint
        unset($_POST['nonce']);
        $_POST['email'] = 'test@example.com';
        
        ob_start();
        $this->emailAdmin->ajax_send_test_email();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        $this->assertFalse($response['success']);
    }

    /**
     * Test data sanitization
     */
    public function testDataSanitization(): void {
        $maliciousData = [
            'user_name' => '<script>alert("XSS")</script>John Doe',
            'email' => 'test@example.com<script>alert("XSS")</script>',
            'subject' => 'Test Subject"><script>alert("XSS")</script>',
            'content' => 'Content with <script>alert("XSS")</script> script'
        ];
        
        foreach ($maliciousData as $key => $value) {
            $sanitized = $this->emailAdmin->sanitize_field($key, $value);
            
            $this->assertStringNotContainsString('<script>', $sanitized);
            $this->assertStringNotContainsString('javascript:', $sanitized);
            
            // Should retain safe content
            if ($key === 'user_name') {
                $this->assertStringContainsString('John Doe', $sanitized);
            }
            if ($key === 'email') {
                $this->assertStringContainsString('test@example.com', $sanitized);
            }
        }
    }

    /**
     * Test secure password handling
     */
    public function testSecurePasswordHandling(): void {
        $passwords = [
            'plain_password',
            'password_with_special_chars!@#$',
            'very_long_password_with_many_characters_1234567890'
        ];
        
        foreach ($passwords as $password) {
            // Password should be encrypted/hashed when stored
            $stored = $this->emailAdmin->secure_password_storage($password);
            
            $this->assertNotEquals($password, $stored);
            $this->assertNotEmpty($stored);
            
            // Should be able to verify the password
            $verified = $this->emailAdmin->verify_stored_password($password, $stored);
            $this->assertTrue($verified);
        }
    }

    /**
     * Test rate limiting protection
     */
    public function testRateLimitingProtection(): void {
        global $mock_get_option;
        
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_rate_limit_enabled') return true;
            if ($option === 'khm_email_rate_limit_per_minute') return 5;
            return $default;
        };
        
        $results = [];
        
        // Try to send many emails rapidly
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->emailService
                ->setSubject("Rate Limit Test {$i}")
                ->send('security_template', 'test@example.com', ['user_name' => "User {$i}"]);
        }
        
        // Some should be blocked by rate limiting
        $successfulSends = count(array_filter($results));
        $this->assertLessThanOrEqual(5, $successfulSends, 'Rate limiting should prevent excessive emails');
    }

    /**
     * Test session security
     */
    public function testSessionSecurity(): void {
        // Test session fixation prevention
        $initialSessionId = session_id();
        
        // Simulate login
        $this->emailAdmin->secure_session_start();
        
        $newSessionId = session_id();
        
        // Session ID should change after authentication
        $this->assertNotEquals($initialSessionId, $newSessionId);
        
        // Test session timeout
        $_SESSION['khm_email_last_activity'] = time() - 3600; // 1 hour ago
        
        $isValid = $this->emailAdmin->validate_session();
        $this->assertFalse($isValid, 'Session should expire after timeout');
    }

    /**
     * Test logging and audit trail
     */
    public function testLoggingAndAuditTrail(): void {
        global $mock_security_logs;
        $mock_security_logs = [];
        
        // Test failed login attempt logging
        $this->emailAdmin->log_security_event('failed_login', [
            'username' => 'admin',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'Mozilla/5.0...'
        ]);
        
        $this->assertNotEmpty($mock_security_logs);
        $this->assertEquals('failed_login', $mock_security_logs[0]['event_type']);
        
        // Test suspicious activity logging
        $this->emailAdmin->log_security_event('suspicious_activity', [
            'description' => 'Multiple failed login attempts',
            'ip_address' => '192.168.1.100'
        ]);
        
        $this->assertCount(2, $mock_security_logs);
    }

    /**
     * Test file upload security
     */
    public function testFileUploadSecurity(): void {
        // Test malicious file upload
        $maliciousFiles = [
            'shell.php' => '<?php system($_GET["cmd"]); ?>',
            'script.js' => 'alert("XSS");',
            'malware.exe' => 'binary_content_here'
        ];
        
        foreach ($maliciousFiles as $filename => $content) {
            $result = $this->emailAdmin->handle_template_upload($filename, $content);
            
            // Should reject dangerous files
            $this->assertFalse($result, "Should reject upload of {$filename}");
        }
        
        // Test legitimate file upload
        $legitimateTemplate = '<!DOCTYPE html><html><body><h1>Hello World</h1></body></html>';
        $result = $this->emailAdmin->handle_template_upload('welcome.html', $legitimateTemplate);
        
        $this->assertTrue($result, 'Should accept legitimate HTML template');
    }

    /**
     * Test configuration security
     */
    public function testConfigurationSecurity(): void {
        // Test sensitive data exposure
        $config = $this->emailAdmin->get_configuration_for_display();
        
        // Passwords should be masked
        $this->assertStringNotContainsString('password123', $config);
        $this->assertStringContainsString('****', $config);
        
        // API keys should be partially masked
        if (strpos($config, 'api_key') !== false) {
            $this->assertStringContainsString('****', $config);
        }
    }

    /**
     * Helper methods
     */
    private function createTestTemplates(): void {
        $securityTemplate = '<!DOCTYPE html>
<html>
<head><title>Security Test</title></head>
<body>
    <h1>Hello !!user_name!!</h1>
    <div class="content">!!content!!</div>
    <p class="message">!!message!!</p>
    <a href="!!link!!">Click here</a>
    <div style="!!style!!">Styled content</div>
</body>
</html>';
        
        file_put_contents($this->testPluginDir . '/email/security_template.html', $securityTemplate);
    }

    private function mockWordPressFunctions(): void {
        global $mock_current_user_can, $mock_wp_verify_nonce, $mock_wp_mail_calls;
        global $mock_saved_options, $mock_security_logs, $mock_get_option;
        
        $mock_current_user_can = true;
        $mock_wp_verify_nonce = true;
        $mock_wp_mail_calls = [];
        $mock_saved_options = [];
        $mock_security_logs = [];
        
        // Mock WordPress security functions
        if (!function_exists('current_user_can')) {
            function current_user_can($capability) {
                global $mock_current_user_can;
                return $mock_current_user_can;
            }
        }
        
        if (!function_exists('wp_verify_nonce')) {
            function wp_verify_nonce($nonce, $action) {
                global $mock_wp_verify_nonce;
                return $mock_wp_verify_nonce;
            }
        }
        
        if (!function_exists('wp_create_nonce')) {
            function wp_create_nonce($action) {
                return 'secure_nonce_' . $action;
            }
        }
        
        if (!function_exists('sanitize_text_field')) {
            function sanitize_text_field($str) {
                return htmlspecialchars(strip_tags($str), ENT_QUOTES, 'UTF-8');
            }
        }
        
        if (!function_exists('sanitize_email')) {
            function sanitize_email($email) {
                $email = preg_replace('/[^a-zA-Z0-9@._-]/', '', $email);
                return filter_var($email, FILTER_SANITIZE_EMAIL);
            }
        }
        
        if (!function_exists('wp_kses')) {
            function wp_kses($string, $allowed_html) {
                return strip_tags($string);
            }
        }
        
        if (!function_exists('esc_html')) {
            function esc_html($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        }
        
        if (!function_exists('esc_attr')) {
            function esc_attr($text) {
                return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
            }
        }
        
        if (!function_exists('esc_url')) {
            function esc_url($url) {
                return filter_var($url, FILTER_SANITIZE_URL);
            }
        }
        
        if (!function_exists('update_option')) {
            function update_option($option, $value) {
                global $mock_saved_options;
                $mock_saved_options[$option] = $value;
                return true;
            }
        }
        
        if (!function_exists('get_option')) {
            function get_option($option, $default = null) {
                global $mock_get_option, $mock_saved_options;
                if ($mock_get_option) {
                    return $mock_get_option($option, $default);
                }
                return $mock_saved_options[$option] ?? $default;
            }
        }
        
        if (!function_exists('wp_hash_password')) {
            function wp_hash_password($password) {
                return password_hash($password, PASSWORD_DEFAULT);
            }
        }
        
        if (!function_exists('wp_check_password')) {
            function wp_check_password($password, $hash) {
                return password_verify($password, $hash);
            }
        }
        
        if (!function_exists('wp_mail')) {
            function wp_mail($to, $subject, $message, $headers = '', $attachments = []) {
                global $mock_wp_mail_calls;
                
                $mock_wp_mail_calls[] = [
                    'to' => $to,
                    'subject' => $subject,
                    'message' => $message,
                    'headers' => $headers,
                    'attachments' => $attachments
                ];
                
                return true;
            }
        }
        
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data = null) {
                echo json_encode(['success' => false, 'message' => $data]);
                exit;
            }
        }
        
        if (!function_exists('wp_send_json_success')) {
            function wp_send_json_success($data = null) {
                echo json_encode(['success' => true, 'data' => $data]);
                exit;
            }
        }
        
        // Mock session functions
        if (!function_exists('session_start')) {
            function session_start() {
                return true;
            }
        }
        
        if (!function_exists('session_regenerate_id')) {
            function session_regenerate_id($delete_old_session = false) {
                return true;
            }
        }
        
        if (!function_exists('session_id')) {
            function session_id($id = null) {
                static $session_id = 'test_session_123';
                if ($id !== null) {
                    $session_id = $id;
                }
                return $session_id;
            }
        }
        
        // Mock other functions
        if (!function_exists('apply_filters')) {
            function apply_filters($hook, $value, ...$args) {
                return $value;
            }
        }
        
        if (!function_exists('do_action')) {
            function do_action($hook, ...$args) {
                // Do nothing
            }
        }
        
        if (!function_exists('error_log')) {
            function error_log($message) {
                global $mock_security_logs;
                $mock_security_logs[] = [
                    'timestamp' => time(),
                    'message' => $message,
                    'event_type' => 'error_log'
                ];
            }
        }
    }

    private function removeDirectory(string $dir): void {
        if (!is_dir($dir)) return;
        
        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}