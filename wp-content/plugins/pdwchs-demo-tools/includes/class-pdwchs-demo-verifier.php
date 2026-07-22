<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Machine-checks the recording guide's Phase 1E acceptance checklist (plus
 * environment sanity) and prints the Phase 2 report-back sheet. Read-only.
 *
 * Staleness-aware: the hero's expected days-since-last-order is 51 only on
 * anchor day; when the ledger anchor is older the expectation shifts by the
 * staleness and a warning tells you to refresh.
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

	public function run() {
		global $wpdb;
		$this->checks = array();
		$ledger       = PDWCHS_Demo_Ledger::get();
		$orders_table = $wpdb->prefix . 'wc_orders';
		$intel_table  = $wpdb->prefix . 'pdwchs_customer_intelligence';
		$scores_table = $wpdb->prefix . 'pdwchs_churn_scores';
		$sess_table   = $wpdb->prefix . 'pd_woo_customer_history_sessions';

		$staleness = 0;
		if ( $ledger ) {
			$staleness = max( 0, (int) floor( ( current_time( 'timestamp' ) - strtotime( $ledger['anchor'] . ' 00:00:00 UTC' ) ) / DAY_IN_SECONDS ) );
		}

		// ── environment ──
		$this->check( 'env_pro', 'Pro build active + licensed (pdwchs_is_pro_active)', function_exists( 'pdwchs_is_pro_active' ) && pdwchs_is_pro_active(), function_exists( 'pdwchs_is_pro_active' ) ? var_export( pdwchs_is_pro_active(), true ) : 'helper missing' );
		$this->check( 'env_currency', 'Currency is USD', 'USD' === get_option( 'woocommerce_currency' ), get_option( 'woocommerce_currency' ) );
		$this->check( 'env_blogname', 'Store name set (not "WordPress Blog")', ! in_array( get_option( 'blogname' ), array( '', 'WordPress Blog' ), true ), get_option( 'blogname' ), 'warn' );
		$this->check( 'env_tz', 'Site timezone UTC+0 (keeps the "51 days" arithmetic exact)', 0.0 === (float) get_option( 'gmt_offset' ), 'gmt_offset=' . get_option( 'gmt_offset' ) . ' tz=' . get_option( 'timezone_string' ), 'warn' );
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
		$this->check( 'data_customers', 'Customers 45-70', $customer_count >= 45 && $customer_count <= 70, $customer_count );
		$this->check( 'data_orders', 'Orders 200-350 (completed+processing)', $order_count >= 200 && $order_count <= 350, $order_count );

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
		$this->check( 'data_gap', 'No order gap > 3 days in last 60', $max_gap <= 3, $max_gap . ' day max gap' );

		$sessions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sess_table} WHERE del=0" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'data_sessions', 'Sessions table alive (≥ 800 rows)', $sessions >= 800, $sessions );
		$searches = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sess_table} WHERE del=0 AND url LIKE 'ACTION::search::%' AND reg_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'data_searches', 'Keyword searches last 30d ≥ 10', $searches >= 10, $searches );

		$carts_table = $wpdb->prefix . 'pdwchs_carts';
		$abandoned   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$carts_table} WHERE status='abandoned' AND created_at >= DATE_SUB(NOW(), INTERVAL 60 DAY)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'data_carts', 'Abandoned carts (60d) ≥ 5', $abandoned >= 5, $abandoned );

		// ── metrics (Phase 1E) ──
		$win   = gmdate( 'Y-m-d H:i:s', strtotime( '-30 days' ) );
		$new_ids = $wpdb->get_col( $wpdb->prepare( "SELECT customer_id FROM {$orders_table} WHERE type='shop_order' AND status IN ('wc-completed','wc-processing') AND customer_id > 0 GROUP BY customer_id HAVING MIN(date_created_gmt) >= %s", $win ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$new_in  = $new_ids ? implode( ',', array_map( 'absint', $new_ids ) ) : '0';
		$rev     = $wpdb->get_row( $wpdb->prepare( "SELECT
				COALESCE(SUM(CASE WHEN customer_id > 0 AND customer_id NOT IN ({$new_in}) THEN total_amount END),0) AS returning_rev,
				COALESCE(SUM(total_amount),0) AS total_rev
			FROM {$orders_table}
			WHERE type='shop_order' AND status IN ('wc-completed','wc-processing') AND date_created_gmt >= %s", $win ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$share   = $rev && $rev->total_rev > 0 ? round( 100 * $rev->returning_rev / $rev->total_rev, 1 ) : 0;
		$this->check( 'metric_returning', 'Returning revenue ≥ 30% of last 30d', $share >= 30, $share . '% ($' . round( (float) $rev->returning_rev ) . ' of $' . round( (float) $rev->total_rev ) . ')' );

		$health_avg = (float) $wpdb->get_var( "SELECT AVG(health_score) FROM {$intel_table} WHERE health_score IS NOT NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$in_band    = $health_avg >= 60 && $health_avg <= 70;
		$near_band  = $health_avg >= 55 && $health_avg <= 75;
		$this->check( 'metric_health_avg', 'Health average 60-70', $in_band, round( $health_avg, 1 ), $near_band ? 'warn' : 'fail' );

		$scored   = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$intel_table} WHERE health_score IS NOT NULL" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$critical = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$intel_table} WHERE health_score BETWEEN 0 AND 20" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$crit_pct = $scored ? round( 100 * $critical / $scored, 1 ) : 0;
		$this->check( 'metric_critical', 'Critical health band ≤ 15%', $crit_pct <= 15, $crit_pct . "% ({$critical}/{$scored})" );

		$max_engagement = (int) $wpdb->get_var( "SELECT COALESCE(MAX(health_engagement),0) FROM {$intel_table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$recent_sess    = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$sess_table} WHERE del=0 AND reg_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'metric_engagement_bug', 'Health engagement sub-score alive (Health.php:268 table-name fix applied)', ! ( 0 === $max_engagement && $recent_sess > 50 ), "max engagement {$max_engagement} with {$recent_sess} recent session rows", 'warn' );

		$hero_id = $ledger ? PDWCHS_Demo_Ledger::hero_user_id() : null;

		$declining = $wpdb->get_results( "SELECT user_id, (health_previous_score - health_score) AS decline FROM {$intel_table} WHERE health_previous_score IS NOT NULL AND (health_previous_score - health_score) >= 10" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$dec_ids   = wp_list_pluck( $declining, 'user_id' );
		$this->check( 'metric_declining', 'Declining Health widget 2-4 customers incl. hero', count( $declining ) >= 2 && count( $declining ) <= 4 && $hero_id && in_array( $hero_id, $dec_ids ), count( $declining ) . ' declining; hero ' . ( $hero_id && in_array( $hero_id, $dec_ids ) ? 'in' : 'MISSING' ) );

		$champions = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$intel_table} WHERE rfm_segment='champions'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$loyal     = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$intel_table} WHERE rfm_segment='loyal'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'metric_rfm', 'RFM Champions > 0 and Loyal > 0', $champions > 0 && $loyal > 0, "champions {$champions}, loyal {$loyal}" );

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
		$this->check( 'metric_lifecycle', 'Lifecycle funnel: all 6 stages populated', empty( $missing ), implode( ' ', $summary ) );

		$at_risk_widget = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$intel_table} WHERE lifecycle_stage='At Risk' AND lifecycle_days_in_stage >= 30" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$this->check( 'metric_atrisk_widget', '"At Risk (30+ Days)" widget non-empty', $at_risk_widget > 0, $at_risk_widget . ' rows' );

		$risk_rows = $wpdb->get_results( "SELECT risk_level, COUNT(*) AS n FROM {$scores_table} WHERE risk_level IN ('medium','high','critical') GROUP BY risk_level", OBJECT_K ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$risk_total  = 0;
		$risk_levels = 0;
		$risk_desc   = array();
		foreach ( array( 'critical', 'high', 'medium' ) as $level ) {
			$n = isset( $risk_rows[ $level ] ) ? (int) $risk_rows[ $level ]->n : 0;
			$risk_total += $n;
			$risk_levels += $n > 0 ? 1 : 0;
			$risk_desc[] = "{$level}:{$n}";
		}
		$this->check( 'metric_atrisk_list', 'Churn At-Risk list 6-8 rows across ≥ 2 levels', $risk_total >= 6 && $risk_total <= 8 && $risk_levels >= 2, implode( ' ', $risk_desc ) );

		// ── the hero ──
		if ( $hero_id ) {
			$hero_score = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$scores_table} WHERE customer_id = %d", $hero_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$expected_days = 51 + $staleness;

			$this->check( 'hero_row', 'Hero has a churn score row', (bool) $hero_score, $hero_score ? 'score ' . $hero_score->churn_score : 'MISSING' );
			if ( $hero_score ) {
				$this->check( 'hero_band', 'Hero risk High, score 61-79 (drifting, not maxed)', 'high' === $hero_score->risk_level && $hero_score->churn_score >= 61 && $hero_score->churn_score <= 79, $hero_score->risk_level . ' ' . $hero_score->churn_score );
				$this->check( 'hero_days', "Hero days-since-last-order == {$expected_days}" . ( $staleness ? ' (51 + staleness)' : '' ), (int) $hero_score->days_since_last_order === $expected_days, $hero_score->days_since_last_order );
				$this->check( 'hero_overdue', 'Hero days overdue 15-25 ("~20 days overdue")', $hero_score->days_overdue >= 15 && $hero_score->days_overdue <= 25, $hero_score->days_overdue );
				$this->check( 'hero_indicator', 'Hero primary indicator frequency/recency', in_array( $hero_score->primary_churn_indicator, array( 'frequency', 'recency' ), true ), (string) $hero_score->primary_churn_indicator );

				$alert = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}pdwchs_churn_alerts WHERE customer_id = %d AND status IN ('new','viewed','in_progress') ORDER BY id DESC LIMIT 1", $hero_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$this->check( 'hero_alert', 'Hero alert exists: New / Unassigned / score matches list exactly', $alert && 'new' === $alert->status && null === $alert->assigned_to && (int) $alert->churn_score === (int) $hero_score->churn_score, $alert ? "status {$alert->status}, assigned " . var_export( $alert->assigned_to, true ) . ", score {$alert->churn_score}" : 'MISSING' );
			}

			$intel = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$intel_table} WHERE user_id = %d", $hero_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$this->check( 'hero_cards', 'Hero cards coherent: RFM champions, Loyal lifecycle, health 55-65 trending down', $intel && 'champions' === $intel->rfm_segment && 'Loyal Customer' === $intel->lifecycle_stage && $intel->health_score >= 55 && $intel->health_score <= 65 && 'down' === $intel->health_trend, $intel ? "{$intel->rfm_segment} / {$intel->lifecycle_stage} / health {$intel->health_score} ({$intel->health_trend})" : 'MISSING' );

			$spent_rows = $wpdb->get_results( "SELECT customer_id, SUM(total_amount) AS spent FROM {$orders_table} WHERE type='shop_order' AND status IN ('wc-completed','wc-processing') AND customer_id > 0 GROUP BY customer_id ORDER BY spent DESC LIMIT 6" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$rank = 0;
			foreach ( $spent_rows as $i => $r ) {
				if ( (int) $r->customer_id === $hero_id ) {
					$rank = $i + 1;
					break;
				}
			}
			$this->check( 'hero_rank', 'Hero in Top Customers top 5 (lifetime spend)', $rank >= 1 && $rank <= 5, $rank ? '#' . $rank : 'not in top 6' );
		} else {
			$this->check( 'hero_row', 'Hero present (ledger knows hero user id)', false, 'no ledger / not seeded' );
		}

		// ── funnel ──
		if ( class_exists( 'PDWCHS_Sessions_Data' ) && method_exists( 'PDWCHS_Sessions_Data', 'count_stage' ) ) {
			$start = gmdate( 'Y-m-d', strtotime( '-30 days' ) );
			$end   = gmdate( 'Y-m-d', current_time( 'timestamp' ) );
			$f     = array(
				'visitors'         => PDWCHS_Sessions_Data::count_stage( 'visitors', $start, $end ),
				'add_to_cart'      => PDWCHS_Sessions_Data::count_stage( 'add_to_cart', $start, $end ),
				'checkout_started' => PDWCHS_Sessions_Data::count_stage( 'checkout_started', $start, $end ),
				'purchased'        => PDWCHS_Sessions_Data::count_stage( 'purchased', $start, $end ),
			);
			$ok = $f['visitors'] > $f['add_to_cart'] && $f['add_to_cart'] >= $f['checkout_started'] && $f['checkout_started'] >= $f['purchased'] && $f['purchased'] > 0;
			$this->check( 'metric_funnel', 'Conversion funnel monotonic and non-empty (30d)', $ok, implode( ' → ', $f ) );
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

	/** Phase-2 report-back sheet: the exact values the narration gets patched to. */
	public function report() {
		global $wpdb;
		$ledger  = PDWCHS_Demo_Ledger::get();
		$hero_id = $ledger ? PDWCHS_Demo_Ledger::hero_user_id() : null;
		if ( ! $hero_id ) {
			return array( 'error' => 'No seeded hero found — seed first.' );
		}

		$intel_table  = $wpdb->prefix . 'pdwchs_customer_intelligence';
		$scores_table = $wpdb->prefix . 'pdwchs_churn_scores';
		$user   = get_userdata( $hero_id );
		$intel  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$intel_table} WHERE user_id = %d", $hero_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$score  = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$scores_table} WHERE customer_id = %d", $hero_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared

		$intel_instance  = apply_filters( 'pdwchs_intelligence_instance', null );
		$indicator_label = $score ? $score->primary_churn_indicator : '';
		$risk_label      = $score ? ucfirst( $score->risk_level ) . ' Risk' : '';
		$health_status   = '';
		if ( $intel_instance && $score ) {
			$churn_calc      = $intel_instance->pdwchs_get_churn_calculator();
			$indicator_label = $churn_calc->pdwchs_get_indicator_label( $score->primary_churn_indicator );
			$info            = $churn_calc->pdwchs_get_risk_level_info( $score->risk_level );
			$risk_label      = $info['label'];
			$health_calc     = $intel_instance->pdwchs_get_health_calculator();
			$status          = $health_calc->pdwchs_get_health_status( (int) $intel->health_score );
			$health_status   = $status['status'];
		}

		$spent  = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(total_amount) FROM {$wpdb->prefix}wc_orders WHERE type='shop_order' AND status IN ('wc-completed','wc-processing') AND customer_id = %d", $hero_id ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rank_rows = $wpdb->get_results( "SELECT customer_id FROM {$wpdb->prefix}wc_orders WHERE type='shop_order' AND status IN ('wc-completed','wc-processing') AND customer_id > 0 GROUP BY customer_id ORDER BY SUM(total_amount) DESC LIMIT 10" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rank = 0;
		foreach ( $rank_rows as $i => $r ) {
			if ( (int) $r->customer_id === $hero_id ) {
				$rank = $i + 1;
				break;
			}
		}

		$admins = get_users( array( 'role' => 'administrator', 'fields' => array( 'display_name' ) ) );

		return array(
			'hero_display_name'        => $user ? $user->display_name : '',
			'health_score'             => $intel ? (int) $intel->health_score : null,
			'health_band'              => $health_status,
			'health_trend'             => $intel ? $intel->health_trend : null,
			'health_previous_score'    => $intel ? $intel->health_previous_score : null,
			'churn_score_pct'          => $score ? (int) $score->churn_score : null,
			'risk_level_label'         => $risk_label,
			'days_since_last_order'    => $score ? (int) $score->days_since_last_order : null,
			'days_overdue'             => $score ? (int) $score->days_overdue : null,
			'expected_next_purchase_days' => $score ? (int) $score->expected_next_purchase_days : null,
			'primary_indicator_label'  => $indicator_label,
			'prediction_confidence'    => $score ? (int) $score->prediction_confidence : null,
			'total_spent'              => round( (float) $spent, 2 ),
			'order_count'              => $score ? (int) $score->total_orders : null,
			'top_customers_rank'       => $rank,
			'status_dropdown_options'  => array( 'New', 'Viewed', 'In Progress', 'Resolved', 'Dismissed' ),
			'assigned_to_options'      => array_merge( array( 'Unassigned' ), wp_list_pluck( $admins, 'display_name' ) ),
			'last_calculated'          => $score ? $score->last_calculated : null,
		);
	}
}
