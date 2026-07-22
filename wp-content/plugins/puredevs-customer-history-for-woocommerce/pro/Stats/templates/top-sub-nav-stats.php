<?php

defined( 'ABSPATH' ) or exit;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only nav; only used to mark the active link, no form data processed.
$pdwchs_current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';

/*
 *	Plugin Top Navigation
 */
?>

<ul class="subsubsub">
	<li>
		<a href="admin.php?page=pd-woo-stats" class="<?php echo $pdwchs_current_page === 'pd-woo-stats' ? 'current' : ''; ?>">
			<?php echo esc_html__( 'Site Pages', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a> |
	</li>
	<li>
		<a href="admin.php?page=pd-woo-search-stats" class="<?php echo $pdwchs_current_page === 'pd-woo-search-stats' ? 'current' : ''; ?>">
			<?php echo esc_html__( 'Keywords', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a> |
	</li>
	<li>
		<a href="admin.php?page=pd-woo-product-view-stats" class="<?php echo $pdwchs_current_page === 'pd-woo-product-view-stats' ? 'current' : ''; ?>">
			<?php echo esc_html__( 'Viewed vs Purchased Ratio', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a> |
	</li>
	<li>
		<a href="admin.php?page=pd-woo-top-selling-stats" class="<?php echo $pdwchs_current_page === 'pd-woo-top-selling-stats' ? 'current' : ''; ?>">
			<?php echo esc_html__( 'Top Selling Products by This Month', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a> |
	</li>
	<li>
		<a href="admin.php?page=pd-woo-product-revenue" class="<?php echo $pdwchs_current_page === 'pd-woo-product-revenue' ? 'current' : ''; ?>">
			<?php echo esc_html__( 'Product Revenue', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a> |
	</li>
	<li>
		<a href="admin.php?page=pd-woo-conversion-funnel-stats" class="<?php echo $pdwchs_current_page === 'pd-woo-conversion-funnel-stats' ? 'current' : ''; ?>">
			<?php echo esc_html__( 'Conversion Funnel', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a> |
	</li>
	<li>
		<a href="admin.php?page=pd-woo-product-correlations" class="<?php echo $pdwchs_current_page === 'pd-woo-product-correlations' ? 'current' : ''; ?>">
			<?php echo esc_html__( 'Frequently Bought Together', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a> |
	</li>
	<?php
	$taxonomies = PDWCHS_Pro_Helpers::pdwchs_get_product_taxonomies();
	if ( ! empty( $taxonomies ) ) :
		foreach ( $taxonomies as $pdwchs_taxonomy ) {
			$pdwchs_tax = get_taxonomy( $pdwchs_taxonomy );
			?>
			<li>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-' . strtolower( $pdwchs_tax->labels->singular_name ) . '-stats&pd_taxonomy=' . $pdwchs_tax->name ) ); ?>" class="<?php echo $pdwchs_current_page === 'pd-woo-' . strtolower( $pdwchs_tax->labels->singular_name ) . '-stats' ? 'current' : ''; ?>">
					<?php echo esc_html( $pdwchs_tax->labels->singular_name . ' View' ); ?>
				</a> |
			</li>
			<?php
		}
	endif;
	?>
	<li>
		<a href="admin.php?page=pd-woo-revenue-patterns" class="<?php echo $pdwchs_current_page === 'pd-woo-revenue-patterns' ? 'current' : ''; ?>">
			<?php echo esc_html__( 'Revenue Patterns', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a> |
	</li>
	<li>
		<a href="admin.php?page=pd-woo-stats-spent" class="<?php echo $pdwchs_current_page === 'pd-woo-stats-spent' ? 'current' : ''; ?>">
			<?php echo esc_html__( 'Total Spent', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a>
	</li>
</ul>
