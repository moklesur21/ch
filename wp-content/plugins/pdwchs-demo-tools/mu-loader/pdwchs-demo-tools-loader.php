<?php
/**
 * Optional mu-plugin for the LIVE demo box: copy this single file into
 * wp-content/mu-plugins/ so the demo tools stay loaded (and the REST refresh
 * trigger stays reachable) even if someone deactivates the plugin in wp-admin.
 * Expects the demo-tools plugin folder at wp-content/plugins/pdwchs-demo-tools/.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pdwchs_demo_tools_main = WP_PLUGIN_DIR . '/pdwchs-demo-tools/pdwchs-demo-tools.php';
if ( file_exists( $pdwchs_demo_tools_main ) && ! defined( 'PDWCHS_DEMO_TOOLS_VERSION' ) ) {
	require_once $pdwchs_demo_tools_main;
}
unset( $pdwchs_demo_tools_main );
