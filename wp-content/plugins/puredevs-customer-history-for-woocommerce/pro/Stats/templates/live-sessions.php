<?php
defined( 'ABSPATH' ) or exit;

/*
 *  Live Sessions / Searches / Trash page
 *  Shared template for all three sub-pages; view is determined by $_GET['page'].
 */

$current_page_slug = ! empty( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : 'pd-woo-sessions'; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$is_trash          = ( 'pd-woo-trash' === $current_page_slug );
$nonce_action      = $is_trash ? 'move-from-trash' : 'move-to-trash';

if ( isset( $_REQUEST['_wpnonce'] ) ) {
	if ( ! wp_verify_nonce( wp_unslash( $_REQUEST['_wpnonce'] ), $nonce_action ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		?>
		<div class="notice notice-error is-dismissible">
			<p><?php echo esc_html__( 'Security check failed!', 'puredevs-customer-history-for-woocommerce' ); ?></p>
		</div>
		<?php
	} else {
		if ( $is_trash ) {
			if ( isset( $_GET['act'] ) && sanitize_text_field( wp_unslash( $_GET['act'] ) ) === 'restore' && isset( $_GET['id'] ) && intval( $_GET['id'] ) > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$restored = PDWCHS_Sessions_Data::restore_session( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( $restored ) : ?>
					<div class="notice notice-success is-dismissible">
						<p><?php echo wp_kses_post( sprintf( __( 'Your session was restored, you can find it in <a href="%s">live sessions</a>', 'puredevs-customer-history-for-woocommerce' ), admin_url( 'admin.php?page=pd-woo-sessions' ) ) ); ?></p>
					</div>
				<?php endif;
			}
		} else {
			if ( isset( $_GET['act'] ) && sanitize_text_field( wp_unslash( $_GET['act'] ) ) === 'hide' && isset( $_GET['id'] ) && intval( $_GET['id'] ) > 0 ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$hidden = PDWCHS_Sessions_Data::delete_session( absint( $_GET['id'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				if ( $hidden ) : ?>
					<div class="notice notice-success is-dismissible">
						<p><?php echo wp_kses_post( sprintf( __( 'Your session was deleted, you can find it in <a href="%s">trash</a>', 'puredevs-customer-history-for-woocommerce' ), admin_url( 'admin.php?page=pd-woo-trash' ) ) ); ?></p>
					</div>
				<?php endif;
			}
		}
	}
}

$pdwchs_search = ! empty( $_GET['search_session'] ) ? sanitize_text_field( wp_unslash( $_GET['search_session'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

$date_range = ! empty( $_GET['start_date'] ) && ! empty( $_GET['end_date'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	? date( 'm-d-Y', strtotime( sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) ) ) . ' - ' . date( 'm-d-Y', strtotime( sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	: '';

if ( 'pd-woo-searches' === $current_page_slug ) {
	$page_title = esc_html__( 'Live Searches', 'puredevs-customer-history-for-woocommerce' );
} elseif ( $is_trash ) {
	$page_title = esc_html__( 'Trash', 'puredevs-customer-history-for-woocommerce' );
} else {
	$page_title = esc_html__( 'Live Sessions', 'puredevs-customer-history-for-woocommerce' );
}
?>

<?php include PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php'; ?>

<ul class="subsubsub">
	<li><a href="admin.php?page=pd-woo-sessions" class="<?php echo ( 'pd-woo-sessions' === $current_page_slug ) ? 'current' : ''; ?>"><?php echo esc_html__( 'Live Sessions', 'puredevs-customer-history-for-woocommerce' ); ?></a> | </li>
	<li><a href="admin.php?page=pd-woo-searches" class="<?php echo ( 'pd-woo-searches' === $current_page_slug ) ? 'current' : ''; ?>"><?php echo esc_html__( 'Live Searches', 'puredevs-customer-history-for-woocommerce' ); ?></a> | </li>
	<li><a href="admin.php?page=pd-woo-trash" class="<?php echo $is_trash ? 'current' : ''; ?>"><?php echo esc_html( sprintf( __( 'Trash (%d)', 'puredevs-customer-history-for-woocommerce' ), PDWCHS_Sessions_Data::get_trashed_count() ) ); ?></a></li>
</ul>
<br class="clear">
<div id="pd-customer-history">

	<div id="live-sessions" class="wrap">
		<h1><?php echo esc_html( $page_title ); ?></h1>
		<?php if ( ! $is_trash ) : ?>
		<a href="" class="button">
			<span class="dashicons dashicons-controls-repeat vertical-align-middle"></span> <?php echo esc_html__( 'Check for new Sessions', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a>
		<?php endif; ?>

		<?php
		$search_start_date = '';
		$search_end_date   = '';
		if ( ! empty( $_GET['start_date'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search_start_date = sanitize_text_field( wp_unslash( $_GET['start_date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['end_date'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search_end_date = sanitize_text_field( wp_unslash( $_GET['end_date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$pdwchs_show_items = isset( $_GET['show-items'] ) ? sanitize_text_field( wp_unslash( $_GET['show-items'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>

		<form action="" class="search-box">
			<input type="text" id="daterange" value="<?php echo esc_attr( $date_range ); ?>" placeholder="<?php echo esc_attr__( 'Select Date Range', 'puredevs-customer-history-for-woocommerce' ); ?>" autocomplete="off" />
			<input type="hidden" name="start_date" value="<?php echo esc_attr( $search_start_date ); ?>" id="start-date" />
			<input type="hidden" name="end_date"   value="<?php echo esc_attr( $search_end_date ); ?>"   id="end-date" />
			<label class="screen-reader-text" for="user-search-input"><?php echo esc_html__( 'Search Sessions', 'puredevs-customer-history-for-woocommerce' ); ?>:</label>
			<input type="hidden" name="page" value="<?php echo esc_attr( $current_page_slug ); ?>">
			<input type="search" id="user-search-input" name="search_session" placeholder="<?php echo esc_attr__( 'Keyword Search', 'puredevs-customer-history-for-woocommerce' ); ?>" value="<?php echo esc_attr( $pdwchs_search ); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php echo esc_attr__( 'Search Sessions', 'puredevs-customer-history-for-woocommerce' ); ?>">
		</form>

		<div class="show-results">
			<form action="<?php echo isset( $_SERVER['REQUEST_URI'] ) ? esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized ?>" name="show-result" id="show-result" method="get">
				<label><?php echo esc_html__( 'Show', 'puredevs-customer-history-for-woocommerce' ); ?></label>
				<select name="show-items" id="show-items">
					<option value=""><?php echo esc_html__( 'select one', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					<option value="10"  <?php selected( $pdwchs_show_items, '10' );  ?>>10</option>
					<option value="20"  <?php selected( $pdwchs_show_items, '20' );  ?>>20</option>
					<option value="50"  <?php selected( $pdwchs_show_items, '50' );  ?>>50</option>
					<option value="100" <?php selected( $pdwchs_show_items, '100' ); ?>>100</option>
				</select>
				<?php echo esc_html__( 'entries', 'puredevs-customer-history-for-woocommerce' ); ?>
			</form>
		</div>

		<?php
		$sessions_table = new PDWCHS_Sessions_List_Table();
		$sessions_table->prepare_items();
		$sessions_table->display();
		?>

	</div>
</div>
