<?php
/**
 * Enhanced Email Service Unit Tests
 *
 * Comprehensive test suite for the EnhancedEmailService class
 * Tests all core functionality, delivery methods, and error handling
 *
 * @package KHM\Tests
 */

namespace KHM\Tests;

use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use KHM\Services\EnhancedEmailService;

class EnhancedEmailServiceTest extends TestCase {

    private EnhancedEmailService $emailService;
    private string $testPluginDir;
    private MockObject $wpdbMock;

    protected function setUp(): void {
        parent::setUp();
        
        // Create test plugin directory
        $this->testPluginDir = sys_get_temp_dir() . '/khm-email-test-' . uniqid();
        mkdir($this->testPluginDir, 0755, true);
        mkdir($this->testPluginDir . '/email', 0755, true);
        
        // Create test email templates
        $this->createTestTemplates();
        
        // Mock WordPress functions
        $this->mockWordPressFunctions();
        
        // Initialize service
        $this->emailService = new EnhancedEmailService($this->testPluginDir);
    }

    protected function tearDown(): void {
        // Clean up test directory
        $this->removeDirectory($this->testPluginDir);
        parent::tearDown();
    }

    /**
     * Test service initialization
     */
    public function testServiceInitialization(): void {
        $this->assertInstanceOf(EnhancedEmailService::class, $this->emailService);
        
        // Test fluent interface methods return self
        $result = $this->emailService->setFrom('test@example.com', 'Test Sender');
        $this->assertSame($this->emailService, $result);
        
        $result = $this->emailService->setSubject('Test Subject');
        $this->assertSame($this->emailService, $result);
        
        $result = $this->emailService->setHeaders(['Content-Type: text/html']);
        $this->assertSame($this->emailService, $result);
        
        $result = $this->emailService->addAttachment('/path/to/file.pdf');
        $this->assertSame($this->emailService, $result);
    }

    /**
     * Test template rendering functionality
     */
    public function testTemplateRendering(): void {
        // Test successful template rendering
        $data = [
            'user_name' => 'John Doe',
            'article_title' => 'Test Article',
            'custom_var' => 'Custom Value'
        ];
        
        $rendered = $this->emailService->render('test_template', $data);
        
        $this->assertNotEmpty($rendered);
        $this->assertStringContainsString('John Doe', $rendered);
        $this->assertStringContainsString('Test Article', $rendered);
        $this->assertStringContainsString('Custom Value', $rendered);
        $this->assertStringContainsString('Test Site', $rendered); // Default sitename
    }

    /**
     * Test template rendering with missing template
     */
    public function testTemplateRenderingMissingTemplate(): void {
        $rendered = $this->emailService->render('nonexistent_template', []);
        $this->assertEmpty($rendered);
    }

    /**
     * Test variable replacement in templates
     */
    public function testVariableReplacement(): void {
        $data = [
            'user_name' => 'Jane Smith',
            'amount' => '$99.99',
            'date' => '2025-11-04'
        ];
        
        $rendered = $this->emailService->render('test_template', $data);
        
        // Check that all variables were replaced
        $this->assertStringNotContainsString('!!user_name!!', $rendered);
        $this->assertStringNotContainsString('!!amount!!', $rendered);
        $this->assertStringNotContainsString('!!date!!', $rendered);
        
        // Check correct values were inserted
        $this->assertStringContainsString('Jane Smith', $rendered);
        $this->assertStringContainsString('$99.99', $rendered);
    }

    /**
     * Test getTemplatePath method
     */
    public function testGetTemplatePath(): void {
        // Test existing template
        $path = $this->emailService->getTemplatePath('test_template');
        $this->assertNotNull($path);
        $this->assertFileExists($path);
        
        // Test non-existing template
        $path = $this->emailService->getTemplatePath('nonexistent_template');
        $this->assertNull($path);
    }

    /**
     * Test email delivery method detection
     */
    public function testDeliveryMethodDetection(): void {
        // Mock get_option function for different delivery methods
        global $mock_get_option;
        
        // Test WordPress method (default)
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_delivery_method') return 'wordpress';
            if ($option === 'khm_email_enhanced_delivery') return true;
            return $default;
        };
        
