<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( ! current_user_can( 'manage_woocommerce' ) ) {
	wp_die( esc_html__( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) );
} ?>

<?php require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php'; ?>
<?php require PDWCHS_PRO_PLUGIN_DIR . 'Stats/templates/top-sub-nav-stats.php'; ?>

<?php
/**
 * Geographic Performance report.
 *
 * Reads one payload and formats it. No arithmetic anywhere below this block: every number is read
 * from PDWCHS_Geographic_Data's payload and passed straight to a formatter. If this template ever
 * needs a number the payload does not carry, the payload is wrong — fix it there.
 *
 * Variables are prefixed $pdwchs_geo_ because the two nav includes above share this scope and own
 * $pdwchs_current_page.
 *
 * @since 3.1.1
 */

// The data class is deliberately not loaded by Pro-Bootstrap.php: it is used on this page only, and
// loading it on every admin request to use it here would be waste.
require_once PDWCHS_PRO_PLUGIN_DIR . 'Analytics/Geographic/Geographic-Data.php';

$pdwchs_geo_range = PDWCHS_Geographic_Page::pdwchs_current_range();
$pdwchs_geo_sort  = PDWCHS_Geographic_Page::pdwchs_current_sort();
$pdwchs_geo_data  = ( new PDWCHS_Geographic_Data() )->pdwchs_get_geographic( $pdwchs_geo_range['start'], $pdwchs_geo_range['end'] );
$pdwchs_geo_rows  = PDWCHS_Geographic_Page::pdwchs_sort_rows( $pdwchs_geo_data['rows'], $pdwchs_geo_sort['orderby'], $pdwchs_geo_sort['order'] );

// The payload's dates are the authoritative pair: the data class normalises again and swaps an
// inverted range, so these are the dates the numbers actually cover.
$pdwchs_geo_start = $pdwchs_geo_data['start_date'];
$pdwchs_geo_end   = $pdwchs_geo_data['end_date'];

$pdwchs_geo_date_format = get_option( 'date_format' );
$pdwchs_geo_start_label = date_i18n( $pdwchs_geo_date_format, strtotime( $pdwchs_geo_start ) );
$pdwchs_geo_end_label   = date_i18n( $pdwchs_geo_date_format, strtotime( $pdwchs_geo_end ) );

$pdwchs_geo_columns = array(
	'country'         => __( 'Country', 'puredevs-customer-history-for-woocommerce' ),
	'net_sales'       => __( 'Net sales', 'puredevs-customer-history-for-woocommerce' ),
	'orders'          => __( 'Orders', 'puredevs-customer-history-for-woocommerce' ),
	'items'           => __( 'Items', 'puredevs-customer-history-for-woocommerce' ),
	'unique_products' => __( 'Unique products', 'puredevs-customer-history-for-woocommerce' ),
);
?>

