<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Aggregate conversion funnel data class.
 *
 * Queries are delegated to PDWCHS_Sessions_Data so that no direct DB access
 * against the sessions table remains in Pro code.
 *
 * Stage definitions:
 *   visitors         — any session in the date range (no url filter)
 *   add_to_cart      — url LIKE 'ACTION::add_to_cart::%'
 *   checkout_started — url LIKE 'checkout/%' AND url NOT LIKE 'checkout/order-received/%'
 *   purchased        — url LIKE 'checkout/order-received/%' OR url LIKE 'ACTION::new_order::%'
 *
 * @since 2.2.0
 */
class PDWCHS_Funnel_Data {

	/**
	 * Transient cache TTL in seconds (30 minutes).
	 */
	const CACHE_TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Return the four stage WHERE clauses keyed by stage name.
	 *
	 * @return array
	 */
	private function pdwchs_get_stage_clauses() : array {
		return array(
			'visitors'         => '',
			'add_to_cart'      => "url LIKE 'ACTION::add_to_cart::%'",
			'checkout_started' => "(url = 'checkout' OR url LIKE 'checkout/%' OR url LIKE 'checkout?%') AND url NOT LIKE 'checkout/order-received/%'",
			'purchased'        => "(url LIKE 'checkout/order-received/%' OR url LIKE 'ACTION::new_order::%')",
		);
	}

	/**
	 * Count distinct sessions for a single funnel stage over a date range.
	 *
	 * Delegates to PDWCHS_Sessions_Data::count_stage().
	 *
	 * @param string $stage      Stage key from pdwchs_get_stage_clauses().
	 * @param string $start_date Y-m-d, already validated.
	 * @param string $end_date   Y-m-d, already validated.
	 * @return int
	 */
	private function pdwchs_count_stage( string $stage, string $start_date, string $end_date ) : int {
		return PDWCHS_Sessions_Data::count_stage( $stage, $start_date, $end_date );
	}

	/**
	 * Validate and normalize date parameters.
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
	 * Return the DATE_FORMAT SQL expression for the requested granularity.
	 *
	 * @param string $granularity 'day', 'week', or 'month'.
	 * @return string SQL expression.
	 */
	private function pdwchs_get_granularity_expr( string $granularity ) : string {
		switch ( $granularity ) {
			case 'week':
				return "DATE_FORMAT(reg_date, '%%x-W%%v')";
			case 'month':
				return "DATE_FORMAT(reg_date, '%%Y-%%m')";
			default: // 'day'
				return "DATE_FORMAT(reg_date, '%%Y-%%m-%%d')";
		}
	}

	/**
	 * Generate an ordered list of period keys for the date range.
	 *
	 * @param string $start_date  Y-m-d.
	 * @param string $end_date    Y-m-d.
	 * @param string $granularity 'day', 'week', or 'month'.
	 * @return array Array of period key strings in ascending order.
	 */
	private function pdwchs_generate_period_list( string $start_date, string $end_date, string $granularity ) : array {
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
					$periods[] = $current->format( 'o-\WW' );
					$current->modify( '+1 week' );
				}
				break;

			case 'month':
				$current->modify( 'first day of this month' );
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
	 * Return a zero-filled aggregate funnel result.
	 *
	 * @return array
	 */
	private function pdwchs_zeroed_aggregate() : array {
		return array(
			'visitors'         => 0,
			'add_to_cart'      => 0,
			'checkout_started' => 0,
			'purchased'        => 0,
			'drop_offs'        => array(
				'visitor_to_cart'      => 0.0,
				'cart_to_checkout'     => 0.0,
				'checkout_to_purchase' => 0.0,
			),
			'overall_conversion' => 0.0,
		);
	}

