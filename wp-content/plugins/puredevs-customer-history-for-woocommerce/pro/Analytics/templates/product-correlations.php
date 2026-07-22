<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) ); } ?>

<?php require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php'; ?>
<?php require PDWCHS_PRO_PLUGIN_DIR . 'Stats/templates/top-sub-nav-stats.php'; ?>

<br class="clear">
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Frequently Bought Together', 'puredevs-customer-history-for-woocommerce' ); ?>
	</h1>

	<!-- Filters card -->
	<div class="pdwchs-correlations-filter-card">
		<div class="pdwchs-correlations-filter-row">

			<div class="pdwchs-filter-group pdwchs-filter-group--product">
				<label class="pdwchs-filter-label" for="pdwchs-anchor-product">
					<?php esc_html_e( 'Anchor Product', 'puredevs-customer-history-for-woocommerce' ); ?>
				</label>
				<select id="pdwchs-anchor-product" name="product_id">
					<option value="">
						<?php esc_html_e( '— Select a product —', 'puredevs-customer-history-for-woocommerce' ); ?>
					</option>
					<?php
					$products = wc_get_products( array(
						'limit'  => 200,
						'status' => 'publish',
						'return' => 'objects',
					) );
					foreach ( $products as $product ) {
						echo '<option value="' . esc_attr( $product->get_id() ) . '">'
							. esc_html( $product->get_name() ) . '</option>';
					}
					?>
				</select>
			</div>

			<div class="pdwchs-filter-group">
				<span class="pdwchs-filter-label">
					<?php esc_html_e( 'Window', 'puredevs-customer-history-for-woocommerce' ); ?>
				</span>
				<div class="pdwchs-btn-toggle-group">
					<?php
					foreach ( array( 30, 60, 90, 180 ) as $days ) {
						$checked  = 90 === $days ? ' checked' : '';
						$input_id = 'pdwchs-win-' . $days;
						echo '<input type="radio" name="pdwchs_window" id="' . esc_attr( $input_id ) . '" value="' . esc_attr( $days ) . '"' . esc_attr( $checked ) . '>';
						echo '<label for="' . esc_attr( $input_id ) . '">'
							. esc_html( $days ) . ' ' . esc_html__( 'days', 'puredevs-customer-history-for-woocommerce' )
							. '</label>';
					}
					?>
				</div>
			</div>

			<div class="pdwchs-filter-group">
				<span class="pdwchs-filter-label">
					<?php esc_html_e( 'Show top', 'puredevs-customer-history-for-woocommerce' ); ?>
				</span>
				<div class="pdwchs-btn-toggle-group">
					<?php
					foreach ( array( 5, 10, 20 ) as $n ) {
						$checked  = 10 === $n ? ' checked' : '';
						$input_id = 'pdwchs-limit-' . $n;
						echo '<input type="radio" name="pdwchs_limit" id="' . esc_attr( $input_id ) . '" value="' . esc_attr( $n ) . '"' . esc_attr( $checked ) . '>';
						echo '<label for="' . esc_attr( $input_id ) . '">' . esc_html( $n ) . '</label>';
					}
					?>
				</div>
			</div>

		</div>
	</div>

	<!-- State: initial -->
	<div class="pdwchs-correlations-empty">
		<p><?php esc_html_e( 'Select a product above to see frequently bought together items.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>

	<!-- State: loading -->
	<div class="pdwchs-correlations-loading" style="display:none;">
		<span class="spinner is-active"></span>
		<p><?php esc_html_e( 'Loading...', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>

	<!-- State: error -->
	<div class="pdwchs-correlations-error" style="display:none;">
		<p><?php esc_html_e( 'Error loading data. Please try again.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>

	<!-- Results table (hidden until first successful fetch) -->
	<div class="pdwchs-correlations-content" style="display:none;">
		<table class="wp-list-table widefat fixed striped pdwchs-correlations-table">
			<thead>
				<tr>
					<th scope="col" style="width:50px;">
						<?php esc_html_e( 'Rank', 'puredevs-customer-history-for-woocommerce' ); ?>
					</th>
					<th scope="col">
						<?php esc_html_e( 'Product', 'puredevs-customer-history-for-woocommerce' ); ?>
					</th>
					<th scope="col" style="width:160px;">
						<?php esc_html_e( 'Co-occurrences', 'puredevs-customer-history-for-woocommerce' ); ?>
					</th>
					<th scope="col" style="width:160px;">
						<?php esc_html_e( 'Confidence', 'puredevs-customer-history-for-woocommerce' ); ?>
					</th>
				</tr>
			</thead>
			<tbody id="pdwchs-correlations-tbody">
				<!-- Rows injected by JS -->
			</tbody>
		</table>
	</div>
</div>
