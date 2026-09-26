<?php
/**
 * Manual tags panel (R15-16C).
 *
 * Required from PDWCHS_Tags_Panel::pdwchs_render_panel().
 * In scope: $tags (array of row objects: id, name, slug), $all_tags (array of
 * row objects: id, name, slug, created_by, created_at), $user_id (int).
 *
 * @since 3.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<section class="pd-ch-panel pdwchs-tags-panel">
	<h2>
		<?php esc_html_e( 'Manual Tags', 'puredevs-customer-history-for-woocommerce' ); ?>
		<span class="pdwchs-tags-count"><?php echo esc_html( count( $tags ) ); ?></span>
	</h2>

	<p class="pdwchs-tags-hint">
		<?php esc_html_e( 'Your own tags, applied by hand. The Behavioral Tags card above is detected automatically.', 'puredevs-customer-history-for-woocommerce' ); ?>
	</p>

	<div class="pdwchs-tags-add">
		<input type="text" class="pdwchs-tag-input" list="pdwchs-tags-datalist" placeholder="<?php esc_attr_e( 'Add or pick a tag...', 'puredevs-customer-history-for-woocommerce' ); ?>" />
		<datalist id="pdwchs-tags-datalist">
			<?php foreach ( $all_tags as $tag_option ) : ?>
				<option value="<?php echo esc_attr( $tag_option->name ); ?>"></option>
			<?php endforeach; ?>
		</datalist>
		<button type="button" class="button button-primary pdwchs-tag-add-btn"><?php esc_html_e( 'Add', 'puredevs-customer-history-for-woocommerce' ); ?></button>
		<div class="pdwchs-crm-error" style="display:none;"></div>
	</div>

	<ul class="pdwchs-tags-list">
		<?php foreach ( $tags as $assigned_tag ) : ?>
			<li class="pdwchs-tag-chip" data-tag-id="<?php echo esc_attr( $assigned_tag->id ); ?>">
				<span class="pdwchs-tag-name"><?php echo esc_html( $assigned_tag->name ); ?></span>
				<button type="button" class="pdwchs-tag-remove" aria-label="<?php esc_attr_e( 'Remove tag', 'puredevs-customer-history-for-woocommerce' ); ?>">&times;</button>
			</li>
		<?php endforeach; ?>
	</ul>

	<p class="pdwchs-tags-empty" <?php echo empty( $tags ) ? '' : 'style="display:none;"'; ?>>
		<?php esc_html_e( 'No tags yet. Add one above.', 'puredevs-customer-history-for-woocommerce' ); ?>
	</p>
</section>
