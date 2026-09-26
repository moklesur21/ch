<?php
/**
 * Retention Metrics Dashboard Panel Template
 *
 * Server-rendered from the R15-15A payload: the expected-CLV card, the current-window churn
 * rate and the churn series as a table. Complete and correct with JavaScript disabled.
 *
 * Every undefined state renders an em-dash with an explanation. A zero churn rate reads as
 * perfect retention and a zero CLV as a worthless customer base; both would be wrong and both
 * would look plausible, so neither is ever printed for a missing value.
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/pro/Analytics/templates
 * @since      3.1.0
 *
 * @var array $retention Payload from PDWCHS_Retention_Metrics::pdwchs_get_retention().
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * The payload's window_label is an untranslated machine-readable descriptor by design — R15-15A
 * leaves every display string to the presentation layer. The adjectival forms live on the panel
 * class so the selector's options, this heading and the list the script rewrites labels from all
 * read the same source; an unrecognised slug falls back to the payload's own label.
 */
$pdwchs_retention_windows = PDWCHS_Retention_Panel::pdwchs_preset_labels();

$pdwchs_retention_window = isset( $pdwchs_retention_windows[ $retention['preset'] ] )
	? $pdwchs_retention_windows[ $retention['preset'] ]
	: $retention['window_label'];

// The panel states its own basis because it sits directly below a revenue widget carrying its
// own date-range picker, and it is not driven by that picker.
$pdwchs_retention_heading = sprintf(
	/* translators: %s: rolling window length, e.g. "30-day". */
	__( 'Retention · %s window', 'puredevs-customer-history-for-woocommerce' ),
	$pdwchs_retention_window
);

$pdwchs_retention_clv_label = sprintf(
	/* translators: %s: rolling window length, e.g. "30-day". */
	__( 'Expected CLV · %s basis', 'puredevs-customer-history-for-woocommerce' ),
	$pdwchs_retention_window
);

$pdwchs_retention_no_base_hint = __( 'No customers ordered in the window before this one, so there is no base to measure churn against.', 'puredevs-customer-history-for-woocommerce' );
$pdwchs_retention_no_churn_hint = __( 'No customers churned in this window, so an expected lifetime cannot be derived.', 'puredevs-customer-history-for-woocommerce' );
$pdwchs_retention_clv_base_hint = __( 'There were no customers in the previous window, so an expected lifetime cannot be derived.', 'puredevs-customer-history-for-woocommerce' );
?>

