<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Revenue data query class (Free tier) — overview KPIs and over-time series.
 *
 * Contains pdwchs_get_revenue_overview() and pdwchs_get_revenue_over_time()
 * carved out of PDWCHS_Revenue_Data so the Free revenue dashboard widget
 * can supply revenue KPIs after PDWCHS_Revenue_Data is deleted in Chunk 1B.
 *
 * @since 2.2.0
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// Reason: All queries use $wpdb->prepare() with dynamically built placeholder arrays.
// All public methods cache results via transients. Interpolated variables are internal
// (table prefixes, status placeholders, granularity SQL fragments) — never user input.
if ( ! class_exists( 'PDWCHS_Revenue_Data_Free' ) ) :
class PDWCHS_Revenue_Data_Free {

	/**
	 * Order statuses for $wpdb queries (with wc- prefix).
	 *
	 * @var array
	 */
	const REVENUE_STATUSES_SQL = array( 'wc-completed', 'wc-processing' );

	/**
	 * Order statuses for wc_get_orders() (without prefix).
	 *
	 * @var array
	 */
	const REVENUE_STATUSES_WC = array( 'completed', 'processing' );

	/**
	 * Transient cache TTL in seconds (30 minutes).
	 *
	 * @var int
	 */
	const CACHE_TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Constructor.
	 */
	public function __construct() {
	}

	/**
	 * Get revenue overview KPIs with period-over-period comparison.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d, inclusive.
	 * @param string $end_date   Y-m-d, inclusive.
	 * @return array
	 */
	public function pdwchs_get_revenue_overview( $start_date, $end_date ) {
		$start_date = PDWCHS_Data_Window::pdwchs_clamp_start( $start_date );
		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			return $this->pdwchs_zeroed_overview();
		}
		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$cache_key = $this->pdwchs_cache_key( 'overview', array( $start_date, $end_date ) ) . '_w' . PDWCHS_Data_Window::pdwchs_days();
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$current  = $this->pdwchs_query_period_metrics( $start_date, $end_date );
		$previous = $this->pdwchs_get_comparison_period( $start_date, $end_date );
		$prev     = $this->pdwchs_query_period_metrics( $previous['start'], $previous['end'] );

		$result = array(
			'net_revenue'     => $current['net_revenue'],
			'gross_revenue'   => $current['gross_revenue'],
			'total_refunds'   => $current['total_refunds'],
			'total_orders'    => $current['total_orders'],
			'aov'             => $current['aov'],
			'total_customers' => $current['total_customers'],
			'comparison'      => array(
				'net_revenue_change'     => $this->pdwchs_calc_percentage_change( $current['net_revenue'], $prev['net_revenue'] ),
				'total_orders_change'    => $this->pdwchs_calc_percentage_change( $current['total_orders'], $prev['total_orders'] ),
				'aov_change'             => $this->pdwchs_calc_percentage_change( $current['aov'], $prev['aov'] ),
				'total_customers_change' => $this->pdwchs_calc_percentage_change( $current['total_customers'], $prev['total_customers'] ),
			),
		);

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Get revenue over time bucketed by granularity.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date  Y-m-d.
	 * @param string $end_date    Y-m-d.
	 * @param string $granularity One of 'daily', 'weekly', 'monthly'.
	 * @return array
	 */
	public function pdwchs_get_revenue_over_time( $start_date, $end_date, $granularity ) {
		$start_date = PDWCHS_Data_Window::pdwchs_clamp_start( $start_date );
		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			return array(
				'granularity' => 'daily',
				'buckets'     => array(),
			);
		}
		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$valid_granularities = array( 'daily', 'weekly', 'monthly' );
		if ( ! in_array( $granularity, $valid_granularities, true ) ) {
			$granularity = 'daily';
		}

