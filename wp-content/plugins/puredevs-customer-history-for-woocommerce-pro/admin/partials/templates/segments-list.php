<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php include 'top-nav.php'; ?>
<div id="pd-woocommerce-customer-history">
	<div id="segments" class="wrap">
		<h1><?php echo esc_html__( 'Customer Segments', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

		<div class="pd-woo-panel">
			<div class="pd-woo-header pd-woo-clearfix">
				<h2 class="pd-woo-float-left">
					<span class="dashicons dashicons-groups"></span>
					&nbsp;&nbsp;<?php echo esc_html__( 'Saved Segments', 'puredevs-customer-history-for-woocommerce' ); ?>
				</h2>
				<div class="pd-woo-float-right">
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-customers' ) ); ?>"
					   class="button button-primary">
						<?php echo esc_html__( '+ New Segment', 'puredevs-customer-history-for-woocommerce' ); ?>
					</a>
				</div>
			</div>

			<div class="pd-woo-clearfix">
				<?php
				if ( ! class_exists( 'PDWCHS_Segments_List_Table' ) ) {
					require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'admin/class-pdwchs-segments-list-table.php';
				}
				$list_table = new PDWCHS_Segments_List_Table();
				$list_table->prepare_items();
				$list_table->display();
				?>
			</div>
		</div>

		<?php
		// Ready-made Pro segments — advertisement only (locked teasers; no member counts).
		// Skip any whose name already exists as a real segment (e.g. on a lapsed-Pro site)
		// to avoid duplicate rows.
		if ( ! pdwchs_is_pro_active() && class_exists( 'PDWCHS_Upsell' ) ) :
			$pdwchs_upgrade_url   = PDWCHS_Upsell::pdwchs_upgrade_url();
			$pdwchs_existing_names = array();
			if ( class_exists( 'PDWCHS_Segments_Manager' ) ) {
				$pdwchs_seg_mgr = new PDWCHS_Segments_Manager();
				foreach ( (array) $pdwchs_seg_mgr->pdwchs_get_segments() as $pdwchs_existing_seg ) {
					if ( isset( $pdwchs_existing_seg->name ) ) {
						$pdwchs_existing_names[] = strtolower( trim( $pdwchs_existing_seg->name ) );
					}
				}
			}
			$pdwchs_pro_segments = array();
			foreach ( PDWCHS_Upsell::pdwchs_pro_segments() as $pdwchs_candidate ) {
				if ( ! in_array( strtolower( $pdwchs_candidate['title'] ), $pdwchs_existing_names, true ) ) {
					$pdwchs_pro_segments[] = $pdwchs_candidate;
				}
			}
		endif;

		if ( ! pdwchs_is_pro_active() && class_exists( 'PDWCHS_Upsell' ) && ! empty( $pdwchs_pro_segments ) ) :
			?>
			<div class="pd-woo-panel pdwchs-pro-segments">
				<div class="pd-woo-header pd-woo-clearfix">
					<h2 class="pd-woo-float-left">
						<span class="dashicons dashicons-lock"></span>
						&nbsp;&nbsp;<?php echo esc_html__( 'Ready-made Pro segments', 'puredevs-customer-history-for-woocommerce' ); ?>
					</h2>
					<div class="pd-woo-float-right">
						<a href="<?php echo esc_url( $pdwchs_upgrade_url ); ?>" class="button button-primary" target="_blank" rel="noopener noreferrer">
							<?php echo esc_html__( 'Unlock with Pro', 'puredevs-customer-history-for-woocommerce' ); ?>
						</a>
					</div>
				</div>
				<p class="description" style="margin:0 0 10px;">
					<?php echo esc_html__( 'Pro auto-builds these intelligence segments from RFM and customer health scoring — no setup required.', 'puredevs-customer-history-for-woocommerce' ); ?>
				</p>
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th><?php echo esc_html__( 'Segment', 'puredevs-customer-history-for-woocommerce' ); ?></th>
							<th><?php echo esc_html__( 'What it finds', 'puredevs-customer-history-for-woocommerce' ); ?></th>
							<th></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $pdwchs_pro_segments as $pdwchs_seg ) : ?>
							<tr style="opacity:0.7;">
								<td>
									<span class="dashicons dashicons-lock" style="color:#888;"></span>
									<strong><?php echo esc_html( $pdwchs_seg['title'] ); ?></strong>
								</td>
								<td><?php echo esc_html( $pdwchs_seg['desc'] ); ?></td>
								<td>
									<a href="<?php echo esc_url( $pdwchs_upgrade_url ); ?>" class="button" target="_blank" rel="noopener noreferrer">
										<?php echo esc_html__( 'Unlock with Pro', 'puredevs-customer-history-for-woocommerce' ); ?>
									</a>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
		<?php endif; ?>

	</div>
</div>
