<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;

// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only view; capability enforced by the page handler before this template loads.
$cart_id = absint( $_GET['cart_id'] ?? 0 );
if ( ! $cart_id || ! current_user_can( 'manage_woocommerce' ) ) {
	echo '<p>' . esc_html__( 'Unauthorized or invalid cart.', 'puredevs-customer-history-for-woocommerce' ) . '</p>';
	return;
}

// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
$cart = $wpdb->get_row(
	$wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}pdwchs_carts WHERE id = %d LIMIT 1",
		$cart_id
	)
);

if ( ! $cart ) {
	echo '<p>' . esc_html__( 'Cart not found.', 'puredevs-customer-history-for-woocommerce' ) . '</p>';
	return;
}

$cart_items = json_decode( $cart->cart_contents, true );
if ( ! is_array( $cart_items ) ) {
	$cart_items = array();
}

$date_format   = get_option( 'date_format' ) . ' ' . get_option( 'time_format' );
$currency_args = array( 'currency' => esc_attr( $cart->currency ) );
?>

<p class="pdwchs-cart-detail-nav">
	<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-abandoned-carts' ) ); ?>" class="button">
		&larr; <?php esc_html_e( 'Back to Abandoned Carts', 'puredevs-customer-history-for-woocommerce' ); ?>
	</a>
</p>

<h2>
	<?php esc_html_e( 'Cart Detail', 'puredevs-customer-history-for-woocommerce' ); ?>
	<span class="pdwchs-cart-status pdwchs-status-<?php echo esc_attr( $cart->status ); ?>">
		<?php echo esc_html( ucfirst( $cart->status ) ); ?>
	</span>
</h2>

