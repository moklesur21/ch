<?php

defined( 'ABSPATH' ) or exit;

/*
 *	Plugin Top Navigation
 */

// All page slugs that belong to the Statistics section (including hidden sub-pages).
$_pdwchs_cur_page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
$_pdwchs_stats_slugs = array(
	'pd-woo-stats',
	'pd-woo-search-stats',
	'pd-woo-product-view-stats',
	'pd-woo-top-selling-stats',
	'pd-woo-conversion-funnel-stats',
	'pd-woo-stats-spent',
	'pd-woo-product-revenue',
	'pd-woo-product-correlations',
	'pd-woo-revenue-patterns',
);
// Also matches dynamic taxonomy stats pages (e.g. pd-woo-category-stats).
$_pdwchs_stats_active = in_array( $_pdwchs_cur_page, $_pdwchs_stats_slugs, true )
	|| ( '' !== $_pdwchs_cur_page && false !== strpos( $_pdwchs_cur_page, '-stats' ) );
?>
<h2 class="nav-tab-wrapper top-nav">
    <a href="admin.php?page=pd-woo-dashboard" class="nav-tab <?php if ( isset($_GET['page']) && $_GET['page'] == 'pd-woo-dashboard' ) : ?>nav-tab-active<?php endif;?>" id="pd-dashboard-tab"><span class="dashicons dashicons-dashboard"></span><?php echo esc_html__( 'Dashboard', 'puredevs-customer-history-for-woocommerce' ); ?></a>
    <a href="admin.php?page=pd-woo-customers" class="nav-tab <?php if ( isset($_GET['page']) && $_GET['page'] == 'pd-woo-customers' ) : ?>nav-tab-active<?php endif;?>" id="pd-customers-tab"><span class="dashicons dashicons-admin-users"></span><?php echo esc_html__( 'Customers & Users', 'puredevs-customer-history-for-woocommerce' ); ?></a>
    <a href="admin.php?page=pd-woo-order-history" class="nav-tab <?php if ( isset($_GET['page']) && $_GET['page'] == 'pd-woo-order-history' ) : ?>nav-tab-active<?php endif;?>" id="pd-order-history-tab"><span class="dashicons dashicons-clock"></span><?php echo esc_html__( 'Order History', 'puredevs-customer-history-for-woocommerce' ); ?></a>
    <a href="admin.php?page=pd-woo-revenue" class="nav-tab <?php if ( isset($_GET['page']) && $_GET['page'] == 'pd-woo-revenue' ) : ?>nav-tab-active<?php endif;?>" id="pd-revenue-tab"><span class="dashicons dashicons-chart-line"></span><?php echo esc_html__( 'Revenue', 'puredevs-customer-history-for-woocommerce' ); ?></a>
    <a href="admin.php?page=pd-woo-segments" class="nav-tab <?php if ( isset($_GET['page']) && $_GET['page'] == 'pd-woo-segments' ) : ?>nav-tab-active<?php endif;?>" id="pd-segments-tab"><span class="dashicons dashicons-filter"></span><?php echo esc_html__( 'Segments', 'puredevs-customer-history-for-woocommerce' ); ?></a>
    <?php if ( ! pdwchs_is_pro_active() ) { ?>
    <a href="admin.php?page=pd-woo-search-analytics" class="nav-tab <?php if ( isset($_GET['page']) && $_GET['page'] == 'pd-woo-search-analytics' ) : ?>nav-tab-active<?php endif;?>" id="pd-search-analytics-tab"><span class="dashicons dashicons-search"></span><?php echo esc_html__( 'Search Analytics', 'puredevs-customer-history-for-woocommerce' ); ?></a>
    <?php } ?>
    <?php if ( pdwchs_is_pro_active() ) { ?>
    <a href="admin.php?page=pd-woo-sessions" class="nav-tab <?php if ( isset($_GET['page']) && $_GET['page'] == 'pd-woo-sessions' ) : ?>nav-tab-active<?php endif;?>" id="pd-sessions-tab"><span class="dashicons dashicons-admin-links"></span><?php echo esc_html__( 'Sessions', 'puredevs-customer-history-for-woocommerce' ); ?></a>
    <a href="admin.php?page=pd-woo-stats" class="nav-tab <?php echo $_pdwchs_stats_active ? 'nav-tab-active' : ''; ?>" id="pd-stats-tab"><span class="dashicons dashicons-chart-area"></span><?php echo esc_html__( 'Statistics', 'puredevs-customer-history-for-woocommerce' ); ?></a>
    <?php }; ?>
	<?php do_action('pchfw_top_nav')?>
    <a href="admin.php?page=pd-woo-settings" class="nav-tab <?php if ( isset($_GET['page']) && $_GET['page'] == 'pd-woo-settings' ) : ?>nav-tab-active<?php endif;?>" id="pd-settings-tab"><span class="dashicons dashicons-admin-settings"></span><?php echo esc_html__( 'Settings', 'puredevs-customer-history-for-woocommerce' ); ?></a>
</h2>
