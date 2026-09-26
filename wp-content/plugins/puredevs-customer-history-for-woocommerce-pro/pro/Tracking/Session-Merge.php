<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// Reason: the UPDATE below is built with $wpdb->prepare(); the only interpolated value is
// $wpdb->prefix, never user input. There is no WP CRUD equivalent for a bulk re-key, and a
// write path must not be cached.

/**
 * Guest-to-customer session merge — engine.
 *
 * Re-keys guest session rows (user_id = 0) onto a real WP user id, matching on the PHP
 * session id the capture path already stores. Creates no new fact about a customer; it
 * corrects the ownership of rows that already exist.
 *
 * This class registers no hooks. R15-12B wires it to wp_login and user_register.
 *
 * @since 3.1.0
 */
class PDWCHS_Session_Merge {

	/** Bot sentinel user id written by the capture path. */
	const BOT_USER_ID = 999999999999999;

	/** Default ceiling on rows re-keyed per call, before the pdwchs_session_merge_max_rows filter. */
	const DEFAULT_MAX_ROWS = 5000;

	public function __construct() {
		$this->pdwchs_register_hooks();
	}

	/**
	 * Register the authentication hooks that trigger a merge.
	 *
	 * Both fire well after init, so session_id() is already populated by
	 * pdwchs_start_session() (init priority 1).
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function pdwchs_register_hooks() {
		add_action( 'wp_login',      array( $this, 'pdwchs_on_login' ),    10, 2 );
		add_action( 'user_register', array( $this, 'pdwchs_on_register' ), 10, 1 );

		// Priority 30: PDWCHS_Cart_Tracker::pdwchs_mark_completed holds 10 and
		// PDWCHS_Cart_Recovery_Detector::pdwchs_detect_recovery holds 20, so both have
		// settled the cart row's status before the email bridge reads it.
		add_action( 'woocommerce_checkout_order_processed',           array( $this, 'pdwchs_on_checkout' ),        30, 1 );
		add_action( 'woocommerce_store_api_checkout_order_processed', array( $this, 'pdwchs_on_checkout_blocks' ), 30, 1 );
	}

	/**
	 * wp_login listener — attach the current visit to the authenticating user.
	 *
	 * Core fires this as do_action( 'wp_login', $user->user_login, $user ), so the
	 * registration must accept 2 args: with 1, $user arrives null and the first
	 * property read is fatal.
	 *
	 * @since 3.1.0
	 *
	 * @param string  $user_login Unused; the hook's first argument.
	 * @param WP_User $user       The user who just logged in.
	 * @return void
	 */
	public function pdwchs_on_login( $user_login, WP_User $user ) {
		$user_id = absint( $user->ID );
		if ( 0 === $user_id ) {
			return;
		}

		$this->pdwchs_attach_current_session( $user_id );
	}

	/**
	 * user_register listener — attach the current visit to the newly created user.
	 *
	 * Covers /my-account/ registration, admin-created users and WooCommerce checkout
	 * account creation alike, which is why no separate WooCommerce customer-created
	 * listener is needed — it would double-fire on the flow that matters most.
	 *
	 * @since 3.1.0
	 *
	 * @param int $user_id The new user's id.
	 * @return void
	 */
	public function pdwchs_on_register( $user_id ) {
		$user_id = absint( $user_id );
		if ( 0 === $user_id ) {
			return;
		}

		$this->pdwchs_attach_current_session( $user_id );

		// The email path is what makes "a guest's email later registers" work at all:
		// by the time they register the original PHP session is long gone, so the
		// carts row is the only surviving link back to that visit.
		$user = get_userdata( $user_id );
		if ( $user && ! empty( $user->user_email ) ) {
			$this->pdwchs_attach_by_email( $user->user_email, $user_id );
		}
	}

	/**
	 * woocommerce_checkout_order_processed listener — attach the buyer's sessions.
	 *
	 * Registered at priority 30 so the cart listeners at 10 and 20 have finished.
	 * The order is read through wc_get_order() and CRUD getters only, which is what
	 * makes HPOS on and HPOS off behave identically.
	 *
	 * @since 3.1.0
	 *
	 * @param int $order_id
	 * @return void
	 */
	public function pdwchs_on_checkout( $order_id ) {
		$order_id = absint( $order_id );
		if ( 0 === $order_id ) {
			return;
		}

		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return;
		}

		$email = sanitize_email( $order->get_billing_email() );

		// A logged-in customer's order already carries the identity; the get_user_by()
		// lookup is a query that would tell us nothing new.
		$user_id = absint( $order->get_customer_id() );
		if ( 0 === $user_id && is_email( $email ) ) {
			$user    = get_user_by( 'email', $email );
			$user_id = $user ? absint( $user->ID ) : 0;
		}

		if ( 0 === $user_id ) {
			// A guest with no account has no profile page to attach to. Silent no-op:
			// no error, no notice, no partial write, order unaffected.
			return;
		}

