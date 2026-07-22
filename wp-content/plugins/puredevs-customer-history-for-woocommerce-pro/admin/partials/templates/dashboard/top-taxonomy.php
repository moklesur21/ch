<?php
defined( 'ABSPATH' ) or exit;

$taxonomies = class_exists( 'PDWCHS_Pro_Helpers' )
	? PDWCHS_Pro_Helpers::pdwchs_get_product_taxonomies()
	: array();
if ( ! empty( $taxonomies ) ) :
	foreach ( $taxonomies as $pdwchs_taxonomy ) {
		$pdwchs_tax = get_taxonomy( $pdwchs_taxonomy );
		?>
		<div class="pd-woo-main-container">
			<div class="pd-woo-panel">
				<div class="pd-woo-header pd-woo-clearfix">
					<h2 class="pd-woo-float-left"><span class="dashicons dashicons-flag"></span>&nbsp;&nbsp;
						<?php
						/* translators: %s is the taxonomy name */
						echo esc_html( sprintf( __( 'Most viewed %s', 'puredevs-customer-history-for-woocommerce' ), $pdwchs_tax->labels->name ) );
					?>
					</h2>
					<div class="chart-table-toggle pd-woo-float-right">
						<span class="pd-toggle-btn pd-active-btn" data-target="pd-table-content">
							<span class="dashicons dashicons-editor-table" title="<?php echo esc_attr__( 'Table View', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
						</span>
						<span class="pd-toggle-btn" data-target="pd-chart-content">
							<span class="dashicons dashicons-chart-pie" title="<?php echo esc_attr__( 'Chart View', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
						</span>
						<span>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-' . strtolower( $pdwchs_tax->labels->singular_name ) . '-stats&pd_taxonomy=' . strtolower( $pdwchs_tax->name ) ) ); ?>" class="view-all-link">
								<span class="dashicons dashicons-admin-site-alt2" title="<?php echo esc_attr__( 'View All', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
							</a>
						</span>
						<span class="toggle-section toggle-btn pd-woo-toggle-btn" data-toggle-content="section-top-<?php echo esc_attr( strtolower( $pdwchs_tax->labels->singular_name ) ); ?>">
							<span class="dashicons dashicons-arrow-down-alt2" title="<?php echo esc_attr__( 'Click to Expend', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
						</span>
					</div>
				</div>
				<div id="section-top-<?php echo esc_attr( strtolower( $pdwchs_tax->labels->singular_name ) ); ?>" class="pd-woo-clearfix">
					<table class="wp-list-table widefat fixed striped posts pd-table-content">
						<tr>
							<th><?php echo esc_html( $pdwchs_tax->labels->singular_name ); ?></th>
							<th><?php echo esc_html__( 'Count', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						</tr>
						<?php
						$top_terms = array();
						$searches  = PDWCHS_Search_Stats_Data::get_url_counts_for_taxonomy( $pdwchs_tax->labels->singular_name . '/', array( 'limit' => 5 ) );
						if ( ! empty( $searches ) ) {
							foreach ( $searches as $pdwchs_search ) {
								$search_url  = explode( '/', $pdwchs_search->url );
								$pdwchs_term = get_term_by( 'slug', end( $search_url ), $pdwchs_tax->name );
								if ( $pdwchs_term && ! is_wp_error( $pdwchs_term ) ) {
									$top_terms[] = array(
										'searchTerm' => $pdwchs_term->name,
										'searchQty'  => $pdwchs_search->qta,
									);
									?>
									<tr>
										<td><?php echo esc_html( $pdwchs_term->name ); ?></td>
										<td><?php echo esc_html( $pdwchs_search->qta ); ?></td>
									</tr>
									<?php
								}
							}
						}
						?>
					</table>
					<script>
						jQuery(function($) {
							var trmid = 'top-<?php echo esc_js( strtolower( $pdwchs_tax->labels->singular_name ) ); ?>';
							var el = document.getElementById(trmid);
							if ( ! el || typeof Chart === 'undefined' ) { return; }
							var pdData = <?php echo wp_json_encode( ! empty( $top_terms ) ? $top_terms : array() ); ?>;
							new Chart(el, {
								type: 'doughnut',
								data: {
									labels: pdData.map(function(r){ return r.searchTerm; }),
									datasets: [{ data: pdData.map(function(r){ return r.searchQty; }), backgroundColor: ['#3b82f6','#ef4444','#10b981','#f59e0b','#8b5cf6','#ec4899','#14b8a6','#f97316','#6366f1','#84cc16'] }]
								},
								options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
							});
						});
					</script>
					<div class="pd-chart-content">
						<div style="position:relative;height:280px;"><canvas id="top-<?php echo esc_attr( strtolower( $pdwchs_tax->labels->singular_name ) ); ?>" class="taxonomy-terms"></canvas></div>
					</div>
				</div>
			</div>
		</div>
	<?php } ?>
<?php endif; ?>
