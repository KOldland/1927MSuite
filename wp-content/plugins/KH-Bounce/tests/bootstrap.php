<?php
$_tests_dir = getenv( 'WP_PHPUNIT__DIR' );
if ( ! $_tests_dir ) {
    $_tests_dir = '/tmp/wordpress-tests-lib';
}

if ( ! file_exists( $_tests_dir . '/includes/functions.php' ) ) {
    fwrite( STDERR, "Cannot find WordPress tests bootstrap in ".$_tests_dir."\n" );
    exit( 1 );
}

require $_tests_dir . '/includes/functions.php';

function kh_bounce_tests_bootstrap() {
    require dirname( __DIR__ ) . '/kh-bounce.php';
}
tests_add_filter( 'muplugins_loaded', 'kh_bounce_tests_bootstrap' );

require $_tests_dir . '/includes/bootstrap.php';
