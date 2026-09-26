<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Geographic Performance admin page.
 *
 * The screen over PDWCHS_Geographic_Data. It runs no query of its own: the range and the sort are
 * read out of the query string, the payload comes from the data class (which caches it), and the
 * sort runs in PHP over the ≤ 250 rows that payload already holds.
 *
 * The whole page renders server-side and is complete with JavaScript off — the range is a plain GET
 * form and every sort control is a link. That is deliberate, not incidental: R15-21B layers a
 * comparison block above this table and changes nothing here.
 *
 * Navigation state carries no nonce: a nonce on a bookmarkable sort link would break the bookmark
 * and would protect nothing, because nothing on this page writes. R15-21B's comparison endpoint is
 * the one thing here that takes a POST, and it carries its own nonce and its own capability check.
 *
 * @since 3.1.1
 */
class PDWCHS_Geographic_Page {

	/** Admin page slug. */
	const PAGE_SLUG = 'pd-woo-geographic';

	/**
	 * Allowed values of ?orderby=.
	 *
	 * This is an allowlist, not a hint. The value indexes the row arrays, so a sanitised-but-
	 * unvalidated key would reach $row[ $orderby ] and emit an undefined-index notice for any
	 * string a visitor invents.
	 */
	const SORT_KEYS = array( 'country', 'net_sales', 'orders', 'items', 'unique_products' );

	/** Column sorted when ?orderby= is absent or rejected. */
	const DEFAULT_ORDERBY = 'net_sales';

	/** Direction used when ?order= is absent or rejected. */
	const DEFAULT_ORDER = 'desc';

	/** Window used when either supplied date is malformed. Mirrors PDWCHS_Geographic_Data. */
	const DEFAULT_DAYS = 30;

	/**
	 * Register the hidden menu entry (H3), the page-scoped assets (H4, H5) and the one endpoint.
	 *
	 * Exactly four listeners. The comparison endpoint deliberately has no logged-out twin: it
	 * reads store revenue, and registering the unauthenticated variant would hand that to any
	 * visitor.
	 *
	 * @since 3.1.1
	 */
	public function __construct() {
		add_action( 'pdwchs_admin_menu_late', array( $this, 'pdwchs_register_menu' ) );          // H3.
		add_action( 'pdwchs_enqueue_admin_styles', array( $this, 'pdwchs_enqueue_styles' ) );    // H4.
		add_action( 'pdwchs_enqueue_admin_scripts', array( $this, 'pdwchs_enqueue_scripts' ) );  // H5.
		add_action( 'wp_ajax_pdwchs_geographic_compare', array( $this, 'pdwchs_ajax_compare' ) );
	}

