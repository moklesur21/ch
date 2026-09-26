<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// Reason: both SELECTs below are built with $wpdb->prepare(); the only interpolated values
// are $wpdb->prefix and a generated run of %s placeholders, never user input. There is no
// WP CRUD equivalent for a cursor walk over a plugin table, and a one-time batch job must
// not be cached.

/**
 * Guest-to-customer session merge — one-time historical backfill.
 *
 * R15-12B attaches the current visit and R15-13A reaches back through a cart email.
 * Neither touches the history a store already had when the feature shipped: every guest
 * who browsed anonymously and then logged in before that day still has orphaned rows,
 * carrying user_id = 0 on one side of the login and the owner's id on the other, joined
 * by a single session_id.
 *
 * This class walks the sessions table once with a string cursor, resolves each session's
 * owner from the rows that already carry one, and hands the orphans to
 * PDWCHS_Session_Merge::pdwchs_attach_session() — the one write path in the feature.
 *
 * It is a chain of single cron events, not a recurring schedule: each tick schedules its
 * own successor and the last tick schedules nothing. It runs once per site and then
 * self-unschedules.
 *
 * No request surface: no AJAX handler, no REST route, no admin-post action, no UI. It is
 * driven entirely by WP-Cron, which is why there is no nonce and no capability check.
 *
 * @since 3.1.0
 */
class PDWCHS_Session_Merge_Backfill {

	const HOOK             = 'pdwchs_session_merge_backfill';
	const CURSOR_OPTION    = 'pdwchs_session_merge_backfill_cursor';
	const COMPLETED_OPTION = 'pdwchs_session_merge_backfill_completed';
	const STATS_OPTION     = 'pdwchs_session_merge_backfill_stats';
	const LOCK_TRANSIENT   = 'pdwchs_session_merge_backfill_lock';
	const LOCK_TTL         = 15 * MINUTE_IN_SECONDS;
	const BATCH_LIMIT      = 200;   // distinct session ids resolved per tick
	const MAX_EXECUTION    = 50;    // seconds of wall clock per tick
	const BOT_USER_ID      = 999999999999999;

	public function __construct() {
		add_action( self::HOOK, array( $this, 'pdwchs_run_backfill' ) );
		add_action( 'pdwchs_register_pro_cron', array( $this, 'pdwchs_on_register_cron' ) ); // H16
	}