		$cache_key = $this->pdwchs_cache_key( 'overtime', array( $start_date, $end_date, $granularity ) ) . '_w' . PDWCHS_Data_Window::pdwchs_days();
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$end_exclusive = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) );
		$start_param   = $start_date . ' 00:00:00';
		$end_param     = $end_exclusive . ' 00:00:00';

		$statuses     = self::REVENUE_STATUSES_SQL;
		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		if ( $this->pdwchs_is_hpos_enabled() ) {
			$date_col = 'date_created_gmt';

			$select_label = $this->pdwchs_get_granularity_select( $granularity, $date_col );
			$group_by     = $this->pdwchs_get_granularity_group_by( $granularity, $date_col );

			// Gross revenue + order count per bucket.

			$gross_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$select_label} AS period_label,
						COALESCE(SUM(total_amount), 0) AS gross_revenue,
						COUNT(id) AS order_count
					FROM {$wpdb->prefix}wc_orders
					WHERE type = 'shop_order'
					  AND status IN ({$placeholders})
					  AND {$date_col} >= %s
					  AND {$date_col} < %s
					GROUP BY {$group_by}",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);

			// Refunds per bucket (attributed to parent order date).
			$parent_date_col   = 'o.' . $date_col;
			$refund_label      = $this->pdwchs_get_granularity_select( $granularity, $parent_date_col );
			$refund_group_by   = $this->pdwchs_get_granularity_group_by( $granularity, $parent_date_col );


			$refund_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$refund_label} AS period_label,
						COALESCE(ABS(SUM(r.total_amount)), 0) AS total_refunds
					FROM {$wpdb->prefix}wc_orders r
					INNER JOIN {$wpdb->prefix}wc_orders o ON r.parent_order_id = o.id
					WHERE r.type = 'shop_order_refund'
					  AND o.type = 'shop_order'
					  AND o.status IN ({$placeholders})
					  AND o.{$date_col} >= %s
					  AND o.{$date_col} < %s
					GROUP BY {$refund_group_by}",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);
		} else {
			$date_col = 'post_date_gmt';

			$select_label = $this->pdwchs_get_granularity_select( $granularity, 'p.' . $date_col );
			$group_by     = $this->pdwchs_get_granularity_group_by( $granularity, 'p.' . $date_col );


			$gross_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$select_label} AS period_label,
						COALESCE(SUM(CAST(pm_total.meta_value AS DECIMAL(12,2))), 0) AS gross_revenue,
						COUNT(DISTINCT p.ID) AS order_count
					FROM {$wpdb->posts} p
					INNER JOIN {$wpdb->postmeta} pm_total
						ON p.ID = pm_total.post_id AND pm_total.meta_key = '_order_total'
					WHERE p.post_type = 'shop_order'
					  AND p.post_status IN ({$placeholders})
					  AND p.{$date_col} >= %s
					  AND p.{$date_col} < %s
					GROUP BY {$group_by}",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);

			$parent_date_col = 'o.' . $date_col;
			$refund_label    = $this->pdwchs_get_granularity_select( $granularity, $parent_date_col );
			$refund_group_by = $this->pdwchs_get_granularity_group_by( $granularity, $parent_date_col );


			$refund_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT {$refund_label} AS period_label,
						COALESCE(ABS(SUM(CAST(pm_ref.meta_value AS DECIMAL(12,2)))), 0) AS total_refunds
					FROM {$wpdb->posts} r
					INNER JOIN {$wpdb->posts} o ON r.post_parent = o.ID
					INNER JOIN {$wpdb->postmeta} pm_ref
						ON r.ID = pm_ref.post_id AND pm_ref.meta_key = '_order_total'
					WHERE r.post_type = 'shop_order_refund'
					  AND o.post_type = 'shop_order'
					  AND o.post_status IN ({$placeholders})
					  AND o.{$date_col} >= %s
					  AND o.{$date_col} < %s
					GROUP BY {$refund_group_by}",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);
		}

		// Index results by period label.
		$gross_by_period  = array();
		$refund_by_period = array();

		if ( $gross_rows ) {
			foreach ( $gross_rows as $row ) {
				$gross_by_period[ $row->period_label ] = array(
					'gross_revenue' => (float) $row->gross_revenue,
					'order_count'   => (int) $row->order_count,
				);
			}
		}

		if ( $refund_rows ) {
			foreach ( $refund_rows as $row ) {
				$refund_by_period[ $row->period_label ] = (float) $row->total_refunds;
			}
		}

		// Generate complete period list and merge.
		$period_list = $this->pdwchs_generate_period_list( $start_date, $end_date, $granularity );
		$buckets     = array();

		foreach ( $period_list as $label ) {
			$gross  = isset( $gross_by_period[ $label ] ) ? $gross_by_period[ $label ] : array(
				'gross_revenue' => 0.0,
				'order_count'   => 0,
			);
			$refund = isset( $refund_by_period[ $label ] ) ? $refund_by_period[ $label ] : 0.0;

			$buckets[] = array(
				'date'          => $label,
				'gross_revenue' => $gross['gross_revenue'],
				'refunds'       => $refund,
				'net_revenue'   => $gross['gross_revenue'] - $refund,
				'order_count'   => $gross['order_count'],
			);
		}

		$result = array(
			'granularity' => $granularity,
			'buckets'     => $buckets,
		);

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Get the repeat purchase rate for a date range with period-over-period comparison.
	 *
	 * A customer counts as "repeat" when they placed two or more revenue-status orders
	 * inside the range. The metric is window-scoped, not lifetime.
	 *
	 * @since 3.1.0
	 *
	 * @param string $start_date Y-m-d, inclusive.
	 * @param string $end_date   Y-m-d, inclusive.
	 * @return array
	 */
	public function pdwchs_get_repeat_purchase_rate( $start_date, $end_date ) {
		$start_date = PDWCHS_Data_Window::pdwchs_clamp_start( $start_date );
		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			return $this->pdwchs_zeroed_repeat_rate();
		}
		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$cache_key = $this->pdwchs_cache_key( 'repeat', array( $start_date, $end_date ) ) . '_w' . PDWCHS_Data_Window::pdwchs_days();
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$current  = $this->pdwchs_query_repeat_counts( $start_date, $end_date );
		$previous = $this->pdwchs_get_comparison_period( $start_date, $end_date );
		$prev     = $this->pdwchs_query_repeat_counts( $previous['start'], $previous['end'] );

		$rate = $current['total_customers'] > 0
			? round( ( $current['repeat_customers'] / $current['total_customers'] ) * 100, 1 )
			: 0.0;

		$previous_rate = $prev['total_customers'] > 0
			? round( ( $prev['repeat_customers'] / $prev['total_customers'] ) * 100, 1 )
			: 0.0;

		$change = $this->pdwchs_calc_percentage_change( $rate, $previous_rate );

		$result = array(
			'rate'             => (float) $rate,
			'repeat_customers' => (int) $current['repeat_customers'],
			'total_customers'  => (int) $current['total_customers'],
			'previous_rate'    => (float) $previous_rate,
			'change'           => $change,
		);

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Check if HPOS (Custom Order Tables) is enabled.
	 *
	 * @since 2.2.0
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
	 * @since 2.2.0
	 *
	 * @param string $method Method identifier.
	 * @param array  $params Parameters to hash.
	 * @return string
	 */
	private function pdwchs_cache_key( $method, $params ) {
		return 'pdwchs_rev_' . $method . '_' . md5( wp_json_encode( $params ) );
	}

	/**
	 * Calculate the comparison period (same length, immediately preceding).
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array { start: string, end: string }
	 */
	private function pdwchs_get_comparison_period( $start_date, $end_date ) {
		$start = new DateTime( $start_date );
		$end   = new DateTime( $end_date );
		$days  = (int) $start->diff( $end )->days + 1;

		$prev_end   = ( clone $start )->modify( '-1 day' );
		$prev_start = ( clone $prev_end )->modify( '-' . ( $days - 1 ) . ' days' );

		return array(
			'start' => $prev_start->format( 'Y-m-d' ),
			'end'   => $prev_end->format( 'Y-m-d' ),
		);
	}

	/**
	 * Calculate percentage change between two values.
	 *
	 * @since 2.2.0
	 *
	 * @param float|int $current  Current period value.
	 * @param float|int $previous Previous period value.
	 * @return float|null Null if previous is zero.
	 */
	private function pdwchs_calc_percentage_change( $current, $previous ) {
		if ( 0 == $previous ) {
			return null;
		}

		return round( ( ( $current - $previous ) / $previous ) * 100, 2 );
	}

	/**
	 * Query aggregate metrics for a date range.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array
	 */
	private function pdwchs_query_period_metrics( $start_date, $end_date ) {
		global $wpdb;

		$end_exclusive = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) );
		$start_param   = $start_date . ' 00:00:00';
		$end_param     = $end_exclusive . ' 00:00:00';

		$statuses     = self::REVENUE_STATUSES_SQL;
		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		if ( $this->pdwchs_is_hpos_enabled() ) {
			// HPOS: Gross revenue + counts.

			$main = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COALESCE(SUM(total_amount), 0) AS gross_revenue,
						COUNT(id) AS total_orders,
						COUNT(DISTINCT CASE WHEN customer_id > 0 THEN customer_id END) AS total_customers
					FROM {$wpdb->prefix}wc_orders
					WHERE type = 'shop_order'
					  AND status IN ({$placeholders})
					  AND date_created_gmt >= %s
					  AND date_created_gmt < %s",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);

			// HPOS: Refunds.

			$refund_total = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT
						COALESCE(ABS(SUM(r.total_amount)), 0) AS total_refunds
					FROM {$wpdb->prefix}wc_orders r
					INNER JOIN {$wpdb->prefix}wc_orders o ON r.parent_order_id = o.id
					WHERE r.type = 'shop_order_refund'
					  AND o.type = 'shop_order'
					  AND o.status IN ({$placeholders})
					  AND o.date_created_gmt >= %s
					  AND o.date_created_gmt < %s",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);
		} else {
			// Legacy: Gross revenue + counts.

			$main = $wpdb->get_row(
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
					  AND p.post_status IN ({$placeholders})
					  AND p.post_date_gmt >= %s
					  AND p.post_date_gmt < %s",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);

			// Legacy: Refunds.

			$refund_total = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT
						COALESCE(ABS(SUM(CAST(pm_ref.meta_value AS DECIMAL(12,2)))), 0) AS total_refunds
					FROM {$wpdb->posts} r
					INNER JOIN {$wpdb->posts} o ON r.post_parent = o.ID
					INNER JOIN {$wpdb->postmeta} pm_ref
						ON r.ID = pm_ref.post_id AND pm_ref.meta_key = '_order_total'
					WHERE r.post_type = 'shop_order_refund'
					  AND o.post_type = 'shop_order'
					  AND o.post_status IN ({$placeholders})
					  AND o.post_date_gmt >= %s
					  AND o.post_date_gmt < %s",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);
		}

		$gross_revenue   = (float) $main->gross_revenue;
		$total_refunds   = (float) $refund_total;
		$total_orders    = (int) $main->total_orders;
		$total_customers = (int) $main->total_customers;
		$net_revenue     = $gross_revenue - $total_refunds;
		$aov             = $total_orders > 0 ? round( $net_revenue / $total_orders, 2 ) : 0.0;

		return array(
			'gross_revenue'   => $gross_revenue,
			'total_refunds'   => $total_refunds,
			'net_revenue'     => $net_revenue,
			'total_orders'    => $total_orders,
			'total_customers' => $total_customers,
			'aov'             => $aov,
		);
	}

	/**
	 * Query distinct-customer counts for a date range.
	 *
	 * Groups revenue-status orders by customer, then counts the groups: every group
	 * is one customer, groups with two or more orders are repeat customers.
	 *
	 * @since 3.1.0
	 *
	 * @param string $start_date Y-m-d.
	 * @param string $end_date   Y-m-d.
	 * @return array
	 */
	private function pdwchs_query_repeat_counts( $start_date, $end_date ) {
		global $wpdb;

		$end_exclusive = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) );
		$start_param   = $start_date . ' 00:00:00';
		$end_param     = $end_exclusive . ' 00:00:00';

		$statuses     = self::REVENUE_STATUSES_SQL;
		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

		if ( $this->pdwchs_is_hpos_enabled() ) {
			// HPOS: one row per order, so COUNT(*) per customer is exact.

			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COUNT(*) AS total_customers,
						COALESCE(SUM(CASE WHEN order_count >= 2 THEN 1 ELSE 0 END), 0) AS repeat_customers
					FROM (
						SELECT customer_id, COUNT(*) AS order_count
						FROM {$wpdb->prefix}wc_orders
						WHERE type = 'shop_order'
						  AND status IN ({$placeholders})
						  AND date_created_gmt >= %s
						  AND date_created_gmt <  %s
						  AND customer_id > 0
						GROUP BY customer_id
					) t",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);
		} else {
			// Legacy: postmeta join can duplicate rows, so count distinct order IDs.

			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
						COUNT(*) AS total_customers,
						COALESCE(SUM(CASE WHEN order_count >= 2 THEN 1 ELSE 0 END), 0) AS repeat_customers
					FROM (
						SELECT pm_cust.meta_value AS customer_id, COUNT(DISTINCT p.ID) AS order_count
						FROM {$wpdb->posts} p
						INNER JOIN {$wpdb->postmeta} pm_cust
							ON p.ID = pm_cust.post_id AND pm_cust.meta_key = '_customer_user'
						WHERE p.post_type = 'shop_order'
						  AND p.post_status IN ({$placeholders})
						  AND p.post_date_gmt >= %s
						  AND p.post_date_gmt <  %s
						  AND pm_cust.meta_value > 0
						GROUP BY pm_cust.meta_value
					) t",
					array_merge( $statuses, array( $start_param, $end_param ) )
				)
			);
		}

		return array(
			'repeat_customers' => $row ? (int) $row->repeat_customers : 0,
			'total_customers'  => $row ? (int) $row->total_customers : 0,
		);
	}

	/**
	 * Generate an ordered list of period labels for the date range.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date  Y-m-d.
	 * @param string $end_date    Y-m-d.
	 * @param string $granularity 'daily', 'weekly', or 'monthly'.
	 * @return array Array of period label strings.
	 */
	private function pdwchs_generate_period_list( $start_date, $end_date, $granularity ) {
		$periods = array();
		$current = new DateTime( $start_date );
		$end     = new DateTime( $end_date );

		switch ( $granularity ) {
			case 'weekly':
				// Start from Monday on or before start_date.
				$weekday = (int) $current->format( 'N' );
				if ( $weekday > 1 ) {
					$current->modify( '-' . ( $weekday - 1 ) . ' days' );
				}
				while ( $current <= $end ) {
					$periods[] = $current->format( 'Y-m-d' );
					$current->modify( '+7 days' );
				}
				break;

			case 'monthly':
				while ( $current <= $end ) {
					$periods[] = $current->format( 'Y-m' );
					$current->modify( 'first day of next month' );
				}
				break;

			default: // daily
				while ( $current <= $end ) {
					$periods[] = $current->format( 'Y-m-d' );
					$current->modify( '+1 day' );
				}
				break;
		}

		return $periods;
	}

	/**
	 * Get the SQL SELECT expression for period labeling by granularity.
	 *
	 * @since 2.2.0
	 *
	 * @param string $granularity 'daily', 'weekly', or 'monthly'.
	 * @param string $date_col    The date column reference.
	 * @return string SQL expression.
	 */
	private function pdwchs_get_granularity_select( $granularity, $date_col ) {
		switch ( $granularity ) {
			case 'weekly':
				return "DATE({$date_col} - INTERVAL WEEKDAY({$date_col}) DAY)";
			case 'monthly':
				return "DATE_FORMAT({$date_col}, '%Y-%m')";
			default:
				return "DATE({$date_col})";
		}
	}

	/**
	 * Get the SQL GROUP BY expression by granularity.
	 *
	 * @since 2.2.0
	 *
	 * @param string $granularity 'daily', 'weekly', or 'monthly'.
	 * @param string $date_col    The date column reference.
	 * @return string SQL expression.
	 */
	private function pdwchs_get_granularity_group_by( $granularity, $date_col ) {
		switch ( $granularity ) {
			case 'weekly':
				return "YEARWEEK({$date_col}, 1)";
			case 'monthly':
				return "DATE_FORMAT({$date_col}, '%Y-%m')";
			default:
				return "DATE({$date_col})";
		}
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
	 * Return zeroed overview structure.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	private function pdwchs_zeroed_overview() {
		return array(
			'net_revenue'     => 0.0,
			'gross_revenue'   => 0.0,
			'total_refunds'   => 0.0,
			'total_orders'    => 0,
			'aov'             => 0.0,
			'total_customers' => 0,
			'comparison'      => array(
				'net_revenue_change'     => null,
				'total_orders_change'    => null,
				'aov_change'             => null,
				'total_customers_change' => null,
			),
		);
	}

	/**
	 * Return zeroed repeat purchase rate structure.
	 *
	 * @since 3.1.0
	 *
	 * @return array
	 */
	private function pdwchs_zeroed_repeat_rate() {
		return array(
			'rate'             => 0.0,
			'repeat_customers' => 0,
			'total_customers'  => 0,
			'previous_rate'    => 0.0,
			'change'           => null,
		);
	}
}
endif;
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
