<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

// Add commission table reference column edit screen link.
add_filter( 'slicewp_list_table_commissions_column_reference', 'slicewp_list_table_commissions_add_reference_edit_link_woo', 10, 2 );
add_filter( 'slicewp_list_table_payout_commissions_column_reference', 'slicewp_list_table_commissions_add_reference_edit_link_woo', 10, 2 );

// Insert a new pending commission.
add_action( 'woocommerce_checkout_update_order_meta', 'slicewp_insert_pending_commission_woo', 10, 1 );

if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '6.4.0', '>=' ) ) {
	add_action( 'woocommerce_store_api_checkout_order_processed', 'slicewp_insert_pending_commission_woo' );
} else {
	add_action( 'woocommerce_blocks_checkout_order_processed', 'slicewp_insert_pending_commission_woo' );
}

// Checks whether the referring customer of the affiliate referrer is a new customer or not.
add_action( 'slicewp_referrer_affiliate_id_woo', 'slicewp_validate_referrer_affiliate_id_new_customer_woo', 15, 2 );

// Update the status of the commission to "unpaid", thus marking it as complete
add_action( 'woocommerce_order_status_completed', 'slicewp_accept_pending_commission_woo', 10, 1 );
add_action( 'woocommerce_order_status_processing', 'slicewp_accept_pending_commission_woo', 10, 1 );

// Update the status of the commission to "rejected" when the originating order is refunded
add_action( 'woocommerce_order_status_changed', 'slicewp_reject_commission_on_refund_woo', 10, 3 );

// Update the status of the commission to "rejected" when the originating order is cancelled or failed payment
add_action( 'woocommerce_order_status_changed', 'slicewp_reject_commission_on_order_fail_woo', 10, 3 );

// Update the status of the commission to "rejected" when the originating order is trashed
add_action( 'wc-on-hold_to_trash', 'slicewp_reject_commission_on_trash_woo', 10 );
add_action( 'wc-processing_to_trash', 'slicewp_reject_commission_on_trash_woo', 10 );
add_action( 'wc-completed_to_trash', 'slicewp_reject_commission_on_trash_woo', 10 );

// Update the status of the commission to "pending" when the originating order is moved from failed to any other status
add_action( 'woocommerce_order_status_changed', 'slicewp_approve_rejected_commission_woo', 10, 3 );

// Add commission settings in product page
add_filter( 'woocommerce_product_data_tabs', 'slicewp_add_product_data_tab_woo' );
add_action( 'woocommerce_product_data_panels', 'slicewp_add_product_commission_settings_woo' );

// Save the product commission settings
add_action( 'save_post_product' , 'slicewp_save_product_commission_settings_woo', 10, 2 );

// Add commission settings in product variation page.
add_action( 'woocommerce_product_after_variable_attributes', 'slicewp_add_product_variation_commission_settings_woo', 10, 3 );

// Save the product variation commission settings.
add_action( 'woocommerce_ajax_save_product_variations', 'slicewp_save_product_variation_commission_settings_woo', 10, 1 );

// Add commission settings in product category page.
add_action( 'product_cat_add_form_fields', 'slicewp_add_product_category_commision_settings_woo', 10 );
add_action( 'product_cat_edit_form_fields', 'slicewp_edit_product_category_commision_settings_woo', 10, 1 );

// Save the product category commission settings.
add_action( 'created_product_cat', 'slicewp_save_product_category_commission_settings_woo', 10, 3 );
add_action( 'edited_product_cat', 'slicewp_save_product_category_commission_settings_woo', 10, 3 );

// Add an order note for the created commission.
add_action( 'slicewp_insert_commission', 'slicewp_add_order_note_woo', 15, 2 );

// Add the reference amount in the commission data.
add_filter( 'slicewp_pre_insert_commission_data', 'slicewp_add_commission_data_reference_amount_woo' );
add_filter( 'slicewp_pre_update_commission_data', 'slicewp_add_commission_data_reference_amount_woo' );

// Add the WooCommerce specific rewrite rules.
add_action( 'init', 'slicewp_add_rewrite_rules_woo' );

/**
 * Adds the edit screen link to the reference column value from the commissions list table.
 *
 * @param string $output
 * @param array  $item
 *
 * @return string
 *
 */
function slicewp_list_table_commissions_add_reference_edit_link_woo( $output, $item ) {

	if ( empty( $item['reference'] ) ) {
		return $output;
	}

	if ( empty( $item['origin'] ) || $item['origin'] != 'woo' ) {
		return $output;
	}

	// Get the order.
	$order = wc_get_order( $item['reference'] );

	// Create link to order only if the order exists.
    if ( ! empty( $order ) && $order->get_status() != 'trash' ) {
		$output = '<a href="' . esc_url( add_query_arg( array( 'post' => $item['reference'], 'action' => 'edit' ), admin_url( 'post.php' ) ) ) . '">' . $item['reference'] . '</a>';
	}

	return $output;

}


/**
 * Inserts a new pending commission when a new pending order is registered
 *
 * @param int|WC_Order $order
 *
 */
