<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Tracks WooCommerce cart state into the pdwchs_carts table.
 *
 * @since 6.0.0
 */
class PDWCHS_Cart_Tracker {

	const CART_TRACKING_OPTION  = 'pdwchs_cart_tracking_enabled';
	const CART_CONTENTS_DEFAULT = '[]';

	public function __construct() {
		$this->pdwchs_register_hooks();
		add_action( 'pdwchs_register_pro_tables',  [ $this, 'pdwchs_on_register_tables' ] );
		add_action( 'pdwchs_register_pro_options', [ $this, 'pdwchs_on_register_options' ] );
	}

	/**
	 * Register all WooCommerce and WordPress hooks.
	 */
	public function pdwchs_register_hooks() {
		add_action( 'woocommerce_cart_updated',             array( $this, 'pdwchs_capture_cart' ) );
		add_action( 'woocommerce_add_to_cart',              array( $this, 'pdwchs_capture_cart' ) );
		add_action( 'woocommerce_cart_item_removed',        array( $this, 'pdwchs_capture_cart' ) );
		add_action( 'woocommerce_cart_emptied',             array( $this, 'pdwchs_capture_cart' ) );
		add_action( 'woocommerce_checkout_order_processed',              array( $this, 'pdwchs_mark_completed' ) );
		add_action( 'woocommerce_store_api_checkout_order_processed',    array( $this, 'pdwchs_mark_completed_blocks' ) );
		add_action( 'wp_login',                                          array( $this, 'pdwchs_merge_guest_cart_on_login' ), 10, 2 );
		add_filter( 'woocommerce_privacy_export_personal_data_exporters',   array( $this, 'pdwchs_register_privacy_exporter' ) );
		add_filter( 'woocommerce_privacy_erasure_applies_to_personal_data', array( $this, 'pdwchs_register_privacy_eraser' ) );
	}

	/**
	 * Upsert the current cart state into pdwchs_carts on every cart mutation.
	 */
	public function pdwchs_capture_cart() {
		if ( ! get_option( self::CART_TRACKING_OPTION, 1 ) ) {
			return;
		}

		if ( ! function_exists( 'WC' ) || is_null( WC()->cart ) ) {
			return;
		}

		$items_count = WC()->cart->get_cart_contents_count();
		if ( 0 === $items_count ) {
			return;
		}

		$cart_contents_json = wp_json_encode( WC()->cart->get_cart() );
		if ( false === $cart_contents_json ) {
			$cart_contents_json = self::CART_CONTENTS_DEFAULT;
		}

		$contents_hash = md5( $cart_contents_json );
		$existing      = $this->pdwchs_find_cart_row();

		if ( ! is_null( $existing ) && $existing->contents_hash === $contents_hash ) {
			return;
		}

		$user_id     = get_current_user_id();
		$session_key = WC()->session ? WC()->session->get_customer_id() : null;
		$email       = $user_id > 0 ? sanitize_email( wp_get_current_user()->user_email ) : null;
		$cart_total  = floatval( WC()->cart->get_cart_contents_total() );
		$cart_hash   = WC()->cart->get_cart_hash();
		$currency    = sanitize_text_field( get_woocommerce_currency() );
		$ip_address  = PD_Customer_History::pdwchs_get_client_ip();

		$user_agent = isset( $_SERVER['HTTP_USER_AGENT'] )
			? substr( sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ), 0, 255 )
			: null;

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_carts';

