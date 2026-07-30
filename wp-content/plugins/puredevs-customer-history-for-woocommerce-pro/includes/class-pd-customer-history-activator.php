<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin activation
 *
 * @link       https://puredevs.com/
 * @since      1.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Activator {

	/**
	 * Runs during activation of the plugin
	 *
	 * @since    1.0.0
	 */
	public static function pdwchs_activate() {
		if ( ! get_option( 'pd-woo-results_per_page' ) ) {
			update_option( 'pd-woo-results_per_page', 10 );
		}

		if ( ! get_option( 'pd-woo-hide_users_with_no_orders' ) ) {
			update_option( 'pd-woo-hide_users_with_no_orders', 0 );
		}
		
		if ( ! get_option( 'pd-woo-generate_weekly_report' ) ) {
			update_option( 'pd-woo-generate_weekly_report', array('order') );
		}

		do_action( 'pdwchs_register_pro_options' );

		do_action( 'pdwchs_register_pro_tables' );

		// Check and run version upgrade if needed
		self::pdwchs_check_version_upgrade();
	}

	/**
	 * Create saved segments table.
	 *
	 * @since 5.0.0
	 */
	private static function pdwchs_create_segments_table() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$segments_table  = $wpdb->prefix . 'pdwchs_segments';
		$segments_sql    = "CREATE TABLE IF NOT EXISTS {$segments_table} (
			id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
			name VARCHAR(191) NOT NULL,
			description TEXT NULL,
			criteria LONGTEXT NOT NULL,
			is_default TINYINT(1) NOT NULL DEFAULT 0,
			created_by BIGINT UNSIGNED NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY (id),
			INDEX idx_is_default (is_default),
			INDEX idx_created_by (created_by)
		) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $segments_sql );

		require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/segments/class-pdwchs-segments-manager.php';
		PDWCHS_Segments_Manager::pdwchs_seed_default_segments();

		update_option( 'pdwchs_db_version', '5.0' );
	}

	/**
	 * Check version and run upgrades if needed
	 *
	 * @since    2.0.0
	 */
	public static function pdwchs_check_version_upgrade() {
		$installed_version = get_option( 'pdwchs_version', '1.0' );
		$current_version = defined( 'PDWCHS_CUSTOMER_HISTORY_VERSION' ) ? PDWCHS_CUSTOMER_HISTORY_VERSION : '2.0';

		/*
		 * Stamp the first-ever activation time (set-once, never overwritten).
		 * This lives here rather than in pdwchs_activate() on purpose:
		 * register_activation_hook() does not fire on plugin update, and does not
		 * complete at all when WooCommerce is absent at activation time, so sites
		 * that upgraded into this feature would otherwise never get the option.
		 * This method runs on every load (main file, plugins_loaded priority 12).
		 */
		if ( ! get_option( 'pdwchs_first_activated_at' ) ) {
			add_option( 'pdwchs_first_activated_at', time() );
		}

		// Check database version separately to handle upgrades properly
		$db_version = get_option( 'pdwchs_db_version', '1.0' );

		// Run database upgrades if needed (check DB version, not plugin version)
		if ( version_compare( $db_version, '5.0', '<' ) ) {
			self::pdwchs_upgrade_to_v5();
		}

		// Heal any premium tables skipped when trial was not active at install time.
		self::pdwchs_ensure_premium_tables();

		// Update plugin version if changed
		if ( version_compare( $installed_version, $current_version, '<' ) ) {
			update_option( 'pdwchs_version', $current_version );
		}
	}

	/**
	 * Upgrade to v5.0 — Segments Manager
	 *
	 * @since 5.0.0
	 */
	private static function pdwchs_upgrade_to_v5() {
		self::pdwchs_create_segments_table();
	}

	/**
	 * Ensure Free-owned tables exist (sessions heal only).
	 * Pro tables are healed by PDWCHS_Pro_Installer.
	 *
	 * @since 2.1.7
	 */
	public static function pdwchs_ensure_premium_tables() {
		global $wpdb;

		$sessions_table = $wpdb->prefix . 'pd_woo_customer_history_sessions';
		if ( ! $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $sessions_table ) ) ) {
			require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/data/class-pdwchs-sessions-data.php';
			PDWCHS_Sessions_Data::pdwchs_create_table();
		}
	}

}
