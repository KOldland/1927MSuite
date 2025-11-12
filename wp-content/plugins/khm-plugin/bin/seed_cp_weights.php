<?php
#!/usr/bin/env php
namespace KHM;

require_once dirname(__DIR__) . '/vendor/autoload.php';

use KHM\Seeds\CpWeightSeeder;

function main( array $argv ): int {
    $options = parse_args( $argv );

    if ( $options['help'] ) {
        show_help();
        return 0;
    }

    try {
        $seeder = new CpWeightSeeder(
            null,
            $options['file'] ?: null
        );
        $result = $seeder->seed( $options['truncate'] );

        echo "cp_weights seed complete.\n";
        echo "Source file : {$result['file']}\n";
        echo "Rows loaded : {$result['rows_processed']}\n";
        echo $options['truncate']
            ? "Mode        : truncate + upsert\n"
            : "Mode        : upsert only\n";

        return 0;
    } catch ( \Throwable $e ) {
        fwrite( STDERR, "Seed failed: {$e->getMessage()}\n" );
        return 1;
    }
}

function parse_args( array $argv ): array {
    $options = [
        'help'     => false,
        'truncate' => false,
        'file'     => '',
    ];

    array_shift( $argv );

    foreach ( $argv as $arg ) {
        if ( $arg === '--help' || $arg === '-h' ) {
            $options['help'] = true;
        } elseif ( $arg === '--truncate' ) {
            $options['truncate'] = true;
        } elseif ( strpos( $arg, '--file=' ) === 0 ) {
            $options['file'] = substr( $arg, 7 );
        }
    }

    return $options;
}

function show_help(): void {
    echo <<<HELP
cp_weights Seed Loader

Usage: seed_cp_weights.php [options]

Options:
  --help, -h       Show this help message
  --truncate       Truncate cp_weights before loading (default: upsert)
  --file=path      Override default JSON seed file path

Examples:
  php seed_cp_weights.php --truncate
  php seed_cp_weights.php --file=/tmp/custom_seed.json

HELP;
}

exit( main( $argv ) );
