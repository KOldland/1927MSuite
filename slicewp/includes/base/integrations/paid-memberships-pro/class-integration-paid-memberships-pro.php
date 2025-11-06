<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * The class that defines the Paid Memberships Pro integration.
 *
 */
Class SliceWP_Integration_Paid_Memberships_Pro extends SliceWP_Integration {

	/**
	 * Constructor.
	 *
	 */
	public function __construct() {

		/**
		 * Set the name of the integration.
		 *
		 */
		$this->name = 'Paid Memberships Pro';

		/**
		 * Set the supports values.
		 *
		 */
		$supports = array(
			'commission_types' 				=> array( 'subscription' ),
			'new_customer_commissions_only' => true,
			'__commission_items'			=> true // This is currently an experimental feature.
		);

		/**
		 * Filter the supports array.
		 *
		 * @param array $supports
		 *
		 */
		$this->supports = apply_filters( 'slicewp_integration_supports_pmpro', $supports );

	}


	/**
	 * Returns a standardized array of the data from the given order.
	 * 
	 * @param int|MemberOrder $order
	 * 
	 * @return array
	 * 
	 */
	public function get_formatted_order_data( $order ) {

		$order = ( is_numeric( $order ) ? new MemberOrder( $order ) : $order );

		if ( empty( $order ) ) {
			return null;
		}

		global $pmpro_currency;

		$data = array(
			'origin'	    => 'pmpro',
			'reference' 	=> absint( $order->id ),
			'type'			=> 'sale',
			'currency'      => sanitize_text_field( $pmpro_currency ),
			'subtotal'		=> slicewp_sanitize_amount( $order->subtotal, $pmpro_currency ),
			'total' 		=> slicewp_sanitize_amount( $order->total, $pmpro_currency ),
			'tax'			=> slicewp_sanitize_amount( $order->tax, $pmpro_currency ),
			'items'			=> array(
				array(
					'type'           => 'product',
					'name'   	 	 => sanitize_text_field( $order->getMembershipLevel()->name ),
					'quantity'       => 1,
					'subtotal'		 => slicewp_sanitize_amount( $order->subtotal, $pmpro_currency ),
					'total'  		 => slicewp_sanitize_amount( $order->total, $pmpro_currency ),
					'tax'			 => slicewp_sanitize_amount( $order->tax, $pmpro_currency ),
					'meta_data'		 => array(
						'product_id'	 => absint( $order->membership_id )
					)
				)
			)
		);
	
		return $data;

	}


	/**
	 * Returns the admin edit page URL for the given product (level) ID.
	 * 
	 * @param int $product_id
	 * 
	 * @return string
	 * 
	 */
	public function get_product_admin_url( $product_id ) {

		return add_query_arg( array( 'page' => 'pmpro-membershiplevels', 'edit' => absint( $product_id ) ), admin_url( 'admin.php' ) );

	}

}