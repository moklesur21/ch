<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// Reason: All queries use $wpdb->prepare(). Results cached via transients.
// Interpolated variables are table prefixes and tz_offset (internal, never user input).

/**
 * Revenue patterns data class.
 *
 * Aggregates WooCommerce order revenue by day-of-week and hour-of-day across a date
 * range, using store-local time (wp_timezone()). Results are cached as transients.
 *
 * TIMEZONE NOTE: The MySQL CONVERT_TZ offset is derived from wp_timezone() at request
 * time using the current moment as the reference. This means orders placed during a DST
 * transition may be bucketed to the wrong local hour near the transition boundary. For
 * analytics data this is an acceptable approximation; a DST-correct path would require
 * per-row PHP timezone conversion, defeating the purpose of SQL aggregation.
 *
 * SCALING NOTE: The wc_get_orders() fallback path loads all orders in the date range
 * into memory. On very large stores (> 50 000 orders) the first uncached call may be
 * slow. Results are cached for 30 minutes via transients.
 *
 * @since 2.2.0
 */
class PDWCHS_Revenue_Patterns_Data {

	const REVENUE_STATUSES_SQL = array( 'wc-completed', 'wc-processing' );
	const REVENUE_STATUSES_WC  = array( 'completed', 'processing' );
	const CACHE_TTL            = 30 * MINUTE_IN_SECONDS;

	/** Cached result of pdwchs_use_hpos_query(), computed once per request. @var bool|null */
	private $hpos_available = null;

	public function __construct() {}

