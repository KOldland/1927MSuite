<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class SliceWP_Migration_1_1_27
 *
 * Migration that runs when updating to version 1.1.27
 *
 */
class SliceWP_Migration_1_1_27 extends SliceWP_Abstract_Migration {


	/**
	 * Constructor.
	 *
	 */
	public function __construct() {
		
		$this->id          = 'slicewp-update-1-1-27';
		$this->notice_type = 'none';

		parent::__construct();

	}


	/**
	 * Actually run the migration.
	 *
	 */
	public function migrate() {

        // Get settings.
        $settings = slicewp_get_option( 'settings', array() );

        if ( empty( $settings ) || empty( $settings['email_notifications'] ) || ! is_array( $settings['email_notifications'] ) || isset( $settings['email_notifications']['affiliate_commission_rejected'] ) ) {
            return true;
        }

        // Set the default settings for the affiliate payment paid email notification.
        $settings['email_notifications']['affiliate_commission_rejected'] = array(
            'enabled' => '',
            'subject' => __( 'Commission Rejected', 'slicewp' ),
            'content' => __( 'Hey {{affiliate_first_name}},', 'slicewp' ) . "\n\n" . __( "Commission #{{commission_id}}, totalling {{commission_amount}}, has been rejected.", 'slicewp' )
        );

        slicewp_update_option( 'settings', $settings );

        return true;

	}

}