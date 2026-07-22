<?php
defined( 'ABSPATH' ) or exit;

/**
 * Comparison function to sort objects by their 'qta' property in descending order.
 *
 * Used with sorting functions like usort to order items from highest to lowest 'qta'.
 *
 * @param object $a First object to compare, expected to have a 'qta' property.
 * @param object $b Second object to compare, expected to have a 'qta' property.
 * @return int Returns 1 if $a->qta is less than $b->qta, -1 if greater, or 0 if equal.
 */
function pdwchs_sortByqta($a, $b) {
    if ($a->qta < $b->qta) {
        return 1;
    } elseif ($a->qta > $b->qta) {
        return -1;
    }
    return 0;
}
$_site_pages = PDWCHS_Search_Stats_Data::get_url_counts();
$_viewed_products = PDWCHS_Helper::pdwchs_get_view_products( $_site_pages );
usort($_viewed_products, 'pdwchs_sortByqta');
?>
<div class="pd-woo-main-container">
	<div class="pd-woo-panel">
		<div class="pd-woo-header pd-woo-clearfix">
			<h2 class="pd-woo-float-left"><span class="dashicons dashicons-flag"></span>&nbsp;&nbsp;
				<?php echo esc_html__( 'Most viewed products', 'puredevs-customer-history-for-woocommerce' ); ?>
			</h2>
			<div class="chart-table-toggle pd-woo-float-right">
				<span class="pd-toggle-btn pd-active-btn" data-target="pd-table-content">
					<span class="dashicons dashicons-editor-table" title="<?php echo esc_attr__( 'Table View', 'puredevs-customer-history-for-woocommerce' );?>"></span>
				</span>
				<span class="pd-toggle-btn" data-target="pd-chart-content">
					<span class="dashicons dashicons-chart-pie" title="<?php echo esc_attr__( 'Chart View', 'puredevs-customer-history-for-woocommerce' );?>"></span>
				</span>
				<span>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . ( pdwchs_is_pro_active() ? 'pd-woo-product-view-stats' : 'pd-woo-search-analytics' ) ) ); ?>" class="view-all-link">
                        <span class="dashicons dashicons-admin-site-alt2" title="<?php echo esc_attr__( 'View All', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
                    </a>
				</span>
				<span class="toggle-section toggle-btn pd-woo-toggle-btn" data-toggle-content="section-top-products">
					<span class="dashicons dashicons-arrow-down-alt2" title="<?php echo esc_attr__( 'Click to Expend', 'puredevs-customer-history-for-woocommerce' );?>"></span>
				</span>
			</div>
		</div>
		<div id="section-top-products" class="pd-woo-clearfix">
			<table class="wp-list-table widefat fixed striped posts pd-table-content">
				<tr>
					<th><?php echo esc_html__( 'Product Name', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					<th><?php echo esc_html__( 'View count', 'puredevs-customer-history-for-woocommerce' ); ?></th>
				</tr>
				<?php
				$top_terms = array();
				if ( !empty( $_viewed_products ) ) {
					$_viewed_products = array_slice($_viewed_products,0,5);
					foreach ($_viewed_products as $_viewed_product) {
						$search_url = explode( '/', $_viewed_product->url );
						$posts = get_posts(array(
							'name' => end( $search_url ),
							'posts_per_page' => 1,
							'post_type' => 'product',
							'post_status' => 'publish'
						));
						if(!empty($posts)){
							$top_terms[] = array(
								'searchTerm' => $posts[0]->post_title,
								'searchQty' => $_viewed_product->qta,
							);
							?>
							<tr>
								<td><a href="<?php echo esc_url( get_permalink( $posts[0] ) ); ?>" target="_blank"><?php echo esc_html( $posts[0]->post_title ); ?></a></td>
								<td><?php echo esc_html( $_viewed_product->qta ); ?></td>
							</tr>
							<?php
						}
					}
				}
				?>
			</table>
			<script>
				jQuery(function($) {
					var el = document.getElementById('top-view-products');
					if ( ! el || typeof Chart === 'undefined' ) { return; }
					var pdData = <?php echo wp_json_encode( ! empty( $top_terms ) ? $top_terms : array() ); ?>;
					new Chart(el, {
						type: 'doughnut',
						data: {
							labels: pdData.map(function(r){ return r.searchTerm; }),
							datasets: [{ data: pdData.map(function(r){ return r.searchQty; }), backgroundColor: ['#3b82f6','#ef4444','#10b981','#f59e0b','#8b5cf6','#ec4899','#14b8a6','#f97316','#6366f1','#84cc16'] }]
						},
						options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
					});
				});
			</script>
			<div class="pd-chart-content">
				<div style="position:relative;height:280px;"><canvas id="top-view-products" class="taxonomy-terms"></canvas></div>
			</div>
		</div>
	</div>
</div>
