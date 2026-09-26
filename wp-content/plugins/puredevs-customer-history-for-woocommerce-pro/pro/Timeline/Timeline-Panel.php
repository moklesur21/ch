<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Timeline-Data.php';

/**
 * Customer activity timeline — profile panel.
 *
 * Renders the merged event stream on the customer profile via the H7 seam
 * (pdwchs_customer_details_sessions_section), enqueues its assets on that page
 * only, and serves the filter / "Load older" endpoint. Presentation layer: all
 * escaping happens here and in the template, never in PDWCHS_Timeline_Data.
 *
 * @since 3.1.0
 */
class PDWCHS_Timeline_Panel {

	/** Admin page slug this panel and its assets belong to. */
	const PAGE_SLUG = 'pd-woo-customer';

	/** @var PDWCHS_Timeline_Data */
	private $data;

	/**
	 * Wire the four seams this panel needs.
	 *
	 * Default priority on H7: once R15-11A removed the Sessions-List-Table
	 * registration this class is the only listener there, so there is no
	 * ordering question to answer.
	 *
	 * No wp_ajax_nopriv_ twin: the endpoint has no logged-out use, and the
	 * right answer is not to register it rather than to register it and reject.
	 */
	public function __construct() {
		$this->data = new PDWCHS_Timeline_Data();

		add_action( 'pdwchs_customer_details_sessions_section', array( $this, 'pdwchs_render_panel' ) );   // H7
		add_action( 'pdwchs_enqueue_admin_styles',              array( $this, 'pdwchs_enqueue_styles' ) ); // H4

		add_action( 'wp_ajax_pdwchs_timeline_load_more', array( $this, 'pdwchs_ajax_load_more' ) );
		add_action( 'pdwchs_enqueue_admin_scripts',      array( $this, 'pdwchs_enqueue_scripts' ) ); // H5
	}

	/**
	 * Render the timeline panel in the wide main column of the customer profile.
	 *
	 * The seam passes the sanitized-string $user_id from customer-details.php;
	 * absint() at the boundary because the seam contract is loose.
	 *
	 * @param int|string $user_id Customer user id.
	 */
	public function pdwchs_render_panel( $user_id ) {
		$user_id = absint( $user_id );
		if ( 0 === $user_id ) {
			return;
		}

		$page = $this->data->pdwchs_get_events( $user_id );

		// Unpacked as documented template variables. $has_more and $next_cursor prime
		// the "Load older" button — the first click continues from where this page ended.
		$events      = $page['events'];
		$has_more    = $page['has_more'];
		$next_cursor = $page['next_cursor'];

		require __DIR__ . '/templates/timeline.php';
	}

	/**
	 * Render one timeline event as an escaped <li>.
	 *
	 * The single source of row markup: the template calls it for the first page and the
	 * AJAX handler calls it for every page after. All escaping happens here.
	 *
	 * @param array $event Event array from PDWCHS_Timeline_Data.
	 * @return string Escaped HTML.
	 */
	public function pdwchs_render_event( $event ) {
		$icons = array(
			'searched'       => 'search',
			'viewed'         => 'visibility',
			'added_to_cart'  => 'cart',
			'ordered'        => 'yes-alt',
			'cart_abandoned' => 'image-rotate',
			'cart_recovered' => 'undo',
		);

		$icon = isset( $icons[ $event['type'] ] ) ? $icons[ $event['type'] ] : 'marker';
		$meta = isset( $event['meta'] ) ? $event['meta'] : array();

		// Relative in the visible text, absolute in the title. Both sides of
		// human_time_diff() are on the site clock: event_time is site-local
		// wall clock, so comparing it against a UTC time() would report every
		// event as hours out.
		$ts       = strtotime( $event['event_time'] );
		$absolute = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts );
		$relative = sprintf(
			/* translators: %s: human-readable time difference, e.g. "3 hours". */
			__( '%s ago', 'puredevs-customer-history-for-woocommerce' ),
			human_time_diff( $ts, current_time( 'timestamp' ) ) // phpcs:ignore WordPress.DateTime.CurrentTimeTimestamp.Requested
		);

