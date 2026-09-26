<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free data layer for the sessions table.
 *
 * Owns schema management (create/update), core DML (insert, read), and
 * stub signatures for the read methods required by Batches 1 and 4.
 * Stubs return array() so no fatal occurs if called before migration.
 *
 * @since 2.2.0
 */
class PDWCHS_Sessions_Data {

	// --- Schema management ---

		public static function pdwchs_create_table() {

		/*
		 *  Check if dbDelta() exists
		 */

		if ( ! function_exists( 'dbDelta' ) ) { require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); }

		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();

		$create = "CREATE TABLE IF NOT EXISTS {$wpdb->prefix}pd_woo_customer_history_sessions (
			id              BIGINT(20) NOT NULL AUTO_INCREMENT,
			user_id         BIGINT(20),
			ip              VARCHAR(250),
			url             VARCHAR(250),
			referer         VARCHAR(250),
			reg_date        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
			del             TINYINT(1) NOT NULL DEFAULT '0',
			session_id      VARCHAR(50),
			results_count   INT NULL DEFAULT NULL,
			PRIMARY KEY     (id)
		) $charset_collate";

		$result = $wpdb->query( $create ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $create is a DDL CREATE TABLE statement with no user input
		if($result){
			add_site_option('pdwchs_db_version','1.1');
		}
	}

		public static function pdwchs_update_table() {

		// R15-28: at most one run per request. The activator reaches this method from
		// four separate guards (the repair branch plus the three since-options), and on
		// a fresh install every one of them fires on the same page load.
		static $ran = false;
		if ( $ran ) {
			return;
		}
		$ran = true;

		/*
		 *  Check if dbDelta() exists
		 */
		if ( ! function_exists( 'dbDelta' ) ) { require_once( ABSPATH . 'wp-admin/includes/upgrade.php' ); }
		global $wpdb;

		$sessions_table = $wpdb->prefix . 'pd_woo_customer_history_sessions';

		// R15-28: every probe in this method filters table_schema = DATABASE(). Without
		// it INFORMATION_SCHEMA spans every schema the MySQL user can read, so a
		// same-named table in another database on the same server answers for this one —
		// the ALTER is skipped, the since-option below latches the failure, and every
		// subsequent search insert dies silently on the missing column.
		$row = $wpdb->get_results( $wpdb->prepare(
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			 WHERE table_schema = DATABASE()
			   AND table_name   = %s
			   AND column_name  = 'session_id'",
			$sessions_table
		) );
		if(empty($row)){
		   $result = $wpdb->query("ALTER TABLE {$wpdb->prefix}pd_woo_customer_history_sessions ADD session_id VARCHAR(50)");
		   if($result){
			   add_site_option('pdwchs_db_version','1.1');
		   }
		}

		$row = $wpdb->get_results( $wpdb->prepare(
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			 WHERE table_schema = DATABASE()
			   AND table_name   = %s
			   AND column_name  = 'results_count'",
			$sessions_table
		) );
		if ( empty( $row ) ) {
			$result = $wpdb->query( "ALTER TABLE {$wpdb->prefix}pd_woo_customer_history_sessions ADD results_count INT NULL DEFAULT NULL" );
			if ( $result !== false ) {
				$row = true; // Column now exists.
			}
		}

		self::pdwchs_sync_since_option( 'pdwchs_results_count_since', ! empty( $row ) );

		// Feature 15.6 — (user_id, reg_date) index for per-customer, newest-first paging.
		$index = $wpdb->get_results( $wpdb->prepare(
			"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
			 WHERE table_schema = DATABASE()
			   AND table_name   = %s
			   AND index_name   = 'idx_user_date'",
			$wpdb->prefix . 'pd_woo_customer_history_sessions'
		) );

		if ( empty( $index ) ) {
			// false !== $result, not if ( $result ): a successful ALTER TABLE returns 0.
			$result = $wpdb->query( "ALTER TABLE {$wpdb->prefix}pd_woo_customer_history_sessions ADD INDEX idx_user_date (user_id, reg_date)" );
			if ( false !== $result ) {
				$index = true;
			}
		}

		self::pdwchs_sync_since_option( 'pdwchs_sessions_index_since', ! empty( $index ) );

		// Feature 15.7 — (session_id, user_id) index for the guest-to-customer merge.
		// Same probe style as idx_user_date above: table_schema-filtered, and guarded by a
		// set-once option. Column order is deliberate — session_id leads because the merge
		// predicate is session_id = %s AND user_id = 0, and the backfill loose-scans
		// distinct session_id values off the same index.
		$session_index = $wpdb->get_results( $wpdb->prepare(
			"SELECT INDEX_NAME FROM INFORMATION_SCHEMA.STATISTICS
			 WHERE table_schema = DATABASE()
			   AND table_name   = %s
			   AND index_name   = 'idx_session_user'",
			$wpdb->prefix . 'pd_woo_customer_history_sessions'
		) );

		if ( empty( $session_index ) ) {
			// false !== $result, not if ( $result ): a successful ALTER TABLE returns 0.
			$result = $wpdb->query( "ALTER TABLE {$wpdb->prefix}pd_woo_customer_history_sessions ADD INDEX idx_session_user (session_id, user_id)" );
			if ( false !== $result ) {
				$session_index = true;
			}
		}

		self::pdwchs_sync_since_option( 'pdwchs_sessions_session_index_since', ! empty( $session_index ) );
	}

	/**
	 * Keep a schema since-option honest about the CURRENT database.
	 *
	 * These options are set-once guards: the activator skips the healer entirely once
	 * one is present. Before R15-28 the column probes above were not schema-scoped, so
	 * a site could stamp an option for a column that exists only in a sibling database
	 * — latching the failure permanently and leaving the admin UI claiming to collect
	 * data it never records.
	 *
	 * So the option is treated as a fact rather than a latch: stamped when the artefact
	 * is genuinely present here, deleted when it is not. A stamp written by the old
	 * unscoped probe therefore clears itself, and the activator's `! get_option()`
	 * guard retries on the next load.
	 *
	 * @since 3.1.2
	 *
	 * @param string $option  Option name.
	 * @param bool   $present Whether the artefact exists in the current database.
	 * @return void
	 */
	private static function pdwchs_sync_since_option( $option, $present ) {
		if ( $present ) {
			if ( ! get_option( $option ) ) {
				add_option( $option, time() );
			}
			return;
		}

		if ( get_option( $option ) ) {
			delete_option( $option );
		}
	}

	// --- Core DML ---

		public static function pdwchs_insert( $user_id, $url, $results_count = null ) {
		if ( $user_id >= 0 && $url != '' ) {
			global $wpdb;
			$wpdb->hide_errors();

			$table = "{$wpdb->prefix}pd_woo_customer_history_sessions";

			$ipaddress = PD_Customer_History::pdwchs_get_client_ip();

			$referer = isset( $_SERVER['HTTP_REFERER'] ) ? $_SERVER['HTTP_REFERER'] : '';

			$timezone = get_option('gmt_offset');

			$reg_date = gmdate( 'Y-m-d  H:i:s', time() + 3600 * $timezone );

			$data = array(
				'id' => '',
				'user_id' => $user_id,
				'ip' => $ipaddress,
				'url' => $url,
				'referer' => $referer,
				'reg_date' => $reg_date,
				'del' => 0,
				'session_id' => session_id(),
			);

			$format = array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
			);

			// Omit the key entirely when no count was supplied — a null against a
			// %d format would be cast to 0 and brand the row "zero results".
			if ( null !== $results_count ) {
				$data['results_count'] = (int) $results_count;
				$format[]              = '%d';
			}

			$wpdb->insert( $table, $data, $format );

		}

	}

		public static function pdwchs_get_all_live_sessions( $search = '', $start = '', $end = '' ) {

		global $wpdb;

            $search = sanitize_text_field($search);
            $start = sanitize_text_field($start);
            $end = sanitize_text_field($end);

		$del = ( ! empty( $_GET['page'] ) && $_GET['page'] == 'pd-woo-trash' ) ? 1 : 0;

		$where = "1=1 AND del=$del";

		$user_id = isset( $_GET['user_id'] ) ? $_GET['user_id'] : 0;

            $user_id = sanitize_text_field($user_id);

		if ( ! empty( $user_id ) ) {
			$where .= $wpdb->prepare( " AND user_id=%s", $user_id );
		}

		if ( ! empty( $_GET['page'] ) && $_GET['page'] == 'pd-woo-searches' ) {
			$where .= " AND url LIKE '%ACTION::search%'";
		}

		if (! empty( $search )) {
			$where .= $wpdb->prepare( " AND url LIKE %s", '%' . $wpdb->esc_like( $search ) . '%' );
		}

		if ( ! empty( $start ) && ! empty( $end ) ) {
			$where .= $wpdb->prepare( " AND reg_date BETWEEN %s AND %s", $start, $end );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared,WordPress.DB.PreparedSQL.NotPrepared -- $where is built entirely from $wpdb->prepare() fragments above
		return $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE $where ORDER BY reg_date DESC" );
	}

	// --- Search/URL stubs — delegate to PDWCHS_Search_Stats_Data (Batch 4A). ---

	public static function get_url_counts( $args = array() ) {
		return PDWCHS_Search_Stats_Data::get_url_counts( $args );
	}

	public static function get_url_counts_by_user( $user_id, $args = array() ) {
		return PDWCHS_Search_Stats_Data::get_url_counts_by_user( $user_id, $args );
	}

	public static function get_keyword_counts( $args = array() ) {
		return PDWCHS_Search_Stats_Data::get_keyword_counts( $args );
	}

	public static function get_last_searches( $limit = 10, $args = array() ) {
		return PDWCHS_Search_Stats_Data::get_last_searches( $limit, $args );
	}

	public static function get_distinct_sessions_by_url( $url_like, $args = array() ) {
		return PDWCHS_Search_Stats_Data::get_distinct_sessions_by_url( $url_like, $args );
	}

	public static function get_distinct_pages( $args = array() ) {
		return PDWCHS_Search_Stats_Data::get_distinct_pages( $args );
	}

	public static function get_url_counts_for_taxonomy( $taxonomy, $args = array() ) {
		return PDWCHS_Search_Stats_Data::get_url_counts_for_taxonomy( $taxonomy, $args );
	}

	// --- Funnel data methods (Batch 4A) ---

	/**
	 * Count distinct sessions for a single funnel stage over a date range.
	 *
	 * Extracted from PDWCHS_Funnel_Data::pdwchs_count_stage() (B4-4A).
	 *
	 * @param string $stage 'visitors', 'add_to_cart', 'checkout_started', or 'purchased'.
	 * @param string $start Y-m-d start date (validated by caller).
	 * @param string $end   Y-m-d end date (validated by caller).
	 * @return int
	 */
	public static function count_stage( $stage, $start, $end ) {
		global $wpdb;

		$stage_clauses = array(
			'visitors'         => '',
			'add_to_cart'      => "url LIKE 'ACTION::add_to_cart::%'",
			'checkout_started' => "(url = 'checkout' OR url LIKE 'checkout/%' OR url LIKE 'checkout?%') AND url NOT LIKE 'checkout/order-received/%'",
			'purchased'        => "(url LIKE 'checkout/order-received/%' OR url LIKE 'ACTION::new_order::%')",
		);

		$stage_clause = isset( $stage_clauses[ $stage ] ) ? $stage_clauses[ $stage ] : '';
		$start_param  = sanitize_text_field( $start ) . ' 00:00:00';
		$end_param    = gmdate( 'Y-m-d', strtotime( sanitize_text_field( $end ) . ' +1 day' ) ) . ' 00:00:00';

		$where = "del = '0' AND session_id <> '' AND user_id <> 999999999999999 AND reg_date >= %s AND reg_date < %s";
		if ( '' !== $stage_clause ) {
			$where .= ' AND ' . $stage_clause;
		}

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$count = $wpdb->get_var( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE {$where}",
				array( $start_param, $end_param )
			)
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return null !== $count ? (int) $count : 0;
	}

	/**
	 * Return per-period funnel counts via a single conditional-aggregation query.
	 *
	 * Extracted from PDWCHS_Funnel_Data::pdwchs_get_funnel_over_time() (B4-4A).
	 * Caching and period-list generation remain in PDWCHS_Funnel_Data.
	 *
	 * @param string $start       Y-m-d start date.
	 * @param string $end         Y-m-d end date.
	 * @param string $granularity 'day', 'week', or 'month'.
	 * @return array[] Raw rows (ARRAY_A) ordered by period ascending.
	 */
	public static function get_funnel_buckets( $start, $end, $granularity = 'day' ) {
		global $wpdb;

		$start_param      = sanitize_text_field( $start ) . ' 00:00:00';
		$end_param        = gmdate( 'Y-m-d', strtotime( sanitize_text_field( $end ) . ' +1 day' ) ) . ' 00:00:00';
		$granularity_expr = self::pdwchs_funnel_granularity_expr( $granularity );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
		$rows = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT
					{$granularity_expr} AS period,
					COUNT(DISTINCT CASE WHEN session_id <> '' THEN session_id END) AS visitors,
					COUNT(DISTINCT CASE WHEN session_id <> '' AND url LIKE 'ACTION::add_to_cart::%' THEN session_id END) AS add_to_cart,
					COUNT(DISTINCT CASE WHEN session_id <> '' AND (url = 'checkout' OR url LIKE 'checkout/%' OR url LIKE 'checkout?%') AND url NOT LIKE 'checkout/order-received/%' THEN session_id END) AS checkout_started,
					COUNT(DISTINCT CASE WHEN session_id <> '' AND (url LIKE 'checkout/order-received/%' OR url LIKE 'ACTION::new_order::%') THEN session_id END) AS purchased
				FROM {$wpdb->prefix}pd_woo_customer_history_sessions
				WHERE del = '0'
				  AND session_id <> ''
				  AND user_id <> 999999999999999
				  AND reg_date >= %s
				  AND reg_date < %s
				GROUP BY {$granularity_expr}
				ORDER BY {$granularity_expr} ASC",
				array( $start_param, $end_param )
			),
			ARRAY_A
		);
		// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

		return $rows;
	}

	/**
	 * Return the DATE_FORMAT SQL expression for the requested granularity.
	 *
	 * @param string $granularity 'day', 'week', or 'month'.
	 * @return string SQL expression.
	 */
	private static function pdwchs_funnel_granularity_expr( $granularity ) {
		switch ( $granularity ) {
			case 'week':
				return "DATE_FORMAT(reg_date, '%%x-W%%v')";
			case 'month':
				return "DATE_FORMAT(reg_date, '%%Y-%%m')";
			default:
				return "DATE_FORMAT(reg_date, '%%Y-%%m-%%d')";
		}
	}

	// --- Session management helpers (Batch 4A / used by Chunk 4C) ---

	/**
	 * Check whether the sessions table exists in the database.
	 *
	 * @return bool
	 */
	public static function table_exists() {
		global $wpdb;
		$table = $wpdb->prefix . 'pd_woo_customer_history_sessions';
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	/**
	 * Return recent keyword-search rows for a specific user.
	 *
	 * @param int $user_id WP user ID.
	 * @param int $limit   Max rows to return.
	 * @return object[] Rows with `url` and `reg_date`.
	 */
	public static function get_user_searches( $user_id, $limit = 10 ) {
		global $wpdb;
		$user_id = absint( $user_id );
		$limit   = absint( $limit );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT url, reg_date FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE del = '0' AND user_id = %d AND url LIKE %s ORDER BY reg_date DESC LIMIT %d",
				$user_id,
				'%:search:%',
				$limit
			)
		);
	}

	/**
	 * Soft-delete a session row by setting del = 1.
	 *
	 * @param int $session_id Row ID.
	 * @return int|false Rows affected, or false on error.
	 */
	public static function delete_session( $session_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->update(
			"{$wpdb->prefix}pd_woo_customer_history_sessions",
			array( 'del' => 1 ),
			array( 'id'  => absint( $session_id ) ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Restore a soft-deleted session row by setting del = 0.
	 *
	 * @param int $session_id Row ID.
	 * @return int|false Rows affected, or false on error.
	 */
	public static function restore_session( $session_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->update(
			"{$wpdb->prefix}pd_woo_customer_history_sessions",
			array( 'del' => 0 ),
			array( 'id'  => absint( $session_id ) ),
			array( '%d' ),
			array( '%d' )
		);
	}

	/**
	 * Return the total number of soft-deleted session rows.
	 *
	 * @return int
	 */
	public static function get_trashed_count() {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return (int) $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE del = '1'" );
	}

	/**
	 * Return distinct session IDs that contain product/cart/order activity within the last N days.
	 *
	 * Used by the per-session conversion funnel list-table.
	 *
	 * @param int $num_days Number of days to look back.
	 * @return object[] Rows with `session_id`.
	 */
	public static function get_distinct_sessions_for_funnel( $num_days ) {
		global $wpdb;
		$num_days = absint( $num_days );
		$window   = PDWCHS_Data_Window::pdwchs_days();
		if ( $window > 0 ) {
			$num_days = min( $num_days, $window );
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT session_id
				 FROM {$wpdb->prefix}pd_woo_customer_history_sessions
				 WHERE del = '0'
				   AND reg_date >= DATE_SUB(CURDATE(), INTERVAL %d DAY)
				   AND ( url LIKE %s OR url LIKE %s OR url LIKE %s )
				   AND session_id IS NOT NULL
				 ORDER BY id DESC",
				$num_days,
				'%product/%',
				'%::add_to_cart::%',
				'%::new_order::%'
			)
		);
	}

	/**
	 * Return the distinct URLs for a single session that are relevant to funnel analysis.
	 *
	 * Used by the per-session conversion funnel list-table.
	 *
	 * @param string $session_id Session identifier.
	 * @return object[] Rows with `url`.
	 */
	public static function get_session_funnel_data( $session_id ) {
		global $wpdb;
		$session_id = sanitize_text_field( $session_id );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT url
				 FROM {$wpdb->prefix}pd_woo_customer_history_sessions
				 WHERE del = '0'
				   AND session_id = %s
				   AND ( url LIKE %s OR url LIKE %s OR url LIKE %s OR url LIKE %s )
				 ORDER BY id ASC",
				$session_id,
				'%product/%',
				'%::add_to_cart::%',
				'%::new_order::%',
				'%checkout/order-received/%'
			)
		);
	}

	// --- Behavioral / Churn engagement helpers (added Chunk 4F) ---

	/**
	 * Count session rows for a user with optional date and URL filters.
	 *
	 * @param int   $user_id WP user ID.
	 * @param array $args {
	 *     @type string $since    Minimum reg_date Y-m-d H:i:s (optional).
	 *     @type string $before   Exclusive upper bound reg_date Y-m-d H:i:s (optional).
	 *     @type string $start    Start of inclusive date range (optional, paired with $end).
	 *     @type string $end      End of inclusive date range (optional, paired with $start).
	 *     @type string $url_like URL LIKE pattern, e.g. '%subscription%' (optional).
	 * }
	 * @return int
	 */
	public static function get_session_count_by_user( $user_id, $args = array() ) {
		global $wpdb;
		$user_id  = absint( $user_id );
		$since    = isset( $args['since'] )    ? sanitize_text_field( $args['since'] )    : '';
		$before   = isset( $args['before'] )   ? sanitize_text_field( $args['before'] )   : '';
		$start    = isset( $args['start'] )    ? sanitize_text_field( $args['start'] )    : '';
		$end      = isset( $args['end'] )      ? sanitize_text_field( $args['end'] )      : '';
		$url_like = isset( $args['url_like'] ) ? $args['url_like']                        : '';

		$where = $wpdb->prepare( "del = '0' AND user_id = %d", $user_id );
		if ( ! empty( $since ) ) {
			$where .= $wpdb->prepare( ' AND reg_date >= %s', $since );
		}
		if ( ! empty( $before ) ) {
			$where .= $wpdb->prepare( ' AND reg_date < %s', $before );
		}
		if ( ! empty( $start ) && ! empty( $end ) ) {
			$where .= $wpdb->prepare( ' AND reg_date BETWEEN %s AND %s', $start, $end );
		}
		if ( ! empty( $url_like ) ) {
			$where .= $wpdb->prepare( ' AND url LIKE %s', $url_like );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var( "SELECT COUNT(id) FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE {$where}" );
		return null !== $count ? (int) $count : 0;
	}

	/**
	 * Return the earliest session reg_date within a time window for a user.
	 *
	 * @param int    $user_id WP user ID.
	 * @param string $start   Start of range Y-m-d H:i:s.
	 * @param string $end     End of range Y-m-d H:i:s.
	 * @return string|null MIN(reg_date) string or null if no sessions found.
	 */
	public static function get_first_session_date_in_range( $user_id, $start, $end ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_var(
			$wpdb->prepare(
				"SELECT MIN(reg_date) FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE user_id = %d AND del = 0 AND reg_date BETWEEN %s AND %s",
				absint( $user_id ),
				sanitize_text_field( $start ),
				sanitize_text_field( $end )
			)
		);
	}

	/**
	 * Return referer values from recent sessions for a user.
	 *
	 * Used by mobile/desktop shopper detection.
	 *
	 * @param int $user_id WP user ID.
	 * @param int $limit   Max rows (default 100).
	 * @return object[] Rows with `referer` field.
	 */
	public static function get_user_sessions_referer( $user_id, $limit = 100 ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_results(
			$wpdb->prepare(
				"SELECT referer FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE user_id = %d AND del = 0 AND referer IS NOT NULL AND referer != '' ORDER BY reg_date DESC LIMIT %d",
				absint( $user_id ),
				absint( $limit )
			)
		);
	}

	/**
	 * Count distinct calendar days that have at least one session for a user within a date range.
	 *
	 * Used by researcher behavior detection.
	 *
	 * @param int    $user_id WP user ID.
	 * @param string $start   Start of range Y-m-d H:i:s.
	 * @param string $end     End of range Y-m-d H:i:s.
	 * @return int
	 */
	public static function get_distinct_days_in_range( $user_id, $start, $end ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$count = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT COUNT(DISTINCT DATE(reg_date)) FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE user_id = %d AND del = 0 AND reg_date BETWEEN %s AND %s",
				absint( $user_id ),
				sanitize_text_field( $start ),
				sanitize_text_field( $end )
			)
		);
		return null !== $count ? (int) $count : 0;
	}

	/**
	 * Return aggregate page-view and visit-day counts for a user.
	 *
	 * Used by churn engagement analysis.
	 *
	 * @param int $user_id WP user ID.
	 * @return object|null Object with `total_views`, `unique_pages`, `visit_days` or null.
	 */
	public static function get_page_engagement_data( $user_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		return $wpdb->get_row(
			$wpdb->prepare(
				"SELECT COUNT(id) AS total_views, COUNT(DISTINCT url) AS unique_pages, COUNT(DISTINCT DATE(reg_date)) AS visit_days FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE user_id = %d AND del = 0",
				absint( $user_id )
			)
		);
	}

	/**
	 * Return number of days since the user's most recent session.
	 *
	 * Used by churn engagement analysis.
	 *
	 * @param int $user_id WP user ID.
	 * @return int|null Days since last session, or null if no sessions exist.
	 */
	public static function get_last_session_days_ago( $user_id ) {
		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT DATEDIFF(NOW(), MAX(reg_date)) FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE user_id = %d AND del = 0",
				absint( $user_id )
			)
		);
		return null !== $result ? (int) $result : null;
	}
}