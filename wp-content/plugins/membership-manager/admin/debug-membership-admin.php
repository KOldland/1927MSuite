<?php

if (!is_admin()) return;

add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Membership Debug',
        'Membership Debug',
        'manage_options',
        'kss-membership-debug',
        'kss_membership_debug_page'
    );
});

function kss_membership_debug_page() {
    if (isset($_POST['reset_kss_articles']) && check_admin_referer('kss_reset_articles_action')) {
        $users = get_users([
            'meta_key' => 'kss_articles_read',
            'meta_compare' => 'EXISTS',
            'fields' => ['ID']
        ]);

        foreach ($users as $user) {
            delete_user_meta($user->ID, 'kss_articles_read');
        }

        echo '<div class="notice notice-success"><p>Article counts reset successfully!</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Membership Debug</h1>
        <p>This page lets you manually trigger the monthly article read count reset. Use this during testing or to fix a stuck state.</p>
        <form method="post">
            <?php wp_nonce_field('kss_reset_articles_action'); ?>
            <button type="submit" name="reset_kss_articles" class="button button-primary">Reset Article Counts Now</button>
        </form>
    </div>
    <?php
}
