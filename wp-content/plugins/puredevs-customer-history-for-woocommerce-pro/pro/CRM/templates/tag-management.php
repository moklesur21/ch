<?php
/**
 * Manual tags management page (R15-17).
 *
 * Required from PDWCHS_Tags_Admin_Page::pdwchs_render_page().
 * In scope: $tags (array of row objects: id, name, slug, created_by,
 * created_at, customer_count).
 *
 * @since 3.1.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap pdwchs-tags-admin">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Manual Tags', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

	<div class="pdwchs-tags-create">
		<input type="text" class="pdwchs-tag-new-name" placeholder="<?php esc_attr_e( 'New tag name...', 'puredevs-customer-history-for-woocommerce' ); ?>" />
		<button type="button" class="button button-primary pdwchs-tag-create-btn"><?php esc_html_e( 'Add Tag', 'puredevs-customer-history-for-woocommerce' ); ?></button>
		<div class="pdwchs-crm-error" style="display:none;"></div>
	</div>

	<table class="wp-list-table widefat fixed striped pdwchs-tags-table">
		<thead>
			<tr>
				<th scope="col"><?php esc_html_e( 'Name', 'puredevs-customer-history-for-woocommerce' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Slug', 'puredevs-customer-history-for-woocommerce' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Customers', 'puredevs-customer-history-for-woocommerce' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Created', 'puredevs-customer-history-for-woocommerce' ); ?></th>
				<th scope="col"><?php esc_html_e( 'Actions', 'puredevs-customer-history-for-woocommerce' ); ?></th>
			</tr>
		</thead>
		<tbody>
			<?php foreach ( $tags as $tag_row ) : ?>
				<tr data-tag-id="<?php echo esc_attr( $tag_row->id ); ?>">
					<td class="pdwchs-tag-name-cell"><?php echo esc_html( $tag_row->name ); ?></td>
					<td class="pdwchs-tag-slug-cell"><?php echo esc_html( $tag_row->slug ); ?></td>
					<?php // R15-19: the count links to the list filtered by this tag. A zero count links too — it lands on an empty filtered list, which is correct and informative. ?>
					<td class="pdwchs-tag-count-cell"><a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-customers&tag_filter=' . (int) $tag_row->id ) ); ?>"><?php echo esc_html( number_format_i18n( $tag_row->customer_count ) ); ?></a></td>
					<td class="pdwchs-tag-created-cell">
						<?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime( $tag_row->created_at ) ) ); ?>
					</td>
					<td class="pdwchs-tag-actions-cell">
						<button type="button" class="button-link pdwchs-tag-rename-btn"><?php esc_html_e( 'Rename', 'puredevs-customer-history-for-woocommerce' ); ?></button>
						<button type="button" class="button-link pdwchs-tag-delete-btn"><?php esc_html_e( 'Delete', 'puredevs-customer-history-for-woocommerce' ); ?></button>
						<div class="pdwchs-crm-error pdwchs-tag-row-error" style="display:none;"></div>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
	</table>

	<p class="pdwchs-tags-empty" <?php echo empty( $tags ) ? '' : 'style="display:none;"'; ?>>
		<?php esc_html_e( 'No tags yet. Create one above.', 'puredevs-customer-history-for-woocommerce' ); ?>
	</p>
</div>
