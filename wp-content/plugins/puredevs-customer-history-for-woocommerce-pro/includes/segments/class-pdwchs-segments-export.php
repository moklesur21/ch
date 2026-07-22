<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Segments CSV Export Handler
 *
 * Handles the admin-post action for exporting a saved customer segment as a CSV file.
 *
 * @since      2.2.0
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes/segments
 */
class PDWCHS_Segments_Export {

	/**
	 * Constructor. Loads dependencies and registers hooks.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		$this->pdwchs_load_dependencies();
		$this->pdwchs_register_hooks();
	}

	/**
	 * Load required class files.
	 *
	 * @since  2.2.0
	 * @access private
	 */
	private function pdwchs_load_dependencies() {
		require_once plugin_dir_path( __FILE__ ) . 'class-pdwchs-segments-manager.php';
	}

	/**
	 * Register the admin-post hook for the CSV export action.
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_register_hooks() {
		add_action( 'admin_post_pdwchs_export_segment_csv', array( $this, 'pdwchs_handle_export' ) );
	}

	/**
	 * Handle the CSV export request. Streams the file to the browser.
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_handle_export() {
		check_admin_referer( 'pdwchs_export_segment_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$segment_id = isset( $_POST['segment_id'] ) ? absint( $_POST['segment_id'] ) : 0;
		if ( ! $segment_id ) {
			wp_die( esc_html__( 'Invalid segment.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$manager = new PDWCHS_Segments_Manager();
		$segment = $manager->pdwchs_get_segment( $segment_id );
		if ( ! $segment ) {
			wp_die( esc_html__( 'Segment not found.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$criteria = json_decode( $segment->criteria, true );
		$criteria = is_array( $criteria ) ? $criteria : array();
		$members  = $manager->pdwchs_get_segment_members( $criteria );

		global $wpdb;
		$user_ids   = array_map( fn( $u ) => $u->ID, $members );
		$table      = $wpdb->prefix . 'pdwchs_customer_intelligence';
		$extra_data = array();

		if ( ! empty( $user_ids ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $user_ids ), '%d' ) );
			$rows = $wpdb->get_results(
				$wpdb->prepare(
					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					"SELECT user_id, rfm_segment, health_score FROM {$table} WHERE user_id IN ({$placeholders})",
					...$user_ids
				)
			);
			foreach ( $rows as $row ) {
				$extra_data[ $row->user_id ] = $row;
			}
		}

		$slug     = sanitize_title( $segment->name );
		$date     = gmdate( 'Y-m-d' );
		$filename = "segment-{$slug}-{$date}.csv";

		ob_end_clean();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- output stream, not filesystem
		fputs( $output, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputs -- output stream for CSV BOM

		fputcsv( $output, array(
			__( 'Name', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Email', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Total Orders', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Total Spent', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Last Order Date', 'puredevs-customer-history-for-woocommerce' ),
			__( 'RFM Segment', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Health Score', 'puredevs-customer-history-for-woocommerce' ),
		) );

		foreach ( $members as $user ) {
			$uid         = $user->ID;
			$order_count = wc_get_customer_order_count( $uid );
			$total_spent = wc_get_customer_total_spent( $uid );

			$last_order      = wc_get_customer_last_order( $uid );
			$last_order_date = $last_order
				? $last_order->get_date_created()->date( 'Y-m-d' )
				: '';

			$intel        = isset( $extra_data[ $uid ] ) ? $extra_data[ $uid ] : null;
			$rfm_segment  = $intel ? sanitize_text_field( $intel->rfm_segment ) : '';
			$health_score = $intel ? (int) $intel->health_score : '';

			fputcsv( $output, array(
				$user->display_name,
				$user->user_email,
				$order_count,
				$total_spent,
				$last_order_date,
				$rfm_segment,
				$health_score,
			) );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- output stream handle
		exit;
	}
}
