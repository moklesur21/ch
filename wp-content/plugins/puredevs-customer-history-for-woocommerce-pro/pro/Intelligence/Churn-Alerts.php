<?php
/**
 * Churn Alert Manager
 *
 * Manages churn prediction alerts, notifications, and retention actions.
 *
 * @link       https://puredevs.com/
 * @since      4.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * Churn Alert Manager Class
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Churn_Alerts {

	/**
	 * Churn calculator instance
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      PD_Customer_History_Churn    $churn_calculator
	 */
	private $churn_calculator;

	/**
	 * Constructor
	 *
	 * @since    4.0.0
	 * @param    PD_Customer_History_Churn    $churn_calculator    Churn calculator instance
	 */
	public function __construct( $churn_calculator = null ) {
		$this->churn_calculator = $churn_calculator;
	}

	/**
	 * Set churn calculator instance
	 *
	 * @since    4.0.0
	 * @param    PD_Customer_History_Churn    $calculator
	 */
	public function pdwchs_set_churn_calculator( $calculator ) {
		$this->churn_calculator = $calculator;
	}

	/**
	 * Generate alerts for customers based on churn risk
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    Optional specific customer ID
	 * @return   array    Results
	 */
	public function pdwchs_generate_alerts( $customer_id = null ) {
		global $wpdb;

		$scores_table = $wpdb->prefix . 'pdwchs_churn_scores';
		$alerts_table = $wpdb->prefix . 'pdwchs_churn_alerts';

		// Check if tables exist
		$scores_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $scores_table ) );
		$alerts_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $alerts_table ) );

		if ( ! $scores_exists || ! $alerts_exists ) {
			return array( 'generated' => 0, 'error' => 'Tables not found' );
		}

		// Get settings
		$alerts_enabled = $this->churn_calculator
			? $this->churn_calculator->pdwchs_get_setting( 'alerts_enabled', true )
			: true;

		if ( ! $alerts_enabled ) {
			return array( 'generated' => 0, 'message' => 'Alerts are disabled' );
		}

		$alert_for_high = $this->churn_calculator
			? $this->churn_calculator->pdwchs_get_setting( 'alert_for_high_risk', true )
			: true;
		$alert_for_critical = $this->churn_calculator
			? $this->churn_calculator->pdwchs_get_setting( 'alert_for_critical_risk', true )
			: true;
		$batch_limit = $this->churn_calculator
			? $this->churn_calculator->pdwchs_get_setting( 'alert_batch_limit', 50 )
			: 50;

		// Build risk levels to alert
		$risk_levels = array();
		if ( $alert_for_critical ) {
			$risk_levels[] = 'critical';
		}
		if ( $alert_for_high ) {
			$risk_levels[] = 'high';
		}

		if ( empty( $risk_levels ) ) {
			return array( 'generated' => 0, 'message' => 'No alert levels enabled' );
		}

		// Query for customers needing alerts
		$where = "cs.risk_level IN ('" . implode( "','", $risk_levels ) . "')";
		$where .= " AND cs.alert_status IN ('none', 'resolved')";

		if ( $customer_id ) {
			$where .= $wpdb->prepare( " AND cs.customer_id = %d", $customer_id );
		}

		$customers = $wpdb->get_results( "
			SELECT cs.*, u.display_name, u.user_email
			FROM {$scores_table} cs
			JOIN {$wpdb->users} u ON cs.customer_id = u.ID
			WHERE {$where}
			ORDER BY cs.churn_score DESC
			LIMIT {$batch_limit}
		" );

		$generated = 0;

		foreach ( $customers as $customer_data ) {
			$result = $this->pdwchs_create_alert( $customer_data );
			if ( $result ) {
				$generated++;
			}
		}

		return array(
			'generated' => $generated,
			'checked'   => count( $customers ),
		);
	}

	/**
	 * Create alert for a customer
	 *
	 * @since    4.0.0
	 * @param    object    $customer_data    Customer churn data
	 * @return   int|false    Alert ID or false
	 */
	public function pdwchs_create_alert( $customer_data ) {
		global $wpdb;

		$alerts_table = $wpdb->prefix . 'pdwchs_churn_alerts';
		$scores_table = $wpdb->prefix . 'pdwchs_churn_scores';

		// Check for existing active alert
		$existing = $wpdb->get_var( $wpdb->prepare( "
			SELECT id FROM {$alerts_table}
			WHERE customer_id = %d
			AND status IN ('new', 'viewed', 'in_progress')
		", $customer_data->customer_id ) );

		if ( $existing ) {
			// Update existing alert if risk escalated
			$this->pdwchs_update_existing_alert( $existing, $customer_data );
			return false;
		}

		// Determine alert type
		$alert_type = $this->pdwchs_determine_alert_type( $customer_data );

		// Generate alert content
		$content = $this->pdwchs_generate_alert_content( $customer_data );

		// Calculate priority
		$priority = $this->pdwchs_calculate_alert_priority( $customer_data );

		// Generate recommended actions
		$actions = $this->pdwchs_generate_recommended_actions( $customer_data );

		// Insert alert
		$result = $wpdb->insert( $alerts_table, array(
			'customer_id'         => $customer_data->customer_id,
			'alert_type'          => $alert_type,
			'risk_level'          => $customer_data->risk_level,
			'churn_score'         => $customer_data->churn_score,
			'priority'            => $priority,
			'status'              => 'new',
			'title'               => $content['title'],
			'message'             => $content['message'],
			'recommended_actions' => wp_json_encode( $actions ),
			'customer_value'      => $customer_data->total_spent,
		) );

		if ( ! $result ) {
			return false;
		}

		$alert_id = $wpdb->insert_id;

		// Update customer alert status
		$wpdb->update( $scores_table, array(
			'alert_status' => 'pending',
		), array( 'customer_id' => $customer_data->customer_id ) );

		// Send email notification if enabled
		$this->pdwchs_send_alert_email( $alert_id, $customer_data, $content );

		// Trigger webhook action
		do_action( 'pdwchs_churn_alert_created', $alert_id, $customer_data );

		return $alert_id;
	}

	/**
	 * Determine alert type
	 *
	 * @since    4.0.0
	 * @param    object    $customer_data    Customer data
	 * @return   string
	 */
	private function pdwchs_determine_alert_type( $customer_data ) {
		if ( $customer_data->risk_level === 'critical' ) {
			return 'critical_risk';
		}

		// Check if this is an escalation
		global $wpdb;
		$history_table = $wpdb->prefix . 'pdwchs_churn_history';

		$previous = $wpdb->get_var( $wpdb->prepare( "
			SELECT previous_risk_level
			FROM {$history_table}
			WHERE customer_id = %d
			ORDER BY recorded_at DESC
			LIMIT 1
		", $customer_data->customer_id ) );

		if ( $previous && $this->pdwchs_risk_is_higher( $customer_data->risk_level, $previous ) ) {
			return 'escalated_risk';
		}

		return 'new_risk';
	}

	/**
	 * Check if risk level A is higher than B
	 *
	 * @since    4.0.0
	 * @param    string    $level_a    First risk level
	 * @param    string    $level_b    Second risk level
	 * @return   bool
	 */
	private function pdwchs_risk_is_higher( $level_a, $level_b ) {
		$order = array( 'low' => 1, 'medium' => 2, 'high' => 3, 'critical' => 4 );
		$a = isset( $order[ $level_a ] ) ? $order[ $level_a ] : 0;
		$b = isset( $order[ $level_b ] ) ? $order[ $level_b ] : 0;
		return $a > $b;
	}

	/**
	 * Generate alert content
	 *
	 * @since    4.0.0
	 * @param    object    $customer_data    Customer data
	 * @return   array
	 */
	private function pdwchs_generate_alert_content( $customer_data ) {
		$customer_name = $customer_data->display_name ?: __( 'Customer', 'puredevs-customer-history-for-woocommerce' );
		$risk_label = ucfirst( $customer_data->risk_level );

		// Generate title
		$title = sprintf(
			/* translators: 1: Risk level, 2: Customer name */
			__( '%1$s Risk Alert: %2$s', 'puredevs-customer-history-for-woocommerce' ),
			$risk_label,
			$customer_name
		);

		// Generate message with specific concerns
		$concerns = array();

		if ( $customer_data->days_overdue > 0 ) {
			$concerns[] = sprintf(
				/* translators: %d: Days overdue */
				__( 'Purchase overdue by %d days', 'puredevs-customer-history-for-woocommerce' ),
				$customer_data->days_overdue
			);
		}

		if ( $customer_data->frequency_decline_pct > 25 ) {
			$concerns[] = sprintf(
				/* translators: %s: Decline percentage */
				__( 'Purchase frequency declined by %s%%', 'puredevs-customer-history-for-woocommerce' ),
				number_format( $customer_data->frequency_decline_pct, 0 )
			);
		}

		if ( $customer_data->spending_decline_pct > 25 ) {
			$concerns[] = sprintf(
				/* translators: %s: Decline percentage */
				__( 'Spending declined by %s%%', 'puredevs-customer-history-for-woocommerce' ),
				number_format( $customer_data->spending_decline_pct, 0 )
			);
		}

		if ( $customer_data->engagement_decline_pct > 30 ) {
			$concerns[] = sprintf(
				/* translators: %s: Decline percentage */
				__( 'Site engagement declined by %s%%', 'puredevs-customer-history-for-woocommerce' ),
				number_format( $customer_data->engagement_decline_pct, 0 )
			);
		}

		if ( $customer_data->sessions_last_30_days == 0 && $customer_data->total_sessions > 0 ) {
			$concerns[] = __( 'No site visits in the last 30 days', 'puredevs-customer-history-for-woocommerce' );
		}

		// Build message
		$message = sprintf(
			/* translators: 1: Customer name, 2: Churn score */
			__( '%1$s has a churn risk score of %2$d/100.', 'puredevs-customer-history-for-woocommerce' ),
			$customer_name,
			$customer_data->churn_score
		);

		if ( ! empty( $concerns ) ) {
			$message .= "\n\n" . __( 'Key concerns:', 'puredevs-customer-history-for-woocommerce' ) . "\n- " . implode( "\n- ", $concerns );
		}

		// Add customer value context
		if ( $customer_data->total_spent > 0 ) {
			$message .= "\n\n" . sprintf(
				/* translators: 1: Total spent, 2: Order count */
				__( 'Customer value: %1$s over %2$d orders.', 'puredevs-customer-history-for-woocommerce' ),
				wc_price( $customer_data->total_spent ),
				$customer_data->total_orders
			);
		}

		return array(
			'title'   => $title,
			'message' => $message,
		);
	}

	/**
	 * Generate recommended actions
	 *
	 * @since    4.0.0
	 * @param    object    $customer_data    Customer data
	 * @return   array
	 */
	private function pdwchs_generate_recommended_actions( $customer_data ) {
		$actions = array();

		// High-value customer actions
		if ( $customer_data->total_spent >= 500 ) {
			$actions[] = array(
				'type'        => 'personal_outreach',
				'label'       => __( 'Personal outreach', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'High-value customer - consider personal phone call or personalized email', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 'high',
			);
		}

		// Actions based on primary concern
		$indicator = $customer_data->primary_churn_indicator;

		if ( $indicator === 'recency' || $customer_data->days_overdue > 30 ) {
			$actions[] = array(
				'type'        => 'win_back_campaign',
				'label'       => __( 'Send win-back offer', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'Send personalized discount or special offer to encourage return', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 'high',
			);
		}

		if ( $indicator === 'frequency' ) {
			$actions[] = array(
				'type'        => 'loyalty_incentive',
				'label'       => __( 'Offer loyalty reward', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'Provide bonus points or exclusive access to re-engage', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 'medium',
			);
		}

		if ( $indicator === 'monetary' ) {
			$actions[] = array(
				'type'        => 'feedback_request',
				'label'       => __( 'Request feedback', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'Send survey to understand why spending decreased', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 'medium',
			);
		}

		if ( $indicator === 'engagement' ) {
			$actions[] = array(
				'type'        => 'content_campaign',
				'label'       => __( 'Send personalized content', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'Share new arrivals or recommendations based on past purchases', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 'medium',
			);
		}

		// Critical risk specific actions
		if ( $customer_data->risk_level === 'critical' ) {
			$actions[] = array(
				'type'        => 'urgent_discount',
				'label'       => __( 'Offer significant discount', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'Provide substantial discount (20%+) with limited validity', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 'urgent',
			);
		}

		// Always include basic follow-up
		$actions[] = array(
			'type'        => 'email_reminder',
			'label'       => __( 'Send reminder email', 'puredevs-customer-history-for-woocommerce' ),
			'description' => __( 'Send friendly check-in email highlighting new products', 'puredevs-customer-history-for-woocommerce' ),
			'priority'    => 'low',
		);

		return $actions;
	}

	/**
	 * Calculate alert priority
	 *
	 * @since    4.0.0
	 * @param    object    $customer_data    Customer data
	 * @return   int    Priority 1-10
	 */
	private function pdwchs_calculate_alert_priority( $customer_data ) {
		$priority = 5; // Base priority

		// Adjust for risk level
		switch ( $customer_data->risk_level ) {
			case 'critical':
				$priority += 4;
				break;
			case 'high':
				$priority += 2;
				break;
		}

		// Adjust for customer value
		if ( $customer_data->total_spent >= 1000 ) {
			$priority += 2;
		} elseif ( $customer_data->total_spent >= 500 ) {
			$priority += 1;
		}

		// Adjust for days overdue
		if ( $customer_data->days_overdue >= 60 ) {
			$priority += 1;
		}

		return min( 10, max( 1, $priority ) );
	}

	/**
	 * Update existing alert
	 *
	 * @since    4.0.0
	 * @param    int       $alert_id        Alert ID
	 * @param    object    $customer_data   Updated customer data
	 */
	private function pdwchs_update_existing_alert( $alert_id, $customer_data ) {
		global $wpdb;

		$alerts_table = $wpdb->prefix . 'pdwchs_churn_alerts';

		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$alerts_table} WHERE id = %d",
			$alert_id
		) );

		// Update if risk escalated
		if ( $this->pdwchs_risk_is_higher( $customer_data->risk_level, $existing->risk_level ) ) {
			$content = $this->pdwchs_generate_alert_content( $customer_data );
			$priority = $this->pdwchs_calculate_alert_priority( $customer_data );
			$actions = $this->pdwchs_generate_recommended_actions( $customer_data );

			$wpdb->update( $alerts_table, array(
				'risk_level'          => $customer_data->risk_level,
				'churn_score'         => $customer_data->churn_score,
				'priority'            => $priority,
				'title'               => $content['title'],
				'message'             => $content['message'],
				'recommended_actions' => wp_json_encode( $actions ),
				'alert_type'          => 'escalated_risk',
			), array( 'id' => $alert_id ) );
		}
	}

	/**
	 * Send alert email notification
	 *
	 * @since    4.0.0
	 * @param    int       $alert_id        Alert ID
	 * @param    object    $customer_data   Customer data
	 * @param    array     $content         Alert content
	 */
	private function pdwchs_send_alert_email( $alert_id, $customer_data, $content ) {
		global $wpdb;

		// Check if email notifications are enabled
		$alerts_enabled = $this->churn_calculator
			? $this->churn_calculator->pdwchs_get_setting( 'alerts_enabled', true )
			: true;

		if ( ! $alerts_enabled ) {
			return;
		}

		// Get recipient email
		$recipient = $this->churn_calculator
			? $this->churn_calculator->pdwchs_get_setting( 'alert_email_recipient', '' )
			: '';

		if ( empty( $recipient ) ) {
			$recipient = get_option( 'admin_email' );
		}

		// Build email
		$subject = sprintf(
			/* translators: 1: Site name, 2: Alert title */
			__( '[%1$s] Churn Alert: %2$s', 'puredevs-customer-history-for-woocommerce' ),
			get_bloginfo( 'name' ),
			$content['title']
		);

		$message = $content['message'];
		$message .= "\n\n" . sprintf(
			/* translators: %s: Admin URL */
			__( 'View customer details: %s', 'puredevs-customer-history-for-woocommerce' ),
			admin_url( 'admin.php?page=pd-woo-customer&user_id=' . $customer_data->customer_id )
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );

		$sent = wp_mail( $recipient, $subject, $message, $headers );

		if ( $sent ) {
			$wpdb->update(
				$wpdb->prefix . 'pdwchs_churn_alerts',
				array(
					'email_sent'    => 1,
					'email_sent_at' => current_time( 'mysql' ),
				),
				array( 'id' => $alert_id )
			);
		}
	}

	/**
	 * Update alert status
	 *
	 * @since    4.0.0
	 * @param    int       $alert_id     Alert ID
	 * @param    string    $new_status   New status
	 * @param    string    $notes        Resolution notes
	 * @return   bool
	 */
	public function pdwchs_update_alert_status( $alert_id, $new_status, $notes = '' ) {
		global $wpdb;

		$alerts_table = $wpdb->prefix . 'pdwchs_churn_alerts';
		$scores_table = $wpdb->prefix . 'pdwchs_churn_scores';

		$valid_statuses = array( 'new', 'viewed', 'in_progress', 'resolved', 'dismissed' );
		if ( ! in_array( $new_status, $valid_statuses, true ) ) {
			return false;
		}

		$alert = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$alerts_table} WHERE id = %d",
			$alert_id
		) );

		if ( ! $alert ) {
			return false;
		}

		$update_data = array( 'status' => $new_status );

		if ( $new_status === 'resolved' || $new_status === 'dismissed' ) {
			$update_data['resolved_at'] = current_time( 'mysql' );
			if ( ! empty( $notes ) ) {
				$update_data['resolution_notes'] = $notes;
			}
		}

		$result = $wpdb->update( $alerts_table, $update_data, array( 'id' => $alert_id ) );

		// Update customer's alert status
		if ( $new_status === 'resolved' || $new_status === 'dismissed' ) {
			$wpdb->update( $scores_table, array(
				'alert_status' => $new_status,
			), array( 'customer_id' => $alert->customer_id ) );
		} elseif ( $new_status === 'viewed' || $new_status === 'in_progress' ) {
			$wpdb->update( $scores_table, array(
				'alert_status' => 'sent',
			), array( 'customer_id' => $alert->customer_id ) );
		}

		// Trigger webhook
		do_action( 'pdwchs_churn_alert_status_updated', $alert_id, $new_status, $alert );

		return $result !== false;
	}

	/**
	 * Assign alert to staff member
	 *
	 * @since    4.0.0
	 * @param    int    $alert_id    Alert ID
	 * @param    int    $user_id     Admin user ID
	 * @return   bool
	 */
	public function pdwchs_assign_alert( $alert_id, $user_id ) {
		global $wpdb;

		$result = $wpdb->update(
			$wpdb->prefix . 'pdwchs_churn_alerts',
			array(
				'assigned_to' => $user_id,
				'status'      => 'in_progress',
			),
			array( 'id' => $alert_id )
		);

		return $result !== false;
	}

	/**
	 * Get active alerts
	 *
	 * @since    4.0.0
	 * @param    int    $limit    Number of alerts to return
	 * @return   array
	 */
	public function pdwchs_get_active_alerts( $limit = 20 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_churn_alerts';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		if ( ! $table_exists ) {
			return array();
		}

		return $wpdb->get_results( $wpdb->prepare( "
			SELECT a.*, u.display_name, u.user_email
			FROM {$table} a
			JOIN {$wpdb->users} u ON a.customer_id = u.ID
			WHERE a.status IN ('new', 'viewed', 'in_progress')
			ORDER BY a.priority DESC, a.created_at DESC
			LIMIT %d
		", $limit ) );
	}

	/**
	 * Get alert statistics
	 *
	 * @since    4.0.0
	 * @param    int    $days    Number of days for statistics
	 * @return   array
	 */
	public function pdwchs_get_alert_statistics( $days = 30 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_churn_alerts';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		if ( ! $table_exists ) {
			return array(
				'total'              => 0,
				'new'                => 0,
				'in_progress'        => 0,
				'resolved'           => 0,
				'by_risk_level'      => array(),
				'avg_resolution_time' => 0,
			);
		}

		// Count by status
		$status_counts = $wpdb->get_results( $wpdb->prepare( "
			SELECT status, COUNT(*) as count
			FROM {$table}
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			GROUP BY status
		", $days ) );

		$stats = array(
			'total'       => 0,
			'new'         => 0,
			'viewed'      => 0,
			'in_progress' => 0,
			'resolved'    => 0,
			'dismissed'   => 0,
		);

		foreach ( $status_counts as $row ) {
			$stats[ $row->status ] = intval( $row->count );
			$stats['total'] += intval( $row->count );
		}

		// Count by risk level
		$risk_counts = $wpdb->get_results( $wpdb->prepare( "
			SELECT risk_level, COUNT(*) as count
			FROM {$table}
			WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			GROUP BY risk_level
		", $days ) );

		$stats['by_risk_level'] = array();
		foreach ( $risk_counts as $row ) {
			$stats['by_risk_level'][ $row->risk_level ] = intval( $row->count );
		}

		// Calculate average resolution time
		$avg_resolution = $wpdb->get_var( $wpdb->prepare( "
			SELECT AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at))
			FROM {$table}
			WHERE resolved_at IS NOT NULL
			AND created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
		", $days ) );

		$stats['avg_resolution_time'] = $avg_resolution ? round( $avg_resolution, 1 ) : 0;

		return $stats;
	}

	/**
	 * Get alert by ID
	 *
	 * @since    4.0.0
	 * @param    int    $alert_id    Alert ID
	 * @return   object|null
	 */
	public function pdwchs_get_alert( $alert_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_churn_alerts';

		return $wpdb->get_row( $wpdb->prepare( "
			SELECT a.*, u.display_name, u.user_email
			FROM {$table} a
			JOIN {$wpdb->users} u ON a.customer_id = u.ID
			WHERE a.id = %d
		", $alert_id ) );
	}

	/**
	 * Mark customer as recovered
	 *
	 * @since    4.0.0
	 * @param    int       $customer_id    Customer ID
	 * @param    string    $action_taken   Retention action that worked
	 */
	public function pdwchs_mark_customer_recovered( $customer_id, $action_taken = '' ) {
		global $wpdb;

		$history_table = $wpdb->prefix . 'pdwchs_churn_history';
		$scores_table = $wpdb->prefix . 'pdwchs_churn_scores';
		$alerts_table = $wpdb->prefix . 'pdwchs_churn_alerts';

		// Get current churn data
		$current = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$scores_table} WHERE customer_id = %d",
			$customer_id
		) );

		if ( ! $current ) {
			return;
		}

		// Record recovery in history
		$wpdb->insert( $history_table, array(
			'customer_id'       => $customer_id,
			'churn_score'       => $current->churn_score,
			'previous_score'    => $current->churn_score,
			'risk_level'        => $current->risk_level,
			'previous_risk_level' => $current->risk_level,
			'customer_status'   => 'recovered',
			'transition_type'   => 'recovered',
			'retention_action'  => $action_taken,
			'retention_success' => 1,
		) );

		// Resolve any active alerts
		$resolution_note = sprintf(
			/* translators: %s: Action taken */
			__( 'Customer recovered. Action: %s', 'puredevs-customer-history-for-woocommerce' ),
			$action_taken ?: __( 'Completed purchase', 'puredevs-customer-history-for-woocommerce' )
		);
		$wpdb->query( $wpdb->prepare(
			"UPDATE {$alerts_table}
			SET status = 'resolved', resolved_at = %s, resolution_notes = %s
			WHERE customer_id = %d AND status IN ('new', 'viewed', 'in_progress')",
			current_time( 'mysql' ),
			$resolution_note,
			$customer_id
		) );

		// Trigger webhook
		do_action( 'pdwchs_customer_recovered', $customer_id, $action_taken );
	}
}
