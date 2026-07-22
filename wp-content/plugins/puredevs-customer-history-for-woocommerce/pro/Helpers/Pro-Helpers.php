<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pro-only static helper utilities.
 *
 * Extracted from PD_Customer_History so the Free coordinator no longer
 * carries the full bodies. Free code delegates to this class via
 * class_exists() guard; when Pro is not active the Free fallback
 * (REMOTE_ADDR / false) is used instead.
 *
 * @since 2.2.0
 */
class PDWCHS_Pro_Helpers {

	/**
	 * Retrieve the client IP address from common server headers.
	 *
	 * Checks HTTP_CLIENT_IP, HTTP_X_FORWARDED_FOR, HTTP_X_FORWARDED,
	 * HTTP_FORWARDED_FOR, HTTP_FORWARDED, REMOTE_ADDR in order.
	 *
	 * @return string Detected IP address or 'UNKNOWN'.
	 */
	public static function pdwchs_get_client_ip() {
		foreach ( array( 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR' ) as $header ) {
			if ( ! empty( $_SERVER[ $header ] ) ) {
				return sanitize_text_field( wp_unslash( $_SERVER[ $header ] ) );
			}
		}
		return 'UNKNOWN';
	}

	/**
	 * Detect if the current request comes from a crawler / bot.
	 *
	 * Matches 'bot', 'crawl', 'slurp', or 'spider' in the User-Agent header
	 * (case-insensitive).
	 *
	 * @return bool True if a bot is detected.
	 */
	public static function pdwchs_woo_bot_detected() {
		if ( isset( $_SERVER['HTTP_USER_AGENT'] ) && preg_match( '/bot|crawl|slurp|spider/i', sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Exclude WooCommerce attribute taxonomies (those containing 'pa_').
	 *
	 * @param string $var Taxonomy slug.
	 * @return string|null
	 */
	public static function pdwchs_filter_taxonomies( $var ) {
		if ( strpos( $var, 'pa_' ) === false ) {
			return $var;
		}
	}

	/**
	 * Return the list of product taxonomies to show in stats/analytics pages.
	 *
	 * Only includes taxonomies that are both public and show_ui=true, which
	 * naturally excludes internal/operational taxonomies (product_type,
	 * product_visibility, pos_product_visibility, etc.) registered by WC or
	 * third-party plugins. The hardcoded exclude list handles edge cases like
	 * product_tag, which is public/show_ui but not useful as a "Most viewed" stat.
	 * pa_* attribute taxonomies are stripped separately.
	 *
	 * @return array Taxonomy slugs.
	 */
	public static function pdwchs_get_product_taxonomies() {
		$exclude    = array( 'product_type', 'product_visibility', 'product_tag', 'product_shipping_class' );
		$taxonomies = get_object_taxonomies( 'product', 'objects' );

		$result = array();
		foreach ( $taxonomies as $slug => $tax ) {
			if ( ! $tax->public || ! $tax->show_ui ) {
				continue;
			}
			if ( strpos( $slug, 'pa_' ) !== false ) {
				continue;
			}
			if ( in_array( $slug, $exclude, true ) ) {
				continue;
			}
			$result[] = $slug;
		}
		return $result;
	}
}
