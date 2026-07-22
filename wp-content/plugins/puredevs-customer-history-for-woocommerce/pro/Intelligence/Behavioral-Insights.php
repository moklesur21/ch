<?php
/**
 * Customer Behavioral Insights Engine
 *
 * Generates actionable insights and next-best-action recommendations
 * based on customer behavioral tags.
 *
 * @link       https://puredevs.com/
 * @since      3.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * Behavioral Insights Engine Class
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Behavioral_Insights {

	/**
	 * Insights map for all 15 behavior types
	 *
	 * @since    3.0.0
	 * @access   private
	 * @var      array
	 */
	private $insights_map;

	/**
	 * Constructor
	 *
	 * @since    3.0.0
	 */
	public function __construct() {
		$this->insights_map = array(
			'bargain_hunter' => array(
				'title'       => __( 'Bargain Hunter', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer frequently uses coupons and is motivated by discounts and deals.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Send exclusive discount codes and flash sale notifications to drive repeat purchases.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 7,
				'icon'        => 'dashicons-tag',
			),
			'impulse_buyer' => array(
				'title'       => __( 'Impulse Buyer', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer makes quick purchase decisions with minimal browsing time.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Use urgency tactics like limited-time offers and low-stock alerts to trigger purchases.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 8,
				'icon'        => 'dashicons-superhero',
			),
			'brand_loyal' => array(
				'title'       => __( 'Brand Loyal', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer repeatedly purchases from the same brand(s).', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Notify about new arrivals from their preferred brand and offer loyalty rewards.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 6,
				'icon'        => 'dashicons-heart',
			),
			'gift_shopper' => array(
				'title'       => __( 'Gift Shopper', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer purchases frequently during gift-giving seasons with diverse product choices.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Send gift guides before holidays and promote gift wrapping and bundles.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 5,
				'icon'        => 'dashicons-gifts',
			),
			'weekend_shopper' => array(
				'title'       => __( 'Weekend Shopper', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer primarily shops on weekends (Saturday and Sunday).', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Schedule promotional emails and campaigns for Friday evening or Saturday morning.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 4,
				'icon'        => 'dashicons-calendar-alt',
			),
			'night_owl' => array(
				'title'       => __( 'Night Owl', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer is most active during late night and early morning hours.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Schedule marketing emails and push notifications for evening hours (8-10 PM).', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 3,
				'icon'        => 'dashicons-visibility',
			),
			'cart_abandoner' => array(
				'title'       => __( 'Cart Abandoner', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer frequently adds items to cart but does not complete the purchase.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Send cart recovery emails with incentives like free shipping or small discounts.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 10,
				'icon'        => 'dashicons-cart',
			),
			'researcher' => array(
				'title'       => __( 'Researcher', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer visits the site multiple times and researches extensively before purchasing.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Provide detailed product comparisons, reviews, and buying guides to support their decision.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 5,
				'icon'        => 'dashicons-search',
			),
			'subscription_seeker' => array(
				'title'       => __( 'Subscription Seeker', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer shows interest in subscription-based products and recurring purchases.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Promote subscription plans with discounts on recurring orders and highlight convenience.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 6,
				'icon'        => 'dashicons-update',
			),
			'category_specialist' => array(
				'title'       => __( 'Category Specialist', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer focuses their purchases within a specific product category.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Cross-sell related products within their preferred category and notify about new arrivals.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 5,
				'icon'        => 'dashicons-category',
			),
			'bulk_buyer' => array(
				'title'       => __( 'Bulk Buyer', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer tends to buy large quantities or many items per order.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Offer volume discounts, wholesale pricing, and bundle deals.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 7,
				'icon'        => 'dashicons-archive',
			),
			'trendsetter' => array(
				'title'       => __( 'Trendsetter', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer is an early adopter who buys newly released products quickly.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Send early access or pre-order notifications for upcoming product launches.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 8,
				'icon'        => 'dashicons-star-filled',
			),
			'seasonal_shopper' => array(
				'title'       => __( 'Seasonal Shopper', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer concentrates their purchases in specific seasonal periods.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Run targeted re-engagement campaigns before their peak shopping season begins.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 4,
				'icon'        => 'dashicons-calendar',
			),
			'mobile_shopper' => array(
				'title'       => __( 'Mobile Shopper', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer primarily browses and shops using a mobile device.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Ensure mobile-optimized experience, quick checkout, and mobile-specific promotions.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 3,
				'icon'        => 'dashicons-smartphone',
			),
			'desktop_shopper' => array(
				'title'       => __( 'Desktop Shopper', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'This customer primarily browses and shops using a desktop computer.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Optimize desktop experience with detailed product views and comparison features.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 2,
				'icon'        => 'dashicons-desktop',
			),
		);
	}

	/**
	 * Generate all insights for a customer based on their behavioral tags
	 *
	 * @since    3.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array              Array of insight objects
	 */
	public function generate_insights( $user_id ) {
		$behavioral = new PD_Customer_History_Behavioral();
		$tags       = $behavioral->get_customer_tags( $user_id );
		$insights   = array();

		foreach ( $tags as $tag ) {
			$insight = $this->get_behavior_insight( $tag );
			if ( $insight ) {
				$insights[] = $insight;
			}
		}

		// Sort by priority (highest first)
		usort( $insights, function( $a, $b ) {
			return $b['priority'] - $a['priority'];
		} );

		return $insights;
	}

	/**
	 * Get the single most important next best action for a customer
	 *
	 * @since    3.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array|null         Next best action data
	 */
	public function get_next_best_action( $user_id ) {
		$behavioral = new PD_Customer_History_Behavioral();
		$tags       = $behavioral->get_customer_tags( $user_id );

		if ( empty( $tags ) ) {
			return array(
				'title'       => __( 'Analyze Customer', 'puredevs-customer-history-for-woocommerce' ),
				'description' => __( 'Not enough data to generate behavioral insights. Run a behavioral analysis first.', 'puredevs-customer-history-for-woocommerce' ),
				'action'      => __( 'Click "Refresh Analysis" to analyze this customer\'s behavior patterns.', 'puredevs-customer-history-for-woocommerce' ),
				'priority'    => 0,
				'icon'        => 'dashicons-analytics',
			);
		}

		// Priority logic: Cart abandoner gets highest priority
		if ( $this->has_behavior( $tags, 'cart_abandoner' ) ) {
			$action = $this->insights_map['cart_abandoner'];
			$action['title'] = __( 'Send Cart Recovery Email', 'puredevs-customer-history-for-woocommerce' );
			return $action;
		}

		// Impulse buyer + active = urgency campaign
		if ( $this->has_behavior( $tags, 'impulse_buyer' ) ) {
			$action = $this->insights_map['impulse_buyer'];
			$action['title'] = __( 'Launch Urgency Campaign', 'puredevs-customer-history-for-woocommerce' );
			return $action;
		}

		// Gift shopper during gift season
		if ( $this->has_behavior( $tags, 'gift_shopper' ) && $this->is_gift_season() ) {
			$action = $this->insights_map['gift_shopper'];
			$action['title'] = __( 'Send Gift Guide', 'puredevs-customer-history-for-woocommerce' );
			return $action;
		}

		// Brand loyal - new arrivals
		if ( $this->has_behavior( $tags, 'brand_loyal' ) ) {
			$brand = $this->get_primary_brand( $user_id );
			$action = $this->insights_map['brand_loyal'];
			if ( $brand ) {
				$action['action'] = sprintf(
					__( 'Notify about new arrivals from %s and offer brand loyalty reward.', 'puredevs-customer-history-for-woocommerce' ),
					$brand
				);
			}
			$action['title'] = __( 'Promote Preferred Brand', 'puredevs-customer-history-for-woocommerce' );
			return $action;
		}

		// Bargain hunter
		if ( $this->has_behavior( $tags, 'bargain_hunter' ) ) {
			$action = $this->insights_map['bargain_hunter'];
			$action['title'] = __( 'Send Exclusive Discount', 'puredevs-customer-history-for-woocommerce' );
			return $action;
		}

		// Fall back to highest priority insight
		$insights = $this->generate_insights( $user_id );
		return ! empty( $insights ) ? $insights[0] : null;
	}

	/**
	 * Get insight data for a specific behavioral tag
	 *
	 * @since    3.0.0
	 * @param    object    $tag    Behavioral tag object
	 * @return   array|null        Insight data
	 */
	public function get_behavior_insight( $tag ) {
		$type = $tag->behavior_type;
		if ( ! isset( $this->insights_map[ $type ] ) ) {
			return null;
		}

		$insight               = $this->insights_map[ $type ];
		$insight['type']       = $type;
		$insight['confidence'] = intval( $tag->confidence_score );

		return $insight;
	}

	/**
	 * Check if customer has a specific behavior in their tags
	 *
	 * @since    3.0.0
	 * @param    array     $tags   Array of tag objects
	 * @param    string    $type   Behavior type to check
	 * @return   bool
	 */
	public function has_behavior( $tags, $type ) {
		foreach ( $tags as $tag ) {
			if ( $tag->behavior_type === $type ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Check if current date is near a gift-giving holiday
	 *
	 * @since    3.0.0
	 * @return   bool
	 */
	public function is_gift_season() {
		$month = intval( current_time( 'm' ) );
		$gift_months = array( 2, 5, 6, 11, 12 ); // Feb, May, Jun, Nov, Dec
		return in_array( $month, $gift_months, true );
	}

	/**
	 * Get top brand from behavioral metadata
	 *
	 * @since    3.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   string|null
	 */
	public function get_primary_brand( $user_id ) {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_behavioral_tags';
		$tag   = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT metadata FROM {$table} WHERE user_id = %d AND behavior_type = 'brand_loyal' AND is_active = 1",
				$user_id
			)
		);

		if ( $tag && $tag->metadata ) {
			$metadata = json_decode( $tag->metadata, true );
			return isset( $metadata['top_brand'] ) ? $metadata['top_brand'] : null;
		}

		return null;
	}

	/**
	 * Fetch combined customer intelligence data
	 *
	 * @since    3.0.0
	 * @param    int    $user_id    WordPress user ID
	 * @return   array
	 */
	public function get_customer_intelligence( $user_id ) {
		global $wpdb;

		$data = array(
			'user_id'          => $user_id,
			'behavioral_tags'  => array(),
			'rfm_segment'      => null,
			'health_score'     => null,
			'lifecycle_stage'  => null,
		);

		// Get behavioral data
		$behavioral = new PD_Customer_History_Behavioral();
		$data['behavioral_tags'] = $behavioral->get_customer_tags( $user_id );

		// Get intelligence data (RFM, Health, Lifecycle)
		$intel_table = $wpdb->prefix . 'pdwchs_customer_intelligence';
		$intel = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT rfm_segment, health_score, lifecycle_stage FROM {$intel_table} WHERE user_id = %d",
				$user_id
			)
		);

		if ( $intel ) {
			$data['rfm_segment']     = $intel->rfm_segment;
			$data['health_score']    = $intel->health_score;
			$data['lifecycle_stage'] = $intel->lifecycle_stage;
		}

		return $data;
	}
}
