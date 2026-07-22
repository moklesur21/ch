<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Removes ONLY what the seeder created (ledger-driven, with an order-meta
 * sweep for orphans), plus the one-time wipe-legacy purge of pre-existing
 * junk data. Every step tolerates already-deleted rows.
 */
class PDWCHS_Demo_Teardown {

	const ORDER_BATCH = 60;
	const USER_BATCH  = 50;

	/** All demo order IDs known to the ledger (customer + guest + temp). */
	public static function ledger_order_ids() {
		$ledger = PDWCHS_Demo_Ledger::get();
		if ( ! $ledger ) {
			return array();
		}
		$ids = array();
		foreach ( $ledger['order_map'] as $per_customer ) {
			foreach ( $per_customer as $id ) {
				$ids[] = (int) $id;
			}
		}
		foreach ( $ledger['guest_order_ids'] as $id ) {
			$ids[] = (int) $id;
		}
		foreach ( $ledger['temp_order_ids'] as $id ) {
			$ids[] = (int) $id;
		}
		return array_values( array_unique( array_filter( $ids ) ) );
	}

	public static function order_batches() {
		return (int) ceil( max( 1, count( self::ledger_order_ids() ) ) / self::ORDER_BATCH );
	}

	public static function user_batches() {
		$ledger = PDWCHS_Demo_Ledger::get();
		$count  = $ledger ? count( $ledger['user_map'] ) : 0;
		return (int) ceil( max( 1, $count ) / self::USER_BATCH );
	}

	/** Delete pro-table rows for all seeded users (fast, one pass). */
	public static function step_scores_tables() {
		global $wpdb;
		$ledger = PDWCHS_Demo_Ledger::get();
		if ( ! $ledger || empty( $ledger['user_map'] ) ) {
			return 'scores: nothing to remove';
		}
		$ids = implode( ',', array_map( 'absint', array_values( $ledger['user_map'] ) ) );

		foreach ( array(
			array( 'pdwchs_churn_alerts', 'customer_id' ),
			array( 'pdwchs_churn_history', 'customer_id' ),
			array( 'pdwchs_churn_scores', 'customer_id' ),
			array( 'pdwchs_behavioral_tags', 'user_id' ),
			array( 'pdwchs_behavioral_patterns', 'user_id' ),
			array( 'pdwchs_lifecycle_history', 'user_id' ),
			array( 'pdwchs_customer_intelligence', 'user_id' ),
		) as $t ) {
			$table = $wpdb->prefix . $t[0];
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
				$wpdb->query( "DELETE FROM {$table} WHERE {$t[1]} IN ({$ids})" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			}
		}
		return 'scores/intelligence rows removed for ' . count( $ledger['user_map'] ) . ' users';
	}

	public static function step_orders( $batch ) {
		$ids   = self::ledger_order_ids();
		$slice = array_slice( $ids, $batch * self::ORDER_BATCH, self::ORDER_BATCH );
		$n     = 0;
		foreach ( $slice as $id ) {
			$order = wc_get_order( $id );
			if ( $order ) {
				$order->delete( true );
				$n++;
			}
		}
		return "orders batch {$batch}: {$n} deleted";
	}

