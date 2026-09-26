<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
// Reason: all three reads are built with $wpdb->prepare() and a dynamically sized placeholder array.
// The only interpolated values are table prefixes and the status placeholder string, both generated
// in this file; the bracket edges are bound, never inlined. This class receives no caller input of
// any kind. The payload is cached in a transient.
// UnfinishedPrepare is added to the pair the sibling data classes use, for the same reason
// Geographic-Data.php:6-17 documents: the summary and bucket statements carry every one of their
// placeholders inside the interpolated derived table (and, for the buckets, inside the interpolated
// CASE ladder), where the sniff cannot see them. The median read is not flagged, because its LIMIT
// offset is a placeholder at the outer level — which is exactly the difference the sniff is
// measuring, and inventing an outer placeholder for the other two to satisfy it would be worse than
// naming the suppression.

/**
 * Store-level lifetime-value distribution — the spend histogram and its average and median.
 *
 * Answers "is this store's revenue coming from a handful of whales or a broad base" by counting how
 * many customers sit in each lifetime-spend bracket. A customer is a *person*: registered users and
 * guests alike, keyed on the prefixed identity the cohort engine uses, so a guest who has spent
 * £2,000 lands in the right bracket rather than vanishing.
 *
 * Lifetime means lifetime. There is no date range, no preset and no window clamp — the class takes no
 * parameters at all, which is why there is exactly one cache entry.
 *
 * Three queries per cold read, all bounded: the summary returns one row, the bucket aggregate returns
 * at most one row per bracket regardless of store size, and the median read returns two rows. Three
 * rather than the usual two is structural, not slack — the bracket edges are resolved from the
 * summary's MAX and the median's OFFSET from its COUNT, so neither can be folded into the first
 * statement.
 *
 * Read-only and presentation-free by construction. It never formats a value, never resolves a bracket
 * label and never writes anything but its own transient, because R15-22B owns every esc_* call and
 * every display string.
 *
 * The constructor registers nothing; the class is inert until R15-22B instantiates it.
 *
 * @since 3.1.1
 */
class PDWCHS_LTV_Distribution {

	/**
	 * Bracket edges, in store currency, before the auto scale.
	 *
	 * Seven edges describe seven buckets: six closed intervals between consecutive edges, then an
	 * open-ended top. Bucket 0 catches everything below the second edge, including the negative
	 * lifetime value an over-refunded customer can carry.
	 */
	const DEFAULT_BRACKETS = array( 0, 50, 100, 250, 500, 1000, 2500 );

	/** Ceiling on the auto scale, so a corrupt MAX cannot spin the resolver. */
	const MAX_SCALE = 1000000000000;

	/** Transient prefix; only the schema token is appended — this class has no parameters to key on. */
	const CACHE_PREFIX = 'pdwchs_ltv_';

	/** Payload TTL — the same 30 minutes every revenue and product aggregate uses. */
	const CACHE_TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Order statuses for $wpdb queries (with wc- prefix).
	 *
	 * Declared locally: the pair is duplicated in seven files today and there is still no shared
	 * constant. Introducing one would touch all seven and is its own chunk.
	 */
	const REVENUE_STATUSES_SQL = array( 'wc-completed', 'wc-processing' );

	/** Memoised HPOS probe; null until first resolved. */
	private $hpos_available = null;

	/**
	 * No hooks, no queries, no properties primed from the database.
	 *
	 * R15-22B registers the hooks; this class only answers questions. An empty constructor is what
	 * lets the unit test instantiate it with no WordPress loaded and no $wpdb present.
	 */
	public function __construct() {
	}

	/**
	 * Assemble the lifetime-value distribution payload.
	 *
	 * Takes no parameters: this is an all-time figure, so there is nothing to scope it by and nothing
	 * to key the cache on. Always returns the full payload shape (see pdwchs_empty_payload()), never
	 * null and never false, so the template can read keys directly without an isset() on each one.
	 *
	 * @return array Payload; has_data is false when the store has no qualifying orders at all.
	 */
	public function pdwchs_get_ltv_distribution() {
		// _v1 is a schema-version token: bumping it invalidates the cached payload if its shape ever
		// changes. No range suffix — there is only ever one payload.
		$cache_key = self::CACHE_PREFIX . 'v1';

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		// Read 1 first, because everything below depends on it: the bracket edges are resolved from
		// its MAX and the median's OFFSET from its COUNT.
		$summary = $this->pdwchs_query_summary();

		$ladder  = $this->pdwchs_resolve_brackets( $summary['max_ltv'] );
		$buckets = $this->pdwchs_query_buckets( $ladder['edges'] );
		$median  = $this->pdwchs_query_median( $summary['total_customers'] );

		$payload = $this->pdwchs_build_payload( $summary, $buckets, $median, $ladder );

		set_transient( $cache_key, $payload, self::CACHE_TTL );

		return $payload;
	}

