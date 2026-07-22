<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// Reason: Query uses $wpdb->prepare() with absint(). Table name is an internal constant — never user input. Result is cached via transient.
/**
 * Cart Widget data class.
 *
 * Provides summary stats for the dashboard abandoned-cart widget.
 *
 * @since 2.2.0
 */
class PDWCHS_Cart_Widget {

	/**
	 * Transient cache TTL in seconds (10 minutes).
	 *
	 * @var int
	 */
	const CACHE_TTL = 10 * MINUTE_IN_SECONDS;

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'pdwchs_dashboard_widgets', [ $this, 'pdwchs_render_widget' ] );
	}

	/**
	 * H6 listener — render the cart dashboard widget via the template.
	 */
	public function pdwchs_render_widget() {
		include __DIR__ . '/templates/cart-dashboard-widget.php';
	}

	/**
	 * Get abandoned-cart summary stats for the last N days.
	 *
	 * @since 2.2.0
	 *
	 * @param int $days Number of days to look back.
	 * @return array { abandoned_count: int, abandoned_value: float, currency: string }
	 */
	public function pdwchs_get_summary_stats( $days = 7 ) {
		$days      = absint( $days );
		$cache_key = 'pdwchs_cart_widget_' . $days . 'd';

		if ( ! get_option( 'pdwchs_cart_tracking_enabled', 1 ) ) {
			return array(
				'abandoned_count' => 0,
				'abandoned_value' => 0.0,
				'currency'        => get_woocommerce_currency(),
			);
		}

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;

		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(*) AS abandoned_count, COALESCE(SUM(cart_total), 0) AS abandoned_value
				FROM {$wpdb->prefix}pdwchs_carts
				WHERE status = 'abandoned'
				  AND abandoned_at >= DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		$result = array(
			'abandoned_count' => $row ? (int) $row->abandoned_count : 0,
			'abandoned_value' => $row ? (float) $row->abandoned_value : 0.0,
			'currency'        => get_woocommerce_currency(),
		);

		set_transient( $cache_key, $result, self::CACHE_TTL );

		return $result;
	}
}
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