function slicewp_insert_pending_commission_woo( $order ) {

	$order_id = ( is_a( $order, 'WC_Order' ) ? $order->get_id() : $order );

	// Get and check to see if referrer exists.
	$affiliate_id = slicewp_get_referrer_affiliate_id();
	$visit_id	  = slicewp_get_referrer_visit_id();

	/**
	 * Filters the referrer affiliate ID for WooCommerce.
	 * This is mainly used by add-ons for different functionality.
	 *
	 * @param int $affiliate_id
	 * @param int $order_id
	 *
	 */
	$affiliate_id = apply_filters( 'slicewp_referrer_affiliate_id_woo', $affiliate_id, $order_id );

	if ( empty( $affiliate_id ) ) {
		return;
	}

	// Verify if the affiliate is valid.
	if ( ! slicewp_is_affiliate_valid( $affiliate_id ) ) {

		slicewp_add_log( 'WOO: Pending commission was not created because the affiliate is not valid.' );
		return;

	}
	
	// Check to see if a commission for this order has been registered.
	$commissions = slicewp_get_commissions( array( 'reference' => $order_id, 'origin' => 'woo' ) );

	if ( ! empty( $commissions ) ) {

		slicewp_add_log( 'WOO: Pending commission was not created because another commission for the reference and origin already exists.' );
		return;

	}

	// Get order.
	$order = ( is_a( $order, 'WC_Order' ) ? $order : wc_get_order( $order_id ) );

	// Check to see if the affiliate made the purchase.
	if ( empty( slicewp_get_setting( 'affiliate_own_commissions' ) ) ) {

		$affiliate = slicewp_get_affiliate( $affiliate_id );

		if ( is_user_logged_in() && get_current_user_id() == $affiliate->get('user_id') ) {

			slicewp_add_log( 'WOO: Pending commission was not created because the customer is also the affiliate.' );
			return;

		}

		// Get billing email
		$billing_email = ( true === version_compare( WC()->version, '3.0.0', '>=' ) ? $order->get_billing_email() : $order->billing_email );

		if ( slicewp_affiliate_has_email( $affiliate_id, $billing_email ) ) {

			slicewp_add_log( 'WOO: Pending commission was not created because the customer is also the affiliate.' );
			return;

		}

	}


	// Process the customer.
	$customer_args = array(
		'email'   	   => $order->get_billing_email(),
		'user_id' 	   => $order->get_user_id(),
		'first_name'   => $order->get_billing_first_name(),
		'last_name'	   => $order->get_billing_last_name(),
		'affiliate_id' => $affiliate_id
	);

	$customer_id = slicewp_process_customer( $customer_args );

	if ( $customer_id ) {
		slicewp_add_log( sprintf( 'WOO: Customer #%s has been successfully processed.', $customer_id ) );
	} else {
		slicewp_add_log( 'WOO: Customer could not be processed due to an unexpected error.' );
	}


	// Get formatted order data.
	$formatted_order_data = slicewp()->integrations['woo']->get_formatted_order_data( $order );

	// Set currencies.
	$active_currency = slicewp_get_setting( 'active_currency', 'USD' );
	$order_currency  = $formatted_order_data['currency'];

	// Prepare the transaction data.
	$transaction_data = $formatted_order_data;

	$transaction_data['original_currency'] = $transaction_data['currency'];
	$transaction_data['original_subtotal'] = $transaction_data['subtotal'];
	$transaction_data['original_total']    = $transaction_data['total'];
	$transaction_data['original_tax'] 	   = $transaction_data['tax'];

	$transaction_data['currency'] = $active_currency;
	$transaction_data['subtotal'] = slicewp_sanitize_amount( slicewp_maybe_convert_amount( $transaction_data['subtotal'], $order_currency, $active_currency ) );
	$transaction_data['total'] 	  = slicewp_sanitize_amount( slicewp_maybe_convert_amount( $transaction_data['total'], $order_currency, $active_currency ) );
	$transaction_data['tax'] 	  = slicewp_sanitize_amount( slicewp_maybe_convert_amount( $transaction_data['tax'], $order_currency, $active_currency ) );

	$transaction_data['customer_id'] = $customer_id;

	$transaction_data['currency_conversion_rate'] = slicewp_get_currency_conversion_rate( $order_currency, $active_currency );

	foreach ( $transaction_data['items'] as $key => $transaction_item_data ) {

		$transaction_item_data['original_subtotal'] = $transaction_item_data['subtotal'];
		$transaction_item_data['original_total']    = $transaction_item_data['total'];
		$transaction_item_data['original_tax'] 	    = $transaction_item_data['tax'];

		$transaction_item_data['subtotal'] = slicewp_sanitize_amount( slicewp_maybe_convert_amount( $transaction_item_data['subtotal'], $order_currency, $active_currency ) );
		$transaction_item_data['total']    = slicewp_sanitize_amount( slicewp_maybe_convert_amount( $transaction_item_data['total'], $order_currency, $active_currency ) );
		$transaction_item_data['tax'] 	   = slicewp_sanitize_amount( slicewp_maybe_convert_amount( $transaction_item_data['tax'], $order_currency, $active_currency ) );

		// Move meta_data at the end for cleaner array.
		if ( ! empty( $transaction_item_data['meta_data'] ) ) {
			$transaction_item_data = array_merge( array_diff_key( $transaction_item_data, array( 'meta_data' => $transaction_item_data['meta_data'] ) ), array( 'meta_data' => $transaction_item_data['meta_data'] ) );
		}

		$transaction_data['items'][$key] = $transaction_item_data;

	}

	// Move items at the end for cleaner array.
	if ( ! empty( $transaction_data['items'] ) ) {
		$transaction_data = array_merge( array_diff_key( $transaction_data, array( 'items' => $transaction_data['items'] ) ), array( 'items' => $transaction_data['items'] ) );
	}


    // Build the commission items for each item in the cart.
	$commission_amount 			   = 0;
	$commission_items 			   = array();
	$order_commission_types 	   = array();
	$is_commission_basis_per_order = slicewp_is_commission_basis_per_order();

    if ( ! $is_commission_basis_per_order ) {

		// Commission items for product transaction items.
		foreach ( $transaction_data['items'] as $transaction_item_data ) {

			if ( $transaction_item_data['type'] != 'product' ) {
				continue;
			}
			
			// Get product and variation IDs.
			$product_id   = absint( $transaction_item_data['meta_data']['product_id'] );
			$variation_id = absint( $transaction_item_data['meta_data']['variation_id'] );

			// Get the product categories.
			$categories = get_the_terms( $product_id, 'product_cat' );

			// Verify if commissions are disabled for this product category.
			if ( ! empty( $categories[0]->term_id ) && get_term_meta( $categories[0]->term_id, 'slicewp_disable_commissions', true ) ) {
				continue;
			}

			// Verify if commissions are disabled for this product.
			if ( get_post_meta( $product_id, 'slicewp_disable_commissions', true ) ) {
				continue;
			}

			// Verify if commissions are disabled for this product variation.
			if ( ! empty( $variation_id ) && get_post_meta( $variation_id, 'slicewp_disable_commissions', true ) ) {
				continue;
			}

			$commissionable_amount = $transaction_item_data['total'];

			// Include tax.
			if ( ! empty( slicewp_get_setting( 'exclude_tax', false ) ) ) {
				$commissionable_amount -= $transaction_item_data['tax'];
			}

			// Get the product.
			$product = wc_get_product( ! empty( $variation_id ) ? $variation_id : $product_id );

			// Calculate commission amount.
			$args = array(
				'origin'	   => 'woo',
				'type' 		   => ( $product->is_type( array( 'subscription', 'variable-subscription', 'subscription_variation' ) ) ? 'subscription' : 'sale' ),
				'affiliate_id' => $affiliate_id,
				'product_id'   => ( ! empty( $variation_id ) ? $variation_id : $product_id ),
				'quantity'	   => $transaction_item_data['quantity'],
				'customer_id'  => $customer_id
			);

			// Add commission item.
			$commission_items[] = array(
				'commissionable_amount'   => $commissionable_amount,
				'commissionable_quantity' => $transaction_item_data['quantity'],
				'amount'				  => slicewp_calculate_commission_amount( $commissionable_amount, $args ),
				'transaction_item'		  => $transaction_item_data
			);

			// Save the order commission types for future use.
			$order_commission_types[] = $args['type'];

		}

		// If we have commission items for products, build commission items for shipping.
		// We do not want to have shipping commission items if no product commission items were generated.
		if ( ! empty( $commission_items ) && empty( slicewp_get_setting( 'exclude_shipping', false ) ) ) {

			foreach ( $transaction_data['items'] as $transaction_item_data ) {

				if ( $transaction_item_data['type'] != 'shipping' ) {
					continue;
				}

				// Skip saving free shipping options.
				if ( empty( floatval( $transaction_item_data['total'] ) ) ) {
					continue;
				}

				$commissionable_amount = $transaction_item_data['total'];

				// Include tax.
				if ( ! empty( slicewp_get_setting( 'exclude_tax', false ) ) ) {
					$commissionable_amount -= $transaction_item_data['tax'];
				}

				// Prepare commission calculation arguments.
				$args = array(
					'origin'	   => 'woo',
					'type' 		   => 'sale',
					'affiliate_id' => $affiliate_id,
					'customer_id'  => $customer_id
				);

				// Add commission item.
				$commission_items[] = array(
					'commissionable_amount'   => slicewp_sanitize_amount( $commissionable_amount ),
					'commissionable_quantity' => $transaction_item_data['quantity'],
					'amount'				  => slicewp_sanitize_amount( slicewp_calculate_commission_amount( $commissionable_amount, $args ) ),
					'transaction_item'		  => $transaction_item_data
				);

			}

		}

		// Sum up the commission items' amounts.
		if ( ! empty( $commission_items ) ) {
			$commission_amount = array_sum( array_column( $commission_items, 'amount' ) );
		}

	// Calculate the commission amount for the entire order.
	} else {

		$args = array(
			'origin'	   => 'woo',
			'type' 		   => 'sale',
			'affiliate_id' => $affiliate_id,
			'customer_id'  => $customer_id
		);

		$commission_amount = slicewp_calculate_commission_amount( 0, $args );

        // Save the order commission types for future use.
        $order_commission_types[] = $args['type'];

	}

    // Check that the commission amount is not zero.
    if ( ( $commission_amount == 0 ) && empty( slicewp_get_setting( 'zero_amount_commissions' ) ) ) {

        slicewp_add_log( 'WOO: Commission was not inserted because the commission amount is zero. Order: ' . absint( $order_id ) );
        return;

    }

    // Remove duplicated order commission types.
    $order_commission_types = array_unique( $order_commission_types );

	// Get the current referrer visit.
	$visit = slicewp_get_visit( $visit_id );

	// Prepare commission data.
	$commission_data = array(
		'affiliate_id'		=> $affiliate_id,
		'visit_id'			=> ( ! is_null( $visit ) && $visit->get( 'affiliate_id' ) == $affiliate_id ? $visit_id : 0 ),
		'type'				=> sizeof( $order_commission_types ) == 1 ? $order_commission_types[0] : 'sale',
		'status'			=> 'pending',
		'reference'			=> $order_id,
		'reference_amount'	=> slicewp_sanitize_amount( $transaction_data['total'] ),
		'customer_id'		=> $customer_id,
		'origin'			=> 'woo',
		'amount'			=> slicewp_sanitize_amount( $commission_amount ),
		'currency'			=> $active_currency,
		'date_created'		=> slicewp_mysql_gmdate(),
		'date_modified'		=> slicewp_mysql_gmdate()
	);

	// Insert the commission.
	$commission_id = slicewp_insert_commission( $commission_data );

	if ( ! empty( $commission_id ) ) {

		// Add the transaction data as metadata.
		if ( ! empty( $transaction_data ) ) {

			slicewp_update_commission_meta( $commission_id, '__transaction_data', $transaction_data );

		}

		// Add commission items as metadata.
		if ( ! empty( $commission_items ) ) {

			slicewp_update_commission_meta( $commission_id, '__commission_items', $commission_items );

		}

		// Mark commission basis per order.
		if ( $is_commission_basis_per_order ) {

			slicewp_add_commission_meta( $commission_id, '_is_commission_basis_per_order', 1 );

		}

		// Update the visit with the newly inserted commission_id if the visit isn't already marked as converted and
		// if the current referrer affiliate is the same as the visit's affiliate.
		if ( ! is_null( $visit ) ) {

			if ( $visit->get( 'affiliate_id' ) == $affiliate_id && empty( $visit->get( 'commission_id' ) ) ) {

				slicewp_update_visit( $visit_id, array( 'date_modified' => slicewp_mysql_gmdate(), 'commission_id' => $commission_id ) );

			}
			
		}

		slicewp_add_log( sprintf( 'WOO: Pending commission #%s has been successfully inserted.', $commission_id ) );
		
	} else {

		slicewp_add_log( 'WOO: Pending commission could not be inserted due to an unexpected error.' );
		
	}

}