		if ( is_null( $existing ) ) {
			$wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$table,
				array(
					'user_id'       => $user_id > 0 ? $user_id : null,
					'session_key'   => $session_key,
					'email'         => $email,
					'status'        => 'active',
					'cart_contents' => $cart_contents_json,
					'contents_hash' => $contents_hash,
					'cart_hash'     => $cart_hash,
					'items_count'   => $items_count,
					'cart_total'    => $cart_total,
					'currency'      => $currency,
					'ip_address'    => $ip_address,
					'user_agent'    => $user_agent,
					// Feature 15.7 bridge: the PHP session that CREATED this cart row.
					// INSERT only — the UPDATE branch below deliberately never refreshes
					// identity columns, and the bridge depends on that.
					'php_session_id' => session_id(),
				),
				array( '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s', '%s' )
			);
		} else {
			$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				$table,
				array(
					'cart_contents' => $cart_contents_json,
					'contents_hash' => $contents_hash,
					'cart_hash'     => $cart_hash,
					'items_count'   => $items_count,
					'cart_total'    => $cart_total,
					'currency'      => $currency,
					'ip_address'    => $ip_address,
					'user_agent'    => $user_agent,
				),
				array( 'id' => $existing->id ),
				array( '%s', '%s', '%s', '%d', '%f', '%s', '%s', '%s' ),
				array( '%d' )
			);
		}
	}

	/**
	 * Transition the matching cart row to `completed` on checkout.
	 *
	 * @param int $order_id
	 */
	public function pdwchs_mark_completed( $order_id ) {
		$order_id = absint( $order_id );
		if ( 0 === $order_id ) {
			return;
		}

		$existing = $this->pdwchs_find_cart_row();
		if ( null === $existing ) {
			return;
		}

		if ( 'abandoned' === $existing->status ) {
			return; // recovery detector (priority 20) will flip this to 'recovered'
		}

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_carts';

		$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			$table,
			array(
				'status'       => 'completed',
				'completed_at' => current_time( 'mysql' ),
				'order_id'     => $order_id,
			),
			array( 'id' => $existing->id ),
			array( '%s', '%s', '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Blocks checkout adapter — extracts order ID and delegates to pdwchs_mark_completed().
	 *
	 * woocommerce_store_api_checkout_order_processed passes a WC_Order object, not an ID.
	 *
	 * @param WC_Order $order
	 */
	public function pdwchs_mark_completed_blocks( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$this->pdwchs_mark_completed( $order->get_id() );
	}

	/**
	 * Merge the guest cart row into the authenticated user's row on login.
	 *
	 * @param string  $user_login
	 * @param WP_User $user
	 */
	public function pdwchs_merge_guest_cart_on_login( $user_login, WP_User $user ) {
		$user_id = absint( $user->ID );
		if ( 0 === $user_id ) {
			return;
		}

		$session_key = WC()->session ? WC()->session->get_customer_id() : null;
		if ( empty( $session_key ) ) {
			return;
		}

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_carts';

		$guest_row = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT * FROM {$table} WHERE session_key = %s AND user_id IS NULL ORDER BY created_at ASC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$session_key
		) );

		if ( null === $guest_row ) {
			return;
		}

		$user_row = $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id
		) );

		if ( null === $user_row ) {
			$wpdb->update( $table, array( 'user_id' => $user_id ), array( 'id' => $guest_row->id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		} else {
			if ( $guest_row->updated_at > $user_row->updated_at ) {
				$wpdb->update( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
					$table,
					array(
						'cart_contents' => $guest_row->cart_contents,
						'contents_hash' => $guest_row->contents_hash,
						'items_count'   => $guest_row->items_count,
						'cart_total'    => $guest_row->cart_total,
					),
					array( 'id' => $user_row->id ),
					array( '%s', '%s', '%d', '%f' ),
					array( '%d' )
				);
				$wpdb->delete( $table, array( 'id' => $guest_row->id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			} else {
				$wpdb->delete( $table, array( 'id' => $guest_row->id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			}
			$wpdb->update( $table, array( 'user_id' => $user_id ), array( 'id' => $user_row->id ), array( '%d' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}
	}

	/**
	 * Register WC personal data exporter for cart rows.
	 *
	 * @param array $exporters
	 * @return array
	 */
	public function pdwchs_register_privacy_exporter( $exporters ) {
		$exporters['pdwchs-carts'] = array(
			'exporter_friendly_name' => __( 'PureDevs Cart History', 'puredevs-customer-history-for-woocommerce' ),
			'callback'               => array( $this, 'pdwchs_export_personal_data_by_email' ),
		);
		return $exporters;
	}

	/**
	 * Export cart rows for a given email address.
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

		$page = absint( $page );

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_carts';

		$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT * FROM {$table} WHERE email = %s LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$email_address, 10, ( $page - 1 ) * 10
		) );

		$items = array();
		foreach ( $rows as $row ) {
			$items[] = array(
				'group_id'    => 'customer-cart',
				'group_label' => __( 'Cart History', 'puredevs-customer-history-for-woocommerce' ),
				'item_id'     => 'cart-' . $row->id,
				'data'        => array(
					array( 'name' => __( 'Cart ID', 'puredevs-customer-history-for-woocommerce' ),       'value' => $row->id ),
					array( 'name' => __( 'Status', 'puredevs-customer-history-for-woocommerce' ),        'value' => $row->status ),
					array( 'name' => __( 'Cart Total', 'puredevs-customer-history-for-woocommerce' ),    'value' => $row->cart_total ),
					array( 'name' => __( 'Currency', 'puredevs-customer-history-for-woocommerce' ),      'value' => $row->currency ),
					array( 'name' => __( 'Items Count', 'puredevs-customer-history-for-woocommerce' ),   'value' => $row->items_count ),
					array( 'name' => __( 'Cart Contents', 'puredevs-customer-history-for-woocommerce' ), 'value' => $row->cart_contents ),
					array( 'name' => __( 'IP Address', 'puredevs-customer-history-for-woocommerce' ),    'value' => $row->ip_address ),
					array( 'name' => __( 'Created At', 'puredevs-customer-history-for-woocommerce' ),    'value' => $row->created_at ),
				),
			);
		}

		return array( 'data' => $items, 'done' => count( $rows ) < 10 );
	}

	/**
	 * Register WC personal data eraser for cart rows.
	 *
	 * @param array $erasers
	 * @return array
	 */
	public function pdwchs_register_privacy_eraser( $erasers ) {
		$erasers['pdwchs-carts'] = array(
			'eraser_friendly_name' => __( 'PureDevs Cart History', 'puredevs-customer-history-for-woocommerce' ),
			'callback'             => array( $this, 'pdwchs_erase_personal_data_by_email' ),
		);
		return $erasers;
	}

	/**
	 * Erase cart rows for a given email address.
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

		$page = absint( $page );

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_carts';

		$rows = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT id FROM {$table} WHERE email = %s LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$email_address, 10, ( $page - 1 ) * 10
		) );

		$count = count( $rows );

		foreach ( $rows as $row ) {
			$wpdb->delete( $table, array( 'id' => $row->id ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		}

		return array(
			'items_removed'  => $count > 0,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => $count < 10,
		);
	}

	/**
	 * Find the existing cart row for the current visitor.
	 * Logged-in: match by user_id. Guest: match by session_key where user_id IS NULL.
	 *
	 * @return object|null
	 */
	private function pdwchs_find_cart_row() {
		global $wpdb;
		$table   = $wpdb->prefix . 'pdwchs_carts';
		$user_id = get_current_user_id();

		if ( $user_id > 0 ) {
			return $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
				"SELECT * FROM {$table} WHERE user_id = %d ORDER BY created_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$user_id
			) );
		}

		$session_key = WC()->session ? WC()->session->get_customer_id() : null;
		if ( empty( $session_key ) ) {
			return null;
		}

		return $wpdb->get_row( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT * FROM {$table} WHERE session_key = %s AND user_id IS NULL ORDER BY created_at DESC LIMIT 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$session_key
		) );
	}

	/**
	 * H14 listener — create the carts table on activation / pro-boot.
	 */
	public function pdwchs_on_register_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$carts_table     = $wpdb->prefix . 'pdwchs_carts';
		$carts_sql       = "CREATE TABLE IF NOT EXISTS {$carts_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			user_id BIGINT UNSIGNED NULL,
			session_key VARCHAR(64) NULL,
			email VARCHAR(191) NULL,
			status ENUM('active','abandoned','recovered','completed') NOT NULL DEFAULT 'active',
			cart_contents LONGTEXT NOT NULL,
			contents_hash CHAR(32) NOT NULL,
			cart_hash VARCHAR(32) NULL,
			items_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
			cart_total DECIMAL(12,2) NOT NULL DEFAULT 0.00,
			currency CHAR(3) NOT NULL DEFAULT '',
			ip_address VARCHAR(45) NULL,
			user_agent VARCHAR(255) NULL,
			php_session_id VARCHAR(50) NULL,
			order_id BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			abandoned_at DATETIME NULL,
			recovered_at DATETIME NULL,
			completed_at DATETIME NULL,
			PRIMARY KEY (id),
			INDEX idx_status (status),
			INDEX idx_user_id (user_id),
			INDEX idx_session_key (session_key),
			INDEX idx_email (email),
			INDEX idx_status_updated (status, updated_at),
			INDEX idx_order_id (order_id),
			INDEX idx_php_session_id (php_session_id)
		) {$charset_collate};";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $carts_sql );
	}

	/**
	 * H15 listener — seed cart option defaults on activation / pro-boot.
	 */
	public function pdwchs_on_register_options() {
		add_option( 'pdwchs_cart_tracking_enabled',       1 );
		add_option( 'pdwchs_cart_email_capture_enabled',  1 );
		add_option( 'pdwchs_cart_abandonment_timeout',    60 );
		add_option( 'pdwchs_cart_cleanup_days',           90 );
	}
}
