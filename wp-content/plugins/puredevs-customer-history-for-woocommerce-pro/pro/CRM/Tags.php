<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customer Tags — definition CRUD, assignment, admin AJAX endpoints, and the
 * pdwchs_tags_rev cache salt.
 *
 * Server side for Feature 15.10 manual tags (chunk R15-16B). Reads/writes
 * {$wpdb->prefix}pdwchs_tags and the {$wpdb->prefix}pdwchs_customer_tags
 * junction (created by R15-16A). No UI here — R15-16C/R15-17 add panels and JS.
 * Loaded only inside the license-gated bootstrap (pro/pro.php →
 * pdwchs_pro_init → PDWCHS_Pro::pdwchs_boot()), so no gating code lives in
 * this class.
 *
 * Also registers the core WP privacy exporter and eraser for tag assignments
 * under the pdwchs-customer-tags key.
 *
 * Visibility contract: read methods are public static so templates and R15-19
 * can fetch data without instantiating the class (a second instance would
 * double-register the AJAX hooks); mutations stay instance methods.
 *
 * @since 3.1.0
 */
class PDWCHS_Customer_Tags {

	public function __construct() {
		add_action( 'wp_ajax_pdwchs_create_tag',   array( $this, 'pdwchs_ajax_create_tag' ) );
		add_action( 'wp_ajax_pdwchs_assign_tag',   array( $this, 'pdwchs_ajax_assign_tag' ) );
		add_action( 'wp_ajax_pdwchs_unassign_tag', array( $this, 'pdwchs_ajax_unassign_tag' ) );
		add_action( 'wp_ajax_pdwchs_rename_tag',   array( $this, 'pdwchs_ajax_rename_tag' ) );
		add_action( 'wp_ajax_pdwchs_delete_tag',   array( $this, 'pdwchs_ajax_delete_tag' ) );

		add_filter( 'wp_privacy_personal_data_exporters', array( $this, 'pdwchs_register_privacy_exporter' ) );
		add_filter( 'wp_privacy_personal_data_erasers',   array( $this, 'pdwchs_register_privacy_eraser' ) );
	}

	// -------------------------------------------------------------------
	// Tag CRUD
	// -------------------------------------------------------------------

