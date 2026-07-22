<?php
/**
 * RFM Dashboard Widget Template
 *
 * Displays customer segments distribution chart and statistics.
 *
 * @link       https://puredevs.com/
 * @since      2.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/admin/partials/templates
 */

defined( 'ABSPATH' ) or exit;
?>

<div class="pd-woo-panel pdwchs-rfm-widget">
	<div class="pd-woo-panel-header" style="display: flex; justify-content: space-between; align-items: center;">
		<h2><?php echo esc_html__( 'Customer Segments (RFM Analysis)', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
		<button type="button" class="pdwchs-refresh-rfm button" title="<?php echo esc_attr__( 'Refresh Data', 'puredevs-customer-history-for-woocommerce' ); ?>">
			<span class="dashicons dashicons-update"></span>
			<?php echo esc_html__( 'Refresh', 'puredevs-customer-history-for-woocommerce' ); ?>
		</button>
	</div>

	<div class="pd-woo-panel-body">
		<div class="pdwchs-rfm-loading">
			<span class="spinner is-active"></span>
			<p><?php echo esc_html__( 'Loading segment data...', 'puredevs-customer-history-for-woocommerce' ); ?></p>
		</div>

		<div class="pdwchs-rfm-content" style="display: none;">
			<div class="pdwchs-rfm-chart-container">
				<canvas id="pdwchs-rfm-chart"></canvas>
			</div>

			<div class="pdwchs-rfm-legend">
				<h3><?php echo esc_html__( 'Segments', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
				<ul id="pdwchs-rfm-legend-list"></ul>
			</div>
		</div>

		<div class="pdwchs-rfm-error" style="display: none;">
			<p class="error"><?php echo esc_html__( 'Failed to load RFM data. Please try again.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
		</div>
	</div>

	<div class="pd-woo-panel-footer pdwchs-rfm-footer" style="display: none;">
		<p class="pdwchs-rfm-timestamp">
			<?php echo esc_html__( 'Last updated:', 'puredevs-customer-history-for-woocommerce' ); ?>
			<span id="pdwchs-rfm-last-updated">--</span>
		</p>
		<p class="pdwchs-rfm-help">
			<?php echo esc_html__( 'Click any segment to view customers', 'puredevs-customer-history-for-woocommerce' ); ?>
		</p>
	</div>
</div>