/**
 * Updates the status of the commission attached to an order to "unpaid", thus marking it as complete.
 *
 * @param int $order_id
 *
 */
function slicewp_accept_pending_commission_woo( $order_id ) {

	// Return if the order is processing and the payment method is cash on delivery.
	$order = wc_get_order( $order_id );

	if ( $order->get_status() == 'processing' && $order->get_payment_method() == 'cod' ) {
		return;
	}

	// Check to see if a commission for this order has been registered.
	$commissions = slicewp_get_commissions( array( 'number' => -1, 'reference' => $order_id, 'origin' => 'woo', 'order' => 'ASC' ) );

	if ( empty( $commissions ) ) {
		return;
	}

	foreach ( $commissions as $commission ) {

		// Return if the commission has already been paid.
		if ( $commission->get('status') == 'paid' ) {
			continue;
		}

		// Prepare commission data.
		$commission_data = array(
			'date_modified' => slicewp_mysql_gmdate(),
			'status' 		=> 'unpaid'
		);

		// Update the commission.
		$updated = slicewp_update_commission( $commission->get( 'id' ), $commission_data );

		if ( false !== $updated ) {

			slicewp_add_log( sprintf( 'WOO: Pending commission #%s successfully marked as completed.', $commission->get( 'id' ) ) );

		} else {

			slicewp_add_log( sprintf( 'WOO: Pending commission #%s could not be completed due to an unexpected error.' ), $commission->get( 'id' ) );

		}

	}

}


