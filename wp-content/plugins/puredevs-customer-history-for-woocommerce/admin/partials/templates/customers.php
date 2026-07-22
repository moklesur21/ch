<?php
defined( 'ABSPATH' ) or exit;

/*
 *  Customer List
 */
if ( pdwchs_is_pro_active() ) {
    $search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
}
?>
<?php include('top-nav.php');?>
<div id="pd-woocommerce-customer-history">
	<div id="customers" class="wrap">
		<h1><?php echo esc_html__( 'Customer List', 'puredevs-customer-history-for-woocommerce' ); ?></h1>
		<form action="" method="get">
			<fieldset>
				<legend>Advance Search:</legend>
				<input type="hidden" name="page" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_REQUEST['page'] ?? '' ) ) ); ?>" />
				<?php if ( pdwchs_is_pro_active() ) { ?>
				<div class="pd-wcch-form-group">
					<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Role :', 'puredevs-customer-history-for-woocommerce' ); ?> </label>
					<select name="user_role" class="pd-wcch-select pd-wcch-inline-block">
						<option value="">-- Select One --</option>
						<option value="<?php echo esc_attr( 'administrator' ); ?>" <?php if( !empty( $_GET['user_role'] ) && $_GET['user_role'] == 'administrator' ):?>selected<?php endif;?>><?php echo esc_html__( 'Admin', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="<?php echo esc_attr( 'subscriber' ); ?>" <?php if( !empty( $_GET['user_role'] ) && $_GET['user_role'] == 'subscriber' ):?>selected<?php endif;?>><?php echo esc_html__( 'Subscriber', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="<?php echo esc_attr( 'customer' ); ?>" <?php if( !empty( $_GET['user_role'] ) && $_GET['user_role'] == 'customer' ):?>selected<?php endif;?>><?php echo esc_html__( 'Customer', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					</select>

					<div class="pd-wcch-inpu-wrapper d-none">
						<button type="button" class="pd-wcch-btn pd-wcch-inline-block  pd-wcch-close button btn">&#10005;</button>
					</div>
				</div>
				<?php }; ?>
				<div class="pd-wcch-form-group">
					<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'No. of orders :', 'puredevs-customer-history-for-woocommerce' ); ?> </label>
					<select name="order_number" class="pd-wcch-select pd-wcch-inline-block">
						<option value="">-- Select One --</option>
						<option value="<?php echo esc_attr( 'equal' ); ?>" <?php if( !empty( $_GET['order_number'] ) && $_GET['order_number'] == 'equal' ):?>selected<?php endif;?>><?php echo esc_html__( 'Equal', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="<?php echo esc_attr( 'greater_then' ); ?>" <?php if( !empty( $_GET['order_number'] ) && $_GET['order_number'] == 'greater_then' ):?>selected<?php endif;?>><?php echo esc_html__( 'Greater then', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="<?php echo esc_attr( 'less_then' ); ?>" <?php if( !empty( $_GET['order_number'] ) && $_GET['order_number'] == 'less_then' ):?>selected<?php endif;?>><?php echo esc_html__( 'Less then', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					</select>

					<div class="pd-wcch-inpu-wrapper <?php if( empty( $_GET['order_number_input'] ) ){ echo esc_attr('d-none'); } ?>">
						<input type="text" class="pd-wcch-form-control pd-wcch-inline-block" name="order_number_input" value="<?php if( !empty( $_GET['order_number_input'] ) ){ echo esc_attr( $_GET['order_number_input'] ); }?>">
						<button type="button" class="pd-wcch-btn pd-wcch-inline-block  pd-wcch-close button btn">&#10005;</button>
					</div>
				</div>

				<div class="pd-wcch-form-group">
					<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Total Spent :', 'puredevs-customer-history-for-woocommerce' ); ?> </label>
					<select name="total_spent" class="pd-wcch-select pd-wcch-inline-block">
						<option value="">-- Select One --</option>
						<option value="<?php echo esc_attr( 'equal' ); ?>" <?php if( !empty( $_GET['total_spent'] ) && $_GET['total_spent'] == 'equal' ):?>selected<?php endif;?>><?php echo esc_html__( 'Equal', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="<?php echo esc_attr( 'greater_then' ); ?>" <?php if( !empty( $_GET['total_spent'] ) && $_GET['total_spent'] == 'greater_then' ):?>selected<?php endif;?>><?php echo esc_html__( 'Greater then', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="<?php echo esc_attr( 'less_then' ); ?>" <?php if( !empty( $_GET['total_spent'] ) && $_GET['total_spent'] == 'less_then' ):?>selected<?php endif;?>><?php echo esc_html__( 'Less then', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					</select>

					<div class="pd-wcch-inpu-wrapper <?php if( empty( $_GET['total_spent_input'] ) ){ echo esc_attr('d-none'); } ?>">
						<input type="text" class="pd-wcch-form-control pd-wcch-inline-block" name="total_spent_input" value="<?php if( !empty( $_GET['total_spent_input'] ) ){ echo esc_attr( $_GET['total_spent_input'] ); }?>">
						<button type="button" class="pd-wcch-btn pd-wcch-inline-block  pd-wcch-close button btn">&#10005;</button>
					</div>
				</div>
				<?php if ( pdwchs_is_pro_active() ) { ?>
				<?php
				$all_countries = PDWCHS_Helper::$countries;
				if ( isset( $all_countries ) && !empty( $all_countries ) ) :
				?>
				<div class="pd-wcch-form-group">
					<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Country :', 'puredevs-customer-history-for-woocommerce' ); ?> </label>
					<select name="customer_country" id="customer_country" class="pd-wcch-select pd-wcch-inline-block" style="min-width: 300px;">
						<option value="">-- Select a country… --</option>
						<?php foreach ( $all_countries as $key => $value ) :?>
						<option value="<?php echo esc_attr( $key );?>" <?php if( !empty( $_GET['customer_country'] ) && $_GET['customer_country'] == $key ):?>selected<?php endif;?>><?php echo esc_html( $value ); ?></option>
						<?php endforeach;?>
					</select>

					<div class="pd-wcch-inpu-wrapper d-none">
						<button type="button" class="pd-wcch-btn pd-wcch-inline-block  pd-wcch-close button btn">&#10005;</button>
					</div>
				</div>
				<?php endif;?>
				<?php do_action('pdwchs_customers_filters_extra'); ?>
				<?php }; ?>
				<div class="pd-wcch-form-group">
					<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Product Purchased :', 'puredevs-customer-history-for-woocommerce' ); ?> </label>
					<select name="product_filter" id="product_filter" class="pd-wcch-select pd-wcch-product-filter pd-wcch-inline-block" style="min-width: 300px;">
						<option value="">-- <?php echo esc_html__( 'Search and select a product', 'puredevs-customer-history-for-woocommerce' ); ?> --</option>
						<?php
						// Get all products
						$args = array(
							'limit' => -1,
							'status' => 'publish',
							'orderby' => 'name',
							'order' => 'ASC',
						);
						$products = wc_get_products( $args );

						if ( ! empty( $products ) ) {
							foreach ( $products as $product ) {
								$selected = ( ! empty( $_GET['product_filter'] ) && $_GET['product_filter'] == $product->get_id() ) ? 'selected' : '';
								echo '<option value="' . esc_attr( $product->get_id() ) . '" ' . esc_attr( $selected ) . '>' . esc_html( $product->get_name() ) . ' (ID: ' . esc_html( $product->get_id() ) . ')</option>';
							}
						}
						?>
					</select>

					<div class="pd-wcch-inpu-wrapper <?php if( empty( $_GET['product_filter'] ) ){ echo esc_attr('d-none'); } ?>">
						<button type="button" class="pd-wcch-btn pd-wcch-inline-block pd-wcch-close button btn">&#10005;</button>
					</div>
				</div>
				<button type="submit" class="pd-wcch-btn button btn boldText"><?php echo esc_html__( 'Search', 'puredevs-customer-history-for-woocommerce' ); ?></button>
				<button type="button" id="pdwchs-save-segment-btn" class="pd-wcch-btn button btn pdwchs-save-segment-btn">
					<?php echo esc_html__( 'Save as Segment', 'puredevs-customer-history-for-woocommerce' ); ?>
				</button>
			</fieldset>
		</form>

		<div id="pdwchs-modal-overlay" class="pdwchs-modal-overlay" style="display:none;"
		     role="dialog" aria-modal="true" aria-labelledby="pdwchs-modal-title">
			<div class="pdwchs-modal-box">
				<h2 id="pdwchs-modal-title" class="pdwchs-modal-title">
					<?php echo esc_html__( 'Save as Segment', 'puredevs-customer-history-for-woocommerce' ); ?>
				</h2>
				<div class="pdwchs-modal-body">
					<label for="pdwchs-segment-name">
						<?php echo esc_html__( 'Segment Name', 'puredevs-customer-history-for-woocommerce' ); ?>
						<span aria-hidden="true"> *</span>
					</label>
					<input type="text" id="pdwchs-segment-name" class="regular-text" maxlength="191"
					       placeholder="<?php echo esc_attr__( 'e.g. High-value customers', 'puredevs-customer-history-for-woocommerce' ); ?>" />
					<p id="pdwchs-name-error" class="pdwchs-field-error" style="display:none;"></p>

					<label for="pdwchs-segment-description">
						<?php echo esc_html__( 'Description (optional)', 'puredevs-customer-history-for-woocommerce' ); ?>
					</label>
					<textarea id="pdwchs-segment-description" class="regular-text" rows="3"
					          placeholder="<?php echo esc_attr__( 'Brief description of this segment', 'puredevs-customer-history-for-woocommerce' ); ?>"></textarea>
					<p id="pdwchs-criteria-error" class="pdwchs-field-error" style="display:none;"></p>
				</div>
				<div class="pdwchs-modal-footer">
					<button type="button" id="pdwchs-modal-cancel" class="button">
						<?php echo esc_html__( 'Cancel', 'puredevs-customer-history-for-woocommerce' ); ?>
					</button>
					<button type="button" id="pdwchs-modal-save" class="button button-primary">
						<?php echo esc_html__( 'Save Segment', 'puredevs-customer-history-for-woocommerce' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php if ( pdwchs_is_pro_active() ) { ?>
		<form action="" class="search-box">
			<label class="screen-reader-text" for="user-search-input"><?php echo esc_html__( 'Search Customer', 'puredevs-customer-history-for-woocommerce' ); ?>:</label>
			<input type="hidden" name="page" value="<?php echo esc_attr( 'pd-woo-customers' );?>">
			<input type="search" id="user-search-input" name="s" value="<?php echo esc_attr( $search ); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php echo esc_attr__( 'Search Customer', 'puredevs-customer-history-for-woocommerce' ); ?>">
		</form>
		
		<div class="show-results">
			<form action="<?php echo esc_url( $_SERVER['REQUEST_URI'] );?>" name="show-result" id="show-result" method="get">
				<label><?php echo esc_html__( 'Show', 'puredevs-customer-history-for-woocommerce' ); ?></label>
				<select name="show-items" id="show-items">
					<option value="">select one</option>
					<option value="<?php echo esc_attr( '10' ); ?>" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '10' ){?>selected<?php };?>><?php echo esc_html__( '10', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					<option value="<?php echo esc_attr( '20' ); ?>" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '20' ){?>selected<?php };?>><?php echo esc_html__( '20', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					<option value="<?php echo esc_attr( '50' ); ?>" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '50' ){?>selected<?php };?>><?php echo esc_html__( '50', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					<option value="<?php echo esc_attr( '100' ); ?>" <?php if( isset( $_GET['show-items'] ) && $_GET['show-items'] === '100' ){?>selected<?php };?>><?php echo esc_html__( '100', 'puredevs-customer-history-for-woocommerce' ); ?></option>
				</select>
				entries
			</form>
		</div>
		<?php }; ?>	
		<?php
			$download_url = add_query_arg( 'download_cus_info', 'true' );
		?>
		<div class="pd-wcch-download"><a class="button btn" href="<?php echo esc_url($download_url);?>"><?php echo esc_html__( 'Download', 'puredevs-customer-history-for-woocommerce' ); ?></a></div>
        <?php
        $customer = new PD_Customer_History_Customers();
        $customer->prepare_items();
        if ( ! pdwchs_is_pro_active() ) {
            $pdwchs_cap   = (int) apply_filters( 'pdwchs_customer_list_limit', 100 );
            $pdwchs_total = $customer->pdwchs_get_full_customer_count();
            if ( $pdwchs_cap > 0 && $pdwchs_total > $pdwchs_cap ) {
                PDWCHS_Upsell::pdwchs_render_banner(
                    'customers-top100',
                    sprintf(
                        /* translators: %s is the number of customers */
                        esc_html__( 'The free version includes up to 100 customers in this list — you have %s in total. Customer History Pro includes every customer.', 'puredevs-customer-history-for-woocommerce' ),
                        number_format_i18n( $pdwchs_total )
                    )
                );
            }
        }
        $customer->display();
        ?>
	</div>
</div>
