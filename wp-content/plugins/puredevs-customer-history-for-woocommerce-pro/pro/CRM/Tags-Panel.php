<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Tags.php';

/**
 * Manual Tags — customer profile panel.
 *
 * Presentation for Feature 15.10 manual tags (chunk R15-16C). Renders the
 * assigned-tag chips and the add row on the customer profile via the H8 seam
 * (pdwchs_customer_details_intelligence_cards), and enqueues its assets on
 * that page only. All escaping happens here and in the template.
 *
 * Reads through PDWCHS_Customer_Tags's public static readers and never
 * instantiates it — R15-16B's visibility contract says a second instance would
 * double-register its five AJAX hooks. Every mutation goes through that
 * class's existing nonce-and-capability-guarded endpoints; this class adds no
 * endpoint of its own.
 *
 * Not to be confused with the Behavioral Tags card rendered at priority 10 on
 * the same seam: those are detected automatically, these are the store owner's
 * own vocabulary applied by hand.
 *
 * @since 3.1.1
 */
class PDWCHS_Tags_Panel {

	/** Admin page slug this panel and its assets belong to. */
	const PAGE_SLUG = 'pd-woo-customer';

	public function __construct() {
		// Priority 20 renders this panel immediately below the automatic
		// Behavioral Tags card, which hooks the same seam at 10 — the two read
		// as related but distinct. Free Notes holds 5; 15 is left free.
		add_action( 'pdwchs_customer_details_intelligence_cards', array( $this, 'pdwchs_render_panel' ), 20 ); // H8.
		add_action( 'pdwchs_enqueue_admin_styles', array( $this, 'pdwchs_enqueue_styles' ) );   // H4.
		add_action( 'pdwchs_enqueue_admin_scripts', array( $this, 'pdwchs_enqueue_scripts' ) ); // H5.
	}

	// -------------------------------------------------------------------
	// Render
	// -------------------------------------------------------------------

	/**
	 * Render the manual tags panel on the customer profile page.
	 *
	 * Hooked to the pdwchs_customer_details_intelligence_cards seam, which
	 * passes the sanitized-string $user_id from customer-details.php.
	 *
	 * The capability check repeats the page's own gate
	 * (customer-details.php:14) as defence in depth: the seam fires
	 * unconditionally and nothing guarantees a future caller gates it.
	 *
	 * @param int|string $user_id Customer user id.
	 */
	public function pdwchs_render_panel( $user_id ) {
		$user_id = absint( $user_id );
		if ( 0 === $user_id ) {
			return;
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$tags = PDWCHS_Customer_Tags::pdwchs_get_customer_tags( $user_id );

		// false skips the LEFT JOIN COUNT — the datalist needs names and slugs,
		// not per-tag customer counts.
		$all_tags = PDWCHS_Customer_Tags::pdwchs_get_all_tags( false );

		require __DIR__ . '/templates/customer-tags-panel.php';
	}

	// -------------------------------------------------------------------
	// Assets
	// -------------------------------------------------------------------

	/**
	 * Enqueue the tags panel stylesheet on the customer profile page only.
	 *
	 * custom-style is declared as a dependency because it owns the
	 * .pd-ch-panel shell this panel sits in; H4 fires after it is enqueued, so
	 * declaring it orders these rules afterwards.
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
			'pdwchs-tags-panel-css',
			PDWCHS_PRO_PLUGIN_URL . 'admin/css/pdwchs-tags-panel.css',
			array( 'custom-style' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION
		);
	}

	/**
	 * Enqueue and localize the tags panel script on the customer profile page only.
	 *
	 * jquery is the only dependency: H5 fires before pdwchs-save-segment and
	 * custom-js, so neither can be declared, and neither is needed. The input
	 * is a plain <input list="…">, so there is no selectWoo dependency either.
	 *
	 * The nonce action must match the five check_ajax_referer() calls in
	 * pro/CRM/Tags.php exactly — a mismatch fails as a bare 403 from
	 * admin-ajax.php with nothing in the console.
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
			'pdwchs-tags-panel-js',
			PDWCHS_PRO_PLUGIN_URL . 'admin/js/pdwchs-tags-panel.js',
			array( 'jquery' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION,
			true
		);
		wp_localize_script( 'pdwchs-tags-panel-js', 'pdwchs_tags_panel_vars', array(
			'ajaxurl'    => admin_url( 'admin-ajax.php' ),
			'nonce'      => wp_create_nonce( 'pdwchs_tags_nonce' ),
			'customerId' => isset( $_GET['user_id'] ) ? absint( wp_unslash( $_GET['user_id'] ) ) : 0, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			'i18n'       => array(
				'confirmRemove' => __( 'Remove this tag from the customer?', 'puredevs-customer-history-for-woocommerce' ),
				'emptyTag'      => __( 'Please enter a tag name first.', 'puredevs-customer-history-for-woocommerce' ),
				'errorFallback' => __( 'Something went wrong. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
			),
		) );
	}
}