/**
 * Update the status of the commission to "rejected" when the originating order is refunded
 *
 * @param int    $order_id
 * @param string $status_from
 * @param string $status_to
 *
 */
function slicewp_reject_commission_on_refund_woo( $order_id, $status_from, $status_to ) {

	if ( ! slicewp_get_setting( 'reject_commissions_on_refund', false ) ) {
		return;
	}

	if ( $status_to != 'refunded' ) {
		return;
	}

	// Check to see if a commission for this order has been registered
	$commissions = slicewp_get_commissions( array( 'number' => -1, 'reference' => $order_id, 'origin' => 'woo', 'order' => 'ASC' ) );

	if ( empty( $commissions ) ) {
		return;
	}

	foreach ( $commissions as $commission ) {

		if ( $commission->get( 'status' ) == 'paid' ) {

			slicewp_add_log( sprintf( 'WOO: Commission #%s could not be rejected because it was already paid.', $commission->get( 'id' ) ) );
			continue;
	
		}

		// Add rejection reason for the commission.
		// We are adding the rejection reason early because the email notification is sent upon commission update.
		// If we'd update the rejection reason after updating the commission, the reason would not be available when sending the commission.
		// It would be great if we could update metadata on the fly alongside the object data and have all data available for the object update action.
		slicewp_update_commission_meta( $commission->get( 'id' ), '_rejection_reason', sprintf( __( "This commission has been rejected because the reference order #%s was refunded.", 'slicewp' ), $order_id ) );
	
		// Prepare commission data.
		$commission_data = array(
			'date_modified' => slicewp_mysql_gmdate(),
			'status' 		=> 'rejected'
		);
	
		// Update the commission.
		$updated = slicewp_update_commission( $commission->get( 'id' ), $commission_data );
	
		if ( false !== $updated ) {

			slicewp_add_log( sprintf( 'WOO: Commission #%s successfully marked as rejected, after order #%s was refunded.', $commission->get( 'id' ), $order_id ) );

		} else {

			slicewp_add_log( sprintf( 'WOO: Commission #%s could not be rejected due to an unexpected error.', $commission->get( 'id' ) ) );

		}

	}

}


