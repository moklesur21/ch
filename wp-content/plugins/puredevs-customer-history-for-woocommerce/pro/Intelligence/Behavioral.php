<?php
/**
 * Customer Behavioral Segmentation Detector
 *
 * Analyzes customer behavior patterns and assigns behavioral tags
 * based on purchase history, session data, and order patterns.
 *
 * @link       https://puredevs.com/
 * @since      3.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * Behavioral Segmentation Detector Class
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Behavioral {

	/**
	 * All behavior types with their labels and cache column mappings
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      array
	 */
	private $behavior_types = array(
		'bargain_hunter'       => array( 'label' => 'Bargain Hunter',       'column' => 'is_bargain_hunter',       'icon' => 'dashicons-tag' ),
		'impulse_buyer'        => array( 'label' => 'Impulse Buyer',        'column' => 'is_impulse_buyer',        'icon' => 'dashicons-superhero' ),
		'brand_loyal'          => array( 'label' => 'Brand Loyal',          'column' => 'is_brand_loyal',          'icon' => 'dashicons-heart' ),
		'gift_shopper'         => array( 'label' => 'Gift Shopper',         'column' => 'is_gift_shopper',         'icon' => 'dashicons-gifts' ),
		'weekend_shopper'      => array( 'label' => 'Weekend Shopper',      'column' => 'is_weekend_shopper',      'icon' => 'dashicons-calendar-alt' ),
		'night_owl'            => array( 'label' => 'Night Owl',            'column' => 'is_night_owl',            'icon' => 'dashicons-visibility' ),
		'cart_abandoner'       => array( 'label' => 'Cart Abandoner',       'column' => 'is_cart_abandoner',       'icon' => 'dashicons-cart' ),
		'researcher'           => array( 'label' => 'Researcher',           'column' => 'is_researcher',           'icon' => 'dashicons-search' ),
		'subscription_seeker'  => array( 'label' => 'Subscription Seeker',  'column' => 'is_subscription_seeker',  'icon' => 'dashicons-update' ),
		'category_specialist'  => array( 'label' => 'Category Specialist',  'column' => 'is_category_specialist',  'icon' => 'dashicons-category' ),
		'bulk_buyer'           => array( 'label' => 'Bulk Buyer',           'column' => 'is_bulk_buyer',           'icon' => 'dashicons-archive' ),
		'trendsetter'          => array( 'label' => 'Trendsetter',          'column' => 'is_trendsetter',          'icon' => 'dashicons-star-filled' ),
		'seasonal_shopper'     => array( 'label' => 'Seasonal Shopper',     'column' => 'is_seasonal_shopper',     'icon' => 'dashicons-calendar' ),
		'mobile_shopper'       => array( 'label' => 'Mobile Shopper',       'column' => 'is_mobile_shopper',       'icon' => 'dashicons-smartphone' ),
		'desktop_shopper'      => array( 'label' => 'Desktop Shopper',      'column' => 'is_desktop_shopper',      'icon' => 'dashicons-desktop' ),
	);

	/**
	 * Get all behavior types
	 *
	 * @since    3.0.0
	 * @return   array
	 */
	public function get_behavior_types() {
		return $this->behavior_types;
	}

	/**
	 * Analyze a single customer - runs all detectors, saves tags, updates cache
	 *
	 * @since    3.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Array of detected behaviors
	 */
	public function analyze_customer( $user_id ) {
		$user_id = intval( $user_id );
		if ( ! $user_id || ! get_user_by( 'id', $user_id ) ) {
			return array();
		}

		$detected = array();
		$detectors = array(
			'bargain_hunter'      => 'detect_bargain_hunter',
			'impulse_buyer'       => 'detect_impulse_buyer',
			'brand_loyal'         => 'detect_brand_loyal',
			'gift_shopper'        => 'detect_gift_shopper',
			'weekend_shopper'     => 'detect_weekend_shopper',
			'night_owl'           => 'detect_night_owl',
			'cart_abandoner'      => 'detect_cart_abandoner',
			'researcher'          => 'detect_researcher',
			'subscription_seeker' => 'detect_subscription_seeker',
			'category_specialist' => 'detect_category_specialist',
			'bulk_buyer'          => 'detect_bulk_buyer',
			'trendsetter'         => 'detect_trendsetter',
			'seasonal_shopper'    => 'detect_seasonal_shopper',
			'mobile_shopper'      => 'detect_mobile_shopper',
			'desktop_shopper'     => 'detect_desktop_shopper',
		);

		foreach ( $detectors as $type => $method ) {
			$result = $this->$method( $user_id );
			if ( $result ) {
				$result['behavior_type']  = $type;
				$result['behavior_label'] = $this->behavior_types[ $type ]['label'];
				$this->save_behavioral_tag( $user_id, $result );
				$detected[ $type ] = $result;
			} else {
				$this->deactivate_tag( $user_id, $type );
			}
		}

		$this->update_behavior_cache( $user_id, $detected );
		$this->mark_analyzed( $user_id );

		return $detected;
	}

	/**
	 * Save or update a behavioral tag
	 *
	 * @since    3.0.0
	 * @param    int    $user_id        WordPress user ID
	 * @param    array  $behavior_data  Behavior detection result
	 */
	public function save_behavioral_tag( $user_id, $behavior_data ) {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_behavioral_tags';
		$now   = current_time( 'mysql' );

		$existing = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT id, first_detected FROM {$table} WHERE user_id = %d AND behavior_type = %s",
				$user_id,
				$behavior_data['behavior_type']
			)
		);

		$metadata = isset( $behavior_data['metadata'] ) ? wp_json_encode( $behavior_data['metadata'] ) : null;

		if ( $existing ) {
			$wpdb->update(
				$table,
				array(
					'behavior_label'   => $behavior_data['behavior_label'],
					'confidence_score' => intval( $behavior_data['confidence_score'] ),
					'evidence_count'   => intval( $behavior_data['evidence_count'] ),
					'last_confirmed'   => $now,
					'metadata'         => $metadata,
					'is_active'        => 1,
					'updated_at'       => $now,
				),
				array( 'id' => $existing->id ),
				array( '%s', '%d', '%d', '%s', '%s', '%d', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table,
				array(
					'user_id'          => $user_id,
					'behavior_type'    => $behavior_data['behavior_type'],
					'behavior_label'   => $behavior_data['behavior_label'],
					'confidence_score' => intval( $behavior_data['confidence_score'] ),
					'evidence_count'   => intval( $behavior_data['evidence_count'] ),
					'first_detected'   => $now,
					'last_confirmed'   => $now,
					'metadata'         => $metadata,
					'is_active'        => 1,
					'created_at'       => $now,
					'updated_at'       => $now,
				),
				array( '%d', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s' )
			);
		}
	}

	/**
	 * Deactivate a specific tag for a user
	 *
	 * @since    3.0.0
	 * @param    int       $user_id         WordPress user ID
	 * @param    string    $behavior_type   Behavior type slug
	 */
	private function deactivate_tag( $user_id, $behavior_type ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_behavioral_tags';
		$wpdb->update(
			$table,
			array( 'is_active' => 0, 'updated_at' => current_time( 'mysql' ) ),
			array( 'user_id' => $user_id, 'behavior_type' => $behavior_type ),
			array( '%d', '%s' ),
			array( '%d', '%s' )
		);
	}

	/**
	 * Update the behavioral patterns cache table
	 *
	 * @since    3.0.0
	 * @param    int    $user_id     WordPress user ID
	 * @param    array  $behaviors   Detected behaviors
	 */
	public function update_behavior_cache( $user_id, $behaviors ) {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_behavioral_patterns';
		$now   = current_time( 'mysql' );

		// Build boolean flags
		$data = array( 'user_id' => $user_id );
		foreach ( $this->behavior_types as $type => $config ) {
			$data[ $config['column'] ] = isset( $behaviors[ $type ] ) ? 1 : 0;
		}

		// Extract preferences
		$data['preferred_categories']    = wp_json_encode( $this->extract_preferred_categories( $user_id ) );
		$data['preferred_brands']        = wp_json_encode( $this->extract_preferred_brands( $user_id ) );
		$data['preferred_price_range']   = wp_json_encode( $this->extract_price_range( $user_id ) );
		$data['preferred_shopping_times'] = wp_json_encode( $this->extract_shopping_times( $user_id ) );

		// Extract metrics
		$data['avg_decision_time_hours'] = $this->calculate_avg_decision_time( $user_id );
		$data['avg_items_per_order']     = $this->calculate_avg_items_per_order( $user_id );
		$data['coupon_usage_rate']       = $this->calculate_coupon_usage( $user_id );
		$data['cart_abandonment_rate']   = $this->calculate_abandonment_rate( $user_id );
		$data['total_behavioral_tags']   = count( $behaviors );
		$data['last_analyzed']           = $now;
		$data['updated_at']              = $now;

		$existing = $wpdb->get_var(
			$wpdb->prepare( "SELECT user_id FROM {$table} WHERE user_id = %d", $user_id )
		);

		if ( $existing ) {
			unset( $data['user_id'] );
			$wpdb->update( $table, $data, array( 'user_id' => $user_id ) );
		} else {
			$data['created_at'] = $now;
			$wpdb->insert( $table, $data );
		}
	}

	/**
	 * Set last_analyzed timestamp
	 *
	 * @since    3.0.0
	 * @param    int    $user_id    WordPress user ID
	 */
	public function mark_analyzed( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_behavioral_patterns';
		$wpdb->update(
			$table,
			array( 'last_analyzed' => current_time( 'mysql' ) ),
			array( 'user_id' => $user_id ),
			array( '%s' ),
			array( '%d' )
		);
	}

	/**
	 * Get all active tags for a customer
	 *
	 * @since    3.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Array of tag objects
	 */
	public function get_customer_tags( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_behavioral_tags';
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE user_id = %d AND is_active = 1 ORDER BY confidence_score DESC",
				$user_id
			)
		);
	}

	/**
	 * Get the customer's behavioral patterns cache row
	 *
	 * @since    3.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   object|null
	 */
	public function get_customer_patterns( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_behavioral_patterns';
		return $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE user_id = %d", $user_id )
		);
	}

	/**
	 * Get count of customers per behavior type
	 *
	 * @since    3.0.0
	 * @return   array    Associative array of behavior_type => count
	 */
	public function get_behavior_counts() {
		global $wpdb;
		$table  = $wpdb->prefix . 'pdwchs_behavioral_patterns';
		$counts = array();

		foreach ( $this->behavior_types as $type => $config ) {
			$column = $config['column'];
			$count  = $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$column} = 1" );
			$counts[ $type ] = intval( $count );
		}

		return $counts;
	}

	/**
	 * Deactivate expired tags (not confirmed in 90 days)
	 *
	 * @since    3.0.0
	 * @param    int    $user_id    WordPress user ID
	 */
	public function deactivate_expired_tags( $user_id ) {
		global $wpdb;
		$table    = $wpdb->prefix . 'pdwchs_behavioral_tags';
		$cutoff   = gmdate( 'Y-m-d H:i:s', strtotime( '-90 days' ) );

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$table} SET is_active = 0, updated_at = %s WHERE user_id = %d AND last_confirmed < %s AND is_active = 1",
				current_time( 'mysql' ),
				$user_id,
				$cutoff
			)
		);
	}

	// =========================================================================
	// Detection Algorithms
	// =========================================================================

	/**
	 * Detect bargain hunter behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_bargain_hunter( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-12 months' ) ),
			'limit'       => -1,
		) );

		if ( count( $orders ) < 3 ) {
			return null;
		}

		$coupon_orders = 0;
		$total_discount = 0;
		foreach ( $orders as $order ) {
			$coupons = $order->get_coupon_codes();
			if ( ! empty( $coupons ) ) {
				$coupon_orders++;
			}
			$total_discount += floatval( $order->get_discount_total() );
		}

		$coupon_rate = ( $coupon_orders / count( $orders ) ) * 100;

		if ( $coupon_rate < 70 ) {
			return null;
		}

		return array(
			'confidence_score' => min( 100, intval( $coupon_rate ) ),
			'evidence_count'   => $coupon_orders,
			'metadata'         => array(
				'coupon_usage_rate' => round( $coupon_rate, 1 ),
				'total_discount'    => round( $total_discount, 2 ),
				'orders_analyzed'   => count( $orders ),
			),
		);
	}

	/**
	 * Detect impulse buyer behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_impulse_buyer( $user_id ) {
		if ( ! PDWCHS_Sessions_Data::table_exists() ) {
			return null;
		}

		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-12 months' ) ),
			'limit'       => -1,
			'orderby'     => 'date',
			'order'       => 'ASC',
		) );

		if ( count( $orders ) < 3 ) {
			return null;
		}

		$quick_decisions = 0;
		$total_decision_hours = 0;
		$orders_with_sessions = 0;

		foreach ( $orders as $order ) {
			$order_date = $order->get_date_created();
			if ( ! $order_date ) {
				continue;
			}

			// Find first session within 30 days before the order
			$first_session = PDWCHS_Sessions_Data::get_first_session_date_in_range(
				$user_id,
				gmdate( 'Y-m-d H:i:s', $order_date->getTimestamp() - ( 30 * DAY_IN_SECONDS ) ),
				$order_date->format( 'Y-m-d H:i:s' )
			);

			if ( $first_session ) {
				$hours = ( $order_date->getTimestamp() - strtotime( $first_session ) ) / 3600;
				$total_decision_hours += $hours;
				$orders_with_sessions++;
				if ( $hours < 3 ) {
					$quick_decisions++;
				}
			}
		}

		if ( $orders_with_sessions < 3 ) {
			return null;
		}

		$quick_rate     = ( $quick_decisions / $orders_with_sessions ) * 100;
		$avg_hours      = $total_decision_hours / $orders_with_sessions;

		if ( $quick_rate < 60 && $avg_hours >= 3 ) {
			return null;
		}

		$confidence = min( 100, max( intval( $quick_rate ), intval( ( 1 - ( $avg_hours / 24 ) ) * 100 ) ) );

		return array(
			'confidence_score' => max( 50, $confidence ),
			'evidence_count'   => $orders_with_sessions,
			'metadata'         => array(
				'quick_decision_rate' => round( $quick_rate, 1 ),
				'avg_decision_hours'  => round( $avg_hours, 1 ),
				'orders_with_data'    => $orders_with_sessions,
			),
		);
	}

	/**
	 * Detect brand loyal behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_brand_loyal( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => -1,
		) );

		if ( count( $orders ) < 3 ) {
			return null;
		}

		$brand_counts = array();
		$total_items  = 0;

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				$brand = get_post_meta( $product_id, '_product_brand', true );
				if ( empty( $brand ) ) {
					// Try WooCommerce Brands taxonomy
					$terms = get_the_terms( $product_id, 'product_brand' );
					if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
						$brand = $terms[0]->name;
					}
				}
				if ( ! empty( $brand ) ) {
					if ( ! isset( $brand_counts[ $brand ] ) ) {
						$brand_counts[ $brand ] = 0;
					}
					$brand_counts[ $brand ]++;
					$total_items++;
				}
			}
		}

		if ( $total_items < 3 ) {
			return null;
		}

		arsort( $brand_counts );
		$top_brand      = key( $brand_counts );
		$top_brand_rate = ( $brand_counts[ $top_brand ] / $total_items ) * 100;

		$loyal_brands = 0;
		foreach ( $brand_counts as $count ) {
			if ( $count >= 2 ) {
				$loyal_brands++;
			}
		}

		if ( $top_brand_rate < 40 && $loyal_brands < 2 ) {
			return null;
		}

		return array(
			'confidence_score' => min( 100, intval( $top_brand_rate ) + ( $loyal_brands * 10 ) ),
			'evidence_count'   => $total_items,
			'metadata'         => array(
				'top_brand'          => $top_brand,
				'top_brand_rate'     => round( $top_brand_rate, 1 ),
				'loyal_brands_count' => $loyal_brands,
				'brand_distribution' => array_slice( $brand_counts, 0, 5, true ),
			),
		);
	}

	/**
	 * Detect gift shopper behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_gift_shopper( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-24 months' ) ),
			'limit'       => -1,
		) );

		if ( count( $orders ) < 3 ) {
			return null;
		}

		$gift_months         = array( 2, 5, 6, 11, 12 ); // Feb, May, Jun, Nov, Dec
		$seasonal_orders     = 0;
		$gift_wrap_orders    = 0;
		$product_diversity   = array();

		foreach ( $orders as $order ) {
			$order_date = $order->get_date_created();
			if ( ! $order_date ) {
				continue;
			}

			$month = intval( $order_date->format( 'm' ) );
			if ( in_array( $month, $gift_months, true ) ) {
				$seasonal_orders++;
			}

			// Check for gift wrap
			$gift_wrap = $order->get_meta( '_gift_wrap' );
			if ( $gift_wrap ) {
				$gift_wrap_orders++;
			}

			// Track product diversity per order
			$order_products = array();
			foreach ( $order->get_items() as $item ) {
				$order_products[] = $item->get_product_id();
			}
			$product_diversity[] = count( array_unique( $order_products ) );
		}

		$total              = count( $orders );
		$seasonal_rate      = ( $seasonal_orders / $total ) * 100;
		$gift_wrap_rate     = ( $gift_wrap_orders / $total ) * 100;
		$avg_diversity      = ! empty( $product_diversity ) ? array_sum( $product_diversity ) / count( $product_diversity ) : 0;

		if ( $seasonal_rate < 50 && $gift_wrap_rate < 30 && $avg_diversity < 3 ) {
			return null;
		}

		$confidence = max( intval( $seasonal_rate ), intval( $gift_wrap_rate ), intval( $avg_diversity * 20 ) );

		return array(
			'confidence_score' => min( 100, $confidence ),
			'evidence_count'   => $seasonal_orders + $gift_wrap_orders,
			'metadata'         => array(
				'seasonal_concentration' => round( $seasonal_rate, 1 ),
				'gift_wrap_rate'         => round( $gift_wrap_rate, 1 ),
				'avg_product_diversity'  => round( $avg_diversity, 1 ),
				'peak_months'            => $this->get_peak_months( $orders, $gift_months ),
			),
		);
	}

	/**
	 * Detect weekend shopper behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_weekend_shopper( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-12 months' ) ),
			'limit'       => -1,
		) );

		if ( count( $orders ) < 5 ) {
			return null;
		}

		$weekend_orders = 0;
		$day_stats      = array();

		foreach ( $orders as $order ) {
			$order_date = $order->get_date_created();
			if ( ! $order_date ) {
				continue;
			}

			$day_of_week = intval( $order_date->format( 'w' ) ); // 0=Sun, 6=Sat
			if ( ! isset( $day_stats[ $day_of_week ] ) ) {
				$day_stats[ $day_of_week ] = 0;
			}
			$day_stats[ $day_of_week ]++;

			if ( $day_of_week === 0 || $day_of_week === 6 ) {
				$weekend_orders++;
			}
		}

		$total          = count( $orders );
		$weekend_rate   = ( $weekend_orders / $total ) * 100;

		if ( $weekend_rate < 60 ) {
			return null;
		}

		return array(
			'confidence_score' => min( 100, intval( $weekend_rate ) ),
			'evidence_count'   => $weekend_orders,
			'metadata'         => array(
				'weekend_percentage' => round( $weekend_rate, 1 ),
				'preferred_day'      => $this->get_preferred_day( $day_stats ),
				'day_distribution'   => $day_stats,
			),
		);
	}

	/**
	 * Detect night owl behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_night_owl( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-12 months' ) ),
			'limit'       => -1,
		) );

		if ( count( $orders ) < 5 ) {
			return null;
		}

		$night_hours  = array( 21, 22, 23, 0, 1, 2, 3 );
		$night_orders = 0;
		$hour_stats   = array();

		foreach ( $orders as $order ) {
			$order_date = $order->get_date_created();
			if ( ! $order_date ) {
				continue;
			}

			$hour = intval( $order_date->format( 'G' ) );
			if ( ! isset( $hour_stats[ $hour ] ) ) {
				$hour_stats[ $hour ] = 0;
			}
			$hour_stats[ $hour ]++;

			if ( in_array( $hour, $night_hours, true ) ) {
				$night_orders++;
			}
		}

		$total      = count( $orders );
		$night_rate = ( $night_orders / $total ) * 100;

		if ( $night_rate < 50 ) {
			return null;
		}

		return array(
			'confidence_score' => min( 100, intval( $night_rate ) ),
			'evidence_count'   => $night_orders,
			'metadata'         => array(
				'night_percentage' => round( $night_rate, 1 ),
				'avg_order_hour'   => $this->calculate_avg_hour( $hour_stats ),
				'hour_distribution' => $hour_stats,
			),
		);
	}

	/**
	 * Detect cart abandoner behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_cart_abandoner( $user_id ) {
		// Count orders with various terminal statuses in last 6 months
		$completed = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-6 months' ) ),
			'limit'       => -1,
			'return'      => 'ids',
		) );

		$cancelled = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'cancelled', 'failed' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-6 months' ) ),
			'limit'       => -1,
			'return'      => 'ids',
		) );

		$pending = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'pending' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-6 months' ) ),
			'limit'       => -1,
			'return'      => 'ids',
		) );

		$total_carts = count( $completed ) + count( $cancelled ) + count( $pending );

		if ( $total_carts < 5 ) {
			return null;
		}

		$abandoned      = count( $cancelled ) + count( $pending );
		$abandonment_rate = ( $abandoned / $total_carts ) * 100;

		if ( $abandonment_rate < 30 ) {
			return null;
		}

		return array(
			'confidence_score' => min( 100, intval( $abandonment_rate ) ),
			'evidence_count'   => $abandoned,
			'metadata'         => array(
				'abandonment_rate'  => round( $abandonment_rate, 1 ),
				'abandoned_carts'   => $abandoned,
				'completed_orders'  => count( $completed ),
				'total_carts'       => $total_carts,
			),
		);
	}

	/**
	 * Detect researcher behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_researcher( $user_id ) {
		if ( ! PDWCHS_Sessions_Data::table_exists() ) {
			return null;
		}

		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-12 months' ) ),
			'limit'       => -1,
			'orderby'     => 'date',
			'order'       => 'ASC',
		) );

		if ( count( $orders ) < 2 ) {
			return null;
		}

		$research_counts = array();
		$days_researching = array();

		foreach ( $orders as $order ) {
			$order_date = $order->get_date_created();
			if ( ! $order_date ) {
				continue;
			}

			// Count sessions within 30 days before order
			$range_start    = gmdate( 'Y-m-d H:i:s', $order_date->getTimestamp() - ( 30 * DAY_IN_SECONDS ) );
			$range_end      = $order_date->format( 'Y-m-d H:i:s' );
			$session_count  = PDWCHS_Sessions_Data::get_distinct_days_in_range( $user_id, $range_start, $range_end );
			$total_sessions = PDWCHS_Sessions_Data::get_session_count_by_user( $user_id, array( 'start' => $range_start, 'end' => $range_end ) );

			if ( $total_sessions >= 3 ) {
				$research_counts[] = $total_sessions;
				$days_researching[] = intval( $session_count );
			}
		}

		if ( count( $research_counts ) < 2 ) {
			return null;
		}

		$avg_sessions = array_sum( $research_counts ) / count( $research_counts );
		$avg_days     = array_sum( $days_researching ) / count( $days_researching );

		if ( $avg_sessions < 5 && $avg_days < 3 ) {
			return null;
		}

		$confidence = min( 100, intval( ( $avg_sessions / 10 ) * 50 + ( $avg_days / 7 ) * 50 ) );

		return array(
			'confidence_score' => max( 50, $confidence ),
			'evidence_count'   => count( $research_counts ),
			'metadata'         => array(
				'avg_sessions_before_purchase' => round( $avg_sessions, 1 ),
				'avg_days_researching'         => round( $avg_days, 1 ),
				'purchases_with_research'      => count( $research_counts ),
			),
		);
	}

	/**
	 * Detect subscription seeker behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_subscription_seeker( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => -1,
		) );

		if ( count( $orders ) < 2 ) {
			return null;
		}

		$subscription_items  = 0;
		$total_items         = 0;
		$subscription_products = array();

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				$total_items++;

				$product = wc_get_product( $product_id );
				if ( $product && ( $product->get_type() === 'subscription' || $product->get_type() === 'variable-subscription' ) ) {
					$subscription_items++;
					$subscription_products[ $product_id ] = true;
				}
			}
		}

		// Check session visits to subscription pages
		$subscription_views = 0;
		if ( PDWCHS_Sessions_Data::table_exists() ) {
			$subscription_views = PDWCHS_Sessions_Data::get_session_count_by_user( $user_id, array( 'url_like' => '%subscription%' ) );
		}

		$subscription_rate = $total_items > 0 ? ( $subscription_items / $total_items ) * 100 : 0;
		$unique_sub_products = count( $subscription_products );

		if ( $subscription_rate < 25 && $unique_sub_products < 2 && $subscription_views < 5 ) {
			return null;
		}

		$confidence = min( 100, intval( $subscription_rate ) + ( $unique_sub_products * 15 ) + ( $subscription_views * 5 ) );

		return array(
			'confidence_score' => max( 50, $confidence ),
			'evidence_count'   => $subscription_items + $subscription_views,
			'metadata'         => array(
				'subscription_rate'        => round( $subscription_rate, 1 ),
				'unique_subscription_products' => $unique_sub_products,
				'subscription_page_views'  => $subscription_views,
			),
		);
	}

	/**
	 * Detect category specialist behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_category_specialist( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => -1,
		) );

		if ( count( $orders ) < 3 ) {
			return null;
		}

		$cat_counts  = array();
		$total_items = 0;

		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				$terms      = get_the_terms( $product_id, 'product_cat' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						if ( ! isset( $cat_counts[ $term->name ] ) ) {
							$cat_counts[ $term->name ] = 0;
						}
						$cat_counts[ $term->name ]++;
					}
				}
				$total_items++;
			}
		}

		if ( $total_items < 3 ) {
			return null;
		}

		arsort( $cat_counts );
		$top_cats = array_values( $cat_counts );
		$top_cat_name = key( $cat_counts );
		$top_rate = ( $top_cats[0] / $total_items ) * 100;
		$top_2_rate = isset( $top_cats[1] ) ? ( ( $top_cats[0] + $top_cats[1] ) / $total_items ) * 100 : $top_rate;

		if ( $top_rate < 50 && $top_2_rate < 70 ) {
			return null;
		}

		return array(
			'confidence_score' => min( 100, intval( max( $top_rate, $top_2_rate ) ) ),
			'evidence_count'   => $top_cats[0],
			'metadata'         => array(
				'top_category'          => $top_cat_name,
				'top_category_rate'     => round( $top_rate, 1 ),
				'top_2_categories_rate' => round( $top_2_rate, 1 ),
				'category_distribution' => array_slice( $cat_counts, 0, 5, true ),
			),
		);
	}

	/**
	 * Detect bulk buyer behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_bulk_buyer( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-12 months' ) ),
			'limit'       => -1,
		) );

		if ( count( $orders ) < 3 ) {
			return null;
		}

		$total_items     = 0;
		$bulk_orders     = 0;
		$items_per_order = array();

		foreach ( $orders as $order ) {
			$order_items = 0;
			foreach ( $order->get_items() as $item ) {
				$order_items += intval( $item->get_quantity() );
			}
			$total_items += $order_items;
			$items_per_order[] = $order_items;
			if ( $order_items >= 10 ) {
				$bulk_orders++;
			}
		}

		$avg_items = $total_items / count( $orders );

		if ( $avg_items < 5 && $bulk_orders === 0 ) {
			return null;
		}

		$confidence = min( 100, intval( $avg_items * 10 ) + ( $bulk_orders * 20 ) );

		return array(
			'confidence_score' => max( 50, $confidence ),
			'evidence_count'   => count( $orders ),
			'metadata'         => array(
				'avg_items_per_order' => round( $avg_items, 1 ),
				'bulk_orders'         => $bulk_orders,
				'max_items_in_order'  => max( $items_per_order ),
			),
		);
	}

	/**
	 * Detect trendsetter behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_trendsetter( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-12 months' ) ),
			'limit'       => -1,
		) );

		if ( count( $orders ) < 3 ) {
			return null;
		}

		$early_purchases = 0;
		$total_products  = 0;

		foreach ( $orders as $order ) {
			$order_date = $order->get_date_created();
			if ( ! $order_date ) {
				continue;
			}

			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				$product    = wc_get_product( $product_id );
				if ( ! $product ) {
					continue;
				}

				$total_products++;
				$product_date = $product->get_date_created();
				if ( $product_date ) {
					$days_since_published = ( $order_date->getTimestamp() - $product_date->getTimestamp() ) / DAY_IN_SECONDS;
					if ( $days_since_published >= 0 && $days_since_published <= 30 ) {
						$early_purchases++;
					}
				}
			}
		}

		if ( $total_products < 3 ) {
			return null;
		}

		$early_rate = ( $early_purchases / $total_products ) * 100;

		if ( $early_rate < 30 ) {
			return null;
		}

		return array(
			'confidence_score' => min( 100, intval( $early_rate ) + 20 ),
			'evidence_count'   => $early_purchases,
			'metadata'         => array(
				'early_adoption_rate'  => round( $early_rate, 1 ),
				'early_purchases'      => $early_purchases,
				'total_products'       => $total_products,
			),
		);
	}

	/**
	 * Detect seasonal shopper behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_seasonal_shopper( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'date_after'  => gmdate( 'Y-m-d', strtotime( '-24 months' ) ),
			'limit'       => -1,
		) );

		if ( count( $orders ) < 4 ) {
			return null;
		}

		// Define seasonal windows (3-month each)
		$seasons = array(
			'winter_holidays' => array( 11, 12, 1 ),
			'spring'          => array( 3, 4, 5 ),
			'summer'          => array( 6, 7, 8 ),
			'fall'            => array( 9, 10, 11 ),
		);

		$monthly_counts = array_fill( 1, 12, 0 );
		foreach ( $orders as $order ) {
			$order_date = $order->get_date_created();
			if ( $order_date ) {
				$month = intval( $order_date->format( 'm' ) );
				$monthly_counts[ $month ]++;
			}
		}

		$total = count( $orders );
		$best_season_count = 0;
		$best_season_name  = '';

		foreach ( $seasons as $name => $months ) {
			$count = 0;
			foreach ( $months as $m ) {
				$count += $monthly_counts[ $m ];
			}
			if ( $count > $best_season_count ) {
				$best_season_count = $count;
				$best_season_name  = $name;
			}
		}

		$seasonal_rate = ( $best_season_count / $total ) * 100;

		if ( $seasonal_rate < 60 ) {
			return null;
		}

		return array(
			'confidence_score' => min( 100, intval( $seasonal_rate ) ),
			'evidence_count'   => $best_season_count,
			'metadata'         => array(
				'seasonal_concentration' => round( $seasonal_rate, 1 ),
				'peak_season'            => str_replace( '_', ' ', ucfirst( $best_season_name ) ),
				'monthly_distribution'   => $monthly_counts,
			),
		);
	}

	/**
	 * Detect mobile shopper behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_mobile_shopper( $user_id ) {
		if ( ! PDWCHS_Sessions_Data::table_exists() ) {
			return null;
		}

		// Get sessions with referer containing user-agent hints
		$sessions = PDWCHS_Sessions_Data::get_user_sessions_referer( $user_id, 100 );

		if ( count( $sessions ) < 5 ) {
			return null;
		}

		$mobile_count = 0;
		foreach ( $sessions as $session ) {
			if ( preg_match( '/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|Opera Mini|IEMobile/i', $session->referer ) ) {
				$mobile_count++;
			}
		}

		$total       = count( $sessions );
		$mobile_rate = ( $mobile_count / $total ) * 100;

		if ( $mobile_rate < 70 ) {
			return null;
		}

		return array(
			'confidence_score' => min( 100, intval( $mobile_rate ) ),
			'evidence_count'   => $mobile_count,
			'metadata'         => array(
				'mobile_percentage'  => round( $mobile_rate, 1 ),
				'sessions_analyzed'  => $total,
			),
		);
	}

	/**
	 * Detect desktop shopper behavior
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array|null
	 */
	private function detect_desktop_shopper( $user_id ) {
		if ( ! PDWCHS_Sessions_Data::table_exists() ) {
			return null;
		}

		$sessions = PDWCHS_Sessions_Data::get_user_sessions_referer( $user_id, 100 );

		if ( count( $sessions ) < 5 ) {
			return null;
		}

		$desktop_count = 0;
		foreach ( $sessions as $session ) {
			if ( ! preg_match( '/Mobile|Android|iPhone|iPad|iPod|webOS|BlackBerry|Opera Mini|IEMobile/i', $session->referer ) ) {
				$desktop_count++;
			}
		}

		$total        = count( $sessions );
		$desktop_rate = ( $desktop_count / $total ) * 100;

		if ( $desktop_rate < 70 ) {
			return null;
		}

		return array(
			'confidence_score' => min( 100, intval( $desktop_rate ) ),
			'evidence_count'   => $desktop_count,
			'metadata'         => array(
				'desktop_percentage' => round( $desktop_rate, 1 ),
				'sessions_analyzed'  => $total,
			),
		);
	}

	// =========================================================================
	// Helper Calculation Methods
	// =========================================================================

	/**
	 * Extract top 5 preferred categories
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array
	 */
	public function extract_preferred_categories( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => -1,
		) );

		$cat_counts = array();
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$terms = get_the_terms( $item->get_product_id(), 'product_cat' );
				if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
					foreach ( $terms as $term ) {
						if ( ! isset( $cat_counts[ $term->name ] ) ) {
							$cat_counts[ $term->name ] = 0;
						}
						$cat_counts[ $term->name ]++;
					}
				}
			}
		}

		arsort( $cat_counts );
		return array_slice( $cat_counts, 0, 5, true );
	}

	/**
	 * Extract top 5 preferred brands
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array
	 */
	public function extract_preferred_brands( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => -1,
		) );

		$brand_counts = array();
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$product_id = $item->get_product_id();
				$brand = get_post_meta( $product_id, '_product_brand', true );
				if ( empty( $brand ) ) {
					$terms = get_the_terms( $product_id, 'product_brand' );
					if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
						$brand = $terms[0]->name;
					}
				}
				if ( ! empty( $brand ) ) {
					if ( ! isset( $brand_counts[ $brand ] ) ) {
						$brand_counts[ $brand ] = 0;
					}
					$brand_counts[ $brand ]++;
				}
			}
		}

		arsort( $brand_counts );
		return array_slice( $brand_counts, 0, 5, true );
	}

	/**
	 * Extract price range preferences
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array
	 */
	public function extract_price_range( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => -1,
		) );

		$prices = array();
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$total = floatval( $item->get_total() );
				$qty   = intval( $item->get_quantity() );
				if ( $qty > 0 ) {
					$prices[] = $total / $qty;
				}
			}
		}

		if ( empty( $prices ) ) {
			return array( 'min' => 0, 'max' => 0, 'avg' => 0 );
		}

		return array(
			'min' => round( min( $prices ), 2 ),
			'max' => round( max( $prices ), 2 ),
			'avg' => round( array_sum( $prices ) / count( $prices ), 2 ),
		);
	}

	/**
	 * Extract shopping time preferences
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   array
	 */
	public function extract_shopping_times( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => -1,
		) );

		$day_counts  = array();
		$hour_counts = array();

		foreach ( $orders as $order ) {
			$date = $order->get_date_created();
			if ( ! $date ) {
				continue;
			}
			$day  = intval( $date->format( 'w' ) );
			$hour = intval( $date->format( 'G' ) );
			$day_counts[ $day ] = isset( $day_counts[ $day ] ) ? $day_counts[ $day ] + 1 : 1;
			$hour_counts[ $hour ] = isset( $hour_counts[ $hour ] ) ? $hour_counts[ $hour ] + 1 : 1;
		}

		$day_names = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
		$peak_day  = ! empty( $day_counts ) ? $day_names[ array_search( max( $day_counts ), $day_counts ) ] : 'N/A';
		$peak_hour = ! empty( $hour_counts ) ? array_search( max( $hour_counts ), $hour_counts ) : 0;

		return array(
			'peak_day'  => $peak_day,
			'peak_hour' => $peak_hour,
			'day_distribution'  => $day_counts,
			'hour_distribution' => $hour_counts,
		);
	}

	/**
	 * Calculate average decision time (hours from first visit to purchase)
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   float|null
	 */
	public function calculate_avg_decision_time( $user_id ) {
		if ( ! PDWCHS_Sessions_Data::table_exists() ) {
			return null;
		}

		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => 20,
			'orderby'     => 'date',
			'order'       => 'DESC',
		) );

		$times = array();
		foreach ( $orders as $order ) {
			$order_date = $order->get_date_created();
			if ( ! $order_date ) {
				continue;
			}
			$first_session = PDWCHS_Sessions_Data::get_first_session_date_in_range(
				$user_id,
				gmdate( 'Y-m-d H:i:s', $order_date->getTimestamp() - ( 30 * DAY_IN_SECONDS ) ),
				$order_date->format( 'Y-m-d H:i:s' )
			);
			if ( $first_session ) {
				$hours = ( $order_date->getTimestamp() - strtotime( $first_session ) ) / 3600;
				$times[] = $hours;
			}
		}

		return ! empty( $times ) ? round( array_sum( $times ) / count( $times ), 2 ) : null;
	}

	/**
	 * Calculate average items per order
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   float|null
	 */
	public function calculate_avg_items_per_order( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => -1,
		) );

		if ( empty( $orders ) ) {
			return null;
		}

		$total_items = 0;
		foreach ( $orders as $order ) {
			foreach ( $order->get_items() as $item ) {
				$total_items += intval( $item->get_quantity() );
			}
		}

		return round( $total_items / count( $orders ), 2 );
	}

	/**
	 * Calculate coupon usage percentage
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   float|null
	 */
	public function calculate_coupon_usage( $user_id ) {
		$orders = wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => -1,
		) );

		if ( empty( $orders ) ) {
			return null;
		}

		$coupon_orders = 0;
		foreach ( $orders as $order ) {
			if ( ! empty( $order->get_coupon_codes() ) ) {
				$coupon_orders++;
			}
		}

		return round( ( $coupon_orders / count( $orders ) ) * 100, 2 );
	}

	/**
	 * Calculate cart abandonment rate
	 *
	 * @since    3.0.0
	 * @param    int    $user_id
	 * @return   float|null
	 */
	public function calculate_abandonment_rate( $user_id ) {
		$completed = count( wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'completed', 'processing' ),
			'limit'       => -1,
			'return'      => 'ids',
		) ) );

		$abandoned = count( wc_get_orders( array(
			'customer_id' => $user_id,
			'status'      => array( 'cancelled', 'failed', 'pending' ),
			'limit'       => -1,
			'return'      => 'ids',
		) ) );

		$total = $completed + $abandoned;
		if ( $total === 0 ) {
			return null;
		}

		return round( ( $abandoned / $total ) * 100, 2 );
	}

	/**
	 * Get preferred day name from day stats
	 *
	 * @since    3.0.0
	 * @param    array    $day_stats    Day => count mapping
	 * @return   string
	 */
	private function get_preferred_day( $day_stats ) {
		if ( empty( $day_stats ) ) {
			return 'N/A';
		}
		$day_names = array( 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday' );
		$max_day   = array_search( max( $day_stats ), $day_stats );
		return isset( $day_names[ $max_day ] ) ? $day_names[ $max_day ] : 'N/A';
	}

	/**
	 * Calculate weighted average order hour
	 *
	 * @since    3.0.0
	 * @param    array    $hour_stats    Hour => count mapping
	 * @return   float
	 */
	private function calculate_avg_hour( $hour_stats ) {
		if ( empty( $hour_stats ) ) {
			return 0;
		}
		$weighted_sum = 0;
		$total_count  = 0;
		foreach ( $hour_stats as $hour => $count ) {
			$weighted_sum += $hour * $count;
			$total_count  += $count;
		}
		return $total_count > 0 ? round( $weighted_sum / $total_count, 1 ) : 0;
	}

	/**
	 * Get peak months from order data
	 *
	 * @since    3.0.0
	 * @param    array    $orders        WC_Order array
	 * @param    array    $gift_months   Gift month numbers
	 * @return   array
	 */
	private function get_peak_months( $orders, $gift_months ) {
		$month_names = array(
			1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
			5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
			9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December',
		);

		$monthly = array_fill( 1, 12, 0 );
		foreach ( $orders as $order ) {
			$date = $order->get_date_created();
			if ( $date ) {
				$monthly[ intval( $date->format( 'm' ) ) ]++;
			}
		}

		arsort( $monthly );
		$peaks = array();
		$i = 0;
		foreach ( $monthly as $month => $count ) {
			if ( $count > 0 && $i < 3 ) {
				$peaks[] = $month_names[ $month ];
				$i++;
			}
		}

		return $peaks;
	}

	// =========================================================================
	// AJAX Handlers
	// =========================================================================

	/**
	 * AJAX: Manual behavior analysis for a customer
	 *
	 * @since    3.0.0
	 */
	public function pdwchs_ajax_manual_behavior_analysis() {
		check_ajax_referer( 'pdwchs_behavioral_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? intval( $_POST['user_id'] ) : 0;
		if ( ! $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid user ID', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$behaviors = $this->analyze_customer( $user_id );
		$tags      = $this->get_customer_tags( $user_id );
		$patterns  = $this->get_customer_patterns( $user_id );

		// Get insights
		$insights_engine = new PD_Customer_History_Behavioral_Insights();
		$next_action     = $insights_engine->get_next_best_action( $user_id );

		$tag_data = array();
		foreach ( $tags as $tag ) {
			$type_config = isset( $this->behavior_types[ $tag->behavior_type ] ) ? $this->behavior_types[ $tag->behavior_type ] : null;
			$insight     = $insights_engine->get_behavior_insight( $tag );

			$tag_data[] = array(
				'type'             => $tag->behavior_type,
				'label'            => $tag->behavior_label,
				'confidence_score' => intval( $tag->confidence_score ),
				'evidence_count'   => intval( $tag->evidence_count ),
				'metadata'         => json_decode( $tag->metadata, true ),
				'icon'             => $type_config ? $type_config['icon'] : 'dashicons-tag',
				'description'      => $insight ? $insight['description'] : '',
				'recommendation'   => $insight ? $insight['action'] : '',
				'first_detected'   => $tag->first_detected,
				'last_confirmed'   => $tag->last_confirmed,
			);
		}

		$preferences = array();
		if ( $patterns ) {
			$preferences = array(
				'categories'     => json_decode( $patterns->preferred_categories, true ),
				'brands'         => json_decode( $patterns->preferred_brands, true ),
				'price_range'    => json_decode( $patterns->preferred_price_range, true ),
				'shopping_times' => json_decode( $patterns->preferred_shopping_times, true ),
			);
		}

		wp_send_json_success( array(
			'behaviors_detected' => count( $behaviors ),
			'tags'               => $tag_data,
			'preferences'        => $preferences,
			'next_action'        => $next_action,
			'last_analyzed'      => $patterns ? $patterns->last_analyzed : null,
		) );
	}

	/**
	 * AJAX: Get behavioral segments data for dashboard widget
	 *
	 * @since    3.0.0
	 */
	public function pdwchs_ajax_behavioral_segments_data() {
		check_ajax_referer( 'pdwchs_behavioral_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_behavioral_patterns';

		$total_customers = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" ) );

		$segments = array();
		foreach ( $this->behavior_types as $type => $config ) {
			$count = intval( $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE {$config['column']} = 1" ) );
			$segments[] = array(
				'type'       => $type,
				'label'      => $config['label'],
				'icon'       => $config['icon'],
				'count'      => $count,
				'percentage' => $total_customers > 0 ? round( ( $count / $total_customers ) * 100, 1 ) : 0,
			);
		}

		// Sort by count descending
		usort( $segments, function( $a, $b ) { return $b['count'] - $a['count']; } );

		// Build actionable insights
		$insights = array();
		$counts   = $this->get_behavior_counts();
		if ( ! empty( $counts['cart_abandoner'] ) ) {
			$insights[] = sprintf( __( '%d customers need cart recovery emails', 'puredevs-customer-history-for-woocommerce' ), $counts['cart_abandoner'] );
		}
		if ( ! empty( $counts['bargain_hunter'] ) ) {
			$insights[] = sprintf( __( '%d customers respond well to discount offers', 'puredevs-customer-history-for-woocommerce' ), $counts['bargain_hunter'] );
		}
		if ( ! empty( $counts['weekend_shopper'] ) ) {
			$insights[] = sprintf( __( '%d customers shop primarily on weekends', 'puredevs-customer-history-for-woocommerce' ), $counts['weekend_shopper'] );
		}
		if ( ! empty( $counts['night_owl'] ) ) {
			$insights[] = sprintf( __( '%d customers are most active during night hours', 'puredevs-customer-history-for-woocommerce' ), $counts['night_owl'] );
		}

		wp_send_json_success( array(
			'segments'        => $segments,
			'insights'        => $insights,
			'total_customers' => $total_customers,
		) );
	}

	/**
	 * AJAX: Filter customers by behavior
	 *
	 * @since    3.0.0
	 */
	public function pdwchs_ajax_filter_by_behavior() {
		check_ajax_referer( 'pdwchs_behavioral_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$behavior_types = isset( $_POST['behavior_types'] ) ? array_map( 'sanitize_text_field', (array) $_POST['behavior_types'] ) : array();
		$match_mode     = isset( $_POST['match_mode'] ) ? sanitize_text_field( $_POST['match_mode'] ) : 'any';

		if ( empty( $behavior_types ) ) {
			wp_send_json_error( array( 'message' => __( 'No behavior types selected', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_behavioral_patterns';

		$conditions = array();
		foreach ( $behavior_types as $type ) {
			if ( isset( $this->behavior_types[ $type ] ) ) {
				$conditions[] = $this->behavior_types[ $type ]['column'] . ' = 1';
			}
		}

		if ( empty( $conditions ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid behavior types', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$operator = $match_mode === 'all' ? ' AND ' : ' OR ';
		$where    = implode( $operator, $conditions );

		$user_ids = $wpdb->get_col( "SELECT user_id FROM {$table} WHERE {$where}" );

		wp_send_json_success( array(
			'user_ids' => array_map( 'intval', $user_ids ),
			'count'    => count( $user_ids ),
		) );
	}

	// =========================================================================
	// Cron & Order Hooks
	// =========================================================================

	/**
	 * Daily batch analysis of all customers
	 *
	 * @since    3.0.0
	 */
	public function pdwchs_analyze_all_behaviors() {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_behavioral_patterns';

		// Get customers needing analysis (not analyzed in 7+ days, or never analyzed)
		$cutoff   = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days' ) );
		$user_ids = $wpdb->get_col(
			"SELECT DISTINCT u.ID FROM {$wpdb->users} u
			 LEFT JOIN {$table} bp ON u.ID = bp.user_id
			 WHERE bp.last_analyzed IS NULL OR bp.last_analyzed < '{$cutoff}'
			 LIMIT 500"
		);

		foreach ( $user_ids as $user_id ) {
			$this->analyze_customer( intval( $user_id ) );
			usleep( 200000 ); // 0.2 second pause
		}
	}

	/**
	 * Handle order completion - schedule delayed analysis
	 *
	 * @since    3.0.0
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

		// Schedule analysis 5 minutes after order completion
		if ( ! wp_next_scheduled( 'pdwchs_analyze_customer_behavior', array( $customer_id ) ) ) {
			wp_schedule_single_event( time() + 300, 'pdwchs_analyze_customer_behavior', array( $customer_id ) );
		}
	}

	/**
	 * Analyze a single customer's behavior (cron callback)
	 *
	 * @since    3.0.0
	 * @param    int    $user_id    WordPress user ID
	 */
	public function pdwchs_analyze_single_customer( $user_id ) {
		$this->analyze_customer( intval( $user_id ) );
	}
}
