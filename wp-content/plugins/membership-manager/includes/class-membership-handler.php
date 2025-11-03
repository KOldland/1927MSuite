<?php

class Membership_Handler {
    public function __construct() {
        add_action('init', [$this, 'maybe_limit_access']);
    }

    public function maybe_limit_access() {
        if (is_admin() || (is_user_logged_in() && current_user_can('manage_options'))) return;

        $user_id = get_current_user_id();
        $free_limit = 4;
        $anon_limit = 1;

        if ($user_id) {
            $articles_read = (int) get_user_meta($user_id, 'articles_read_this_month', true);
            update_user_meta($user_id, 'articles_read_this_month', $articles_read + 1);
        } else {
            $articles_read = isset($_COOKIE['anon_reads']) ? (int) $_COOKIE['anon_reads'] : 0;
            setcookie('anon_reads', $articles_read + 1, time() + (30 * DAY_IN_SECONDS), COOKIEPATH, COOKIE_DOMAIN);
        }

        if (($user_id && $articles_read >= $free_limit) || (!$user_id && $articles_read >= $anon_limit)) {
            wp_redirect(home_url('/subscribe/'));
            exit;
        }
    }
}
