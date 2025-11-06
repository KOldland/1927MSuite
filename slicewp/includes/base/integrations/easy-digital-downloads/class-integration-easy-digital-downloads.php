<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * The class that defines the Easy Digital Downloads integration.
 *
 */
Class SliceWP_Integration_Easy_Digital_Downloads extends SliceWP_Integration {

	/**
	 * Constructor.
	 *
	 */
	public function __construct() {

		/**
		 * Set the name of the integration.
		 *
		 */
		$this->name = 'Easy Digital Downloads';

		/**
		 * Set the supports values.
		 *
		 */
		$supports = array(
			'commission_types' 				=> array( 'sale' ),
			'new_customer_commissions_only' => true,
			'__commission_items'			=> true // This is currently an experimental feature.
		);

		/**
		 * Filter the supports array.
		 *
		 * @param array $supports
		 *
		 */
		$this->supports = apply_filters( 'slicewp_integration_supports_edd', $supports );

	}


	/**
	 * Returns a standardized array of the data from the given order.
	 * 
	 * @param int|EDD\Orders\Order $order
	 * 
	 * @return array
	 * 
	 */
	public function get_formatted_order_data( $order ) {

		$order = ( function_exists( 'edd_get_order' ) ? edd_get_order( $order ) : ( is_numeric( $order ) ? edd_get_payment( $order ) : null ) );

		if ( empty( $order ) ) {
			return null;
		}

		$order_id = absint( isset( $order->id ) ? $order->id : ( isset( $order->ID ) ? $order->ID : 0 ) );

		if ( empty( $order_id ) ) {
			return null;
		}

		$currency = sanitize_text_field( $order->currency );

		$data = array(
			'origin'	    => 'edd',
			'reference' 	=> absint( $order_id ),
			'type'			=> 'sale',
			'currency'      => sanitize_text_field( $order->currency ),
			'subtotal'		=> slicewp_sanitize_amount( $order->subtotal, $currency ),
			'total' 		=> slicewp_sanitize_amount( $order->total, $currency ),
			'tax'			=> slicewp_sanitize_amount( $order->tax, $currency )
		);

		$cart_items = edd_get_payment_meta_cart_details( $order_id );

		foreach ( $cart_items as $cart_item ) {

			$item = array(
				'type'           => 'product',
				'name'   	 	 => sanitize_text_field( $cart_item['name'] ),
				'quantity'       => $cart_item['quantity'],
				'subtotal'		 => slicewp_sanitize_amount( $cart_item['subtotal'], $currency ),
				'total'  		 => slicewp_sanitize_amount( $cart_item['price'], $currency ),
				'tax'			 => slicewp_sanitize_amount( $cart_item['tax'], $currency ),
				'meta_data'		 => array(
					'order_item_id'  => $cart_item['order_item_id'],
					'product_id'     => $cart_item['item_number']['id'],
					'price_id'       => ( ! empty( $cart_item['item_number']['options']['price_id'] ) ? $cart_item['item_number']['options']['price_id'] : 0 )
				)
			);

			$items[] = $item;

		}

		$data['items'] = $items;
	
		return $data;

	}


	/**
	 * Returns the admin edit page URL for the given product ID.
	 * 
	 * @param int $product_id
	 * 
	 * @return string
	 * 
	 */
	public function get_product_admin_url( $product_id ) {

		return add_query_arg( array( 'post' => absint( $product_id ), 'action' => 'edit' ), admin_url( 'post.php' ) );

	}

}