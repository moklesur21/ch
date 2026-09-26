<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// Reason: every query below is built with $wpdb->prepare() and a dynamically sized placeholder
// array. The only interpolated values are table prefixes and placeholder strings — never user
// input. Results are stored by the caller (R15-07B), not cached here.

/**
 * Cohort retention engine.
 *
 * Computes the monthly acquisition-cohort matrix (customers + net revenue) from the
 * orders table. Pure computation: reads no option, writes no option, caches nothing.
 * The daily cron (PDWCHS_Cohort_Cron, R15-07B) is the only caller.
 *
 * @since 3.1.0
 */
class PDWCHS_Cohort_Data {

	/** Order statuses that count as a purchase, wc- prefixed for SQL. */
	const REVENUE_STATUSES_SQL = array( 'wc-completed', 'wc-processing' );

	/** Highest month offset in the matrix: M+0 … M+12 = 13 columns. */
	const MAX_OFFSET = 12;

	/** Default number of acquisition months, before the pdwchs_cohort_max_months filter. */
	const DEFAULT_MAX_MONTHS = 24;

	/** Payload schema version, stored so a later change can detect old data. */
	const PAYLOAD_VERSION = 1;

	/** Identity rule this engine applies, recorded in the payload. */
	const IDENTITY_RULE = 'customer_id_else_billing_email';

	/** Memoised HPOS probe result. */
	private $hpos_available = null;

	/**
	 * Build the complete cohort matrix.
	 *
	 * Cohorts are ordered newest acquisition month first, which is the order the
	 * page and the CSV export present them in.
	 *
	 * @since 3.1.0
	 *
	 * @return array {
	 *     @type int    $version        PAYLOAD_VERSION.
	 *     @type string $tz_offset      Store offset used for month bucketing, e.g. '+06:00'.
	 *     @type int    $max_months     Number of acquisition months requested.
	 *     @type string $currency       Store currency code at build time.
	 *     @type string $identity_rule  'customer_id_else_billing_email'.
	 *     @type array  $cohorts        List of { month, size, cells[13] }.
	 *     @type array  $averages       List of 13 { offset, retention_pct, cohorts_counted }.
	 * }
	 */
	public function pdwchs_build_matrix() {
		$max_months    = $this->pdwchs_max_months();
		$tz_offset     = $this->pdwchs_get_tz_offset();
		$current_month = $this->pdwchs_current_month();
		$min_month     = $this->pdwchs_add_months( $current_month, -( $max_months - 1 ) );

		$cohorts = $this->pdwchs_zero_fill_cohorts( $min_month, $current_month, $current_month );

		foreach ( $this->pdwchs_fetch_cells( $tz_offset, $min_month ) as $row ) {
			$month  = (int) $row['cohort_month'];
			$offset = (int) $row['offset_months'];

			if ( ! isset( $cohorts[ $month ]['cells'][ $offset ] ) ) {
				continue;
			}

			$cohorts[ $month ]['cells'][ $offset ]['customers'] = (int) $row['customers'];
			$cohorts[ $month ]['cells'][ $offset ]['revenue']   = (float) $row['gross'];
		}

		foreach ( $this->pdwchs_fetch_refunds( $tz_offset, $min_month ) as $row ) {
			$month  = (int) $row['cohort_month'];
			$offset = (int) $row['offset_months'];

			if ( ! isset( $cohorts[ $month ]['cells'][ $offset ] ) ) {
				continue;
			}

			$cohorts[ $month ]['cells'][ $offset ]['revenue'] -= (float) $row['refunded'];
		}

		foreach ( $cohorts as $month => $cohort ) {
			$cohorts[ $month ]['size'] = (int) $cohort['cells'][0]['customers'];
		}

		// A month that acquired nobody is not a cohort: it has no denominator, so it
		// carries no retention rate. Dropping those rows is what makes a store with no
		// qualifying orders return an empty cohort list rather than a grid of zeroes.
		$cohorts = array_filter(
			$cohorts,
			static function ( $cohort ) {
				return $cohort['size'] > 0;
			}
		);

		krsort( $cohorts );

		$cohorts = array_values( $cohorts );

		return array(
			'version'       => self::PAYLOAD_VERSION,
			'tz_offset'     => $tz_offset,
			'max_months'    => $max_months,
			'currency'      => get_woocommerce_currency(),
			'identity_rule' => self::IDENTITY_RULE,
			'cohorts'       => $cohorts,
			'averages'      => $this->pdwchs_build_averages( $cohorts ),
		);
	}

