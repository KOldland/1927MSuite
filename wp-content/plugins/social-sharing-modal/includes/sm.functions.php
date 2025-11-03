<?php

// Register scripts and styles
function ssm_enqueue_assets() {
    wp_enqueue_style('ssm-modal-style', plugin_dir_url(__FILE__) . '../assets/social-modal.css');
    wp_enqueue_script('ssm-modal-script', plugin_dir_url(__FILE__) . '../assets/modal.js', ['jquery'], null, true);
}
add_action('wp_enqueue_scripts', 'ssm_enqueue_assets');
