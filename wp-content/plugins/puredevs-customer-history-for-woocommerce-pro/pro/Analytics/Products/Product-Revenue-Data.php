<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// Reason: All queries use $wpdb->prepare() with dynamically built placeholder arrays.
// All public methods cache results via transients. Interpolated variables are internal
// (table prefixes, status placeholders, granularity SQL fragments) — never user input.
class PDWCHS_Product_Revenue_Data {

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
	 * Cached result of pdwchs_use_lookup_table(), computed once per request.
	 *
	 * @var bool|null
	 */
	private $lookup_table_available = null;

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Get revenue over time per product, bucketed by granularity.
	 *
	 * @since 2.2.0
	 *
	 * @param array  $product_ids  WooCommerce product IDs.
	 * @param string $start_date   Y-m-d, inclusive.
	 * @param string $end_date     Y-m-d, inclusive.
	 * @param string $granularity  'day', 'week', or 'month'.
	 * @return array Keyed by product_id; each value is an array of period buckets.
	 */
	public function pdwchs_get_product_revenue_over_time(
		array  $product_ids,
		string $start_date,
		string $end_date,
		string $granularity = 'day'
	) : array {
		if ( empty( $product_ids ) ) {
			return array();
		}

		$product_ids = array_values( array_filter( array_map( 'absint', $product_ids ) ) );
		if ( empty( $product_ids ) ) {
			return array();
		}

		if ( ! in_array( $granularity, array( 'day', 'week', 'month' ), true ) ) {
			$granularity = 'day';
		}

		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			$result = array();
			foreach ( $product_ids as $pid ) {
				$result[ $pid ] = array();
			}
			return $result;
		}
		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$cache_key = 'pdwchs_product_revenue_' . md5( wp_json_encode( array( $product_ids, $start_date, $end_date, $granularity ) ) );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$end_exclusive = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) );
		$start_param   = $start_date . ' 00:00:00';
		$end_param     = $end_exclusive . ' 00:00:00';
		$data_by_pid   = array();

		if ( $this->pdwchs_use_lookup_table() ) {
			global $wpdb;
			$id_ph         = implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) );
			$statuses      = self::REVENUE_STATUSES_SQL;
			$status_ph     = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );
			$date_col      = 'l.date_created';
			$select_expr   = $this->pdwchs_get_granularity_select( $granularity, $date_col );
			$group_by_expr = $this->pdwchs_get_granularity_group_by( $granularity, $date_col );

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT l.product_id,
					        {$select_expr} AS period_label,
					        SUM( l.product_net_revenue ) AS revenue,
					        SUM( l.product_qty )         AS units
					 FROM   {$wpdb->prefix}wc_order_product_lookup l
					 INNER JOIN {$wpdb->prefix}wc_orders o ON l.order_id = o.id
					 WHERE  l.product_id IN ({$id_ph})
					   AND  o.status IN ({$status_ph})
					   AND  l.date_created >= %s
					   AND  l.date_created <  %s
					 GROUP BY l.product_id, {$group_by_expr}",
					array_merge( $product_ids, $statuses, array( $start_param, $end_param ) )
				),
				ARRAY_A
			);

			if ( $rows ) {
				foreach ( $rows as $row ) {
					$pid = (int) $row['product_id'];
					if ( ! isset( $data_by_pid[ $pid ] ) ) {
						$data_by_pid[ $pid ] = array();
					}
					$data_by_pid[ $pid ][ $row['period_label'] ] = array(
						'revenue' => (float) $row['revenue'],
						'units'   => (int)   $row['units'],
					);
				}
			}
		} else {
			$orders = wc_get_orders( array(
				'status'       => self::REVENUE_STATUSES_WC,
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'return'       => 'objects',
			) );

			foreach ( $orders as $order ) {
				foreach ( $order->get_items() as $item ) {
					$pid = $item->get_product_id();
					if ( ! in_array( $pid, $product_ids, true ) ) {
						continue;
					}
					$order_date = $order->get_date_created();
					if ( null === $order_date ) {
						continue;
					}
					switch ( $granularity ) {
						case 'week':
							$weekday = (int) $order_date->format( 'N' );
							$monday  = clone $order_date;
							if ( $weekday > 1 ) {
								$monday->modify( '-' . ( $weekday - 1 ) . ' days' );
							}
							$period = $monday->format( 'Y-m-d' );
							break;
						case 'month':
							$period = $order_date->format( 'Y-m' );
							break;
						default:
							$period = $order_date->format( 'Y-m-d' );
							break;
					}
					if ( ! isset( $data_by_pid[ $pid ] ) ) {
						$data_by_pid[ $pid ] = array();
					}
					if ( ! isset( $data_by_pid[ $pid ][ $period ] ) ) {
						$data_by_pid[ $pid ][ $period ] = array( 'revenue' => 0.0, 'units' => 0 );
					}
					$data_by_pid[ $pid ][ $period ]['revenue'] += (float) $item->get_total();
					$data_by_pid[ $pid ][ $period ]['units']   += (int)   $item->get_quantity();
				}
			}
		}

		$period_list = $this->pdwchs_generate_period_list( $start_date, $end_date, $granularity );
		$result      = array();

		foreach ( $product_ids as $pid ) {
			$result[ $pid ] = array();
			foreach ( $period_list as $period ) {
				if ( isset( $data_by_pid[ $pid ][ $period ] ) ) {
					$result[ $pid ][] = array(
						'period'  => $period,
						'revenue' => $data_by_pid[ $pid ][ $period ]['revenue'],
						'units'   => $data_by_pid[ $pid ][ $period ]['units'],
					);
				} else {
					$result[ $pid ][] = array(
						'period'  => $period,
						'revenue' => 0.0,
						'units'   => 0,
					);
				}
			}
		}

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Get aggregated revenue summary per product.
	 *
	 * @since 2.2.0
	 *
	 * @param array  $product_ids WooCommerce product IDs.
	 * @param string $start_date  Y-m-d, inclusive.
	 * @param string $end_date    Y-m-d, inclusive.
	 * @return array Keyed by product_id; each value has revenue, units, orders, aov.
	 */
	public function pdwchs_get_product_summary(
		array  $product_ids,
		string $start_date,
		string $end_date
	) : array {
		if ( empty( $product_ids ) ) {
			return array();
		}

		$product_ids = array_values( array_filter( array_map( 'absint', $product_ids ) ) );
		if ( empty( $product_ids ) ) {
			return array();
		}

		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			$result = array();
			foreach ( $product_ids as $pid ) {
				$result[ $pid ] = array( 'revenue' => 0.0, 'units' => 0, 'orders' => 0, 'aov' => 0.0 );
			}
			return $result;
		}
		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$cache_key = 'pdwchs_product_summary_' . md5( wp_json_encode( array( $product_ids, $start_date, $end_date ) ) );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$result = array();
		foreach ( $product_ids as $pid ) {
			$result[ $pid ] = array( 'revenue' => 0.0, 'units' => 0, 'orders' => 0, 'aov' => 0.0 );
		}

		$end_exclusive = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) );
		$start_param   = $start_date . ' 00:00:00';
		$end_param     = $end_exclusive . ' 00:00:00';

		if ( $this->pdwchs_use_lookup_table() ) {
			global $wpdb;
			$id_ph     = implode( ', ', array_fill( 0, count( $product_ids ), '%d' ) );
			$statuses  = self::REVENUE_STATUSES_SQL;
			$status_ph = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT l.product_id,
					        SUM( l.product_net_revenue ) AS revenue,
					        SUM( l.product_qty )         AS units,
					        COUNT( DISTINCT l.order_id ) AS orders
					 FROM   {$wpdb->prefix}wc_order_product_lookup l
					 INNER JOIN {$wpdb->prefix}wc_orders o ON l.order_id = o.id
					 WHERE  l.product_id IN ({$id_ph})
					   AND  o.status IN ({$status_ph})
					   AND  l.date_created >= %s
					   AND  l.date_created <  %s
					 GROUP BY l.product_id",
					array_merge( $product_ids, $statuses, array( $start_param, $end_param ) )
				),
				ARRAY_A
			);

			if ( $rows ) {
				foreach ( $rows as $row ) {
					$pid     = (int) $row['product_id'];
					$revenue = (float) $row['revenue'];
					$orders  = (int)   $row['orders'];
					$result[ $pid ] = array(
						'revenue' => $revenue,
						'units'   => (int) $row['units'],
						'orders'  => $orders,
						'aov'     => $orders > 0 ? round( $revenue / $orders, 2 ) : 0.0,
					);
				}
			}
		} else {
			$orders = wc_get_orders( array(
				'status'       => self::REVENUE_STATUSES_WC,
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'return'       => 'objects',
			) );

			$totals = array();
			foreach ( $orders as $order ) {
				$order_id = $order->get_id();
				foreach ( $order->get_items() as $item ) {
					$pid = $item->get_product_id();
					if ( ! in_array( $pid, $product_ids, true ) ) {
						continue;
					}
					if ( ! isset( $totals[ $pid ] ) ) {
						$totals[ $pid ] = array( 'revenue' => 0.0, 'units' => 0, 'order_ids' => array() );
					}
					$totals[ $pid ]['revenue']               += (float) $item->get_total();
					$totals[ $pid ]['units']                 += (int)   $item->get_quantity();
					$totals[ $pid ]['order_ids'][ $order_id ] = true;
				}
			}

			foreach ( $totals as $pid => $data ) {
				$revenue = $data['revenue'];
				$ord     = count( $data['order_ids'] );
				$result[ $pid ] = array(
					'revenue' => $revenue,
					'units'   => $data['units'],
					'orders'  => $ord,
					'aov'     => $ord > 0 ? round( $revenue / $ord, 2 ) : 0.0,
				);
			}
		}

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Check whether the WC analytics lookup table is present and populated.
	 * Result is cached in $this->lookup_table_available for the lifetime of the request.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	private function pdwchs_use_lookup_table() : bool {
		if ( null !== $this->lookup_table_available ) {
			return $this->lookup_table_available;
		}

		// Only use the lookup-table SQL path when HPOS is active.
		// With classic storage wp_wc_orders is empty; the INNER JOIN would return 0 rows.
		if ( ! $this->pdwchs_is_hpos_active() ) {
			$this->lookup_table_available = false;
			return false;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'wc_order_product_lookup';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table}'" ) !== $table ) {
			$this->lookup_table_available = false;
			return false;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$this->lookup_table_available = (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$table}` LIMIT 1" ) > 0;
		return $this->lookup_table_available;
	}

	/**
	 * Return true when WooCommerce HPOS (custom orders table) is active.
	 * Extracted as a protected method so unit tests can override it.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	protected function pdwchs_is_hpos_active() : bool {
		return class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();
	}

	/**
	 * Get the SQL SELECT expression for period labeling by granularity.
	 *
	 * @since 2.2.0
	 *
	 * @param string $granularity 'day', 'week', or 'month'.
	 * @param string $date_col    The date column reference.
	 * @return string SQL expression.
	 */
	private function pdwchs_get_granularity_select( $granularity, $date_col ) {
		switch ( $granularity ) {
			case 'week':
				return "DATE({$date_col} - INTERVAL WEEKDAY({$date_col}) DAY)";
			case 'month':
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
	 * @param string $granularity 'day', 'week', or 'month'.
	 * @param string $date_col    The date column reference.
	 * @return string SQL expression.
	 */
	private function pdwchs_get_granularity_group_by( $granularity, $date_col ) {
		switch ( $granularity ) {
			case 'week':
				return "YEARWEEK({$date_col}, 1)";
			case 'month':
				return "DATE_FORMAT({$date_col}, '%Y-%m')";
			default:
				return "DATE({$date_col})";
		}
	}

	/**
	 * Generate an ordered list of period labels for the date range.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date  Y-m-d.
	 * @param string $end_date    Y-m-d.
	 * @param string $granularity 'day', 'week', or 'month'.
	 * @return array Array of period label strings.
	 */
	private function pdwchs_generate_period_list( $start_date, $end_date, $granularity ) {
		$periods = array();
		$current = new DateTime( $start_date );
		$end     = new DateTime( $end_date );

		switch ( $granularity ) {
			case 'week':
				$weekday = (int) $current->format( 'N' );
				if ( $weekday > 1 ) {
					$current->modify( '-' . ( $weekday - 1 ) . ' days' );
				}
				while ( $current <= $end ) {
					$periods[] = $current->format( 'Y-m-d' );
					$current->modify( '+7 days' );
				}
				break;

			case 'month':
				while ( $current <= $end ) {
					$periods[] = $current->format( 'Y-m' );
					$current->modify( 'first day of next month' );
				}
				break;

			default: // 'day'
				while ( $current <= $end ) {
					$periods[] = $current->format( 'Y-m-d' );
					$current->modify( '+1 day' );
				}
				break;
		}

		return $periods;
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
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
