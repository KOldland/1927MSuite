#!/usr/bin/env php
<?php
/**
 * Validate Migration
 * 
 * Checks that data successfully migrated from pmpro_* tables to khm_* tables.
 * Reports row counts, sample mismatches, and validation status.
 * 
 * Usage: php bin/validate_migration.php
 */

namespace KHM;

require_once dirname(__DIR__) . '/vendor/autoload.php';

use KHM\Services\DB;

function main(): int {
    try {
        $pdo = DB::getInstance()->getPDO();
        
        echo "KHM Migration Validation\n";
        echo "========================\n\n";
        
        // Check table counts
        echo "1. Row Count Comparison:\n";
        $tables = [
            'membership_levels' => ['pmpro_membership_levels', 'khm_membership_levels'],
            'membership_levelmeta' => ['pmpro_membership_levelmeta', 'khm_membership_levelmeta'],
            'memberships_users' => ['pmpro_memberships_users', 'khm_memberships_users'],
            'membership_orders' => ['pmpro_membership_orders', 'khm_membership_orders'],
        ];
        
        $allMatch = true;
        foreach ($tables as $label => $pair) {
            [$source, $target] = $pair;
            $sourceCount = getCount($pdo, $source);
            $targetCount = getCount($pdo, $target);
            $match = ($sourceCount === $targetCount) ? '✓' : '✗';
            if ($sourceCount !== $targetCount) {
                $allMatch = false;
            }
            echo "  $label: $source=$sourceCount, $target=$targetCount $match\n";
        }
        echo "\n";
        
        // Sample row validation for memberships_users
        echo "2. Sample Membership Levels (first 5 rows):\n";
        $sourceLevels = $pdo->query("SELECT id, name, initial_payment FROM pmpro_membership_levels ORDER BY id LIMIT 5")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ( $sourceLevels as $row ) {
            $stmt = $pdo->prepare("SELECT name, initial_payment FROM khm_membership_levels WHERE id = ? LIMIT 1");
            $stmt->execute([$row['id']]);
            $target = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ( $target && $target['name'] === $row['name'] && (float) $target['initial_payment'] == (float) $row['initial_payment'] ) {
                echo "  ✓ Level {$row['id']} ({$row['name']})\n";
            } else {
                echo "  ✗ Level {$row['id']} - MISMATCH\n";
                $allMatch = false;
            }
        }
        echo "\n";

        echo "3. Sample Memberships Validation (first 5 rows):\n";
        $sourceMemberships = $pdo->query("SELECT user_id, membership_id, status, startdate FROM pmpro_memberships_users ORDER BY id LIMIT 5")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($sourceMemberships as $row) {
            $stmt = $pdo->prepare("SELECT status, startdate FROM khm_memberships_users WHERE user_id = ? AND membership_id = ? LIMIT 1");
            $stmt->execute([$row['user_id'], $row['membership_id']]);
            $target = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($target && $target['status'] === $row['status'] && $target['startdate'] === $row['startdate']) {
                echo "  ✓ User {$row['user_id']}, Level {$row['membership_id']}\n";
            } else {
                echo "  ✗ User {$row['user_id']}, Level {$row['membership_id']} - MISMATCH\n";
                $allMatch = false;
            }
        }
        echo "\n";
        
        // Sample row validation for membership_orders
        echo "4. Sample Orders Validation (first 5 rows):\n";
        $sourceOrders = $pdo->query("SELECT code, user_id, membership_id, total, status, payment_transaction_id FROM pmpro_membership_orders ORDER BY id LIMIT 5")->fetchAll(\PDO::FETCH_ASSOC);
        foreach ($sourceOrders as $row) {
            $stmt = $pdo->prepare("SELECT status, total, payment_transaction_id FROM khm_membership_orders WHERE code = ? LIMIT 1");
            $stmt->execute([$row['code']]);
            $target = $stmt->fetch(\PDO::FETCH_ASSOC);
            if ($target && $target['status'] === $row['status'] && $target['total'] == $row['total']) {
                echo "  ✓ Order {$row['code']}, Total {$row['total']}, Status {$row['status']}\n";
            } else {
                echo "  ✗ Order {$row['code']} - MISMATCH or MISSING\n";
                $allMatch = false;
            }
        }
        echo "\n";
        
        // Check indexes
        echo "5. Index Check (khm_membership_orders):\n";
        $indexes = $pdo->query("SHOW INDEX FROM khm_membership_orders")->fetchAll(\PDO::FETCH_ASSOC);
        $indexNames = array_unique(array_column($indexes, 'Key_name'));
        $required = ['PRIMARY', 'code', 'user_id', 'membership_id', 'status', 'payment_transaction_id', 'subscription_transaction_id'];
        foreach ($required as $idx) {
            $exists = in_array($idx, $indexNames) ? '✓' : '✗';
            echo "  $idx: $exists\n";
            if (!in_array($idx, $indexNames)) {
                $allMatch = false;
            }
        }
        echo "\n";
        
        // Final status
        if ($allMatch) {
            echo "✓ Validation PASSED: All checks successful.\n";
            return 0;
        } else {
            echo "✗ Validation FAILED: Some checks did not pass.\n";
            return 1;
        }
        
    } catch (\Exception $e) {
        fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
        return 1;
    }
}

function getCount(\PDO $pdo, string $table): int {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM $table");
        return (int)$stmt->fetch(\PDO::FETCH_ASSOC)['cnt'];
    } catch (\PDOException $e) {
        return 0;
    }
}

exit(main());
