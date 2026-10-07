<?php
/**
 * V8 pre-install check — run by `scripts/v8-site.sh check` through
 * `wp eval-file` on ch-v8. Prints one line per check and a verdict; any FAIL
 * means "do not record the install take" (and exits non-zero).
 *
 * Inputs come from the environment: CH_FREE (ch's free build — the one the
 * Demo Brief was written against), SLUG, SNAPSHOT, SHOT_EMAIL, PLUGIN_DIR, HTACCESS.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$v8_env = function ( $k ) {
	$v = getenv( $k );
	return false === $v ? '' : $v;
};

$v8_results = array();
$v8_check   = function ( $id, $label, $ok, $actual, $level = 'fail' ) use ( &$v8_results ) {
	$v8_results[] = array( $ok ? 'PASS' : strtoupper( $level ), $id, $label, $actual );
};

global $wpdb, $wp_version;
$slug = $v8_env( 'SLUG' );

// ── environment ─────────────────────────────────────────────────────────
// The video always runs the latest WordPress, whatever the plugin readme's
// "Tested up to" says (owner rule, 7 Oct 2026). Plugin and theme updates are
// covered by cam_updates below.
wp_version_check();
$core_upd    = get_site_transient( 'update_core' );
$core_latest = ( $core_upd && ! empty( $core_upd->updates ) ) ? $core_upd->updates[0]->current : '';
$v8_check( 'env_wp', 'WordPress is the latest release', '' !== $core_latest && version_compare( $wp_version, $core_latest, '>=' ), $wp_version . ' (latest ' . ( $core_latest ? $core_latest : 'unknown' ) . ')' );
$wc_ver = defined( 'WC_VERSION' ) ? WC_VERSION : 'not active';
$v8_check( 'env_wc_active', 'WooCommerce active', defined( 'WC_VERSION' ), $wc_ver );
$active = (array) get_option( 'active_plugins', array() );
$v8_check( 'env_plugins', 'Only WooCommerce is active', array( 'woocommerce/woocommerce.php' ) === array_values( $active ), implode( ', ', $active ) );
$v8_check( 'env_theme', 'Northline theme active', 'northline' === get_stylesheet(), get_stylesheet() );
$v8_check( 'env_local', 'WP_ENVIRONMENT_TYPE is local', 'local' === wp_get_environment_type(), wp_get_environment_type() );
$v8_check( 'env_latch', 'Demo-tools latch removed (PDWCHS_DEMO_ALLOW undefined)', ! defined( 'PDWCHS_DEMO_ALLOW' ), defined( 'PDWCHS_DEMO_ALLOW' ) ? 'defined' : 'undefined' );
$shot = get_user_by( 'email', $v8_env( 'SHOT_EMAIL' ) );
$v8_check( 'env_shot', 'Shot user exists, administrator, "Sarah M."', $shot && in_array( 'administrator', (array) $shot->roles, true ) && 'Sarah M.' === $shot->display_name, $shot ? $shot->user_login . ' / ' . $shot->display_name : 'missing' );
$v8_check( 'env_autologin', 'Auto-login mu-plugin present', file_exists( WPMU_PLUGIN_DIR . '/auto_login_local.php' ), WPMU_PLUGIN_DIR );
$htaccess = (string) @file_get_contents( $v8_env( 'HTACCESS' ) ); // phpcs:ignore
$v8_check( 'env_permalinks', 'Pretty permalinks + .htaccess RewriteBase /ch-v8/', '/%postname%/' === get_option( 'permalink_structure' ) && false !== strpos( $htaccess, 'RewriteBase /ch-v8/' ), get_option( 'permalink_structure' ) );
$v8_check( 'env_coming_soon', 'Store is live (not "coming soon")', 'no' === get_option( 'woocommerce_coming_soon' ), (string) get_option( 'woocommerce_coming_soon' ) );

// ── pre-install state: the PureDevs plugin has never been here ──────────
$dir = rtrim( $v8_env( 'PLUGIN_DIR' ), '/' );
$v8_check( 'pre_files', 'No PureDevs plugin folder', ! is_dir( "$dir/$slug" ) && ! is_dir( "$dir/$slug-pro" ), is_dir( "$dir/$slug" ) ? "$slug present — run reset" : 'absent' );
$opts = $wpdb->get_col(
	"SELECT option_name FROM {$wpdb->options}
	 WHERE option_name LIKE 'fs\\_%' OR option_name LIKE 'pdwchs%' OR option_name LIKE 'pd-woo-%'
	    OR option_name LIKE '\\_transient%pdwchs%' OR option_name LIKE '\\_site\\_transient%fs\\_%'"
);
$v8_check( 'pre_options', 'No Freemius or plugin options', ! $opts, $opts ? implode( ', ', array_slice( $opts, 0, 6 ) ) . ( count( $opts ) > 6 ? ' …' : '' ) : 'none' );
$tables = array_merge(
	$wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', '%pd\_woo%' ) ),
	$wpdb->get_col( $wpdb->prepare( 'SHOW TABLES LIKE %s', '%pdwchs%' ) )
);
$v8_check( 'pre_tables', 'No plugin tables', ! $tables, $tables ? implode( ', ', $tables ) : 'none' );
$meta = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key LIKE 'pdwchs%'" );
$v8_check( 'pre_usermeta', 'No plugin user meta', 0 === $meta, (string) $meta );
$v8_check( 'pre_snapshot', 'Pre-install snapshot exists (for reset)', file_exists( $v8_env( 'SNAPSHOT' ) ), $v8_env( 'SNAPSHOT' ) );

// ── store data: the first Dashboard after install has orders to read ────
$products = (int) wp_count_posts( 'product' )->publish;
$v8_check( 'data_products', 'Catalog published (>= 10 products)', $products >= 10, (string) $products );
$all    = wc_get_orders( array( 'limit' => -1, 'return' => 'ids', 'type' => 'shop_order', 'status' => array( 'wc-completed', 'wc-processing' ) ) );
$recent = wc_get_orders( array( 'limit' => -1, 'return' => 'ids', 'type' => 'shop_order', 'status' => array( 'wc-completed', 'wc-processing' ), 'date_created' => '>=' . gmdate( 'Y-m-d', strtotime( '-30 days' ) ) ) );
$v8_check( 'data_orders', 'Completed/processing orders, some in the last 30 days', count( $all ) > 0 && count( $recent ) > 0, count( $all ) . ' total, ' . count( $recent ) . ' in 30 days' );

// ── nothing on screen that is not the subject ───────────────────────────
wp_update_plugins();
wp_update_themes();
$core_new = false;
if ( $core_upd && ! empty( $core_upd->updates ) ) {
	foreach ( $core_upd->updates as $u ) {
		if ( 'upgrade' === $u->response ) {
			$core_new = $u->current;
		}
	}
}
$plug_upd  = get_site_transient( 'update_plugins' );
$theme_upd = get_site_transient( 'update_themes' );
$pending   = array_merge(
	array_keys( (array) ( $plug_upd->response ?? array() ) ),
	array_keys( (array) ( $theme_upd->response ?? array() ) )
);
$v8_check( 'cam_updates', 'No update bubbles (core, plugins, themes)', ! $core_new && ! $pending, $core_new ? "core $core_new available" : ( $pending ? implode( ', ', $pending ) : 'none' ) );
$notices = (array) get_option( 'woocommerce_admin_notices', array() );
$v8_check( 'cam_wc_notices', 'No queued WooCommerce admin notices', ! $notices, $notices ? implode( ', ', $notices ) : 'none', 'warn' );

// ── WordPress.org: what Install Now will fetch, and the card it shows ───
$resp = wp_remote_get( 'https://api.wordpress.org/plugins/info/1.2/?action=plugin_information&request[slug]=' . rawurlencode( $slug ), array( 'timeout' => 20 ) );
$code = (int) wp_remote_retrieve_response_code( $resp );
$info = json_decode( (string) wp_remote_retrieve_body( $resp ), true );
$v8_check( 'org_reach', 'This PHP reaches api.wordpress.org (Install Now needs it)', 200 === $code, is_wp_error( $resp ) ? $resp->get_error_message() : "HTTP $code" );
if ( is_array( $info ) && ! empty( $info['version'] ) ) {
	$v8_check( 'org_version', 'wp.org serves the version ch runs (the brief was written against it)', $info['version'] === $v8_env( 'CH_FREE' ), 'wp.org ' . $info['version'] . ' / ch ' . $v8_env( 'CH_FREE' ), 'warn' );
	// WordPress's own rule (wp-admin/includes/class-wp-plugin-install-list-table.php).
	$tested_ok = empty( $info['tested'] ) || version_compare( $wp_version, $info['tested'], '<=' );
	// Owner decision, 7 Oct 2026: the readme's "Tested up to" does not block the take (the site always
	// runs the latest WordPress; the edit zooms on Install Now). It stays a warning, and dev request 1 stays open.
	$v8_check( 'org_tested', 'Install card reads "Compatible", not "Untested with your version of WordPress"', $tested_ok, 'tested ' . ( $info['tested'] ?? '?' ) . ' vs site ' . $wp_version . ( $tested_ok ? '' : ' — dev request 1 (not blocking: owner decision 7 Oct)' ), 'warn' );
}

// ── print ───────────────────────────────────────────────────────────────
$fails = 0;
$warns = 0;
foreach ( $v8_results as $r ) {
	if ( 'FAIL' === $r[0] ) {
		$fails++;
	} elseif ( 'WARN' === $r[0] ) {
		$warns++;
	}
	printf( "%-4s  %-16s %-70s %s\n", $r[0], $r[1], $r[2], $r[3] );
}
if ( $fails ) {
	WP_CLI::error( "$fails FAIL / $warns warn — do not record the install take." );
}
WP_CLI::success( "0 FAIL / $warns warn — pre-install state, record-ready." );
