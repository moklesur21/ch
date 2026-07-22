<?php
/**
 * Churn Engagement Metrics
 *
 * Provides detailed engagement analysis for churn prediction,
 * including session analysis and cart abandonment tracking.
 *
 * @link       https://puredevs.com/
 * @since      4.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * Churn Engagement Metrics Class
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Churn_Engagement {

	/**
	 * Get detailed engagement metrics for a customer
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    WordPress user ID
	 * @return   array    Engagement metrics
	 */
	public function get_engagement_metrics( $customer_id ) {
		$metrics = array(
			'total_sessions'           => 0,
			'sessions_last_7_days'     => 0,
			'sessions_last_30_days'    => 0,
			'sessions_last_90_days'    => 0,
			'sessions_previous_30'     => 0,
			'total_page_views'         => 0,
			'page_views_last_30_days'  => 0,
			'unique_pages_visited'     => 0,
			'avg_pages_per_visit_day'  => 0,
			'last_session_days_ago'    => null,
			'session_decline_rate'     => 0,
			'engagement_score'         => 0,
			'engagement_level'         => 'unknown',
		);

		if ( ! PDWCHS_Sessions_Data::table_exists() ) {
			return $metrics;
		}

		// Total sessions
		$metrics['total_sessions'] = PDWCHS_Sessions_Data::get_session_count_by_user( $customer_id, array() );

		// Sessions by time period
		$metrics['sessions_last_7_days'] = PDWCHS_Sessions_Data::get_session_count_by_user(
			$customer_id,
			array( 'since' => gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) ) )
		);

		$metrics['sessions_last_30_days'] = PDWCHS_Sessions_Data::get_session_count_by_user(
			$customer_id,
			array( 'since' => gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) ) )
		);

		$metrics['sessions_last_90_days'] = PDWCHS_Sessions_Data::get_session_count_by_user(
			$customer_id,
			array( 'since' => gmdate( 'Y-m-d H:i:s', strtotime( '-90 days' ) ) )
		);

		$metrics['sessions_previous_30'] = PDWCHS_Sessions_Data::get_session_count_by_user(
			$customer_id,
			array(
				'since'  => gmdate( 'Y-m-d H:i:s', strtotime( '-60 days' ) ),
				'before' => gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) ),
			)
		);

		// Page view metrics
		$page_data = PDWCHS_Sessions_Data::get_page_engagement_data( $customer_id );
		if ( $page_data ) {
			$metrics['total_page_views']     = intval( $page_data->total_views );
			$metrics['unique_pages_visited'] = intval( $page_data->unique_pages );
			if ( $page_data->visit_days > 0 ) {
				$metrics['avg_pages_per_visit_day'] = round( $page_data->total_views / $page_data->visit_days, 1 );
			}
		}

		$metrics['page_views_last_30_days'] = PDWCHS_Sessions_Data::get_session_count_by_user(
			$customer_id,
			array( 'since' => gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) ) )
		);

		// Days since last session
		$metrics['last_session_days_ago'] = PDWCHS_Sessions_Data::get_last_session_days_ago( $customer_id );

		// Calculate session decline rate
		if ( $metrics['sessions_previous_30'] > 0 ) {
			$metrics['session_decline_rate'] = round(
				( ( $metrics['sessions_previous_30'] - $metrics['sessions_last_30_days'] ) / $metrics['sessions_previous_30'] ) * 100,
				2
			);
			$metrics['session_decline_rate'] = max( 0, $metrics['session_decline_rate'] );
		}

		// Calculate engagement score (0-100, higher is better)
		$metrics['engagement_score'] = $this->calculate_engagement_score( $metrics );
		$metrics['engagement_level'] = $this->determine_engagement_level( $metrics['engagement_score'] );

		return $metrics;
	}

	/**
	 * Analyze cart abandonment for a customer
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    WordPress user ID
	 * @return   array    Cart abandonment metrics
	 */
	public function analyze_cart_abandonment( $customer_id ) {
		global $wpdb;

		$orders_table = $wpdb->prefix . 'wc_orders';

		// Get completed orders in last 90 days
		$completed = intval( $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(id) FROM {$orders_table}
			WHERE customer_id = %d
			AND status IN ('wc-completed', 'wc-processing')
			AND date_created_gmt >= DATE_SUB(NOW(), INTERVAL 90 DAY)
		", $customer_id ) ) );

		// Get abandoned/failed orders in last 90 days
		$abandoned = intval( $wpdb->get_var( $wpdb->prepare( "
			SELECT COUNT(id) FROM {$orders_table}
			WHERE customer_id = %d
			AND status IN ('wc-pending', 'wc-failed', 'wc-cancelled')
			AND date_created_gmt >= DATE_SUB(NOW(), INTERVAL 90 DAY)
		", $customer_id ) ) );

		$total = $completed + $abandoned;

		// Calculate abandonment rate
		$abandonment_rate = 0;
		if ( $total > 0 ) {
			$abandonment_rate = round( ( $abandoned / $total ) * 100, 2 );
		}

		// Calculate conversion rate
		$conversion_rate = 0;
		if ( $total > 0 ) {
			$conversion_rate = round( ( $completed / $total ) * 100, 2 );
		}

		// Get abandoned order value
		$abandoned_value = floatval( $wpdb->get_var( $wpdb->prepare( "
			SELECT COALESCE(SUM(total_amount), 0) FROM {$orders_table}
			WHERE customer_id = %d
			AND status IN ('wc-pending', 'wc-failed', 'wc-cancelled')
			AND date_created_gmt >= DATE_SUB(NOW(), INTERVAL 90 DAY)
		", $customer_id ) ) );

		return array(
			'completed_orders'  => $completed,
			'abandoned_orders'  => $abandoned,
			'total_attempts'    => $total,
			'abandonment_rate'  => $abandonment_rate,
			'conversion_rate'   => $conversion_rate,
			'abandoned_value'   => $abandoned_value,
		);
	}

	/**
	 * Calculate engagement score
	 *
	 * @since    4.0.0
	 * @param    array    $metrics    Engagement metrics
	 * @return   int    Score 0-100 (higher = more engaged)
	 */
	private function calculate_engagement_score( $metrics ) {
		$score = 0;

		// Recent activity (40 points max)
		if ( $metrics['sessions_last_7_days'] > 0 ) {
			$score += 40;
		} elseif ( $metrics['sessions_last_30_days'] > 0 ) {
			$score += 25;
		} elseif ( $metrics['sessions_last_90_days'] > 0 ) {
			$score += 10;
		}

		// Session volume (30 points max)
		if ( $metrics['sessions_last_30_days'] >= 10 ) {
			$score += 30;
		} elseif ( $metrics['sessions_last_30_days'] >= 5 ) {
			$score += 20;
		} elseif ( $metrics['sessions_last_30_days'] >= 1 ) {
			$score += 10;
		}

		// Page diversity (15 points max)
		if ( $metrics['unique_pages_visited'] >= 20 ) {
			$score += 15;
		} elseif ( $metrics['unique_pages_visited'] >= 10 ) {
			$score += 10;
		} elseif ( $metrics['unique_pages_visited'] >= 3 ) {
			$score += 5;
		}

		// Growth vs decline (15 points max)
		if ( $metrics['session_decline_rate'] <= 0 ) {
			$score += 15; // Growing or stable
		} elseif ( $metrics['session_decline_rate'] <= 25 ) {
			$score += 10;
		} elseif ( $metrics['session_decline_rate'] <= 50 ) {
			$score += 5;
		}

		return min( 100, max( 0, $score ) );
	}

	/**
	 * Determine engagement level label
	 *
	 * @since    4.0.0
	 * @param    int    $score    Engagement score
	 * @return   string
	 */
	private function determine_engagement_level( $score ) {
		if ( $score >= 75 ) {
			return 'highly_engaged';
		} elseif ( $score >= 50 ) {
			return 'moderately_engaged';
		} elseif ( $score >= 25 ) {
			return 'low_engagement';
		} else {
			return 'disengaged';
		}
	}
}
