<?php
defined( 'ABSPATH' ) or exit;

/*
 * Free Search Analytics admin page.
 * Renders: Top Keyword Searches, Last Searches, Most Viewed Products, Top Taxonomy Views.
 */

include 'top-nav.php';

$pdwchs_search = ! empty( $_GET['search_session'] ) ? sanitize_text_field( wp_unslash( $_GET['search_session'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<br class="clear">
<div id="pd-customer-history">
	<div id="pd-search-analytics" class="wrap">
		<h1><?php echo esc_html__( 'Search Analytics', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

		<?php
		if ( ! pdwchs_is_pro_active() && PDWCHS_Data_Window::pdwchs_was_clamped() ) {
			pdwchs_record_first_cap_hit( 'data_window' );
			PDWCHS_Upsell::pdwchs_render_banner(
				'data-window-90d',
				esc_html__( 'The free version includes your most recent 90 days of activity. Customer History Pro includes your full history — your older data is already collected and waiting.', 'puredevs-customer-history-for-woocommerce' )
			);
		}
		?>

		<?php if ( current_user_can( 'manage_woocommerce' ) && ! get_option( 'pd-woo-default_save_admin_session' ) ) : ?>
			<div class="notice notice-info inline" style="margin:10px 0;">
				<p>
					<?php echo esc_html__( 'Heads up: your own administrator activity isn’t recorded by default, so this page stays empty while you browse the store as an admin. To see searches and product views here, visit your store while logged out or as a customer.', 'puredevs-customer-history-for-woocommerce' ); ?>
					<?php if ( pdwchs_is_pro_active() ) { echo ' ' . esc_html__( 'Or set “Save ‘admin’ sessions” to Yes under Settings.', 'puredevs-customer-history-for-woocommerce' ); } ?>
				</p>
			</div>
		<?php endif; ?>

		<div class="pd-woo-panel">
			<h2><?php echo esc_html__( 'Top Keyword Searches', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
			<table class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Keyword', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th><?php echo esc_html__( 'Count', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$kw_searches = PDWCHS_Search_Stats_Data::get_keyword_counts( array( 'search' => $pdwchs_search, 'limit' => 20 ) );
				if ( ! empty( $kw_searches ) ) :
					foreach ( $kw_searches as $row ) :
						$url_parts = explode( '::', $row->url );
						if ( isset( $url_parts[1] ) && 'search' === $url_parts[1] && ! empty( $url_parts[2] ) ) :
				?>
					<tr>
						<td><?php echo esc_html( $url_parts[2] ); ?></td>
						<td><?php echo esc_html( $row->qta ); ?></td>
					</tr>
				<?php
						endif;
					endforeach;
				endif;
				?>
				</tbody>
			</table>
		</div>

		<div class="pd-woo-panel" style="margin-top:20px;">
			<h2><?php echo esc_html__( 'Last Searches', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
			<table class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Keyword', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th><?php echo esc_html__( 'Date', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				$last_searches = PDWCHS_Search_Stats_Data::get_last_searches( 20, array( 'search' => $pdwchs_search ) );
				if ( ! empty( $last_searches ) ) :
					foreach ( $last_searches as $row ) :
						$url_parts = explode( '::', $row->url );
						if ( isset( $url_parts[1] ) && 'search' === $url_parts[1] && ! empty( $url_parts[2] ) ) :
				?>
					<tr>
						<td><?php echo esc_html( $url_parts[2] ); ?></td>
						<td><?php echo esc_html( $row->reg_date ); ?></td>
					</tr>
				<?php
						endif;
					endforeach;
				endif;
				?>
				</tbody>
			</table>
		</div>

		<div class="pd-woo-panel" style="margin-top:20px;">
			<h2><?php echo esc_html__( 'Zero-Result Searches', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
			<?php
			$zero_results = PDWCHS_Search_Stats_Data::pdwchs_get_zero_result_terms( array( 'search' => $pdwchs_search, 'limit' => 20 ) );
			if ( ! empty( $zero_results ) ) :
			?>
			<table class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Search Term', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th><?php echo esc_html__( 'Times Searched', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th><?php echo esc_html__( 'Last Searched', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				foreach ( $zero_results as $row ) :
					$url_parts = explode( '::', $row->url );
					if ( isset( $url_parts[1] ) && 'search' === $url_parts[1] && ! empty( $url_parts[2] ) ) :
						$last_ts = strtotime( $row->last_searched );
				?>
					<tr>
						<td><?php echo esc_html( $url_parts[2] ); ?></td>
						<td><?php echo esc_html( number_format_i18n( (int) $row->qta ) ); ?></td>
						<td><?php echo esc_html( $last_ts ? date_i18n( get_option( 'date_format' ), $last_ts, true ) : $row->last_searched ); ?></td>
					</tr>
				<?php
					endif;
				endforeach;
				?>
				</tbody>
			</table>
			<?php else : ?>
				<p style="color:#666;">
				<?php
				$pdwchs_zr_since = get_option( 'pdwchs_results_count_since' );
				if ( $pdwchs_zr_since ) {
					echo esc_html(
						sprintf(
							/* translators: %s: the date on which zero-result collection began on this site. */
							__( 'No zero-result searches recorded yet. Collecting since %s — terms appear here as visitors search your store and find nothing.', 'puredevs-customer-history-for-woocommerce' ),
							date_i18n( get_option( 'date_format' ), (int) $pdwchs_zr_since )
						)
					);
				} else {
					echo esc_html__( 'No zero-result searches recorded yet. Terms appear here as visitors search your store and find nothing.', 'puredevs-customer-history-for-woocommerce' );
				}
				?>
				</p>
			<?php endif; ?>
		</div>

		<div class="pd-woo-panel" style="margin-top:20px;">
			<h2><?php echo esc_html__( 'Most Viewed Products', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
			<?php
			$site_pages      = PDWCHS_Search_Stats_Data::get_url_counts( array( 'search' => $pdwchs_search ) );
			$viewed_products = PDWCHS_Helper::pdwchs_get_view_products( $site_pages );
			usort( $viewed_products, function( $a, $b ) {
				if ( $a->qta < $b->qta ) return 1;
				if ( $a->qta > $b->qta ) return -1;
				return 0;
			} );
			$viewed_products = array_slice( $viewed_products, 0, 20 );
			?>
			<table class="wp-list-table widefat fixed striped posts">
				<thead>
					<tr>
						<th><?php echo esc_html__( 'Product', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th><?php echo esc_html__( 'View Count', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				if ( ! empty( $viewed_products ) ) :
					foreach ( $viewed_products as $vp ) :
						$slug  = explode( '/', $vp->url );
						$pdwchs_posts = get_posts( array(
							'name'           => end( $slug ),
							'posts_per_page' => 1,
							'post_type'      => 'product',
							'post_status'    => 'publish',
						) );
						if ( ! empty( $pdwchs_posts ) ) :
				?>
					<tr>
						<td><a href="<?php echo esc_url( get_permalink( $pdwchs_posts[0] ) ); ?>" target="_blank"><?php echo esc_html( $pdwchs_posts[0]->post_title ); ?></a></td>
						<td><?php echo esc_html( $vp->qta ); ?></td>
					</tr>
				<?php
						endif;
					endforeach;
				endif;
				?>
				</tbody>
			</table>
		</div>

		<?php
		$taxonomies = class_exists( 'PDWCHS_Pro_Helpers' )
				? PDWCHS_Pro_Helpers::pdwchs_get_product_taxonomies()
				: array();
		if ( ! empty( $taxonomies ) ) :
			foreach ( $taxonomies as $pdwchs_taxonomy ) :
				$pdwchs_tax = get_taxonomy( $pdwchs_taxonomy );
				?>
				<div class="pd-woo-panel" style="margin-top:20px;">
					<h2><?php
						/* translators: %s is the taxonomy name */
						echo esc_html( sprintf( __( 'Top %s Views', 'puredevs-customer-history-for-woocommerce' ), $pdwchs_tax->labels->name ) );
					?></h2>
					<table class="wp-list-table widefat fixed striped posts">
						<thead>
							<tr>
								<th><?php echo esc_html( $pdwchs_tax->labels->singular_name ); ?></th>
								<th><?php echo esc_html__( 'Count', 'puredevs-customer-history-for-woocommerce' ); ?></th>
							</tr>
						</thead>
						<tbody>
						<?php
						$tax_stats = PDWCHS_Search_Stats_Data::get_url_counts_for_taxonomy( $pdwchs_tax->labels->singular_name . '/', array( 'limit' => 20 ) );
						if ( ! empty( $tax_stats ) ) :
							foreach ( $tax_stats as $ts ) :
								$slug        = explode( '/', $ts->url );
								$pdwchs_term = get_term_by( 'slug', end( $slug ), $pdwchs_tax->name );
								if ( $pdwchs_term && ! is_wp_error( $pdwchs_term ) ) :
						?>
							<tr>
								<td><?php echo esc_html( $pdwchs_term->name ); ?></td>
								<td><?php echo esc_html( $ts->qta ); ?></td>
							</tr>
						<?php
								endif;
							endforeach;
						endif;
						?>
						</tbody>
					</table>
				</div>
		<?php
			endforeach;
		endif;
		?>

	</div>
</div>
