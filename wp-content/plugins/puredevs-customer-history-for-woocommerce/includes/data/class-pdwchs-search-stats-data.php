<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// phpcs:disable WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
// Reason: All queries use $wpdb->prepare(). Results cached by callers via transients.
// Table prefix is internal, never user input. LIKE wildcards are hardcoded URL-encoding
// constants derived from the WooCommerce session recording scheme.
// NotPrepared fires for $sql built via $wpdb->prepare() fragments — safe, not user input.
// UnfinishedPrepare fires because %s placeholders live inside a dynamically built
// $where variable that PHPCS cannot statically resolve.

/**
 * Free data layer for all session-table search and URL queries.
 *
 * Migrated from PD_Customer_History_Stats and PD_Customer_History_Taxonomy_Stats.
 * All raw $wpdb queries against the sessions table for search/URL statistics now
 * live here. Callers (both Free and Pro) access the data through this class.
 * PDWCHS_Sessions_Data search stubs delegate to the matching method here.
 *
 * @since 2.2.0
 */
class PDWCHS_Search_Stats_Data {

	/**
	 * Count sessions grouped by non-action URL.
	 *
	 * Migrated from PD_Customer_History_Stats::pdwchs_woo_count_by_site_pages().
	 *
	 * @param array $args {
	 *     @type string $search URL substring filter (optional).
	 *     @type string $start  Start date Y-m-d H:i:s (optional).
	 *     @type string $end    End date Y-m-d H:i:s (optional).
	 *     @type int    $limit  Max rows; 0 = unlimited (optional).
	 * }
	 * @return object[] Rows with `qta` (count) and `url`.
	 */
	public static function get_url_counts( $args = array() ) {
		global $wpdb;
		$search = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
		$start  = isset( $args['start'] )  ? sanitize_text_field( $args['start'] )  : '';
		$end    = isset( $args['end'] )    ? sanitize_text_field( $args['end'] )    : '';
		$limit  = isset( $args['limit'] )  ? absint( $args['limit'] )               : 0;

		$start = PDWCHS_Data_Window::pdwchs_clamp_start( $start );

		$where = "1=1 AND del='0' AND url NOT LIKE '%::%'";
		if ( ! empty( $search ) ) {
			$where .= $wpdb->prepare( ' AND url LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
		}
		if ( ! empty( $start ) && ! empty( $end ) ) {
			$where .= $wpdb->prepare( ' AND reg_date BETWEEN %s AND %s', $start, $end );
		} elseif ( ! empty( $start ) ) {
			$where .= $wpdb->prepare( ' AND reg_date >= %s', $start );
		}

		$sql = "SELECT COUNT(id) AS qta, url FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE {$where} GROUP BY url";
		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' ORDER BY qta DESC LIMIT %d', $limit );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Count sessions grouped by non-action URL for a specific user.
	 *
	 * Migrated from PD_Customer_History_Stats::pdwchs_woo_count_by_site_pages_by_user().
	 *
	 * @param int   $user_id WP user ID.
	 * @param array $args {
	 *     @type string $search URL substring filter (optional).
	 *     @type string $start  Start date (optional).
	 *     @type string $end    End date (optional).
	 *     @type int    $limit  Max rows; 0 = unlimited (optional).
	 *     @type string $order  'ASC' or 'DESC' (default 'DESC').
	 * }
	 * @return object[] Rows with `qta` (count) and `url`.
	 */
	public static function get_url_counts_by_user( $user_id, $args = array() ) {
		global $wpdb;
		$user_id = absint( $user_id );
		$search  = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
		$start   = isset( $args['start'] )  ? sanitize_text_field( $args['start'] )  : '';
		$end     = isset( $args['end'] )    ? sanitize_text_field( $args['end'] )    : '';
		$limit   = isset( $args['limit'] )  ? absint( $args['limit'] )               : 0;
		$order   = ( isset( $args['order'] ) && 'ASC' === strtoupper( $args['order'] ) ) ? 'ASC' : 'DESC';

		$start = PDWCHS_Data_Window::pdwchs_clamp_start( $start );

		$where = $wpdb->prepare( "1=1 AND del='0' AND url NOT LIKE '%::%' AND user_id = %d", $user_id );
		if ( ! empty( $search ) ) {
			$where .= $wpdb->prepare( ' AND url LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
		}
		if ( ! empty( $start ) && ! empty( $end ) ) {
			$where .= $wpdb->prepare( ' AND reg_date BETWEEN %s AND %s', $start, $end );
		} elseif ( ! empty( $start ) ) {
			$where .= $wpdb->prepare( ' AND reg_date >= %s', $start );
		}

		$sql = "SELECT COUNT(id) AS qta, url FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE {$where} GROUP BY url ORDER BY qta {$order}";
		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', $limit );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Count keyword search sessions grouped by search term.
	 *
	 * Migrated from PD_Customer_History_Stats::pdwchs_woo_count_by_keywords().
	 *
	 * @param array $args {
	 *     @type string $search Keyword filter (optional).
	 *     @type int    $limit  Max rows; 0 = unlimited (optional).
	 * }
	 * @return object[] Rows with `qta` (count) and `url` (raw ACTION::search::keyword).
	 */
	public static function get_keyword_counts( $args = array() ) {
		global $wpdb;
		$search = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
		$limit  = isset( $args['limit'] )  ? absint( $args['limit'] )               : 0;

		$where = "1=1 AND del='0' AND url LIKE '%:search:%'";
		if ( ! empty( $search ) ) {
			$where .= $wpdb->prepare( ' AND url LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
		}
		$min_start = PDWCHS_Data_Window::pdwchs_min_start();
		if ( '' !== $min_start ) {
			$where .= $wpdb->prepare( ' AND reg_date >= %s', $min_start . ' 00:00:00' );
		}

		$sql = "SELECT COUNT(id) AS qta, url FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE {$where} GROUP BY url";
		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' ORDER BY qta DESC LIMIT %d', $limit );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Return the most recent search sessions.
	 *
	 * Migrated from PD_Customer_History_Stats::pdwchs_woo_last_searches().
	 *
	 * @param int   $limit Max rows to return; 0 = unlimited.
	 * @param array $args  { search: string keyword filter (optional) }.
	 * @return object[] Rows with `url` and `reg_date`.
	 */
	public static function get_last_searches( $limit = 10, $args = array() ) {
		global $wpdb;
		$limit  = absint( $limit );
		$search = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';

		$where = "1=1 AND del='0' AND url LIKE '%:search:%'";
		if ( ! empty( $search ) ) {
			$where .= $wpdb->prepare( ' AND url LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
		}
		$min_start = PDWCHS_Data_Window::pdwchs_min_start();
		if ( '' !== $min_start ) {
			$where .= $wpdb->prepare( ' AND reg_date >= %s', $min_start . ' 00:00:00' );
		}

		$sql = "SELECT url, reg_date FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE {$where} ORDER BY reg_date DESC";
		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' LIMIT %d', $limit );
		}

		return $wpdb->get_results( $sql );
	}

	/**
	 * Count distinct sessions matching a URL LIKE pattern.
	 *
	 * Used by behavioral and churn analysis to count session activity for a given
	 * URL pattern (e.g. product views, cart additions) per user or time window.
	 *
	 * @param string $url_like LIKE pattern (pass '' to match any URL).
	 * @param array  $args {
	 *     @type int    $user_id WP user ID filter (optional).
	 *     @type string $since   Minimum reg_date Y-m-d H:i:s (optional).
	 *     @type string $start   Start of date range (optional).
	 *     @type string $end     End of date range (optional).
	 * }
	 * @return int Count of distinct matching session_ids.
	 */
	public static function get_distinct_sessions_by_url( $url_like, $args = array() ) {
		global $wpdb;
		$user_id = isset( $args['user_id'] ) ? absint( $args['user_id'] )              : 0;
		$since   = isset( $args['since'] )   ? sanitize_text_field( $args['since'] )   : '';
		$start   = isset( $args['start'] )   ? sanitize_text_field( $args['start'] )   : '';
		$end     = isset( $args['end'] )     ? sanitize_text_field( $args['end'] )     : '';

		$since = PDWCHS_Data_Window::pdwchs_clamp_start( $since );
		$start = PDWCHS_Data_Window::pdwchs_clamp_start( $start );

		$where = "del = '0' AND session_id <> ''";
		if ( ! empty( $url_like ) ) {
			$where .= $wpdb->prepare( ' AND url LIKE %s', $url_like );
		}
		if ( $user_id > 0 ) {
			$where .= $wpdb->prepare( ' AND user_id = %d', $user_id );
		}
		if ( ! empty( $since ) ) {
			$where .= $wpdb->prepare( ' AND reg_date >= %s', $since );
		}
		if ( ! empty( $start ) && ! empty( $end ) ) {
			$where .= $wpdb->prepare( ' AND reg_date BETWEEN %s AND %s', $start, $end );
		}

		$count = $wpdb->get_var( "SELECT COUNT(DISTINCT session_id) FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE {$where}" );
		return null !== $count ? (int) $count : 0;
	}

	/**
	 * Return distinct page URLs visited (excludes ACTION:: entries).
	 *
	 * Used by behavioral analysis to examine what pages a user has visited.
	 *
	 * @param array $args {
	 *     @type int    $user_id WP user ID filter (optional).
	 *     @type string $start   Start of date range (optional).
	 *     @type string $end     End of date range (optional).
	 * }
	 * @return object[] Rows with `url`.
	 */
	public static function get_distinct_pages( $args = array() ) {
		global $wpdb;
		$user_id = isset( $args['user_id'] ) ? absint( $args['user_id'] )            : 0;
		$start   = isset( $args['start'] )   ? sanitize_text_field( $args['start'] ) : '';
		$end     = isset( $args['end'] )     ? sanitize_text_field( $args['end'] )   : '';

		$start = PDWCHS_Data_Window::pdwchs_clamp_start( $start );

		$where = "del = '0' AND url NOT LIKE '%::%'";
		if ( $user_id > 0 ) {
			$where .= $wpdb->prepare( ' AND user_id = %d', $user_id );
		}
		if ( ! empty( $start ) && ! empty( $end ) ) {
			$where .= $wpdb->prepare( ' AND reg_date BETWEEN %s AND %s', $start, $end );
		} elseif ( ! empty( $start ) ) {
			$where .= $wpdb->prepare( ' AND reg_date >= %s', $start );
		}

		return $wpdb->get_results( "SELECT DISTINCT url FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE {$where}" );
	}

	/**
	 * Count sessions for a taxonomy label grouped by URL.
	 *
	 * Migrated from PD_Customer_History_Taxonomy_Stats::pdwchs_woo_count_by_taxonomy().
	 *
	 * @param string $taxonomy Taxonomy singular label (e.g. 'Category', 'Tag').
	 * @param array  $args {
	 *     @type string $search URL substring filter (optional).
	 *     @type int    $limit  Max rows; 0 = unlimited (optional).
	 * }
	 * @return object[] Rows with `qta` (count) and `url`.
	 */
	public static function get_url_counts_for_taxonomy( $taxonomy, $args = array() ) {
		global $wpdb;
		$taxonomy = sanitize_text_field( $taxonomy );
		$search   = isset( $args['search'] ) ? sanitize_text_field( $args['search'] ) : '';
		$limit    = isset( $args['limit'] )  ? absint( $args['limit'] )               : 0;

		$where = $wpdb->prepare( "1=1 AND del='0' AND url LIKE %s", '%' . $taxonomy . '%' );
		if ( ! empty( $search ) ) {
			$where .= $wpdb->prepare( ' AND url LIKE %s', '%' . $wpdb->esc_like( $search ) . '%' );
		}

		$sql = "SELECT COUNT(id) AS qta, url FROM {$wpdb->prefix}pd_woo_customer_history_sessions WHERE {$where} GROUP BY url";
		if ( $limit > 0 ) {
			$sql .= $wpdb->prepare( ' ORDER BY qta DESC LIMIT %d', $limit );
		}

		return $wpdb->get_results( $sql );
	}
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
// phpcs:enable WordPress.DB.PreparedSQLPlaceholders.LikeWildcardsInQuery, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
