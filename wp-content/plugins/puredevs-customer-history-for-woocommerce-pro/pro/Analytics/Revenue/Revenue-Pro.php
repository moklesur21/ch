<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// Reason: All queries use $wpdb->prepare() with dynamically built placeholder arrays.
// All public methods cache results via transients. Interpolated variables are internal
// (table prefixes, status placeholders) — never user input.

/**
 * Pro Revenue Analytics
 *
 * Wires the revenue AJAX handlers and enqueue hooks into the Pro plugin via
 * seams H4 (pdwchs_enqueue_admin_styles), H5 (pdwchs_enqueue_admin_scripts),
 * and H6 (pdwchs_dashboard_widgets) laid in Batch 0.
 *
 * Delegates Free data methods to PDWCHS_Revenue_Data_Free; owns the
 * new-vs-returning query method internally.
 *
 * @since 2.3.0
 */
class PDWCHS_Revenue_Pro {

	/** Order statuses for $wpdb queries (with wc- prefix). */
	const REVENUE_STATUSES_SQL = array( 'wc-completed', 'wc-processing' );

	/** Transient cache TTL in seconds (30 minutes). */
	const CACHE_TTL = 30 * MINUTE_IN_SECONDS;

	public function __construct() {
		add_action( 'pdwchs_enqueue_admin_styles',         array( $this, 'pdwchs_enqueue_styles' ) );
		add_action( 'pdwchs_enqueue_admin_scripts',        array( $this, 'pdwchs_enqueue_scripts' ) );
		add_action( 'pdwchs_dashboard_widgets',            array( $this, 'pdwchs_dashboard_widget' ) );

		add_action( 'wp_ajax_pdwchs_get_revenue_overview',          array( $this, 'pdwchs_ajax_get_revenue_overview' ) );
		add_action( 'wp_ajax_pdwchs_get_revenue_over_time',         array( $this, 'pdwchs_ajax_get_revenue_over_time' ) );
		add_action( 'wp_ajax_pdwchs_get_new_vs_returning_revenue',  array( $this, 'pdwchs_ajax_get_new_vs_returning_revenue' ) );
	}

	/**
	 * H4 — Enqueue Revenue Analytics and Data Integrity CSS on dashboard page.
	 *
	 * @since 2.3.0
	 */
	public function pdwchs_enqueue_styles() {
		if ( ! isset( $_GET['page'] ) || 'pd-woo-dashboard' !== $_GET['page'] ) {
			return;
		}
		$admin_css_url = PDWCHS_PRO_PLUGIN_URL . 'admin/css/';
		wp_enqueue_style(
			'pdwchs-revenue-css',
			$admin_css_url . 'pd-customer-history-revenue.css',
			array(),
			time(),
			'all'
		);
		wp_enqueue_style(
			'pdwchs-data-integrity-css',
			$admin_css_url . 'pd-customer-history-data-integrity.css',
			array(),
			time(),
			'all'
		);
	}

