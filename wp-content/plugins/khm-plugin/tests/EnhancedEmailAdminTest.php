<?php
/**
 * Enhanced Email Admin Interface Tests
 *
 * Tests admin interface functionality, settings management,
 * test email sending, and dashboard widgets
 *
 * @package KHM\Tests
 */

namespace KHM\Tests;

use PHPUnit\Framework\TestCase;
use KHM\Admin\EnhancedEmailAdmin;
use KHM\Services\EnhancedEmailService;

class EnhancedEmailAdminTest extends TestCase {

    private EnhancedEmailAdmin $emailAdmin;
    private EnhancedEmailService $emailService;
    private string $testPluginDir;
    private array $mockOptions = [];

    protected function setUp(): void {
        parent::setUp();
        
        $this->testPluginDir = sys_get_temp_dir() . '/khm-email-admin-test-' . uniqid();
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
     * Test admin initialization
     */
    public function testAdminInitialization(): void {
        $this->assertInstanceOf(EnhancedEmailAdmin::class, $this->emailAdmin);
        
        // Test hook registration
        global $mock_wp_hooks;
        $this->emailAdmin->init();
        
        $this->assertArrayHasKey('admin_menu', $mock_wp_hooks);
        $this->assertArrayHasKey('admin_init', $mock_wp_hooks);
        $this->assertArrayHasKey('wp_ajax_khm_test_email', $mock_wp_hooks);
        $this->assertArrayHasKey('wp_ajax_khm_process_queue', $mock_wp_hooks);
    }

    /**
     * Test admin menu registration
     */
    public function testAdminMenuRegistration(): void {
        global $mock_admin_pages;
        $mock_admin_pages = [];
        
        $this->emailAdmin->add_admin_menu();
        
        $this->assertNotEmpty($mock_admin_pages);
        $this->assertArrayHasKey('khm-email-settings', $mock_admin_pages);
        
        $page = $mock_admin_pages['khm-email-settings'];
        $this->assertEquals('Enhanced Email Settings', $page['page_title']);
        $this->assertEquals('manage_options', $page['capability']);
    }

    /**
     * Test settings registration
     */
    public function testSettingsRegistration(): void {
        global $mock_settings, $mock_settings_sections, $mock_settings_fields;
        $mock_settings = [];
        $mock_settings_sections = [];
        $mock_settings_fields = [];
        
        $this->emailAdmin->register_settings();
        
        // Check main settings groups
        $this->assertContains('khm_email_delivery', $mock_settings);
        $this->assertContains('khm_email_smtp', $mock_settings);
        $this->assertContains('khm_email_api', $mock_settings);
        
        // Check settings sections
        $this->assertNotEmpty($mock_settings_sections);
        $this->assertNotEmpty($mock_settings_fields);
    }

    /**
     * Test admin page rendering
     */
    public function testAdminPageRendering(): void {
        ob_start();
        $this->emailAdmin->render_admin_page();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Enhanced Email Settings', $output);
        $this->assertStringContainsString('id="khm-email-settings"', $output);
        
        // Check for main sections
        $this->assertStringContainsString('Delivery Settings', $output);
        $this->assertStringContainsString('SMTP Configuration', $output);
        $this->assertStringContainsString('API Configuration', $output);
        $this->assertStringContainsString('Queue Management', $output);
    }

    /**
     * Test delivery settings section
     */
    public function testDeliverySettingsSection(): void {
        ob_start();
        $this->emailAdmin->render_delivery_settings_section();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Enhanced Delivery', $output);
        $this->assertStringContainsString('Delivery Method', $output);
        
        // Check for delivery method options
        $this->assertStringContainsString('WordPress', $output);
        $this->assertStringContainsString('SMTP', $output);
        $this->assertStringContainsString('API', $output);
    }

    /**
     * Test SMTP settings section
     */
    public function testSMTPSettingsSection(): void {
        ob_start();
        $this->emailAdmin->render_smtp_settings_section();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        
        // Check for SMTP fields
        $this->assertStringContainsString('SMTP Host', $output);
        $this->assertStringContainsString('SMTP Port', $output);
        $this->assertStringContainsString('Encryption', $output);
        $this->assertStringContainsString('Username', $output);
        $this->assertStringContainsString('Password', $output);
    }

    /**
     * Test API settings section
     */
    public function testAPISettingsSection(): void {
        ob_start();
        $this->emailAdmin->render_api_settings_section();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        
        // Check for API fields
        $this->assertStringContainsString('API Provider', $output);
        $this->assertStringContainsString('API Key', $output);
        $this->assertStringContainsString('SendGrid', $output);
        $this->assertStringContainsString('Mailgun', $output);
    }

    /**
     * Test queue management section
     */
    public function testQueueManagementSection(): void {
        ob_start();
        $this->emailAdmin->render_queue_settings_section();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        
        // Check for queue settings
        $this->assertStringContainsString('Use Queue', $output);
        $this->assertStringContainsString('Batch Size', $output);
        $this->assertStringContainsString('Process Now', $output);
    }

    /**
     * Test email statistics display
     */
    public function testEmailStatisticsDisplay(): void {
        // Mock some statistics data
        global $mock_wpdb_statistics;
        $mock_wpdb_statistics = [
            'sent_today' => 25,
            'sent_week' => 150,
            'sent_month' => 600,
            'failed_today' => 2,
            'queue_pending' => 5
        ];
        
        ob_start();
        $this->emailAdmin->render_statistics_section();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('25', $output); // Sent today
        $this->assertStringContainsString('150', $output); // Sent this week
        $this->assertStringContainsString('5', $output); // Queue pending
    }

    /**
     * Test test email functionality
     */
    public function testTestEmailFunctionality(): void {
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        // Mock POST data for test email
        $_POST['test_email'] = 'test@example.com';
        $_POST['test_subject'] = 'Test Email Subject';
        $_POST['khm_email_test_nonce'] = 'valid_nonce';
        $_POST['action'] = 'send_test_email';
        
        // Mock nonce verification
        global $mock_wp_verify_nonce;
        $mock_wp_verify_nonce = true;
        
        ob_start();
        $result = $this->emailAdmin->handle_test_email();
        $output = ob_get_clean();
        
        $this->assertTrue($result);
        $this->assertNotEmpty($mock_wp_mail_calls);
        
        $testEmail = $mock_wp_mail_calls[0];
        $this->assertEquals('test@example.com', $testEmail['to']);
        $this->assertEquals('Test Email Subject', $testEmail['subject']);
    }

    /**
     * Test AJAX test email handler
     */
    public function testAjaxTestEmailHandler(): void {
        global $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        // Mock AJAX request
        $_POST['email'] = 'ajax@example.com';
        $_POST['delivery_method'] = 'smtp';
        $_POST['nonce'] = 'valid_nonce';
        
        global $mock_wp_verify_nonce;
        $mock_wp_verify_nonce = true;
        
        ob_start();
        $this->emailAdmin->ajax_send_test_email();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertTrue($response['success']);
        $this->assertNotEmpty($mock_wp_mail_calls);
    }

    /**
     * Test queue processing AJAX handler
     */
    public function testAjaxQueueProcessing(): void {
        global $mock_wp_verify_nonce;
        $mock_wp_verify_nonce = true;
        
        $_POST['nonce'] = 'valid_nonce';
        
        ob_start();
        $this->emailAdmin->ajax_process_queue();
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertTrue($response['success']);
        $this->assertArrayHasKey('processed', $response);
    }

    /**
     * Test settings validation
     */
    public function testSettingsValidation(): void {
        // Test SMTP settings validation
        $smtpSettings = [
            'khm_smtp_host' => 'smtp.gmail.com',
            'khm_smtp_port' => '587',
            'khm_smtp_encryption' => 'tls',
            'khm_smtp_username' => 'test@gmail.com',
            'khm_smtp_password' => 'password123'
        ];
        
        $validated = $this->emailAdmin->validate_smtp_settings($smtpSettings);
        
        $this->assertEquals('smtp.gmail.com', $validated['khm_smtp_host']);
        $this->assertEquals(587, $validated['khm_smtp_port']); // Should be converted to int
        $this->assertEquals('tls', $validated['khm_smtp_encryption']);
        
        // Test API settings validation
        $apiSettings = [
            'khm_email_api_provider' => 'sendgrid',
            'khm_email_api_key' => 'SG.test_key',
            'khm_email_api_domain' => 'mg.example.com'
        ];
        
        $validated = $this->emailAdmin->validate_api_settings($apiSettings);
        
        $this->assertEquals('sendgrid', $validated['khm_email_api_provider']);
        $this->assertEquals('SG.test_key', $validated['khm_email_api_key']);
    }

    /**
     * Test settings sanitization
     */
    public function testSettingsSanitization(): void {
        // Test input sanitization
        $dirtyInput = [
            'khm_smtp_host' => '<script>alert("xss")</script>smtp.gmail.com',
            'khm_smtp_username' => 'test@gmail.com<script>',
            'khm_email_from_name' => 'Test Site"<script>alert(1)</script>'
        ];
        
        $sanitized = $this->emailAdmin->sanitize_settings($dirtyInput);
        
        $this->assertStringNotContainsString('<script>', $sanitized['khm_smtp_host']);
        $this->assertStringNotContainsString('<script>', $sanitized['khm_smtp_username']);
        $this->assertStringNotContainsString('<script>', $sanitized['khm_email_from_name']);
    }

    /**
     * Test dashboard widget
     */
    public function testDashboardWidget(): void {
        global $mock_dashboard_widgets;
        $mock_dashboard_widgets = [];
        
        $this->emailAdmin->add_dashboard_widget();
        
        $this->assertNotEmpty($mock_dashboard_widgets);
        $this->assertArrayHasKey('khm_email_stats_widget', $mock_dashboard_widgets);
        
        $widget = $mock_dashboard_widgets['khm_email_stats_widget'];
        $this->assertEquals('Email Statistics', $widget['title']);
    }

    /**
     * Test dashboard widget content
     */
    public function testDashboardWidgetContent(): void {
        global $mock_wpdb_statistics;
        $mock_wpdb_statistics = [
            'sent_today' => 15,
            'failed_today' => 1,
            'queue_pending' => 3
        ];
        
        ob_start();
        $this->emailAdmin->render_dashboard_widget();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('15', $output); // Sent today
        $this->assertStringContainsString('3', $output); // Queue pending
    }

    /**
     * Test capability checks
     */
    public function testCapabilityChecks(): void {
        global $mock_current_user_can;
        
        // Test with insufficient capabilities
        $mock_current_user_can = false;
        
        ob_start();
        $this->emailAdmin->render_admin_page();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('You do not have sufficient permissions', $output);
        
        // Test with sufficient capabilities
        $mock_current_user_can = true;
        
        ob_start();
        $this->emailAdmin->render_admin_page();
        $output = ob_get_clean();
        
        $this->assertStringNotContainsString('You do not have sufficient permissions', $output);
    }

    /**
     * Test nonce verification
     */
    public function testNonceVerification(): void {
        global $mock_wp_verify_nonce, $mock_wp_mail_calls;
        $mock_wp_mail_calls = [];
        
        // Test with invalid nonce
        $mock_wp_verify_nonce = false;
        $_POST['test_email'] = 'test@example.com';
        $_POST['khm_email_test_nonce'] = 'invalid_nonce';
        
        $result = $this->emailAdmin->handle_test_email();
        
        $this->assertFalse($result);
        $this->assertEmpty($mock_wp_mail_calls);
        
        // Test with valid nonce
        $mock_wp_verify_nonce = true;
        $_POST['khm_email_test_nonce'] = 'valid_nonce';
        
        $result = $this->emailAdmin->handle_test_email();
        
        $this->assertTrue($result);
        $this->assertNotEmpty($mock_wp_mail_calls);
    }

    /**
     * Test admin notices
     */
    public function testAdminNotices(): void {
        // Test success notice
        $this->emailAdmin->add_admin_notice('Test email sent successfully!', 'success');
        
        ob_start();
        $this->emailAdmin->display_admin_notices();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('notice-success', $output);
        $this->assertStringContainsString('Test email sent successfully!', $output);
        
        // Test error notice
        $this->emailAdmin->add_admin_notice('Email sending failed!', 'error');
        
        ob_start();
        $this->emailAdmin->display_admin_notices();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('notice-error', $output);
        $this->assertStringContainsString('Email sending failed!', $output);
    }

    /**
     * Test configuration export/import
     */
    public function testConfigurationExportImport(): void {
        // Set some test configuration
        $this->mockOptions = [
            'khm_email_delivery_method' => 'smtp',
            'khm_smtp_host' => 'smtp.gmail.com',
            'khm_smtp_port' => 587,
            'khm_email_api_provider' => 'sendgrid'
        ];
        
        // Test export
        $exported = $this->emailAdmin->export_configuration();
        
        $this->assertNotEmpty($exported);
        $this->assertStringContainsString('smtp.gmail.com', $exported);
        
        // Test import
        $result = $this->emailAdmin->import_configuration($exported);
        
        $this->assertTrue($result);
    }

    /**
     * Test admin asset enqueuing
     */
    public function testAdminAssetEnqueuing(): void {
        global $mock_enqueued_scripts, $mock_enqueued_styles;
        $mock_enqueued_scripts = [];
        $mock_enqueued_styles = [];
        
        $this->emailAdmin->enqueue_admin_assets();
        
        $this->assertContains('khm-email-admin', $mock_enqueued_scripts);
        $this->assertContains('khm-email-admin', $mock_enqueued_styles);
    }

    /**
     * Test email template management
     */
    public function testEmailTemplateManagement(): void {
        ob_start();
        $this->emailAdmin->render_template_management_section();
        $output = ob_get_clean();
        
        $this->assertNotEmpty($output);
        $this->assertStringContainsString('Email Templates', $output);
        $this->assertStringContainsString('test_template', $output);
    }

    /**
     * Helper methods
     */
    private function createTestTemplates(): void {
        $testTemplate = '<!DOCTYPE html>
<html>
<body>
    <h1>Hello !!user_name!!</h1>
    <p>This is a test email template.</p>
</body>
</html>';
        
        file_put_contents($this->testPluginDir . '/email/test_template.html', $testTemplate);
    }

    private function mockWordPressFunctions(): void {
        global $mock_wp_hooks, $mock_admin_pages, $mock_settings, $mock_settings_sections;
        global $mock_settings_fields, $mock_wp_mail_calls, $mock_current_user_can;
        global $mock_wp_verify_nonce, $mock_dashboard_widgets, $mock_enqueued_scripts;
        global $mock_enqueued_styles, $mock_wpdb_statistics;
        
        $mock_wp_hooks = [];
        $mock_admin_pages = [];
        $mock_settings = [];
        $mock_settings_sections = [];
        $mock_settings_fields = [];
        $mock_wp_mail_calls = [];
        $mock_current_user_can = true;
        $mock_wp_verify_nonce = true;
        $mock_dashboard_widgets = [];
        $mock_enqueued_scripts = [];
        $mock_enqueued_styles = [];
        $mock_wpdb_statistics = [];
        
        // Mock WordPress admin functions
        if (!function_exists('add_action')) {
            function add_action($hook, $callback, $priority = 10) {
                global $mock_wp_hooks;
                $mock_wp_hooks[$hook] = $callback;
            }
        }
        
        if (!function_exists('add_submenu_page')) {
            function add_submenu_page($parent_slug, $page_title, $menu_title, $capability, $menu_slug, $callback) {
                global $mock_admin_pages;
                $mock_admin_pages[$menu_slug] = [
                    'parent_slug' => $parent_slug,
                    'page_title' => $page_title,
                    'menu_title' => $menu_title,
                    'capability' => $capability,
                    'callback' => $callback
                ];
            }
        }
        
        if (!function_exists('register_setting')) {
            function register_setting($option_group, $option_name, $args = []) {
                global $mock_settings;
                $mock_settings[] = $option_name;
            }
        }
        
        if (!function_exists('add_settings_section')) {
            function add_settings_section($id, $title, $callback, $page) {
                global $mock_settings_sections;
                $mock_settings_sections[] = ['id' => $id, 'title' => $title, 'page' => $page];
            }
        }
        
        if (!function_exists('add_settings_field')) {
            function add_settings_field($id, $title, $callback, $page, $section) {
                global $mock_settings_fields;
                $mock_settings_fields[] = ['id' => $id, 'title' => $title, 'page' => $page, 'section' => $section];
            }
        }
        
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
                return 'test_nonce_' . $action;
            }
        }
        
