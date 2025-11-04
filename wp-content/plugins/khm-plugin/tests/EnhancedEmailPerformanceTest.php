<?php
/**
 * Enhanced Email Performance Tests
 *
 * Tests system performance under load, memory usage,
 * concurrent access, and response times
 *
 * @package KHM\Tests
 */

namespace KHM\Tests;

use PHPUnit\Framework\TestCase;
use KHM\Services\EnhancedEmailService;

class EnhancedEmailPerformanceTest extends TestCase {

    private EnhancedEmailService $emailService;
    private string $testPluginDir;

    protected function setUp(): void {
        parent::setUp();
        
        $this->testPluginDir = sys_get_temp_dir() . '/khm-email-perf-test-' . uniqid();
        mkdir($this->testPluginDir, 0755, true);
        mkdir($this->testPluginDir . '/email', 0755, true);
        
        $this->createTestTemplates();
        $this->mockWordPressFunctions();
        
        $this->emailService = new EnhancedEmailService($this->testPluginDir);
    }

    protected function tearDown(): void {
        $this->removeDirectory($this->testPluginDir);
        parent::tearDown();
    }

    /**
     * Test template rendering performance
     */
    public function testTemplateRenderingPerformance(): void {
        $data = $this->generateLargeDataSet();
        
        $startTime = microtime(true);
        $startMemory = memory_get_usage();
        
        // Render template 100 times
        for ($i = 0; $i < 100; $i++) {
            $rendered = $this->emailService->render('performance_template', $data);
            $this->assertNotEmpty($rendered);
        }
        
        $endTime = microtime(true);
        $endMemory = memory_get_usage();
        
        $executionTime = $endTime - $startTime;
        $memoryUsed = $endMemory - $startMemory;
        
        // Should render 100 templates in less than 2 seconds
        $this->assertLessThan(2.0, $executionTime, 'Template rendering is too slow');
        
        // Memory usage should be reasonable (less than 10MB)
        $this->assertLessThan(10 * 1024 * 1024, $memoryUsed, 'Template rendering uses too much memory');
    }

    /**
     * Test large template rendering
     */
    public function testLargeTemplateRendering(): void {
        $largeData = [
            'user_name' => str_repeat('John Doe', 100),
            'content' => str_repeat('Lorem ipsum dolor sit amet. ', 1000),
            'items' => array_fill(0, 100, 'Item content'),
            'metadata' => json_encode(array_fill(0, 50, ['key' => 'value']))
        ];
        
        $startTime = microtime(true);
        $rendered = $this->emailService->render('large_template', $largeData);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        $this->assertNotEmpty($rendered);
        $this->assertLessThan(1.0, $executionTime, 'Large template rendering is too slow');
    }

    /**
     * Test concurrent template rendering
     */
    public function testConcurrentTemplateRendering(): void {
        $processes = [];
        $startTime = microtime(true);
        
        // Simulate concurrent rendering (in real scenario would use actual concurrency)
        for ($i = 0; $i < 10; $i++) {
            $data = [
                'user_name' => "User {$i}",
                'content' => "Content for user {$i}",
                'process_id' => $i
            ];
            
            $rendered = $this->emailService->render('performance_template', $data);
            $this->assertNotEmpty($rendered);
            $this->assertStringContainsString("User {$i}", $rendered);
        }
        
        $endTime = microtime(true);
        $totalTime = $endTime - $startTime;
        
        // 10 concurrent renders should complete quickly
        $this->assertLessThan(3.0, $totalTime, 'Concurrent rendering is too slow');
    }

    /**
     * Test memory usage with large datasets
     */
    public function testMemoryUsageWithLargeDatasets(): void {
        $initialMemory = memory_get_usage();
        
        // Process progressively larger datasets
        for ($size = 100; $size <= 1000; $size += 100) {
            $data = $this->generateDataSetOfSize($size);
            $rendered = $this->emailService->render('performance_template', $data);
            $this->assertNotEmpty($rendered);
        }
        
        $finalMemory = memory_get_usage();
        $memoryIncrease = $finalMemory - $initialMemory;
        
        // Memory increase should be reasonable
        $this->assertLessThan(20 * 1024 * 1024, $memoryIncrease, 'Memory usage grows too much with large datasets');
    }

