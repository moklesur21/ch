<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Segments Manager
 *
 * Provides CRUD operations, member counting, and default seeding for saved customer segments.
 *
 * @since      5.0.0
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes/segments
 */
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// Reason: All queries use $wpdb->prepare() with %s/%d placeholders for user input.
// Interpolated variables are internal table names ($wpdb->prefix + literal) and
// whitelisted column names from PD_Customer_History_Behavioral::get_behavior_types() —
// never user input. The member-count public method caches via transients.
class PDWCHS_Segments_Manager {

	const SEGMENT_COUNT_CACHE_TTL = 10 * MINUTE_IN_SECONDS; // 600 seconds

	/**
	 * Allowed criteria keys — matches $allowed_search_keys in class-pd-customer-history-customers.php line 80.
	 *
	 * @since  5.0.0
	 * @var    array
	 */
	private static $allowed_criteria_keys = array(
		'user_role', 'customer_country', 'order_number', 'order_number_input',
		'total_spent', 'total_spent_input', 'start_date', 'end_date',
		'total_amount', 'total_order_input', 'order_status', 'payment_method',
		's', 'product_filter', 'rfm_segment', 'health_score_range',
		'health_trend', 'lifecycle_stage', 'lifecycle_days', 'behavior_filter',
		'show-items',
	);