		// Both run. The first claims the visit they are checking out from right now;
		// the second reaches back to earlier visits bridged through the carts table.
		$this->pdwchs_attach_current_session( $user_id );
		$this->pdwchs_attach_by_email( $email, $user_id );
	}

	/**
	 * Blocks checkout adapter — the Store API passes a WC_Order, not an id.
	 *
	 * Same shape as PDWCHS_Cart_Tracker::pdwchs_mark_completed_blocks(): type-check,
	 * extract the id, delegate. Two adapters that behaved differently on Blocks and
	 * classic is exactly the bug this prevents.
	 *
	 * @since 3.1.0
	 *
	 * @param WC_Order $order
	 * @return void
	 */
	public function pdwchs_on_checkout_blocks( $order ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$this->pdwchs_on_checkout( $order->get_id() );
	}

	/**
	 * Attach every session bridged to this email through the carts table.
	 *
	 * The sessions table has no email column and must not gain one — it is free-owned
	 * and has no privacy exporter or eraser. The carts table already holds the email
	 * and already ships both, so the carts row is the bridge and all new PII linkage
	 * stays inside a Pro table that has erasure.
	 *
	 * @since 3.1.0
	 *
	 * @param string $email   Customer email.
	 * @param int    $user_id WP user id to attach the rows to.
	 * @return int Number of rows re-keyed across all matched sessions.
	 */
	public function pdwchs_attach_by_email( $email, $user_id ) {
		// sanitize_email() strips illegal characters but can still return something
		// invalid; is_email() is the validity test. Both, in that order.
		$email = sanitize_email( is_scalar( $email ) ? (string) $email : '' );
		if ( ! is_email( $email ) ) {
			return 0;
		}

		if ( ! is_numeric( $user_id ) || $user_id <= 0 ) {
			return 0;
		}
		$user_id = absint( $user_id );

		global $wpdb;

		// IS NOT NULL skips every carts row written before this chunk — they stay null
		// forever, deliberately. <> '' skips rows written on a broken-sessions host:
		// pdwchs_attach_session() would reject '' anyway, but this method's safety must
		// not depend on a guard in another method.
		$session_ids = $wpdb->get_col( $wpdb->prepare(
			"SELECT DISTINCT php_session_id
			 FROM   {$wpdb->prefix}pdwchs_carts
			 WHERE  email          = %s
			   AND  php_session_id IS NOT NULL
			   AND  php_session_id <> ''",
			$email
		) );

		if ( empty( $session_ids ) ) {
			return 0;
		}

		// One bounded call per session rather than a single IN (…) UPDATE: a shared row
		// ceiling would let a busy first session starve the rest and truncate the LIMIT
		// unpredictably. Per-session calls reuse every guard for free.
		$attached = 0;
		foreach ( $session_ids as $session_id ) {
			$attached += $this->pdwchs_attach_session( $session_id, $user_id );
		}

		return $attached;
	}

	/**
	 * Attach every guest row in one PHP session to a real user.
	 *
	 * @since 3.1.0
	 *
	 * @param string $session_id PHP session id, as stored in the sessions table.
	 * @param int    $user_id    WP user id to attach the rows to.
	 * @return int Number of rows re-keyed. 0 on any rejected input.
	 */
	public function pdwchs_attach_session( $session_id, $user_id ) {

		// This guard returns first, and deliberately stands alone. On a host where
		// session_start() fails every row carries session_id = '', so a match on ''
		// would attach the entire anonymous history of the site to one account.
		$session_id = is_scalar( $session_id ) ? trim( (string) $session_id ) : '';
		if ( '' === $session_id ) {
			return 0;
		}

		// Reject the raw value BEFORE absint(): absint( -7 ) is 7, so a check made
		// after it could never see a negative id and would attach a stranger's rows
		// to user 7. is_numeric() also rejects null, '' and non-numeric strings.
		if ( ! is_numeric( $user_id ) || $user_id <= 0 ) {
			return 0;
		}

		$user_id = absint( $user_id );

		$limit = $this->pdwchs_max_rows();

		global $wpdb;

		// One statement, and exactly one. $wpdb->query() returns the affected-row count.
		// The BOT:: pattern is passed as a replacement parameter rather than inlined as
		// 'BOT::%%' — WordPress.DB.PreparedSQLPlaceholders wants LIKE wildcards prepared,
		// and it removes the escaping hazard of a literal percent inside prepare().
		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->prefix}pd_woo_customer_history_sessions
				 SET    user_id = %d
				 WHERE  session_id = %s
				   AND  user_id    = 0
				   AND  del        = '0'
				   AND  url NOT LIKE %s
				 LIMIT  %d",
				$user_id,
				$session_id,
				$wpdb->esc_like( 'BOT::' ) . '%',
				$limit
			)
		);

		return ( false === $result ) ? 0 : (int) $result;
	}

	/**
	 * Attach the current request's PHP session to a user.
	 *
	 * session_id() is populated because pdwchs_start_session() runs on init priority 1,
	 * ahead of the tracking registrar, and PHP never regenerates the id across a
	 * WordPress login. An empty id means sessions are unavailable, which the guard in
	 * pdwchs_attach_session() turns into a zero-row no-op.
	 *
	 * @since 3.1.0
	 *
	 * @param int $user_id WP user id.
	 * @return int Number of rows re-keyed.
	 */
	public function pdwchs_attach_current_session( $user_id ) {
		return $this->pdwchs_attach_session( session_id(), $user_id );
	}

	/**
	 * Ceiling on rows re-keyed by one call.
	 *
	 * A filter is public API, so the value is cast and clamped: anything below 1 would
	 * silently make every merge a no-op, which looks exactly like the feature being
	 * broken. There is deliberately no upper clamp.
	 *
	 * @since 3.1.0
	 *
	 * @return int
	 */
	private function pdwchs_max_rows() {
		$max = (int) apply_filters( 'pdwchs_session_merge_max_rows', self::DEFAULT_MAX_ROWS );

		return ( $max >= 1 ) ? $max : self::DEFAULT_MAX_ROWS;
	}
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