<div class="pdwchs-cart-detail-grid">

	<!-- Main column -->
	<div class="pdwchs-cart-detail-main">

		<!-- Customer block -->
		<div class="pdwchs-cart-detail-meta-box">
			<h3><?php esc_html_e( 'Customer', 'puredevs-customer-history-for-woocommerce' ); ?></h3>

			<?php if ( (int) $cart->user_id > 0 ) : ?>
				<?php $user = get_userdata( intval( $cart->user_id ) ); ?>
				<?php if ( $user ) : ?>
					<?php $profile_url = admin_url( 'admin.php?page=pd-woo-customer&user_id=' . intval( $cart->user_id ) ); ?>
					<p>
						<strong>
							<a href="<?php echo esc_url( $profile_url ); ?>"><?php echo esc_html( $user->display_name ); ?></a>
						</strong><br>
						<small><?php echo esc_html( $user->user_email ); ?></small>
					</p>
				<?php else : ?>
					<p>
						<?php echo esc_html( $cart->email ); ?>
						<em><?php esc_html_e( '(account deleted)', 'puredevs-customer-history-for-woocommerce' ); ?></em>
					</p>
				<?php endif; ?>
			<?php else : ?>
				<p><span class="pdwchs-guest-badge"><?php esc_html_e( 'Guest', 'puredevs-customer-history-for-woocommerce' ); ?></span></p>
				<?php if ( ! empty( $cart->email ) ) : ?>
					<p><?php echo esc_html( $cart->email ); ?></p>
				<?php endif; ?>
				<?php if ( ! empty( $cart->ip_address ) ) : ?>
					<p>
						<strong><?php esc_html_e( 'IP:', 'puredevs-customer-history-for-woocommerce' ); ?></strong>
						<?php echo esc_html( $cart->ip_address ); ?>
					</p>
				<?php endif; ?>
				<?php if ( ! empty( $cart->user_agent ) ) : ?>
					<p>
						<strong><?php esc_html_e( 'User Agent:', 'puredevs-customer-history-for-woocommerce' ); ?></strong>
						<?php
						$ua = $cart->user_agent;
						if ( strlen( $ua ) > 80 ) {
							echo esc_html( substr( $ua, 0, 80 ) ) . '&hellip;';
						} else {
							echo esc_html( $ua );
						}
						?>
					</p>
				<?php endif; ?>
			<?php endif; ?>

			<p>
				<strong><?php esc_html_e( 'Cart created:', 'puredevs-customer-history-for-woocommerce' ); ?></strong>
				<?php echo esc_html( wp_date( $date_format, strtotime( $cart->created_at ) ) ); ?>
			</p>
		</div><!-- /.pdwchs-cart-detail-meta-box -->

		<!-- Cart contents -->
		<div class="pdwchs-cart-detail-meta-box">
			<h3><?php esc_html_e( 'Cart Contents', 'puredevs-customer-history-for-woocommerce' ); ?></h3>

			<?php if ( ! empty( $cart_items ) ) : ?>
				<table class="wp-list-table widefat fixed striped posts pdwchs-cart-items-table">
					<thead>
						<tr>
							<th class="column-image"><?php esc_html_e( 'Image', 'puredevs-customer-history-for-woocommerce' ); ?></th>
							<th class="column-product"><?php esc_html_e( 'Product', 'puredevs-customer-history-for-woocommerce' ); ?></th>
							<th class="column-qty"><?php esc_html_e( 'Qty', 'puredevs-customer-history-for-woocommerce' ); ?></th>
							<th class="column-unit-price"><?php esc_html_e( 'Unit Price', 'puredevs-customer-history-for-woocommerce' ); ?></th>
							<th class="column-line-total"><?php esc_html_e( 'Line Total', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $cart_items as $item ) : ?>
							<?php
							$product_id = ! empty( $item['variation_id'] ) ? intval( $item['variation_id'] ) : intval( $item['product_id'] );
							$parent_id  = intval( $item['product_id'] );
							$product    = wc_get_product( $product_id );

							$image_html = $product
								? wp_kses_post( $product->get_image( array( 36, 36 ) ) )
								: '<span class="pdwchs-no-image">&mdash;</span>';

							if ( $product ) {
								$edit_link = get_edit_post_link( $parent_id );
								$item_name = esc_html( $product->get_name() );
							} else {
								$edit_link = '';
								$item_name = esc_html__( '(Product deleted)', 'puredevs-customer-history-for-woocommerce' );
							}

							$variation_html = '';
							if ( ! empty( $item['variation'] ) && is_array( $item['variation'] ) ) {
								foreach ( $item['variation'] as $attr => $val ) {
									$variation_html .= '<br><small>'
										. esc_html( wc_attribute_label( str_replace( 'attribute_', '', $attr ) ) )
										. ': ' . esc_html( $val )
										. '</small>';
								}
							}

							$qty        = intval( $item['quantity'] );
							$unit_price = ( $qty > 0 && isset( $item['line_subtotal'] ) )
								? floatval( $item['line_subtotal'] ) / $qty
								: 0.0;
							$line_total = isset( $item['line_subtotal'] ) ? floatval( $item['line_subtotal'] ) : 0.0;
							?>
							<tr>
								<td class="column-image"><?php echo $image_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with wp_kses_post(). ?></td>
								<td class="column-product">
									<?php if ( $edit_link ) : ?>
										<a href="<?php echo esc_url( $edit_link ); ?>"><?php echo $item_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_html(). ?></a>
									<?php else : ?>
										<?php echo $item_name; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built with esc_html(). ?>
									<?php endif; ?>
									<?php echo $variation_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from esc_html() calls. ?>
								</td>
								<td class="column-qty"><?php echo esc_html( number_format_i18n( $qty ) ); ?></td>
								<td class="column-unit-price"><?php echo wp_kses_post( wc_price( $unit_price, $currency_args ) ); ?></td>
								<td class="column-line-total"><?php echo wp_kses_post( wc_price( $line_total, $currency_args ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
					<tfoot>
						<tr>
							<td colspan="4" style="text-align: right; font-weight: 600;"><?php esc_html_e( 'Cart Total', 'puredevs-customer-history-for-woocommerce' ); ?></td>
							<td class="column-line-total"><?php echo wp_kses_post( wc_price( floatval( $cart->cart_total ), $currency_args ) ); ?></td>
						</tr>
					</tfoot>
				</table>
			<?php else : ?>
				<p><?php esc_html_e( 'No items captured.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
			<?php endif; ?>
		</div><!-- /.pdwchs-cart-detail-meta-box -->

	</div><!-- /.pdwchs-cart-detail-main -->

	<!-- Sidebar -->
	<div class="pdwchs-cart-detail-sidebar">

		<!-- Status timeline -->
		<div class="pdwchs-cart-detail-meta-box">
			<h3><?php esc_html_e( 'Status Timeline', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
			<?php
			$timeline_entries = array(
				array( 'label' => __( 'Cart created', 'puredevs-customer-history-for-woocommerce' ), 'ts' => $cart->created_at ),
				array( 'label' => __( 'Last updated', 'puredevs-customer-history-for-woocommerce' ), 'ts' => $cart->updated_at ),
				array( 'label' => __( 'Marked abandoned', 'puredevs-customer-history-for-woocommerce' ), 'ts' => $cart->abandoned_at ),
				array( 'label' => __( 'Recovered', 'puredevs-customer-history-for-woocommerce' ), 'ts' => $cart->recovered_at ),
				array( 'label' => __( 'Checkout completed', 'puredevs-customer-history-for-woocommerce' ), 'ts' => $cart->completed_at ),
			);

			$timeline_entries = array_values( array_filter( $timeline_entries, function( $entry ) {
				return ! empty( $entry['ts'] );
			} ) );

			$max_ts  = 0;
			$max_idx = 0;
			foreach ( $timeline_entries as $idx => $entry ) {
				$parsed = strtotime( $entry['ts'] );
				if ( false !== $parsed && $parsed > $max_ts ) {
					$max_ts  = $parsed;
					$max_idx = $idx;
				}
			}
			?>
			<ul class="pdwchs-cart-detail-timeline">
				<?php foreach ( $timeline_entries as $idx => $entry ) : ?>
					<li<?php echo $idx === $max_idx ? ' class="pdwchs-timeline-current"' : ''; ?>>
						<span class="pdwchs-timeline-label"><?php echo esc_html( $entry['label'] ); ?></span>
						<?php echo esc_html( wp_date( $date_format, strtotime( $entry['ts'] ) ) ); ?>
					</li>
				<?php endforeach; ?>
			</ul>
		</div><!-- /.pdwchs-cart-detail-meta-box -->

		<?php if ( ! empty( $cart->order_id ) ) : ?>
			<div class="pdwchs-cart-detail-meta-box">
				<h3><?php esc_html_e( 'Linked Order', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
				<?php
				$linked_order = wc_get_order( intval( $cart->order_id ) );
				if ( $linked_order ) {
					$order_edit_url = esc_url( $linked_order->get_edit_order_url() );
					$order_num      = esc_html( $linked_order->get_order_number() );
					echo '<p>' . esc_html__( 'Linked order:', 'puredevs-customer-history-for-woocommerce' ) . ' <a href="' . $order_edit_url . '">#' . $order_num . '</a></p>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- $order_edit_url esc_url'd, $order_num esc_html'd.
				} else {
					echo '<p>' . esc_html( sprintf( __( 'Linked order: #%d (deleted)', 'puredevs-customer-history-for-woocommerce' ), intval( $cart->order_id ) ) ) . '</p>';
				}
				?>
			</div><!-- /.pdwchs-cart-detail-meta-box -->
		<?php endif; ?>

	</div><!-- /.pdwchs-cart-detail-sidebar -->

</div><!-- /.pdwchs-cart-detail-grid -->
