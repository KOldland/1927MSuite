<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Includes the files needed for the commission admin area.
 *
 */
function slicewp_include_files_admin_commission() {

	// Get commission admin dir path.
	$dir_path = plugin_dir_path( __FILE__ );

	// Include submenu page.
	if ( file_exists( $dir_path . 'class-submenu-page-commissions.php' ) ) {
		include $dir_path . 'class-submenu-page-commissions.php';
	}

	// Include actions.
	if ( file_exists( $dir_path . 'functions-actions-commissions.php' ) ) {
		include $dir_path . 'functions-actions-commissions.php';
	}

	// Include commissions list table.
	if ( file_exists( $dir_path . 'class-list-table-commissions.php' ) ) {
		include $dir_path . 'class-list-table-commissions.php';
	}

}
add_action( 'slicewp_include_files', 'slicewp_include_files_admin_commission' );


/**
 * Register the Commission admin submenu page.
 *
 */
function slicewp_register_submenu_page_commissions( $submenu_pages ) {

	if ( ! is_array( $submenu_pages ) ) {
		return $submenu_pages;
	}

	$submenu_pages['commissions'] = array(
		'class_name' => 'SliceWP_Submenu_Page_Commissions',
		'data' 		 => array(
			'page_title' => __( 'Commissions', 'slicewp' ),
			'menu_title' => __( 'Commissions', 'slicewp' ),
			'capability' => apply_filters( 'slicewp_submenu_page_capability_commissions', 'manage_options' ),
			'menu_slug'  => 'slicewp-commissions'
		)
	);

	return $submenu_pages;

}
add_filter( 'slicewp_register_submenu_page', 'slicewp_register_submenu_page_commissions', 25 );


/**
 * Capture the commission status in a global before it gets updated. This global value is used mainly by email notifications.
 *
 * Ideally, we should have a better method of detecting an object's status change, but for alternative will work.
 *
 * @param int $commission_id
 *
 */
function slicewp_set_pre_update_previous_commission_status_global( $commission_id = 0 ) {

	// Verify received arguments not to be empty.
	if ( empty( $commission_id ) ) {
		return;
	}

	// Get the commission that will be updated.
	$commission = slicewp_get_commission( $commission_id );

	if ( empty( $commission ) ) {
		return;
	}

	// Save the previous commission status.
	slicewp()->globals()->set( 'pre_update_commission_status_' . $commission_id, $commission->get( 'status' ) );

}
add_action( 'slicewp_pre_update_commission', 'slicewp_set_pre_update_previous_commission_status_global' );


/**
 * Outputs the commission items at the bottom of the edit commission page.
 * 
 */
