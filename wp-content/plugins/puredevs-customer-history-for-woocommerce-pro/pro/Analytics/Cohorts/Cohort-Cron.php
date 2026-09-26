<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daily precompute of the cohort retention matrix.
 *
 * The matrix is expensive to build and never computed on page load: this cron
 * runs it nightly and stores the finished payload in one option, which the
 * Cohorts page, its chart and its CSV export all read through
 * pdwchs_get_matrix().
 *
 * @since 3.1.0
 */
class PDWCHS_Cohort_Cron {

	const HOOK           = 'pdwchs_daily_cohort_calculation';
	const OPTION         = 'pdwchs_cohort_matrix';
	const LOCK_TRANSIENT = 'pdwchs_cohort_cron_lock';
	const LOCK_TTL       = 15 * MINUTE_IN_SECONDS;

	public function __construct() {
		add_action( self::HOOK, array( $this, 'pdwchs_run_calculation' ) );
		add_action( 'pdwchs_register_pro_cron', array( $this, 'pdwchs_on_register_cron' ) ); // H16
	}

	/** H16 listener — defer scheduling to init, like PDWCHS_Cart_Cron. */
	public function pdwchs_on_register_cron() {
		add_action( 'init', array( 'PDWCHS_Cohort_Cron', 'pdwchs_schedule_events' ) );
	}

	/**
	 * Schedule the nightly build, and a one-off run when there is nothing stored yet.
	 *
	 * The two guards are independent on purpose: folding them into one would give
	 * the bootstrap run only to sites that had no daily event yet.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public static function pdwchs_schedule_events() {
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( strtotime( 'tomorrow 3:30 AM' ), 'daily', self::HOOK );
		}

		if ( false === get_option( self::OPTION, false ) && ! wp_next_scheduled( self::HOOK, array( 'bootstrap' ) ) ) {
			wp_schedule_single_event( time() + ( 5 * MINUTE_IN_SECONDS ), self::HOOK, array( 'bootstrap' ) );
		}
	}

	/**
	 * Clear both the recurring event and any pending bootstrap event.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public static function pdwchs_unschedule_events() {
		wp_clear_scheduled_hook( self::HOOK );
		wp_clear_scheduled_hook( self::HOOK, array( 'bootstrap' ) );
	}

	/**
	 * Build the matrix and replace the stored payload.
	 *
	 * @since 3.1.0
	 *
	 * @param string $context Unused; distinguishes the bootstrap single event.
	 * @return void
	 */
	public function pdwchs_run_calculation( $context = '' ) {
		if ( get_transient( self::LOCK_TRANSIENT ) ) {
			return;
		}
		set_transient( self::LOCK_TRANSIENT, 1, self::LOCK_TTL );

		try {
			$started = microtime( true );

			require_once __DIR__ . '/Cohort-Data.php';
			$payload = ( new PDWCHS_Cohort_Data() )->pdwchs_build_matrix();

			$payload['generated_at'] = gmdate( 'Y-m-d H:i:s' );
			$payload['duration_ms']  = (int) round( ( microtime( true ) - $started ) * 1000 );

			update_option( self::OPTION, $payload, false );
		} finally {
			delete_transient( self::LOCK_TRANSIENT );
		}
	}

	/**
	 * The stored cohort payload, or null when the cron has never completed.
	 *
	 * The single read door: the page, the chart and the CSV export all call this and
	 * none of them calls get_option() directly.
	 *
	 * @since 3.1.0
	 *
	 * @return array|null
	 */
	public static function pdwchs_get_matrix() {
		$payload = get_option( self::OPTION, null );

		return ( is_array( $payload ) && isset( $payload['cohorts'] ) ) ? $payload : null;
	}
}