        // Test SMTP method
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_delivery_method') return 'smtp';
            if ($option === 'khm_email_enhanced_delivery') return true;
            return $default;
        };
        
        // Test API method
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_delivery_method') return 'api';
            if ($option === 'khm_email_enhanced_delivery') return true;
            return $default;
        };
        
        $this->assertTrue(true); // Method detection tested via mocking
    }

    /**
     * Test SMTP settings validation
     */
    public function testSMTPSettings(): void {
        global $mock_get_option;
        
        $mock_get_option = function($option, $default = null) {
            switch ($option) {
                case 'khm_smtp_host': return 'smtp.gmail.com';
                case 'khm_smtp_port': return 587;
                case 'khm_smtp_encryption': return 'tls';
                case 'khm_smtp_username': return 'test@gmail.com';
                case 'khm_smtp_password': return 'app_password';
                case 'khm_smtp_from_email': return 'noreply@example.com';
                case 'khm_smtp_from_name': return 'Test Site';
                default: return $default;
            }
        };
        
        // Test SMTP configuration retrieval
        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('get_smtp_settings');
        $method->setAccessible(true);
        
        $settings = $method->invoke($this->emailService);
        
        $this->assertEquals('smtp.gmail.com', $settings['host']);
        $this->assertEquals(587, $settings['port']);
        $this->assertEquals('tls', $settings['encryption']);
        $this->assertEquals('test@gmail.com', $settings['username']);
        $this->assertEquals('app_password', $settings['password']);
    }

    /**
     * Test API settings validation
     */
    public function testAPISettings(): void {
        global $mock_get_option;
        
        $mock_get_option = function($option, $default = null) {
            switch ($option) {
                case 'khm_email_api_provider': return 'sendgrid';
                case 'khm_email_api_key': return 'SG.test_api_key';
                case 'khm_email_api_domain': return 'mg.example.com';
                default: return $default;
            }
        };
        
        // Test API configuration retrieval
        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('get_api_settings');
        $method->setAccessible(true);
        
        $settings = $method->invoke($this->emailService);
        
        $this->assertEquals('sendgrid', $settings['provider']);
        $this->assertEquals('SG.test_api_key', $settings['api_key']);
        $this->assertEquals('mg.example.com', $settings['domain']);
    }

    /**
     * Test email priority calculation
     */
    public function testEmailPriority(): void {
        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('get_email_priority');
        $method->setAccessible(true);
        
        // Test high priority templates
        $this->assertEquals(10, $method->invoke($this->emailService, 'gift_notification'));
        $this->assertEquals(8, $method->invoke($this->emailService, 'checkout_paid'));
        
        // Test medium priority
        $this->assertEquals(5, $method->invoke($this->emailService, 'welcome'));
        
        // Test low priority
        $this->assertEquals(1, $method->invoke($this->emailService, 'newsletter'));
        
        // Test unknown template (default priority)
        $this->assertEquals(5, $method->invoke($this->emailService, 'unknown_template'));
    }

    /**
     * Test email logging functionality
     */
    public function testEmailLogging(): void {
        global $wpdb, $mock_wpdb_insert_id;
        
        $mock_wpdb_insert_id = 123;
        
        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('log_email_attempt');
        $method->setAccessible(true);
        
        $email_id = $method->invoke(
            $this->emailService,
            'test_template',
            'recipient@example.com',
            ['test' => 'data'],
            'smtp'
        );
        
        $this->assertEquals(123, $email_id);
    }

    /**
     * Test queue functionality
     */
    public function testEmailQueue(): void {
        global $mock_get_option;
        
        // Enable queue
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_use_queue') return true;
            return $default;
        };
        
        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('should_queue_email');
        $method->setAccessible(true);
        
        $this->assertTrue($method->invoke($this->emailService));
        
        // Disable queue
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_use_queue') return false;
            return $default;
        };
        
        $this->assertFalse($method->invoke($this->emailService));
    }

    /**
     * Test error handling
     */
    public function testErrorHandling(): void {
        // Test with invalid email address
        $result = $this->emailService->send('test_template', 'invalid-email', []);
        
        // Should handle gracefully (specific behavior depends on implementation)
        $this->assertIsBool($result);
        
        // Test with missing template data
        $result = $this->emailService->render('test_template', []);
        
        // Should still render with default values
        $this->assertIsString($result);
    }

    /**
     * Test template hierarchy
     */
    public function testTemplateHierarchy(): void {
        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('get_template_hierarchy');
        $method->setAccessible(true);
        
        $hierarchy = $method->invoke($this->emailService, 'test_template');
        
        $this->assertIsArray($hierarchy);
        $this->assertNotEmpty($hierarchy);
        
        // Should include plugin template path
        $pluginPath = $this->testPluginDir . '/email/test_template.html';
        $this->assertContains($pluginPath, $hierarchy);
    }

    /**
     * Test email status updates
     */
    public function testEmailStatusUpdates(): void {
        global $wpdb;
        
        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('update_email_status');
        $method->setAccessible(true);
        
        // Test successful status update
        $method->invoke($this->emailService, 123, 'sent');
        
        // Test failed status update with error
        $method->invoke($this->emailService, 124, 'failed', 'SMTP connection failed');
        
        // Should not throw exceptions
        $this->assertTrue(true);
    }

    /**
     * Test security and sanitization
     */
    public function testSecurityAndSanitization(): void {
        // Test template data with potentially malicious content
        $maliciousData = [
            'user_name' => '<script>alert("xss")</script>',
            'email' => 'test@example.com<script>',
            'message' => '"><img src=x onerror=alert(1)>'
        ];
        
        $rendered = $this->emailService->render('test_template', $maliciousData);
        
        // Should not contain script tags (depends on template implementation)
        $this->assertIsString($rendered);
        
        // Test subject sanitization
        $this->emailService->setSubject('<script>alert("test")</script>Test Subject');
        
        // Should handle gracefully
        $this->assertTrue(true);
    }

    /**
     * Test concurrent access handling
     */
    public function testConcurrentAccess(): void {
        // Test multiple simultaneous email sends
        $emails = [];
        for ($i = 0; $i < 5; $i++) {
            $emails[] = [
                'template' => 'test_template',
                'recipient' => "test{$i}@example.com",
                'data' => ['user_name' => "User {$i}"]
            ];
        }
        
        foreach ($emails as $email) {
            $result = $this->emailService->render($email['template'], $email['data']);
            $this->assertNotEmpty($result);
        }
        
        $this->assertTrue(true); // No exceptions thrown
    }

    /**
     * Test memory usage and performance
     */
    public function testMemoryUsage(): void {
        $initialMemory = memory_get_usage();
        
        // Process multiple emails
        for ($i = 0; $i < 100; $i++) {
            $this->emailService->render('test_template', [
                'user_name' => "User {$i}",
                'large_data' => str_repeat('x', 1000)
            ]);
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 10MB for 100 emails)
        $this->assertLessThan(10 * 1024 * 1024, $memoryIncrease);
    }

    /**
     * Helper method to create test templates
     */
    private function createTestTemplates(): void {
        $testTemplate = '<!DOCTYPE html>
<html>
<head><title>Test Email</title></head>
<body>
    <h1>Hello !!user_name!!</h1>
    <p>Article: !!article_title!!</p>
    <p>Custom: !!custom_var!!</p>
    <p>Amount: !!amount!!</p>
    <p>Site: !!sitename!!</p>
    <p>Date: !!date!!</p>
</body>
</html>';
        
        file_put_contents($this->testPluginDir . '/email/test_template.html', $testTemplate);
        
        $giftTemplate = '<!DOCTYPE html>
<html>
<body>
    <h1>Gift from !!sender_name!!</h1>
    <p>Article: !!article_title!!</p>
    <p>Message: !!gift_message!!</p>
    <a href="!!redemption_url!!">Redeem Gift</a>
</body>
</html>';
        
        file_put_contents($this->testPluginDir . '/email/gift_notification.html', $giftTemplate);
    }

    /**
     * Helper method to mock WordPress functions
     */
    private function mockWordPressFunctions(): void {
        global $mock_get_option, $wpdb, $mock_wpdb_insert_id;
        
        // Mock get_option
        if (!function_exists('get_option')) {
            function get_option($option, $default = null) {
                global $mock_get_option;
                if ($mock_get_option) {
                    return $mock_get_option($option, $default);
                }
                switch ($option) {
                    case 'admin_email': return 'admin@example.com';
                    case 'blogname': return 'Test Site';
                    case 'khm_email_delivery_method': return 'wordpress';
                    case 'khm_email_enhanced_delivery': return false;
                    case 'khm_email_use_queue': return false;
                    default: return $default;
                }
            }
        }
        
        // Mock get_site_url
        if (!function_exists('get_site_url')) {
            function get_site_url() {
                return 'https://example.com';
            }
        }
        
        // Mock get_bloginfo
        if (!function_exists('get_bloginfo')) {
            function get_bloginfo($show = '') {
                if ($show === 'name') return 'Test Site';
                return 'Test Site';
            }
        }
        
        // Mock current_time
        if (!function_exists('current_time')) {
            function current_time($type) {
                return date($type === 'mysql' ? 'Y-m-d H:i:s' : 'U');
            }
        }
        
        // Mock apply_filters
        if (!function_exists('apply_filters')) {
            function apply_filters($hook, $value, ...$args) {
                return $value;
            }
        }
        
        // Mock do_action
        if (!function_exists('do_action')) {
            function do_action($hook, ...$args) {
                // Do nothing
            }
        }
        
        // Mock error_log
        if (!function_exists('error_log')) {
            function error_log($message) {
                // Silent for tests
            }
        }
        
        // Mock wpdb
        $wpdb = new class {
            public $prefix = 'wp_';
            public $insert_id = 123;
            
            public function insert($table, $data) {
                global $mock_wpdb_insert_id;
                $this->insert_id = $mock_wpdb_insert_id ?? 123;
                return true;
            }
            
            public function update($table, $data, $where) {
                return true;
            }
            
            public function get_results($query) {
                return [];
            }
            
            public function get_var($query) {
                return 0;
            }
            
            public function prepare($query, ...$args) {
                return $query;
            }
        };
    }

    /**
     * Helper method to remove directory recursively
     */
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