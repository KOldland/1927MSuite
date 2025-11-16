<?php

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;


/**
 * Class that outputs the "commissions" HTML table for a particular payment from the affiliate account.
 * 
 */
class SliceWP_List_Table_Affiliate_Account_Payment_Commissions extends SliceWP_List_Table {

    /**
     * A string identifying the table.
     * 
     * @access protected
	 * @var    string
     * 
     */
    protected $id = 'affiliate_account_payment_commissions';

    /**
     * Array containing all available commission types.
     * 
     * @access protected
	 * @var    array
     * 
     */
    protected $commission_types = array();

    /**
     * Array containing all available commission statuses.
     * 
     * @access protected
	 * @var    array
     * 
     */
    protected $commission_statuses = array();


    /**
     * Constructor.
     * 
     */
    public function __construct( $args = array() ) {

        parent::__construct( $args );

        $this->table_columns = array(
            'id'        => __( 'ID', 'slicewp' ),
            'amount'    => __( 'Amount', 'slicewp' ),
            'reference' => __( 'Reference', 'slicewp' ),
            'type'      => __( 'Type', 'slicewp' ),
            'date'      => __( 'Date', 'slicewp' )
        );

        $this->commission_types    = slicewp_get_commission_types();
        $this->commission_statuses = slicewp_get_commission_available_statuses();
        $this->no_items            = ( empty( $_GET['list-table-filter-date-start'] ) ? __( 'You have no commissions.', 'slicewp' ) : '' );

        $this->set_table_items_data();

    }


    /**
     * Sets the commissions data.
     * 
     */
    protected function set_table_items_data() {

        if ( empty( $this->args['payment_id'] ) ) {
            return;
        }

        $affiliate_id = slicewp_get_current_affiliate_id();

        // Prepare the commission args.
        $commission_args = array(
            'number'		=> -1,
            'affiliate_id'	=> $affiliate_id,
            'payment_id'    => $this->args['payment_id'],
            'status'		=> array( 'paid', 'unpaid' )
        );

        $this->items_total = slicewp_get_commissions( $commission_args, true );
        $this->items 	   = slicewp_get_commissions( $commission_args );

    }


    /**
     * Column "date".
     * 
     * @param array $item
     * 
     * @return string
     * 
     */
    public function column_date( $item ) {

        return slicewp_date_i18n( $item['date_created'] );

    }


    /**
     * Column "type".
     * 
     * @param array $item
     * 
     * @return string
     * 
     */
    public function column_type( $item ) {

        return ( ! empty( $this->commission_types[$item['type']]['label'] ) ? $this->commission_types[$item['type']]['label'] : $item['type'] );

    }


    /**
     * Column "amount".
     * 
     * @param array $item
     * 
     * @return string
     * 
     */
    public function column_amount( $item ) {

        return slicewp_format_amount( $item['amount'], slicewp_get_setting( 'active_currency', 'USD' ) );

    }


    /**
     * Outputs the pagination elements.
     * 
     */
    public function output_table_pagination() {

        if ( count( $this->items ) == 0 ) {
            return;
        }

        $total_pages = ceil( $this->items_total / $this->items_per_page );

        echo '<div class="slicewp-list-table-pagination">';

        if ( $this->items_total <= count( $this->items ) ) {

            if ( $this->items_total == 1 ) {
                echo '<span>' . __( '1 commission', 'slicewp' ) . '</span>';
            } else {
                echo '<span>' . sprintf( __( '%d commissions', 'slicewp' ), $this->items_total ) . '</span>';
            }

        }

        echo '</div>';

    }


    /**
     * Outputs elements before the table.
     * 
     */
    public function output_table_before() {}


    /**
     * Outputs the per page selector.
     * 
     */
    public function output_per_page_selector() {}

}