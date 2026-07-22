<?php
/**
 * Customer Health Score Dashboard Widgets
 *
 * Displays health distribution and declining health alert widgets
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
?>

<!-- Health Distribution Widget -->
<div class="pdwchs-health-distribution-widget pdwchs-widget pdwchs-dashboard-widget">
	<div class="pdwchs-widget-header">
		<h3><?php _e( 'Customer Health Distribution', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
		<button class="pdwchs-refresh-health" title="<?php esc_attr_e( 'Refresh', 'puredevs-customer-history-for-woocommerce' ); ?>">
			<span class="dashicons dashicons-update"></span>
		</button>
	</div>

	<div class="pdwchs-widget-body">
		<div class="pdwchs-health-average">
			<strong><?php _e( 'Average Score:', 'puredevs-customer-history-for-woocommerce' ); ?></strong>
			<span class="pdwchs-avg-score">--</span>
		</div>

		<div class="pdwchs-health-bars" id="pdwchs-health-distribution-chart">
			<div class="pdwchs-loading">
				<span class="spinner is-active"></span>
				<p><?php _e( 'Loading health data...', 'puredevs-customer-history-for-woocommerce' ); ?></p>
			</div>
		</div>

		<div class="pdwchs-health-critical-alert" style="display:none;">
			<div class="pdwchs-alert pdwchs-alert-warning">
				<span class="dashicons dashicons-warning"></span>
				<span class="pdwchs-critical-message"></span>
			</div>
		</div>
	</div>
</div>

<!-- Declining Health Alert Widget -->
<div class="pdwchs-declining-health-widget pdwchs-widget pdwchs-dashboard-widget">
	<div class="pdwchs-widget-header">
		<h3>
			<span class="dashicons dashicons-warning" style="color: #dc3232;"></span>
			<?php _e( 'Declining Health Alert', 'puredevs-customer-history-for-woocommerce' ); ?>
		</h3>
		<p class="pdwchs-widget-subtitle">
			<?php _e( 'Customers with 10+ point health score drop', 'puredevs-customer-history-for-woocommerce' ); ?>
		</p>
	</div>

	<div class="pdwchs-widget-body">
		<div id="pdwchs-declining-health-list">
			<div class="pdwchs-loading">
				<span class="spinner is-active"></span>
				<p><?php _e( 'Loading declining customers...', 'puredevs-customer-history-for-woocommerce' ); ?></p>
			</div>
		</div>
	</div>
</div>
