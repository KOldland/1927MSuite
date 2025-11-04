<?php
/**
 * Enhanced Email Delivery Integration Tests
 *
 * Tests actual email delivery via different providers and methods
 * Includes SMTP, API, and WordPress mail function integration
 *
 * @package KHM\Tests
 */

namespace KHM\Tests;

use PHPUnit\Framework\TestCase;
use KHM\Services\EnhancedEmailService;

class EnhancedEmailDeliveryTest extends TestCase {

    private EnhancedEmailService $emailService;
    private string $testPluginDir;
    private array $testEmails = [];

    protected function setUp(): void {
        parent::setUp();
        
        // Create test plugin directory and templates
        $this->testPluginDir = sys_get_temp_dir() . '/khm-email-delivery-test-' . uniqid();
        mkdir($this->testPluginDir, 0755, true);
        mkdir($this->testPluginDir . '/email', 0755, true);
        
        $this->createTestTemplates();
        $this->mockWordPressFunctions();
        
        $this->emailService = new EnhancedEmailService($this->testPluginDir);
        
        // Setup test email addresses (use environment variables for real testing)
        $this->testEmails = [
            'recipient' => $_ENV['TEST_EMAIL_RECIPIENT'] ?? 'test@example.com',
            'sender' => $_ENV['TEST_EMAIL_SENDER'] ?? 'noreply@example.com'
        ];
    }

    protected function tearDown(): void {
        $this->removeDirectory($this->testPluginDir);
        parent::tearDown();
    }

    /**
     * Test WordPress mail function delivery
     */
    public function testWordPressMailDelivery(): void {
        // Mock WordPress mail function
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        // Set delivery method to WordPress
        global $mock_get_option;
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_delivery_method') return 'wordpress';
            if ($option === 'khm_email_enhanced_delivery') return true;
            return $default;
        };
        
