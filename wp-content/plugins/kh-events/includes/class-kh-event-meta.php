<?php
/**
 * Event Meta Box Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KH_Event_Meta {

    public function __construct() {
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        add_action('save_post', array($this, 'save_meta'));
    }

    public function add_meta_boxes() {
        add_meta_box(
            'kh_event_details',
            __('Event Details', 'kh-events'),
            array($this, 'render_meta_box'),
            'kh_event',
            'normal',
            'high'
        );
    }

    public function render_meta_box($post) {
        wp_nonce_field('kh_event_meta_nonce', 'kh_event_meta_nonce');

        $start_date = get_post_meta($post->ID, '_kh_event_start_date', true);
        $end_date = get_post_meta($post->ID, '_kh_event_end_date', true);
        $start_time = get_post_meta($post->ID, '_kh_event_start_time', true);
        $end_time = get_post_meta($post->ID, '_kh_event_end_time', true);
        $recurring = get_post_meta($post->ID, '_kh_event_recurring', true);
        $recurring_type = get_post_meta($post->ID, '_kh_event_recurring_type', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="kh_event_start_date"><?php _e('Start Date', 'kh-events'); ?></label></th>
                <td><input type="date" id="kh_event_start_date" name="kh_event_start_date" value="<?php echo esc_attr($start_date); ?>" /></td>
            </tr>
            <tr>
                <th><label for="kh_event_end_date"><?php _e('End Date', 'kh-events'); ?></label></th>
                <td><input type="date" id="kh_event_end_date" name="kh_event_end_date" value="<?php echo esc_attr($end_date); ?>" /></td>
            </tr>
            <tr>
                <th><label for="kh_event_start_time"><?php _e('Start Time', 'kh-events'); ?></label></th>
                <td><input type="time" id="kh_event_start_time" name="kh_event_start_time" value="<?php echo esc_attr($start_time); ?>" /></td>
            </tr>
            <tr>
                <th><label for="kh_event_end_time"><?php _e('End Time', 'kh-events'); ?></label></th>
                <td><input type="time" id="kh_event_end_time" name="kh_event_end_time" value="<?php echo esc_attr($end_time); ?>" /></td>
            </tr>
            <tr>
                <th><label for="kh_event_recurring"><?php _e('Recurring Event', 'kh-events'); ?></label></th>
                <td>
                    <input type="checkbox" id="kh_event_recurring" name="kh_event_recurring" value="1" <?php checked($recurring, '1'); ?> />
                    <label for="kh_event_recurring"><?php _e('This is a recurring event', 'kh-events'); ?></label>
                </td>
            </tr>
            <tr id="recurring_type_row" style="display: <?php echo $recurring ? 'table-row' : 'none'; ?>;">
                <th><label for="kh_event_recurring_type"><?php _e('Recurring Type', 'kh-events'); ?></label></th>
                <td>
                    <select id="kh_event_recurring_type" name="kh_event_recurring_type">
                        <option value="daily" <?php selected($recurring_type, 'daily'); ?>><?php _e('Daily', 'kh-events'); ?></option>
                        <option value="weekly" <?php selected($recurring_type, 'weekly'); ?>><?php _e('Weekly', 'kh-events'); ?></option>
                        <option value="monthly" <?php selected($recurring_type, 'monthly'); ?>><?php _e('Monthly', 'kh-events'); ?></option>
                        <option value="yearly" <?php selected($recurring_type, 'yearly'); ?>><?php _e('Yearly', 'kh-events'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <script>
        jQuery(document).ready(function($) {
            $('#kh_event_recurring').change(function() {
                if ($(this).is(':checked')) {
                    $('#recurring_type_row').show();
                } else {
                    $('#recurring_type_row').hide();
                }
            });
        });
        </script>
        <?php
    }

    public function save_meta($post_id) {
        if (!isset($_POST['kh_event_meta_nonce']) || !wp_verify_nonce($_POST['kh_event_meta_nonce'], 'kh_event_meta_nonce')) {
            return;
        }

        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        $fields = array(
            'kh_event_start_date' => '_kh_event_start_date',
            'kh_event_end_date' => '_kh_event_end_date',
            'kh_event_start_time' => '_kh_event_start_time',
            'kh_event_end_time' => '_kh_event_end_time',
            'kh_event_recurring' => '_kh_event_recurring',
            'kh_event_recurring_type' => '_kh_event_recurring_type',
        );

        foreach ($fields as $field => $meta_key) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
            }
        }
    }
}