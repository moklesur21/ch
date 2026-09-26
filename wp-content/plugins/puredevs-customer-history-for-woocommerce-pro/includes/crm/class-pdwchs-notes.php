<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customer Notes — CRUD, admin AJAX endpoints, and WP privacy exporter/eraser.
 *
 * Server side for Feature 15.3 notes (chunk R15-04B). Reads/writes
 * {$wpdb->prefix}pdwchs_customer_notes (created by R15-04A). No UI here —
 * R15-05 adds the panel listeners. Lives in the free tree and is
 * instantiated unconditionally on every build, licensed or not.
 *
 * @since 3.1.0
 */
class PDWCHS_Customer_Notes {

	public function __construct() {
		add_action( 'wp_ajax_pdwchs_add_customer_note',    array( $this, 'pdwchs_ajax_add_note' ) );
		add_action( 'wp_ajax_pdwchs_delete_customer_note', array( $this, 'pdwchs_ajax_delete_note' ) );
		add_filter( 'wp_privacy_personal_data_exporters',  array( $this, 'pdwchs_register_privacy_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers',    array( $this, 'pdwchs_register_privacy_eraser' ) );

		// Priority 5 renders the free notes panel above any Pro intelligence cards,
		// which hook the same seam at the default priority 10.
		add_action( 'pdwchs_customer_details_intelligence_cards', array( $this, 'pdwchs_render_notes_panel' ), 5 );
		add_action( 'pdwchs_enqueue_admin_styles',  array( $this, 'pdwchs_enqueue_styles' ) );   // H4
		add_action( 'pdwchs_enqueue_admin_scripts', array( $this, 'pdwchs_enqueue_scripts' ) );  // H5
	}

	// -------------------------------------------------------------------
	// UI — panel render + asset enqueues (R15-05)
	// -------------------------------------------------------------------

	/**
	 * Render the notes panel on the customer profile page.
	 *
	 * Hooked to the pdwchs_customer_details_intelligence_cards seam, which
	 * passes the sanitized-string $user_id from customer-details.php.
	 *
	 * @param int|string $user_id Customer user id.
	 */
	public function pdwchs_render_notes_panel( $user_id ) {
		$user_id = absint( $user_id );
		if ( 0 === $user_id ) {
			return;
		}

		$notes = $this->pdwchs_get_notes( $user_id );
		$count = $this->pdwchs_count_notes( $user_id );

		require PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/crm/templates/customer-notes-panel.php';
	}

	/**
	 * Enqueue the notes panel stylesheet on the customer profile page only.
	 */
	public function pdwchs_enqueue_styles() {
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'pd-woo-customer' !== $page ) {
			return;
		}

		wp_enqueue_style(
			'pdwchs-notes-css',
			PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL . 'admin/css/pdwchs-notes.css',
			array(),
			PDWCHS_CUSTOMER_HISTORY_VERSION
		);
	}

	/**
	 * Enqueue and localize the notes panel script on the customer profile page only.
	 */
	public function pdwchs_enqueue_scripts() {
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'pd-woo-customer' !== $page ) {
			return;
		}