<br class="clear">
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Geographic Performance', 'puredevs-customer-history-for-woocommerce' ); ?>
	</h1>

	<div class="pdwchs-geo-toolbar">
		<form method="get" class="pdwchs-geo-range-form">
			<input type="hidden" name="page" value="<?php echo esc_attr( PDWCHS_Geographic_Page::PAGE_SLUG ); ?>">
			<?php // The sort travels with the range form, and the range travels with the sort links. ?>
			<input type="hidden" name="orderby" value="<?php echo esc_attr( $pdwchs_geo_sort['orderby'] ); ?>">
			<input type="hidden" name="order" value="<?php echo esc_attr( $pdwchs_geo_sort['order'] ); ?>">

			<label for="pdwchs-geo-date-range">
				<?php esc_html_e( 'Date range', 'puredevs-customer-history-for-woocommerce' ); ?>
			</label>
			<?php // Intentionally unnamed: with no picker attached a browser would submit this display string. ?>
			<input type="text" id="pdwchs-geo-date-range" class="pdwchs-date-range-input" readonly
				value="<?php echo esc_attr( $pdwchs_geo_start_label . ' - ' . $pdwchs_geo_end_label ); ?>">

			<input type="hidden" name="start_date" value="<?php echo esc_attr( $pdwchs_geo_start ); ?>">
			<input type="hidden" name="end_date" value="<?php echo esc_attr( $pdwchs_geo_end ); ?>">

			<button type="submit" class="button">
				<?php esc_html_e( 'Apply', 'puredevs-customer-history-for-woocommerce' ); ?>
			</button>
		</form>
	</div>

	<?php if ( false === $pdwchs_geo_data['has_data'] ) : ?>

		<!-- State: no orders in this range -->
		<div class="notice notice-info inline">
			<p>
				<strong>
					<?php
					printf(
						/* translators: 1: range start date, 2: range end date. */
						esc_html__( 'No orders between %1$s and %2$s.', 'puredevs-customer-history-for-woocommerce' ),
						esc_html( $pdwchs_geo_start_label ),
						esc_html( $pdwchs_geo_end_label )
					);
					?>
				</strong>
			</p>
			<p>
				<?php esc_html_e( 'Try a wider date range. Only completed and processing orders are counted.', 'puredevs-customer-history-for-woocommerce' ); ?>
			</p>
		</div>

	<?php else : ?>

		<!-- State: data -->
		<div class="pdwchs-geo-summary">
			<p class="pdwchs-geo-summary-range">
				<?php
				printf(
					/* translators: 1: range start date, 2: range end date. */
					esc_html__( '%1$s to %2$s', 'puredevs-customer-history-for-woocommerce' ),
					esc_html( $pdwchs_geo_start_label ),
					esc_html( $pdwchs_geo_end_label )
				);
				?>
			</p>
			<ul class="pdwchs-geo-summary-list">
				<li class="pdwchs-geo-summary-item">
					<span class="pdwchs-geo-summary-label"><?php esc_html_e( 'Net sales', 'puredevs-customer-history-for-woocommerce' ); ?></span>
					<span class="pdwchs-geo-summary-value"><?php echo wp_kses_post( wc_price( $pdwchs_geo_data['total_net_sales'] ) ); ?></span>
				</li>
				<li class="pdwchs-geo-summary-item">
					<span class="pdwchs-geo-summary-label"><?php esc_html_e( 'Orders', 'puredevs-customer-history-for-woocommerce' ); ?></span>
					<span class="pdwchs-geo-summary-value"><?php echo esc_html( number_format_i18n( $pdwchs_geo_data['total_orders'] ) ); ?></span>
				</li>
				<li class="pdwchs-geo-summary-item">
					<span class="pdwchs-geo-summary-label"><?php esc_html_e( 'Items', 'puredevs-customer-history-for-woocommerce' ); ?></span>
					<span class="pdwchs-geo-summary-value"><?php echo esc_html( number_format_i18n( $pdwchs_geo_data['total_items'] ) ); ?></span>
				</li>
				<li class="pdwchs-geo-summary-item">
					<span class="pdwchs-geo-summary-label"><?php esc_html_e( 'Countries', 'puredevs-customer-history-for-woocommerce' ); ?></span>
					<span class="pdwchs-geo-summary-value"><?php echo esc_html( number_format_i18n( $pdwchs_geo_data['total_countries'] ) ); ?></span>
				</li>
			</ul>
		</div>

		<?php
		// The comparison layer. Below two countries there is nothing to compare and the table
		// already says everything, so the whole block is absent — not disabled, not an empty canvas
		// with axes. PDWCHS_Geographic_Page::pdwchs_enqueue_scripts() bails on the same count, so
		// neither script is downloaded in that state either.
		//
		// The default pair is the top two by net sales, which is the payload's own order — not the
		// table's, which follows whatever the reader last clicked. Everything below is rendered
		// server-side so the block is correct and complete before any JavaScript runs.
		if ( count( $pdwchs_geo_rows ) >= 2 ) :
			$pdwchs_geo_options  = $pdwchs_geo_data['rows'];
			$pdwchs_geo_defaults = array(
				'a' => $pdwchs_geo_options[0],
				'b' => $pdwchs_geo_options[1],
			);
			$pdwchs_geo_metrics  = array(
				'net_sales'       => __( 'Net sales', 'puredevs-customer-history-for-woocommerce' ),
				'orders'          => __( 'Orders', 'puredevs-customer-history-for-woocommerce' ),
				'items'           => __( 'Items', 'puredevs-customer-history-for-woocommerce' ),
				'unique_products' => __( 'Unique products', 'puredevs-customer-history-for-woocommerce' ),
			);
			?>

			<div class="pdwchs-geo-compare">
				<h2 class="pdwchs-geo-compare-title">
					<?php esc_html_e( 'Compare countries', 'puredevs-customer-history-for-woocommerce' ); ?>
				</h2>

				<div class="pdwchs-geo-compare-controls">
					<?php foreach ( $pdwchs_geo_defaults as $pdwchs_geo_side => $pdwchs_geo_default ) : ?>
						<p class="pdwchs-geo-compare-field">
							<label for="pdwchs-geo-compare-<?php echo esc_attr( $pdwchs_geo_side ); ?>">
								<?php
								if ( 'a' === $pdwchs_geo_side ) {
									esc_html_e( 'Country A', 'puredevs-customer-history-for-woocommerce' );
								} else {
									esc_html_e( 'Country B', 'puredevs-customer-history-for-woocommerce' );
								}
								?>
							</label>
							<?php // A plain select, deliberately unenhanced: WooCommerce's picker is registered by ID for three specific fields on the customers page and would pull in admin styles this page does not use. ?>
							<select id="pdwchs-geo-compare-<?php echo esc_attr( $pdwchs_geo_side ); ?>" class="pdwchs-geo-compare-select">
								<?php foreach ( $pdwchs_geo_options as $pdwchs_geo_option ) : ?>
									<option value="<?php echo esc_attr( $pdwchs_geo_option['country_code'] ); ?>"
										<?php selected( $pdwchs_geo_option['country_code'], $pdwchs_geo_default['country_code'] ); ?>>
										<?php echo esc_html( PDWCHS_Geographic_Page::pdwchs_country_name( $pdwchs_geo_option['country_code'] ) ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</p>
					<?php endforeach; ?>
				</div>

				<?php
				// Hidden until the script shows them; with JavaScript off the content below simply
				// stays visible. The `hidden` attribute rather than CSS alone: a stale or missing
				// stylesheet would otherwise leave a permanent "Loading…" on a page that has
				// finished loading. jQuery's .show() sets an inline display and still wins over it.
				?>
				<p class="pdwchs-geo-compare-loading" hidden><?php esc_html_e( 'Loading…', 'puredevs-customer-history-for-woocommerce' ); ?></p>
				<p class="pdwchs-geo-compare-error" hidden></p>

				<div class="pdwchs-geo-compare-content">
					<div class="pdwchs-geo-compare-panels">
						<?php foreach ( $pdwchs_geo_defaults as $pdwchs_geo_side => $pdwchs_geo_default ) : ?>
							<div class="pdwchs-geo-compare-panel" data-side="<?php echo esc_attr( $pdwchs_geo_side ); ?>">
								<h3 class="pdwchs-geo-compare-country">
									<?php echo esc_html( PDWCHS_Geographic_Page::pdwchs_country_name( $pdwchs_geo_default['country_code'] ) ); ?>
								</h3>
								<ul class="pdwchs-geo-compare-metrics">
									<?php foreach ( $pdwchs_geo_metrics as $pdwchs_geo_metric => $pdwchs_geo_metric_label ) : ?>
										<li class="pdwchs-geo-compare-metric">
											<span class="pdwchs-geo-compare-label"><?php echo esc_html( $pdwchs_geo_metric_label ); ?></span>
											<span class="pdwchs-geo-compare-value" data-metric="<?php echo esc_attr( $pdwchs_geo_metric ); ?>">
												<?php if ( 'net_sales' === $pdwchs_geo_metric ) : ?>
													<?php echo wp_kses_post( wc_price( $pdwchs_geo_default['net_sales'] ) ); ?>
												<?php else : ?>
													<?php echo esc_html( number_format_i18n( $pdwchs_geo_default[ $pdwchs_geo_metric ] ) ); ?>
												<?php endif; ?>
											</span>
										</li>
									<?php endforeach; ?>
								</ul>
							</div>
						<?php endforeach; ?>
					</div>

					<div class="pdwchs-geo-compare-chart-wrap">
						<?php
						// Percentages, not raw values: net sales are money and the other three are
						// counts, and a grouped bar chart gives every group one shared scale. The
						// exact figures are in the panels above.
						?>
						<canvas id="pdwchs-geo-compare-chart" height="260" role="img"
							aria-label="<?php esc_attr_e( 'Grouped bar chart comparing the two selected countries on net sales, orders, items and unique products, each shown as a percentage of the higher of the two values. The exact figures are listed above the chart.', 'puredevs-customer-history-for-woocommerce' ); ?>"></canvas>
					</div>
				</div>
			</div>

		<?php endif; ?>

		<div class="pdwchs-geo-table-wrap">
			<table class="wp-list-table widefat fixed striped pdwchs-geo-table">
				<thead>
					<tr>
						<?php foreach ( $pdwchs_geo_columns as $pdwchs_geo_key => $pdwchs_geo_label ) : ?>
							<?php
							$pdwchs_geo_is_active  = ( $pdwchs_geo_key === $pdwchs_geo_sort['orderby'] );
							$pdwchs_geo_is_numeric = ( 'country' !== $pdwchs_geo_key );

							// The core list-table convention: on the active column the class is the
							// direction in force, on the others it is the opposite of the direction
							// the link would apply.
							if ( $pdwchs_geo_is_active ) {
								$pdwchs_geo_th_class = 'sorted ' . $pdwchs_geo_sort['order'];
								$pdwchs_geo_aria     = ( 'asc' === $pdwchs_geo_sort['order'] ) ? 'ascending' : 'descending';
							} else {
								$pdwchs_geo_th_class = 'sortable ' . ( $pdwchs_geo_is_numeric ? 'asc' : 'desc' );
								$pdwchs_geo_aria     = '';
							}

							if ( $pdwchs_geo_is_numeric ) {
								$pdwchs_geo_th_class .= ' pdwchs-geo-numeric';
							}
							?>
							<th scope="col" class="manage-column <?php echo esc_attr( $pdwchs_geo_th_class ); ?>"
								<?php if ( '' !== $pdwchs_geo_aria ) : ?>aria-sort="<?php echo esc_attr( $pdwchs_geo_aria ); ?>"<?php endif; ?>>
								<a href="<?php echo esc_url( PDWCHS_Geographic_Page::pdwchs_sort_url( $pdwchs_geo_key ) ); ?>">
									<span><?php echo esc_html( $pdwchs_geo_label ); ?></span>
									<span class="sorting-indicator"></span>
								</a>
							</th>
						<?php endforeach; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pdwchs_geo_rows as $pdwchs_geo_row ) : ?>
						<tr>
							<td class="pdwchs-geo-country">
								<?php echo esc_html( PDWCHS_Geographic_Page::pdwchs_country_name( $pdwchs_geo_row['country_code'] ) ); ?>
							</td>
							<td class="pdwchs-geo-numeric">
								<?php echo wp_kses_post( wc_price( $pdwchs_geo_row['net_sales'] ) ); ?>
							</td>
							<td class="pdwchs-geo-numeric">
								<?php echo esc_html( number_format_i18n( $pdwchs_geo_row['orders'] ) ); ?>
							</td>
							<td class="pdwchs-geo-numeric">
								<?php echo esc_html( number_format_i18n( $pdwchs_geo_row['items'] ) ); ?>
							</td>
							<td class="pdwchs-geo-numeric">
								<?php echo esc_html( number_format_i18n( $pdwchs_geo_row['unique_products'] ) ); ?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<p class="description">
			<?php esc_html_e( 'Orders are grouped by their own billing country, including guest checkouts. Orders with no billing country are grouped under "Unknown" and are counted in the totals, which is what makes these rows add up to the store total. There is no combined unique-product count: a product sold in two countries would be counted twice.', 'puredevs-customer-history-for-woocommerce' ); ?>
		</p>

	<?php endif; ?>
</div>
