<?php
/**
 * TEMPORARY dev-only override used while capturing marketing screenshots.
 * Forces the PureDevs Customer History tier from the `pdwchs_capture_tier`
 * option ('pro' | 'free'). Delete this file when capture is finished.
 *
 * Two layers, because the plugin gates in two places:
 *  1. the `pdwchs_is_pro_active` filter (pro/Trial-Gate.php) — answered below at priority 99;
 *  2. a handful of Pro templates that call pchfwv_fs()->can_use_premium_code() directly —
 *     answered by swapping the global Freemius instance for a proxy that forwards every other
 *     call to the real instance.
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
function pdwchs_capture_tier() {
	return get_option( 'pdwchs_capture_tier', '' );
}
add_filter( 'pdwchs_is_pro_active', function ( $active ) {
	$tier = pdwchs_capture_tier();
	if ( 'pro' === $tier ) { return true; }
	if ( 'free' === $tier ) { return false; }
	return $active;
}, 99 );

class PDWCHS_Capture_FS_Proxy {
	private $fs;
	public function __construct( $fs ) { $this->fs = $fs; }
	public function can_use_premium_code() {
		$tier = pdwchs_capture_tier();
		if ( 'pro' === $tier ) { return true; }
		if ( 'free' === $tier ) { return false; }
		return $this->fs->can_use_premium_code();
	}
	public function __call( $name, $args ) { return call_user_func_array( array( $this->fs, $name ), $args ); }
	public function __get( $name ) { return $this->fs->$name; }
	public function __set( $name, $value ) { $this->fs->$name = $value; }
	public function __isset( $name ) { return isset( $this->fs->$name ); }
}
// The main plugin file creates the real instance and then fires pchfwv_fs_loaded; pchfwv_fs()
// returns the global on every later call, so swapping the global for the proxy here is enough.
add_action( 'pchfwv_fs_loaded', function () {
	if ( isset( $GLOBALS['pchfwv_fs'] ) && ! ( $GLOBALS['pchfwv_fs'] instanceof PDWCHS_Capture_FS_Proxy ) ) {
		$GLOBALS['pchfwv_fs'] = new PDWCHS_Capture_FS_Proxy( $GLOBALS['pchfwv_fs'] );
	}
}, 0 );