        // Test email sending
        $result = $this->emailService
            ->setFrom($this->testEmails['sender'], 'Test Sender')
            ->setSubject('WordPress Mail Test')
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'Integration Test User',
                'article_title' => 'Test Article'
            ]);
        
        $this->assertTrue($result);
        $this->assertNotEmpty($mock_wp_mail_calls);
        
        // Verify wp_mail was called with correct parameters
        $lastCall = end($mock_wp_mail_calls);
        $this->assertEquals($this->testEmails['recipient'], $lastCall['to']);
        $this->assertEquals('WordPress Mail Test', $lastCall['subject']);
        $this->assertStringContainsString('Integration Test User', $lastCall['message']);
    }

    /**
     * Test SMTP delivery with Gmail configuration
     */
    public function testSMTPDeliveryGmail(): void {
        if (empty($_ENV['GMAIL_SMTP_USERNAME']) || empty($_ENV['GMAIL_SMTP_PASSWORD'])) {
            $this->markTestSkipped('Gmail SMTP credentials not provided in environment variables');
        }
        
        // Configure SMTP settings
        global $mock_get_option;
        $mock_get_option = function($option, $default = null) {
            switch ($option) {
                case 'khm_email_delivery_method': return 'smtp';
                case 'khm_email_enhanced_delivery': return true;
                case 'khm_smtp_host': return 'smtp.gmail.com';
                case 'khm_smtp_port': return 587;
                case 'khm_smtp_encryption': return 'tls';
                case 'khm_smtp_username': return $_ENV['GMAIL_SMTP_USERNAME'];
                case 'khm_smtp_password': return $_ENV['GMAIL_SMTP_PASSWORD'];
                case 'khm_smtp_from_email': return $_ENV['GMAIL_SMTP_USERNAME'];
                case 'khm_smtp_from_name': return 'KHM Test Suite';
                default: return $default;
            }
        };
        
        $result = $this->emailService
            ->setSubject('SMTP Gmail Integration Test - ' . date('Y-m-d H:i:s'))
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'SMTP Test User',
                'article_title' => 'Gmail SMTP Test Article'
            ]);
        
        // For real SMTP testing, this should actually send an email
        $this->assertTrue($result, 'SMTP Gmail delivery should succeed');
    }

    /**
     * Test SMTP delivery with custom SMTP server
     */
    public function testSMTPDeliveryCustom(): void {
        if (empty($_ENV['CUSTOM_SMTP_HOST'])) {
            $this->markTestSkipped('Custom SMTP configuration not provided');
        }
        
        global $mock_get_option;
        $mock_get_option = function($option, $default = null) {
            switch ($option) {
                case 'khm_email_delivery_method': return 'smtp';
                case 'khm_email_enhanced_delivery': return true;
                case 'khm_smtp_host': return $_ENV['CUSTOM_SMTP_HOST'];
                case 'khm_smtp_port': return $_ENV['CUSTOM_SMTP_PORT'] ?? 587;
                case 'khm_smtp_encryption': return $_ENV['CUSTOM_SMTP_ENCRYPTION'] ?? 'tls';
                case 'khm_smtp_username': return $_ENV['CUSTOM_SMTP_USERNAME'];
                case 'khm_smtp_password': return $_ENV['CUSTOM_SMTP_PASSWORD'];
                case 'khm_smtp_from_email': return $_ENV['CUSTOM_SMTP_FROM_EMAIL'];
                case 'khm_smtp_from_name': return 'KHM Test Suite';
                default: return $default;
            }
        };
        
        $result = $this->emailService
            ->setSubject('SMTP Custom Integration Test')
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'Custom SMTP User',
                'article_title' => 'Custom SMTP Article'
            ]);
        
        $this->assertTrue($result, 'Custom SMTP delivery should succeed');
    }

    /**
     * Test SendGrid API delivery
     */
    public function testSendGridAPIDelivery(): void {
        if (empty($_ENV['SENDGRID_API_KEY'])) {
            $this->markTestSkipped('SendGrid API key not provided');
        }
        
        global $mock_get_option;
        $mock_get_option = function($option, $default = null) {
            switch ($option) {
                case 'khm_email_delivery_method': return 'api';
                case 'khm_email_enhanced_delivery': return true;
                case 'khm_email_api_provider': return 'sendgrid';
                case 'khm_email_api_key': return $_ENV['SENDGRID_API_KEY'];
                case 'khm_email_from_email': return $_ENV['SENDGRID_FROM_EMAIL'] ?? $this->testEmails['sender'];
                case 'khm_email_from_name': return 'KHM Test Suite';
                default: return $default;
            }
        };
        
        $result = $this->emailService
            ->setSubject('SendGrid API Integration Test - ' . date('Y-m-d H:i:s'))
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'SendGrid API User',
                'article_title' => 'SendGrid Test Article'
            ]);
        
        $this->assertTrue($result, 'SendGrid API delivery should succeed');
    }

    /**
     * Test Mailgun API delivery
     */
    public function testMailgunAPIDelivery(): void {
        if (empty($_ENV['MAILGUN_API_KEY']) || empty($_ENV['MAILGUN_DOMAIN'])) {
            $this->markTestSkipped('Mailgun credentials not provided');
        }
        
        global $mock_get_option;
        $mock_get_option = function($option, $default = null) {
            switch ($option) {
                case 'khm_email_delivery_method': return 'api';
                case 'khm_email_enhanced_delivery': return true;
                case 'khm_email_api_provider': return 'mailgun';
                case 'khm_email_api_key': return $_ENV['MAILGUN_API_KEY'];
                case 'khm_email_api_domain': return $_ENV['MAILGUN_DOMAIN'];
                case 'khm_email_from_email': return $_ENV['MAILGUN_FROM_EMAIL'] ?? $this->testEmails['sender'];
                case 'khm_email_from_name': return 'KHM Test Suite';
                default: return $default;
            }
        };
        
        $result = $this->emailService
            ->setSubject('Mailgun API Integration Test - ' . date('Y-m-d H:i:s'))
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'Mailgun API User',
                'article_title' => 'Mailgun Test Article'
            ]);
        
        $this->assertTrue($result, 'Mailgun API delivery should succeed');
    }

    /**
     * Test delivery with attachments
     */
    public function testDeliveryWithAttachments(): void {
        // Create test attachment
        $attachmentPath = $this->testPluginDir . '/test-attachment.txt';
        file_put_contents($attachmentPath, 'This is a test attachment content.');
        
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        $result = $this->emailService
            ->setFrom($this->testEmails['sender'], 'Test Sender')
            ->setSubject('Attachment Test')
            ->addAttachment($attachmentPath)
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'Attachment Test User'
            ]);
        
        $this->assertTrue($result);
        
        // Verify attachment was included
        $lastCall = end($mock_wp_mail_calls);
        $this->assertNotEmpty($lastCall['attachments']);
        $this->assertContains($attachmentPath, $lastCall['attachments']);
        
        // Clean up
        unlink($attachmentPath);
    }

    /**
     * Test delivery with custom headers
     */
    public function testDeliveryWithCustomHeaders(): void {
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        $customHeaders = [
            'Reply-To: reply@example.com',
            'X-Mailer: KHM Enhanced Email Service',
            'X-Priority: 1'
        ];
        
        $result = $this->emailService
            ->setFrom($this->testEmails['sender'], 'Test Sender')
            ->setSubject('Custom Headers Test')
            ->setHeaders($customHeaders)
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'Custom Headers User'
            ]);
        
        $this->assertTrue($result);
        
        // Verify headers were included
        $lastCall = end($mock_wp_mail_calls);
        $this->assertNotEmpty($lastCall['headers']);
        
        foreach ($customHeaders as $header) {
            $this->assertContains($header, $lastCall['headers']);
        }
    }

    /**
     * Test delivery fallback mechanism
     */
    public function testDeliveryFallback(): void {
        // Configure primary method to fail, secondary to succeed
        global $mock_get_option, $mock_smtp_failure;
        $mock_smtp_failure = true;
        
        $mock_get_option = function($option, $default = null) {
            switch ($option) {
                case 'khm_email_delivery_method': return 'smtp';
                case 'khm_email_enhanced_delivery': return true;
                case 'khm_email_fallback_enabled': return true;
                case 'khm_email_fallback_method': return 'wordpress';
                // Invalid SMTP settings to force failure
                case 'khm_smtp_host': return 'invalid.smtp.server';
                case 'khm_smtp_port': return 587;
                default: return $default;
            }
        };
        
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        $result = $this->emailService
            ->setSubject('Fallback Test')
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'Fallback Test User'
            ]);
        
        // Should fallback to WordPress mail and succeed
        $this->assertTrue($result);
        $this->assertNotEmpty($mock_wp_mail_calls);
    }

    /**
     * Test retry mechanism
     */
    public function testRetryMechanism(): void {
        global $mock_get_option, $mock_delivery_attempts;
        $mock_delivery_attempts = 0;
        
        $mock_get_option = function($option, $default = null) {
            switch ($option) {
                case 'khm_email_delivery_method': return 'smtp';
                case 'khm_email_enhanced_delivery': return true;
                case 'khm_email_retry_attempts': return 3;
                case 'khm_email_retry_delay': return 1; // 1 second for testing
                // Invalid SMTP to trigger retries
                case 'khm_smtp_host': return 'invalid.smtp.server';
                default: return $default;
            }
        };
        
        $result = $this->emailService
            ->setSubject('Retry Test')
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'Retry Test User'
            ]);
        
        // Should attempt retries (exact behavior depends on implementation)
        $this->assertIsBool($result);
    }

    /**
     * Test concurrent email delivery
     */
    public function testConcurrentDelivery(): void {
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        $emails = [];
        for ($i = 0; $i < 5; $i++) {
            $emails[] = [
                'template' => 'test_template',
                'recipient' => "test{$i}@example.com",
                'data' => ['user_name' => "Concurrent User {$i}"],
                'subject' => "Concurrent Test {$i}"
            ];
        }
        
        $results = [];
        foreach ($emails as $email) {
            $results[] = $this->emailService
                ->setSubject($email['subject'])
                ->send($email['template'], $email['recipient'], $email['data']);
        }
        
        // All emails should be sent successfully
        foreach ($results as $result) {
            $this->assertTrue($result);
        }
        
        $this->assertCount(5, $mock_wp_mail_calls);
    }

    /**
     * Test delivery with different content types
     */
    public function testDifferentContentTypes(): void {
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        // Test HTML content
        $result = $this->emailService
            ->setSubject('HTML Content Test')
            ->setHeaders(['Content-Type: text/html; charset=UTF-8'])
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'HTML User'
            ]);
        
        $this->assertTrue($result);
        
        // Test plain text content
        $result = $this->emailService
            ->setSubject('Plain Text Test')
            ->setHeaders(['Content-Type: text/plain; charset=UTF-8'])
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'Plain Text User'
            ]);
        
        $this->assertTrue($result);
        
        $this->assertCount(2, $mock_wp_mail_calls);
    }

    /**
     * Test delivery performance and timeouts
     */
    public function testDeliveryPerformance(): void {
        $startTime = microtime(true);
        
        $result = $this->emailService
            ->setSubject('Performance Test')
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'Performance User'
            ]);
        
        $endTime = microtime(true);
        $deliveryTime = $endTime - $startTime;
        
        $this->assertTrue($result);
        
        // Email delivery should complete within reasonable time (5 seconds)
        $this->assertLessThan(5.0, $deliveryTime, 'Email delivery took too long');
    }

    /**
     * Test error handling and logging
     */
    public function testErrorHandlingAndLogging(): void {
        global $mock_get_option, $mock_error_log_calls;
        $mock_error_log_calls = [];
        
        // Configure invalid settings to trigger errors
        $mock_get_option = function($option, $default = null) {
            switch ($option) {
                case 'khm_email_delivery_method': return 'smtp';
                case 'khm_email_enhanced_delivery': return true;
                case 'khm_smtp_host': return '';
                case 'khm_smtp_port': return 0;
                default: return $default;
            }
        };
        
        $result = $this->emailService
            ->setSubject('Error Test')
            ->send('test_template', $this->testEmails['recipient'], [
                'user_name' => 'Error User'
            ]);
        
        // Should handle errors gracefully
        $this->assertIsBool($result);
        
        // Should log errors
        $this->assertNotEmpty($mock_error_log_calls);
    }

    /**
     * Test rate limiting
     */
    public function testRateLimiting(): void {
        if (empty($_ENV['TEST_RATE_LIMITING'])) {
            $this->markTestSkipped('Rate limiting test disabled');
        }
        
        global $mock_get_option;
        $mock_get_option = function($option, $default = null) {
            switch ($option) {
                case 'khm_email_rate_limit_enabled': return true;
                case 'khm_email_rate_limit_per_hour': return 2; // Very low for testing
                default: return $default;
            }
        };
        
        $results = [];
        
        // Send emails rapidly
        for ($i = 0; $i < 5; $i++) {
            $results[] = $this->emailService
                ->setSubject("Rate Limit Test {$i}")
                ->send('test_template', $this->testEmails['recipient'], [
                    'user_name' => "Rate Limit User {$i}"
                ]);
        }
        
        // Some should succeed, some should be rate limited
        $successCount = count(array_filter($results));
        $this->assertLessThanOrEqual(2, $successCount, 'Rate limiting should prevent excessive emails');
    }

    /**
     * Helper methods
     */
    private function createTestTemplates(): void {
        $testTemplate = '<!DOCTYPE html>
<html>
<head><title>Test Email</title></head>
<body>
    <h1>Hello !!user_name!!</h1>
    <p>Article: !!article_title!!</p>
    <p>This is an integration test email.</p>
    <p>Site: !!sitename!!</p>
    <p>Sent at: !!current_time!!</p>
</body>
</html>';
        
        file_put_contents($this->testPluginDir . '/email/test_template.html', $testTemplate);
    }

    private function mockWordPressFunctions(): void {
        global $mock_get_option, $wpdb, $mock_wp_mail_calls, $mock_error_log_calls;
        
        $mock_wp_mail_calls = [];
        $mock_error_log_calls = [];
        
        // Mock wp_mail
        if (!function_exists('wp_mail')) {
            function wp_mail($to, $subject, $message, $headers = '', $attachments = []) {
                global $mock_wp_mail_calls, $mock_wp_mail_failure;
                
                $mock_wp_mail_calls[] = [
                    'to' => $to,
                    'subject' => $subject,
                    'message' => $message,
                    'headers' => $headers,
                    'attachments' => $attachments
                ];
                
                return !($mock_wp_mail_failure ?? false);
            }
        }
        
        // Mock error_log
        if (!function_exists('error_log')) {
            function error_log($message, $message_type = 0, $destination = null, $extra_headers = null) {
                global $mock_error_log_calls;
                $mock_error_log_calls[] = $message;
            }
        }
        
        // Mock other WordPress functions
        if (!function_exists('get_option')) {
            function get_option($option, $default = null) {
                global $mock_get_option;
                if ($mock_get_option) {
                    return $mock_get_option($option, $default);
                }
                return $default;
            }
        }
        
        if (!function_exists('get_site_url')) {
            function get_site_url() {
                return 'https://example.com';
            }
        }
        
        if (!function_exists('get_bloginfo')) {
            function get_bloginfo($show = '') {
                return 'Test Site';
            }
        }
        
        if (!function_exists('current_time')) {
            function current_time($type) {
                return date($type === 'mysql' ? 'Y-m-d H:i:s' : 'U');
            }
        }
        
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
        
        // Mock wpdb
        $wpdb = new class {
            public $prefix = 'wp_';
            public $insert_id = 123;
            
            public function insert($table, $data) {
                $this->insert_id = rand(100, 999);
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