	/**
	 * H16 listener — defer scheduling to init, like PDWCHS_Cart_Cron and PDWCHS_Cohort_Cron.
	 *
	 * pdwchs_register_pro_cron fires before init, where wp_schedule_single_event() can run
	 * ahead of the cron API and the site's timezone being fully settled.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function pdwchs_on_register_cron() {
		add_action( 'init', array( 'PDWCHS_Session_Merge_Backfill', 'pdwchs_schedule_events' ) );
	}

	/**
	 * Schedule the first tick, exactly once per site.
	 *
	 * Ten minutes out, not immediately: the first load after an upgrade is the worst time
	 * to start scanning the largest table on the site — R15-12A's index migration may just
	 * have run, caches are cold, and the admin is probably clicking around.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public static function pdwchs_schedule_events() {
		if ( get_option( self::COMPLETED_OPTION ) ) {
			return;
		}
		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_single_event( time() + ( 10 * MINUTE_IN_SECONDS ), self::HOOK );
		}
	}

	/**
	 * One tick: resolve up to BATCH_LIMIT sessions, advance the cursor, schedule the next.
	 *
	 * The completion guard is checked before the lock on purpose — a finished site must
	 * cost nothing: no transient read or write, no query, no reschedule.
	 *
	 * The lock is released in a finally block rather than at the end of the body: a fatal
	 * or an exception mid-batch would otherwise hold it for its full 15-minute TTL and
	 * stall the chain. The TTL is a deadlock breaker, not a scheduling knob, which is why
	 * it is generous against a 50-second work budget.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function pdwchs_run_backfill() {
		if ( get_option( self::COMPLETED_OPTION ) ) {
			return; // no query, no reschedule
		}
		if ( get_transient( self::LOCK_TRANSIENT ) ) {
			return;
		}
		set_transient( self::LOCK_TRANSIENT, 1, self::LOCK_TTL );

		try {
			$started = time();

			$session_ids = $this->pdwchs_next_batch();

			if ( empty( $session_ids ) ) {
				$this->pdwchs_complete();
				return;
			}

			$owners    = $this->pdwchs_resolve_owners( $session_ids );
			$ambiguous = $this->pdwchs_ambiguous_sessions( $session_ids );

			// Local instance so every Decision 1 guard — user_id = 0, del = '0',
			// NOT LIKE 'BOT::%', the row ceiling, the empty-id return — comes for free and
			// stays in one place. Never extended, never modified.
			require_once __DIR__ . '/Session-Merge.php';
			$merge = new PDWCHS_Session_Merge();

			$scanned       = 0;
			$resolved      = 0;
			$skipped       = 0;
			$rows_attached = 0;
			$cursor        = '';

			foreach ( $session_ids as $session_id ) {
				$scanned++;
				$cursor = $session_id;

				if ( isset( $owners[ $session_id ] ) ) {
					$resolved++;
					$rows_attached += $merge->pdwchs_attach_session( $session_id, $owners[ $session_id ] );
				} elseif ( isset( $ambiguous[ $session_id ] ) ) {
					// Skipped entirely: never guessed, never split by timestamp, never
					// awarded to whoever has more rows. A session with no owner at all is
					// neither resolved nor ambiguous — it is counted as scanned and nothing
					// else, and the cursor moves past it.
					$skipped++;
				}

				// BATCH_LIMIT bounds the query; MAX_EXECUTION bounds the work. A batch of
				// 200 sessions can be 200 rows or 200,000 depending on the site.
				if ( ( time() - $started ) > self::MAX_EXECUTION ) {
					break;
				}
			}

			// Cursor first, always. If the tick is killed between scheduling the successor
			// and writing the cursor, the successor reprocesses the same batch — harmless
			// once, but a deterministic kill would loop forever.
			update_option( self::CURSOR_OPTION, $cursor, false );

			wp_schedule_single_event( time() + 60, self::HOOK );

			$this->pdwchs_accumulate_stats( $scanned, $resolved, $skipped, $rows_attached );
		} finally {
			delete_transient( self::LOCK_TRANSIENT );
		}
	}

	/**
	 * The next batch of distinct session ids, above the stored cursor.
	 *
	 * The cursor is the session_id string, not a numeric offset. The sessions table is
	 * still growing while the backfill runs, and an OFFSET walk over a growing table skips
	 * rows — insert anything below the current offset and the window shifts. A string
	 * cursor is stable under concurrent inserts, and R15-12A's idx_session_user
	 * (session_id, user_id) makes it a seek rather than a scan.
	 *
	 * session_id <> '' is Decision 1's guard re-stated at the batch layer: on a
	 * broken-sessions host every row carries '', DISTINCT would return it as one "session"
	 * containing the entire site, and '' sorts first so it would be the very first thing
	 * the walk ever touched. session_id is NULL-able, and <> '' is false for NULL in SQL,
	 * so nulls are excluded by the same predicate.
	 *
	 * @since 3.1.0
	 *
	 * @return array List of session ids, ascending. Empty when the walk is exhausted.
	 */
	private function pdwchs_next_batch() {
		global $wpdb;

		$cursor = (string) get_option( self::CURSOR_OPTION, '' );

		return (array) $wpdb->get_col(
			$wpdb->prepare(
				"SELECT DISTINCT session_id
				 FROM   {$wpdb->prefix}pd_woo_customer_history_sessions
				 WHERE  session_id <> ''
				   AND  session_id >  %s
				 ORDER  BY session_id ASC
				 LIMIT  %d",
				$cursor,
				self::BATCH_LIMIT
			)
		);
	}

	/**
	 * Map of session_id => owner user id, for the sessions that resolve unambiguously.
	 *
	 * HAVING COUNT(DISTINCT user_id) = 1 is the point of this query. A shared browser — a
	 * household laptop, an office machine, a kiosk — can have one person log out and
	 * another log in inside a single PHP session, and that session resolves to two owners.
	 * It is skipped, never guessed: attaching a stranger's browsing to a named customer is
	 * exactly the harm this feature exists to avoid, and at backfill scale a heuristic
	 * would do it thousands of times silently.
	 *
	 * MIN(user_id) is safe only because of that HAVING. With exactly one distinct id in the
	 * group, MIN() is that id. Without the HAVING it would award every ambiguous session to
	 * the lowest user id on it — on most stores, the administrator.
	 *
	 * The bot sentinel is excluded numerically here and again by pdwchs_attach_session()'s
	 * NOT LIKE 'BOT::%' on the write: free builds never detect bots, so a site that ran
	 * free for years has crawler rows written as ordinary guests.
	 *
	 * @since 3.1.0
	 *
	 * @param array $session_ids Session ids from the current batch.
	 * @return array session_id => user id.
	 */
	private function pdwchs_resolve_owners( $session_ids ) {
		global $wpdb;

		$placeholders = implode( ', ', array_fill( 0, count( $session_ids ), '%s' ) );

		$sql = "SELECT session_id, MIN(user_id) AS owner
				FROM   {$wpdb->prefix}pd_woo_customer_history_sessions
				WHERE  session_id IN ( {$placeholders} )
				  AND  user_id  > 0
				  AND  user_id <> " . self::BOT_USER_ID . "
				GROUP  BY session_id
				HAVING COUNT(DISTINCT user_id) = 1";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $sql is a literal with a generated run of %s placeholders; every id is passed as a replacement, never interpolated.
		$rows = $wpdb->get_results( $wpdb->prepare( $sql, ...array_values( $session_ids ) ) );

		$owners = array();
		foreach ( (array) $rows as $row ) {
			$owners[ (string) $row->session_id ] = absint( $row->owner );
		}

		return $owners;
	}

