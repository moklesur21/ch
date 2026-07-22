<?php
defined( 'ABSPATH' ) or exit;

/*
 *  Live Sessions Product Taxonomy Stats
 */

include PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php';
include __DIR__ . '/top-sub-nav-stats.php';
$search = ! empty( $_GET['search_session'] ) ? sanitize_text_field( $_GET['search_session'] ) : '';
?>

<br class="clear">
<div id="pd-customer-history">

	<div id="live-sessions" class="wrap">
		<h1><?php echo esc_html__( 'Top Selling Products by This Month', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

		<form action="" class="search-box">
			<input type="hidden" name="page" value="pd-woo-top-selling-stats">
			<input type="search" id="user-search-input" name="search_session" placeholder="<?php echo esc_attr__( 'Product Search', 'puredevs-customer-history-for-woocommerce' ); ?>" value="<?php echo esc_attr($search); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php echo esc_attr__( 'Search Products', 'puredevs-customer-history-for-woocommerce' ); ?>">
		</form>
        <div class="show-results">
            <form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] );?>" name="show-result" id="show-result" method="get">
                <label><?php echo esc_html__( 'Show', 'puredevs-customer-history-for-woocommerce' ); ?></label>
                <select name="show-items" id="show-items">
                    <option value="">select one</option>
                    <option value="10" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '10' ){?>selected<?php };?>>10</option>
                    <option value="20" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '20' ){?>selected<?php };?>>20</option>
                    <option value="50" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '50' ){?>selected<?php };?>>50</option>
                    <option value="100" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '100' ){?>selected<?php };?>>100</option>
                </select>
                <?php echo esc_html__( 'entries', 'puredevs-customer-history-for-woocommerce' ); ?>
            </form>
        </div>
		<?php
		$stats = new PDWCHS_Stats_List_Table();
		$stats->prepare_items();
		$stats->display();
		?>

	</div>
</div>
