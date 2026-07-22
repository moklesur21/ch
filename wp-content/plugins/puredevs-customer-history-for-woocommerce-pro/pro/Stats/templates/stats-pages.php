<?php
defined( 'ABSPATH' ) or exit;

/*
 *  Live Sessions Pages Stats
 */

include PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php';
include __DIR__ . '/top-sub-nav-stats.php';
$search = ! empty( $_GET['search_session'] ) ? sanitize_text_field( $_GET['search_session'] ) : '';
?>

<br class="clear">
<div id="pd-customer-history">

	<div id="live-sessions" class="wrap">
		<h1><?php echo esc_html__( 'Site Pages', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

		<form action="" class="search-box">
			<label class="screen-reader-text" for="user-search-input"><?php echo esc_html__( 'Site Pages', 'puredevs-customer-history-for-woocommerce' ); ?>:</label>
			<input type="hidden" name="page" value="pd-woo-stats">
			<input type="search" id="user-search-input" name="search_session" placeholder="<?php esc_attr__( 'Keyword Search', 'puredevs-customer-history-for-woocommerce' ); ?>" value="<?php echo esc_attr($search); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php echo esc_attr__( 'Search Sessions', 'puredevs-customer-history-for-woocommerce' ); ?>">
		</form>

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
