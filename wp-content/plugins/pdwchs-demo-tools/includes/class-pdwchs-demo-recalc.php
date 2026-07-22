<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Runs the product's own intelligence calculators synchronously, in the same
 * dependency order as its cron map (pro/Intelligence/Intelligence.php), then
 * applies the two post-recalc fixups a fresh dataset cannot produce on its
 * own (backdated lifecycle stage dates; the Declining-Health two-pass), and
 * finally regenerates alerts so alert scores equal list scores exactly.
 */
class PDWCHS_Demo_Recalc {

	/** @var PDWCHS_Demo_Plan */
	private $plan;

	public function __construct( PDWCHS_Demo_Plan $plan ) {
		$this->plan = $plan;
	}

	private function intel() {
		$intel = apply_filters( 'pdwchs_intelligence_instance', null );
		if ( ! $intel ) {
			throw new RuntimeException( 'Pro intelligence not loaded — is the Pro build active and licensed?' );
		}
		return $intel;
	}

	// ── Action Scheduler drain (wc-admin order-stats + lookup imports) ──

	public function step_as_drain() {
		if ( ! function_exists( 'as_get_scheduled_actions' ) || ! class_exists( 'ActionScheduler_QueueRunner' ) ) {
			return 'action scheduler absent — skipped';
		}
		$start = time();
		$loops = 0;
		while ( $loops < 200 ) {
			$due = as_get_scheduled_actions( array(
				'status'       => ActionScheduler_Store::STATUS_PENDING,
				'date'         => as_get_datetime_object( 'now' ),
				'date_compare' => '<=',
				'per_page'     => 1,
				'orderby'      => 'none',
			), 'ids' );
			if ( empty( $due ) ) {
				$kick = $this->maybe_kick_stats_import();
				if ( $kick ) {
					return $kick;
				}
				return "action scheduler drained ({$loops} batches)";
			}
			ActionScheduler_QueueRunner::instance()->run( 'pdwchs-demo-drain' );
			$loops++;
			if ( ( time() - $start ) > 15 ) {
				return array( 'repeat' => true, 'msg' => "AS drain in progress ({$loops} batches this slice)" );
			}
		}
		return 'AS drain: loop cap reached (check `wp action-scheduler run`)';
	}

