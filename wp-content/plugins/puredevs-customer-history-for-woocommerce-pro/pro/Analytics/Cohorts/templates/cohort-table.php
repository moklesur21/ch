<?php defined( 'ABSPATH' ) || exit; ?>
<?php if ( ! current_user_can( 'manage_woocommerce' ) ) {
	wp_die( esc_html__( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) );
} ?>

<?php require PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php'; ?>
<?php require PDWCHS_PRO_PLUGIN_DIR . 'Stats/templates/top-sub-nav-stats.php'; ?>

<?php
$pdwchs_matrix   = PDWCHS_Cohort_Cron::pdwchs_get_matrix();
$pdwchs_view     = PDWCHS_Cohort_Page::pdwchs_current_view();
$pdwchs_base_url = admin_url( 'admin.php?page=' . PDWCHS_Cohort_Page::PAGE_SLUG );
?>

<br class="clear">
<div class="wrap">
	<h1 class="wp-heading-inline">
		<?php esc_html_e( 'Cohort Retention', 'puredevs-customer-history-for-woocommerce' ); ?>
	</h1>

	<?php if ( null === $pdwchs_matrix ) : ?>

		<!-- State: never computed -->
		<div class="notice notice-info inline">
			<p>
				<strong><?php esc_html_e( 'Cohort data has not been generated yet.', 'puredevs-customer-history-for-woocommerce' ); ?></strong>
			</p>
			<p>
				<?php esc_html_e( 'Cohorts are calculated once a day. The first calculation runs within a few minutes of activation — check back shortly.', 'puredevs-customer-history-for-woocommerce' ); ?>
			</p>
		</div>

	<?php elseif ( empty( $pdwchs_matrix['cohorts'] ) ) : ?>

		<!-- State: empty store -->
		<div class="notice notice-info inline">
			<p>
				<strong><?php esc_html_e( 'No cohorts yet.', 'puredevs-customer-history-for-woocommerce' ); ?></strong>
				<?php esc_html_e( 'Cohorts appear once the store has completed or processing orders.', 'puredevs-customer-history-for-woocommerce' ); ?>
			</p>
		</div>

	<?php else : ?>

		<!-- Toolbar: view toggle + generated stamp -->
		<div class="pdwchs-cohort-toolbar">
			<div class="pdwchs-cohort-view-toggle" role="group">
				<a class="button pdwchs-view-btn<?php echo ( 'customers' === $pdwchs_view ) ? ' pdwchs-view-btn--active' : ''; ?>"
				   href="<?php echo esc_url( $pdwchs_base_url . '&view=customers' ); ?>">
					<?php esc_html_e( 'Customers', 'puredevs-customer-history-for-woocommerce' ); ?>
				</a>
				<a class="button pdwchs-view-btn<?php echo ( 'revenue' === $pdwchs_view ) ? ' pdwchs-view-btn--active' : ''; ?>"
				   href="<?php echo esc_url( $pdwchs_base_url . '&view=revenue' ); ?>">
					<?php esc_html_e( 'Revenue', 'puredevs-customer-history-for-woocommerce' ); ?>
				</a>
			</div>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="pdwchs-cohort-export-form">
				<input type="hidden" name="action" value="pdwchs_export_cohorts_csv">
				<input type="hidden" name="view" value="<?php echo esc_attr( $pdwchs_view ); ?>">
				<?php wp_nonce_field( 'pdwchs_export_cohorts_nonce', 'nonce' ); ?>
				<button type="submit" class="button">
					<?php esc_html_e( 'Export CSV', 'puredevs-customer-history-for-woocommerce' ); ?>
				</button>
			</form>

			<span class="pdwchs-cohort-generated">
				<?php
				/* translators: %s: human-readable time difference, e.g. "3 hours". */
				printf(
					esc_html__( 'Generated %s ago', 'puredevs-customer-history-for-woocommerce' ),
					esc_html( human_time_diff( strtotime( $pdwchs_matrix['generated_at'] ) ) )
				);
				?>
			</span>
		</div>

		<div class="pdwchs-cohort-chart-wrap">
			<canvas id="pdwchs-cohort-chart" height="280"
					aria-label="<?php esc_attr_e( 'Average retention by month since first order', 'puredevs-customer-history-for-woocommerce' ); ?>"
					role="img"></canvas>
		</div>

		<div class="pdwchs-cohort-table-wrap">
			<table class="widefat striped pdwchs-cohort-table">
				<thead>
					<tr>
						<th class="pdwchs-cohort-label"><?php esc_html_e( 'Cohort', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th><?php esc_html_e( 'Customers', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<?php for ( $pdwchs_offset = 0; $pdwchs_offset <= 12; $pdwchs_offset++ ) : ?>
							<th>
								<?php
								/* translators: %d: number of months since acquisition. */
								printf( esc_html__( 'M+%d', 'puredevs-customer-history-for-woocommerce' ), (int) $pdwchs_offset );
								?>
							</th>
						<?php endfor; ?>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $pdwchs_matrix['cohorts'] as $pdwchs_cohort ) : ?>
						<tr>
							<td class="pdwchs-cohort-label"><?php echo esc_html( $pdwchs_cohort['month'] ); ?></td>
							<td><?php echo esc_html( (int) $pdwchs_cohort['size'] ); ?></td>
							<?php
							for ( $pdwchs_offset = 0; $pdwchs_offset <= 12; $pdwchs_offset++ ) :
								$pdwchs_cell = $pdwchs_cohort['cells'][ $pdwchs_offset ];
								$pdwchs_size = (int) $pdwchs_cohort['size'];

								if ( false === $pdwchs_cell['complete'] ) :
									?>
									<td class="pdwchs-cohort-cell pdwchs-cohort-cell--partial"
										title="<?php echo esc_attr__( 'This month has not ended yet.', 'puredevs-customer-history-for-woocommerce' ); ?>">&mdash;</td>
									<?php
								elseif ( 0 === $pdwchs_size ) :
									?>
									<td class="pdwchs-cohort-cell pdwchs-cohort-cell--empty">&mdash;</td>
									<?php
								else :
									$pdwchs_pct   = round( $pdwchs_cell['customers'] / $pdwchs_size * 100, 1 );
									$pdwchs_class = 'pdwchs-cohort-cell--d' . min( 9, (int) floor( $pdwchs_pct / 10 ) );
									?>
									<td class="pdwchs-cohort-cell <?php echo esc_attr( $pdwchs_class ); ?>">
										<?php
										if ( 'revenue' === $pdwchs_view ) {
											echo wp_kses_post( wc_price( $pdwchs_cell['revenue'] ) );
										} else {
											echo esc_html( $pdwchs_pct . '%' );
										}
										?>
									</td>
									<?php
								endif;
							endfor;
							?>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</div>

		<p class="description">
			<?php esc_html_e( 'Cohorts count registered customers by account and guest checkouts by billing email. A shopper who checked out as a guest and later registered appears in both. These totals will not match the new-vs-returning split on the Revenue dashboard, which counts guests separately.', 'puredevs-customer-history-for-woocommerce' ); ?>
		</p>

	<?php endif; ?>
</div>
