<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ordered idempotent step registry shared by the CLI (synchronous) and the
 * REST trigger (background execution in ~18s self-chaining WP-Cron slices,
 * progress in an option, resumable after a died tick).
 *
 * The step-name list is FROZEN into the run state at start — later ticks
 * dispatch by name, so a mid-run ledger change can never desynchronize the
 * sequence.
 */
class PDWCHS_Demo_Runner {

	const STATE_OPTION  = 'pdwchs_demo_run_state';
	const TICK_BUDGET   = 18;   // seconds of work per background slice
	const STALL_SECONDS = 180;  // a "running" run older than this is considered stalled/resumable

	public static function state() {
		$state = get_option( self::STATE_OPTION, null );
		return is_array( $state ) ? $state : null;
	}

	/** Launch a background run (REST path). Returns state array or WP_Error. */
	public static function start( $mode, $args = array() ) {
		if ( ! pdwchs_demo_allowed() ) {
			return new WP_Error( 'pdwchs_demo_blocked', pdwchs_demo_blocked_message(), array( 'status' => 403 ) );
		}

		$existing = self::state();
		if ( $existing && 'running' === $existing['status'] ) {
			$age = time() - (int) $existing['updated_utc'];
			if ( $age < self::STALL_SECONDS ) {
				return new WP_Error( 'pdwchs_demo_busy', 'A run is already in progress (run_id ' . $existing['run_id'] . ').', array( 'status' => 409 ) );
			}
			// Stalled — kick it back to life instead of starting over.
			self::schedule_tick();
			$existing['log'][] = array( current_time( 'mysql' ), '(resume)', "stalled {$age}s — rescheduled" );
			$existing['updated_utc'] = time();
			update_option( self::STATE_OPTION, $existing, false );
			$existing['resumed'] = true;
			return $existing;
		}

		$state = self::new_state( $mode, $args );
		if ( is_wp_error( $state ) ) {
			return $state;
		}
		update_option( self::STATE_OPTION, $state, false );
		self::schedule_tick();
		return $state;
	}

	/** Run every step inline (CLI path). $logger: function( $step, $msg ). */
	public static function run_sync( $mode, $args = array(), $logger = null ) {
		if ( ! pdwchs_demo_allowed() ) {
			return new WP_Error( 'pdwchs_demo_blocked', pdwchs_demo_blocked_message() );
		}
		$state = self::new_state( $mode, $args );
		if ( is_wp_error( $state ) ) {
			return $state;
		}
		update_option( self::STATE_OPTION, $state, false );

		PDWCHS_Demo_Seeder::suppress_side_effects();
		$ctx = self::ctx( $state );

		foreach ( $state['steps'] as $i => $name ) {
			$guard = 0;
			do {
				try {
					$result = self::execute( $name, $ctx, $state );
				} catch ( Throwable $e ) {
					$state['status']      = 'failed';
					$state['error']       = $name . ': ' . $e->getMessage();
					$state['updated_utc'] = time();
					update_option( self::STATE_OPTION, $state, false );
					return new WP_Error( 'pdwchs_demo_step_failed', $state['error'] );
				}
				$repeat = is_array( $result ) && ! empty( $result['repeat'] );
				$msg    = is_array( $result ) ? $result['msg'] : $result;
				if ( $logger ) {
					call_user_func( $logger, $name, $msg );
				}
				$guard++;
			} while ( $repeat && $guard < 500 );

			$state['next']        = $i + 1;
			$state['log'][]       = array( current_time( 'mysql' ), $name, is_array( $result ) ? $result['msg'] : $result );
			$state['log']         = array_slice( $state['log'], -80 );
			$state['updated_utc'] = time();
			update_option( self::STATE_OPTION, $state, false );
		}

		$state['status']      = 'done';
		$state['updated_utc'] = time();
		update_option( self::STATE_OPTION, $state, false );
		return $state;
	}

