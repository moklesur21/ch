<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tools → PDWCHS Demo: read-only status page (deliberately NOT under the
 * product plugin's own menu — that sidebar is on camera in shot LF-01).
 * All actions run via WP-CLI or the REST trigger; this page just shows state
 * and the copy-paste commands.
 */
class PDWCHS_Demo_Admin {

	public static function register_page() {
		add_management_page( 'PDWCHS Demo', 'PDWCHS Demo', 'manage_options', 'pdwchs-demo-tools', array( __CLASS__, 'render' ) );
	}

	public static function render() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'default' ) );
		}

		$ledger = PDWCHS_Demo_Ledger::get();
		$state  = PDWCHS_Demo_Runner::state();
		$verify = get_option( 'pdwchs_demo_last_verify', null );

		echo '<div class="wrap"><h1>PDWCHS Demo Tools</h1>';

		echo '<h2>Environment</h2><table class="widefat striped" style="max-width:760px"><tbody>';
		self::row( 'Mutation latch (PDWCHS_DEMO_ALLOW)', pdwchs_demo_allowed() ? '✅ enabled' : '⛔ disabled — seed/refresh/wipe blocked' );
		self::row( 'REST token (PDWCHS_DEMO_TOKEN)', defined( 'PDWCHS_DEMO_TOKEN' ) ? '✅ defined (never shown here)' : '⛔ not defined — REST trigger dead' );
		self::row( 'Pro license', function_exists( 'pdwchs_is_pro_active' ) && pdwchs_is_pro_active() ? '✅ active' : '⛔ inactive' );
		self::row( 'Store / currency', get_option( 'blogname' ) . ' / ' . get_option( 'woocommerce_currency' ) );
		echo '</tbody></table>';

		echo '<h2>Dataset</h2><table class="widefat striped" style="max-width:760px"><tbody>';
		if ( $ledger ) {
			$staleness = max( 0, (int) floor( ( current_time( 'timestamp' ) - strtotime( $ledger['anchor'] . ' 00:00:00 UTC' ) ) / DAY_IN_SECONDS ) );
			self::row( 'Anchor (day zero)', $ledger['anchor'] . ( $staleness ? ' — ⚠ ' . $staleness . ' day(s) stale, refresh before demoing' : ' — fresh today' ) );
			self::row( 'Seeded at', $ledger['seeded_at'] );
			self::row( 'Contents', count( $ledger['user_map'] ) . ' customers · ' . count( PDWCHS_Demo_Teardown::ledger_order_ids() ) . ' orders · ' . count( $ledger['session_ids'] ) . ' session rows · ' . count( $ledger['cart_ids'] ) . ' carts' );
		} else {
			self::row( 'Dataset', 'Not seeded yet' );
		}
		echo '</tbody></table>';

		if ( $state ) {
			echo '<h2>Last run</h2><table class="widefat striped" style="max-width:760px"><tbody>';
			self::row( 'Run', $state['run_id'] . ' [' . $state['mode'] . ']' );
			self::row( 'Status', $state['status'] . ' — step ' . $state['next'] . '/' . count( $state['steps'] ) . ( $state['error'] ? ' — ' . $state['error'] : '' ) );
			$log_lines = array();
			foreach ( array_slice( $state['log'], -6 ) as $entry ) {
				$log_lines[] = $entry[0] . '  [' . $entry[1] . ']  ' . $entry[2];
			}
			self::row( 'Recent log', implode( "\n", $log_lines ), true );
			echo '</tbody></table>';
		}

		if ( $verify ) {
			echo '<h2>Last verify — ' . esc_html( $verify['time'] ) . ' (' . (int) $verify['counts']['pass'] . ' pass / ' . (int) $verify['counts']['fail'] . ' fail / ' . (int) $verify['counts']['warn'] . ' warn)</h2>';
			echo '<table class="widefat striped"><thead><tr><th style="width:70px">Status</th><th>Check</th><th>Actual</th></tr></thead><tbody>';
			foreach ( $verify['checks'] as $c ) {
				$icon = 'pass' === $c['status'] ? '✅' : ( 'warn' === $c['status'] ? '⚠️' : '❌' );
				echo '<tr><td>' . esc_html( $icon . ' ' . strtoupper( $c['status'] ) ) . '</td><td>' . esc_html( $c['label'] ) . '</td><td><code>' . esc_html( $c['actual'] ) . '</code></td></tr>';
			}
			echo '</tbody></table>';
		}

		echo '<h2>Commands</h2>';
		echo '<p>Everything runs via WP-CLI on the box, or the token-authenticated REST trigger from anywhere:</p>';
		echo '<pre style="background:#f6f7f7;padding:12px;max-width:920px;overflow:auto">';
		echo esc_html( "# on the machine (WP-CLI):\nwp pdwchs-demo refresh          # pre-demo: fresh dates, hero back to 51 days\nwp pdwchs-demo verify --report  # acceptance checks + Phase-2 sheet\nwp pdwchs-demo status\n\n# from anywhere (REST):\ncurl -X POST  -H 'X-PDWCHS-Demo-Token: <token>' " . home_url( '/wp-json/pdwchs-demo/v1/refresh' ) . "\ncurl          -H 'X-PDWCHS-Demo-Token: <token>' " . home_url( '/wp-json/pdwchs-demo/v1/status' ) . "\ncurl          -H 'X-PDWCHS-Demo-Token: <token>' '" . home_url( '/wp-json/pdwchs-demo/v1/verify?report=1' ) . "'" );
		echo '</pre>';
		echo '<p>Full procedures: <code>docs/demo/DEMO-STORE-RUNBOOK.md</code> in the plugin repo.</p>';
		echo '</div>';
	}

	private static function row( $label, $value, $pre = false ) {
		echo '<tr><th style="width:280px;text-align:left">' . esc_html( $label ) . '</th><td>' . ( $pre ? '<pre style="margin:0;white-space:pre-wrap">' . esc_html( $value ) . '</pre>' : esc_html( $value ) ) . '</td></tr>';
	}
}
