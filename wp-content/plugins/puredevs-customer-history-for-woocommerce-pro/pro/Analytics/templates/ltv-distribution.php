<?php
/**
 * LTV Distribution Dashboard Panel Template
 *
 * Server-rendered from the R15-22A payload: the average and median cards, the customer total, and
 * the spend brackets as a table. Complete and correct with JavaScript disabled — the histogram
 * canvas is a layer above the table, and nothing here depends on it drawing.
 *
 * Every bracket in the ladder is rendered, including the ones nobody falls into. They are the shape
 * of the distribution; dropping them would make a bimodal store look continuous.
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/pro/Analytics/templates
 * @since      3.1.1
 *
 * @var array $ltv Payload from PDWCHS_LTV_Distribution::pdwchs_get_ltv_distribution().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * This total counts *people* — registered customers and guests alike — over completed and processing
 * orders, net of refunds, uncapped. The Customers & Users list answers a different question: any
 * order status at full value, gross of refunds, registered users only, and capped at 100 on the free
 * tier. The two will not match, and neither is wrong, so the label says exactly what this one counts.
 */
$pdwchs_ltv_total_label = __( 'Customers with at least one completed or processing order', 'puredevs-customer-history-for-woocommerce' );

$pdwchs_ltv_no_value_hint = __( 'There are no customers yet, so this cannot be derived.', 'puredevs-customer-history-for-woocommerce' );
?>

<div class="pd-woo-panel pdwchs-ltv-widget">

	<div class="pd-woo-panel-header pdwchs-ltv-header">
		<h2><?php echo esc_html__( 'Lifetime value distribution', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
	</div><!-- .pdwchs-ltv-header -->

	<div class="pd-woo-panel-body pdwchs-ltv-body">

		<?php if ( empty( $ltv['has_data'] ) ) : ?>

			<p class="pdwchs-ltv-empty">
				<?php echo esc_html__( 'No completed or processing orders yet, so there is no lifetime value to distribute.', 'puredevs-customer-history-for-woocommerce' ); ?>
			</p>

		<?php else : ?>

			<div class="pdwchs-ltv-cards">

				<div class="pdwchs-ltv-card" data-metric="average">
					<span class="pdwchs-ltv-card-label">
						<?php echo esc_html__( 'Average lifetime value', 'puredevs-customer-history-for-woocommerce' ); ?>
					</span>
					<?php if ( null !== $ltv['average_ltv'] ) : ?>
						<span class="pdwchs-ltv-card-value">
							<?php echo wp_kses_post( wc_price( $ltv['average_ltv'] ) ); ?>
						</span>
					<?php else : ?>
						<span class="pdwchs-ltv-card-value pdwchs-ltv-undefined"
							title="<?php echo esc_attr( $pdwchs_ltv_no_value_hint ); ?>">&mdash;</span>
					<?php endif; ?>
				</div>

				<div class="pdwchs-ltv-card" data-metric="median">
					<span class="pdwchs-ltv-card-label">
						<?php echo esc_html__( 'Median lifetime value', 'puredevs-customer-history-for-woocommerce' ); ?>
					</span>
					<?php if ( null !== $ltv['median_ltv'] ) : ?>
						<span class="pdwchs-ltv-card-value">
							<?php echo wp_kses_post( wc_price( $ltv['median_ltv'] ) ); ?>
						</span>
					<?php else : ?>
						<span class="pdwchs-ltv-card-value pdwchs-ltv-undefined"
							title="<?php echo esc_attr( $pdwchs_ltv_no_value_hint ); ?>">&mdash;</span>
					<?php endif; ?>
				</div>

			</div><!-- .pdwchs-ltv-cards -->

			<p class="pdwchs-ltv-total">
				<span class="pdwchs-ltv-total-value">
					<?php echo esc_html( number_format_i18n( (int) $ltv['total_customers'] ) ); ?>
				</span>
				<span class="pdwchs-ltv-total-label">
					<?php echo esc_html( $pdwchs_ltv_total_label ); ?>
				</span>
			</p>

			<?php
			/*
			 * Inside the has_data branch on purpose: a store with nothing to plot gets no canvas at
			 * all, not an empty grid with axes. The table below is the no-script equivalent, and the
			 * script never removes or repopulates it.
			 */
			?>
			<div class="pdwchs-ltv-chart-wrap">
				<canvas id="pdwchs-ltv-chart" height="260"
					role="img"
					aria-label="<?php echo esc_attr__( 'Number of customers in each lifetime spend bracket. The table below lists the same figures.', 'puredevs-customer-history-for-woocommerce' ); ?>"></canvas>
			</div><!-- .pdwchs-ltv-chart-wrap -->

			<table class="pdwchs-ltv-bracket-table">
				<thead>
					<tr>
						<th scope="col" class="pdwchs-ltv-col-bracket">
							<?php echo esc_html__( 'Spend bracket', 'puredevs-customer-history-for-woocommerce' ); ?>
						</th>
						<th scope="col" class="pdwchs-ltv-col-num">
							<?php echo esc_html__( 'Customers', 'puredevs-customer-history-for-woocommerce' ); ?>
						</th>
						<th scope="col" class="pdwchs-ltv-col-num">
							<?php echo esc_html__( 'Share', 'puredevs-customer-history-for-woocommerce' ); ?>
						</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $ltv['brackets'] as $pdwchs_ltv_bracket ) : ?>
						<tr>
							<td class="pdwchs-ltv-cell-bracket">
								<?php
								echo esc_html(
									PDWCHS_LTV_Panel::pdwchs_bracket_label(
										$pdwchs_ltv_bracket['min'],
										$pdwchs_ltv_bracket['max']
									)
								);
								?>
							</td>
							<td class="pdwchs-ltv-cell-num">
								<?php echo esc_html( number_format_i18n( (int) $pdwchs_ltv_bracket['customers'] ) ); ?>
							</td>
							<td class="pdwchs-ltv-cell-num">
								<?php
								echo esc_html(
									sprintf(
										/* translators: %s: share of customers, already formatted to one decimal place (e.g. "12.4"). */
										__( '%s%%', 'puredevs-customer-history-for-woocommerce' ),
										number_format_i18n( $pdwchs_ltv_bracket['percentage'], 1 )
									)
								);
								?>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>

		<?php endif; ?>

	</div><!-- .pdwchs-ltv-body -->

</div><!-- .pdwchs-ltv-widget -->