	/**
	 * Get the aggregate conversion funnel for a date range.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date Y-m-d, inclusive.
	 * @param string $end_date   Y-m-d, inclusive.
	 * @return array {
	 *     visitors, add_to_cart, checkout_started, purchased: int,
	 *     drop_offs: { visitor_to_cart, cart_to_checkout, checkout_to_purchase: float },
	 *     overall_conversion: float
	 * }
	 */
	public function pdwchs_get_aggregate_funnel( string $start_date, string $end_date ) : array {
		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			return $this->pdwchs_zeroed_aggregate();
		}
		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		$cache_key = 'pdwchs_funnel_' . md5( $start_date . '|' . $end_date );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$visitors         = $this->pdwchs_count_stage( 'visitors', $start_date, $end_date );
		$add_to_cart      = $this->pdwchs_count_stage( 'add_to_cart', $start_date, $end_date );
		$checkout_started = $this->pdwchs_count_stage( 'checkout_started', $start_date, $end_date );
		$purchased        = $this->pdwchs_count_stage( 'purchased', $start_date, $end_date );

		$result = array(
			'visitors'         => $visitors,
			'add_to_cart'      => $add_to_cart,
			'checkout_started' => $checkout_started,
			'purchased'        => $purchased,
			'drop_offs'        => array(
				'visitor_to_cart'      => $visitors > 0 ? round( ( $add_to_cart / $visitors ) * 100, 2 ) : 0.0,
				'cart_to_checkout'     => $add_to_cart > 0 ? round( ( $checkout_started / $add_to_cart ) * 100, 2 ) : 0.0,
				'checkout_to_purchase' => $checkout_started > 0 ? round( ( $purchased / $checkout_started ) * 100, 2 ) : 0.0,
			),
			'overall_conversion' => $visitors > 0 ? round( ( $purchased / $visitors ) * 100, 2 ) : 0.0,
		);

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}

	/**
	 * Get the conversion funnel broken down by time period.
	 *
	 * Delegates the raw DB query to PDWCHS_Sessions_Data::get_funnel_buckets().
	 * Period-list generation and zero-filling remain here.
	 *
	 * @since 2.2.0
	 *
	 * @param string $start_date  Y-m-d, inclusive.
	 * @param string $end_date    Y-m-d, inclusive.
	 * @param string $granularity 'day', 'week', or 'month'.
	 * @return array Ordered list of period buckets ascending by period.
	 */
	public function pdwchs_get_funnel_over_time(
		string $start_date,
		string $end_date,
		string $granularity = 'day'
	) : array {
		$dates = $this->pdwchs_validate_and_normalize_dates( $start_date, $end_date );
		if ( false === $dates ) {
			return array();
		}
		$start_date = $dates['start'];
		$end_date   = $dates['end'];

		if ( ! in_array( $granularity, array( 'day', 'week', 'month' ), true ) ) {
			$granularity = 'day';
		}

		$cache_key = 'pdwchs_funnel_ts_' . md5( $start_date . '|' . $end_date . '|' . $granularity );
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$period_list = $this->pdwchs_generate_period_list( $start_date, $end_date, $granularity );

		$result = array();
		foreach ( $period_list as $period ) {
			$result[ $period ] = array(
				'period'           => $period,
				'visitors'         => 0,
				'add_to_cart'      => 0,
				'checkout_started' => 0,
				'purchased'        => 0,
			);
		}

		$rows = PDWCHS_Sessions_Data::get_funnel_buckets( $start_date, $end_date, $granularity );

		if ( $rows ) {
			foreach ( $rows as $row ) {
				$period = $row['period'];
				if ( isset( $result[ $period ] ) ) {
					$result[ $period ]['visitors']         = (int) $row['visitors'];
					$result[ $period ]['add_to_cart']      = (int) $row['add_to_cart'];
					$result[ $period ]['checkout_started'] = (int) $row['checkout_started'];
					$result[ $period ]['purchased']        = (int) $row['purchased'];
				}
			}
		}

		$final = array_values( $result );

		set_transient( $cache_key, $final, self::CACHE_TTL );

		return $final;
	}
}
