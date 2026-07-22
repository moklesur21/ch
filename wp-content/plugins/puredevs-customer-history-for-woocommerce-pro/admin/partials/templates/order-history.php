<?php
defined( 'ABSPATH' ) or exit;

/*
 *  Customer List
 */
if ( pdwchs_is_pro_active() ) {
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
}
$date_range = !empty($_GET['start_date']) && !empty($_GET['end_date']) ?gmdate("m-d-Y", strtotime(sanitize_text_field($_GET['start_date']))) . ' - ' .gmdate("m-d-Y", strtotime(sanitize_text_field($_GET['end_date']))) : '';
?>
<?php include('top-nav.php');?>
<div id="pd-woocommerce-order-history">
	<div id="orders" class="wrap">
		<h1><?php echo esc_html__( 'Order History', 'puredevs-customer-history-for-woocommerce' ); ?></h1>
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
		<form action="" method="get">
			<fieldset>
				<legend><?php echo esc_html__( 'Advance Search:', 'puredevs-customer-history-for-woocommerce' ); ?></legend>
				<input type="hidden" name="page" value="<?php echo esc_attr( $_REQUEST['page'] ); ?>" />
				<div class="pd-wcch-form-group">
					<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Date :', 'puredevs-customer-history-for-woocommerce' ); ?> </label>
					<input type="text" id="daterange" class="pd-wcch-select2" value="<?php echo esc_attr( $date_range ); ?>" placeholder="<?php echo esc_attr__( 'Select Date Range', 'puredevs-customer-history-for-woocommerce' ); ?>" autocomplete="off"/>
					<input type="hidden" name="start_date" value="<?php echo esc_attr( $search_start_date ); ?>" id="start-date" />
					<input type="hidden" name="end_date" value="<?php echo esc_attr( $search_end_date ); ?>" id="end-date" />
					<div class="pd-wcch-inpu-wrapper pd-wcch-close-cus-wrap  d-none">
						<button type="button" class="pd-wcch-btn pd-wcch-inline-block  pd-wcch-close-cus button btn">&#10005;</button>
					</div>
				</div>
				<?php if ( pdwchs_is_pro_active() ) { ?>
				<div class="pd-wcch-form-group">
					<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Total Amount :', 'puredevs-customer-history-for-woocommerce' ); ?> </label>
					<select name="total_amount" class="pd-wcch-select pd-wcch-inline-block">
						<option value="">-- Select One --</option>
						<option value="<?php echo esc_attr( 'equal' );?>" <?php if( !empty( $_GET['total_amount'] ) && $_GET['total_amount'] == 'equal' ):?>selected<?php endif;?>><?php echo esc_html__( 'Equal', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="<?php echo esc_attr( 'greater_then' );?>" <?php if( !empty( $_GET['total_amount'] ) && $_GET['total_amount'] == 'greater_then' ):?>selected<?php endif;?>><?php echo esc_html__( 'Greater then', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="<?php echo esc_attr( 'less_then' );?>" <?php if( !empty( $_GET['total_amount'] ) && $_GET['total_amount'] == 'less_then' ):?>selected<?php endif;?>><?php echo esc_html__( 'Less then', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					</select>
					<div class="pd-wcch-inpu-wrapper <?php if( empty( $_GET['total_order_input'] ) ){ echo esc_attr('d-none'); } ?>">
						<input type="text" class="pd-wcch-form-control pd-wcch-inline-block" name="total_order_input" value="<?php if( !empty( $_GET['total_order_input'] ) ){ echo esc_attr( $_GET['total_order_input'] ); } ?>">
						<button type="button" class="pd-wcch-btn pd-wcch-inline-block  pd-wcch-close button btn">&#10005;</button>
					</div>
				</div>

				<?php
				$gateways = WC()->payment_gateways->get_available_payment_gateways();
				if ( !empty( $gateways ) ) : ?>
				<div class="pd-wcch-form-group">
					<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Payment Method :', 'puredevs-customer-history-for-woocommerce' ); ?> </label>
					<select name="payment_method" class="pd-wcch-select pd-wcch-inline-block">
						<option value="">-- Select One --</option>
						<?php foreach ( $gateways as $key => $value ) :?>
						<option value="<?php echo esc_attr( $key );?>" <?php if( !empty( $_GET['payment_method'] ) && $_GET['payment_method'] == $key ):?>selected<?php endif;?>><?php echo esc_html( $value->title ); ?></option>
						<?php endforeach;?>
					</select>

					<div class="pd-wcch-inpu-wrapper d-none">
						<button type="button" class="pd-wcch-btn pd-wcch-inline-block  pd-wcch-close button btn">&#10005;</button>
					</div>
				</div>
				<?php endif;?>

				<?php
				$orderStatuses = wc_get_order_statuses();

				if( !empty( $orderStatuses ) ):?>
					<div class="pd-wcch-form-group">
						<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Order Status :', 'puredevs-customer-history-for-woocommerce' ); ?> </label>
						<select name="order_status" class="pd-wcch-select pd-wcch-inline-block">
							<option value="">-- Select One --</option>
							<?php foreach ( $orderStatuses as $key => $value ) : ?>
								<option value="<?php echo esc_attr( $key );?>" <?php if( !empty( $_GET['order_status'] ) && $_GET['order_status'] == $key ):?>selected<?php endif;?>><?php echo esc_html( $value ); ?></option>
							<?php endforeach;?>
						</select>
						<div class="pd-wcch-inpu-wrapper  d-none">
							<button type="button" class="pd-wcch-btn pd-wcch-inline-block  pd-wcch-close button btn">&#10005;</button>
						</div>
					</div>
				<?php endif;?>
				<?php };?>
				<button type="submit" class="pd-wcch-btn button btn boldText"><?php echo esc_html__( 'Search', 'puredevs-customer-history-for-woocommerce' ); ?></button>
			</fieldset>
		</form>
		<?php if ( pdwchs_is_pro_active() ) { ?>
		<form action="" class="search-box">
			<label class="screen-reader-text" for="user-search-input"><?php echo esc_html__( 'Search Order', 'puredevs-customer-history-for-woocommerce' ); ?>:</label>
			<input type="hidden" name="page" value="pd-woo-order-history">
			<input type="search" id="user-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php echo esc_attr__( 'Search Order', 'puredevs-customer-history-for-woocommerce' ); ?>">
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
				entries
			</form>
        </div>
		<?php };?>
		<h2><?php echo esc_html__( 'Last Orders', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
        <?php
        $orders = new PD_Customer_Order_History();
        $orders->prepare_items();
        $orders->display();
        ?>
	</div>
</div>
