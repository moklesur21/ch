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

		// R15-27B: preset keys are slugs, so sanitize_key() bounds the character set and the
		// registry lookup in pdwchs_get_export_columns() bounds the value set. An unknown or
		// tampered key resolves to 'default' rather than dying — a form bookmarked while licensed
		// and submitted after a lapse is a normal state, not an attack. The read sits after both
		// gates above, and the value is used only as a registry key: it never reaches the filename,
		// a header, fputcsv(), SQL or any echo.
		$preset = isset( $_POST['preset'] ) ? sanitize_key( wp_unslash( $_POST['preset'] ) ) : 'default';

		$columns = self::pdwchs_get_export_columns( $preset );
		$rows    = self::pdwchs_get_export_rows( $criteria, $preset );

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

		// array_values() because the map is keyed by field and the header row is the labels in order.
		fputcsv( $output, array_values( $columns ) );

		foreach ( $rows as $row ) {
			fputcsv( $output, $row );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- output stream handle
		exit;
	}

	/**
	 * The export column layouts available to the segment CSV export.
	 *
	 * Free registers exactly one, 'default', whose columns are the seven this export has always
	 * written. Pro adds vendor layouts on the filter; nothing else in the free build touches it.
	 *
	 * Static, and deliberately so: free constructs this class exactly once
	 * (class-pd-customer-history.php:649) and the constructor registers an admin_post action, so a
	 * second instance built merely to reach this method would make every manual export write its
	 * CSV twice.
	 *
	 * @since 3.1.2
	 *
	 * @return array Preset key => array( 'label' => string, 'columns' => array( field_key => header ) ).
	 */
	public static function pdwchs_get_export_presets() {
		$presets = array(
			'default' => array(
				'label'   => __( 'Standard', 'puredevs-customer-history-for-woocommerce' ),
				'columns' => array(
					'display_name'    => __( 'Name', 'puredevs-customer-history-for-woocommerce' ),
					'user_email'      => __( 'Email', 'puredevs-customer-history-for-woocommerce' ),
					'orders'          => __( 'Total Orders', 'puredevs-customer-history-for-woocommerce' ),
					'total_spent'     => __( 'Total Spent', 'puredevs-customer-history-for-woocommerce' ),
					'last_order_date' => __( 'Last Order Date', 'puredevs-customer-history-for-woocommerce' ),
					'rfm_segment'     => __( 'RFM Segment', 'puredevs-customer-history-for-woocommerce' ),
					'health_score'    => __( 'Health Score', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
		);

		/**
		 * Filter the available segment-export column layouts.
		 *
		 * A listener may add a preset. Removing or rewriting 'default' is not supported — it is the
		 * fallback every unknown key resolves to, and the layout the digest attachment uses.
		 *
		 * Every field key in a preset's columns must exist in the row bag built by
		 * pdwchs_get_export_rows(); an unknown key renders an empty cell rather than an error.
		 *
		 * @since 3.1.2
		 *
		 * @param array $presets Preset key => array( 'label' => string, 'columns' => array ).
		 */
		return apply_filters( 'pdwchs_segment_export_presets', $presets );
	}

	/**
	 * Resolve a preset key to its ordered field => header map.
	 *
	 * Unknown, empty and non-string keys all resolve to 'default', so a tampered or stale request
	 * degrades to the standard layout rather than erroring.
	 *
	 * Carries no nonce check and no capability check, deliberately: the caller owns the gates.
	 * pdwchs_handle_export() checks both before it gets here, and the digest attachment runs
	 * unattended in a cron where there is no request and no current user.
	 *
	 * @since 3.1.2
	 *
	 * @param string $preset Preset key. Defaults to 'default'.
	 * @return array Ordered field_key => header label.
	 */
	public static function pdwchs_get_export_columns( $preset = 'default' ) {
		$presets = self::pdwchs_get_export_presets();

		if ( ! is_string( $preset ) || ! isset( $presets[ $preset ]['columns'] ) || ! is_array( $presets[ $preset ]['columns'] ) ) {
			$preset = 'default';
		}

		// The second isset() is not redundant: it covers a listener that unset 'default' itself,
		// where returning an empty map produces an empty CSV rather than a fatal.
		return isset( $presets[ $preset ]['columns'] ) ? $presets[ $preset ]['columns'] : array();
	}

	/**
	 * Build the export rows for a set of segment criteria.
	 *
	 * Callable from any context, including a cron run: it verifies no nonce, checks no capability
	 * and emits nothing. The caller owns the gates.
	 *
	 * Segment membership is evaluated against the criteria handed in, which the manager whitelists
	 * itself, so this method does not.
	 *
	 * @since 3.1.2
	 *
	 * @param array  $criteria Segment criteria, as stored on the segment row.
	 * @param string $preset   Preset key. Defaults to 'default'.
	 * @return array List of rows, each an ordered list of scalar cell values matching
	 *               pdwchs_get_export_columns( $preset ).
	 */
	public static function pdwchs_get_export_rows( $criteria, $preset = 'default' ) {
		$criteria = is_array( $criteria ) ? $criteria : array();
		$columns  = self::pdwchs_get_export_columns( $preset );
		$fields   = array_keys( $columns );

		$manager = new PDWCHS_Segments_Manager();
		$members = $manager->pdwchs_get_segment_members( $criteria );

		$intel = self::pdwchs_get_intelligence_map( wp_list_pluck( $members, 'ID' ) );

		$rows = array();

		foreach ( $members as $user ) {
			$bag = self::pdwchs_build_field_bag( $user, $intel );
			$row = array();

			// A field key the bag does not carry yields an empty cell, not a notice: free cannot see
			// Pro's presets at build time.
			foreach ( $fields as $field ) {
				$row[] = isset( $bag[ $field ] ) ? $bag[ $field ] : '';
			}

			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * The per-member field bag every preset draws from.
	 *
	 * All nine values are built and the projection picks from them, which is what makes a preset
	 * free of queries: switching layouts never changes what is fetched.
	 *
	 * first_name and last_name are carried for presets that need them; no shipped preset does.
	 * WP_User resolves them from usermeta, which the member query's WP_User_Query has already
	 * primed in bulk, so they are not a per-row query.
	 *
	 * @since 3.1.2
	 *
	 * @param WP_User $user  The member.
	 * @param array   $intel Map of user_id => intelligence row.
	 * @return array Field key => scalar cell value.
	 */
	private static function pdwchs_build_field_bag( $user, $intel ) {
		$uid = $user->ID;

		$last_order      = wc_get_customer_last_order( $uid );
		$last_order_date = $last_order
			? $last_order->get_date_created()->date( 'Y-m-d' )
			: '';

		$row = isset( $intel[ $uid ] ) ? $intel[ $uid ] : null;

		return array(
			'display_name'    => $user->display_name,
			'user_email'      => $user->user_email,
			'first_name'      => $user->first_name,
			'last_name'       => $user->last_name,
			'orders'          => wc_get_customer_order_count( $uid ),
			'total_spent'     => wc_get_customer_total_spent( $uid ),
			'last_order_date' => $last_order_date,
			'rfm_segment'     => $row ? sanitize_text_field( $row->rfm_segment ) : '',
			'health_score'    => $row ? (int) $row->health_score : '',
		);
	}

	/**
	 * Bulk-read the intelligence columns for a set of member ids.
	 *
	 * The ids come from WP_User objects, never from the request. The empty guard stays: an IN ()
	 * with no placeholders is a SQL syntax error.
	 *
	 * @since 3.1.2
	 *
	 * @param array $user_ids Member ids.
	 * @return array Map of user_id => row object with rfm_segment and health_score.
	 */
	private static function pdwchs_get_intelligence_map( $user_ids ) {
		global $wpdb;

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

		return $extra_data;
	}
}