	/** WP-Cron callback: one background slice. */
	public static function tick() {
		$state = self::state();
		if ( ! $state || 'running' !== $state['status'] ) {
			return;
		}
		if ( ! pdwchs_demo_allowed() ) {
			$state['status'] = 'failed';
			$state['error']  = pdwchs_demo_blocked_message();
			update_option( self::STATE_OPTION, $state, false );
			return;
		}

		PDWCHS_Demo_Seeder::suppress_side_effects();
		$ctx   = self::ctx( $state );
		$start = time();

		while ( $state['next'] < count( $state['steps'] ) && ( time() - $start ) < self::TICK_BUDGET ) {
			$name = $state['steps'][ $state['next'] ];
			try {
				$result = self::execute( $name, $ctx, $state );
			} catch ( Throwable $e ) {
				$state['status']      = 'failed';
				$state['error']       = $name . ': ' . $e->getMessage();
				$state['updated_utc'] = time();
				update_option( self::STATE_OPTION, $state, false );
				return;
			}

			$repeat = is_array( $result ) && ! empty( $result['repeat'] );
			$msg    = is_array( $result ) ? $result['msg'] : $result;

			$state['log'][]       = array( current_time( 'mysql' ), $name, $msg );
			$state['log']         = array_slice( $state['log'], -80 );
			$state['updated_utc'] = time();
			if ( ! $repeat ) {
				$state['next']++;
			}
			update_option( self::STATE_OPTION, $state, false );
		}

		if ( $state['next'] >= count( $state['steps'] ) ) {
			$state['status']      = 'done';
			$state['updated_utc'] = time();
			update_option( self::STATE_OPTION, $state, false );
			return;
		}
		self::schedule_tick();
	}

	private static function schedule_tick() {
		if ( ! wp_next_scheduled( 'pdwchs_demo_run_tick' ) ) {
			wp_schedule_single_event( time(), 'pdwchs_demo_run_tick' );
		}
		if ( function_exists( 'spawn_cron' ) ) {
			spawn_cron();
		}
	}

	private static function new_state( $mode, $args ) {
		$anchor   = ! empty( $args['anchor'] ) ? $args['anchor'] : null;
		$rng_seed = ! empty( $args['rng_seed'] ) ? (int) $args['rng_seed'] : null;

		if ( in_array( $mode, array( 'seed', 'seed-woo' ), true ) && PDWCHS_Demo_Ledger::exists() ) {
			return new WP_Error( 'pdwchs_demo_seeded', 'A seeded dataset already exists — use refresh (or teardown first).' );
		}
		if ( in_array( $mode, array( 'teardown', 'recalc' ), true ) && ! PDWCHS_Demo_Ledger::exists() ) {
			return new WP_Error( 'pdwchs_demo_no_ledger', 'No seeded dataset found (no ledger).' );
		}

		$plan  = new PDWCHS_Demo_Plan( $anchor, $rng_seed );
		$steps = self::build_step_names( $mode, $plan );
		if ( is_wp_error( $steps ) ) {
			return $steps;
		}

		return array(
			'run_id'      => 'run_' . gmdate( 'Ymd_His' ) . '_' . strtolower( wp_generate_password( 6, false, false ) ),
			'mode'        => $mode,
			'args'        => array( 'with_catalog' => ! empty( $args['with_catalog'] ) ),
			'anchor'      => $plan->anchor(),
			'rng_seed'    => $plan->rng_seed(),
			'status'      => 'running',
			'steps'       => $steps,
			'next'        => 0,
			'log'         => array(),
			'error'       => null,
			'started_at'  => current_time( 'mysql' ),
			'updated_utc' => time(),
		);
	}

	private static function build_step_names( $mode, PDWCHS_Demo_Plan $plan ) {
		$teardown = array();
		if ( PDWCHS_Demo_Ledger::exists() ) {
			$teardown[] = 't_scores';
			for ( $i = 0; $i < PDWCHS_Demo_Teardown::order_batches(); $i++ ) {
				$teardown[] = 't_orders:' . $i;
			}
			$teardown[] = 't_orders_sweep';
			$teardown[] = 't_sessions_carts';
			for ( $i = 0; $i < PDWCHS_Demo_Teardown::user_batches(); $i++ ) {
				$teardown[] = 't_users:' . $i;
			}
			$teardown[] = 't_finalize';
		}

		$seed = array( 's_init', 's_identity', 's_catalog', 's_users' );
		$order_batches = (int) ceil( count( ( new PDWCHS_Demo_Seeder( $plan ) )->order_specs() ) / PDWCHS_Demo_Seeder::ORDER_BATCH );
		for ( $i = 0; $i < $order_batches; $i++ ) {
			$seed[] = 's_orders:' . $i;
		}
		$seed = array_merge( $seed, array( 's_refunds', 's_as_drain' ) );
		// WooCommerce data only (catalog, customers, orders, refunds): for a
		// store the product has never been installed on (V8's install video).
		$seed_woo = $seed;
		$seed     = array_merge( $seed, array( 's_sessions', 's_carts' ) );

		$recalc = array( 'r_store_avg', 'r_rfm', 'r_health', 'r_lifecycle', 'r_behavior', 'r_churn', 'f_lifecycle', 'f_health', 'r_alerts', 'r_finalize' );

		switch ( $mode ) {
			case 'refresh':
				return array_merge( $teardown, $seed, $recalc, array( 'v_verify' ) );
			case 'seed':
				return array_merge( $seed, $recalc, array( 'v_verify' ) );
			case 'seed-woo':
				return $seed_woo;
			case 'teardown':
				return $teardown ? $teardown : new WP_Error( 'pdwchs_demo_no_ledger', 'Nothing to tear down.' );
			case 'recalc':
				return array_merge( $recalc, array( 'v_verify' ) );
			case 'wipe-legacy':
				return array( 'w_orders', 'w_users', 'w_tables' );
		}
		return new WP_Error( 'pdwchs_demo_bad_mode', 'Unknown run mode: ' . $mode );
	}

