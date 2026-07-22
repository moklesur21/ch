<?php
/**
 * Churn Prediction Dashboard Widget
 *
 * @link       https://puredevs.com/
 * @since      4.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/admin/partials/templates
 */

defined( 'ABSPATH' ) or exit;

// Get churn calculator instance
$intelligence = null;
if ( function_exists( 'pdwchs_get_plugin_instance' ) ) {
	$plugin = pdwchs_get_plugin_instance();
	if ( $plugin && method_exists( $plugin, 'pdwchs_get_intelligence' ) ) {
		$intelligence = $plugin->pdwchs_get_intelligence();
	}
}

$churn_calculator = null;
$alerts_manager = null;

if ( $intelligence && method_exists( $intelligence, 'pdwchs_get_churn_calculator' ) ) {
	$churn_calculator = $intelligence->pdwchs_get_churn_calculator();
}
if ( $intelligence && method_exists( $intelligence, 'pdwchs_get_alerts_manager' ) ) {
	$alerts_manager = $intelligence->pdwchs_get_alerts_manager();
}

// Get statistics
$distribution = $churn_calculator ? $churn_calculator->pdwchs_get_risk_distribution() : array(
	'low'      => 0,
	'medium'   => 0,
	'high'     => 0,
	'critical' => 0,
	'total'    => 0,
);

$high_risk_customers = $churn_calculator ? $churn_calculator->pdwchs_get_high_risk_customers( 5 ) : array();
$active_alerts = $alerts_manager ? $alerts_manager->pdwchs_get_active_alerts( 5 ) : array();
?>

<div class="pdwchs-churn-dashboard-widget">
	<div class="pdwchs-churn-widget-header">
		<h3><?php esc_html_e( 'Churn Risk Overview', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
		<button type="button" class="pdwchs-refresh-churn" title="<?php esc_attr_e( 'Refresh', 'puredevs-customer-history-for-woocommerce' ); ?>">
			<span class="dashicons dashicons-update"></span>
		</button>
	</div>

	<!-- Statistics Grid -->
	<div class="pdwchs-churn-stats-grid">
		<div class="pdwchs-churn-stat-box critical">
			<span class="pdwchs-stat-number"><?php echo esc_html( $distribution['critical'] ); ?></span>
			<span class="pdwchs-stat-label"><?php esc_html_e( 'Critical', 'puredevs-customer-history-for-woocommerce' ); ?></span>
		</div>
		<div class="pdwchs-churn-stat-box high">
			<span class="pdwchs-stat-number"><?php echo esc_html( $distribution['high'] ); ?></span>
			<span class="pdwchs-stat-label"><?php esc_html_e( 'High Risk', 'puredevs-customer-history-for-woocommerce' ); ?></span>
		</div>
		<div class="pdwchs-churn-stat-box medium">
			<span class="pdwchs-stat-number"><?php echo esc_html( $distribution['medium'] ); ?></span>
			<span class="pdwchs-stat-label"><?php esc_html_e( 'Medium', 'puredevs-customer-history-for-woocommerce' ); ?></span>
		</div>
		<div class="pdwchs-churn-stat-box alerts">
			<span class="pdwchs-stat-number"><?php echo esc_html( count( $active_alerts ) ); ?></span>
			<span class="pdwchs-stat-label"><?php esc_html_e( 'Active Alerts', 'puredevs-customer-history-for-woocommerce' ); ?></span>
		</div>
	</div>

	<?php if ( ! empty( $high_risk_customers ) ) : ?>
	<!-- Top At-Risk Customers Table -->
	<div class="pdwchs-churn-at-risk-section">
		<h4><?php esc_html_e( 'Top At-Risk Customers', 'puredevs-customer-history-for-woocommerce' ); ?></h4>
		<table class="pdwchs-churn-customers-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Customer', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Risk', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Value', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Action', 'puredevs-customer-history-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $high_risk_customers as $customer ) :
					$customer_url = admin_url( 'admin.php?page=pd-woo-customer&user_id=' . $customer->customer_id );
				?>
				<tr>
					<td>
						<a href="<?php echo esc_url( $customer_url ); ?>">
							<?php echo esc_html( $customer->display_name ?: __( 'Guest', 'puredevs-customer-history-for-woocommerce' ) ); ?>
						</a>
					</td>
					<td>
						<span class="pdwchs-risk-badge pdwchs-risk-<?php echo esc_attr( $customer->risk_level ); ?>">
							<?php echo esc_html( $customer->churn_score ); ?>%
						</span>
					</td>
					<td><?php echo wc_price( $customer->total_spent ); ?></td>
					<td>
						<a href="<?php echo esc_url( $customer_url ); ?>" class="pdwchs-action-btn" title="<?php esc_attr_e( 'View Profile', 'puredevs-customer-history-for-woocommerce' ); ?>">
							<span class="dashicons dashicons-visibility"></span>
						</a>
					</td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</div>
	<?php else : ?>
	<div class="pdwchs-churn-no-data">
		<span class="dashicons dashicons-shield"></span>
		<p><?php esc_html_e( 'No high-risk customers detected. Your customers are healthy!', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>
	<?php endif; ?>

	<?php if ( ! empty( $active_alerts ) ) : ?>
	<!-- Active Alerts Section -->
	<div class="pdwchs-churn-alerts-section">
		<h4><?php esc_html_e( 'Active Alerts', 'puredevs-customer-history-for-woocommerce' ); ?></h4>
		<ul class="pdwchs-alerts-list">
			<?php foreach ( array_slice( $active_alerts, 0, 3 ) as $alert ) : ?>
			<li class="pdwchs-alert-item pdwchs-alert-<?php echo esc_attr( $alert->risk_level ); ?>">
				<span class="pdwchs-alert-priority">P<?php echo esc_html( $alert->priority ); ?></span>
				<span class="pdwchs-alert-title"><?php echo esc_html( $alert->title ); ?></span>
				<span class="pdwchs-alert-time"><?php echo esc_html( human_time_diff( strtotime( $alert->created_at ) ) ); ?></span>
			</li>
			<?php endforeach; ?>
		</ul>
	</div>
	<?php endif; ?>

	<div class="pdwchs-churn-widget-footer">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-churn-prediction' ) ); ?>" class="pdwchs-link">
			<?php esc_html_e( 'View All At-Risk Customers', 'puredevs-customer-history-for-woocommerce' ); ?>
			<span class="dashicons dashicons-arrow-right-alt"></span>
		</a>
	</div>
</div>
