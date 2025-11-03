<?php

add_action('admin_menu', function() {
    add_submenu_page(
        'tools.php',
        'Reset Article Counts',
        'Reset Article Counts',
        'manage_options',
        'reset-article-counts',
        'kss_reset_admin_page'
    );
});

function kss_reset_admin_page() {
    if (isset($_POST['kss_reset_now']) && current_user_can('manage_options')) {
        do_action('kss_reset_article_counts');
        echo '<div class="notice notice-success"><p>Article counts have been reset.</p></div>';
    }

    ?>
    <div class="wrap">
        <h1>Reset Article Counts</h1>
        <form method="post">
            <p>This will delete the <code>kss_articles_read</code> meta for all users who have it.</p>
            <input type="submit" name="kss_reset_now" class="button button-primary" value="Reset Now">
        </form>
    </div>
    <?php
}
