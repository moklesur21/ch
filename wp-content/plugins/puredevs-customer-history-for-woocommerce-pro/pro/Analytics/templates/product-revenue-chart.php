<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( ! current_user_can( 'manage_woocommerce' ) ) { wp_die( esc_html__( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) ); } ?>

<?php require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php'; ?>
<?php require PDWCHS_PRO_PLUGIN_DIR . 'Stats/templates/top-sub-nav-stats.php'; ?>

<br class="clear">
<div class="wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Product Revenue & Comparison', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

	<!-- Filters bar -->
	<div class="pdwchs-product-revenue-filters">
		<div class="pdwchs-filter-group">
			<label for="pdwchs-product-select">
				<?php esc_html_e( 'Products (max 3)', 'puredevs-customer-history-for-woocommerce' ); ?>
			</label>
			<select id="pdwchs-product-select" name="product_ids[]" multiple style="width:100%">
				<?php
				$products = wc_get_products( array( 'limit' => 200, 'status' => 'publish', 'return' => 'objects' ) );
				foreach ( $products as $product ) {
					echo '<option value="' . esc_attr( $product->get_id() ) . '">'
						. esc_html( $product->get_name() ) . '</option>';
				}
				?>
			</select>
		</div>

		<div class="pdwchs-filter-group">
			<label for="pdwchs-product-date-range">
				<?php esc_html_e( 'Date Range', 'puredevs-customer-history-for-woocommerce' ); ?>
			</label>
			<input id="pdwchs-product-date-range" type="text" readonly
			       class="pdwchs-date-range-input"
			       value="<?php esc_attr_e( 'Last 30 Days', 'puredevs-customer-history-for-woocommerce' ); ?>">
		</div>

		<div class="pdwchs-filter-group pdwchs-granularity-group">
			<span><?php esc_html_e( 'Granularity', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<label><input type="radio" name="pdwchs_granularity" value="day" checked>
				<?php esc_html_e( 'Day', 'puredevs-customer-history-for-woocommerce' ); ?></label>
			<label><input type="radio" name="pdwchs_granularity" value="week">
				<?php esc_html_e( 'Week', 'puredevs-customer-history-for-woocommerce' ); ?></label>
			<label><input type="radio" name="pdwchs_granularity" value="month">
				<?php esc_html_e( 'Month', 'puredevs-customer-history-for-woocommerce' ); ?></label>
		</div>

		<button id="pdwchs-product-apply" class="button button-primary">
			<?php esc_html_e( 'Apply', 'puredevs-customer-history-for-woocommerce' ); ?>
		</button>
	</div>

	<!-- States -->
	<div class="pdwchs-product-revenue-loading" style="display:none;">
		<span class="spinner is-active"></span>
		<p><?php esc_html_e( 'Loading...', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>
	<div class="pdwchs-product-revenue-error" style="display:none;">
		<p><?php esc_html_e( 'Error loading data. Please try again.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>
	<div class="pdwchs-product-revenue-empty" style="display:none;">
		<p><?php esc_html_e( 'Select at least one product and click Apply.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>

	<!-- Content (hidden until first successful fetch) -->
	<div class="pdwchs-product-revenue-content" style="display:none;">
		<div class="pdwchs-chart-container">
			<canvas id="pdwchs-product-revenue-chart" height="300"
			        aria-label="<?php esc_attr_e( 'Product revenue chart', 'puredevs-customer-history-for-woocommerce' ); ?>"
			        role="img"></canvas>
		</div>
		<div id="pdwchs-product-summary-cards" class="pdwchs-product-summary-grid"></div>
	</div>
</div>
