#!/usr/bin/env php
<?php
/**
 * KHM Marketing Suite Database Migration Runner
 * 
 * This script runs the credit system and affiliate system migrations
 * to set up all necessary database tables.
 */

// Load WordPress environment
define('WP_USE_THEMES', false);
require_once(__DIR__ . '/../../../../wp-load.php');

if (!defined('ABSPATH')) {
    die('WordPress environment not loaded properly.');
}

echo "🚀 Starting KHM Marketing Suite Database Migration...\n\n";

// Get WordPress database connection
global $wpdb;

// Helper function to run a migration file
function run_migration_file($file_path, $name) {
    global $wpdb;
    
    echo "📝 Running migration: {$name}\n";
    
    if (!file_exists($file_path)) {
        echo "❌ Error: Migration file not found: {$file_path}\n";
        return false;
    }
    
    $sql = file_get_contents($file_path);
    if (empty($sql)) {
        echo "❌ Error: Migration file is empty: {$file_path}\n";
        return false;
    }
    
    // Split SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    $success_count = 0;
    $total_count = count($statements);
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        $result = $wpdb->query($statement);
        
        if ($result === false) {
            echo "⚠️  Warning: Failed to execute statement: " . substr($statement, 0, 100) . "...\n";
            echo "   Error: " . $wpdb->last_error . "\n";
        } else {
            $success_count++;
        }
    }
    
    echo "✅ Migration completed: {$success_count}/{$total_count} statements executed successfully\n\n";
    return $success_count > 0;
}

// Helper function to verify table creation
function verify_table_exists($table_name) {
    global $wpdb;
    
    $table_exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = %s AND table_name = %s",
        $wpdb->dbname,
        $table_name
    ));
    
    return $table_exists > 0;
}

// Migration files to run
$migrations = [
    [
        'file' => __DIR__ . '/../migrations/2025_11_04_create_credit_system_tables.sql',
        'name' => 'Credit System Tables',
        'tables' => ['khm_user_credits', 'khm_credit_usage']
    ],
    [
        'file' => __DIR__ . '/../migrations/2025_11_04_create_affiliate_system_tables.sql',
        'name' => 'Affiliate System Tables',
        'tables' => ['khm_affiliate_codes', 'khm_affiliate_clicks', 'khm_affiliate_conversions', 'khm_affiliate_generations', 'khm_social_shares', 'khm_commission_rates']
    ]
];

// Run each migration
$all_successful = true;
foreach ($migrations as $migration) {
    $success = run_migration_file($migration['file'], $migration['name']);
    if (!$success) {
        $all_successful = false;
    }
}

echo "🔍 Verifying table creation...\n";

// Verify all expected tables were created
$all_tables_created = true;
foreach ($migrations as $migration) {
    foreach ($migration['tables'] as $table) {
        $full_table_name = $wpdb->prefix . $table;
        if (verify_table_exists($full_table_name)) {
            echo "✅ Table verified: {$full_table_name}\n";
        } else {
            echo "❌ Table missing: {$full_table_name}\n";
            $all_tables_created = false;
        }
    }
}

echo "\n";

// Initialize affiliate service tables (call the static method)
echo "🛠️  Initializing affiliate service tables...\n";
try {
    require_once(__DIR__ . '/../src/Services/AffiliateService.php');
    \KHM\Services\AffiliateService::create_tables();
    echo "✅ Affiliate service tables initialized\n";
} catch (Exception $e) {
    echo "⚠️  Warning: Could not initialize affiliate service tables: " . $e->getMessage() . "\n";
}

// Create test credit allocation for existing members
echo "\n🧪 Creating test credit allocations...\n";

$current_month = date('Y-m');
$test_users = $wpdb->get_results("
    SELECT u.ID, u.display_name, ul.level_id 
    FROM {$wpdb->users} u 
    LEFT JOIN {$wpdb->prefix}khm_user_levels ul ON u.ID = ul.user_id 
    WHERE ul.level_id IS NOT NULL 
    LIMIT 5
");

if (empty($test_users)) {
    echo "ℹ️  No users with membership levels found. Creating sample data...\n";
    
    // Get admin user for testing
    $admin_user = $wpdb->get_row("SELECT ID, display_name FROM {$wpdb->users} WHERE user_login = 'admin' OR ID = 1 LIMIT 1");
    
    if ($admin_user) {
        // Create test credit allocation for admin
        $wpdb->insert(
            $wpdb->prefix . 'khm_user_credits',
            [
                'user_id' => $admin_user->ID,
                'membership_level_id' => 1,
                'allocation_month' => $current_month,
                'allocated_credits' => 10,
                'current_balance' => 10,
                'total_used' => 0,
                'bonus_credits' => 0
            ],
            ['%d', '%d', '%s', '%d', '%d', '%d', '%d']
        );
        
        echo "✅ Test credit allocation created for user: {$admin_user->display_name} (10 credits)\n";
    }
} else {
    foreach ($test_users as $user) {
        // Determine credits based on level (sample logic)
        $credits = match($user->level_id) {
            1 => 5,   // Basic
            2 => 10,  // Premium  
            3 => 20,  // VIP
            default => 5
        };
        
        $wpdb->insert(
            $wpdb->prefix . 'khm_user_credits',
            [
                'user_id' => $user->ID,
                'membership_level_id' => $user->level_id,
                'allocation_month' => $current_month,
                'allocated_credits' => $credits,
                'current_balance' => $credits,
                'total_used' => 0,
                'bonus_credits' => 0
            ],
            ['%d', '%d', '%s', '%d', '%d', '%d', '%d']
        );
        
        echo "✅ Credit allocation created for {$user->display_name}: {$credits} credits\n";
    }
}

// Final status report
echo "\n" . str_repeat('=', 60) . "\n";
echo "📊 MIGRATION SUMMARY\n";
echo str_repeat('=', 60) . "\n";

if ($all_successful && $all_tables_created) {
    echo "🎉 SUCCESS: All migrations completed successfully!\n\n";
    echo "✅ Credit System: Ready for use\n";
    echo "✅ Affiliate System: Ready for tracking\n";
    echo "✅ Social Sharing: Enhanced with affiliate URLs\n";
    echo "✅ Database Tables: All created and verified\n\n";
    echo "🚀 Your KHM Marketing Suite is now fully operational!\n";
} else {
    echo "⚠️  PARTIAL SUCCESS: Some issues were encountered.\n";
    echo "   Please review the output above and fix any errors.\n";
}

echo "\n📋 Next Steps:\n";
echo "   1. Test affiliate URL generation in social sharing modal\n";
echo "   2. Verify credit allocation for members\n";
echo "   3. Test purchase and conversion tracking\n";
echo "   4. Set up admin dashboard for monitoring\n";
echo "\n";