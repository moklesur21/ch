<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://puredevs.com/
 * @since      1.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/admin
 */

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/admin
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Admin {

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
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {

		$this->plugin_name = $plugin_name;
		$this->version = $version;

	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function pdwchs_enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in PD_Customer_History_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The PD_Customer_History_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( 'date-picker', plugin_dir_url( __FILE__ ) . 'css/daterangepicker.css', array(), $this->version, 'all' );
		//wp_enqueue_style( 'custom-style', plugin_dir_url( __FILE__ ) . 'css/pd-customer-history-admin.css', array(), $this->version, 'all' );
		wp_enqueue_style( 'custom-style', plugin_dir_url( __FILE__ ) . 'css/pd-customer-history-admin.css', array(), PDWCHS_CUSTOMER_HISTORY_VERSION, 'all' );

		do_action( 'pdwchs_enqueue_admin_styles', $this->version ); // H4

		if ( isset( $_GET['page'] ) && in_array( $_GET['page'], array( 'pd-woo-customers', 'pd-woo-segments' ), true ) ) {
			wp_enqueue_style(
				'pdwchs-segments-css',
				plugin_dir_url( __FILE__ ) . 'css/pd-customer-history-segments.css',
				array(),
				PDWCHS_CUSTOMER_HISTORY_VERSION,
				'all'
			);
		}

		$plugin_pages = array(
			'pd-woo-dashboard',
			'pd-woo-customers',
			'pd-woo-customer',
			'pd-woo-customer-email',
			'pd-woo-order-history',
			'pd-woo-segments',
			'pd-woo-sessions',
			'pd-woo-settings',
		);
		if ( isset( $_GET['page'] ) && in_array( sanitize_text_field( wp_unslash( $_GET['page'] ) ), $plugin_pages, true ) ) {
			wp_enqueue_style(
				'pdwchs-upsell-css',
				plugin_dir_url( __FILE__ ) . 'css/pdwchs-upsell.css',
				array(),
				PDWCHS_CUSTOMER_HISTORY_VERSION,
				'all'
			);
		}

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function pdwchs_enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in PD_Customer_History_Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The PD_Customer_History_Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( 'moment' );
		wp_enqueue_script( 'date-picker', plugin_dir_url( __FILE__ ) . 'js/daterangepicker.min.js', array( 'jquery', 'moment' ), $this->version, false );
        add_thickbox();

		// Enqueue Select2 for product filter on customers page
		if ( isset( $_GET['page'] ) && $_GET['page'] === 'pd-woo-customers' ) {
			// Use WooCommerce's SelectWoo for better compatibility
			wp_enqueue_script( 'selectWoo' );
			wp_enqueue_style( 'select2' );

			// Also enqueue WooCommerce admin styles for consistent look
			wp_enqueue_style( 'woocommerce_admin_styles' );
		}

		do_action( 'pdwchs_enqueue_admin_scripts', $this->version ); // H5

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'pd-woo-customers' ) {
			wp_enqueue_script(
				'pdwchs-save-segment',
				plugin_dir_url( __FILE__ ) . 'js/pdwchs-save-segment.js',
				array( 'jquery' ),
				PDWCHS_CUSTOMER_HISTORY_VERSION,
				true
			);
			wp_localize_script( 'pdwchs-save-segment', 'pdwchs_segments_vars', array(
				'nonce'       => wp_create_nonce( 'pdwchs_segment_nonce' ),
				'ajaxurl'     => admin_url( 'admin-ajax.php' ),
				'allowedKeys' => array(
					'user_role', 'customer_country', 'order_number', 'order_number_input',
					'total_spent', 'total_spent_input', 'start_date', 'end_date',
					'total_amount', 'total_order_input', 'order_status', 'payment_method',
					's', 'product_filter', 'rfm_segment', 'health_score_range',
					'health_trend', 'lifecycle_stage', 'lifecycle_days', 'behavior_filter',
					'show-items',
				),
				'i18n'        => array(
					'emptyName'     => __( 'Segment name cannot be empty.', 'puredevs-customer-history-for-woocommerce' ),
					'nameTooLong'   => __( 'Segment name cannot exceed 191 characters.', 'puredevs-customer-history-for-woocommerce' ),
					'noFilters'     => __( 'No filters applied. Cannot save empty segment.', 'puredevs-customer-history-for-woocommerce' ),
					'errorFallback' => __( 'An error occurred. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
					'saved'         => __( 'Segment saved successfully.', 'puredevs-customer-history-for-woocommerce' ),
				),
			) );
		}

		if ( isset( $_GET['page'] ) && $_GET['page'] === 'pd-woo-segments' ) {
			wp_enqueue_script(
				'pdwchs-segments-list',
				plugin_dir_url( __FILE__ ) . 'js/pdwchs-segments-list.js',
				array( 'jquery' ),
				PDWCHS_CUSTOMER_HISTORY_VERSION,
				true
			);
			wp_localize_script( 'pdwchs-segments-list', 'pdwchs_segments_vars', array(
				'nonce'        => wp_create_nonce( 'pdwchs_segment_nonce' ),
				'exportNonce'  => wp_create_nonce( 'pdwchs_export_segment_nonce' ),
				'ajaxurl'      => admin_url( 'admin-ajax.php' ),
				'adminPostUrl' => admin_url( 'admin-post.php' ),
				'customersUrl' => admin_url( 'admin.php?page=pd-woo-customers' ),
				'i18n'         => array(
					'confirmDelete' => __( 'Are you sure you want to delete this segment? This cannot be undone.', 'puredevs-customer-history-for-woocommerce' ),
					'deleteError'   => __( 'Could not delete segment. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
				),
			) );
		}

		//wp_enqueue_script( 'custom-js', plugin_dir_url( __FILE__ ) . 'js/pd-customer-history-admin.js', array( 'jquery' ), $this->version, false );
		wp_enqueue_script( 'custom-js', plugin_dir_url( __FILE__ ) . 'js/pd-customer-history-admin.js', array( 'jquery' ), PDWCHS_CUSTOMER_HISTORY_VERSION, false );
		wp_localize_script( 'custom-js', 'pdwchsAdminVars', array(
			'minDate' => PDWCHS_Data_Window::pdwchs_min_start(),
		) );

	}

	/*
	 *  Admin Menu and Separator
	 */
	function pdwchs_admin_menu() {
		global $menu;
		$capability = 'manage_woocommerce';

        $dashboard_page = add_menu_page(
            __( 'PD Customer History', 'puredevs-customer-history-for-woocommerce' ),
            __( 'Customer History', 'puredevs-customer-history-for-woocommerce' ),
            $capability,
            'pd-woo-dashboard',
            array($this, 'pdwchs_woo_dashboard'),
            'dashicons-analytics',
            '55.5'
        );

        add_submenu_page( 'pd-woo-dashboard', __( 'Dashboard', 'puredevs-customer-history-for-woocommerce' ), __( '<span id="pd-dashboard-menu">Dashboard</span>', 'puredevs-customer-history-for-woocommerce' ), $capability, 'pd-woo-dashboard', array(
            $this,
            'pdwchs_woo_dashboard'
        ) );

		add_submenu_page( 'pd-woo-dashboard', __( 'PD Customers', 'puredevs-customer-history-for-woocommerce' ), __( '<span id="pd-customers-menu">Customers & Users</span>', 'puredevs-customer-history-for-woocommerce' ), $capability, 'pd-woo-customers', array(
			$this,
			'pdwchs_all_customers'
		) );
		add_submenu_page( '', __( 'PD Customer', 'puredevs-customer-history-for-woocommerce' ), __( 'Customer', 'puredevs-customer-history-for-woocommerce' ), $capability, 'pd-woo-customer', array(
			$this,
			'pdwchs_customer_details'
		) );
		add_submenu_page( '', __( 'PD Customer Email', 'puredevs-customer-history-for-woocommerce' ), __( 'Customer Email', 'puredevs-customer-history-for-woocommerce' ), $capability, 'pd-woo-customer-email', array(
			$this,
			'pdwchs_customer_email'
		) );
        do_action( 'pdwchs_admin_menu_early', $capability );
        add_submenu_page(
            'pd-woo-dashboard',
            __( 'Customer Segments', 'puredevs-customer-history-for-woocommerce' ),
            __( '<span id="pd-segments-menu">Segments</span>', 'puredevs-customer-history-for-woocommerce' ),
            'manage_woocommerce',
            'pd-woo-segments',
            array( $this, 'pdwchs_segments_page' )
        );
        add_submenu_page( 'pd-woo-dashboard', __( 'Order History', 'puredevs-customer-history-for-woocommerce' ), __( '<span id="pd-order-history-menu">Order History</span>', 'puredevs-customer-history-for-woocommerce' ), $capability, 'pd-woo-order-history', array(
            $this,
            'pdwchs_order_history'
        ) );

        $revenue_page = add_submenu_page(
            'pd-woo-dashboard',
            __( 'Revenue', 'puredevs-customer-history-for-woocommerce' ),
            __( '<span id="pd-revenue-menu">Revenue</span>', 'puredevs-customer-history-for-woocommerce' ),
            $capability,
            'pd-woo-revenue',
            array( $this, 'pdwchs_revenue_page' )
        );

        if ( ! pdwchs_is_pro_active() ) {
            add_submenu_page(
                'pd-woo-dashboard',
                __( 'Search Analytics', 'puredevs-customer-history-for-woocommerce' ),
                __( '<span id="pd-search-analytics-menu">Search Analytics</span>', 'puredevs-customer-history-for-woocommerce' ),
                $capability,
                'pd-woo-search-analytics',
                array( $this, 'pdwchs_search_analytics_page' )
            );
        }

        do_action( 'pdwchs_admin_menu_late', $capability ); // H3

        add_submenu_page('pd-woo-dashboard', __('Settings', 'puredevs-customer-history-for-woocommerce'), __('Settings', 'puredevs-customer-history-for-woocommerce'), $capability, 'pd-woo-settings', array(
            $this,
            'pdwchs_admin_settings'
        ));

        add_action( 'admin_print_scripts-' . $dashboard_page, array($this, 'pdwchs_admin_custom_dashboard_js') );
        add_action( 'admin_print_scripts-' . $revenue_page, array($this, 'pdwchs_admin_custom_dashboard_js') );
	}

	/*
	 *  Callback functions
	 */
	function pdwchs_woo_dashboard() {
		require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/dashboard.php';
	}

	/**
	 * Display all customers page.
	 */
	function pdwchs_all_customers() {
		require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/customers.php';
	}

	/**
	 * Display customer segments page (premium only).
	 */
	function pdwchs_segments_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'puredevs-customer-history-for-woocommerce' ) );
		}
		require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/segments-list.php';
	}

	/**
	 * Display plugin settings page.
	 */
	function pdwchs_admin_settings() {
		require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/plugin-settings.php';
	}

	/**
	 * Display Free search analytics page.
	 */
	function pdwchs_search_analytics_page() {
		require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/search-analytics.php';
	}

	/**
	 * Display customer details page.
	 */
	function pdwchs_customer_details() {
		$screen = get_current_screen();
		require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/customer-details.php';
	}

	/**
	 * Display customer email page.
	 */
	function pdwchs_customer_email() {
		require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/customer-email.php';
	}

	/**
	 * Display order history page.
	 */
	function pdwchs_order_history() {
		require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/order-history.php';
	}

	/**
	 * Display the free Revenue dashboard page.
	 */
	function pdwchs_revenue_page() {
		require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/revenue.php';
	}

	/**
	 * Enqueue custom dashboard JavaScript for admin.
	 */
	function pdwchs_admin_custom_dashboard_js() {
		// Chart.js (MIT) — bundled locally; powers the dashboard pie charts.
		wp_enqueue_script( 'chartjs', plugin_dir_url( __FILE__ ) . 'js/chart.umd.js', array(), '4.4.1', false );
	}

	/**
	 * AJAX handler for product search.
	 * Fallback handler in case WooCommerce's handler doesn't work.
	 *
	 * @since    1.0.0
	 */
	public function pdwchs_ajax_product_search() {
		check_ajax_referer( 'search-products', 'security' );

		$term = isset( $_GET['term'] ) ? sanitize_text_field( $_GET['term'] ) : '';
		$limit = isset( $_GET['limit'] ) ? absint( $_GET['limit'] ) : 20;

		if ( empty( $term ) ) {
			wp_die();
		}

		$data_store = WC_Data_Store::load( 'product' );
		$product_ids = $data_store->search_products( $term, '', true, false, $limit );

		$products = array();

		foreach ( $product_ids as $product_id ) {
			$product = wc_get_product( $product_id );

			if ( ! $product ) {
				continue;
			}

			$products[ $product_id ] = rawurldecode( wp_strip_all_tags( $product->get_formatted_name() ) );
		}

		wp_send_json( $products );
	}


}
