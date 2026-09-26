<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
// Reason: the session query, the cart-milestone query and the carts table-exists probe are all
// built with $wpdb->prepare(); the only interpolated value in any of them is $wpdb->prefix, never
// user input. The assembled page is cached in a transient by pdwchs_get_events(), which is where
// the caching this sniff asks about actually belongs.

/**
 * Customer activity timeline — event assembler.
 *
 * Returns one page of normalised activity events for a single customer, newest first,
 * merged from three sources: the sessions table, WooCommerce orders and the carts table.
 * Returns data, never HTML — the template and the AJAX handler own presentation.
 *
 * @since 3.1.0
 */
class PDWCHS_Timeline_Data {

	/** Bot sentinel user id written by the capture path. */
	const BOT_USER_ID = 999999999999999;

	/** Default events per page, before the pdwchs_timeline_page_size filter. */
	const DEFAULT_PAGE_SIZE = 25;

	/**
	 * Sort weight per type, used only to break exact-timestamp ties deterministically.
	 * Lower sorts first within one second. Values are arbitrary but must never change.
	 */
	const TYPE_ORDER = array(
		'ordered'         => 10,
		'cart_recovered'  => 20,
		'cart_abandoned'  => 30,
		'added_to_cart'   => 40,
		'viewed'          => 50,
		'searched'        => 60,
	);

	/**
	 * Seconds to add to a MySQL-server-clock value to express it in site-local time.
	 * Null until pdwchs_clock_delta() resolves it; see that method for why it is lazy.
	 *
	 * @var int|null
	 */
	private $clock_delta = null;

	/**
	 * Return one page of timeline events for a customer, newest first.
	 *
	 * @param int   $user_id WP user id.
	 * @param array $args {
	 *     @type string       $before    Exclusive upper bound, 'Y-m-d H:i:s' site-local. '' = newest page.
	 *     @type int          $page_size Override the filtered default. Optional.
	 *     @type string|array $type      One or more TYPE_ORDER keys. Absent or unrecognised = all.
	 * }
	 * @return array {
	 *     @type array       $events      List of event arrays, newest first.
	 *     @type string|null $next_cursor Pass as $args['before'] for the next page, or null.
	 *     @type bool        $has_more    Whether another page exists.
	 * }
	 */
	public function pdwchs_get_events( $user_id, $args = array() ) {
		$user_id = absint( $user_id );
		if ( 0 === $user_id ) {
			return $this->pdwchs_empty_result();
		}

		$page_size = $this->pdwchs_page_size( $args );
		$before    = $this->pdwchs_normalise_cursor( $args );
		$types     = $this->pdwchs_normalise_types( $args );

		// Every parameter that changes the answer is in the key — CLAUDE.md rule 9. Dropping
		// $types would serve an "ordered only" page to someone who asked for everything.
		$cache_key = 'pdwchs_timeline_' . md5( wp_json_encode( array( $user_id, $before, $types, $page_size ) ) );

		$cached = get_transient( $cache_key );
		if ( false !== $cached ) {
			return $cached;
		}

		$sources = $this->pdwchs_sources_for_types( $types );
		$events  = array();

		// Each source over-fetches by one so pdwchs_cut_page() can tell whether the boundary
		// second is complete. A source that comes back short only moves next_cursor earlier.
		if ( in_array( 'session', $sources, true ) ) {
			$rows   = $this->pdwchs_fetch_session_rows( $user_id, $before, $page_size + 1 );
			$events = array_merge( $events, $this->pdwchs_map_session_rows( $rows ) );
		}

		if ( in_array( 'order', $sources, true ) ) {
			$events = array_merge( $events, $this->pdwchs_fetch_order_events( $user_id, $before, $page_size + 1 ) );
		}

		if ( in_array( 'cart', $sources, true ) ) {
			$events = array_merge( $events, $this->pdwchs_fetch_cart_events( $user_id, $before, $page_size + 1 ) );
		}

		// The source gate skips whole queries; this drops the types a queried source also
		// produces — the session query cannot return 'searched' without 'viewed' beside it.
		if ( ! empty( $types ) ) {
			$events = array_values(
				array_filter(
					$events,
					function ( $event ) use ( $types ) {
						return in_array( $event['type'], $types, true );
					}
				)
			);
		}

		usort( $events, array( $this, 'pdwchs_compare_events' ) );

		$page = $this->pdwchs_cut_page( $events, $page_size );

		set_transient( $cache_key, $page, 5 * MINUTE_IN_SECONDS );

		return $page;
	}

