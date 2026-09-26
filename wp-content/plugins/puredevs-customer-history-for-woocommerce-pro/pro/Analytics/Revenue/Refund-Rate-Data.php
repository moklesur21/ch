<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
// Reason: the single read is built with $wpdb->prepare() and a dynamically sized placeholder array.
// The only interpolated values are the table prefixes, the scope sub-select assembled in this file,
// and the status placeholder string; every status is bound, never inlined. This class receives no
// caller input of any kind — the public method takes no parameters — and the payload is cached in a
// transient.
// UnfinishedPrepare is added to the pair the sibling data classes use, for the reason
// Geographic-Data.php:13-17 records: every placeholder in the statement lives inside the interpolated
// scope sub-select, where the sniff cannot see it. The outer statement has nothing of its own to bind,
// and inventing a placeholder for it to satisfy a static check would be worse than naming the
// suppression.

/**
 * Per-customer refund rate — refunded amount as a share of what the customer was billed.
 *
 * Answers "which of my big customers are actually expensive" by separating a £2,000 customer from a
 * £2,000-minus-40%-refunds customer. Free's existing "Refunded Orders" column counts orders whose
 * *status* is refunded (class-pd-customer-history-customers.php:297-313), so a partly refunded order is
 * invisible to it; this class measures the money instead.
 *
 * A customer here is a **registered WordPress user**, keyed on customer_id. Guests are deliberately
 * excluded — unlike PDWCHS_LTV_Distribution, whose prefixed identity counts people, this class feeds a
 * column cell and a WP_User prune on the Customers & Users list, which is a WP_User_Query over roles and
 * cannot display a guest at all.
 *
 * The status scope is wc-completed, wc-processing **and wc-refunded** — one member wider than the pair
 * every other money aggregate in the plugin uses. See REFUND_STATUSES_SQL for why, and for the
 * consequence: this number will not reconcile with the LTV panel, the revenue widget, the geographic
 * report or the retention pack.
 *
 * One query per cold read, bounded by the customer count rather than the order count: a single grouped
 * aggregate over the orders table with the refund totals joined in. There is no batching, no cron and no
 * stored column, because there is no per-customer PHP step to batch — the ratio is a division.
 *
 * Read-only and presentation-free by construction. It never formats a value, never appends a percent
 * sign and never writes anything but its own transient, because R15-24B and R15-24C own every esc_*
 * call and every display string.
 *
 * The constructor registers nothing; the class is inert until a consumer instantiates it.
 *
 * @since 3.1.2
 */
class PDWCHS_Refund_Rate_Data {

	/**
	 * Order statuses for $wpdb queries (with wc- prefix).
	 *
	 * Three members, not the house pair. WooCommerce flips a **fully** refunded order to wc-refunded,
	 * which the pair excludes — so under the pair a customer whose only £2,000 order was fully refunded
	 * leaves both the numerator and the denominator and reads 0%, the precise opposite of the signal
	 * this class exists to give.
	 *
	 * The consequence, stated rather than hidden: this figure will **not** reconcile with
	 * LTV-Distribution, Revenue-Pro, Geographic-Data or Retention-Metrics, all of which use the pair.
	 * That is a deliberate divergence, not a defect. Order-Panel-Data.php:212-218 is the precedent for
	 * naming a mismatch of this kind in place.
	 */
	const REFUND_STATUSES_SQL = array( 'wc-completed', 'wc-processing', 'wc-refunded' );

	/** Transient prefix; only the schema token is appended — this class has no parameters to key on. */
	const CACHE_PREFIX = 'pdwchs_refund_';

	/** Payload TTL — the same 30 minutes every revenue and product aggregate uses. */
	const CACHE_TTL = 30 * MINUTE_IN_SECONDS;

	/** Memoised HPOS probe; null until first resolved. */
	private $hpos_available = null;

