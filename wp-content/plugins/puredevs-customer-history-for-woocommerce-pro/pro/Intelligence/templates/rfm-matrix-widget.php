<?php
/**
 * RFM Matrix Heat-Map Widget Template
 *
 * Server-rendered from the R15-25A payload: a 5 × 5 grid of customer counts, recency down the rows
 * and frequency across the columns, each cell shaded by how full it is relative to the largest.
 * Complete and correct with JavaScript disabled — there is no canvas, no endpoint and no script.
 *
 * Every cell in the grid is rendered, including the ones nobody falls into. They are the shape of the
 * distribution; dropping them would make a bimodal customer base look continuous.
 *
 * Rows run R5 → R1 and columns F1 → F5, which puts the best customers in the top-right corner the way
 * every published RFM matrix does. That order comes from the payload itself — the data class builds
 * the array descending — so this template must never sort it.
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/pro/Intelligence/templates
 * @since      3.1.2
 *
 * @var array $matrix Payload from PDWCHS_RFM_Matrix_Data::pdwchs_get_matrix().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * Band wording reused from PD_Customer_History_RFM's describers (RFM.php:602-660) rather than
 * invented here, so the tooltip vocabulary matches the RFM card on a customer's profile. The
 * describers themselves cannot be called: their signatures need a per-customer days-since and order
 * count, which a population aggregate does not have.
 */
$pdwchs_rfm_recency_bands = array(
	5 => __( 'Very recent', 'puredevs-customer-history-for-woocommerce' ),
	4 => __( 'Recent', 'puredevs-customer-history-for-woocommerce' ),
	3 => __( 'Fairly recent', 'puredevs-customer-history-for-woocommerce' ),
	2 => __( 'Some time ago', 'puredevs-customer-history-for-woocommerce' ),
	1 => __( 'Long time ago', 'puredevs-customer-history-for-woocommerce' ),
);

$pdwchs_rfm_frequency_bands = array(
	5 => __( 'Very frequent buyer', 'puredevs-customer-history-for-woocommerce' ),
	4 => __( 'Regular buyer', 'puredevs-customer-history-for-woocommerce' ),
	3 => __( 'Occasional buyer', 'puredevs-customer-history-for-woocommerce' ),
	2 => __( 'Repeat buyer', 'puredevs-customer-history-for-woocommerce' ),
	1 => __( 'One-time buyer', 'puredevs-customer-history-for-woocommerce' ),
);
?>

