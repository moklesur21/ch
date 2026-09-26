<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// Reason: both queries are built with $wpdb->prepare() and a dynamically sized placeholder array.
// The only interpolated values are table prefixes, the status placeholder string and the CASE
// ladder's integer period indexes — all generated in this file, never user input. Every public
// read is cached in a transient.

/**
 * Store-level retention metrics — the churn-rate series and the expected-CLV figure.
 *
 * Answers "how fast is this store leaking customers, and what is a customer worth because of it"
 * for one of eight rolling window presets. A customer is churned in a window when they placed an
 * order in the window immediately before it and no order in the window itself, so the denominator
 * is a bounded, recently-active set rather than the whole customer base.
 *
 * Two queries per cold read, both bounded: the series aggregate returns at most one row per window
 * regardless of store size, and the CLV aggregate returns one row. Results are cached per preset.
 *
 * Read-only and presentation-free by construction. It never calculates a per-customer score, never
 * writes anything but its own transient, and never formats a value — rates leave as floats and
 * timestamps as integers, because R15-15B owns every esc_* call and every display string.
 *
 * The constructor registers nothing; the class is inert until R15-15B instantiates it.
 *
 * @since 3.1.0
 */
class PDWCHS_Retention_Metrics {

	/**
	 * The eight window presets, slug => interval spec.
	 *
	 * 'label' is an untranslated machine-readable descriptor. R15-15B translates it.
	 */
	const PRESETS = array(
		'15d' => array(
			'n'     => 15,
			'unit'  => 'day',
			'label' => '15 days',
		),
		'30d' => array(
			'n'     => 30,
			'unit'  => 'day',
			'label' => '30 days',
		),
		'60d' => array(
			'n'     => 60,
			'unit'  => 'day',
			'label' => '60 days',
		),
		'90d' => array(
			'n'     => 90,
			'unit'  => 'day',
			'label' => '90 days',
		),
		'4m'  => array(
			'n'     => 4,
			'unit'  => 'month',
			'label' => '4 months',
		),
		'6m'  => array(
			'n'     => 6,
			'unit'  => 'month',
			'label' => '6 months',
		),
		'12m' => array(
			'n'     => 12,
			'unit'  => 'month',
			'label' => '12 months',
		),
		'24m' => array(
			'n'     => 24,
			'unit'  => 'month',
			'label' => '24 months',
		),
	);

	/** Preset used for an unknown, empty or malformed slug. */
	const DEFAULT_PRESET = '30d';

	/** Measured periods in the series. One more window than this is queried, for the first base. */
	const SERIES_PERIODS = 6;

	/** Transient prefix; the validated preset and the schema token are appended. */
	const CACHE_PREFIX = 'pdwchs_retention_';

	/** Payload TTL — the same 30 minutes every revenue and product aggregate uses. */
	const CACHE_TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Order statuses for $wpdb queries (with wc- prefix).
	 *
	 * Declared locally: the pair is duplicated in six files today and there is no shared constant.
	 * Introducing one would touch all six and is its own chunk.
	 */
	const REVENUE_STATUSES_SQL = array( 'wc-completed', 'wc-processing' );

	/** Memoised HPOS probe; null until first resolved. */
	private $hpos_available = null;

	/**
	 * No hooks, no queries, no properties primed from the database.
	 *
	 * R15-15B registers the hooks; this class only answers questions. An empty constructor is what
	 * lets the unit test instantiate it with no WordPress loaded and no $wpdb present.
	 */
	public function __construct() {
	}

