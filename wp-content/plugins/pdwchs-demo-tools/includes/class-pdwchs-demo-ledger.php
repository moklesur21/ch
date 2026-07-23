<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Persistent record of everything the seeder created, so teardown only ever
 * touches demo data and chunked runs can resume. Single non-autoloaded option.
 */
class PDWCHS_Demo_Ledger {

	const OPTION = 'pdwchs_demo_ledger';

	public static function get() {
		$data = get_option( self::OPTION, null );
		return is_array( $data ) ? $data : null;
	}

	public static function exists() {
		return null !== self::get();
	}

	public static function init( $anchor, $rng_seed ) {
		$data = array(
			'anchor'          => $anchor,
			'rng_seed'        => $rng_seed,
			'seeded_at'       => current_time( 'mysql' ),
			'user_map'        => array(), // plan key => user_id
			'order_map'       => array(), // plan key => [order_idx => order_id]
			'guest_order_ids' => array(), // guest spec idx => order_id
			'refund_map'      => array(), // plan refund idx => refund_id (deletion cascades from the parent order)
			'product_ids'     => array(), // products the seeder itself created
			'session_ids'     => array(),
			'cart_ids'        => array(),
			'temp_order_ids'  => array(),
			'flags'           => array(), // step-done flags (sessions, carts, fixups…)
		);
		update_option( self::OPTION, $data, false );
		return $data;
	}

	public static function save( $data ) {
		update_option( self::OPTION, $data, false );
	}

	public static function set( $field, $value ) {
		$data = self::get();
		if ( null === $data ) {
			return;
		}
		$data[ $field ] = $value;
		self::save( $data );
	}

	public static function flag( $name, $value = true ) {
		$data = self::get();
		if ( null === $data ) {
			return;
		}
		$data['flags'][ $name ] = $value;
		self::save( $data );
	}

	public static function has_flag( $name ) {
		$data = self::get();
		return $data && ! empty( $data['flags'][ $name ] );
	}

	public static function delete() {
		delete_option( self::OPTION );
	}

	/** user_id of the hero (null before the users step ran). */
	public static function hero_user_id() {
		$data = self::get();
		return $data && isset( $data['user_map']['hero'] ) ? (int) $data['user_map']['hero'] : null;
	}
}
