<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Order-Panel-Data.php';

/**
 * Order-screen intelligence panel — meta box, render callback and stylesheet.
 *
 * Puts the R15-14A payload on the WooCommerce order edit screen: health score, RFM
 * segment, lifecycle stage, lifetime value, last seen and predicted next order, in the
 * side column, with one link into the full customer profile.
 *
 * Presentation only. It assembles nothing, calculates nothing and writes nothing —
 * PDWCHS_Order_Panel_Data owns the data and every esc_* call lives here or in the
 * template. There is no button, no form and no endpoint of any kind: this plugin
 * observes and reports (CLAUDE.md rule 8).
 *
 * @since 3.1.0
 */
class PDWCHS_Order_Panel {

	/** Meta box id. */
	const BOX_ID = 'pdwchs_order_intelligence';

	/** Style handle. */
	const STYLE_HANDLE = 'pdwchs-order-panel-css';

	/**
	 * The two screens this panel lives on: legacy post storage and HPOS.
	 *
	 * One accessor, two callers — the meta box and the stylesheet must agree on which
	 * screens they belong to, or the panel renders unstyled on a screen someone added
	 * to only one of two hardcoded lists.
	 *
	 * @return string[]
	 */
	private static function pdwchs_screens() {
		return array( 'shop_order', 'woocommerce_page_wc-orders' );
	}

	/** @var PDWCHS_Order_Panel_Data */
	private $data;

	/**
	 * Two registrations, and no more.
	 *
	 * No H5 script seam (there is no script), no AJAX endpoint seam, no admin-menu seam
	 * (there is no page), no cron seam, and no H8 intelligence-card seam — the customer
	 * profile already renders those five cards.
	 */
	public function __construct() {
		$this->data = new PDWCHS_Order_Panel_Data();

		add_action( 'add_meta_boxes', array( $this, 'pdwchs_register_meta_box' ) );
		add_action( 'pdwchs_enqueue_admin_styles', array( $this, 'pdwchs_enqueue_styles' ) ); // H4
	}

	/**
	 * Register the meta box on both order screens, unconditionally.
	 *
	 * add_meta_box() takes an array of screens and simply never fires on one that does
	 * not exist on this site, so registering both is correct with HPOS on, with HPOS off,
	 * and in the sync-enabled middle state where both storage paths are live — and it
	 * survives a store toggling HPOS after install, which a value resolved once at boot
	 * would not. No HPOS probe: WooCommerce's order-screen helper is newer than the WC
	 * floor this plugin declares, and two literals need no detection anyway.
	 *
	 * 'side', not 'normal': a six-field summary belongs beside WooCommerce's own order
	 * boxes, not above the line items. Screen Options already lets the owner hide or
	 * reorder it, which is why this chunk ships no setting to toggle the panel.
	 *
	 * @return void
	 */
	public function pdwchs_register_meta_box() {
		add_meta_box(
			self::BOX_ID,
			__( 'Customer Intelligence', 'puredevs-customer-history-for-woocommerce' ),
			array( $this, 'pdwchs_render_panel' ),
			self::pdwchs_screens(),
			'side',
			'default'
		);
	}

	/**
	 * Render the panel.
	 *
	 * Legacy post storage passes a WP_Post, HPOS passes a WC_Order — wc_get_order()
	 * normalises the two and a falsy return bails, so a trashed or unreadable order
	 * cannot white-screen the order edit screen. CRUD getters only; no order query of
	 * any kind happens here.
	 *
	 * The capability gate is first, before the order is resolved and before the payload
	 * is assembled: a user with edit_shop_orders alone can reach this screen but is not
	 * entitled to the customer's scores.
	 *
	 * @param WP_Post|WC_Order $post_or_order Screen object, shape depending on storage.
	 * @return void
	 */
	public function pdwchs_render_panel( $post_or_order ) {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$order = ( $post_or_order instanceof WP_Post )
			? wc_get_order( $post_or_order->ID )
			: $post_or_order;

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$user_id = absint( $order->get_customer_id() );
		$panel   = $this->data->pdwchs_get_panel_data( $user_id );

		// A guest order and a deleted user both come back has_customer => false, so one
		// flag covers both. A link to a profile of a user that no longer exists is worse
		// than no link at all.
		$profile_url = ( $panel['has_customer'] )
			? add_query_arg(
				array(
					'page'    => 'pd-woo-customer',
					'user_id' => $user_id,
				),
				admin_url( 'admin.php' )
			)
			: '';

		require __DIR__ . '/templates/order-panel.php';
	}

	/**
	 * Enqueue the panel stylesheet on the two order screens only.
	 *
	 * The screen id, not the page query parameter: the legacy order screen is
	 * post.php?post=N&action=edit and carries no page parameter at all, so the guard every
	 * other Pro listener uses would silently never load this stylesheet on half the installs.
	 *
	 * PDWCHS_CUSTOMER_HISTORY_VERSION, never a request timestamp — a timestamped version
	 * busts the browser cache on every admin request. custom-style is declared as a
	 * dependency so the panel's rules land after the plugin's base styles.
	 *
	 * @return void
	 */
	public function pdwchs_enqueue_styles() {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, self::pdwchs_screens(), true ) ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			PDWCHS_PRO_PLUGIN_URL . 'admin/css/pd-customer-history-order-panel.css',
			array( 'custom-style' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION
		);
	}
}
