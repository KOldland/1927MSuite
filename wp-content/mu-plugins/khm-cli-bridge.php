<?php
/**
 * Ensure the 4A scoring WP-CLI command is available in local environments even
 * when the full KHM plugin stack is not activated (due to heavy dependencies).
 */
/*
Plugin Name: KHM CLI Bridge
*/

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	$plugin_root = WP_CONTENT_DIR . '/plugins/khm-plugin';
	$autoloader  = $plugin_root . '/vendor/autoload.php';

	if ( file_exists( $autoloader ) ) {
		require_once $autoloader;
	}

	if ( class_exists( 'KHM\\Cli\\FourAScoreCommand' ) ) {
		WP_CLI::add_command( 'khm-4a', 'KHM\\Cli\\FourAScoreCommand' );
	}
}
