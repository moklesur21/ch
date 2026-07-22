<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PDWCHS_Cart_Email_Capture {

	public function __construct() {
		$this->pdwchs_register_hooks();
		add_action( 'pdwchs_public_init', [ $this, 'pdwchs_on_public_init' ], 10, 2 );
	}

	public function pdwchs_on_public_init( $plugin_public, $loader ) {
		$loader->add_action( 'wp_enqueue_scripts', $plugin_public, 'pdwchs_enqueue_cart_email_capture_scripts' );
	}

	public function pdwchs_register_hooks() {
		add_action( 'wp_ajax_pdwchs_capture_cart_email',        [ $this, 'pdwchs_ajax_capture_cart_email' ] );
		add_action( 'wp_ajax_nopriv_pdwchs_capture_cart_email', [ $this, 'pdwchs_ajax_capture_cart_email' ] );
	}

	public function pdwchs_ajax_capture_cart_email() {
		if ( ! get_option( PDWCHS_Cart_Tracker::CART_TRACKING_OPTION, 1 ) ) {
			wp_send_json_error( [ 'code' => 'tracking_disabled' ] );
		}

		if ( ! get_option( 'pdwchs_cart_email_capture_enabled', 1 ) ) {
			wp_send_json_error( [ 'code' => 'email_capture_disabled' ] );
		}

		check_ajax_referer( 'pdwchs_cart_email_capture', 'nonce' );

		$email = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
		if ( ! is_email( $email ) ) {
			wp_send_json_error( [ 'code' => 'invalid_email' ] );
		}

		$row_id = $this->pdwchs_get_cart_row_id();
		if ( null === $row_id ) {
			wp_send_json_error( [ 'code' => 'no_cart_row' ] );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_carts';
		$wpdb->update( $table, [ 'email' => $email ], [ 'id' => $row_id ], [ '%s' ], [ '%d' ] ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		wp_send_json_success();
	}

	private function pdwchs_get_cart_row_id(): ?int {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_carts';

		if ( is_user_logged_in() ) {
			$row = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT id FROM {$table} WHERE user_id = %d AND status != 'completed' ORDER BY created_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				get_current_user_id()
			) );
		} else {
			$row = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT id FROM {$table} WHERE session_key = %s AND user_id IS NULL AND status != 'completed' ORDER BY created_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				WC()->session->get_customer_id()
			) );
		}

		return $row ? (int) $row->id : null;
	}

}
