<?php
/**
 * RFM Analysis Calculator
 *
 * Handles calculation of Recency, Frequency, and Monetary scores
 * and determines customer segments based on RFM methodology.
 *
 * @link       https://puredevs.com/
 * @since      2.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * RFM Analysis Calculator Class
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_RFM {

	/**
	 * Calculate RFM for a single customer
	 *
	 * @since    2.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              RFM data including scores and segment
	 */
	public function pdwchs_calculate_customer_rfm( $user_id ) {
		// Calculate individual scores
		$recency_data = $this->pdwchs_calculate_recency_score( $user_id );
		$frequency_data = $this->pdwchs_calculate_frequency_score( $user_id );
		$monetary_data = $this->pdwchs_calculate_monetary_score( $user_id );

		// Determine segment from scores
		$segment = $this->pdwchs_determine_segment(
			$recency_data['score'],
			$frequency_data['score'],
			$monetary_data['score']
		);

		// Prepare complete data array
		$rfm_data = array(
			'user_id'               => $user_id,
			'recency'               => $recency_data['score'],
			'frequency'             => $frequency_data['score'],
			'monetary'              => $monetary_data['score'],
			'segment'               => $segment,
			'days_since_last_order' => $recency_data['days_since'],
			'total_orders'          => $frequency_data['total_orders'],
			'total_spent'           => $monetary_data['total_spent'],
			'vs_average'            => $monetary_data['vs_average'],
			'calculated_at'         => current_time( 'mysql' ),
		);

		// Save to database
		$this->pdwchs_save_rfm_data( $user_id, $rfm_data );

		// Clear cache
		wp_cache_delete( 'pdwchs_customer_rfm_' . $user_id, 'pdwchs' );

		return $rfm_data;
	}

	/**
	 * Calculate Recency score (0-5)
	 *
	 * @since    2.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Recency score and days since last order
	 */
	private function pdwchs_calculate_recency_score( $user_id ) {
		// Get completed orders using WooCommerce function
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => 'completed',
			'limit'       => 1,
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		// Get most recent completed order date
		$last_order_date = null;
		if ( ! empty( $orders ) ) {
			$last_order_date = $orders[0]->get_date_created();
		}

		// If no orders, return 0
		if ( ! $last_order_date ) {
			return array(
				'score'      => 0,
				'days_since' => null,
			);
		}

		// Calculate days since last order
		$days_since = floor( ( current_time( 'timestamp' ) - $last_order_date->getTimestamp() ) / DAY_IN_SECONDS );

		// Apply scoring logic
		$score = 0;
		if ( $days_since <= 30 ) {
			$score = 5;
		} elseif ( $days_since <= 60 ) {
			$score = 4;
		} elseif ( $days_since <= 90 ) {
			$score = 3;
		} elseif ( $days_since <= 180 ) {
			$score = 2;
		} else {
			$score = 1;
		}

		return array(
			'score'      => $score,
			'days_since' => $days_since,
		);
	}

	/**
	 * Calculate Frequency score (0-5)
	 *
	 * @since    2.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Frequency score and total orders
	 */
	private function pdwchs_calculate_frequency_score( $user_id ) {
		// Count total completed orders using WooCommerce function
		$total_orders = wc_get_customer_order_count( $user_id );

		// Apply scoring logic
		$score = 0;
		if ( $total_orders >= 20 ) {
			$score = 5;
		} elseif ( $total_orders >= 10 ) {
			$score = 4;
		} elseif ( $total_orders >= 5 ) {
			$score = 3;
		} elseif ( $total_orders >= 2 ) {
			$score = 2;
		} elseif ( $total_orders == 1 ) {
			$score = 1;
		}

		return array(
			'score'        => $score,
			'total_orders' => intval( $total_orders ),
		);
	}

	/**
	 * Calculate Monetary score (0-5)
	 *
	 * @since    2.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Monetary score, total spent, and vs average
	 */
	private function pdwchs_calculate_monetary_score( $user_id ) {
		// Get customer's total spending using WooCommerce function
		$total_spent = wc_get_customer_total_spent( $user_id );

		// If no orders, return 0
		if ( ! $total_spent || $total_spent == 0 ) {
			return array(
				'score'       => 0,
				'total_spent' => 0,
				'vs_average'  => 0,
			);
		}

		$total_spent = floatval( $total_spent );

		// Get store average (cached)
		$store_average = $this->pdwchs_get_store_average_spend();

		// Calculate multiplier
		$multiplier = $store_average > 0 ? ( $total_spent / $store_average ) : 0;

		// Apply scoring logic
		$score = 0;
		if ( $multiplier >= 3.0 ) {
			$score = 5;
		} elseif ( $multiplier >= 2.0 ) {
			$score = 4;
		} elseif ( $multiplier >= 1.5 ) {
			$score = 3;
		} elseif ( $multiplier >= 0.75 ) {
			$score = 2;
		} else {
			$score = 1;
		}

		return array(
			'score'       => $score,
			'total_spent' => $total_spent,
			'vs_average'  => round( $multiplier, 2 ),
		);
	}

	/**
	 * Determine segment from R, F, M scores
	 *
	 * @since    2.0.0
	 * @param    int    $r    Recency score (0-5)
	 * @param    int    $f    Frequency score (0-5)
	 * @param    int    $m    Monetary score (0-5)
	 * @return   string       Segment slug
	 */
	private function pdwchs_determine_segment( $r, $f, $m ) {
		// Champions: R >= 4, F >= 4, M >= 4
		if ( $r >= 4 && $f >= 4 && $m >= 4 ) {
			return 'champions';
		}

		// Loyal: R >= 3, F >= 4
		if ( $r >= 3 && $f >= 4 ) {
			return 'loyal';
		}

		// Potential Loyalist: R >= 4, F in [2,3]
		if ( $r >= 4 && $f >= 2 && $f <= 3 ) {
			return 'potential_loyalist';
		}

		// New Customer: R >= 4, F = 1
		if ( $r >= 4 && $f == 1 ) {
			return 'new_customer';
		}

		// Promising: R >= 3, F = 1, M >= 3
		if ( $r >= 3 && $f == 1 && $m >= 3 ) {
			return 'promising';
		}

		// Cannot Lose: R <= 2, F >= 4, M >= 4 (high-value lapsed — check before at_risk)
		if ( $r <= 2 && $f >= 4 && $m >= 4 ) {
			return 'cannot_lose';
		}

		// At Risk: R <= 2, F >= 3 (frequent buyers gone quiet — check before needs_attention)
		if ( $r <= 2 && $f >= 3 ) {
			return 'at_risk';
		}

		// Needs Attention: R in [2,3], F in [2,3]
		if ( $r >= 2 && $r <= 3 && $f >= 2 && $f <= 3 ) {
			return 'needs_attention';
		}

		// Lost: R = 1, F = 1 (single purchase, never returned — check before hibernating)
		if ( $r == 1 && $f <= 1 ) {
			return 'lost';
		}

		// Hibernating: R <= 2, F <= 2, M <= 2 (repeat buyer gone quiet)
		if ( $r <= 2 && $f <= 2 && $m <= 2 ) {
			return 'hibernating';
		}

		// Default fallback
		return 'undefined';
	}

	/**
	 * Get or calculate store average spend (cached for 24 hours)
	 *
	 * @since    2.0.0
	 * @return   float    Store average spend
	 */
	public function pdwchs_get_store_average_spend() {
		// Check cache
		$cached = get_transient( 'pdwchs_store_average_spend' );
		if ( false !== $cached && is_numeric( $cached ) ) {
			return floatval( $cached );
		}

		// Get all customers who have placed orders
		// Using wc_get_orders() which is compatible with both HPOS and traditional storage
		$customer_ids_temp = array();
		$page = 1;
		$limit = 100;

		// Get orders in batches to avoid memory issues
		while ( $page <= 100 ) { // Safety limit: max 10,000 orders
			$orders = wc_get_orders( array(
				'limit'  => $limit,
				'page'   => $page,
				'status' => array( 'completed', 'processing', 'on-hold', 'pending', 'refunded' ),
				'return' => 'ids',
			) );

			if ( empty( $orders ) ) {
				break;
			}

			foreach ( $orders as $order_id ) {
				$order = wc_get_order( $order_id );
				if ( $order && $order instanceof WC_Order ) {
					$customer_id = $order->get_customer_id();
					if ( $customer_id > 0 ) {
						$customer_ids_temp[ $customer_id ] = true;
					}
				}
			}

			$page++;
		}

		$customer_ids = array_keys( $customer_ids_temp );

		// Calculate average if we have customers
		$average = 0;
		if ( ! empty( $customer_ids ) ) {
			$total_sum = 0;
			$customer_count = 0;

			foreach ( $customer_ids as $customer_id ) {
				$customer_total = wc_get_customer_total_spent( $customer_id );
				if ( $customer_total > 0 ) {
					$total_sum += $customer_total;
					$customer_count++;
				}
			}

			if ( $customer_count > 0 ) {
				$average = $total_sum / $customer_count;
			}
		}

		// Cache for 24 hours
		set_transient( 'pdwchs_store_average_spend', $average, DAY_IN_SECONDS );

		return $average;
	}

	/**
	 * Update store average cache
	 *
	 * @since    2.0.0
	 */
	public function pdwchs_update_store_average_cache() {
		delete_transient( 'pdwchs_store_average_spend' );
		$this->pdwchs_get_store_average_spend();
	}

	/**
	 * Save RFM data to database
	 *
	 * @since    2.0.0
	 * @param    int      $user_id     WordPress user ID
	 * @param    array    $rfm_data    RFM data array
	 */
	private function pdwchs_save_rfm_data( $user_id, $rfm_data ) {
		global $wpdb;

		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';

		// Insert or update
		$wpdb->query(
			$wpdb->prepare(
				"INSERT INTO {$table_name}
				(user_id, rfm_recency, rfm_frequency, rfm_monetary, rfm_segment, rfm_calculated_at, updated_at)
				VALUES (%d, %d, %d, %d, %s, %s, %s)
				ON DUPLICATE KEY UPDATE
				rfm_recency = %d,
				rfm_frequency = %d,
				rfm_monetary = %d,
				rfm_segment = %s,
				rfm_calculated_at = %s,
				updated_at = %s",
				$user_id,
				$rfm_data['recency'],
				$rfm_data['frequency'],
				$rfm_data['monetary'],
				$rfm_data['segment'],
				$rfm_data['calculated_at'],
				current_time( 'mysql' ),
				// For ON DUPLICATE KEY UPDATE
				$rfm_data['recency'],
				$rfm_data['frequency'],
				$rfm_data['monetary'],
				$rfm_data['segment'],
				$rfm_data['calculated_at'],
				current_time( 'mysql' )
			)
		);

		// Clear distribution cache
		delete_transient( 'pdwchs_segment_distribution' );
	}

	/**
	 * Get RFM data for customer (cached)
	 *
	 * @since    2.0.0
	 * @param    int           $user_id    WordPress user ID
	 * @return   array|null                RFM data or null
	 */
	public function pdwchs_get_customer_rfm( $user_id ) {
		// Check cache
		$cache_key = 'pdwchs_customer_rfm_' . $user_id;
		$cached = wp_cache_get( $cache_key, 'pdwchs' );

		if ( false !== $cached ) {
			return $cached;
		}

		// Query database
		global $wpdb;
		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';

		$data = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE user_id = %d",
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
	 * Get segment distribution statistics
	 *
	 * @since    2.0.0
	 * @return   array    Distribution data
	 */
	public function pdwchs_get_segment_distribution() {
		// Check cache
		$cached = get_transient( 'pdwchs_segment_distribution' );
		if ( false !== $cached ) {
			return $cached;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'pdwchs_customer_intelligence';

		// Get total customers
		$total_customers = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$table_name} WHERE rfm_segment IS NOT NULL"
		);

		// Get segment counts
		$segment_counts = $wpdb->get_results(
			"SELECT
				rfm_segment as segment,
				COUNT(*) as count
			 FROM {$table_name}
			 WHERE rfm_segment IS NOT NULL
			 GROUP BY rfm_segment",
			ARRAY_A
		);

		// Get all segments
		$all_segments = PD_Customer_History_Segments::pdwchs_get_segments();
		$segments_data = array();

		foreach ( $all_segments as $slug => $config ) {
			// Find count for this segment
			$count = 0;
			foreach ( $segment_counts as $segment_count ) {
				if ( $segment_count['segment'] === $slug ) {
					$count = intval( $segment_count['count'] );
					break;
				}
			}

			$percentage = $total_customers > 0 ? ( $count / $total_customers ) * 100 : 0;

			$segments_data[] = array(
				'slug'       => $slug,
				'name'       => $config['name'],
				'count'      => $count,
				'percentage' => round( $percentage, 2 ),
				'color'      => $config['color'],
				'icon'       => $config['icon'],
			);
		}

		// Get last calculation time
		$last_calculated = $wpdb->get_var(
			"SELECT MAX(rfm_calculated_at) FROM {$table_name}"
		);

		$distribution = array(
			'total_customers' => intval( $total_customers ),
			'segments'        => $segments_data,
			'last_calculated' => $last_calculated ? $last_calculated : current_time( 'mysql' ),
		);

		// Cache for 30 minutes
		set_transient( 'pdwchs_segment_distribution', $distribution, 30 * MINUTE_IN_SECONDS );

		return $distribution;
	}

	/**
	 * Batch calculate RFM for all customers
	 *
	 * @since    2.0.0
	 * @return   bool    True if completed, false if continuing
	 */
	public function pdwchs_batch_calculate_all() {
		$batch_size = 100;
		$offset = get_option( 'pdwchs_rfm_batch_offset', 0 );
		$start_time = time();
		$max_execution = 50; // 50 seconds max per batch

		global $wpdb;

		// Get all customer role users (including those who haven't placed orders)
		$customer_role_users = get_users( array(
			'role'    => 'customer',
			'number'  => $batch_size,
			'offset'  => $offset,
			'fields'  => 'ID',
			'orderby' => 'ID',
			'order'   => 'ASC'
		) );

		// Get users who have placed orders (any role) - using same batch size and offset
		// Check if HPOS is enabled
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

		// If no users from both sources, we're done
		if ( empty( $users ) ) {
			delete_option( 'pdwchs_rfm_batch_offset' );
			update_option( 'pdwchs_rfm_last_batch_completed', current_time( 'mysql' ) );
			return true;
		}

		// Process users
		$processed = 0;
		foreach ( $users as $user_id ) {
			$this->pdwchs_calculate_customer_rfm( $user_id );
			$processed++;

			// Check time limit
			if ( ( time() - $start_time ) > $max_execution ) {
				break;
			}
		}

		// Update offset
		$offset += $batch_size;
		update_option( 'pdwchs_rfm_batch_offset', $offset );

		// Schedule continuation
		wp_schedule_single_event( time() + 60, 'pdwchs_daily_rfm_calculation' );

		return false; // Not completed
	}

	/**
	 * Get recency description text
	 *
	 * @since    2.0.0
	 * @param    int       $score         Recency score
	 * @param    int|null  $days_since    Days since last order
	 * @return   string                   Description text
	 */
	public function pdwchs_get_recency_description( $score, $days_since ) {
		if ( $score == 0 || is_null( $days_since ) ) {
			return __( 'No orders yet', 'puredevs-customer-history-for-woocommerce' );
		}

		$descriptions = array(
			5 => __( 'Very recent', 'puredevs-customer-history-for-woocommerce' ),
			4 => __( 'Recent', 'puredevs-customer-history-for-woocommerce' ),
			3 => __( 'Fairly recent', 'puredevs-customer-history-for-woocommerce' ),
			2 => __( 'Some time ago', 'puredevs-customer-history-for-woocommerce' ),
			1 => __( 'Long time ago', 'puredevs-customer-history-for-woocommerce' ),
		);

		return sprintf(
			/* translators: %1$d: days since last order, %2$s: recency description */
			__( 'Last order %1$d days ago (%2$s)', 'puredevs-customer-history-for-woocommerce' ),
			$days_since,
			$descriptions[ $score ]
		);
	}

	/**
	 * Get frequency description text
	 *
	 * @since    2.0.0
	 * @param    int    $score          Frequency score
	 * @param    int    $total_orders   Total orders
	 * @return   string                 Description text
	 */
	public function pdwchs_get_frequency_description( $score, $total_orders ) {
		if ( $score == 0 ) {
			return __( 'No orders yet', 'puredevs-customer-history-for-woocommerce' );
		}

		$descriptions = array(
			5 => __( 'Very frequent buyer', 'puredevs-customer-history-for-woocommerce' ),
			4 => __( 'Regular buyer', 'puredevs-customer-history-for-woocommerce' ),
			3 => __( 'Occasional buyer', 'puredevs-customer-history-for-woocommerce' ),
			2 => __( 'Repeat buyer', 'puredevs-customer-history-for-woocommerce' ),
			1 => __( 'One-time buyer', 'puredevs-customer-history-for-woocommerce' ),
		);

		return sprintf(
			/* translators: %1$d: total orders, %2$s: frequency description */
			__( '%1$d total orders (%2$s)', 'puredevs-customer-history-for-woocommerce' ),
			$total_orders,
			$descriptions[ $score ]
		);
	}

	/**
	 * Get monetary description text
	 *
	 * @since    2.0.0
	 * @param    int      $score         Monetary score
	 * @param    float    $total_spent   Total amount spent
	 * @param    float    $vs_average    Multiplier vs store average
	 * @return   string                  Description text
	 */
	public function pdwchs_get_monetary_description( $score, $total_spent, $vs_average ) {
		if ( $score == 0 ) {
			return __( 'No orders yet', 'puredevs-customer-history-for-woocommerce' );
		}

		$descriptions = array(
			5 => __( 'High value', 'puredevs-customer-history-for-woocommerce' ),
			4 => __( 'Above average', 'puredevs-customer-history-for-woocommerce' ),
			3 => __( 'Above average', 'puredevs-customer-history-for-woocommerce' ),
			2 => __( 'Average', 'puredevs-customer-history-for-woocommerce' ),
			1 => __( 'Below average', 'puredevs-customer-history-for-woocommerce' ),
		);

		return sprintf(
			/* translators: %1$s: total spent with currency, %2$s: multiplier vs average, %3$s: value description */
			__( 'Total spent: %1$s (%2$sx store average - %3$s)', 'puredevs-customer-history-for-woocommerce' ),
			wc_price( $total_spent ),
			number_format( $vs_average, 1 ),
			$descriptions[ $score ]
		);
	}
}