	/**
	 * Create a new segment.
	 *
	 * @since  5.0.0
	 * @param  string  $name
	 * @param  string  $description
	 * @param  array   $criteria
	 * @param  bool    $is_default
	 * @return int|WP_Error  Inserted row ID on success, WP_Error on failure.
	 */
	public function pdwchs_create_segment( $name, $description, $criteria, $is_default = false ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_segments';

		$name = sanitize_text_field( wp_unslash( $name ) );
		if ( '' === $name ) {
			return new WP_Error( 'pdwchs_invalid_name', __( 'Segment name cannot be empty.', 'puredevs-customer-history-for-woocommerce' ) );
		}
		if ( strlen( $name ) > 191 ) {
			return new WP_Error( 'pdwchs_name_too_long', __( 'Segment name cannot exceed 191 characters.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$description = sanitize_textarea_field( wp_unslash( $description ) );

		$criteria = $this->pdwchs_whitelist_criteria( $criteria );
		if ( empty( $criteria ) ) {
			return new WP_Error( 'pdwchs_invalid_criteria', __( 'Segment criteria must contain at least one valid filter.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$criteria_json = wp_json_encode( $criteria );
		if ( false === $criteria_json ) {
			return new WP_Error( 'pdwchs_encode_error', __( 'Failed to encode segment criteria.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$is_default = (bool) $is_default ? 1 : 0;
		$created_by = is_user_logged_in() ? get_current_user_id() : null;

		// Enforce segment creation cap; Pro listener raises this via filter.
		$cap = (int) apply_filters( 'pdwchs_segment_creation_cap', 1 );
		if ( $cap > 0 ) {
			$existing = (int) $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*) FROM {$table} WHERE is_default = %d",
					0
				)
			);
			if ( $existing >= $cap ) {
				return new WP_Error(
					'pdwchs_segment_cap_reached',
					__( 'Free plan is limited to 1 custom segment. Upgrade to Pro for unlimited segments.', 'puredevs-customer-history-for-woocommerce' )
				);
			}
		}

		$result = $wpdb->insert(
			$table,
			array(
				'name'        => $name,
				'description' => $description,
				'criteria'    => $criteria_json,
				'is_default'  => $is_default,
				'created_by'  => $created_by,
			),
			array( '%s', '%s', '%s', '%d', '%d' )
		);

		if ( false === $result ) {
			return new WP_Error( 'pdwchs_db_error', __( 'Database error while saving segment.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		return (int) $wpdb->insert_id;
	}

	/**
	 * Get all segments ordered by defaults first, then oldest custom first.
	 *
	 * @since  5.0.0
	 * @param  array  $args  Reserved for future pagination.
	 * @return array         Array of stdClass objects.
	 */
	public function pdwchs_get_segments( $args = array() ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_segments';
		return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY is_default DESC, created_at ASC" );
	}

	/**
	 * Get a single segment by ID.
	 *
	 * @since  5.0.0
	 * @param  int         $id
	 * @return object|null
	 */
	public function pdwchs_get_segment( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_segments';

		$id = absint( $id );
		if ( 0 === $id ) {
			return null;
		}

		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $id ) );
	}

	/**
	 * Update an existing segment.
	 *
	 * @since  5.0.0
	 * @param  int          $id
	 * @param  array        $data  Keys: name, description, criteria (any subset).
	 * @return true|WP_Error
	 */
	public function pdwchs_update_segment( $id, $data ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_segments';

		$id = absint( $id );
		if ( 0 === $id ) {
			return new WP_Error( 'pdwchs_invalid_id', __( 'Invalid segment ID.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$row = $this->pdwchs_get_segment( $id );
		if ( null === $row ) {
			return new WP_Error( 'pdwchs_not_found', __( 'Segment not found.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		if ( 1 === (int) $row->is_default && ( array_key_exists( 'name', $data ) || array_key_exists( 'criteria', $data ) ) ) {
			return new WP_Error( 'pdwchs_default_protected', __( 'Default segments cannot have their name or criteria changed.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$update_data = array();
		$format      = array();

		if ( array_key_exists( 'name', $data ) ) {
			$name = sanitize_text_field( wp_unslash( $data['name'] ) );
			if ( '' === $name ) {
				return new WP_Error( 'pdwchs_invalid_name', __( 'Segment name cannot be empty.', 'puredevs-customer-history-for-woocommerce' ) );
			}
			if ( strlen( $name ) > 191 ) {
				return new WP_Error( 'pdwchs_name_too_long', __( 'Segment name cannot exceed 191 characters.', 'puredevs-customer-history-for-woocommerce' ) );
			}
			$update_data['name'] = $name;
			$format[]            = '%s';
		}

		if ( array_key_exists( 'description', $data ) ) {
			$update_data['description'] = sanitize_textarea_field( wp_unslash( $data['description'] ) );
			$format[]                   = '%s';
		}

		if ( array_key_exists( 'criteria', $data ) ) {
			$criteria = $this->pdwchs_whitelist_criteria( $data['criteria'] );
			if ( empty( $criteria ) ) {
				return new WP_Error( 'pdwchs_invalid_criteria', __( 'Segment criteria must contain at least one valid filter.', 'puredevs-customer-history-for-woocommerce' ) );
			}
			$criteria_json = wp_json_encode( $criteria );
			if ( false === $criteria_json ) {
				return new WP_Error( 'pdwchs_encode_error', __( 'Failed to encode segment criteria.', 'puredevs-customer-history-for-woocommerce' ) );
			}
			$update_data['criteria'] = $criteria_json;
			$format[]                = '%s';
		}

		if ( empty( $update_data ) ) {
			return true;
		}

		$result = $wpdb->update( $table, $update_data, array( 'id' => $id ), $format, array( '%d' ) );

		if ( false === $result ) {
			return new WP_Error( 'pdwchs_db_error', __( 'Database error while updating segment.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		return true;
	}

	/**
	 * Delete a custom segment. Default segments cannot be deleted.
	 *
	 * @since  5.0.0
	 * @param  int         $id
	 * @return true|WP_Error
	 */
	public function pdwchs_delete_segment( $id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_segments';

		$id = absint( $id );
		if ( 0 === $id ) {
			return new WP_Error( 'pdwchs_invalid_id', __( 'Invalid segment ID.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$row = $this->pdwchs_get_segment( $id );
		if ( null === $row ) {
			return new WP_Error( 'pdwchs_not_found', __( 'Segment not found.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		if ( 1 === (int) $row->is_default ) {
			return new WP_Error( 'pdwchs_default_protected', __( 'Default segments cannot be deleted.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		delete_transient( 'pdwchs_seg_count_' . md5( $row->criteria ) );

		$result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );

		if ( false === $result ) {
			return new WP_Error( 'pdwchs_db_error', __( 'Database error while deleting segment.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		return true;
	}

	/**
	 * Count how many customers currently match the given criteria.
	 *
	 * @since  5.0.0
	 * @param  array  $criteria
	 * @return int
	 */
	public function pdwchs_count_segment_members( $criteria ) {
		$criteria  = $this->pdwchs_whitelist_criteria( $criteria );
		$cache_key = 'pdwchs_seg_count_' . md5( wp_json_encode( $criteria ) );

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return (int) $cached;
		}

		$customers = PD_Customer_History_Customers::pdwchs_woo_get_all_customers( '', $criteria );
		$customers = $this->pdwchs_apply_criteria_filters( $customers, $criteria );
		$count     = count( $customers );

		set_transient( $cache_key, $count, self::SEGMENT_COUNT_CACHE_TTL );

		return $count;
	}

	/**
	 * Return WP_User objects matching $criteria. Used by the CSV export handler.
	 * Unlike pdwchs_count_segment_members(), this method is not cached.
	 *
	 * @since  5.0.0
	 * @param  array  $criteria
	 * @return WP_User[]
	 */
	public function pdwchs_get_segment_members( $criteria ) {
		$criteria  = $this->pdwchs_whitelist_criteria( $criteria );
		$customers = PD_Customer_History_Customers::pdwchs_woo_get_all_customers( '', $criteria );
		return $this->pdwchs_apply_criteria_filters( $customers, $criteria );
	}

	/**
	 * Seed the 5 factory-default segments. Idempotent — safe to call on every activation.
	 *
	 * @since  5.0.0
	 * @return void
	 */
	public static function pdwchs_seed_default_segments() {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_segments';

		// No user input — prepare() not required
		$existing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_default = 1" );
		if ( $existing > 0 ) {
			return;
		}

		// All default segments are Pro-only; seeded by PDWCHS_Pro_Segments_Defaults via H15.
	}

	/**
	 * Strip any criteria key not in the allowed whitelist.
	 *
	 * @since  5.0.0
	 * @param  mixed  $criteria
	 * @return array
	 */
	private function pdwchs_whitelist_criteria( $criteria ) {
		if ( ! is_array( $criteria ) ) {
			return array();
		}
		return array_intersect_key( $criteria, array_flip( self::$allowed_criteria_keys ) );
	}

	/**
	 * Apply the same PHP-level filter pipeline used in PD_Customer_History_Customers::prepare_items()
	 * but reading from a $criteria array rather than $_GET.
	 *
	 * @since  5.0.0
	 * @param  array  $customers  WP_User objects from pdwchs_woo_get_all_customers().
	 * @param  array  $criteria   Whitelisted criteria array.
	 * @return array
	 */
	private function pdwchs_apply_criteria_filters( $customers, $criteria ) {
		global $wpdb;

		$data_sync_enabled = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );

		// Block 1 — Hide no-order users (reads store option; no criteria key)
		if ( get_option( 'pd-woo-hide_users_with_no_orders' ) ) {
			foreach ( $customers as $key => $user ) {
				if ( isset( $data_sync_enabled ) && 'yes' === $data_sync_enabled ) {
					$order_count = count( get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_customer_user',
						'meta_value'  => $user->ID,
						'post_type'   => 'shop_order',
						'post_status' => 'any',
						'post_parent' => '0',
					) ) );
				} else {
					$order_count = wc_get_customer_order_count( $user->ID );
				}
				if ( ! $order_count > 0 ) {
					unset( $customers[ $key ] );
				}
			}
		}

		// Block 2 — Total spent
		$total_spent_filter = ! empty( $criteria['total_spent'] ) ? sanitize_text_field( $criteria['total_spent'] ) : '';
		$total_spent_input  = ! empty( $criteria['total_spent_input'] ) ? floatval( $criteria['total_spent_input'] ) : 0;

		if ( ! empty( $total_spent_filter ) && $total_spent_input > 0 ) {
			foreach ( $customers as $key => $user ) {
				$total_spent = PD_Customer_History_Customers::pdwchs_woo_get_customer_total_spent( $user->ID );

				if ( 'equal' === $total_spent_filter && $total_spent != $total_spent_input ) {
					unset( $customers[ $key ] );
				}
				if ( 'greater_then' === $total_spent_filter && $total_spent <= $total_spent_input ) {
					unset( $customers[ $key ] );
				}
				if ( 'less_then' === $total_spent_filter && $total_spent >= $total_spent_input ) {
					unset( $customers[ $key ] );
				}
			}
		}

		// Block 3 — Order count
		$order_number_filter = ! empty( $criteria['order_number'] ) ? sanitize_text_field( $criteria['order_number'] ) : '';
		$order_number_input  = ! empty( $criteria['order_number_input'] ) ? absint( $criteria['order_number_input'] ) : 0;

		if ( ! empty( $order_number_filter ) && $order_number_input > 0 ) {
			foreach ( $customers as $key => $user ) {
				if ( isset( $data_sync_enabled ) && 'yes' === $data_sync_enabled ) {
					$order_count = count( get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_customer_user',
						'meta_value'  => $user->ID,
						'post_type'   => 'shop_order',
						'post_status' => 'any',
						'post_parent' => '0',
					) ) );
				} else {
					$order_count = wc_get_customer_order_count( $user->ID );
				}

				if ( 'equal' === $order_number_filter && $order_count != $order_number_input ) {
					unset( $customers[ $key ] );
				}
				if ( 'greater_then' === $order_number_filter && $order_count <= $order_number_input ) {
					unset( $customers[ $key ] );
				}
				if ( 'less_then' === $order_number_filter && $order_count >= $order_number_input ) {
					unset( $customers[ $key ] );
				}
			}
		}

		// Block 4 — Product purchased (logic inlined; distinct cache prefix pdwchs_seg_prod_ to avoid collisions)
		if ( ! empty( $criteria['product_filter'] ) ) {
			$product_id    = intval( $criteria['product_filter'] );
			$cache_key     = 'pdwchs_seg_prod_' . $product_id;
			$cached_result = get_transient( $cache_key );

			if ( false !== $cached_result ) {
				$customers_with_product = $cached_result;
			} else {
				$customer_ids   = array();
				$page           = 1;
				$limit          = 100;
				$order_statuses = array( 'completed', 'processing', 'on-hold', 'pending', 'refunded', 'failed' );

				while ( true ) {
					$orders = wc_get_orders( array(
						'limit'  => $limit,
						'page'   => $page,
						'status' => $order_statuses,
						'return' => 'ids',
					) );

					if ( empty( $orders ) ) {
						break;
					}

					foreach ( $orders as $order_id ) {
						$order = wc_get_order( $order_id );
						if ( ! $order ) {
							continue;
						}
						foreach ( $order->get_items() as $item ) {
							$product_id_in_order = $item->get_product_id();
							$variation_id        = $item->get_variation_id();
							if ( $product_id_in_order == $product_id || $variation_id == $product_id ) {
								$customer_id = $order->get_customer_id();
								if ( $customer_id > 0 ) {
									$customer_ids[] = $customer_id;
								}
								break;
							}
						}
					}

					$page++;
					if ( $page > 1000 ) {
						break;
					}
				}

				$customers_with_product = array_unique( $customer_ids );
				set_transient( $cache_key, $customers_with_product, HOUR_IN_SECONDS );
			}

			foreach ( $customers as $key => $user ) {
				if ( ! in_array( $user->ID, $customers_with_product ) ) {
					unset( $customers[ $key ] );
				}
			}
		}

		$customers = $this->pdwchs_apply_intelligence_filters( $customers, $criteria );

		$customers = array_values( $customers );

		return $customers;
	}

	/**
	 * Apply the Pro "intelligence" criteria (RFM segment, health score range, health
	 * trend, lifecycle stage, days-in-stage, behavior) to an already-fetched customer
	 * set. Split out from pdwchs_apply_criteria_filters() so the customer list table can
	 * reuse the exact same membership logic (see pdwchs_filter_customers_by_intelligence()).
	 *
	 * @since  5.0.0
	 * @param  array  $customers  WP_User objects.
	 * @param  array  $criteria   Whitelisted criteria array.
	 * @return array
	 */
	private function pdwchs_apply_intelligence_filters( $customers, $criteria ) {
		global $wpdb;

		// Block 5 — RFM segment
		if ( ! empty( $criteria['rfm_segment'] ) ) {
			$selected_segment = sanitize_text_field( $criteria['rfm_segment'] );
			$intel_table      = $wpdb->prefix . 'pdwchs_customer_intelligence';
			$customer_ids     = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT user_id FROM {$intel_table} WHERE rfm_segment = %s",
					$selected_segment
				)
			);
			$customer_ids = $customer_ids ? $customer_ids : array();

			foreach ( $customers as $key => $user ) {
				if ( ! in_array( $user->ID, $customer_ids ) ) {
					unset( $customers[ $key ] );
				}
			}
		}

		// Block 6 — Health score range
		if ( ! empty( $criteria['health_score_range'] ) ) {
			$selected_range = sanitize_text_field( $criteria['health_score_range'] );
			$range_map      = array(
				'excellent' => array( 'min' => 81, 'max' => 100 ),
				'good'      => array( 'min' => 61, 'max' => 80 ),
				'fair'      => array( 'min' => 41, 'max' => 60 ),
				'poor'      => array( 'min' => 21, 'max' => 40 ),
				'critical'  => array( 'min' => 0,  'max' => 20 ),
			);

			if ( isset( $range_map[ $selected_range ] ) ) {
				$min         = $range_map[ $selected_range ]['min'];
				$max         = $range_map[ $selected_range ]['max'];
				$intel_table = $wpdb->prefix . 'pdwchs_customer_intelligence';
				$customer_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_id FROM {$intel_table}
						 WHERE health_score >= %d AND health_score <= %d
						 AND health_score IS NOT NULL",
						$min,
						$max
					)
				);
				$customer_ids = $customer_ids ? $customer_ids : array();

				foreach ( $customers as $key => $user ) {
					if ( ! in_array( $user->ID, $customer_ids ) ) {
						unset( $customers[ $key ] );
					}
				}
			}
		}

		// Block 7 — Health trend
		if ( ! empty( $criteria['health_trend'] ) ) {
			$selected_trend = sanitize_text_field( $criteria['health_trend'] );
			$valid_trends   = array( 'up', 'down', 'stable' );

			if ( in_array( $selected_trend, $valid_trends ) ) {
				$intel_table  = $wpdb->prefix . 'pdwchs_customer_intelligence';
				$customer_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_id FROM {$intel_table}
						 WHERE health_trend = %s
						 AND health_score IS NOT NULL",
						$selected_trend
					)
				);
				$customer_ids = $customer_ids ? $customer_ids : array();

				foreach ( $customers as $key => $user ) {
					if ( ! in_array( $user->ID, $customer_ids ) ) {
						unset( $customers[ $key ] );
					}
				}
			}
		}

		// Block 8 — Lifecycle stage
		if ( ! empty( $criteria['lifecycle_stage'] ) ) {
			$selected_stage = sanitize_text_field( $criteria['lifecycle_stage'] );
			$intel_table    = $wpdb->prefix . 'pdwchs_customer_intelligence';
			$customer_ids   = $wpdb->get_col(
				$wpdb->prepare(
					"SELECT user_id FROM {$intel_table} WHERE lifecycle_stage = %s",
					$selected_stage
				)
			);
			$customer_ids = $customer_ids ? $customer_ids : array();

			foreach ( $customers as $key => $user ) {
				if ( ! in_array( $user->ID, $customer_ids ) ) {
					unset( $customers[ $key ] );
				}
			}
		}

		// Block 9 — Days in stage
		if ( ! empty( $criteria['lifecycle_days'] ) ) {
			$selected_days = sanitize_text_field( $criteria['lifecycle_days'] );
			$days_map      = array(
				'0-30'  => array( 'min' => 0,  'max' => 30 ),
				'31-60' => array( 'min' => 31, 'max' => 60 ),
				'61-90' => array( 'min' => 61, 'max' => 90 ),
				'90+'   => array( 'min' => 91, 'max' => 99999 ),
			);

			if ( isset( $days_map[ $selected_days ] ) ) {
				$min         = $days_map[ $selected_days ]['min'];
				$max         = $days_map[ $selected_days ]['max'];
				$intel_table = $wpdb->prefix . 'pdwchs_customer_intelligence';
				$customer_ids = $wpdb->get_col(
					$wpdb->prepare(
						"SELECT user_id FROM {$intel_table}
						 WHERE lifecycle_days_in_stage >= %d
						 AND lifecycle_days_in_stage <= %d
						 AND lifecycle_stage IS NOT NULL",
						$min,
						$max
					)
				);
				$customer_ids = $customer_ids ? $customer_ids : array();

				foreach ( $customers as $key => $user ) {
					if ( ! in_array( $user->ID, $customer_ids ) ) {
						unset( $customers[ $key ] );
					}
				}
			}
		}

		// Block 10 — Behavior (behavior_match is not in allowed_criteria_keys; match_mode fixed to 'any')
		if ( ! empty( $criteria['behavior_filter'] ) ) {
			if ( ! class_exists( 'PD_Customer_History_Behavioral' ) ) {
				return $customers; // behavioral filtering requires Pro
			}
			$selected_behavior = sanitize_text_field( $criteria['behavior_filter'] );
			$match_mode        = 'any';

			$bp_table       = $wpdb->prefix . 'pdwchs_behavioral_patterns';
			$behavioral     = new PD_Customer_History_Behavioral();
			$behavior_types = $behavioral->get_behavior_types();

			$types      = array_map( 'sanitize_text_field', explode( ',', $selected_behavior ) );
			$conditions = array();

			foreach ( $types as $type ) {
				$type = trim( $type );
				if ( isset( $behavior_types[ $type ] ) ) {
					$conditions[] = $behavior_types[ $type ]['column'] . ' = 1';
				}
			}

			if ( ! empty( $conditions ) ) {
				$operator     = 'all' === $match_mode ? ' AND ' : ' OR ';
				$where        = implode( $operator, $conditions );
				// $where is built solely from $behavior_types column names — no user input in SQL
				$customer_ids = $wpdb->get_col( "SELECT user_id FROM {$bp_table} WHERE {$where}" );
				$customer_ids = $customer_ids ? array_map( 'intval', $customer_ids ) : array();

				foreach ( $customers as $key => $user ) {
					if ( ! in_array( $user->ID, $customer_ids ) ) {
						unset( $customers[ $key ] );
					}
				}
			}
		}

		$customers = array_values( $customers );

		return $customers;
	}

	/**
	 * Public entry point for the customer list table: apply only the intelligence
	 * criteria (RFM / health / lifecycle / behavior) to an already-fetched customer set,
	 * using the same logic that powers the segment count and CSV export. This keeps the
	 * "Load segment" list in sync with the saved member count.
	 *
	 * @since  5.0.0
	 * @param  array  $customers  WP_User objects from pdwchs_woo_get_all_customers().
	 * @param  array  $criteria   Raw criteria (will be whitelisted).
	 * @return array
	 */
	public function pdwchs_filter_customers_by_intelligence( $customers, $criteria ) {
		$criteria = $this->pdwchs_whitelist_criteria( $criteria );
		return array_values( $this->pdwchs_apply_intelligence_filters( $customers, $criteria ) );
	}
}
