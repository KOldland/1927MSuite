<?php

function kh_render_ad($ad_id) {
    // --- ACF FIELD MAP ---
    $ad_type      = get_field('ad_type', $ad_id); // "Display" or "Dynamic"
    $ad_format    = get_field('ad_format', $ad_id); // "Image", "Card", "Code", "Network"
    $ad_slot_raw  = get_field('ad_slot_selector', $ad_id); // Taxonomy, might be array/object
    $img          = get_field('ad_image', $ad_id);
    $ad_code      = get_field('ad_code', $ad_id);

    // Card fields
    $headline     = get_field('headline', $ad_id);
    $subheadline  = get_field('ad_subheadline', $ad_id);
    $body         = get_field('ad_body', $ad_id);
    $btn_text     = get_field('ad_button_text', $ad_id);
    $btn_url      = get_field('ad_button_url', $ad_id);
    $badge        = get_field('ad_badge', $ad_id);

    // --- SLOT NORMALIZATION ---
    if (is_array($ad_slot_raw)) {
        $first = $ad_slot_raw[0];
        $ad_slot = is_numeric($first)
        ? get_term($first, 'ad-slot')->slug ?? ''
        : (is_object($first) ? $first->slug ?? '' : $first);
    } elseif (is_numeric($ad_slot_raw)) {
        $term = get_term($ad_slot_raw, 'ad-slot');
        $ad_slot = $term ? $term->slug : '';
    } elseif (is_object($ad_slot_raw) && isset($ad_slot_raw->slug)) {
        $ad_slot = $ad_slot_raw->slug;
    } else {
        $ad_slot = $ad_slot_raw;
    }


    // --- DEBUG (can remove later) ---
    echo '<div style="background:#eee;padding:3px 8px;font-size:12px;">AD FORMAT: <b>' . esc_html($ad_format) . '</b> | SLOT: <b>' . esc_html($ad_slot) . '</b></div>';

    // --- IMAGE DIMENSION WARNING (Display slots only) ---
    $warn_msg = '';
    if ($ad_format === 'Image' && $img && isset($img['width'], $img['height']) && in_array($ad_slot, ['header', 'sidebar1', 'sidebar2', 'footer'])) {
        if ($ad_slot === 'header' && $img['width'] <= $img['height']) {
            $warn_msg = 'Header ads should be landscape (wider than tall). Wanna check that?';
        }
        if (in_array($ad_slot, ['sidebar1', 'sidebar2']) && $img['width'] >= $img['height']) {
            $warn_msg = 'Sidebar ads should be portrait (taller than wide). Wanna check that?';
        }
        if ($ad_slot === 'footer' && $img['width'] <= $img['height']) {
            $warn_msg = 'Footer ads should be landscape. Try again, Monet.';
        }
    }
    if ($warn_msg) {
        echo '<div style="background:#ffd600;color:#900;font-weight:bold;padding:10px 12px;margin:8px 0 12px 0;border-radius:5px;border:2px solid #900;">⚠️ ' . esc_html($warn_msg) . '</div>';
    }

    // --- MAIN RENDER LOGIC ---
    switch ($ad_format) {
        case 'Image':
            echo '<div class="ad-unit ' . esc_attr($ad_slot) . '">';
            if ($img) echo '<img src="' . esc_url($img['url']) . '" alt="" style="max-width:100%;height:auto;" />';
            echo '</div>';
            return;

        case 'Card':
            echo '<div class="ad-unit ' . esc_attr($ad_slot) . ' ad-card">';
            if ($badge) echo '<span class="ad-badge">' . esc_html($badge) . '</span>';
            if ($img) echo '<img src="' . esc_url($img['url']) . '" alt="" />';
            if ($headline) echo '<div class="ad-headline">' . esc_html($headline) . '</div>';
            if ($subheadline) echo '<div class="ad-subheadline">' . esc_html($subheadline) . '</div>';
            if ($body) echo '<div class="ad-body">' . esc_html($body) . '</div>';
            if ($btn_url && $btn_text) echo '<a href="' . esc_url($btn_url) . '" class="ad-btn">' . esc_html($btn_text) . '</a>';
            echo '</div>';
            return;

        case 'Code':
        case 'Network':
            echo '<div class="ad-unit ' . esc_attr($ad_slot) . ' ad-code">';
            echo $ad_code ?: '<em>No code set for this ad.</em>';
            echo '</div>';
            return;
    }

    // --- CATCH ALL ---
    echo '<div style="color:red;font-weight:bold;">Unknown or unsupported ad format or slot.<br>Format: ' . esc_html($ad_format) . '<br>Slot: ' . esc_html($ad_slot) . '</div>';
}


// --- ELEMENTOR + ACF SLOT RENDERER ---
    function kh_ad_manager_render_ad_for_slot_in_context($slot, $post_id = null) {
        if (!$slot) return;
        
        $post_id = $post_id ?: get_the_ID();
        $slot_key = sanitize_title($slot);
        
        $mode = get_field("{$slot_key}_ad_mode", $post_id) ?: 'default';
        $field_key = $mode === 'manual' 
        ? "manual_ad_code_{$slot_key}" 
        : "ad_code_{$slot_key}";
        
        // NOW you can safely debug
        echo "<!-- DEBUG: Slot={$slot_key}, Post={$post_id} -->";
        echo "<!-- DEBUG: Mode={$mode}, Field={$field_key} -->";
        
        if ($mode === 'off') {
            echo '<!-- KH Ad: ' . esc_html($slot_key) . ' is turned off -->';
            return;
        }
        
        $ad_code = get_field($field_key, 'option');
        
        echo '<div class="kh-ad-slot" data-slot="' . esc_attr($slot_key) . '">';
        echo $ad_code 
        ? wp_kses_post($ad_code) 
        : '<!-- KH Ad: no ad for slot ' . esc_html($slot_key) . ' (' . esc_html($mode) . ') -->';
        echo '</div>';

    }

    $slots = wp_get_post_terms(get_the_ID(), 'ad-slot', ['fields' => 'slugs']);
    
    if (array_intersect(['slide-in', 'pop-up'], $slots)) {
        $context = [
            'headline'     => get_field('card_headline', get_the_ID()),
            'subheading'   => get_field('card_subheading', get_the_ID()),
            'body'         => get_field('card_body', get_the_ID()),
            'button_text'  => get_field('card_button_text', get_the_ID()),
            'button_url'   => get_field('card_button_url', get_the_ID()),
            'bg_color'     => get_field('card_background_color', get_the_ID()),
            'text_color'   => get_field('card_text_color', get_the_ID()),
            'slot'         => reset($slots) // We'll use this to detect if it's slide-in or pop-up
        ];
        
        include AM_PATH . 'partials/ads/card-ad.php';
    }
    