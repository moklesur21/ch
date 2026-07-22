<?php
/**
 * Customer Health Score Card
 *
 * Displays health score gauge and component breakdown on customer profile
 *
 * @link       https://puredevs.com/
 * @since      2.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/admin/partials/templates
 */

defined( 'ABSPATH' ) or exit;

// Only show if premium
if ( ! pchfwv_fs()->can_use_premium_code() ) {
	return;
}

// Get user ID
$user_id = isset( $_GET['user_id'] ) ? intval( $_GET['user_id'] ) : 0;

if ( ! $user_id ) {
	return;
}

// Get intelligence coordinator instance
global $pd_customer_history;
if ( ! isset( $pd_customer_history ) || ! method_exists( $pd_customer_history, 'pdwchs_get_intelligence' ) ) {
	return;
}

$intelligence = $pd_customer_history->pdwchs_get_intelligence();
if ( ! $intelligence ) {
	return;
}

$health_calculator = $intelligence->pdwchs_get_health_calculator();
if ( ! $health_calculator ) {
	return;
}

// Get health data
$health_data = $health_calculator->pdwchs_get_customer_health( $user_id );

// If no data, try to calculate
if ( ! $health_data || empty( $health_data['health_score'] ) ) {
	$health_full_data = $health_calculator->pdwchs_calculate_customer_health( $user_id );
	$health_data = $health_full_data;
}

// Get health status
$score = isset( $health_data['health_score'] ) ? intval( $health_data['health_score'] ) : 0;
$status = $health_calculator->pdwchs_get_health_status( $score );

// Get trend
$trend = isset( $health_data['health_trend'] ) ? $health_data['health_trend'] : 'stable';
$previous_score = isset( $health_data['health_previous_score'] ) ? intval( $health_data['health_previous_score'] ) : null;
$trend_diff = $previous_score ? ( $score - $previous_score ) : 0;

// Trend display
$trend_icon = '→';
$trend_class = 'stable';
$trend_text = __( 'Stable', 'puredevs-customer-history-for-woocommerce' );

if ( $trend === 'up' ) {
	$trend_icon = '↗';
	$trend_class = 'up';
	$trend_text = sprintf( __( 'Up %d points', 'puredevs-customer-history-for-woocommerce' ), $trend_diff );
} elseif ( $trend === 'down' ) {
	$trend_icon = '↘';
	$trend_class = 'down';
	$trend_text = sprintf( __( 'Down %d points', 'puredevs-customer-history-for-woocommerce' ), abs( $trend_diff ) );
}

// Get component scores
$recent_activity = isset( $health_data['health_recent_activity'] ) ? intval( $health_data['health_recent_activity'] ) : 0;
$frequency = isset( $health_data['health_frequency'] ) ? intval( $health_data['health_frequency'] ) : 0;
$spending_trend = isset( $health_data['health_spending_trend'] ) ? intval( $health_data['health_spending_trend'] ) : 0;
$engagement = isset( $health_data['health_engagement'] ) ? intval( $health_data['health_engagement'] ) : 0;

// Calculate percentages
$recent_activity_percent = ( $recent_activity / 40 ) * 100;
$frequency_percent = ( $frequency / 30 ) * 100;
$spending_trend_percent = ( $spending_trend / 20 ) * 100;
$engagement_percent = ( $engagement / 10 ) * 100;

// Recalculate to get full component data
$full_health_data = $health_calculator->pdwchs_calculate_customer_health( $user_id );
$components_data = isset( $full_health_data['components_data'] ) ? $full_health_data['components_data'] : array();

// Get component descriptions
$recent_activity_desc = isset( $components_data['recent_activity'] )
	? $health_calculator->pdwchs_get_component_description( 'recent_activity', $recent_activity, $components_data['recent_activity'] )
	: '';

$frequency_desc = isset( $components_data['frequency'] )
	? $health_calculator->pdwchs_get_component_description( 'frequency', $frequency, $components_data['frequency'] )
	: '';

$spending_trend_desc = isset( $components_data['spending_trend'] )
	? $health_calculator->pdwchs_get_component_description( 'spending_trend', $spending_trend, $components_data['spending_trend'] )
	: '';

$engagement_desc = isset( $components_data['engagement'] )
	? $health_calculator->pdwchs_get_component_description( 'engagement', $engagement, $components_data['engagement'] )
	: '';

// Last calculated time
$calculated_at = isset( $health_data['health_calculated_at'] ) ? $health_data['health_calculated_at'] : '';
$time_ago = '';
if ( $calculated_at ) {
	$time_ago = human_time_diff( strtotime( $calculated_at ), current_time( 'timestamp' ) );
}
?>

