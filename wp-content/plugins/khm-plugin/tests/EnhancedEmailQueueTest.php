<?php
/**
 * Enhanced Email Queue System Tests
 *
 * Tests email queue functionality, background processing, retry logic,
 * and cron job integration
 *
 * @package KHM\Tests
 */

namespace KHM\Tests;

use PHPUnit\Framework\TestCase;
use KHM\Services\EnhancedEmailService;

class EnhancedEmailQueueTest extends TestCase {

    private EnhancedEmailService $emailService;
    private string $testPluginDir;
    private array $mockDatabase = [];

    protected function setUp(): void {
        parent::setUp();
        
        $this->testPluginDir = sys_get_temp_dir() . '/khm-email-queue-test-' . uniqid();
        mkdir($this->testPluginDir, 0755, true);
        mkdir($this->testPluginDir . '/email', 0755, true);
        
        $this->createTestTemplates();
        $this->mockWordPressFunctions();
        $this->initializeMockDatabase();
        
        $this->emailService = new EnhancedEmailService($this->testPluginDir);
    }

    protected function tearDown(): void {
        $this->removeDirectory($this->testPluginDir);
        parent::tearDown();
    }

    /**
     * Test email queuing functionality
     */
    public function testEmailQueuing(): void {
        global $mock_get_option;
        
        // Enable queue
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_use_queue') return true;
            if ($option === 'khm_email_enhanced_delivery') return true;
            return $default;
        };
        
        $result = $this->emailService
            ->setSubject('Queue Test Email')
            ->send('test_template', 'test@example.com', [
                'user_name' => 'Queue Test User'
            ]);
        
        $this->assertTrue($result);
        
        // Check that email was queued instead of sent immediately
        $this->assertNotEmpty($this->mockDatabase['email_queue']);
        
