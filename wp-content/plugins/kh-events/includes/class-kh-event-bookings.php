<?php
/**
 * Bookings Class
 */

if (!defined('ABSPATH')) {
    exit;
}

class KH_Event_Bookings {

    public function __construct() {
        add_action('init', array($this, 'register_post_type'));
        add_shortcode('kh_event_booking_form', array($this, 'booking_form_shortcode'));
        add_action('wp_ajax_kh_submit_booking', array($this, 'submit_booking'));
        add_action('wp_ajax_nopriv_kh_submit_booking', array($this, 'submit_booking'));
        add_action('add_meta_boxes', array($this, 'add_booking_meta_boxes'));
    }

    public function register_post_type() {
        register_post_type('kh_booking', array(
            'labels' => array(
                'name' => __('Bookings', 'kh-events'),
                'singular_name' => __('Booking', 'kh-events'),
            ),
            'public' => false,
            'show_ui' => true,
            'show_in_menu' => 'kh-events',
            'supports' => array('title'),
            'capability_type' => 'post',
        ));
    }

    public function add_booking_meta_boxes() {
        add_meta_box(
            'kh_booking_details',
            __('Booking Details', 'kh-events'),
            array($this, 'render_booking_meta_box'),
            'kh_booking',
            'normal',
            'high'
        );
    }

