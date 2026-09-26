<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RFM matrix — the recency × frequency population grid.
 *
 * Counts customers by their stored RFM recency and frequency scores, so the dashboard can render a
 * heat-map of where the customer base actually sits. It computes no score: PD_Customer_History_RFM's
 * nightly batch owns every number in {prefix}pdwchs_customer_intelligence, and this class reads them.
 *
 * One table, one aggregate, one transient. No order table, no HPOS dialect, no hook, no markup — the
 * dual-dialect discipline the Analytics data classes carry is irrelevant here, because nothing in this
 * class touches wc_orders or posts.
 *
 * A score of 0 on either axis means the customer has never completed an order (RFM.php:93-98 and
 * :144-146), and a NULL means the batch has not reached them. Neither is a band, so neither appears
 * in the grid; both are counted once in 'unscored'. The column comment on the table says "Score 0-5",
 * which is why the scorers rather than the DDL are the source of truth for that distinction.
 *
 * The grid is always dense — twenty-five cells, zero-filled — so the consumer template needs no
 * isset() guard and no conditional. A store where nobody scores a 5 still renders an empty 5 column,
 * because that emptiness is the shape of the distribution.
 *
 * Read-only and presentation-free by construction. It returns ints, arrays and one nullable string,
 * and never formats a value, because R15-25B owns every esc_* call and every display string.
 *
 * The constructor registers nothing; the class is inert until a consumer instantiates it.
 *
 * @since 3.1.2
 */
class PDWCHS_RFM_Matrix_Data {

	/**
	 * Lowest scored band.
	 *
	 * Scores below this are "not scored", not "low" — see the class docblock. Declared as a constant
	 * rather than inlined so the densify loop, the partition and the consumer's column headers all
	 * follow one place if the scorers are ever rebanded.
	 */
	const MIN_SCORE = 1;

	/** Highest scored band. */
	const MAX_SCORE = 5;

	/** Transient prefix; only the schema token is appended — this class has no parameters to key on. */
	const CACHE_PREFIX = 'pdwchs_rfm_matrix_';

	/** Payload TTL — the same 30 minutes every dashboard aggregate uses. */
	const CACHE_TTL = 30 * MINUTE_IN_SECONDS;

	/**
	 * Registers nothing and queries nothing.
	 *
	 * Load-bearing rather than an oversight: R15-25B constructs this class at boot, before any
	 * capability check has run, and it is what lets the class be unit-tested with no WordPress loaded
	 * and no $wpdb present.
	 */
	public function __construct() {}

	/**
	 * The population grid, cached.
	 *
	 * @since 3.1.2
	 *
	 * @return array {
	 *     @type array       $grid       [recency][frequency] => customer count. Always 25 cells,
	 *                                   recency and frequency both 1..5, zero-filled. Recency keys
	 *                                   descend (5..1) so the consumer iterates without sorting.
	 *     @type int         $max        Largest single cell, for the shading scale. 0 on an empty store.
	 *     @type int         $total      Customers counted in the grid.
	 *     @type int         $unscored   Customers with a 0 or NULL on either axis.
	 *     @type string|null $calculated MAX(rfm_calculated_at), or null if the batch has never run.
	 * }
	 */
	public function pdwchs_get_matrix() {
		// _v1 is a schema-version token: bumping it invalidates the cached payload if its shape ever
		// changes. No parameter suffix — this class takes no parameters, so there is only ever one
		// payload. Anyone adding a parameter later must add a suffix here with it.
		$cache_key = self::CACHE_PREFIX . 'v1';

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$payload = $this->pdwchs_build_payload( $this->pdwchs_query_rows() );

		// An empty store caches a zero-filled grid, which is not false, so the next call is still a
		// hit. Returning early without caching would make the empty store the only case that queries
		// on every dashboard load.
		set_transient( $cache_key, $payload, self::CACHE_TTL );

		return $payload;
	}

