<?php defined( 'ABSPATH' ) or exit; ?>
<div class="pd-woo-main-container">
    <div class="pd-woo-panel">
        <div class="pd-woo-header pd-woo-clearfix">
            <h2 class="pd-woo-float-left"><span class="dashicons dashicons-groups"></span>&nbsp;&nbsp;
                <?php echo esc_html__( 'Top Customers', 'puredevs-customer-history-for-woocommerce' ); ?>
            </h2>
            <div class="chart-table-toggle pd-woo-float-right">
                <span class="pd-toggle-btn pd-active-btn" data-target="pd-table-content">
                    <span class="dashicons dashicons-editor-table" title="<?php echo esc_attr__( 'Table View', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
				</span>
                <span class="pd-toggle-btn" data-target="pd-chart-content">
                    <span class="dashicons dashicons-chart-pie" title="<?php echo esc_attr__( 'Chart View', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
                </span>
                <span>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-customers' ) ); ?>" class="view-all-link">
                        <span class="dashicons dashicons-admin-site-alt2" title="<?php echo esc_attr__( 'View All', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
                    </a>
				</span>
                <span class="toggle-section toggle-btn pd-woo-toggle-btn" data-toggle-content="section-top-users">
					<span class="dashicons dashicons-arrow-down-alt2" title="<?php echo esc_attr__( 'Click to Expend', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
                </span>
            </div>
        </div>
        <div id="section-top-users" class="pd-woo-clearfix">
            <table class="wp-list-table widefat fixed striped posts pd-table-content">
                <tr>
                    <th><?php echo esc_html__( 'Customer', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Total Amount', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Total Orders', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                </tr>
		        <?php
		        $top_customers = array();
		        $customers     = PD_Customer_History_Customers::pdwchs_woo_get_all_customers( '', '' );
				$data_sync_enabled = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );
		        if ( get_option( 'pd-woo-hide_users_with_no_orders' ) ) {

		            if( !empty( $customers ) ):
                        foreach ( $customers as $key => $user ) {
							if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
								$order_count = count( get_posts( array(
									'numberposts' => - 1,
									'meta_key'    => '_customer_user',
									'meta_value'  => $user->ID,
									'post_type'   => 'shop_order',
									'post_status' => 'any',
									'post_parent' => '0',
								) ) );
							}else{
								$order_count = wc_get_customer_order_count( $user->ID );
							}
                            if ( ! $order_count > 0 ) {
                                unset( $customers[ $key ] );
                            }
                        }
			        endif;
		        }

		        if ( !empty( $customers ) ) :
                    foreach ( $customers as $customer ) {
                        $total_spent = PD_Customer_History_Customers::pdwchs_woo_get_customer_total_spent( $customer->ID );
						if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
							$order_count                 = count( get_posts( array(
								'numberposts' => - 1,
								'meta_key'    => '_customer_user',
								'meta_value'  => $customer->ID,
								'post_type'   => 'shop_order',
								'post_status' => 'any',
								'post_parent' => '0',
							) ) );
						}else{
							$order_count = wc_get_customer_order_count( $customer->ID );
						}
                        $top_customer['user_id']     = $customer->ID;
                        $top_customer['user_name']   = ( $customer->first_name != '' || $customer->last_name != '' ) ? esc_html( $customer->first_name . ' ' . $customer->last_name ) : esc_html( $customer->display_name );
                        $top_customer['total_spent'] = $total_spent;
                        $top_customer['order_count'] = $order_count;
                        array_push( $top_customers, $top_customer );
                    }
		        endif;
		        $spent = array();
		        if ( !empty( $top_customers ) ) :
                    foreach ( $top_customers as $key => $row ) {
                        $spent[ $key ] = $row['total_spent'];
                    }
                    array_multisort( $spent, SORT_DESC, $top_customers );
                    $top_customers = array_slice( $top_customers, 0, 5 );

                    $t_cus = array();
                    foreach ( $top_customers as $customer ) {
                        $t_cus[] = array(
                                'username' => $customer['user_name'],
                                'totalspent' => $customer['total_spent'],
                            );
                        ?>
                        <tr>
                            <td>
                                <span class="dashicons dashicons-admin-users"></span>
                                <a href="admin.php?page=pd-woo-customer&user_id=<?php echo intval( $customer['user_id'] ); ?>"
                                   target="_blank">
                                    <?php echo esc_html( $customer['user_name'] ); ?>
                                </a>
                            </td>
                            <td><?php echo esc_html( get_woocommerce_currency_symbol() ) . esc_html( $customer['total_spent'] ); ?></td>
                            <td><?php echo absint( $customer['order_count'] ); ?></td>
                        </tr>
                    <?php } ?>
                <?php endif;?>
            </table>
            <script>
                jQuery(function($) {
                    var el = document.getElementById('top-customers');
                    if ( ! el || typeof Chart === 'undefined' ) { return; }
                    var pdData = <?php echo wp_json_encode( ! empty( $t_cus ) ? $t_cus : array() ); ?>;
                    new Chart(el, {
                        type: 'doughnut',
                        data: {
                            labels: pdData.map(function(r){ return r.username; }),
                            datasets: [{ data: pdData.map(function(r){ return r.totalspent; }), backgroundColor: ['#3b82f6','#ef4444','#10b981','#f59e0b','#8b5cf6','#ec4899','#14b8a6','#f97316','#6366f1','#84cc16'] }]
                        },
                        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
                    });
                });
            </script>
            <div class="pd-chart-content">
                <div style="position:relative;height:280px;"><canvas id="top-customers"></canvas></div>
            </div>
        </div>
    </div>
</div>
