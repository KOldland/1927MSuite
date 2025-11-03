<?php
#!/usr/bin/env php
namespace KHM;

require_once dirname(__DIR__) . '/vendor/autoload.php';

use KHM\Services\DB;
use KHM\Services\Migration;

function main(array $argv): int {
    $options = parseArgs($argv);
    
    if ($options['help']) {
        showHelp();
        return 0;
    }

    try {
        // Initialize DB connection
        $pdo = DB::getInstance()->getPDO();
        
        // Initialize migration service
        $migrationDir = dirname(__DIR__) . '/db/migrations';
        $migration = new Migration($pdo, $migrationDir);
        
        // Set mode
        $migration->setDryRun(!$options['apply']);
        
        // Run migrations
        $results = $migration->run(
            $options['migrations'] ? explode(',', $options['migrations']) : []
        );
        
        // Output results
        if ($options['apply']) {
            echo "Running migrations...\n";
        } else {
            echo "Dry run mode (no changes will be made)\n";
        }
        
        foreach ($results as $file => $result) {
            echo "\n$file: {$result['status']}\n";
            if (isset($result['sql'])) {
                echo "Would execute:\n{$result['sql']}\n";
            }
            if (isset($result['error'])) {
                echo "Error: {$result['error']}\n";
            }
        }
        
        return 0;
    } catch (\Exception $e) {
        fwrite(STDERR, "Error: " . $e->getMessage() . "\n");
        return 1;
    }
}

function parseArgs(array $argv): array {
    $options = [
        'help' => false,
        'apply' => false,
        'migrations' => ''
    ];

    array_shift($argv); // Remove script name

    foreach ($argv as $arg) {
        if ($arg === '--help' || $arg === '-h') {
            $options['help'] = true;
        } elseif ($arg === '--apply') {
            $options['apply'] = true;
        } elseif (strpos($arg, '--migrations=') === 0) {
            $options['migrations'] = substr($arg, 12);
        }
    }

    return $options;
}

function showHelp(): void {
    echo <<<HELP
KHM Database Migration Tool

Usage: migrate.php [options]

Options:
  --help, -h         Show this help message
  --apply            Actually run migrations (default: dry run)
  --migrations=x,y,z Run specific migrations (comma-separated filenames)

Examples:
  migrate.php                    # Dry run all pending migrations
  migrate.php --apply           # Run all pending migrations
  migrate.php --migrations=0001_create_khm_tables.sql,0002_migrate_pmpro_to_khm.sql

The tool will:
1. Auto-detect WordPress database credentials from wp-config.php
2. Create a backup before applying any changes (when --apply is used)
3. Run migrations in order, tracking completed migrations
4. Stop on first error when applying changes

Run this tool from your WordPress root directory or a subdirectory containing
wp-config.php.

HELP;
}

exit(main($argv));