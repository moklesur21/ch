<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Segments AJAX Coordinator
 *
 * Registers AJAX handlers for Saved Customer Segments and delegates
 * all data operations to PDWCHS_Segments_Manager.
 *
 * @link       https://puredevs.com/
 * @since      2.2.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes/segments
 */
class PDWCHS_Segments_Ajax {

	/**
	 * Segments manager instance.
	 *
	 * @since  2.2.0
	 * @access private
	 * @var    PDWCHS_Segments_Manager $segments_manager
	 */
	private $segments_manager;

	/**
	 * Constructor. Loads dependencies and initialises the coordinator.
	 *
	 * @since 2.2.0
	 */
	public function __construct() {
		$this->pdwchs_load_dependencies();
		$this->pdwchs_init();
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
	 * Instantiate dependencies and register hooks.
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_init() {
		$this->segments_manager = new PDWCHS_Segments_Manager();
		$this->pdwchs_register_hooks();
	}

	/**
	 * Register wp_ajax_ hooks for the three segment endpoints.
	 * No nopriv variants — admin-only feature.
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_register_hooks() {
		add_action( 'wp_ajax_pdwchs_save_segment',      array( $this, 'pdwchs_ajax_save_segment' ) );
		add_action( 'wp_ajax_pdwchs_delete_segment',    array( $this, 'pdwchs_ajax_delete_segment' ) );
		add_action( 'wp_ajax_pdwchs_get_segments_list', array( $this, 'pdwchs_ajax_get_segments_list' ) );
	}

	/**
	 * AJAX: create or update a segment (upsert via segment_id).
	 *
	 * POST params: nonce, segment_id (optional), segment_name, segment_description, criteria (JSON)
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_ajax_save_segment() {
		check_ajax_referer( 'pdwchs_segment_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$segment_id   = isset( $_POST['segment_id'] )         ? absint( $_POST['segment_id'] )                                        : 0;
		$name         = isset( $_POST['segment_name'] )        ? sanitize_text_field( wp_unslash( $_POST['segment_name'] ) )           : '';
		$description  = isset( $_POST['segment_description'] ) ? sanitize_textarea_field( wp_unslash( $_POST['segment_description'] ) ) : '';
		// Raw JSON string; sanitize_text_field would corrupt escaped \n/\t inside
		// quoted values. Decoded array is sanitized via map_deep() below, and the
		// manager additionally whitelists known keys before any DB or query use.
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$criteria_raw = isset( $_POST['criteria'] )            ? wp_unslash( $_POST['criteria'] )                                      : '';

		$criteria = json_decode( $criteria_raw, true );
		if ( ! is_array( $criteria ) || empty( $criteria ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid segment criteria.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}
		$criteria = map_deep( $criteria, 'sanitize_text_field' );

		if ( $segment_id > 0 ) {
			$result = $this->segments_manager->pdwchs_update_segment( $segment_id, array(
				'name'        => $name,
				'description' => $description,
				'criteria'    => $criteria,
			) );
		} else {
			$result = $this->segments_manager->pdwchs_create_segment( $name, $description, $criteria );
		}

		if ( is_wp_error( $result ) ) {
			$payload = array( 'message' => $result->get_error_message() );

			// Free segment-cap reached → return structured upsell data so the JS can
			// render a focused upgrade modal (advertisement only).
			if ( 'pdwchs_segment_cap_reached' === $result->get_error_code() ) {
				if ( ! class_exists( 'PDWCHS_Upsell' ) ) {
					require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/class-pdwchs-upsell.php';
				}
				$payload['upsell']       = true;
				$payload['upgrade_url']  = PDWCHS_Upsell::pdwchs_upgrade_url();
				$payload['pro_segments'] = array_values( wp_list_pluck( PDWCHS_Upsell::pdwchs_pro_segments(), 'title' ) );
				$payload['title']        = __( 'Unlock unlimited segments with Pro', 'puredevs-customer-history-for-woocommerce' );
				$payload['ready_label']  = __( 'Plus ready-made intelligence segments:', 'puredevs-customer-history-for-woocommerce' );
				$payload['cta']          = __( 'Upgrade to Pro', 'puredevs-customer-history-for-woocommerce' );
				$payload['dismiss']      = __( 'Maybe later', 'puredevs-customer-history-for-woocommerce' );
			}

			wp_send_json_error( $payload );
		}

		wp_send_json_success( array(
			'id'      => $segment_id > 0 ? $segment_id : $result,
			'message' => __( 'Segment saved.', 'puredevs-customer-history-for-woocommerce' ),
		) );
	}

	/**
	 * AJAX: delete a user-created segment.
	 *
	 * POST params: nonce, segment_id
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_ajax_delete_segment() {
		check_ajax_referer( 'pdwchs_segment_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$segment_id = isset( $_POST['segment_id'] ) ? absint( $_POST['segment_id'] ) : 0;
		if ( ! $segment_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid segment ID.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$result = $this->segments_manager->pdwchs_delete_segment( $segment_id );
		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'message' => __( 'Segment deleted.', 'puredevs-customer-history-for-woocommerce' ) ) );
	}

	/**
	 * AJAX: return all segments with live member counts.
	 *
	 * POST params: nonce
	 *
	 * @since 2.2.0
	 */
	public function pdwchs_ajax_get_segments_list() {
		check_ajax_referer( 'pdwchs_segment_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$segments = $this->segments_manager->pdwchs_get_segments();
		$data     = array();

		foreach ( $segments as $segment ) {
			$criteria     = json_decode( $segment->criteria, true );
			$criteria     = is_array( $criteria ) ? $criteria : array();
			$member_count = $this->segments_manager->pdwchs_count_segment_members( $criteria );

			$data[] = array(
				'id'           => absint( $segment->id ),
				'name'         => esc_html( $segment->name ),
				'description'  => esc_html( $segment->description ),
				'criteria'     => $criteria,
				'is_default'   => (bool) $segment->is_default,
				'member_count' => $member_count,
				'created_at'   => esc_html( $segment->created_at ),
			);
		}

		wp_send_json_success( $data );
	}
}