	/**
	 * wc-admin never schedules its order-stats/lookup import for orders the
	 * seeder inserts directly with backdates, so an empty queue can still leave
	 * wc_order_stats missing every seeded order — which skews the store-average
	 * inputs and trips the verifier's parity warn. Detect the gap, kick one full
	 * ReportsSync regenerate, and keep draining until parity (or a 4-min cap,
	 * after which the gap is left for verify to report).
	 *
	 * @return array|null Repeat instruction while converging, null when done.
	 */
	private function maybe_kick_stats_import() {
		if ( ! class_exists( '\Automattic\WooCommerce\Admin\ReportsSync' ) ) {
			return null;
		}
		global $wpdb;
		$stats_table = $wpdb->prefix . 'wc_order_stats';
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $stats_table ) ) ) {
			return null;
		}
		$order_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}wc_orders WHERE type='shop_order' AND status IN ('wc-completed','wc-processing')" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$stats_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$stats_table} WHERE status IN ('wc-completed','wc-processing')" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		if ( $stats_count >= $order_count ) {
			delete_transient( 'pdwchs_demo_stats_regen' );
			return null;
		}
		$kicked_at = (int) get_transient( 'pdwchs_demo_stats_regen' );
		if ( $kicked_at && ( time() - $kicked_at ) > 4 * MINUTE_IN_SECONDS ) {
			return null; // not converging — surface via the verifier's parity warn instead of spinning
		}
		if ( ! $kicked_at ) {
			set_transient( 'pdwchs_demo_stats_regen', time(), 10 * MINUTE_IN_SECONDS );
			if ( ! \Automattic\WooCommerce\Admin\ReportsSync::is_importing() ) {
				\Automattic\WooCommerce\Admin\ReportsSync::regenerate_report_data( null, false );
			}
			sleep( 2 ); // import batches self-schedule a few seconds out; don't spin the repeat guard
			return array( 'repeat' => true, 'msg' => "wc-admin stats regenerate kicked ({$stats_count}/{$order_count})" );
		}
		sleep( 2 );
		return array( 'repeat' => true, 'msg' => "wc-admin stats import running ({$stats_count}/{$order_count})" );
	}

	// ── the six calculators, cron order ─────────────────────────────────

	public function step_store_average() {
		$this->intel()->pdwchs_get_rfm_calculator()->pdwchs_update_store_average_cache();
		return 'store average rebuilt';
	}

	public function step_rfm() {
		delete_option( 'pdwchs_rfm_batch_offset' );
		$rfm = $this->intel()->pdwchs_get_rfm_calculator();
		for ( $i = 0; $i < 60; $i++ ) {
			if ( true === $rfm->pdwchs_batch_calculate_all() ) {
				return "rfm complete ({$i} batches)";
			}
		}
		return 'rfm: batch cap reached';
	}

	public function step_health() {
		delete_option( 'pdwchs_health_batch_offset' );
		$health = $this->intel()->pdwchs_get_health_calculator();
		for ( $i = 0; $i < 60; $i++ ) {
			if ( true === $health->pdwchs_batch_calculate_all_health() ) {
				return "health complete ({$i} batches)";
			}
		}
		return 'health: batch cap reached';
	}

	public function step_lifecycle() {
		delete_option( 'pdwchs_lifecycle_batch_offset' );
		$lifecycle = $this->intel()->pdwchs_get_lifecycle_calculator();
		for ( $i = 0; $i < 60; $i++ ) {
			if ( true === $lifecycle->pdwchs_batch_calculate_all() ) {
				return "lifecycle complete ({$i} batches)";
			}
		}
		return 'lifecycle: batch cap reached';
	}

	public function step_behavior() {
		$this->intel()->pdwchs_get_behavioral_analyzer()->pdwchs_analyze_all_behaviors();
		return 'behavior analyzed';
	}

	public function step_churn() {
		delete_option( 'pdwchs_churn_batch_offset' );
		$churn = $this->intel()->pdwchs_get_churn_calculator();
		for ( $i = 0; $i < 60; $i++ ) {
			$res = $churn->pdwchs_batch_calculate_all();
			if ( empty( $res['processed'] ) || ! get_option( 'pdwchs_churn_batch_offset' ) ) {
				return 'churn complete (' . ( $i + 1 ) . ' batches)';
			}
		}
		return 'churn: batch cap reached';
	}

	// ── fixup 1: lifecycle stage-date backfill + history rewrite ────────
	//
	// A fresh calculation always yields "entered stage today / 0 days in
	// stage", which keeps the "At Risk (30+ Days)" widget empty and makes
	// every profile read "entered stage <refresh morning>". Backdate stage
	// entry to its logical date derived from the order timeline; later
	// product crons only ever recompute days_in_stage FROM this date, so the
	// fixup is stable while the stage itself is unchanged.

	public function step_fixup_lifecycle() {
		global $wpdb;
		$plan   = $this->plan->build();
		$ledger = PDWCHS_Demo_Ledger::get();
		$table  = $wpdb->prefix . 'pdwchs_customer_intelligence';
		$hist   = $wpdb->prefix . 'pdwchs_lifecycle_history';
		$now_ts = current_time( 'timestamp' );
		$fixed  = 0;

		foreach ( $plan['customers'] as $c ) {
			$user_id = isset( $ledger['user_map'][ $c['key'] ] ) ? (int) $ledger['user_map'][ $c['key'] ] : 0;
			if ( ! $user_id ) {
				continue;
			}

			$trail = $this->stage_trail( $c );
			if ( ! $trail ) {
				continue;
			}
			$current = end( $trail );

			// Only backdate when the product computed the stage we expected —
			// never forge a stage the calculators did not produce.
			$db_stage = $wpdb->get_var( $wpdb->prepare( "SELECT lifecycle_stage FROM {$table} WHERE user_id = %d", $user_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			if ( $db_stage !== $current['stage'] ) {
				continue;
			}

			$wpdb->delete( $hist, array( 'user_id' => $user_id ), array( '%d' ) );
			$prev = null;
			foreach ( $trail as $i => $step ) {
				$next = isset( $trail[ $i + 1 ] ) ? $trail[ $i + 1 ] : null;
				$wpdb->insert( $hist, array(
					'user_id'        => $user_id,
					'stage'          => $step['stage'],
					'previous_stage' => $prev,
					'entered_date'   => $step['date'],
					'exited_date'    => $next ? $next['date'] : null,
					'days_in_stage'  => $next ? (int) floor( ( strtotime( $next['date'] ) - strtotime( $step['date'] ) ) / DAY_IN_SECONDS ) : null,
				) );
				$prev = $step['stage'];
			}

			$prev_stage = count( $trail ) > 1 ? $trail[ count( $trail ) - 2 ]['stage'] : null;
			$wpdb->update( $table, array(
				'lifecycle_stage_date'     => $current['date'],
				'lifecycle_previous_stage' => $prev_stage,
				'lifecycle_days_in_stage'  => (int) floor( ( $now_ts - strtotime( $current['date'] ) ) / DAY_IN_SECONDS ),
			), array( 'user_id' => $user_id ) );
			$fixed++;
		}

		delete_transient( 'pdwchs_lifecycle_distribution' );
		return "lifecycle backdated for {$fixed} customers";
	}

	/** Logical stage trail [(stage, local datetime), …] from the planned order timeline. */
	private function stage_trail( $c ) {
		if ( empty( $c['orders'] ) ) {
			return array( array( 'stage' => 'Prospect', 'date' => $this->plan->local_datetime( $c['registered'], 11 ) ) );
		}

		$days = array();
		foreach ( $c['orders'] as $o ) {
			$days[] = $o['days'];
		}
		rsort( $days ); // oldest first
		$last  = min( $days );
		$trail = array( array( 'stage' => 'New Customer', 'date' => $this->plan->local_datetime( $days[0], 12 ) ) );
		if ( count( $days ) >= 2 ) {
			$trail[] = array( 'stage' => 'Active Customer', 'date' => $this->plan->local_datetime( $days[1], 12 ) );
		}
		if ( count( $days ) >= 3 ) {
			$trail[] = array( 'stage' => 'Loyal Customer', 'date' => $this->plan->local_datetime( $days[2], 12 ) );
		}
		if ( $last > 90 ) {
			$trail[] = array( 'stage' => 'At Risk', 'date' => $this->plan->local_datetime( $last - 91, 9 ) );
		}
		if ( $last > 180 ) {
			$trail[] = array( 'stage' => 'Churned', 'date' => $this->plan->local_datetime( $last - 181, 9 ) );
		}
		return $trail;
	}

	// ── fixup 2: Declining-Health two-pass ──────────────────────────────
	//
	// The widget needs previous_score − current ≥ 10, impossible on a first
	// calculation. Pass A: temporary very-recent order + sessions → the
	// product's own calculator stores a high score. Remove temps. Pass B:
	// recalculate → previous = high, current = real, trend = down. No score
	// is ever written by hand.

	public function step_fixup_health() {
		global $wpdb;
		$plan   = $this->plan->build();
		$ledger = PDWCHS_Demo_Ledger::get();
		$health = $this->intel()->pdwchs_get_health_calculator();
		$table  = $wpdb->prefix . 'pd_woo_customer_history_sessions';
		$done   = array();

		foreach ( $plan['decliners'] as $key ) {
			$customer = null;
			foreach ( $plan['customers'] as $c ) {
				if ( $c['key'] === $key ) {
					$customer = $c;
					break;
				}
			}
			$user_id = isset( $ledger['user_map'][ $key ] ) ? (int) $ledger['user_map'][ $key ] : 0;
			if ( ! $customer || ! $user_id ) {
				continue;
			}

			// Pass A: temp order 5 days ago at the customer's usual basket size.
			$spec = array(
				'days'   => 5,
				'hh'     => 11,
				'mm'     => 30,
				'total'  => $customer['orders'] ? $customer['orders'][0]['total'] : 60,
				'status' => 'completed',
			);
			$temp_order_id = $this->create_temp_order( $spec, $user_id, $customer );

			$temp_session_ids = array();
			for ( $i = 0; $i < 8; $i++ ) {
				$wpdb->insert( $table, array(
					'user_id'    => $user_id,
					'ip'         => $customer['ip'],
					'url'        => 'shop',
					'reg_date'   => $this->plan->local_datetime( 3 + ( $i % 3 ), 10, $i * 4 ),
					'referer'    => '',
					'del'        => 0,
					'session_id' => substr( md5( 'temp' . $key . $i ), 0, 16 ),
				) );
				if ( $wpdb->insert_id ) {
					$temp_session_ids[] = $wpdb->insert_id;
				}
			}

			$health->pdwchs_calculate_customer_health( $user_id );

			// Remove temps, then Pass B stores the real score with the high previous.
			if ( $temp_order_id ) {
				$order = wc_get_order( $temp_order_id );
				if ( $order ) {
					$order->delete( true );
				}
			}
			if ( $temp_session_ids ) {
				$ids = implode( ',', array_map( 'absint', $temp_session_ids ) );
				$wpdb->query( "DELETE FROM {$table} WHERE id IN ({$ids})" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}

			$health->pdwchs_calculate_customer_health( $user_id );
			$done[] = $key;
		}

		delete_transient( 'pdwchs_health_distribution' );
		return 'health decline staged for: ' . implode( ', ', $done );
	}

	private function create_temp_order( $spec, $user_id, $customer ) {
		$order = wc_create_order( array( 'customer_id' => $user_id, 'created_via' => 'checkout' ) );
		if ( is_wp_error( $order ) ) {
			return 0;
		}
		$products = wc_get_products( array( 'limit' => 1, 'orderby' => 'ID', 'order' => 'ASC' ) );
		if ( $products ) {
			$order->add_product( $products[0], 1, array( 'subtotal' => $spec['total'], 'total' => $spec['total'] ) );
		}
		$order->calculate_totals( false );
		$order->set_status( 'completed' );
		$ts = $this->plan->gmt_ts( $spec['days'], $spec['hh'], $spec['mm'] );
		$order->set_date_created( $ts );
		$order->set_date_paid( $ts + 120 );
		$order->set_date_completed( $ts + HOUR_IN_SECONDS );
		$order->add_meta_data( '_pdwchs_demo_temp', 1, true );
		$order->save();
		return $order->get_id();
	}

	// ── alerts + finalize ────────────────────────────────────────────────

	public function step_alerts() {
		$res = $this->intel()->pdwchs_get_alerts_manager()->pdwchs_generate_alerts();
		return 'alerts generated: ' . ( isset( $res['generated'] ) ? $res['generated'] : 0 );
	}

	public function step_finalize() {
		// Kill the stray single events the batch loops self-scheduled, then
		// restore the product's daily schedule.
		foreach ( array( 'pdwchs_daily_rfm_calculation', 'pdwchs_daily_health_calculation', 'pdwchs_daily_lifecycle_calculation', 'pdwchs_analyze_customer_behavior' ) as $hook ) {
			wp_unschedule_hook( $hook );
		}
		$installer = PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'pro/Pro-Installer.php';
		if ( ! class_exists( 'PDWCHS_Pro_Installer' ) && file_exists( $installer ) ) {
			require_once $installer;
		}
		if ( class_exists( 'PDWCHS_Pro_Installer' ) ) {
			PDWCHS_Pro_Installer::pdwchs_register_cron();
		}

		self::flush_plugin_caches();

		update_option( 'pdwchs_demo_last_refresh', array(
			'anchor'   => $this->plan->anchor(),
			'rng_seed' => $this->plan->rng_seed(),
			'time'     => current_time( 'mysql' ),
		), false );

		return 'crons restored, caches flushed';
	}

	public static function flush_plugin_caches() {
		global $wpdb;
		// Demo-site-acceptable catch-all: every pdwchs transient (30-min
		// distribution caches, 24h store average, churn caches, revenue caches).
		$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '\_transient\_%pdwchs%' OR option_name LIKE '\_transient\_timeout\_%pdwchs%'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		wp_cache_flush();
	}
}
