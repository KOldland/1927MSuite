<?php
/**
 * Utility script to create the core KHM membership tables with the current
 * WordPress table prefix.
 *
 * Usage (from WordPress root):
 *   php wp-content/plugins/khm-plugin/bin/create_base_tables.php
 */

$root = __DIR__;
for ($i = 0; $i < 6; $i++) {
    if (file_exists($root . '/wp-config.php')) {
        break;
    }
    $root = dirname($root);
}

$configPath = $root . '/wp-config.php';

if ( ! file_exists($configPath) ) {
    fwrite(STDERR, "wp-config.php not found. Run the script from within the WordPress installation.\n");
    exit(1);
}

$config = file_get_contents($configPath);

$creds = [
    'DB_NAME'     => null,
    'DB_USER'     => null,
    'DB_PASSWORD' => null,
    'DB_HOST'     => null,
    'table_prefix' => 'wp_',
];

foreach (['DB_NAME', 'DB_USER', 'DB_PASSWORD', 'DB_HOST'] as $key) {
    if (preg_match("/define\\s*\\(\\s*'{$key}'\\s*,\\s*'([^']*)'\\s*\\)/", $config, $m)) {
        $creds[$key] = $m[1];
    }
}

if (preg_match('/\\$table_prefix\\s*=\\s*\'([^\']+)\'\\s*;/', $config, $m)) {
    $creds['table_prefix'] = $m[1];
}

if (in_array(null, [$creds['DB_NAME'], $creds['DB_USER'], $creds['DB_HOST']], true)) {
    fwrite(STDERR, "Failed to parse database credentials from wp-config.php.\n");
    exit(1);
}

$host = $creds['DB_HOST'] === 'localhost' ? '127.0.0.1' : $creds['DB_HOST'];
$mysqli = @new mysqli($host, $creds['DB_USER'], $creds['DB_PASSWORD'], $creds['DB_NAME']);

if ($mysqli->connect_error) {
    fwrite(STDERR, "Connect failed: {$mysqli->connect_error}\n");
    exit(1);
}

$sqlPath = $root . '/wp-content/plugins/khm-plugin/db/migrations/0001_create_khm_tables.sql';
if ( ! file_exists($sqlPath) ) {
    fwrite(STDERR, "Migration file 0001_create_khm_tables.sql not found.\n");
    exit(1);
}

$sql = file_get_contents($sqlPath);
$prefix = $creds['table_prefix'];
$sql = str_replace('`khm_', '`' . $prefix . 'khm_', $sql);

if ( ! $mysqli->multi_query($sql) ) {
    fwrite(STDERR, "Migration failed: {$mysqli->error}\n");
    exit(1);
}

do {
    if ($result = $mysqli->store_result()) {
        $result->free();
    }
} while ($mysqli->more_results() && $mysqli->next_result());

$mysqli->close();

echo "Base KHM membership tables created (if they did not already exist).\n";
