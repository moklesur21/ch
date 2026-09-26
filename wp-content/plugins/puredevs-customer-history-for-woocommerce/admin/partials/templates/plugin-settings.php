<?php

defined( 'ABSPATH' ) or exit;

/*
 *	Plugin Settings
 */
?>

<?php include('top-nav.php');?>
<div id="pd-customer-history">
	<div id="settings" class="wrap">
		<h1><?php echo esc_html__( 'Settings', 'puredevs-customer-history-for-woocommerce' ); ?></h1>
		<hr />
        <?php
        if ( isset( $_POST['act'] ) && $_POST['act'] == 'save' ) {

            if ( ! isset( $_POST['save_customer_history_settings'] )
                || ! wp_verify_nonce( $_POST['save_customer_history_settings'], 'save_customer_history_settings_action' )
            ) {
                ?>
                <div class="error">
                    <p><?php esc_html_e('Security check failed!', 'puredevs-customer-history-for-woocommerce'); ?></p>
                </div>
                <?php
            } else {
                $results_per_page_input = sanitize_text_field($_POST['results_per_page']);
                if ( pdwchs_is_pro_active() ) {
                    $default_save_admin_session_input = sanitize_text_field($_POST['default_save_admin_session']);
                    $show_bot_sessions_input = sanitize_text_field($_POST['show_bot_sessions']);
                }
                $hide_users_with_no_orders_input = sanitize_text_field($_POST['hide_users_with_no_orders']);


                $result_per_page   = update_option('pd-woo-results_per_page', $results_per_page_input);
                $save_sessions     = false;
                $show_bot_sessions = false;
                if ( pdwchs_is_pro_active() ) {
                    $save_sessions     = update_option('pd-woo-default_save_admin_session', $default_save_admin_session_input);
                    $show_bot_sessions = update_option('pd-woo-show_bot_sessions', $show_bot_sessions_input);
                }
                $hide_users_with_no_order = update_option('pd-woo-hide_users_with_no_orders', $hide_users_with_no_orders_input);
				
				if( isset( $_POST['generate_weekly_report'] ) && !empty( $_POST['generate_weekly_report'] ) ){
					$generate_weekly_report = array_map('sanitize_text_field', $_POST['generate_weekly_report']);
					$save_generate_weekly_report = update_option('pd-woo-generate_weekly_report', $generate_weekly_report);
				}else{
					$save_generate_weekly_report = delete_option( 'pd-woo-generate_weekly_report' );
				}
				
				$send_weekly_report_to = sanitize_text_field($_POST['send_weekly_report_to']);
				$save_send_weekly_report_to = update_option('pd-woo-send_weekly_report_to', $send_weekly_report_to);

				$digest_interval_input = isset( $_POST['digest_interval'] ) ? sanitize_text_field( wp_unslash( $_POST['digest_interval'] ) ) : 'weekly';
				if ( 'monthly' !== $digest_interval_input ) {
					$digest_interval_input = 'weekly';
				}
				$save_digest_interval = update_option( 'pdwchs_digest_interval', $digest_interval_input );

				if ( $result_per_page || $save_sessions || $hide_users_with_no_order || $show_bot_sessions || $save_generate_weekly_report || $save_send_weekly_report_to || $save_digest_interval ) {
                    ?>
                    <div class="updated">
                        <p><?php esc_html_e('Settings Saved', 'puredevs-customer-history-for-woocommerce'); ?></p>
                    </div>
                    <?php
                }
            }
        }
        ?>
		<form id="group-form" class="settings-form pdwchs-settings-stack" action="admin.php?page=pd-woo-settings" method="post">
			<?php wp_nonce_field( 'save_customer_history_settings_action', 'save_customer_history_settings' ); ?>
			<input type="hidden" name="act" value="save">

			<div class="pdwchs-settings-card">
				<div class="pdwchs-settings-card__head">
					<span class="dashicons dashicons-admin-generic" aria-hidden="true"></span>
					<div class="pdwchs-settings-card__head-text">
						<h2><?php echo esc_html__( 'General Settings', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
						<p><?php echo esc_html__( 'How customer data is listed inside the admin.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
					</div>
				</div>

				<div class="pdwchs-settings-card__body">
					<div class="pdwchs-field">
						<div class="pdwchs-field__label">
							<label for="results_per_page"><?php echo esc_html__( 'Results per page', 'puredevs-customer-history-for-woocommerce' ); ?></label>
						</div>
						<div class="pdwchs-field__control">
							<input id="results_per_page" name="results_per_page" type="number" class="small-text" placeholder="10" value="<?php echo esc_attr( get_option( 'pd-woo-results_per_page' ) ); ?>">
							<p class="description"><?php echo esc_html__( 'Rows shown per page on the customer, session and search lists.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
						</div>
					</div>

					<?php if ( pdwchs_is_pro_active() ) { ?>
					<div class="pdwchs-field">
						<div class="pdwchs-field__label">
							<label for="show_bot_sessions"><?php echo esc_html__( 'Show BOT sessions', 'puredevs-customer-history-for-woocommerce' ); ?></label>
						</div>
						<div class="pdwchs-field__control">
							<select id="show_bot_sessions" name="show_bot_sessions">
								<option value="0"><?php echo esc_html__( 'No', 'puredevs-customer-history-for-woocommerce' ); ?></option>
								<option value="1"<?php echo get_option( 'pd-woo-show_bot_sessions' ) ? ' selected="selected"' : ''; ?>><?php echo esc_html__( 'Yes', 'puredevs-customer-history-for-woocommerce' ); ?></option>
							</select>
							<p class="description"><?php echo esc_html__( 'List sessions identified as crawlers alongside real visitors.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
						</div>
					</div>

					<div class="pdwchs-field">
						<div class="pdwchs-field__label">
							<label for="default_save_admin_session"><?php echo esc_html__( 'Save admin sessions', 'puredevs-customer-history-for-woocommerce' ); ?></label>
						</div>
						<div class="pdwchs-field__control">
							<select id="default_save_admin_session" name="default_save_admin_session">
								<option value="0"><?php echo esc_html__( 'No', 'puredevs-customer-history-for-woocommerce' ); ?></option>
								<option value="1"<?php echo get_option( 'pd-woo-default_save_admin_session' ) ? ' selected="selected"' : ''; ?>><?php echo esc_html__( 'Yes', 'puredevs-customer-history-for-woocommerce' ); ?></option>
							</select>
							<p class="description"><?php echo esc_html__( 'Record sessions for logged-in store administrators.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
						</div>
					</div>
					<?php } ?>

					<div class="pdwchs-field">
						<div class="pdwchs-field__label">
							<label for="hide_users_with_no_orders"><?php echo esc_html__( 'Hide users with no orders', 'puredevs-customer-history-for-woocommerce' ); ?></label>
						</div>
						<div class="pdwchs-field__control">
							<select id="hide_users_with_no_orders" name="hide_users_with_no_orders">
								<option value="0"><?php echo esc_html__( 'No', 'puredevs-customer-history-for-woocommerce' ); ?></option>
								<option value="1"<?php echo get_option( 'pd-woo-hide_users_with_no_orders' ) ? ' selected="selected"' : ''; ?>><?php echo esc_html__( 'Yes', 'puredevs-customer-history-for-woocommerce' ); ?></option>
							</select>
							<p class="description"><?php echo esc_html__( 'Leave registered users who never ordered out of the customer list.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<div class="pdwchs-settings-card">
				<div class="pdwchs-settings-card__head">
					<span class="dashicons dashicons-email-alt" aria-hidden="true"></span>
					<div class="pdwchs-settings-card__head-text">
						<h2><?php echo esc_html__( 'Reports', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
						<p><?php echo esc_html__( 'The emailed digest of store activity.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
					</div>
				</div>

				<div class="pdwchs-settings-card__body">
					<?php $pd_woo_generate_weekly_report = get_option( 'pd-woo-generate_weekly_report' ); ?>
					<div class="pdwchs-field">
						<div class="pdwchs-field__label">
							<label for="order"><?php echo esc_html__( 'Generate reports', 'puredevs-customer-history-for-woocommerce' ); ?></label>
						</div>
						<div class="pdwchs-field__control">
							<label class="pdwchs-check" for="order">
								<input type="checkbox" id="order" name="generate_weekly_report[]" value="order" <?php if ( ! empty( $pd_woo_generate_weekly_report ) && in_array( 'order', $pd_woo_generate_weekly_report ) ) { ?>checked<?php } ?>/>
								<span><?php echo esc_html__( 'Order', 'puredevs-customer-history-for-woocommerce' ); ?></span>
							</label>
							<label class="pdwchs-check" for="top_customer">
								<input type="checkbox" id="top_customer" name="generate_weekly_report[]" value="top_customer" <?php if ( ! empty( $pd_woo_generate_weekly_report ) && in_array( 'top_customer', $pd_woo_generate_weekly_report ) ) { ?>checked<?php } ?>/>
								<span><?php echo esc_html__( 'Top customers', 'puredevs-customer-history-for-woocommerce' ); ?></span>
							</label>
							<?php if ( pdwchs_is_pro_active() ) { ?>
							<label class="pdwchs-check" for="search">
								<input type="checkbox" id="search" name="generate_weekly_report[]" value="search" <?php if ( ! empty( $pd_woo_generate_weekly_report ) && in_array( 'search', $pd_woo_generate_weekly_report ) ) { ?>checked<?php } ?>/>
								<span><?php echo esc_html__( 'Search', 'puredevs-customer-history-for-woocommerce' ); ?></span>
							</label>
							<label class="pdwchs-check" for="most_viewed_products">
								<input type="checkbox" id="most_viewed_products" name="generate_weekly_report[]" value="most_viewed_products" <?php if ( ! empty( $pd_woo_generate_weekly_report ) && in_array( 'most_viewed_products', $pd_woo_generate_weekly_report ) ) { ?>checked<?php } ?>/>
								<span><?php echo esc_html__( 'Most viewed products', 'puredevs-customer-history-for-woocommerce' ); ?></span>
							</label>
							<label class="pdwchs-check" for="most_viewed_categories">
								<input type="checkbox" id="most_viewed_categories" name="generate_weekly_report[]" value="most_viewed_categories" <?php if ( ! empty( $pd_woo_generate_weekly_report ) && in_array( 'most_viewed_categories', $pd_woo_generate_weekly_report ) ) { ?>checked<?php } ?>/>
								<span><?php echo esc_html__( 'Most viewed categories', 'puredevs-customer-history-for-woocommerce' ); ?></span>
							</label>
							<?php } ?>
							<p class="description"><?php echo esc_html__( 'With none selected, no digest is sent and no attachment is built.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
						</div>
					</div>

					<div class="pdwchs-field digest_interval">
						<div class="pdwchs-field__label">
							<label for="digest_interval"><?php echo esc_html__( 'Send reports', 'puredevs-customer-history-for-woocommerce' ); ?></label>
						</div>
						<div class="pdwchs-field__control">
							<?php $pdwchs_digest_interval = get_option( 'pdwchs_digest_interval', 'weekly' ); ?>
							<select name="digest_interval" id="digest_interval">
								<option value="weekly" <?php selected( $pdwchs_digest_interval, 'weekly' ); ?>><?php echo esc_html__( 'Weekly', 'puredevs-customer-history-for-woocommerce' ); ?></option>
								<option value="monthly" <?php selected( $pdwchs_digest_interval, 'monthly' ); ?>><?php echo esc_html__( 'Monthly', 'puredevs-customer-history-for-woocommerce' ); ?></option>
							</select>
							<p class="description"><?php echo esc_html__( 'How often the digest is sent.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
						</div>
					</div>

					<div class="pdwchs-field send_weekly_report_to">
						<div class="pdwchs-field__label">
							<label for="send_weekly_report_to"><?php echo esc_html__( 'Send reports to', 'puredevs-customer-history-for-woocommerce' ); ?></label>
						</div>
						<div class="pdwchs-field__control">
							<input type="email" id="send_weekly_report_to" name="send_weekly_report_to" value="<?php echo esc_attr( get_option( 'pd-woo-send_weekly_report_to' ) ); ?>" placeholder="admin@example.com"/>
							<p class="description"><?php echo esc_html__( 'The address every digest is delivered to.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
						</div>
					</div>
				</div>
			</div>

			<div class="pdwchs-settings-actions">
				<input type="submit" value="<?php echo esc_attr__( 'Save', 'puredevs-customer-history-for-woocommerce' ); ?>" class="button button-primary button-large">
			</div>
		</form>
        <?php do_action('pdwchs_settings_extra_sections'); ?>
        <?php if ( pdwchs_is_pro_active() ) { ?>
        <div class="pd-settings-block">
            <div class="settings-box">
                <h2><span class="dashicons dashicons-download"></span> <?php echo esc_html__( 'Export', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
                <form id="group-form" action="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-settings' ) ); ?>" method="post">
                    <?php wp_nonce_field( 'export_customer_history_action', 'export_customer_history' ); ?>
                    <input type="hidden" name="export" value="1">
                    <!--<p><?php echo esc_html__( 'You will download a file with "Sessions", "Searches" and "Stats".', 'puredevs-customer-history-for-woocommerce' ); ?></p>-->
                    <p><?php echo esc_html__( 'Download a file containing all the information about your customers activities.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
                    <input type="submit" value="<?php echo esc_attr__( 'Export', 'puredevs-customer-history-for-woocommerce' ); ?>" class="button button-primary export-btn">
                </form>
            </div>

            <div class="settings-box">
                <h2><span class="dashicons dashicons-upload"></span> <?php echo esc_html__( 'Import', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
                <form id="group-form" action="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-settings' ) ); ?>" method="post" enctype="multipart/form-data">
                    <?php wp_nonce_field( 'import_customer_history_action', 'import_customer_history' ); ?>
                    <input type="hidden" name="import" value="1">
                    <p><?php echo esc_html__( 'Imported data will be added to your database.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
                    <input type="file" name="pd_ch_import"><br/>
                    <input type="submit" value="<?php echo esc_attr__( 'Import', 'puredevs-customer-history-for-woocommerce' ); ?>" class="button button-primary import-btn" onclick="return confirm('Are you sure?')">
                </form>
            </div>

            <div class="settings-box">
                <h2><span class="dashicons dashicons-trash"></span> <?php echo esc_html__( 'Empty Tables', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
                <p class="warning"><span class="dashicons dashicons-warning"></span> <?php echo esc_html__( 'You will lose saved Sessions & Searches!', 'puredevs-customer-history-for-woocommerce' ); ?></p>

                <?php
                $search_start_date = '';
                $search_end_date = '';
                if ( isset( $_GET['start_date'] ) && !empty( $_GET['start_date'] ) ) {
                    $search_start_date = sanitize_text_field( $_GET['start_date'] );
                }
                if ( isset( $_GET['end_date'] ) && !empty( $_GET['end_date'] ) ) {
                    $search_end_date = sanitize_text_field( $_GET['end_date'] );
                }

                $date_range = ! empty( $_GET['start_date'] ) && ! empty( $_GET['end_date'] ) ?gmdate( "m-d-Y", strtotime( sanitize_text_field( $_GET['start_date'] ) ) ) . ' - ' .gmdate( "m-d-Y", strtotime( sanitize_text_field( $_GET['end_date'] ) ) ) : '';
                ?>

                <form id="group-form" action="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-settings' ) ); ?>" method="post">
                    <?php wp_nonce_field( 'custom_delete_history_action', 'custom_delete_history' ); ?>
                    <input type="hidden" name="delete_custom" value="1">
                    <input type="submit" value="<?php echo esc_attr__( 'Custom Delete', 'puredevs-customer-history-for-woocommerce' ); ?>" class="delete-custom-btn">
                    <input type="submit" value="<?php echo esc_attr__( 'Delete', 'puredevs-customer-history-for-woocommerce' ); ?>" class="delete-custom-btn-confirm d-none" onclick="return confirm('Are you sure?')">
                    <input type="text" id="daterange" class="d-none cdfild" value="<?php $date_range; ?>" placeholder="<?php echo esc_attr__( 'Select Date Range', 'puredevs-customer-history-for-woocommerce' ); ?>" autocomplete="off" required/>
                    <input type="hidden" name="start_date" value="<?php echo esc_attr( $search_start_date ); ?>" id="start-date"/>
                    <input type="hidden" name="end_date" value="<?php echo esc_attr( $search_end_date ); ?>" id="end-date"/>
                </form>

                <br/>

                <form id="group-form" action="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-settings' ) ); ?>" method="post">
                    <?php wp_nonce_field( 'delete_session_history_action', 'delete_session_history' ); ?>
                    <input type="hidden" name="delete_sessions" value="1">
                    <input type="submit" value="<?php echo esc_attr__( 'Delete All', 'puredevs-customer-history-for-woocommerce' ); ?>" class="button button-primary delete-all-btn" onclick="return confirm('Are you sure?')">
                </form>

            </div>
        </div>
        <?php }; ?>
	</div>
</div>
