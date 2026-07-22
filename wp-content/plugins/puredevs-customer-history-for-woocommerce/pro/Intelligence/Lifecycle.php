<?php
/**
 * Customer Lifecycle Stages Calculator
 *
 * Handles calculation of customer lifecycle stages based on order history and timing patterns.
 * Tracks stage transitions and provides stage-based recommendations.
 *
 * @link       https://puredevs.com/
 * @since      2.1.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * Customer Lifecycle Stages Calculator Class
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Lifecycle {

	/**
	 * Calculate lifecycle stage for a single customer
	 *
	 * @since    2.1.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Lifecycle data including stage and metadata
	 */
	public function pdwchs_calculate_customer_lifecycle( $user_id ) {
		// Determine current lifecycle stage
		$stage_result = $this->pdwchs_determine_lifecycle_stage( $user_id );

		if ( ! $stage_result ) {
			return null;
		}

		$new_stage = $stage_result['stage'];

		// Get current stage from database
		$current_data = $this->pdwchs_get_customer_lifecycle( $user_id );
		$current_stage = $current_data ? $current_data['lifecycle_stage'] : null;

		// Get order data for metadata
		$order_data = $this->pdwchs_get_order_data( $user_id );

		// Handle stage transition if changed
		if ( $current_stage !== $new_stage ) {
			$this->pdwchs_handle_stage_transition( $user_id, $new_stage, $current_stage, $order_data );
		} else {
			// Update days in stage
			$this->pdwchs_update_days_in_stage( $user_id );
		}

		// Prepare complete lifecycle data
		$lifecycle_data = array(
			'user_id'                  => $user_id,
			'lifecycle_stage'          => $new_stage,
			'lifecycle_stage_date'     => $current_data && $current_stage === $new_stage
				? $current_data['lifecycle_stage_date']
				: current_time( 'mysql' ),
			'lifecycle_previous_stage' => $current_stage,
			'lifecycle_days_in_stage'  => $current_stage === $new_stage && $current_data
				? $this->pdwchs_calculate_days_between( $current_data['lifecycle_stage_date'], current_time( 'mysql' ) )
				: 0,
			'lifecycle_calculated_at'  => current_time( 'mysql' ),
			'reason'                   => $stage_result['reason'],
		);

		// Clear cache
		wp_cache_delete( 'pdwchs_customer_lifecycle_' . $user_id, 'pdwchs' );

		return $lifecycle_data;
	}

	/**
	 * Determine lifecycle stage based on order history
	 *
	 * @since    2.1.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array|null         Stage and reason or null
	 */
	private function pdwchs_determine_lifecycle_stage( $user_id ) {
		// Get order data
		$order_data = $this->pdwchs_get_order_data( $user_id );
		$completed_orders = $order_data['count'];

		// No orders = Prospect
		if ( $completed_orders == 0 ) {
			return array(
				'stage'  => 'Prospect',
				'reason' => __( 'No completed orders', 'puredevs-customer-history-for-woocommerce' ),
			);
		}

		$days_since_last = $order_data['days_since_last'];

		// Decision tree (evaluated in order per spec)

		// 1. Churned (180+ days inactive)
		if ( $days_since_last > 180 ) {
			return array(
				'stage'  => 'Churned',
				'reason' => sprintf( __( 'Inactive for %d days', 'puredevs-customer-history-for-woocommerce' ), $days_since_last ),
			);
		}

		// 2. At Risk (91-180 days inactive)
		if ( $days_since_last >= 91 && $days_since_last <= 180 ) {
			return array(
				'stage'  => 'At Risk',
				'reason' => sprintf( __( 'Inactive for %d days', 'puredevs-customer-history-for-woocommerce' ), $days_since_last ),
			);
		}

		// 3. Loyal Customer (3+ orders, active in last 90 days)
		if ( $completed_orders >= 3 && $days_since_last <= 90 ) {
			return array(
				'stage'  => 'Loyal Customer',
				'reason' => sprintf( __( '%d orders, active', 'puredevs-customer-history-for-woocommerce' ), $completed_orders ),
			);
		}

		// 4. Active Customer (2 orders, active in last 90 days)
		if ( $completed_orders >= 2 && $days_since_last <= 90 ) {
			return array(
				'stage'  => 'Active Customer',
				'reason' => sprintf( __( '%d orders, active', 'puredevs-customer-history-for-woocommerce' ), $completed_orders ),
			);
		}

		// 5. New Customer (1 order, within 90 days)
		if ( $completed_orders == 1 && $days_since_last <= 90 ) {
			return array(
				'stage'  => 'New Customer',
				'reason' => sprintf( __( 'First purchase %d days ago', 'puredevs-customer-history-for-woocommerce' ), $days_since_last ),
			);
		}

		// 6. Edge case: 1 order but > 90 days (should be At Risk)
		if ( $completed_orders == 1 && $days_since_last > 90 ) {
			return array(
				'stage'  => 'At Risk',
				'reason' => __( 'Single purchase, now inactive', 'puredevs-customer-history-for-woocommerce' ),
			);
		}

		// Fallback
		return array(
			'stage'  => 'Undefined',
			'reason' => sprintf( __( 'Edge case: %d orders, %d days', 'puredevs-customer-history-for-woocommerce' ), $completed_orders, $days_since_last ),
		);
	}

	/**
	 * Get order data for a customer
	 *
	 * @since    2.1.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Order statistics
	 */
	private function pdwchs_get_order_data( $user_id ) {
		// Get completed orders count
		$order_count = wc_get_customer_order_count( $user_id );

		if ( $order_count == 0 ) {
			return array(
				'count'           => 0,
				'days_since_last' => null,
				'last_order_date' => null,
				'first_order_date' => null,
			);
		}

		// Get last order
		$last_orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => 'completed',
			'limit'       => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		$last_order_date = null;
		$days_since_last = null;

		if ( ! empty( $last_orders ) ) {
			$last_order_date = $last_orders[0]->get_date_created();
			$days_since_last = floor( ( current_time( 'timestamp' ) - $last_order_date->getTimestamp() ) / DAY_IN_SECONDS );
		}

		// Get first order
		$first_orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => 'completed',
			'limit'       => 1,
			'orderby'     => 'date',
			'order'       => 'ASC',
		) );

		$first_order_date = null;
		if ( ! empty( $first_orders ) ) {
			$first_order_date = $first_orders[0]->get_date_created();
		}

		return array(
			'count'            => intval( $order_count ),
			'days_since_last'  => $days_since_last,
			'last_order_date'  => $last_order_date,
			'first_order_date' => $first_order_date,
		);
	}

	/**
	 * Handle stage transition
	 *
	 * @since    2.1.0
	 * @param    int        $user_id         WordPress user ID
	 * @param    string     $new_stage       New stage name
	 * @param    string     $previous_stage  Previous stage name
	 * @param    array      $order_data      Order data array
	 */
	private function pdwchs_handle_stage_transition( $user_id, $new_stage, $previous_stage, $order_data ) {
		global $wpdb;

		$now = current_time( 'mysql' );
		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';

		// If previous stage exists, close it out in history
		if ( $previous_stage ) {
			$this->pdwchs_close_previous_stage_history( $user_id, $previous_stage );
		}

		// Update main table
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table_name}
				(user_id, lifecycle_stage, lifecycle_stage_date, lifecycle_previous_stage,
				 lifecycle_days_in_stage, lifecycle_calculated_at, updated_at)
				VALUES (%d, %s, %s, %s, %d, %s, %s)
				ON DUPLICATE KEY UPDATE
					lifecycle_previous_stage = lifecycle_stage,
					lifecycle_stage = %s,
					lifecycle_stage_date = %s,
					lifecycle_days_in_stage = 0,
					lifecycle_calculated_at = %s,
					updated_at = %s",
				$user_id,
				$new_stage,
				$now,
				$previous_stage,
				0,
				$now,
				$now,
				// For ON DUPLICATE KEY UPDATE
				$new_stage,
				$now,
				$now,
				$now
			)
		);

		// Record new stage in history
		$this->pdwchs_record_stage_history( $user_id, $new_stage, $previous_stage );

		// Clear distribution cache
		delete_transient( 'pdwchs_lifecycle_distribution' );
	}

	/**
	 * Update days in stage without transition
	 *
	 * @since    2.1.0
	 * @param    int    $user_id    WordPress user ID
	 */
	private function pdwchs_update_days_in_stage( $user_id ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';
		$now = current_time( 'mysql' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_name}
				SET lifecycle_days_in_stage = DATEDIFF(%s, lifecycle_stage_date),
					lifecycle_calculated_at = %s,
					updated_at = %s
				WHERE user_id = %d",
				$now,
				$now,
				$now,
				$user_id
			)
		);
	}

	/**
	 * Close previous stage in history table
	 *
	 * @since    2.1.0
	 * @param    int       $user_id    WordPress user ID
	 * @param    string    $stage      Stage to close
	 */
	private function pdwchs_close_previous_stage_history( $user_id, $stage ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pdwchs_lifecycle_history';
		$now = current_time( 'mysql' );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table_name}
				SET exited_date = %s,
					days_in_stage = DATEDIFF(%s, entered_date)
				WHERE user_id = %d
					AND stage = %s
					AND exited_date IS NULL",
				$now,
				$now,
				$user_id,
				$stage
			)
		);
	}

	/**
	 * Record new stage in history table
	 *
	 * @since    2.1.0
	 * @param    int        $user_id          WordPress user ID
	 * @param    string     $stage            New stage name
	 * @param    string     $previous_stage   Previous stage name
	 */
	private function pdwchs_record_stage_history( $user_id, $stage, $previous_stage ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pdwchs_lifecycle_history';

		$wpdb->insert(
			$table_name,
			array(
				'user_id'        => $user_id,
				'stage'          => $stage,
				'previous_stage' => $previous_stage,
				'entered_date'   => current_time( 'mysql' ),
				'exited_date'    => null,
				'days_in_stage'  => null,
			),
			array( '%d', '%s', '%s', '%s', '%s', '%d' )
		);
	}

	/**
	 * Get lifecycle data for customer (cached)
	 *
	 * @since    2.1.0
	 * @param    int           $user_id    WordPress user ID
	 * @return   array|null                Lifecycle data or null
	 */
	public function pdwchs_get_customer_lifecycle( $user_id ) {
		// Check cache
		$cache_key = 'pdwchs_customer_lifecycle_' . $user_id;
		$cached = wp_cache_get( $cache_key, 'pdwchs' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database
		global $wpdb;
		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT user_id, lifecycle_stage, lifecycle_stage_date, lifecycle_previous_stage,
				        lifecycle_days_in_stage, lifecycle_calculated_at
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
	 * Get stage distribution statistics
	 *
	 * @since    2.1.0
	 * @return   array    Distribution data
	 */
	public function pdwchs_get_stage_distribution() {
		// Check cache
		$cached = get_transient( 'pdwchs_lifecycle_distribution' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';

		// Get total customers
		$total_customers = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} WHERE lifecycle_stage IS NOT NULL"
		);

		// Get stage counts
		$stage_counts = $wpdb->get_results(
			"SELECT
				lifecycle_stage as stage,
				COUNT(*) as count,
				AVG(lifecycle_days_in_stage) as avg_days
			 FROM {$table_name}
			 WHERE lifecycle_stage IS NOT NULL
			 GROUP BY lifecycle_stage",
			ARRAY_A
		);

		// Get all stage configs
		$all_stages = $this->pdwchs_get_all_stage_configs();
		$stages_data = array();

		foreach ( $all_stages as $slug => $config ) {
			// Find count for this stage
			$count = 0;
			$avg_days = 0;

			foreach ( $stage_counts as $stage_count ) {
				if ( $stage_count['stage'] === $slug ) {
					$count = intval( $stage_count['count'] );
					$avg_days = round( floatval( $stage_count['avg_days'] ), 1 );
					break;
				}
			}

			$stages_data[] = array(
				'slug'       => $slug,
				'name'       => $config['name'],
				'count'      => $count,
				'avg_days'   => $avg_days,
				'color'      => $config['color'],
				'icon'       => $config['icon'],
				'conversion_rate' => 0, // Can be calculated if needed
			);
		}

		// Get last calculation time
		$last_calculated = $wpdb->get_var(
			"SELECT MAX(lifecycle_calculated_at) FROM {$table_name}"
		);

		$distribution = array(
			'total_customers' => intval( $total_customers ),
			'stages'          => $stages_data,
			'last_calculated' => $last_calculated ? $last_calculated : current_time( 'mysql' ),
		);

		// Cache for 30 minutes
		set_transient( 'pdwchs_lifecycle_distribution', $distribution, 30 * MINUTE_IN_SECONDS );

		return $distribution;
	}

	/**
	 * Get at risk customers (30+ days)
	 *
	 * @since    2.1.0
	 * @param    int      $limit    Maximum number of customers to return
	 * @return   array              At risk customer data
	 */
	public function pdwchs_get_at_risk_customers( $limit = 15 ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';

		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT ci.user_id, ci.lifecycle_days_in_stage as days_at_risk,
				        ci.lifecycle_stage_date
				 FROM {$table_name} ci
				 WHERE ci.lifecycle_stage = 'At Risk'
				   AND ci.lifecycle_days_in_stage >= 30
				 ORDER BY ci.lifecycle_days_in_stage DESC
				 LIMIT %d",
				$limit
			),
			ARRAY_A
		);

		if ( empty( $results ) ) {
			return array();
		}

		// Enrich with user and order data
		$customers = array();
		foreach ( $results as $row ) {
			$user = get_userdata( $row['user_id'] );
			if ( ! $user ) {
				continue;
			}

			$order_count = wc_get_customer_order_count( $row['user_id'] );
			$total_spent = wc_get_customer_total_spent( $row['user_id'] );
			$order_data = $this->pdwchs_get_order_data( $row['user_id'] );

			$customers[] = array(
				'user_id'                  => $row['user_id'],
				'name'                     => $user->display_name,
				'email'                    => $user->user_email,
				'days_at_risk'             => intval( $row['days_at_risk'] ),
				'days_since_last'          => $order_data['days_since_last'],
				'total_orders'             => $order_count,
				'lifetime_value'           => $total_spent,
				'lifetime_value_formatted' => wc_price( $total_spent ),
				'priority'                 => $this->pdwchs_calculate_at_risk_priority( $row['days_at_risk'], $order_count, $total_spent ),
			);
		}

		// Sort by priority
		usort( $customers, function( $a, $b ) {
			$priority_order = array( 'critical' => 1, 'high' => 2, 'medium' => 3 );
			$a_priority = $priority_order[ $a['priority'] ] ?? 4;
			$b_priority = $priority_order[ $b['priority'] ] ?? 4;

			if ( $a_priority !== $b_priority ) {
				return $a_priority - $b_priority;
			}

			// Same priority, sort by days at risk
			return $b['days_at_risk'] - $a['days_at_risk'];
		} );

		return $customers;
	}

	/**
	 * Calculate at risk priority
	 *
	 * @since    2.1.0
	 * @param    int      $days_at_risk    Days in at risk stage
	 * @param    int      $order_count     Total orders
	 * @param    float    $lifetime_value  Lifetime value
	 * @return   string                    Priority level
	 */
	private function pdwchs_calculate_at_risk_priority( $days_at_risk, $order_count, $lifetime_value ) {
		// Critical: 60+ days OR high value customers
		if ( $days_at_risk >= 60 || $order_count >= 10 ) {
			return 'critical';
		}

		// High: 45-59 days OR medium value customers
		if ( $days_at_risk >= 45 || $order_count >= 5 ) {
			return 'high';
		}

		// Medium: 30-44 days
		return 'medium';
	}

	/**
	 * Batch calculate lifecycle for all customers
	 *
	 * @since    2.1.0
	 * @return   bool    True if completed, false if continuing
	 */
	public function pdwchs_batch_calculate_all() {
		$batch_size = 100;
		$offset = get_option( 'pdwchs_lifecycle_batch_offset', 0 );
		$start_time = time();
		$max_execution = 50; // 50 seconds max per batch

		// Get customer role users
		$customer_role_users = get_users( array(
			'role'    => 'customer',
			'number'  => $batch_size,
			'offset'  => $offset,
			'fields'  => 'ID',
			'orderby' => 'ID',
			'order'   => 'ASC',
		) );

		// Get users who have placed orders (any role)
		global $wpdb;
		$order_users = array();

		if ( class_exists( 'Automattic\WooCommerce\Utilities\OrderUtil' ) &&
		     \Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled() ) {
			// HPOS enabled
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
			// Traditional posts table
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

		// Merge and deduplicate
		$users = array_unique( array_merge( $customer_role_users, $order_users ) );

		// If no users, we're done
		if ( empty( $users ) ) {
			delete_option( 'pdwchs_lifecycle_batch_offset' );
			update_option( 'pdwchs_lifecycle_last_batch_completed', current_time( 'mysql' ) );
			return true;
		}

		// Process users
		$processed = 0;
		foreach ( $users as $user_id ) {
			$this->pdwchs_calculate_customer_lifecycle( $user_id );
			$processed++;

			// Check time limit
			if ( ( time() - $start_time ) > $max_execution ) {
				break;
			}
		}

		// Update offset
		$offset += $batch_size;
		update_option( 'pdwchs_lifecycle_batch_offset', $offset );

		// Schedule continuation
		wp_schedule_single_event( time() + 60, 'pdwchs_daily_lifecycle_calculation' );

		return false; // Not completed
	}

	/**
	 * Get stage configuration
	 *
	 * @since    2.1.0
	 * @param    string    $stage_slug    Stage slug
	 * @return   array                    Stage configuration
	 */
	public function pdwchs_get_stage_config( $stage_slug ) {
		$all_configs = $this->pdwchs_get_all_stage_configs();
		return isset( $all_configs[ $stage_slug ] ) ? $all_configs[ $stage_slug ] : array(
			'name'  => $stage_slug,
			'color' => '#94a3b8',
			'icon'  => '👤',
		);
	}

	/**
	 * Get all stage configurations
	 *
	 * @since    2.1.0
	 * @return   array    All stage configurations
	 */
	public function pdwchs_get_all_stage_configs() {
		return array(
			'Prospect' => array(
				'name'            => __( 'Prospect', 'puredevs-customer-history-for-woocommerce' ),
				'color'           => '#94a3b8',
				'icon'            => '👤',
				'priority'        => 'medium',
				'description'     => __( 'Registered user, never purchased', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Send first-purchase incentive (15% discount)', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Welcome email sequence', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Product recommendations', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Trust-building content (reviews, guarantees)', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'New Customer' => array(
				'name'            => __( 'New Customer', 'puredevs-customer-history-for-woocommerce' ),
				'color'           => '#06b6d4',
				'icon'            => '🆕',
				'priority'        => 'high',
				'description'     => __( 'Just made first purchase (≤90 days)', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Onboarding email sequence', 'puredevs-customer-history-for-woocommerce' ),
					__( 'First repeat purchase discount', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Request feedback on first order', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Product recommendations based on first purchase', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'Active Customer' => array(
				'name'            => __( 'Active Customer', 'puredevs-customer-history-for-woocommerce' ),
				'color'           => '#10b981',
				'icon'            => '✅',
				'priority'        => 'medium',
				'description'     => __( '2 orders, active in last 90 days', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Upsell to premium products', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Cross-sell complementary items', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Encourage loyalty program enrollment', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Send personalized recommendations', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'Loyal Customer' => array(
				'name'            => __( 'Loyal Customer', 'puredevs-customer-history-for-woocommerce' ),
				'color'           => '#8b5cf6',
				'icon'            => '💜',
				'priority'        => 'high',
				'description'     => __( '3+ orders, active relationship', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Reward loyalty with exclusive perks', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Request product reviews and testimonials', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Ask for referrals to similar customers', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Provide VIP customer service', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'At Risk' => array(
				'name'            => __( 'At Risk', 'puredevs-customer-history-for-woocommerce' ),
				'color'           => '#f97316',
				'icon'            => '⚠️',
				'priority'        => 'high',
				'description'     => __( 'Last order 91-180 days ago', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'URGENT: Launch win-back campaign', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Send personalized discount (20%)', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Survey to understand issues', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Highlight new products since last visit', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'Churned' => array(
				'name'            => __( 'Churned', 'puredevs-customer-history-for-woocommerce' ),
				'color'           => '#ef4444',
				'icon'            => '💔',
				'priority'        => 'low',
				'description'     => __( 'Last order 180+ days ago', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Last-chance aggressive discount (30%)', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Personal outreach if high-value', 'puredevs-customer-history-for-woocommerce' ),
					__( 'OR ignore and focus resources elsewhere', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Consider removing from active marketing lists', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
		);
	}

	/**
	 * Calculate days between two dates
	 *
	 * @since    2.1.0
	 * @param    string    $start_date    Start date
	 * @param    string    $end_date      End date
	 * @return   int                      Days between dates
	 */
	private function pdwchs_calculate_days_between( $start_date, $end_date ) {
		$start = strtotime( $start_date );
		$end = strtotime( $end_date );
		return floor( ( $end - $start ) / DAY_IN_SECONDS );
	}
}
