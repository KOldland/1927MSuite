<?php

/**
 * Lightweight PSR-4 autoloader to unblock ACF Pro when Composer vendor files
 * haven't been installed in local development. This only covers the `ACF\`
 * namespace that ships with the plugin and is sufficient for our WP-CLI usage.
 */
spl_autoload_register(
    static function ( $class ) {
        $prefix = 'ACF\\';
        if ( strncmp( $class, $prefix, strlen( $prefix ) ) !== 0 ) {
            return;
        }

        $relative = substr( $class, strlen( $prefix ) );
        $relative = str_replace( '\\', '/', $relative );

        $path = __DIR__ . '/../src/' . $relative . '.php';
        if ( file_exists( $path ) ) {
            require_once $path;
        }
    }
);
