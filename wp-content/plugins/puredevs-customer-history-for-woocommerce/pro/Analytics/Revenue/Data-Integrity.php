<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// Reason: All queries use $wpdb->prepare(). Results cached via transients.
// Table prefix is an internal constant, never user input.

/**
 * Revenue data integrity class.
 *
 * Cross-checks cached totals from PDWCHS_Revenue_Data::pdwchs_get_revenue_overview()
 * against a fresh wc_get_orders() recomputation for the same date range and statuses.
 * Also provides diagnostic details (unusual statuses, partial refunds, currency mismatches).
 *
 * Pure data class â€” no HTML, no AJAX, no DB writes. Read-only per CLAUDE.md rule #8.
 *
 * @since 2.2.0
 */
class PDWCHS_Data_Integrity {

	/** Default mismatch threshold (%). Override via 'pdwchs_data_integrity_threshold' filter. */
	const MISMATCH_THRESHOLD_PCT = 1.0;

	/** Boundary between 'low' and 'high' severity mismatches (%). */
	const HIGH_SEVERITY_PCT = 5.0;

	/** Order statuses that are NOT counted in revenue but are surfaced as diagnostic candidates. */
	const UNUSUAL_STATUSES = array( 'pending', 'on-hold', 'cancelled', 'refunded', 'failed' );

	/** Transient TTL for the comparison result (short â€” admin re-checks are expected to be fresh). */
	const CACHE_TTL = 5 * MINUTE_IN_SECONDS;

