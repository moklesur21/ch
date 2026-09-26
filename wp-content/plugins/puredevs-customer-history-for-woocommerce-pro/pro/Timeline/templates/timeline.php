<?php
/**
 * Customer activity timeline panel.
 *
 * Rendered by PDWCHS_Timeline_Panel::pdwchs_render_panel() on the H7 seam. The template
 * is pulled in with require from inside that method, so $this is the panel instance
 * here — the row formatter below is a method call, not a global and not a static.
 *
 * Variables in scope from the enclosing method:
 *
 * @var array       $events      Event arrays, newest first.
 * @var bool        $has_more    Whether an older page exists.
 * @var string|null $next_cursor Cursor for the next page.
 * @var int         $user_id     Customer user id, already absint()ed.
 *
 * PDWCHS_Timeline_Data returns raw strings by contract — a search term is whatever
 * the visitor typed and a viewed path is whatever URL they hit. Every row is escaped
 * inside PDWCHS_Timeline_Panel::pdwchs_render_event(), which is also what the AJAX
 * handler calls; there is exactly one place an event becomes markup.
 *
 * @since 3.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// "Carted" sends added_to_cart alone; pdwchs_expand_type() in the handler expands it to
// the three cart-ish types, so the grouping lives in one place instead of a data- attribute.
$filters = array(
	''              => __( 'All', 'puredevs-customer-history-for-woocommerce' ),
	'searched'      => __( 'Searched', 'puredevs-customer-history-for-woocommerce' ),
	'viewed'        => __( 'Viewed', 'puredevs-customer-history-for-woocommerce' ),
	'added_to_cart' => __( 'Carted', 'puredevs-customer-history-for-woocommerce' ),
	'ordered'       => __( 'Ordered', 'puredevs-customer-history-for-woocommerce' ),
);
?>
<div class="pd-ch-panel pdwchs-timeline-panel">
	<h2>
		<span class="dashicons dashicons-backup"></span>&nbsp;<?php esc_html_e( 'Activity Timeline', 'puredevs-customer-history-for-woocommerce' ); ?>
		&nbsp;&nbsp;&nbsp;
		(<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-searches&user_id=' . $user_id ) ); ?>"><?php esc_html_e( 'Search History', 'puredevs-customer-history-for-woocommerce' ); ?></a>
		&middot;
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-sessions&user_id=' . $user_id ) ); ?>"><?php esc_html_e( 'All Sessions', 'puredevs-customer-history-for-woocommerce' ); ?></a>)
	</h2>
	<span class="toggle-section toggle-btn pd-ch-toggle-btn pd-woo-toggle-btn" data-toggle-content="section-timeline">
		<span class="dashicons dashicons-arrow-down-alt2" title="<?php esc_attr_e( 'Click to Expand', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
	</span>

	<div id="section-timeline" class="pd-ch-clearfix">
		<?php if ( empty( $events ) ) : ?>
			<p class="pdwchs-timeline-empty">
				<?php esc_html_e( 'No recorded activity for this customer yet.', 'puredevs-customer-history-for-woocommerce' ); ?>
				<br />
				<span class="description"><?php esc_html_e( 'Activity is recorded from the date this plugin was activated — earlier visits and orders are not shown here.', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			</p>
		<?php else : ?>
			<div class="pdwchs-timeline-filters">
				<?php foreach ( $filters as $key => $label ) : ?>
					<button type="button" class="button pdwchs-timeline-filter<?php echo ( '' === $key ) ? ' is-active' : ''; ?>"
						data-type="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $label ); ?></button>
				<?php endforeach; ?>
			</div>
			<?php wp_nonce_field( 'pdwchs_timeline_nonce', 'pdwchs_timeline_nonce_field' ); ?>

			<ul class="pdwchs-timeline-list">
				<?php foreach ( $events as $event ) : ?>
					<?php echo $this->pdwchs_render_event( $event ); // phpcs:ignore WordPress.Security.EscapeOutput -- every value is escaped inside pdwchs_render_event(). ?>
				<?php endforeach; ?>
			</ul>

			<div class="pdwchs-timeline-footer">
				<button type="button" class="button pdwchs-timeline-more"<?php echo $has_more ? '' : ' style="display:none;"'; ?>
					data-cursor="<?php echo esc_attr( (string) $next_cursor ); ?>">
					<?php esc_html_e( 'Load older', 'puredevs-customer-history-for-woocommerce' ); ?>
				</button>
				<span class="pdwchs-timeline-status-msg" aria-live="polite"></span>
			</div>
		<?php endif; ?>
	</div>
</div>
