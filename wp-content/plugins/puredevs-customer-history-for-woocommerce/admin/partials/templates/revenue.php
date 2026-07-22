<?php
/**
 * Free Revenue dashboard — KPI cards + over-time trend (Chart.js).
 *
 * Data: PDWCHS_Revenue_Data_Free (cached, HPOS-aware). Date range is a read-only
 * GET filter, defaulting to the last 30 days and clamped to the free 90-day window.
 *
 * @package PD_Customer_History
 */

defined( 'ABSPATH' ) or exit;

require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/analytics/class-pdwchs-revenue-data-free.php';

$pdwchs_rev = new PDWCHS_Revenue_Data_Free();

$pdwchs_rev_end = isset( $_GET['pdwchs_rev_end'] )
	? sanitize_text_field( wp_unslash( $_GET['pdwchs_rev_end'] ) )
	: current_time( 'Y-m-d' );
$pdwchs_rev_start = isset( $_GET['pdwchs_rev_start'] )
	? sanitize_text_field( wp_unslash( $_GET['pdwchs_rev_start'] ) )
	: gmdate( 'Y-m-d', strtotime( $pdwchs_rev_end . ' -29 days' ) );

$pdwchs_min_start = PDWCHS_Data_Window::pdwchs_min_start();

$pdwchs_overview = $pdwchs_rev->pdwchs_get_revenue_overview( $pdwchs_rev_start, $pdwchs_rev_end );
$pdwchs_series   = $pdwchs_rev->pdwchs_get_revenue_over_time( $pdwchs_rev_start, $pdwchs_rev_end, 'daily' );

$pdwchs_labels = array();
$pdwchs_values = array();
foreach ( $pdwchs_series['buckets'] as $pdwchs_b ) {
	$pdwchs_labels[] = $pdwchs_b['date'];
	$pdwchs_values[] = round( (float) $pdwchs_b['net_revenue'], 2 );
}
$pdwchs_has_data = ( (int) $pdwchs_overview['total_orders'] > 0 );

// Period-over-period change badge.
$pdwchs_change = function ( $val ) {
	if ( null === $val ) {
		return '<span class="pd-rev-change">—</span>';
	}
	$color = $val > 0 ? '#15803d' : ( $val < 0 ? '#b91c1c' : '#666' );
	$arrow = $val > 0 ? '▲' : ( $val < 0 ? '▼' : '' );
	return '<span class="pd-rev-change" style="color:' . esc_attr( $color ) . ';">'
		. esc_html( trim( $arrow . ' ' . number_format_i18n( abs( (float) $val ), 1 ) . '%' ) )
		. '</span>';
};
?>
<div id="pd-customer-history" class="wrap">
	<?php include __DIR__ . '/top-nav.php'; ?>

	<h1><?php echo esc_html__( 'Revenue', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

	<form method="get" style="margin:12px 0;display:flex;gap:10px;align-items:center;flex-wrap:wrap;">
		<input type="hidden" name="page" value="pd-woo-revenue" />
		<label><?php echo esc_html__( 'From', 'puredevs-customer-history-for-woocommerce' ); ?>
			<input type="date" name="pdwchs_rev_start" value="<?php echo esc_attr( $pdwchs_rev_start ); ?>" min="<?php echo esc_attr( $pdwchs_min_start ); ?>" />
		</label>
		<label><?php echo esc_html__( 'To', 'puredevs-customer-history-for-woocommerce' ); ?>
			<input type="date" name="pdwchs_rev_end" value="<?php echo esc_attr( $pdwchs_rev_end ); ?>" />
		</label>
		<button type="submit" class="button button-primary"><?php echo esc_html__( 'Apply', 'puredevs-customer-history-for-woocommerce' ); ?></button>
	</form>

	<div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:16px;">
		<div class="pd-woo-panel" style="flex:1;min-width:180px;padding:16px;">
			<div style="color:#666;font-size:13px;"><?php echo esc_html__( 'Net Revenue', 'puredevs-customer-history-for-woocommerce' ); ?></div>
			<div style="font-size:22px;font-weight:600;"><?php echo wp_kses_post( wc_price( $pdwchs_overview['net_revenue'] ) ); ?></div>
			<?php echo wp_kses_post( $pdwchs_change( $pdwchs_overview['comparison']['net_revenue_change'] ) ); ?>
		</div>
		<div class="pd-woo-panel" style="flex:1;min-width:180px;padding:16px;">
			<div style="color:#666;font-size:13px;"><?php echo esc_html__( 'Orders', 'puredevs-customer-history-for-woocommerce' ); ?></div>
			<div style="font-size:22px;font-weight:600;"><?php echo esc_html( number_format_i18n( $pdwchs_overview['total_orders'] ) ); ?></div>
			<?php echo wp_kses_post( $pdwchs_change( $pdwchs_overview['comparison']['total_orders_change'] ) ); ?>
		</div>
		<div class="pd-woo-panel" style="flex:1;min-width:180px;padding:16px;">
			<div style="color:#666;font-size:13px;"><?php echo esc_html__( 'Avg Order Value', 'puredevs-customer-history-for-woocommerce' ); ?></div>
			<div style="font-size:22px;font-weight:600;"><?php echo wp_kses_post( wc_price( $pdwchs_overview['aov'] ) ); ?></div>
			<?php echo wp_kses_post( $pdwchs_change( $pdwchs_overview['comparison']['aov_change'] ) ); ?>
		</div>
		<div class="pd-woo-panel" style="flex:1;min-width:180px;padding:16px;">
			<div style="color:#666;font-size:13px;"><?php echo esc_html__( 'Customers', 'puredevs-customer-history-for-woocommerce' ); ?></div>
			<div style="font-size:22px;font-weight:600;"><?php echo esc_html( number_format_i18n( $pdwchs_overview['total_customers'] ) ); ?></div>
			<?php echo wp_kses_post( $pdwchs_change( $pdwchs_overview['comparison']['total_customers_change'] ) ); ?>
		</div>
	</div>

	<div class="pd-woo-panel" style="padding:16px;">
		<div class="pd-woo-header"><h2 style="margin-top:0;"><?php echo esc_html__( 'Net revenue over time', 'puredevs-customer-history-for-woocommerce' ); ?></h2></div>
		<?php if ( $pdwchs_has_data ) : ?>
			<div style="position:relative;height:320px;"><canvas id="pdwchs-revenue-chart"></canvas></div>
			<script>
				jQuery(function($){
					var el = document.getElementById('pdwchs-revenue-chart');
					if ( ! el || typeof Chart === 'undefined' ) { return; }
					new Chart(el, {
						type: 'line',
						data: {
							labels: <?php echo wp_json_encode( $pdwchs_labels ); ?>,
							datasets: [{
								label: <?php echo wp_json_encode( __( 'Net revenue', 'puredevs-customer-history-for-woocommerce' ) ); ?>,
								data: <?php echo wp_json_encode( $pdwchs_values ); ?>,
								borderColor: '#3b82f6',
								backgroundColor: 'rgba(59,130,246,0.12)',
								fill: true,
								tension: 0.3
							}]
						},
						options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true } } }
					});
				});
			</script>
		<?php else : ?>
			<p style="color:#666;"><?php echo esc_html__( 'No completed orders in this period yet. Revenue will appear here as orders come in.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
		<?php endif; ?>
	</div>
</div>
