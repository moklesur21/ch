<?php defined( 'ABSPATH' ) or exit; ?>
<div class="pd-woo-main-container">
    <div class="pd-woo-panel">
        <div class="pd-woo-header pd-woo-clearfix">
            <h2 class="pd-woo-float-left"><span class="dashicons dashicons-products"></span>&nbsp;&nbsp;
                <?php echo esc_html__( 'Top Selling Products by This Month', 'puredevs-customer-history-for-woocommerce' ); ?>
            </h2>
            <div class="chart-table-toggle pd-woo-float-right">
                <span class="pd-toggle-btn pd-active-btn" data-target="pd-table-content">
                    <span class="dashicons dashicons-editor-table" title="<?php echo esc_attr__( 'Table View', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
				</span>
                <span class="pd-toggle-btn" data-target="pd-chart-content">
                    <span class="dashicons dashicons-chart-pie" title="<?php echo esc_attr__( 'Chart View', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
                </span>
				<?php if ( pdwchs_is_pro_active() ) { ?>
                <span>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-top-selling-stats' ) ); ?>" class="view-all-link">
                        <span class="dashicons dashicons-admin-site-alt2" title="<?php echo esc_attr__( 'View All', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
                    </a>
				</span>
				<?php } ?>
                <span class="toggle-section toggle-btn pd-woo-toggle-btn" data-toggle-content="section-top-users">
					<span class="dashicons dashicons-arrow-down-alt2" title="<?php echo esc_attr__( 'Click to Expend', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
                </span>
            </div>
        </div>
        <div id="section-top-users" class="pd-woo-clearfix">
            <table class="wp-list-table widefat fixed striped posts pd-table-content">
                <tr>
                    <th><?php echo esc_html__( 'Product Name', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Purchase Count', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                </tr>
		        <?php
				$top_selling_products = PD_Customer_History::pdwchs_top_selling_products_by_month(5);
		        if ( !empty( $top_selling_products ) ) :
                    $t_pro = array();
                    foreach ( $top_selling_products as $key => $val ) {
						$product   = get_post( $key );
                        $t_pro[] = array(
							'productname' => $product->post_title,
							'purchaseamount' => $val,
						);
                        ?>
                        <tr>
                            <td>
                                <a href="<?php the_permalink($key);?>" target="_blank">
                                    <?php echo esc_html( $product->post_title ); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html( $val ); ?></td>
                        </tr>
                    <?php } ?>
                <?php endif;?>
            </table>
            <script>
                jQuery(function($) {
                    var el = document.getElementById('top-selling-products');
                    if ( ! el || typeof Chart === 'undefined' ) { return; }
                    var pdData = <?php echo wp_json_encode( ! empty( $t_pro ) ? $t_pro : array() ); ?>;
                    new Chart(el, {
                        type: 'doughnut',
                        data: {
                            labels: pdData.map(function(r){ return r.productname; }),
                            datasets: [{ data: pdData.map(function(r){ return r.purchaseamount; }), backgroundColor: ['#3b82f6','#ef4444','#10b981','#f59e0b','#8b5cf6','#ec4899','#14b8a6','#f97316','#6366f1','#84cc16'] }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                    });
                });
            </script>
            <div class="pd-chart-content">
                <div style="position:relative;height:280px;"><canvas id="top-selling-products"></canvas></div>
            </div>
        </div>
    </div>
</div>