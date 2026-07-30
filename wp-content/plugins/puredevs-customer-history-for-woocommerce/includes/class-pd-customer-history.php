<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       https://puredevs.com/
 * @since      1.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */

class PD_Customer_History {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      PD_Customer_History_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'PDWCHS_CUSTOMER_HISTORY_VERSION' ) ) {
			$this->version = PDWCHS_CUSTOMER_HISTORY_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'puredevs-customer-history-for-woocommerce';

		add_action( 'admin_init',    array( 'PD_Customer_History_Activator', 'pdwchs_check_version_upgrade' ) );

		$this->pdwchs_load_dependencies();
		$this->pdwchs_set_locale();
		$this->pdwchs_define_admin_hooks();
		$this->pdwchs_define_public_hooks();

        if ( is_admin() ) {
            add_action('in_plugin_update_message-' . PDWCHS_CUSTOMER_HISTORY_PLUGIN_BASENAME, array( $this, 'pdwchs_plugin_update_message' ), 10, 2);
        }

        add_filter( 'plugin_action_links_'.PDWCHS_CUSTOMER_HISTORY_PLUGIN_BASENAME, array( $this, 'pdwchs_add_plugin_page_settings_link' ) );

		add_filter( 'cron_schedules', array($this, 'pdwchs_custom_add_cron_interval') );

		$_generate_weekly_report = get_option('pd-woo-generate_weekly_report');

		if(!empty($_generate_weekly_report)){
			add_action('init', array($this, 'pdwchs_generate_weekly_reports_schedule_events'));
			add_action('pdwchs_generate_weekly_reports', array($this, 'pdwchs_generate_weekly_reports_callback'));
		}else{
			wp_clear_scheduled_hook( 'pdwchs_generate_weekly_reports' );
		}

