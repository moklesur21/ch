<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
// Reason: both reads are built with $wpdb->prepare() and a dynamically sized placeholder array.
// The only interpolated values are table prefixes, the shared scope sub-select assembled in this
// file, and the status placeholder string — never caller input. Every public read is cached in a
// transient.
// UnfinishedPrepare is added to the pair the sibling data classes use because every placeholder in
// both statements lives inside the interpolated scope sub-select, where the sniff cannot see it.
// Retention-Metrics.php:307 avoids the warning only because its outer statement happens to carry a
// %d of its own; neither read here has a value to bind at that level, and inventing one to satisfy
// a static check would be worse than naming the suppression.

/**
 * Per-country order aggregates — net sales, orders, items and unique products.
 *
 * Answers "where is this store's money coming from" for one date range, grouped by the order's
 * own billing country. Two queries per cold range, both bounded: the result set is one row per
 * country the store actually sold to, never one per order or per customer.
 *
 * Billing country lives in a different place under each storage engine — {prefix}wc_order_addresses
 * under HPOS, the _billing_country postmeta under legacy — so the order scope is written twice and
 * selected by a memoised OrderUtil probe. Order items are NOT moved by HPOS, so the item join above
 * that scope is written once.
 *
 * Guests are included: this is a sales report, and a guest order carries a billing country and real
 * revenue. Orders with no billing country are kept in a single '' bucket rather than dropped, which
 * is what makes the rows sum to the store total and reconcile with the revenue widget.
 *
 * Read-only and presentation-free by construction. It never resolves a country name, never formats
 * a value and never escapes anything — R15-21A owns every esc_* call and every display string.
 *
 * The constructor registers nothing; the class is inert until R15-21A instantiates it.
 *
 * @since 3.1.1
 */
class PDWCHS_Geographic_Data {

	/** Transient prefix; an md5 of the range and the schema token are appended. */
	const CACHE_PREFIX = 'pdwchs_geo_';

	/** Payload TTL — the same 30 minutes every revenue and product aggregate uses. */
	const CACHE_TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Order statuses for $wpdb queries (with wc- prefix).
	 *
	 * Declared locally: the pair is duplicated in six files today and there is no shared constant.
	 * Introducing one would touch all six and is its own chunk.
	 */
	const REVENUE_STATUSES_SQL = array( 'wc-completed', 'wc-processing' );

	/** Window used when either supplied date is malformed. */
	const DEFAULT_DAYS = 30;

	/** Memoised HPOS probe; null until first resolved. */
	private $hpos_available = null;

	/**
	 * No hooks, no queries, no properties primed from the database.
	 *
	 * R15-21A registers the hooks; this class only answers questions. An empty constructor is what
	 * lets the unit test instantiate it with no WordPress loaded and no $wpdb present.
	 */
	public function __construct() {
	}

	/**
	 * Assemble the geographic payload for one date range.
	 *
	 * Always returns the full payload shape (see pdwchs_empty_payload()), never null and never
	 * false, so the caller can read keys directly without an isset() on each one.
	 *
	 * @param string $start_date Inclusive start, 'Y-m-d'. Malformed input falls back to DEFAULT_DAYS.
	 * @param string $end_date   Inclusive end, 'Y-m-d'.
	 * @return array Payload; has_data is false when the range holds no qualifying orders.
	 */
	public function pdwchs_get_geographic( $start_date, $end_date ) {
		// Normalise before anything else: a malformed date must never reach the cache key or a
		// query. R15-21A rejects at the HTTP boundary first; this is the second line, and it fails
		// soft because a report defaulting to 30 days is the right answer to a bad range.
		$bounds = $this->pdwchs_normalize_dates( $start_date, $end_date );

		// _v1 is a schema-version token: bumping it invalidates every cached range at once if the
		// payload shape ever changes. No window suffix — this class only loads inside the Pro gate,
		// where the Free 90-day clamp is already lifted.
		$cache_key = self::CACHE_PREFIX . md5( $bounds['start'] . '|' . $bounds['end'] ) . '_v1';

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$totals = $this->pdwchs_query_totals( $bounds );
		$items  = $this->pdwchs_query_items( $bounds );

		$payload = $this->pdwchs_build_payload( $totals, $items, $bounds );

		set_transient( $cache_key, $payload, self::CACHE_TTL );

		return $payload;
	}

