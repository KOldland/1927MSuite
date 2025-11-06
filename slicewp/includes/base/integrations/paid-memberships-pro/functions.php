<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Includes the Paid Memberships Pro files.
 *
 */
function slicewp_include_files_pmpro() {

	// Get legend dir path.
	$dir_path = plugin_dir_path( __FILE__ );

	// Include main class.
	if ( file_exists( $dir_path . 'class-integration-paid-memberships-pro.php' ) ) {
		include $dir_path . 'class-integration-paid-memberships-pro.php';
	}

	// Include hooks functions.
	if ( slicewp_is_integration_active( 'pmpro' ) && slicewp_is_integration_plugin_active( 'pmpro' ) ) {

		if ( file_exists( $dir_path . 'functions-hooks-integration-paid-memberships-pro.php' ) ) {
			include $dir_path . 'functions-hooks-integration-paid-memberships-pro.php';
		}

	}

}
add_action( 'slicewp_include_files', 'slicewp_include_files_pmpro' );


/**
 * Register the class that handles PMPRO related actions.
 *
 * @param array $integrations
 *
 * @return array
 *
 */
function slicewp_register_integration_pmpro( $integrations ) {

	$integrations['pmpro'] = 'SliceWP_Integration_Paid_Memberships_Pro';

	return $integrations;

}
add_filter( 'slicewp_register_integration', 'slicewp_register_integration_pmpro', 40 );


/**
 * Verifies if Paid Memberships Pro is active.
 *
 * @param bool $is_active
 *
 * @return bool
 *
 */
function slicewp_is_integration_plugin_active_pmpro( $is_active = false ) {

	if ( defined( 'PMPRO_VERSION' ) ) {
		$is_active = true;
	}

	return $is_active;

}
add_filter( 'slicewp_is_integration_plugin_active_pmpro', 'slicewp_is_integration_plugin_active_pmpro' );


/**
 * Outputs the Paid Memberships Pro view for the commission items in the affiliate account commissions list table.
 *
 * This function resides in this file because we always want to load it. In case the Paid Memberships Pro integration is disabled,
 * the functions-hooks-integrations-paid-memberships-pro.php file does not get loaded, so this function cannot reside in that file,
 * as it would lead to missing information in the affiliate account.
 * 
 * @param array $item
 * 
 */
function slicewp_output_list_table_commission_items_pmpro( $item ) {

	if ( $item['origin'] != 'pmpro' ) {
		return;
	}

	if ( ! in_array( $item['type'], array( 'sale', 'subscription', 'sale_lifetime', 'recurring' ) ) ) {
		return;
	}

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
						echo esc_html( $commission_item['transaction_item']['name'] );
					echo '</td>';

					// Item commission amount.
					echo '<td class="slicewp-table-td slicewp-column-item-commission-amount">';
						echo slicewp_format_amount( $commission_item['amount'], $item['currency'] );
					echo '</td>';

				echo '</tr>';

			}

		echo '</tbody>';

	echo '</table>';

}
add_action( 'slicewp_list_table_output_item_details_affiliate_account_commissions', 'slicewp_output_list_table_commission_items_pmpro', 50 );