	/**
	 * Registers nothing and queries nothing.
	 *
	 * Load-bearing rather than an oversight: the class is inert until a consumer constructs it, which is
	 * what lets it be unit-tested with no WordPress loaded and no $wpdb present.
	 */
	public function __construct() {}

	/**
	 * Every registered customer's refund rate, keyed by user id.
	 *
	 * @since 3.1.2
	 *
	 * @return array Map of user_id => { gross float, refunded float, rate float|null, refund_orders int }.
	 *               Customers with no qualifying orders are absent, not present with zeroes.
	 */
	public function pdwchs_get_refund_rates() {
		// _v1 is a schema-version token: bumping it invalidates the cached payload if its shape ever
		// changes. No parameter suffix — this class takes no parameters, so there is only ever one
		// payload. Anyone adding a parameter later must add a suffix here with it.
		$cache_key = self::CACHE_PREFIX . 'v1';

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$payload = $this->pdwchs_build_payload( $this->pdwchs_query_refund_rows() );

		// An empty store caches array(), which is not false, so the next call is still a hit. Returning
		// early without caching would make the empty store the only case that queries on every load.
		set_transient( $cache_key, $payload, self::CACHE_TTL );

		return $payload;
	}

	/**
	 * The one read: gross, refunded and refunded-order count, grouped per customer.
	 *
	 * Bounded by the number of customers, not by the number of orders — the scope sub-select emits one
	 * row per qualifying order and the outer statement collapses it, so the result set is one row per
	 * person however large the store is.
	 *
	 * @return array Rows as ARRAY_A, or an empty array.
	 */
	private function pdwchs_query_refund_rows() {
		global $wpdb;

		$scope = $this->pdwchs_order_scope_sql();

		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT customer_id,
					SUM( order_total )    AS gross,
					SUM( order_refunded ) AS refunded,
					SUM( is_refunded )    AS refund_orders
				FROM ( {$scope} ) s
				GROUP BY customer_id",
				self::REFUND_STATUSES_SQL
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * One row per qualifying order: whose it is, what it was billed, and what came back.
	 *
	 * The refund totals arrive through a derived table already grouped by parent order, so the join adds
	 * at most one row per order and cannot multiply the gross. ABS() is applied there, per parent order,
	 * rather than around the outer SUM — wrapping the outer sum instead would let a stray positive
	 * refund row cancel a negative one before the sign is normalised.
	 *
	 * The refund join is LEFT and its sum is COALESCEd, because a customer with no refunds must still
	 * appear with a rate of 0.0: "this customer is clean" is a result, not an absence. The
	 * _customer_user join in the legacy branch is INNER for the opposite reason — see the comment there.
	 *
	 * Refunds are attributed to the parent order, so a refund raised today against a two-year-old order
	 * reduces that customer's rate today. There is no date bound anywhere in this class, and the free
	 * 90-day data window is not consulted: clamping a lifetime ratio to a window would be incoherent.
	 *
	 * is_refunded is a 0/1 flag per order, so the outer SUM counts orders that carry at least one refund
	 * row. That is deliberately **not** a count of orders whose status is wc-refunded, which is what
	 * free's existing "Refunded Orders" column shows; the two disagree on any store with partial
	 * refunds, and both are right about their own question.
	 *
	 * Interpolated into the outer statement, so its %s placeholders are bound once by the caller.
	 *
	 * @return string SQL sub-select with %s status placeholders.
	 */
	private function pdwchs_order_scope_sql() {
		global $wpdb;

		$status_ph = implode( ', ', array_fill( 0, count( self::REFUND_STATUSES_SQL ), '%s' ) );

		if ( $this->pdwchs_use_hpos_query() ) {
			return "SELECT o.customer_id                                  AS customer_id,
					o.total_amount                                        AS order_total,
					COALESCE( ABS( rf.refunded ), 0 )                     AS order_refunded,
					CASE WHEN rf.parent_order_id IS NULL THEN 0 ELSE 1 END AS is_refunded
				FROM {$wpdb->prefix}wc_orders o
				LEFT JOIN ( SELECT parent_order_id, SUM( total_amount ) AS refunded
						FROM {$wpdb->prefix}wc_orders
						WHERE type = 'shop_order_refund'
						GROUP BY parent_order_id ) rf ON rf.parent_order_id = o.id
				WHERE o.type = 'shop_order'
				  AND o.status IN ({$status_ph})
				  AND o.customer_id > 0";
		}

		// INNER JOIN, not the cohort engine's LEFT JOIN: this feature counts registered customers
		// only, so an order with no _customer_user row should be dropped. A LEFT JOIN here would
		// group every guest order in the store under one NULL key.
		return "SELECT pm_cust.meta_value                                AS customer_id,
				CAST( pm_total.meta_value AS DECIMAL(26,8) )              AS order_total,
				COALESCE( ABS( rf.refunded ), 0 )                         AS order_refunded,
				CASE WHEN rf.post_parent IS NULL THEN 0 ELSE 1 END        AS is_refunded
			FROM {$wpdb->posts} o
			INNER JOIN {$wpdb->postmeta} pm_total
				ON pm_total.post_id = o.ID AND pm_total.meta_key = '_order_total'
			INNER JOIN {$wpdb->postmeta} pm_cust
				ON pm_cust.post_id = o.ID AND pm_cust.meta_key = '_customer_user'
			LEFT JOIN ( SELECT r.post_parent, SUM( CAST( pm_ref.meta_value AS DECIMAL(26,8) ) ) AS refunded
					FROM {$wpdb->posts} r
					INNER JOIN {$wpdb->postmeta} pm_ref
						ON pm_ref.post_id = r.ID AND pm_ref.meta_key = '_order_total'
					WHERE r.post_type = 'shop_order_refund'
					GROUP BY r.post_parent ) rf ON rf.post_parent = o.ID
			WHERE o.post_type = 'shop_order'
			  AND o.post_status IN ({$status_ph})
			  AND pm_cust.meta_value > 0";
	}

	/**
	 * Turn the result rows into the keyed payload.
	 *
	 * A $wpdb result is strings, so everything is cast on the way out.
	 *
	 * The refund join is LEFT and the sum is COALESCEd, so a customer with no refunds arrives with
	 * refunded 0 and leaves with a rate of 0.0 — "this customer is clean" is a measured result, not an
	 * absence. A customer whose qualifying orders sum to nothing leaves with a rate of **null**: they
	 * have no rate at all, and collapsing the two would put every zero-total customer at the clean end
	 * of a sort and inside every "refund rate under 10%" filter.
	 *
	 * A customer with no qualifying orders is absent from the map entirely. The GROUP BY never produces
	 * a row for them, and inventing one would mean carrying a list of every registered user, which is a
	 * different query and a different concern.
	 *
	 * @param array $rows Rows from pdwchs_query_refund_rows().
	 * @return array
	 */
	private function pdwchs_build_payload( $rows ) {
		$payload = array();

		foreach ( $rows as $row ) {
			$id = isset( $row['customer_id'] ) ? (int) $row['customer_id'] : 0;

			if ( $id <= 0 ) {
				continue;
			}

			$gross = round( (float) $row['gross'], 2 );

			// abs() again, on top of the ABS() in the scope sub-select. The payload's contract is
			// that refunded is a magnitude, and defending it here means a caller can rely on that
			// without knowing which dialect produced the row or whether a stored refund total
			// arrived with an unexpected sign.
			$refunded = round( abs( (float) $row['refunded'] ), 2 );

			$payload[ $id ] = array(
				'gross'         => $gross,
				'refunded'      => $refunded,
				'rate'          => ( 0.0 === $gross ) ? null : round( ( $refunded / $gross ) * 100, 2 ),
				'refund_orders' => (int) $row['refund_orders'],
			);
		}

		return $payload;
	}

	/**
	 * Whether to read the HPOS tables, memoised for the request.
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

// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
