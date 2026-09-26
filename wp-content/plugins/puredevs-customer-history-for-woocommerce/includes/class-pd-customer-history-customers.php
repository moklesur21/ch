<?php

defined( 'ABSPATH' ) or exit;

/*
 *  PD WooCommerce Customer Customers
 */

if ( ! class_exists( 'PD_Customer_History_Customers' ) ) {

    class PD_Customer_History_Customers extends WP_List_Table {

        /**
         * Store customer information
         *
         * @since 1.0.0
         * @access protected
         * @var array $items
         */
        public $items = array();

        /**
         * Store customer information
         *
         * @since 1.0.0
         * @access protected
         * @var array $customer_array
         */
        protected $customer_array = array();

        /** Full (uncapped) customer count — set in prepare_items() before scope slice. */
        public $pdwchs_full_customer_count = 0;

        /*
         *  Constructor
         */
        public function __construct() {
            parent::__construct();
        }

        public function pdwchs_get_full_customer_count() {
            return $this->pdwchs_full_customer_count;
        }

        public function get_columns() {
            $columns = array(
                'user' => esc_html__( 'User', 'puredevs-customer-history-for-woocommerce' ),
                'role'    => esc_html__( 'Role', 'puredevs-customer-history-for-woocommerce' ),
            );

            if ( pdwchs_is_pro_active() ) {
                $columns['rfm_segment']     = esc_html__( 'RFM Segment', 'puredevs-customer-history-for-woocommerce' );
                $columns['health_score']    = esc_html__( 'Health Score', 'puredevs-customer-history-for-woocommerce' );
                $columns['lifecycle_stage'] = esc_html__( 'Lifecycle Stage', 'puredevs-customer-history-for-woocommerce' );
                $columns['behavior_tags']   = esc_html__( 'Behaviors', 'puredevs-customer-history-for-woocommerce' );
            }

            $columns = array_merge( $columns, array(
                'orders'      => esc_html__( 'Orders', 'puredevs-customer-history-for-woocommerce' ),
                'pending_orders'      => esc_html__( 'Pending Orders', 'puredevs-customer-history-for-woocommerce' ),
                'refund_orders'      => esc_html__( 'Refunded Orders', 'puredevs-customer-history-for-woocommerce' ),
                'orders_average'      => esc_html__( 'Orders average', 'puredevs-customer-history-for-woocommerce' ),
                'total_spent'      => esc_html__( 'Total Spending', 'puredevs-customer-history-for-woocommerce' ),
                'actions'      => esc_html__( 'Actions', 'puredevs-customer-history-for-woocommerce' ),
            ) );

            /**
             * Filter the Customers & Users list columns.
             *
             * This free build declares no column through this seam itself — it returns exactly the
             * columns it has always returned. The seam exists so a premium build can add one without
             * a free-file edit.
             *
             * It runs last, after the pdwchs_is_pro_active() block above, so a listener sees the array
             * this request would otherwise have rendered: the four intelligence columns are present on
             * a licensed build and absent on a lapse. A listener that wants to sit beside a particular
             * column can therefore find it by key.
             *
             * A listener may add, reorder or remove, because the return value is used directly.
             * Removal is honoured but not encouraged: a listener that removes a column it did not add
             * is breaking someone else's page.
             *
             * A column added here is filled by pdwchs_customers_column_{$column_name} in
             * column_default() below — declaring one without the other renders an empty cell.
             *
             * @since 3.1.2
             *
             * @param array $columns Column key => display label, in render order.
             */
            return apply_filters( 'pdwchs_customers_columns', $columns );
        }
		/**
		 * Prepare the list of items for display.
		 *
		 * @since    1.0.0
		 * @access   public
		 */
        public function prepare_items() {
            $columns = $this->get_columns();
            $hidden = array();
            $sortable = $this->get_sortable_columns();
            $this->_column_headers = array($columns, $hidden, $sortable);

            $search = isset( $_GET['s'] ) ? sanitize_text_field( $_GET['s'] ) : '';
            $advanceSearchTerms = array();

            $customers = $this->pdwchs_woo_get_all_customers( $search, $advanceSearchTerms );

			// Filter by user role
			$user_role_filter = ! empty( $_GET['user_role'] ) ? sanitize_text_field( wp_unslash( $_GET['user_role'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $user_role_filter ) ) {
				foreach ( $customers as $key => $user ) {
					if ( ! in_array( $user_role_filter, (array) $user->roles, true ) ) {
						unset( $customers[ $key ] );
					}
				}
				$customers = array_values( $customers );
			}

			// Filter by billing country
			$country_filter = ! empty( $_GET['customer_country'] ) ? sanitize_text_field( wp_unslash( $_GET['customer_country'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ! empty( $country_filter ) ) {
				foreach ( $customers as $key => $user ) {
					$billing_country = get_user_meta( $user->ID, 'billing_country', true );
					if ( $billing_country !== $country_filter ) {
						unset( $customers[ $key ] );
					}
				}
				$customers = array_values( $customers );
			}

			$data_sync_enabled = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );

            if ( get_option('pd-woo-hide_users_with_no_orders') ) {
                foreach ( $customers as $key => $user) {
					if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
						$order_count = count( get_posts( array(
							'numberposts' => -1,
							'meta_key'    => '_customer_user',
							'meta_value'  => $user->ID,
							'post_type'   => 'shop_order',
							'post_status' =>  'any',
							'post_parent' => '0',
						) ) );
					}else{
						$order_count = wc_get_customer_order_count( $user->ID );
					}
                    if ( ! $order_count > 0 ) {
                        unset( $customers[ $key ] );
                    }
                }
            }

			$total_spent_filter = ! empty( $_GET['total_spent'] ) ? sanitize_text_field( wp_unslash( $_GET['total_spent'] ) ) : '';
			$total_spent_input  = ! empty( $_GET['total_spent_input'] ) ? floatval( $_GET['total_spent_input'] ) : 0;

			if ( ! empty( $total_spent_filter ) && $total_spent_input > 0 ) {
				foreach ($customers as $key => $user) {
					$total_spent = $this->pdwchs_woo_get_customer_total_spent($user->ID);

					if ( $total_spent_filter === 'equal' ) {
						if ($total_spent != $total_spent_input) {
							unset($customers[$key]);
						}
					}
					if ( $total_spent_filter === 'greater_then' ) {
						if ($total_spent <= $total_spent_input) {
							unset($customers[$key]);
						}
					}
					if ( $total_spent_filter === 'less_then' ) {
						if ($total_spent >= $total_spent_input) {
							unset($customers[$key]);
						}
					}
				}
			}

			$order_number_filter = ! empty( $_GET['order_number'] ) ? sanitize_text_field( wp_unslash( $_GET['order_number'] ) ) : '';
			$order_number_input  = ! empty( $_GET['order_number_input'] ) ? absint( $_GET['order_number_input'] ) : 0;

			if ( ! empty( $order_number_filter ) && $order_number_input > 0 ) {

				foreach ($customers as $key => $user) {
					if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
						$order_count = count(get_posts(array(
							'numberposts' => -1,
							'meta_key' => '_customer_user',
							'meta_value' => $user->ID,
							'post_type' => 'shop_order',
							'post_status' => 'any',
							'post_parent' => '0',
						)));
					}else{
						$order_count = wc_get_customer_order_count( $user->ID );
					}

					if ( $order_number_filter === 'equal' ) {
						if ($order_count != $order_number_input) {
							unset($customers[$key]);
						}
					}
					if ( $order_number_filter === 'greater_then' ) {
						if ($order_count <= $order_number_input) {
							unset($customers[$key]);
						}
					}
					if ( $order_number_filter === 'less_then' ) {
						if ($order_count >= $order_number_input) {
							unset($customers[$key]);
						}
					}
				}
			}

			// Filter by product purchased
			if ( !empty( $_GET['product_filter'] ) ) {
				$product_id = intval( $_GET['product_filter'] );
				$customers_with_product = $this->get_customers_who_bought_product( $product_id );

				foreach ( $customers as $key => $user ) {
					if ( !in_array( $user->ID, $customers_with_product ) ) {
						unset( $customers[$key] );
					}
				}
			}

			// Pro intelligence criteria (RFM / health / lifecycle / behavior): reuse the exact
			// segment-membership logic so "Load segment" matches the saved count and CSV export.
			$pdwchs_intel = array();
			foreach ( array( 'rfm_segment', 'health_score_range', 'health_trend', 'lifecycle_stage', 'lifecycle_days', 'behavior_filter' ) as $pdwchs_k ) {
				if ( isset( $_GET[ $pdwchs_k ] ) && '' !== $_GET[ $pdwchs_k ] ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$pdwchs_intel[ $pdwchs_k ] = sanitize_text_field( wp_unslash( $_GET[ $pdwchs_k ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}
			}
			if ( ! empty( $pdwchs_intel ) && class_exists( 'PDWCHS_Segments_Manager' ) ) {
				$pdwchs_seg_mgr = new PDWCHS_Segments_Manager();
				$customers      = $pdwchs_seg_mgr->pdwchs_filter_customers_by_intelligence( $customers, $pdwchs_intel );
			}

			/**
			 * Filter the customer list by criteria this free build does not know about.
			 *
			 * Same filter as the one in PDWCHS_Segments_Manager::pdwchs_apply_criteria_filters(),
			 * so the list, the saved-segment count and the CSV export stay in agreement.
			 * Here the second argument is a RAW $_GET superset — unslashed, but neither
			 * whitelisted nor sanitised. Listeners must sanitise whatever they read.
			 *
			 * Applied before pdwchs_full_customer_count below, so the free-tier cap banner
			 * counts the filtered set rather than the whole one.
			 *
			 * @since 3.1.1
			 * @param array $customers WP_User objects, keys not guaranteed contiguous.
			 * @param array $criteria  Raw $_GET, unslashed and unsanitised.
			 */
			$customers = apply_filters( 'pdwchs_segment_apply_extra_criteria', $customers, wp_unslash( $_GET ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			$args = array();
			$args = apply_filters( 'pdwchs_customers_query_args', $args, $_GET ); // H11

			// Scope: Free = top 100, Pro lifts to 0 (unlimited) via filter (R2: fixed tier, never quota-triggered).
			$this->pdwchs_full_customer_count = count( $customers );
			$pdwchs_cap = (int) apply_filters( 'pdwchs_customer_list_limit', 100 );
			if ( $pdwchs_cap > 0 && $this->pdwchs_full_customer_count > $pdwchs_cap ) {
				$customers = array_slice( $customers, 0, $pdwchs_cap );
				// Free-tier wall actually reached — record it once (local only).
				pdwchs_record_first_cap_hit( 'customer_cap' );
			}

			if( isset( $_GET['download_cus_info'] ) && current_user_can( 'manage_woocommerce' ) ){
				ob_clean();
				$delimiter = ",";
				$filename = "customers_order_history_" .gmdate('Y-m-d') . ".csv";

				//create a file pointer
				$f = fopen( 'php://memory', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- memory stream, not filesystem

				//set column headers
				$fields = array('User name', 'User role', 'Orders', 'Pending orders', 'Refund orders', 'Orders average', 'Total spent');
				fputcsv($f, $fields, $delimiter);
			}


            //$data
            foreach ( $customers as $user ) :

                $total_spent = $this->pdwchs_woo_get_customer_total_spent( $user->ID );

				if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
					$order_count = count( get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_customer_user',
						'meta_value'  => $user->ID,
						'post_type'   => 'shop_order',
						'post_status' =>  'any',
						'post_parent' => '0',
					) ) );
				}else{
					$order_count = wc_get_customer_order_count( $user->ID );
				}

				if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
					$pending_orders_count = count( get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_customer_user',
						'meta_value'  => $user->ID,
						'post_type'   => 'shop_order',
						'post_status' =>  array( 'pending', 'wc-pending'),
						'post_parent' => '0',
					) ) );
				}else{
					$args_pending_orders = array(
						'customer_id' => $user->ID,
						'status' => array( 'pending', 'wc-pending'),
						'limit' => -1,
					);
					$pending_orders_count = count(wc_get_orders($args_pending_orders));
				}

				if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
					$refunded_orders_count = count( get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_customer_user',
						'meta_value'  => $user->ID,
						'post_type'   => 'shop_order',
						'post_status' =>  array( 'refunded', 'wc-refunded'),
						'post_parent' => '0',
					) ) );
				}else{
					$args_refunded_orders = array(
						'customer_id' => $user->ID,
						'status' => array( 'refunded', 'wc-refunded'),
						'limit' => -1,
					);
					$refunded_orders_count = count(wc_get_orders($args_refunded_orders));
				}

                global $wp_roles;
                $roles_array = array();
                foreach ( $wp_roles->role_names as $role => $name ) {
                    if ( user_can( $user, $role ) ) {
                        $roles_array[] = $role;
                    }
                }

                $lifecycle_stage = '';
                $lifecycle_days_in_stage = 0;

                $this->customer_array[] = array(
                    'user_id' => $user->ID,
                    'user' => '<span class="dashicons dashicons-admin-users" style="margin-right: 5px;"></span><a href="admin.php?page=pd-woo-customer&user_id='.esc_html( $user->ID ).'">'.esc_html( $user->display_name ).'</a>',
                    'role' => implode( '<br />', $roles_array ),
                    'orders' => $order_count,
                    'pending_orders' => $pending_orders_count,
                    'refund_orders' => $refunded_orders_count,
                    'orders_average' => $order_count > 0 ? wc_price( $total_spent / $order_count ) : wc_price( $total_spent ),
                    'total_spent' => wc_price( $total_spent ),
                    'lifecycle_stage' => $lifecycle_stage,
                    'lifecycle_days_in_stage' => $lifecycle_days_in_stage,
                    'actions' => '<a href="admin.php?page=pd-woo-customer&user_id='.esc_html( $user->ID ).'" class="button"><strong><span class="dashicons dashicons-visibility vertical-align-middle"></span> '. esc_html__( 'View', 'puredevs-customer-history-for-woocommerce' ).'</strong></a> <a href="admin.php?page=pd-woo-customer-email&user_id='.esc_html( $user->ID ).'" class="button"><strong><span class="dashicons dashicons-email vertical-align-middle"></span> '. esc_html__( 'Email', 'puredevs-customer-history-for-woocommerce' ).'</strong></a>',
                );

				if( isset( $_GET['download_cus_info'] ) && current_user_can( 'manage_woocommerce' ) ){
					$lineData = array($user->display_name, implode( '<br />', $roles_array ), $order_count, $pending_orders_count, $refunded_orders_count, $order_count > 0 ? number_format( ( $total_spent / $order_count ), 2, '.', '' ) : number_format($total_spent, 2, '.', ''), number_format($total_spent, 2, '.', ''));
					fputcsv($f, $lineData, $delimiter);
				}

            endforeach;

			if( isset( $_GET['download_cus_info'] ) && current_user_can( 'manage_woocommerce' ) ){
				//move back to beginning of file
				fseek($f, 0);

				//set headers to download file rather than displayed
				header('Content-Type: text/csv');
				header('Content-Disposition: attachment; filename="' . $filename . '";');

				//output all remaining data on a file pointer
				fpassthru($f);
				exit;
			}

            usort( $this->customer_array, array( &$this, 'sort_data' ) );

            $results_per_page = get_option( 'pd-woo-results_per_page' );
            if ( !empty( $_GET['show-items'] ) ) {
                $perPage = sanitize_text_field( $_GET['show-items'] );
            } else {
                $perPage = ! empty( $results_per_page ) ? $results_per_page : 10;
            }

            $perPage = (int) $perPage;

            $currentPage = $this->get_pagenum();
            $totalItems = count( $this->customer_array );
            $this->set_pagination_args( array(
                'total_items' => $totalItems,
                'per_page'    => $perPage,
            ) );
            if ( !empty( $this->customer_array ) ) {
                $this->customer_array = array_slice( $this->customer_array, ( ( $currentPage-1 ) * $perPage ), $perPage );
            }

            $this->items = $this->customer_array;
        }

        public function column_default( $item, $column_name ) {
            switch( $column_name ) {
                case 'user':
                case 'role':
                case 'orders':
                case 'pending_orders':
                case 'refund_orders':
                case 'orders_average':
                case 'total_spent':
                case 'actions':
                    return $item[ $column_name ];
                case 'rfm_segment':
                    return apply_filters( 'pdwchs_customers_column_rfm_segment', '--', $item['user_id'] ); // H12
                case 'health_score':
                    return $this->pdwchs_get_health_score_badge( $item['user_id'] );
                case 'lifecycle_stage':
                    return $this->pdwchs_get_lifecycle_stage_badge( $item['user_id'] );
                case 'behavior_tags':
                    return apply_filters( 'pdwchs_customers_column_behavioral', '--', $item['user_id'] ); // H13
                default:
                    /**
                     * Filter the cell value for a column this free build does not declare.
                     *
                     * The counterpart to pdwchs_customers_columns in get_columns(): that seam declares
                     * the column, this one fills it. The free build declares none through either, so
                     * with no listener registered this returns '' and the cell renders empty — the
                     * behaviour this branch has today, and deliberately not the debug print_r dump it
                     * used to carry.
                     *
                     * The hook name carries the column key as a dynamic suffix, matching
                     * pdwchs_customers_column_rfm_segment and pdwchs_customers_column_behavioral
                     * above, so a listener never fires for a column it does not own.
                     *
                     * IMPORTANT: WP_List_Table echoes this return value into the <td> unescaped, and
                     * this class does not override single_row_columns(). A listener must therefore
                     * return escaped, display-ready markup. The value is deliberately not escaped
                     * here, because the two filters above return badge markup and every future
                     * consumer will need to as well.
                     *
                     * @since 3.1.2
                     *
                     * @param string $value   Cell contents. Default '' — an unfilled column renders empty.
                     * @param int    $user_id The row's WordPress user id.
                     */
                    return apply_filters( "pdwchs_customers_column_{$column_name}", '', $item['user_id'] );
            }
        }

        /**
         * Get RFM segment badge HTML for customer
         *
         * @since    2.0.0
         * @param    int    $user_id    WordPress user ID
         * @return   string             Badge HTML
         */
        private function pdwchs_get_rfm_segment_badge( $user_id ) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';
            $rfm_data = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT rfm_segment FROM {$table_name} WHERE user_id = %d",
                    $user_id
                ),
                ARRAY_A
            );

            if ( ! $rfm_data || ! isset( $rfm_data['rfm_segment'] ) ) {
                return '<span class="pdwchs-badge pdwchs-badge-undefined" style="background-color: #94a3b8; color: #fff; padding: 4px 12px; border-radius: 16px; font-size: 12px; font-weight: 500;">--</span>';
            }

            $segment_slug = $rfm_data['rfm_segment'];
            return apply_filters( 'pdwchs_customers_column_rfm_segment', '', $user_id ); // H12
        }

        /**
         * Get health score badge HTML for customer
         *
         * @since    2.0.0
         * @param    int    $user_id    WordPress user ID
         * @return   string             Badge HTML
         */
        private function pdwchs_get_health_score_badge( $user_id ) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';
            $health_data = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT health_score, health_trend FROM {$table_name} WHERE user_id = %d",
                    $user_id
                ),
                ARRAY_A
            );

            if ( ! $health_data || is_null( $health_data['health_score'] ) ) {
                return '<span class="pdwchs-health-column pdwchs-health-no-data" style="background-color: #f1f5f9; color: #64748b;">--</span>';
            }

            $score = intval( $health_data['health_score'] );
            $trend = isset( $health_data['health_trend'] ) ? $health_data['health_trend'] : 'stable';

            // Determine color class based on score range
            if ( $score >= 81 ) {
                $class = 'pdwchs-health-81-100';
            } elseif ( $score >= 61 ) {
                $class = 'pdwchs-health-61-80';
            } elseif ( $score >= 41 ) {
                $class = 'pdwchs-health-41-60';
            } elseif ( $score >= 21 ) {
                $class = 'pdwchs-health-21-40';
            } else {
                $class = 'pdwchs-health-0-20';
            }

            // Trend arrow
            $trend_arrow = '';
            if ( $trend === 'up' ) {
                $trend_arrow = ' <span class="pdwchs-trend-arrow up">↗</span>';
            } elseif ( $trend === 'down' ) {
                $trend_arrow = ' <span class="pdwchs-trend-arrow down">↘</span>';
            } else {
                $trend_arrow = ' <span class="pdwchs-trend-arrow stable">→</span>';
            }

            return sprintf(
                '<span class="pdwchs-health-column %s">%d%s</span>',
                esc_attr( $class ),
                $score,
                $trend_arrow
            );
        }

        /**
         * Get lifecycle stage badge HTML for customer
         *
         * @since    2.1.0
         * @param    int    $user_id    WordPress user ID
         * @return   string             Badge HTML
         */
        private function pdwchs_get_lifecycle_stage_badge( $user_id ) {
            global $wpdb;

            $table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';
            $lifecycle_data = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT lifecycle_stage, lifecycle_days_in_stage FROM {$table_name} WHERE user_id = %d",
                    $user_id
                ),
                ARRAY_A
            );

            if ( ! $lifecycle_data || ! isset( $lifecycle_data['lifecycle_stage'] ) || empty( $lifecycle_data['lifecycle_stage'] ) ) {
                return '<span class="pdwchs-lifecycle-badge pdwchs-lifecycle-undefined" style="background-color: #94a3b8; color: #fff; padding: 4px 12px; border-radius: 16px; font-size: 11px; font-weight: 500; display: inline-block;">--</span>';
            }

            $stage = $lifecycle_data['lifecycle_stage'];
            $days_in_stage = isset( $lifecycle_data['lifecycle_days_in_stage'] ) ? intval( $lifecycle_data['lifecycle_days_in_stage'] ) : 0;

            // Stage configuration with colors and icons
            $stage_configs = array(
                'Prospect' => array(
                    'color'      => '#94a3b8',
                    'icon'       => '👤',
                    'text_color' => '#fff',
                ),
                'New Customer' => array(
                    'color'      => '#06b6d4',
                    'icon'       => '🆕',
                    'text_color' => '#fff',
                ),
                'Active Customer' => array(
                    'color'      => '#10b981',
                    'icon'       => '✅',
                    'text_color' => '#fff',
                ),
                'Loyal Customer' => array(
                    'color'      => '#8b5cf6',
                    'icon'       => '💜',
                    'text_color' => '#fff',
                ),
                'At Risk' => array(
                    'color'      => '#f97316',
                    'icon'       => '⚠️',
                    'text_color' => '#fff',
                ),
                'Churned' => array(
                    'color'      => '#ef4444',
                    'icon'       => '💔',
                    'text_color' => '#fff',
                ),
            );

            $config = isset( $stage_configs[ $stage ] ) ? $stage_configs[ $stage ] : array(
                'color'      => '#94a3b8',
                'icon'       => '👤',
                'text_color' => '#fff',
            );

            $days_text = '';
            if ( $days_in_stage > 0 ) {
                $days_text = sprintf(
                    '<span class="pdwchs-lifecycle-days" style="font-size: 10px; opacity: 0.9; margin-left: 4px;">(%dd)</span>',
                    $days_in_stage
                );
            }

            return sprintf(
                '<span class="pdwchs-lifecycle-badge pdwchs-lifecycle-%s" style="background-color: %s; color: %s; padding: 4px 10px; border-radius: 16px; font-size: 11px; font-weight: 500; display: inline-block; white-space: nowrap;" title="%s">%s %s%s</span>',
                esc_attr( sanitize_title( $stage ) ),
                esc_attr( $config['color'] ),
                esc_attr( $config['text_color'] ),
                /* translators: %1$s is lifecycle stage name, %2$d is number of days */
                esc_attr( sprintf( __( '%1$s - %2$d days in stage', 'puredevs-customer-history-for-woocommerce' ), $stage, $days_in_stage ) ),
                esc_html( $config['icon'] ),
                esc_html( $stage ),
                $days_text
            );
        }

        public function get_sortable_columns() {
            $sortable_columns = array(
                'user'  => array('user',false),
                'role' => array('role',false),
                'orders' => array('orders',false),
                'pending_orders' => array('pending_orders',false),
                'refund_orders' => array('refund_orders',false),
                'orders_average' => array('orders_average',false),
                'total_spent' => array('total_spent',false),
            );

            $sortable_columns['lifecycle_stage'] = array( 'lifecycle_stage', false );

            return $sortable_columns;
        }

        /**
         * Extra table navigation with lifecycle filters.
         *
         * @since    2.1.0
         * @param    string    $which    Position (top or bottom)
         */
        protected function extra_tablenav( $which ) {
            if ( 'top' !== $which ) {
                return;
            }
            if ( ! pdwchs_is_pro_active() ) {
                return;
            }

            $selected_stage = isset( $_GET['lifecycle_stage'] ) ? sanitize_text_field( $_GET['lifecycle_stage'] ) : '';
            $selected_days = isset( $_GET['lifecycle_days'] ) ? sanitize_text_field( $_GET['lifecycle_days'] ) : '';
            $stages = $this->get_all_lifecycle_stages();

            ?>
            <form method="get" class="alignleft actions pdwchs-lifecycle-filters">
                <input type="hidden" name="page" value="<?php echo esc_attr( isset( $_GET['page'] ) ? $_GET['page'] : 'pd-woo-customers' ); ?>" />
                <?php
                // Preserve other existing query parameters
                /**
                 * Filter the query parameters this second filter form re-emits as hidden inputs.
                 *
                 * extra_tablenav() renders its own <form method="get">, separate from the main
                 * filter form, so any parameter missing from this list is dropped the moment the
                 * user submits the lifecycle filters. Listeners adding a criteria key should add
                 * it here too, or their filter silently stops applying.
                 *
                 * @since 3.1.1
                 * @param array $params Query parameter names to preserve.
                 */
                $preserve_params = apply_filters( 'pdwchs_customers_preserve_params', array( 's', 'user_role', 'order_number', 'order_number_input', 'total_spent', 'total_spent_input', 'customer_country', 'rfm_segment', 'health_score_range', 'health_trend', 'product_filter', 'behavior_filter', 'behavior_match', 'show-items' ) );
                foreach ( $preserve_params as $param ) {
                    if ( isset( $_GET[ $param ] ) && ! empty( $_GET[ $param ] ) ) {
                        echo '<input type="hidden" name="' . esc_attr( $param ) . '" value="' . esc_attr( $_GET[ $param ] ) . '" />';
                    }
                }
                ?>
                <select name="lifecycle_stage" id="lifecycle_stage_filter">
                    <option value=""><?php esc_html_e( 'All Lifecycle Stages', 'puredevs-customer-history-for-woocommerce' ); ?></option>
                    <?php foreach ( $stages as $slug => $label ) : ?>
                        <option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $selected_stage, $slug ); ?>>
                            <?php echo esc_html( $label ); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <select name="lifecycle_days" id="lifecycle_days_filter">
                    <option value=""><?php esc_html_e( 'All Days in Stage', 'puredevs-customer-history-for-woocommerce' ); ?></option>
                    <option value="0-30" <?php selected( $selected_days, '0-30' ); ?>><?php esc_html_e( '0-30 days', 'puredevs-customer-history-for-woocommerce' ); ?></option>
                    <option value="31-60" <?php selected( $selected_days, '31-60' ); ?>><?php esc_html_e( '31-60 days', 'puredevs-customer-history-for-woocommerce' ); ?></option>
                    <option value="61-90" <?php selected( $selected_days, '61-90' ); ?>><?php esc_html_e( '61-90 days', 'puredevs-customer-history-for-woocommerce' ); ?></option>
                    <option value="90+" <?php selected( $selected_days, '90+' ); ?>><?php esc_html_e( '90+ days', 'puredevs-customer-history-for-woocommerce' ); ?></option>
                </select>

                <?php submit_button( __( 'Filter', 'puredevs-customer-history-for-woocommerce' ), '', 'filter_action', false ); ?>
            </form>
            <style>
                .pdwchs-lifecycle-filters {
                    margin-left: 10px;
                    display: inline-flex;
                    align-items: center;
                    gap: 5px;
                }
                .pdwchs-lifecycle-filters select {
                    margin-right: 5px;
                }
            </style>
            <?php
        }

        private function sort_data( $a, $b ) {
            // Set defaults
            $orderby = 'user';
            $order = 'desc';
            // If orderby is set, use this as the sort column
            if ( !empty( $_GET['orderby'] ) ) {
                $orderby = sanitize_text_field( $_GET['orderby'] );
            }
            // If order is set use this as the order
            if ( !empty( $_GET['order'] ) ) {
                $order = sanitize_text_field( $_GET['order'] );
            }

            // Handle missing keys with default empty string
            $val_a = isset( $a[$orderby] ) ? $a[$orderby] : '';
            $val_b = isset( $b[$orderby] ) ? $b[$orderby] : '';

            // Handle lifecycle_days_in_stage as numeric
            if ( $orderby === 'lifecycle_days_in_stage' ) {
                $val_a = intval( $val_a );
                $val_b = intval( $val_b );
                $result = $val_a - $val_b;
            } else {
                $result = strcmp( $val_a, $val_b );
            }

            if ( $order === 'asc' ) {
                return $result;
            }
            return -$result;
        }

        /**
         * Load all customer.
         *
         * @since    1.0.0
         * @access   public static
         */

        public static function pdwchs_woo_get_all_customers( $search, $advanceSearchTerms ) {

            $user_role = array('administrator','customer','subscriber');

            $args = array(
                'number'   => -1,
                'role__in' => $user_role,
            );

            $customer_query = new WP_User_Query( $args );
            return $customer_query->get_results();
        }

        /**
         * Load customer total spent.
         *
         * @since    1.0.0
         * @access   public static
         */

        public static function pdwchs_woo_get_customer_total_spent( $customer_id ) {
            $total_spent = 0;
			$data_sync_enabled = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );
			if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
				$customer_orders = get_posts( array(
					'numberposts' => -1,
					'meta_key'    => '_customer_user',
					'meta_value'  => $customer_id,
					'post_type'   => 'shop_order',
					'post_status' =>  array( 'any' ),
					'post_parent'   => '0',
				) );

				if(!empty($customer_orders)){
					foreach ( $customer_orders as $order ) {
						$order = new WC_Order( $order->ID );
						$total_spent += $order->get_total();
					}
				}
			}else{
				$args = array(
					'customer_id' => $customer_id,
					'limit' => -1,
				);
				$orders = wc_get_orders($args);

				if(!empty($orders)){
					foreach ( $orders as $order ) {
						$total_spent += $order->get_total();
					}
				}
			}

            return $total_spent;
        }

        /**
         * Load customer pending orders total.
         *
         * @since    1.0.0
         * @access   public static
         */

        public static function pdwchs_woo_get_customer_pending_orders_total( $customer_id ) {
            $pending_orders_total = 0;
			$data_sync_enabled = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );
			if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
				$pending_orders = get_posts( array(
					'numberposts'   => -1,
					'meta_key'      => '_customer_user',
					'meta_value'    => $customer_id,
					'post_type'     => 'shop_order',
					'post_status'   =>  array( 'pending', 'wc-pending'),
					'post_parent'   => '0',
				) );
				if(!empty($pending_orders)){
					foreach ( $pending_orders as $order ) {
						$order = new WC_Order( $order->ID );
						$pending_orders_total += $order->get_total();
					}
				}
			}else{
				$args_pending_orders = array(
					'customer_id' => $customer_id,
					'status' => array( 'pending', 'wc-pending'),
					'limit' => -1,
				);
				$pending_orders = wc_get_orders($args_pending_orders);
				if(!empty($pending_orders)){
					foreach ( $pending_orders as $order ) {
						$pending_orders_total += $order->get_total();
					}
				}
			}
            return $pending_orders_total;
        }

        /**
         * Load customer refunded orders total.
         *
         * @since    1.0.0
         * @access   public static
         */

        public static function pdwchs_woo_get_customer_refunded_orders_total( $customer_id ) {
            $refunded_orders_total = 0;
			$data_sync_enabled = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );
			if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
				$refunded_orders = get_posts( array(
					'numberposts'   => -1,
					'meta_key'      => '_customer_user',
					'meta_value'    => $customer_id,
					'post_type'     => 'shop_order',
					'post_status'   =>  array( 'refunded', 'wc-refunded'),
					'post_parent'   => '0',
				) );
				if(!empty($refunded_orders)){
					foreach ( $refunded_orders as $order ) {
						$order = new WC_Order( $order->ID );
						$refunded_orders_total += $order->get_total();
					}
				}
			}else{
				$args_refunded_orders = array(
					'customer_id' => $customer_id,
					'status' => array( 'refunded', 'wc-refunded'),
					'limit' => -1,
				);
				$refunded_orders = wc_get_orders($args_refunded_orders);
				if(!empty($refunded_orders)){
					foreach ( $refunded_orders as $order ) {
						$refunded_orders_total += $order->get_total();
					}
				}
			}
            return $refunded_orders_total;
        }

        /**
         * Get customers who bought a specific product.
         *
         * @since    1.0.0
         * @access   private
         * @param    int    $product_id    Product ID to search for
         * @return   array                 Array of customer IDs
         */
        private function get_customers_who_bought_product( $product_id ) {
            // Check cache first to improve performance
            $cache_key = 'pdwchs_customers_product_' . $product_id;
            $cached_result = get_transient( $cache_key );

            if ( $cached_result !== false ) {
                return $cached_result;
            }

            $customer_ids = array();
            $page = 1;
            $limit = 100; // Process orders in batches to avoid memory issues

            // Get all order statuses except trash and cancelled
            $order_statuses = array( 'completed', 'processing', 'on-hold', 'pending', 'refunded', 'failed' );

            while ( true ) {
                // Get orders in batches
                $orders = wc_get_orders( array(
                    'limit'  => $limit,
                    'page'   => $page,
                    'status' => $order_statuses,
                    'return' => 'ids', // More efficient - just get IDs first
                ) );

                if ( empty( $orders ) ) {
                    break; // No more orders to process
                }

                foreach ( $orders as $order_id ) {
                    $order = wc_get_order( $order_id );

                    if ( ! $order ) {
                        continue;
                    }

                    // Check if this order contains the product
                    foreach ( $order->get_items() as $item ) {
                        $product_id_in_order = $item->get_product_id();
                        $variation_id = $item->get_variation_id();

                        // Check both product ID and variation ID
                        if ( $product_id_in_order == $product_id || $variation_id == $product_id ) {
                            $customer_id = $order->get_customer_id();

                            if ( $customer_id > 0 ) { // Only registered customers
                                $customer_ids[] = $customer_id;
                            }
                            break; // Product found in this order, move to next order
                        }
                    }
                }

                $page++;

                // Safety limit to prevent infinite loops
                if ( $page > 1000 ) {
                    break;
                }
            }

            $customer_ids = array_unique( $customer_ids );

            // Cache for 1 hour
            set_transient( $cache_key, $customer_ids, HOUR_IN_SECONDS );

            return $customer_ids;
        }

        /**
         * Get all lifecycle stages for filter dropdown.
         *
         * @since    2.1.0
         * @access   private
         * @return   array    Array of lifecycle stages
         */
        private function get_all_lifecycle_stages() {
            return array(
                'Prospect'        => __( 'Prospect', 'puredevs-customer-history-for-woocommerce' ),
                'New Customer'    => __( 'New Customer', 'puredevs-customer-history-for-woocommerce' ),
                'Active Customer' => __( 'Active Customer', 'puredevs-customer-history-for-woocommerce' ),
                'Loyal Customer'  => __( 'Loyal Customer', 'puredevs-customer-history-for-woocommerce' ),
                'At Risk'         => __( 'At Risk', 'puredevs-customer-history-for-woocommerce' ),
                'Churned'         => __( 'Churned', 'puredevs-customer-history-for-woocommerce' ),
            );
        }

        /**
         * Count registered customers whose most recent order is older than 30 days.
         * HPOS-safe: uses wc_get_orders() only. Cached for 12 hours.
         *
         * @return int
         */
        public static function pdwchs_count_customers_needing_attention() {
            $cached = get_transient( 'pdwchs_attention_count_30d' );
            if ( false !== $cached ) {
                return (int) $cached;
            }

            $cutoff     = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
            $all_ids    = array();
            $recent_ids = array();
            $page       = 1;

            while ( true ) {
                $orders = wc_get_orders( array(
                    'limit'  => 100,
                    'page'   => $page,
                    'type'   => 'shop_order',
                ) );

                if ( empty( $orders ) ) {
                    break;
                }

                foreach ( $orders as $order ) {
                    $cid = $order->get_customer_id();
                    if ( $cid <= 0 ) {
                        continue; // skip guest orders
                    }
                    $all_ids[ $cid ] = true;
                    $date = $order->get_date_created();
                    if ( $date && $date->format( 'Y-m-d H:i:s' ) >= $cutoff ) {
                        $recent_ids[ $cid ] = true;
                    }
                }

                $page++;
                if ( $page > 500 ) {
                    break; // safety valve for very large stores
                }
            }

            $count = count( array_diff_key( $all_ids, $recent_ids ) );
            set_transient( 'pdwchs_attention_count_30d', $count, 12 * HOUR_IN_SECONDS );
            return $count;
        }

    }

}
