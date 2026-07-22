<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Product Reports AJAX Coordinator
 *
 * Registers AJAX handlers for all Feature 1.4 product report pages and
 * delegates data retrieval to the relevant data classes.
 * R1-26 adds the first endpoint; R1-27/R1-28 will extend this class.
 *
 * @since      2.2.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes/analytics
 */
class PDWCHS_Product_Reports_Ajax {

	/**
	 * Product revenue data query instance.
	 *
	 * @since  2.2.0
	 * @access private
	 * @var    PDWCHS_Product_Revenue_Data $product_revenue_data
	 */
	private $product_revenue_data;

	/**
	 * Product correlations data query instance.
	 *
	 * @since  2.2.0
	 * @access private
	 * @var    PDWCHS_Product_Correlations_Data $product_correlations_data
	 */
	private $product_correlations_data;

	/**
	 * Revenue patterns data query instance.
	 *
	 * @since  2.2.0
	 * @access private
	 * @var    PDWCHS_Revenue_Patterns_Data $revenue_patterns_data
	 */
	private $revenue_patterns_data;

	/**
	 * Constructor. Instantiates dependencies and registers hooks.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		$this->product_revenue_data      = new PDWCHS_Product_Revenue_Data();
		$this->product_correlations_data = new PDWCHS_Product_Correlations_Data();
		$this->revenue_patterns_data     = new PDWCHS_Revenue_Patterns_Data();
		$this->pdwchs_register_hooks();
		add_action( 'pdwchs_admin_menu_late',      array( $this, 'pdwchs_register_menus' ) );
		add_action( 'pdwchs_enqueue_admin_styles',  array( $this, 'pdwchs_enqueue_styles' ) );
		add_action( 'pdwchs_enqueue_admin_scripts', array( $this, 'pdwchs_enqueue_scripts' ) );
	}

	/**
	 * Register wp_ajax_ hooks for Feature 1.4 endpoints.
	 * No nopriv variants — admin-only.
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_register_hooks() {
		add_action( 'wp_ajax_pdwchs_get_product_revenue_over_time',
		            array( $this, 'pdwchs_ajax_get_product_revenue_over_time' ) );
		add_action( 'wp_ajax_pdwchs_get_product_correlations',
		            array( $this, 'pdwchs_ajax_get_product_correlations' ) );
		add_action( 'wp_ajax_pdwchs_get_revenue_patterns',
		            array( $this, 'pdwchs_ajax_get_revenue_patterns' ) );
	}

	/**
	 * AJAX: product revenue over time (multi-product time-series + summary).
	 *
	 * POST params: nonce, product_ids[] (1–3 IDs), start_date (Y-m-d),
	 *              end_date (Y-m-d), granularity ('day'|'week'|'month')
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_ajax_get_product_revenue_over_time() {
		check_ajax_referer( 'pdwchs_product_reports_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		// Product IDs — sanitize each element, cast to int, filter zeros, cap at 3.
		$raw_ids     = isset( $_POST['product_ids'] )
			? array_map( 'sanitize_text_field', (array) wp_unslash( $_POST['product_ids'] ) )
			: array();
		$product_ids = array_slice( array_filter( array_map( 'absint', $raw_ids ) ), 0, 3 );

		if ( empty( $product_ids ) ) {
			wp_send_json_error( array( 'message' => __( 'No products selected.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		// Dates — sanitize then strict regex.
		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date   = isset( $_POST['end_date'] )   ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) )   : '';

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date )
		  || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		// Granularity — allowlist.
		$granularity = isset( $_POST['granularity'] ) ? sanitize_text_field( wp_unslash( $_POST['granularity'] ) ) : 'day';
		if ( ! in_array( $granularity, array( 'day', 'week', 'month' ), true ) ) {
			$granularity = 'day';
		}

		$over_time = $this->product_revenue_data->pdwchs_get_product_revenue_over_time(
			$product_ids, $start_date, $end_date, $granularity
		);
		$summary = $this->product_revenue_data->pdwchs_get_product_summary(
			$product_ids, $start_date, $end_date
		);

		wp_send_json_success( array(
			'over_time' => $over_time,
			'summary'   => $summary,
		) );
	}

	/**
	 * AJAX: frequently bought together (co-purchased product correlations).
	 *
	 * POST params: nonce, product_id (int), window_days (int), limit (int)
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_ajax_get_product_correlations() {
		check_ajax_referer( 'pdwchs_product_reports_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$product_id  = isset( $_POST['product_id'] )  ? absint( wp_unslash( $_POST['product_id'] ) )  : 0;
		$window_days = isset( $_POST['window_days'] )  ? absint( wp_unslash( $_POST['window_days'] ) ) : 90;
		$limit       = isset( $_POST['limit'] )        ? absint( wp_unslash( $_POST['limit'] ) )       : 10;

		if ( 0 === $product_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid product.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		$window_days = max( 30, min( 365, $window_days ) );
		$limit       = max( 1,  min( 50,  $limit ) );

		$correlations = $this->product_correlations_data->pdwchs_get_top_correlations(
			$product_id, $limit, $window_days
		);

		wp_send_json_success( array( 'correlations' => $correlations ) );
	}

	/**
	 * AJAX: revenue patterns by day-of-week, hour-of-day, or 7×24 heatmap.
	 *
	 * POST params: nonce, start_date (Y-m-d), end_date (Y-m-d),
	 *              view ('dow'|'hour'|'heatmap')
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_ajax_get_revenue_patterns() {
		check_ajax_referer( 'pdwchs_product_reports_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date   = isset( $_POST['end_date'] )   ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) )   : '';

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date )
		  || ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		$view = isset( $_POST['view'] ) ? sanitize_text_field( wp_unslash( $_POST['view'] ) ) : 'dow';
		if ( ! in_array( $view, array( 'dow', 'hour', 'heatmap' ), true ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid view.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		switch ( $view ) {
			case 'hour':
				$data = $this->revenue_patterns_data->pdwchs_get_revenue_by_hour( $start_date, $end_date );
				break;
			case 'heatmap':
				$data = $this->revenue_patterns_data->pdwchs_get_heatmap( $start_date, $end_date );
				break;
			default: // 'dow'
				$data = $this->revenue_patterns_data->pdwchs_get_revenue_by_dayofweek( $start_date, $end_date );
		}

		wp_send_json_success( array(
			'view'               => $view,
			'data'               => $data,
			'currency_symbol'    => get_woocommerce_currency_symbol(),
			'currency_position'  => get_option( 'woocommerce_currency_pos' ),
			'thousand_separator' => wc_get_price_thousand_separator(),
			'decimal_separator'  => wc_get_price_decimal_separator(),
			'timezone_label'     => wp_timezone_string(),
		) );
	}

	/**
	 * H3 — Register hidden product admin sub-pages.
	 *
	 * @since 2.3.0
	 *
	 * @param string $capability The capability string passed by the pdwchs_admin_menu_late action.
	 */
	public function pdwchs_register_menus( $capability ) {
		add_submenu_page(
			'',
			__( 'Product Revenue', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Product Revenue', 'puredevs-customer-history-for-woocommerce' ),
			'manage_woocommerce',
			'pd-woo-product-revenue',
			array( $this, 'pdwchs_product_revenue_page' )
		);
		add_submenu_page(
			'',
			__( 'Frequently Bought Together', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Frequently Bought Together', 'puredevs-customer-history-for-woocommerce' ),
			'manage_woocommerce',
			'pd-woo-product-correlations',
			array( $this, 'pdwchs_product_correlations_page' )
		);
		add_submenu_page(
			'',
			__( 'Revenue Patterns', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Revenue Patterns', 'puredevs-customer-history-for-woocommerce' ),
			'manage_woocommerce',
			'pd-woo-revenue-patterns',
			array( $this, 'pdwchs_revenue_patterns_page' )
		);
	}

	/**
	 * H4 — Enqueue product reports CSS on relevant pages.
	 *
	 * @since 2.3.0
	 */
	public function pdwchs_enqueue_styles() {
		if ( ! isset( $_GET['page'] ) || ! in_array( sanitize_text_field( wp_unslash( $_GET['page'] ) ), array( 'pd-woo-product-revenue', 'pd-woo-product-correlations', 'pd-woo-revenue-patterns' ), true ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$admin_css_url = PDWCHS_PRO_PLUGIN_URL . 'admin/css/';
		wp_enqueue_style( 'select2' );
		wp_enqueue_style( 'woocommerce_admin_styles' );
		wp_enqueue_style(
			'pdwchs-products-css',
			$admin_css_url . 'pd-customer-history-products.css',
			array(),
			time(),
			'all'
		);
	}

	/**
	 * H5 — Enqueue product reports JS on relevant pages.
	 *
	 * @since 2.3.0
	 */
	public function pdwchs_enqueue_scripts() {
		$admin_js_url = PDWCHS_PRO_PLUGIN_URL . 'admin/js/';
		$page         = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'pd-woo-product-revenue' === $page ) {
			wp_enqueue_script(
				'chartjs',
				PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL . 'admin/js/chart.umd.js',
				array(),
				'4.4.1',
				true
			);
			wp_enqueue_script( 'selectWoo' );
			wp_enqueue_script(
				'pdwchs-product-revenue-js',
				$admin_js_url . 'pdwchs-product-revenue-chart.js',
				array( 'jquery', 'chartjs', 'moment', 'date-picker', 'selectWoo' ),
				time(),
				true
			);
			wp_localize_script( 'pdwchs-product-revenue-js', 'pdwchs_product_revenue_vars', array(
				'ajaxurl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'pdwchs_product_reports_nonce' ),
				'currency_symbol'    => get_woocommerce_currency_symbol(),
				'currency_position'  => get_option( 'woocommerce_currency_pos' ),
				'thousand_separator' => wc_get_price_thousand_separator(),
				'decimal_separator'  => wc_get_price_decimal_separator(),
				'i18n'               => array(
					'loading_text'       => __( 'Loading product data...', 'puredevs-customer-history-for-woocommerce' ),
					'error_text'         => __( 'Error loading data. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
					'no_data_text'       => __( 'No revenue data for this selection.', 'puredevs-customer-history-for-woocommerce' ),
					'no_products'        => __( 'Select at least one product and click Apply.', 'puredevs-customer-history-for-woocommerce' ),
					'select_placeholder' => __( 'Select up to 3 products…', 'puredevs-customer-history-for-woocommerce' ),
				),
			) );
		}

		if ( 'pd-woo-product-correlations' === $page ) {
			wp_enqueue_script( 'selectWoo' );
			wp_enqueue_script(
				'pdwchs-product-correlations-js',
				$admin_js_url . 'pdwchs-product-correlations.js',
				array( 'jquery', 'selectWoo' ),
				time(),
				true
			);
			wp_localize_script( 'pdwchs-product-correlations-js', 'pdwchs_product_correlations_vars', array(
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'pdwchs_product_reports_nonce' ),
				'edit_link_base' => admin_url( 'post.php?action=edit&post=' ),
				'i18n'           => array(
					'loading_text'  => __( 'Loading correlations...', 'puredevs-customer-history-for-woocommerce' ),
					'error_text'    => __( 'Error loading data. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
					'no_data_text'  => __( 'No frequently bought together data for this product and window.', 'puredevs-customer-history-for-woocommerce' ),
					'select_prompt' => __( 'Select a product above to see frequently bought together items.', 'puredevs-customer-history-for-woocommerce' ),
				),
			) );
		}

		if ( 'pd-woo-revenue-patterns' === $page ) {
			wp_enqueue_script(
				'chartjs',
				PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL . 'admin/js/chart.umd.js',
				array(),
				'4.4.1',
				true
			);
			wp_enqueue_script(
				'pdwchs-revenue-patterns-js',
				$admin_js_url . 'pdwchs-revenue-patterns.js',
				array( 'jquery', 'chartjs', 'moment', 'date-picker' ),
				time(),
				true
			);
			wp_localize_script( 'pdwchs-revenue-patterns-js', 'pdwchs_revenue_patterns_vars', array(
				'ajaxurl'            => admin_url( 'admin-ajax.php' ),
				'nonce'              => wp_create_nonce( 'pdwchs_product_reports_nonce' ),
				'currency_symbol'    => get_woocommerce_currency_symbol(),
				'currency_position'  => get_option( 'woocommerce_currency_pos' ),
				'thousand_separator' => wc_get_price_thousand_separator(),
				'decimal_separator'  => wc_get_price_decimal_separator(),
				'timezone_label'     => wp_timezone_string(),
				'i18n'               => array(
					'loading_text'  => __( 'Loading revenue patterns...', 'puredevs-customer-history-for-woocommerce' ),
					'error_text'    => __( 'Error loading data. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
					'no_data_text'  => __( 'No revenue data for this date range.', 'puredevs-customer-history-for-woocommerce' ),
					'revenue_label' => __( 'Revenue', 'puredevs-customer-history-for-woocommerce' ),
					'orders_label'  => __( 'Orders', 'puredevs-customer-history-for-woocommerce' ),
					'best_label'    => __( 'Best', 'puredevs-customer-history-for-woocommerce' ),
					'worst_label'   => __( 'Worst', 'puredevs-customer-history-for-woocommerce' ),
					'day_labels'    => array(
						__( 'Sunday',    'puredevs-customer-history-for-woocommerce' ),
						__( 'Monday',    'puredevs-customer-history-for-woocommerce' ),
						__( 'Tuesday',   'puredevs-customer-history-for-woocommerce' ),
						__( 'Wednesday', 'puredevs-customer-history-for-woocommerce' ),
						__( 'Thursday',  'puredevs-customer-history-for-woocommerce' ),
						__( 'Friday',    'puredevs-customer-history-for-woocommerce' ),
						__( 'Saturday',  'puredevs-customer-history-for-woocommerce' ),
					),
				),
			) );
		}
	}

	/**
	 * Page callback: Product Revenue & Comparison.
	 *
	 * @since 2.3.0
	 */
	public function pdwchs_product_revenue_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'puredevs-customer-history-for-woocommerce' ) );
		}
		require dirname( __DIR__ ) . '/templates/product-revenue-chart.php';
	}

	/**
	 * Page callback: Frequently Bought Together.
	 *
	 * @since 2.3.0
	 */
	public function pdwchs_product_correlations_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'puredevs-customer-history-for-woocommerce' ) );
		}
		require dirname( __DIR__ ) . '/templates/product-correlations.php';
	}

	/**
	 * Page callback: Revenue Patterns.
	 *
	 * @since 2.3.0
	 */
	public function pdwchs_revenue_patterns_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'puredevs-customer-history-for-woocommerce' ) );
		}
		require dirname( __DIR__ ) . '/templates/revenue-patterns.php';
	}
}
