<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Detects cart recovery: flips a prior abandoned (or active) cart row to
 * `recovered` when the customer completes checkout.
 *
 * Fires at priority 20 on woocommerce_checkout_order_processed, after
 * PDWCHS_Cart_Tracker::pdwchs_mark_completed (priority 10).
 *
 * @since 6.0.0
 */
class PDWCHS_Cart_Recovery_Detector {

	const CHECKOUT_HOOK        = 'woocommerce_checkout_order_processed';
	const BLOCKS_CHECKOUT_HOOK = 'woocommerce_store_api_checkout_order_processed';
	const HOOK_PRIORITY        = 20; // fires after PDWCHS_Cart_Tracker::pdwchs_mark_completed (priority 10)

	public function __construct() {
		$this->pdwchs_register_hooks();
	}

	public function pdwchs_register_hooks() {
		add_action( self::CHECKOUT_HOOK,        [ $this, 'pdwchs_detect_recovery' ],        self::HOOK_PRIORITY );
		add_action( self::BLOCKS_CHECKOUT_HOOK, [ $this, 'pdwchs_detect_recovery_blocks' ], self::HOOK_PRIORITY );
	}

	/**
	 * Detect whether a prior abandoned (or active) cart row exists for the
	 * completing customer and flip it to `recovered`.
	 *
	 * @param int $order_id
	 */
	public function pdwchs_detect_recovery( $order_id ) {
		$order_id = absint( $order_id );
		if ( 0 === $order_id ) {
			return;
		}
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$cleanup_days = max( 7, absint( get_option( 'pdwchs_cart_cleanup_days', 90 ) ) );

		$customer_id = absint( $order->get_customer_id() );
		$email       = sanitize_email( $order->get_billing_email() );

		$row = $this->pdwchs_find_abandoned_cart_row( $customer_id, $email, $cleanup_days );
		if ( null === $row ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_carts';

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$table,
			[
				'status'       => 'recovered',
				'recovered_at' => current_time( 'mysql' ),
				'order_id'     => $order_id,
			],
			[ 'id' => $row->id ],
			[ '%s', '%s', '%d' ],
			[ '%d' ]
		);
	}

	/**
	 * Blocks checkout adapter — extracts order ID and delegates to pdwchs_detect_recovery().
	 *
	 * woocommerce_store_api_checkout_order_processed passes a WC_Order object, not an ID.
	 *
	 * @param WC_Order $order
	 */
	public function pdwchs_detect_recovery_blocks( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->pdwchs_detect_recovery( $order->get_id() );
	}

	/**
	 * Find the most-recent abandoned or active cart row for the given identity.
	 *
	 * Logged-in: match by user_id. Guest: match by email.
	 * Only rows within the cleanup window are eligible (prevents recovering rows
	 * that the cleanup cron is about to delete).
	 *
	 * @param int    $customer_id  WC customer user ID (0 for guest).
	 * @param string $email        Billing email (sanitized).
	 * @param int    $cleanup_days Match window in days (mirrors the cleanup cron boundary).
	 * @return object|null
	 */
	private function pdwchs_find_abandoned_cart_row( int $customer_id, string $email, int $cleanup_days ): ?object {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_carts';

		if ( $customer_id > 0 ) {
			return $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT * FROM {$table} WHERE user_id = %d AND status IN ('abandoned','active') AND updated_at >= NOW() - INTERVAL %d DAY ORDER BY updated_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$customer_id,
				$cleanup_days
			) );
		}

		if ( is_email( $email ) ) {
			return $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT * FROM {$table} WHERE email = %s AND status IN ('abandoned','active') AND updated_at >= NOW() - INTERVAL %d DAY ORDER BY updated_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$email,
				$cleanup_days
			) );
		}

		return null;
	}
}