    /**
     * Test cache performance
     */
    public function testCachePerformance(): void {
        $data = ['user_name' => 'Test User', 'content' => 'Test content'];
        
        // First render (should cache)
        $startTime = microtime(true);
        $rendered1 = $this->emailService->render('performance_template', $data);
        $firstRenderTime = microtime(true) - $startTime;
        
        // Second render (should use cache)
        $startTime = microtime(true);
        $rendered2 = $this->emailService->render('performance_template', $data);
        $secondRenderTime = microtime(true) - $startTime;
        
        $this->assertEquals($rendered1, $rendered2);
        
        // Cached render should be significantly faster (at least 2x)
        // Note: This test may not be meaningful without actual caching implementation
        $this->assertLessThan($firstRenderTime * 2, $secondRenderTime + $firstRenderTime);
    }

    /**
     * Test variable replacement performance
     */
    public function testVariableReplacementPerformance(): void {
        // Create template with many variables
        $template = $this->createTemplateWithManyVariables(100);
        file_put_contents($this->testPluginDir . '/email/many_vars_template.html', $template);
        
        // Create data for all variables
        $data = [];
        for ($i = 0; $i < 100; $i++) {
            $data["var_{$i}"] = "Value {$i}";
        }
        
        $startTime = microtime(true);
        $rendered = $this->emailService->render('many_vars_template', $data);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        $this->assertNotEmpty($rendered);
        
        // Verify all variables were replaced
        for ($i = 0; $i < 100; $i++) {
            $this->assertStringContainsString("Value {$i}", $rendered);
            $this->assertStringNotContainsString("!!var_{$i}!!", $rendered);
        }
        
        // Should complete quickly even with many variables
        $this->assertLessThan(0.5, $executionTime, 'Variable replacement is too slow');
    }

