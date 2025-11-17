<?php
namespace KHFormBuilder;

use KHFormBuilder\Admin\FormAdmin;
use KHFormBuilder\Shortcodes\FormShortcode;
use KHFormBuilder\Submissions\SubmissionHandler;

class Plugin
{
    public static function init()
    {
        spl_autoload_register([__CLASS__, 'autoload']);

        add_action('init', [__CLASS__, 'registerPostTypes']);
        add_action('init', [FormShortcode::class, 'register']);

        if (is_admin()) {
            FormAdmin::getInstance();
        }

        SubmissionHandler::getInstance();
    }

    public static function autoload($class)
    {
        if (strpos($class, __NAMESPACE__ . '\\') !== 0) {
            return;
        }

        $relative = substr($class, strlen(__NAMESPACE__) + 1);
        $path     = KH_FORM_BUILDER_PATH . 'includes/' . str_replace('\\', '/', $relative) . '.php';
        if (file_exists($path)) {
            require_once $path;
        }
    }

    public static function registerPostTypes()
    {
        register_post_type('kh_form', [
            'labels'      => [
                'name'          => __('Forms', 'kh-form-builder'),
                'singular_name' => __('Form', 'kh-form-builder'),
            ],
            'public'      => false,
            'show_ui'     => true,
            'menu_icon'   => 'dashicons-feedback',
            'supports'    => ['title'],
        ]);

        register_post_type('kh_form_entry', [
            'labels'      => [
                'name'          => __('Form Entries', 'kh-form-builder'),
                'singular_name' => __('Entry', 'kh-form-builder'),
            ],
            'public'      => false,
            'show_ui'     => true,
            'show_in_menu'=> 'edit.php?post_type=kh_form',
            'supports'    => ['title'],
        ]);
    }
}
