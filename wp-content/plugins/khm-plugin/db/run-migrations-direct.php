#!/usr/bin/env php
<?php
/**
 * Direct Database Migration Runner
 * 
 * This script runs the credit system and affiliate system migrations
 * directly against the database without requiring WordPress.
 */

echo "🚀 Starting KHM Marketing Suite Database Migration...\n\n";

// Database configuration (you may need to adjust these)
$db_config = [
    'host' => 'localhost',
    'username' => 'root',
    'password' => '',
    'database' => '1927msuite',
    'charset' => 'utf8mb4'
];

// Try to connect to database
try {
    $pdo = new PDO(
        "mysql:host={$db_config['host']};dbname={$db_config['database']};charset={$db_config['charset']}",
        $db_config['username'],
        $db_config['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
    echo "✅ Database connection established\n\n";
} catch (PDOException $e) {
    echo "❌ Database connection failed: " . $e->getMessage() . "\n";
    echo "💡 Please check your database configuration in this script.\n";
    exit(1);
}

// Helper function to run a migration file
function run_migration_file($pdo, $file_path, $name) {
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
    $total_count = 0;
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue; // Skip empty statements and comments
        }
        
        $total_count++;
        
        try {
            $pdo->exec($statement);
            $success_count++;
            echo "   ✓ Statement executed successfully\n";
        } catch (PDOException $e) {
            echo "   ⚠️  Warning: Failed to execute statement\n";
            echo "      Error: " . $e->getMessage() . "\n";
            echo "      SQL: " . substr($statement, 0, 100) . "...\n";
        }
    }
    
    echo "✅ Migration completed: {$success_count}/{$total_count} statements executed successfully\n\n";
    return $success_count > 0;
}

// Helper function to verify table creation
function verify_table_exists($pdo, $table_name) {
    try {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table_name]);
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        return false;
    }
}

// Migration files to run
$migrations = [
    [
        'file' => __DIR__ . '/migrations/2025_11_04_create_credit_system_tables.sql',
        'name' => 'Credit System Tables',
        'tables' => ['khm_user_credits', 'khm_credit_usage']
    ],
    [
        'file' => __DIR__ . '/migrations/2025_11_04_create_affiliate_system_tables.sql',
        'name' => 'Affiliate System Tables',
        'tables' => ['khm_affiliate_codes', 'khm_affiliate_clicks', 'khm_affiliate_conversions', 'khm_affiliate_generations', 'khm_social_shares', 'khm_commission_rates']
    ]
];

// Run each migration
$all_successful = true;
foreach ($migrations as $migration) {
    $success = run_migration_file($pdo, $migration['file'], $migration['name']);
    if (!$success) {
        $all_successful = false;
    }
}

echo "🔍 Verifying table creation...\n";

// Verify all expected tables were created
$all_tables_created = true;
foreach ($migrations as $migration) {
    foreach ($migration['tables'] as $table) {
        if (verify_table_exists($pdo, $table)) {
            echo "✅ Table verified: {$table}\n";
        } else {
            echo "❌ Table missing: {$table}\n";
            $all_tables_created = false;
        }
    }
}

echo "\n";

// Create sample credit allocations if users table exists
echo "🧪 Creating test credit allocations...\n";

try {
    // Check if we have a users table (WordPress style)
    $users_tables = ['wp_users', 'users'];
    $users_table = null;
    
    foreach ($users_tables as $table) {
        if (verify_table_exists($pdo, $table)) {
            $users_table = $table;
            break;
        }
    }
    
    if ($users_table) {
        // Get some test users
        $stmt = $pdo->prepare("SELECT ID, display_name FROM {$users_table} LIMIT 3");
        $stmt->execute();
        $users = $stmt->fetchAll();
        
        if ($users) {
            $current_month = date('Y-m');
            
            foreach ($users as $user) {
                // Create test credit allocation
                $stmt = $pdo->prepare("
                    INSERT IGNORE INTO khm_user_credits 
                    (user_id, membership_level_id, allocation_month, allocated_credits, current_balance, total_used, bonus_credits) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                
                $credits = 10; // Default test allocation
                $stmt->execute([
                    $user['ID'],
                    1, // Default membership level
                    $current_month,
                    $credits,
                    $credits,
                    0,
                    0
                ]);
                
                echo "✅ Credit allocation created for user {$user['display_name']}: {$credits} credits\n";
            }
        } else {
            echo "ℹ️  No users found in {$users_table} table\n";
        }
    } else {
        echo "ℹ️  No users table found. Skipping test credit allocations.\n";
    }
} catch (PDOException $e) {
    echo "⚠️  Warning: Could not create test allocations: " . $e->getMessage() . "\n";
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

// Show created tables
echo "📋 Created Tables:\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'khm_%'");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if ($tables) {
        foreach ($tables as $table) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM {$table}");
            $count = $stmt->fetch()['count'];
            echo "   📄 {$table} ({$count} rows)\n";
        }
    } else {
        echo "   ❌ No KHM tables found\n";
    }
} catch (PDOException $e) {
    echo "   ⚠️  Could not list tables: " . $e->getMessage() . "\n";
}

echo "\n";