		ob_start();
		?>
		<li class="pdwchs-timeline-item pdwchs-timeline-<?php echo esc_attr( $event['type'] ); ?>">
			<span class="pdwchs-timeline-icon dashicons dashicons-<?php echo esc_attr( $icon ); ?>"></span>
			<span class="pdwchs-timeline-label">
				<?php
				switch ( $event['type'] ) :
					case 'searched':
						printf(
							/* translators: %s: the term the customer searched for. */
							esc_html__( 'Searched "%s"', 'puredevs-customer-history-for-woocommerce' ),
							esc_html( $event['label'] )
						);

						// isset(), never ! empty(): 0 means "this search found
						// nothing", which is exactly the case worth surfacing.
						// The key is absent entirely on pre-3.1.0 rows.
						if ( isset( $meta['results_count'] ) ) :
							$results_count = (int) $meta['results_count'];
							echo ' ';
							printf(
								/* translators: %s: number of results the search returned. */
								esc_html( _n( '— %s result', '— %s results', $results_count, 'puredevs-customer-history-for-woocommerce' ) ),
								esc_html( number_format_i18n( $results_count ) )
							);
						endif;
						break;

					case 'viewed':
						?>
						<a href="<?php echo esc_url( $event['link'] ); ?>" target="_blank" rel="noopener noreferrer">
							<?php
							printf(
								/* translators: %s: the page path the customer viewed. */
								esc_html__( 'Viewed %s', 'puredevs-customer-history-for-woocommerce' ),
								esc_html( $event['label'] )
							);
							?>
						</a>
						<?php
						break;

					case 'added_to_cart':
						?>
						<a href="<?php echo esc_url( $event['link'] ); ?>">
							<?php
							printf(
								/* translators: %s: product name, or #id when the product is gone. */
								esc_html__( 'Added to cart: %s', 'puredevs-customer-history-for-woocommerce' ),
								esc_html( $event['label'] )
							);
							?>
						</a>
						<?php
						$qty = isset( $meta['qty'] ) ? (int) $meta['qty'] : 0;
						if ( $qty > 1 ) {
							echo ' ';
							printf(
								/* translators: %s: quantity added to the cart. */
								esc_html__( '× %s', 'puredevs-customer-history-for-woocommerce' ),
								esc_html( number_format_i18n( $qty ) )
							);
						}
						break;

					case 'ordered':
						?>
						<a href="<?php echo esc_url( $event['link'] ); ?>">
							<?php
							printf(
								/* translators: %s: order number. */
								esc_html__( 'Ordered #%s', 'puredevs-customer-history-for-woocommerce' ),
								esc_html( $event['label'] )
							);
							?>
						</a>
						<?php
						$order_status = isset( $meta['status'] ) ? (string) $meta['status'] : '';
						if ( '' !== $order_status ) :
							$status_label = function_exists( 'wc_get_order_status_name' )
								? wc_get_order_status_name( $order_status )
								: $order_status;
							?>
							<span class="pdwchs-timeline-status pdwchs-status-<?php echo esc_attr( $order_status ); ?>"><?php echo esc_html( $status_label ); ?></span>
							<?php
						endif;
						break;

					case 'cart_abandoned':
					case 'cart_recovered':
						// The label already carries the translated "Cart abandoned —
						// N items" sentence from the data layer; only the money is
						// formatted here.
						echo esc_html( $event['label'] );
						echo ', ';
						echo wp_kses_post(
							wc_price(
								isset( $meta['cart_total'] ) ? $meta['cart_total'] : 0,
								array( 'currency' => isset( $meta['currency'] ) ? $meta['currency'] : '' )
							)
						);
						break;

