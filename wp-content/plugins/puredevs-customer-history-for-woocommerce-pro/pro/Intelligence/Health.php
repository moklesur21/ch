<?php
/**
 * Customer Health Score Calculator
 *
 * Handles calculation of customer health scores based on 4 weighted components:
 * - Recent Activity (0-40 points)
 * - Order Frequency (0-30 points)
 * - Spending Trend (0-20 points)
 * - Engagement (0-10 points)
 *
 * @link       https://puredevs.com/
 * @since      2.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * Customer Health Score Calculator Class
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Health {

	/**
	 * Calculate complete health score for a customer
	 *
	 * @since    2.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Health score data including all components
	 */
	public function pdwchs_calculate_customer_health( $user_id ) {
		// Calculate all 4 components
		$recent_activity = $this->pdwchs_calculate_recent_activity( $user_id );
		$frequency = $this->pdwchs_calculate_frequency_score( $user_id );
		$spending_trend = $this->pdwchs_calculate_spending_trend( $user_id );
		$engagement = $this->pdwchs_calculate_engagement_score( $user_id );

		// Sum components to get final health score (0-100)
		$health_score = $recent_activity['score'] + $frequency['score'] +
		                $spending_trend['score'] + $engagement['score'];

		// Ensure valid range
		$health_score = max( 0, min( 100, $health_score ) );

		// Get previous score for trend detection
		$previous_score = $this->pdwchs_get_previous_score( $user_id );
		$trend = $this->pdwchs_determine_trend( $health_score, $previous_score );

		// Prepare complete data array
		$health_data = array(
			'user_id'                   => $user_id,
			'health_score'              => $health_score,
			'health_recent_activity'    => $recent_activity['score'],
			'health_frequency'          => $frequency['score'],
			'health_spending_trend'     => $spending_trend['score'],
			'health_engagement'         => $engagement['score'],
			'health_previous_score'     => $previous_score,
			'health_trend'              => $trend,
			'health_calculated_at'      => current_time( 'mysql' ),
			'components_data'           => array(
				'recent_activity'   => $recent_activity,
				'frequency'         => $frequency,
				'spending_trend'    => $spending_trend,
				'engagement'        => $engagement,
			),
		);

		// Save to database
		$this->pdwchs_save_health_data( $user_id, $health_data );

		// Clear cache
		wp_cache_delete( 'pdwchs_customer_health_' . $user_id, 'pdwchs' );

		return $health_data;
	}

	/**
	 * Calculate Recent Activity score (0-40 points)
	 * Based on days since last completed order
	 *
	 * @since    2.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Recent activity score and data
	 */
	private function pdwchs_calculate_recent_activity( $user_id ) {
		// Get last completed order
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => 'completed',
			'limit'       => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		// No orders found
		if ( empty( $orders ) ) {
			return array(
				'score'           => 0,
				'days_since'      => null,
				'last_order_date' => null,
			);
		}

		$last_order_date = $orders[0]->get_date_created();
		$days_since = floor( ( current_time( 'timestamp' ) - $last_order_date->getTimestamp() ) / DAY_IN_SECONDS );

		// Apply scoring logic based on recency
		if ( $days_since <= 7 ) {
			$score = 40; // Ordered this week
		} elseif ( $days_since <= 30 ) {
			$score = 35; // Ordered this month
		} elseif ( $days_since <= 60 ) {
			$score = 25; // Ordered last 2 months
		} elseif ( $days_since <= 90 ) {
			$score = 15; // Ordered last quarter
		} elseif ( $days_since <= 180 ) {
			$score = 5;  // Ordered last 6 months
		} else {
			$score = 0;  // 180+ days inactive
		}

		return array(
			'score'           => $score,
			'days_since'      => $days_since,
			'last_order_date' => $last_order_date->date( 'Y-m-d H:i:s' ),
		);
	}

	/**
	 * Calculate Order Frequency score (0-30 points)
	 * Based on number of orders in last 12 months
	 *
	 * @since    2.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Frequency score and data
	 */
	private function pdwchs_calculate_frequency_score( $user_id ) {
		// Count orders in last 12 months
		$twelve_months_ago = strtotime( '-12 months' );

		$orders = wc_get_orders( array(
			'customer_id'  => $user_id,
			'status'       => 'completed',
			'date_created' => '>=' . $twelve_months_ago,
			'return'       => 'ids',
		) );

		$order_count = count( $orders );

		// Apply scoring logic based on frequency
		if ( $order_count >= 12 ) {
			$score = 30; // 12+ orders (Monthly+)
		} elseif ( $order_count >= 6 ) {
			$score = 25; // 6-11 orders (Bi-monthly)
		} elseif ( $order_count >= 4 ) {
			$score = 20; // 4-5 orders (Quarterly)
		} elseif ( $order_count >= 2 ) {
			$score = 10; // 2-3 orders (Semi-annual)
		} elseif ( $order_count == 1 ) {
			$score = 5;  // 1 order (Annual)
		} else {
			$score = 0;  // 0 orders (Inactive)
		}

		return array(
			'score'            => $score,
			'orders_last_year' => $order_count,
		);
	}

	/**
	 * Calculate Spending Trend score (0-20 points)
	 * Compares recent 3 orders vs older 3 orders spending
	 *
	 * @since    2.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Spending trend score and data
	 */
	private function pdwchs_calculate_spending_trend( $user_id ) {
		// Get last 6 completed orders
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => 'completed',
			'limit'       => 6,
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		$order_count = count( $orders );

		// Need at least 2 orders for trend calculation
		if ( $order_count < 2 ) {
			return array(
				'score'           => 10,
				'change_percent'  => 0,
				'recent_avg'      => 0,
				'older_avg'       => 0,
				'insufficient_data' => true,
			);
		}

		// Split into recent (first 3) and older (next 3) orders
		$recent_orders = array_slice( $orders, 0, 3 );
		$older_orders = array_slice( $orders, 3, 3 );

		// Calculate recent average
		$recent_sum = 0;
		$recent_count = 0;
		foreach ( $recent_orders as $order ) {
			$recent_sum += floatval( $order->get_total() );
			$recent_count++;
		}
		$recent_avg = $recent_count > 0 ? $recent_sum / $recent_count : 0;

		// Calculate older average
		$older_sum = 0;
		$older_count = 0;
		foreach ( $older_orders as $order ) {
			$older_sum += floatval( $order->get_total() );
			$older_count++;
		}
		$older_avg = $older_count > 0 ? $older_sum / $older_count : 0;

		// Calculate percentage change
		$change_percent = 0;
		if ( $older_avg > 0 ) {
			$change_percent = ( ( $recent_avg - $older_avg ) / $older_avg ) * 100;
		}

		// Apply scoring logic based on spending trend
		if ( $change_percent >= 25 ) {
			$score = 20; // Growing 25%+
		} elseif ( $change_percent >= 10 ) {
			$score = 17; // Growing 10-24%
		} elseif ( $change_percent >= 0 ) {
			$score = 14; // Slight growth 0-9%
		} elseif ( $change_percent >= -9 ) {
			$score = 10; // Slight decline 0-9%
		} elseif ( $change_percent >= -24 ) {
			$score = 5;  // Declining 10-24%
		} else {
			$score = 0;  // Declining 25%+
		}

		return array(
			'score'          => $score,
			'change_percent' => round( $change_percent, 2 ),
			'recent_avg'     => round( $recent_avg, 2 ),
			'older_avg'      => round( $older_avg, 2 ),
		);
	}

	/**
	 * Calculate Engagement score (0-10 points)
	 * Based on sessions and searches in last 30 days
	 *
	 * @since    2.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Engagement score and data
	 */
	private function pdwchs_calculate_engagement_score( $user_id ) {
		global $wpdb;
		$sessions_table = $wpdb->prefix . 'pdwchs_customer_history_sessions';

		// Check if sessions table exists
		if ( $wpdb->get_var( "SHOW TABLES LIKE '{$sessions_table}'" ) != $sessions_table ) {
			return array(
				'score'         => 0,
				'sessions_30d'  => 0,
				'searches_30d'  => 0,
				'session_score' => 0,
				'search_score'  => 0,
			);
		}

		// Count unique sessions in last 30 days
		$sessions_30d = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(DISTINCT id)
			 FROM {$sessions_table}
			 WHERE user_id = %d
			 AND reg_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			 AND del = 0",
			$user_id
		) );

		$sessions_30d = intval( $sessions_30d );

		// Calculate session score (0-7)
		if ( $sessions_30d >= 10 ) {
			$session_score = 7; // Very active
		} elseif ( $sessions_30d >= 5 ) {
			$session_score = 5; // Active
		} elseif ( $sessions_30d >= 2 ) {
			$session_score = 3; // Moderate
		} elseif ( $sessions_30d == 1 ) {
			$session_score = 1; // Low activity
		} else {
			$session_score = 0; // No activity
		}

		// Count searches in last 30 days
		// Assuming searches are URLs containing 's=' or '/search/'
		$searches_30d = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*)
			 FROM {$sessions_table}
			 WHERE user_id = %d
			 AND reg_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
			 AND (url LIKE %s OR url LIKE %s)
			 AND del = 0",
			$user_id,
			'%?s=%',
			'%/search/%'
		) );

		$searches_30d = intval( $searches_30d );

		// Calculate search score (0-3)
		if ( $searches_30d >= 5 ) {
			$search_score = 3; // Very engaged
		} elseif ( $searches_30d >= 2 ) {
			$search_score = 2; // Engaged
		} elseif ( $searches_30d == 1 ) {
			$search_score = 1; // Some engagement
		} else {
			$search_score = 0; // No search activity
		}

		// Combined engagement score (max 10)
		$engagement_score = min( 10, $session_score + $search_score );

		return array(
			'score'         => $engagement_score,
			'sessions_30d'  => $sessions_30d,
			'searches_30d'  => $searches_30d,
			'session_score' => $session_score,
			'search_score'  => $search_score,
		);
	}

	/**
	 * Determine health trend (up/down/stable)
	 *
	 * @since    2.0.0
	 * @param    int       $current_score     Current health score
	 * @param    int|null  $previous_score    Previous health score
	 * @return   string                       Trend: 'up', 'down', or 'stable'
	 */
	private function pdwchs_determine_trend( $current_score, $previous_score ) {
		if ( is_null( $previous_score ) ) {
			return 'stable'; // First calculation
		}

		$difference = $current_score - $previous_score;

		if ( $difference >= 5 ) {
			return 'up'; // Improving
		} elseif ( $difference <= -5 ) {
			return 'down'; // Declining
		} else {
			return 'stable'; // No significant change
		}
	}

	/**
	 * Get previous health score for a customer
	 *
	 * @since    2.0.0
	 * @param    int           $user_id    WordPress user ID
	 * @return   int|null                  Previous score or null
	 */
	private function pdwchs_get_previous_score( $user_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';

		$previous_score = $wpdb->get_var( $wpdb->prepare(
			"SELECT health_score FROM {$table_name} WHERE user_id = %d",
			$user_id
		) );

		return $previous_score ? intval( $previous_score ) : null;
	}

	/**
	 * Save health data to database
	 *
	 * @since    2.0.0
	 * @param    int      $user_id       WordPress user ID
	 * @param    array    $health_data   Health data array
	 */
	private function pdwchs_save_health_data( $user_id, $health_data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';

		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table_name}
				(user_id, health_score, health_recent_activity, health_frequency,
				 health_spending_trend, health_engagement, health_previous_score,
				 health_trend, health_calculated_at, updated_at)
				VALUES (%d, %d, %d, %d, %d, %d, %d, %s, %s, %s)
				ON DUPLICATE KEY UPDATE
				health_previous_score = health_score,
				health_score = %d,
				health_recent_activity = %d,
				health_frequency = %d,
				health_spending_trend = %d,
				health_engagement = %d,
				health_trend = %s,
				health_calculated_at = %s,
				updated_at = %s",
				// INSERT values
				$user_id,
				$health_data['health_score'],
				$health_data['health_recent_activity'],
				$health_data['health_frequency'],
				$health_data['health_spending_trend'],
				$health_data['health_engagement'],
				$health_data['health_previous_score'],
				$health_data['health_trend'],
				$health_data['health_calculated_at'],
				current_time( 'mysql' ),
				// UPDATE values
				$health_data['health_score'],
				$health_data['health_recent_activity'],
				$health_data['health_frequency'],
				$health_data['health_spending_trend'],
				$health_data['health_engagement'],
				$health_data['health_trend'],
				$health_data['health_calculated_at'],
				current_time( 'mysql' )
			)
		);

		// Clear distribution cache
		delete_transient( 'pdwchs_health_distribution' );
		delete_transient( 'pdwchs_declining_health' );
	}

	/**
	 * Get health data for a customer (cached)
	 *
	 * @since    2.0.0
	 * @param    int           $user_id    WordPress user ID
	 * @return   array|null                Health data or null
	 */
	public function pdwchs_get_customer_health( $user_id ) {
		// Check cache
		$cache_key = 'pdwchs_customer_health_' . $user_id;
		$cached = wp_cache_get( $cache_key, 'pdwchs' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database
		global $wpdb;
		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT health_score, health_recent_activity, health_frequency,
				        health_spending_trend, health_engagement, health_previous_score,
				        health_trend, health_calculated_at
				 FROM {$table_name}
				 WHERE user_id = %d",
				$user_id
			),
			ARRAY_A
		);

		// Cache for 1 hour
		if ( $data ) {
			wp_cache_set( $cache_key, $data, 'pdwchs', HOUR_IN_SECONDS );
		}

		return $data;
	}

	/**
	 * Get health distribution statistics
	 *
	 * @since    2.0.0
	 * @return   array    Distribution data
	 */
	public function pdwchs_get_health_distribution() {
		// Check cache
		$cached = get_transient( 'pdwchs_health_distribution' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';

		// Get total customers with health scores
		$total_customers = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} WHERE health_score IS NOT NULL"
		);

		if ( ! $total_customers || $total_customers == 0 ) {
			return array(
				'total_customers' => 0,
				'average_score'   => 0,
				'ranges'          => array(),
			);
		}

		// Get average health score
		$average_score = $wpdb->get_var(
			"SELECT ROUND(AVG(health_score), 1) FROM {$table_name} WHERE health_score IS NOT NULL"
		);

		// Get distribution by ranges
		$distribution = $wpdb->get_results(
			"SELECT
				CASE
					WHEN health_score >= 81 THEN 'Excellent (81-100)'
					WHEN health_score >= 61 THEN 'Good (61-80)'
					WHEN health_score >= 41 THEN 'Fair (41-60)'
					WHEN health_score >= 21 THEN 'Poor (21-40)'
					ELSE 'Critical (0-20)'
				END as health_range,
				COUNT(*) as customer_count,
				MIN(health_score) as min_score,
				MAX(health_score) as max_score
			 FROM {$table_name}
			 WHERE health_score IS NOT NULL
			 GROUP BY health_range
			 ORDER BY min_score DESC",
			ARRAY_A
		);

		// Define colors for each range
		$range_colors = array(
			'Excellent (81-100)' => '#10b981',
			'Good (61-80)'       => '#84cc16',
			'Fair (41-60)'       => '#fbbf24',
			'Poor (21-40)'       => '#fb923c',
			'Critical (0-20)'    => '#ef4444',
		);

		// Format ranges data
		$ranges = array();
		foreach ( $distribution as $row ) {
			$percentage = ( $row['customer_count'] / $total_customers ) * 100;
			$ranges[] = array(
				'label'      => $row['health_range'],
				'count'      => intval( $row['customer_count'] ),
				'percentage' => round( $percentage, 2 ),
				'color'      => $range_colors[ $row['health_range'] ],
				'min_score'  => intval( $row['min_score'] ),
				'max_score'  => intval( $row['max_score'] ),
			);
		}

		// Get critical count
		$critical_count = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} WHERE health_score >= 0 AND health_score <= 20"
		);

		$result = array(
			'total_customers' => intval( $total_customers ),
			'average_score'   => floatval( $average_score ),
			'ranges'          => $ranges,
			'critical_count'  => intval( $critical_count ),
		);

		// Cache for 30 minutes
		set_transient( 'pdwchs_health_distribution', $result, 30 * MINUTE_IN_SECONDS );

		return $result;
	}

	/**
	 * Get customers with declining health scores
	 *
	 * @since    2.0.0
	 * @param    int    $limit    Number of customers to return
	 * @return   array            Array of declining customers
	 */
	public function pdwchs_get_declining_health_customers( $limit = 10 ) {
		// Check cache
		$cache_key = 'pdwchs_declining_health_' . $limit;
		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';
		$users_table = $wpdb->users;

		// Get customers with 10+ point decline
		$customers = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
					ci.user_id,
					u.display_name as name,
					u.user_email as email,
					ci.health_score as current_score,
					ci.health_previous_score as previous_score,
					(ci.health_previous_score - ci.health_score) as decline,
					ci.health_calculated_at
				 FROM {$table_name} ci
				 INNER JOIN {$users_table} u ON ci.user_id = u.ID
				 WHERE ci.health_previous_score IS NOT NULL
				 AND (ci.health_previous_score - ci.health_score) >= 10
				 ORDER BY decline DESC, ci.health_score ASC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		// Get additional data for each customer
		foreach ( $customers as $key => $customer ) {
			// Get days since last order
			$orders = wc_get_orders( array(
				'customer_id' => $customer['user_id'],
				'status'      => 'completed',
				'limit'       => 1,
				'orderby'     => 'date',
				'order'       => 'DESC',
			) );

			$days_since_order = null;
			if ( ! empty( $orders ) ) {
				$last_order_date = $orders[0]->get_date_created();
				$days_since_order = floor( ( current_time( 'timestamp' ) - $last_order_date->getTimestamp() ) / DAY_IN_SECONDS );
			}

			$customers[ $key ]['days_since_order'] = $days_since_order;
			$customers[ $key ]['profile_url'] = admin_url( 'admin.php?page=pd-woo-customer&user_id=' . $customer['user_id'] );
			$customers[ $key ]['decline'] = intval( $customer['decline'] );
			$customers[ $key ]['current_score'] = intval( $customer['current_score'] );
			$customers[ $key ]['previous_score'] = intval( $customer['previous_score'] );
		}

		$result = array(
			'customers'   => $customers,
			'total_count' => count( $customers ),
		);

		// Cache for 30 minutes
		set_transient( $cache_key, $result, 30 * MINUTE_IN_SECONDS );

		return $result;
	}

	/**
	 * Get health status details for a score
	 *
	 * @since    2.0.0
	 * @param    int      $score    Health score (0-100)
	 * @return   array              Status details
	 */
	public function pdwchs_get_health_status( $score ) {
		if ( $score >= 81 ) {
			return array(
				'status'      => __( 'Excellent', 'puredevs-customer-history-for-woocommerce' ),
				'color'       => '#10b981',
				'bg_color'    => '#d1fae5',
				'icon'        => '💚',
				'description' => __( 'Highly engaged, strong relationship', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Maintain satisfaction, reward loyalty', 'puredevs-customer-history-for-woocommerce' ),
			);
		} elseif ( $score >= 61 ) {
			return array(
				'status'      => __( 'Good', 'puredevs-customer-history-for-woocommerce' ),
				'color'       => '#84cc16',
				'bg_color'    => '#ecfccb',
				'icon'        => '💛',
				'description' => __( 'Healthy relationship, good engagement', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Continue nurturing, occasional incentives', 'puredevs-customer-history-for-woocommerce' ),
			);
		} elseif ( $score >= 41 ) {
			return array(
				'status'      => __( 'Fair', 'puredevs-customer-history-for-woocommerce' ),
				'color'       => '#fbbf24',
				'bg_color'    => '#fef3c7',
				'icon'        => '🟡',
				'description' => __( 'Average engagement, room for improvement', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Re-engagement campaigns, special offers', 'puredevs-customer-history-for-woocommerce' ),
			);
		} elseif ( $score >= 21 ) {
			return array(
				'status'      => __( 'Poor', 'puredevs-customer-history-for-woocommerce' ),
				'color'       => '#fb923c',
				'bg_color'    => '#fed7aa',
				'icon'        => '🟠',
				'description' => __( 'Low engagement, needs attention', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Win-back campaigns, investigate issues', 'puredevs-customer-history-for-woocommerce' ),
			);
		} else {
			return array(
				'status'      => __( 'Critical', 'puredevs-customer-history-for-woocommerce' ),
				'color'       => '#ef4444',
				'bg_color'    => '#fee2e2',
				'icon'        => '🔴',
				'description' => __( 'At risk of churning, urgent action needed', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Immediate intervention, aggressive recovery', 'puredevs-customer-history-for-woocommerce' ),
			);
		}
	}

	/**
	 * Batch calculate health scores for all customers
	 *
	 * @since    2.0.0
	 * @return   bool    True if completed, false if continuing
	 */
	public function pdwchs_batch_calculate_all_health() {
		$batch_size = 100;
		$offset = get_option( 'pdwchs_health_batch_offset', 0 );
		$start_time = time();
		$max_execution = 50; // 50 seconds max per batch

		global $wpdb;

		// Get all customer role users
		$customer_role_users = get_users( array(
			'role'    => 'customer',
			'number'  => $batch_size,
			'offset'  => $offset,
			'fields'  => 'ID',
			'orderby' => 'ID',
			'order'   => 'ASC'
		) );

		// Get users who have placed orders
		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) &&
		     \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS: Query from wc_orders table
			$order_users = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT customer_id
					 FROM {$wpdb->prefix}wc_orders
					 WHERE customer_id > 0
					 ORDER BY customer_id ASC
					 LIMIT %d OFFSET %d",
					$batch_size,
					$offset
				)
			);
		} else {
			// Traditional: Query from postmeta
			$order_users = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT DISTINCT meta_value as customer_id
					 FROM {$wpdb->postmeta}
					 WHERE meta_key = '_customer_user'
					 AND meta_value > 0
					 ORDER BY CAST(meta_value AS UNSIGNED) ASC
					 LIMIT %d OFFSET %d",
					$batch_size,
					$offset
				)
			);
		}

		// Merge both arrays and remove duplicates
		$users = array_unique( array_merge( $customer_role_users, $order_users ) );

		// If no users, we're done
		if ( empty( $users ) ) {
			delete_option( 'pdwchs_health_batch_offset' );
			update_option( 'pdwchs_health_last_batch_completed', current_time( 'mysql' ) );
			return true;
		}

		// Process users
		$processed = 0;
		foreach ( $users as $user_id ) {
			$this->pdwchs_calculate_customer_health( $user_id );
			$processed++;

			// Check time limit
			if ( ( time() - $start_time ) > $max_execution ) {
				break;
			}
		}

		// Update offset
		$offset += $batch_size;
		update_option( 'pdwchs_health_batch_offset', $offset );

		// Schedule continuation
		wp_schedule_single_event( time() + 60, 'pdwchs_daily_health_calculation' );

		return false; // Not completed
	}

	/**
	 * Get component description text
	 *
	 * @since    2.0.0
	 * @param    string  $component    Component name
	 * @param    int     $score        Component score
	 * @param    array   $data         Component data
	 * @return   string                Description text
	 */
	public function pdwchs_get_component_description( $component, $score, $data ) {
		switch ( $component ) {
			case 'recent_activity':
				if ( $score == 0 || is_null( $data['days_since'] ) ) {
					return __( 'No orders yet', 'puredevs-customer-history-for-woocommerce' );
				}
				return sprintf(
					__( 'Last order %d days ago', 'puredevs-customer-history-for-woocommerce' ),
					$data['days_since']
				);

			case 'frequency':
				if ( $score == 0 ) {
					return __( 'No orders in last 12 months', 'puredevs-customer-history-for-woocommerce' );
				}
				return sprintf(
					__( '%d orders in last year', 'puredevs-customer-history-for-woocommerce' ),
					$data['orders_last_year']
				);

			case 'spending_trend':
				if ( isset( $data['insufficient_data'] ) && $data['insufficient_data'] ) {
					return __( 'Insufficient data for trend', 'puredevs-customer-history-for-woocommerce' );
				}
				if ( $data['change_percent'] > 0 ) {
					return sprintf(
						__( 'Spending up %s%% recently', 'puredevs-customer-history-for-woocommerce' ),
						number_format( $data['change_percent'], 1 )
					);
				} elseif ( $data['change_percent'] < 0 ) {
					return sprintf(
						__( 'Spending down %s%% recently', 'puredevs-customer-history-for-woocommerce' ),
						number_format( abs( $data['change_percent'] ), 1 )
					);
				} else {
					return __( 'Spending stable', 'puredevs-customer-history-for-woocommerce' );
				}

			case 'engagement':
				if ( $score == 0 ) {
					return __( 'No activity in last 30 days', 'puredevs-customer-history-for-woocommerce' );
				}
				return sprintf(
					__( '%d sessions, %d searches (30 days)', 'puredevs-customer-history-for-woocommerce' ),
					$data['sessions_30d'],
					$data['searches_30d']
				);

			default:
				return '';
		}
	}
}
