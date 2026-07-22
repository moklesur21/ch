<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// Reason: All queries use $wpdb->prepare() with dynamically built placeholder arrays.
// All public methods cache results via transients. Interpolated variables are internal
// (table prefixes, status placeholders) — never user input.

/**
 * Product correlations data class.
 *
 * Computes frequently-bought-together correlations for a given anchor product.
 * For each anchor, finds all completed/processing orders within a configurable
 * time window, then counts how often each co-purchased product appears in those
 * same orders. Results are sorted by co_count descending with a confidence score
 * (co_count ÷ total anchor orders) per row.
 *
 * NOTE — Scaling boundary: stores with > 100 000 orders may experience slow
 * first-load on an uncached call due to the self-JOIN across
 * wc_order_product_lookup. Mitigation options (pre-warming via WP-CLI, or
 * a materialized correlation table with a nightly cron) are out of scope
 * for Feature 1.4. With an external object cache the 24-hour transient TTL
 * protects subsequent calls. The pdwchs_clear_cache_for_product() method
 * only flushes DB-backed transients; external caches (Redis, Memcached)
 * are NOT flushed.
 *
 * @since 2.2.0
 */
class PDWCHS_Product_Correlations_Data {

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
	 * Transient cache TTL in seconds (24 hours).
	 *
	 * @var int
	 */
	const CORRELATION_CACHE_TTL = 24 * HOUR_IN_SECONDS;

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
	 * Get the top co-purchased products for a given anchor product.
	 *
	 * @since 2.2.0
	 *
	 * @param int $product_id  WooCommerce product ID (anchor product).
	 * @param int $limit       Max number of correlations to return (1–50).
	 * @param int $window_days Look-back window in days (1–365).
	 * @return array Array of rows: product_id, product_name, co_count, confidence.
	 */
	public function pdwchs_get_top_correlations(
		int $product_id,
		int $limit       = 10,
		int $window_days = 90
	) : array {
		$product_id  = absint( $product_id );
		$limit       = max( 1, min( 50, absint( $limit ) ) );
		$window_days = max( 1, min( 365, absint( $window_days ) ) );

		$cache_key = 'pdwchs_product_correlations_' . $product_id . '_' . $window_days . '_' . $limit;
		$cached    = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$window_days} days" ) );
		$result = array();

		if ( $this->pdwchs_use_lookup_table() ) {
			global $wpdb;
			$statuses  = self::REVENUE_STATUSES_SQL;
			$status_ph = implode( ', ', array_fill( 0, count( $statuses ), '%s' ) );

			$co_rows = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT co.product_id,
					        COUNT( DISTINCT co.order_id ) AS co_count
					 FROM   {$wpdb->prefix}wc_order_product_lookup anchor
					 INNER JOIN {$wpdb->prefix}wc_order_product_lookup co ON anchor.order_id = co.order_id
					 INNER JOIN {$wpdb->prefix}wc_orders o ON anchor.order_id = o.id
					 WHERE  anchor.product_id = %d
					   AND  co.product_id != %d
					   AND  o.status IN ({$status_ph})
					   AND  anchor.date_created >= %s
					 GROUP BY co.product_id
					 ORDER BY co_count DESC
					 LIMIT %d",
					array_merge(
						array( $product_id, $product_id ),
						$statuses,
						array( $cutoff, $limit )
					)
				)
			);

			if ( ! empty( $co_rows ) ) {
				$anchor_row = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT COUNT( DISTINCT anchor.order_id ) AS anchor_count
						 FROM   {$wpdb->prefix}wc_order_product_lookup anchor
						 INNER JOIN {$wpdb->prefix}wc_orders o ON anchor.order_id = o.id
						 WHERE  anchor.product_id = %d
						   AND  o.status IN ({$status_ph})
						   AND  anchor.date_created >= %s",
						array_merge(
							array( $product_id ),
							$statuses,
							array( $cutoff )
						)
					)
				);

				$anchor_count = ( $anchor_row && isset( $anchor_row->anchor_count ) ) ? (int) $anchor_row->anchor_count : 0;

				foreach ( $co_rows as $row ) {
					$pid      = (int) $row->product_id;
					$co_count = (int) $row->co_count;
					$result[] = array(
						'product_id'   => $pid,
						'product_name' => get_the_title( $pid ),
						'co_count'     => $co_count,
						'confidence'   => $anchor_count > 0
						                      ? round( $co_count / $anchor_count, 4 )
						                      : 0.0,
					);
				}
			}
		} else {
			$orders = wc_get_orders( array(
				'status'       => self::REVENUE_STATUSES_WC,
				'date_created' => '>' . ( time() - $window_days * DAY_IN_SECONDS ),
				'limit'        => -1,
				'return'       => 'objects',
			) );

			$anchor_order_ids = array();
			$co_counts        = array();

			foreach ( $orders as $order ) {
				$item_pids  = array();
				$has_anchor = false;

				foreach ( $order->get_items() as $item ) {
					$pid = $item->get_product_id();
					if ( $pid === $product_id ) {
						$has_anchor = true;
					}
					$item_pids[] = $pid;
				}

				if ( ! $has_anchor ) {
					continue;
				}

				$anchor_order_ids[] = $order->get_id();
				foreach ( array_unique( $item_pids ) as $pid ) {
					if ( $pid !== $product_id ) {
						$co_counts[ $pid ] = ( isset( $co_counts[ $pid ] ) ? $co_counts[ $pid ] : 0 ) + 1;
					}
				}
			}

			arsort( $co_counts );
			$co_counts    = array_slice( $co_counts, 0, $limit, true );
			$anchor_count = count( $anchor_order_ids );

			foreach ( $co_counts as $pid => $co_count ) {
				$result[] = array(
					'product_id'   => $pid,
					'product_name' => get_the_title( $pid ),
					'co_count'     => $co_count,
					'confidence'   => $anchor_count > 0
					                      ? round( $co_count / $anchor_count, 4 )
					                      : 0.0,
				);
			}
		}

		set_transient( $cache_key, $result, self::CORRELATION_CACHE_TTL );

		return $result;
	}

	/**
	 * Clear all correlation transients for a given anchor product.
	 *
	 * Uses a LIKE query against wp_options because the standard delete_transient()
	 * API requires the exact key. External object caches (Redis, Memcached) are
	 * NOT flushed by this method.
	 *
	 * @since 2.2.0
	 *
	 * @param int $product_id WooCommerce product ID.
	 * @return void
	 */
	public function pdwchs_clear_cache_for_product( int $product_id ) : void {
		global $wpdb;
		$pattern = $wpdb->esc_like( '_transient_pdwchs_product_correlations_' . (int) $product_id . '_' ) . '%';
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
				$pattern
			)
		);
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
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