/**
 * Update the status of the commission to "rejected" when the originating order is cancelled or failed payment
 *
 * @param int    $order_id
 * @param string $status_from
 * @param string $status_to
 *
 */
function slicewp_reject_commission_on_order_fail_woo( $order_id, $status_from, $status_to ) {

	if ( $status_to != 'failed' && $status_to != 'cancelled' ) {
		return;
	}

	// Check to see if a commission for this order has been registered.
	$commissions = slicewp_get_commissions( array( 'number' => -1, 'reference' => $order_id, 'origin' => 'woo', 'order' => 'ASC' ) );

	if ( empty( $commissions ) ) {
		return;
	}

	foreach ( $commissions as $commission ) {

		if ( $commission->get( 'status' ) == 'paid' ) {

			slicewp_add_log( sprintf( 'WOO: Commission #%s could not be rejected because it was already paid.', $commission->get( 'id' ) ) );
			continue;
	
		}
	
		// Prepare commission data.
		$commission_data = array(
			'date_modified' => slicewp_mysql_gmdate(),
			'status' 		=> 'rejected'
		);
	
		// Update the commission.
		$updated = slicewp_update_commission( $commission->get('id'), $commission_data );
	
		if ( false !== $updated ) {

			slicewp_add_log( sprintf( 'WOO: Commission #%s successfully marked as rejected, after order #%s failed or was cancelled.', $commission->get('id'), $order_id ) );

		} else {

			slicewp_add_log( sprintf( 'WOO: Commission #%s could not be rejected due to an unexpected error.', $commission->get('id') ) );

		}

	}

}


/**
 * Update the status of the commission to "rejected" when the originating order is trashed
 *
 * @param int $order_id
 *
 */
function slicewp_reject_commission_on_trash_woo( $order_id ) {

	if ( is_a( $order_id, 'WP_Post' ) ) {
		$order_id = $order_id->ID;
	}

	if ( get_post_type( $order_id ) != 'shop_order' ) {
		return;
	}

	// Check to see if a commission for this order has been registered.
	$commissions = slicewp_get_commissions( array( 'number' => -1, 'reference' => $order_id, 'origin' => 'woo', 'order' => 'ASC' ) );

	if ( empty( $commissions ) ) {
		return;
	}

	foreach ( $commissions as $commission ) {

		if ( $commission->get( 'status' ) == 'paid' ) {

			slicewp_add_log( sprintf( 'WOO: Commission #%s could not be rejected because it was already paid.', $commission->get( 'id' ) ) );
			continue;
	
		}
	
		// Prepare commission data.
		$commission_data = array(
			'date_modified' => slicewp_mysql_gmdate(),
			'status' 		=> 'rejected'
		);
	
		// Update the commission.
		$updated = slicewp_update_commission( $commission->get( 'id' ), $commission_data );
	
		if ( false !== $updated ) {

			slicewp_add_log( sprintf( 'WOO: Commission #%s successfully marked as rejected, after order #%s was trashed.', $commission->get( 'id' ), $order_id ) );

		} else {

			slicewp_add_log( sprintf( 'WOO: Commission #%s could not be rejected due to an unexpected error.', $commission->get( 'id' ) ) );

		}

	}

}


/**
 * Update the status of the commission to "pending" when the originating order is moved from failed to any other status
 *
 * @param int    $order_id
 * @param string $status_from
 * @param string $status_to
 *
 */
function slicewp_approve_rejected_commission_woo( $order_id, $status_from, $status_to ) {

	if ( ! in_array( $status_from, array( 'failed', 'cancelled', 'refunded' ) ) ) {
		return;
	}

	if ( in_array( $status_to, array( 'failed', 'cancelled', 'refunded', 'processing', 'completed' ) ) ) {
		return;
	}

	// Check to see if a commission for this order has been registered.
	$commissions = slicewp_get_commissions( array( 'number' => -1, 'reference' => $order_id, 'origin' => 'woo', 'order' => 'ASC' ) );

	if ( empty( $commissions ) ) {
		return;
	}

	foreach ( $commissions as $commission ) {

		if ( $commission->get( 'status' ) != 'rejected' ) {
			continue;
		}

		// Prepare commission data.
		$commission_data = array(
			'date_modified' => slicewp_mysql_gmdate(),
			'status' 		=> 'pending'
		);

		// Update the commission.
		$updated = slicewp_update_commission( $commission->get( 'id' ), $commission_data );

		if ( false !== $updated ) {

			slicewp_add_log( sprintf( 'WOO: Commission #%s successfully marked as pending, after order #%s was updated from %s to %s.', $commission->get( 'id' ), $order_id, $status_from, $status_to ) );

		} else {

			slicewp_add_log( sprintf( 'WOO: Commission #%s could not be marked as pending due to an unexpected error.', $commission->get( 'id' ) ) );

		}

	}

}