	/** Transient TTL for the diagnostic detail rows (row-by-row query; less time-sensitive). */
	const DETAILS_CACHE_TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Validate and normalize date parameters.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array|false { start: string, end: string } or false if invalid.
	 */
	private function pdwchs_validate_and_normalize_dates( string $start_date, string $end_date ) {
		$start_dt = DateTime::createFromFormat( 'Y-m-d', $start_date );
		$end_dt   = DateTime::createFromFormat( 'Y-m-d', $end_date );

		if ( ! $start_dt || $start_dt->format( 'Y-m-d' ) !== $start_date ) {
			return false;
		}

		if ( ! $end_dt || $end_dt->format( 'Y-m-d' ) !== $end_date ) {
			return false;
		}

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
	 * Fresh, independent recomputation via wc_get_orders() CRUD path.
	 *
	 * Deliberately uses a different query path from PDWCHS_Revenue_Data (which uses
	 * raw $wpdb) so the two sides of the comparison are genuinely independent.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array { gross_revenue: float, net_revenue: float, total_orders: int, total_refunds: float }
	 */
	private function pdwchs_compute_wc_totals( string $start_date, string $end_date ) : array {
		$order_ids = wc_get_orders(
			array(
				'type'         => 'shop_order',
				'status'       => PDWCHS_Revenue_Data_Free::REVENUE_STATUSES_WC,
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'return'       => 'ids',
			)
		);

		if ( empty( $order_ids ) ) {
			return array(
				'gross_revenue' => 0.0,
				'net_revenue'   => 0.0,
				'total_orders'  => 0,
				'total_refunds' => 0.0,
			);
		}

		$gross_revenue = 0.0;
		$total_refunds = 0.0;
		$total_orders  = 0;

		foreach ( $order_ids as $id ) {
			$order = wc_get_order( $id );
			if ( ! $order || is_a( $order, 'WC_Order_Refund' ) ) {
				continue;
			}
			$gross_revenue += (float) $order->get_total();
			$total_refunds += (float) $order->get_total_refunded();
			++$total_orders;
		}

		return array(
			'gross_revenue' => $gross_revenue,
			'net_revenue'   => $gross_revenue - $total_refunds,
			'total_orders'  => $total_orders,
			'total_refunds' => $total_refunds,
		);
	}

	/**
	 * Zero-filled comparison result for invalid dates or empty ranges.
	 *
	 * is_verified = true because a store with no orders is not a mismatch.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array
	 */
	private function pdwchs_zeroed_comparison( string $start_date, string $end_date ) : array {
		$empty_calc = array(
			'gross_revenue' => 0.0,
			'net_revenue'   => 0.0,
			'total_orders'  => 0,
			'total_refunds' => 0.0,
		);
		return array(
			'is_verified'   => true,
			'severity'      => 'verified',
			'our_calc'      => $empty_calc,
			'wc_calc'       => $empty_calc,
			'diff'          => array(
				'gross_revenue' => 0.0,
				'net_revenue'   => 0.0,
				'total_orders'  => 0,
				'total_refunds' => 0.0,
			),
			'diff_pct'      => array(
				'gross_revenue' => 0.0,
				'net_revenue'   => 0.0,
			),
			'threshold_pct' => (float) apply_filters( 'pdwchs_data_integrity_threshold', self::MISMATCH_THRESHOLD_PCT ),
			'checked_at'    => gmdate( 'Y-m-d H:i:s' ),
			'date_range'    => array( 'start' => $start_date, 'end' => $end_date ),
		);
	}

	/**
	 * Query orders in unusual statuses (not counted in revenue; diagnostic only).
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @param int    $limit      Maximum rows to return.
	 * @return array
	 */
	private function pdwchs_query_unusual_status_orders( string $start_date, string $end_date, int $limit ) : array {
		$order_ids = wc_get_orders(
			array(
				'type'         => 'shop_order',
				'status'       => self::UNUSUAL_STATUSES,
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => $limit,
				'return'       => 'ids',
				'orderby'      => 'date',
				'order'        => 'DESC',
			)
		);

		$rows = array();
		foreach ( $order_ids as $id ) {
			$order = wc_get_order( $id );
			if ( ! $order || is_a( $order, 'WC_Order_Refund' ) ) {
				continue;
			}
			$rows[] = array(
				'order_id' => $order->get_id(),
				'status'   => $order->get_status(),
				'total'    => (float) $order->get_total(),
				'date'     => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
				'edit_url' => $order->get_edit_order_url(),
			);
		}

		return $rows;
	}

	/**
	 * Query revenue-status orders with partial refunds (0 < refunded < total).
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @param int    $limit      Maximum rows to return after scanning all qualifying orders.
	 * @return array
	 */
	private function pdwchs_query_partial_refund_orders( string $start_date, string $end_date, int $limit ) : array {
		$order_ids = wc_get_orders(
			array(
				'type'         => 'shop_order',
				'status'       => PDWCHS_Revenue_Data_Free::REVENUE_STATUSES_WC,
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'return'       => 'ids',
				'orderby'      => 'date',
				'order'        => 'DESC',
			)
		);

		$rows = array();
		foreach ( $order_ids as $id ) {
			$order = wc_get_order( $id );
			if ( ! $order || is_a( $order, 'WC_Order_Refund' ) ) {
				continue;
			}
			$refunded = (float) $order->get_total_refunded();
			$total    = (float) $order->get_total();
			if ( $refunded > 0.0 && $refunded < $total ) {
				$rows[] = array(
					'order_id'    => $order->get_id(),
					'order_total' => $total,
					'refunded'    => $refunded,
					'pct'         => round( ( $refunded / $total ) * 100, 2 ),
					'date'        => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
					'edit_url'    => $order->get_edit_order_url(),
				);
			}
		}

		return array_slice( $rows, 0, $limit );
	}

	/**
	 * Query revenue-status orders whose currency differs from the store default.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @param int    $limit      Maximum rows to return after scanning all orders.
	 * @return array
	 */
	private function pdwchs_query_currency_mismatch_orders( string $start_date, string $end_date, int $limit ) : array {
		$store_currency = get_woocommerce_currency();

		$order_ids = wc_get_orders(
			array(
				'type'         => 'shop_order',
				'status'       => PDWCHS_Revenue_Data_Free::REVENUE_STATUSES_WC,
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'return'       => 'ids',
				'orderby'      => 'date',
				'order'        => 'DESC',
			)
		);

		$rows = array();
		foreach ( $order_ids as $id ) {
			$order = wc_get_order( $id );
			if ( ! $order || is_a( $order, 'WC_Order_Refund' ) ) {
				continue;
			}
			if ( $order->get_currency() !== $store_currency ) {
				$rows[] = array(
					'order_id'       => $order->get_id(),
					'currency'       => $order->get_currency(),
					'store_currency' => $store_currency,
					'total'          => (float) $order->get_total(),
					'date'           => $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '',
					'edit_url'       => $order->get_edit_order_url(),
				);
			}
		}

		return array_slice( $rows, 0, $limit );
	}

	/**
	 * Compare cached revenue overview totals against a fresh wc_get_orders() recomputation.
	 *
	 * Returns is_verified=true when abs(diff_pct) < threshold for both gross and net revenue.
	 * Caches result for 5 minutes (short TTL â€” admins re-check after correcting data).
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array {
	 *     @type bool   $is_verified   True when both gross and net diff_pct are within threshold.
	 *     @type string $severity      'verified' | 'low' | 'high'.
	 *     @type array  $our_calc      { gross_revenue, net_revenue, total_orders, total_refunds }.
	 *     @type array  $wc_calc       Same shape as our_calc.
	 *     @type array  $diff          Signed wc - our differences per metric.
	 *     @type array  $diff_pct      Percentage differences for gross and net.
	 *     @type float  $threshold_pct Resolved threshold at time of call.
	 *     @type string $checked_at    UTC timestamp Y-m-d H:i:s.
	 *     @type array  $date_range    { start: string, end: string }.
	 * }
	 */
	public function pdwchs_compare_revenue( string $start_date, string $end_date ) : array {
		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			return $this->pdwchs_zeroed_comparison( $start_date, $end_date );
		}

		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$cache_key = 'pdwchs_integrity_' . md5( $start_date . $end_date );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$threshold = (float) apply_filters( 'pdwchs_data_integrity_threshold', self::MISMATCH_THRESHOLD_PCT );

		$revenue_data = new PDWCHS_Revenue_Data_Free();
		$overview     = $revenue_data->pdwchs_get_revenue_overview( $start_date, $end_date );

		$our_calc = array(
			'gross_revenue' => (float) $overview['gross_revenue'],
			'net_revenue'   => (float) $overview['net_revenue'],
			'total_orders'  => (int)   $overview['total_orders'],
			'total_refunds' => (float) $overview['total_refunds'],
		);

		$wc_calc = $this->pdwchs_compute_wc_totals( $start_date, $end_date );

		$diff = array(
			'gross_revenue' => round( $wc_calc['gross_revenue'] - $our_calc['gross_revenue'], 2 ),
			'net_revenue'   => round( $wc_calc['net_revenue']   - $our_calc['net_revenue'],   2 ),
			'total_orders'  => $wc_calc['total_orders']  - $our_calc['total_orders'],
			'total_refunds' => round( $wc_calc['total_refunds'] - $our_calc['total_refunds'],  2 ),
		);

		$diff_pct = array(
			'gross_revenue' => round( ( $diff['gross_revenue'] / max( 1, $our_calc['gross_revenue'] ) ) * 100, 2 ),
			'net_revenue'   => round( ( $diff['net_revenue']   / max( 1, $our_calc['net_revenue']   ) ) * 100, 2 ),
		);

		$max_abs_pct = max( abs( $diff_pct['gross_revenue'] ), abs( $diff_pct['net_revenue'] ) );
		$is_verified = $max_abs_pct < $threshold;
		if ( $is_verified ) {
			$severity = 'verified';
		} elseif ( $max_abs_pct < self::HIGH_SEVERITY_PCT ) {
			$severity = 'low';
		} else {
			$severity = 'high';
		}

		$result = array(
			'is_verified'   => $is_verified,
			'severity'      => $severity,
			'our_calc'      => $our_calc,
			'wc_calc'       => $wc_calc,
			'diff'          => $diff,
			'diff_pct'      => $diff_pct,
			'threshold_pct' => $threshold,
			'checked_at'    => gmdate( 'Y-m-d H:i:s' ),
			'date_range'    => array( 'start' => $start_date, 'end' => $end_date ),
		);

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Return diagnostic detail rows across three buckets: unusual statuses,
	 * partial refunds, and currency mismatches.
	 *
	 * Caches result for 30 minutes (heavier row-by-row queries; stale listing acceptable).
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @param int    $limit      Maximum rows per bucket (default 100; AJAX layer clamps to [1, 500]).
	 * @return array {
	 *     @type array $unusual_statuses    Rows: { order_id, status, total, date, edit_url }.
	 *     @type array $partial_refunds     Rows: { order_id, order_total, refunded, pct, date, edit_url }.
	 *     @type array $currency_mismatches Rows: { order_id, currency, store_currency, total, date, edit_url }.
	 *     @type int   $total_flagged       Total row count across all three buckets.
	 * }
	 */
	public function pdwchs_get_diagnostic_details(
		string $start_date,
		string $end_date,
		int    $limit = 100
	) : array {
		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			return array(
				'unusual_statuses'    => array(),
				'partial_refunds'     => array(),
				'currency_mismatches' => array(),
				'total_flagged'       => 0,
			);
		}

		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$cache_key = 'pdwchs_integrity_details_' . md5( $start_date . $end_date . $limit );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$unusual  = $this->pdwchs_query_unusual_status_orders( $start_date, $end_date, $limit );
		$partial  = $this->pdwchs_query_partial_refund_orders( $start_date, $end_date, $limit );
		$currency = $this->pdwchs_query_currency_mismatch_orders( $start_date, $end_date, $limit );

		$result = array(
			'unusual_statuses'    => $unusual,
			'partial_refunds'     => $partial,
			'currency_mismatches' => $currency,
			'total_flagged'       => count( $unusual ) + count( $partial ) + count( $currency ),
		);

		set_transient( $cache_key, $result, self::DETAILS_CACHE_TTL );

		return $result;
	}
}