	/**
	 * Validate and normalise the supplied range.
	 *
	 * Follows pro/Analytics/Products/Product-Revenue-Data.php:478-500 exactly, because this
	 * report's numbers have to agree with that one: an inverted range is swapped rather than
	 * rejected, and only a malformed date falls back.
	 *
	 * The is_string() guard matters — absint()/createFromFormat() on an array emits a notice on
	 * some PHP versions, and this method is the only thing standing between a caller and that.
	 *
	 * @param mixed $start_date Candidate start.
	 * @param mixed $end_date   Candidate end.
	 * @return array { @type string $start 'Y-m-d', @type string $end 'Y-m-d' }
	 */
	private function pdwchs_normalize_dates( $start_date, $end_date ) {
		$fallback = array(
			'start' => gmdate( 'Y-m-d', strtotime( '-' . ( self::DEFAULT_DAYS - 1 ) . ' days' ) ),
			'end'   => gmdate( 'Y-m-d' ),
		);

		if ( ! is_string( $start_date ) || ! is_string( $end_date ) ) {
			return $fallback;
		}

		$start_dt = DateTime::createFromFormat( 'Y-m-d', $start_date );
		$end_dt   = DateTime::createFromFormat( 'Y-m-d', $end_date );

		// The round-trip comparison is what rejects '2026-13-45' and '2026-1-1' — createFromFormat()
		// accepts both and silently rolls them over.
		if ( ! $start_dt || $start_dt->format( 'Y-m-d' ) !== $start_date ) {
			return $fallback;
		}

		if ( ! $end_dt || $end_dt->format( 'Y-m-d' ) !== $end_date ) {
			return $fallback;
		}

		// Swap if start > end. The user meant the range they dragged.
		if ( $start_dt > $end_dt ) {
			$swap       = $start_date;
			$start_date = $end_date;
			$end_date   = $swap;
		}

		return array(
			'start' => $start_date,
			'end'   => $end_date,
		);
	}

	/**
	 * The shared order scope both reads run over, as a sub-select.
	 *
	 * One row per order: the address join is 1:1 by wc_order_addresses' UNIQUE KEY
	 * (address_type, order_id), and the refunds derived table is 1:1 by its own GROUP BY. That is
	 * what makes COUNT(*) and SUM( order_total ) correct in the same statement in read 1 — changing
	 * either join breaks both at once, silently.
	 *
	 * LEFT on the country, INNER on the total, deliberately: many orders legitimately have no
	 * billing country (virtual products, admin-created orders, imports) and dropping them would
	 * stop the rows summing to the store total; every WooCommerce order has an _order_total.
	 *
	 * No customer_id predicate anywhere — guest orders belong in a sales report.
	 *
	 * @param bool $with_refunds Include the refunds derived table. Read 2 has no use for it.
	 * @return string SQL sub-select with %s placeholders, in pdwchs_order_scope_args() order.
	 */
	private function pdwchs_order_scope_sql( $with_refunds ) {
		global $wpdb;

		$status_ph = implode( ', ', array_fill( 0, count( self::REVENUE_STATUSES_SQL ), '%s' ) );

		if ( $this->pdwchs_use_hpos_query() ) {
			$refund_select = $with_refunds ? ', COALESCE( rf.refunded, 0 ) AS order_refunded' : '';
			$refund_join   = $with_refunds
				? "LEFT JOIN ( SELECT parent_order_id, SUM( total_amount ) AS refunded
						FROM {$wpdb->prefix}wc_orders
						WHERE type = 'shop_order_refund'
						GROUP BY parent_order_id ) rf ON rf.parent_order_id = o.id"
				: '';

			return "SELECT o.id AS order_id,
						COALESCE( a.country, '' ) AS country_code,
						o.total_amount AS order_total{$refund_select}
					FROM {$wpdb->prefix}wc_orders o
					LEFT JOIN {$wpdb->prefix}wc_order_addresses a
						ON a.order_id = o.id AND a.address_type = 'billing'
					{$refund_join}
					WHERE o.type = 'shop_order'
					  AND o.status IN ({$status_ph})
					  AND o.date_created_gmt >= %s
					  AND o.date_created_gmt <  %s";
		}