	/**
	 * Assemble the retention payload for one window preset.
	 *
	 * Always returns the full payload shape (see pdwchs_empty_payload()), never null and never
	 * false, so the template can read keys directly without an isset() on each one.
	 *
	 * @param string $preset One of the PRESETS slugs. Anything else falls back to DEFAULT_PRESET.
	 * @return array Payload; has_data is false when the store has no qualifying orders at all.
	 */
	public function pdwchs_get_retention( $preset ) {
		// Validate before anything else: an unknown slug must never reach the cache key, the bound
		// resolution or a query. R15-15C rejects a bad preset at the HTTP boundary with a 400; this
		// is the second line and fails soft, because a display option defaulting to 30 days is the
		// right answer to a malformed request.
		if ( ! is_string( $preset ) || ! isset( self::PRESETS[ $preset ] ) ) {
			$preset = self::DEFAULT_PRESET;
		}

		// _v1 is a schema-version token: bumping it invalidates all eight cached entries at once if
		// the payload shape ever changes. No _w window suffix — this class only loads inside the Pro
		// gate, where the Free 90-day clamp is already lifted.
		$cache_key = self::CACHE_PREFIX . $preset . '_v1';

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$bounds = $this->pdwchs_resolve_bounds( $preset );
		$rows   = $this->pdwchs_query_series( $bounds );
		$clv    = $this->pdwchs_query_clv_terms( $bounds );

		$payload = $this->pdwchs_build_payload( $rows, $clv, $preset, $bounds );

		set_transient( $cache_key, $payload, self::CACHE_TTL );

		return $payload;
	}

	/**
	 * The payload shape, with nothing in it.
	 *
	 * Every other path starts from this array and fills fields in, which is what keeps the key set
	 * and the key order identical on every path.
	 *
	 * @param string $preset Validated preset slug.
	 * @return array
	 */
	private function pdwchs_empty_payload( $preset ) {
		return array(
			'preset'                 => $preset,
			'window_label'           => self::PRESETS[ $preset ]['label'],
			'series'                 => array(),
			'current_churn_rate'     => null,
			'current_state'          => 'no_base',
			'expected_clv'           => null,
			'clv_state'              => 'no_base',
			'avg_value_per_customer' => null,
			'active_customers'       => 0,
			'has_data'               => false,
			'generated_at'           => time(),
		);
	}

	/**
	 * Resolve the preset to its window boundaries.
	 *
	 * Returns SERIES_PERIODS + 1 pairs, oldest first: the extra pair at index 0 is the base window
	 * for the first measured period. The last pair ends at the anchor.
	 *
	 * Both interval kinds are resolved here rather than in SQL, which is why this feature needs no
	 * timezone conversion in MySQL and no timezone tables loaded — the queries only ever receive
	 * literal datetime bounds.
	 *
	 * @param string $preset Validated preset slug.
	 * @return array List of array( 'start' => 'Y-m-d H:i:s', 'end' => 'Y-m-d H:i:s' ) in UTC.
	 */
	private function pdwchs_resolve_bounds( $preset ) {
		$spec = self::PRESETS[ $preset ];

		/**
		 * Filter the number of measured periods in the retention series.
		 *
		 * Clamped: the bound count is periods + 1, and each bound pair becomes a CASE branch and a
		 * placeholder pair in both interpolations of the bucket sub-select.
		 *
		 * @since 3.1.0
		 * @param int $periods Default SERIES_PERIODS.
		 */
		$periods = absint( apply_filters( 'pdwchs_retention_series_periods', self::SERIES_PERIODS ) );
		$periods = max( 1, min( 12, $periods ) );

		// Resolved once. Calling time() per boundary would let the windows drift apart mid-loop.
		$anchor = time();
		$bounds = array();

		if ( 'day' === $spec['unit'] ) {
			$width = $spec['n'] * DAY_IN_SECONDS;

			for ( $i = 0; $i <= $periods; $i++ ) {
				$end   = $anchor - ( ( $periods - $i ) * $width );
				$start = $end - $width;

				$bounds[] = array(
					'start' => gmdate( 'Y-m-d H:i:s', $start ),
					'end'   => gmdate( 'Y-m-d H:i:s', $end ),
				);
			}

			return $bounds;
		}

		// Calendar months are counted in the store's timezone, then emitted as UTC. Offsets are
		// taken from the anchor each time rather than compounded, so a 31st does not walk backwards.
		$utc  = new DateTimeZone( 'UTC' );
		$base = ( new DateTimeImmutable( '@' . $anchor ) )->setTimezone( wp_timezone() );

		for ( $i = 0; $i <= $periods; $i++ ) {
			$steps = $periods - $i;
			$end   = $base->modify( '-' . ( $steps * $spec['n'] ) . ' months' );
			$start = $base->modify( '-' . ( ( $steps + 1 ) * $spec['n'] ) . ' months' );

			$bounds[] = array(
				'start' => $start->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
				'end'   => $end->setTimezone( $utc )->format( 'Y-m-d H:i:s' ),
			);
		}

		return $bounds;
	}