/**
 * Data Integrity AJAX Coordinator
 *
 * Registers AJAX handlers for Feature 1.6 revenue data integrity check
 * and delegates data retrieval to PDWCHS_Data_Integrity.
 *
 * @since      2.2.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes/analytics
 */
class PDWCHS_Data_Integrity_Ajax {

	/**
	 * Data integrity query instance.
	 *
	 * @since  2.2.0
	 * @access private
	 * @var    PDWCHS_Data_Integrity $data_integrity
	 */
	private $data_integrity;

	/**
	 * Constructor. Instantiates dependency and registers hooks.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		$this->data_integrity = new PDWCHS_Data_Integrity();
		$this->pdwchs_register_hooks();
	}

	/**
	 * Register wp_ajax_ hooks. No nopriv variants â€” admin-only.
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_register_hooks() {
		add_action( 'wp_ajax_pdwchs_run_data_integrity_check',   [ $this, 'pdwchs_ajax_run_check' ] );
		add_action( 'wp_ajax_pdwchs_get_data_integrity_details', [ $this, 'pdwchs_ajax_get_details' ] );
	}

	/**
	 * AJAX: run a revenue integrity comparison for a date range.
	 *
	 * POST params: nonce, start_date (Y-m-d), end_date (Y-m-d)
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_ajax_run_check() {
		check_ajax_referer( 'pdwchs_data_integrity_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ), 403 );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date   = isset( $_POST['end_date'] )   ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) )   : '';

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date )
		  || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		$result = $this->data_integrity->pdwchs_compare_revenue( $start_date, $end_date );

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: fetch diagnostic details (flagged orders) for a date range.
	 *
	 * POST params: nonce, start_date (Y-m-d), end_date (Y-m-d), limit (int, clamped 1â€“500)
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_ajax_get_details() {
		check_ajax_referer( 'pdwchs_data_integrity_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ), 403 );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date   = isset( $_POST['end_date'] )   ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) )   : '';

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date )
		  || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		$limit = isset( $_POST['limit'] ) ? absint( $_POST['limit'] ) : 100;
		$limit = max( 1, min( 500, $limit ) );

		$result = $this->data_integrity->pdwchs_get_diagnostic_details( $start_date, $end_date, $limit );

		wp_send_json_success( $result );
	}
}
