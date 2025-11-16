<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * The class that defines the WooCommerce integration.
 *
 */
Class SliceWP_Integration_WooCommerce extends SliceWP_Integration {

	/**
	 * Constructor.
	 *
	 */
	public function __construct() {

		/**
		 * Set the name of the integration.
		 *
		 */
		$this->name = 'WooCommerce';

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
		$this->supports = apply_filters( 'slicewp_integration_supports_woo', $supports );

	}


	/**
	 * Returns a standardized array of the data from the given order.
	 * 
	 * @param int|WC_Order $order
	 * 
	 * @return array
	 * 
	 */
	public function get_formatted_order_data( $order ) {

		$order = wc_get_order( $order );

		if ( empty( $order ) ) {
			return null;
		}

		$order_type = ( $order->get_type() == 'shop_order' ? 'sale' : ( $order->get_type() == 'shop_order_refund' ? 'refund' : '' ) );

		if ( empty( $order_type ) ) {
			return null;
		}

		$currency = sanitize_text_field( $order->get_currency( 'edit' ) );

		$data = array(
			'origin'	    => 'woo',
			'reference' 	=> absint( $order->get_id() ),
			'type'			=> sanitize_text_field( $order_type ),
			'currency'      => $currency,
			'subtotal'		=> slicewp_sanitize_amount( $order->get_subtotal(), $currency ),
			'total' 		=> slicewp_sanitize_amount( $order->get_total( 'edit' ), $currency ),
			'tax'			=> slicewp_sanitize_amount( $order->get_total_tax( 'edit' ), $currency )
		);
	
		$items = array();

		foreach ( $order->get_items( array( 'line_item', 'shipping', 'fee' ) ) as $order_item ) {

			if ( $order_item->get_type() == 'line_item' ) {

				$item = array(
					'type'           => 'product',
					'name'   	 	 => sanitize_text_field( $order_item->get_name() ),
					'quantity'       => $order_item->get_quantity(),
					'subtotal'		 => slicewp_sanitize_amount( $order_item->get_subtotal( 'edit' ), $currency ),
					'total'  		 => slicewp_sanitize_amount( $order_item->get_total( 'edit' ) + $order_item->get_total_tax( 'edit' ), $currency ),
					'tax'			 => slicewp_sanitize_amount( $order_item->get_total_tax( 'edit' ), $currency ),
					'meta_data'		 => array(
						'order_item_id'  => $order_item->get_id(),
						'product_id'     => absint( $order_item->get_product_id( 'edit' ) ),
						'product_sku'  	 => sanitize_text_field( $order_item->get_product()->get_sku( 'edit' ) ),
						'variation_id'   => absint( $order_item->get_variation_id( 'edit' ) )
					)
				);

			} else {

				$item = array(
					'type'           => $order_item->get_type(),
					'name'   	 	 => sanitize_text_field( $order_item->get_name() ),
					'quantity'       => $order_item->get_quantity(),
					'total'  		 => slicewp_sanitize_amount( $order_item->get_total( 'edit' ) + $order_item->get_total_tax( 'edit' ), $currency ),
					'tax'			 => slicewp_sanitize_amount( $order_item->get_total_tax( 'edit' ), $currency ),
					'meta_data'		 => array(
						'order_item_id'  => $order_item->get_id()
					)
				);

			}

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