<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/RFM-Matrix-Data.php';

/**
 * RFM matrix — the dashboard heat-map panel.
 *
 * Presentation half of the pair whose data half is PDWCHS_RFM_Matrix_Data: it renders the recency ×
 * frequency population grid as a shaded table on pd-woo-dashboard and enqueues that panel's
 * stylesheet. Every esc_* call and every display string lives here or in the template, and every
 * number comes from the data class — this file performs no arithmetic beyond deriving a shade class
 * from count ÷ max.
 *
 * Server-rendered and complete with JavaScript disabled. Twenty-five integers do not need a chart
 * library or an endpoint, and the tree already renders a shaded grid this way in
 * Cohorts/templates/cohort-table.php. There is no wp_ajax_ action, no nonce, no localized payload and
 * no script anywhere in this chunk — the sibling RFM widget is JS because it is a doughnut chart, not
 * because dashboard widgets are JS.
 *
 * Two registrations. The panel mounts on H6, the seam the retention and LTV panels already use, so
 * PD_Customer_History_Intelligence is untouched — which is what places this panel after its six
 * widgets rather than beside the RFM doughnut. That trade was made deliberately: the alternative is a
 * require line inside a 1,722-line coordinator that Feature 15.14 Decision 6 otherwise keeps clean.
 *
 * Gating is structural — the whole pro/ tree loads only behind the licence gate, so the panel
 * disappears on a lapse with no gating code here.
 *
 * @since 3.1.2
 */
class PDWCHS_RFM_Matrix_Widget {

	/** Stylesheet handle. Verified unused elsewhere in the plugin. */
	const STYLE_HANDLE = 'pdwchs-rfm-matrix-css';

	/** The one admin page this panel and its stylesheet belong to. */
	const PAGE_SLUG = 'pd-woo-dashboard';

	/** @var PDWCHS_RFM_Matrix_Data */
	private $data;

	/**
	 * Two registrations, and no more.
	 *
	 * H6 renders the panel and H4 loads its stylesheet. No H5, because there is no script; no menu
	 * seam, because this is a panel on an existing page; no cron seam, because the data class caches
	 * its own reads; and no endpoint of any kind, because a static grid has nothing to fetch.
	 *
	 * Both seams are do_action — admin/partials/templates/dashboard.php:25 and
	 * admin/class-pd-customer-history-admin.php:84 — so both are add_action. H4 passes the free
	 * plugin version; this listener ignores it and uses the constant instead, for the reason
	 * pdwchs_enqueue_styles() records.
	 */
	public function __construct() {
		$this->data = new PDWCHS_RFM_Matrix_Data();

		add_action( 'pdwchs_dashboard_widgets', array( $this, 'pdwchs_render_panel' ) );      // H6.
		add_action( 'pdwchs_enqueue_admin_styles', array( $this, 'pdwchs_enqueue_styles' ) ); // H4.
	}

	/**
	 * H6 listener — render the heat-map panel.
	 *
	 * The capability gate is here rather than in the template so that a user without it costs zero
	 * queries: the payload is never assembled. manage_woocommerce is the capability the dashboard menu
	 * itself is registered under.
	 *
	 * It returns rather than calling wp_die(), because this is one widget inside someone else's page —
	 * cohort-table.php:2-4 uses wp_die() because it *is* the page. Blanking the whole dashboard for a
	 * user who is merely not entitled to one panel would be the wrong failure.
	 *
	 * Nothing is read from the request. The matrix is an all-time population count, not a range
	 * aggregate, so it is not driven by the revenue widget's date-range picker.
	 *
	 * @return void
	 */
	public function pdwchs_render_panel() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$matrix = $this->data->pdwchs_get_matrix();

		require __DIR__ . '/templates/rfm-matrix-widget.php';
	}

	/**
	 * H4 listener — enqueue the panel stylesheet on the dashboard only.
	 *
	 * The page slug is sanitised before it is compared, and the asset is versioned on
	 * PDWCHS_CUSTOMER_HISTORY_VERSION rather than a request timestamp, which would bust the browser
	 * cache on every admin request — the pattern several older Intelligence enqueues still use.
	 * custom-style is declared as a dependency because it owns .pd-woo-panel, the shell this panel's
	 * own rules extend.
	 *
	 * @return void
	 */
	public function pdwchs_enqueue_styles() {
		if ( ! $this->pdwchs_is_panel_page() ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			PDWCHS_PRO_PLUGIN_URL . 'admin/css/pd-customer-history-rfm-matrix.css',
			array( 'custom-style' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION
		);
	}

	/**
	 * Whether the current request is the dashboard this panel belongs to.
	 *
	 * @return bool
	 */
	private function pdwchs_is_panel_page() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- A read-only page gate; nothing is submitted or changed here.
		if ( ! isset( $_GET['page'] ) ) {
			return false;
		}

		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return self::PAGE_SLUG === $page;
	}
}