<div class="pd-woo-panel pdwchs-retention-widget">

	<div class="pd-woo-panel-header pdwchs-retention-header">
		<div class="pdwchs-retention-title-row">
			<h2><?php echo esc_html( $pdwchs_retention_heading ); ?></h2>
		</div>

		<div class="pdwchs-retention-controls">
			<label class="screen-reader-text" for="pdwchs-retention-preset">
				<?php echo esc_html__( 'Window', 'puredevs-customer-history-for-woocommerce' ); ?>
			</label>
			<select id="pdwchs-retention-preset" class="pdwchs-retention-preset">
				<?php foreach ( $pdwchs_retention_windows as $pdwchs_retention_slug => $pdwchs_retention_option ) : ?>
					<option value="<?php echo esc_attr( $pdwchs_retention_slug ); ?>"
						<?php selected( $retention['preset'], $pdwchs_retention_slug ); ?>>
						<?php echo esc_html( $pdwchs_retention_option ); ?>
					</option>
				<?php endforeach; ?>
			</select>
		</div><!-- .pdwchs-retention-controls -->

	</div><!-- .pdwchs-retention-header -->

	<div class="pd-woo-panel-body pdwchs-retention-body">

		<?php if ( empty( $retention['has_data'] ) ) : ?>

			<p class="pdwchs-retention-empty">
				<?php echo esc_html__( 'No completed or processing orders from registered customers yet, so there is nothing to measure retention against.', 'puredevs-customer-history-for-woocommerce' ); ?>
			</p>

		<?php else : ?>

			<div class="pdwchs-retention-cards">

				<div class="pdwchs-retention-card">
					<span class="pdwchs-retention-card-label">
						<?php echo esc_html( $pdwchs_retention_clv_label ); ?>
					</span>
					<?php if ( 'ok' === $retention['clv_state'] && null !== $retention['expected_clv'] ) : ?>
						<span class="pdwchs-retention-card-value">
							<?php echo wp_kses_post( wc_price( $retention['expected_clv'] ) ); ?>
						</span>
					<?php else : ?>
						<span class="pdwchs-retention-card-value pdwchs-retention-undefined"
							title="<?php echo esc_attr( 'no_churn' === $retention['clv_state'] ? $pdwchs_retention_no_churn_hint : $pdwchs_retention_clv_base_hint ); ?>">&mdash;</span>
					<?php endif; ?>
				</div>

				<div class="pdwchs-retention-card">
					<span class="pdwchs-retention-card-label">
						<?php echo esc_html__( 'Churn rate', 'puredevs-customer-history-for-woocommerce' ); ?>
					</span>
					<?php if ( 'ok' === $retention['current_state'] && null !== $retention['current_churn_rate'] ) : ?>
						<span class="pdwchs-retention-card-value">
							<?php
							echo esc_html(
								sprintf(
									/* translators: %s: churn rate, already formatted to one decimal place (e.g. "12.4"). */
									__( '%s%%', 'puredevs-customer-history-for-woocommerce' ),
									number_format_i18n( $retention['current_churn_rate'], 1 )
								)
							);
							?>
						</span>
					<?php else : ?>
						<span class="pdwchs-retention-card-value pdwchs-retention-undefined"
							title="<?php echo esc_attr( $pdwchs_retention_no_base_hint ); ?>">&mdash;</span>
					<?php endif; ?>
				</div>

			</div><!-- .pdwchs-retention-cards -->

			<p class="pdwchs-retention-note">
				<?php echo esc_html__( 'Registered customers only.', 'puredevs-customer-history-for-woocommerce' ); ?>
			</p>

			<div class="pdwchs-retention-chart-wrap">
				<canvas id="pdwchs-retention-chart"
					role="img"
					aria-label="<?php echo esc_attr__( 'Churn rate over the selected window, one point per period.', 'puredevs-customer-history-for-woocommerce' ); ?>"></canvas>
			</div>

			<div class="pdwchs-retention-error" style="display:none;">
				<p class="error">
					<?php echo esc_html__( 'Could not load retention data. Please try again.', 'puredevs-customer-history-for-woocommerce' ); ?>
				</p>
			</div>


			<?php if ( ! empty( $retention['series'] ) ) : ?>

				<table class="pdwchs-retention-series-table">
					<thead>
						<tr>
							<th scope="col" class="pdwchs-retention-col-period">
								<?php echo esc_html__( 'Period', 'puredevs-customer-history-for-woocommerce' ); ?>
							</th>
							<th scope="col" class="pdwchs-retention-col-num">
								<?php echo esc_html__( 'Base', 'puredevs-customer-history-for-woocommerce' ); ?>
							</th>
							<th scope="col" class="pdwchs-retention-col-num">
								<?php echo esc_html__( 'Churned', 'puredevs-customer-history-for-woocommerce' ); ?>
							</th>
							<th scope="col" class="pdwchs-retention-col-num">
								<?php echo esc_html__( 'Rate', 'puredevs-customer-history-for-woocommerce' ); ?>
							</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $retention['series'] as $pdwchs_retention_point ) : ?>
							<tr>
								<td class="pdwchs-retention-cell-period">
									<?php echo esc_html( $pdwchs_retention_point['label'] ); ?>
								</td>
								<td class="pdwchs-retention-cell-num">
									<?php echo esc_html( number_format_i18n( (int) $pdwchs_retention_point['base'] ) ); ?>
								</td>
								<td class="pdwchs-retention-cell-num">
									<?php echo esc_html( number_format_i18n( (int) $pdwchs_retention_point['churned'] ) ); ?>
								</td>
								<?php if ( 'ok' === $pdwchs_retention_point['state'] && null !== $pdwchs_retention_point['churn_rate'] ) : ?>
									<td class="pdwchs-retention-cell-num">
										<?php
										echo esc_html(
											sprintf(
												/* translators: %s: churn rate, already formatted to one decimal place (e.g. "12.4"). */
												__( '%s%%', 'puredevs-customer-history-for-woocommerce' ),
												number_format_i18n( $pdwchs_retention_point['churn_rate'], 1 )
											)
										);
										?>
									</td>
								<?php else : ?>
									<td class="pdwchs-retention-cell-num pdwchs-retention-undefined"
										title="<?php echo esc_attr( $pdwchs_retention_no_base_hint ); ?>">&mdash;</td>
								<?php endif; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

			<?php endif; ?>

		<?php endif; ?>

	</div><!-- .pdwchs-retention-body -->

</div><!-- .pdwchs-retention-widget -->
