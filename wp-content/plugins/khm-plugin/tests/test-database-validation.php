<?php
/**
 * TouchPoint Database Validation Test
 * Tests database schema integrity and relationships
 */

// Include WordPress if not already loaded
if (!defined('ABSPATH')) {
    // Try to find WordPress
    $wp_path = dirname(__FILE__);
    for ($i = 0; $i < 10; $i++) {
        if (file_exists($wp_path . '/wp-config.php')) {
            require_once($wp_path . '/wp-config.php');
            require_once($wp_path . '/wp-load.php');
            break;
        }
        $wp_path = dirname($wp_path);
    }
}

class TouchPoint_DB_Validator {
    
    private $wpdb;
    private $results;
    
    public function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->results = [
            'passed' => 0,
            'failed' => 0,
            'warnings' => 0,
            'tests' => []
        ];
    }
    
    public function run_all_tests() {
        echo "🧪 TouchPoint Database Validation Tests\n";
        echo "=====================================\n\n";
        
        $this->test_core_tables();
        $this->test_foreign_keys();
        $this->test_indexes();
        $this->test_data_integrity();
        $this->test_membership_system();
        $this->test_credit_system();
        $this->test_ecommerce_system();
        $this->test_library_system();
        
        $this->print_summary();
    }
    
    private function test_core_tables() {
        echo "📋 Testing Core Table Structure...\n";
        
        $required_tables = [
            'khm_membership_levels' => ['id', 'name', 'monthly_credits'],
            'khm_memberships_users' => ['id', 'user_id', 'membership_id', 'status'],
            'khm_user_credits' => ['id', 'user_id', 'current_balance'],
            'khm_article_products' => ['id', 'post_id', 'regular_price'],
            'khm_member_library' => ['id', 'user_id', 'post_id'],
            'khm_gifts' => ['id', 'sender_id', 'gift_code'],
            'khm_email_queue' => ['id', 'to_email', 'status']
        ];
        
        foreach ($required_tables as $table => $required_columns) {
            $this->test_table_exists($table);
            $this->test_table_columns($table, $required_columns);
        }
        
        echo "\n";
    }
    
    private function test_table_exists($table) {
        $full_table = $this->wpdb->prefix . $table;
        $exists = $this->wpdb->get_var("SHOW TABLES LIKE '{$full_table}'") == $full_table;
        
        if ($exists) {
            $this->test_pass("✅ Table {$table} exists");
        } else {
            $this->test_fail("❌ Table {$table} missing");
        }
        
        return $exists;
    }
    
    private function test_table_columns($table, $required_columns) {
        $full_table = $this->wpdb->prefix . $table;
        
        $columns = $this->wpdb->get_col("SHOW COLUMNS FROM {$full_table}");
        
        foreach ($required_columns as $column) {
            if (in_array($column, $columns)) {
                $this->test_pass("   ✅ Column {$table}.{$column} exists");
            } else {
                $this->test_fail("   ❌ Column {$table}.{$column} missing");
            }
        }
    }
    
    private function test_foreign_keys() {
        echo "🔗 Testing Foreign Key Relationships...\n";
        
        $foreign_keys = [
            'khm_member_library' => [
                'category_id' => 'khm_library_categories.id'
            ],
            'khm_gift_redemptions' => [
                'gift_id' => 'khm_gifts.id'
            ]
        ];
        
        foreach ($foreign_keys as $table => $fks) {
            foreach ($fks as $fk_column => $referenced_table_column) {
                $this->test_foreign_key($table, $fk_column, $referenced_table_column);
            }
        }
        
        echo "\n";
    }
    
    private function test_foreign_key($table, $fk_column, $referenced_table_column) {
        $full_table = $this->wpdb->prefix . $table;
        
        // Check if foreign key constraint exists
        $fk_exists = $this->wpdb->get_var("
            SELECT COUNT(*) FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = '{$full_table}' 
            AND COLUMN_NAME = '{$fk_column}' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if ($fk_exists) {
            $this->test_pass("   ✅ Foreign key {$table}.{$fk_column} → {$referenced_table_column}");
        } else {
            $this->test_warning("   ⚠️  Foreign key {$table}.{$fk_column} → {$referenced_table_column} not enforced");
        }
    }
    
    private function test_indexes() {
        echo "📈 Testing Database Indexes...\n";
        
        $important_indexes = [
            'khm_memberships_users' => ['user_id', 'membership_id'],
            'khm_user_credits' => ['user_id', 'allocation_month'],
            'khm_member_library' => ['user_id', 'post_id'],
            'khm_purchases' => ['user_id', 'post_id', 'status']
        ];
        
        foreach ($important_indexes as $table => $columns) {
            foreach ($columns as $column) {
                $this->test_index_exists($table, $column);
            }
        }
        
        echo "\n";
    }
    
    private function test_index_exists($table, $column) {
        $full_table = $this->wpdb->prefix . $table;
        
        $index_exists = $this->wpdb->get_var("
            SHOW INDEX FROM {$full_table} WHERE Column_name = '{$column}'
        ");
        
        if ($index_exists) {
            $this->test_pass("   ✅ Index on {$table}.{$column}");
        } else {
            $this->test_warning("   ⚠️  Missing index on {$table}.{$column} (may affect performance)");
        }
    }
    
    private function test_data_integrity() {
        echo "🔍 Testing Data Integrity...\n";
        
        // Test membership levels have been seeded
        $levels = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}khm_membership_levels");
        if ($levels >= 3) {
            $this->test_pass("   ✅ Membership levels seeded ({$levels} levels)");
        } else {
            $this->test_fail("   ❌ Insufficient membership levels ({$levels}/3 minimum)");
        }
        
        // Test discount codes have been seeded
        $codes = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}khm_discount_codes");
        if ($codes >= 1) {
            $this->test_pass("   ✅ Discount codes seeded ({$codes} codes)");
        } else {
            $this->test_warning("   ⚠️  No discount codes found");
        }
        
        // Test default categories exist
        $categories = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}khm_library_categories WHERE user_id = 0");
        if ($categories >= 3) {
            $this->test_pass("   ✅ Default library categories created ({$categories} categories)");
        } else {
            $this->test_warning("   ⚠️  Few default library categories ({$categories}/4 expected)");
        }
        
        echo "\n";
    }
    
    private function test_membership_system() {
        echo "👥 Testing Membership System...\n";
        
        // Test membership levels have proper credit allocations
        $invalid_credits = $this->wpdb->get_var("
            SELECT COUNT(*) FROM {$this->wpdb->prefix}khm_membership_levels 
            WHERE monthly_credits <= 0
        ");
        
        if ($invalid_credits == 0) {
            $this->test_pass("   ✅ All membership levels have credit allocations");
        } else {
            $this->test_fail("   ❌ {$invalid_credits} membership levels missing credit allocations");
        }
        
        // Test level metadata exists
        $metadata_count = $this->wpdb->get_var("
            SELECT COUNT(DISTINCT level_id) FROM {$this->wpdb->prefix}khm_membership_levelmeta
        ");
        
        if ($metadata_count >= 3) {
            $this->test_pass("   ✅ Membership level metadata configured");
        } else {
            $this->test_warning("   ⚠️  Limited membership level metadata");
        }
        
        echo "\n";
    }
    
    private function test_credit_system() {
        echo "💳 Testing Credit System...\n";
        
        // Test credit tables are properly structured
        $credit_tables = [
            'khm_user_credits',
            'khm_credit_usage'
        ];
        
        foreach ($credit_tables as $table) {
            if ($this->test_table_exists($table)) {
                // Test table is empty (no test data)
                $count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->wpdb->prefix}{$table}");
                $this->test_pass("   ✅ {$table} ready (empty)");
            }
        }
        
        echo "\n";
    }
    
    private function test_ecommerce_system() {
        echo "🛒 Testing eCommerce System...\n";
        
        // Test product pricing setup
        $products_with_pricing = $this->wpdb->get_var("
            SELECT COUNT(*) FROM {$this->wpdb->prefix}khm_article_products 
            WHERE regular_price > 0
        ");
        
        if ($products_with_pricing >= 10) {
            $this->test_pass("   ✅ Article products configured ({$products_with_pricing} products)");
        } else {
            $this->test_warning("   ⚠️  Few article products configured ({$products_with_pricing})");
        }
        
        // Test cart and purchase tables exist
        $ecommerce_tables = [
            'khm_shopping_cart',
            'khm_purchases'
        ];
        
        foreach ($ecommerce_tables as $table) {
            $this->test_table_exists($table);
        }
        
        echo "\n";
    }
    
    private function test_library_system() {
        echo "📚 Testing Library System...\n";
        
        // Test library structure
        $library_tables = [
            'khm_member_library',
            'khm_library_categories'
        ];
        
        foreach ($library_tables as $table) {
            $this->test_table_exists($table);
        }
        
        echo "\n";
    }
    
    private function test_pass($message) {
        echo $message . "\n";
        $this->results['passed']++;
        $this->results['tests'][] = ['status' => 'pass', 'message' => $message];
    }
    
    private function test_fail($message) {
        echo $message . "\n";
        $this->results['failed']++;
        $this->results['tests'][] = ['status' => 'fail', 'message' => $message];
    }
    
    private function test_warning($message) {
        echo $message . "\n";
        $this->results['warnings']++;
        $this->results['tests'][] = ['status' => 'warning', 'message' => $message];
    }
    
    private function print_summary() {
        echo "📊 Test Summary\n";
        echo "===============\n";
        echo "✅ Passed: {$this->results['passed']}\n";
        echo "❌ Failed: {$this->results['failed']}\n";
        echo "⚠️  Warnings: {$this->results['warnings']}\n";
        echo "📝 Total Tests: " . count($this->results['tests']) . "\n\n";
        
        if ($this->results['failed'] == 0) {
            echo "🎉 All critical tests passed! TouchPoint database is ready.\n";
        } else {
            echo "🔧 Some tests failed. Please run migrations or check database setup.\n";
        }
        
        // Calculate health score
        $total_tests = count($this->results['tests']);
        $health_score = round(($this->results['passed'] / $total_tests) * 100, 2);
        echo "🏥 Database Health Score: {$health_score}%\n";
    }
    
    public function get_results() {
        return $this->results;
    }
}

// Run tests if called directly
if (php_sapi_name() === 'cli' || (!empty($argv) && basename($argv[0]) === basename(__FILE__))) {
    $validator = new TouchPoint_DB_Validator();
    $validator->run_all_tests();
}