	/**
	 * The one shape an empty page ever takes.
	 *
	 * Never false, never null, never a missing key — R15-11A's template indexes all three.
	 *
	 * @return array
	 */
	private function pdwchs_empty_result() {
		return array(
			'events'      => array(),
			'next_cursor' => null,
			'has_more'    => false,
		);
	}

	/**
	 * Resolve the page size from the caller argument or the filter, clamped.
	 *
	 * A filter is public API, so a site can hand back 0, -5, '25' or null. Zero would make
	 * LIMIT %d return nothing and the panel would look broken with no error explaining it.
	 * Anything below 1 falls back to the default. There is deliberately no upper clamp.
	 *
	 * @param array $args Arguments passed to pdwchs_get_events().
	 * @return int
	 */
	private function pdwchs_page_size( $args ) {
		$size = isset( $args['page_size'] )
			? (int) $args['page_size']
			: (int) apply_filters( 'pdwchs_timeline_page_size', self::DEFAULT_PAGE_SIZE );

		return ( $size >= 1 ) ? $size : self::DEFAULT_PAGE_SIZE;
	}

	/**
	 * Validate the cursor to a literal 'Y-m-d H:i:s' string.
	 *
	 * An unparseable cursor means "newest page", never an error and never a raw value
	 * reaching SQL. This is the server-side half of R15-11B's cursor hardening.
	 *
	 * @param array $args Arguments passed to pdwchs_get_events().
	 * @return string Validated cursor, or '' for the newest page.
	 */
	private function pdwchs_normalise_cursor( $args ) {
		$before = isset( $args['before'] ) && is_scalar( $args['before'] )
			? trim( (string) $args['before'] )
			: '';
		if ( '' === $before ) {
			return '';
		}
		if ( ! preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $before ) ) {
			return '';
		}
		return $before;
	}

	/**
	 * Normalise the requested type filter to a whitelisted list of TYPE_ORDER keys.
	 *
	 * An unknown value degrades to "all", never to "none": an empty stream would be
	 * indistinguishable from a customer with no activity and the panel would look broken
	 * instead of ignoring bad input. This is the server half of R15-11B's cursor hardening.
	 *
	 * @param array $args Arguments passed to pdwchs_get_events().
	 * @return array List of type keys to include. Empty array = all types.
	 */
	private function pdwchs_normalise_types( $args ) {
		if ( empty( $args['type'] ) ) {
			return array();
		}

		$requested = (array) $args['type'];
		$allowed   = array_keys( self::TYPE_ORDER );
		$types     = array_values( array_intersect( $requested, $allowed ) );

		return $types;
	}

	/**
	 * Which sources have to be queried to satisfy a type filter.
	 *
	 * Filtering to 'ordered' and still running the session query would scan the biggest table
	 * in the plugin only to throw every row away, so the filter gates the query rather than
	 * the result array.
	 *
	 * @param array $types Normalised type list; empty = all types.
	 * @return array Subset of 'session', 'order', 'cart'.
	 */
	private function pdwchs_sources_for_types( $types ) {
		$produces = array(
			'session' => array( 'searched', 'viewed', 'added_to_cart' ),
			'order'   => array( 'ordered' ),
			'cart'    => array( 'cart_abandoned', 'cart_recovered' ),
		);

		if ( empty( $types ) ) {
			return array_keys( $produces );
		}

		$sources = array();
		foreach ( $produces as $source => $source_types ) {
			if ( ! empty( array_intersect( $types, $source_types ) ) ) {
				$sources[] = $source;
			}
		}

		return $sources;
	}

	/**
	 * Seconds to add to a MySQL-server-clock datetime to express it in site-local time.
	 *
	 * Memoised per request; rounded to 15 minutes because every real timezone offset is a
	 * multiple of 15 and rounding absorbs the sub-second drift between the two readings — the
	 * raw difference is the timezone gap plus a fraction of a second, which occasionally
	 * straddles a second boundary and comes out as +/-1. A same-timezone install rounds to
	 * exactly 0 and this helper becomes a no-op.
	 *
	 * Called lazily from the cart mapper, never from the constructor: a customer with no
	 * carts pays no query at all.
	 *
	 * @return int
	 */
	private function pdwchs_clock_delta() {
		if ( null !== $this->clock_delta ) {
			return $this->clock_delta;
		}

		global $wpdb;
		$mysql_now = $wpdb->get_var( 'SELECT NOW()' );

		if ( empty( $mysql_now ) ) {
			// Unreadable clock: assume the two agree rather than invent a shift.
			$this->clock_delta = 0;
			return $this->clock_delta;
		}

		$diff  = strtotime( current_time( 'mysql' ) ) - strtotime( $mysql_now );
		$delta = (int) ( round( $diff / 900 ) * 900 );

		$this->clock_delta = $delta;

		return $this->clock_delta;
	}

	/**
	 * Fetch one over-sized page of session rows for a customer, newest first.
	 *
	 * No bot predicate: every BOT:: row is written with the sentinel user id, and a profile
	 * page is keyed on a real WP user id, so user_id = %d already excludes them. The mapper
	 * drops BOT:: rows anyway, so a future change to the capture path cannot leak one.
	 *
	 * @param int    $user_id WP user id, already absint()ed.
	 * @param string $before  Validated exclusive upper bound, or '' for the newest page.
	 * @param int    $limit   Row cap (page size + 1).
	 * @return array Row objects with id, url, reg_date, results_count.
	 */
	private function pdwchs_fetch_session_rows( $user_id, $before, $limit ) {
		global $wpdb;

		// del = '0' as a string: every existing query on this table compares it that way.
		$where = $wpdb->prepare( "user_id = %d AND del = '0'", $user_id );
		if ( '' !== $before ) {
			$where .= $wpdb->prepare( ' AND reg_date < %s', $before );
		}

		// id DESC makes the sort a total order — reg_date alone is not, this table records
		// several rows per second on an active visitor, and the page cut depends on it.
		$rows = $wpdb->get_results(
			"SELECT id, url, reg_date, results_count
			 FROM {$wpdb->prefix}pd_woo_customer_history_sessions
			 WHERE {$where}
			 ORDER BY reg_date DESC, id DESC"
			. $wpdb->prepare( ' LIMIT %d', $limit )
		);

		return is_array( $rows ) ? $rows : array();
	}

	/**
	 * Map session rows onto timeline events, dropping the rows that produce none.
	 *
	 * Product titles are primed in one pass so a page of add-to-cart rows costs one query
	 * rather than one per row.
	 *
	 * @param array $rows Row objects from pdwchs_fetch_session_rows().
	 * @return array Event arrays, in the order the rows arrived.
	 */
	private function pdwchs_map_session_rows( $rows ) {
		if ( empty( $rows ) ) {
			return array();
		}

		$product_ids = array();
		foreach ( $rows as $row ) {
			$url = isset( $row->url ) ? (string) $row->url : '';
			if ( 0 === strpos( $url, 'ACTION::add_to_cart::' ) ) {
				$parts = explode( '::', $url );
				if ( isset( $parts[2] ) && absint( $parts[2] ) > 0 ) {
					$product_ids[] = absint( $parts[2] );
				}
			}
		}

		if ( ! empty( $product_ids ) ) {
			// Core, safe to call with ids that no longer exist: a deleted product then falls
			// back to #<id> from cache, still without a query.
			_prime_post_caches( array_unique( $product_ids ), false, false );
		}

		$events = array();
		foreach ( $rows as $row ) {
			$event = $this->pdwchs_map_session_row( $row );
			if ( null !== $event ) {
				$events[] = $event;
			}
		}

		return $events;
	}

	/**
	 * Map one session row onto an event, or null when the row produces none.
	 *
	 * The decode rules are copied from the legacy session-URL formatter in pro/Helpers,
	 * not called: that method returns dashicon markup, which cannot go through R15-11B's
	 * JSON response. label and link are raw here — escaping happens at the point of output.
	 *
	 * @param object $row Row object with id, url, reg_date, results_count.
	 * @return array|null
	 */
	private function pdwchs_map_session_row( $row ) {
		$url = isset( $row->url ) ? (string) $row->url : '';
		if ( '' === $url ) {
			return null;
		}

		// Bot rows never belong in a customer's story, whatever user id they carry.
		if ( 0 === strpos( $url, 'BOT::' ) ) {
			return null;
		}

		$event = array(
			'type'       => '',
			'event_time' => isset( $row->reg_date ) ? (string) $row->reg_date : '',
			'label'      => '',
			'link'       => '',
			'meta'       => array(),
			'source'     => 'session',
			'source_id'  => isset( $row->id ) ? absint( $row->id ) : 0,
		);

		if ( 0 === strpos( $url, 'ACTION::search::' ) ) {
			$parts          = explode( '::', $url );
			$event['type']  = 'searched';
			$event['label'] = isset( $parts[2] ) ? $parts[2] : '';

			// Omitted, never defaulted to 0: the column is NULL for every search row written
			// before 3.1.0, and 0 means "this search found nothing" — a real, different signal.
			if ( isset( $row->results_count ) && null !== $row->results_count ) {
				$event['meta']['results_count'] = (int) $row->results_count;
			}

			return $event;
		}

		if ( 0 === strpos( $url, 'ACTION::add_to_cart::' ) ) {
			$parts      = explode( '::', $url );
			$product_id = isset( $parts[2] ) ? absint( $parts[2] ) : 0;
			$qty        = isset( $parts[3] ) ? (int) $parts[3] : 0;
			$title      = $product_id > 0 ? (string) get_the_title( $product_id ) : '';

			$event['type']  = 'added_to_cart';
			$event['label'] = ( '' !== $title ) ? $title : '#' . $product_id;
			$event['link']  = admin_url( 'post.php?post=' . $product_id . '&action=edit' );
			$event['meta']  = array(
				'product_id' => $product_id,
				'qty'        => $qty,
			);

			return $event;
		}

		// ACTION::new_order:: rows produce no event — the order itself is the event, and
		// R15-10B sources it from WooCommerce where the total, the status and the edit link
		// live. Emitting both would show every order twice. Any other ACTION:: verb is
		// unknown to this mapper and is omitted rather than rendered as a placeholder.
		if ( 0 === strpos( $url, 'ACTION::' ) ) {
			return null;
		}

		// The stored path verbatim: resolving it to a post title would cost a query per row
		// on the most common event type, and the path is what the Sessions page has always shown.
		$event['type']  = 'viewed';
		$event['label'] = $url;
		$event['link']  = home_url( '/' . $url );

		return $event;
	}

	/**
	 * Fetch one over-sized page of the customer's orders and map them to events.
	 *
	 * CRUD, not SQL. Feature 15.5's cohort engine banned wc_get_orders() because it reads every
	 * order the store has ever taken; neither condition holds here — the query is scoped to one
	 * customer_id and bounded to page_size + 1 rows, and the hydrated object is needed anyway for
	 * get_order_number(), get_total() and get_edit_order_url(). Using CRUD means no SQL, no HPOS
	 * branch and no legacy dialect anywhere in this feature.
	 *
	 * No status filter: unlike every revenue class in the plugin, a cancelled or failed order is
	 * part of the customer's story, and the entry carries its status so the reader can see which.
	 *
	 * @param int    $user_id WP user id, already absint()ed.
	 * @param string $before  Validated exclusive upper bound, or '' for the newest page.
	 * @param int    $limit   Row cap (page size + 1).
	 * @return array Event arrays, newest first.
	 */
	private function pdwchs_fetch_order_events( $user_id, $before, $limit ) {
		if ( ! function_exists( 'wc_get_orders' ) ) {
			return array();
		}

		$query_args = array(
			'customer_id' => $user_id,
			'limit'       => $limit,
			'orderby'     => 'date',
			'order'       => 'DESC',
			'type'        => 'shop_order',
		);

		if ( '' !== $before ) {
			// A Unix int, not the raw string: a bare 'Y-m-d H:i:s' is read at day granularity
			// by some date-query paths, which would silently drop the rest of that day.
			$query_args['date_created'] = '<' . (int) strtotime( $before );
		}

		$orders = wc_get_orders( $query_args );
		if ( ! is_array( $orders ) ) {
			return array();
		}

		$events = array();
		foreach ( $orders as $order ) {
			$created = $order->get_date_created();
			if ( null === $created ) {
				continue;
			}

			$events[] = array(
				'type'       => 'ordered',
				'event_time' => $created->date( 'Y-m-d H:i:s' ),
				'label'      => (string) $order->get_order_number(),

				// Never a hand-built post.php?post= link: that breaks on HPOS stores, where
				// orders live at admin.php?page=wc-orders. CRUD returns the right URL for both.
				'link'       => $order->get_edit_order_url(),
				'meta'       => array(
					'order_id' => $order->get_id(),
					'status'   => $order->get_status(),
					'total'    => (float) $order->get_total(),
					'currency' => $order->get_currency(),
				),
				'source'     => 'order',
				'source_id'  => absint( $order->get_id() ),
			);
		}

		return $events;
	}

	/**
	 * Fetch one over-sized page of the customer's cart milestones and map them to events.
	 *
	 * One row can yield two events — a cart abandoned and later recovered — so the LIMIT counts
	 * rows, not events, and a short page is expected and safe: pdwchs_cut_page() cuts on a
	 * timestamp boundary, so a short source only means an earlier next_cursor, never a lost event.
	 *
	 * A cart that turned into an order emits nothing here: the order source already has it, and
	 * two entries seconds apart would say the same thing twice.
	 *
	 * @param int    $user_id WP user id, already absint()ed.
	 * @param string $before  Validated exclusive upper bound, or '' for the newest page.
	 * @param int    $limit   Row cap (page size + 1).
	 * @return array Event arrays, in row order.
	 */
	private function pdwchs_fetch_cart_events( $user_id, $before, $limit ) {
		global $wpdb;

		// The table exists on any licensed install, but a site whose Pro tables have not been
		// healed yet must get a session + order stream rather than a fatal.
		if ( ! $this->pdwchs_carts_table_exists() ) {
			return array();
		}

		// cart_contents is deliberately absent: it is a LONGTEXT JSON blob and the timeline
		// shows an item count, not a line-item list.
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, status, cart_total, currency, items_count, abandoned_at, recovered_at
				 FROM {$wpdb->prefix}pdwchs_carts
				 WHERE user_id = %d
				   AND ( abandoned_at IS NOT NULL OR recovered_at IS NOT NULL )
				 ORDER BY GREATEST( COALESCE( abandoned_at, '0000-00-00' ), COALESCE( recovered_at, '0000-00-00' ) ) DESC
				 LIMIT %d",
				$user_id,
				$limit
			)
		);

		if ( ! is_array( $rows ) ) {
			return array();
		}

		$link   = admin_url( 'admin.php?page=pd-woo-abandoned-carts' );
		$events = array();

		foreach ( $rows as $row ) {
			$cart_id     = isset( $row->id ) ? absint( $row->id ) : 0;
			$items_count = isset( $row->items_count ) ? (int) $row->items_count : 0;
			$meta        = array(
				'cart_id'     => $cart_id,
				'cart_total'  => isset( $row->cart_total ) ? (float) $row->cart_total : 0.0,
				'items_count' => $items_count,
				'currency'    => isset( $row->currency ) ? (string) $row->currency : '',
			);

			// abandoned_at is written by the cron with MySQL's NOW(), on the database server's
			// clock — the only column here that needs shifting onto the site clock. Parsed and
			// re-rendered in UTC so the shift is pure wall-clock arithmetic: the stored value
			// carries no zone, and anchoring both ends to UTC keeps the round trip exact
			// whatever the process timezone happens to be.
			$abandoned = ! empty( $row->abandoned_at ) ? strtotime( (string) $row->abandoned_at . ' UTC' ) : false;
			if ( false !== $abandoned ) {
				$events[] = array(
					'type'       => 'cart_abandoned',
					'event_time' => gmdate( 'Y-m-d H:i:s', $abandoned + $this->pdwchs_clock_delta() ),
					/* translators: %d: number of items in the cart. */
					'label'      => sprintf( _n( 'Cart abandoned — %d item', 'Cart abandoned — %d items', $items_count, 'pd-customer-history' ), $items_count ),
					'link'       => $link,
					'meta'       => $meta,
					'source'     => 'cart',
					'source_id'  => $cart_id,
				);
			}

			// recovered_at is written with current_time( 'mysql' ) and is already site-local.
			// Shifting it too would put recoveries hours off in the opposite direction.
			if ( ! empty( $row->recovered_at ) ) {
				$events[] = array(
					'type'       => 'cart_recovered',
					'event_time' => (string) $row->recovered_at,
					/* translators: %d: number of items in the cart. */
					'label'      => sprintf( _n( 'Cart recovered — %d item', 'Cart recovered — %d items', $items_count, 'pd-customer-history' ), $items_count ),
					'link'       => $link,
					'meta'       => $meta,
					'source'     => 'cart',
					'source_id'  => $cart_id,
				);
			}
		}

		// The cursor is applied here, not in the WHERE: "either timestamp below the cursor"
		// cannot be expressed without an OR that defeats idx_user_id or a UNION, and a customer
		// has a handful of carts, not thousands, so the extra rows cost nothing.
		if ( '' !== $before ) {
			$events = array_values(
				array_filter(
					$events,
					function ( $event ) use ( $before ) {
						return $event['event_time'] < $before;
					}
				)
			);
		}

		return $events;
	}

	/**
	 * Whether the Pro carts table is present.
	 *
	 * Same SHOW TABLES LIKE shape as PDWCHS_Sessions_Data::table_exists().
	 *
	 * @return bool
	 */
	private function pdwchs_carts_table_exists() {
		global $wpdb;

		$table = $wpdb->prefix . 'pdwchs_carts';

		return (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
	}

	/**
	 * Newest first. Ties broken by type weight, then by source id descending.
	 *
	 * The order has to be total, or paging breaks: usort is not stable before PHP 8.0 and this
	 * plugin supports 7.4, so two events that compare equal can swap places between the call
	 * that produced page 1 and the call that produces page 2 — and an event then appears twice
	 * or never. TYPE_ORDER separates the sources and source_id is unique within one, so no two
	 * distinct events ever compare equal.
	 *
	 * strcmp on the fixed-width, zero-padded 'Y-m-d H:i:s' string, not strtotime: lexical order
	 * is chronological order here, converting on every comparison is pure overhead, and
	 * strtotime returns false on a malformed value, which sorts as 0 and teleports the event
	 * to 1970.
	 *
	 * @param array $a Event array.
	 * @param array $b Event array.
	 * @return int
	 */
	private function pdwchs_compare_events( $a, $b ) {
		if ( $a['event_time'] !== $b['event_time'] ) {
			return strcmp( $b['event_time'], $a['event_time'] );
		}

		$wa = isset( self::TYPE_ORDER[ $a['type'] ] ) ? self::TYPE_ORDER[ $a['type'] ] : 99;
		$wb = isset( self::TYPE_ORDER[ $b['type'] ] ) ? self::TYPE_ORDER[ $b['type'] ] : 99;
		if ( $wa !== $wb ) {
			return $wa - $wb;
		}

		return (int) $b['source_id'] - (int) $a['source_id'];
	}

	/**
	 * Trim an over-fetched, sorted event list to a page that ends on a clean timestamp boundary.
	 *
	 * The cursor is a timestamp and the next page asks for reg_date < cursor, so a page that
	 * ended mid-second would lose every remaining event in that second permanently — the next
	 * page starts strictly below it. The page therefore ends on a whole-second boundary: every
	 * fetched item down to and including the boundary second is kept, which is why a page may
	 * exceed the requested size. The + 1 row is what identifies the boundary second at all.
	 *
	 * A compound (reg_date, id) cursor would also solve it here and will not work in R15-10B —
	 * an order id and a session row id are not comparable — so it is solved the way that also
	 * works there.
	 *
	 * @param array $events    Sorted newest-first; may contain up to page_size + 1 items per source.
	 * @param int   $page_size Requested page size.
	 * @return array { events, next_cursor, has_more }
	 */
	private function pdwchs_cut_page( $events, $page_size ) {
		if ( count( $events ) <= $page_size ) {
			return array(
				'events'      => $events,
				'next_cursor' => null,
				'has_more'    => false,
			);
		}

		// The timestamp of the first item that would be cut. Because the list is sorted
		// newest-first, every item sharing it is contiguous and starts at or before this point.
		$boundary = $events[ $page_size ]['event_time'];

		// Keep everything at or above the boundary second, drop what is strictly below it.
		// Indices 0..$page_size are all >= $boundary, so the page is never empty here — it
		// can never return zero events with has_more true and spin "Load older" forever.
		$page = array();
		foreach ( $events as $event ) {
			if ( $event['event_time'] < $boundary ) {
				break;
			}
			$page[] = $event;
		}

		return array(
			'events'      => $page,
			'next_cursor' => $boundary,
			'has_more'    => true,
		);
	}
}

// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
// phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
