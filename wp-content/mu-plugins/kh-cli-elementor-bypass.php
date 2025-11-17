<?php
/**
 * Safely bypass Elementor when running CLI scripts.
 */
/*
Plugin Name: KH CLI Elementor Bypass
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! function_exists( 'kh_cli_should_skip_elementor' ) ) {
    function kh_cli_should_skip_elementor() {
        if ( defined( 'KH_SMMA_FORCE_ELEMENTOR' ) && KH_SMMA_FORCE_ELEMENTOR ) {
            return false;
        }

        if ( defined( 'KH_SMMA_SKIP_ELEMENTOR' ) && KH_SMMA_SKIP_ELEMENTOR ) {
            return true;
        }

        if ( defined( 'WP_CLI' ) && WP_CLI ) {
            return true;
        }

        return php_sapi_name() === 'cli';
    }
}

if ( kh_cli_should_skip_elementor() ) {
    add_filter( 'option_active_plugins', 'kh_cli_filter_active_plugins', 5 );
    add_filter( 'site_option_active_sitewide_plugins', 'kh_cli_filter_network_plugins', 5 );
}

function kh_cli_filter_active_plugins( $plugins ) {
    $blocked = array( 'elementor/elementor.php', 'elementor-pro/elementor-pro.php' );

    return array_values( array_filter( $plugins, function ( $plugin ) use ( $blocked ) {
        return ! in_array( $plugin, $blocked, true );
    } ) );
}

function kh_cli_filter_network_plugins( $plugins ) {
    foreach ( array( 'elementor/elementor.php', 'elementor-pro/elementor-pro.php' ) as $plugin ) {
        if ( isset( $plugins[ $plugin ] ) ) {
            unset( $plugins[ $plugin ] );
        }
    }

    return $plugins;
}
