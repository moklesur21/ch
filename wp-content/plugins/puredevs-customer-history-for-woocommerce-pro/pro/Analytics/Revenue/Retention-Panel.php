<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Retention-Metrics.php';

/**
 * Retention metrics — the Pro dashboard panel.
 *
 * Presentation half of the pair whose data half is PDWCHS_Retention_Metrics: it renders the
 * default preset's payload as a panel on pd-woo-dashboard and enqueues that panel's stylesheet.
 * Every esc_* call and every display string lives here or in the template, and every number
 * comes from the data class — this file performs no arithmetic beyond formatting.
 *
 * The panel mounts on H6 (pdwchs_dashboard_widgets), the same seam the revenue and cart widgets
 * use, so PDWCHS_Revenue_Pro is untouched. Construction order in PDWCHS_Pro::pdwchs_boot() is
 * what places the panel directly below the revenue widget.
 *
 * The first paint is server-rendered and complete with JavaScript disabled; the preset selector
 * and the chart are layered on top of it, and the series table stays as the no-script fallback.
 * The one endpoint changes what is displayed and nothing else: it recalculates nothing, persists
 * no preference, and writes nothing.
 *
 * @since 3.1.0
 */
class PDWCHS_Retention_Panel {

	/** Stylesheet handle. Verified unused elsewhere in the plugin. */
	const STYLE_HANDLE = 'pdwchs-retention-css';

	/** Script handle. Verified unused elsewhere in the plugin. */
	const SCRIPT_HANDLE = 'pdwchs-retention-js';

	/** The one admin page this panel and its assets belong to. */
	const PAGE_SLUG = 'pd-woo-dashboard';

	/** Nonce action. Must match the check_ajax_referer() call and the script's posted key. */
	const NONCE_ACTION = 'pdwchs_retention_nonce';

	/** @var PDWCHS_Retention_Metrics */
	private $data;

	/**
	 * Four registrations, and no more.
	 *
	 * H6 renders the panel, H4 loads its stylesheet, H5 loads its script, and the one endpoint
	 * serves a preset switch. Nothing on a menu seam because this is a panel on an existing page,
	 * and nothing on a cron seam because the data class caches its own reads.
	 *
	 * No nopriv twin on the endpoint: it has no logged-out use, and the right answer is not to
	 * register it rather than to register it and reject.
	 */
	public function __construct() {
		$this->data = new PDWCHS_Retention_Metrics();

		add_action( 'pdwchs_dashboard_widgets', array( $this, 'pdwchs_render_panel' ) );        // H6.
		add_action( 'pdwchs_enqueue_admin_styles', array( $this, 'pdwchs_enqueue_styles' ) );   // H4.
		add_action( 'pdwchs_enqueue_admin_scripts', array( $this, 'pdwchs_enqueue_scripts' ) ); // H5.

		add_action( 'wp_ajax_pdwchs_get_retention_metrics', array( $this, 'pdwchs_ajax_get_retention' ) );
	}

	/**
	 * The eight window presets as translatable display labels, slug => label.
	 *
	 * PDWCHS_Retention_Metrics::PRESETS carries an untranslated machine-readable descriptor by
	 * design; these are its adjectival presentation forms, and they are the single source for the
	 * selector's options, the panel heading and the localised list the script rewrites labels
	 * from. The keys come from the constant so the allowlist and the labels cannot drift apart.
	 *
	 * @return array
	 */
	public static function pdwchs_preset_labels() {
		$labels = array(
			'15d' => __( '15-day', 'puredevs-customer-history-for-woocommerce' ),
			'30d' => __( '30-day', 'puredevs-customer-history-for-woocommerce' ),
			'60d' => __( '60-day', 'puredevs-customer-history-for-woocommerce' ),
			'90d' => __( '90-day', 'puredevs-customer-history-for-woocommerce' ),
			'4m'  => __( '4-month', 'puredevs-customer-history-for-woocommerce' ),
			'6m'  => __( '6-month', 'puredevs-customer-history-for-woocommerce' ),
			'12m' => __( '12-month', 'puredevs-customer-history-for-woocommerce' ),
			'24m' => __( '24-month', 'puredevs-customer-history-for-woocommerce' ),
		);

		$ordered = array();

		foreach ( array_keys( PDWCHS_Retention_Metrics::PRESETS ) as $slug ) {
			$ordered[ $slug ] = isset( $labels[ $slug ] )
				? $labels[ $slug ]
				: PDWCHS_Retention_Metrics::PRESETS[ $slug ]['label'];
		}

		return $ordered;
	}

	/**
	 * H6 listener — render the retention panel.
	 *
	 * The capability gate is here rather than in the template so that a user without it costs
	 * zero queries: the payload is never assembled. manage_woocommerce is the capability the
	 * dashboard menu itself is registered under.
	 *
	 * Only DEFAULT_PRESET is rendered. Nothing is read from the request, and the panel is not
	 * driven by the revenue widget's date-range picker — these are rolling windows anchored to
	 * now, not a range aggregate.
	 *
	 * @return void
	 */
	public function pdwchs_render_panel() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$retention = $this->data->pdwchs_get_retention( PDWCHS_Retention_Metrics::DEFAULT_PRESET );