	/**
	 * Current month in store-local time as YYYYMM.
	 *
	 * @since 3.1.0
	 *
	 * @return int
	 */
	private function pdwchs_current_month() {
		return (int) ( new DateTime( 'now', wp_timezone() ) )->format( 'Ym' );
	}

	/**
	 * Add (or subtract) whole months to a YYYYMM integer.
	 *
	 * Period arithmetic, not date arithmetic: strtotime( '-1 month' ) overflows on
	 * the 31st, and every boundary in this engine is a month, never a day.
	 *
	 * @since 3.1.0
	 *
	 * @param int $ym     Month as YYYYMM.
	 * @param int $months Months to add; may be negative.
	 * @return int
	 */
	private function pdwchs_add_months( $ym, $months ) {
		$year  = intdiv( $ym, 100 );
		$month = $ym % 100;
		$total = ( $year * 12 ) + ( $month - 1 ) + $months;

		return ( intdiv( $total, 12 ) * 100 ) + ( $total % 12 ) + 1;
	}

	/**
	 * '2026-03' label for display and CSV.
	 *
	 * @since 3.1.0
	 *
	 * @param int $ym Month as YYYYMM.
	 * @return string
	 */
	private function pdwchs_month_label( $ym ) {
		return sprintf( '%04d-%02d', intdiv( $ym, 100 ), $ym % 100 );
	}

	/**
	 * Number of acquisition months to compute.
	 *
	 * A filter is public API, so anything below 1 falls back to the default rather
	 * than pushing the earliest cohort month into the future.
	 *
	 * @since 3.1.0
	 *
	 * @return int >= 1
	 */
	private function pdwchs_max_months() {
		$months = (int) apply_filters( 'pdwchs_cohort_max_months', self::DEFAULT_MAX_MONTHS );

		return ( $months >= 1 ) ? $months : self::DEFAULT_MAX_MONTHS;
	}

	/**
	 * A cell is meaningful only once the month it covers has ended.
	 *
	 * @since 3.1.0
	 *
	 * @param int $cohort_month  Acquisition month as YYYYMM.
	 * @param int $offset        Months since acquisition.
	 * @param int $current_month Current store-local month as YYYYMM.
	 * @return bool
	 */
	private function pdwchs_cell_is_complete( $cohort_month, $offset, $current_month ) {
		return $this->pdwchs_add_months( $cohort_month, $offset ) < $current_month;
	}

	/**
	 * Build the empty matrix: every month in range, every offset, all zeroes.
	 *
	 * Zero-filling first means every cohort has exactly MAX_OFFSET + 1 cells, so no
	 * consumer has to defend against a missing key.
	 *
	 * @since 3.1.0
	 *
	 * @param int $min_month     Earliest acquisition month as YYYYMM.
	 * @param int $max_month     Latest acquisition month as YYYYMM.
	 * @param int $current_month Current store-local month as YYYYMM.
	 * @return array Keyed by YYYYMM.
	 */
	private function pdwchs_zero_fill_cohorts( $min_month, $max_month, $current_month ) {
		$cohorts = array();

		for ( $month = $min_month; $month <= $max_month; $month = $this->pdwchs_add_months( $month, 1 ) ) {
			$cells = array();

			for ( $offset = 0; $offset <= self::MAX_OFFSET; $offset++ ) {
				$cells[ $offset ] = array(
					'customers' => 0,
					'revenue'   => 0.0,
					'complete'  => $this->pdwchs_cell_is_complete( $month, $offset, $current_month ),
				);
			}

			$cohorts[ $month ] = array(
				'month' => $this->pdwchs_month_label( $month ),
				'size'  => 0,
				'cells' => $cells,
			);
		}

		return $cohorts;
	}

