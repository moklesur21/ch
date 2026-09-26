<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/LTV-Distribution.php';

/**
 * Lifetime-value distribution — the Pro dashboard panel.
 *
 * Presentation half of the pair whose data half is PDWCHS_LTV_Distribution: it renders the spend
 * histogram's numbers as a panel on pd-woo-dashboard and enqueues that panel's stylesheet. Every
 * esc_* call and every display string lives here or in the template, and every number comes from the
 * data class — this file performs no arithmetic beyond formatting.
 *
 * The panel mounts on H6 (pdwchs_dashboard_widgets), the same seam the revenue and retention widgets
 * use, so PDWCHS_Revenue_Pro is untouched. Construction order in PDWCHS_Pro::pdwchs_boot() is what
 * places this panel directly below the retention panel.
 *
 * Three registrations, and no endpoint. The feature is all-time, so there is nothing to switch: no
 * preset selector, no date control, no nonce and no AJAX handler anywhere in it. The first paint is
 * server-rendered and complete with JavaScript disabled; the histogram R15-22C layers above the
 * bracket table draws from a localized payload, so it makes no request either.
 *
 * @since 3.1.1
 */
class PDWCHS_LTV_Panel {

	/** Stylesheet handle. Verified unused elsewhere in the plugin. */
	const STYLE_HANDLE = 'pdwchs-ltv-css';

	/** Script handle for the histogram. Verified unused elsewhere in the plugin. */
	const SCRIPT_HANDLE = 'pdwchs-ltv-js';

	/** The one admin page this panel and its assets belong to. */
	const PAGE_SLUG = 'pd-woo-dashboard';

	/** @var PDWCHS_LTV_Distribution */
	private $data;

	/**
	 * Three registrations, and no more.
	 *
	 * H6 renders the panel, H4 loads its stylesheet and H5 loads the histogram script — and no
	 * endpoint of any kind, because an all-time figure has nothing to switch between. Nothing on a
	 * menu seam because this is a panel on an existing page, and nothing on a cron seam because the
	 * data class caches its own reads.
	 */
	public function __construct() {
		$this->data = new PDWCHS_LTV_Distribution();

		add_action( 'pdwchs_dashboard_widgets', array( $this, 'pdwchs_render_panel' ) );        // H6.
		add_action( 'pdwchs_enqueue_admin_styles', array( $this, 'pdwchs_enqueue_styles' ) );   // H4.
		add_action( 'pdwchs_enqueue_admin_scripts', array( $this, 'pdwchs_enqueue_scripts' ) ); // H5.
	}

	/**
	 * H6 listener — render the distribution panel.
	 *
	 * The capability gate is here rather than in the template so that a user without it costs zero
	 * queries: the payload is never assembled. manage_woocommerce is the capability the dashboard
	 * menu itself is registered under.
	 *
	 * Nothing is read from the request, and the panel is not driven by the revenue widget's
	 * date-range picker — lifetime value is an all-time figure, not a range aggregate.
	 *
	 * @return void
	 */
	public function pdwchs_render_panel() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$ltv = $this->data->pdwchs_get_ltv_distribution();