/**
 * Adds the SliceWP data tab in the WooCommerce product page
 * 
 * @param array $tabs
 * 
 * @return array $tabs
 * 
 */
function slicewp_add_product_data_tab_woo( $tabs ) {

    $tabs['slicewp'] = array(
        'label'    => __( 'SliceWP', 'slicewp' ),
        'target'   => 'slicewp_product_settings',
        'class'    => array()
    );

    return $tabs;

}


/**
 * Adds the product commission settings in the product page - SliceWP data tab
 * 
 * 
 */
function slicewp_add_product_commission_settings_woo() {

    global $post;

?>

    <div id="slicewp_product_settings" class="panel woocommerce_options_panel slicewp-options-groups-wrapper">
	    
	    <?php

	    	/**
	         * Hook to add option groups before the core one
	         * 
	         */
	        do_action( 'slicewp_woo_product_data_panel_top' );

	    ?>

        <div class="slicewp-options-group options_group">

	        <p><?php echo( __( 'Here you can make commission customizations for this product. These settings will be used to calculate the commissions for this product.', 'slicewp' ) ); ?></p>

			<?php

			    // Get the product categories
			    $categories = get_the_terms( $post->ID, 'product_cat' );

			?>

			<?php if ( ! empty( $categories[0]->term_id ) && get_term_meta( $categories[0]->term_id, 'slicewp_disable_commissions', true ) ): ?>

				<p class="form-row form-row-full slicewp-product-commissions-disabled"><?php echo __( 'The product commission rate settings are not available because the commissions for this product category are disabled.', 'slicewp' ); ?></p>

			<?php else: ?>

				<?php

			        /**
			         * Hook to add settings before the core ones
			         * 
			         */
			        do_action( 'slicewp_woo_product_data_panel_options_group_core_top' );
			        
			        woocommerce_wp_checkbox( array(
			            'id'          => 'slicewp_disable_commissions',
			            'label'       => __( 'Disable commissions', 'slicewp' ),
			            'description' => __( 'When checked, commissions will not be generated for this product.', 'slicewp' ),
			            'cbvalue'     => 1,
			            'class'		  => 'slicewp-option-field-disable-commissions',
			            'wrapper_class' => 'slicewp-option-field-wrapper'
			        ) );

			        /**
			         * Hook to add settings after the core ones
			         * 
			         */
			        do_action( 'slicewp_woo_product_data_panel_options_group_core_bottom' );
			        
				?>

			<?php endif; ?>

        </div>

        <?php 

        	/**
	         * Hook to add options groups after the core one
	         * 
	         */
	        do_action( 'slicewp_woo_product_data_panel_bottom' );

        ?>

    </div>

<?php

    // Add nonce field
    wp_nonce_field( 'slicewp_save_meta', 'slicewp_token', false );

}


/**
 * Saves the product commission settings into the product meta
 * 
 * @param int $post_id
 * @param WP_Post $post
 * 
 */
function slicewp_save_product_commission_settings_woo( $post_id, $post ) {

	// Verify for nonce
    if ( empty( $_POST['slicewp_token'] ) || ! wp_verify_nonce( $_POST['slicewp_token'], 'slicewp_save_meta' ) ) {
		return $post_id;
	}
    
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return $post_id;
	}

    if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) ) {
		return $post_id;
	}

    // Update the disable commissions settings
    if ( ! empty( $_POST['slicewp_disable_commissions'] ) ) {

        update_post_meta( $post_id, 'slicewp_disable_commissions', 1 );

    } else {

        delete_post_meta( $post_id, 'slicewp_disable_commissions' );

    }

}


/**
 * Adds the product variation commission settings in the variations tab
 * 
 * @param int     $loop           Position in the loop.
 * @param array   $variation_data Variation data.
 * @param WP_Post $variation      Post data.
 * 
 */