	/**
	 * Read 1 — the churn series.
	 *
	 * One aggregate. A derived table buckets each customer's activity into the window it fell in,
	 * and the same sub-select is joined to itself one window forward: a customer present in window
	 * i with no match in i + 1 churned in i + 1. Output is one row per window at most, so the
	 * result set is bounded by the preset and not by the size of the store.
	 *
	 * @param array $bounds Output of pdwchs_resolve_bounds().
	 * @return array Rows of period_index / base / churned, ARRAY_A. Empty on failure.
	 */
	private function pdwchs_query_series( $bounds ) {
		global $wpdb;

		$hpos     = $this->pdwchs_use_hpos_query();
		$date_col = $hpos ? 'date_created_gmt' : 'o.post_date_gmt';

		$status_ph = implode( ', ', array_fill( 0, count( self::REVENUE_STATUSES_SQL ), '%s' ) );

		// The CASE ladder is contiguous across the whole queried span, so every row the WHERE
		// admits matches exactly one branch. The THEN values are loop counters, not placeholders.
		$ladder = '';
		foreach ( $bounds as $index => $pair ) {
			$ladder .= " WHEN {$date_col} >= %s AND {$date_col} < %s THEN " . (int) $index;
		}

		if ( $hpos ) {
			$bucket = "SELECT customer_id,
						CASE{$ladder} END AS period_index
					FROM {$wpdb->prefix}wc_orders
					WHERE type = 'shop_order'
					  AND status IN ({$status_ph})
					  AND customer_id > 0
					  AND {$date_col} >= %s
					  AND {$date_col} < %s
					GROUP BY customer_id, period_index";
		} else {
			// INNER JOIN, not the cohort engine's LEFT JOIN: this feature counts registered
			// customers only, so an order with no _customer_user row should be dropped.
			$bucket = "SELECT pm_cust.meta_value AS customer_id,
						CASE{$ladder} END AS period_index
					FROM {$wpdb->posts} o
					INNER JOIN {$wpdb->postmeta} pm_cust
						ON pm_cust.post_id = o.ID AND pm_cust.meta_key = '_customer_user'
					WHERE o.post_type = 'shop_order'
					  AND o.post_status IN ({$status_ph})
					  AND pm_cust.meta_value > 0
					  AND {$date_col} >= %s
					  AND {$date_col} < %s
					GROUP BY pm_cust.meta_value, period_index";
		}

		$bucket_args = $this->pdwchs_bucket_args( $bounds );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT p.period_index AS period_index,
					COUNT( DISTINCT p.customer_id ) AS base,
					COUNT( DISTINCT CASE WHEN n.customer_id IS NULL THEN p.customer_id END ) AS churned
				FROM ( {$bucket} ) p
				LEFT JOIN ( {$bucket} ) n
					ON n.customer_id = p.customer_id
				   AND n.period_index = p.period_index + %d
				GROUP BY p.period_index
				ORDER BY p.period_index",
				// The sub-select is interpolated twice, so its arguments are passed twice. The
				// trailing 1 is the join's one-window offset, bound rather than inlined so the
				// outer statement carries a placeholder of its own.
				array_merge( $bucket_args, $bucket_args, array( 1 ) )
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Bound arguments for one interpolation of the bucket sub-select.
	 *
	 * Order matches the placeholders as they appear in the assembled statement: the CASE ladder's
	 * pairs, then the statuses, then the outer range.
	 *
	 * @param array $bounds Output of pdwchs_resolve_bounds().
	 * @return array
	 */
	private function pdwchs_bucket_args( $bounds ) {
		$args = array();

		foreach ( $bounds as $pair ) {
			$args[] = $pair['start'];
			$args[] = $pair['end'];
		}

		$args = array_merge( $args, self::REVENUE_STATUSES_SQL );

		$args[] = $bounds[0]['start'];
		$args[] = $bounds[ count( $bounds ) - 1 ]['end'];

		return $args;
	}

	/**
	 * Read 2 — the expected-CLV terms over the most recent window.
	 *
	 * Gross, the active-customer count and refunds in one statement: refunds are a scalar
	 * sub-select rather than a second query, which is what keeps the whole class to two reads.
	 * Refunds are attributed by the parent order's date, so a refund raised this window against an
	 * older order does not distort this window.
	 *
	 * @param array $bounds Output of pdwchs_resolve_bounds().
	 * @return array { @type float $gross, @type float $refunds, @type int $active_customers }
	 */
	private function pdwchs_query_clv_terms( $bounds ) {
		global $wpdb;

		$window = $bounds[ count( $bounds ) - 1 ];
		$start  = $window['start'];
		$end    = $window['end'];

		$status_ph = implode( ', ', array_fill( 0, count( self::REVENUE_STATUSES_SQL ), '%s' ) );

		// The sub-select sits in the SELECT list, so its arguments precede the outer WHERE's.
		$args = array_merge(
			self::REVENUE_STATUSES_SQL,
			array( $start, $end ),
			self::REVENUE_STATUSES_SQL,
			array( $start, $end )
		);

		if ( $this->pdwchs_use_hpos_query() ) {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
					COALESCE( SUM( o.total_amount ), 0 ) AS gross,
					COUNT( DISTINCT o.customer_id ) AS active_customers,
					COALESCE( (
						SELECT ABS( SUM( r.total_amount ) )
						FROM {$wpdb->prefix}wc_orders r
						INNER JOIN {$wpdb->prefix}wc_orders ro ON r.parent_order_id = ro.id
						WHERE r.type = 'shop_order_refund'
						  AND ro.type = 'shop_order'
						  AND ro.status IN ({$status_ph})
						  AND ro.customer_id > 0
						  AND ro.date_created_gmt >= %s
						  AND ro.date_created_gmt < %s
					), 0 ) AS refunds
				FROM {$wpdb->prefix}wc_orders o
				WHERE o.type = 'shop_order'
				  AND o.status IN ({$status_ph})
				  AND o.customer_id > 0
				  AND o.date_created_gmt >= %s
				  AND o.date_created_gmt < %s",
					$args
				),
				ARRAY_A
			);
		} else {
			$row = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT
					COALESCE( SUM( CAST( pm_total.meta_value AS DECIMAL(12,2) ) ), 0 ) AS gross,
					COUNT( DISTINCT pm_cust.meta_value ) AS active_customers,
					COALESCE( (
						SELECT ABS( SUM( CAST( pm_ref.meta_value AS DECIMAL(12,2) ) ) )
						FROM {$wpdb->posts} r
						INNER JOIN {$wpdb->posts} ro ON r.post_parent = ro.ID
						INNER JOIN {$wpdb->postmeta} pm_ref
							ON pm_ref.post_id = r.ID AND pm_ref.meta_key = '_order_total'
						INNER JOIN {$wpdb->postmeta} pm_rcust
							ON pm_rcust.post_id = ro.ID AND pm_rcust.meta_key = '_customer_user'
						WHERE r.post_type = 'shop_order_refund'
						  AND ro.post_type = 'shop_order'
						  AND ro.post_status IN ({$status_ph})
						  AND pm_rcust.meta_value > 0
						  AND ro.post_date_gmt >= %s
						  AND ro.post_date_gmt < %s
					), 0 ) AS refunds
				FROM {$wpdb->posts} o
				INNER JOIN {$wpdb->postmeta} pm_total
					ON pm_total.post_id = o.ID AND pm_total.meta_key = '_order_total'
				INNER JOIN {$wpdb->postmeta} pm_cust
					ON pm_cust.post_id = o.ID AND pm_cust.meta_key = '_customer_user'
				WHERE o.post_type = 'shop_order'
				  AND o.post_status IN ({$status_ph})
				  AND pm_cust.meta_value > 0
				  AND o.post_date_gmt >= %s
				  AND o.post_date_gmt < %s",
					$args
				),
				ARRAY_A
			);
		}

		return array(
			'gross'            => is_array( $row ) ? (float) $row['gross'] : 0.0,
			'refunds'          => is_array( $row ) ? (float) $row['refunds'] : 0.0,
			'active_customers' => is_array( $row ) ? (int) $row['active_customers'] : 0,
		);
	}

	/**
	 * Turn the two reads into the payload.
	 *
	 * @param array  $rows   Read 1 rows.
	 * @param array  $clv    Read 2 terms.
	 * @param string $preset Validated preset slug.
	 * @param array  $bounds Output of pdwchs_resolve_bounds().
	 * @return array
	 */
	private function pdwchs_build_payload( $rows, $clv, $preset, $bounds ) {
		$payload = $this->pdwchs_empty_payload( $preset );
		$periods = count( $bounds ) - 1;

		$by_index = array();
		foreach ( $rows as $row ) {
			$by_index[ (int) $row['period_index'] ] = array(
				'base'    => (int) $row['base'],
				'churned' => (int) $row['churned'],
			);
		}

		// Windows older than the first one that produced any order predate the store's history.
		// They are omitted rather than reported as a churn rate on a base that never existed; a
		// later window with no activity is a real no_base and is kept.
		$first_index = empty( $by_index ) ? null : min( array_keys( $by_index ) );

		$series = array();
		for ( $point = 1; $point <= $periods; $point++ ) {
			$base_index = $point - 1;

			if ( null === $first_index || $base_index < $first_index ) {
				continue;
			}

			$base    = isset( $by_index[ $base_index ] ) ? $by_index[ $base_index ]['base'] : 0;
			$churned = isset( $by_index[ $base_index ] ) ? $by_index[ $base_index ]['churned'] : 0;

			$series[] = array(
				'period_index' => $point,
				'label'        => substr( $bounds[ $point ]['start'], 0, 10 ) . '…' . substr( $bounds[ $point ]['end'], 0, 10 ),
				'base'         => $base,
				'churned'      => $churned,
				// 0 and null are different answers: 0 % is "nobody left", null is "no one to leave".
				'churn_rate'   => $base > 0 ? round( ( $churned / $base ) * 100, 2 ) : null,
				'state'        => $base > 0 ? 'ok' : 'no_base',
			);
		}

		$current       = empty( $series ) ? null : $series[ count( $series ) - 1 ];
		$current_ok    = ( null !== $current && 'ok' === $current['state'] );
		$active        = (int) $clv['active_customers'];
		$net           = (float) $clv['gross'] - (float) $clv['refunds'];
		$avg           = $active > 0 ? round( $net / $active, 2 ) : null;
		$current_rate  = $current_ok ? $current['churn_rate'] : null;

		if ( ! $current_ok || null === $avg ) {
			$clv_state    = 'no_base';
			$expected_clv = null;
		} elseif ( 0.0 === (float) $current_rate ) {
			// Everybody came back. Dividing by zero would yield INF, which reads as an infinitely
			// valuable customer base rather than as an unanswerable question.
			$clv_state    = 'no_churn';
			$expected_clv = null;
		} else {
			$clv_state    = 'ok';
			$expected_clv = round( $avg / ( $current_rate / 100 ), 2 );
		}

		$payload['series']                 = $series;
		$payload['current_churn_rate']     = $current_rate;
		$payload['current_state']          = $current_ok ? 'ok' : 'no_base';
		$payload['expected_clv']           = $expected_clv;
		$payload['clv_state']              = $clv_state;
		$payload['avg_value_per_customer'] = $avg;
		$payload['active_customers']       = $active;
		$payload['has_data']               = ( ! empty( $by_index ) || $active > 0 );

		return $payload;
	}

	/**
	 * Whether to use the HPOS dialect. Memoised — it is asked once per read.
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
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
