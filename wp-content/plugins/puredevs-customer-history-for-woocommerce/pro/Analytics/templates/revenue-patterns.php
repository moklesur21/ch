<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( ! current_user_can( 'manage_woocommerce' ) ) {
	wp_die( esc_html__( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) );
} ?>

<?php require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php'; ?>
<?php require PDWCHS_PRO_PLUGIN_DIR . 'Stats/templates/top-sub-nav-stats.php'; ?>

<br class="clear">
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Revenue Patterns', 'puredevs-customer-history-for-woocommerce' ); ?>
	</h1>

	<!-- Filters bar -->
	<div class="pdwchs-product-revenue-filters">
		<div class="pdwchs-filter-group">
			<label for="pdwchs-patterns-date-range">
				<?php esc_html_e( 'Date Range', 'puredevs-customer-history-for-woocommerce' ); ?>
			</label>
			<input id="pdwchs-patterns-date-range" type="text" readonly
				   class="pdwchs-date-range-input"
				   value="<?php esc_attr_e( 'Last 30 Days', 'puredevs-customer-history-for-woocommerce' ); ?>">
		</div>

		<div class="pdwchs-filter-group">
			<label><?php esc_html_e( 'View', 'puredevs-customer-history-for-woocommerce' ); ?></label>
			<div class="pdwchs-patterns-view-toggle" role="group">
				<button type="button" class="button pdwchs-view-btn pdwchs-view-btn--active"
						data-view="dow">
					<?php esc_html_e( 'Day of Week', 'puredevs-customer-history-for-woocommerce' ); ?>
				</button>
				<button type="button" class="button pdwchs-view-btn"
						data-view="hour">
					<?php esc_html_e( 'Hour of Day', 'puredevs-customer-history-for-woocommerce' ); ?>
				</button>
				<button type="button" class="button pdwchs-view-btn"
						data-view="heatmap">
					<?php esc_html_e( 'Heatmap', 'puredevs-customer-history-for-woocommerce' ); ?>
				</button>
			</div>
		</div>

		<div class="pdwchs-filter-group pdwchs-filter-group--apply">
			<button id="pdwchs-patterns-apply" class="button button-primary">
				<?php esc_html_e( 'Apply', 'puredevs-customer-history-for-woocommerce' ); ?>
			</button>
		</div>
	</div>

	<!-- State: loading -->
	<div class="pdwchs-patterns-loading" style="display:none;">
		<span class="spinner is-active"></span>
		<p><?php esc_html_e( 'Loading...', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>

	<!-- State: error -->
	<div class="pdwchs-patterns-error" style="display:none;">
		<p><?php esc_html_e( 'Error loading data. Please try again.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>

	<!-- State: empty -->
	<div class="pdwchs-patterns-empty">
		<p><?php esc_html_e( 'Select a date range and click Apply to view revenue patterns.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>

	<!-- Content (hidden until first successful fetch) -->
	<div class="pdwchs-patterns-content" style="display:none;">

		<!-- Summary cards: best/worst day or hour -->
		<div id="pdwchs-patterns-summary" class="pdwchs-patterns-summary-cards"></div>

		<!-- DOW chart (visible when view === 'dow') -->
		<div id="pdwchs-patterns-dow-wrap" class="pdwchs-patterns-chart-container" style="display:none;">
			<canvas id="pdwchs-patterns-dow-chart" height="300"
					aria-label="<?php esc_attr_e( 'Revenue by day of week', 'puredevs-customer-history-for-woocommerce' ); ?>"
					role="img"></canvas>
		</div>

		<!-- Hour chart (visible when view === 'hour') -->
		<div id="pdwchs-patterns-hour-wrap" class="pdwchs-patterns-chart-container" style="display:none;">
			<canvas id="pdwchs-patterns-hour-chart" height="300"
					aria-label="<?php esc_attr_e( 'Revenue by hour of day', 'puredevs-customer-history-for-woocommerce' ); ?>"
					role="img"></canvas>
		</div>

		<!-- Heatmap grid (visible when view === 'heatmap') -->
		<div id="pdwchs-patterns-heatmap-wrap" class="pdwchs-patterns-heatmap-wrap" style="display:none;">
			<div id="pdwchs-patterns-heatmap" class="pdwchs-patterns-heatmap"></div>
		</div>
	</div>
</div>