<div class="pd-woo-panel pdwchs-rfm-matrix-widget">

	<div class="pd-woo-panel-header pdwchs-rfm-matrix-header">
		<h2><?php echo esc_html__( 'RFM matrix', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
	</div><!-- .pdwchs-rfm-matrix-header -->

	<div class="pd-woo-panel-body pdwchs-rfm-matrix-body">

		<?php if ( null === $matrix['calculated'] ) : ?>

			<?php /* State 1: the RFM batch has never run. Say so; do not fabricate a timestamp. */ ?>
			<p class="pdwchs-rfm-matrix-empty">
				<?php echo esc_html__( 'RFM scores have not been calculated yet. Scores are recalculated nightly, and the matrix appears after the first run.', 'puredevs-customer-history-for-woocommerce' ); ?>
			</p>

		<?php elseif ( 0 === (int) $matrix['total'] ) : ?>

			<?php /* State 2: the batch has run, but nobody has a recency and frequency band yet. */ ?>
			<p class="pdwchs-rfm-matrix-empty">
				<?php echo esc_html__( 'No customers have been scored yet. The matrix fills in once customers start completing orders.', 'puredevs-customer-history-for-woocommerce' ); ?>
			</p>

		<?php else : ?>

			<p class="pdwchs-rfm-matrix-intro">
				<?php echo esc_html__( 'How many customers sit in each recency and frequency band. Recency runs down the rows, frequency across the columns, so your best customers are top-right.', 'puredevs-customer-history-for-woocommerce' ); ?>
			</p>

			<div class="pdwchs-rfm-matrix-scroll">
				<table class="widefat striped pdwchs-rfm-matrix">
					<thead>
						<tr>
							<th class="pdwchs-rfm-matrix-corner" scope="col">
								<span class="screen-reader-text">
									<?php echo esc_html__( 'Recency band', 'puredevs-customer-history-for-woocommerce' ); ?>
								</span>
							</th>
							<?php foreach ( array_keys( reset( $matrix['grid'] ) ) as $pdwchs_f ) : ?>
								<th scope="col" class="pdwchs-rfm-matrix-axis"
									title="<?php echo esc_attr( isset( $pdwchs_rfm_frequency_bands[ $pdwchs_f ] ) ? $pdwchs_rfm_frequency_bands[ $pdwchs_f ] : '' ); ?>">
									<?php
									/* translators: %d: RFM frequency score, 1 to 5. */
									echo esc_html( sprintf( __( 'F%d', 'puredevs-customer-history-for-woocommerce' ), (int) $pdwchs_f ) );
									?>
								</th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $matrix['grid'] as $pdwchs_r => $pdwchs_row ) : ?>
							<tr>
								<th scope="row" class="pdwchs-rfm-matrix-axis"
									title="<?php echo esc_attr( isset( $pdwchs_rfm_recency_bands[ $pdwchs_r ] ) ? $pdwchs_rfm_recency_bands[ $pdwchs_r ] : '' ); ?>">
									<?php
									/* translators: %d: RFM recency score, 1 to 5. */
									echo esc_html( sprintf( __( 'R%d', 'puredevs-customer-history-for-woocommerce' ), (int) $pdwchs_r ) );
									?>
								</th>
								<?php
								foreach ( $pdwchs_row as $pdwchs_f => $pdwchs_count ) :
									$pdwchs_count = (int) $pdwchs_count;

									/*
									 * Ten buckets, min( 9, … ) so a cell equal to max lands in d9 rather
									 * than a non-existent d10. The max > 0 test is defence in depth: the
									 * empty state above already means this branch is unreachable on a
									 * store with nothing scored.
									 */
									$pdwchs_shade = ( (int) $matrix['max'] > 0 )
										? min( 9, (int) floor( $pdwchs_count / (int) $matrix['max'] * 10 ) )
										: 0;

									$pdwchs_cell_title = sprintf(
										/* translators: 1: recency band description, 2: frequency band description, 3: number of customers. */
										__( '%1$s · %2$s — %3$s customers', 'puredevs-customer-history-for-woocommerce' ),
										isset( $pdwchs_rfm_recency_bands[ $pdwchs_r ] ) ? $pdwchs_rfm_recency_bands[ $pdwchs_r ] : '',
										isset( $pdwchs_rfm_frequency_bands[ $pdwchs_f ] ) ? $pdwchs_rfm_frequency_bands[ $pdwchs_f ] : '',
										number_format_i18n( $pdwchs_count )
									);
									?>
									<td class="pdwchs-rfm-matrix-cell pdwchs-rfm-matrix-cell--d<?php echo esc_attr( $pdwchs_shade ); ?>"
										title="<?php echo esc_attr( $pdwchs_cell_title ); ?>">
										<?php echo esc_html( number_format_i18n( $pdwchs_count ) ); ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div><!-- .pdwchs-rfm-matrix-scroll -->

			<?php if ( (int) $matrix['unscored'] > 0 ) : ?>
				<p class="pdwchs-rfm-matrix-footnote">
					<?php
					printf(
						/* translators: %s: number of customers with no recency or frequency band. */
						esc_html__( '%s customers are not yet scored — a customer with no completed order has no recency or frequency band.', 'puredevs-customer-history-for-woocommerce' ),
						esc_html( number_format_i18n( (int) $matrix['unscored'] ) )
					);
					?>
				</p>
			<?php endif; ?>

			<p class="pdwchs-rfm-matrix-calculated">
				<?php
				printf(
					/* translators: %s: date and time the RFM scores were last calculated. */
					esc_html__( 'Scores last calculated %s.', 'puredevs-customer-history-for-woocommerce' ),
					esc_html(
						date_i18n(
							get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
							strtotime( $matrix['calculated'] )
						)
					)
				);
				?>
			</p>

		<?php endif; ?>

	</div><!-- .pdwchs-rfm-matrix-body -->

</div><!-- .pdwchs-rfm-matrix-widget -->