	/**
	 * The one read: a customer count per (recency, frequency) pair.
	 *
	 * Deliberately unfiltered. The NULL and 0 rows are wanted — they are what 'unscored' is built
	 * from — so the partition happens in pdwchs_build_payload() where it is one readable branch.
	 * A WHERE clause here would make the totals silently wrong with no way to tell.
	 *
	 * Bounded by the number of distinct score pairs, not by the number of customers: the result set
	 * is at most a few dozen rows however large the store is.
	 *
	 * @return array Rows as objects with r, f and c properties, or an empty array.
	 */
	private function pdwchs_query_rows() {
		global $wpdb;

		// $wpdb->prefix is interpolated directly rather than via a local, which is what keeps this
		// statement free of a PreparedSQL.InterpolatedNotPrepared suppression: the sniff whitelists
		// the property but cannot see through an assignment. Nothing else here is interpolated, and
		// the method takes no arguments, so there is nothing to bind.
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned aggregate table with no core API; the caller caches the payload in a transient.
		$rows = $wpdb->get_results(
			"SELECT
				rfm_recency   AS r,
				rfm_frequency AS f,
				COUNT(*)      AS c
			FROM {$wpdb->prefix}pdwchs_customer_intelligence
			GROUP BY rfm_recency, rfm_frequency"
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * When the RFM batch last wrote a score.
	 *
	 * A separate plain aggregate rather than a window function folded into the read above: the
	 * minimum MySQL this plugin supports predates OVER (), and two aggregates over one small indexed
	 * table are cheap.
	 *
	 * Returns null — never a fabricated timestamp — when the batch has never run, so the consumer can
	 * say so honestly. RFM.php:499 defaults the same value to current_time( 'mysql' ), which makes a
	 * store that has never calculated claim it calculated seconds ago; that is not repeated here.
	 *
	 * @return string|null MySQL datetime, or null.
	 */
	private function pdwchs_query_calculated_at() {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned aggregate table with no core API; the caller caches the payload in a transient.
		$calculated = $wpdb->get_var( "SELECT MAX(rfm_calculated_at) FROM {$wpdb->prefix}pdwchs_customer_intelligence" );

		return ( null === $calculated || '' === $calculated ) ? null : (string) $calculated;
	}

	/**
	 * Densify the rows into a 5 × 5 grid and partition off everything that is not a band.
	 *
	 * @param array $rows Rows from pdwchs_query_rows().
	 * @return array The payload described on pdwchs_get_matrix().
	 */
	private function pdwchs_build_payload( $rows ) {
		$grid = array();

		// Recency descends so array_keys( $grid ) is already 5,4,3,2,1 — R5 is the top row and F5 the
		// rightmost column, putting the best customers in the top-right corner the way every published
		// RFM matrix does. The consumer iterates this in its natural order and must not sort it.
		for ( $r = self::MAX_SCORE; $r >= self::MIN_SCORE; $r-- ) {
			for ( $f = self::MIN_SCORE; $f <= self::MAX_SCORE; $f++ ) {
				$grid[ $r ][ $f ] = 0;
			}
		}

		$total    = 0;
		$unscored = 0;
		$max      = 0;

		foreach ( (array) $rows as $row ) {
			$r = ( null === $row->r ) ? 0 : (int) $row->r;
			$f = ( null === $row->f ) ? 0 : (int) $row->f;
			$c = (int) $row->c;

			// One check covers four cases: a 0 on either axis, a NULL on either axis, a score above
			// MAX_SCORE and a score below MIN_SCORE. A corrupt TINYINT of 7 lands in 'unscored'
			// rather than creating a sixth column, which is the degradation a defensive read wants.
			if ( ! isset( $grid[ $r ][ $f ] ) ) {
				$unscored += $c;
				continue;
			}

			$grid[ $r ][ $f ] += $c;
			$total            += $c;

			// The shading divisor is the largest *cell*. 'unscored' is usually the largest number in
			// the payload on a store with many never-ordered users, and feeding it in here would
			// render every real cell at the palest shade.
			$max = max( $max, $grid[ $r ][ $f ] );
		}

		return array(
			'grid'       => $grid,
			'max'        => $max,
			'total'      => $total,
			'unscored'   => $unscored,
			'calculated' => $this->pdwchs_query_calculated_at(),
		);
	}
}