		require __DIR__ . '/../templates/ltv-distribution.php';
	}

	/**
	 * H4 listener — enqueue the panel stylesheet on the dashboard only.
	 *
	 * The page slug is sanitised before it is compared, and the asset is versioned on
	 * PDWCHS_CUSTOMER_HISTORY_VERSION rather than a request timestamp, which would bust the browser
	 * cache on every admin request. custom-style is declared as a dependency because it owns
	 * .pd-woo-panel — the shell this panel's own rules extend.
	 *
	 * @return void
	 */
	public function pdwchs_enqueue_styles() {
		if ( ! $this->pdwchs_is_panel_page() ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			PDWCHS_PRO_PLUGIN_URL . 'admin/css/pd-customer-history-ltv.css',
			array( 'custom-style' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION
		);
	}

	/**
	 * H5 listener — enqueue the histogram script on the dashboard only, and hand it its data.
	 *
	 * Chart.js is re-enqueued defensively with the handle, source and version the free build already
	 * uses on this page: a second wp_enqueue_script() for a registered handle is a no-op, so this
	 * costs nothing and survives a change to the free enqueue. It ships in the free build, hence
	 * PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL here and PDWCHS_PRO_PLUGIN_URL for our own script. There is
	 * one Chart.js in the plugin and no CDN anywhere.
	 *
	 * The payload is localized rather than fetched, which is what removes the request: the histogram
	 * draws on first paint with zero network traffic, and there is nothing to switch it to. Because
	 * the data class is transient-backed, the H6 render that follows this call is a cache hit rather
	 * than a second aggregate.
	 *
	 * The capability is checked a second time here. H5 fires for every request that passes the slug
	 * gate, including one from a user who will never see the panel; without this check the payload
	 * would be assembled and then embedded in the page source for someone not allowed to read it.
	 * The script's own guard handles the resulting null.
	 *
	 * @return void
	 */
	public function pdwchs_enqueue_scripts() {
		if ( ! $this->pdwchs_is_panel_page() ) {
			return;
		}

		wp_enqueue_script(
			'chartjs',
			PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL . 'admin/js/chart.umd.js',
			array(),
			'4.4.1',
			true
		);

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			PDWCHS_PRO_PLUGIN_URL . 'admin/js/pdwchs-ltv-distribution.js',
			array( 'jquery', 'chartjs' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION,
			true
		);

		$initial = current_user_can( 'manage_woocommerce' )
			? $this->data->pdwchs_get_ltv_distribution()
			: null;

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'pdwchs_ltv_vars',
			array(
				'initial'  => $initial,
				'currency' => array(
					'symbol'             => self::pdwchs_currency_symbol(),
					'position'           => get_option( 'woocommerce_currency_pos' ),
					'thousand_separator' => wc_get_price_thousand_separator(),
					'decimal_separator'  => wc_get_price_decimal_separator(),
				),
				'i18n'     => array(
					'chart_title'     => __( 'Customers by lifetime spend', 'puredevs-customer-history-for-woocommerce' ),
					'customers_label' => __( 'Customers', 'puredevs-customer-history-for-woocommerce' ),
					'bracket_label'   => __( 'Spend bracket', 'puredevs-customer-history-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * The store's currency symbol as a plain string, ready for a JS tick label.
	 *
	 * WooCommerce stores symbols as HTML entities, several of them with a trailing space baked
	 * in — 'BDT' => '&#2547;&nbsp;' in wc-core-functions.php is the one that exposed this.
	 * wp_localize_script() decodes entities only for top-level scalars, and this value is nested
	 * inside the currency block, so an undecoded symbol reaches the browser and renders as the
	 * literal "&#2547;&nbsp;". Decoding here is what keeps the script free of any markup handling;
	 * the position case in the script's own formatter supplies the spacing.
	 *
	 * @return string
	 */
	private static function pdwchs_currency_symbol() {
		$symbol = html_entity_decode( get_woocommerce_currency_symbol(), ENT_QUOTES, 'UTF-8' );

		// The /u modifier so the trim matches the non-breaking space as a codepoint: a
		// byte-wise trim would corrupt any symbol whose own bytes start or end with 0xC2.
		return (string) preg_replace( '/^[\s\x{00A0}]+|[\s\x{00A0}]+$/u', '', $symbol );
	}

	/**
	 * One bracket rendered as a display label.
	 *
	 * Built from the payload's own numbers, never from a parallel list of strings: the ladder is
	 * auto-scaled to the store's currency and spend range, so a second list would drift the moment
	 * the scale moved — and the drift would be invisible, showing correct counts under wrong labels.
	 *
	 * Returns plain text, not markup. wc_price() emits a currency span, which is right for a headline
	 * card value but wrong inside a table cell that is already a label; stripping it also keeps the
	 * template's only wp_kses_post() calls wrapped directly around wc_price(), where they can be
	 * checked at a glance. The entity decode is what stops a symbol stored as '&#2547;' reaching the
	 * page as its literal source.
	 *
	 * @since 3.1.1
	 *
	 * @param float      $min Lower bound, inclusive.
	 * @param float|null $max Upper bound, exclusive; null on the open-ended top bracket.
	 * @return string
	 */
	public static function pdwchs_bracket_label( $min, $max ) {
		$low = html_entity_decode( wp_strip_all_tags( wc_price( (float) $min ) ), ENT_QUOTES, 'UTF-8' );

		if ( null === $max ) {
			return sprintf(
				/* translators: %s: lower bound of the top spend bracket, e.g. "$2,500". */
				__( '%s and above', 'puredevs-customer-history-for-woocommerce' ),
				$low
			);
		}

		$high = html_entity_decode( wp_strip_all_tags( wc_price( (float) $max ) ), ENT_QUOTES, 'UTF-8' );

		return sprintf(
			/* translators: 1: lower bound of a spend bracket, 2: upper bound. */
			__( '%1$s to %2$s', 'puredevs-customer-history-for-woocommerce' ),
			$low,
			$high
		);
	}

	/**
	 * Whether the current request is the admin page this panel lives on.
	 *
	 * One gate for both asset hooks — two copies of three lines drift. The slug is sanitised before
	 * it is compared; the neighbouring Revenue-Pro.php compares it raw.
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
