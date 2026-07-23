<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Remote trigger: POST /wp-json/pdwchs-demo/v1/refresh from any machine
 * replaces SSH-ing into the live demo box. Every route authenticates the
 * X-PDWCHS-Demo-Token header against the PDWCHS_DEMO_TOKEN wp-config constant
 * via hash_equals(); mutating routes additionally require the
 * PDWCHS_DEMO_ALLOW latch — on a normal site these endpoints are dead.
 */
class PDWCHS_Demo_REST {

	const NS = 'pdwchs-demo/v1';

	public static function register_routes() {
		register_rest_route( self::NS, '/refresh', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_refresh' ),
			'permission_callback' => array( __CLASS__, 'permission_mutate' ),
			'args'                => array(
				'anchor'   => array( 'type' => 'string', 'required' => false ),
				'rng_seed' => array( 'type' => 'integer', 'required' => false ),
			),
		) );
		register_rest_route( self::NS, '/status', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'handle_status' ),
			'permission_callback' => array( __CLASS__, 'permission_read' ),
		) );
		register_rest_route( self::NS, '/verify', array(
			'methods'             => 'GET',
			'callback'            => array( __CLASS__, 'handle_verify' ),
			'permission_callback' => array( __CLASS__, 'permission_read' ),
		) );
		register_rest_route( self::NS, '/wipe-legacy', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_wipe_legacy' ),
			'permission_callback' => array( __CLASS__, 'permission_mutate' ),
		) );
		register_rest_route( self::NS, '/setup-storefront', array(
			'methods'             => 'POST',
			'callback'            => array( __CLASS__, 'handle_setup_storefront' ),
			'permission_callback' => array( __CLASS__, 'permission_mutate' ),
		) );
	}

	private static function token_ok( WP_REST_Request $request ) {
		if ( ! defined( 'PDWCHS_DEMO_TOKEN' ) || ! is_string( PDWCHS_DEMO_TOKEN ) || strlen( PDWCHS_DEMO_TOKEN ) < 16 ) {
			return false;
		}
		$given = (string) $request->get_header( 'X-PDWCHS-Demo-Token' );
		return '' !== $given && hash_equals( PDWCHS_DEMO_TOKEN, $given );
	}

	public static function permission_read( WP_REST_Request $request ) {
		return self::token_ok( $request )
			? true
			: new WP_Error( 'pdwchs_demo_forbidden', 'Invalid or missing X-PDWCHS-Demo-Token (PDWCHS_DEMO_TOKEN must be a 16+ char wp-config constant).', array( 'status' => 403 ) );
	}

	public static function permission_mutate( WP_REST_Request $request ) {
		$read = self::permission_read( $request );
		if ( true !== $read ) {
			return $read;
		}
		return pdwchs_demo_allowed()
			? true
			: new WP_Error( 'pdwchs_demo_blocked', pdwchs_demo_blocked_message(), array( 'status' => 403 ) );
	}

	public static function handle_refresh( WP_REST_Request $request ) {
		$state = PDWCHS_Demo_Runner::start( 'refresh', array(
			'anchor'   => $request->get_param( 'anchor' ),
			'rng_seed' => $request->get_param( 'rng_seed' ),
		) );
		if ( is_wp_error( $state ) ) {
			return $state;
		}
		return new WP_REST_Response( array(
			'run_id'  => $state['run_id'],
			'mode'    => $state['mode'],
			'anchor'  => $state['anchor'],
			'steps'   => count( $state['steps'] ),
			'resumed' => ! empty( $state['resumed'] ),
			'message' => 'Refresh running in background — poll GET /status.',
		), 202 );
	}

	public static function handle_status() {
		$state  = PDWCHS_Demo_Runner::state();
		$ledger = PDWCHS_Demo_Ledger::get();
		$verify = get_option( 'pdwchs_demo_last_verify', null );

		return array(
			'run'    => $state ? array(
				'run_id'   => $state['run_id'],
				'mode'     => $state['mode'],
				'status'   => $state['status'],
				'progress' => $state['next'] . '/' . count( $state['steps'] ),
				'anchor'   => $state['anchor'],
				'error'    => $state['error'],
				'updated'  => gmdate( 'Y-m-d H:i:s', (int) $state['updated_utc'] ) . ' UTC',
				'last_log' => array_slice( $state['log'], -5 ),
			) : null,
			'ledger' => $ledger ? array(
				'anchor'    => $ledger['anchor'],
				'seeded_at' => $ledger['seeded_at'],
				'users'     => count( $ledger['user_map'] ),
				'orders'    => count( PDWCHS_Demo_Teardown::ledger_order_ids() ),
				'sessions'  => count( $ledger['session_ids'] ),
				'carts'     => count( $ledger['cart_ids'] ),
			) : null,
			'last_verify' => $verify ? array(
				'time'      => $verify['time'],
				'counts'    => $verify['counts'],
				'staleness' => $verify['staleness'],
			) : null,
		);
	}

	public static function handle_verify( WP_REST_Request $request ) {
		$verifier = new PDWCHS_Demo_Verifier();
		$result   = $verifier->run();
		update_option( 'pdwchs_demo_last_verify', $result, false );
		if ( $request->get_param( 'report' ) ) {
			$result['report'] = $verifier->report();
		}
		return $result;
	}

	/**
	 * Northline branding pass (theme, product copy, pages, shipping) —
	 * synchronous and idempotent, so no runner/background handling needed.
	 * Replaces the SSH `wp pdwchs-demo setup-storefront` step on boxes
	 * without WP-CLI.
	 */
	public static function handle_setup_storefront() {
		$result = PDWCHS_Demo_Storefront::setup();
		if ( empty( $result['ok'] ) ) {
			return new WP_Error(
				'pdwchs_demo_storefront_failed',
				isset( $result['message'] ) ? $result['message'] : 'Storefront setup failed.',
				array( 'status' => 500, 'log' => $result['log'] )
			);
		}
		return new WP_REST_Response( array( 'ok' => true, 'log' => $result['log'] ), 200 );
	}

	public static function handle_wipe_legacy( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		if ( ! is_array( $body ) || ! isset( $body['confirm'] ) || 'yes-really' !== $body['confirm'] ) {
			return new WP_Error( 'pdwchs_demo_confirm', 'Body must be {"confirm":"yes-really"} — this deletes ALL orders and non-admin users on this site.', array( 'status' => 400 ) );
		}
		$state = PDWCHS_Demo_Runner::start( 'wipe-legacy' );
		if ( is_wp_error( $state ) ) {
			return $state;
		}
		return new WP_REST_Response( array( 'run_id' => $state['run_id'], 'message' => 'Wipe running in background — poll GET /status.' ), 202 );
	}
}
