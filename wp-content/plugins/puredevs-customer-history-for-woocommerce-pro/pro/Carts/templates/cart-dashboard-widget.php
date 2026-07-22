<?php
defined( 'ABSPATH' ) || exit;

if ( ! get_option( 'pdwchs_cart_tracking_enabled', 1 ) ) {
	return;
}

$cart_widget = new PDWCHS_Cart_Widget();
$stats       = $cart_widget->pdwchs_get_summary_stats( 7 );
?>

<div class="pd-woo-main-container">
	<div class="pd-woo-panel">
		<div class="pdwchs-cart-widget">
			<h3><?php echo esc_html__( 'Abandoned Carts (Last 7 Days)', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
			<p class="pdwchs-cart-widget-headline">
				<strong><?php echo esc_html( number_format_i18n( $stats['abandoned_count'] ) ); ?></strong>
				<?php echo esc_html__( 'abandoned carts worth', 'puredevs-customer-history-for-woocommerce' ); ?>
				<strong><?php echo wp_kses_post( wc_price( $stats['abandoned_value'], array( 'currency' => $stats['currency'] ) ) ); ?></strong>
			</p>
			<p class="pdwchs-cart-widget-note">
				<?php echo esc_html__( 'Observation only — see the Automation Add-on for recovery actions.', 'puredevs-customer-history-for-woocommerce' ); ?>
			</p>
			<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-abandoned-carts&status=abandoned&date_range=last_7_days' ) ); ?>">
				<?php echo esc_html__( 'View abandoned carts →', 'puredevs-customer-history-for-woocommerce' ); ?>
			</a>
		</div>
	</div>
</div>
