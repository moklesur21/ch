<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cohort Retention admin page.
 *
 * Renders the precomputed matrix stored by PDWCHS_Cohort_Cron. Performs no
 * queries and no arithmetic beyond formatting: the page is a view over one option.
 *
 * @since 3.1.0
 */
class PDWCHS_Cohort_Page {

	/** Admin page slug. */
	const PAGE_SLUG = 'pd-woo-cohorts';

	/** Allowed values of the ?view= query argument. */
	const VIEWS = array( 'customers', 'revenue' );

	/**
	 * Register the menu (H3), the page-scoped stylesheet (H4) and the chart
	 * scripts (H5).
	 *
	 * @since 3.1.0
	 */
	public function __construct() {
		add_action( 'pdwchs_admin_menu_late',       array( $this, 'pdwchs_register_menu' ) );   // H3
		add_action( 'pdwchs_enqueue_admin_styles',  array( $this, 'pdwchs_enqueue_styles' ) );  // H4
		add_action( 'pdwchs_enqueue_admin_scripts', array( $this, 'pdwchs_enqueue_scripts' ) ); // H5
	}

	/**
	 * H3 — register the visible Cohort Retention submenu.
	 *
	 * @since 3.1.0
	 *
	 * @param string $capability Capability string passed by pdwchs_admin_menu_late.
	 * @return void
	 */
	public function pdwchs_register_menu( $capability ) {
		add_submenu_page(
			'pd-woo-dashboard',
			__( 'Cohort Retention', 'puredevs-customer-history-for-woocommerce' ),
			__( '<span id="pd-cohorts-menu">Cohorts</span>', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			self::PAGE_SLUG,
			array( $this, 'pdwchs_cohorts_page' )
		);
	}

	/**
	 * Page callback — include the cohort table template.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function pdwchs_cohorts_page() {
		require __DIR__ . '/templates/cohort-table.php';
	}

	/**
	 * H4 — enqueue the cohort stylesheet on the cohort page only.
	 *
	 * @since 3.1.0
	 *
	 * @param string $version Plugin version string passed by H4 action.
	 * @return void
	 */
	public function pdwchs_enqueue_styles( $version ) {
		if ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page-scope check, nothing is written.
			return;
		}
		wp_enqueue_style(
			'pdwchs-cohorts-css',
			PDWCHS_PRO_PLUGIN_URL . 'admin/css/pd-customer-history-cohorts.css',
			array(),
			PDWCHS_CUSTOMER_HISTORY_VERSION
		);
	}

	/**
	 * H5 — enqueue Chart.js and the cohort chart script on the cohort page only.
	 *
	 * Bails on exactly the two states in which the template renders no canvas
	 * (never computed, empty store) so nothing is downloaded for a chart that
	 * cannot be drawn. The averages are already computed and stored by the cron;
	 * this method only hands them to the browser.
	 *
	 * @since 3.1.0
	 *
	 * @param string $version Plugin version string passed by H5 action.
	 * @return void
	 */
	public function pdwchs_enqueue_scripts( $version ) {
		if ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page-scope check, nothing is written.
			return;
		}

		$matrix = PDWCHS_Cohort_Cron::pdwchs_get_matrix();
		if ( null === $matrix || empty( $matrix['cohorts'] ) ) {
			return;
		}

		wp_enqueue_script( 'chartjs', PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL . 'admin/js/chart.umd.js', array(), '4.4.1', true );
		wp_enqueue_script(
			'pdwchs-cohorts-js',
			PDWCHS_PRO_PLUGIN_URL . 'admin/js/pdwchs-cohorts.js',
			array( 'jquery', 'chartjs' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION,
			true
		);

		wp_localize_script(
			'pdwchs-cohorts-js',
			'pdwchs_cohort_vars',
			array(
				'averages' => array_values( $matrix['averages'] ),
				'i18n'     => array(
					'chart_title'   => __( 'Average retention by month since first order', 'puredevs-customer-history-for-woocommerce' ),
					'series_label'  => __( 'Average retention', 'puredevs-customer-history-for-woocommerce' ),
					/* translators: %d: number of months since acquisition. */
					'offset_label'  => __( 'M+%d', 'puredevs-customer-history-for-woocommerce' ),
					/* translators: %d: number of cohorts the average was taken over. */
					'cohorts_label' => __( 'from %d cohorts', 'puredevs-customer-history-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * The requested view, validated against the whitelist.
	 *
	 * @since 3.1.0
	 *
	 * @return string 'customers'|'revenue'
	 */
	public static function pdwchs_current_view() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only display toggle, nothing is written.
		$view = isset( $_GET['view'] ) ? sanitize_text_field( wp_unslash( $_GET['view'] ) ) : '';

		return in_array( $view, self::VIEWS, true ) ? $view : 'customers';
	}
}
