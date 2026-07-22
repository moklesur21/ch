<?php
/**
 * Customer Segments Configuration
 *
 * Defines RFM segment properties including colors, icons, names, and recommendations.
 *
 * @link       https://puredevs.com/
 * @since      2.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

defined( 'ABSPATH' ) or exit;

/**
 * Customer Segments Configuration Class
 *
 * Provides segment definitions and utilities for RFM analysis.
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Segments {

	/**
	 * Get all segment definitions
	 *
	 * @since    2.0.0
	 * @return   array    Array of segment configurations
	 */
	public static function pdwchs_get_segments() {
		return array(
			'champions' => array(
				'name'            => __( 'Champions', 'puredevs-customer-history-for-woocommerce' ),
				'slug'            => 'champions',
				'color'           => '#10b981',
				'icon'            => '⭐',
				'priority'        => 'High',
				'description'     => __( 'Your best customers - recent, frequent, high-value buyers', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Reward with exclusive perks and VIP treatment', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Request product reviews and testimonials', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Ask for referrals to similar customers', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Provide early access to new products', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'loyal' => array(
				'name'            => __( 'Loyal', 'puredevs-customer-history-for-woocommerce' ),
				'slug'            => 'loyal',
				'color'           => '#3b82f6',
				'icon'            => '💙',
				'priority'        => 'High',
				'description'     => __( 'Frequent buyers who are consistently engaged', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Upsell to premium products', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Offer loyalty program benefits', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Provide bundle deals and volume discounts', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Encourage brand advocacy', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'potential_loyalist' => array(
				'name'            => __( 'Potential Loyalist', 'puredevs-customer-history-for-woocommerce' ),
				'slug'            => 'potential_loyalist',
				'color'           => '#8b5cf6',
				'icon'            => '🌟',
				'priority'        => 'Medium',
				'description'     => __( 'Recent buyers showing promise for loyalty', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Offer membership programs', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Provide special discounts to encourage frequency', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Send personalized product recommendations', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Build relationship through engagement', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'new_customer' => array(
				'name'            => __( 'New Customer', 'puredevs-customer-history-for-woocommerce' ),
				'slug'            => 'new_customer',
				'color'           => '#06b6d4',
				'icon'            => '🆕',
				'priority'        => 'Medium',
				'description'     => __( 'First-time buyers in critical retention window', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Send onboarding email sequence', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Offer first repeat purchase discount', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Provide welcome package or bonus', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Deliver excellent customer service', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'promising' => array(
				'name'            => __( 'Promising', 'puredevs-customer-history-for-woocommerce' ),
				'slug'            => 'promising',
				'color'           => '#14b8a6',
				'icon'            => '💎',
				'priority'        => 'Medium',
				'description'     => __( 'Recent big spenders with high potential value', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Showcase premium product range', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Encourage second purchase quickly', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Build premium relationship', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Offer exclusive high-value deals', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'needs_attention' => array(
				'name'            => __( 'Needs Attention', 'puredevs-customer-history-for-woocommerce' ),
				'slug'            => 'needs_attention',
				'color'           => '#f59e0b',
				'icon'            => '⚠️',
				'priority'        => 'Medium',
				'description'     => __( 'Average customers showing signs of decline', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Launch re-engagement campaigns', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Send limited time offers', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Remind of product benefits', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Reach out with "We miss you" message', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'at_risk' => array(
				'name'            => __( 'At Risk', 'puredevs-customer-history-for-woocommerce' ),
				'slug'            => 'at_risk',
				'color'           => '#f97316',
				'icon'            => '🚨',
				'priority'        => 'High',
				'description'     => __( 'Previously good customers who are slipping away', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Execute win-back email campaign', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Offer personalized special discounts', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Survey to understand concerns', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Provide aggressive incentives to return', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'cannot_lose' => array(
				'name'            => __( 'Cannot Lose', 'puredevs-customer-history-for-woocommerce' ),
				'slug'            => 'cannot_lose',
				'color'           => '#ef4444',
				'icon'            => '🆘',
				'priority'        => 'Critical',
				'description'     => __( 'High-value customers at risk of churning', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'URGENT: Make personal outreach (phone/email)', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Offer significant discount or incentive', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Understand what went wrong', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Implement VIP recovery program', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'hibernating' => array(
				'name'            => __( 'Hibernating', 'puredevs-customer-history-for-woocommerce' ),
				'slug'            => 'hibernating',
				'color'           => '#64748b',
				'icon'            => '😴',
				'priority'        => 'Low',
				'description'     => __( 'Long inactive customers with low historical value', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Use low-cost re-engagement tactics', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Send deep discount offers', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Consider removing from active marketing', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Minimize investment in this segment', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'lost' => array(
				'name'            => __( 'Lost', 'puredevs-customer-history-for-woocommerce' ),
				'slug'            => 'lost',
				'color'           => '#94a3b8',
				'icon'            => '💔',
				'priority'        => 'Very Low',
				'description'     => __( 'Extremely inactive customers with very low recovery chance', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Send last-chance aggressive discount', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Or ignore completely to save costs', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Remove from expensive marketing campaigns', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Clean up from active email lists', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
			'undefined' => array(
				'name'            => __( 'Undefined', 'puredevs-customer-history-for-woocommerce' ),
				'slug'            => 'undefined',
				'color'           => '#9ca3af',
				'icon'            => '❓',
				'priority'        => 'Low',
				'description'     => __( 'Customers who do not fit any standard segment criteria', 'puredevs-customer-history-for-woocommerce' ),
				'recommendations' => array(
					__( 'Review customer data for anomalies', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Monitor for emerging patterns', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Consider manual categorization', 'puredevs-customer-history-for-woocommerce' ),
					__( 'Use low-cost engagement tactics', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
		);
	}

	/**
	 * Get segment configuration by slug
	 *
	 * @since    2.0.0
	 * @param    string    $segment    Segment slug
	 * @return   array|null            Segment configuration or null
	 */
	public static function pdwchs_get_segment( $segment ) {
		$segments = self::pdwchs_get_segments();
		return isset( $segments[ $segment ] ) ? $segments[ $segment ] : null;
	}

	/**
	 * Get badge HTML for segment
	 *
	 * @since    2.0.0
	 * @param    string    $segment    Segment slug
	 * @param    string    $size       Badge size (small, medium, large)
	 * @return   string                Badge HTML
	 */
	public static function pdwchs_get_segment_badge( $segment, $size = 'medium' ) {
		$config = self::pdwchs_get_segment( $segment );

		if ( ! $config ) {
			return sprintf(
				'<span class="pdwchs-badge pdwchs-badge-undefined pdwchs-badge-%s">%s</span>',
				esc_attr( $size ),
				esc_html__( 'Unknown', 'puredevs-customer-history-for-woocommerce' )
			);
		}

		return sprintf(
			'<span class="pdwchs-badge pdwchs-badge-%s pdwchs-badge-%s" style="background-color: %s;">
				<span class="pdwchs-badge-icon">%s</span>
				<span class="pdwchs-badge-text">%s</span>
			</span>',
			esc_attr( $segment ),
			esc_attr( $size ),
			esc_attr( $config['color'] ),
			esc_html( $config['icon'] ),
			esc_html( $config['name'] )
		);
	}

	/**
	 * Get recommendations for segment
	 *
	 * @since    2.0.0
	 * @param    string    $segment    Segment slug
	 * @return   array                 Array of recommendations
	 */
	public static function pdwchs_get_recommendations( $segment ) {
		$config = self::pdwchs_get_segment( $segment );
		return $config ? $config['recommendations'] : array();
	}

	/**
	 * Get priority label for segment
	 *
	 * @since    2.0.0
	 * @param    string    $segment    Segment slug
	 * @return   string                Priority label
	 */
	public static function pdwchs_get_priority( $segment ) {
		$config = self::pdwchs_get_segment( $segment );
		return $config ? $config['priority'] : 'Low';
	}

	/**
	 * Get segment color
	 *
	 * @since    2.0.0
	 * @param    string    $segment    Segment slug
	 * @return   string                Color hex code
	 */
	public static function pdwchs_get_color( $segment ) {
		$config = self::pdwchs_get_segment( $segment );
		return $config ? $config['color'] : '#94a3b8';
	}
}