	private static function ctx( $state ) {
		$plan = new PDWCHS_Demo_Plan( $state['anchor'], $state['rng_seed'] );
		return array(
			'plan'   => $plan,
			'seeder' => new PDWCHS_Demo_Seeder( $plan ),
			'recalc' => new PDWCHS_Demo_Recalc( $plan ),
		);
	}

	/** Dispatch one named step. Returns a message string or ['repeat'=>true,'msg'=>…]. */
	private static function execute( $name, $ctx, $state ) {
		$idx = null;
		if ( false !== strpos( $name, ':' ) ) {
			list( $name, $idx ) = explode( ':', $name, 2 );
			$idx = (int) $idx;
		}

		switch ( $name ) {
			// teardown
			case 't_scores':
				return PDWCHS_Demo_Teardown::step_scores_tables();
			case 't_orders':
				return PDWCHS_Demo_Teardown::step_orders( $idx );
			case 't_orders_sweep':
				return PDWCHS_Demo_Teardown::step_orders_sweep();
			case 't_sessions_carts':
				return PDWCHS_Demo_Teardown::step_sessions_carts();
			case 't_users':
				return PDWCHS_Demo_Teardown::step_users( $idx );
			case 't_finalize':
				return PDWCHS_Demo_Teardown::step_finalize( ! empty( $state['args']['with_catalog'] ) );

			// seed
			case 's_init':
				if ( ! PDWCHS_Demo_Ledger::exists() ) {
					PDWCHS_Demo_Ledger::init( $ctx['plan']->anchor(), $ctx['plan']->rng_seed() );
					return 'ledger initialized (anchor ' . $ctx['plan']->anchor() . ')';
				}
				return 'ledger present';
			case 's_identity':
				return $ctx['seeder']->step_identity();
			case 's_catalog':
				return $ctx['seeder']->step_catalog();
			case 's_users':
				return $ctx['seeder']->step_users();
			case 's_orders':
				return $ctx['seeder']->step_orders( $idx );
			case 's_refunds':
				return $ctx['seeder']->step_refunds();
			case 's_as_drain':
				return $ctx['recalc']->step_as_drain();
			case 's_sessions':
				return $ctx['seeder']->step_sessions();
			case 's_carts':
				return $ctx['seeder']->step_carts();

			// recalc + fixups
			case 'r_store_avg':
				return $ctx['recalc']->step_store_average();
			case 'r_rfm':
				return $ctx['recalc']->step_rfm();
			case 'r_health':
				return $ctx['recalc']->step_health();
			case 'r_lifecycle':
				return $ctx['recalc']->step_lifecycle();
			case 'r_behavior':
				return $ctx['recalc']->step_behavior();
			case 'r_churn':
				return $ctx['recalc']->step_churn();
			case 'f_lifecycle':
				return $ctx['recalc']->step_fixup_lifecycle();
			case 'f_health':
				return $ctx['recalc']->step_fixup_health();
			case 'r_alerts':
				return $ctx['recalc']->step_alerts();
			case 'r_finalize':
				return $ctx['recalc']->step_finalize();

			// verify (never fails the run — it reports)
			case 'v_verify':
				$verifier = new PDWCHS_Demo_Verifier( $ctx['plan'] );
				$result   = $verifier->run();
				update_option( 'pdwchs_demo_last_verify', $result, false );
				return 'verify: ' . $result['counts']['pass'] . ' pass / ' . $result['counts']['fail'] . ' fail / ' . $result['counts']['warn'] . ' warn';

			// wipe-legacy
			case 'w_orders':
				return PDWCHS_Demo_Teardown::wipe_legacy_order_pass();
			case 'w_users':
				return PDWCHS_Demo_Teardown::wipe_legacy_users();
			case 'w_tables':
				return PDWCHS_Demo_Teardown::wipe_legacy_tables();
		}
		throw new RuntimeException( 'Unknown step: ' . $name );
	}
}
