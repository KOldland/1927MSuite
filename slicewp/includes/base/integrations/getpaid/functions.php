<?php

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Includes the GetPaid files
 *
 */
function slicewp_include_files_gpd() {

	// Get legend dir path
	$dir_path = plugin_dir_path( __FILE__ );

	// Include main class
	if( file_exists( $dir_path . 'class-integration-getpaid.php' ) )
		include $dir_path . 'class-integration-getpaid.php';

	// Include hooks functions
	if( slicewp_is_integration_active( 'gpd' ) && slicewp_is_integration_plugin_active( 'gpd' ) ) {

		if( file_exists( $dir_path . 'functions-hooks-integration-getpaid.php' ) )
			include $dir_path . 'functions-hooks-integration-getpaid.php';
		
	}

}
add_action( 'slicewp_include_files_late', 'slicewp_include_files_gpd' );


/**
 * Register the class that handles GetPaid related actions
 *
 * @param array $integrations
 *
 * @return array
 *
 */
function slicewp_register_integration_gpd( $integrations ) {

	$integrations['gpd'] = 'SliceWP_Integration_GetPaid';

	return $integrations;

}
add_filter( 'slicewp_register_integration', 'slicewp_register_integration_gpd', 30 );


/**
 * Verifies if GetPaid is active
 *
 * @param bool $is_active
 *
 * @return bool
 *
 */
function slicewp_is_integration_plugin_active_gpd( $is_active = false ) {

	if ( class_exists( 'WPInv_Plugin' ) )
		$is_active = true;
	
	return $is_active;

}
add_filter( 'slicewp_is_integration_plugin_active_gpd', 'slicewp_is_integration_plugin_active_gpd' );


/**
 * Returns the taxes of a GetPaid item.
 * 
 * @param WPInv_Item    $item
 * @param WPInv_Invoice $invoice
 * 
 * @return float
 * 
 */
function slicewp_process_item_tax_gpd( $invoice_item, $invoice ) {

	// Compute item taxes.
	$rates   = getpaid_get_item_tax_rates( $invoice_item, $invoice->get_country(), $invoice->get_state() );
	$rates   = getpaid_filter_item_tax_rates( $invoice_item, $rates );
	$taxes   = getpaid_calculate_item_taxes( getpaid_get_taxable_amount( $invoice_item, false ), $rates );
	$r_taxes = getpaid_calculate_item_taxes( getpaid_get_taxable_amount( $invoice_item, true ), $rates );

	$item_taxes = array();

	foreach ( $taxes as $name => $tax_amount ) {
		
		$recurring = isset( $r_taxes[ $name ] ) ? $r_taxes[ $name ] : 0;
		$tax       = getpaid_prepare_item_tax( $invoice_item, $name, $tax_amount, $recurring );

		if ( ! isset( $item_taxes[ $name ] ) ) {
			$item_taxes[ $name ] = $tax;
			continue;
		}

		$item_taxes[ $name ]['initial_tax']   = $tax['initial_tax'];
		$item_taxes[ $name ]['recurring_tax'] = $tax['recurring_tax'];

	}

	$initial_tax   = array_sum( wp_list_pluck( $item_taxes, 'initial_tax' ) );
	$recurring_tax = array_sum( wp_list_pluck( $item_taxes, 'recurring_tax' ) );

	$taxes = $invoice->is_renewal() ? $recurring_tax : $initial_tax;

	return $taxes;

}


/**
 * Returns the amount of an GetPaid item after substracting the discount and the taxes.
 * 
 * @param WPInv_Item    $item
 * @param WPInv_Invoice $invoice
 * 
 * @return float
 * 
 */
function slicewp_process_item_amount_gpd( $invoice_item, $invoice ) {

	// Get the item price.
	$amount = $invoice_item->get_price() * absint( $invoice_item->get_quantity( 'edit' ) );

	// Apply discount if we have any.
	if ( ! empty( $invoice_item->item_discount ) ) {
		$amount -= $invoice_item->item_discount;
	}

	// Apply taxes if we have any.
	if ( wpinv_use_taxes() ) {

		// Compute the taxes.
		$taxes = slicewp_process_item_tax_gpd( $invoice_item, $invoice );

		if ( slicewp_get_setting( 'exclude_tax', false ) && wpinv_prices_include_tax() ) {
			
			// Exclude taxes from commission calculation.
			$amount -= round( $taxes, 2 );

		} elseif ( ! slicewp_get_setting( 'exclude_tax', false ) && ! wpinv_prices_include_tax() ) {

			// Include tax in commission calculation.
			$amount += round( $taxes, 2 );

		}

	}

	return $amount;

}