	/**
	 * H3 — register the Geographic Performance page under a hidden parent.
	 *
	 * The '' parent follows the three sibling analytics report pages
	 * (Product-Reports-Ajax.php:220-244), not Cohort-Page.php's visible entry: this page is reached
	 * from the analytics sub-nav, and a second visible menu item for it would duplicate that.
	 *
	 * @since 3.1.1
	 *
	 * @param string $capability Capability string passed by pdwchs_admin_menu_late. Unused: the
	 *                           three sibling report pages hardcode manage_woocommerce and this
	 *                           page matches them.
	 * @return void
	 */
	public function pdwchs_register_menu( $capability ) {
		add_submenu_page(
			'',
			__( 'Geographic Performance', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Geographic Performance', 'puredevs-customer-history-for-woocommerce' ),
			'manage_woocommerce',
			self::PAGE_SLUG,
			array( $this, 'pdwchs_geographic_page' )
		);
	}

	/**
	 * Page callback — include the report template.
	 *
	 * @since 3.1.1
	 *
	 * @return void
	 */
	public function pdwchs_geographic_page() {
		require __DIR__ . '/templates/geographic-report.php';
	}

	/**
	 * H4 — enqueue the geographic stylesheet on this page only.
	 *
	 * The early return is not optional: without it the stylesheet loads on every admin screen in
	 * WordPress. The gate compares the *sanitised* value, and the version is the plugin constant —
	 * a timestamped version busts the browser cache on every admin request.
	 *
	 * @since 3.1.1
	 *
	 * @param string $version Plugin version string passed by the H4 action. Unused: the constant is
	 *                        the single source of truth for asset versions.
	 * @return void
	 */
	public function pdwchs_enqueue_styles( $version ) {
		if ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page-scope check, nothing is written.
			return;
		}

		wp_enqueue_style(
			'pdwchs-geographic-css',
			PDWCHS_PRO_PLUGIN_URL . 'admin/css/pd-customer-history-geographic.css',
			array( 'custom-style' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION
		);
	}

	/**
	 * H5 — enqueue this page's scripts, in two tiers.
	 *
	 * The range picker loads on every state of the page: its form is in the toolbar, outside the
	 * has-data branch, so it is always on screen and must always work.
	 *
	 * The comparison assets sit behind a second gate that mirrors the template exactly: below two
	 * countries the template renders no comparison block, so there is no canvas to draw on and
	 * nothing to select between — Cohort-Page.php:102-105 bails on the same principle. Neither
	 * Chart.js nor the comparison script may be downloaded in that state.
	 *
	 * The payload read here is the same cached read the template makes a moment later, so this
	 * costs no extra query. Localizing the rows is what lets the first paint happen with no network
	 * request at all; the endpoint exists for subsequent switches.
	 *
	 * @since 3.1.1
	 *
	 * @param string $version Plugin version string passed by the H5 action. Unused: the constant is
	 *                        the single source of truth for asset versions.
	 * @return void
	 */
	public function pdwchs_enqueue_scripts( $version ) {
		if ( ! isset( $_GET['page'] ) || self::PAGE_SLUG !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only page-scope check, nothing is written.
			return;
		}

		require_once __DIR__ . '/Geographic-Data.php';

		$range   = self::pdwchs_current_range();
		$payload = ( new PDWCHS_Geographic_Data() )->pdwchs_get_geographic( $range['start'], $range['end'] );

		// Above the bail, deliberately. The range form sits in the toolbar, outside the has-data
		// branch, so it renders in every state — including the empty one, where a reader most needs
		// to widen the range. The 'date-picker' handle is registered by the free admin
		// (class-pd-customer-history-admin.php:138); naming it as a dependency guarantees ordering
		// rather than hoping for it.
		wp_enqueue_script(
			'pdwchs-geographic-range-js',
			PDWCHS_PRO_PLUGIN_URL . 'admin/js/pdwchs-geographic-range.js',
			array( 'jquery', 'date-picker' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION,
			true
		);

		wp_localize_script(
			'pdwchs-geographic-range-js',
			'pdwchs_geographic_range_vars',
			array(
				'startDate' => $payload['start_date'],
				'endDate'   => $payload['end_date'],
			)
		);

		// Everything below is the comparison layer, and it needs two countries to have a subject.
		if ( count( $payload['rows'] ) < 2 ) {
			return;
		}

		wp_enqueue_script( 'chartjs', PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL . 'admin/js/chart.umd.js', array(), '4.4.1', true );
		wp_enqueue_script(
			'pdwchs-geographic-js',
			PDWCHS_PRO_PLUGIN_URL . 'admin/js/pdwchs-geographic.js',
			array( 'jquery', 'chartjs' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION,
			true
		);

		$rows = array();
		foreach ( $payload['rows'] as $row ) {
			$rows[] = self::pdwchs_comparable_row( $row );
		}

		wp_localize_script(
			'pdwchs-geographic-js',
			'pdwchs_geographic_vars',
			array(
				'ajaxurl'   => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'pdwchs_geographic_nonce' ),
				// The payload's dates, not the request's: the data class swaps an inverted range,
				// so these are the dates the localized numbers actually cover.
				'startDate' => $payload['start_date'],
				'endDate'   => $payload['end_date'],
				'rows'      => $rows,
				'i18n'      => array(
					'chartTitle'     => __( 'Share of the higher value, per metric (%)', 'puredevs-customer-history-for-woocommerce' ),
					'netSales'       => __( 'Net sales', 'puredevs-customer-history-for-woocommerce' ),
					'orders'         => __( 'Orders', 'puredevs-customer-history-for-woocommerce' ),
					'items'          => __( 'Items', 'puredevs-customer-history-for-woocommerce' ),
					'uniqueProducts' => __( 'Unique products', 'puredevs-customer-history-for-woocommerce' ),
					'errorFallback'  => __( 'Could not load the comparison. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
				),
			)
		);
	}

	/**
	 * AJAX — the four metrics for two countries in one range.
	 *
	 * Runs no query of its own: PDWCHS_Geographic_Data is asked once for the whole range and both
	 * countries are read out of that single payload. Asking it per country would double the work on
	 * a cold cache for two numbers that live in the same array.
	 *
	 * It also computes nothing. Every value it returns is a value R15-20 already produced; the only
	 * thing added is presentation — the resolved country name and the formatted strings. A metric
	 * the payload does not carry is an R15-20 change, not a second query here.
	 *
	 * POST params: nonce, start_date (Y-m-d), end_date (Y-m-d), country_a, country_b.
	 *
	 * @since 3.1.1
	 *
	 * @return void
	 */
	public function pdwchs_ajax_compare() {
		check_ajax_referer( 'pdwchs_geographic_nonce', 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'Insufficient permissions.', 'puredevs-customer-history-for-woocommerce' ) ), 403 );
		}

		$start_date = isset( $_POST['start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['start_date'] ) ) : '';
		$end_date   = isset( $_POST['end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['end_date'] ) ) : '';

		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start_date )
			|| ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end_date ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid date format.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		// The is_string() test has to happen before sanitize_text_field(), which flattens an array
		// to ''. Without it, country_a[]=1 would arrive as '' and match the Unknown bucket on any
		// store that has one. null is the sentinel for "was not a string at all".
		//
		// The test lives inside the ternary condition rather than on a raw assignment above it so
		// that no unsanitised value is ever bound to a variable — which is also what keeps this
		// free of a ValidatedSanitizedInput suppression. wp_unslash() never changes the type, so
		// testing before it and testing after it are the same test.
		$country_a = ( isset( $_POST['country_a'] ) && is_string( $_POST['country_a'] ) )
			? sanitize_text_field( wp_unslash( $_POST['country_a'] ) )
			: null;
		$country_b = ( isset( $_POST['country_b'] ) && is_string( $_POST['country_b'] ) )
			? sanitize_text_field( wp_unslash( $_POST['country_b'] ) )
			: null;

		require_once __DIR__ . '/Geographic-Data.php';

		$payload = ( new PDWCHS_Geographic_Data() )->pdwchs_get_geographic( $start_date, $end_date );

		$rows = array();
		foreach ( $payload['rows'] as $row ) {
			$rows[ (string) $row['country_code'] ] = $row;
		}

		// Validated against this range's own rows, not against a 247-entry country list: you can
		// only compare countries that appear in the report, and every other code would render an
		// empty panel. array_key_exists(), never empty() — '' is a legitimate code here (the
		// no-billing-country bucket), and a later refactor to ! empty() would silently drop it.
		// The null tests come first so array_key_exists() never sees one.
		if ( null === $country_a || null === $country_b
			|| ! array_key_exists( $country_a, $rows )
			|| ! array_key_exists( $country_b, $rows ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown country.', 'puredevs-customer-history-for-woocommerce' ) ), 400 );
		}

		wp_send_json_success(
			array(
				'a' => self::pdwchs_comparable_row( $rows[ $country_a ] ),
				'b' => self::pdwchs_comparable_row( $rows[ $country_b ] ),
			)
		);
	}

	/**
	 * One payload row, decorated for the comparison panel.
	 *
	 * Written once and used by both paths — the H5 localize and the AJAX response — because the
	 * first paint and every repaint have to agree to the cent. Two copies of this shaping would
	 * drift, and the drift would only show after a dropdown change.
	 *
	 * The raw numbers stay raw: the chart does arithmetic on them. The display strings are
	 * separate, and net sales carries two of them because the panel and the chart tooltip need
	 * different things — the panel takes WooCommerce's currency markup (the single field the script
	 * inserts with .html()), the tooltip takes plain text (.text()).
	 *
	 * @since 3.1.1
	 *
	 * @param array $row One row from PDWCHS_Geographic_Data's payload.
	 * @return array
	 */
	private static function pdwchs_comparable_row( $row ) {
		$net_sales = (float) $row['net_sales'];

		return array(
			'country_code'    => (string) $row['country_code'],
			'country_name'    => self::pdwchs_country_name( $row['country_code'] ),
			'net_sales'       => $net_sales,
			'orders'          => (int) $row['orders'],
			'items'           => (int) $row['items'],
			'unique_products' => (int) $row['unique_products'],
			'display'         => array(
				'net_sales'       => wp_kses_post( wc_price( $net_sales ) ),
				'net_sales_text'  => html_entity_decode( wp_strip_all_tags( wc_price( $net_sales ) ), ENT_QUOTES, 'UTF-8' ),
				'orders'          => number_format_i18n( (int) $row['orders'] ),
				'items'           => number_format_i18n( (int) $row['items'] ),
				'unique_products' => number_format_i18n( (int) $row['unique_products'] ),
			),
		);
	}

	/**
	 * The requested date range, validated at the HTTP boundary.
	 *
	 * Sanitise, then the strict Y-m-d test (Product-Reports-Ajax.php:106-109). A malformed value
	 * takes the whole pair back to the default rather than half of it — a half-honoured range is a
	 * range nobody asked for, and PDWCHS_Geographic_Data falls back the same way.
	 *
	 * Both values are handed to PDWCHS_Geographic_Data, which validates again. Neither pass is
	 * redundant: this one keeps a bad value out of the markup, that one keeps it out of the cache
	 * key.
	 *
	 * @since 3.1.1
	 *
	 * @return array { @type string $start 'Y-m-d', @type string $end 'Y-m-d' }
	 */
	public static function pdwchs_current_range() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only navigation state, nothing is written.
		$start = isset( $_GET['start_date'] ) ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) ) : '';
		$end   = isset( $_GET['end_date'] ) ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $start ) && preg_match( '/^\d{4}-\d{2}-\d{2}$/', $end ) ) {
			return array(
				'start' => $start,
				'end'   => $end,
			);
		}