		require __DIR__ . '/../templates/retention-metrics.php';
	}

	/**
	 * H4 listener — enqueue the panel stylesheet on the dashboard only.
	 *
	 * The page slug is sanitised before it is compared, and the asset is versioned on
	 * PDWCHS_CUSTOMER_HISTORY_VERSION rather than a request timestamp, which would bust the
	 * browser cache on every admin request. custom-style is declared as a dependency because it
	 * owns .pd-woo-panel — the shell this panel's own rules extend.
	 *
	 * @return void
	 */
	public function pdwchs_enqueue_styles() {
		if ( ! $this->pdwchs_is_panel_page() ) {
			return;
		}

		wp_enqueue_style(
			self::STYLE_HANDLE,
			PDWCHS_PRO_PLUGIN_URL . 'admin/css/pd-customer-history-retention.css',
			array( 'custom-style' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION
		);
	}

	/**
	 * H5 listener — enqueue the chart script on the dashboard only.
	 *
	 * Chart.js is re-enqueued defensively with the handle, source and version the free build
	 * already uses on this page: a second wp_enqueue_script() for a registered handle is a no-op,
	 * so this costs nothing and survives a change to the free enqueue. It ships in the free build,
	 * hence PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL here and PDWCHS_PRO_PLUGIN_URL for our own script.
	 * There is one Chart.js in the plugin and no CDN anywhere.
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
			PDWCHS_PRO_PLUGIN_URL . 'admin/js/pdwchs-retention.js',
			array( 'jquery', 'chartjs' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION,
			true
		);

		// The first paint is server-rendered, but the canvas is not: handing the script the
		// default preset's payload lets the chart draw without a round-trip on page load. The
		// payload is transient-backed, so the render call that follows this one is a cache hit
		// and the panel still costs the same two queries at most.
		$initial = current_user_can( 'manage_woocommerce' )
			? $this->data->pdwchs_get_retention( PDWCHS_Retention_Metrics::DEFAULT_PRESET )
			: null;

		wp_localize_script(
			self::SCRIPT_HANDLE,
			'pdwchs_retention_vars',
			array(
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( self::NONCE_ACTION ),
				'presets'  => self::pdwchs_preset_labels(),
				'initial'  => $initial,
				'currency' => array(
					'symbol'             => self::pdwchs_currency_symbol(),
					'position'           => get_option( 'woocommerce_currency_pos' ),
					'thousand_separator' => wc_get_price_thousand_separator(),
					'decimal_separator'  => wc_get_price_decimal_separator(),
				),
				'i18n'     => array(
					'loading'        => __( 'Loading…', 'puredevs-customer-history-for-woocommerce' ),
					'error'          => __( 'Could not load retention data. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
					'no_data'        => __( 'No completed or processing orders from registered customers yet, so there is nothing to measure retention against.', 'puredevs-customer-history-for-woocommerce' ),
					'undefined_dash' => '—',
					/* translators: %s: rolling window length, e.g. "30-day". */
					'window_heading' => __( 'Retention · %s window', 'puredevs-customer-history-for-woocommerce' ),
					/* translators: %s: rolling window length, e.g. "30-day". */
					'clv_label'      => __( 'Expected CLV · %s basis', 'puredevs-customer-history-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * AJAX — return the payload for one window preset.
	 *
	 * Nonce, then capability with a 403, then the preset against the eight-slug allowlist with a
	 * 400. That order is the contract: a capability check after a $_POST read is a finding even
	 * when the outcome is identical.
	 *
	 * The allowlist is strict. Without the third argument to in_array(), PHP's loose comparison
	 * would accept a posted 0 as a valid slug.
	 *
	 * The handler adds no data method and no second cache: R15-15A's transient means a repeat
	 * request for the same preset costs zero queries.
	 *
	 * @return void
	 */
	public function pdwchs_ajax_get_retention() {
		check_ajax_referer( self::NONCE_ACTION, 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ),
				403
			);
		}

		$preset = isset( $_POST['preset'] ) ? sanitize_text_field( wp_unslash( $_POST['preset'] ) ) : '';

		if ( ! in_array( $preset, array_keys( PDWCHS_Retention_Metrics::PRESETS ), true ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Invalid window preset.', 'puredevs-customer-history-for-woocommerce' ) ),
				400
			);
		}

		wp_send_json_success( $this->data->pdwchs_get_retention( $preset ) );
	}

	/**
	 * The store's currency symbol as a plain string, ready for a JS .text() call.
	 *
	 * WooCommerce stores symbols as HTML entities, several of them with a trailing space baked
	 * in — 'BDT' => '&#2547;&nbsp;' in wc-core-functions.php is the one that exposed this.
	 * wp_localize_script() decodes entities only for top-level scalars, and this value is nested
	 * inside the currency block, so an undecoded symbol reaches the browser and renders as the
	 * literal "&#2547;&nbsp;". Decoding here is what keeps the script free of any markup
	 * handling; the position case in formatCurrency() supplies the spacing.
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
	 * Whether the current request is the admin page this panel lives on.
	 *
	 * One gate for both asset hooks — two copies of three lines drift. The slug is sanitised
	 * before it is compared; the neighbouring Revenue-Pro.php compares it raw.
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