<div class="pdwchs-health-score-card pdwchs-widget">
	<div class="pdwchs-widget-header">
		<h3><?php _e( 'Customer Health Score', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
		<?php if ( $trend !== 'stable' ) : ?>
			<span class="pdwchs-health-trend pdwchs-trend-<?php echo esc_attr( $trend_class ); ?>">
				<?php echo esc_html( $trend_icon . ' ' . $trend_text ); ?>
			</span>
		<?php endif; ?>
	</div>

	<div class="pdwchs-widget-body">
		<!-- Circular Gauge -->
		<div class="pdwchs-health-gauge-container">
			<svg class="pdwchs-health-gauge" width="200" height="200" viewBox="0 0 200 200">
				<circle class="pdwchs-gauge-bg" cx="100" cy="100" r="80"
				        fill="none" stroke="#e5e7eb" stroke-width="12"/>
				<circle class="pdwchs-gauge-fill" cx="100" cy="100" r="80"
				        fill="none" stroke="<?php echo esc_attr( $status['color'] ); ?>" stroke-width="12"
				        stroke-dasharray="502.65"
				        stroke-dashoffset="<?php echo 502.65 - ( ( $score / 100 ) * 502.65 ); ?>"
				        transform="rotate(-90 100 100)"
				        stroke-linecap="round"/>
				<text x="100" y="90" class="pdwchs-gauge-score"
				      text-anchor="middle" dominant-baseline="middle"
				      font-size="42" font-weight="bold" fill="#1e293b">
					<?php echo esc_html( $score ); ?>
				</text>
				<text x="100" y="120" class="pdwchs-gauge-status"
				      text-anchor="middle" dominant-baseline="middle"
				      font-size="16" fill="<?php echo esc_attr( $status['color'] ); ?>">
					<?php echo esc_html( $status['status'] ); ?>
				</text>
			</svg>
		</div>

		<!-- Score Breakdown -->
		<div class="pdwchs-health-breakdown">
			<h4><?php _e( 'Score Breakdown:', 'puredevs-customer-history-for-woocommerce' ); ?></h4>

			<!-- Recent Activity -->
			<div class="pdwchs-component-bar">
				<div class="pdwchs-component-label">
					<span><?php _e( 'Recent Activity', 'puredevs-customer-history-for-woocommerce' ); ?></span>
					<span><strong><?php echo esc_html( $recent_activity ); ?>/40</strong> (<?php echo esc_html( number_format( $recent_activity_percent, 0 ) ); ?>%)</span>
				</div>
				<div class="pdwchs-component-progress">
					<div class="pdwchs-component-fill" style="width: <?php echo esc_attr( $recent_activity_percent ); ?>%; background-color: <?php echo esc_attr( $status['color'] ); ?>;"></div>
				</div>
				<div class="pdwchs-component-description">
					<?php echo esc_html( $recent_activity_desc ); ?>
				</div>
			</div>

			<!-- Order Frequency -->
			<div class="pdwchs-component-bar">
				<div class="pdwchs-component-label">
					<span><?php _e( 'Order Frequency', 'puredevs-customer-history-for-woocommerce' ); ?></span>
					<span><strong><?php echo esc_html( $frequency ); ?>/30</strong> (<?php echo esc_html( number_format( $frequency_percent, 0 ) ); ?>%)</span>
				</div>
				<div class="pdwchs-component-progress">
					<div class="pdwchs-component-fill" style="width: <?php echo esc_attr( $frequency_percent ); ?>%; background-color: <?php echo esc_attr( $status['color'] ); ?>;"></div>
				</div>
				<div class="pdwchs-component-description">
					<?php echo esc_html( $frequency_desc ); ?>
				</div>
			</div>

			<!-- Spending Trend -->
			<div class="pdwchs-component-bar">
				<div class="pdwchs-component-label">
					<span><?php _e( 'Spending Trend', 'puredevs-customer-history-for-woocommerce' ); ?></span>
					<span><strong><?php echo esc_html( $spending_trend ); ?>/20</strong> (<?php echo esc_html( number_format( $spending_trend_percent, 0 ) ); ?>%)</span>
				</div>
				<div class="pdwchs-component-progress">
					<div class="pdwchs-component-fill" style="width: <?php echo esc_attr( $spending_trend_percent ); ?>%; background-color: <?php echo esc_attr( $status['color'] ); ?>;"></div>
				</div>
				<div class="pdwchs-component-description">
					<?php echo esc_html( $spending_trend_desc ); ?>
				</div>
			</div>

			<!-- Engagement -->
			<div class="pdwchs-component-bar">
				<div class="pdwchs-component-label">
					<span><?php _e( 'Engagement', 'puredevs-customer-history-for-woocommerce' ); ?></span>
					<span><strong><?php echo esc_html( $engagement ); ?>/10</strong> (<?php echo esc_html( number_format( $engagement_percent, 0 ) ); ?>%)</span>
				</div>
				<div class="pdwchs-component-progress">
					<div class="pdwchs-component-fill" style="width: <?php echo esc_attr( $engagement_percent ); ?>%; background-color: <?php echo esc_attr( $status['color'] ); ?>;"></div>
				</div>
				<div class="pdwchs-component-description">
					<?php echo esc_html( $engagement_desc ); ?>
				</div>
			</div>
		</div>

		<!-- Health Interpretation -->
		<div class="pdwchs-health-interpretation">
			<h4>
				<span class="dashicons dashicons-lightbulb"></span>
				<?php _e( 'Health Interpretation:', 'puredevs-customer-history-for-woocommerce' ); ?>
			</h4>
			<p><strong><?php echo esc_html( $status['description'] ); ?></strong></p>
			<p><em><?php echo esc_html( $status['action'] ); ?></em></p>
		</div>

		<!-- Last Calculated & Recalculate Button -->
		<div class="pdwchs-health-footer">
			<?php if ( $time_ago ) : ?>
				<p class="pdwchs-last-calculated">
					<?php
					printf(
						__( 'Last calculated: %s ago', 'puredevs-customer-history-for-woocommerce' ),
						esc_html( $time_ago )
					);
					?>
				</p>
			<?php endif; ?>
			<button type="button" class="button button-primary pdwchs-recalculate-health" data-user-id="<?php echo esc_attr( $user_id ); ?>">
				<span class="dashicons dashicons-update"></span>
				<?php _e( 'Recalculate Now', 'puredevs-customer-history-for-woocommerce' ); ?>
			</button>
		</div>
	</div>
</div>
