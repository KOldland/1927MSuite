<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Register the email notification sent to affiliates when a commission is approved.
 *
 * @param array $email_notifications
 *
 * @return array
 *
 */
function slicewp_email_notification_affiliate_commission_approved( $email_notifications = array() ) {

	// Prepare notification data.
	$notification = array(
		'name'			=> __( 'Commission Approved', 'slicewp' ),
		'description'	=> __( 'The affiliate will receive an email when an order that generated a commission is completed.', 'slicewp' ),
		'recipient'		=> 'affiliate',
		'merge_tags'  	=> array(),
	);

	// Add merge tags.
	$merge_tags = new SliceWP_Merge_Tags();

	foreach ( $merge_tags->get_tags() as $tag_slug => $tag_data ) {

		if ( empty( $tag_data['category'] ) || in_array( $tag_data['category'], array( 'affiliate', 'commission', 'general' ) ) ) {
			$notification['merge_tags'][] = $tag_slug;
		}

	}

    // Register notification.
    $email_notifications['affiliate_commission_approved'] = $notification;

	return $email_notifications;

}
add_filter( 'slicewp_available_email_notification', 'slicewp_email_notification_affiliate_commission_approved', 45 );


/**
 * Send an email notification to the affiliate when a commision is approved.
 *
 * @param int	$commission_id
 * @param array	$commission_data
 *
 */
function slicewp_send_email_notification_affiliate_commission_approved( $commission_id = 0, $commission_data = array() ) {

	// Verify received arguments not to be empty.
	if ( empty( $commission_id ) ) {
		return;
	}

	// Verify if the commission status was changed to unpaid.
	if ( empty( $commission_data['status'] ) || $commission_data['status'] != 'unpaid' ) {
		return;
	}

	// Verify previus commission status to be pending.
	if ( doing_action( 'slicewp_update_commission' ) && slicewp()->globals()->get( 'pre_update_commission_status_' . $commission_id ) != 'pending' ) {
		return;
	}

	// Verify if email notification sending is enabled.
	$notification_settings = slicewp_get_email_notification_settings( 'affiliate_commission_approved' );

	if ( empty( $notification_settings['enabled'] ) ) {
		return;
	}

	// Verify if the email notification subject and content are filled in.
	if ( empty( $notification_settings['subject'] ) || empty( $notification_settings['content'] ) ) {
		return;
	}

	// Get the commission.
	$commission = slicewp_get_commission( $commission_id );
	
	// Get the affiliate email address.
	$affiliate = slicewp_get_affiliate( absint( $commission->get( 'affiliate_id' ) ) );
	$user      = get_user_by( 'id', $affiliate->get( 'user_id' ) );

	if ( empty( $user->user_email ) ) {
		return;
	}

	// Replace the tags with data.
	$merge_tags = new SliceWP_Merge_Tags();
	$merge_tags->set_data( 'affiliate', $affiliate );
	$merge_tags->set_data( 'commission', $commission );

	$email_subject = $merge_tags->replace_tags( sanitize_text_field( $notification_settings['subject'] ) );
	$email_content = $merge_tags->replace_tags( $notification_settings['content'] );

	slicewp_wp_email( $user->user_email, $email_subject, $email_content );

}
add_action( 'slicewp_insert_commission', 'slicewp_send_email_notification_affiliate_commission_approved', 10, 2 );
add_action( 'slicewp_update_commission', 'slicewp_send_email_notification_affiliate_commission_approved', 10, 2 );