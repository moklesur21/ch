<?php
/**
 * Customer Intelligence Coordinator
 *
 * Coordinates RFM analysis, Health Scores, Lifecycle tracking,
 * and Behavioral Segmentation. Handles AJAX requests and scheduling.
 *
 * @link       https://puredevs.com/
 * @since      2.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * Customer Intelligence Coordinator Class
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Intelligence {

	/**
	 * RFM Calculator instance
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      PD_Customer_History_RFM    $rfm_calculator    RFM calculator
	 */
	private $rfm_calculator;

	/**
	 * Health Score Calculator instance
	 *
	 * @since    2.0.0
	 * @access   private
	 * @var      PD_Customer_History_Health    $health_calculator    Health calculator
	 */
	private $health_calculator;

	/**
	 * Lifecycle Calculator instance
	 *
	 * @since    2.1.0
	 * @access   private
	 * @var      PD_Customer_History_Lifecycle    $lifecycle_calculator    Lifecycle calculator
	 */
	private $lifecycle_calculator;

	/**
	 * Behavioral Analyzer instance
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      PD_Customer_History_Behavioral    $behavioral_analyzer    Behavioral analyzer
	 */
	private $behavioral_analyzer;

	/**
	 * Churn Calculator instance
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      PD_Customer_History_Churn    $churn_calculator    Churn calculator
	 */
	private $churn_calculator;

	/**
	 * Churn Alerts Manager instance
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      PD_Customer_History_Churn_Alerts    $alerts_manager    Alerts manager
	 */
	private $alerts_manager;

	/**
	 * Churn Predictor instance
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      PD_Customer_History_Churn_Predictor    $churn_predictor    Churn predictor
	 */
	private $churn_predictor;

	/**
	 * Churn Engagement Metrics instance
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      PD_Customer_History_Churn_Engagement    $churn_engagement    Engagement metrics
	 */
	private $churn_engagement;

	/**
	 * Churn Cache Manager instance
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      PD_Customer_History_Churn_Cache    $churn_cache    Cache manager
	 */
	private $churn_cache;

	/**
	 * Initialize the intelligence system
	 *
	 * @since    2.0.0
	 */
	public function __construct() {
		$this->pdwchs_load_dependencies();
		$this->pdwchs_init();
		add_action( 'pdwchs_register_pro_tables', array( $this, 'pdwchs_register_tables' ) );
		add_action( 'pdwchs_register_pro_cron',   array( $this, 'pdwchs_register_cron' ) );
		add_filter( 'pdwchs_weekly_report_extra_sheets', array( $this, 'pdwchs_weekly_extra_sheets' ), 10, 3 );
		add_action( 'pdwchs_admin_menu_late',                         array( $this, 'pdwchs_register_churn_menu' ) );
		add_action( 'pdwchs_dashboard_widgets',                       array( $this, 'pdwchs_render_dashboard_widgets' ) );
		add_action( 'pdwchs_customer_details_intelligence_cards',     array( $this, 'pdwchs_render_intelligence_cards' ) );
		add_action( 'pdwchs_enqueue_admin_styles',                    array( $this, 'pdwchs_enqueue_styles' ) );
		add_action( 'pdwchs_enqueue_admin_scripts',                   array( $this, 'pdwchs_enqueue_scripts' ) );
		add_action( 'pdwchs_customers_filters_extra',                 array( $this, 'pdwchs_render_customer_filters' ) );
		add_filter( 'pdwchs_customers_query_args',                    array( $this, 'pdwchs_apply_intelligence_query_args' ), 10, 2 );
		add_filter( 'pdwchs_customers_column_rfm_segment',            array( $this, 'pdwchs_rfm_column_badge' ), 10, 2 );
		add_filter( 'pdwchs_customers_column_behavioral',             array( $this, 'pdwchs_behavioral_column_override' ), 10, 2 );
	}

	/**
	 * Load required dependencies
	 *
	 * @since    2.0.0
	 */
	private function pdwchs_load_dependencies() {
		require_once __DIR__ . '/Segments-Labels.php';
		require_once __DIR__ . '/RFM.php';
		require_once __DIR__ . '/Health.php';
		require_once __DIR__ . '/Lifecycle.php';
		require_once __DIR__ . '/Behavioral.php';
		require_once __DIR__ . '/Behavioral-Insights.php';
		require_once __DIR__ . '/Churn-Cache.php';
		require_once __DIR__ . '/Churn-Engagement.php';
		require_once __DIR__ . '/Churn-Predictor.php';
		require_once __DIR__ . '/Churn.php';
		require_once __DIR__ . '/Churn-Alerts.php';
	}

	/**
	 * Initialize intelligence components
	 *
	 * @since    2.0.0
	 */
	public function pdwchs_init() {
		$this->rfm_calculator = new PD_Customer_History_RFM();
		$this->health_calculator = new PD_Customer_History_Health();
		$this->lifecycle_calculator = new PD_Customer_History_Lifecycle();
		$this->behavioral_analyzer = new PD_Customer_History_Behavioral();
		$this->churn_calculator = new PD_Customer_History_Churn();
		$this->alerts_manager = new PD_Customer_History_Churn_Alerts( $this->churn_calculator );
		$this->churn_predictor = new PD_Customer_History_Churn_Predictor( $this->churn_calculator, $this->rfm_calculator );
		$this->churn_engagement = new PD_Customer_History_Churn_Engagement();
		$this->churn_cache = new PD_Customer_History_Churn_Cache();
		$this->pdwchs_register_hooks();
	}

	/**
	 * Register WordPress hooks
	 *
	 * @since    2.0.0
	 */
	public function pdwchs_register_hooks() {
		// Order completion hook
		add_action( 'woocommerce_order_status_completed', array( $this, 'pdwchs_on_order_completed' ) );

		// RFM Cron hooks
		add_action( 'pdwchs_daily_rfm_calculation', array( $this->rfm_calculator, 'pdwchs_batch_calculate_all' ) );
		add_action( 'pdwchs_update_store_average', array( $this->rfm_calculator, 'pdwchs_update_store_average_cache' ) );

		// Health Score Cron hooks
		add_action( 'pdwchs_daily_health_calculation', array( $this->health_calculator, 'pdwchs_batch_calculate_all_health' ) );

		// RFM AJAX hooks
		add_action( 'wp_ajax_pdwchs_get_rfm_distribution', array( $this, 'pdwchs_ajax_get_rfm_distribution' ) );
		add_action( 'wp_ajax_pdwchs_recalculate_rfm', array( $this, 'pdwchs_ajax_recalculate_rfm' ) );
		add_action( 'wp_ajax_pdwchs_trigger_batch_calculation', array( $this, 'pdwchs_ajax_trigger_batch_calculation' ) );

		// Health Score AJAX hooks
		add_action( 'wp_ajax_pdwchs_get_health_distribution', array( $this, 'pdwchs_ajax_get_health_distribution' ) );
		add_action( 'wp_ajax_pdwchs_get_declining_health', array( $this, 'pdwchs_ajax_get_declining_health' ) );
		add_action( 'wp_ajax_pdwchs_recalculate_health', array( $this, 'pdwchs_ajax_recalculate_health' ) );

		// Lifecycle Cron hooks
		add_action( 'pdwchs_daily_lifecycle_calculation', array( $this->lifecycle_calculator, 'pdwchs_batch_calculate_all' ) );

		// Lifecycle AJAX hooks
		add_action( 'wp_ajax_pdwchs_get_lifecycle_funnel', array( $this, 'pdwchs_ajax_get_lifecycle_funnel' ) );
		add_action( 'wp_ajax_pdwchs_get_at_risk_alert', array( $this, 'pdwchs_ajax_get_at_risk_alert' ) );
		add_action( 'wp_ajax_pdwchs_recalculate_lifecycle', array( $this, 'pdwchs_ajax_recalculate_lifecycle' ) );

		// Behavioral Segmentation Cron hooks
		add_action( 'pdwchs_daily_behavior_analysis', array( $this->behavioral_analyzer, 'pdwchs_analyze_all_behaviors' ) );
		add_action( 'pdwchs_analyze_customer_behavior', array( $this->behavioral_analyzer, 'pdwchs_analyze_single_customer' ) );

		// Behavioral Segmentation AJAX hooks
		add_action( 'wp_ajax_pdwchs_analyze_behavior', array( $this->behavioral_analyzer, 'pdwchs_ajax_manual_behavior_analysis' ) );
		add_action( 'wp_ajax_pdwchs_behavioral_segments_data', array( $this->behavioral_analyzer, 'pdwchs_ajax_behavioral_segments_data' ) );
		add_action( 'wp_ajax_pdwchs_filter_by_behavior', array( $this->behavioral_analyzer, 'pdwchs_ajax_filter_by_behavior' ) );

		// Churn Prediction Cron hooks
		add_action( 'pdwchs_daily_churn_calculation', array( $this->churn_calculator, 'pdwchs_batch_calculate_all' ) );
		add_action( 'pdwchs_hourly_alert_check', array( $this->alerts_manager, 'pdwchs_generate_alerts' ) );

		// Churn Prediction AJAX hooks
		add_action( 'wp_ajax_pdwchs_get_churn_distribution', array( $this, 'pdwchs_ajax_get_churn_distribution' ) );
		add_action( 'wp_ajax_pdwchs_recalculate_churn', array( $this, 'pdwchs_ajax_recalculate_churn' ) );
		add_action( 'wp_ajax_pdwchs_refresh_churn_widget', array( $this, 'pdwchs_ajax_refresh_churn_widget' ) );
		add_action( 'wp_ajax_pdwchs_update_alert_status', array( $this, 'pdwchs_ajax_update_alert_status' ) );
		add_action( 'wp_ajax_pdwchs_assign_alert', array( $this, 'pdwchs_ajax_assign_alert' ) );

		// Churn Prediction Page AJAX hooks
		add_action( 'wp_ajax_pdwchs_save_churn_settings', array( $this, 'pdwchs_ajax_save_churn_settings' ) );
		add_action( 'wp_ajax_pdwchs_get_at_risk_customers', array( $this, 'pdwchs_ajax_get_at_risk_customers' ) );
		add_action( 'wp_ajax_pdwchs_get_churn_alerts', array( $this, 'pdwchs_ajax_get_churn_alerts' ) );

		// Weekly cleanup cron
		add_action( 'pdwchs_weekly_churn_cleanup', array( $this, 'pdwchs_run_weekly_cleanup' ) );
	}

	/**
	 * H14 listener — create all Intelligence DB tables on plugin activation.
	 *
	 * @since 2.1.7
	 */
	public function pdwchs_register_tables() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		// pdwchs_customer_intelligence
		$intelligence_table = $wpdb->prefix . 'pdwchs_customer_intelligence';
		dbDelta( "CREATE TABLE IF NOT EXISTS {$intelligence_table} (
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
		) {$charset_collate};" );

		// pdwchs_lifecycle_history
		$lifecycle_table = $wpdb->prefix . 'pdwchs_lifecycle_history';
		dbDelta( "CREATE TABLE IF NOT EXISTS {$lifecycle_table} (
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
		) {$charset_collate};" );

		// pdwchs_behavioral_tags
		$behavioral_tags_table = $wpdb->prefix . 'pdwchs_behavioral_tags';
		dbDelta( "CREATE TABLE IF NOT EXISTS {$behavioral_tags_table} (
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
		) {$charset_collate};" );

		// pdwchs_behavioral_patterns
		$behavioral_patterns_table = $wpdb->prefix . 'pdwchs_behavioral_patterns';
		dbDelta( "CREATE TABLE IF NOT EXISTS {$behavioral_patterns_table} (
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
		) {$charset_collate};" );

		// pdwchs_churn_scores
		// Note: the retention_campaign_sent column is deprecated as of 3.0.2 (the retention
		// coupon/win-back email actions were removed). Retained because dropping it needs a
		// DB migration; nothing reads or writes it any more.
		$churn_scores_table = $wpdb->prefix . 'pdwchs_churn_scores';
		dbDelta( "CREATE TABLE IF NOT EXISTS {$churn_scores_table} (
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
		) {$charset_collate};" );

		// pdwchs_churn_history
		$churn_history_table = $wpdb->prefix . 'pdwchs_churn_history';
		dbDelta( "CREATE TABLE IF NOT EXISTS {$churn_history_table} (
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
		) {$charset_collate};" );

		// pdwchs_churn_alerts
		$churn_alerts_table = $wpdb->prefix . 'pdwchs_churn_alerts';
		dbDelta( "CREATE TABLE IF NOT EXISTS {$churn_alerts_table} (
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
		) {$charset_collate};" );

		// pdwchs_churn_settings
		$churn_settings_table = $wpdb->prefix . 'pdwchs_churn_settings';
		dbDelta( "CREATE TABLE IF NOT EXISTS {$churn_settings_table} (
			id BIGINT(20) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
			setting_key VARCHAR(100) NOT NULL UNIQUE,
			setting_value TEXT DEFAULT NULL,
			setting_type VARCHAR(20) DEFAULT 'string' COMMENT 'string, int, float, json, bool',
			description VARCHAR(255) DEFAULT NULL,
			created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
			updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			INDEX idx_setting_key (setting_key)
		) {$charset_collate};" );
	}

	/**
	 * H16 listener — schedule all Intelligence cron events on plugin activation.
	 *
	 * @since 2.1.7
	 */
	public function pdwchs_register_cron() {
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
	}

	/**
	 * H3 listener — register Churn Prediction submenu page.
	 *
	 * @since 2.1.7
	 * @param string $capability WordPress capability string.
	 */
	public function pdwchs_register_churn_menu( $capability ) {
		add_submenu_page(
			'pd-woo-dashboard',
			__( 'Churn Prediction', 'puredevs-customer-history-for-woocommerce' ),
			__( '<span id="pd-churn-prediction-menu">Churn Prediction</span>', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			'pd-woo-churn-prediction',
			array( $this, 'pdwchs_churn_prediction_page' )
		);
	}

	/**
	 * Render Churn Prediction admin page.
	 *
	 * @since 2.1.7
	 */
	public function pdwchs_churn_prediction_page() {
		require __DIR__ . '/templates/churn-prediction.php';
	}

	/**
	 * H6 listener — render all 6 Intelligence dashboard widgets.
	 *
	 * @since 2.1.7
	 */
	public function pdwchs_render_dashboard_widgets() {
		require __DIR__ . '/templates/rfm-dashboard-widget.php';
		require __DIR__ . '/templates/health-dashboard-widget.php';
		require __DIR__ . '/templates/lifecycle-funnel-widget.php';
		require __DIR__ . '/templates/at-risk-alert-widget.php';
		require __DIR__ . '/templates/behavioral-dashboard-widget.php';
		require __DIR__ . '/templates/churn-dashboard-widget.php';
	}

	/**
	 * H8 listener — render all 5 Intelligence cards on customer detail page.
	 *
	 * @since 2.1.7
	 * @param int $user_id WordPress user ID.
	 */
	public function pdwchs_render_intelligence_cards( $user_id ) {
		require __DIR__ . '/templates/customer-rfm-card.php';
		require __DIR__ . '/templates/customer-health-card.php';
		require __DIR__ . '/templates/customer-lifecycle-card.php';
		require __DIR__ . '/templates/customer-behavioral-card.php';
		require __DIR__ . '/templates/customer-churn-card.php';
	}

	/**
	 * H4 — Enqueue Intelligence CSS on relevant admin pages.
	 *
	 * @since 2.1.7
	 */
	public function pdwchs_enqueue_styles() {
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$page        = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$admin_url   = PDWCHS_PRO_PLUGIN_URL . 'admin/css/';

		if ( in_array( $page, array( 'pd-woo-dashboard', 'pd-woo-customer' ), true ) ) {
			wp_enqueue_style( 'pdwchs-health-css', $admin_url . 'pd-customer-history-health.css', array(), time(), 'all' );
		}

		if ( in_array( $page, array( 'pd-woo-dashboard', 'pd-woo-customers', 'pd-woo-customer' ), true ) ) {
			wp_enqueue_style( 'pdwchs-lifecycle-css', $admin_url . 'pd-customer-history-lifecycle.css', array(), time(), 'all' );
			wp_enqueue_style( 'pdwchs-behavioral-css', $admin_url . 'pd-customer-history-behavioral.css', array(), time(), 'all' );
		}

		if ( in_array( $page, array( 'pd-woo-dashboard', 'pd-woo-customers', 'pd-woo-customer', 'pd-woo-churn-prediction' ), true ) ) {
			wp_enqueue_style( 'pdwchs-churn-css', $admin_url . 'pd-customer-history-churn.css', array(), time(), 'all' );
		}
	}

	/**
	 * H5 — Enqueue Intelligence JS on relevant admin pages.
	 *
	 * @since 2.1.7
	 */
	public function pdwchs_enqueue_scripts() {
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$page      = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$admin_url = PDWCHS_PRO_PLUGIN_URL . 'admin/js/';

		// RFM JS — dashboard + customer detail
		if ( in_array( $page, array( 'pd-woo-dashboard', 'pd-woo-customer' ), true ) ) {
			wp_enqueue_script( 'chartjs', PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL . 'admin/js/chart.umd.js', array(), '4.4.1', true );
			wp_enqueue_script( 'pdwchs-rfm-js', $admin_url . 'pd-customer-history-rfm.js', array( 'jquery', 'chartjs' ), time(), true );
			$rfm_vars = array(
				'nonce'             => wp_create_nonce( 'pdwchs_rfm_nonce' ),
				'ajaxurl'           => admin_url( 'admin-ajax.php' ),
				'no_data_text'      => __( 'No customer data available yet. RFM analysis will appear once customers start making purchases.', 'puredevs-customer-history-for-woocommerce' ),
				'calculating_text'  => __( 'Calculating...', 'puredevs-customer-history-for-woocommerce' ),
			);
			if ( $page === 'pd-woo-customer' ) {
				unset( $rfm_vars['no_data_text'] );
			}
			wp_localize_script( 'pdwchs-rfm-js', 'pdwchs_rfm_vars', $rfm_vars );
		}

		// Health JS — dashboard + customer detail
		if ( in_array( $page, array( 'pd-woo-dashboard', 'pd-woo-customer' ), true ) ) {
			wp_enqueue_script( 'pdwchs-health-js', $admin_url . 'pd-customer-history-health.js', array( 'jquery' ), time(), true );
			$health_vars = array(
				'nonce'          => wp_create_nonce( 'pdwchs_health_nonce' ),
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'calculating_text' => __( 'Calculating...', 'puredevs-customer-history-for-woocommerce' ),
				'error_text'     => __( 'Error loading data. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
			);
			if ( $page === 'pd-woo-dashboard' ) {
				$health_vars['no_data_text']            = __( 'No customer health data available yet.', 'puredevs-customer-history-for-woocommerce' );
				$health_vars['no_declining_text']       = __( 'No customers with declining health at this time.', 'puredevs-customer-history-for-woocommerce' );
				$health_vars['critical_customers_text'] = __( 'customers in Critical range', 'puredevs-customer-history-for-woocommerce' );
				$health_vars['points_text']             = __( 'points', 'puredevs-customer-history-for-woocommerce' );
				$health_vars['last_order_text']         = __( 'Last order:', 'puredevs-customer-history-for-woocommerce' );
				$health_vars['days_ago_text']           = __( 'days ago', 'puredevs-customer-history-for-woocommerce' );
				$health_vars['view_profile_text']       = __( 'View Profile', 'puredevs-customer-history-for-woocommerce' );
			}
			wp_localize_script( 'pdwchs-health-js', 'pdwchs_health_vars', $health_vars );
		}

		// Lifecycle JS — dashboard + customers + customer detail
		if ( in_array( $page, array( 'pd-woo-dashboard', 'pd-woo-customers', 'pd-woo-customer' ), true ) ) {
			wp_enqueue_script( 'pdwchs-lifecycle-js', $admin_url . 'pd-customer-history-lifecycle.js', array( 'jquery' ), time(), true );
			wp_localize_script( 'pdwchs-lifecycle-js', 'pdwchs_lifecycle_vars', array(
				'nonce'   => wp_create_nonce( 'pdwchs_lifecycle_nonce' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			) );
		}

		// Behavioral JS — dashboard + customers + customer detail
		if ( in_array( $page, array( 'pd-woo-dashboard', 'pd-woo-customers', 'pd-woo-customer' ), true ) ) {
			wp_enqueue_script( 'pdwchs-behavioral-js', $admin_url . 'pd-customer-history-behavioral.js', array( 'jquery' ), time(), true );
			wp_localize_script( 'pdwchs-behavioral-js', 'pdwchs_behavioral_vars', array(
				'nonce'   => wp_create_nonce( 'pdwchs_behavioral_nonce' ),
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
			) );
		}

		// Churn JS — dashboard + customers + customer detail + churn prediction page
		if ( in_array( $page, array( 'pd-woo-dashboard', 'pd-woo-customers', 'pd-woo-customer', 'pd-woo-churn-prediction' ), true ) ) {
			if ( $page === 'pd-woo-churn-prediction' ) {
				wp_enqueue_script( 'chartjs', PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL . 'admin/js/chart.umd.js', array(), '4.4.1', true );
			}
			wp_enqueue_script( 'pdwchs-churn-js', $admin_url . 'pd-customer-history-churn.js', array( 'jquery' ), time(), true );
			wp_localize_script( 'pdwchs-churn-js', 'pdwchs_churn_vars', array(
				'nonce'                 => wp_create_nonce( 'pdwchs_churn_nonce' ),
				'ajaxurl'               => admin_url( 'admin-ajax.php' ),
				'error_text'            => __( 'An error occurred. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
				'settings_saved_text'   => __( 'Settings saved successfully.', 'puredevs-customer-history-for-woocommerce' ),
				'is_prediction_page'    => ( 'pd-woo-churn-prediction' === $page ),
			) );
		}
	}

	/**
	 * H9 — Render Intelligence filter form groups on the customers list page.
	 *
	 * Outputs the four filter rows (RFM, Health Score, Health Trend, Behavior)
	 * using the same .pd-wcch-form-group markup as the rest of the Advanced Search form.
	 *
	 * @since 2.1.7
	 */
	public function pdwchs_render_customer_filters() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$rfm_selected      = isset( $_GET['rfm_segment'] )      ? sanitize_text_field( wp_unslash( $_GET['rfm_segment'] ) )      : '';
		$health_selected   = isset( $_GET['health_score_range'] ) ? sanitize_text_field( wp_unslash( $_GET['health_score_range'] ) ) : '';
		$trend_selected    = isset( $_GET['health_trend'] )     ? sanitize_text_field( wp_unslash( $_GET['health_trend'] ) )     : '';
		$behavior_selected = isset( $_GET['behavior_filter'] )  ? sanitize_text_field( wp_unslash( $_GET['behavior_filter'] ) )  : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		// --- RFM Segment ---
		$segments = PD_Customer_History_Segments::pdwchs_get_segments();
		?>
		<div class="pd-wcch-form-group">
			<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'RFM Segment :', 'puredevs-customer-history-for-woocommerce' ); ?></label>
			<select name="rfm_segment" id="rfm_segment" class="pd-wcch-select pd-wcch-inline-block" style="min-width: 300px;">
				<option value="">-- <?php echo esc_html__( 'All Segments', 'puredevs-customer-history-for-woocommerce' ); ?> --</option>
				<?php foreach ( $segments as $slug => $config ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $rfm_selected, $slug ); ?>>
						<?php echo esc_html( $config['icon'] . ' ' . $config['name'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<div class="pd-wcch-inpu-wrapper d-none">
				<button type="button" class="pd-wcch-btn pd-wcch-inline-block pd-wcch-close button btn">&#10005;</button>
			</div>
		</div>

		<?php // --- Health Score Range --- ?>
		<div class="pd-wcch-form-group">
			<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Health Score :', 'puredevs-customer-history-for-woocommerce' ); ?></label>
			<select name="health_score_range" id="health_score_range" class="pd-wcch-select pd-wcch-inline-block" style="min-width: 300px;">
				<option value="">-- <?php echo esc_html__( 'All Scores', 'puredevs-customer-history-for-woocommerce' ); ?> --</option>
				<?php
				$health_ranges = array(
					'excellent' => '💚 ' . __( 'Excellent (81-100)', 'puredevs-customer-history-for-woocommerce' ),
					'good'      => '💛 ' . __( 'Good (61-80)', 'puredevs-customer-history-for-woocommerce' ),
					'fair'      => '🟡 ' . __( 'Fair (41-60)', 'puredevs-customer-history-for-woocommerce' ),
					'poor'      => '🟠 ' . __( 'Poor (21-40)', 'puredevs-customer-history-for-woocommerce' ),
					'critical'  => '🔴 ' . __( 'Critical (0-20)', 'puredevs-customer-history-for-woocommerce' ),
				);
				foreach ( $health_ranges as $value => $label ) :
					?>
					<option value="<?php echo esc_attr( $value ); ?>" <?php selected( $health_selected, $value ); ?>>
						<?php echo esc_html( $label ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<div class="pd-wcch-inpu-wrapper d-none">
				<button type="button" class="pd-wcch-btn pd-wcch-inline-block pd-wcch-close button btn">&#10005;</button>
			</div>
		</div>

		<?php // --- Health Trend --- ?>
		<div class="pd-wcch-form-group">
			<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Health Trend :', 'puredevs-customer-history-for-woocommerce' ); ?></label>
			<select name="health_trend" id="health_trend" class="pd-wcch-select pd-wcch-inline-block" style="min-width: 300px;">
				<option value="">-- <?php echo esc_html__( 'All Trends', 'puredevs-customer-history-for-woocommerce' ); ?> --</option>
				<option value="up"     <?php selected( $trend_selected, 'up' ); ?>><?php echo esc_html__( '↗ Improving', 'puredevs-customer-history-for-woocommerce' ); ?></option>
				<option value="stable" <?php selected( $trend_selected, 'stable' ); ?>><?php echo esc_html__( '→ Stable', 'puredevs-customer-history-for-woocommerce' ); ?></option>
				<option value="down"   <?php selected( $trend_selected, 'down' ); ?>><?php echo esc_html__( '↘ Declining', 'puredevs-customer-history-for-woocommerce' ); ?></option>
			</select>
			<div class="pd-wcch-inpu-wrapper d-none">
				<button type="button" class="pd-wcch-btn pd-wcch-inline-block pd-wcch-close button btn">&#10005;</button>
			</div>
		</div>

		<?php // --- Behavioral Filter --- ?>
		<div class="pd-wcch-form-group">
			<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Behavior :', 'puredevs-customer-history-for-woocommerce' ); ?></label>
			<select name="behavior_filter" id="behavior_filter" class="pd-wcch-select pd-wcch-inline-block" style="min-width: 300px;">
				<option value="">-- <?php echo esc_html__( 'All Behaviors', 'puredevs-customer-history-for-woocommerce' ); ?> --</option>
				<?php foreach ( $this->behavioral_analyzer->get_behavior_types() as $slug => $config ) : ?>
					<option value="<?php echo esc_attr( $slug ); ?>" <?php selected( $behavior_selected, $slug ); ?>>
						<?php echo esc_html( $config['label'] ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<div class="pd-wcch-inpu-wrapper d-none">
				<button type="button" class="pd-wcch-btn pd-wcch-inline-block pd-wcch-close button btn">&#10005;</button>
			</div>
		</div>
		<?php
	}

	/**
	 * H11 — Seam for augmenting the customer query args with intelligence filters.
	 *
	 * PHP-level filtering in customers.php already handles these params; this
	 * method is a forward-compatible seam for future SQL-level optimization.
	 *
	 * @since 2.1.7
	 * @param mixed $args The current customer query args.
	 * @param array $get  The $_GET superglobal snapshot.
	 * @return mixed
	 */
	public function pdwchs_apply_intelligence_query_args( $args, $get ) {
		return $args;
	}

	/**
	 * H12 — Return the RFM segment badge HTML for a customer list column.
	 *
	 * @since 2.1.7
	 * @param string $default Empty default value (Free-safe).
	 * @param int    $user_id WordPress user ID.
	 * @return string Badge HTML.
	 */
	public function pdwchs_rfm_column_badge( $default, $user_id ) {
		global $wpdb;
		$table        = $wpdb->prefix . 'pdwchs_customer_intelligence';
		$segment_slug = $wpdb->get_var( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT rfm_segment FROM {$table} WHERE user_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id
		) );
		if ( ! $segment_slug ) {
			return $default;
		}
		return PD_Customer_History_Segments::pdwchs_get_segment_badge( $segment_slug, 'small' );
	}

	/**
	 * H13 — Return behavioral column badge HTML, avoiding bare instantiation of Behavioral class.
	 *
	 * @since 2.1.7
	 * @param mixed $override Null (default) or pre-built HTML.
	 * @param int   $user_id  WordPress user ID.
	 * @return string|null Badge HTML or null to fall through to default rendering.
	 */
	public function pdwchs_behavioral_column_override( $override, $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_behavioral_tags';
		$tags  = $wpdb->get_results( $wpdb->prepare( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT behavior_type, behavior_label, confidence_score FROM {$table} WHERE user_id = %d AND is_active = 1 ORDER BY confidence_score DESC LIMIT 4", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$user_id
		) );

		if ( empty( $tags ) ) {
			return '<span style="color: #94a3b8;">--</span>';
		}

		$behavior_types = $this->behavioral_analyzer->get_behavior_types();
		$html           = '<div class="pdwchs-behavior-column-tags">';
		$shown          = 0;
		$total          = count( $tags );

		foreach ( $tags as $tag ) {
			if ( $shown >= 3 ) {
				$html .= '<span class="pdwchs-behavior-more">+' . intval( $total - $shown ) . '</span>';
				break;
			}
			$icon  = isset( $behavior_types[ $tag->behavior_type ] ) ? $behavior_types[ $tag->behavior_type ]['icon'] : 'dashicons-tag';
			$short = array(
				'Bargain Hunter' => 'Bargain', 'Impulse Buyer' => 'Impulse', 'Brand Loyal' => 'Brand',
				'Gift Shopper' => 'Gift', 'Weekend Shopper' => 'Weekend', 'Night Owl' => 'Night',
				'Cart Abandoner' => 'Abandoner', 'Researcher' => 'Research', 'Subscription Seeker' => 'Subscr.',
				'Category Specialist' => 'Category', 'Bulk Buyer' => 'Bulk', 'Trendsetter' => 'Trend',
				'Seasonal Shopper' => 'Seasonal', 'Mobile Shopper' => 'Mobile', 'Desktop Shopper' => 'Desktop',
			);
			$label = isset( $short[ $tag->behavior_label ] ) ? $short[ $tag->behavior_label ] : $tag->behavior_label;
			$html .= sprintf(
				'<span class="pdwchs-behavior-mini-tag" title="%s (%d%%)"><span class="dashicons %s"></span>%s</span>',
				esc_attr( $tag->behavior_label ),
				intval( $tag->confidence_score ),
				esc_attr( $icon ),
				esc_html( $label )
			);
			$shown++;
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * Handle order completion
	 *
	 * @since    2.0.0
	 * @param    int    $order_id    WooCommerce order ID
	 */
	public function pdwchs_on_order_completed( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$customer_id = $order->get_customer_id();
		if ( ! $customer_id ) {
			return;
		}

		// Recalculate RFM for this customer
		$this->rfm_calculator->pdwchs_calculate_customer_rfm( $customer_id );

		// Recalculate Health Score for this customer
		$this->health_calculator->pdwchs_calculate_customer_health( $customer_id );

		// Recalculate Lifecycle Stage for this customer
		$this->lifecycle_calculator->pdwchs_calculate_customer_lifecycle( $customer_id );

		// Trigger behavioral analysis (delayed via cron)
		$this->behavioral_analyzer->pdwchs_on_order_completed( $order_id );

		// Invalidate churn cache for this customer
		$this->churn_cache->invalidate_customer_cache( $customer_id );

		// Capture risk level BEFORE recalculation so we can detect recovery
		$pre_recalc = $this->churn_calculator->pdwchs_get_customer_churn_data( $customer_id );
		$was_high_risk = $pre_recalc && in_array( $pre_recalc->risk_level, array( 'high', 'critical' ), true );

		// Recalculate Churn Score for this customer (may improve score)
		$this->churn_calculator->pdwchs_calculate_churn_score( $customer_id );

		// Mark customer as recovered if they previously had high churn risk
		if ( $was_high_risk ) {
			$this->alerts_manager->pdwchs_mark_customer_recovered( $customer_id, 'Completed purchase' );
		}
	}

	/**
	 * AJAX: Get RFM distribution data
	 *
	 * @since    2.0.0
	 */
	public function pdwchs_ajax_get_rfm_distribution() {
		check_ajax_referer( 'pdwchs_rfm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$distribution = $this->rfm_calculator->pdwchs_get_segment_distribution();
		wp_send_json_success( $distribution );
	}

	/**
	 * AJAX: Recalculate RFM for a customer
	 *
	 * @since    2.0.0
	 */
	public function pdwchs_ajax_recalculate_rfm() {
		check_ajax_referer( 'pdwchs_rfm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$rfm_data = $this->rfm_calculator->pdwchs_calculate_customer_rfm( $user_id );
		wp_send_json_success( array(
			'message' => __( 'RFM scores updated successfully', 'puredevs-customer-history-for-woocommerce' ),
			'rfm'     => $rfm_data,
		) );
	}

	/**
	 * AJAX: Trigger manual batch calculation
	 *
	 * @since    2.0.0
	 */
	public function pdwchs_ajax_trigger_batch_calculation() {
		check_ajax_referer( 'pdwchs_rfm_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		// Reset offset to start fresh
		delete_option( 'pdwchs_rfm_batch_offset' );

		// Schedule immediate calculation
		wp_schedule_single_event( time(), 'pdwchs_daily_rfm_calculation' );

		wp_send_json_success( array(
			'message' => __( 'Batch calculation started in background', 'puredevs-customer-history-for-woocommerce' ),
		) );
	}

	/**
	 * Get RFM calculator instance
	 *
	 * @since    2.0.0
	 * @return   PD_Customer_History_RFM
	 */
	public function pdwchs_get_rfm_calculator() {
		return $this->rfm_calculator;
	}

	/**
	 * AJAX: Get Health Score distribution data
	 *
	 * @since    2.0.0
	 */
	public function pdwchs_ajax_get_health_distribution() {
		check_ajax_referer( 'pdwchs_health_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$distribution = $this->health_calculator->pdwchs_get_health_distribution();
		wp_send_json_success( $distribution );
	}

	/**
	 * AJAX: Get declining health customers
	 *
	 * @since    2.0.0
	 */
	public function pdwchs_ajax_get_declining_health() {
		check_ajax_referer( 'pdwchs_health_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 10;
		$declining = $this->health_calculator->pdwchs_get_declining_health_customers( $limit );
		wp_send_json_success( $declining );
	}

	/**
	 * AJAX: Recalculate Health Score for a customer
	 *
	 * @since    2.0.0
	 */
	public function pdwchs_ajax_recalculate_health() {
		check_ajax_referer( 'pdwchs_health_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$health_data = $this->health_calculator->pdwchs_calculate_customer_health( $user_id );
		wp_send_json_success( array(
			'message' => __( 'Health score updated successfully', 'puredevs-customer-history-for-woocommerce' ),
			'health'  => $health_data,
		) );
	}

	/**
	 * Get Health calculator instance
	 *
	 * @since    2.0.0
	 * @return   PD_Customer_History_Health
	 */
	public function pdwchs_get_health_calculator() {
		return $this->health_calculator;
	}

	/**
	 * AJAX: Get lifecycle funnel distribution data
	 *
	 * @since    2.1.0
	 */
	public function pdwchs_ajax_get_lifecycle_funnel() {
		check_ajax_referer( 'pdwchs_lifecycle_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$distribution = $this->lifecycle_calculator->pdwchs_get_stage_distribution();
		wp_send_json_success( $distribution );
	}

	/**
	 * AJAX: Get at risk customers alert data
	 *
	 * @since    2.1.0
	 */
	public function pdwchs_ajax_get_at_risk_alert() {
		check_ajax_referer( 'pdwchs_lifecycle_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$limit = isset( $_POST['limit'] ) ? intval( $_POST['limit'] ) : 15;
		$at_risk = $this->lifecycle_calculator->pdwchs_get_at_risk_customers( $limit );
		wp_send_json_success( $at_risk );
	}

	/**
	 * AJAX: Recalculate lifecycle for a customer
	 *
	 * @since    2.1.0
	 */
	public function pdwchs_ajax_recalculate_lifecycle() {
		check_ajax_referer( 'pdwchs_lifecycle_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$lifecycle_data = $this->lifecycle_calculator->pdwchs_calculate_customer_lifecycle( $user_id );
		wp_send_json_success( array(
			'message'   => __( 'Lifecycle stage updated successfully', 'puredevs-customer-history-for-woocommerce' ),
			'lifecycle' => $lifecycle_data,
		) );
	}

	/**
	 * Get Lifecycle calculator instance
	 *
	 * @since    2.1.0
	 * @return   PD_Customer_History_Lifecycle
	 */
	public function pdwchs_get_lifecycle_calculator() {
		return $this->lifecycle_calculator;
	}

	/**
	 * Get Behavioral analyzer instance
	 *
	 * @since    3.0.0
	 * @return   PD_Customer_History_Behavioral
	 */
	public function pdwchs_get_behavioral_analyzer() {
		return $this->behavioral_analyzer;
	}

	/**
	 * Get Churn calculator instance
	 *
	 * @since    4.0.0
	 * @return   PD_Customer_History_Churn
	 */
	public function pdwchs_get_churn_calculator() {
		return $this->churn_calculator;
	}

	/**
	 * Get Alerts manager instance
	 *
	 * @since    4.0.0
	 * @return   PD_Customer_History_Churn_Alerts
	 */
	public function pdwchs_get_alerts_manager() {
		return $this->alerts_manager;
	}

	/**
	 * AJAX: Get churn risk distribution data
	 *
	 * @since    4.0.0
	 */
	public function pdwchs_ajax_get_churn_distribution() {
		check_ajax_referer( 'pdwchs_churn_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$distribution = $this->churn_calculator->pdwchs_get_risk_distribution();
		wp_send_json_success( $distribution );
	}

	/**
	 * AJAX: Recalculate churn for a customer
	 *
	 * @since    4.0.0
	 */
	public function pdwchs_ajax_recalculate_churn() {
		check_ajax_referer( 'pdwchs_churn_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$churn_data = $this->churn_calculator->pdwchs_calculate_churn_score( $user_id );
		wp_send_json_success( array(
			'message' => __( 'Churn score updated successfully', 'puredevs-customer-history-for-woocommerce' ),
			'churn'   => $churn_data,
		) );
	}

	/**
	 * AJAX: Refresh churn dashboard widget
	 *
	 * @since    4.0.0
	 */
	public function pdwchs_ajax_refresh_churn_widget() {
		check_ajax_referer( 'pdwchs_churn_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		ob_start();
		require __DIR__ . '/templates/churn-dashboard-widget.php';
		$html = ob_get_clean();

		wp_send_json_success( array( 'html' => $html ) );
	}

	/**
	 * AJAX: Update alert status
	 *
	 * @since    4.0.0
	 */
	public function pdwchs_ajax_update_alert_status() {
		check_ajax_referer( 'pdwchs_churn_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$alert_id = isset( $_POST['alert_id'] ) ? intval( $_POST['alert_id'] ) : 0;
		$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';
		$notes = isset( $_POST['notes'] ) ? sanitize_textarea_field( $_POST['notes'] ) : '';

		if ( ! $alert_id || ! $status ) {
			wp_send_json_error( array( 'message' => __( 'Invalid parameters', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$result = $this->alerts_manager->pdwchs_update_alert_status( $alert_id, $status, $notes );

		if ( $result ) {
			wp_send_json_success( array(
				'message' => __( 'Alert status updated', 'puredevs-customer-history-for-woocommerce' ),
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to update alert status', 'puredevs-customer-history-for-woocommerce' ),
			) );
		}
	}

	/**
	 * AJAX: Assign alert to user
	 *
	 * @since    4.0.0
	 */
	public function pdwchs_ajax_assign_alert() {
		check_ajax_referer( 'pdwchs_churn_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$alert_id = isset( $_POST['alert_id'] ) ? intval( $_POST['alert_id'] ) : 0;
		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;

		if ( ! $alert_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid alert ID', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$result = $this->alerts_manager->pdwchs_assign_alert( $alert_id, $user_id );

		if ( $result ) {
			wp_send_json_success( array(
				'message' => __( 'Alert assigned successfully', 'puredevs-customer-history-for-woocommerce' ),
			) );
		} else {
			wp_send_json_error( array(
				'message' => __( 'Failed to assign alert', 'puredevs-customer-history-for-woocommerce' ),
			) );
		}
	}

	/**
	 * AJAX: Save churn prediction settings
	 *
	 * @since    4.0.0
	 */
	public function pdwchs_ajax_save_churn_settings() {
		check_ajax_referer( 'pdwchs_churn_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_churn_settings';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		if ( ! $table_exists ) {
			wp_send_json_error( array( 'message' => __( 'Settings table not found', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		// Define valid settings with their types
		$setting_definitions = array(
			'risk_threshold_low_max'    => 'int',
			'risk_threshold_medium_max' => 'int',
			'risk_threshold_high_max'   => 'int',
			'weight_recency'            => 'int',
			'weight_frequency'          => 'int',
			'weight_monetary'           => 'int',
			'weight_engagement'         => 'int',
			'alerts_enabled'            => 'bool',
			'alert_for_high_risk'       => 'bool',
			'alert_for_critical_risk'   => 'bool',
			'alert_email_recipient'     => 'string',
			'min_orders_for_prediction' => 'int',
		);

		$saved = 0;

		foreach ( $setting_definitions as $key => $type ) {
			if ( ! isset( $_POST[ $key ] ) && $type !== 'bool' ) {
				continue;
			}

			$value = '';
			switch ( $type ) {
				case 'int':
					$value = intval( $_POST[ $key ] );
					break;
				case 'bool':
					$value = isset( $_POST[ $key ] ) ? '1' : '0';
					break;
				case 'string':
					$value = sanitize_text_field( $_POST[ $key ] );
					break;
			}

			// Upsert the setting
			$existing = $wpdb->get_var( $wpdb->prepare(
				"SELECT setting_key FROM {$table} WHERE setting_key = %s",
				$key
			) );

			if ( $existing ) {
				$wpdb->update(
					$table,
					array(
						'setting_value' => $value,
						'setting_type'  => $type,
					),
					array( 'setting_key' => $key )
				);
			} else {
				$wpdb->insert( $table, array(
					'setting_key'   => $key,
					'setting_value' => $value,
					'setting_type'  => $type,
				) );
			}

			$saved++;
		}

		// Invalidate cache
		$this->churn_cache->invalidate_statistics_cache();

		wp_send_json_success( array(
			'message' => __( 'Settings saved successfully', 'puredevs-customer-history-for-woocommerce' ),
			'saved'   => $saved,
		) );
	}

	/**
	 * AJAX: Get paginated at-risk customers list
	 *
	 * @since    4.0.0
	 */
	public function pdwchs_ajax_get_at_risk_customers() {
		check_ajax_referer( 'pdwchs_churn_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		global $wpdb;

		$page       = isset( $_POST['page'] ) ? max( 1, intval( $_POST['page'] ) ) : 1;
		$per_page   = isset( $_POST['per_page'] ) ? min( 50, max( 10, intval( $_POST['per_page'] ) ) ) : 25;
		$risk_level = isset( $_POST['risk_level'] ) ? sanitize_text_field( $_POST['risk_level'] ) : '';
		$sort       = isset( $_POST['sort'] ) ? sanitize_text_field( $_POST['sort'] ) : 'score_desc';
		$offset     = ( $page - 1 ) * $per_page;

		$table = $wpdb->prefix . 'pdwchs_churn_scores';

		// Build WHERE clause
		$where = "cs.risk_level IN ('medium', 'high', 'critical')";
		if ( ! empty( $risk_level ) && in_array( $risk_level, array( 'medium', 'high', 'critical' ), true ) ) {
			$where = $wpdb->prepare( "cs.risk_level = %s", $risk_level );
		}

		// Build ORDER BY
		$order_by = 'cs.churn_score DESC';
		switch ( $sort ) {
			case 'score_asc':
				$order_by = 'cs.churn_score ASC';
				break;
			case 'value_desc':
				$order_by = 'cs.total_spent DESC';
				break;
		}

		// Get total count
		$total = intval( $wpdb->get_var( "
			SELECT COUNT(*)
			FROM {$table} cs
			JOIN {$wpdb->users} u ON cs.customer_id = u.ID
			WHERE {$where}
		" ) );

		// Get paginated results
		$customers = $wpdb->get_results( $wpdb->prepare( "
			SELECT cs.*, u.display_name, u.user_email
			FROM {$table} cs
			JOIN {$wpdb->users} u ON cs.customer_id = u.ID
			WHERE {$where}
			ORDER BY {$order_by}
			LIMIT %d OFFSET %d
		", $per_page, $offset ) );

		// Add indicator labels
		foreach ( $customers as &$customer ) {
			$customer->indicator_label = $this->churn_calculator->pdwchs_get_indicator_label( $customer->primary_churn_indicator );
			$customer->risk_level_info = $this->churn_calculator->pdwchs_get_risk_level_info( $customer->risk_level );
		}

		wp_send_json_success( array(
			'customers'  => $customers,
			'total'      => $total,
			'page'       => $page,
			'per_page'   => $per_page,
			'total_pages' => ceil( $total / $per_page ),
		) );
	}

	/**
	 * AJAX: Get paginated churn alerts list
	 *
	 * @since    4.0.0
	 */
	public function pdwchs_ajax_get_churn_alerts() {
		check_ajax_referer( 'pdwchs_churn_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		global $wpdb;

		$page     = isset( $_POST['page'] ) ? max( 1, intval( $_POST['page'] ) ) : 1;
		$per_page = isset( $_POST['per_page'] ) ? min( 50, max( 10, intval( $_POST['per_page'] ) ) ) : 25;
		$status   = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'active';
		$offset   = ( $page - 1 ) * $per_page;

		$table = $wpdb->prefix . 'pdwchs_churn_alerts';

		// Build WHERE clause based on status filter
		$where = "a.status IN ('new', 'viewed', 'in_progress')";
		if ( $status === 'resolved' ) {
			$where = "a.status = 'resolved'";
		} elseif ( $status === 'dismissed' ) {
			$where = "a.status = 'dismissed'";
		} elseif ( $status === 'all' ) {
			$where = '1=1';
		}

		// Get total count
		$total = intval( $wpdb->get_var( "
			SELECT COUNT(*)
			FROM {$table} a
			JOIN {$wpdb->users} u ON a.customer_id = u.ID
			WHERE {$where}
		" ) );

		// Get paginated results
		$alerts = $wpdb->get_results( $wpdb->prepare( "
			SELECT a.*, u.display_name, u.user_email
			FROM {$table} a
			JOIN {$wpdb->users} u ON a.customer_id = u.ID
			WHERE {$where}
			ORDER BY a.priority DESC, a.created_at DESC
			LIMIT %d OFFSET %d
		", $per_page, $offset ) );

		wp_send_json_success( array(
			'alerts'      => $alerts,
			'total'       => $total,
			'page'        => $page,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total / $per_page ),
		) );
	}

	/**
	 * Run weekly churn data cleanup
	 *
	 * Archives old churn history and cleans up resolved alerts.
	 *
	 * @since    4.0.0
	 */
	public function pdwchs_run_weekly_cleanup() {
		global $wpdb;

		$history_table = $wpdb->prefix . 'pdwchs_churn_history';
		$alerts_table  = $wpdb->prefix . 'pdwchs_churn_alerts';

		// Archive churn_history records older than 1 year
		$history_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $history_table ) );
		if ( $history_exists ) {
			$wpdb->query( "
				DELETE FROM {$history_table}
				WHERE recorded_at < DATE_SUB(NOW(), INTERVAL 1 YEAR)
			" );
		}

		// Clean up resolved/dismissed alerts older than 90 days
		$alerts_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $alerts_table ) );
		if ( $alerts_exists ) {
			$wpdb->query( "
				DELETE FROM {$alerts_table}
				WHERE status IN ('resolved', 'dismissed')
				AND resolved_at < DATE_SUB(NOW(), INTERVAL 90 DAY)
			" );
		}

		// Invalidate statistics cache after cleanup
		$this->churn_cache->invalidate_statistics_cache();
	}

	/**
	 * Get Churn Predictor instance
	 *
	 * @since    4.0.0
	 * @return   PD_Customer_History_Churn_Predictor
	 */
	public function pdwchs_get_churn_predictor() {
		return $this->churn_predictor;
	}

	/**
	 * Get Churn Engagement instance
	 *
	 * @since    4.0.0
	 * @return   PD_Customer_History_Churn_Engagement
	 */
	public function pdwchs_get_churn_engagement() {
		return $this->churn_engagement;
	}

	/**
	 * Get Churn Cache instance
	 *
	 * @since    4.0.0
	 * @return   PD_Customer_History_Churn_Cache
	 */
	public function pdwchs_get_churn_cache() {
		return $this->churn_cache;
	}

	/**
	 * H19 listener — append Pro CSV sheets to the weekly digest.
	 *
	 * Writes search-keyword, most-viewed-products, and most-viewed-categories
	 * CSV files for the date window [$start, $end] and returns them bundled
	 * with the HTML email previews in $extra.
	 *
	 * @param array  $extra { reports: string[], attachments: string[], html: string[] }
	 * @param string $start Start datetime Y-m-d H:i:s.
	 * @param string $end   End datetime Y-m-d H:i:s.
	 * @return array Augmented $extra.
	 */
	public function pdwchs_weekly_extra_sheets( $extra, $start, $end ) {
		global $wpdb;

		$_generate_weekly_report = get_option( 'pd-woo-generate_weekly_report' );
		if ( empty( $_generate_weekly_report ) ) {
			return $extra;
		}

		$upload_dir   = wp_upload_dir();
		$initial_date = substr( $start, 0, 10 );
		$final_date   = substr( $end, 0, 10 );

		// --- Search keywords CSV ---
		if ( in_array( 'search', $_generate_weekly_report, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$searches = $wpdb->get_results(
				$wpdb->prepare(
					"SELECT COUNT(id) as qta, url FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE del='0' AND url LIKE '%%:search:%%' AND reg_date BETWEEN %s AND %s GROUP BY url", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
					$start,
					$end
				)
			);

			if ( ! empty( $searches ) ) {
				$search_report = $upload_dir['path'] . "/customers_search_history_from_{$initial_date}_to_{$final_date}.csv";
				$search_f      = fopen( $search_report, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				fputcsv( $search_f, array( 'Keyword', 'Count' ) );
				foreach ( $searches as $search ) {
					$url_array = explode( '::', $search->url );
					$keyword   = ( isset( $url_array[1] ) && 'search' === $url_array[1] && ! empty( $url_array[2] ) ) ? $url_array[2] : $search->url;
					fputcsv( $search_f, array( $keyword, $search->qta ) );
				}
				fseek( $search_f, 0 );
				fpassthru( $search_f );
				fclose( $search_f ); // phpcs:ignore WordPress.WP.AlternativeFunctions

				$extra['attachments'][] = $search_report;
				$extra['reports'][]     = 'Search';

				$l_searches = count( $searches ) > 3 ? array_slice( $searches, 0, 3 ) : $searches;
				$html  = '<table style="border: 1px solid gray;border-collapse: collapse;padding:5px;">';
				$html .= '<tr><th colspan="2" style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Search Report</th></tr>';
				$html .= '<tr>';
				$html .= '<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Keyword</th>';
				$html .= '<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Count</th>';
				$html .= '</tr>';
				foreach ( $l_searches as $l_search ) {
					$url_array = explode( '::', $l_search->url );
					$keyword   = ( isset( $url_array[1] ) && 'search' === $url_array[1] && ! empty( $url_array[2] ) ) ? $url_array[2] : $l_search->url;
					$html .= '<tr>';
					$html .= '<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">' . esc_html( $keyword ) . '</td>';
					$html .= '<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">' . esc_html( $l_search->qta ) . '</td>';
					$html .= '</tr>';
				}
				$html         .= '</table>';
				$extra['html'][] = $html;
			}
		}

		// --- Most viewed products CSV ---
		if ( in_array( 'most_viewed_products', $_generate_weekly_report, true ) ) {
			$_site_pages     = PDWCHS_Search_Stats_Data::get_url_counts( array( 'start' => $start, 'end' => $end ) );
			$_viewed_products = array();
			if ( ! empty( $_site_pages ) ) {
				$_viewed_products = PDWCHS_Helper::pdwchs_get_view_products( $_site_pages );
				usort( $_viewed_products, static function ( $a, $b ) {
					if ( $a->qta < $b->qta ) { return 1; }
					if ( $a->qta > $b->qta ) { return -1; }
					return 0;
				} );
			}

			if ( ! empty( $_viewed_products ) ) {
				$most_viewed_products_report = $upload_dir['path'] . "/customers_most_viewed_products_history_from_{$initial_date}_to_{$final_date}.csv";
				$mvp_f = fopen( $most_viewed_products_report, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				fputcsv( $mvp_f, array( 'Product ID', 'Product Name', 'Count' ) );
				foreach ( $_viewed_products as $_vp ) {
					$slug  = explode( '/', $_vp->url );
					$posts = get_posts( array(
						'name'           => end( $slug ),
						'posts_per_page' => 1,
						'post_type'      => 'product',
						'post_status'    => 'publish',
					) );
					if ( ! empty( $posts ) ) {
						fputcsv( $mvp_f, array( $posts[0]->ID, $posts[0]->post_title, $_vp->qta ) );
					}
				}
				fseek( $mvp_f, 0 );
				fpassthru( $mvp_f );
				fclose( $mvp_f ); // phpcs:ignore WordPress.WP.AlternativeFunctions

				$extra['attachments'][] = $most_viewed_products_report;
				$extra['reports'][]     = 'Most Viewed Products';

				$l_vp  = count( $_viewed_products ) > 3 ? array_slice( $_viewed_products, 0, 3 ) : $_viewed_products;
				$html  = '<table style="border: 1px solid gray;border-collapse: collapse;padding:5px;">';
				$html .= '<tr><th colspan="3" style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Most Viewed Products Report</th></tr>';
				$html .= '<tr>';
				$html .= '<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Product ID</th>';
				$html .= '<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Product Name</th>';
				$html .= '<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Count</th>';
				$html .= '</tr>';
				foreach ( $l_vp as $_lvp ) {
					$slug  = explode( '/', $_lvp->url );
					$posts = get_posts( array(
						'name'           => end( $slug ),
						'posts_per_page' => 1,
						'post_type'      => 'product',
						'post_status'    => 'publish',
					) );
					if ( ! empty( $posts ) ) {
						$html .= '<tr>';
						$html .= '<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">' . esc_html( $posts[0]->ID ) . '</td>';
						$html .= '<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">' . esc_html( $posts[0]->post_title ) . '</td>';
						$html .= '<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">' . esc_html( $_lvp->qta ) . '</td>';
						$html .= '</tr>';
					}
				}
				$html         .= '</table>';
				$extra['html'][] = $html;
			}
		}

		// --- Most viewed categories CSV ---
		if ( in_array( 'most_viewed_categories', $_generate_weekly_report, true ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$_cat_pages = $wpdb->get_results(
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery
					"SELECT COUNT(id) as qta, url FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE del='0' AND url LIKE %s AND reg_date BETWEEN %s AND %s GROUP BY url ORDER BY qta DESC", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
					'%product_cat%',
					$start,
					$end
				)
			);

			if ( ! empty( $_cat_pages ) ) {
				$mvc_report = $upload_dir['path'] . "/customers_most_viewed_categories_history_from_{$initial_date}_to_{$final_date}.csv";
				$mvc_f      = fopen( $mvc_report, 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions
				fputcsv( $mvc_f, array( 'Category', 'Count' ) );
				foreach ( $_cat_pages as $_cp ) {
					$slug = explode( '/', $_cp->url );
					fputcsv( $mvc_f, array( end( $slug ), $_cp->qta ) );
				}
				fseek( $mvc_f, 0 );
				fpassthru( $mvc_f );
				fclose( $mvc_f ); // phpcs:ignore WordPress.WP.AlternativeFunctions

				$extra['attachments'][] = $mvc_report;
				$extra['reports'][]     = 'Most Viewed Categories';

				$l_cp  = count( $_cat_pages ) > 3 ? array_slice( $_cat_pages, 0, 3 ) : $_cat_pages;
				$html  = '<table style="border: 1px solid gray;border-collapse: collapse;padding:5px;">';
				$html .= '<tr><th colspan="2" style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Most Viewed Categories Report</th></tr>';
				$html .= '<tr>';
				$html .= '<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Category</th>';
				$html .= '<th style="border: 1px solid gray;border-collapse: collapse;padding:5px;">Count</th>';
				$html .= '</tr>';
				foreach ( $l_cp as $_lcp ) {
					$slug  = explode( '/', $_lcp->url );
					$html .= '<tr>';
					$html .= '<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">' . esc_html( end( $slug ) ) . '</td>';
					$html .= '<td style="border: 1px solid gray;border-collapse: collapse;padding:5px;">' . esc_html( $_lcp->qta ) . '</td>';
					$html .= '</tr>';
				}
				$html         .= '</table>';
				$extra['html'][] = $html;
			}
		}

		return $extra;
	}
}