function slicewp_add_product_variation_commission_settings_woo( $loop, $variation_data, $variation ) {

    // Show the product variation commission settings
?>

    <div id="slicewp_product_variation_settings" class="slicewp-options-groups-wrapper">

    	<?php

    		/**
	         * Hook to add settings before the core ones
	         * 
	         */
	        do_action( 'slicewp_woo_variation_product_data_panel_top', $loop, $variation_data, $variation );

    	?>

    	<div class="slicewp-options-group">

	        <p class="form-row form-row-full">
	            <strong><?php echo __( 'SliceWP Commission Settings', 'slicewp' ); ?></strong>
	        </p>

			<?php

			    // Get the product categories
			    $categories = get_the_terms( $variation->post_parent, 'product_cat' );

			?>

			<?php 

				// Verify if commissions are disabled for category
				if ( ! empty( $categories[0]->term_id ) && get_term_meta( $categories[0]->term_id, 'slicewp_disable_commissions', true ) ): 

			?>

	        	<p class="form-row form-row-full slicewp-product-variation-commissions-disabled"><?php echo __( 'The product variation commission rate settings are not available because the commissions for this product category are disabled.', 'slicewp' ); ?></p>

			<?php

			    // Verify if the commissions are disabled for the parent product
			    elseif ( get_post_meta( $variation->post_parent, 'slicewp_disable_commissions', true ) ):

			?>

	        	<p class="form-row form-row-full slicewp-product-variation-commissions-disabled"><?php echo __( 'The product variation commission rate settings are not available because the commissions for this product are disabled.', 'slicewp' ); ?></p>

			<?php else: ?>

				<?php

			        $disable_commissions = get_post_meta( $variation->ID, 'slicewp_disable_commissions', true );

			        /**
			         * Hook to add settings before the core ones
			         * 
			         */
			        do_action( 'slicewp_woo_variation_product_data_panel_options_group_core_top', $loop, $variation_data, $variation );

		    	?>

		        <p class="slicewp-option-field-wrapper form-row form-row-full options slicewp_variation_disable_commissions[<?php echo $variation->ID?>]">
		            <label for="slicewp_variation_disable_commissions[<?php echo $variation->ID?>]">
		                <input type="checkbox" class="slicewp-option-field-disable-commissions checkbox" name="slicewp_variation_disable_commissions[<?php echo $variation->ID; ?>]" id="slicewp_variation_disable_commissions[<?php echo $variation->ID; ?>]" <?php checked( $disable_commissions, true ); ?> /> <?php echo __( 'Disable commissions for this product variation', 'slicewp' ); ?>
		            </label>
		        </p>


			    <?php

			        /**
			         * Hook to add settings after the core ones
			         * 
			         */
			        do_action( 'slicewp_woo_variation_product_data_panel_options_group_core_bottom', $loop, $variation_data, $variation );

				?>

			<?php endif; ?>

		</div>

		<?php

	        /**
	         * Hook to add settings after the core ones
	         * 
	         */
	        do_action( 'slicewp_woo_variation_product_data_panel_bottom', $loop, $variation_data, $variation );

		?>

    </div>

<?php

}


/**
 * Saves the product variation commission settings into the product meta
 * 
 * @param int $product_id
 * 
 */
function slicewp_save_product_variation_commission_settings_woo( $product_id = 0 ) {

    // Check for variations
    if ( empty( $_POST['variable_post_id'] ) ) {
		return;
	}
    
    // Parse all the variations
    foreach( $_POST['variable_post_id'] as $variation_id ) {

        $variation_id = absint( $variation_id );

        // Update the disable commissions settings
        if ( ! empty( $_POST['slicewp_variation_disable_commissions'] ) && ! empty( $_POST['slicewp_variation_disable_commissions'][$variation_id] ) ) {

            update_post_meta( $variation_id, 'slicewp_disable_commissions', 1 );

        } else {

            delete_post_meta( $variation_id, 'slicewp_disable_commissions' );

        }
        
    }

}


/**
 * Adds the category commission settings in the add product category page
 * 
 */
function slicewp_add_product_category_commision_settings_woo() {

    /**
     * Hook to add fields before the core ones
     * 
     */
    do_action( 'slicewp_woo_add_product_category_form_fields_top' );

	?>

    <div class="slicewp-option-field-wrapper">

        <label for="slicewp-disable-commissions">
            <input type="checkbox" class="slicewp-option-field-disable-commissions checkbox" name="slicewp_disable_commissions" id="slicewp-disable-commissions" class="slicewp-option-field-disable-commissions" /><?php echo __( 'Disable commissions', 'slicewp' ); ?>
        </label>
        <p><?php echo __( 'When checked, commissions will not be generated for this product category.', 'slicewp' ); ?></p>

    </div>

	<?php

    /**
     * Hook to add fields after the core ones
     * 
     */
    do_action( 'slicewp_woo_add_product_category_form_fields_bottom' );

}


/**
 * Adds the category commission settings in the edit product category page
 * 
 * @param WP_Term $category
 * 
 */
function slicewp_edit_product_category_commision_settings_woo( $category ) {

    // Get the product category commission settings
    $category_disable_commissions = get_term_meta( $category->term_id, 'slicewp_disable_commissions', true );

    /**
     * Hook to add fields before the core ones
     * 
     */
    do_action( 'slicewp_woo_edit_product_category_form_fields_top', $category );

    ?>
    
    <tr class="slicewp-option-field-wrapper form-field">
        <th scope="row">
            <label for="slicewp-disable-commissions"><?php echo __( 'Disable commissions', 'slicewp' ); ?></label>
        </th>
        <td>
            <input type="checkbox" class="slicewp-option-field-disable-commissions checkbox" name="slicewp_disable_commissions" id="slicewp-disable-commissions" <?php checked( $category_disable_commissions, true ); ?> />
            <p class="description"><?php echo __( 'When checked, commissions will not be generated for this product category.', 'slicewp' ); ?></p>
        </td>
    </tr>

    <?php

    /**
     * Hook to add fields after the core ones
     * 
     */    
    do_action( 'slicewp_woo_edit_product_category_form_fields_bottom', $category );
       
}


/**
 * Saves the product category commission settings into the category meta
 * 
 * @param int $category_id
 * 
 */
function slicewp_save_product_category_commission_settings_woo( $category_id ) {

    // Update the disable commissions settings
    if ( ! empty( $_POST['slicewp_disable_commissions'] ) ) {

        update_term_meta( $category_id, 'slicewp_disable_commissions', 1 );
    
    } else {

        delete_term_meta( $category_id, 'slicewp_disable_commissions' );
    
    }

}


/**
 * Checks whether the referring customer of the affiliate referrer is a new customer or not.
 * If they are, the affiliate referrer is no longer valid.
 * 
 * @param int $affiliate_id
 * @param int $order_id
 * 
 * @return int
 * 
 */
