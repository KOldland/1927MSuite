<?php
// Add custom columns
add_filter('manage_ad_unit_posts_columns', function($columns) {
    $columns['ad-slots'] = 'Ad Slot(s)';
    $columns['impressions'] = 'Impressions';
    $columns['clicks'] = 'Clicks';
    $columns['ctr'] = 'CTR%';
    return $columns;
});

// Populate custom columns
add_action('manage_ad_unit_posts_custom_column', function($column, $post_id) {
    if ($column === 'ad-slots') {
        $terms = get_the_terms($post_id, 'ad-slot');
        if (!empty($terms) && !is_wp_error($terms)) {
            echo esc_html(join(', ', wp_list_pluck($terms, 'name')));
        } else {
            echo '<span style="color:#bbb">—</span>';
        }
    }
    if ($column === 'impressions') echo esc_html(get_field('ad_impressions', $post_id) ?: 0);
    if ($column === 'clicks') echo esc_html(get_field('ad_clicks', $post_id) ?: 0);
    if ($column === 'ctr') {
        $imp = (int) get_field('ad_impressions', $post_id);
        $clk = (int) get_field('ad_clicks', $post_id);
        echo $imp ? number_format(100 * $clk / $imp, 1) . '%' : '0%';
    }
}, 10, 2);