	/**
	 * The payload shape, with nothing in it.
	 *
	 * Every other path starts from this array and fills fields in, which is what keeps the key set
	 * and the key order identical on every path.
	 *
	 * @return array
	 */
	private function pdwchs_empty_payload() {
		return array(
			'brackets'        => array(),
			'total_customers' => 0,
			'total_ltv'       => 0.0,
			'average_ltv'     => null,
			'median_ltv'      => null,
			'min_ltv'         => null,
			'max_ltv'         => null,
			'scale'           => 1,
			'has_data'        => false,
			'generated_at'    => time(),
		);
	}

	/**
	 * Scale the bracket ladder to the store's currency and spend range.
	 *
	 * Returns the smallest power-of-ten multiple of DEFAULT_BRACKETS whose top edge is at least one
	 * tenth of the store's maximum lifetime value. That puts the biggest customer inside the open top
	 * bucket and within one decade of its floor, which is what keeps the rest of the distribution
	 * spread across the ladder.
	 *
	 * Powers of ten rather than equal-width buckets: a store selling in IDR or JPY has lifetime values
	 * four orders of magnitude larger than a USD store, and a fixed USD ladder would put every
	 * customer in the top bucket. Dividing the observed range into n equal parts instead produces
	 * edges like 1,347.20, which read as noise. Powers of ten keep the labels round in every currency.
	 *
	 * Costs no query: MAX came back with read 1.
	 *
	 * @param float|null $max Highest lifetime value in the store, or null when there are no customers.
	 * @return array { @type array $edges Scaled edges, @type int $scale The multiplier applied }
	 */
	private function pdwchs_resolve_brackets( $max ) {
		$edges = self::DEFAULT_BRACKETS;
		$top   = $edges[ count( $edges ) - 1 ];
		$scale = 1;

		if ( null !== $max && $max > 0 && $top > 0 ) {
			$target = (float) $max / 10;

			while ( ( $top * $scale ) < $target && $scale < self::MAX_SCALE ) {
				$scale *= 10;
			}
		}

		$scaled = array();
		foreach ( $edges as $edge ) {
			$scaled[] = $edge * $scale;
		}

		return array(
			'edges' => $scaled,
			'scale' => $scale,
		);
	}

	/**
	 * The per-customer lifetime-value derived table, in whichever dialect the store uses.
	 *
	 * One row per *person*: the identity expression is prefixed so a user id and an email can never
	 * collide, which is what makes GROUP BY identity mean "per customer" across registered users and
	 * guests alike (pro/Analytics/Cohorts/Cohort-Data.php:392-395).
	 *
	 * The legacy branch joins its identity meta with LEFT JOIN, deliberately and unlike the sibling
	 * Retention-Metrics.php:287, because a guest order may have no _customer_user row at all and an
	 * INNER JOIN would drop every guest silently. Only _order_total is INNER: every order has one.
	 *
	 * Refunds are attributed to the parent order through a 1:1 derived table, so a refund raised today
	 * against a two-year-old order reduces that customer's lifetime value — which is the correct
	 * answer. Refund totals are stored negative, so they are added; subtracting would double them.
	 *
	 * Orders with neither a customer id nor a billing email are excluded: an order belonging to nobody
	 * cannot sit in a customer's bracket, and bucketing it under the empty identity would invent one.
	 *
	 * Interpolated into all three reads, so its placeholders appear once per read and
	 * pdwchs_scope_args() must be passed once per interpolation.
	 *
	 * @return string SQL sub-select with %s status placeholders.
	 */
	private function pdwchs_identity_sql() {
		global $wpdb;

		$status_ph = implode( ', ', array_fill( 0, count( self::REVENUE_STATUSES_SQL ), '%s' ) );

		if ( $this->pdwchs_use_hpos_query() ) {
			return "SELECT
					IF( o.customer_id > 0,
						CONCAT( 'u:', o.customer_id ),
						CONCAT( 'e:', LOWER( o.billing_email ) ) )        AS identity,
					SUM( o.total_amount + COALESCE( rf.refunded, 0 ) )    AS ltv
				FROM {$wpdb->prefix}wc_orders o
				LEFT JOIN ( SELECT parent_order_id, SUM( total_amount ) AS refunded
						FROM {$wpdb->prefix}wc_orders
						WHERE type = 'shop_order_refund'
						GROUP BY parent_order_id ) rf ON rf.parent_order_id = o.id
				WHERE o.type = 'shop_order'
				  AND o.status IN ({$status_ph})
				  AND ( o.customer_id > 0 OR ( o.billing_email IS NOT NULL AND o.billing_email <> '' ) )
				GROUP BY identity";
		}

		return "SELECT
				IF( pm_cust.meta_value > 0,
					CONCAT( 'u:', pm_cust.meta_value ),
					CONCAT( 'e:', LOWER( pm_mail.meta_value ) ) )                              AS identity,
				SUM( CAST( pm_total.meta_value AS DECIMAL(26,8) ) + COALESCE( rf.refunded, 0 ) ) AS ltv
			FROM {$wpdb->posts} o
			INNER JOIN {$wpdb->postmeta} pm_total
				ON pm_total.post_id = o.ID AND pm_total.meta_key = '_order_total'
			LEFT JOIN {$wpdb->postmeta} pm_cust
				ON pm_cust.post_id = o.ID AND pm_cust.meta_key = '_customer_user'
			LEFT JOIN {$wpdb->postmeta} pm_mail
				ON pm_mail.post_id = o.ID AND pm_mail.meta_key = '_billing_email'
			LEFT JOIN ( SELECT r.post_parent, SUM( CAST( pm_ref.meta_value AS DECIMAL(26,8) ) ) AS refunded
					FROM {$wpdb->posts} r
					INNER JOIN {$wpdb->postmeta} pm_ref
						ON pm_ref.post_id = r.ID AND pm_ref.meta_key = '_order_total'
					WHERE r.post_type = 'shop_order_refund'
					GROUP BY r.post_parent ) rf ON rf.post_parent = o.ID
			WHERE o.post_type = 'shop_order'
			  AND o.post_status IN ({$status_ph})
			  AND ( pm_cust.meta_value > 0 OR ( pm_mail.meta_value IS NOT NULL AND pm_mail.meta_value <> '' ) )
			GROUP BY identity";
	}

