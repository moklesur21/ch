<?php
/**
 * Order-screen intelligence panel.
 *
 * Rendered by PDWCHS_Order_Panel::pdwchs_render_panel(), pulled in with require from
 * inside that method, so the variables below come from the caller's scope.
 *
 * @var array  $panel       Payload from PDWCHS_Order_Panel_Data::pdwchs_get_panel_data().
 * @var string $profile_url Customer profile link, or '' when there is no live customer.
 * @var int    $user_id     Customer user id, already absint()ed.
 *
 * Six rows, each independently suppressed. Three degrade to "Not calculated yet", last
 * seen degrades to "Never seen on site", and lifetime value and predicted next order are
 * omitted outright — for the predicted date, absence means "does not apply" rather than
 * "not computed yet", and an empty row would only invite the reader to wonder what is
 * missing.
 *
 * Every value is escaped here, including the colours out of the label-config arrays:
 * they come from plugin arrays rather than user input, but CLAUDE.md rule 5 has no
 * trusted-source exemption. The segment badge helper is deliberately not used — its
 * <span style="background-color: …"> was never sized for a 250px side column.
 *
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="pdwchs-order-panel">
<?php if ( false === $panel['has_customer'] ) : ?>
	<p class="pdwchs-order-panel-guest">
		<?php esc_html_e( 'This order was placed as a guest.', 'puredevs-customer-history-for-woocommerce' ); ?>
	</p>
<?php else : ?>
	<ul class="pdwchs-order-panel-rows">
		<li class="pdwchs-order-panel-row">
			<span class="pdwchs-order-panel-label"><?php esc_html_e( 'Health', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<?php if ( null !== $panel['health_score'] ) : ?>
				<span class="pdwchs-order-panel-value">
					<?php if ( isset( $panel['health_status']['color'] ) ) : ?>
						<span class="pdwchs-order-panel-dot" style="background-color: <?php echo esc_attr( $panel['health_status']['color'] ); ?>;"></span>
					<?php endif; ?>
					<?php echo esc_html( $panel['health_score'] ); ?>/100
					<?php if ( isset( $panel['health_status']['status'] ) ) : ?>
						<span class="pdwchs-order-panel-sub"><?php echo esc_html( $panel['health_status']['status'] ); ?></span>
					<?php endif; ?>
				</span>
			<?php else : ?>
				<span class="pdwchs-order-panel-value pdwchs-order-panel-empty"><?php esc_html_e( 'Not calculated yet', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<?php endif; ?>
		</li>

		<li class="pdwchs-order-panel-row">
			<span class="pdwchs-order-panel-label"><?php esc_html_e( 'RFM segment', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<?php if ( null !== $panel['rfm_segment'] ) : ?>
				<span class="pdwchs-order-panel-value">
					<?php if ( isset( $panel['rfm_config']['color'] ) ) : ?>
						<span class="pdwchs-order-panel-dot" style="background-color: <?php echo esc_attr( $panel['rfm_config']['color'] ); ?>;"></span>
					<?php endif; ?>
					<?php if ( isset( $panel['rfm_config']['icon'] ) ) : ?>
						<span class="pdwchs-order-panel-icon"><?php echo esc_html( $panel['rfm_config']['icon'] ); ?></span>
					<?php endif; ?>
					<?php echo esc_html( isset( $panel['rfm_config']['name'] ) ? $panel['rfm_config']['name'] : $panel['rfm_segment'] ); ?>
				</span>
			<?php else : ?>
				<span class="pdwchs-order-panel-value pdwchs-order-panel-empty"><?php esc_html_e( 'Not calculated yet', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<?php endif; ?>
		</li>

		<li class="pdwchs-order-panel-row">
			<span class="pdwchs-order-panel-label"><?php esc_html_e( 'Lifecycle', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<?php if ( null !== $panel['lifecycle_stage'] ) : ?>
				<span class="pdwchs-order-panel-value">
					<?php if ( isset( $panel['lifecycle_config']['color'] ) ) : ?>
						<span class="pdwchs-order-panel-dot" style="background-color: <?php echo esc_attr( $panel['lifecycle_config']['color'] ); ?>;"></span>
					<?php endif; ?>
					<?php if ( isset( $panel['lifecycle_config']['icon'] ) ) : ?>
						<span class="pdwchs-order-panel-icon"><?php echo esc_html( $panel['lifecycle_config']['icon'] ); ?></span>
					<?php endif; ?>
					<?php echo esc_html( isset( $panel['lifecycle_config']['name'] ) ? $panel['lifecycle_config']['name'] : $panel['lifecycle_stage'] ); ?>
				</span>
			<?php else : ?>
				<span class="pdwchs-order-panel-value pdwchs-order-panel-empty"><?php esc_html_e( 'Not calculated yet', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<?php endif; ?>
		</li>

		<?php if ( null !== $panel['ltv'] ) : ?>
			<li class="pdwchs-order-panel-row">
				<span class="pdwchs-order-panel-label"><?php esc_html_e( 'Lifetime value', 'puredevs-customer-history-for-woocommerce' ); ?></span>
				<span class="pdwchs-order-panel-value">
					<?php
					// wc_price() returns a <span> wrapper and an HTML currency entity. The side
					// column wants plain text and esc_html() is the only escaper here, so strip
					// the markup and decode the entity — esc_html() then encodes it exactly once.
					echo esc_html(
						html_entity_decode(
							wp_strip_all_tags( wc_price( $panel['ltv'] ) ),
							ENT_QUOTES,
							'UTF-8'
						)
					);
					?>
				</span>
			</li>
		<?php endif; ?>

		<?php
		// Hoisted so the class attribute stays a single escaped echo like every other
		// output in this template.
		$last_seen_class = ( null === $panel['last_seen_days'] )
			? 'pdwchs-order-panel-value pdwchs-order-panel-empty'
			: 'pdwchs-order-panel-value';
		?>
		<li class="pdwchs-order-panel-row">
			<span class="pdwchs-order-panel-label"><?php esc_html_e( 'Last seen', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<span class="<?php echo esc_attr( $last_seen_class ); ?>">
				<?php
				if ( null === $panel['last_seen_days'] ) {
					esc_html_e( 'Never seen on site', 'puredevs-customer-history-for-woocommerce' );
				} elseif ( 0 === $panel['last_seen_days'] ) {
					esc_html_e( 'Today', 'puredevs-customer-history-for-woocommerce' );
				} else {
					printf(
						/* translators: %s: number of days since the customer's last session. */
						esc_html( _n( '%s day ago', '%s days ago', $panel['last_seen_days'], 'puredevs-customer-history-for-woocommerce' ) ),
						esc_html( number_format_i18n( $panel['last_seen_days'] ) )
					);
				}
				?>
			</span>
		</li>

		<?php if ( null !== $panel['predicted_next_ts'] ) : ?>
			<li class="pdwchs-order-panel-row">
				<span class="pdwchs-order-panel-label"><?php esc_html_e( 'Predicted next order', 'puredevs-customer-history-for-woocommerce' ); ?></span>
				<span class="pdwchs-order-panel-value">
					<?php
					// A raw UTC timestamp from R15-14A; wp_date() lands it in the site's
					// timezone and locale, which date() would not.
					echo esc_html( wp_date( get_option( 'date_format' ), $panel['predicted_next_ts'] ) );
					?>
				</span>
			</li>
		<?php endif; ?>
	</ul>

	<?php if ( false === $panel['scores_calculated'] ) : ?>
		<p class="pdwchs-order-panel-note description">
			<?php esc_html_e( 'Scores are calculated nightly.', 'puredevs-customer-history-for-woocommerce' ); ?>
		</p>
	<?php endif; ?>

	<?php if ( '' !== $profile_url ) : ?>
		<p class="pdwchs-order-panel-link">
			<a href="<?php echo esc_url( $profile_url ); ?>"><?php esc_html_e( 'View full profile', 'puredevs-customer-history-for-woocommerce' ); ?></a>
		</p>
	<?php endif; ?>
<?php endif; ?>
</div>