		wp_enqueue_script(
			'pdwchs-notes-js',
			PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL . 'admin/js/pdwchs-notes.js',
			array( 'jquery' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION,
			true
		);
		wp_localize_script( 'pdwchs-notes-js', 'pdwchs_notes_vars', array(
			'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'pdwchs_crm_nonce' ),
			'customerId' => isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'i18n'       => array(
				'confirmDeleteNote' => __( 'Delete this note?', 'puredevs-customer-history-for-woocommerce' ),
				'emptyNote'         => __( 'Please enter a note first.', 'puredevs-customer-history-for-woocommerce' ),
				'errorFallback'     => __( 'Something went wrong. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
			),
		) );
	}

	// -------------------------------------------------------------------
	// CRUD
	// -------------------------------------------------------------------

	/**
	 * Add a note for a customer.
	 *
	 * @param int    $customer_id WP user the note is about.
	 * @param string $note_text   Note body (sanitized here; ≤5000 bytes after sanitization).
	 * @param int    $created_by  Authoring admin's user id.
	 * @return int|WP_Error New note id, or WP_Error on validation/DB failure.
	 */
	public function pdwchs_add_note( $customer_id, $note_text, $created_by ) {
		global $wpdb;

		$customer_id = absint( $customer_id );
		if ( false === get_userdata( $customer_id ) ) {
			return new WP_Error( 'pdwchs_note_invalid_customer', __( 'Invalid customer.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$note_text = sanitize_textarea_field( $note_text );
		if ( '' === trim( $note_text ) ) {
			return new WP_Error( 'pdwchs_note_empty', __( 'Note cannot be empty.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		if ( strlen( $note_text ) > 5000 ) {
			return new WP_Error( 'pdwchs_note_too_long', __( 'Note cannot exceed 5000 characters.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$created_by = absint( $created_by );

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'pdwchs_customer_notes',
			array(
				'customer_id' => $customer_id,
				'note_text'   => $note_text,
				'created_by'  => $created_by,
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%s', '%d', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error( 'pdwchs_note_db_error', __( 'Database error while saving note.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get a customer's notes, newest first (id DESC tiebreak keeps
	 * same-second notes in insertion order). Uses idx_customer_created.
	 *
	 * @param int $customer_id Customer user id.
	 * @param int $limit       Max rows (default 50).
	 * @return array Row objects.
	 */
	public function pdwchs_get_notes( $customer_id, $limit = 50 ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_customer_notes';

		return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT * FROM {$table} WHERE customer_id = %d ORDER BY created_at DESC, id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			absint( $customer_id ),
			absint( $limit )
		) );
	}

	/**
	 * Count a customer's notes.
	 *
	 * @param int $customer_id Customer user id.
	 * @return int
	 */
	public function pdwchs_count_notes( $customer_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_customer_notes';

		return (int) $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT COUNT(*) FROM {$table} WHERE customer_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			absint( $customer_id )
		) );
	}

	/**
	 * Delete a note by id.
	 *
	 * @param int $note_id Note id.
	 * @return true|WP_Error
	 */
	public function pdwchs_delete_note( $note_id ) {
		global $wpdb;
		$table   = $wpdb->prefix . 'pdwchs_customer_notes';
		$note_id = absint( $note_id );

		$exists = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id FROM {$table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$note_id
		) );

		if ( null === $exists ) {
			return new WP_Error( 'pdwchs_note_not_found', __( 'Note not found.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$wpdb->delete( $table, array( 'id' => $note_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		return true;
	}

	// -------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------

	/**
	 * AJAX: add a customer note.
	 *
	 * POST params: nonce, customer_id, note_text.
	 * created_by is always get_current_user_id() — never client-supplied.
	 */
	public function pdwchs_ajax_add_note() {
		check_ajax_referer( 'pdwchs_crm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
		$note_text   = isset( $_POST['note_text'] ) ? sanitize_textarea_field( wp_unslash( $_POST['note_text'] ) ) : '';

		$note_id = $this->pdwchs_add_note( $customer_id, $note_text, get_current_user_id() );

		if ( is_wp_error( $note_id ) ) {
			wp_send_json_error( array( 'message' => $note_id->get_error_message() ) );
		}

		$timestamp = current_time( 'timestamp' );

		// Render-ready contract consumed byte-for-byte by R2-04's JS — do not rename fields.
		wp_send_json_success( array(
			'id'           => $note_id,
			'note_text'    => $note_text,
			'author'       => wp_get_current_user()->display_name,
			'date_display' => date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp ),
			'time_ago'     => human_time_diff( $timestamp, current_time( 'timestamp' ) ),
			'count'        => $this->pdwchs_count_notes( $customer_id ),
		) );
	}

	/**
	 * AJAX: delete a customer note.
	 *
	 * POST params: nonce, note_id.
	 */
	public function pdwchs_ajax_delete_note() {
		check_ajax_referer( 'pdwchs_crm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$note_id = isset( $_POST['note_id'] ) ? absint( $_POST['note_id'] ) : 0;

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_customer_notes';

		// Look up the note first to learn its customer_id for the count.
		$note = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id, customer_id FROM {$table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$note_id
		) );

		if ( null === $note ) {
			wp_send_json_error( array( 'message' => __( 'Note not found.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$customer_id = (int) $note->customer_id;

		$result = $this->pdwchs_delete_note( $note_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array(
			'id'    => $note_id,
			'count' => $this->pdwchs_count_notes( $customer_id ),
		) );
	}

	// -------------------------------------------------------------------
	// Privacy — core WP exporter/eraser (NOT the WooCommerce variants)
	// -------------------------------------------------------------------

	/**
	 * Register the core WP personal data exporter for customer notes.
	 *
	 * @param array $exporters
	 * @return array
	 */
	public function pdwchs_register_privacy_exporter( $exporters ) {
		$exporters['pdwchs-customer-notes'] = array(
			'exporter_friendly_name' => __( 'Customer Notes', 'puredevs-customer-history-for-woocommerce' ),
			'callback'               => array( $this, 'pdwchs_export_personal_data_by_email' ),
		);
		return $exporters;
	}

	/**
	 * Export note rows for a given email address, 10 per page.
	 *
	 * @param string $email_address
	 * @param int    $page
	 * @return array
	 */
	public function pdwchs_export_personal_data_by_email( $email_address, $page ) {
		$email_address = sanitize_email( $email_address );
		if ( ! is_email( $email_address ) ) {
			return array( 'data' => array(), 'done' => true );
		}

		$user = get_user_by( 'email', $email_address );
		if ( false === $user ) {
			return array( 'data' => array(), 'done' => true );
		}

		$page = absint( $page );

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_customer_notes';

		$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT * FROM {$table} WHERE customer_id = %d LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user->ID, 10, ( $page - 1 ) * 10
		) );

		$items = array();
		foreach ( $rows as $row ) {
			$author      = get_userdata( $row->created_by );
			$author_name = $author
				? $author->display_name
				/* translators: %d: WP user id of the deleted note author. */
				: sprintf( __( 'Deleted user (#%d)', 'puredevs-customer-history-for-woocommerce' ), $row->created_by );

			$items[] = array(
				'group_id'    => 'pdwchs-customer-notes',
				'group_label' => __( 'Customer Notes', 'puredevs-customer-history-for-woocommerce' ),
				'item_id'     => "pdwchs-note-{$row->id}",
				'data'        => array(
					array( 'name' => __( 'Note', 'puredevs-customer-history-for-woocommerce' ),        'value' => $row->note_text ),
					array( 'name' => __( 'Date', 'puredevs-customer-history-for-woocommerce' ),        'value' => $row->created_at ),
					array( 'name' => __( 'Author name', 'puredevs-customer-history-for-woocommerce' ), 'value' => $author_name ),
				),
			);
		}

		return array( 'data' => $items, 'done' => count( $rows ) < 10 );
	}

	/**
	 * Register the core WP personal data eraser for customer notes.
	 *
	 * @param array $erasers
	 * @return array
	 */
	public function pdwchs_register_privacy_eraser( $erasers ) {
		$erasers['pdwchs-customer-notes'] = array(
			'eraser_friendly_name' => __( 'Customer Notes', 'puredevs-customer-history-for-woocommerce' ),
			'callback'             => array( $this, 'pdwchs_erase_personal_data_by_email' ),
		);
		return $erasers;
	}

	/**
	 * Erase a user's notes. Single-pass erase; row counts are small, done
	 * always true.
	 *
	 * @param string $email_address
	 * @param int    $page
	 * @return array
	 */
	public function pdwchs_erase_personal_data_by_email( $email_address, $page ) {
		$email_address = sanitize_email( $email_address );
		if ( ! is_email( $email_address ) ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}

		$user = get_user_by( 'email', $email_address );
		if ( false === $user ) {
			return array( 'items_removed' => false, 'items_retained' => false, 'messages' => array(), 'done' => true );
		}

		global $wpdb;

		$notes_removed = (int) $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'pdwchs_customer_notes',
			array( 'customer_id' => $user->ID ),
			array( '%d' )
		);

		return array(
			'items_removed'  => $notes_removed > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}
}
