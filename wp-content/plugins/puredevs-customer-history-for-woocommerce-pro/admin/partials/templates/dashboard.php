<?php

defined( 'ABSPATH' ) or exit;

/**
 * Template for customer history dashboard.
 */

include( 'top-nav.php' );

?>

<div id="pd-customer-history">
	<div id="dashboard" class="wrap">
		<h1><?php echo esc_html__( 'Dashboard', 'puredevs-customer-history-for-woocommerce' ); ?></h1>
		<div class="pd-woo-dashboard">
			<?php
				include ('dashboard/top-customers.php');
				include ('dashboard/last-orders.php');
				include ('dashboard/top-selling-products.php');
				include ('dashboard/top-searches.php');
				include ('dashboard/top-taxonomy.php');
				include ('dashboard/last-searches.php');
				include ('dashboard/most-viewed-products.php');
				do_action( 'pdwchs_dashboard_widgets' );
				if ( ! pdwchs_is_pro_active() ) {
					$pdwchs_attention_count = PD_Customer_History_Customers::pdwchs_count_customers_needing_attention();
					if ( $pdwchs_attention_count > 0 ) {
						PDWCHS_Upsell::pdwchs_render_banner(
							'dashboard-attention',
							sprintf(
								/* translators: %s: number of customers */
								esc_html__( '%s customers haven\'t ordered in over 30 days. Customer History Pro shows you who\'s at risk of churning — and why.', 'puredevs-customer-history-for-woocommerce' ),
								esc_html( number_format_i18n( $pdwchs_attention_count ) )
							)
						);
					}
				}
				if ( ! pdwchs_is_pro_active() && PDWCHS_Data_Window::pdwchs_was_clamped() ) {
					pdwchs_record_first_cap_hit( 'data_window' );
					PDWCHS_Upsell::pdwchs_render_banner(
						'data-window-90d',
						esc_html__( 'The free version includes your most recent 90 days of activity. Customer History Pro includes your full history — your older data is already collected and waiting.', 'puredevs-customer-history-for-woocommerce' )
					);
				}
			?>
		</div>
	</div>
</div>