	/**
	 * H5 — Enqueue Revenue Analytics JS on dashboard page.
	 *
	 * @since 2.3.0
	 */
	public function pdwchs_enqueue_scripts() {
		if ( ! isset( $_GET['page'] ) || 'pd-woo-dashboard' !== $_GET['page'] ) {
			return;
		}
		$admin_js_url = PDWCHS_PRO_PLUGIN_URL . 'admin/js/';
		wp_enqueue_script(
			'pdwchs-revenue-js',
			$admin_js_url . 'pdwchs-revenue-dashboard.js',
			array( 'jquery', 'chartjs', 'moment', 'date-picker' ),
			time(),
			true
		);
		wp_localize_script( 'pdwchs-revenue-js', 'pdwchs_revenue_vars', array(
			'nonce'                => wp_create_nonce( 'pdwchs_revenue_nonce' ),
			'ajaxurl'              => admin_url( 'admin-ajax.php' ),
			'currency_symbol'      => get_woocommerce_currency_symbol(),
			'currency_position'    => get_option( 'woocommerce_currency_pos' ),
			'thousand_separator'   => wc_get_price_thousand_separator(),
			'decimal_separator'    => wc_get_price_decimal_separator(),
			'loading_text'         => __( 'Loading revenue data...', 'puredevs-customer-history-for-woocommerce' ),
			'error_text'           => __( 'Error loading revenue data. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
			'no_data_text'         => __( 'No revenue data available for this period.', 'puredevs-customer-history-for-woocommerce' ),
			'data_integrity_nonce' => wp_create_nonce( 'pdwchs_data_integrity_nonce' ),
			'integrity_i18n'       => array(
				'verifying'        => __( 'Verifying data…',                   'puredevs-customer-history-for-woocommerce' ),
				'verified'         => __( 'Data Verified ✓',                   'puredevs-customer-history-for-woocommerce' ),
				'mismatch'         => __( 'Data Mismatch ⚠️',                 'puredevs-customer-history-for-woocommerce' ),
				'view_details'     => __( 'View Details',                      'puredevs-customer-history-for-woocommerce' ),
				'hide_details'     => __( 'Hide Details',                      'puredevs-customer-history-for-woocommerce' ),
				'unusual_statuses' => __( 'Orders in unusual statuses',        'puredevs-customer-history-for-woocommerce' ),
				'partial_refunds'  => __( 'Partially refunded orders',         'puredevs-customer-history-for-woocommerce' ),
				'currency_mism'    => __( 'Currency mismatch',                 'puredevs-customer-history-for-woocommerce' ),
				'no_diagnostics'   => __( 'No flagged orders for this range.', 'puredevs-customer-history-for-woocommerce' ),
				'fetch_error'      => __( 'Could not load integrity check.',   'puredevs-customer-history-for-woocommerce' ),
				'order_id'         => __( 'Order',                             'puredevs-customer-history-for-woocommerce' ),
				'status_label'     => __( 'Status',                            'puredevs-customer-history-for-woocommerce' ),
				'total_label'      => __( 'Total',                             'puredevs-customer-history-for-woocommerce' ),
				'date_label'       => __( 'Date',                              'puredevs-customer-history-for-woocommerce' ),
				'refunded_label'   => __( 'Refunded',                          'puredevs-customer-history-for-woocommerce' ),
				'currency_label'   => __( 'Currency',                          'puredevs-customer-history-for-woocommerce' ),
			),
		) );
	}

	/**
	 * H6 — Include revenue dashboard widget template.
	 *
	 * @since 2.3.0
	 */
	public function pdwchs_dashboard_widget() {
		include __DIR__ . '/../templates/revenue-dashboard-widget.php';
	}

	/**
	 * AJAX: revenue overview (4 KPI cards + comparison percentages).
	 *
	 * POST params: nonce, start_date (Y-m-d), end_date (Y-m-d)
	 *
	 * @since 2.3.0
	 */
	public function pdwchs_ajax_get_revenue_overview() {
		check_ajax_referer( 'pdwchs_revenue_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date   = isset( $_POST['end_date'] )   ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) )   : '';

		$data = ( new PDWCHS_Revenue_Data_Free() )->pdwchs_get_revenue_overview( $start_date, $end_date );
		wp_send_json_success( $data );
	}

	/**
	 * AJAX: revenue time-series (daily / weekly / monthly buckets).
	 *
	 * POST params: nonce, start_date (Y-m-d), end_date (Y-m-d), granularity ('daily'|'weekly'|'monthly')
	 *
	 * @since 2.3.0
	 */
	public function pdwchs_ajax_get_revenue_over_time() {
		check_ajax_referer( 'pdwchs_revenue_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$start_date  = isset( $_POST['start_date'] )  ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) )  : '';
		$end_date    = isset( $_POST['end_date'] )     ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) )    : '';
		$granularity = isset( $_POST['granularity'] )  ? sanitize_text_field( wp_unslash( $_POST['granularity'] ) ) : 'daily';

		$allowed = array( 'daily', 'weekly', 'monthly' );
		if ( ! in_array( $granularity, $allowed, true ) ) {
			$granularity = 'daily';
		}

		$data = ( new PDWCHS_Revenue_Data_Free() )->pdwchs_get_revenue_over_time( $start_date, $end_date, $granularity );
		wp_send_json_success( $data );
	}

	/**
	 * AJAX: new-vs-returning revenue split.
	 *
	 * POST params: nonce, start_date (Y-m-d), end_date (Y-m-d)
	 *
	 * @since 2.3.0
	 */
	public function pdwchs_ajax_get_new_vs_returning_revenue() {
		check_ajax_referer( 'pdwchs_revenue_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date   = isset( $_POST['end_date'] )   ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) )   : '';

		$data = $this->pdwchs_get_new_vs_returning_revenue( $start_date, $end_date );
		wp_send_json_success( $data );
	}

	/**
	 * Check if HPOS (Custom Order Tables) is enabled.
	 *
	 * @since 2.3.0
	 *
	 * @return bool
	 */
	private function pdwchs_is_hpos_enabled() {
		return class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Generate a transient cache key.
	 *
	 * @since 2.3.0
	 *
	 * @param string $method Method identifier.
	 * @param array  $params Parameters to hash.
	 * @return string
	 */
	private function pdwchs_cache_key( $method, $params ) {
		return 'pdwchs_rev_' . $method . '_' . md5( wp_json_encode( $params ) );
	}

	// -------------------------------------------------------------------------
	// Large verbatim methods shell-copied from class-pdwchs-revenue-data.php
	// -------------------------------------------------------------------------
	/**
	 * Get revenue split by new, returning, and guest customers.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array
	 */
	public function pdwchs_get_new_vs_returning_revenue( $start_date, $end_date ) {
		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			return $this->pdwchs_zeroed_new_vs_returning();
		}
		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$cache_key = $this->pdwchs_cache_key( 'newret', array( $start_date, $end_date ) );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$end_exclusive = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) );
		$start_param   = $start_date . ' 00:00:00';
		$end_param     = $end_exclusive . ' 00:00:00';

		$statuses     = self::REVENUE_STATUSES_SQL;
		$status_ph    = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		$new_ids = $this->pdwchs_get_new_customer_ids( $start_date, $end_date );

		$result = array(
			'new'       => array(
				'revenue'   => 0.0,
				'orders'    => 0,
				'customers' => 0,
			),
			'returning' => array(
				'revenue'   => 0.0,
				'orders'    => 0,
				'customers' => 0,
			),
			'guest'     => array(
				'revenue'   => 0.0,
				'orders'    => 0,
			),
		);

		if ( $this->pdwchs_is_hpos_enabled() ) {
			// --- New customers ---
			if ( ! empty( $new_ids ) ) {
				$id_ph = implode( ', ', array_fill( 0, count( $new_ids ), '%d' ) );

	
				$new_gross = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT
							COALESCE(SUM(total_amount), 0) AS gross_revenue,
							COUNT(id) AS total_orders,
							COUNT(DISTINCT customer_id) AS total_customers
						FROM {$wpdb->prefix}wc_orders
						WHERE type = 'shop_order'
						  AND status IN ({$status_ph})
						  AND date_created_gmt >= %s
						  AND date_created_gmt < %s
						  AND customer_id IN ({$id_ph})",
						array_merge( $statuses, array( $start_param, $end_param ), $new_ids )
					)
				);

	
				$new_refunds = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE(ABS(SUM(r.total_amount)), 0)
						FROM {$wpdb->prefix}wc_orders r
						INNER JOIN {$wpdb->prefix}wc_orders o ON r.parent_order_id = o.id
						WHERE r.type = 'shop_order_refund'
						  AND o.type = 'shop_order'
						  AND o.status IN ({$status_ph})
						  AND o.date_created_gmt >= %s
						  AND o.date_created_gmt < %s
						  AND o.customer_id IN ({$id_ph})",
						array_merge( $statuses, array( $start_param, $end_param ), $new_ids )
					)
				);

				$result['new']['revenue']   = (float) $new_gross->gross_revenue - (float) $new_refunds;
				$result['new']['orders']    = (int) $new_gross->total_orders;
				$result['new']['customers'] = (int) $new_gross->total_customers;
			}

			// --- Returning customers ---
			if ( ! empty( $new_ids ) ) {
				$id_ph = implode( ', ', array_fill( 0, count( $new_ids ), '%d' ) );

	
				$ret_gross = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT
							COALESCE(SUM(total_amount), 0) AS gross_revenue,
							COUNT(id) AS total_orders,
							COUNT(DISTINCT customer_id) AS total_customers
						FROM {$wpdb->prefix}wc_orders
						WHERE type = 'shop_order'
						  AND status IN ({$status_ph})
						  AND date_created_gmt >= %s
						  AND date_created_gmt < %s
						  AND customer_id > 0
						  AND customer_id NOT IN ({$id_ph})",
						array_merge( $statuses, array( $start_param, $end_param ), $new_ids )
					)
				);

	
				$ret_refunds = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE(ABS(SUM(r.total_amount)), 0)
						FROM {$wpdb->prefix}wc_orders r
						INNER JOIN {$wpdb->prefix}wc_orders o ON r.parent_order_id = o.id
						WHERE r.type = 'shop_order_refund'
						  AND o.type = 'shop_order'
						  AND o.status IN ({$status_ph})
						  AND o.date_created_gmt >= %s
						  AND o.date_created_gmt < %s
						  AND o.customer_id > 0
						  AND o.customer_id NOT IN ({$id_ph})",
						array_merge( $statuses, array( $start_param, $end_param ), $new_ids )
					)
				);
			} else {
				// No new customers — all customer_id > 0 are returning.
	
				$ret_gross = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT
							COALESCE(SUM(total_amount), 0) AS gross_revenue,
							COUNT(id) AS total_orders,
							COUNT(DISTINCT customer_id) AS total_customers
						FROM {$wpdb->prefix}wc_orders
						WHERE type = 'shop_order'
						  AND status IN ({$status_ph})
						  AND date_created_gmt >= %s
						  AND date_created_gmt < %s
						  AND customer_id > 0",
						array_merge( $statuses, array( $start_param, $end_param ) )
					)
				);

	
				$ret_refunds = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE(ABS(SUM(r.total_amount)), 0)
						FROM {$wpdb->prefix}wc_orders r
						INNER JOIN {$wpdb->prefix}wc_orders o ON r.parent_order_id = o.id
						WHERE r.type = 'shop_order_refund'
						  AND o.type = 'shop_order'
						  AND o.status IN ({$status_ph})
						  AND o.date_created_gmt >= %s
						  AND o.date_created_gmt < %s
						  AND o.customer_id > 0",
						array_merge( $statuses, array( $start_param, $end_param ) )
					)
				);
			}

			$result['returning']['revenue']   = (float) $ret_gross->gross_revenue - (float) $ret_refunds;
			$result['returning']['orders']    = (int) $ret_gross->total_orders;
			$result['returning']['customers'] = (int) $ret_gross->total_customers;

			// --- Guest orders ---

			$guest_gross = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COALESCE(SUM(total_amount), 0) AS gross_revenue,
						COUNT(id) AS total_orders
					FROM {$wpdb->prefix}wc_orders
					WHERE type = 'shop_order'
					  AND status IN ({$status_ph})
					  AND date_created_gmt >= %s
					  AND date_created_gmt < %s
					  AND customer_id = 0",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);


			$guest_refunds = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(ABS(SUM(r.total_amount)), 0)
					FROM {$wpdb->prefix}wc_orders r
					INNER JOIN {$wpdb->prefix}wc_orders o ON r.parent_order_id = o.id
					WHERE r.type = 'shop_order_refund'
					  AND o.type = 'shop_order'
					  AND o.status IN ({$status_ph})
					  AND o.date_created_gmt >= %s
					  AND o.date_created_gmt < %s
					  AND o.customer_id = 0",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);

			$result['guest']['revenue'] = (float) $guest_gross->gross_revenue - (float) $guest_refunds;
			$result['guest']['orders']  = (int) $guest_gross->total_orders;
		} else {
			// Legacy path.
			// --- New customers ---
			if ( ! empty( $new_ids ) ) {
				$id_ph = implode( ', ', array_fill( 0, count( $new_ids ), '%d' ) );

	
				$new_gross = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT
							COALESCE(SUM(CAST(pm_total.meta_value AS DECIMAL(12,2))), 0) AS gross_revenue,
							COUNT(DISTINCT p.ID) AS total_orders,
							COUNT(DISTINCT pm_cust.meta_value) AS total_customers
						FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->postmeta} pm_total
							ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
						INNER JOIN {$wpdb->postmeta} pm_cust
							ON p.ID = pm_cust.post_id AND pm_cust.meta_key = '_customer_user'
						WHERE p.post_type = 'shop_order'
						  AND p.post_status IN ({$status_ph})
						  AND p.post_date_gmt >= %s
						  AND p.post_date_gmt < %s
						  AND pm_cust.meta_value IN ({$id_ph})",
						array_merge( $statuses, array( $start_param, $end_param ), $new_ids )
					)
				);

	
				$new_refunds = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE(ABS(SUM(CAST(pm_ref.meta_value AS DECIMAL(12,2)))), 0)
						FROM {$wpdb->posts} r
						INNER JOIN {$wpdb->posts} o ON r.post_parent = o.ID
						INNER JOIN {$wpdb->postmeta} pm_ref
							ON r.ID = pm_ref.post_id AND pm_ref.meta_key = '_order_total'
						INNER JOIN {$wpdb->postmeta} pm_cust
							ON o.ID = pm_cust.post_id AND pm_cust.meta_key = '_customer_user'
						WHERE r.post_type = 'shop_order_refund'
						  AND o.post_type = 'shop_order'
						  AND o.post_status IN ({$status_ph})
						  AND o.post_date_gmt >= %s
						  AND o.post_date_gmt < %s
						  AND pm_cust.meta_value IN ({$id_ph})",
						array_merge( $statuses, array( $start_param, $end_param ), $new_ids )
					)
				);

				$result['new']['revenue']   = (float) $new_gross->gross_revenue - (float) $new_refunds;
				$result['new']['orders']    = (int) $new_gross->total_orders;
				$result['new']['customers'] = (int) $new_gross->total_customers;
			}

			// --- Returning customers ---
			if ( ! empty( $new_ids ) ) {
				$id_ph = implode( ', ', array_fill( 0, count( $new_ids ), '%d' ) );

	
				$ret_gross = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT
							COALESCE(SUM(CAST(pm_total.meta_value AS DECIMAL(12,2))), 0) AS gross_revenue,
							COUNT(DISTINCT p.ID) AS total_orders,
							COUNT(DISTINCT CASE WHEN pm_cust.meta_value > 0 THEN pm_cust.meta_value END) AS total_customers
						FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->postmeta} pm_total
							ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
						INNER JOIN {$wpdb->postmeta} pm_cust
							ON p.ID = pm_cust.post_id AND pm_cust.meta_key = '_customer_user'
						WHERE p.post_type = 'shop_order'
						  AND p.post_status IN ({$status_ph})
						  AND p.post_date_gmt >= %s
						  AND p.post_date_gmt < %s
						  AND pm_cust.meta_value > 0
						  AND pm_cust.meta_value NOT IN ({$id_ph})",
						array_merge( $statuses, array( $start_param, $end_param ), $new_ids )
					)
				);

	
				$ret_refunds = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE(ABS(SUM(CAST(pm_ref.meta_value AS DECIMAL(12,2)))), 0)
						FROM {$wpdb->posts} r
						INNER JOIN {$wpdb->posts} o ON r.post_parent = o.ID
						INNER JOIN {$wpdb->postmeta} pm_ref
							ON r.ID = pm_ref.post_id AND pm_ref.meta_key = '_order_total'
						INNER JOIN {$wpdb->postmeta} pm_cust
							ON o.ID = pm_cust.post_id AND pm_cust.meta_key = '_customer_user'
						WHERE r.post_type = 'shop_order_refund'
						  AND o.post_type = 'shop_order'
						  AND o.post_status IN ({$status_ph})
						  AND o.post_date_gmt >= %s
						  AND o.post_date_gmt < %s
						  AND pm_cust.meta_value > 0
						  AND pm_cust.meta_value NOT IN ({$id_ph})",
						array_merge( $statuses, array( $start_param, $end_param ), $new_ids )
					)
				);
			} else {
	
				$ret_gross = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT
							COALESCE(SUM(CAST(pm_total.meta_value AS DECIMAL(12,2))), 0) AS gross_revenue,
							COUNT(DISTINCT p.ID) AS total_orders,
							COUNT(DISTINCT CASE WHEN pm_cust.meta_value > 0 THEN pm_cust.meta_value END) AS total_customers
						FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->postmeta} pm_total
							ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
						INNER JOIN {$wpdb->postmeta} pm_cust
							ON p.ID = pm_cust.post_id AND pm_cust.meta_key = '_customer_user'
						WHERE p.post_type = 'shop_order'
						  AND p.post_status IN ({$status_ph})
						  AND p.post_date_gmt >= %s
						  AND p.post_date_gmt < %s
						  AND pm_cust.meta_value > 0",
						array_merge( $statuses, array( $start_param, $end_param ) )
					)
				);

	
				$ret_refunds = $wpdb->get_var(
					$wpdb->prepare(
						"SELECT COALESCE(ABS(SUM(CAST(pm_ref.meta_value AS DECIMAL(12,2)))), 0)
						FROM {$wpdb->posts} r
						INNER JOIN {$wpdb->posts} o ON r.post_parent = o.ID
						INNER JOIN {$wpdb->postmeta} pm_ref
							ON r.ID = pm_ref.post_id AND pm_ref.meta_key = '_order_total'
						INNER JOIN {$wpdb->postmeta} pm_cust
							ON o.ID = pm_cust.post_id AND pm_cust.meta_key = '_customer_user'
						WHERE r.post_type = 'shop_order_refund'
						  AND o.post_type = 'shop_order'
						  AND o.post_status IN ({$status_ph})
						  AND o.post_date_gmt >= %s
						  AND o.post_date_gmt < %s
						  AND pm_cust.meta_value > 0",
						array_merge( $statuses, array( $start_param, $end_param ) )
					)
				);
			}

			$result['returning']['revenue']   = (float) $ret_gross->gross_revenue - (float) $ret_refunds;
			$result['returning']['orders']    = (int) $ret_gross->total_orders;
			$result['returning']['customers'] = (int) $ret_gross->total_customers;

			// --- Guest orders ---

			$guest_gross = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COALESCE(SUM(CAST(pm_total.meta_value AS DECIMAL(12,2))), 0) AS gross_revenue,
						COUNT(DISTINCT p.ID) AS total_orders
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm_total
						ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
					INNER JOIN {$wpdb->postmeta} pm_cust
						ON p.ID = pm_cust.post_id AND pm_cust.meta_key = '_customer_user'
					WHERE p.post_type = 'shop_order'
					  AND p.post_status IN ({$status_ph})
					  AND p.post_date_gmt >= %s
					  AND p.post_date_gmt < %s
					  AND pm_cust.meta_value = 0",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);


			$guest_refunds = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COALESCE(ABS(SUM(CAST(pm_ref.meta_value AS DECIMAL(12,2)))), 0)
					FROM {$wpdb->posts} r
					INNER JOIN {$wpdb->posts} o ON r.post_parent = o.ID
					INNER JOIN {$wpdb->postmeta} pm_ref
						ON r.ID = pm_ref.post_id AND pm_ref.meta_key = '_order_total'
					INNER JOIN {$wpdb->postmeta} pm_cust
						ON o.ID = pm_cust.post_id AND pm_cust.meta_key = '_customer_user'
					WHERE r.post_type = 'shop_order_refund'
					  AND o.post_type = 'shop_order'
					  AND o.post_status IN ({$status_ph})
					  AND o.post_date_gmt >= %s
					  AND o.post_date_gmt < %s
					  AND pm_cust.meta_value = 0",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);

			$result['guest']['revenue'] = (float) $guest_gross->gross_revenue - (float) $guest_refunds;
			$result['guest']['orders']  = (int) $guest_gross->total_orders;
		}

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Get customer IDs whose first-ever qualifying order is within the date range.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array Array of int customer IDs.
	 */
	private function pdwchs_get_new_customer_ids( $start_date, $end_date ) {
		global $wpdb;

		$end_exclusive = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) );
		$start_param   = $start_date . ' 00:00:00';
		$end_param     = $end_exclusive . ' 00:00:00';

		$statuses     = self::REVENUE_STATUSES_SQL;
		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		if ( $this->pdwchs_is_hpos_enabled() ) {

			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT customer_id
					FROM {$wpdb->prefix}wc_orders
					WHERE type = 'shop_order'
					  AND status IN ({$placeholders})
					  AND customer_id > 0
					GROUP BY customer_id
					HAVING MIN(date_created_gmt) >= %s
					   AND MIN(date_created_gmt) < %s",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);
		} else {

			$ids = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT pm.meta_value AS customer_id
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm
						ON p.ID = pm.post_id AND pm.meta_key = '_customer_user'
					WHERE p.post_type = 'shop_order'
					  AND p.post_status IN ({$placeholders})
					  AND pm.meta_value > 0
					GROUP BY pm.meta_value
					HAVING MIN(p.post_date_gmt) >= %s
					   AND MIN(p.post_date_gmt) < %s",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);
		}

		return array_map( 'intval', $ids );
	}

	/**
	 * Validate and normalize date parameters.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array|false { start: string, end: string } or false if invalid.
	 */
	private function pdwchs_validate_and_normalize_dates( $start_date, $end_date ) {
		$start_dt = DateTime::createFromFormat( 'Y-m-d', $start_date );
		$end_dt   = DateTime::createFromFormat( 'Y-m-d', $end_date );

		if ( ! $start_dt || $start_dt->format( 'Y-m-d' ) !== $start_date ) {
			return false;
		}

		if ( ! $end_dt || $end_dt->format( 'Y-m-d' ) !== $end_date ) {
			return false;
		}

		// Swap if start > end.
		if ( $start_dt > $end_dt ) {
			$temp       = $start_date;
			$start_date = $end_date;
			$end_date   = $temp;
		}

		return array(
			'start' => $start_date,
			'end'   => $end_date,
		);
	}
	/**
	 * Return zeroed new-vs-returning structure.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	private function pdwchs_zeroed_new_vs_returning() {
		return array(
			'new'       => array(
				'revenue'   => 0.0,
				'orders'    => 0,
				'customers' => 0,
			),
			'returning' => array(
				'revenue'   => 0.0,
				'orders'    => 0,
				'customers' => 0,
			),
			'guest'     => array(
				'revenue'   => 0.0,
				'orders'    => 0,
			),
		);
	}

}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