function slicewp_output_view_commissions_edit_commission_bottom_commission_items() {

	$commission_id = ( ! empty( $_GET['commission_id'] ) ? absint( $_GET['commission_id'] ) : 0 );

	if ( empty( $commission_id ) ) {
		return;
	}

	$commission = slicewp_get_commission( $commission_id );

	if ( empty( $commission ) ) {
		return;
	}

	// Get is commission basis per order.
	$is_commission_basis_per_order = slicewp_get_commission_meta( $commission_id, '_is_commission_basis_per_order', true );

	if ( empty( $is_commission_basis_per_order ) ) {

		$commission_items = slicewp_get_commission_meta( $commission_id, '__commission_items', true );

		if ( empty( $commission_items ) ) {
			return;
		}

	} else {

		// Get transaction.
		$transaction_data = slicewp_get_commission_meta( $commission_id, '__transaction_data', true );

		if ( empty( $transaction_data['items'] ) ) {
			return;
		}

	}

	// As this is an experimental feature, we'll output the styles here.
	echo '<style>';
		echo '#slicewp-card-commission-items table .slicewp-column-item-commissionable-amount { width: 27.5%; }';
		echo '#slicewp-card-commission-items table .slicewp-column-item-commission-amount { width: 27.5%; }';
		echo '#slicewp-card-commission-items table tbody td { padding-top: 15px; padding-bottom: 15px; }';
	echo '</style>';

	// Output the commission items card.
	echo '<div id="slicewp-card-commission-items" class="slicewp-card">';

		/**
		 * @todo - Add an info button with a tooltip letting users know this is an experimental feature
		 * 		   and they should contact if something isn't working normally.
		 * 
		 */

		echo '<div class="slicewp-card-header">';
			echo '<span class="slicewp-card-title">' . __( 'Commission Items', 'slicewp' ) . '</span>';
		echo '</div>';

		echo '<div class="slicewp-card-inner">';

			echo '<table class="slicewp-card-table-full-width">';

				if ( empty( $is_commission_basis_per_order ) ) {

					echo '<thead>';
						echo '<th class="slicewp-column-item-name">' . __( 'Item', 'slicewp' ) . '</th>';
						echo '<th class="slicewp-column-item-commissionable-amount">' . __( 'Commissionable Amount', 'slicewp' ) . '</th>';
						echo '<th class="slicewp-column-item-commission">' . __( 'Commission', 'slicewp' ) . '</th>';
					echo '</thead>';

					echo '<tbody>';

						foreach ( $commission_items as $commission_item ) {

							echo '<tr>';

								// Item name.
								echo '<td class="slicewp-column-item-name">';
	
									// Item name.
									$item_admin_url = ( ! empty( $commission_item['transaction_item']['meta_data']['product_id'] ) ? slicewp()->integrations[$commission->get( 'origin' )]->get_product_admin_url( $commission_item['transaction_item']['meta_data']['product_id'] ) : '' );
									$item_name 		= ( $commission_item['transaction_item']['type'] != 'shipping' ? $commission_item['transaction_item']['name'] : __( 'Shipping', 'slicewp' ) );
	
									if ( ! empty( $item_admin_url ) ) {
										echo '<a href="' . esc_url( $item_admin_url ) . '">';
											echo esc_html( $item_name );
										echo '</a>';
									} else {
										echo esc_html( $item_name );
									}
								
									// Item quantity.
									if ( $commission_item['commissionable_quantity'] > 1 ) {
										echo ' &times; ';
										echo absint( $commission_item['commissionable_quantity'] );
									}
	
								echo '</td>';
	
								// Item commissionable amount.
								echo '<td class="slicewp-column-item-commissionable-amount">';
									echo slicewp_format_amount( $commission_item['commissionable_amount'], $commission->get( 'currency' ) );
								echo '</td>';
	
								// Item commission amount.
								echo '<td class="slicewp-column-item-commission-amount">';
									echo slicewp_format_amount( $commission_item['amount'], $commission->get( 'currency' ) );
								echo '</td>';
							
							echo '</tr>';
	
						}

					echo '</tbody>';

				} else {

					echo '<thead>';
						echo '<th class="slicewp-column-item-name">' . __( 'Item', 'slicewp' ) . '</th>';
					echo '</thead>';

					echo '<tbody>';

						foreach ( $transaction_data['items'] as $transaction_item ) {

							echo '<tr>';

								// Item name.
								echo '<td class="slicewp-table-td slicewp-column-item-name">';

									// Item name.
									$item_admin_url = ( ! empty( $transaction_item['meta_data']['product_id'] ) ? slicewp()->integrations[$commission->get( 'origin' )]->get_product_admin_url( $transaction_item['meta_data']['product_id'] ) : '' );
									$item_name 		= ( $transaction_item['type'] != 'shipping' ? $transaction_item['name'] : __( 'Shipping', 'slicewp' ) );

									if ( ! empty( $item_admin_url ) ) {
										echo '<a href="' . esc_url( $item_admin_url ) . '">';
											echo esc_html( $item_name );
										echo '</a>';
									} else {
										echo esc_html( $item_name );
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

				}
				
			echo '</table>';

		echo '</div>';

	echo '</div>';

}
add_action( 'slicewp_view_commissions_edit_commission_bottom', 'slicewp_output_view_commissions_edit_commission_bottom_commission_items' );