	/**
	 * Create a tag, or return the existing tag on a slug match.
	 *
	 * Slug identity is case-insensitive ("VIP" and "vip" are one tag); the
	 * stored display name keeps first-creation casing. A dedupe hit does NOT
	 * bump the rev salt (nothing changed).
	 *
	 * @param string $name       Tag display name (sanitized here; ≤100 chars).
	 * @param int    $created_by Authoring admin's user id.
	 * @return int|WP_Error Tag id (new or existing), or WP_Error on failure.
	 */
	public function pdwchs_create_tag( $name, $created_by ) {
		global $wpdb;
		$tags_table = $wpdb->prefix . 'pdwchs_tags';

		$name = sanitize_text_field( $name );
		if ( '' === trim( $name ) ) {
			return new WP_Error( 'pdwchs_tag_name_empty', __( 'Tag name cannot be empty.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		if ( mb_strlen( $name ) > 100 ) {
			return new WP_Error( 'pdwchs_tag_name_too_long', __( 'Tag name cannot exceed 100 characters.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$slug = sanitize_title( $name );
		if ( '' === $slug ) {
			return new WP_Error( 'pdwchs_tag_name_empty', __( 'Tag name cannot be empty.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$existing_id = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id FROM {$tags_table} WHERE slug = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$slug
		) );

		if ( null !== $existing_id ) {
			return (int) $existing_id;
		}

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$tags_table,
			array(
				'name'       => $name,
				'slug'       => $slug,
				'created_by' => absint( $created_by ),
				'created_at' => current_time( 'mysql' ),
			),
			array( '%s', '%s', '%d', '%s' )
		);

		if ( false === $result ) {
			return new WP_Error( 'pdwchs_tag_db_error', __( 'Database error while saving tag.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$this->pdwchs_bump_rev();

		return (int) $wpdb->insert_id;
	}

	/**
	 * Rename a tag (name + re-slug).
	 *
	 * @param int    $tag_id Tag id.
	 * @param string $name   New display name (validated exactly as create).
	 * @return true|WP_Error
	 */
	public function pdwchs_rename_tag( $tag_id, $name ) {
		global $wpdb;
		$tags_table = $wpdb->prefix . 'pdwchs_tags';

		$name = sanitize_text_field( $name );
		if ( '' === trim( $name ) ) {
			return new WP_Error( 'pdwchs_tag_name_empty', __( 'Tag name cannot be empty.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		if ( mb_strlen( $name ) > 100 ) {
			return new WP_Error( 'pdwchs_tag_name_too_long', __( 'Tag name cannot exceed 100 characters.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$slug = sanitize_title( $name );
		if ( '' === $slug ) {
			return new WP_Error( 'pdwchs_tag_name_empty', __( 'Tag name cannot be empty.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$tag_id = absint( $tag_id );

		$exists = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id FROM {$tags_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$tag_id
		) );

		if ( null === $exists ) {
			return new WP_Error( 'pdwchs_tag_not_found', __( 'Tag not found.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$slug_owner = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id FROM {$tags_table} WHERE slug = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$slug
		) );

		if ( null !== $slug_owner && (int) $slug_owner !== $tag_id ) {
			return new WP_Error( 'pdwchs_tag_slug_exists', __( 'A tag with that name already exists.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$tags_table,
			array(
				'name' => $name,
				'slug' => $slug,
			),
			array( 'id' => $tag_id ),
			array( '%s', '%s' ),
			array( '%d' )
		);

		$this->pdwchs_bump_rev();

		return true;
	}

	/**
	 * Delete a tag definition. Junction rows vanish via the FK cascade (R2-01).
	 *
	 * @param int $tag_id Tag id.
	 * @return true|WP_Error
	 */
	public function pdwchs_delete_tag( $tag_id ) {
		global $wpdb;
		$tags_table = $wpdb->prefix . 'pdwchs_tags';
		$tag_id     = absint( $tag_id );

		$exists = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id FROM {$tags_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$tag_id
		) );

		if ( null === $exists ) {
			return new WP_Error( 'pdwchs_tag_not_found', __( 'Tag not found.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$wpdb->delete( $tags_table, array( 'id' => $tag_id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching

		$this->pdwchs_bump_rev();

		return true;
	}

	/**
	 * Get all tag definitions, ordered by name.
	 *
	 * With counts: one LEFT JOIN COUNT query grouped by tag — no per-row
	 * queries. Internal table names only; no user input in this query.
	 *
	 * @param bool $with_counts Include customer_count per tag (default true).
	 * @return array Row objects { id, name, slug, created_by, created_at[, customer_count] }.
	 */
	public static function pdwchs_get_all_tags( $with_counts = true ) {
		global $wpdb;
		$tags_table     = $wpdb->prefix . 'pdwchs_tags';
		$junction_table = $wpdb->prefix . 'pdwchs_customer_tags';

		if ( $with_counts ) {
			return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT t.id, t.name, t.slug, t.created_by, t.created_at, COUNT(ct.id) AS customer_count FROM {$tags_table} t LEFT JOIN {$junction_table} ct ON ct.tag_id = t.id GROUP BY t.id ORDER BY t.name ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
			);
		}

		return $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id, name, slug, created_by, created_at FROM {$tags_table} ORDER BY name ASC" // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared
		);
	}

	// -------------------------------------------------------------------
	// Assignment
	// -------------------------------------------------------------------

	/**
	 * Assign a tag to a customer.
	 *
	 * Duplicate assignment is a graceful no-op returning true — the
	 * uniq_customer_tag key blocks the row at the DB level; the prepared
	 * existence check detects it first and skips the rev bump.
	 *
	 * @param int $customer_id WP user id of the customer.
	 * @param int $tag_id      Tag id.
	 * @param int $tagged_by   Assigning admin's user id.
	 * @return true|WP_Error
	 */
	public function pdwchs_assign_tag( $customer_id, $tag_id, $tagged_by ) {
		global $wpdb;
		$tags_table     = $wpdb->prefix . 'pdwchs_tags';
		$junction_table = $wpdb->prefix . 'pdwchs_customer_tags';

		$customer_id = absint( $customer_id );
		if ( false === get_userdata( $customer_id ) ) {
			return new WP_Error( 'pdwchs_customer_not_found', __( 'Invalid customer.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$tag_id = absint( $tag_id );

		$tag_exists = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id FROM {$tags_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$tag_id
		) );

		if ( null === $tag_exists ) {
			return new WP_Error( 'pdwchs_tag_not_found', __( 'Tag not found.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$already = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id FROM {$junction_table} WHERE customer_id = %d AND tag_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$customer_id,
			$tag_id
		) );

		if ( null !== $already ) {
			return true;
		}

		$result = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$junction_table,
			array(
				'customer_id' => $customer_id,
				'tag_id'      => $tag_id,
				'tagged_by'   => absint( $tagged_by ),
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%d', '%s' )
		);

		if ( false === $result ) {
			// A racing duplicate blocked by uniq_customer_tag — tolerate as a
			// no-op; the row exists either way. No rev bump.
			return true;
		}

		$this->pdwchs_bump_rev();

		return true;
	}

	/**
	 * Unassign a tag from a customer. Removing a non-existent assignment is a
	 * no-op true (and does not bump the rev salt).
	 *
	 * @param int $customer_id WP user id of the customer.
	 * @param int $tag_id      Tag id.
	 * @return true|WP_Error
	 */
	public function pdwchs_unassign_tag( $customer_id, $tag_id ) {
		global $wpdb;
		$junction_table = $wpdb->prefix . 'pdwchs_customer_tags';

		$removed = $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$junction_table,
			array(
				'customer_id' => absint( $customer_id ),
				'tag_id'      => absint( $tag_id ),
			),
			array( '%d', '%d' )
		);

		if ( $removed ) {
			$this->pdwchs_bump_rev();
		}

		return true;
	}

	/**
	 * Get a customer's tags, ordered by name.
	 *
	 * @param int $customer_id WP user id of the customer.
	 * @return array Row objects { id, name, slug }.
	 */
	public static function pdwchs_get_customer_tags( $customer_id ) {
		global $wpdb;
		$tags_table     = $wpdb->prefix . 'pdwchs_tags';
		$junction_table = $wpdb->prefix . 'pdwchs_customer_tags';

		return $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT t.id, t.name, t.slug FROM {$junction_table} ct INNER JOIN {$tags_table} t ON t.id = ct.tag_id WHERE ct.customer_id = %d ORDER BY t.name ASC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			absint( $customer_id )
		) );
	}

	/**
	 * Get all customer ids assigned a tag (uses idx_tag_id).
	 *
	 * Raw query, no caching here — R2-08 owns the pdwchs_tag_members_*
	 * transients.
	 *
	 * @param int $tag_id Tag id.
	 * @return int[]
	 */
	public static function pdwchs_get_customer_ids_by_tag( $tag_id ) {
		global $wpdb;
		$junction_table = $wpdb->prefix . 'pdwchs_customer_tags';

		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT customer_id FROM {$junction_table} WHERE tag_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			absint( $tag_id )
		) ) );
	}

	// -------------------------------------------------------------------
	// Privacy — core WP exporter/eraser (NOT the WooCommerce variants)
	// -------------------------------------------------------------------

	/**
	 * Register the core WP personal data exporter for tag assignments.
	 *
	 * @param array $exporters
	 * @return array
	 */
	public function pdwchs_register_privacy_exporter( $exporters ) {
		$exporters['pdwchs-customer-tags'] = array(
			'exporter_friendly_name' => __( 'Customer Tags', 'puredevs-customer-history-for-woocommerce' ),
			'callback'               => array( $this, 'pdwchs_export_personal_data_by_email' ),
		);
		return $exporters;
	}

	/**
	 * Export a customer's tag assignments for a given email address, 10 per page.
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
		$tags_table     = $wpdb->prefix . 'pdwchs_tags';
		$junction_table = $wpdb->prefix . 'pdwchs_customer_tags';

		$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT ct.id, ct.created_at, t.name, t.slug FROM {$junction_table} ct INNER JOIN {$tags_table} t ON t.id = ct.tag_id WHERE ct.customer_id = %d ORDER BY t.name ASC LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user->ID, 10, ( $page - 1 ) * 10
		) );

		$items = array();
		foreach ( $rows as $row ) {
			$items[] = array(
				'group_id'    => 'pdwchs-customer-tags',
				'group_label' => __( 'Customer Tags', 'puredevs-customer-history-for-woocommerce' ),
				'item_id'     => "pdwchs-customer-tag-{$row->id}",
				'data'        => array(
					array( 'name' => __( 'Tag', 'puredevs-customer-history-for-woocommerce' ),           'value' => $row->name ),
					array( 'name' => __( 'Slug', 'puredevs-customer-history-for-woocommerce' ),          'value' => $row->slug ),
					array( 'name' => __( 'Assigned date', 'puredevs-customer-history-for-woocommerce' ), 'value' => $row->created_at ),
				),
			);
		}

		return array( 'data' => $items, 'done' => count( $rows ) < 10 );
	}

	/**
	 * Register the core WP personal data eraser for tag assignments.
	 *
	 * @param array $erasers
	 * @return array
	 */
	public function pdwchs_register_privacy_eraser( $erasers ) {
		$erasers['pdwchs-customer-tags'] = array(
			'eraser_friendly_name' => __( 'Customer Tags', 'puredevs-customer-history-for-woocommerce' ),
			'callback'             => array( $this, 'pdwchs_erase_personal_data_by_email' ),
		);
		return $erasers;
	}

	/**
	 * Erase a user's tag assignments. Junction rows only — a tag definition is
	 * store vocabulary, not personal data, so pdwchs_tags is never touched.
	 * Single-pass erase; row counts are small, done always true.
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

		$rows_removed = (int) $wpdb->delete( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prefix . 'pdwchs_customer_tags',
			array( 'customer_id' => $user->ID ),
			array( '%d' )
		);

		if ( $rows_removed > 0 ) {
			$this->pdwchs_bump_rev();
		}

		return array(
			'items_removed'  => $rows_removed > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);
	}

	// -------------------------------------------------------------------
	// Rev salt
	// -------------------------------------------------------------------

	/**
	 * Rotate the tags cache salt. Called on every successful mutation —
	 * create (new row only), rename, delete, assign (new row only), unassign
	 * (row removed only). R2-08's transient key
	 * pdwchs_tag_members_{tag_id}_r{rev} embeds it, so any mutation rotates
	 * the key and stale id-sets die.
	 */
	private function pdwchs_bump_rev() {
		update_option( 'pdwchs_tags_rev', (int) get_option( 'pdwchs_tags_rev', 0 ) + 1 );
	}

	// -------------------------------------------------------------------
	// AJAX handlers
	// -------------------------------------------------------------------

	/**
	 * AJAX: create a tag.
	 *
	 * POST params: nonce, name.
	 * created_by is always get_current_user_id() — never client-supplied.
	 */
	public function pdwchs_ajax_create_tag() {
		check_ajax_referer( 'pdwchs_tags_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$name = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

		global $wpdb;
		$tags_table = $wpdb->prefix . 'pdwchs_tags';

		// Precheck so the response can say whether a new definition was made.
		$slug     = sanitize_title( $name );
		$existing = null;
		if ( '' !== $slug ) {
			$existing = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT id FROM {$tags_table} WHERE slug = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$slug
			) );
		}

		$tag_id = $this->pdwchs_create_tag( $name, get_current_user_id() );

		if ( is_wp_error( $tag_id ) ) {
			wp_send_json_error( array( 'message' => $tag_id->get_error_message() ) );
		}

		$tag = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id, name, slug FROM {$tags_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$tag_id
		) );

		if ( null === $tag ) {
			wp_send_json_error( array( 'message' => __( 'Tag not found.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		// Response contract consumed by R2-05/R2-06 JS — field names are frozen.
		// tag.name is the STORED display name (first-creation casing on a
		// dedupe hit, not the submitted one).
		wp_send_json_success( array(
			'tag'     => array(
				'id'   => (int) $tag->id,
				'name' => $tag->name,
				'slug' => $tag->slug,
			),
			'created' => ( null === $existing ),
		) );
	}

	/**
	 * AJAX: assign a tag to a customer.
	 *
	 * POST params: nonce, customer_id, plus EITHER tag_id OR tag_name
	 * (create-and-assign in one round trip: when tag_name is present it is
	 * resolved via pdwchs_create_tag() first, so dedupe rules hold).
	 * tagged_by is always get_current_user_id() — never client-supplied.
	 */
	public function pdwchs_ajax_assign_tag() {
		check_ajax_referer( 'pdwchs_tags_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
		$tag_id      = isset( $_POST['tag_id'] ) ? absint( $_POST['tag_id'] ) : 0;
		$tag_name    = isset( $_POST['tag_name'] ) ? sanitize_text_field( wp_unslash( $_POST['tag_name'] ) ) : '';

		global $wpdb;
		$tags_table     = $wpdb->prefix . 'pdwchs_tags';
		$junction_table = $wpdb->prefix . 'pdwchs_customer_tags';

		$was_new = false;
		if ( '' !== $tag_name ) {
			// Precheck so the response can say whether a new definition was made.
			$slug     = sanitize_title( $tag_name );
			$existing = null;
			if ( '' !== $slug ) {
				$existing = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					"SELECT id FROM {$tags_table} WHERE slug = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					$slug
				) );
			}

			$resolved = $this->pdwchs_create_tag( $tag_name, get_current_user_id() );

			if ( is_wp_error( $resolved ) ) {
				wp_send_json_error( array( 'message' => $resolved->get_error_message() ) );
			}

			$tag_id  = $resolved;
			$was_new = ( null === $existing );
		}

		// Precheck the pair so the response can flag a duplicate chip.
		$already = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id FROM {$junction_table} WHERE customer_id = %d AND tag_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$customer_id,
			$tag_id
		) );

		$result = $this->pdwchs_assign_tag( $customer_id, $tag_id, get_current_user_id() );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		$tag = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id, name, slug FROM {$tags_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$tag_id
		) );

		if ( null === $tag ) {
			wp_send_json_error( array( 'message' => __( 'Tag not found.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		// Response contract consumed by R2-05 JS — field names are frozen.
		wp_send_json_success( array(
			'tag'              => array(
				'id'   => (int) $tag->id,
				'name' => $tag->name,
				'slug' => $tag->slug,
			),
			'created'          => $was_new,
			'already_assigned' => ( null !== $already ),
		) );
	}

	/**
	 * AJAX: unassign a tag from a customer.
	 *
	 * POST params: nonce, customer_id, tag_id.
	 */
	public function pdwchs_ajax_unassign_tag() {
		check_ajax_referer( 'pdwchs_tags_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$customer_id = isset( $_POST['customer_id'] ) ? absint( $_POST['customer_id'] ) : 0;
		$tag_id      = isset( $_POST['tag_id'] ) ? absint( $_POST['tag_id'] ) : 0;

		$result = $this->pdwchs_unassign_tag( $customer_id, $tag_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'tag_id' => $tag_id ) );
	}

	/**
	 * AJAX: rename a tag.
	 *
	 * POST params: nonce, tag_id, name.
	 */
	public function pdwchs_ajax_rename_tag() {
		check_ajax_referer( 'pdwchs_tags_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$tag_id = isset( $_POST['tag_id'] ) ? absint( $_POST['tag_id'] ) : 0;
		$name   = isset( $_POST['name'] ) ? sanitize_text_field( wp_unslash( $_POST['name'] ) ) : '';

		$result = $this->pdwchs_rename_tag( $tag_id, $name );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		global $wpdb;
		$tags_table = $wpdb->prefix . 'pdwchs_tags';

		$tag = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id, name, slug FROM {$tags_table} WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$tag_id
		) );

		if ( null === $tag ) {
			wp_send_json_error( array( 'message' => __( 'Tag not found.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		wp_send_json_success( array(
			'tag' => array(
				'id'   => (int) $tag->id,
				'name' => $tag->name,
				'slug' => $tag->slug,
			),
		) );
	}

	/**
	 * AJAX: delete a tag.
	 *
	 * POST params: nonce, tag_id.
	 */
	public function pdwchs_ajax_delete_tag() {
		check_ajax_referer( 'pdwchs_tags_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$tag_id = isset( $_POST['tag_id'] ) ? absint( $_POST['tag_id'] ) : 0;

		$result = $this->pdwchs_delete_tag( $tag_id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ) );
		}

		wp_send_json_success( array( 'tag_id' => $tag_id ) );
	}
}
