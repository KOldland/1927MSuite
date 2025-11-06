<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * List table class outputter for Payouts.
 *
 */
Class SliceWP_WP_List_Table_Payouts extends SliceWP_WP_List_Table {

	/**
	 * The number of payouts that should appear in the table.
	 *
	 * @access private
	 * @var int
	 *
	 */
	private $items_per_page;

	/**
	 * The data of the table.
	 *
	 * @access public
	 * @var array
	 *
	 */
	public $data = array();


	/**
	 * Constructor.
	 *
	 */
	public function __construct() {

		parent::__construct( array(
			'plural' 	=> 'slicewp_payouts',
			'singular' 	=> 'slicewp_payout',
			'ajax' 		=> false
		));

		$this->items_per_page = 10;
		$this->paged 		  = ( ! empty( $_GET['paged'] ) ? (int)$_GET['paged'] : 1 );

		$this->set_pagination_args( array(
            'total_items' => slicewp_get_payouts( array( 'number' => -1 ), true ),
			'per_page'    => $this->items_per_page
        ));

		// Get and set table data.
		$this->set_table_data();
		
		// Add column headers and table items.
		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->items 		   = $this->data;
	
    }


	/**
	 * Get a list of CSS classes for the table tag.
	 *
	 * @return array
	 * 
	 */
	protected function get_table_classes() {

		return array( 'striped', $this->_args['plural'] );

	}


	/**
	 * Returns all the columns for the table.
	 *
	 */
	public function get_columns() {

		$columns = array(
            'id' 		   	 => __( 'ID', 'slicewp' ),
            'total'		 	 => __( 'Total', 'slicewp '),
			'payments'		 => __( 'Payments', 'slicewp' ),
			'date_created'	 => __( 'Date', 'slicewp' ),
			'notes'			 => '<span class="dashicons dashicons-admin-comments" title="' . __( 'Notes', 'slicewp' ) . '"></span>',
			'actions'		 => ''
		);

		/**
		 * Filter the columns of the payouts table.
		 *
		 * @param array $columns
		 *
		 */
		return apply_filters( 'slicewp_list_table_payouts_columns', $columns );

	}

    
	/**
	 * Returns all the sortable columns for the table.
	 *
	 */
	public function get_sortable_columns() {

		$columns = array(
			'id'		=> array( 'id', false ),
			'amount'	=> array( 'amount', false)
        );

		/**
		 * Filter the sortable columns of the visits table.
		 *
		 * @param array $columns
		 *
		 */
		return apply_filters( 'slicewp_list_table_payouts_sortable_columns', $columns );

	}


	/**
	 * Gets the payouts data and sets it.
	 *
	 */
	private function set_table_data() {

		$payout_args = array(
			'number'  => $this->items_per_page,
			'offset'  => ( $this->get_pagenum() - 1 ) * $this->items_per_page,
			'orderby' => ( ! empty( $_GET['orderby'] ) ? sanitize_text_field( $_GET['orderby'] ) : 'id' ),
			'order'	  => ( ! empty( $_GET['order'] ) ? sanitize_text_field( $_GET['order'] ) : 'desc' )
		);

		$payouts = slicewp_get_payouts( $payout_args );
		
		if ( empty( $payouts ) ) {
			return;
		}

		foreach ( $payouts as $payout ) {
			
			$row_data = $payout->to_array();
			
			/**
			 * Filter the payout row data.
			 *
			 * @param array				$row_data
			 * @param SliceWP_Payout	$payout
			 *
			 */
			$row_data = apply_filters( 'slicewp_list_table_payouts_row_data', $row_data, $payout );

			$this->data[] = $row_data;

		}
		
	}


	/**
	 * Returns the HTML that will be displayed in the "date_created" column.
	 *
	 * @param array $item - data for the current row
	 *
	 * @return string
	 *
	 */
	public function column_date_created( $item ) {

		$output = slicewp_date_i18n( $item['date_created'] );

		return $output;

	}


	/**
	 * Returns the HTML that will be displayed in the "total" column.
	 *
	 * @param array $item - data for the current row
	 *
	 * @return string
	 *
	 */
	public function column_total( $item ) {

		$output = slicewp_format_amount( $item['amount'], slicewp_get_setting( 'active_currency', 'USD' ) );

		return $output;

	}


	/**
	 * Returns the HTML that will be displayed in the "payments" column.
	 *
	 * @param array $item - data for the current row
	 *
	 * @return string
	 *
	 */
	public function column_payments( $item ) {

		// Get total and paid payments count.
		$payments_all  = slicewp_get_payments( array( 'payout_id' => absint( $item['id'] ), 'fields' => 'amount' ) );
		$payments_paid = slicewp_get_payments( array( 'payout_id' => absint( $item['id'] ), 'fields' => 'amount', 'status'	=> 'paid' ) );

		// Compute the paid percentages.
		$paid_percentage = ( count( $payments_all ) != 0 ? round( count( $payments_paid ) / count( $payments_all ) * 100 ) : 0 );

		$output = '<div class="slicewp-tooltip-wrapper">';
			
			$output .= '<div>';
				$output .= '<div style="display: flex; align-items: center; justify-content: space-between;">';
					$output .= '<span>' . __( 'Payments paid', 'slicewp' ) . ': ' . count( $payments_paid ) . ' / ' . count( $payments_all ) . '</span>';
					$output .= '<svg class="slicewp-tooltip-icon" height="18" width="18" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24"><g><path d="M13 9h-2V7h2v2zm0 2h-2v6h2v-6zm-1-7c-4.41 0-8 3.59-8 8s3.59 8 8 8 8-3.59 8-8-3.59-8-8-8m0-2c5.523 0 10 4.477 10 10s-4.477 10-10 10S2 17.523 2 12 6.477 2 12 2z"></path></g></svg>';
				$output .= '</div>';

				$output .= slicewp_output_progressbar( $paid_percentage, true );
			$output .= '</div>';

			$output .= '<span class="slicewp-tooltip-message">';
				
				$output .= '<p>' . sprintf( __( "This bulk payout contains %d payment(s) totaling %s.", 'slicewp' ), count( $payments_all ), slicewp_format_amount( array_sum( $payments_all ), slicewp_get_setting( 'active_currency', 'USD' ) ) ) . '</p>';

				$output .= '<hr />';

				$output .= '<div class="slicewp-grid slicewp-grid-columns-2 slicewp-no-collapse">';

					$output .= '<div class="slicew-grid-item">';
						$output .= '<span>' . __( "Paid", 'slicewp' ) . '</span>';
						$output .= '<div style="font-size: 18px; line-height: 1; margin-top: 10px; margin-bottom: 5px;">' . slicewp_format_amount( array_sum( $payments_paid ), slicewp_get_setting( 'active_currency', 'USD' ) ) . '</div>';
						$output .= '<div>' . ( count( $payments_paid ) != 1 ? sprintf( __( '%d payments', 'slicewp' ), count( $payments_paid ) ) : sprintf( __( '%d payment', 'slicewp' ), count( $payments_paid ) ) ) . '</div>';
					$output .= '</div>';

					$output .= '<div class="slicew-grid-item" style="text-align: right;">';
						$output .= '<span>' . __( "Still to be paid", 'slicewp' ) . '</span>';
						$output .= '<div style="font-size: 18px; line-height: 1; margin-top: 10px; margin-bottom: 5px;">' . slicewp_format_amount( array_sum( $payments_all ) - array_sum( $payments_paid ), slicewp_get_setting( 'active_currency', 'USD' ) ) . '</div>';
						$output .= '<div>' . ( count( $payments_all ) - count( $payments_paid ) != 1 ? sprintf( __( '%d payments', 'slicewp' ), count( $payments_all ) - count( $payments_paid ) ) : sprintf( __( '%d payment', 'slicewp' ), count( $payments_all ) - count( $payments_paid ) ) ) . '</div>';
					$output .= '</div>';

				$output .= '</div>';

				$output .= '<span class="slicewp-tooltip-arrow"></span>';

			$output .= '</span>';
		
		$output .= '</div>';

		return $output;

	}


	/**
	 * Returns the HTML that will be displayed in the "notes" column.
	 *
	 * @param array $item - data for the current row
	 *
	 * @return string
	 *
	 */
	public function column_notes( $item ) {

		$notes_count = slicewp_get_notes( array( 'object_context' => 'payout', 'object_id' => $item['id'] ), true );

		if ( empty( $notes_count ) ) {
			return '-';
		}

		$output = '<span class="slicewp-notes-count">' . absint( $notes_count ) . '</span>';

		return $output;

	}


	/**
	 * Returns the HTML that will be displayed in the "actions" column.
	 *
	 * @param array $item - data for the current row
	 *
	 * @return string
	 *
	 */
	public function column_actions( $item ) {

		/**
		 * Set actions.
		 *
		 */
		$output = '<div class="row-actions">';

			$output .= '<a href="' . esc_url( add_query_arg( array( 'page' => 'slicewp-payouts', 'subpage'=>'view-payout', 'payout_id' => $item['id'] ) , admin_url( 'admin.php' ) ) ) . '" class="slicewp-button-secondary">' . __( 'View', 'slicewp' ) . '</a>';

			// Allow CSV Generation only if the Payout has an amount greater than zero.
			if ( $item['amount'] != 0 ) {
				$output .= '<a href="' . esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'slicewp-payouts', 'subpage' => 'payouts-history', 'slicewp_action' => 'generate_payouts_csv', 'payout_id' => $item['id'] ) , admin_url( 'admin.php' ) ), 'slicewp_generate_payouts_csv', 'slicewp_token' ) ) . '" class="slicewp-button-secondary">' . __( 'Generate CSV', 'slicewp' ) . '</a>';
			} else {
				$output .= '<a href="#" class="slicewp-button-secondary slicewp-disabled" onclick="return false;">' . __( 'Generate CSV', 'slicewp' ) . '</a>';
			}

			// Get paid payments.
			$payments_count = slicewp_get_payments( array( 'number' => -1, 'payout_id' => $item['id'], 'status' => array( 'paid', 'processing' ) ), true );

			// Block deletion in certain circumstances
			if ( $item['originator_user_id'] != get_current_user_id() || $payments_count != 0 ) {

				$title = ( $payments_count != 0 ? __( 'You cannot delete this payout because it contains paid payments.', 'slicewp' ) : __( 'You cannot delete this payout because you are not the one that created it.', 'slicewp' ) );

				$output .= '<span class="slicewp-disabled" title="' . esc_attr( $title ) . '">' . __( 'Delete', 'slicewp' ) . '</span>';

			// Add the delete payout link.
			} else {

				$output .= '<span class="trash"><a onclick="return confirm( \'' . __( "Are you sure you want to delete this payout? All the contained payments will be also deleted!", "slicewp" ) . ' \' )" href="' . esc_url( wp_nonce_url( add_query_arg( array( 'page' => 'slicewp-payouts', 'slicewp_action' => 'delete_payout', 'payout_id' => $item['id'] ) , admin_url( 'admin.php' ) ), 'slicewp_delete_payout', 'slicewp_token' ) ) . '" class="submitdelete">' . __( 'Delete', 'slicewp' ) . '</a></span>';

			}

		$output .= '</div>';

		return $output;

	}


	/**
	 * Returns the HTML that will be displayed in each columns.
	 *
	 * @param array $item 			- data for the current row
	 * @param string $column_name 	- name of the current column
	 *
	 * @return string
	 *
	 */
	public function column_default( $item, $column_name ) {

		return isset( $item[ $column_name ] ) ? $item[ $column_name ] : '-';

    }
	
	/**
	 * HTML display when there are no items in the table.
	 *
	 */
	public function no_items() {

		echo __( 'No payouts found.', 'slicewp' );

	}
}