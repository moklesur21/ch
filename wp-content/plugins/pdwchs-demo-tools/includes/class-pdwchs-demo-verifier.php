<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Machine-checks that the seeded store is ready to record the Topic Filter
 * v4.2 videos (project_asset/docs/video-production.md §3, step 3). Read-only.
 *
 * Sections: env_* (site sanity) · data_* (dataset shape) · dash_* (Pro
 * dashboard widgets alive — V16 scrolls past them, never narrates them, so
 * these only WARN) · v1_* / v4_* (per-video prerequisites and the script's
 * stop-and-report triggers; these FAIL). The per-video checks call the
 * plugin's own data methods, so a PASS means "this is what the screen shows".
 *
 * The July V2–V4 checks (churn hero, Champions, refund cases, five-month
 * shape) were retired on 2026-09-28 with those videos; the seeder still
 * builds that data unchanged. See git history for the old checks.
 */
class PDWCHS_Demo_Verifier {

	/** @var PDWCHS_Demo_Plan */
	private $plan;

	private $checks = array();

	public function __construct( PDWCHS_Demo_Plan $plan = null ) {
		if ( ! $plan ) {
			$ledger = PDWCHS_Demo_Ledger::get();
			$plan   = new PDWCHS_Demo_Plan(
				$ledger ? $ledger['anchor'] : null,
				$ledger ? $ledger['rng_seed'] : null
			);
		}
		$this->plan = $plan;
	}

	private function check( $id, $label, $ok, $actual, $fail_level = 'fail' ) {
		$this->checks[] = array(
			'id'     => $id,
			'label'  => $label,
			'status' => $ok ? 'pass' : $fail_level,
			'actual' => is_scalar( $actual ) ? (string) $actual : wp_json_encode( $actual ),
		);
	}

	/**
	 * The date window every V1 screen shows by default: the Dashboard's
	 * "Last 30 Days" preset (pro/admin/js/pdwchs-revenue-dashboard.js) and the
	 * Revenue page's default range (admin/partials/templates/revenue.php) are
	 * both "today and the 29 days before it", as Y-m-d strings compared to
	 * date_created_gmt. Site timezone is UTC (env_tz), so site-today == GMT-today.
	 *
	 * @return array [ start Y-m-d, end Y-m-d ]
	 */
	public function window() {
		$end   = gmdate( 'Y-m-d', current_time( 'timestamp' ) );
		$start = gmdate( 'Y-m-d', strtotime( $end . ' -29 days' ) );
		return array( $start, $end );
	}

