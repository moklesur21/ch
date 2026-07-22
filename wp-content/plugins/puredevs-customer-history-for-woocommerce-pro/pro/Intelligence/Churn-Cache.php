<?php
/**
 * Churn Prediction Cache Manager
 *
 * WordPress transient-based caching for churn prediction data
 * to reduce database queries and improve performance.
 *
 * @link       https://puredevs.com/
 * @since      4.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * Churn Cache Manager Class
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Churn_Cache {

	/**
	 * Cache prefix for transients
	 *
	 * @since    4.0.0
	 * @var      string
	 */
	const CACHE_PREFIX = 'pdwchs_churn_';

	/**
	 * TTL for individual customer score cache (1 hour)
	 *
	 * @since    4.0.0
	 * @var      int
	 */
	const SCORE_TTL = HOUR_IN_SECONDS;

	/**
	 * TTL for aggregate statistics cache (30 minutes)
	 *
	 * @since    4.0.0
	 * @var      int
	 */
	const STATS_TTL = 1800;

	/**
	 * Get cached churn score for a customer
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    Customer ID
	 * @return   array|false    Cached data or false if not cached
	 */
	public function get_cached_score( $customer_id ) {
		$key  = self::CACHE_PREFIX . 'score_' . $customer_id;
		$data = get_transient( $key );

		return $data !== false ? $data : false;
	}

	/**
	 * Set cached churn score for a customer
	 *
	 * @since    4.0.0
	 * @param    int      $customer_id    Customer ID
	 * @param    array    $data           Churn score data
	 * @return   bool
	 */
	public function set_cached_score( $customer_id, $data ) {
		$key = self::CACHE_PREFIX . 'score_' . $customer_id;
		return set_transient( $key, $data, self::SCORE_TTL );
	}

	/**
	 * Invalidate cached churn score for a customer
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    Customer ID
	 * @return   bool
	 */
	public function invalidate_customer_cache( $customer_id ) {
		$score_key      = self::CACHE_PREFIX . 'score_' . $customer_id;
		$prediction_key = self::CACHE_PREFIX . 'prediction_' . $customer_id;
		$engagement_key = self::CACHE_PREFIX . 'engagement_' . $customer_id;

		delete_transient( $score_key );
		delete_transient( $prediction_key );
		delete_transient( $engagement_key );

		// Also invalidate aggregate stats since individual data changed
		$this->invalidate_statistics_cache();

		return true;
	}

	/**
	 * Get cached aggregate statistics
	 *
	 * @since    4.0.0
	 * @return   array|false    Cached statistics or false
	 */
	public function get_cached_statistics() {
		$key  = self::CACHE_PREFIX . 'statistics';
		$data = get_transient( $key );

		return $data !== false ? $data : false;
	}

	/**
	 * Set cached aggregate statistics
	 *
	 * @since    4.0.0
	 * @param    array    $stats    Statistics data
	 * @return   bool
	 */
	public function set_cached_statistics( $stats ) {
		$key = self::CACHE_PREFIX . 'statistics';
		return set_transient( $key, $stats, self::STATS_TTL );
	}

	/**
	 * Invalidate statistics cache
	 *
	 * @since    4.0.0
	 * @return   bool
	 */
	public function invalidate_statistics_cache() {
		delete_transient( self::CACHE_PREFIX . 'statistics' );
		delete_transient( self::CACHE_PREFIX . 'distribution' );
		delete_transient( self::CACHE_PREFIX . 'alert_stats' );
		return true;
	}

	/**
	 * Get cached risk distribution
	 *
	 * @since    4.0.0
	 * @return   array|false
	 */
	public function get_cached_distribution() {
		return get_transient( self::CACHE_PREFIX . 'distribution' );
	}

	/**
	 * Set cached risk distribution
	 *
	 * @since    4.0.0
	 * @param    array    $distribution    Distribution data
	 * @return   bool
	 */
	public function set_cached_distribution( $distribution ) {
		return set_transient( self::CACHE_PREFIX . 'distribution', $distribution, self::STATS_TTL );
	}

	/**
	 * Get cached prediction for a customer
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    Customer ID
	 * @return   array|false
	 */
	public function get_cached_prediction( $customer_id ) {
		$key = self::CACHE_PREFIX . 'prediction_' . $customer_id;
		return get_transient( $key );
	}

	/**
	 * Set cached prediction for a customer
	 *
	 * @since    4.0.0
	 * @param    int      $customer_id    Customer ID
	 * @param    array    $data           Prediction data
	 * @return   bool
	 */
	public function set_cached_prediction( $customer_id, $data ) {
		$key = self::CACHE_PREFIX . 'prediction_' . $customer_id;
		return set_transient( $key, $data, self::SCORE_TTL );
	}

	/**
	 * Get cached engagement metrics for a customer
	 *
	 * @since    4.0.0
	 * @param    int    $customer_id    Customer ID
	 * @return   array|false
	 */
	public function get_cached_engagement( $customer_id ) {
		$key = self::CACHE_PREFIX . 'engagement_' . $customer_id;
		return get_transient( $key );
	}

	/**
	 * Set cached engagement metrics for a customer
	 *
	 * @since    4.0.0
	 * @param    int      $customer_id    Customer ID
	 * @param    array    $data           Engagement data
	 * @return   bool
	 */
	public function set_cached_engagement( $customer_id, $data ) {
		$key = self::CACHE_PREFIX . 'engagement_' . $customer_id;
		return set_transient( $key, $data, self::SCORE_TTL );
	}
}
