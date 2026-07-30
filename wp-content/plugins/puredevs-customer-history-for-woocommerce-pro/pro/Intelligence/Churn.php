<?php
/**
 * Churn Prediction Calculator
 *
 * Calculates customer churn risk scores based on recency, frequency,
 * monetary value, and engagement metrics.
 *
 * @link       https://puredevs.com/
 * @since      4.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * Churn Prediction Calculator Class
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Churn {

	/**
	 * Cached settings
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      array    $settings    Churn prediction settings
	 */
	private $settings = null;

	/**
	 * Risk level definitions
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      array    $risk_levels    Risk level info
	 */
	private $risk_levels = array(
		'low' => array(
			'label'       => 'Low Risk',
			'color'       => '#22c55e',
			'bg_color'    => '#dcfce7',
			'icon'        => 'shield-check',
			'description' => 'Customer is healthy and engaged',
			'priority'    => 1,
		),
		'medium' => array(
			'label'       => 'Medium Risk',
			'color'       => '#f59e0b',
			'bg_color'    => '#fef3c7',
			'icon'        => 'warning',
			'description' => 'Customer showing early warning signs',
			'priority'    => 2,
		),
		'high' => array(
			'label'       => 'High Risk',
			'color'       => '#ef4444',
			'bg_color'    => '#fee2e2',
			'icon'        => 'alert-triangle',
			'description' => 'Customer at significant risk of churning',
			'priority'    => 3,
		),
		'critical' => array(
			'label'       => 'Critical Risk',
			'color'       => '#dc2626',
			'bg_color'    => '#fecaca',
			'icon'        => 'alert-octagon',
			'description' => 'Immediate action required to prevent churn',
			'priority'    => 4,
		),
	);

	/**
	 * Constructor
	 *
	 * @since    4.0.0
	 */
	public function __construct() {
		$this->pdwchs_load_settings();
	}

	/**
	 * Load settings from database
	 *
	 * @since    4.0.0
	 */
	private function pdwchs_load_settings() {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_churn_settings';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare(
			"SHOW TABLES LIKE %s",
			$table
		) );

		if ( ! $table_exists ) {
			$this->settings = $this->pdwchs_get_default_settings();
			return;
		}

		$results = $wpdb->get_results( "SELECT setting_key, setting_value, setting_type FROM {$table}" );

		$this->settings = array();
		foreach ( $results as $row ) {
			$value = $row->setting_value;

			switch ( $row->setting_type ) {
				case 'int':
					$value = intval( $value );
					break;
				case 'float':
					$value = floatval( $value );
					break;
				case 'bool':
					$value = ( $value === '1' || $value === 'true' );
					break;
				case 'json':
					$value = json_decode( $value, true );
					break;
			}

			$this->settings[ $row->setting_key ] = $value;
		}

		// Merge with defaults for any missing settings
		$this->settings = array_merge( $this->pdwchs_get_default_settings(), $this->settings );
	}

	/**
	 * Get default settings
	 *
	 * @since    4.0.0
	 * @return   array
	 */
	private function pdwchs_get_default_settings() {
		return array(
			'risk_threshold_low_max'       => 30,
			'risk_threshold_medium_max'    => 60,
			'risk_threshold_high_max'      => 80,
			'weight_recency'               => 40,
			'weight_frequency'             => 25,
			'weight_monetary'              => 20,
			'weight_engagement'            => 15,
			'recency_excellent_max'        => 30,
			'recency_good_max'             => 60,
			'recency_fair_max'             => 90,
			'recency_critical_threshold'   => 180,
			'frequency_decline_warning'    => 25,
			'frequency_decline_critical'   => 50,
			'alerts_enabled'               => true,
			'alert_for_high_risk'          => true,
			'alert_for_critical_risk'      => true,
			'alert_email_recipient'        => '',
			'alert_batch_limit'            => 50,
			'calculation_frequency'        => 'daily',
			'min_orders_for_prediction'    => 2,
			'historical_window_days'       => 365,
			'confidence_threshold'         => 50,
		);
	}

	/**
	 * Get a specific setting
	 *
	 * @since    4.0.0
	 * @param    string    $key        Setting key
	 * @param    mixed     $default    Default value
	 * @return   mixed
	 */
	public function pdwchs_get_setting( $key, $default = null ) {
		if ( $this->settings === null ) {
			$this->pdwchs_load_settings();
		}
		return isset( $this->settings[ $key ] ) ? $this->settings[ $key ] : $default;
	}

	/**
	 * Calculate churn score for a customer
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    WordPress user ID
	 * @return   array|false    Churn data array or false on failure
	 */
	public function pdwchs_calculate_churn_score( $customer_id ) {
		if ( ! $customer_id ) {
			return false;
		}

		// Get customer analysis data
		$customer_data = $this->pdwchs_get_customer_analysis_data( $customer_id );

		if ( ! $customer_data || $customer_data['total_orders'] < $this->pdwchs_get_setting( 'min_orders_for_prediction', 2 ) ) {
			return array(
				'customer_id'           => $customer_id,
				'churn_score'           => 0,
				'risk_level'            => 'low',
				'has_sufficient_data'   => false,
				'message'               => __( 'Insufficient order history for prediction', 'puredevs-customer-history-for-woocommerce' ),
			);
		}

		// Calculate component scores
		$recency_score    = $this->pdwchs_calculate_recency_score( $customer_data );
		$frequency_score  = $this->pdwchs_calculate_frequency_score( $customer_data );
		$monetary_score   = $this->pdwchs_calculate_monetary_score( $customer_data );
		$engagement_score = $this->pdwchs_calculate_engagement_score( $customer_data );

		// Calculate weighted churn score
		$weights = array(
			'recency'    => $this->pdwchs_get_setting( 'weight_recency', 40 ),
			'frequency'  => $this->pdwchs_get_setting( 'weight_frequency', 25 ),
			'monetary'   => $this->pdwchs_get_setting( 'weight_monetary', 20 ),
			'engagement' => $this->pdwchs_get_setting( 'weight_engagement', 15 ),
		);

		$churn_score = (
			( $recency_score * $weights['recency'] ) +
			( $frequency_score * $weights['frequency'] ) +
			( $monetary_score * $weights['monetary'] ) +
			( $engagement_score * $weights['engagement'] )
		) / 100;

		$churn_score = min( 100, max( 0, round( $churn_score ) ) );

		// Determine risk level
		$risk_level = $this->pdwchs_determine_risk_level( $churn_score );

		// Calculate prediction confidence
		$confidence = $this->pdwchs_calculate_prediction_confidence( $customer_data, $churn_score );

		// Determine primary indicator
		$primary_indicator = $this->pdwchs_determine_primary_indicator(
			$recency_score,
			$frequency_score,
			$monetary_score,
			$engagement_score
		);

		// Build churn data array
		$churn_data = array(
			'customer_id'            => $customer_id,
			'churn_score'            => $churn_score,
			'risk_level'             => $risk_level,
			'recency_score'          => $recency_score,
			'frequency_score'        => $frequency_score,
			'monetary_score'         => $monetary_score,
			'engagement_score'       => $engagement_score,
			'days_since_last_order'  => $customer_data['days_since_last_order'],
			'expected_next_purchase' => $customer_data['expected_next_purchase_days'],
			'days_overdue'           => $customer_data['days_overdue'],
			'total_orders'           => $customer_data['total_orders'],
			'total_spent'            => $customer_data['total_spent'],
			'avg_order_value'        => $customer_data['avg_order_value'],
			'order_frequency_days'   => $customer_data['order_frequency_days'],
			'frequency_decline_pct'  => $customer_data['frequency_decline_pct'],
			'spending_decline_pct'   => $customer_data['spending_decline_pct'],
			'total_sessions'         => $customer_data['total_sessions'],
			'sessions_last_30_days'  => $customer_data['sessions_last_30_days'],
			'engagement_decline_pct' => $customer_data['engagement_decline_pct'],
			'cart_abandonment_rate'  => $customer_data['cart_abandonment_rate'],
			'prediction_confidence'  => $confidence,
			'primary_churn_indicator' => $primary_indicator,
			'has_sufficient_data'    => true,
			'risk_level_info'        => $this->pdwchs_get_risk_level_info( $risk_level ),
		);

		// Save to database
		$this->pdwchs_save_churn_score( $churn_data );

		return $churn_data;
	}

	/**
	 * Get customer analysis data
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    Customer ID
	 * @return   array
	 */
	public function pdwchs_get_customer_analysis_data( $customer_id ) {
		global $wpdb;

		$historical_days = $this->pdwchs_get_setting( 'historical_window_days', 365 );

		// Get order data
		$order_data = $wpdb->get_row( $wpdb->prepare( "
			SELECT
				COUNT(id) as total_orders,
				COALESCE(SUM(total_amount), 0) as total_spent,
				COALESCE(AVG(total_amount), 0) as avg_order_value,
				MIN(date_created_gmt) as first_order_date,
				MAX(date_created_gmt) as last_order_date,
				DATEDIFF(NOW(), MAX(date_created_gmt)) as days_since_last_order
			FROM {$wpdb->prefix}wc_orders
			WHERE customer_id = %d
			AND status IN ('wc-completed', 'wc-processing')
			AND date_created_gmt >= DATE_SUB(NOW(), INTERVAL %d DAY)
		", $customer_id, $historical_days ) );

		if ( ! $order_data || $order_data->total_orders == 0 ) {
			return array(
				'total_orders'              => 0,
				'total_spent'               => 0,
				'avg_order_value'           => 0,
				'days_since_last_order'     => 999,
				'expected_next_purchase_days' => 30,
				'days_overdue'              => 0,
				'order_frequency_days'      => null,
				'frequency_decline_pct'     => 0,
				'spending_decline_pct'      => 0,
				'total_sessions'            => 0,
				'sessions_last_30_days'     => 0,
				'engagement_decline_pct'    => 0,
				'cart_abandonment_rate'     => 0,
			);
		}

		// Calculate order frequency
		$order_frequency_days = null;
		if ( $order_data->total_orders > 1 ) {
			$days_span = $wpdb->get_var( $wpdb->prepare( "
				SELECT DATEDIFF(MAX(date_created_gmt), MIN(date_created_gmt))
				FROM {$wpdb->prefix}wc_orders
				WHERE customer_id = %d
				AND status IN ('wc-completed', 'wc-processing')
			", $customer_id ) );

			if ( $days_span > 0 ) {
				$order_frequency_days = $days_span / ( $order_data->total_orders - 1 );
			}
		}

		// Calculate expected next purchase and days overdue
		$expected_next_purchase_days = $order_frequency_days ? round( $order_frequency_days ) : 30;
		$days_overdue = $order_data->days_since_last_order - $expected_next_purchase_days;

		// Calculate frequency decline (compare last 90 days vs previous 90 days)
		$recent_orders = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(id)
			FROM {$wpdb->prefix}wc_orders
			WHERE customer_id = %d
			AND status IN ('wc-completed', 'wc-processing')
			AND date_created_gmt >= DATE_SUB(NOW(), INTERVAL 90 DAY)
		", $customer_id ) );

		$previous_orders = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(id)
			FROM {$wpdb->prefix}wc_orders
			WHERE customer_id = %d
			AND status IN ('wc-completed', 'wc-processing')
			AND date_created_gmt >= DATE_SUB(NOW(), INTERVAL 180 DAY)
			AND date_created_gmt < DATE_SUB(NOW(), INTERVAL 90 DAY)
		", $customer_id ) );

		$frequency_decline_pct = 0;
		if ( $previous_orders > 0 ) {
			$frequency_decline_pct = ( ( $previous_orders - $recent_orders ) / $previous_orders ) * 100;
		}

		// Calculate spending decline
		$recent_spending = $wpdb->get_var( $wpdb->prepare( "
			SELECT COALESCE(SUM(total_amount), 0)
			FROM {$wpdb->prefix}wc_orders
			WHERE customer_id = %d
			AND status IN ('wc-completed', 'wc-processing')
			AND date_created_gmt >= DATE_SUB(NOW(), INTERVAL 90 DAY)
		", $customer_id ) );

		$previous_spending = $wpdb->get_var( $wpdb->prepare( "
			SELECT COALESCE(SUM(total_amount), 0)
			FROM {$wpdb->prefix}wc_orders
			WHERE customer_id = %d
			AND status IN ('wc-completed', 'wc-processing')
			AND date_created_gmt >= DATE_SUB(NOW(), INTERVAL 180 DAY)
			AND date_created_gmt < DATE_SUB(NOW(), INTERVAL 90 DAY)
		", $customer_id ) );

		$spending_decline_pct = 0;
		if ( $previous_spending > 0 ) {
			$spending_decline_pct = ( ( $previous_spending - $recent_spending ) / $previous_spending ) * 100;
		}

		// Get engagement data from sessions table
		$sessions_table = $wpdb->prefix . 'pd_woo_customer_history_sessions';
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $sessions_table ) );

		$total_sessions = 0;
		$sessions_last_30_days = 0;
		$sessions_previous_30_days = 0;
		$engagement_decline_pct = 0;

		if ( $table_exists ) {
			$total_sessions = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(id)
				FROM {$sessions_table}
				WHERE user_id = %d AND del = 0
			", $customer_id ) );

			$sessions_last_30_days = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(id)
				FROM {$sessions_table}
				WHERE user_id = %d
				AND del = 0
				AND reg_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			", $customer_id ) );

			$sessions_previous_30_days = $wpdb->get_var( $wpdb->prepare( "
				SELECT COUNT(id)
				FROM {$sessions_table}
				WHERE user_id = %d
				AND del = 0
				AND reg_date >= DATE_SUB(NOW(), INTERVAL 60 DAY)
				AND reg_date < DATE_SUB(NOW(), INTERVAL 30 DAY)
			", $customer_id ) );

			if ( $sessions_previous_30_days > 0 ) {
				$engagement_decline_pct = ( ( $sessions_previous_30_days - $sessions_last_30_days ) / $sessions_previous_30_days ) * 100;
			}
		}

		// Calculate cart abandonment rate (simplified - based on pending orders)
		$pending_orders = $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(id)
			FROM {$wpdb->prefix}wc_orders
			WHERE customer_id = %d
			AND status IN ('wc-pending', 'wc-failed', 'wc-cancelled')
			AND date_created_gmt >= DATE_SUB(NOW(), INTERVAL 90 DAY)
		", $customer_id ) );

		$cart_abandonment_rate = 0;
		$total_recent = $recent_orders + $pending_orders;
		if ( $total_recent > 0 ) {
			$cart_abandonment_rate = ( $pending_orders / $total_recent ) * 100;
		}

		return array(
			'total_orders'              => intval( $order_data->total_orders ),
			'total_spent'               => floatval( $order_data->total_spent ),
			'avg_order_value'           => floatval( $order_data->avg_order_value ),
			'days_since_last_order'     => intval( $order_data->days_since_last_order ),
			'expected_next_purchase_days' => $expected_next_purchase_days,
			'days_overdue'              => $days_overdue,
			'order_frequency_days'      => $order_frequency_days,
			'frequency_decline_pct'     => round( max( 0, $frequency_decline_pct ), 2 ),
			'spending_decline_pct'      => round( max( 0, $spending_decline_pct ), 2 ),
			'total_sessions'            => intval( $total_sessions ),
			'sessions_last_30_days'     => intval( $sessions_last_30_days ),
			'engagement_decline_pct'    => round( max( 0, $engagement_decline_pct ), 2 ),
			'cart_abandonment_rate'     => round( $cart_abandonment_rate, 2 ),
		);
	}

	/**
	 * Calculate recency score
	 *
	 * @since    4.0.0
	 * @param    array    $customer_data    Customer analysis data
	 * @return   int    Score 0-100
	 */
	private function pdwchs_calculate_recency_score( $customer_data ) {
		$days = $customer_data['days_since_last_order'];
		$days_overdue = $customer_data['days_overdue'];

		$excellent_max = $this->pdwchs_get_setting( 'recency_excellent_max', 30 );
		$good_max = $this->pdwchs_get_setting( 'recency_good_max', 60 );
		$fair_max = $this->pdwchs_get_setting( 'recency_fair_max', 90 );
		$critical_threshold = $this->pdwchs_get_setting( 'recency_critical_threshold', 180 );

		// Base score on days since last order
		if ( $days <= $excellent_max ) {
			$score = 0;
		} elseif ( $days <= $good_max ) {
			$score = ( ( $days - $excellent_max ) / ( $good_max - $excellent_max ) ) * 30;
		} elseif ( $days <= $fair_max ) {
			$score = 30 + ( ( $days - $good_max ) / ( $fair_max - $good_max ) ) * 30;
		} elseif ( $days <= $critical_threshold ) {
			$score = 60 + ( ( $days - $fair_max ) / ( $critical_threshold - $fair_max ) ) * 25;
		} else {
			$score = 85 + min( 15, ( $days - $critical_threshold ) / 30 * 5 );
		}

		// Apply overdue multiplier
		if ( $days_overdue > 0 ) {
			$overdue_multiplier = 1 + ( min( 60, $days_overdue ) / 60 ) * 0.3;
			$score = $score * $overdue_multiplier;
		}

		return min( 100, max( 0, round( $score ) ) );
	}

	/**
	 * Calculate frequency score
	 *
	 * @since    4.0.0
	 * @param    array    $customer_data    Customer analysis data
	 * @return   int    Score 0-100
	 */
	private function pdwchs_calculate_frequency_score( $customer_data ) {
		$decline_pct = $customer_data['frequency_decline_pct'];
		$warning_threshold = $this->pdwchs_get_setting( 'frequency_decline_warning', 25 );
		$critical_threshold = $this->pdwchs_get_setting( 'frequency_decline_critical', 50 );

		if ( $decline_pct <= 0 ) {
			return 0;
		}

		if ( $decline_pct <= $warning_threshold ) {
			$score = ( $decline_pct / $warning_threshold ) * 40;
		} elseif ( $decline_pct <= $critical_threshold ) {
			$score = 40 + ( ( $decline_pct - $warning_threshold ) / ( $critical_threshold - $warning_threshold ) ) * 35;
		} else {
			$score = 75 + min( 25, ( $decline_pct - $critical_threshold ) / 25 * 25 );
		}

		// If complete stop (100% decline), max score
		if ( $decline_pct >= 100 ) {
			$score = 100;
		}

		return min( 100, max( 0, round( $score ) ) );
	}

	/**
	 * Calculate monetary score
	 *
	 * @since    4.0.0
	 * @param    array    $customer_data    Customer analysis data
	 * @return   int    Score 0-100
	 */
	private function pdwchs_calculate_monetary_score( $customer_data ) {
		$decline_pct = $customer_data['spending_decline_pct'];

		if ( $decline_pct <= 0 ) {
			return 0;
		}

		if ( $decline_pct <= 25 ) {
			$score = ( $decline_pct / 25 ) * 35;
		} elseif ( $decline_pct <= 50 ) {
			$score = 35 + ( ( $decline_pct - 25 ) / 25 ) * 30;
		} elseif ( $decline_pct <= 75 ) {
			$score = 65 + ( ( $decline_pct - 50 ) / 25 ) * 20;
		} else {
			$score = 85 + min( 15, ( $decline_pct - 75 ) / 25 * 15 );
		}

		// If complete stop (100% decline), max score
		if ( $decline_pct >= 100 ) {
			$score = 100;
		}

		return min( 100, max( 0, round( $score ) ) );
	}

	/**
	 * Calculate engagement score
	 *
	 * @since    4.0.0
	 * @param    array    $customer_data    Customer analysis data
	 * @return   int    Score 0-100
	 */
	private function pdwchs_calculate_engagement_score( $customer_data ) {
		$engagement_decline = $customer_data['engagement_decline_pct'];
		$cart_abandonment = $customer_data['cart_abandonment_rate'];
		$sessions_30_days = $customer_data['sessions_last_30_days'];

		$score = 0;

		// Factor 1: Engagement decline (60% weight)
		if ( $engagement_decline > 0 ) {
			if ( $engagement_decline <= 30 ) {
				$score += ( $engagement_decline / 30 ) * 25;
			} elseif ( $engagement_decline <= 60 ) {
				$score += 25 + ( ( $engagement_decline - 30 ) / 30 ) * 20;
			} else {
				$score += 45 + min( 15, ( $engagement_decline - 60 ) / 40 * 15 );
			}
		}

		// Factor 2: No recent sessions (20% weight)
		if ( $sessions_30_days == 0 && $customer_data['total_sessions'] > 0 ) {
			$score += 20;
		}

		// Factor 3: Cart abandonment (20% weight)
		if ( $cart_abandonment >= 50 ) {
			$score += 20;
		} elseif ( $cart_abandonment >= 25 ) {
			$score += ( $cart_abandonment / 50 ) * 20;
		}

		return min( 100, max( 0, round( $score ) ) );
	}

	/**
	 * Determine risk level from churn score
	 *
	 * @since    4.0.0
	 * @param    int    $churn_score    Score 0-100
	 * @return   string    Risk level key
	 */
	public function pdwchs_determine_risk_level( $churn_score ) {
		$low_max = $this->pdwchs_get_setting( 'risk_threshold_low_max', 30 );
		$medium_max = $this->pdwchs_get_setting( 'risk_threshold_medium_max', 60 );
		$high_max = $this->pdwchs_get_setting( 'risk_threshold_high_max', 80 );

		if ( $churn_score <= $low_max ) {
			return 'low';
		} elseif ( $churn_score <= $medium_max ) {
			return 'medium';
		} elseif ( $churn_score <= $high_max ) {
			return 'high';
		} else {
			return 'critical';
		}
	}

	/**
	 * Calculate prediction confidence
	 *
	 * @since    4.0.0
	 * @param    array    $customer_data    Customer analysis data
	 * @param    int      $churn_score      Calculated churn score
	 * @return   int    Confidence 0-100
	 */
	private function pdwchs_calculate_prediction_confidence( $customer_data, $churn_score ) {
		$confidence = 50; // Base confidence

		// Factor 1: Order history depth (+20 max)
		$orders = $customer_data['total_orders'];
		if ( $orders >= 10 ) {
			$confidence += 20;
		} elseif ( $orders >= 5 ) {
			$confidence += 15;
		} elseif ( $orders >= 3 ) {
			$confidence += 10;
		}

		// Factor 2: Pattern consistency (+15 max)
		if ( $customer_data['order_frequency_days'] !== null ) {
			$confidence += 10;
			if ( $customer_data['order_frequency_days'] < 60 ) {
				$confidence += 5; // Frequent buyers have clearer patterns
			}
		}

		// Factor 3: Engagement data availability (+10 max)
		if ( $customer_data['total_sessions'] > 10 ) {
			$confidence += 10;
		} elseif ( $customer_data['total_sessions'] > 0 ) {
			$confidence += 5;
		}

		// Factor 4: Score clarity (+5 max)
		// Very low or very high scores are more confident
		if ( $churn_score <= 15 || $churn_score >= 85 ) {
			$confidence += 5;
		}

		return min( 100, max( 0, $confidence ) );
	}

	/**
	 * Determine primary churn indicator
	 *
	 * @since    4.0.0
	 * @param    int    $recency_score      Recency component score
	 * @param    int    $frequency_score    Frequency component score
	 * @param    int    $monetary_score     Monetary component score
	 * @param    int    $engagement_score   Engagement component score
	 * @return   string    Primary indicator key
	 */
	private function pdwchs_determine_primary_indicator( $recency_score, $frequency_score, $monetary_score, $engagement_score ) {
		$indicators = array(
			'recency'    => $recency_score,
			'frequency'  => $frequency_score,
			'monetary'   => $monetary_score,
			'engagement' => $engagement_score,
		);

		arsort( $indicators );
		return key( $indicators );
	}

	/**
	 * Get risk level information
	 *
	 * @since    4.0.0
	 * @param    string    $risk_level    Risk level key
	 * @return   array
	 */
	public function pdwchs_get_risk_level_info( $risk_level ) {
		return isset( $this->risk_levels[ $risk_level ] )
			? $this->risk_levels[ $risk_level ]
			: $this->risk_levels['low'];
	}

	/**
	 * Save churn score to database
	 *
	 * @since    4.0.0
	 * @param    array    $churn_data    Churn data array
	 */
	private function pdwchs_save_churn_score( $churn_data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_churn_scores';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		if ( ! $table_exists ) {
			return;
		}

		// Get existing score for history tracking
		$existing = $wpdb->get_row( $wpdb->prepare(
			"SELECT churn_score, risk_level FROM {$table} WHERE customer_id = %d",
			$churn_data['customer_id']
		) );

		$data = array(
			'customer_id'            => $churn_data['customer_id'],
			'churn_score'            => $churn_data['churn_score'],
			'risk_level'             => $churn_data['risk_level'],
			'recency_score'          => $churn_data['recency_score'],
			'frequency_score'        => $churn_data['frequency_score'],
			'monetary_score'         => $churn_data['monetary_score'],
			'engagement_score'       => $churn_data['engagement_score'],
			'days_since_last_order'  => $churn_data['days_since_last_order'],
			'expected_next_purchase_days' => $churn_data['expected_next_purchase'],
			'days_overdue'           => $churn_data['days_overdue'],
			'total_orders'           => $churn_data['total_orders'],
			'total_spent'            => $churn_data['total_spent'],
			'avg_order_value'        => $churn_data['avg_order_value'],
			'order_frequency_days'   => $churn_data['order_frequency_days'],
			'frequency_decline_pct'  => $churn_data['frequency_decline_pct'],
			'spending_decline_pct'   => $churn_data['spending_decline_pct'],
			'total_sessions'         => $churn_data['total_sessions'],
			'sessions_last_30_days'  => $churn_data['sessions_last_30_days'],
			'engagement_decline_pct' => $churn_data['engagement_decline_pct'],
			'cart_abandonment_rate'  => $churn_data['cart_abandonment_rate'],
			'prediction_confidence'  => $churn_data['prediction_confidence'],
			'primary_churn_indicator' => $churn_data['primary_churn_indicator'],
			'last_calculated'        => current_time( 'mysql' ),
		);

		// Insert or update
		$wpdb->replace( $table, $data );

		// Record history if score changed significantly
		if ( $existing && abs( $existing->churn_score - $churn_data['churn_score'] ) >= 10 ) {
			$this->pdwchs_record_history( $churn_data, $existing );
		}
	}

	/**
	 * Record churn history
	 *
	 * @since    4.0.0
	 * @param    array     $churn_data    New churn data
	 * @param    object    $previous      Previous churn data
	 */
	private function pdwchs_record_history( $churn_data, $previous ) {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_churn_history';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		if ( ! $table_exists ) {
			return;
		}

		$score_change = $churn_data['churn_score'] - $previous->churn_score;

		// Determine transition type
		$transition_type = null;
		if ( $score_change > 0 ) {
			$transition_type = 'escalated';
		} elseif ( $score_change < 0 ) {
			$transition_type = 'de_escalated';
		}

		$wpdb->insert( $table, array(
			'customer_id'         => $churn_data['customer_id'],
			'churn_score'         => $churn_data['churn_score'],
			'previous_score'      => $previous->churn_score,
			'risk_level'          => $churn_data['risk_level'],
			'previous_risk_level' => $previous->risk_level,
			'score_change'        => $score_change,
			'customer_status'     => 'active',
			'transition_type'     => $transition_type,
		) );
	}

	/**
	 * Get customer churn data
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    Customer ID
	 * @return   object|null
	 */
	public function pdwchs_get_customer_churn_data( $customer_id ) {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_churn_scores';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		if ( ! $table_exists ) {
			return null;
		}

		$data = $wpdb->get_row( $wpdb->prepare(
			"SELECT * FROM {$table} WHERE customer_id = %d",
			$customer_id
		) );

		if ( $data ) {
			$data->risk_level_info = $this->pdwchs_get_risk_level_info( $data->risk_level );
		}

		return $data;
	}

	/**
	 * Derive the predicted next-order timestamp from churn data.
	 *
	 * Costs no extra query — it reconstructs the date from figures already present:
	 * the last order was days_since_last_order ago, and the customer is expected to
	 * buy again expected_next_purchase_days after that. The result is in the past for
	 * an overdue customer and in the future for one still on schedule.
	 *
	 * Accepts either shape the churn data arrives in:
	 *  - the {$prefix}pdwchs_churn_scores row, which has `expected_next_purchase_days`;
	 *  - the array from pdwchs_calculate_churn_score(), which has `expected_next_purchase`
	 *    (pdwchs_save_churn_score() bridges the two names).
	 *
	 * Guards against the zero-order case: pdwchs_get_customer_data() returns a
	 * days_since_last_order sentinel of 999 for a customer with no completed orders,
	 * which would otherwise yield a date years in the past. The `has_sufficient_data`
	 * flag cannot be relied on here — it exists only on the freshly-calculated array,
	 * never on a stored row — so total_orders is the check that works for both.
	 *
	 * @since    3.0.2
	 * @param    object|array    $churn_data    Churn data row or array.
	 * @return   int|null                       UTC timestamp, or null when not derivable.
	 */
	public function pdwchs_get_predicted_next_order_ts( $churn_data ) {
		$data = is_object( $churn_data ) ? get_object_vars( $churn_data ) : (array) $churn_data;

		if ( empty( $data['total_orders'] ) || ! isset( $data['days_since_last_order'] ) ) {
			return null;
		}

		$expected = 0;
		if ( isset( $data['expected_next_purchase_days'] ) ) {
			$expected = intval( $data['expected_next_purchase_days'] );
		} elseif ( isset( $data['expected_next_purchase'] ) ) {
			$expected = intval( $data['expected_next_purchase'] );
		}

		if ( $expected <= 0 ) {
			return null;
		}

		$days_since = intval( $data['days_since_last_order'] );

		// days_since - expected is the overdue amount (negative when still on schedule),
		// so one expression covers both directions. Returns a UTC timestamp for wp_date().
		return time() - ( ( $days_since - $expected ) * DAY_IN_SECONDS );
	}

	/**
	 * Batch calculate churn scores for all customers
	 *
	 * @since    4.0.0
	 * @param    int    $batch_size    Number of customers per batch
	 * @return   array    Results
	 */
	public function pdwchs_batch_calculate_all( $batch_size = 50 ) {
		global $wpdb;

		$batch_size = absint( $batch_size ) ?: 50;
		$offset     = absint( get_option( 'pdwchs_churn_batch_offset', 0 ) );

		// Get customers needing calculation
		$customers = $wpdb->get_col( $wpdb->prepare( "
			SELECT DISTINCT o.customer_id
			FROM {$wpdb->prefix}wc_orders o
			LEFT JOIN {$wpdb->prefix}pdwchs_churn_scores cs ON o.customer_id = cs.customer_id
			WHERE o.customer_id > 0
			AND o.status IN ('wc-completed', 'wc-processing')
			AND (cs.last_calculated IS NULL
				 OR cs.last_calculated < DATE_SUB(NOW(), INTERVAL 24 HOUR)
				 OR o.date_updated_gmt > cs.last_calculated)
			GROUP BY o.customer_id
			HAVING COUNT(o.id) >= %d
			ORDER BY MAX(o.date_created_gmt) DESC
			LIMIT %d OFFSET %d
		", $this->pdwchs_get_setting( 'min_orders_for_prediction', 2 ), $batch_size, $offset ) );

		$processed = 0;
		$errors = 0;

		foreach ( $customers as $customer_id ) {
			try {
				$this->pdwchs_calculate_churn_score( $customer_id );
				$processed++;
			} catch ( Exception $e ) {
				$errors++;
			}
		}

		// Update offset for next batch
		if ( count( $customers ) < $batch_size ) {
			// Reset offset if we've processed all
			delete_option( 'pdwchs_churn_batch_offset' );
		} else {
			update_option( 'pdwchs_churn_batch_offset', $offset + $batch_size );
		}

		return array(
			'processed' => $processed,
			'errors'    => $errors,
			'offset'    => $offset,
			'batch_size' => $batch_size,
		);
	}

	/**
	 * Get churn risk distribution statistics
	 *
	 * @since    4.0.0
	 * @return   array
	 */
	public function pdwchs_get_risk_distribution() {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_churn_scores';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		if ( ! $table_exists ) {
			return array(
				'low'      => 0,
				'medium'   => 0,
				'high'     => 0,
				'critical' => 0,
				'total'    => 0,
			);
		}

		$results = $wpdb->get_results( "
			SELECT risk_level, COUNT(*) as count
			FROM {$table}
			GROUP BY risk_level
		" );

		$distribution = array(
			'low'      => 0,
			'medium'   => 0,
			'high'     => 0,
			'critical' => 0,
		);

		foreach ( $results as $row ) {
			if ( isset( $distribution[ $row->risk_level ] ) ) {
				$distribution[ $row->risk_level ] = intval( $row->count );
			}
		}

		$distribution['total'] = array_sum( $distribution );

		return $distribution;
	}

	/**
	 * Get high risk customers for dashboard
	 *
	 * @since    4.0.0
	 * @param    int    $limit    Number of customers to return
	 * @return   array
	 */
	public function pdwchs_get_high_risk_customers( $limit = 10 ) {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_churn_scores';

		// Check if table exists
		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
		if ( ! $table_exists ) {
			return array();
		}

		$customers = $wpdb->get_results( $wpdb->prepare( "
			SELECT cs.*, u.display_name, u.user_email
			FROM {$table} cs
			JOIN {$wpdb->users} u ON cs.customer_id = u.ID
			WHERE cs.risk_level IN ('high', 'critical')
			ORDER BY cs.churn_score DESC, cs.total_spent DESC
			LIMIT %d
		", $limit ) );

		foreach ( $customers as &$customer ) {
			$customer->risk_level_info = $this->pdwchs_get_risk_level_info( $customer->risk_level );
		}

		return $customers;
	}

	/**
	 * Get indicator label
	 *
	 * @since    4.0.0
	 * @param    string    $indicator    Indicator key
	 * @return   string
	 */
	public function pdwchs_get_indicator_label( $indicator ) {
		$labels = array(
			'recency'    => __( 'Extended absence', 'puredevs-customer-history-for-woocommerce' ),
			'frequency'  => __( 'Declining purchase frequency', 'puredevs-customer-history-for-woocommerce' ),
			'monetary'   => __( 'Declining spending', 'puredevs-customer-history-for-woocommerce' ),
			'engagement' => __( 'Reduced engagement', 'puredevs-customer-history-for-woocommerce' ),
		);

		return isset( $labels[ $indicator ] ) ? $labels[ $indicator ] : $indicator;
	}
}