	/**
	 * Lookup set of the batch's session ids that carry more than one distinct owner.
	 *
	 * The resolve query filters these out by design, so they are invisible to it — and the
	 * ambiguous count is the one number a support case will ask about. Deliberately carries
	 * no MIN(): there is no owner here to pick, and writing one would invite the heuristic
	 * this feature refuses.
	 *
	 * @since 3.1.0
	 *
	 * @param array $session_ids Session ids from the current batch.
	 * @return array session_id => true.
	 */
	private function pdwchs_ambiguous_sessions( $session_ids ) {
		global $wpdb;

		$placeholders = implode( ', ', array_fill( 0, count( $session_ids ), '%s' ) );

		$sql = "SELECT session_id
				FROM   {$wpdb->prefix}pd_woo_customer_history_sessions
				WHERE  session_id IN ( {$placeholders} )
				  AND  user_id  > 0
				  AND  user_id <> " . self::BOT_USER_ID . "
				GROUP  BY session_id
				HAVING COUNT(DISTINCT user_id) > 1";

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- $sql is a literal with a generated run of %s placeholders; every id is passed as a replacement, never interpolated.
		$ids = (array) $wpdb->get_col( $wpdb->prepare( $sql, ...array_values( $session_ids ) ) );

		$ambiguous = array();
		foreach ( $ids as $id ) {
			$ambiguous[ (string) $id ] = true;
		}

		return $ambiguous;
	}

	/**
	 * The walk is exhausted: delete the cursor, stamp completion, clear the hook.
	 *
	 * A stale cursor left behind would restart the whole walk if the completion option were
	 * ever cleared. The hook is cleared belt-and-braces: the last tick schedules no
	 * successor, but a concurrent tick, a WP-CLI cron run or a manual
	 * wp_schedule_single_event() could have queued one. Stats are left as they accumulated.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	private function pdwchs_complete() {
		delete_option( self::CURSOR_OPTION );
		update_option( self::COMPLETED_OPTION, current_time( 'mysql' ), false );
		wp_clear_scheduled_hook( self::HOOK );
	}

	/**
	 * Read-modify-write the four cumulative counters.
	 *
	 * Cumulative across ticks, not per-tick: this is the only observability the feature has.
	 *
	 * @since 3.1.0
	 *
	 * @param int $scanned       Sessions looked at this tick.
	 * @param int $resolved      Sessions that resolved to exactly one owner.
	 * @param int $skipped       Sessions skipped because they carried more than one owner.
	 * @param int $rows_attached Rows re-keyed by pdwchs_attach_session().
	 * @return void
	 */
	private function pdwchs_accumulate_stats( $scanned, $resolved, $skipped, $rows_attached ) {
		$stats = get_option( self::STATS_OPTION, array() );
		if ( ! is_array( $stats ) ) {
			$stats = array();
		}

		$totals = array(
			'scanned'           => ( isset( $stats['scanned'] ) ? (int) $stats['scanned'] : 0 ) + (int) $scanned,
			'resolved'          => ( isset( $stats['resolved'] ) ? (int) $stats['resolved'] : 0 ) + (int) $resolved,
			'skipped_ambiguous' => ( isset( $stats['skipped_ambiguous'] ) ? (int) $stats['skipped_ambiguous'] : 0 ) + (int) $skipped,
			'rows_attached'     => ( isset( $stats['rows_attached'] ) ? (int) $stats['rows_attached'] : 0 ) + (int) $rows_attached,
		);

		update_option( self::STATS_OPTION, $totals, false );
	}
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
