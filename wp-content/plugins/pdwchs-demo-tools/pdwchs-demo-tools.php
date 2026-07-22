<?php
/**
 * Plugin Name: PDWCHS Demo Tools
 * Description: Internal ops tooling for the PureDevs Customer History demo stores. Deterministic anchored seeder, recalc runner, acceptance verifier, REST refresh trigger. NEVER install on a production customer site.
 * Version: 0.1.0
 * Author: PureDevs (internal)
 * Requires Plugins: woocommerce
 *
 * This plugin lives in the product repo under docs/demo/ (build-excluded via
 * .distignore) and is deployed only to demo sites. Mutating commands are dead
 * unless wp-config.php defines:
 *
 *   define( 'PDWCHS_DEMO_ALLOW', true );          // enables seed/teardown/wipe
 *   define( 'PDWCHS_DEMO_TOKEN', '<random-hex>' ); // enables the REST trigger
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PDWCHS_DEMO_TOOLS_VERSION', '0.1.0' );
define( 'PDWCHS_DEMO_TOOLS_DIR', plugin_dir_path( __FILE__ ) );
define( 'PDWCHS_DEMO_TOOLS_DATA_DIR', PDWCHS_DEMO_TOOLS_DIR . 'data/' );

/** True when destructive/mutating demo commands are allowed on this site. */
function pdwchs_demo_allowed() {
	return defined( 'PDWCHS_DEMO_ALLOW' ) && PDWCHS_DEMO_ALLOW;
}

/** Human-readable reason used everywhere a mutating entry point refuses. */
function pdwchs_demo_blocked_message() {
	return 'Blocked: define PDWCHS_DEMO_ALLOW as true in wp-config.php on this DEMO site first.';
}

require_once PDWCHS_DEMO_TOOLS_DIR . 'includes/class-pdwchs-demo-plan.php';
require_once PDWCHS_DEMO_TOOLS_DIR . 'includes/class-pdwchs-demo-ledger.php';
require_once PDWCHS_DEMO_TOOLS_DIR . 'includes/class-pdwchs-demo-seeder.php';
require_once PDWCHS_DEMO_TOOLS_DIR . 'includes/class-pdwchs-demo-recalc.php';
require_once PDWCHS_DEMO_TOOLS_DIR . 'includes/class-pdwchs-demo-teardown.php';
require_once PDWCHS_DEMO_TOOLS_DIR . 'includes/class-pdwchs-demo-verifier.php';
require_once PDWCHS_DEMO_TOOLS_DIR . 'includes/class-pdwchs-demo-runner.php';
require_once PDWCHS_DEMO_TOOLS_DIR . 'includes/class-pdwchs-demo-rest.php';
require_once PDWCHS_DEMO_TOOLS_DIR . 'includes/class-pdwchs-demo-admin.php';

add_action( 'rest_api_init', array( 'PDWCHS_Demo_REST', 'register_routes' ) );
add_action( 'pdwchs_demo_run_tick', array( 'PDWCHS_Demo_Runner', 'tick' ) );
add_action( 'admin_menu', array( 'PDWCHS_Demo_Admin', 'register_page' ) );

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	require_once PDWCHS_DEMO_TOOLS_DIR . 'includes/class-pdwchs-demo-cli.php';
	WP_CLI::add_command( 'pdwchs-demo', 'PDWCHS_Demo_CLI' );
}