	public function run() {
		global $wpdb;
		$this->checks = array();
		$ledger       = PDWCHS_Demo_Ledger::get();
		$orders_table = $wpdb->prefix . 'wc_orders';
		$intel_table  = $wpdb->prefix . 'pdwchs_customer_intelligence';
		$sess_table   = $wpdb->prefix . 'pd_woo_customer_history_sessions';

		$staleness = 0;
		if ( $ledger ) {
			$staleness = max( 0, (int) floor( ( current_time( 'timestamp' ) - strtotime( $ledger['anchor'] . ' 00:00:00 UTC' ) ) / DAY_IN_SECONDS ) );
		}

		// ── environment ──
		$this->check( 'env_pro', 'Pro build active + licensed (pdwchs_is_pro_active)', function_exists( 'pdwchs_is_pro_active' ) && pdwchs_is_pro_active(), function_exists( 'pdwchs_is_pro_active' ) ? var_export( pdwchs_is_pro_active(), true ) : 'helper missing' );
		$this->check( 'env_currency', 'Currency is USD', 'USD' === get_option( 'woocommerce_currency' ), get_option( 'woocommerce_currency' ) );
		$this->check( 'env_blogname', 'Store name set (not "WordPress Blog")', ! in_array( get_option( 'blogname' ), array( '', 'WordPress Blog' ), true ), get_option( 'blogname' ), 'warn' );
		$this->check( 'env_tz', 'Site timezone UTC+0 (the Y-m-d windows the screens send line up with date_created_gmt)', 0.0 === (float) get_option( 'gmt_offset' ), 'gmt_offset=' . get_option( 'gmt_offset' ) . ' tz=' . get_option( 'timezone_string' ), 'warn' );
		$this->check( 'env_ledger', 'Seeded dataset present (ledger)', (bool) $ledger, $ledger ? 'anchor ' . $ledger['anchor'] : 'none' );
		$this->check( 'env_stale', 'Dataset anchored today (refresh before any demo/recording)', $staleness <= 0, $staleness . ' day(s) stale', 'warn' );

		$mysql_date = $wpdb->get_var( 'SELECT DATE(NOW())' );
		$php_date   = gmdate( 'Y-m-d', current_time( 'timestamp' ) );
		$this->check( 'env_dates', 'MySQL date == site date (DATEDIFF day-boundary skew)', $mysql_date === $php_date, "mysql {$mysql_date} vs site {$php_date}", 'warn' );

		if ( function_exists( 'as_get_scheduled_actions' ) && class_exists( 'ActionScheduler_Store' ) ) {
			$due = as_get_scheduled_actions( array( 'status' => ActionScheduler_Store::STATUS_PENDING, 'date' => as_get_datetime_object( 'now' ), 'date_compare' => '<=', 'per_page' => 3, 'orderby' => 'none' ), 'ids' );
			$this->check( 'env_as', 'Action Scheduler queue drained', empty( $due ), count( $due ) . '+ due actions', 'warn' );
		}

		$order_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$orders_table} WHERE type='shop_order' AND status IN ('wc-completed','wc-processing')" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$stats_table = $wpdb->prefix . 'wc_order_stats';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $stats_table ) ) ) {
			$stats_count = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$stats_table} WHERE status IN ('wc-completed','wc-processing')" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$this->check( 'env_stats', 'wc_order_stats parity with orders (Woo Analytics synced)', $stats_count >= $order_count, "{$stats_count} stats vs {$order_count} orders", 'warn' );
		}

		// ── dataset shape ──
		$customer_count = count( get_users( array( 'role' => 'customer', 'fields' => 'ID' ) ) );
		$this->check( 'data_customers', 'Customers 70-110', $customer_count >= 70 && $customer_count <= 110, $customer_count );
		$this->check( 'data_orders', 'Orders 220-400 (completed+processing)', $order_count >= 220 && $order_count <= 400, $order_count );

		$oldest = $wpdb->get_var( "SELECT MIN(date_created_gmt) FROM {$orders_table} WHERE type='shop_order' AND status IN ('wc-completed','wc-processing')" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$newest = $wpdb->get_var( "SELECT MAX(date_created_gmt) FROM {$orders_table} WHERE type='shop_order' AND status IN ('wc-completed','wc-processing')" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'data_span', 'Order history spans ≥ 12 months', $oldest && strtotime( $oldest ) <= strtotime( '-355 days' ), 'oldest ' . $oldest );
		$this->check( 'data_fresh', 'Newest order ≤ 2 days old', $newest && strtotime( $newest ) >= strtotime( '-2 days' ), 'newest ' . $newest );

		$gap_rows = $wpdb->get_col( "SELECT DISTINCT DATE(date_created_gmt) FROM {$orders_table} WHERE type='shop_order' AND status IN ('wc-completed','wc-processing') AND date_created_gmt >= DATE_SUB(NOW(), INTERVAL 60 DAY) ORDER BY 1" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$max_gap  = 0;
		$prev     = strtotime( '-60 days' );
		foreach ( $gap_rows as $d ) {
			$max_gap = max( $max_gap, (int) round( ( strtotime( $d ) - $prev ) / DAY_IN_SECONDS ) );
			$prev    = strtotime( $d );
		}
		$max_gap = max( $max_gap, (int) round( ( time() - $prev ) / DAY_IN_SECONDS ) );
		$this->check( 'data_gap', 'No order gap > 3 days in last 60 (daily revenue chart has bars on most days)', $max_gap <= 3, $max_gap . ' day max gap' );

		$sessions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sess_table} WHERE del=0" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'data_sessions', 'Sessions table alive (≥ 800 rows)', $sessions >= 800, $sessions );
		$searches = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sess_table} WHERE del=0 AND url LIKE 'ACTION::search::%' AND reg_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'data_searches', 'Keyword searches last 30d ≥ 10', $searches >= 10, $searches );

		$carts_table = $wpdb->prefix . 'pdwchs_carts';
		$abandoned   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$carts_table} WHERE status='abandoned' AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'data_carts', 'Abandoned carts (60d) ≥ 5', $abandoned >= 5, $abandoned );

		// ── Pro dashboard widgets alive (⚑ widgets: on screen in passing only, so WARN) ──
		$health_avg = (float) $wpdb->get_var( "SELECT AVG(health_score) FROM {$intel_table} WHERE health_score IS NOT NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'dash_health_avg', 'Health average 55-70', $health_avg >= 55 && $health_avg <= 70, round( $health_avg, 1 ), 'warn' );

		$scored   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$intel_table} WHERE health_score IS NOT NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$critical = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$intel_table} WHERE health_score BETWEEN 0 AND 20" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$crit_pct = $scored ? round( 100 * $critical / $scored, 1 ) : 0;
		$this->check( 'dash_critical', 'Critical health band ≤ 15%', $crit_pct <= 15, $crit_pct . "% ({$critical}/{$scored})", 'warn' );

		$max_engagement = (int) $wpdb->get_var( "SELECT COALESCE(MAX(health_engagement),0) FROM {$intel_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$recent_sess    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sess_table} WHERE del=0 AND reg_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'dash_engagement', 'Health engagement sub-score alive (Health.php:268 table-name fix applied)', ! ( 0 === $max_engagement && $recent_sess > 50 ), "max engagement {$max_engagement} with {$recent_sess} recent session rows", 'warn' );

		$champions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$intel_table} WHERE rfm_segment='champions'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$loyal     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$intel_table} WHERE rfm_segment='loyal'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'dash_rfm', 'RFM Champions > 0 and Loyal > 0', $champions > 0 && $loyal > 0, "champions {$champions}, loyal {$loyal}", 'warn' );

		$stages   = $wpdb->get_results( "SELECT lifecycle_stage, COUNT(*) AS n FROM {$intel_table} WHERE lifecycle_stage IS NOT NULL GROUP BY lifecycle_stage", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$expected = array( 'Prospect', 'New Customer', 'Active Customer', 'Loyal Customer', 'At Risk', 'Churned' );
		$missing  = array();
		$summary  = array();
		foreach ( $expected as $stage ) {
			$n         = isset( $stages[ $stage ] ) ? (int) $stages[ $stage ]->n : 0;
			$summary[] = $stage . ':' . $n;
			if ( 0 === $n ) {
				$missing[] = $stage;
			}
		}
		$this->check( 'dash_lifecycle', 'Lifecycle funnel: all 6 stages populated', empty( $missing ), implode( ' ', $summary ), 'warn' );

		$at_risk_widget = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$intel_table} WHERE lifecycle_stage='At Risk' AND lifecycle_days_in_stage >= 30" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'dash_atrisk', '"At Risk (30+ Days)" widget non-empty', $at_risk_widget > 0, $at_risk_widget . ' rows', 'warn' );

		// ── V1: Revenue by Customer Type, Last 30 Days (DASH-10) + Repeat Purchase Rate (REV-02) ──
		list( $start, $end ) = $this->window();
		$s = $this->split();
		if ( null === $s ) {
			$this->check( 'v1_cards', 'V1 · three cards non-zero (Revenue by Customer Type, Last 30 Days)', false, 'PDWCHS_Revenue_Pro unavailable — Pro build inactive?' );
		} else {
			$fmt = function ( $b ) use ( $s ) {
				return '$' . round( $s[ $b ]['revenue'] ) . '/' . $s[ $b ]['orders'];
			};
			$cards   = "new {$fmt('new')} · returning {$fmt('returning')} · guest {$fmt('guest')} ({$start}..{$end})";
			$nonzero = true;
			foreach ( array( 'new', 'returning', 'guest' ) as $b ) {
				if ( $s[ $b ]['revenue'] <= 0 || $s[ $b ]['orders'] < 2 ) {
					$nonzero = false;
				}
			}
			$this->check( 'v1_cards', 'V1 · three cards non-zero, ≥ 2 orders each (Revenue by Customer Type, Last 30 Days)', $nonzero, $cards );

			$rev = array( 'new' => $s['new']['revenue'], 'returning' => $s['returning']['revenue'], 'guest' => $s['guest']['revenue'] );
			arsort( $rev );
			$ranked  = array_keys( $rev );
			$biggest = $ranked[0];
			$second  = $rev[ $ranked[1] ];
			$ratio   = $second > 0 ? round( $rev[ $biggest ] / $second, 2 ) : 99;

			$this->check( 'v1_guest', 'V1 · Guest Orders is not the largest card (script: stop and report)', 'guest' !== $biggest, 'biggest: ' . $biggest );
			$this->check( 'v1_biggest', 'V1 · Returning Customers is the largest card (script option A; New = option B)', 'returning' === $biggest, 'biggest: ' . $biggest );
			$this->check( 'v1_margin', 'V1 · largest card ≥ 1.25× the next (readable at a glance, no near tie)', $ratio >= 1.25, $ratio . '× the next card' );

			$days = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(DISTINCT DATE(date_created_gmt)) FROM {$orders_table} WHERE type='shop_order' AND status IN ('wc-completed','wc-processing') AND date_created_gmt >= %s AND date_created_gmt < %s", $start . ' 00:00:00', gmdate( 'Y-m-d', strtotime( $end . ' +1 day' ) ) . ' 00:00:00' ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->check( 'v1_days', 'V1 · orders on ≥ 20 of the 30 days (Revenue Over Time, daily)', $days >= 20, $days . ' order days' );
		}

		$integrity = $this->integrity();
		if ( null === $integrity ) {
			$this->check( 'v1_integrity', 'V1 · badge reads "Data Verified ✓" (Data Integrity, same window)', false, 'PDWCHS_Data_Integrity unavailable', 'warn' );
		} else {
			$this->check( 'v1_integrity', 'V1 · badge reads "Data Verified ✓" (Data Integrity, same window)', ! empty( $integrity['is_verified'] ), ( ! empty( $integrity['is_verified'] ) ? 'verified' : 'MISMATCH ' . $integrity['severity'] ) . ' (net diff ' . $integrity['diff_pct']['net_revenue'] . '%)' );
		}

		$repeat = $this->repeat_rate();
		if ( null === $repeat ) {
			$this->check( 'v1_repeat', 'V1 · Repeat Purchase Rate > 0 on the Revenue page default range (scene 08, free build)', false, 'PDWCHS_Revenue_Data_Free unavailable', 'warn' );
		} else {
			$this->check( 'v1_repeat', 'V1 · Repeat Purchase Rate > 0 on the Revenue page default range (scene 08, free build)', $repeat['rate'] > 0, $repeat['rate'] . "% ({$repeat['repeat_customers']} of {$repeat['total_customers']} registered customers)" );
		}

		// ── V4: Conversion Funnel (STAT-07) — shape check to follow when V4 is scripted ──
		if ( class_exists( 'PDWCHS_Sessions_Data' ) && method_exists( 'PDWCHS_Sessions_Data', 'count_stage' ) ) {
			$f  = array(
				'visitors'         => PDWCHS_Sessions_Data::count_stage( 'visitors', $start, $end ),
				'add_to_cart'      => PDWCHS_Sessions_Data::count_stage( 'add_to_cart', $start, $end ),
				'checkout_started' => PDWCHS_Sessions_Data::count_stage( 'checkout_started', $start, $end ),
				'purchased'        => PDWCHS_Sessions_Data::count_stage( 'purchased', $start, $end ),
			);
			$ok = $f['visitors'] > $f['add_to_cart'] && $f['add_to_cart'] >= $f['checkout_started'] && $f['checkout_started'] >= $f['purchased'] && $f['purchased'] > 0;
			$this->check( 'v4_funnel', 'V4 · conversion funnel monotonic and non-empty (30d)', $ok, implode( ' → ', $f ) );
		}

		$counts = array( 'pass' => 0, 'fail' => 0, 'warn' => 0 );
		foreach ( $this->checks as $c ) {
			$counts[ $c['status'] ]++;
		}

		return array(
			'time'      => current_time( 'mysql' ),
			'anchor'    => $ledger ? $ledger['anchor'] : null,
			'staleness' => $staleness,
			'checks'    => $this->checks,
			'counts'    => $counts,
		);
	}

	/**
	 * The three "Revenue by Customer Type" cards for window(), from the
	 * plugin's own query (pro/Analytics/Revenue/Revenue-Pro.php): completed +
	 * processing, net of refunds; guest = customer_id 0; new = a registered
	 * customer's first-ever qualifying order falls inside the window. Warms
	 * the same 30-minute transient the dashboard reads, so after a refresh the
	 * screen shows exactly these numbers.
	 *
	 * @return array|null null when the Pro class is not loaded.
	 */
	public function split() {
		if ( ! class_exists( 'PDWCHS_Revenue_Pro' ) ) {
			return null;
		}
		list( $start, $end ) = $this->window();
		return ( new PDWCHS_Revenue_Pro() )->pdwchs_get_new_vs_returning_revenue( $start, $end );
	}

	/** The Data Integrity badge's own comparison for window(). */
	public function integrity() {
		if ( ! class_exists( 'PDWCHS_Data_Integrity' ) ) {
			return null;
		}
		list( $start, $end ) = $this->window();
		return ( new PDWCHS_Data_Integrity() )->pdwchs_compare_revenue( $start, $end );
	}

	/** The Revenue page's Repeat Purchase Rate card (REV-02) for its default range. */
	public function repeat_rate() {
		if ( ! class_exists( 'PDWCHS_Revenue_Data_Free' ) && defined( 'PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR' ) ) {
			$file = PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/analytics/class-pdwchs-revenue-data-free.php';
			if ( file_exists( $file ) ) {
				require_once $file;
			}
		}
		if ( ! class_exists( 'PDWCHS_Revenue_Data_Free' ) || ! class_exists( 'PDWCHS_Data_Window' ) ) {
			return null;
		}
		list( $start, $end ) = $this->window();
		return ( new PDWCHS_Revenue_Data_Free() )->pdwchs_get_repeat_purchase_rate( $start, $end );
	}

	/**
	 * Five-calendar-month revenue split, replicating the PLUGIN's dashboard
	 * semantics (pro/Analytics/Revenue/Revenue-Pro.php): completed+processing
	 * net revenue; guest = customer_id 0; "new" = the customer's first-ever
	 * order falls inside that month; refunds attributed to the PARENT order's
	 * month. The seeder's balance_v4_revenue() targets this shape; the sheet
	 * prints it so the "Revenue Over Time → Monthly" view can be sanity-read.
	 */
	public function monthly_split() {
		global $wpdb;
		$orders_table = $wpdb->prefix . 'wc_orders';
		$anchor       = $this->plan->anchor();
		$first_this   = gmdate( 'Y-m-01', strtotime( $anchor . ' 00:00:00 UTC' ) );
		$start        = gmdate( 'Y-m-d', strtotime( $first_this . ' 00:00:00 UTC -4 months' ) ) . ' 00:00:00';
		$end          = gmdate( 'Y-m-d', strtotime( $first_this . ' 00:00:00 UTC +1 month' ) ) . ' 00:00:00';

		$firsts = $wpdb->get_results( "SELECT customer_id, MIN(date_created_gmt) AS first_dt FROM {$orders_table} WHERE type='shop_order' AND status IN ('wc-completed','wc-processing') AND customer_id > 0 GROUP BY customer_id", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$orders = $wpdb->get_results( $wpdb->prepare( "SELECT customer_id, total_amount, date_created_gmt FROM {$orders_table} WHERE type='shop_order' AND status IN ('wc-completed','wc-processing') AND date_created_gmt >= %s AND date_created_gmt < %s", $start, $end ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$refunds = $wpdb->get_results( $wpdb->prepare( "SELECT r.total_amount AS amt, p.customer_id, p.date_created_gmt FROM {$orders_table} r JOIN {$orders_table} p ON p.id = r.parent_order_id WHERE r.type='shop_order_refund' AND p.type='shop_order' AND p.status IN ('wc-completed','wc-processing') AND p.date_created_gmt >= %s AND p.date_created_gmt < %s", $start, $end ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$months = array();
		for ( $k = 4; $k >= 0; $k-- ) {
			$m            = gmdate( 'Y-m', strtotime( $first_this . " 00:00:00 UTC -{$k} months" ) );
			$months[ $m ] = array( 'month' => $m, 'new' => 0.0, 'returning' => 0.0, 'guest' => 0.0, 'total' => 0.0, 'orders' => 0 );
		}

		$bucket_of = function ( $customer_id, $m ) use ( $firsts ) {
			if ( 0 === (int) $customer_id ) {
				return 'guest';
			}
			$first_m = isset( $firsts[ $customer_id ] ) ? substr( $firsts[ $customer_id ]->first_dt, 0, 7 ) : $m;
			return ( $first_m === $m ) ? 'new' : 'returning';
		};

		foreach ( $orders as $o ) {
			$m = substr( $o->date_created_gmt, 0, 7 );
			if ( ! isset( $months[ $m ] ) ) {
				continue;
			}
			$b = $bucket_of( $o->customer_id, $m );
			$months[ $m ][ $b ]     += (float) $o->total_amount;
			$months[ $m ]['total']  += (float) $o->total_amount;
			$months[ $m ]['orders']++;
		}
		foreach ( $refunds as $r ) {
			$m = substr( $r->date_created_gmt, 0, 7 );
			if ( ! isset( $months[ $m ] ) ) {
				continue;
			}
			$b   = $bucket_of( $r->customer_id, $m );
			$amt = abs( (float) $r->amt );
			$months[ $m ][ $b ]    -= $amt;
			$months[ $m ]['total'] -= $amt;
		}
		return array_values( $months );
	}

	/**
	 * Answer-frame sheet: what the V1 screens show today. Feeds the delivery
	 * note and the script's A/B and C/D choices — never the narration (owner
	 * rule: no spoken figures). Flat strings: the CLI/REST render values with
	 * implode().
	 */
	public function report() {
		list( $start, $end ) = $this->window();
		$out = array( 'window' => "{$start} .. {$end} (Last 30 Days)" );

		$s = $this->split();
		if ( null === $s ) {
			$out['revenue_by_customer_type'] = 'unavailable — PDWCHS_Revenue_Pro not loaded (Pro build inactive?)';
		} else {
			$money = function ( $v ) {
				return '$' . number_format( (float) $v, 2 );
			};
			$out['new_customers']       = $money( $s['new']['revenue'] ) . ' · ' . $s['new']['orders'] . ' orders';
			$out['returning_customers'] = $money( $s['returning']['revenue'] ) . ' · ' . $s['returning']['orders'] . ' orders';
			$out['guest_orders']        = $money( $s['guest']['revenue'] ) . ' · ' . $s['guest']['orders'] . ' orders';

			$rev = array( 'New Customers' => $s['new']['revenue'], 'Returning Customers' => $s['returning']['revenue'], 'Guest Orders' => $s['guest']['revenue'] );
			arsort( $rev );
			$ranked = array_keys( $rev );
			$labels = array( 'Returning Customers' => 'option A', 'New Customers' => 'option B', 'Guest Orders' => 'STOP AND REPORT — reseed' );
			$out['biggest_card'] = $ranked[0] . ' (' . $labels[ $ranked[0] ] . ')';
			if ( 'Guest Orders' === $ranked[2] ) {
				$out['guest_card'] = 'the smallest of the three (option C)';
			} else {
				$out['guest_card'] = 'bigger than ' . ( 'Guest Orders' === $ranked[0] ? 'both' : $ranked[2] ) . ' (option D)';
			}
			$second        = $rev[ $ranked[1] ];
			$out['margin'] = ( $second > 0 ? round( $rev[ $ranked[0] ] / $second, 2 ) : '∞' ) . '× the second card';
		}

		$integrity = $this->integrity();
		$out['data_integrity_badge'] = null === $integrity ? 'unavailable' : ( ! empty( $integrity['is_verified'] ) ? 'Data Verified ✓' : 'Data Mismatch ⚠️ (' . $integrity['severity'] . ')' );

		$repeat = $this->repeat_rate();
		$out['repeat_purchase_rate'] = null === $repeat ? 'unavailable' : $repeat['rate'] . '% (' . $repeat['repeat_customers'] . ' of ' . $repeat['total_customers'] . ' registered customers)';

		$month_table = array();
		foreach ( $this->monthly_split() as $m ) {
			$month_table[] = sprintf(
				'%s: new $%d · returning $%d · guest $%d · total $%d (%d orders)',
				$m['month'], round( $m['new'] ), round( $m['returning'] ), round( $m['guest'] ), round( $m['total'] ), $m['orders']
			);
		}
		$out['revenue_by_month'] = $month_table;

		return $out;
	}
}
