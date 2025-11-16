<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Includes the Easy Digital Downloads files
 *
 */
function slicewp_include_files_edd() {

	// Get legend dir path
	$dir_path = plugin_dir_path( __FILE__ );

	// Include main class
	if( file_exists( $dir_path . 'class-integration-easy-digital-downloads.php' ) )
		include $dir_path . 'class-integration-easy-digital-downloads.php';

	// Include hooks functions
	if( slicewp_is_integration_active( 'edd' ) && slicewp_is_integration_plugin_active( 'edd' ) ) {

		if( file_exists( $dir_path . 'functions-hooks-integration-easy-digital-downloads.php' ) )
			include $dir_path . 'functions-hooks-integration-easy-digital-downloads.php';
		
	}

}
add_action( 'slicewp_include_files_late', 'slicewp_include_files_edd' );


/**
 * Register the class that handles EDD related actions
 *
 * @param array $integrations
 *
 * @return array
 *
 */
function slicewp_register_integration_edd( $integrations ) {

	$integrations['edd'] = 'SliceWP_Integration_Easy_Digital_Downloads';

	return $integrations;

}
add_filter( 'slicewp_register_integration', 'slicewp_register_integration_edd', 20 );


/**
 * Verifies if Easy Digital Downloads is active
 *
 * @param bool $is_active
 *
 * @return bool
 *
 */
function slicewp_is_integration_plugin_active_edd( $is_active = false ) {

	if( class_exists( 'Easy_Digital_Downloads' ) )
		$is_active = true;

	return $is_active;

}
add_filter( 'slicewp_is_integration_plugin_active_edd', 'slicewp_is_integration_plugin_active_edd' );


/**
 * Adds additional commission types for Easy Digital Downloads
 *
 * @param array $supports
 *
 * @return array
 *
 */
function slicewp_integration_supports_edd( $supports ) {

    // Add subscription commission type if Easy Digital Downloads - Recurring Payments is active
	if( defined( 'EDD_RECURRING_VERSION' ) )
        $supports['commission_types'][] = 'subscription';

    return $supports;

}
add_filter( 'slicewp_integration_supports_edd', 'slicewp_integration_supports_edd' );


/**
 * Outputs the Easy Digital Downloads view for the commission items in the affiliate account commissions list table.
 *
 * This function resides in this file because we always want to load it. In case the Easy Digital Downloads integration is disabled,
 * the functions-hooks-integrations-easy-digital-downloads.php file does not get loaded, so this function cannot reside in that file,
 * as it would lead to missing information in the affiliate account.
 * 
 * @param array $item
 * 
 */
function slicewp_output_list_table_commission_items_edd( $item ) {

	if ( $item['origin'] != 'edd' ) {
		return;
	}

	if ( ! in_array( $item['type'], array( 'sale', 'subscription', 'sale_lifetime', 'recurring' ) ) ) {
		return;
	}

	// Get is commission basis per order.
	$is_commission_basis_per_order = slicewp_get_commission_meta( absint( $item['id'] ), '_is_commission_basis_per_order', true );

	if ( empty( $is_commission_basis_per_order ) ) {

		// Get commission items.
		$commission_items = slicewp_get_commission_meta( absint( $item['id'] ), '__commission_items', true );

		if ( empty( $commission_items ) ) {
			return;
		}

		echo '<h4>' . esc_html( count( $commission_items ) > 1 ? __( 'Items', 'slicewp' ) : __( 'Item', 'slicewp' ) ). '</h4>';

		echo '<table class="slicewp-list-table slicewp-list-table-commission-items">';

			echo '<thead>';
				echo '<tr>';
					echo '<th class="slicewp-column-item-name">' . __( 'Name', 'slicewp' ) . '</th>';
					echo '<th class="slicewp-column-item-commission-amount">' . __( 'Commission', 'slicewp' ) . '</th>';
				echo '</tr>';
			echo '</thead>';

			echo '<tbody>';

				foreach ( $commission_items as $commission_item ) {

					echo '<tr>';

						// Item name.
						echo '<td class="slicewp-table-td slicewp-column-item-name">';

							// Item name.
							echo esc_html( $commission_item['transaction_item']['name'] );
						
							// Item quantity.
							if ( $commission_item['commissionable_quantity'] > 1 ) {
								echo ' &times; ';
								echo absint( $commission_item['commissionable_quantity'] );
							}

						echo '</td>';

						// Item commission amount.
						echo '<td class="slicewp-table-td slicewp-column-item-commission-amount">';
							echo slicewp_format_amount( $commission_item['amount'], $item['currency'] );
						echo '</td>';

					echo '</tr>';

				}

			echo '</tbody>';

		echo '</table>';

	} else {

		// Get transaction.
		$transaction_data = slicewp_get_commission_meta( absint( $item['id'] ), '__transaction_data', true );

		if ( empty( $transaction_data['items'] ) ) {
			return;
		}

		echo '<h4>' . esc_html( count( $transaction_data['items'] ) > 1 ? __( 'Items', 'slicewp' ) : __( 'Item', 'slicewp' ) ). '</h4>';

		echo '<table class="slicewp-list-table slicewp-list-table-commission-items">';

			echo '<thead>';
				echo '<tr>';
					echo '<th class="slicewp-column-item-name">' . __( 'Name', 'slicewp' ) . '</th>';
				echo '</tr>';
			echo '</thead>';

			echo '<tbody>';

				foreach ( $transaction_data['items'] as $transaction_item ) {

					echo '<tr>';

						// Item name.
						echo '<td class="slicewp-table-td slicewp-column-item-name">';

							// Item name.
							$product_url  = ( ! empty( $transaction_item['meta_data']['product_id'] ) ? get_permalink( $transaction_item['meta_data']['product_id'] ) : '' );
							$product_name = ( $transaction_item['type'] != 'shipping' ? $transaction_item['name'] : __( 'Shipping', 'slicewp' ) );

							if ( ! empty( $product_url ) ) {
								echo '<a href="' . esc_url( $product_url ) . '">';
									echo esc_html( $product_name );
								echo '</a>';
							} else {
								echo esc_html( $product_name );
							}
						
							// Item quantity.
							if ( $transaction_item['quantity'] > 1 ) {
								echo ' &times; ';
								echo absint( $transaction_item['quantity'] );
							}

						echo '</td>';

					echo '</tr>';

				}

			echo '</tbody>';

		echo '</table>';

	}

}
add_action( 'slicewp_list_table_output_item_details_affiliate_account_commissions', 'slicewp_output_list_table_commission_items_edd', 50 );