    public function render_booking_meta_box($post) {
        $event_id = get_post_meta($post->ID, '_kh_booking_event_id', true);
        $attendee_name = get_post_meta($post->ID, '_kh_booking_attendee_name', true);
        $attendee_email = get_post_meta($post->ID, '_kh_booking_attendee_email', true);
        $tickets = get_post_meta($post->ID, '_kh_booking_tickets', true);
        $status = get_post_meta($post->ID, '_kh_booking_status', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label><?php _e('Event', 'kh-events'); ?></label></th>
                <td><?php echo get_the_title($event_id); ?></td>
            </tr>
            <tr>
                <th><label><?php _e('Attendee Name', 'kh-events'); ?></label></th>
                <td><?php echo esc_html($attendee_name); ?></td>
            </tr>
            <tr>
                <th><label><?php _e('Attendee Email', 'kh-events'); ?></label></th>
                <td><?php echo esc_html($attendee_email); ?></td>
            </tr>
            <tr>
                <th><label><?php _e('Tickets', 'kh-events'); ?></label></th>
                <td>
                    <?php if ($tickets): ?>
                        <ul>
                            <?php foreach ($tickets as $ticket): ?>
                                <li><?php echo esc_html($ticket['name']); ?> (<?php echo $ticket['quantity']; ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th><label for="kh_booking_status"><?php _e('Status', 'kh-events'); ?></label></th>
                <td>
                    <select name="kh_booking_status" id="kh_booking_status">
                        <option value="pending" <?php selected($status, 'pending'); ?>><?php _e('Pending', 'kh-events'); ?></option>
                        <option value="confirmed" <?php selected($status, 'confirmed'); ?>><?php _e('Confirmed', 'kh-events'); ?></option>
                        <option value="cancelled" <?php selected($status, 'cancelled'); ?>><?php _e('Cancelled', 'kh-events'); ?></option>
                    </select>
                </td>
            </tr>
        </table>
        <?php
    }

    public function booking_form_shortcode($atts) {
        $atts = shortcode_atts(array(
            'event_id' => get_the_ID(),
        ), $atts);

        $event_id = intval($atts['event_id']);
        $tickets = get_post_meta($event_id, '_kh_event_tickets', true);

        if (!$tickets) {
            return '<p>' . __('No tickets available for this event.', 'kh-events') . '</p>';
        }

        ob_start();
        ?>
        <div class="kh-booking-form">
            <h3><?php _e('Book Tickets', 'kh-events'); ?></h3>
            <form id="kh-booking-form" method="post">
                <?php wp_nonce_field('kh_booking_nonce', 'kh_booking_nonce'); ?>
                <input type="hidden" name="event_id" value="<?php echo $event_id; ?>" />

                <p>
                    <label for="attendee_name"><?php _e('Your Name', 'kh-events'); ?>:</label>
                    <input type="text" id="attendee_name" name="attendee_name" required />
                </p>
                <p>
                    <label for="attendee_email"><?php _e('Your Email', 'kh-events'); ?>:</label>
                    <input type="email" id="attendee_email" name="attendee_email" required />
                </p>

                <h4><?php _e('Select Tickets', 'kh-events'); ?></h4>
                <?php foreach ($tickets as $index => $ticket): ?>
                    <div class="kh-ticket-selection">
                        <h5><?php echo esc_html($ticket['name']); ?></h5>
                        <p><?php echo esc_html($ticket['description']); ?></p>
                        <p><?php _e('Price', 'kh-events'); ?>: $<?php echo number_format($ticket['price'], 2); ?></p>
                        <p>
                            <label><?php _e('Quantity', 'kh-events'); ?>:</label>
                            <input type="number" name="tickets[<?php echo $index; ?>][quantity]" min="0" max="<?php echo $ticket['quantity']; ?>" />
                            <input type="hidden" name="tickets[<?php echo $index; ?>][name]" value="<?php echo esc_attr($ticket['name']); ?>" />
                            <input type="hidden" name="tickets[<?php echo $index; ?>][price]" value="<?php echo $ticket['price']; ?>" />
                        </p>
                    </div>
                <?php endforeach; ?>

                <p>
                    <button type="submit" class="button"><?php _e('Submit Booking', 'kh-events'); ?></button>
                </p>
            </form>
            <div id="kh-booking-message"></div>
        </div>

        <script>
        jQuery(document).ready(function($) {
            $('#kh-booking-form').submit(function(e) {
                e.preventDefault();
                var formData = $(this).serialize();

                $.ajax({
                    type: 'POST',
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    data: formData + '&action=kh_submit_booking',
                    success: function(response) {
                        if (response.success) {
                            $('#kh-booking-message').html('<p class="success">' + response.data.message + '</p>');
                            $('#kh-booking-form')[0].reset();
                        } else {
                            $('#kh-booking-message').html('<p class="error">' + response.data.message + '</p>');
                        }
                    }
                });
            });
        });
        </script>
        <?php
        return ob_get_clean();
    }

    public function submit_booking() {
        if (!wp_verify_nonce($_POST['kh_booking_nonce'], 'kh_booking_nonce')) {
            wp_send_json_error(array('message' => __('Security check failed.', 'kh-events')));
        }

        $event_id = intval($_POST['event_id']);
        $attendee_name = sanitize_text_field($_POST['attendee_name']);
        $attendee_email = sanitize_email($_POST['attendee_email']);
        $tickets = $_POST['tickets'];

        // Validate tickets
        $selected_tickets = array();
        foreach ($tickets as $ticket) {
            if (intval($ticket['quantity']) > 0) {
                $selected_tickets[] = array(
                    'name' => sanitize_text_field($ticket['name']),
                    'price' => floatval($ticket['price']),
                    'quantity' => intval($ticket['quantity']),
                );
            }
        }

        if (empty($selected_tickets)) {
            wp_send_json_error(array('message' => __('Please select at least one ticket.', 'kh-events')));
        }

        // Create booking post
        $booking_id = wp_insert_post(array(
            'post_type' => 'kh_booking',
            'post_title' => sprintf(__('Booking for %s by %s', 'kh-events'), get_the_title($event_id), $attendee_name),
            'post_status' => 'publish',
        ));

        if ($booking_id) {
            update_post_meta($booking_id, '_kh_booking_event_id', $event_id);
            update_post_meta($booking_id, '_kh_booking_attendee_name', $attendee_name);
            update_post_meta($booking_id, '_kh_booking_attendee_email', $attendee_email);
            update_post_meta($booking_id, '_kh_booking_tickets', $selected_tickets);
            update_post_meta($booking_id, '_kh_booking_status', 'pending');

            wp_send_json_success(array('message' => __('Booking submitted successfully. You will receive a confirmation email soon.', 'kh-events')));
        } else {
            wp_send_json_error(array('message' => __('Failed to submit booking. Please try again.', 'kh-events')));
        }
    }
}