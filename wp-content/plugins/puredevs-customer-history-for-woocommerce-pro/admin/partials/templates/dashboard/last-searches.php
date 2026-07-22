<?php defined( 'ABSPATH' ) or exit; ?>
<div class="pd-woo-main-container">
    <div class="pd-woo-panel">
        <div class="pd-woo-header pd-woo-clearfix">
            <h2 class="pd-woo-float-left"><span class="dashicons dashicons-search"></span>&nbsp;&nbsp;
				<?php echo esc_html__( 'Latest Searches', 'puredevs-customer-history-for-woocommerce' ); ?>
            </h2>
            <div class="chart-table-toggle pd-woo-float-right">
                <span>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=' . ( pdwchs_is_pro_active() ? 'pd-woo-searches' : 'pd-woo-search-analytics' ) ) ); ?>" class="view-all-link">
                        <span class="dashicons dashicons-admin-site-alt2" title="<?php echo esc_attr__( 'View All', 'puredevs-customer-history-for-woocommerce' );?>"></span>
                    </a>
				</span>
                <span class="toggle-section toggle-btn pd-woo-toggle-btn" data-toggle-content="section-last-searches"><span class="dashicons dashicons-arrow-down-alt2" title="<?php echo esc_attr__( 'Click to Expend', 'puredevs-customer-history-for-woocommerce' );?>"></span></span>
            </div>
        </div>
        <div id="section-last-searches" class="pd-woo-clearfix">
            <table class="wp-list-table widefat fixed striped posts pd-table-content">
                <tr>
                    <th><?php echo esc_html__( 'Keyword', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Date', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                </tr>
				<?php
                $searches = PDWCHS_Search_Stats_Data::get_last_searches( 5 );
                if ( !empty( $searches ) ) :
                    foreach ( $searches as $search ) {
                        $url = '';
                        $url_array = explode('::', $search->url);
                        if ( isset( $url_array[1] ) && $url_array[1] == 'search' && !empty( $url_array[2] ) ) {
                            $url = $url_array[2];
                        }
                        if ( !empty( $url ) ) {
                            ?>
                            <tr>
                                <td><?php echo esc_html( $url ); ?></td>
                                <td><?php echo esc_html( $search->reg_date ); ?></td>
                            </tr>
                        <?php }
                    }
                endif;
				?>
            </table>
        </div>
    </div>
</div>