	/**
	 * Get order revenue aggregated by day-of-week.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d, inclusive.
	 * @param string $end_date   Y-m-d, inclusive.
	 * @return array Always 7 elements keyed 0–6 (0=Sun…6=Sat), each with revenue (float) and orders (int).
	 */
	public function pdwchs_get_revenue_by_dayofweek( string $start_date, string $end_date ) : array {
		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			return $this->pdwchs_zero_fill_dow();
		}
		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$cache_key = 'pdwchs_revenue_patterns_dow_v2_' . $start_date . '_' . $end_date;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$result      = $this->pdwchs_zero_fill_dow();
		$start_param = $start_date . ' 00:00:00';
		$end_param   = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) ) . ' 00:00:00';

		if ( $this->pdwchs_use_hpos_query() ) {
			global $wpdb;
			$tz_offset = $this->pdwchs_get_tz_offset();

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
					    DAYOFWEEK( CONVERT_TZ( date_paid_gmt, '+00:00', %s ) ) - 1 AS dow,
					    SUM( total_amount )  AS revenue,
					    COUNT( * )           AS orders
					FROM   {$wpdb->prefix}wc_orders
					WHERE  status IN ( %s, %s )
					  AND  type = 'shop_order'
					  AND  date_paid_gmt >= %s
					  AND  date_paid_gmt <  %s
					  AND  date_paid_gmt IS NOT NULL
					GROUP BY dow
					ORDER BY dow ASC",
					array( $tz_offset, 'wc-completed', 'wc-processing', $start_param, $end_param )
				),
				ARRAY_A
			);

			if ( $rows ) {
				foreach ( $rows as $row ) {
					$dow             = (int) $row['dow'];
					$result[ $dow ]  = array(
						'revenue' => (float) $row['revenue'],
						'orders'  => (int)   $row['orders'],
					);
				}
			}
		} else {
			$tz     = wp_timezone();
			$orders = wc_get_orders( array(
				'status'       => self::REVENUE_STATUSES_WC,
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'return'       => 'objects',
			) );

			foreach ( $orders as $order ) {
				$paid = $order->get_date_paid() ?? $order->get_date_created();
				if ( null === $paid ) {
					continue;
				}
				$local   = ( clone $paid )->setTimezone( $tz );
				$dow     = (int) $local->format( 'w' );
				$revenue = (float) $order->get_total();
				$result[ $dow ]['revenue'] += $revenue;
				$result[ $dow ]['orders']  += 1;
			}
		}

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Get order revenue aggregated by hour-of-day (store timezone).
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d, inclusive.
	 * @param string $end_date   Y-m-d, inclusive.
	 * @return array Always 24 elements keyed 0–23, each with revenue (float) and orders (int).
	 */
	public function pdwchs_get_revenue_by_hour( string $start_date, string $end_date ) : array {
		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			return $this->pdwchs_zero_fill_hours();
		}
		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$cache_key = 'pdwchs_revenue_patterns_hour_v2_' . $start_date . '_' . $end_date;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$result      = $this->pdwchs_zero_fill_hours();
		$start_param = $start_date . ' 00:00:00';
		$end_param   = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) ) . ' 00:00:00';

		if ( $this->pdwchs_use_hpos_query() ) {
			global $wpdb;
			$tz_offset = $this->pdwchs_get_tz_offset();

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
					    HOUR( CONVERT_TZ( date_paid_gmt, '+00:00', %s ) ) AS hour_of_day,
					    SUM( total_amount )  AS revenue,
					    COUNT( * )           AS orders
					FROM   {$wpdb->prefix}wc_orders
					WHERE  status IN ( %s, %s )
					  AND  type = 'shop_order'
					  AND  date_paid_gmt >= %s
					  AND  date_paid_gmt <  %s
					  AND  date_paid_gmt IS NOT NULL
					GROUP BY hour_of_day
					ORDER BY hour_of_day ASC",
					array( $tz_offset, 'wc-completed', 'wc-processing', $start_param, $end_param )
				),
				ARRAY_A
			);

			if ( $rows ) {
				foreach ( $rows as $row ) {
					$hour             = (int) $row['hour_of_day'];
					$result[ $hour ]  = array(
						'revenue' => (float) $row['revenue'],
						'orders'  => (int)   $row['orders'],
					);
				}
			}
		} else {
			$tz     = wp_timezone();
			$orders = wc_get_orders( array(
				'status'       => self::REVENUE_STATUSES_WC,
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'return'       => 'objects',
			) );

			foreach ( $orders as $order ) {
				$paid = $order->get_date_paid() ?? $order->get_date_created();
				if ( null === $paid ) {
					continue;
				}
				$local   = ( clone $paid )->setTimezone( $tz );
				$hour    = (int) $local->format( 'G' );
				$revenue = (float) $order->get_total();
				$result[ $hour ]['revenue'] += $revenue;
				$result[ $hour ]['orders']  += 1;
			}
		}

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Get order revenue aggregated by day-of-week × hour-of-day (7×24 heatmap).
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d, inclusive.
	 * @param string $end_date   Y-m-d, inclusive.
	 * @return array 7 outer keys (0=Sun…6=Sat) × 24 inner keys (0–23), each with revenue (float) and orders (int).
	 */
	public function pdwchs_get_heatmap( string $start_date, string $end_date ) : array {
		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			return $this->pdwchs_zero_fill_heatmap();
		}
		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$cache_key = 'pdwchs_revenue_patterns_heatmap_v2_' . $start_date . '_' . $end_date;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$result      = $this->pdwchs_zero_fill_heatmap();
		$start_param = $start_date . ' 00:00:00';
		$end_param   = gmdate( 'Y-m-d', strtotime( $end_date . ' +1 day' ) ) . ' 00:00:00';

		if ( $this->pdwchs_use_hpos_query() ) {
			global $wpdb;
			$tz_offset = $this->pdwchs_get_tz_offset();

			$rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT
					    DAYOFWEEK( CONVERT_TZ( date_paid_gmt, '+00:00', %s ) ) - 1 AS dow,
					    HOUR( CONVERT_TZ( date_paid_gmt, '+00:00', %s ) )          AS hour_of_day,
					    SUM( total_amount )  AS revenue,
					    COUNT( * )           AS orders
					FROM   {$wpdb->prefix}wc_orders
					WHERE  status IN ( %s, %s )
					  AND  type = 'shop_order'
					  AND  date_paid_gmt >= %s
					  AND  date_paid_gmt <  %s
					  AND  date_paid_gmt IS NOT NULL
					GROUP BY dow, hour_of_day",
					array( $tz_offset, $tz_offset, 'wc-completed', 'wc-processing', $start_param, $end_param )
				),
				ARRAY_A
			);

			if ( $rows ) {
				foreach ( $rows as $row ) {
					$dow              = (int) $row['dow'];
					$hour             = (int) $row['hour_of_day'];
					$result[ $dow ][ $hour ] = array(
						'revenue' => (float) $row['revenue'],
						'orders'  => (int)   $row['orders'],
					);
				}
			}
		} else {
			$tz     = wp_timezone();
			$orders = wc_get_orders( array(
				'status'       => self::REVENUE_STATUSES_WC,
				'date_created' => $start_date . '...' . $end_date,
				'limit'        => -1,
				'return'       => 'objects',
			) );

			foreach ( $orders as $order ) {
				$paid = $order->get_date_paid() ?? $order->get_date_created();
				if ( null === $paid ) {
					continue;
				}
				$local   = ( clone $paid )->setTimezone( $tz );
				$dow     = (int) $local->format( 'w' );
				$hour    = (int) $local->format( 'G' );
				$revenue = (float) $order->get_total();
				$result[ $dow ][ $hour ]['revenue'] += $revenue;
				$result[ $dow ][ $hour ]['orders']  += 1;
			}
		}

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Probe whether HPOS (wc_orders table) is active.
	 *
	 * @since 2.2.0
	 *
	 * @return bool
	 */
	private function pdwchs_use_hpos_query() : bool {
		if ( null !== $this->hpos_available ) {
			return $this->hpos_available;
		}
		if ( ! class_exists( '\Automattic\WooCommerce\Utilities\OrderUtil' ) ||
			! \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			$this->hpos_available = false;
			return false;
		}
		// date_paid_gmt column was added to wp_wc_orders in WC 8.6+. Fall back to PHP path on older installs.
		global $wpdb;
		$cols                 = $wpdb->get_col( "SHOW COLUMNS FROM `{$wpdb->prefix}wc_orders`" );
		$this->hpos_available = in_array( 'date_paid_gmt', $cols, true );
		return $this->hpos_available;
	}

	/**
	 * Return the WP timezone as a MySQL CONVERT_TZ-compatible fixed-offset string.
	 *
	 * @since 2.2.0
	 *
	 * @return string e.g. '+05:30', '-07:00', '+00:00'
	 */
	private function pdwchs_get_tz_offset() : string {
		$tz      = wp_timezone();
		$now     = new DateTime( 'now', $tz );
		$seconds = $tz->getOffset( $now );
		$sign    = $seconds < 0 ? '-' : '+';
		$abs     = abs( $seconds );
		return sprintf( '%s%02d:%02d', $sign, intdiv( $abs, 3600 ), ( $abs % 3600 ) / 60 );
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
	 * Return a zero-filled 7-element DOW array (0=Sun…6=Sat).
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	private function pdwchs_zero_fill_dow() : array {
		return array_fill( 0, 7, array( 'revenue' => 0.0, 'orders' => 0 ) );
	}

	/**
	 * Return a zero-filled 24-element hour array (0–23).
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	private function pdwchs_zero_fill_hours() : array {
		return array_fill( 0, 24, array( 'revenue' => 0.0, 'orders' => 0 ) );
	}

	/**
	 * Return a zero-filled 7×24 heatmap array.
	 *
	 * @since 2.2.0
	 *
	 * @return array
	 */
	private function pdwchs_zero_fill_heatmap() : array {
		$row = array_fill( 0, 24, array( 'revenue' => 0.0, 'orders' => 0 ) );
		return array_fill( 0, 7, $row );
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