		$refund_select = $with_refunds ? ', COALESCE( rf.refunded, 0 ) AS order_refunded' : '';
		$refund_join   = $with_refunds
			? "LEFT JOIN ( SELECT r.post_parent, SUM( CAST( pm_ref.meta_value AS DECIMAL(26,8) ) ) AS refunded
					FROM {$wpdb->posts} r
					INNER JOIN {$wpdb->postmeta} pm_ref
						ON pm_ref.post_id = r.ID AND pm_ref.meta_key = '_order_total'
					WHERE r.post_type = 'shop_order_refund'
					GROUP BY r.post_parent ) rf ON rf.post_parent = o.ID"
			: '';

		return "SELECT o.ID AS order_id,
					COALESCE( pm_country.meta_value, '' ) AS country_code,
					CAST( pm_total.meta_value AS DECIMAL(26,8) ) AS order_total{$refund_select}
				FROM {$wpdb->posts} o
				INNER JOIN {$wpdb->postmeta} pm_total
					ON pm_total.post_id = o.ID AND pm_total.meta_key = '_order_total'
				LEFT JOIN {$wpdb->postmeta} pm_country
					ON pm_country.post_id = o.ID AND pm_country.meta_key = '_billing_country'
				{$refund_join}
				WHERE o.post_type = 'shop_order'
				  AND o.post_status IN ({$status_ph})
				  AND o.post_date_gmt >= %s
				  AND o.post_date_gmt <  %s";
	}

	/**
	 * Bound arguments for one interpolation of the scope sub-select.
	 *
	 * Order matches the placeholders as they appear in the assembled statement: the statuses, then
	 * the half-open range. The end bound is the day *after* the requested end, so the end day is
	 * included — the Product-Revenue-Data.php:93-95 shape.
	 *
	 * @param array $bounds Output of pdwchs_normalize_dates().
	 * @return array
	 */
	private function pdwchs_order_scope_args( $bounds ) {
		// Not Product-Revenue-Data.php:93's gmdate( 'Y-m-d', strtotime( $end . ' +1 day' ) ).
		// That form is correct inside WordPress — wp-settings.php:73 pins PHP's default timezone to
		// UTC on every request — but it is correct only because of that, and it silently returns the
		// *same* day back under any other default, losing the range's final day. Which is exactly
		// what happens in the unit suite, where no WordPress is booted. Resolving in UTC explicitly
		// depends on nothing. The sibling classes are fine as they stand; do not "fix" them.
		$end_exclusive = ( new DateTimeImmutable( $bounds['end'], new DateTimeZone( 'UTC' ) ) )
			->modify( '+1 day' )
			->format( 'Y-m-d' );

		return array_merge(
			self::REVENUE_STATUSES_SQL,
			array( $bounds['start'] . ' 00:00:00', $end_exclusive . ' 00:00:00' )
		);
	}

	/**
	 * Read 1 — orders, gross and refunds per country.
	 *
	 * Refunds are attributed to the parent order's date and country, because the derived table is
	 * keyed by the parent: a refund raised today against a March order reduces March.
	 *
	 * @param array $bounds Output of pdwchs_normalize_dates().
	 * @return array Rows of country_code / orders / gross / refunded, ARRAY_A. Empty on failure.
	 */
	private function pdwchs_query_totals( $bounds ) {
		global $wpdb;

		$scope = $this->pdwchs_order_scope_sql( true );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT country_code,
					COUNT(*) AS orders,
					COALESCE( SUM( order_total ), 0 ) AS gross,
					COALESCE( SUM( order_refunded ), 0 ) AS refunded
				FROM ( {$scope} ) s
				GROUP BY country_code",
				$this->pdwchs_order_scope_args( $bounds )
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Read 2 — items sold and distinct products per country.
	 *
	 * The item join is written once: HPOS does not move woocommerce_order_items or its meta table,
	 * and order_id is valid and indexed under both engines. Only the scope above it branches.
	 *
	 * INNER JOIN is correct here — an order with no line items contributes no items and no
	 * products, and still appears in read 1 with its revenue. pdwchs_build_payload() fills the gap
	 * with zeros.
	 *
	 * @param array $bounds Output of pdwchs_normalize_dates().
	 * @return array Rows of country_code / items / unique_products, ARRAY_A. Empty on failure.
	 */
	private function pdwchs_query_items( $bounds ) {
		global $wpdb;

		$scope = $this->pdwchs_order_scope_sql( false );

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT s.country_code AS country_code,
					COALESCE( SUM( CAST( im_qty.meta_value AS SIGNED ) ), 0 ) AS items,
					COUNT( DISTINCT im_pid.meta_value ) AS unique_products
				FROM ( {$scope} ) s
				INNER JOIN {$wpdb->prefix}woocommerce_order_items oi
					ON oi.order_id = s.order_id AND oi.order_item_type = 'line_item'
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta im_qty
					ON im_qty.order_item_id = oi.order_item_id AND im_qty.meta_key = '_qty'
				INNER JOIN {$wpdb->prefix}woocommerce_order_itemmeta im_pid
					ON im_pid.order_item_id = oi.order_item_id AND im_pid.meta_key = '_product_id'
				GROUP BY s.country_code",
				$this->pdwchs_order_scope_args( $bounds )
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * The payload shape, with nothing in it.
	 *
	 * Every other path starts from this array and fills fields in, which is what keeps the key set
	 * and the key order identical whether the store sells to forty countries or none.
	 *
	 * @param array $bounds Output of pdwchs_normalize_dates().
	 * @return array
	 */
	private function pdwchs_empty_payload( $bounds ) {
		return array(
			'rows'            => array(),
			'total_net_sales' => 0.0,
			'total_orders'    => 0,
			'total_items'     => 0,
			'total_countries' => 0,
			'has_data'        => false,
			'start_date'      => $bounds['start'],
			'end_date'        => $bounds['end'],
			'generated_at'    => time(),
		);
	}

	/**
	 * Turn the two reads into the payload.
	 *
	 * Read 1 is the spine. Read 2's scope is a subset of it by construction, so a country present
	 * only in read 2 cannot happen and is deliberately not coded for — it would mean the two scopes
	 * had diverged, and a defensive merge would hide that.
	 *
	 * There is no total_unique_products: summing the per-country values would double-count a
	 * product sold in two countries, and getting it right needs a third aggregate over the whole
	 * scope, outside this class's two-query budget and for a number nothing displays.
	 *
	 * @param array $totals Read 1 rows.
	 * @param array $items  Read 2 rows.
	 * @param array $bounds Output of pdwchs_normalize_dates().
	 * @return array
	 */
	private function pdwchs_build_payload( $totals, $items, $bounds ) {
		$payload = $this->pdwchs_empty_payload( $bounds );

		$by_country = array();
		foreach ( $items as $item_row ) {
			$by_country[ (string) $item_row['country_code'] ] = array(
				'items'           => (int) $item_row['items'],
				'unique_products' => (int) $item_row['unique_products'],
			);
		}

		$rows = array();
		foreach ( $totals as $row ) {
			$code = (string) $row['country_code'];

			$rows[] = array(
				'country_code' => $code,
				// Refund totals are stored negative, so this adds. Subtracting would double them.
				'net_sales'       => round( (float) $row['gross'] + (float) $row['refunded'], 2 ),
				'orders'          => (int) $row['orders'],
				'items'           => isset( $by_country[ $code ] ) ? $by_country[ $code ]['items'] : 0,
				'unique_products' => isset( $by_country[ $code ] ) ? $by_country[ $code ]['unique_products'] : 0,
			);
		}

		// Net sales descending is the payload's natural order, so a consumer that does no sorting
		// of its own still gets something sensible. R15-21A re-sorts on request.
		usort(
			$rows,
			static function ( $a, $b ) {
				if ( $a['net_sales'] === $b['net_sales'] ) {
					return 0;
				}
				return ( $a['net_sales'] < $b['net_sales'] ) ? 1 : -1;
			}
		);

		$total_net_sales = 0.0;
		$total_orders    = 0;
		$total_items     = 0;

		foreach ( $rows as $row ) {
			$total_net_sales += $row['net_sales'];
			$total_orders    += $row['orders'];
			$total_items     += $row['items'];
		}

		$payload['rows']            = $rows;
		$payload['total_net_sales'] = round( $total_net_sales, 2 );
		$payload['total_orders']    = $total_orders;
		$payload['total_items']     = $total_items;
		$payload['total_countries'] = count( $rows );
		// Not derived from the money: a fully refunded range nets to 0.0 and still has data.
		$payload['has_data']        = ! empty( $rows );

		return $payload;
	}

	/**
	 * Whether to use the HPOS dialect. Memoised — it is asked twice per cold read.
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