        $queuedEmail = $this->mockDatabase['email_queue'][0];
        $this->assertEquals('test@example.com', $queuedEmail['recipient']);
        $this->assertEquals('pending', $queuedEmail['status']);
        $this->assertNotEmpty($queuedEmail['template_data']);
    }

    /**
     * Test queue processing
     */
    public function testQueueProcessing(): void {
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        // Add emails to queue
        $this->addTestEmailsToQueue(5);
        
        // Process queue
        $processed = $this->emailService->process_email_queue();
        
        $this->assertGreaterThan(0, $processed);
        $this->assertNotEmpty($mock_wp_mail_calls);
        
        // Check that emails were marked as processed
        foreach ($this->mockDatabase['email_queue'] as $email) {
            $this->assertContains($email['status'], ['sent', 'failed']);
        }
    }

    /**
     * Test queue processing with priority
     */
    public function testQueueProcessingWithPriority(): void {
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        // Add emails with different priorities
        $this->addEmailToQueue('gift_notification', 'high@example.com', 10); // High priority
        $this->addEmailToQueue('newsletter', 'low@example.com', 1); // Low priority
        $this->addEmailToQueue('welcome', 'medium@example.com', 5); // Medium priority
        
        // Process queue (should process high priority first)
        $this->emailService->process_email_queue(1); // Process only 1 email
        
        $this->assertCount(1, $mock_wp_mail_calls);
        
        // Should have processed high priority email first
        $processedEmail = $mock_wp_mail_calls[0];
        $this->assertEquals('high@example.com', $processedEmail['to']);
    }

    /**
     * Test retry mechanism for failed emails
     */
    public function testRetryMechanism(): void {
        global $mock_wp_mail_failure, $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        // Add email to queue
        $this->addEmailToQueue('test_template', 'retry@example.com');
        
        // Make wp_mail fail initially
        $mock_wp_mail_failure = true;
        
        // Process queue
        $this->emailService->process_email_queue();
        
        // Email should be marked for retry
        $queuedEmail = $this->mockDatabase['email_queue'][0];
        $this->assertEquals('failed', $queuedEmail['status']);
        $this->assertGreaterThan(0, $queuedEmail['retry_count']);
        $this->assertNotNull($queuedEmail['next_retry']);
        
        // Fix wp_mail and process again
        $mock_wp_mail_failure = false;
        
        // Simulate retry time has passed
        $this->mockDatabase['email_queue'][0]['next_retry'] = date('Y-m-d H:i:s', time() - 1);
        
        $this->emailService->process_email_queue();
        
        // Email should now be sent
        $this->assertNotEmpty($mock_wp_mail_calls);
        $this->assertEquals('sent', $this->mockDatabase['email_queue'][0]['status']);
    }

    /**
     * Test maximum retry limit
     */
    public function testMaximumRetryLimit(): void {
        global $mock_wp_mail_failure, $mock_get_option;
        $mock_wp_mail_failure = true;
        
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_max_retries') return 3;
            return $default;
        };
        
        // Add email to queue
        $this->addEmailToQueue('test_template', 'maxretry@example.com');
        
        // Process queue multiple times to exceed retry limit
        for ($i = 0; $i < 5; $i++) {
            $this->emailService->process_email_queue();
            
            // Simulate retry time passing
            if (!empty($this->mockDatabase['email_queue'][0]['next_retry'])) {
                $this->mockDatabase['email_queue'][0]['next_retry'] = date('Y-m-d H:i:s', time() - 1);
            }
        }
        
        // Email should be marked as permanently failed
        $queuedEmail = $this->mockDatabase['email_queue'][0];
        $this->assertEquals('failed', $queuedEmail['status']);
        $this->assertGreaterThanOrEqual(3, $queuedEmail['retry_count']);
    }

    /**
     * Test queue processing batch size
     */
    public function testQueueBatchProcessing(): void {
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        // Add 10 emails to queue
        $this->addTestEmailsToQueue(10);
        
        // Process with batch size of 3
        $processed = $this->emailService->process_email_queue(3);
        
        $this->assertEquals(3, $processed);
        $this->assertCount(3, $mock_wp_mail_calls);
        
        // 7 emails should remain in queue
        $remainingEmails = array_filter($this->mockDatabase['email_queue'], function($email) {
            return $email['status'] === 'pending';
        });
        
        $this->assertCount(7, $remainingEmails);
    }

    /**
     * Test cron job integration
     */
    public function testCronJobIntegration(): void {
        global $mock_cron_schedules, $mock_cron_events;
        $mock_cron_schedules = [];
        $mock_cron_events = [];
        
        // Test cron schedule registration
        $this->emailService->register_cron_schedules();
        
        // Should register email processing schedule
        $this->assertArrayHasKey('khm_email_queue_processing', $mock_cron_schedules);
        
        // Test cron event scheduling
        $this->emailService->schedule_queue_processing();
        
        // Should schedule the cron event
        $this->assertContains('khm_process_email_queue', $mock_cron_events);
    }

    /**
     * Test queue cleanup of old emails
     */
    public function testQueueCleanup(): void {
        // Add old processed emails
        $oldDate = date('Y-m-d H:i:s', time() - (30 * 24 * 60 * 60)); // 30 days ago
        
        $this->addEmailToQueue('test_template', 'old1@example.com');
        $this->mockDatabase['email_queue'][0]['status'] = 'sent';
        $this->mockDatabase['email_queue'][0]['sent_at'] = $oldDate;
        
        $this->addEmailToQueue('test_template', 'old2@example.com');
        $this->mockDatabase['email_queue'][1]['status'] = 'failed';
        $this->mockDatabase['email_queue'][1]['created_at'] = $oldDate;
        
        // Add recent email
        $this->addEmailToQueue('test_template', 'recent@example.com');
        
        // Run cleanup
        $this->emailService->cleanup_old_queue_emails();
        
        // Old emails should be removed, recent should remain
        $this->assertCount(1, $this->mockDatabase['email_queue']);
        $this->assertEquals('recent@example.com', $this->mockDatabase['email_queue'][0]['recipient']);
    }

    /**
     * Test queue status reporting
     */
    public function testQueueStatusReporting(): void {
        // Add emails with different statuses
        $this->addEmailToQueue('test_template', 'pending1@example.com');
        $this->addEmailToQueue('test_template', 'pending2@example.com');
        
        $this->addEmailToQueue('test_template', 'sent@example.com');
        $this->mockDatabase['email_queue'][2]['status'] = 'sent';
        
        $this->addEmailToQueue('test_template', 'failed@example.com');
        $this->mockDatabase['email_queue'][3]['status'] = 'failed';
        
        // Get queue statistics
        $stats = $this->emailService->get_queue_statistics();
        
        $this->assertEquals(2, $stats['pending']);
        $this->assertEquals(1, $stats['sent']);
        $this->assertEquals(1, $stats['failed']);
        $this->assertEquals(4, $stats['total']);
    }

    /**
     * Test queue processing with different delivery methods
     */
    public function testQueueProcessingWithDifferentDeliveryMethods(): void {
        global $mock_get_option, $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        // Test with SMTP delivery method
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_delivery_method') return 'smtp';
            return $default;
        };
        
        $this->addEmailToQueue('test_template', 'smtp@example.com');
        $this->emailService->process_email_queue();
        
        // Test with API delivery method
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_delivery_method') return 'api';
            return $default;
        };
        
        $this->addEmailToQueue('test_template', 'api@example.com');
        $this->emailService->process_email_queue();
        
        // Both should be processed
        $sentEmails = array_filter($this->mockDatabase['email_queue'], function($email) {
            return $email['status'] === 'sent';
        });
        
        $this->assertCount(2, $sentEmails);
    }

    /**
     * Test queue processing performance
     */
    public function testQueueProcessingPerformance(): void {
        // Add many emails to queue
        $this->addTestEmailsToQueue(100);
        
        $startTime = microtime(true);
        
        // Process all emails
        $processed = $this->emailService->process_email_queue(100);
        
        $endTime = microtime(true);
        $processingTime = $endTime - $startTime;
        
        $this->assertEquals(100, $processed);
        
        // Should process 100 emails within reasonable time (10 seconds)
        $this->assertLessThan(10.0, $processingTime, 'Queue processing took too long');
    }

    /**
     * Test queue processing with memory management
     */
    public function testQueueMemoryManagement(): void {
        $initialMemory = memory_get_usage();
        
        // Process large batch
        $this->addTestEmailsToQueue(200);
        $this->emailService->process_email_queue(200);
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable (less than 50MB)
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease);
    }

    /**
     * Test queue locking mechanism
     */
    public function testQueueLocking(): void {
        global $mock_get_option;
        
        // Mock queue processing lock
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_queue_processing') return time();
            return $default;
        };
        
        $this->addTestEmailsToQueue(3);
        
        // Should not process when locked
        $processed = $this->emailService->process_email_queue();
        
        $this->assertEquals(0, $processed);
        
        // Remove lock
        $mock_get_option = function($option, $default = null) {
            if ($option === 'khm_email_queue_processing') return false;
            return $default;
        };
        
        // Should process when unlocked
        $processed = $this->emailService->process_email_queue();
        
        $this->assertGreaterThan(0, $processed);
    }

    /**
     * Test queue processing error handling
     */
    public function testQueueErrorHandling(): void {
        global $mock_error_log_calls;
        $mock_error_log_calls = [];
        
        // Add email with invalid template
        $this->addEmailToQueue('nonexistent_template', 'error@example.com');
        
        // Process queue
        $this->emailService->process_email_queue();
        
        // Should log error and mark email as failed
        $this->assertNotEmpty($mock_error_log_calls);
        $this->assertEquals('failed', $this->mockDatabase['email_queue'][0]['status']);
    }

    /**
     * Test queue priority calculation
     */
    public function testQueuePriorityCalculation(): void {
        $reflection = new \ReflectionClass($this->emailService);
        $method = $reflection->getMethod('get_email_priority');
        $method->setAccessible(true);
        
        // Test different template priorities
        $this->assertEquals(10, $method->invoke($this->emailService, 'gift_notification'));
        $this->assertEquals(8, $method->invoke($this->emailService, 'checkout_paid'));
        $this->assertEquals(5, $method->invoke($this->emailService, 'welcome'));
        $this->assertEquals(1, $method->invoke($this->emailService, 'newsletter'));
    }

    /**
     * Helper methods
     */
    private function createTestTemplates(): void {
        $testTemplate = '<!DOCTYPE html>
<html>
<body>
    <h1>Hello !!user_name!!</h1>
    <p>This is a test email from the queue.</p>
</body>
</html>';
        
        file_put_contents($this->testPluginDir . '/email/test_template.html', $testTemplate);
        
        $giftTemplate = '<!DOCTYPE html>
<html>
<body>
    <h1>Gift Notification</h1>
    <p>You have received a gift!</p>
</body>
</html>';
        
        file_put_contents($this->testPluginDir . '/email/gift_notification.html', $giftTemplate);
    }

    private function addTestEmailsToQueue(int $count): void {
        for ($i = 0; $i < $count; $i++) {
            $this->addEmailToQueue('test_template', "test{$i}@example.com");
        }
    }

    private function addEmailToQueue(string $template, string $recipient, int $priority = 5): void {
        $this->mockDatabase['email_queue'][] = [
            'id' => count($this->mockDatabase['email_queue']) + 1,
            'recipient' => $recipient,
            'subject' => 'Test Subject',
            'template_name' => $template,
            'template_data' => json_encode(['user_name' => 'Test User']),
            'priority' => $priority,
            'status' => 'pending',
            'retry_count' => 0,
            'created_at' => date('Y-m-d H:i:s'),
            'next_retry' => null,
            'sent_at' => null,
            'error_message' => null
        ];
    }

    private function initializeMockDatabase(): void {
        $this->mockDatabase = [
            'email_queue' => [],
            'email_logs' => [],
            'email_statistics' => []
        ];
    }

    private function mockWordPressFunctions(): void {
        global $mock_get_option, $wpdb, $mock_wp_mail_calls, $mock_error_log_calls;
        global $mock_cron_schedules, $mock_cron_events;
        
        $mock_wp_mail_calls = [];
        $mock_error_log_calls = [];
        $mock_cron_schedules = [];
        $mock_cron_events = [];
        
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
            function error_log($message) {
                global $mock_error_log_calls;
                $mock_error_log_calls[] = $message;
            }
        }
        
        // Mock WordPress cron functions
        if (!function_exists('wp_schedule_event')) {
            function wp_schedule_event($timestamp, $recurrence, $hook, $args = []) {
                global $mock_cron_events;
                $mock_cron_events[] = $hook;
                return true;
            }
        }
        
        if (!function_exists('wp_clear_scheduled_hook')) {
            function wp_clear_scheduled_hook($hook, $args = []) {
                return true;
            }
        }
        
        if (!function_exists('wp_next_scheduled')) {
            function wp_next_scheduled($hook, $args = []) {
                return false;
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
        
        if (!function_exists('update_option')) {
            function update_option($option, $value) {
                return true;
            }
        }
        
        if (!function_exists('delete_option')) {
            function delete_option($option) {
                return true;
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
        
        // Mock wpdb with queue operations
        $wpdb = new class {
            public $prefix = 'wp_';
            public $insert_id = 123;
            
            public function insert($table, $data) {
                global $mockDatabase;
                
                if (strpos($table, 'email_queue') !== false) {
                    $data['id'] = count($mockDatabase['email_queue']) + 1;
                    $mockDatabase['email_queue'][] = $data;
                }
                
                $this->insert_id = $data['id'] ?? rand(100, 999);
                return true;
            }
            
            public function update($table, $data, $where) {
                global $mockDatabase;
                
                if (strpos($table, 'email_queue') !== false && isset($where['id'])) {
                    foreach ($mockDatabase['email_queue'] as &$email) {
                        if ($email['id'] == $where['id']) {
                            $email = array_merge($email, $data);
                            break;
                        }
                    }
                }
                
                return true;
            }
            
            public function get_results($query) {
                global $mockDatabase;
                
                if (strpos($query, 'email_queue') !== false) {
                    if (strpos($query, 'ORDER BY priority DESC') !== false) {
                        // Sort by priority for queue processing
                        $results = $mockDatabase['email_queue'];
                        usort($results, function($a, $b) {
                            return $b['priority'] <=> $a['priority'];
                        });
                        return array_slice($results, 0, 10); // Limit for testing
                    }
                    return $mockDatabase['email_queue'];
                }
                
                return [];
            }
            
            public function get_var($query) {
                global $mockDatabase;
                
                if (strpos($query, 'COUNT') !== false && strpos($query, 'email_queue') !== false) {
                    if (strpos($query, "status = 'pending'") !== false) {
                        return count(array_filter($mockDatabase['email_queue'], function($e) {
                            return $e['status'] === 'pending';
                        }));
                    }
                    if (strpos($query, "status = 'sent'") !== false) {
                        return count(array_filter($mockDatabase['email_queue'], function($e) {
                            return $e['status'] === 'sent';
                        }));
                    }
                    if (strpos($query, "status = 'failed'") !== false) {
                        return count(array_filter($mockDatabase['email_queue'], function($e) {
                            return $e['status'] === 'failed';
                        }));
                    }
                    return count($mockDatabase['email_queue']);
                }
                
                return 0;
            }
            
            public function delete($table, $where) {
                global $mockDatabase;
                
                if (strpos($table, 'email_queue') !== false) {
                    $mockDatabase['email_queue'] = array_filter($mockDatabase['email_queue'], function($email) use ($where) {
                        foreach ($where as $key => $value) {
                            if ($email[$key] != $value) {
                                return true;
                            }
                        }
                        return false;
                    });
                }
                
                return true;
            }
            
            public function prepare($query, ...$args) {
                return $query;
            }
        };
        
        // Make mockDatabase global accessible
        global $mockDatabase;
        $mockDatabase = &$this->mockDatabase;
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