        if (!function_exists('wp_add_dashboard_widget')) {
            function wp_add_dashboard_widget($widget_id, $widget_name, $callback) {
                global $mock_dashboard_widgets;
                $mock_dashboard_widgets[$widget_id] = [
                    'title' => $widget_name,
                    'callback' => $callback
                ];
            }
        }
        
        if (!function_exists('wp_enqueue_script')) {
            function wp_enqueue_script($handle, $src = '', $deps = [], $ver = false, $in_footer = false) {
                global $mock_enqueued_scripts;
                $mock_enqueued_scripts[] = $handle;
            }
        }
        
        if (!function_exists('wp_enqueue_style')) {
            function wp_enqueue_style($handle, $src = '', $deps = [], $ver = false, $media = 'all') {
                global $mock_enqueued_styles;
                $mock_enqueued_styles[] = $handle;
            }
        }
        
        if (!function_exists('wp_die')) {
            function wp_die($message) {
                echo json_encode(['success' => false, 'message' => $message]);
                exit;
            }
        }
        
        if (!function_exists('wp_send_json_success')) {
            function wp_send_json_success($data = null) {
                echo json_encode(['success' => true, 'data' => $data]);
                exit;
            }
        }
        
        if (!function_exists('wp_send_json_error')) {
            function wp_send_json_error($data = null) {
                echo json_encode(['success' => false, 'data' => $data]);
                exit;
            }
        }
        
        // Mock other WordPress functions
        if (!function_exists('get_option')) {
            function get_option($option, $default = null) {
                global $mockOptions;
                return $mockOptions[$option] ?? $default;
            }
        }
        
        if (!function_exists('update_option')) {
            function update_option($option, $value) {
                global $mockOptions;
                $mockOptions[$option] = $value;
                return true;
            }
        }
        
        if (!function_exists('sanitize_text_field')) {
            function sanitize_text_field($str) {
                return strip_tags($str);
            }
        }
        
        if (!function_exists('sanitize_email')) {
            function sanitize_email($email) {
                return filter_var($email, FILTER_SANITIZE_EMAIL);
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
        
        // Reference global mockOptions
        global $mockOptions;
        $mockOptions = &$this->mockOptions;
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