	/**
	 * Average retention per offset, across cohorts that have lived through it.
	 *
	 * Incomplete cells and cohorts with no members are excluded: an average taken
	 * over months that have not ended yet reads as a collapse that never happened.
	 *
	 * @since 3.1.0
	 *
	 * @param array $cohorts Cohort rows.
	 * @return array MAX_OFFSET + 1 entries of { offset, retention_pct, cohorts_counted }.
	 */
	private function pdwchs_build_averages( $cohorts ) {
		$averages = array();

		for ( $offset = 0; $offset <= self::MAX_OFFSET; $offset++ ) {
			$sum   = 0.0;
			$count = 0;

			foreach ( $cohorts as $cohort ) {
				if ( $cohort['size'] <= 0 || empty( $cohort['cells'][ $offset ]['complete'] ) ) {
					continue;
				}

				$sum += ( $cohort['cells'][ $offset ]['customers'] / $cohort['size'] ) * 100;
				$count++;
			}

			$averages[] = array(
				'offset'          => $offset,
				'retention_pct'   => ( $count > 0 ) ? round( $sum / $count, 1 ) : 0.0,
				'cohorts_counted' => $count,
			);
		}

		return $averages;
	}

	/**
	 * Per-cohort, per-offset customer counts and gross revenue.
	 *
	 * @since 3.1.0
	 *
	 * @param string $tz_offset Store offset for CONVERT_TZ, e.g. '+06:00'.
	 * @param int    $min_month Earliest acquisition month as YYYYMM.
	 * @return array Rows of { cohort_month, offset_months, customers, gross }.
	 */
	private function pdwchs_fetch_cells( $tz_offset, $min_month ) {
		global $wpdb;

		$base      = $this->pdwchs_base_sql();
		$base_args = $this->pdwchs_base_args( $tz_offset );

		$args = array_merge(
			$base_args,
			$base_args,
			array( $min_month, self::MAX_OFFSET )
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					c.cohort_month                               AS cohort_month,
					PERIOD_DIFF( b.order_month, c.cohort_month ) AS offset_months,
					COUNT( DISTINCT b.identity )                 AS customers,
					COALESCE( SUM( b.order_total ), 0 )          AS gross
				FROM ( {$base} ) b
				INNER JOIN (
					SELECT identity, MIN( order_month ) AS cohort_month
					FROM ( {$base} ) c0
					GROUP BY identity
					HAVING cohort_month >= %d
				) c ON c.identity = b.identity
				GROUP BY c.cohort_month, offset_months
				HAVING offset_months BETWEEN 0 AND %d",
				$args
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Refunds, attributed to the parent order's cohort and offset month.
	 *
	 * A March order refunded in June belongs to that cohort's M+0 cell — otherwise a
	 * cohort's lifetime revenue would depend on when the shop processed paperwork.
	 *
	 * @since 3.1.0
	 *
	 * @param string $tz_offset Store offset for CONVERT_TZ, e.g. '+06:00'.
	 * @param int    $min_month Earliest acquisition month as YYYYMM.
	 * @return array Rows of { cohort_month, offset_months, refunded }.
	 */
	private function pdwchs_fetch_refunds( $tz_offset, $min_month ) {
		global $wpdb;

		$base      = $this->pdwchs_base_sql();
		$base_args = $this->pdwchs_base_args( $tz_offset );

		if ( $this->pdwchs_use_hpos_query() ) {
			$refund_amount = 'COALESCE( ABS( SUM( r.total_amount ) ), 0 )';
			$refund_join   = "INNER JOIN {$wpdb->prefix}wc_orders r
					ON r.parent_order_id = b.order_id AND r.type = 'shop_order_refund'";
		} else {
			$refund_amount = 'COALESCE( ABS( SUM( CAST( pm_ref.meta_value AS DECIMAL(12,2) ) ) ), 0 )';
			$refund_join   = "INNER JOIN {$wpdb->posts} r
					ON r.post_parent = b.order_id AND r.post_type = 'shop_order_refund'
				INNER JOIN {$wpdb->postmeta} pm_ref
					ON pm_ref.post_id = r.ID AND pm_ref.meta_key = '_order_total'";
		}

		$args = array_merge(
			$base_args,
			$base_args,
			array( $min_month, self::MAX_OFFSET )
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					c.cohort_month                               AS cohort_month,
					PERIOD_DIFF( b.order_month, c.cohort_month ) AS offset_months,
					{$refund_amount}                             AS refunded
				FROM ( {$base} ) b
				INNER JOIN (
					SELECT identity, MIN( order_month ) AS cohort_month
					FROM ( {$base} ) c0
					GROUP BY identity
					HAVING cohort_month >= %d
				) c ON c.identity = b.identity
				{$refund_join}
				GROUP BY c.cohort_month, offset_months
				HAVING offset_months BETWEEN 0 AND %d",
				$args
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * One qualifying order per row: identity, store-local month, total, order id.
	 *
	 * The identity is prefixed so a user id and an email can never collide, which is
	 * what makes COUNT( DISTINCT identity ) mean "distinct people" across both kinds.
	 * The legacy branch joins its meta with LEFT JOIN so guest orders — which may have
	 * no _customer_user row at all — are not silently dropped.
	 *
	 * Interpolated into both aggregates, so its placeholders appear twice in the final
	 * statement and pdwchs_base_args() must be passed twice.
	 *
	 * @since 3.1.0
	 *
	 * @return string
	 */
	private function pdwchs_base_sql() {
		global $wpdb;

		$status_ph = implode( ', ', array_fill( 0, count( self::REVENUE_STATUSES_SQL ), '%s' ) );

		if ( $this->pdwchs_use_hpos_query() ) {
			return "SELECT
					IF( o.customer_id > 0,
						CONCAT( 'u:', o.customer_id ),
						CONCAT( 'e:', LOWER( o.billing_email ) ) )                            AS identity,
					EXTRACT( YEAR_MONTH FROM CONVERT_TZ( o.date_created_gmt, '+00:00', %s ) ) AS order_month,
					o.total_amount                                                            AS order_total,
					o.id                                                                      AS order_id
				FROM {$wpdb->prefix}wc_orders o
				WHERE o.type = 'shop_order'
				  AND o.status IN ({$status_ph})
				  AND ( o.customer_id > 0 OR ( o.billing_email IS NOT NULL AND o.billing_email <> '' ) )";
		}

		return "SELECT
				IF( pm_cust.meta_value > 0,
					CONCAT( 'u:', pm_cust.meta_value ),
					CONCAT( 'e:', LOWER( pm_mail.meta_value ) ) )                        AS identity,
				EXTRACT( YEAR_MONTH FROM CONVERT_TZ( p.post_date_gmt, '+00:00', %s ) )   AS order_month,
				CAST( pm_total.meta_value AS DECIMAL(12,2) )                             AS order_total,
				p.ID                                                                     AS order_id
			FROM {$wpdb->posts} p
			LEFT JOIN {$wpdb->postmeta} pm_cust  ON pm_cust.post_id  = p.ID AND pm_cust.meta_key  = '_customer_user'
			LEFT JOIN {$wpdb->postmeta} pm_mail  ON pm_mail.post_id  = p.ID AND pm_mail.meta_key  = '_billing_email'
			LEFT JOIN {$wpdb->postmeta} pm_total ON pm_total.post_id = p.ID AND pm_total.meta_key = '_order_total'
			WHERE p.post_type = 'shop_order'
			  AND p.post_status IN ({$status_ph})
			  AND ( pm_cust.meta_value > 0 OR ( pm_mail.meta_value IS NOT NULL AND pm_mail.meta_value <> '' ) )";
	}

	/**
	 * Prepare arguments for one occurrence of the base subquery.
	 *
	 * @since 3.1.0
	 *
	 * @param string $tz_offset Store offset for CONVERT_TZ.
	 * @return array
	 */
	private function pdwchs_base_args( $tz_offset ) {
		return array_merge( array( $tz_offset ), self::REVENUE_STATUSES_SQL );
	}

	/**
	 * Check if HPOS (Custom Order Tables) is enabled.
	 *
	 * @since 3.1.0
	 *
	 * @return bool
	 */
	private function pdwchs_use_hpos_query() {
		if ( null !== $this->hpos_available ) {
			return $this->hpos_available;
		}

		$this->hpos_available = class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' )
			&& \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled();

		return $this->hpos_available;
	}

	/**
	 * Return the WP timezone as a MySQL CONVERT_TZ-compatible fixed-offset string.
	 *
	 * A named zone would return NULL wherever the server's timezone tables are not
	 * loaded; a fixed offset works everywhere.
	 *
	 * @since 3.1.0
	 *
	 * @return string e.g. '+05:30', '-07:00', '+00:00'
	 */
	private function pdwchs_get_tz_offset() {
		$tz      = wp_timezone();
		$now     = new DateTime( 'now', $tz );
		$seconds = $tz->getOffset( $now );
		$sign    = $seconds < 0 ? '-' : '+';
		$abs     = abs( $seconds );

		return sprintf( '%s%02d:%02d', $sign, intdiv( $abs, 3600 ), ( $abs % 3600 ) / 60 );
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