	/**
	 * Bound arguments for one interpolation of the derived table.
	 *
	 * Only the statuses: this class has no range and no caller input, so there is nothing else to
	 * bind at that level.
	 *
	 * @return array
	 */
	private function pdwchs_scope_args() {
		return self::REVENUE_STATUSES_SQL;
	}

	/**
	 * Read 1 — the summary.
	 *
	 * One row: the customer count, the store total, the mean, and the extremes. MIN and MAX come back
	 * NULL on an empty store and stay null rather than becoming 0.0, because "no customers" and "a
	 * customer who spent nothing" are different answers.
	 *
	 * @return array { total_customers int, total_ltv float, average_ltv float|null,
	 *                 min_ltv float|null, max_ltv float|null }
	 */
	private function pdwchs_query_summary() {
		global $wpdb;

		$derived = $this->pdwchs_identity_sql();

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS total_customers,
					COALESCE( SUM( t.ltv ), 0 ) AS total_ltv,
					AVG( t.ltv ) AS average_ltv,
					MIN( t.ltv ) AS min_ltv,
					MAX( t.ltv ) AS max_ltv
				FROM ( {$derived} ) t",
				$this->pdwchs_scope_args()
			),
			ARRAY_A
		);

		if ( ! is_array( $row ) ) {
			return array(
				'total_customers' => 0,
				'total_ltv'       => 0.0,
				'average_ltv'     => null,
				'min_ltv'         => null,
				'max_ltv'         => null,
			);
		}

		return array(
			'total_customers' => (int) $row['total_customers'],
			'total_ltv'       => round( (float) $row['total_ltv'], 2 ),
			'average_ltv'     => null === $row['average_ltv'] ? null : round( (float) $row['average_ltv'], 2 ),
			'min_ltv'         => null === $row['min_ltv'] ? null : round( (float) $row['min_ltv'], 2 ),
			'max_ltv'         => null === $row['max_ltv'] ? null : round( (float) $row['max_ltv'], 2 ),
		);
	}

	/**
	 * Read 2 — the bucket counts.
	 *
	 * A cascading CASE over the resolved edges, grouped. The result set is bounded by the ladder, not
	 * by the store: at most one row per bracket. The edges are bound as %f, never inlined.
	 *
	 * The ladder's first WHEN has no lower guard, so a negative lifetime value — an over-refunded
	 * customer — lands in bucket 0 rather than falling out of the histogram entirely.
	 *
	 * @param array $edges Scaled edges from pdwchs_resolve_brackets().
	 * @return array Map of bucket index => customer count. Empty on failure.
	 */
	private function pdwchs_query_buckets( $edges ) {
		global $wpdb;

		$derived = $this->pdwchs_identity_sql();
		$last    = count( $edges ) - 1;

		// One branch per closed interval; the open top is the ELSE. The THEN values are loop
		// counters, not placeholders.
		$ladder = '';
		for ( $index = 1; $index <= $last; $index++ ) {
			$ladder .= ' WHEN t.ltv < %f THEN ' . (int) ( $index - 1 );
		}

		// The CASE sits in the SELECT list, so its arguments precede the derived table's.
		$args = array_merge(
			array_slice( $edges, 1 ),
			$this->pdwchs_scope_args()
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT CASE{$ladder} ELSE " . (int) $last . " END AS bucket,
					COUNT(*) AS customers
				FROM ( {$derived} ) t
				GROUP BY bucket",
				$args
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$counts = array();
		foreach ( $rows as $row ) {
			$counts[ (int) $row['bucket'] ] = (int) $row['customers'];
		}

		return $counts;
	}

	/**
	 * Read 3 — the median.
	 *
	 * Two rows, never n. MySQL 5.7 has no window functions at all, so the percentile and bucketing
	 * helpers MySQL 8 offers are unavailable to us; ORDER BY with LIMIT 2 OFFSET is the portable form
	 * and it stays bounded on a store of any size. On an odd count the first row is the median; on an
	 * even one the two middle values are averaged.
	 *
	 * Returns null, never 0.0, when there are no customers: zero would say "the median customer has
	 * spent nothing", null says "there is no median to take".
	 *
	 * @param int $count Customer count from read 1.
	 * @return float|null
	 */
	private function pdwchs_query_median( $count ) {
		global $wpdb;

		if ( $count < 1 ) {
			return null;
		}

		$derived = $this->pdwchs_identity_sql();
		$offset  = (int) floor( ( $count - 1 ) / 2 );

		$args = array_merge(
			$this->pdwchs_scope_args(),
			array( $offset )
		);

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT t.ltv AS ltv
				FROM ( {$derived} ) t
				ORDER BY t.ltv ASC
				LIMIT 2 OFFSET %d",
				$args
			),
			ARRAY_A
		);

		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return null;
		}

		$first = (float) $rows[0]['ltv'];

		// An even count straddles two rows; an odd count lands on the first.
		if ( 0 === $count % 2 && isset( $rows[1] ) ) {
			return round( ( $first + (float) $rows[1]['ltv'] ) / 2, 2 );
		}

		return round( $first, 2 );
	}

	/**
	 * Turn the three reads into the payload.
	 *
	 * The ladder is the source of truth for which brackets exist; SQL only supplies counts. Every
	 * bracket is emitted whether or not read 2 returned a row for it, because GROUP BY returns no row
	 * for an empty bucket and dropping those would make a bimodal store look continuous.
	 *
	 * @param array      $summary Read 1.
	 * @param array      $counts  Read 2, bucket index => count.
	 * @param float|null $median  Read 3.
	 * @param array      $ladder  Output of pdwchs_resolve_brackets().
	 * @return array
	 */
	private function pdwchs_build_payload( $summary, $counts, $median, $ladder ) {
		$payload = $this->pdwchs_empty_payload();

		$edges = $ladder['edges'];
		$last  = count( $edges ) - 1;
		$total = (int) $summary['total_customers'];

		$brackets = array();
		for ( $index = 0; $index <= $last; $index++ ) {
			$customers = isset( $counts[ $index ] ) ? (int) $counts[ $index ] : 0;

			$brackets[] = array(
				'index'      => $index,
				'min'        => (float) $edges[ $index ],
				// The top bracket is open-ended, and null is what says so. R15-22B renders it as "+".
				'max'        => ( $index === $last ) ? null : (float) $edges[ $index + 1 ],
				'customers'  => $customers,
				'percentage' => $total > 0 ? round( ( $customers / $total ) * 100, 2 ) : 0.0,
			);
		}

		$payload['brackets']        = $brackets;
		$payload['total_customers'] = $total;
		$payload['total_ltv']       = $summary['total_ltv'];
		$payload['average_ltv']     = $summary['average_ltv'];
		$payload['median_ltv']      = $median;
		$payload['min_ltv']         = $summary['min_ltv'];
		$payload['max_ltv']         = $summary['max_ltv'];
		$payload['scale']           = (int) $ladder['scale'];
		$payload['has_data']        = $total > 0;

		return $payload;
	}

	/**
	 * Whether to use the HPOS dialect. Memoised — it is asked three times per cold read.
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
// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
