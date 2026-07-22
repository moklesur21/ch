<?php

defined( 'ABSPATH' ) or exit;

/*
 *  Customer
 */

global $wpdb;
?>
<div id="pd-customer-history">
    <?php
    $user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : 0;
    if ( $user_id > 0 && current_user_can( 'manage_woocommerce' ) ) :
		$data_sync_enabled = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );
        $user = get_user_by( 'id', $user_id ); ?>

        <div id="customer" class="wrap">
            <h1><?php echo esc_html__( 'Customer Details', 'puredevs-customer-history-for-woocommerce' ); ?></h1>
            <div class="pd-ch-dashboard">
                <div class="pd-ch-main-container">
                    <?php
					$customer_orders = wc_get_orders( array(
						'customer_id' => $user_id,
					) );
					if(!empty( $customer_orders )){
                    $orderStatuses = wc_get_order_statuses();
                    if ( !empty( $orderStatuses ) ) :
                        $counter = 1;
                        foreach ( $orderStatuses as $key => $value ) :
							if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
								$records = get_posts( array(
									'numberposts' => 20,
									'meta_key'    => '_customer_user',
									'meta_value'  => $user_id,
									'post_type'   => 'shop_order',
									'post_status' =>  array( str_replace( 'wc-', '', $key ), $key ),
								) );
							}else{
								$args_orders = array(
									'customer_id' => $user_id,
									'post_status' =>  array( str_replace( 'wc-', '', $key ), $key ),
									'limit' => 20,
								);
								$records = wc_get_orders($args_orders);
							}
                            if ( !empty( $records ) ) : ?>
                                <div class="pd-ch-panel">
                                    <h2 class="status-<?php echo esc_attr($key);?>"><span class="dashicons dashicons-cart"></span>&nbsp;&nbsp;<?php echo esc_html( $value ) . ' ' . esc_html__( 'Orders', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
                                    <span class="toggle-section toggle-btn pd-ch-toggle-btn pd-woo-toggle-btn" data-toggle-content="section<?php echo esc_attr( $counter );?>"><span class="dashicons dashicons-arrow-down-alt2" title="<?php echo esc_attr__( 'Click to Expend', 'puredevs-customer-history-for-woocommerce' ); ?>"></span></span>
                                    <div id="section<?php echo esc_attr( $counter );?>" class="pd-ch-clearfix">
                                        <table class="wp-list-table widefat fixed striped posts">
                                            <tr>
                                                <th><?php echo esc_html__( 'Order', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                                                <th><?php echo esc_html__( 'Date', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                                                <th><?php echo esc_html__( 'Items', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                                                <th><?php echo esc_html__( 'Total', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                                                <th><?php echo esc_html__( 'Actions', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                                            </tr>

                                            <?php foreach ( $records as $order ) :
                                                if ( wp_get_post_parent_id( $order->ID ) == 0 ) :
													$order = wc_get_order( $order->ID );
                                                    $order_id = $order->get_id();
                                                    $order_date = $order->get_date_created();
                                                    ?>
                                                    <tr>
                                                        <td><a href="<?php echo esc_url( admin_url() ); ?>post.php?post=<?php echo intval( $order_id ); ?>&action=edit" class="">#<?php echo intval( $order_id ); ?></a></td>
                                                        <td><?php echo esc_html( get_the_date( '', $order_id ) ); ?> <?php echo esc_html( get_the_time( '', $order_id ) ); ?></td>
                                                        <td><?php foreach ( $order->get_items() as $line ) { echo intval( $line['qty'] ) . ' x <a href="' . esc_url( admin_url() ) . 'post.php?post=' . intval( $line['product_id'] ) . '&action=edit" class="">' . esc_html( $line['name'] ) . '</a><br />'; } ?></td>
                                                        <td>
                                                            <?php
                                                            $args = array(
                                                                'span'     => array(
                                                                    'class' => true,
                                                                )
                                                            );
                                                            echo wp_kses( $order->get_formatted_order_total(), $args );
                                                            ?>
                                                        </td>
                                                        <td><a href="<?php echo esc_url( admin_url() ); ?>post.php?post=<?php echo intval( $order_id ); ?>&action=edit" class="button"><?php echo esc_html__( 'View', 'puredevs-customer-history-for-woocommerce' ); ?></a></td>
                                                    </tr>
                                                    </tr>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </table>
                                    </div>
                                </div>
                            <?php endif;?>
                            <?php $counter++;?>
                        <?php endforeach;?>
                    <?php endif;?>
					<?php }else{?>
					<div class="pd-ch-panel"><strong><?php echo esc_html__( 'No orders found!', 'puredevs-customer-history-for-woocommerce' ); ?></strong></div>
					<?php };?>
                    <?php do_action('pdwchs_customer_details_sessions_section', $user->ID); ?>
                    <?php if ( ! pdwchs_is_pro_active() ) { PDWCHS_Upsell::pdwchs_render_locked_section( 'sessions' ); } ?>
                </div>
                <div class="pd-ch-side-container pd-ch-side-container-sidebar">
                    <section class="pd-ch-panel">
                        <h2><?php echo esc_html__( 'Financial Summary', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
                        <div class="customer-info">
                            <table>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'Total Orders', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <?php
										if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
											$order_count = count( get_posts( array(
												'numberposts'   => -1,
												'meta_key'      => '_customer_user',
												'meta_value'    => $user_id,
												'post_type'     => 'shop_order',
												'post_status'   =>  'any',
												'post_parent'   => '0',
											) ) );
										}else{
											$order_count = wc_get_customer_order_count( $user_id );
										}
                                        ?>
                                        <strong><?php echo intval( $order_count ); ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'Pending Orders', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <?php
										if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
											$pending_orders_count = count( get_posts( array(
												'numberposts'   => -1,
												'meta_key'      => '_customer_user',
												'meta_value'    => $user_id,
												'post_type'     => 'shop_order',
												'post_status'   =>  array( 'pending', 'wc-pending'),
												'post_parent'   => '0',
											) ) );
										}else{
											$args_pending_orders = array(
												'customer_id' => $user_id,
												'status' => array( 'pending', 'wc-pending'),
												'limit' => -1,
											);
											$pending_orders_count = count(wc_get_orders($args_pending_orders));
										}
                                        ?>
                                        <strong><?php echo intval( $pending_orders_count ); ?></strong><br />
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'Pending Orders Amount', 'puredevs-customer-history-for-woocommerce' ); ?>:<br />
                                    </td>
                                    <td>
                                        <?php
                                        $pending_orders_total = PD_Customer_History_Customers::pdwchs_woo_get_customer_pending_orders_total( $user_id );
                                        ?>
                                        <strong><?php echo esc_html( get_woocommerce_currency_symbol() ) . esc_html( $pending_orders_total ); ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'Refunded Orders', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <?php
										if( isset( $data_sync_enabled ) && $data_sync_enabled == 'yes' ){
                                        $refunded_orders_count = count( get_posts( array(
                                            'numberposts'   => -1,
                                            'meta_key'      => '_customer_user',
                                            'meta_value'    => $user_id,
                                            'post_type'     => 'shop_order',
                                            'post_status'   =>  array( 'refunded', 'wc-refunded'),
                                            'post_parent'   => '0',
                                        ) ) );
										}else{
											$args_refunded_orders = array(
												'customer_id' => $user_id,
												'status' => array( 'refunded', 'wc-refunded'),
												'limit' => -1,
											);
											$refunded_orders_count = count(wc_get_orders($args_refunded_orders));
										}
                                        ?>
                                        <strong><?php echo intval( $refunded_orders_count ); ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'Refunded Orders Amount', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <?php
                                        $refunded_orders_total = PD_Customer_History_Customers::pdwchs_woo_get_customer_refunded_orders_total( $user->ID );
                                        ?>
                                        <strong><?php echo esc_html( get_woocommerce_currency_symbol() ) . esc_html( $refunded_orders_total ); ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'Orders average', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <?php
                                        $total_spent = PD_Customer_History_Customers::pdwchs_woo_get_customer_total_spent( $user->ID );
                                        ?>
                                        <strong>
                                            <?php
                                            if ( $order_count > 0 ){
                                                echo wp_kses_post( wc_price( $total_spent / $order_count ) );
                                            } else {
                                                echo wp_kses_post( wc_price( $total_spent ) );
                                            }
                                            ?>
                                        </strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'Total Spent', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <strong><?php echo wp_kses_post( wc_price( $total_spent ) ); ?></strong>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="clear"></div>
                    </section>
					
                    <section class="pd-ch-panel">
                        <h2><?php echo esc_html__( 'Personal Information', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
                        <div class="customer-avatar"><?php echo get_avatar( $user_id ); ?></div>
                        <div class="customer-info">
                            <table>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'User ID', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <strong><?php echo intval( $user->ID ); ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'Name', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html( $user->display_name ); ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'Username', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <strong><?php echo esc_html( $user->user_login ); ?></strong>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'Email', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <a href="mailto:<?php echo esc_attr( $user->user_email ); ?>"><?php echo esc_html( $user->user_email ); ?></a>
                                    </td>
                                </tr>
								<?php
								$country = get_user_meta($user_id, 'billing_country', true);
								$country = $country ? $country : get_user_meta($user_id, 'shipping_country', true);
								if( ! empty($country) ) {
								?>
								<tr>
                                    <td>
                                        <?php echo esc_html__( 'Country / Region', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <?php
										$country_name = WC()->countries->countries[ $country ];
										echo esc_html($country_name);
										?> 
                                    </td>
                                </tr>
								<?php 
								}
								?>
                                <tr>
                                    <td>
                                        <?php echo esc_html__( 'Website', 'puredevs-customer-history-for-woocommerce' ); ?>:
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url( $user->user_url ); ?>" target="_blank"><?php echo esc_url( $user->user_url ); ?></a>
                                    </td>
                                </tr>
                            </table>
                        </div>

                        <div class="customer-actions">
                            <table>
                                <tr>
                                    <td>
                                        <span class="dashicons dashicons-edit"></span>&nbsp;<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php echo esc_html__( 'Edit this user', 'puredevs-customer-history-for-woocommerce' ); ?></a>
                                    </td>
                                    <td>&nbsp;</td>
                                    <td></td>
                                </tr>
                            </table>
                        </div>
                        <div class="clear"></div>
                    </section>
					
					<?php do_action('pdwchs_customer_details_intelligence_cards', $user_id); ?>
					<?php if ( ! pdwchs_is_pro_active() ) {
						PDWCHS_Upsell::pdwchs_render_locked_section( 'rfm' );
						PDWCHS_Upsell::pdwchs_render_locked_section( 'health' );
						PDWCHS_Upsell::pdwchs_render_locked_section( 'churn' );
						PDWCHS_Upsell::pdwchs_render_locked_section( 'behavioral' );
						PDWCHS_Upsell::pdwchs_render_locked_section( 'lifecycle' );
					} ?>

                </div>

                <div class="pd-ch-clearfix  "></div>
            </div>

        </div>

    <?php else : ?>

        <div class="wrap">
            <h1><?php echo esc_html__( 'Error', 'puredevs-customer-history-for-woocommerce' ); ?></h1>
            <p><?php echo esc_html__( "I'm sorry, you are not allowed to see this page!", 'puredevs-customer-history-for-woocommerce' ); ?><br /><br /><a href="#"><?php echo esc_html__( 'Back', 'puredevs-customer-history-for-woocommerce' ); ?></a></p>
        </div>

    <?php endif; ?>

</div>
