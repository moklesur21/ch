<?php
defined( 'ABSPATH' ) or exit;

/*
 *  Total Spent Stats
 */

include PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php';
include __DIR__ . '/top-sub-nav-stats.php'; ?>

<br class="clear">
<div id="pd-customer-history">

	<div id="live-sessions" class="wrap">
		<h1><?php echo esc_html__( 'Total Spent', 'puredevs-customer-history-for-woocommerce' ); ?></h1>
        <div class="show-results">
            <form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] );?>" name="show-result" id="show-result" method="get">
                <label><?php echo esc_html__( 'Show', 'puredevs-customer-history-for-woocommerce' ); ?></label>
                <select name="show-items" id="show-items">
                    <option value="">select one</option>
                    <option value="10" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '10' ){?>selected<?php };?>><?php echo esc_html__( '10', 'puredevs-customer-history-for-woocommerce' ); ?></option>
                    <option value="20" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '20' ){?>selected<?php };?>><?php echo esc_html__( '20', 'puredevs-customer-history-for-woocommerce' ); ?></option>
                    <option value="50" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '50' ){?>selected<?php };?>><?php echo esc_html__( '50', 'puredevs-customer-history-for-woocommerce' ); ?></option>
                    <option value="100" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '100' ){?>selected<?php };?>><?php echo esc_html__( '100', 'puredevs-customer-history-for-woocommerce' ); ?></option>
                </select>
                entries
            </form>
        </div>
		<?php
		$stats = new PDWCHS_Stats_List_Table();
		$stats->prepare_items();
		$stats->display();
		?>

	</div>
</div>