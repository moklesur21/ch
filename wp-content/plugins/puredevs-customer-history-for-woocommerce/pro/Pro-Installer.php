<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PDWCHS_Pro_Installer {

	/** Pro activation: all Pro tables + options + crons. Runs pre-license. */
	public static function pdwchs_activate() {
		self::pdwchs_create_intelligence_tables(); // chains behavioral → churn → default settings
		self::pdwchs_create_carts_table();
		self::pdwchs_register_options();
		self::pdwchs_register_cron();
	}

	/** Mirror of the H15 listeners (Cart-Tracker options + Pro segment defaults). */
	public static function pdwchs_register_options() {
		// Mirrors PDWCHS_Cart_Tracker::pdwchs_on_register_options().
		add_option( 'pdwchs_cart_tracking_enabled',      1 );
		add_option( 'pdwchs_cart_email_capture_enabled', 1 );
		add_option( 'pdwchs_cart_abandonment_timeout',   60 );
		add_option( 'pdwchs_cart_cleanup_days',          90 );
		// Reuse the existing H15 seeder (idempotent).
		require_once __DIR__ . '/Segments/Pro-Defaults.php';
		$defaults = new PDWCHS_Pro_Segments_Defaults();
		$defaults->pdwchs_seed();
	}

	/** Mirror of the H16 listeners — schedule all Pro cron events. */
	public static function pdwchs_register_cron() {
		if ( ! wp_next_scheduled( 'pdwchs_daily_rfm_calculation' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 2:00 AM' ), 'daily', 'pdwchs_daily_rfm_calculation' );
		}
		if ( ! wp_next_scheduled( 'pdwchs_update_store_average' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 1:00 AM' ), 'daily', 'pdwchs_update_store_average' );
		}
		if ( ! wp_next_scheduled( 'pdwchs_daily_health_calculation' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 3:00 AM' ), 'daily', 'pdwchs_daily_health_calculation' );
		}
		if ( ! wp_next_scheduled( 'pdwchs_daily_lifecycle_calculation' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 4:00 AM' ), 'daily', 'pdwchs_daily_lifecycle_calculation' );
		}
		if ( ! wp_next_scheduled( 'pdwchs_daily_behavior_analysis' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 5:00 AM' ), 'daily', 'pdwchs_daily_behavior_analysis' );
		}
		if ( ! wp_next_scheduled( 'pdwchs_daily_churn_calculation' ) ) {
			wp_schedule_event( strtotime( 'tomorrow 2:00 AM' ), 'daily', 'pdwchs_daily_churn_calculation' );
		}
		if ( ! wp_next_scheduled( 'pdwchs_hourly_alert_check' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'hourly', 'pdwchs_hourly_alert_check' );
		}
		if ( ! wp_next_scheduled( 'pdwchs_weekly_churn_cleanup' ) ) {
			wp_schedule_event( strtotime( 'next sunday 3:00 AM' ), 'weekly', 'pdwchs_weekly_churn_cleanup' );
		}
		require_once __DIR__ . '/Carts/Cart-Cron.php';
		PDWCHS_Cart_Cron::pdwchs_schedule_events();
	}

	/** Clear every Pro cron (incl. the legacy analyze_customer_behavior hook). */
	public static function pdwchs_clear_cron() {
		$hooks = array(
			'pdwchs_daily_rfm_calculation',
			'pdwchs_update_store_average',
			'pdwchs_daily_health_calculation',
			'pdwchs_daily_lifecycle_calculation',
			'pdwchs_daily_behavior_analysis',
			'pdwchs_analyze_customer_behavior',
			'pdwchs_daily_churn_calculation',
			'pdwchs_hourly_alert_check',
			'pdwchs_weekly_churn_cleanup',
			'pdwchs_cart_abandonment_check',
			'pdwchs_cart_cleanup',
		);
		foreach ( $hooks as $hook ) {
			wp_clear_scheduled_hook( $hook );
		}
	}

	/** On-load healer: recreate any missing Pro table (cheap SHOW TABLES probes). */
	public static function pdwchs_check_version_upgrade() {
		global $wpdb;
		$probes = array(
			'pdwchs_customer_intelligence' => 'pdwchs_create_intelligence_tables',
			'pdwchs_behavioral_tags'       => 'pdwchs_create_behavioral_tables',
			'pdwchs_churn_scores'          => 'pdwchs_create_churn_tables',
			'pdwchs_carts'                 => 'pdwchs_create_carts_table',
			'pdwchs_customer_notes'        => 'pdwchs_create_crm_tables',
		);
		foreach ( $probes as $table => $method ) {
			$exists = $wpdb->get_var(
				$wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->prefix . $table )
			);
			if ( ! $exists ) {
				self::$method();
			}
		}
	}

	// ── five DDL methods appended below via sed (action 2) ──
	public static function pdwchs_create_intelligence_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Main intelligence table
		$intelligence_table = $wpdb->prefix . 'pdwchs_customer_intelligence';
		$intelligence_sql = "CREATE TABLE IF NOT EXISTS {$intelligence_table} (
			user_id BIGINT(20) UNSIGNED PRIMARY KEY,
			rfm_recency TINYINT UNSIGNED DEFAULT NULL COMMENT 'Score 0-5',
			rfm_frequency TINYINT UNSIGNED DEFAULT NULL COMMENT 'Score 0-5',
			rfm_monetary TINYINT UNSIGNED DEFAULT NULL COMMENT 'Score 0-5',
			rfm_segment VARCHAR(50) DEFAULT NULL COMMENT 'Segment name',
			rfm_calculated_at DATETIME DEFAULT NULL,
			health_score TINYINT UNSIGNED DEFAULT NULL COMMENT 'Score 0-100',
			health_recent_activity TINYINT UNSIGNED DEFAULT NULL COMMENT 'Score 0-40',
			health_frequency TINYINT UNSIGNED DEFAULT NULL COMMENT 'Score 0-30',
			health_spending_trend TINYINT UNSIGNED DEFAULT NULL COMMENT 'Score 0-20',
			health_engagement TINYINT UNSIGNED DEFAULT NULL COMMENT 'Score 0-10',
			health_previous_score TINYINT UNSIGNED DEFAULT NULL,
			health_trend ENUM('up', 'down', 'stable') DEFAULT NULL,
			health_calculated_at DATETIME DEFAULT NULL,
			lifecycle_stage VARCHAR(50) DEFAULT NULL COMMENT 'Prospect, New, Active, Loyal, At Risk, Churned',
			lifecycle_stage_date DATETIME DEFAULT NULL,
			lifecycle_previous_stage VARCHAR(50) DEFAULT NULL,
			lifecycle_days_in_stage INT DEFAULT NULL,
			lifecycle_calculated_at DATETIME DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_rfm_segment (rfm_segment),
			INDEX idx_health_score (health_score),
			INDEX idx_health_trend (health_score, health_trend),
			INDEX idx_lifecycle_stage (lifecycle_stage),
			INDEX idx_lifecycle_days (lifecycle_stage, lifecycle_days_in_stage),
			INDEX idx_rfm_health (rfm_segment, health_score),
			INDEX idx_stage_health (lifecycle_stage, health_score),
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
		) {$charset_collate};";

		// Lifecycle history table
		$lifecycle_table = $wpdb->prefix . 'pdwchs_lifecycle_history';
		$lifecycle_sql = "CREATE TABLE IF NOT EXISTS {$lifecycle_table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			stage VARCHAR(50) NOT NULL COMMENT 'Stage customer entered',
			previous_stage VARCHAR(50) DEFAULT NULL COMMENT 'Stage they came from',
			entered_date DATETIME NOT NULL COMMENT 'When they entered this stage',
			exited_date DATETIME DEFAULT NULL COMMENT 'When they left this stage',
			days_in_stage INT DEFAULT NULL COMMENT 'Total days spent in stage',
			INDEX idx_user_id (user_id),
			INDEX idx_stage (stage),
			INDEX idx_entered_date (entered_date),
			INDEX idx_user_stage (user_id, stage),
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
		) {$charset_collate};";

		// Use dbDelta for safe table creation/updates
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $intelligence_sql );
		dbDelta( $lifecycle_sql );

		// Store database version
		update_option( 'pdwchs_db_version', '2.0' );

		// Create behavioral tables (v3.0)
		self::pdwchs_create_behavioral_tables();
	}

	/**
	 * Create behavioral segmentation tables
	 *
	 * @since    3.0.0
	 */
	public static function pdwchs_create_behavioral_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Behavioral tags table - stores individual behavioral tags per customer
		$behavioral_tags_table = $wpdb->prefix . 'pdwchs_behavioral_tags';
		$behavioral_tags_sql = "CREATE TABLE IF NOT EXISTS {$behavioral_tags_table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			user_id BIGINT(20) UNSIGNED NOT NULL,
			behavior_type VARCHAR(50) NOT NULL COMMENT 'e.g. bargain_hunter, impulse_buyer',
			behavior_label VARCHAR(100) NOT NULL COMMENT 'Human-readable label',
			confidence_score TINYINT UNSIGNED DEFAULT 0 COMMENT 'Score 0-100',
			evidence_count INT UNSIGNED DEFAULT 0,
			first_detected DATETIME DEFAULT NULL,
			last_confirmed DATETIME DEFAULT NULL,
			metadata LONGTEXT DEFAULT NULL COMMENT 'JSON metadata',
			is_active TINYINT(1) DEFAULT 1,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY unique_user_behavior (user_id, behavior_type),
			INDEX idx_user_id (user_id),
			INDEX idx_behavior_type (behavior_type),
			INDEX idx_is_active (is_active),
			INDEX idx_confidence_score (confidence_score),
			INDEX idx_last_confirmed (last_confirmed),
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
		) {$charset_collate};";

		// Behavioral patterns cache table - fast filtering with boolean flags
		$behavioral_patterns_table = $wpdb->prefix . 'pdwchs_behavioral_patterns';
		$behavioral_patterns_sql = "CREATE TABLE IF NOT EXISTS {$behavioral_patterns_table} (
			user_id BIGINT(20) UNSIGNED PRIMARY KEY,
			is_bargain_hunter TINYINT(1) DEFAULT 0,
			is_impulse_buyer TINYINT(1) DEFAULT 0,
			is_brand_loyal TINYINT(1) DEFAULT 0,
			is_gift_shopper TINYINT(1) DEFAULT 0,
			is_weekend_shopper TINYINT(1) DEFAULT 0,
			is_night_owl TINYINT(1) DEFAULT 0,
			is_cart_abandoner TINYINT(1) DEFAULT 0,
			is_researcher TINYINT(1) DEFAULT 0,
			is_subscription_seeker TINYINT(1) DEFAULT 0,
			is_category_specialist TINYINT(1) DEFAULT 0,
			is_bulk_buyer TINYINT(1) DEFAULT 0,
			is_trendsetter TINYINT(1) DEFAULT 0,
			is_seasonal_shopper TINYINT(1) DEFAULT 0,
			is_mobile_shopper TINYINT(1) DEFAULT 0,
			is_desktop_shopper TINYINT(1) DEFAULT 0,
			preferred_categories LONGTEXT DEFAULT NULL COMMENT 'JSON top categories',
			preferred_brands LONGTEXT DEFAULT NULL COMMENT 'JSON top brands',
			preferred_price_range LONGTEXT DEFAULT NULL COMMENT 'JSON min/max/avg',
			preferred_shopping_times LONGTEXT DEFAULT NULL COMMENT 'JSON peak days/hours',
			avg_decision_time_hours DECIMAL(10,2) DEFAULT NULL,
			avg_items_per_order DECIMAL(10,2) DEFAULT NULL,
			coupon_usage_rate DECIMAL(5,2) DEFAULT NULL,
			cart_abandonment_rate DECIMAL(5,2) DEFAULT NULL,
			total_behavioral_tags TINYINT UNSIGNED DEFAULT 0,
			last_analyzed DATETIME DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_last_analyzed (last_analyzed),
			INDEX idx_total_tags (total_behavioral_tags),
			FOREIGN KEY (user_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
		) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $behavioral_tags_sql );
		dbDelta( $behavioral_patterns_sql );

		update_option( 'pdwchs_db_version', '3.0' );

		// Create churn prediction tables (v4.0)
		self::pdwchs_create_churn_tables();
	}

	/**
	 * Create churn prediction tables
	 *
	 * @since    4.0.0
	 */
	public static function pdwchs_create_churn_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Churn Scores Table - Primary storage for customer churn risk data
		$churn_scores_table = $wpdb->prefix . 'pdwchs_churn_scores';
		$churn_scores_sql = "CREATE TABLE IF NOT EXISTS {$churn_scores_table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			customer_id BIGINT(20) UNSIGNED NOT NULL,
			churn_score TINYINT UNSIGNED DEFAULT 0 COMMENT 'Overall score 0-100',
			risk_level VARCHAR(20) DEFAULT 'low' COMMENT 'low, medium, high, critical',
			recency_score TINYINT UNSIGNED DEFAULT 0 COMMENT 'Recency component 0-100',
			frequency_score TINYINT UNSIGNED DEFAULT 0 COMMENT 'Frequency component 0-100',
			monetary_score TINYINT UNSIGNED DEFAULT 0 COMMENT 'Monetary component 0-100',
			engagement_score TINYINT UNSIGNED DEFAULT 0 COMMENT 'Engagement component 0-100',
			days_since_last_order INT UNSIGNED DEFAULT 0,
			expected_next_purchase_days INT UNSIGNED DEFAULT 30,
			days_overdue INT DEFAULT 0 COMMENT 'Negative if not overdue',
			total_orders INT UNSIGNED DEFAULT 0,
			total_spent DECIMAL(12,2) DEFAULT 0.00,
			avg_order_value DECIMAL(10,2) DEFAULT 0.00,
			order_frequency_days DECIMAL(8,2) DEFAULT NULL COMMENT 'Avg days between orders',
			frequency_decline_pct DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Decline percentage',
			spending_decline_pct DECIMAL(5,2) DEFAULT 0.00 COMMENT 'Decline percentage',
			total_sessions INT UNSIGNED DEFAULT 0,
			sessions_last_30_days INT UNSIGNED DEFAULT 0,
			engagement_decline_pct DECIMAL(5,2) DEFAULT 0.00,
			cart_abandonment_rate DECIMAL(5,2) DEFAULT 0.00,
			prediction_confidence TINYINT UNSIGNED DEFAULT 50 COMMENT '0-100',
			primary_churn_indicator VARCHAR(50) DEFAULT NULL COMMENT 'Main reason for risk',
			alert_status VARCHAR(20) DEFAULT 'none' COMMENT 'none, pending, sent, resolved',
			alert_sent_at DATETIME DEFAULT NULL,
			retention_campaign_sent TINYINT(1) DEFAULT 0,
			last_calculated DATETIME DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			UNIQUE KEY unique_customer (customer_id),
			INDEX idx_churn_score (churn_score),
			INDEX idx_risk_level (risk_level),
			INDEX idx_risk_score (risk_level, churn_score),
			INDEX idx_alert_status (alert_status),
			INDEX idx_last_calculated (last_calculated),
			INDEX idx_days_overdue (days_overdue),
			FOREIGN KEY (customer_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
		) {$charset_collate};";

		// Churn History Table - Historical records of churn score changes
		$churn_history_table = $wpdb->prefix . 'pdwchs_churn_history';
		$churn_history_sql = "CREATE TABLE IF NOT EXISTS {$churn_history_table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			customer_id BIGINT(20) UNSIGNED NOT NULL,
			churn_score TINYINT UNSIGNED DEFAULT 0,
			previous_score TINYINT UNSIGNED DEFAULT NULL,
			risk_level VARCHAR(20) DEFAULT 'low',
			previous_risk_level VARCHAR(20) DEFAULT NULL,
			score_change INT DEFAULT 0 COMMENT 'Positive = worsening',
			customer_status VARCHAR(20) DEFAULT 'active' COMMENT 'active, churned, recovered',
			transition_type VARCHAR(30) DEFAULT NULL COMMENT 'escalated, de_escalated, churned, recovered',
			retention_action VARCHAR(50) DEFAULT NULL COMMENT 'Action taken',
			retention_success TINYINT(1) DEFAULT NULL COMMENT '1=success, 0=failed',
			notes TEXT DEFAULT NULL,
			recorded_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_customer_id (customer_id),
			INDEX idx_recorded_at (recorded_at),
			INDEX idx_customer_date (customer_id, recorded_at),
			INDEX idx_risk_level (risk_level),
			INDEX idx_customer_status (customer_status),
			FOREIGN KEY (customer_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
		) {$charset_collate};";

		// Churn Alerts Table - Alert management system
		$churn_alerts_table = $wpdb->prefix . 'pdwchs_churn_alerts';
		$churn_alerts_sql = "CREATE TABLE IF NOT EXISTS {$churn_alerts_table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			customer_id BIGINT(20) UNSIGNED NOT NULL,
			alert_type VARCHAR(30) NOT NULL COMMENT 'new_risk, escalated_risk, critical_risk',
			risk_level VARCHAR(20) NOT NULL,
			churn_score TINYINT UNSIGNED NOT NULL,
			priority TINYINT UNSIGNED DEFAULT 5 COMMENT '1-10, 10 highest',
			status VARCHAR(20) DEFAULT 'new' COMMENT 'new, viewed, in_progress, resolved, dismissed',
			title VARCHAR(255) NOT NULL,
			message TEXT DEFAULT NULL,
			recommended_actions TEXT DEFAULT NULL COMMENT 'JSON array of actions',
			customer_value DECIMAL(12,2) DEFAULT 0.00 COMMENT 'Total customer value',
			assigned_to BIGINT(20) UNSIGNED DEFAULT NULL COMMENT 'Admin user ID',
			resolution_notes TEXT DEFAULT NULL,
			resolved_at DATETIME DEFAULT NULL,
			email_sent TINYINT(1) DEFAULT 0,
			email_sent_at DATETIME DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_customer_id (customer_id),
			INDEX idx_alert_type (alert_type),
			INDEX idx_status (status),
			INDEX idx_priority (priority),
			INDEX idx_priority_status (priority DESC, status),
			INDEX idx_risk_level (risk_level),
			INDEX idx_assigned_to (assigned_to),
			INDEX idx_created_at (created_at),
			FOREIGN KEY (customer_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
		) {$charset_collate};";

		// Churn Settings Table - Configurable thresholds and weights
		$churn_settings_table = $wpdb->prefix . 'pdwchs_churn_settings';
		$churn_settings_sql = "CREATE TABLE IF NOT EXISTS {$churn_settings_table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			setting_key VARCHAR(100) NOT NULL UNIQUE,
			setting_value TEXT DEFAULT NULL,
			setting_type VARCHAR(20) DEFAULT 'string' COMMENT 'string, int, float, json, bool',
			description VARCHAR(255) DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_setting_key (setting_key)
		) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $churn_scores_sql );
		dbDelta( $churn_history_sql );
		dbDelta( $churn_alerts_sql );
		dbDelta( $churn_settings_sql );

		// Insert default settings
		self::pdwchs_insert_default_churn_settings();

		update_option( 'pdwchs_db_version', '4.0' );
	}

	/**
	 * Create abandoned carts table.
	 *
	 * @since 6.0.0
	 */
	public static function pdwchs_create_carts_table() {
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
			INDEX idx_order_id (order_id)
		) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $carts_sql );

		update_option( 'pdwchs_db_version', '6.0' );
	}

	/**
	 * Create CRM tables: customer notes, tags, customer↔tag junction.
	 *
	 * Creation order is fixed: notes → tags → junction. The junction table
	 * carries a FOREIGN KEY to pdwchs_tags, so pdwchs_tags must exist first.
	 *
	 * @since 3.1.0
	 */
	public static function pdwchs_create_crm_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$notes_table         = $wpdb->prefix . 'pdwchs_customer_notes';
		$tags_table          = $wpdb->prefix . 'pdwchs_tags';
		$customer_tags_table = $wpdb->prefix . 'pdwchs_customer_tags';

		$notes_sql = "CREATE TABLE IF NOT EXISTS {$notes_table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			customer_id BIGINT(20) UNSIGNED NOT NULL,
			note_text TEXT NOT NULL,
			created_by BIGINT(20) UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			INDEX idx_customer_created (customer_id, created_at),
			INDEX idx_created_by (created_by),
			FOREIGN KEY (customer_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE
		) {$charset_collate};";

		$tags_sql = "CREATE TABLE IF NOT EXISTS {$tags_table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			name VARCHAR(100) NOT NULL,
			slug VARCHAR(100) NOT NULL,
			created_by BIGINT(20) UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY uniq_slug (slug)
		) {$charset_collate};";

		$customer_tags_sql = "CREATE TABLE IF NOT EXISTS {$customer_tags_table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			customer_id BIGINT(20) UNSIGNED NOT NULL,
			tag_id BIGINT(20) UNSIGNED NOT NULL,
			tagged_by BIGINT(20) UNSIGNED NOT NULL,
			created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
			UNIQUE KEY uniq_customer_tag (customer_id, tag_id),
			INDEX idx_tag_id (tag_id),
			FOREIGN KEY (customer_id) REFERENCES {$wpdb->users}(ID) ON DELETE CASCADE,
			FOREIGN KEY (tag_id) REFERENCES {$tags_table}(id) ON DELETE CASCADE
		) {$charset_collate};";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $notes_sql );
		dbDelta( $tags_sql );
		dbDelta( $customer_tags_sql );

		update_option( 'pdwchs_db_version', '7.0' );
	}

	/**
	 * Insert default churn prediction settings
	 *
	 * @since    4.0.0
	 */
	public static function pdwchs_insert_default_churn_settings() {
		global $wpdb;

		$settings_table = $wpdb->prefix . 'pdwchs_churn_settings';

		$default_settings = array(
			// Risk level thresholds
			array( 'risk_threshold_low_max', '30', 'int', 'Maximum score for low risk' ),
			array( 'risk_threshold_medium_max', '60', 'int', 'Maximum score for medium risk' ),
			array( 'risk_threshold_high_max', '80', 'int', 'Maximum score for high risk' ),

			// Component weights (must sum to 100)
			array( 'weight_recency', '40', 'int', 'Weight for recency component' ),
			array( 'weight_frequency', '25', 'int', 'Weight for frequency component' ),
			array( 'weight_monetary', '20', 'int', 'Weight for monetary component' ),
			array( 'weight_engagement', '15', 'int', 'Weight for engagement component' ),

			// Recency thresholds (days)
			array( 'recency_excellent_max', '30', 'int', 'Max days for excellent recency' ),
			array( 'recency_good_max', '60', 'int', 'Max days for good recency' ),
			array( 'recency_fair_max', '90', 'int', 'Max days for fair recency' ),
			array( 'recency_critical_threshold', '180', 'int', 'Days after which customer is critical' ),

			// Frequency decline thresholds
			array( 'frequency_decline_warning', '25', 'int', 'Percentage decline for warning' ),
			array( 'frequency_decline_critical', '50', 'int', 'Percentage decline for critical' ),

			// Alert configuration
			array( 'alerts_enabled', '1', 'bool', 'Enable churn alerts' ),
			array( 'alert_for_high_risk', '1', 'bool', 'Send alerts for high risk' ),
			array( 'alert_for_critical_risk', '1', 'bool', 'Send alerts for critical risk' ),
			array( 'alert_email_recipient', '', 'string', 'Email for alert notifications' ),
			array( 'alert_batch_limit', '50', 'int', 'Max alerts per batch' ),

			// Calculation settings
			array( 'calculation_frequency', 'daily', 'string', 'How often to recalculate' ),
			array( 'min_orders_for_prediction', '2', 'int', 'Minimum orders needed' ),
			array( 'historical_window_days', '365', 'int', 'Days of history to analyze' ),
			array( 'confidence_threshold', '50', 'int', 'Min confidence for predictions' ),
		);

		foreach ( $default_settings as $setting ) {
			$wpdb->query( $wpdb->prepare(
				"INSERT IGNORE INTO {$settings_table} (setting_key, setting_value, setting_type, description) VALUES (%s, %s, %s, %s)",
				$setting[0], $setting[1], $setting[2], $setting[3]
			) );
		}
	}

	/**
	 * Check version and run upgrades if needed
	 *
	 * @since    2.0.0
	 */
}
