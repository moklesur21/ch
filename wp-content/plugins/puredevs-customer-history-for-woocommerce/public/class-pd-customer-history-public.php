<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The public-facing functionality of the plugin.
 *
 * @link       https://puredevs.com/
 * @since      1.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/public
 */

/**
 * The public-facing functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the public-facing stylesheet and JavaScript.
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/public
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Public {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Initialize user session for inserting into database table
	 *
	 * @since    1.0.0
	 */
	public function pdwchs_woo_init_session() {
		if ( ! current_user_can( 'manage_woocommerce' ) || get_option( 'pd-woo-default_save_admin_session' ) ) {
			if ( ! empty( $_GET['s'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			    $search_key = sanitize_text_field( wp_unslash( $_GET['s'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$url = 'ACTION::search::' . $search_key;
				PDWCHS_Sessions_Data::pdwchs_insert( is_user_logged_in() ? get_current_user_id() : 0, $url );
			}
			add_action( 'wp_footer', array( $this, 'pdwchs_woo_session_insert' ) );
			add_action( 'woocommerce_add_to_cart', array( $this, 'pdwchs_woo_action_add_to_cart' ), 10, 3 );
			add_action( 'woocommerce_new_order', array( $this, 'pdwchs_woo_action_new_order' ), 10, 1 );
		}
	}

	/**
	 * Insert session URL
	 *
	 * @since    1.0.0
	 */
	function pdwchs_woo_session_insert() {
		if ( ! is_404() ) {
			global $wp;
			$url = add_query_arg( array(), $wp->request );
			if ( PD_Customer_History::pdwchs_woo_bot_detected() ) {
				$url = 'BOT::' . sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ?? '' ) ) . '::' . $url; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated
				PDWCHS_Sessions_Data::pdwchs_insert( 999999999999999, $url );
			} else {
				PDWCHS_Sessions_Data::pdwchs_insert( is_user_logged_in() ? get_current_user_id() : 0, $url );
			}
		}
	}

	/**
	 * Insert add_to_cart action
	 *
	 * @since    1.0.0
	 */
	function pdwchs_woo_action_add_to_cart( $array, $product_id, $quantity ) {
		$url = 'ACTION::add_to_cart::' . $product_id . '::' . $quantity;
		PDWCHS_Sessions_Data::pdwchs_insert( is_user_logged_in() ? get_current_user_id() : 0, $url );
	}

	/**
	 * Insert new_order action
	 *
	 * @since    1.0.0
	 */
	function pdwchs_woo_action_new_order( $order_id ) {
		$url = 'ACTION::new_order::' . $order_id;
		PDWCHS_Sessions_Data::pdwchs_insert( is_user_logged_in() ? get_current_user_id() : 0, $url );
	}

	public function pdwchs_enqueue_cart_email_capture_scripts() {
		if ( ! is_checkout() ) {
			return;
		}
		wp_enqueue_script(
			'pdwchs-email-capture',
			plugin_dir_url( __FILE__ ) . 'js/pdwchs-email-capture.js',
			[ 'jquery' ],
			PDWCHS_CUSTOMER_HISTORY_VERSION,
			true
		);
		wp_localize_script(
			'pdwchs-email-capture',
			'pdwchsCartCapture',
			[
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'pdwchs_cart_email_capture' ),
				'enabled' => (bool) get_option( 'pdwchs_cart_email_capture_enabled', 1 ),
			]
		);
	}

}
