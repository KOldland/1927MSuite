<?php
namespace KHM;

class Plugin {

    protected static $file;

    public static function init( $file ) {
        self::$file = $file;
        // Hook into WordPress if available. These calls are placeholders.
        if ( function_exists('add_action') ) {
            add_action('init', [ self::class, 'on_init' ]);
        }
    }

    public static function on_init() {
        // Register custom post types, shortcodes, etc.
    }

    public static function get_dir() {
        return dirname(self::$file);
    }
}