function slicewp_validate_referrer_affiliate_id_new_customer_woo( $affiliate_id, $order_id ) {

	if ( empty( slicewp_get_setting( 'new_customer_commissions_only' ) ) ) {
		return $affiliate_id;
	}

	// Get current order.
	$order = wc_get_order( $order_id );

	if ( empty( $order ) ) {
		return $affiliate_id;
	}

	// Get customer's order count.
	if ( ! empty( $order->get_customer_id() ) ) {
		$orders_count = wc_get_customer_order_count( $order->get_customer_id() );
	} else {
		$orders_count = count( wc_get_orders( array( 'billing_email' => $order->get_billing_email(), 'limit' => 2 ) ) );
	}

	return ( $orders_count > 1 ? 0 : $affiliate_id );

}


/**
 * Adds a note to the order when a commission is inserted into the database.
 *
 * @param int   $commission_id
 * @param array $commission_data
 *
 */
function slicewp_add_order_note_woo( $commission_id, $commission_data ) {

	if ( empty( $commission_data['origin'] ) || $commission_data['origin'] != 'woo' ) {
		return;
	}

	if ( empty( $commission_data['reference'] ) ) {
		return;
	}

	$order = wc_get_order( absint( $commission_data['reference'] ) );

	if ( ! $order ) {
		return;
	}

	$order->add_order_note( sprintf( __( 'A %s commission (%s) has been registered for affiliate %s (%s)', 'slicewp' ),
		slicewp_format_amount( $commission_data['amount'], $commission_data['currency'] ),
		'<a href="' . esc_url( add_query_arg( array( 'page' => 'slicewp-commissions', 'subpage' => 'edit-commission', 'commission_id' => absint( $commission_id ) ), admin_url( 'admin.php' ) ) ) . '">' . '#' . absint( $commission_id ) . '</a>',
		slicewp_get_affiliate_name( absint( $commission_data['affiliate_id'] ) ),
		'<a href="' . esc_url( add_query_arg( array( 'page' => 'slicewp-affiliates', 'subpage' => 'edit-affiliate', 'affiliate_id' => absint( $commission_data['affiliate_id'] ) ), admin_url( 'admin.php' ) ) ) . '">' . '#' . absint( $commission_data['affiliate_id'] ) . '</a>'
	) );

}


/**
 * Adds the reference amount in the commission data.
 * 
 * @todo - This should only be updated if the commission has no reference amount data already saved.
 * 
 * @param array $commission_data
 * 
 * @return array
 * 
 */
function slicewp_add_commission_data_reference_amount_woo( $commission_data ) {

	if ( ! ( doing_action( 'slicewp_admin_action_add_commission' ) || doing_action( 'slicewp_admin_action_update_commission' ) ) ) {
		return $commission_data;
	}

	// Check if the origin is WooCommerce.
	if ( 'woo' != $commission_data['origin'] ) {
		return $commission_data;
	}

	// Check if we have a reference.
	if ( empty( $commission_data['reference'] ) ) {
		return $commission_data;
	}

	// Get the order.
	$order = wc_get_order( $commission_data['reference'] );

	if ( empty( $order ) ) {
		return $commission_data;
	}

	// Save the reference amount.
	$commission_data['reference_amount'] = slicewp_sanitize_amount( $order->get_total() );

	// Return the updated commission data.
	return $commission_data;

}


/**
 * Add the rewrite rules for the WooCommerce 'Shop' page
 * 
 **/
function slicewp_add_rewrite_rules_woo() {

	// Get the page id
	$shop_page_id = wc_get_page_id( 'shop' );

	if ( $shop_page_id > 0 ) {

		// Get the page slug
		$shop_slug = get_post_field( 'post_name', $shop_page_id );

		// Get the affiliate keyword
		$keyword = slicewp_get_setting( 'affiliate_keyword' );

		// Add the rewrite rule
		add_rewrite_rule( $shop_slug . '/' . $keyword . '(/(.*))?/?$', 'index.php?post_type=product&' . $keyword . '=$matches[1]', 'top' );

	}

}


/**
 * Returns an array of products data from WooCommerce to populate the "slicewp_action_ajax_get_products" AJAX callback.
 * 
 * @param array $products
 * 
 * @return array
 * 
 */
function slicewp_action_ajax_get_products_woo( $products, $args = array() ) {

	if ( empty( $args['origin'] ) || $args['origin'] != 'woo' ) {
		return $products;
	}

	if ( empty( $args['search_term'] ) ) {
		return $products;
	}

	/**
	 * @todo - Filter to just a selected product types.
	 * 
	 */
	$data_store = WC_Data_Store::load( 'product' );
	$ids        = array_filter( $data_store->search_products( wc_clean( $args['search_term'] ), '', true, true ) );

	if ( empty( $ids ) ) {
		return $products;
	}

	$_products = wc_get_products( array( 'include' => $ids, 'limit' => -1, 'status' => 'publish' ) );

	foreach ( $_products as $_product ) {

		$products[] = array(
			'origin' => 'woo',
			'id'   	 => $_product->get_id(),
			'name'   => $_product->get_name()
		);

	}

	return $products;

}
add_filter( 'slicewp_action_ajax_get_products', 'slicewp_action_ajax_get_products_woo', 10, 2 );