	/** Orphan sweep: any order tagged _pdwchs_demo that escaped the ledger. */
	public static function step_orders_sweep() {
		global $wpdb;
		$meta_table = $wpdb->prefix . 'wc_orders_meta';
		$n          = 0;
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $meta_table ) ) ) {
			$ids = $wpdb->get_col( "SELECT DISTINCT order_id FROM {$meta_table} WHERE meta_key IN ('_pdwchs_demo', '_pdwchs_demo_temp') LIMIT 500" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			foreach ( $ids as $id ) {
				$order = wc_get_order( (int) $id );
				if ( $order ) {
					$order->delete( true );
					$n++;
				}
			}
		}
		return "orphan order sweep: {$n} deleted";
	}

	public static function step_sessions_carts() {
		global $wpdb;
		$ledger = PDWCHS_Demo_Ledger::get();
		if ( ! $ledger ) {
			return 'sessions/carts: no ledger';
		}
		$sessions = $wpdb->prefix . 'pd_woo_customer_history_sessions';
		$carts    = $wpdb->prefix . 'pdwchs_carts';

		foreach ( array_chunk( array_map( 'absint', $ledger['session_ids'] ), 500 ) as $chunk ) {
			$wpdb->query( "DELETE FROM {$sessions} WHERE id IN (" . implode( ',', $chunk ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		foreach ( array_chunk( array_map( 'absint', $ledger['cart_ids'] ), 500 ) as $chunk ) {
			$wpdb->query( "DELETE FROM {$carts} WHERE id IN (" . implode( ',', $chunk ) . ')' ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
		return 'sessions (' . count( $ledger['session_ids'] ) . ') + carts (' . count( $ledger['cart_ids'] ) . ') rows removed';
	}

	public static function step_users( $batch ) {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		$ledger = PDWCHS_Demo_Ledger::get();
		if ( ! $ledger ) {
			return 'users: no ledger';
		}
		$ids   = array_values( array_map( 'absint', $ledger['user_map'] ) );
		$slice = array_slice( $ids, $batch * self::USER_BATCH, self::USER_BATCH );
		$n     = 0;
		foreach ( $slice as $id ) {
			if ( get_userdata( $id ) && ! user_can( $id, 'manage_options' ) ) {
				wp_delete_user( $id );
				$n++;
			}
		}
		return "users batch {$batch}: {$n} deleted";
	}

	/** Optionally remove seeder-created products, then reset state. */
	public static function step_finalize( $with_catalog = false ) {
		$ledger = PDWCHS_Demo_Ledger::get();
		if ( $with_catalog && $ledger && ! empty( $ledger['product_ids'] ) ) {
			foreach ( $ledger['product_ids'] as $pid ) {
				$product = wc_get_product( (int) $pid );
				if ( $product ) {
					$product->delete( true );
				}
			}
		}

		foreach ( array( 'pdwchs_rfm_batch_offset', 'pdwchs_health_batch_offset', 'pdwchs_lifecycle_batch_offset', 'pdwchs_churn_batch_offset' ) as $opt ) {
			delete_option( $opt );
		}
		foreach ( array( 'pdwchs_daily_rfm_calculation', 'pdwchs_daily_health_calculation', 'pdwchs_daily_lifecycle_calculation', 'pdwchs_analyze_customer_behavior' ) as $hook ) {
			wp_unschedule_hook( $hook );
		}

		PDWCHS_Demo_Ledger::delete();
		PDWCHS_Demo_Recalc::flush_plugin_caches();
		return 'teardown finalized (ledger cleared)';
	}

	// ── one-time legacy junk purge (guarded twice: latch + --yes-really) ──

	public static function wipe_legacy_order_pass() {
		$ids = wc_get_orders( array(
			'limit'  => 100,
			'return' => 'ids',
			'status' => array_keys( wc_get_order_statuses() ),
		) );
		foreach ( $ids as $id ) {
			$order = wc_get_order( $id );
			if ( $order ) {
				$order->delete( true );
			}
		}
		$deleted = count( $ids );
		if ( 100 === $deleted ) {
			return array( 'repeat' => true, 'msg' => "legacy orders: {$deleted} deleted this pass" );
		}
		return "legacy orders: {$deleted} deleted (done)";
	}

	public static function wipe_legacy_users() {
		require_once ABSPATH . 'wp-admin/includes/user.php';
		$users = get_users( array( 'role__in' => array( 'customer', 'subscriber' ), 'fields' => 'ID', 'number' => 500 ) );
		foreach ( $users as $id ) {
			if ( ! user_can( $id, 'manage_options' ) ) {
				wp_delete_user( (int) $id );
			}
		}
		return 'legacy users: ' . count( $users ) . ' deleted';
	}

	public static function wipe_legacy_tables() {
		global $wpdb;
		$tables = array(
			'pd_woo_customer_history_sessions', 'pdwchs_carts', 'pdwchs_customer_intelligence',
			'pdwchs_lifecycle_history', 'pdwchs_behavioral_tags', 'pdwchs_behavioral_patterns',
			'pdwchs_churn_scores', 'pdwchs_churn_history', 'pdwchs_churn_alerts',
		);
		$done = array();
		foreach ( $tables as $t ) {
			$table = $wpdb->prefix . $t;
			if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) ) {
				$wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$done[] = $t;
			}
		}
		PDWCHS_Demo_Ledger::delete();
		PDWCHS_Demo_Recalc::flush_plugin_caches();
		return 'legacy tables truncated: ' . implode( ', ', $done );
	}
}