		return array(
			'start' => gmdate( 'Y-m-d', strtotime( '-' . ( self::DEFAULT_DAYS - 1 ) . ' days' ) ),
			'end'   => gmdate( 'Y-m-d' ),
		);
	}

	/**
	 * The requested sort, resolved against the allowlist.
	 *
	 * A rejected value is replaced silently — no notice, and the submitted value is never echoed
	 * back in any form, escaped or not.
	 *
	 * @since 3.1.1
	 *
	 * @return array { @type string $orderby One of SORT_KEYS, @type string $order 'asc'|'desc' }
	 */
	public static function pdwchs_current_sort() {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- read-only navigation state, nothing is written.
		$orderby = isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : '';
		$order   = isset( $_GET['order'] ) ? strtolower( sanitize_text_field( wp_unslash( $_GET['order'] ) ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		return array(
			'orderby' => in_array( $orderby, self::SORT_KEYS, true ) ? $orderby : self::DEFAULT_ORDERBY,
			'order'   => in_array( $order, array( 'asc', 'desc' ), true ) ? $order : self::DEFAULT_ORDER,
		);
	}

	/**
	 * Sort a copy of the payload rows.
	 *
	 * 'country' sorts by the *resolved display name*, so the alphabetical order matches what the
	 * reader sees rather than the ISO code behind it. The four numeric keys sort numerically.
	 *
	 * The '' bucket sorts last in both directions on the country column only: "Unknown" is not a
	 * country name and should not head the alphabet. On the numeric columns it sorts on its value
	 * like every other row — it is real revenue, and pinning it would be a lie about the numbers.
	 *
	 * The original position is the final tiebreaker, which makes the sort stable on PHP 7.4 as well
	 * as on 8.0+, where usort() is stable by itself.
	 *
	 * @since 3.1.1
	 *
	 * @param array  $rows    Payload rows from PDWCHS_Geographic_Data.
	 * @param string $orderby One of SORT_KEYS; anything else falls back to DEFAULT_ORDERBY.
	 * @param string $order   'asc'|'desc'; anything else falls back to DEFAULT_ORDER.
	 * @return array Sorted copy. The input is never modified.
	 */
	public static function pdwchs_sort_rows( $rows, $orderby, $order ) {
		if ( ! is_array( $rows ) || count( $rows ) < 2 ) {
			return is_array( $rows ) ? $rows : array();
		}

		if ( ! in_array( $orderby, self::SORT_KEYS, true ) ) {
			$orderby = self::DEFAULT_ORDERBY;
		}

		if ( ! in_array( $order, array( 'asc', 'desc' ), true ) ) {
			$order = self::DEFAULT_ORDER;
		}

		$direction = ( 'asc' === $order ) ? 1 : -1;

		// Decorate with the original index so ties keep their incoming order on every PHP version.
		$decorated = array();
		$position  = 0;
		foreach ( $rows as $row ) {
			$decorated[] = array(
				'row'   => $row,
				'index' => $position,
			);
			++$position;
		}

		usort(
			$decorated,
			static function ( $a, $b ) use ( $orderby, $direction ) {
				$comparison = self::pdwchs_compare_rows( $a['row'], $b['row'], $orderby, $direction );

				if ( 0 !== $comparison ) {
					return $comparison;
				}

				return ( $a['index'] < $b['index'] ) ? -1 : 1;
			}
		);

		$sorted = array();
		foreach ( $decorated as $entry ) {
			$sorted[] = $entry['row'];
		}

		return $sorted;
	}

	/**
	 * Compare two payload rows on one column.
	 *
	 * @since 3.1.1
	 *
	 * @param array  $a         Left row.
	 * @param array  $b         Right row.
	 * @param string $orderby   Resolved sort key.
	 * @param int    $direction 1 for ascending, -1 for descending.
	 * @return int Negative, zero or positive.
	 */
	private static function pdwchs_compare_rows( $a, $b, $orderby, $direction ) {
		if ( 'country' === $orderby ) {
			$a_code = isset( $a['country_code'] ) ? (string) $a['country_code'] : '';
			$b_code = isset( $b['country_code'] ) ? (string) $b['country_code'] : '';

			// Applied before $direction, which is what keeps '' last in both directions.
			if ( '' === $a_code || '' === $b_code ) {
				if ( $a_code === $b_code ) {
					return 0;
				}
				return ( '' === $a_code ) ? 1 : -1;
			}

			$comparison = strcasecmp( self::pdwchs_country_name( $a_code ), self::pdwchs_country_name( $b_code ) );

			if ( 0 === $comparison ) {
				return 0;
			}

			return ( $comparison < 0 ) ? -$direction : $direction;
		}

		$a_value = isset( $a[ $orderby ] ) ? (float) $a[ $orderby ] : 0.0;
		$b_value = isset( $b[ $orderby ] ) ? (float) $b[ $orderby ] : 0.0;

		if ( $a_value === $b_value ) {
			return 0;
		}

		return ( $a_value < $b_value ) ? -$direction : $direction;
	}

	/**
	 * Resolve an ISO-2 country code to a display name.
	 *
	 * WooCommerce's list is preferred because it is translated
	 * (admin/partials/templates/customer-details.php:282 is the precedent). PDWCHS_Helper is the
	 * free-side fallback — not the pro/Helpers duplicate, which has no live country consumer.
	 *
	 * A code neither list knows is returned as-is. Never an empty string: a blank cell reads as a
	 * rendering bug, a bare code reads as a country nobody has named yet.
	 *
	 * @since 3.1.1
	 *
	 * @param string $code ISO-2 code, or '' for the no-billing-country bucket.
	 * @return string Display name.
	 */
	public static function pdwchs_country_name( $code ) {
		$code = (string) $code;

		if ( '' === $code ) {
			return __( 'Unknown', 'puredevs-customer-history-for-woocommerce' );
		}

		if ( function_exists( 'WC' ) && isset( WC()->countries->countries[ $code ] ) ) {
			return WC()->countries->countries[ $code ];
		}

		if ( isset( PDWCHS_Helper::$countries[ $code ] ) ) {
			return PDWCHS_Helper::$countries[ $code ];
		}

		return $code;
	}

	/**
	 * The URL that sorts the table by one column.
	 *
	 * Carries the current range, which is the half of the round trip the form cannot do: the form
	 * carries the sort in hidden inputs, and this carries the range in the query string. Drop either
	 * and applying one control resets the other.
	 *
	 * Clicking the active column flips its direction. A first click lands on 'desc' for the numeric
	 * columns — the interesting end of a sales table — and 'asc' for the country name.
	 *
	 * @since 3.1.1
	 *
	 * @param string $key Column key; anything outside SORT_KEYS falls back to DEFAULT_ORDERBY.
	 * @return string Unescaped URL. Escape with esc_url() at output.
	 */
	public static function pdwchs_sort_url( $key ) {
		if ( ! in_array( $key, self::SORT_KEYS, true ) ) {
			$key = self::DEFAULT_ORDERBY;
		}

		$range = self::pdwchs_current_range();
		$sort  = self::pdwchs_current_sort();

		if ( $key === $sort['orderby'] ) {
			$order = ( 'asc' === $sort['order'] ) ? 'desc' : 'asc';
		} else {
			$order = ( 'country' === $key ) ? 'asc' : 'desc';
		}

		return add_query_arg(
			array(
				'page'       => self::PAGE_SLUG,
				'start_date' => $range['start'],
				'end_date'   => $range['end'],
				'orderby'    => $key,
				'order'      => $order,
			),
			admin_url( 'admin.php' )
		);
	}
}
