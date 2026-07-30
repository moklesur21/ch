<?php
/**
 * Churn Predictor - Ensemble Prediction Model
 *
 * Aggregates multiple prediction models (behavioral, statistical, RFM,
 * engagement) to produce refined churn predictions.
 *
 * @link       https://puredevs.com/
 * @since      4.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * Churn Predictor Class
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Churn_Predictor {

	/**
	 * Churn calculator instance
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      PD_Customer_History_Churn
	 */
	private $churn_calculator;

	/**
	 * RFM calculator instance
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      PD_Customer_History_RFM
	 */
	private $rfm_calculator;

	/**
	 * Model weights for ensemble prediction
	 *
	 * @since    4.0.0
	 * @access   private
	 * @var      array
	 */
	private $model_weights = array(
		'behavioral'  => 0.30,
		'statistical' => 0.25,
		'rfm'         => 0.25,
		'engagement'  => 0.20,
	);

	/**
	 * Constructor
	 *
	 * @since    4.0.0
	 * @param    PD_Customer_History_Churn    $churn_calculator    Churn calculator instance
	 * @param    PD_Customer_History_RFM      $rfm_calculator      RFM calculator instance
	 */
	public function __construct( $churn_calculator, $rfm_calculator ) {
		$this->churn_calculator = $churn_calculator;
		$this->rfm_calculator   = $rfm_calculator;
	}

	/**
	 * Predict churn by aggregating multiple models
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    WordPress user ID
	 * @return   array    Prediction data
	 */
	public function predict_churn( $customer_id ) {
		if ( ! $customer_id ) {
			return array( 'error' => true, 'message' => __( 'Invalid customer ID', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$customer_data = $this->churn_calculator->pdwchs_get_customer_analysis_data( $customer_id );

		$min_orders = $this->churn_calculator->pdwchs_get_setting( 'min_orders_for_prediction', 2 );
		if ( ! $customer_data || $customer_data['total_orders'] < $min_orders ) {
			return array(
				'customer_id'       => $customer_id,
				'ensemble_score'    => 0,
				'risk_level'        => 'low',
				'has_sufficient_data' => false,
				'message'           => __( 'Insufficient order history for prediction', 'puredevs-customer-history-for-woocommerce' ),
			);
		}

		// Run individual models
		$behavioral_score  = $this->behavioral_model( $customer_id, $customer_data );
		$statistical_score = $this->statistical_model( $customer_id, $customer_data );
		$rfm_score         = $this->rfm_model( $customer_id );
		$engagement_score  = $this->engagement_model( $customer_id, $customer_data );

		// Calculate weighted ensemble score
		$ensemble_score = (
			( $behavioral_score['score'] * $this->model_weights['behavioral'] ) +
			( $statistical_score['score'] * $this->model_weights['statistical'] ) +
			( $rfm_score['score'] * $this->model_weights['rfm'] ) +
			( $engagement_score['score'] * $this->model_weights['engagement'] )
		);

		$ensemble_score = min( 100, max( 0, round( $ensemble_score ) ) );

		// Determine risk level
		$risk_level = $this->churn_calculator->pdwchs_determine_risk_level( $ensemble_score );

		// Calculate agreement between models
		$model_scores = array(
			$behavioral_score['score'],
			$statistical_score['score'],
			$rfm_score['score'],
			$engagement_score['score'],
		);
		$model_agreement = $this->calculate_model_agreement( $model_scores );

		return array(
			'customer_id'        => $customer_id,
			'ensemble_score'     => $ensemble_score,
			'risk_level'         => $risk_level,
			'risk_level_info'    => $this->churn_calculator->pdwchs_get_risk_level_info( $risk_level ),
			'has_sufficient_data' => true,
			'model_scores'       => array(
				'behavioral'  => $behavioral_score,
				'statistical' => $statistical_score,
				'rfm'         => $rfm_score,
				'engagement'  => $engagement_score,
			),
			'model_agreement'    => $model_agreement,
		);
	}

	/**
	 * Behavioral model - pattern analysis
	 *
	 * Analyzes purchasing and browsing patterns to detect churn signals.
	 *
	 * @since    4.0.0
	 * @param    int      $customer_id      Customer ID
	 * @param    array    $customer_data    Pre-fetched customer data (optional)
	 * @return   array    Score and details
	 */
	public function behavioral_model( $customer_id, $customer_data = null ) {
		if ( ! $customer_data ) {
			$customer_data = $this->churn_calculator->pdwchs_get_customer_analysis_data( $customer_id );
		}

		$score = 0;

		// Factor 1: Purchase frequency decline (35% weight)
		$freq_decline = $customer_data['frequency_decline_pct'];
		if ( $freq_decline > 0 ) {
			$score += min( 35, ( $freq_decline / 100 ) * 35 );
		}

		// Factor 2: Spending decline (25% weight)
		$spend_decline = $customer_data['spending_decline_pct'];
		if ( $spend_decline > 0 ) {
			$score += min( 25, ( $spend_decline / 100 ) * 25 );
		}

		// Factor 3: Cart abandonment rate (20% weight)
		$abandonment = $customer_data['cart_abandonment_rate'];
		if ( $abandonment > 0 ) {
			$score += min( 20, ( $abandonment / 100 ) * 20 );
		}

		// Factor 4: Days overdue relative to expected purchase (20% weight)
		$days_overdue = $customer_data['days_overdue'];
		if ( $days_overdue > 0 ) {
			$expected = max( 1, $customer_data['expected_next_purchase_days'] );
			$overdue_ratio = min( 3, $days_overdue / $expected );
			$score += ( $overdue_ratio / 3 ) * 20;
		}

		$score = min( 100, max( 0, round( $score ) ) );

		return array(
			'score'   => $score,
			'factors' => array(
				'frequency_decline'  => $freq_decline,
				'spending_decline'   => $spend_decline,
				'cart_abandonment'   => $abandonment,
				'days_overdue'       => $days_overdue,
			),
		);
	}

	/**
	 * Statistical model - Z-score deviation analysis
	 *
	 * Compares customer metrics against store-wide averages
	 * using standard deviation analysis.
	 *
	 * @since    4.0.0
	 * @param    int      $customer_id      Customer ID
	 * @param    array    $customer_data    Pre-fetched customer data (optional)
	 * @return   array    Score and details
	 */
	public function statistical_model( $customer_id, $customer_data = null ) {
		global $wpdb;

		if ( ! $customer_data ) {
			$customer_data = $this->churn_calculator->pdwchs_get_customer_analysis_data( $customer_id );
		}

		// Get store-wide averages
		$store_stats = $wpdb->get_row( "
			SELECT
				AVG(days_since) as avg_recency,
				STDDEV(days_since) as std_recency,
				AVG(order_count) as avg_frequency,
				STDDEV(order_count) as std_frequency,
				AVG(total_amount) as avg_monetary,
				STDDEV(total_amount) as std_monetary
			FROM (
				SELECT
					customer_id,
					DATEDIFF(NOW(), MAX(date_created_gmt)) as days_since,
					COUNT(id) as order_count,
					SUM(total_amount) as total_amount
				FROM {$wpdb->prefix}wc_orders
				WHERE customer_id > 0
				AND status IN ('wc-completed', 'wc-processing')
				GROUP BY customer_id
				HAVING COUNT(id) >= 2
			) customer_stats
		" );

		if ( ! $store_stats || ! $store_stats->std_recency ) {
			return array( 'score' => 50, 'factors' => array( 'message' => 'Insufficient store data' ) );
		}

		// Calculate Z-scores (positive = worse than average)
		$z_recency = 0;
		if ( $store_stats->std_recency > 0 ) {
			$z_recency = ( $customer_data['days_since_last_order'] - $store_stats->avg_recency ) / $store_stats->std_recency;
		}

		$z_frequency = 0;
		if ( $store_stats->std_frequency > 0 ) {
			// Inverted: fewer orders = higher churn risk
			$z_frequency = ( $store_stats->avg_frequency - $customer_data['total_orders'] ) / $store_stats->std_frequency;
		}

		$z_monetary = 0;
		if ( $store_stats->std_monetary > 0 ) {
			// Inverted: less spending = higher churn risk
			$z_monetary = ( $store_stats->avg_monetary - $customer_data['total_spent'] ) / $store_stats->std_monetary;
		}

		// Convert Z-scores to a 0-100 risk score
		// Z > 2 means significantly worse than average
		$avg_z = ( max( 0, $z_recency ) + max( 0, $z_frequency ) + max( 0, $z_monetary ) ) / 3;
		$score = min( 100, max( 0, round( $avg_z * 25 ) ) ); // 4 std deviations = 100

		return array(
			'score'   => $score,
			'factors' => array(
				'z_recency'   => round( $z_recency, 2 ),
				'z_frequency' => round( $z_frequency, 2 ),
				'z_monetary'  => round( $z_monetary, 2 ),
				'avg_z_score' => round( $avg_z, 2 ),
			),
		);
	}

	/**
	 * RFM model - leverage existing RFM analysis
	 *
	 * Translates RFM segment data into churn risk score.
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    Customer ID
	 * @return   array    Score and details
	 */
	public function rfm_model( $customer_id ) {
		$rfm_data = $this->rfm_calculator->pdwchs_get_customer_rfm( $customer_id );

		if ( ! $rfm_data ) {
			return array( 'score' => 50, 'factors' => array( 'message' => 'No RFM data available' ) );
		}

		/*
		 * Map RFM segments to churn risk. Keys must be the canonical lowercase slugs
		 * returned by PD_Customer_History_RFM::pdwchs_determine_segment() and stored in the
		 * rfm_segment column — see pro/Intelligence/Segments-Labels.php for the
		 * authoritative list. (Before 3.0.2 these keys were Title Case, so no lookup
		 * ever matched and every customer scored a flat 50 from this model.)
		 */
		$segment_risk_map = array(
			'champions'          => 5,
			'loyal'              => 10,
			'potential_loyalist' => 20,
			'new_customer'       => 30,
			'promising'          => 35,
			'needs_attention'    => 50,
			'at_risk'            => 75,
			'cannot_lose'        => 80,
			'hibernating'        => 85,
			'lost'               => 95,
			'undefined'          => 50,
		);

		/*
		 * pdwchs_get_customer_rfm() returns a raw `SELECT *` row, whose columns are
		 * prefixed (rfm_segment, rfm_recency, …). A freshly calculated array from
		 * PDWCHS_RFM uses the unprefixed keys instead. Accept either shape so this
		 * model works on both paths.
		 */
		$segment = $rfm_data['rfm_segment'] ?? $rfm_data['segment'] ?? '';
		$score   = isset( $segment_risk_map[ $segment ] ) ? $segment_risk_map[ $segment ] : 50;

		// Adjust based on individual RFM scores (1-5 scale)
		$r_score = intval( $rfm_data['rfm_recency'] ?? $rfm_data['recency'] ?? 3 );
		$f_score = intval( $rfm_data['rfm_frequency'] ?? $rfm_data['frequency'] ?? 3 );
		$m_score = intval( $rfm_data['rfm_monetary'] ?? $rfm_data['monetary'] ?? 3 );

		// Low RFM scores (1-2) increase churn risk
		if ( $r_score <= 2 ) {
			$score = min( 100, $score + 10 );
		}
		if ( $f_score <= 2 ) {
			$score = min( 100, $score + 5 );
		}

		return array(
			'score'   => min( 100, max( 0, $score ) ),
			'factors' => array(
				'segment'         => $segment,
				'recency_score'   => $r_score,
				'frequency_score' => $f_score,
				'monetary_score'  => $m_score,
			),
		);
	}

	/**
	 * Engagement model - session and activity analysis
	 *
	 * Analyzes site visit patterns and browsing engagement.
	 *
	 * @since    4.0.0
	 * @param    int      $customer_id      Customer ID
	 * @param    array    $customer_data    Pre-fetched customer data (optional)
	 * @return   array    Score and details
	 */
	public function engagement_model( $customer_id, $customer_data = null ) {
		global $wpdb;

		if ( ! $customer_data ) {
			$customer_data = $this->churn_calculator->pdwchs_get_customer_analysis_data( $customer_id );
		}

		$score = 0;
		$sessions_table = $wpdb->prefix . 'pd_woo_customer_history_sessions';
		$table_exists   = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $sessions_table ) );

		$pages_per_session = 0;
		$session_recency   = 999;

		if ( $table_exists ) {
			// Get recent session metrics
			$session_metrics = $wpdb->get_row( $wpdb->prepare( "
				SELECT
					COUNT(DISTINCT DATE(reg_date)) as visit_days_30,
					COUNT(id) as page_views_30
				FROM {$sessions_table}
				WHERE user_id = %d
				AND del = 0
				AND reg_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			", $customer_id ) );

			// Get days since last session
			$last_session = $wpdb->get_var( $wpdb->prepare( "
				SELECT DATEDIFF(NOW(), MAX(reg_date))
				FROM {$sessions_table}
				WHERE user_id = %d AND del = 0
			", $customer_id ) );

			$session_recency = $last_session !== null ? intval( $last_session ) : 999;

			if ( $session_metrics && $session_metrics->visit_days_30 > 0 ) {
				$pages_per_session = $session_metrics->page_views_30 / $session_metrics->visit_days_30;
			}
		}

		// Factor 1: Engagement decline (40% weight)
		$engagement_decline = $customer_data['engagement_decline_pct'];
		if ( $engagement_decline > 0 ) {
			$score += min( 40, ( $engagement_decline / 100 ) * 40 );
		}

		// Factor 2: No recent sessions (30% weight)
		if ( $customer_data['total_sessions'] > 0 ) {
			if ( $session_recency > 90 ) {
				$score += 30;
			} elseif ( $session_recency > 60 ) {
				$score += 20;
			} elseif ( $session_recency > 30 ) {
				$score += 10;
			}
		}

		// Factor 3: Low page engagement (15% weight)
		if ( $pages_per_session > 0 && $pages_per_session < 2 ) {
			$score += 15;
		} elseif ( $pages_per_session >= 2 && $pages_per_session < 4 ) {
			$score += 7;
		}

		// Factor 4: Total session volume decline (15% weight)
		if ( $customer_data['sessions_last_30_days'] == 0 && $customer_data['total_sessions'] > 5 ) {
			$score += 15;
		} elseif ( $customer_data['sessions_last_30_days'] <= 2 && $customer_data['total_sessions'] > 10 ) {
			$score += 10;
		}

		$score = min( 100, max( 0, round( $score ) ) );

		return array(
			'score'   => $score,
			'factors' => array(
				'engagement_decline' => $engagement_decline,
				'session_recency'    => $session_recency,
				'pages_per_session'  => round( $pages_per_session, 1 ),
				'sessions_30_days'   => $customer_data['sessions_last_30_days'],
			),
		);
	}

	/**
	 * Analyze churn score trends over time
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    Customer ID
	 * @param    int    $days           Number of days to analyze
	 * @return   array    Trend data
	 */
	public function analyze_churn_trends( $customer_id, $days = 90 ) {
		global $wpdb;

		$history_table = $wpdb->prefix . 'pdwchs_churn_history';

		$table_exists = $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $history_table ) );
		if ( ! $table_exists ) {
			return array( 'trend' => 'stable', 'data_points' => array() );
		}

		$history = $wpdb->get_results( $wpdb->prepare( "
			SELECT churn_score, risk_level, transition_type, recorded_at
			FROM {$history_table}
			WHERE customer_id = %d
			AND recorded_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
			ORDER BY recorded_at ASC
		", $customer_id, $days ) );

		if ( count( $history ) < 2 ) {
			return array( 'trend' => 'stable', 'data_points' => $history );
		}

		// Calculate trend direction
		$first_score = intval( $history[0]->churn_score );
		$last_score  = intval( $history[ count( $history ) - 1 ]->churn_score );
		$change      = $last_score - $first_score;

		$trend = 'stable';
		if ( $change >= 15 ) {
			$trend = 'worsening';
		} elseif ( $change <= -15 ) {
			$trend = 'improving';
		}

		// Count transitions
		$escalations   = 0;
		$de_escalations = 0;
		foreach ( $history as $record ) {
			if ( $record->transition_type === 'escalated' ) {
				$escalations++;
			} elseif ( $record->transition_type === 'de_escalated' ) {
				$de_escalations++;
			}
		}

		return array(
			'trend'          => $trend,
			'score_change'   => $change,
			'first_score'    => $first_score,
			'last_score'     => $last_score,
			'escalations'    => $escalations,
			'de_escalations' => $de_escalations,
			'data_points'    => $history,
		);
	}

	/**
	 * Calculate agreement between model scores
	 *
	 * @since    4.0.0
	 * @param    array    $scores    Array of model scores
	 * @return   array    Agreement metrics
	 */
	private function calculate_model_agreement( $scores ) {
		$count = count( $scores );
		if ( $count < 2 ) {
			return array( 'agreement' => 'high', 'variance' => 0 );
		}

		$mean = array_sum( $scores ) / $count;

		// Calculate variance
		$variance = 0;
		foreach ( $scores as $score ) {
			$variance += pow( $score - $mean, 2 );
		}
		$variance = $variance / $count;
		$std_dev  = sqrt( $variance );

		// Classify agreement
		if ( $std_dev <= 10 ) {
			$agreement = 'high';
		} elseif ( $std_dev <= 20 ) {
			$agreement = 'moderate';
		} else {
			$agreement = 'low';
		}

		return array(
			'agreement' => $agreement,
			'variance'  => round( $variance, 2 ),
			'std_dev'   => round( $std_dev, 2 ),
		);
	}
}
