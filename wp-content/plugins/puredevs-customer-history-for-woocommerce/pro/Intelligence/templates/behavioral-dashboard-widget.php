<?php
/**
 * Behavioral Segmentation Dashboard Widget
 *
 * Displays behavioral segment distribution and actionable insights
 * on the plugin dashboard.
 *
 * @link       https://puredevs.com/
 * @since      3.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/admin/partials/templates
 */

defined( 'ABSPATH' ) or exit;

if ( ! pchfwv_fs()->can_use_premium_code() ) {
	return;
}
?>

<!-- Behavioral Segments Distribution Widget -->
<div class="pdwchs-behavioral-distribution-widget pdwchs-widget pdwchs-dashboard-widget">
	<div class="pdwchs-widget-header">
		<div>
			<h3>
				<span class="dashicons dashicons-groups"></span>
				<?php echo esc_html__( 'Behavioral Segments', 'puredevs-customer-history-for-woocommerce' ); ?>
			</h3>
			<p class="pdwchs-widget-subtitle">
				<?php echo esc_html__( 'Customer behavior patterns detected across your store', 'puredevs-customer-history-for-woocommerce' ); ?>
			</p>
		</div>
		<button class="pdwchs-refresh-behavioral-widget" title="<?php esc_attr_e( 'Refresh', 'puredevs-customer-history-for-woocommerce' ); ?>">
			<span class="dashicons dashicons-update"></span>
		</button>
	</div>

	<div class="pdwchs-widget-body">
		<div id="pdwchs-behavioral-segments-chart">
			<div class="pdwchs-loading">
				<span class="spinner is-active"></span>
				<p><?php echo esc_html__( 'Loading behavioral data...', 'puredevs-customer-history-for-woocommerce' ); ?></p>
			</div>
		</div>

		<div id="pdwchs-behavioral-insights" style="display:none;">
			<h4>
				<span class="dashicons dashicons-lightbulb"></span>
				<?php echo esc_html__( 'Actionable Insights', 'puredevs-customer-history-for-woocommerce' ); ?>
			</h4>
			<ul class="pdwchs-insight-list"></ul>
		</div>
	</div>
</div>

<script type="text/javascript">
var pdwchs_behavioral_nonce = '<?php echo wp_create_nonce( 'pdwchs_behavioral_nonce' ); ?>';
</script>
