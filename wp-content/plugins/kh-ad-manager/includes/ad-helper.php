<?php

/**
 * Get the highest priority ad for a given slot (and optionally, a category).
 *
 * @param string $slot_slug Slug of the ad slot (e.g. 'sidebar1')
 * @param int|null $category_id Optional category ID for more specific targeting
 * @return WP_Post|null
 */
function kh_get_ad_for_slot($slot_slug, $category_id = null) {
    $args = [
        'post_type'      => 'ad_unit',
        'posts_per_page' => 1,
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'ad_priority',
        'order'          => 'DESC',
        'tax_query'      => [
            [
                'taxonomy' => 'ad_slot',
                'field'    => 'slug',
                'terms'    => $slot_slug,
            ]
        ],
    ];

    if ($category_id) {
        $args['tax_query'][] = [
            'taxonomy' => 'category',
            'field'    => 'term_id',
            'terms'    => $category_id,
        ];
    }

    $ads = get_posts($args);
    return $ads ? $ads[0] : null;
}


/**
 * Slot-specific dimension rules.
 * Use these to bully users into uploading properly-sized ads.
 *
 * @return array
 */
function kh_get_slot_exact_dimensions() {
    return [
        'header'     => ['width' => 1600, 'height' => 500],
        'footer'     => ['width' => 1600, 'height' => 500],
        'sidebar1'   => ['width' => 300,  'height' => 600],
        'sidebar2'   => ['width' => 300,  'height' => 600],
        'pop-up'      => ['width' => 700,  'height' => 700],
        'slide-in'   => ['width' => 300,  'height' => 250],
    ];
}