<?php

defined( 'ABSPATH' ) or exit;

/*
 *  PD WooCommerce Customer Order History
 */

if ( ! class_exists( 'PD_Customer_Order_History' ) ) {

    class PD_Customer_Order_History extends WP_List_Table {

        /**
         * Store customer information
         *
         * @since 1.0.0
         * @access protected
         * @var array $items
         */
        public $items = array();

        /**
         * Store order information
         *
         * @since 1.0.0
         * @access protected
         * @var array $last_orders
         */
        protected $last_orders = array();

        /*
         *  Constructor
         */
        public function __construct() {
            parent::__construct();
        }
		/**
		* Get the columns to be displayed in the table.
		 * @since  1.0.0
		 * @access public
		 * @return array
		 */
        public function get_columns() {
            $columns = array(
                'order_date' => esc_html__( 'Date', 'puredevs-customer-history-for-woocommerce'),
                'order_id'    => esc_html__( 'Order ID', 'puredevs-customer-history-for-woocommerce'),
                'customer_name'      => esc_html__( 'Customer Name', 'puredevs-customer-history-for-woocommerce'),
                'items_qty'      => esc_html__( 'Items & Qty', 'puredevs-customer-history-for-woocommerce'),
                'order_total'      => esc_html__( 'Total', 'puredevs-customer-history-for-woocommerce'),
                'payment_method'      => esc_html__( 'Payment Method', 'puredevs-customer-history-for-woocommerce'),
                'order_status'      => esc_html__( 'Status', 'puredevs-customer-history-for-woocommerce'),
            );
            return $columns;
        }

		/**
		 * Prepares the items for display in the customer order history table.
		 *
		 * - Sets up column headers, sortable columns, and hidden columns.
		 * - Handles search and advanced search terms (premium feature).
		 * - Retrieves the latest WooCommerce orders based on search criteria.
		 * - For each order:
		 *   - Checks if the order is a parent order.
		 *   - Retrieves order details, including items, customer info, and order status.
		 *   - Formats customer name and links to customer profile if available.
		 *   - Formats order date, ID, items, total, payment method, and status for display.
		 * - Sorts the orders using the custom sort function.
		 * - Handles pagination based on user settings or default value.
		 * - Slices the orders array for the current page and assigns to the table items.
		 *
		 * @return void
		 */
        public function prepare_items() {
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable);

            $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';

            $search = sanitize_text_field( $search );

            $advanceSearchTerms = array();
			$allowed_order_search_keys = array( 'start_date', 'end_date', 'total_amount', 'total_order_input', 'order_status', 'payment_method', 's', 'show-items' );
			foreach ( $allowed_order_search_keys as $key ) {
				if ( isset( $_GET[ $key ] ) && '' !== $_GET[ $key ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$advanceSearchTerms[ $key ] = sanitize_text_field( wp_unslash( $_GET[ $key ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
			}
            $latest_orders = $this->pdwchs_get_latest_orders( $search, $advanceSearchTerms );
			$data_sync_enabled = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );
            if ( !empty($latest_orders) ) :
                foreach ( $latest_orders as $order ) :
                    if ( wp_get_post_parent_id( $order->ID ) == 0 ) :
                        $order = new WC_Order( $order->ID );

						// Filter by order status
						if ( ! empty( $advanceSearchTerms['order_status'] ) ) {
							$selected_status = str_replace( 'wc-', '', $advanceSearchTerms['order_status'] );
							if ( $order->get_status() !== $selected_status ) {
								continue;
							}
						}
						// Filter by payment method
						if ( ! empty( $advanceSearchTerms['payment_method'] ) ) {
							if ( $order->get_payment_method() !== $advanceSearchTerms['payment_method'] ) {
								continue;
							}
						}
						// Filter by total amount
						if ( ! empty( $advanceSearchTerms['total_amount'] ) && isset( $advanceSearchTerms['total_order_input'] ) && '' !== $advanceSearchTerms['total_order_input'] ) {
							$filter_op    = $advanceSearchTerms['total_amount'];
							$filter_value = floatval( $advanceSearchTerms['total_order_input'] );
							$order_total  = floatval( $order->get_total() );
							if ( 'equal' === $filter_op && $order_total != $filter_value ) {
								continue;
							}
							if ( 'greater_then' === $filter_op && $order_total <= $filter_value ) {
								continue;
							}
							if ( 'less_then' === $filter_op && $order_total >= $filter_value ) {
								continue;
							}
						}

                        $order_id = $order->get_id();
                        $items_qty = '';
                        foreach ( $order->get_items() as $line ) {
                            $items_qty .= $line['qty'] . ' x <a href="'.admin_url().'post.php?post=' . $line['product_id'] . '&action=edit" class="">' . $line['name'] . '</a><br />';
                        }

						if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
							$cus_id = get_post_meta( $order_id, '_customer_user', true );
						}else{
							$cus_id = $order->get_customer_id();
						}

                        $customer = new WC_Customer( $cus_id );
                        $name = ( $customer->get_first_name() != '' || $customer->get_last_name() != '' ) ? esc_html( $customer->get_first_name() . ' ' . $customer->get_last_name() ) : esc_html( $customer->get_display_name() );

                        //$name = $order->get_billing_first_name().' '.$order->get_billing_last_name();

                        if( $order->get_user_id() > 0 ) :
                            $url = admin_url().'admin.php?page=pd-woo-customer&user_id=' . $order->get_user_id();
                            $customer_name = '<span class="dashicons dashicons-admin-users"></span><strong><a href="'.$url.'" target="_blank">'.$name.'</a></strong>';
                        else:
                            $customer_name = '<span class="dashicons dashicons-businessman"></span><strong>Guest</strong>';
                        endif;

                        $this->last_orders[] = array(
                            'order_date' => '<span class="dashicons dashicons-calendar-alt"></span>
&nbsp;'.get_the_date( '', $order_id ).' '. get_the_time( '', $order_id ),
                            'order_id' => '<a href="'.admin_url().'post.php?post='.$order_id.'&action=edit" class="">#'.$order_id.'</a>',
                            'customer_name' => $customer_name,
                            'items_qty' => $items_qty,
                            'order_total' => $order->get_formatted_order_total(),
                            'payment_method' => $order->get_payment_method(),
                            'order_status' => '<span class="pd-woo-order-status '.str_replace( 'wc-', '', $order->get_status() ).'">'.ucfirst( str_replace( 'wc-', '', $order->get_status() ) ).'</span>',
                        );
                    endif;
                endforeach;
            endif;

            usort( $this->last_orders, array( &$this, 'sort_data' ) );

            $results_per_page = get_option( 'pd-woo-results_per_page' );

            if ( !empty( $_GET['show-items'] ) ) {
                $perPage = sanitize_text_field( $_GET['show-items'] );
            } else {
                $perPage = ! empty( $results_per_page ) ? $results_per_page : 10;
            }
            $perPage = (int) $perPage;

            $currentPage = $this->get_pagenum();
            $totalItems = count( $this->last_orders );
            $this->set_pagination_args( array(
                'total_items' => $totalItems,
                'per_page'    => $perPage,
            ) );
            if ( !empty( $this->last_orders ) ) {
                $this->last_orders = array_slice( $this->last_orders, ( ( $currentPage-1 ) * $perPage ), $perPage );
            }

            $this->items = $this->last_orders;
        }

		/**
		 * Handles the display of custom columns for the customer order history table.
		 *
		 * Depending on the column name, returns the corresponding value from the $item array.
		 * For unrecognized columns, returns a printable representation of the entire $item array for troubleshooting.
		 *
		 * @param array  $item         The current row's data.
		 * @param string $column_name  The name of the column being rendered.
		 *
		 * @return mixed The value to display in the column, or the full array for unknown columns.
		 */
        public function column_default( $item, $column_name ) {
            switch( $column_name ) {
                case 'order_date':
                case 'order_id':
                case 'customer_name':
                case 'items_qty':
                case 'order_total':
                case 'payment_method':
                case 'order_status':
                    return $item[ $column_name ];
                default:
                    return ''; // Unknown column — render nothing (was a debug print_r dump).
            }
        }

		/**
		 * Retrieves the list of sortable columns for the customer order history table.
		 *
		 * Each sortable column is defined as an array containing the column key and a boolean
		 * indicating the default sort order (false for ascending, true for descending).
		 *
		 * @return array Associative array of sortable columns and their sort options.
		 */
        public function get_sortable_columns() {
            $sortable_columns = array(
                'order_date'  => array('order_date',false),
                'order_id' => array('order_id',false),
                'customer_name' => array('customer_name',false),
                'order_total' => array('order_total',false),
                'payment_method' => array('payment_method',false),
                'order_status' => array('order_status',false),
            );
            return $sortable_columns;
        }


		/**
		 * Sorts two data rows based on a specified column and order.
		 *
		 * This method compares two associative arrays ($a and $b) using a column specified by the 'orderby' GET parameter,
		 * defaulting to 'order_date' if not set. The sort direction is determined by the 'order' GET parameter,
		 * defaulting to 'desc' (descending) if not set.
		 *
		 * @param array $a The first data row to compare.
		 * @param array $b The second data row to compare.
		 * @return int Returns < 0 if $a is less than $b, 0 if they are equal, > 0 if $a is greater than $b,
		 *             depending on the specified sort order.
		 */
        private function sort_data( $a, $b ) {
            // Set defaults
            $orderby = 'order_date';
            $order = 'desc';
            // If orderby is set, use this as the sort column
            if ( !empty( $_GET['orderby'] ) ) {
                $orderby = sanitize_text_field( $_GET['orderby'] );
            }
            // If order is set use this as the order
            if ( !empty( $_GET['order'] ) ) {
                $order = sanitize_text_field( $_GET['order'] );
            }
            $result = strcmp( $a[$orderby], $b[$orderby] );
            if ( $order === 'asc' ) {
                return $result;
            }
            return -$result;
        }

        /**
         * Get latest orders.
         *
         * @since    1.0.0
         * @access   public
         */
        public function pdwchs_get_latest_orders( $search, $advanceSearchTerms ) {

			if (is_array($advanceSearchTerms) && !empty($advanceSearchTerms)) {
				$advanceSearchTerms = array_map('sanitize_text_field', wp_unslash($advanceSearchTerms));
			}

            $order_status = array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed', 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed' );

			if (!empty($advanceSearchTerms['start_date'])) :
				$date_query = array(
					array(
						'after' => '' . $advanceSearchTerms['start_date'] . '',
						'before' => '' . $advanceSearchTerms['end_date'] . '',
						'inclusive' => true,
					),
				);
			endif;

			$data_sync_enabled = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );

            $args = array(
                'numberposts' => -1,
                'post_type'   => 'shop_order',
                'post_status' =>  $order_status,
            );
			if (isset($date_query) && !empty($date_query)) {
				$args['date_query'] = $date_query;
			}
			if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
				$latest_orders = get_posts( $args );
			}else{
				$latest_orders = wc_get_orders( $args );
			}
            return $latest_orders;
        }

	    /**
	     * Get last orders.
	     *
	     * @since    1.0.0
	     * @access   public
	     */
	    public static function pdwchs_get_last_orders( $limit = '' ) {
		    $order_status = array( 'pending', 'processing', 'on-hold', 'completed', 'cancelled', 'refunded', 'failed', 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed' );
			$data_sync_enabled = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );
			if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
				$args = array(
					'numberposts' => $limit,
					'post_type'   => 'shop_order',
					'post_status' =>  $order_status,
				);
				$last_orders = get_posts( $args );
			}else{
				$args = array(
					'status' => $order_status,
					'limit' => $limit + 1,
				);
				$last_orders = wc_get_orders($args);
			}
		    return $last_orders;
	    }

    }

}
