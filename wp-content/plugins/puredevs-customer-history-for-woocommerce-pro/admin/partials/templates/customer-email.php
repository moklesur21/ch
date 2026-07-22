<?php

defined( 'ABSPATH' ) or exit;

/*
 *  Customer Email Interface
 */

global $wpdb;
?>
<div id="pd-customer-history">
    <?php
    $user_id = isset( $_GET['user_id'] ) ? sanitize_text_field( $_GET['user_id'] ) : 0;
    if ( $user_id > 0 && current_user_can( 'manage_woocommerce' ) ) :
        $user = get_user_by( 'id', $user_id );

        // Handle email form submission
        if ( isset( $_POST['pdwchs_send_email_nonce'] ) && wp_verify_nonce( $_POST['pdwchs_send_email_nonce'], 'pdwchs_send_customer_email' ) ) {
            $email_subject = isset( $_POST['email_subject'] ) ? $_POST['email_subject'] : '';
            $email_message = isset( $_POST['email_message'] ) ? $_POST['email_message'] : '';

            // Validate email data
            $validation = PD_Customer_History_Email::pdwchs_validate_email_data( $user_id, $email_subject, $email_message );

            if ( $validation['valid'] ) {
                // Send the email using our custom class
                $email_sent = PD_Customer_History_Email::pdwchs_send_customer_email( $user_id, $email_subject, $email_message );

                if ( $email_sent ) {
                    echo '<div class="notice notice-success is-dismissible"><p><strong>' . esc_html__( 'Email sent successfully!', 'puredevs-customer-history-for-woocommerce' ) . '</strong></p></div>';
                } else {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html__( 'Failed to send email. Please try again.', 'puredevs-customer-history-for-woocommerce' ) . '</strong></p></div>';
                }
            } else {
                // Display validation errors
                foreach ( $validation['errors'] as $error ) {
                    echo '<div class="notice notice-error is-dismissible"><p><strong>' . esc_html( $error ) . '</strong></p></div>';
                }
            }
        }
        ?>

        <div id="customer-email" class="wrap">
            <h1><?php echo esc_html__( 'Send Email to Customer', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

            <div class="pd-ch-dashboard">
                <div class="pd-ch-main-container">
                    <div class="pd-ch-panel">
                        <h2><span class="dashicons dashicons-email"></span>&nbsp;<?php echo esc_html__( 'Email Form', 'puredevs-customer-history-for-woocommerce' ); ?></h2>

                        <form method="post" action="">
                            <?php wp_nonce_field( 'pdwchs_send_customer_email', 'pdwchs_send_email_nonce' ); ?>

                            <table class="form-table">
                                <tr>
                                    <th scope="row">
                                        <label for="email_to"><?php echo esc_html__( 'To:', 'puredevs-customer-history-for-woocommerce' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="email" id="email_to" class="regular-text" value="<?php echo esc_attr( $user->user_email ); ?>" readonly />
                                        <p class="description"><?php echo esc_html__( 'Customer email address (read-only)', 'puredevs-customer-history-for-woocommerce' ); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="email_subject"><?php echo esc_html__( 'Subject:', 'puredevs-customer-history-for-woocommerce' ); ?></label>
                                    </th>
                                    <td>
                                        <input type="text" id="email_subject" name="email_subject" class="regular-text" required />
                                        <p class="description"><?php echo esc_html__( 'Enter the email subject', 'puredevs-customer-history-for-woocommerce' ); ?></p>
                                    </td>
                                </tr>

                                <tr>
                                    <th scope="row">
                                        <label for="email_message"><?php echo esc_html__( 'Message:', 'puredevs-customer-history-for-woocommerce' ); ?></label>
                                    </th>
                                    <td>
                                        <?php
                                        $content = '';
                                        $editor_id = 'email_message';
                                        $settings = array(
                                            'textarea_name' => 'email_message',
                                            'media_buttons' => false,
                                            'textarea_rows' => 10,
                                            'teeny' => true,
                                            'quicktags' => false,
                                        );
                                        wp_editor( $content, $editor_id, $settings );
                                        ?>
                                        <p class="description"><?php echo esc_html__( 'Enter your email message', 'puredevs-customer-history-for-woocommerce' ); ?></p>
                                    </td>
                                </tr>
                            </table>

                            <p class="submit">
                                <input type="submit" name="send_email" id="send_email" class="button button-primary" value="<?php echo esc_attr__( 'Send Email', 'puredevs-customer-history-for-woocommerce' ); ?>" />
                                <a href="admin.php?page=pd-woo-customer&user_id=<?php echo intval( $user_id ); ?>" class="button"><?php echo esc_html__( 'Back to Customer Details', 'puredevs-customer-history-for-woocommerce' ); ?></a>
								<a href="admin.php?page=pd-woo-customers" class="button"><?php echo esc_html__( 'Back to Customer List', 'puredevs-customer-history-for-woocommerce' ); ?></a>
                            </p>
                        </form>
                    </div>
                </div>

                <div class="pd-ch-side-container pd-ch-side-container-sidebar">
                    <section class="pd-ch-panel">
                        <h2><?php echo esc_html__( 'Customer Information', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
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
                                        <span class="dashicons dashicons-visibility"></span>&nbsp;<a href="admin.php?page=pd-woo-customer&user_id=<?php echo intval( $user->ID ); ?>"><?php echo esc_html__( 'View Customer Details', 'puredevs-customer-history-for-woocommerce' ); ?></a>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <span class="dashicons dashicons-edit"></span>&nbsp;<a href="<?php echo esc_url( get_edit_user_link( $user->ID ) ); ?>"><?php echo esc_html__( 'Edit this user', 'puredevs-customer-history-for-woocommerce' ); ?></a>
                                    </td>
                                </tr>
                            </table>
                        </div>
                        <div class="clear"></div>
                    </section>
                </div>

                <div class="pd-ch-clearfix"></div>
            </div>
        </div>

    <?php else : ?>

        <div class="wrap">
            <h1><?php echo esc_html__( 'Error', 'puredevs-customer-history-for-woocommerce' ); ?></h1>
            <p><?php echo esc_html__( "I'm sorry, you are not allowed to see this page!", 'puredevs-customer-history-for-woocommerce' ); ?><br /><br /><a href="admin.php?page=pd-woo-customers"><?php echo esc_html__( 'Back to Customer List', 'puredevs-customer-history-for-woocommerce' ); ?></a></p>
        </div>

    <?php endif; ?>

</div>
