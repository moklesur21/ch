<?php
/**
 * Pro loader.
 *
 * This file (and the whole pro/ directory) is present ONLY in the Pro build.
 * The free build shipped to WordPress.org has pro/ stripped by build.sh, so the
 * main plugin file's file_exists() check skips this loader and no premium code
 * is shipped to .org (keeps the repo listing compliant).
 *
 * @package PDWCHS_Pro
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PDWCHS_PRO_VERSION', defined( 'PDWCHS_CUSTOMER_HISTORY_VERSION' ) ? PDWCHS_CUSTOMER_HISTORY_VERSION : '2.2.0' );
define( 'PDWCHS_PRO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ); // .../pro/
define( 'PDWCHS_PRO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );  // .../pro/

// The license/trial gate filter + trial notice must register regardless of
// license state (so the gate can answer false and the upgrade notice can show).
require_once PDWCHS_PRO_PLUGIN_DIR . 'Trial-Gate.php';

/**
 * Boot Pro at plugins_loaded@11 — BEFORE the Free core fires pdwchs_pro_init in
 * its own plugins_loaded@12 bootstrap — so PDWCHS_Pro's listener is registered
 * in time. Boots only when the license/trial gate (Trial-Gate.php) passes.
 *
 * On a clean install the Pro tables are created here by the on-load healer the
 * first time the gate passes; they are also created via the Free activator's
 * pdwchs_register_pro_tables/_options seams, which the Pro feature classes
 * self-register on once booted.
 *
 * @return void
 */
function pdwchs_pro_bootstrap() {
	if ( ! function_exists( 'pdwchs_is_pro_active' ) || ! pdwchs_is_pro_active() ) {
		return;
	}
	require_once PDWCHS_PRO_PLUGIN_DIR . 'Pro-Bootstrap.php';
	new PDWCHS_Pro();

	// On-load healer for Pro tables (mirrors Free's version-upgrade pattern).
	require_once PDWCHS_PRO_PLUGIN_DIR . 'Pro-Installer.php';
	PDWCHS_Pro_Installer::pdwchs_check_version_upgrade();
}
add_action( 'plugins_loaded', 'pdwchs_pro_bootstrap', 11 );
