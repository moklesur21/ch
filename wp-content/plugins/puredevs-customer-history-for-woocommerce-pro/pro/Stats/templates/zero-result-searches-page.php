<?php
defined( 'ABSPATH' ) or exit;

/*
 *  Zero-Result Searches — search terms that returned nothing
 *
 *  Shell cloned from search-keywords-page.php. The read-only GET filters below carry
 *  nonce-verification ignores with a reason rather than the bare reads the siblings use,
 *  and neither form posts anywhere: the search box filters by query string and the
 *  show-items select is driven by admin JS rewriting the current URL
 *  (admin/js/pd-customer-history-admin.js:229-237), so no form action is needed.
 */

include PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php';
include __DIR__ . '/top-sub-nav-stats.php';

// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only list filters; no state is changed.
$pdwchs_search     = isset( $_GET['search_session'] ) ? sanitize_text_field( wp_unslash( $_GET['search_session'] ) ) : '';
$pdwchs_show_items = isset( $_GET['show-items'] ) ? sanitize_text_field( wp_unslash( $_GET['show-items'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended
?>

<br class="clear">
<div id="pd-customer-history">

	<div id="live-sessions" class="wrap">
		<h1><?php echo esc_html__( 'Zero-Result Searches', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

		<form action="" class="search-box">
			<label class="screen-reader-text" for="user-search-input"><?php echo esc_html__( 'Zero-Result Searches', 'puredevs-customer-history-for-woocommerce' ); ?>:</label>
			<input type="hidden" name="page" value="pd-woo-zero-result-searches">
			<input type="search" id="user-search-input" name="search_session" placeholder="<?php echo esc_attr__( 'Search Terms', 'puredevs-customer-history-for-woocommerce' ); ?>" value="<?php echo esc_attr( $pdwchs_search ); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php echo esc_attr__( 'Search Terms', 'puredevs-customer-history-for-woocommerce' ); ?>">
		</form>

		<div class="show-results">
			<form action="" name="show-result" id="show-result" method="get">
				<label><?php echo esc_html__( 'Show', 'puredevs-customer-history-for-woocommerce' ); ?></label>
				<select name="show-items" id="show-items">
					<option value=""><?php echo esc_html__( 'select one', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					<option value="10" <?php selected( $pdwchs_show_items, '10' ); ?>>10</option>
					<option value="20" <?php selected( $pdwchs_show_items, '20' ); ?>>20</option>
					<option value="50" <?php selected( $pdwchs_show_items, '50' ); ?>>50</option>
					<option value="100" <?php selected( $pdwchs_show_items, '100' ); ?>>100</option>
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
