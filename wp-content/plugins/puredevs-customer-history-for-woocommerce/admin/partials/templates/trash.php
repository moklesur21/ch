<?php
defined( 'ABSPATH' ) or exit;

/*
 *  Live Sessions List
 */

if ( isset( $_REQUEST['_wpnonce'] ) ) {
    if ( !wp_verify_nonce( $_REQUEST['_wpnonce'], 'move-from-trash' ) ) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php echo esc_html__('Security check failed!', 'puredevs-customer-history-for-woocommerce'); ?></p>
        </div>
        <?php
    } else {

        if ( isset( $_GET['act'] ) && sanitize_text_field( $_GET['act'] ) == 'restore' && isset( $_GET['id'] ) && intval( $_GET['id'] ) > 0 ) {
            $restore_session = PD_Customer_History_Session::pdwchs_woo_restore_live_session( $_GET['id'] );
            if ($restore_session) : ?>
                <div class="notice notice-success is-dismissible">
                    <p><?php echo wp_kses_post( sprintf(
                            /* translators: %s is a link to the live sessions page */
                            __( 'Your session was restored, you can find it in <a href="%s">live sessions</a>.', 'puredevs-customer-history-for-woocommerce' ),
                            esc_url( admin_url( 'admin.php?page=pd-woo-sessions' ) )
                        ) ); ?></p>
                </div>
            <?php endif;
        }
    }
}

$search = ! empty( $_GET['search_session'] ) ? sanitize_text_field( $_GET['search_session'] ) : '';
$date_range = ! empty( $_GET['start_date'] ) && ! empty( $_GET['end_date'] ) ?gmdate( "m-d-Y", strtotime( sanitize_text_field( $_GET['start_date'] ) ) ) . ' - ' .gmdate( "m-d-Y", strtotime( sanitize_text_field( $_GET['end_date'] ) ) ) : '';
?>

<?php include( 'top-nav.php' ); ?>

<ul class="subsubsub">
    <li><a href="admin.php?page=pd-woo-sessions" class=""><?php echo esc_html__( 'Live Sessions', 'puredevs-customer-history-for-woocommerce' ); ?></a> | </li>
    <li><a href="admin.php?page=pd-woo-searches" class=""><?php echo esc_html__( 'Live Searches', 'puredevs-customer-history-for-woocommerce' ); ?></a> | </li>
    <li><a href="admin.php?page=pd-woo-trash" class="current"><?php echo esc_html__( 'Trash', 'puredevs-customer-history-for-woocommerce' ) . ' (' . absint( PD_Customer_History_Session::pdwchs_woo_trashed_sessions() ) . ')'; ?></a> </li>
</ul>
<br class="clear">
<div id="pd-customer-history">

	<div id="live-sessions" class="wrap">
		<h1><?php echo esc_html__( 'Trash', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

        <?php
        $search_start_date = '';
        $search_end_date = '';
        if ( isset( $_GET['start_date'] ) && !empty( $_GET['start_date'] ) ) {
            $search_start_date = sanitize_text_field( $_GET['start_date'] );
        }
        if ( isset( $_GET['end_date'] ) && !empty( $_GET['end_date'] ) ) {
            $search_end_date = sanitize_text_field( $_GET['end_date'] );
        }
        ?>

		<form action="" class="search-box">
            <input type="text" id="daterange" value="<?php echo esc_attr( $date_range ); ?>" placeholder="<?php echo esc_attr__( 'Select Date Range', 'puredevs-customer-history-for-woocommerce' ); ?>" autocomplete="off" />
            <input type="hidden" name="start_date" value="<?php echo esc_attr( $search_start_date ); ?>" id="start-date" />
            <input type="hidden" name="end_date" value="<?php echo esc_attr( $search_end_date ); ?>" id="end-date" />
			<label class="screen-reader-text" for="user-search-input"><?php echo esc_html__( 'Search in Trash', 'puredevs-customer-history-for-woocommerce' ); ?>:</label>
			<input type="hidden" name="page" value="pd-woo-trash">
			<input type="search" id="user-search-input" name="search_session" placeholder="<?php echo esc_attr__( 'Keyword Search', 'puredevs-customer-history-for-woocommerce' ); ?>" value="<?php echo esc_attr($search); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php echo esc_html__( 'Search in Trash', 'puredevs-customer-history-for-woocommerce' ); ?>">
		</form>

        <div class="show-results">
            <form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] );?>" name="show-result" id="show-result" method="get">
                <label>Show</label>
                <select name="show-items" id="show-items">
                    <option value="">select one</option>
                    <option value="10" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '10' ){?>selected<?php };?>>10</option>
                    <option value="20" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '20' ){?>selected<?php };?>>20</option>
                    <option value="50" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '50' ){?>selected<?php };?>>50</option>
                    <option value="100" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '100' ){?>selected<?php };?>>100</option>
                </select>
                entries
            </form>
        </div>

		<?php
		$sessions = new PD_Customer_History_Session();
		$sessions->prepare_items();
		$sessions->display();
		?>

	</div>
</div>

