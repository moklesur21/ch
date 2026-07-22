<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers and runs WP-Cron jobs for abandoned cart detection and cleanup.
 *
 * @since 6.0.0
 */
class PDWCHS_Cart_Cron {

	const ABANDONMENT_HOOK = 'pdwchs_cart_abandonment_check';
	const CLEANUP_HOOK     = 'pdwchs_cart_cleanup';
	const LOCK_TRANSIENT   = 'pdwchs_cart_cron_lock';
	const LOCK_TTL         = 840;
	const BATCH_LIMIT      = 500;
	const INTERVAL_SLUG    = 'pdwchs_fifteen_minutes';

	public function __construct() {
		$this->pdwchs_register_hooks();
		add_action( 'pdwchs_register_pro_cron', [ $this, 'pdwchs_on_register_cron' ] );
	}

	public function pdwchs_on_register_cron() {
		add_action( 'init', [ 'PDWCHS_Cart_Cron', 'pdwchs_schedule_events' ] );
	}

	public function pdwchs_register_hooks() {
		add_action( self::ABANDONMENT_HOOK, [ $this, 'pdwchs_run_abandonment_check' ] );
		add_action( self::CLEANUP_HOOK,     [ $this, 'pdwchs_run_cleanup' ] );
	}

	public function pdwchs_run_abandonment_check() {
		if ( ! get_option( 'pdwchs_cart_tracking_enabled', 1 ) ) {
			return;
		}

		if ( get_transient( self::LOCK_TRANSIENT ) ) {
			return;
		}
		set_transient( self::LOCK_TRANSIENT, 1, self::LOCK_TTL );

		$timeout = max( 5, absint( get_option( 'pdwchs_cart_abandonment_timeout_minutes', 60 ) ) );

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_carts';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- cron batch UPDATE; $table is $wpdb->prefix . literal string; no user input; no WP CRUD equivalent
		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table}
				 SET    status = 'abandoned', abandoned_at = NOW()
				 WHERE  status = 'active'
				   AND  updated_at <= NOW() - INTERVAL %d MINUTE
				 LIMIT  %d",
				$timeout,
				self::BATCH_LIMIT
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		delete_transient( self::LOCK_TRANSIENT );
	}

	public function pdwchs_run_cleanup() {
		if ( ! get_option( 'pdwchs_cart_tracking_enabled', 1 ) ) {
			return;
		}

		$days = max( 7, absint( get_option( 'pdwchs_cart_cleanup_days', 90 ) ) );

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_carts';
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- cron batch DELETE; $table is $wpdb->prefix . literal string; no user input; no WP CRUD equivalent
		$wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$table} WHERE updated_at < NOW() - INTERVAL %d DAY",
				$days
			)
		);
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public static function pdwchs_schedule_events() {
		if ( ! wp_next_scheduled( self::ABANDONMENT_HOOK ) ) {
			wp_schedule_event(
				time() + MINUTE_IN_SECONDS,
				self::INTERVAL_SLUG,
				self::ABANDONMENT_HOOK
			);
		}
		if ( ! wp_next_scheduled( self::CLEANUP_HOOK ) ) {
			wp_schedule_event(
				strtotime( 'tomorrow 6:00 AM' ),
				'daily',
				self::CLEANUP_HOOK
			);
		}
	}

	public static function pdwchs_unschedule_events() {
		wp_clear_scheduled_hook( self::ABANDONMENT_HOOK );
		wp_clear_scheduled_hook( self::CLEANUP_HOOK );
	}
}
