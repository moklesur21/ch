<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Funnel-Data.php';
require_once __DIR__ . '/Funnel-Session-List-Table.php';

/**
 * Funnel AJAX Coordinator
 *
 * Wires the aggregate conversion funnel AJAX handlers and admin menu/enqueue
 * hooks via seams H3 (pdwchs_admin_menu_late), H4 (pdwchs_enqueue_admin_styles),
 * and H5 (pdwchs_enqueue_admin_scripts).
 *
 * Delegates data retrieval to PDWCHS_Funnel_Data.
 *
 * @since 2.2.0
 */
class PDWCHS_Funnel_Ajax {

	/**
	 * Funnel data query instance.
	 *
	 * @since  2.2.0
	 * @access private
	 * @var    PDWCHS_Funnel_Data $funnel_data
	 */
	private $funnel_data;

	/**
	 * Constructor. Instantiates dependency and registers hooks.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		$this->funnel_data = new PDWCHS_Funnel_Data();
		$this->pdwchs_register_hooks();
	}

	/**
	 * Register all hooks: AJAX handlers (wp_ajax_*), menu (H3), styles (H4), scripts (H5).
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_register_hooks() {
		add_action( 'wp_ajax_pdwchs_get_aggregate_funnel', array( $this, 'pdwchs_ajax_get_aggregate_funnel' ) );
		add_action( 'wp_ajax_pdwchs_get_funnel_over_time', array( $this, 'pdwchs_ajax_get_funnel_over_time' ) );

		add_action( 'pdwchs_admin_menu_late',    array( $this, 'pdwchs_register_menu' ) );    // H3
		add_action( 'pdwchs_enqueue_admin_styles',  array( $this, 'pdwchs_enqueue_styles' ) );  // H4
		add_action( 'pdwchs_enqueue_admin_scripts', array( $this, 'pdwchs_enqueue_scripts' ) ); // H5
	}

	/**
	 * H3 — Register the hidden Conversion Funnel submenu.
	 *
	 * @param string $capability Capability string from pdwchs_admin_menu_late.
	 */
	public function pdwchs_register_menu( $capability ) {
		add_submenu_page(
			'',
			__( 'Conversion Funnel', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Conversion Funnel', 'puredevs-customer-history-for-woocommerce' ),
			'manage_woocommerce',
			'pd-woo-conversion-funnel-stats',
			array( $this, 'pdwchs_funnel_page' )
		);
	}

	/**
	 * Page callback — include the aggregate funnel template.
	 */
	public function pdwchs_funnel_page() {
		require __DIR__ . '/templates/aggregate-funnel.php';
	}

	/**
	 * H4 — Enqueue funnel CSS on the conversion funnel admin page.
	 *
	 * @param string $version Plugin version string passed by H4 action.
	 */
	public function pdwchs_enqueue_styles( $version ) {
		if ( ! isset( $_GET['page'] ) || 'pd-woo-conversion-funnel-stats' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$admin_css_url = PDWCHS_PRO_PLUGIN_URL . 'admin/css/';
		wp_enqueue_style(
			'pdwchs-funnel-css',
			$admin_css_url . 'pd-customer-history-funnel.css',
			array( 'custom-style' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION
		);
	}

	/**
	 * H5 — Enqueue funnel JS on the conversion funnel admin page.
	 *
	 * @param string $version Plugin version string passed by H5 action.
	 */
	public function pdwchs_enqueue_scripts( $version ) {
		if ( ! isset( $_GET['page'] ) || 'pd-woo-conversion-funnel-stats' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$admin_js_url = PDWCHS_PRO_PLUGIN_URL . 'admin/js/';
		wp_enqueue_script( 'chartjs', PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL . 'admin/js/chart.umd.js', array(), '4.4.1', true );
		wp_enqueue_script(
			'pdwchs-aggregate-funnel-js',
			$admin_js_url . 'pdwchs-aggregate-funnel.js',
			array( 'jquery', 'chartjs', 'moment', 'date-picker' ),
			time(),
			true
		);
		wp_localize_script( 'pdwchs-aggregate-funnel-js', 'pdwchs_funnel_vars', array(
			'ajaxurl'            => admin_url( 'admin-ajax.php' ),
			'nonce'              => wp_create_nonce( 'pdwchs_funnel_nonce' ),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'decimal_separator'  => wc_get_price_decimal_separator(),
			'i18n'               => array(
				'loading_text'     => __( 'Loading…', 'puredevs-customer-history-for-woocommerce' ),
				'error_text'       => __( 'Error loading data. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
				'no_data_text'     => __( 'No funnel data for this period.', 'puredevs-customer-history-for-woocommerce' ),
				'visitors_label'   => __( 'Visitors', 'puredevs-customer-history-for-woocommerce' ),
				'cart_label'       => __( 'Add to Cart', 'puredevs-customer-history-for-woocommerce' ),
				'checkout_label'   => __( 'Checkout Started', 'puredevs-customer-history-for-woocommerce' ),
				'purchased_label'  => __( 'Purchased', 'puredevs-customer-history-for-woocommerce' ),
				'conversion_label' => __( 'Overall Conversion', 'puredevs-customer-history-for-woocommerce' ),
				'trend_title'      => __( 'Funnel Over Time', 'puredevs-customer-history-for-woocommerce' ),
			),
		) );
	}

	/**
	 * AJAX: aggregate funnel snapshot for a date range.
	 *
	 * POST params: nonce, start_date (Y-m-d), end_date (Y-m-d)
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_ajax_get_aggregate_funnel() {
		check_ajax_referer( 'pdwchs_funnel_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ), 403 );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date   = isset( $_POST['end_date'] )   ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) )   : '';

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date )
		  || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		$result = $this->funnel_data->pdwchs_get_aggregate_funnel( $start_date, $end_date );

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: funnel counts broken down over time.
	 *
	 * POST params: nonce, start_date (Y-m-d), end_date (Y-m-d),
	 *              granularity ('day'|'week'|'month')
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_ajax_get_funnel_over_time() {
		check_ajax_referer( 'pdwchs_funnel_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ), 403 );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date   = isset( $_POST['end_date'] )   ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) )   : '';

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date )
		  || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		$granularity = isset( $_POST['granularity'] ) ? sanitize_text_field( wp_unslash( $_POST['granularity'] ) ) : '';
		if ( empty( $granularity ) ) {
			$granularity = 'day';
		}
		if ( ! in_array( $granularity, array( 'day', 'week', 'month' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid granularity.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		$result = $this->funnel_data->pdwchs_get_funnel_over_time( $start_date, $end_date, $granularity );

		wp_send_json_success( array( 'series' => $result ) );
	}
}
