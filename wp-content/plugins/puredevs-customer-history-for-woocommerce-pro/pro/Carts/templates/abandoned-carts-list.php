<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php include PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php'; ?>
<div id="pd-woocommerce-customer-history">
	<div id="abandoned-carts" class="wrap">
		<h1><?php echo esc_html__( 'Abandoned Carts', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

		<?php if ( ! get_option( 'pdwchs_cart_tracking_enabled', 1 ) ) : ?>
			<div class="notice notice-warning">
				<p><?php echo esc_html__( 'Cart tracking is currently disabled. Enable it in Settings → Abandoned Cart Tracking.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
			</div>
		<?php endif; ?>

		<div class="pd-woo-panel">
			<div class="pd-woo-header pd-woo-clearfix">
				<h2 class="pd-woo-float-left">
					<span class="dashicons dashicons-cart"></span>
					&nbsp;&nbsp;<?php echo esc_html__( 'Abandoned Carts', 'puredevs-customer-history-for-woocommerce' ); ?>
				</h2>
			</div>
			<div class="pd-woo-clearfix">
				<?php
				// Router: delegate to detail view when cart_id is present (R1-21)
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only router; capability is enforced by the page callback before this template loads.
				if ( ! empty( $_GET['cart_id'] ) ) {
					$detail_tpl = __DIR__ . '/cart-detail.php';
					if ( file_exists( $detail_tpl ) ) {
						require $detail_tpl;
					} else {
						echo '<p>' . esc_html__( 'Detail view coming soon.', 'puredevs-customer-history-for-woocommerce' ) . '</p>';
					}
				} else {
					if ( ! class_exists( 'PDWCHS_Abandoned_Carts_List_Table' ) ) {
						require_once PDWCHS_PRO_PLUGIN_DIR . 'Carts/Abandoned-Carts-List-Table.php';
					}
					$list_table = new PDWCHS_Abandoned_Carts_List_Table();
					$list_table->prepare_items();
					$list_table->display();
				}
				?>
			</div>
		</div>
	</div>
</div>
