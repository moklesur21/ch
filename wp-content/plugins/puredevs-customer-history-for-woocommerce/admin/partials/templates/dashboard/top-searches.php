<?php defined( 'ABSPATH' ) or exit; ?>
<div class="pd-woo-main-container">
    <div class="pd-woo-panel ">
        <div class="pd-woo-header pd-woo-clearfix">
            <h2 class="pd-woo-float-left"><span class="dashicons dashicons-search"></span>&nbsp;&nbsp;
                <?php echo esc_html__( 'Top Searches', 'puredevs-customer-history-for-woocommerce' ); ?>
            </h2>
            <div class="chart-table-toggle pd-woo-float-right">
                <span class="pd-toggle-btn pd-active-btn" data-target="pd-table-content">
                    <span class="dashicons dashicons-editor-table" title="<?php echo esc_attr__( 'Table View', 'puredevs-customer-history-for-woocommerce' );?>"></span>
				</span>
                <span class="pd-toggle-btn" data-target="pd-chart-content">
                    <span class="dashicons dashicons-chart-pie" title="<?php echo esc_attr__( 'Chart View', 'puredevs-customer-history-for-woocommerce' );?>"></span>
                </span>
                <span>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . ( pdwchs_is_pro_active() ? 'pd-woo-searches' : 'pd-woo-search-analytics' ) ) ); ?>" class="view-all-link">
                        <span class="dashicons dashicons-admin-site-alt2" title="<?php echo esc_attr__( 'View All', 'puredevs-customer-history-for-woocommerce' );?>"></span>
                    </a>
				</span>
                <span class="toggle-section toggle-btn pd-woo-toggle-btn" data-toggle-content="section-top-searches">
					<span class="dashicons dashicons-arrow-down-alt2" title="<?php echo esc_attr__( 'Click to Expend', 'puredevs-customer-history-for-woocommerce' );?>"></span>
                </span>
            </div>
        </div>


        <div id="section-top-searches" class="pd-woo-clearfix">
            <table class="wp-list-table widefat fixed striped posts pd-table-content">
                <tr>
                    <th><?php echo esc_html__( 'Keyword', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Count', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                </tr>
				<?php
                $searches = PDWCHS_Search_Stats_Data::get_keyword_counts( array( 'limit' => 5 ) );
                $top_search = array();
                if ( !empty( $searches ) ) :
                    foreach ( $searches as $search ) {
                        $url_array = explode('::', $search->url);
                        if ( isset( $url_array['1'] ) && $url_array['1'] == 'search' && !empty( $url_array['2'] ) ) {
                            $url = $url_array['2'];
                        }
                        if ( !empty( $url ) ) {
                            $top_search[] = array(
                                'searchTerm' => $url,
                                'searchQty' => $search->qta,
                            );
                            ?>
                            <tr>
                                <td><?php echo esc_html( $url ); ?></td>
                                <td><?php echo absint( $search->qta ); ?></td>
                            </tr>
                        <?php }
                    }
                endif;
				?>
            </table>
            <script>
                jQuery(function($) {
                    var el = document.getElementById('top-searches');
                    if ( ! el || typeof Chart === 'undefined' ) { return; }
                    var pdData = <?php echo wp_json_encode( ! empty( $top_search ) ? $top_search : array() ); ?>;
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
                <div style="position:relative;height:280px;"><canvas id="top-searches"></canvas></div>
            </div>
        </div>
    </div>
</div>