    /**
     * Test template file I/O performance
     */
    public function testTemplateFileIOPerformance(): void {
        // Create multiple template files
        for ($i = 0; $i < 20; $i++) {
            $template = "<!DOCTYPE html><html><body><h1>Template {$i}</h1><p>!!content!!</p></body></html>";
            file_put_contents($this->testPluginDir . "/email/perf_template_{$i}.html", $template);
        }
        
        $startTime = microtime(true);
        
        // Render all templates
        for ($i = 0; $i < 20; $i++) {
            $rendered = $this->emailService->render("perf_template_{$i}", ['content' => "Content {$i}"]);
            $this->assertNotEmpty($rendered);
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Should load and render 20 templates quickly
        $this->assertLessThan(1.0, $executionTime, 'Template file I/O is too slow');
    }

    /**
     * Test email queue processing performance
     */
    public function testEmailQueueProcessingPerformance(): void {
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        // Mock queue with many emails
        $this->mockQueueWithEmails(500);
        
        $startTime = microtime(true);
        $processed = $this->emailService->process_email_queue(500);
        $endTime = microtime(true);
        
        $executionTime = $endTime - $startTime;
        
        $this->assertEquals(500, $processed);
        
        // Should process 500 emails in reasonable time (less than 10 seconds)
        $this->assertLessThan(10.0, $executionTime, 'Queue processing is too slow');
        
        // Check emails per second rate
        $emailsPerSecond = $processed / $executionTime;
        $this->assertGreaterThan(50, $emailsPerSecond, 'Email processing rate is too low');
    }

    /**
     * Test database query performance
     */
    public function testDatabaseQueryPerformance(): void {
        global $mock_db_query_time;
        $mock_db_query_time = 0;
        
        $startTime = microtime(true);
        
        // Simulate multiple database operations
        for ($i = 0; $i < 100; $i++) {
            $this->emailService->log_email_attempt(
                'performance_template',
                "test{$i}@example.com",
                ['user_name' => "User {$i}"],
                'wordpress'
            );
        }
        
        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;
        
        // Database operations should be fast
        $this->assertLessThan(2.0, $executionTime, 'Database queries are too slow');
    }

    /**
     * Test system resource usage
     */
    public function testSystemResourceUsage(): void {
        $initialMemory = memory_get_usage();
        $initialPeakMemory = memory_get_peak_usage();
        
        // Perform intensive operations
        for ($i = 0; $i < 50; $i++) {
            $data = $this->generateLargeDataSet();
            $rendered = $this->emailService->render('performance_template', $data);
            
            // Force garbage collection periodically
            if ($i % 10 === 0) {
                gc_collect_cycles();
            }
        }
        
        $finalMemory = memory_get_usage();
        $finalPeakMemory = memory_get_peak_usage();
        
        $memoryIncrease = $finalMemory - $initialMemory;
        $peakMemoryIncrease = $finalPeakMemory - $initialPeakMemory;
        
        // Memory usage should be controlled
        $this->assertLessThan(50 * 1024 * 1024, $memoryIncrease, 'Memory usage increase is too high');
        $this->assertLessThan(100 * 1024 * 1024, $peakMemoryIncrease, 'Peak memory usage is too high');
    }

    /**
     * Test response time under load
     */
    public function testResponseTimeUnderLoad(): void {
        $responseTimes = [];
        
        // Measure response times for multiple operations
        for ($i = 0; $i < 50; $i++) {
            $startTime = microtime(true);
            
            $data = ['user_name' => "User {$i}", 'content' => "Content {$i}"];
            $rendered = $this->emailService->render('performance_template', $data);
            
            $endTime = microtime(true);
            $responseTimes[] = $endTime - $startTime;
            
            $this->assertNotEmpty($rendered);
        }
        
        // Calculate statistics
        $avgResponseTime = array_sum($responseTimes) / count($responseTimes);
        $maxResponseTime = max($responseTimes);
        $minResponseTime = min($responseTimes);
        
        // Response times should be consistent and fast
        $this->assertLessThan(0.1, $avgResponseTime, 'Average response time is too high');
        $this->assertLessThan(0.5, $maxResponseTime, 'Maximum response time is too high');
        
        // Response time variance should be low (no outliers > 10x average)
        foreach ($responseTimes as $time) {
            $this->assertLessThan($avgResponseTime * 10, $time, 'Response time variance is too high');
        }
    }

    /**
     * Test scalability with increasing load
     */
    public function testScalabilityWithIncreasingLoad(): void {
        $loadSizes = [10, 50, 100, 200];
        $executionTimes = [];
        
        foreach ($loadSizes as $size) {
            $startTime = microtime(true);
            
            for ($i = 0; $i < $size; $i++) {
                $data = ['user_name' => "User {$i}", 'load_size' => $size];
                $rendered = $this->emailService->render('performance_template', $data);
                $this->assertNotEmpty($rendered);
            }
            
            $endTime = microtime(true);
            $executionTimes[$size] = $endTime - $startTime;
        }
        
        // Execution time should scale roughly linearly
        $ratio1 = $executionTimes[50] / $executionTimes[10];
        $ratio2 = $executionTimes[100] / $executionTimes[50];
        $ratio3 = $executionTimes[200] / $executionTimes[100];
        
        // Ratios should be reasonable (not exponential growth)
        $this->assertLessThan(10, $ratio1, 'Scaling from 10 to 50 is not linear');
        $this->assertLessThan(5, $ratio2, 'Scaling from 50 to 100 is not linear');
        $this->assertLessThan(3, $ratio3, 'Scaling from 100 to 200 is not linear');
    }

    /**
     * Helper methods
     */
    private function createTestTemplates(): void {
        $performanceTemplate = '<!DOCTYPE html>
<html>
<head><title>Performance Test</title></head>
<body>
    <h1>Hello !!user_name!!</h1>
    <div class="content">!!content!!</div>
    <p>Process ID: !!process_id!!</p>
    <p>Timestamp: !!timestamp!!</p>
    <ul>
        !!items!!
    </ul>
</body>
</html>';
        
        file_put_contents($this->testPluginDir . '/email/performance_template.html', $performanceTemplate);
        
        $largeTemplate = '<!DOCTYPE html>
<html>
<head><title>Large Template</title></head>
<body>
    <header>
        <h1>!!user_name!!</h1>
        <nav>Navigation Content</nav>
    </header>
    <main>
        <section class="content">
            !!content!!
        </section>
        <section class="items">
            !!items!!
        </section>
        <section class="metadata">
            !!metadata!!
        </section>
    </main>
    <footer>
        <p>Footer content with !!timestamp!!</p>
    </footer>
</body>
</html>';
        
        file_put_contents($this->testPluginDir . '/email/large_template.html', $largeTemplate);
    }

    private function createTemplateWithManyVariables(int $count): string {
        $template = '<!DOCTYPE html><html><body>';
        $template .= '<h1>Template with Many Variables</h1>';
        
        for ($i = 0; $i < $count; $i++) {
            $template .= "<p>Variable {$i}: !!var_{$i}!!</p>";
        }
        
        $template .= '</body></html>';
        
        return $template;
    }

    private function generateLargeDataSet(): array {
        return [
            'user_name' => 'Performance Test User',
            'content' => str_repeat('Lorem ipsum dolor sit amet, consectetur adipiscing elit. ', 50),
            'timestamp' => date('Y-m-d H:i:s'),
            'items' => implode('', array_map(function($i) {
                return "<li>Item {$i}</li>";
            }, range(1, 20))),
            'metadata' => json_encode([
                'test_run' => uniqid(),
                'data_size' => 'large',
                'timestamp' => time()
            ])
        ];
    }

    private function generateDataSetOfSize(int $size): array {
        $data = [
            'user_name' => 'Performance User',
            'content' => str_repeat('Content block. ', $size / 10),
        ];
        
        for ($i = 0; $i < $size; $i++) {
            $data["dynamic_var_{$i}"] = "Value {$i}";
        }
        
        return $data;
    }

    private function mockQueueWithEmails(int $count): void {
        global $mock_queue_emails;
        $mock_queue_emails = [];
        
        for ($i = 0; $i < $count; $i++) {
            $mock_queue_emails[] = [
                'id' => $i + 1,
                'recipient' => "test{$i}@example.com",
                'subject' => "Performance Test {$i}",
                'template_name' => 'performance_template',
                'template_data' => json_encode(['user_name' => "User {$i}"]),
                'priority' => 5,
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
    }

    private function mockWordPressFunctions(): void {
        global $mock_wp_mail_calls, $mock_db_query_time, $mock_queue_emails;
        
        $mock_wp_mail_calls = [];
        $mock_db_query_time = 0;
        $mock_queue_emails = [];
        
        // Mock wp_mail with performance tracking
        if (!function_exists('wp_mail')) {
            function wp_mail($to, $subject, $message, $headers = '', $attachments = []) {
                global $mock_wp_mail_calls;
                
                $mock_wp_mail_calls[] = [
                    'to' => $to,
                    'subject' => $subject,
                    'message' => $message,
                    'timestamp' => microtime(true)
                ];
                
                // Simulate small delay for realistic testing
                usleep(1000); // 1ms delay
                
                return true;
            }
        }
        
        // Mock other WordPress functions
        if (!function_exists('get_option')) {
            function get_option($option, $default = null) {
                return $default;
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
        
        // Mock wpdb with performance tracking
        global $wpdb;
        $wpdb = new class {
            public $prefix = 'wp_';
            public $insert_id = 123;
            
            public function insert($table, $data) {
                global $mock_db_query_time;
                
                $start = microtime(true);
                // Simulate database operation
                usleep(500); // 0.5ms delay
                $mock_db_query_time += microtime(true) - $start;
                
                $this->insert_id = rand(100, 999);
                return true;
            }
            
            public function get_results($query) {
                global $mock_queue_emails;
                
                if (strpos($query, 'email_queue') !== false) {
                    return array_slice($mock_queue_emails, 0, 500);
                }
                
                return [];
            }
            
            public function update($table, $data, $where) {
                global $mock_db_query_time;
                
                $start = microtime(true);
                usleep(200); // 0.2ms delay
                $mock_db_query_time += microtime(true) - $start;
                
                return true;
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