					default:
						echo esc_html( $event['label'] );
						break;
				endswitch;
				?>
			</span>
			<span class="pdwchs-timeline-time" title="<?php echo esc_attr( $absolute ); ?>"><?php echo esc_html( $relative ); ?></span>
		</li>
		<?php
		return ob_get_clean();
	}

	/**
	 * Enqueue the timeline stylesheet on the customer profile page only.
	 *
	 * PDWCHS_CUSTOMER_HISTORY_VERSION, never time(): a time() version busts the
	 * browser cache on every admin page load. custom-style is declared as a
	 * dependency because it owns the .pd-ch-panel shell this panel sits in.
	 */
	public function pdwchs_enqueue_styles() {
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		wp_enqueue_style(
			'pdwchs-timeline-css',
			PDWCHS_PRO_PLUGIN_URL . 'admin/css/pd-customer-history-timeline.css',
			array( 'custom-style' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION
		);
	}

	/**
	 * Enqueue and localize the timeline script on the customer profile page only.
	 *
	 * jquery is the only dependency: this feature has no chart and no date picker,
	 * so moment and date-picker (already loaded plugin-wide) are not declared here.
	 *
	 * The nonce minted here uses the same action string as the template's
	 * wp_nonce_field() and the handler's check_ajax_referer() — a mismatch fails
	 * closed and presents as "the button does nothing".
	 */
	public function pdwchs_enqueue_scripts() {
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		wp_enqueue_script(
			'pdwchs-timeline-js',
			PDWCHS_PRO_PLUGIN_URL . 'admin/js/pdwchs-timeline.js',
			array( 'jquery' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION,
			true
		);
		wp_localize_script( 'pdwchs-timeline-js', 'pdwchs_timeline_vars', array(
			'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'pdwchs_timeline_nonce' ),
			'customerId' => isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'i18n'       => array(
				'loading'     => __( 'Loading…', 'puredevs-customer-history-for-woocommerce' ),
				'error'       => __( 'Could not load more activity. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
				'noMore'      => __( 'No older activity.', 'puredevs-customer-history-for-woocommerce' ),
				'emptyFilter' => __( 'No activity of this type for this customer.', 'puredevs-customer-history-for-woocommerce' ),
			),
		) );
	}

	/**
	 * AJAX: one older page of timeline events, already rendered as escaped rows.
	 *
	 * POST params: nonce, user_id, before (cursor), type (filter-bar key).
	 *
	 * Nonce first, capability second, sanitize third — check_ajax_referer() dies
	 * before anything else runs, so a forged request never reaches a capability
	 * lookup, let alone a query.
	 *
	 * The request carries user_id and the handler honours it: the caller has already
	 * proven manage_woocommerce, the same capability that gates the profile page the
	 * request came from. There is no per-customer permission model to enforce beyond
	 * that — only that the id is non-zero.
	 */
	public function pdwchs_ajax_load_more() {
		check_ajax_referer( 'pdwchs_timeline_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$user_id = isset( $_POST['user_id'] ) ? absint( wp_unslash( $_POST['user_id'] ) ) : 0;
		$before  = isset( $_POST['before'] ) ? sanitize_text_field( wp_unslash( $_POST['before'] ) ) : '';
		$type    = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';

		if ( 0 === $user_id ) {
			wp_send_json_error( array( 'message' => __( 'Invalid customer.', 'puredevs-customer-history-for-woocommerce' ) ) );
		}

		$page = $this->data->pdwchs_get_events(
			$user_id,
			array(
				'before' => $before,
				'type'   => $this->pdwchs_expand_type( $type ),
			)
		);

		$html = '';
		foreach ( $page['events'] as $event ) {
			$html .= $this->pdwchs_render_event( $event );
		}

		wp_send_json_success(
			array(
				'html'        => $html,
				'count'       => count( $page['events'] ),
				'next_cursor' => $page['next_cursor'],
				'has_more'    => (bool) $page['has_more'],
			)
		);
	}

	/**
	 * Expand a filter-bar key into the type list the data layer expects.
	 *
	 * "Carted" covers all three cart-ish types, so the button sends one value and
	 * this method owns the grouping. An unrecognised key returns the empty array,
	 * which the data layer reads as "all types": a tampered value yielding an empty
	 * stream would be indistinguishable from a customer with no activity.
	 *
	 * @param string $type Sanitized key from the request. '' = all.
	 * @return array
	 */
	private function pdwchs_expand_type( $type ) {
		$map = array(
			'searched'      => array( 'searched' ),
			'viewed'        => array( 'viewed' ),
			'added_to_cart' => array( 'added_to_cart', 'cart_abandoned', 'cart_recovered' ),
			'ordered'       => array( 'ordered' ),
		);

		return isset( $map[ $type ] ) ? $map[ $type ] : array();   // unknown → all
	}
}