		do_action( 'pdwchs_register_pro_cron' ); // H16 — fires Pro cron registrations when Pro listeners are loaded
	}

	/**
	 * Starts a new PHP session if one does not already exist.
	 *
	 * This method checks if a session ID is present. If not, it initiates a new session.
	 * Useful for tracking user data across requests.
	 *
	 * @return void
	 */
	public function pdwchs_start_session(){
		if (!session_id()) {
			session_start();
		}
	}

	/**
	 * Adds a custom cron schedule interval for WordPress.
	 *
	 * This function registers a new schedule called 'weekly' with an interval of 604800 seconds (once per week).
	 *
	 * @param array $schedules Existing cron schedules.
	 * @return array Modified cron schedules including the 'weekly' interval.
	 */
	function pdwchs_custom_add_cron_interval( $schedules ) {
		$schedules['weekly'] = array(
			'interval' => 604800,
			'display' => __('Once Weekly', 'puredevs-customer-history-for-woocommerce')
		);
		$schedules['pdwchs_fifteen_minutes'] = [
			'interval' => 15 * MINUTE_IN_SECONDS,
			'display'  => __( 'Every 15 minutes', 'puredevs-customer-history-for-woocommerce' ),
		];
		return $schedules;
	}

	/**
	 * Comparison function to sort objects by their 'qta' property in descending order.
	 *
	 * Used with sorting functions like usort to order objects based on the 'qta' value.
	 *
	 * @param object $a First object to compare, expected to have a 'qta' property.
	 * @param object $b Second object to compare, expected to have a 'qta' property.
	 * @return int Returns 1 if $a->qta < $b->qta, -1 if $a->qta > $b->qta, or 0 if equal.
	 */
	function pdwchs_sortByqta($a, $b){
		if ($a->qta < $b->qta) {
			return 1;
		} elseif ($a->qta > $b->qta) {
			return -1;
		}
		return 0;
	}

	public function pdwchs_generate_weekly_reports_schedule_events(){
		if ( ! wp_next_scheduled( 'pdwchs_generate_weekly_reports' ) ) {
			wp_schedule_event( time(), 'weekly', 'pdwchs_generate_weekly_reports' );
		}
	}

	/**
	 * Schedules a weekly event to generate customer history reports if not already scheduled.
	 *
	 * This function checks if the 'pdwchs_generate_weekly_reports' event is already scheduled.
	 * If not, it schedules the event to run weekly using WordPress's cron system.
	 *
	 * @return void
	 */
	public function pdwchs_generate_weekly_reports_callback(){
		global $wpdb;
		$initial_date =gmdate('Y-m-d', strtotime('-1 week'));;
		$final_date =gmdate('Y-m-d');
		$initial_date_fn =gmdate('Y-m-d H:i:s', strtotime('-1 week'));;
		$final_date_fn =gmdate('Y-m-d H:i:s');
		$upload_dir = wp_upload_dir();
		$attachments = [];
		$reports = [];
		$order_report_html = '';
		$top_customer_report_html = '';
		$search_report_html = '';
		$most_viewed_products_report_html = '';
		$most_viewed_categories_report_html = '';

		$_generate_weekly_report = get_option('pd-woo-generate_weekly_report');

		if(!empty($_generate_weekly_report) && in_array('order',$_generate_weekly_report)){

			$args = array(
				'limit'=>-1,
				'type'=> 'shop_order',
				'date_created'=> $initial_date .'...'. $final_date
			);
			$orders = wc_get_orders($args);

			if ( !empty( $orders ) ) :

				$delimiter = ",";

				$order_report = $upload_dir['path']."/customers_order_history_from_" . $initial_date . "_to_".$final_date.".csv";

				// Build CSV in memory, then write to disk via WP_Filesystem
				$order_tmp = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- memory stream, not filesystem
				$fields = array('Date', 'Order ID', 'Customer ID', 'Quantity', 'Amount', 'Status');
				fputcsv( $order_tmp, $fields, $delimiter );

				foreach ( $orders as $order ) {
					if ( wp_get_post_parent_id( $order->ID ) == 0 ) {
						$order     = new WC_Order( $order->ID );
						$order_id  = $order->get_id();

						if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
							$cus_id = get_post_meta( $order_id, '_customer_user', true );
						}else{
							$cus_id = $order->get_customer_id();
						}

						$items_qty = '';
						foreach ( $order->get_items() as $line ) {
							$items_qty .= $line['qty'] . ' x ' . $line['name'] . '\n';
						}

						$order_date = $order->get_date_created() ? $order->get_date_created()->date( 'Y-m-d H:i:s' ) : '';
						$order_total = $order->get_total();
						$order_status = ucfirst( str_replace( 'wc-', '', $order->get_status() ) );
						$lineData = array($order_date, $order_id, $cus_id, $items_qty, $order_total, $order_status);
						fputcsv( $order_tmp, $lineData, $delimiter );
					}
				}

				rewind( $order_tmp );
				$order_csv_content = stream_get_contents( $order_tmp );
				fclose( $order_tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

				global $wp_filesystem;
				if ( ! function_exists( 'WP_Filesystem' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}
				WP_Filesystem();
				$wp_filesystem->put_contents( $order_report, $order_csv_content, FS_CHMOD_FILE );

				$attachments[] = $order_report;

				$reports[] = 'Order';

				if( count($orders) > 3 ){
					$l_orders = array_slice($orders,0,3);
				}else{
					$l_orders = $orders;
				}

				if( $l_orders ){
					$order_report_html .= '<table style="border: 1px solid gray;border-collapse: collapse;padding:5px;">
					<tr>
						<th colspan="6" style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Orders Report</th>
					</tr>
					<tr>
						<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Date</th>
						<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Order ID</th>
						<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Customer ID</th>
						<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Quantity</th>
						<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Amount</th>
						<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Status</th>
					</tr>';
					foreach ( $l_orders as $l_order ) {
						if ( wp_get_post_parent_id( $l_order->ID ) == 0 ) {
							$l_order     = new WC_Order( $l_order->ID );
							$order_id  = $l_order->get_id();

							if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
								$cus_id = get_post_meta( $order_id, '_customer_user', true );
							}else{
								$cus_id = $l_order->get_customer_id();
							}

							$items_qty = '';
							foreach ( $l_order->get_items() as $line ) {
								$items_qty .= $line['qty'] . ' x ' . $line['name'] . '\n';
							}

							$order_date = $l_order->get_date_created() ? $l_order->get_date_created()->date( 'Y-m-d H:i:s' ) : '';
							$order_total = $l_order->get_total();
							$order_status = ucfirst( str_replace( 'wc-', '', $l_order->get_status() ) );
							$lineData = array($order_date, $order_id, $cus_id, $items_qty, $order_total, $order_status);
							$order_report_html .= '<tr>
								<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">'.$order_date.'</td>
								<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">'.$order_id.'</td>
								<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">'.$cus_id.'</td>
								<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">'.$items_qty.'</td>
								<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">'.get_woocommerce_currency_symbol().$order_total.'</td>
								<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">'.$order_status.'</td>
							</tr>';
						}
					}
					$order_report_html .= '</table>';
				}

			endif;

		}

		if(!empty($_generate_weekly_report) && in_array('top_customer',$_generate_weekly_report)){
			$query = $wpdb->prepare(
				"SELECT COUNT(id) as qta, SUM(total_amount) as order_total, customer_id FROM {$wpdb->prefix}wc_orders WHERE date_created_gmt BETWEEN %s AND %s GROUP BY customer_id ORDER BY order_total DESC",
				$initial_date_fn,
				$final_date_fn
			);
			$_top_customers = $wpdb->get_results( $query ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- query built via $wpdb->prepare() above

			if ( !empty( $_top_customers ) ) :

				$delimiter = ",";

				$top_customers_report = $upload_dir['path']."/top_customers_history_from_" . $initial_date . "_to_".$final_date.".csv";
				// Build CSV in memory, then write to disk via WP_Filesystem
				$top_customers_tmp = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- memory stream, not filesystem
				$fields = array('Customer', 'Total Amount', 'Total Orders');
				fputcsv( $top_customers_tmp, $fields, $delimiter );

				foreach ( $_top_customers as $_top_customer ) {
					$customer = get_user_by('id', $_top_customer->customer_id);
					$customer_name   = ( $customer->first_name != '' || $customer->last_name != '' ) ? esc_html( $customer->first_name . ' ' . $customer->last_name ) : esc_html( $customer->display_name );
					$order_total = number_format($_top_customer->order_total, 2, '.', '');
					$lineData = array($customer_name, $order_total, $_top_customer->qta);
					fputcsv( $top_customers_tmp, $lineData, $delimiter );
				}
				rewind( $top_customers_tmp );
				$top_customers_csv_content = stream_get_contents( $top_customers_tmp );
				fclose( $top_customers_tmp ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose

				if ( ! function_exists( 'WP_Filesystem' ) ) {
					require_once ABSPATH . 'wp-admin/includes/file.php';
				}
				global $wp_filesystem;
				WP_Filesystem();
				$wp_filesystem->put_contents( $top_customers_report, $top_customers_csv_content, FS_CHMOD_FILE );

				$attachments[] = $top_customers_report;

				$reports[] = 'Top Customers';

				if( count($_top_customers) > 3 ){
					$l_top_customers = array_slice($_top_customers,0,3);
				}else{
					$l_top_customers = $_top_customers;
				}

				if( $l_top_customers ){
					$top_customer_report_html .= '<table style="border: 1px solid gray;border-collapse: collapse;padding:5px;">
					<tr>
						<th colspan="3" style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Top Customers Report</th>
					</tr>
					<tr>
						<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Customer</th>
						<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Total Amount</th>
						<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Total Orders</th>
					</tr>';
					foreach ( $l_top_customers as $l_top_customer ) {
						$customer = get_user_by('id', $l_top_customer->customer_id);
						$customer_name   = ( $customer->first_name != '' || $customer->last_name != '' ) ? esc_html( $customer->first_name . ' ' . $customer->last_name ) : esc_html( $customer->display_name );
						$order_total = number_format($l_top_customer->order_total, 2, '.', '');
						$top_customer_report_html .= '<tr>
							<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">'.$customer_name.'</td>
							<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">'.get_woocommerce_currency_symbol().$order_total.'</td>
							<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">'.$l_top_customer->qta.'</td>
						</tr>';
					}
					$top_customer_report_html .= '</table>';
				}

			endif;
		}

		// H19 — Pro listeners add search/product/category CSV sheets.
		$extra_sheets = apply_filters( 'pdwchs_weekly_report_extra_sheets', [
			'reports'     => [],
			'attachments' => [],
			'html'        => [],
		], $initial_date_fn, $final_date_fn );
		foreach ( (array) $extra_sheets['reports'] as $_sheet_name ) {
			$reports[] = $_sheet_name;
		}
		foreach ( (array) $extra_sheets['attachments'] as $_sheet_file ) {
			$attachments[] = $_sheet_file;
		}
		if(!empty($reports)){
			$email = get_option('pd-woo-send_weekly_report_to') ? get_option('pd-woo-send_weekly_report_to') : get_option('admin_email');

			$headers = 'From: '.get_option('blogname').' <'.get_option('admin_email').'>' . "\r\n";
			$headers .= "MIME-Version: 1.0\r\n";
			$headers .= "Content-Type: text/html; charset=UTF-8\r\n";
			$subject = 'Weekly reports';
			$msg = 'Hi! Please check attached weekly reports for '.implode(', ',$reports).' from '.$initial_date.' to '.$final_date;

			$msg .= '<br/>';
			if(!empty($order_report_html)){
				$msg .= '<br/>';
				$msg .= $order_report_html;
			}

			if(!empty($top_customer_report_html)){
				$msg .= '<br/>';
				$msg .= $top_customer_report_html;
			}

			if(!empty($search_report_html)){
				$msg .= '<br/>';
				$msg .= $search_report_html;
			}

			if(!empty($most_viewed_products_report_html)){
				$msg .= '<br/>';
				$msg .= $most_viewed_products_report_html;
			}

			if(!empty($most_viewed_categories_report_html)){
				$msg .= '<br/>';
				$msg .= $most_viewed_categories_report_html;
			}
			foreach ( ! empty( $extra_sheets['html'] ) ? (array) $extra_sheets['html'] : [] as $_extra_html ) {
				if ( ! empty( $_extra_html ) ) {
					$msg .= '<br/>';
					$msg .= $_extra_html;
				}
			}
			$msg .= '<br/>';
			$msg .= '<b>Please download attached files to see full reports</b>';

			wp_mail($email, $subject, $msg, $headers, $attachments);
		}

	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - PD_Customer_History_Loader. Orchestrates the hooks of the plugin.
	 * - PD_Customer_History_i18n. Defines internationalization functionality.
	 * - PD_Customer_History_Admin. Defines all hooks for the admin area.
	 * - PD_Customer_History_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pdwchs_load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pd-customer-history-loader.php';

		/**
		 * The class responsible for defining internationalization functionality
		 * of the plugin.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-pd-customer-history-i18n.php';

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-pd-customer-history-admin.php';

		/**
		 * The class responsible for defining all actions that occur in the public-facing
		 * side of the site.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'public/class-pd-customer-history-public.php';

		if ( ! class_exists( 'WP_List_Table' ) ) {
			require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}

        /**
         * The class responsible for managing customer/user information's
         */
        require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/class-pd-customer-history-customers.php';

        /**
         * The class responsible for managing customer/user order information's
         */
        require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/class-pd-customer-order-history.php';

        /**
         * The class responsible for email functionality
         */
        require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/class-pd-customer-history-email.php';

        /**
         * Free-tier helper class (countries list, session URL formatter, product view filter).
         */
        require_once plugin_dir_path( __FILE__ ) . 'helper/class-pdwchs-helper.php';

        /**
         * Free sessions data layer (schema management, core DML, read stubs).
         */
        require_once plugin_dir_path( __FILE__ ) . 'data/class-pdwchs-sessions-data.php';
        require_once plugin_dir_path( __FILE__ ) . 'data/class-pdwchs-search-stats-data.php';

		// Premium classes — only loaded when pro distribution is active (trial or paid).
		// Free-tier classes above remain available even after trial expiry.
		require_once plugin_dir_path( __FILE__ ) . 'analytics/class-pdwchs-revenue-data-free.php';
		do_action( 'pdwchs_pro_init' );
		if ( pdwchs_is_pro_active() ) {
			// Capture the Intelligence coordinator instance exposed by Pro.php boot.
			$this->intelligence = apply_filters( 'pdwchs_intelligence_instance', null );
		}

		require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/class-pdwchs-data-window.php';
		require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/class-pdwchs-upsell.php';
		new PDWCHS_Upsell();

		require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/class-pdwchs-telemetry.php';
		new PDWCHS_Telemetry();

		require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/segments/class-pdwchs-segments-manager.php';

		$this->segments_manager = new PDWCHS_Segments_Manager();

		require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/segments/class-pdwchs-segments-ajax.php';
		$this->segments_ajax = new PDWCHS_Segments_Ajax();
		require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/segments/class-pdwchs-segments-export.php';
		$this->segments_export = new PDWCHS_Segments_Export();

		$this->loader = new PD_Customer_History_Loader();

	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the PD_Customer_History_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pdwchs_set_locale() {

		$plugin_i18n = new PD_Customer_History_i18n();

		$this->loader->add_action( 'plugins_loaded', $plugin_i18n, 'pdwchs_load_plugin_textdomain' );

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pdwchs_define_admin_hooks() {

		$plugin_admin = new PD_Customer_History_Admin( $this->pdwchs_get_plugin_name(), $this->pdwchs_get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'pdwchs_enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'pdwchs_enqueue_scripts' );

		$this->loader->add_action( 'admin_menu', $plugin_admin, 'pdwchs_admin_menu' );
		do_action( 'pdwchs_register_pro_settings', $this->loader, $plugin_admin );

		$this->loader->add_action( 'admin_init', $this, 'pdwchs_export_csv' );
		$this->loader->add_action( 'admin_init', $this, 'pdwchs_import_csv' );
		$this->loader->add_action( 'admin_init', $this, 'pdwchs_delete_sessions' );
		$this->loader->add_action( 'admin_init', $this, 'pdwchs_delete_custom_sessions' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function pdwchs_define_public_hooks() {

		$plugin_public = new PD_Customer_History_Public( $this->pdwchs_get_plugin_name(), $this->pdwchs_get_version() );

		$this->loader->add_action( 'init', $this, 'pdwchs_start_session', 1 );
		$this->loader->add_action( 'init', $plugin_public, 'pdwchs_woo_init_session' );
		do_action( 'pdwchs_public_init', $plugin_public, $this->loader );

	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function pdwchs_run() {
		$this->loader->pdwchs_run_loader();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function pdwchs_get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    PD_Customer_History_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function pdwchs_get_version() {
		return $this->version;
	}

	/**
	 * Retrieves the client's IP address from various server variables.
	 *
	 * This function checks multiple server headers to determine the client's IP address,
	 * including HTTP_CLIENT_IP, HTTP_X_FORWARDED_FOR, HTTP_X_FORWARDED, HTTP_FORWARDED_FOR,
	 * HTTP_FORWARDED, and REMOTE_ADDR. If none are set, it returns 'UNKNOWN'.
	 *
	 * @return string The detected client IP address or 'UNKNOWN' if not found.
	 */
	public static function pdwchs_get_client_ip() {
		if ( class_exists( 'PDWCHS_Pro_Helpers' ) ) {
			return PDWCHS_Pro_Helpers::pdwchs_get_client_ip();
		}
		return isset( $_SERVER['REMOTE_ADDR'] ) ? $_SERVER['REMOTE_ADDR'] : 'UNKNOWN';
	}

	/**
	 * Detects if the current request is made by a bot based on the User-Agent header.
	 *
	 * Checks the HTTP_USER_AGENT server variable for common bot keywords such as
	 * 'bot', 'crawl', 'slurp', or 'spider' (case-insensitive).
	 *
	 * @return bool Returns true if a bot is detected, false otherwise.
	 */
	public static function pdwchs_woo_bot_detected() {
		if ( class_exists( 'PDWCHS_Pro_Helpers' ) ) {
			return PDWCHS_Pro_Helpers::pdwchs_woo_bot_detected();
		}
		return false;
	}

    /**
     * Export sessions data to csv file.
     *
     * @since     1.0.0
     * @return    null
     */
    public function pdwchs_export_csv() {
        if ( empty( $_POST['export'] )
            || empty( $_GET['page'] )
            || 'pd-woo-settings' !== sanitize_text_field( wp_unslash( $_GET['page'] ) )
            || ! isset( $_POST['export_customer_history'] )
        ) {
            return;
        }
        if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['export_customer_history'] ) ), 'export_customer_history_action' )
        ) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' . esc_html__('Security check failed!', 'puredevs-customer-history-for-woocommerce') . '</p></div>';
            });
        } else {
            global $wpdb;
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name uses $wpdb->prefix, no user input
            $sessions = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pd_woo_customer_history_sessions ORDER BY id ASC" );

            if (empty($sessions)) {
                return false;
            }

            $delimiter = ",";
            $filename = "customer-sessions-export" .gmdate('Y-m-d') . ".csv";

            //create a file pointer
            $f = fopen( 'php://memory', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- memory stream, not filesystem

            //set column headers
            $fields = array('id', 'user_id', 'ip', 'url', 'referer', 'reg_date', 'del');
            fputcsv($f, $fields, $delimiter);

            //output each row of the data, format line as csv and write to file pointer
            foreach ($sessions as $session) {
                $lineData = array($session->id, $session->user_id, $session->ip, $session->url, $session->referer, $session->reg_date, $session->del);
                fputcsv($f, $lineData, $delimiter);
            }

            //move back to beginning of file
            fseek($f, 0);

            //set headers to download file rather than displayed
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '";');

            //output all remaining data on a file pointer
            fpassthru($f);
            exit;
        }
    }

    /**
     * Import sessions from csv file.
     *
     * @since     1.0.0
     * @return    null
     */
    public function pdwchs_import_csv() {
        if ( empty( $_POST['import'] )
            || empty( $_GET['page'] )
            || 'pd-woo-settings' !== sanitize_text_field( wp_unslash( $_GET['page'] ) )
            || ! isset( $_POST['import_customer_history'] )
        ) {
            return;
        }
        if ( ! isset( $_POST['import_customer_history'] )
            || ! wp_verify_nonce( $_POST['import_customer_history'], 'import_customer_history_action' )
        ) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' . esc_html__('Security check failed!', 'puredevs-customer-history-for-woocommerce') . '</p></div>';
            });
        } else {
            global $wpdb;
            if (isset($_FILES["pd_ch_import"]) && $_FILES["pd_ch_import"]['size'] > 0) {
                $table_name = $wpdb->prefix . 'pd_woo_customer_history_sessions';
                $filename = $_FILES["pd_ch_import"]["tmp_name"];
                //$filename = sanitize_file_name($_FILES["pd_ch_import"]["tmp_name"]);

                $file = fopen( $filename, 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- reading uploaded temp file
                $count = 0;
                while (($getData = fgetcsv($file, 10000, ",")) !== FALSE) {
                    $count++;
                    if ($count == 1) {
                        continue;
                    }
                    $wpdb->insert(
                        $table_name,
                        array(
                            'user_id' => $getData[1],
                            'ip' => $getData[2],
                            'url' => $getData[3],
                            'referer' => $getData[4],
                            'reg_date' => $getData[5],
                            'del' => $getData[6],
                        ),
                        array(
                            '%d',
                            '%s',
                            '%s',
                            '%s',
                            '%s',
                            '%d',
                        )
                    );
                }
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-success"><p>' . esc_html__('CSV file imported successfully.', 'puredevs-customer-history-for-woocommerce') . '</p></div>';
                });
                fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- closing uploaded temp file handle
            } else {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>' . esc_html__('CSV File Error!', 'puredevs-customer-history-for-woocommerce') . '</p></div>';
                });
            }
        }
    }

    /**
     * Delete all sessions.
     *
     * @since     1.0.0
     * @return    null
     */
    public function pdwchs_delete_sessions( ) {
        if ( empty( $_POST['delete_sessions'] )
            || empty( $_GET['page'] )
            || 'pd-woo-settings' !== sanitize_text_field( wp_unslash( $_GET['page'] ) )
            || ! isset( $_POST['delete_session_history'] )
        ) {
            return;
        }
        global $wpdb;
        $table_name = $wpdb->prefix.'pd_woo_customer_history_sessions';
        if ( current_user_can( 'manage_woocommerce' ) ) {

            if ( ! isset( $_POST['delete_session_history'] )
                || ! wp_verify_nonce( $_POST['delete_session_history'], 'delete_session_history_action' )
            ) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>' . esc_html__('Security check failed!', 'puredevs-customer-history-for-woocommerce') . '</p></div>';
                });
            } else {

                // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- TRUNCATE TABLE, table name from $wpdb->prefix only
                $result = $wpdb->query( "TRUNCATE {$table_name}" );
                if ($result) {
                    add_action('admin_notices', function () {
                        echo '<div class="notice notice-success"><p>' . esc_html__('Sessions deleted successfully.', 'puredevs-customer-history-for-woocommerce') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function () {
                        echo '<div class="notice notice-error"><p>' . esc_html__('Failed to delete sessions!', 'puredevs-customer-history-for-woocommerce') . '</p></div>';
                    });
                }
            }
        } else { add_action( 'admin_notices', function(){
                echo '<div class="notice notice-error"><p>' . esc_html__( 'Sorry, you don\'t have permission to access this page.', 'puredevs-customer-history-for-woocommerce' ) . '</p></div>';
            } );
        }

    }

    /**
     * Delete sessions by date range.
     *
     * @since     1.0.0
     * @return    null
     */
    public function pdwchs_delete_custom_sessions( ) {
        if ( empty( $_POST['delete_custom'] )
            || empty( $_GET['page'] )
            || 'pd-woo-settings' !== sanitize_text_field( wp_unslash( $_GET['page'] ) )
            || ! isset( $_POST['custom_delete_history'] )
        ) {
            return;
        }
        global $wpdb;
        $table_name = $wpdb->prefix.'pd_woo_customer_history_sessions';
        if ( current_user_can( 'manage_woocommerce' ) ) {

            if ( ! isset( $_POST['custom_delete_history'] )
                || ! wp_verify_nonce( $_POST['custom_delete_history'], 'custom_delete_history_action' )
            ) {
                add_action('admin_notices', function () {
                    echo '<div class="notice notice-error"><p>' . esc_html__('Security check failed!', 'puredevs-customer-history-for-woocommerce') . '</p></div>';
                });
            } else {

                $start_date = sanitize_text_field($_POST['start_date']);
                $end_date = sanitize_text_field($_POST['end_date']);

                $result = $wpdb->query($wpdb->prepare("DELETE FROM $table_name WHERE reg_date BETWEEN %s AND %s", $start_date, $end_date));
                if ($result) {
                    add_action('admin_notices', function () {
                        echo '<div class="notice notice-success"><p>' . esc_html__('Sessions deleted successfully.', 'puredevs-customer-history-for-woocommerce') . '</p></div>';
                    });
                } else {
                    add_action('admin_notices', function () {
                        echo '<div class="notice notice-error"><p>' . esc_html__('Failed to delete sessions!', 'puredevs-customer-history-for-woocommerce') . '</p></div>';
                    });
                }
            }
        } else { add_action( 'admin_notices', function(){
            echo '<div class="notice notice-error"><p>' . esc_html__( 'Sorry, you don\'t have permission to access this page.', 'puredevs-customer-history-for-woocommerce' ) . '</p></div>';
        } );
        }

    }

    /**
     * Show row meta on the plugin screen.
     *
     * @param mixed $links Plugin Row Meta.
     * @param mixed $file  Plugin Base file.
     *
     * @return array
     */

    public static function pdwchs_plugin_row_meta( $links, $file ) {

        if ( PDWCHS_CUSTOMER_HISTORY_PLUGIN_BASENAME === $file ) {
            $row_meta = array(
                'docs'    => '<a href="' . esc_url( apply_filters( 'pdwchs_customer_history_docs_url', '#' ) ) . '" aria-label="' . esc_attr__( 'PureDevs Customer History for WooCommerce Documentation', 'puredevs-customer-history-for-woocommerce' ) . '">' . esc_html__( 'Docs', 'puredevs-customer-history-for-woocommerce' ) . '</a>',
                'support' => '<a href="' . esc_url( apply_filters( 'pdwchs_customer_history_support_url', 'https://puredevs.com/support' ) ) . '" aria-label="' . esc_attr__( 'Visit customer support', 'puredevs-customer-history-for-woocommerce' ) . '">' . esc_html__( 'Customer support', 'puredevs-customer-history-for-woocommerce' ) . '</a>',
            );

            return array_merge( $links, $row_meta );
        }

        return (array) $links;
    }

    /**
     * Show action links on the plugin screen.
     *
     * @param mixed $links Plugin Action links.
     *
     * @return array
     */

    public function pdwchs_add_plugin_page_settings_link( $links ) {
        $links[] = '<a href="' .
            admin_url( 'admin.php?page=pd-woo-settings' ) .
            '">' . __( 'Settings', 'puredevs-customer-history-for-woocommerce' ) . '</a>';
        return $links;
    }

    /**
     * Plugin inline update notice.
     *
     * @since    1.0.0
     * @access   public
     */

    public function pdwchs_plugin_update_message( $data, $response ) {
        if( isset( $data['upgrade_notice'] ) ) {
            printf(
                '<div class="update-message">%s</div>',
                wp_kses_post( wpautop( $data['upgrade_notice'] ) )
            );
        }
    }

	/**
     * Top selling products.
     *
     * @since    2.1.1
     * @access   public
     */

    public static function pdwchs_top_selling_products_by_month( $limit=5 ) {
        $first_day_this_month =gmdate('Y-m-01');
		$last_day_this_month  =gmdate('Y-m-t');
		$top_selling_products = array();
		$orders = wc_get_orders(array(
			'limit'=>-1,
			'type'=> 'shop_order',
			'status'=> array( 'wc-on-hold', 'wc-processing', 'wc-completed' ),
			'date_created'=> $first_day_this_month .'...'. $last_day_this_month
			)
		);
		if(!empty($orders)){
			$product_identifiers = array();
			foreach ( $orders as $order ) {
				$items = wc_get_order( $order )->get_items();
				foreach ( $items as $item ) {
					array_push( $product_identifiers, $item['product_id'] );
				};

			};
			$top_selling_products = array_count_values( $product_identifiers );
			arsort($top_selling_products);
			$size = count($top_selling_products);
			if($size > $limit){
				$top_selling_products = array_slice($top_selling_products,0,$limit,true);
			}
		}
		return $top_selling_products;
    }

	public function pdwchs_get_intelligence() {
		return isset( $this->intelligence ) ? $this->intelligence : null;
	}

}
