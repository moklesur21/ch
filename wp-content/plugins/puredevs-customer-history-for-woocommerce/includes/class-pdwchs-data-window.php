<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free analytics read window. Fixed product-tier scope (wp.org Guideline 5
 * compliant): applies from first activation, never degrades with usage.
 * Data COLLECTION is never windowed — only read queries clamp.
 * The Pro add-on returns 0 (unlimited) via the same filter.
 */
class PDWCHS_Data_Window {

	/** Set true whenever a clamp actually changed a requested start date. */
	private static $clamped = false;

	public static function pdwchs_days() {
		return (int) apply_filters( 'pdwchs_data_window_days', 90 );
	}

	/** Earliest readable Y-m-d, or '' when unlimited. */
	public static function pdwchs_min_start() {
		$days = self::pdwchs_days();
		return ( $days > 0 ) ? gmdate( 'Y-m-d', strtotime( '-' . $days . ' days' ) ) : '';
	}

	/** Clamp a requested start date into the window; records whether it bit. */
	public static function pdwchs_clamp_start( $start_date ) {
		$min = self::pdwchs_min_start();
		if ( '' === $min ) {
			return $start_date;
		}
		if ( empty( $start_date ) || strtotime( $start_date ) < strtotime( $min ) ) {
			self::$clamped = ! empty( $start_date );
			return $min;
		}
		return $start_date;
	}

	public static function pdwchs_was_clamped() {
		return self::$clamped;
	}
}
