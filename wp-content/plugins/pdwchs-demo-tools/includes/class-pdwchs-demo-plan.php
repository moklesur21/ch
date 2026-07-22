<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pure, deterministic blueprint of the demo dataset.
 *
 * Everything random is decided here under a fixed mt_srand seed, relative to
 * an anchor date — so the same (anchor, rng_seed) always yields the same plan,
 * and a background runner can rebuild the plan on every tick and execute any
 * step idempotently. No DB reads or writes happen in this class, with one
 * documented exception: the three founder-pick overrides read via get_option.
 *
 * The archetype census is reverse-engineered from the product scoring code
 * (pro/Intelligence/{Churn,Health,Lifecycle,RFM}.php) so the computed state
 * lands inside the acceptance bands of
 * docs/demo/PureDevs_V1_Demo_Recording_Guide.md Phase 1E:
 * Churn At-Risk list = exactly 7 rows (2 critical, 2 high incl. hero,
 * 3 medium), hero at ~64/High with last order exactly 51 days ago.
 */
class PDWCHS_Demo_Plan {

	const DEFAULT_RNG_SEED = 20260722;

	private $anchor;   // Y-m-d, site-local "day zero"
	private $rng_seed;
	private $plan = null;

	public function __construct( $anchor = null, $rng_seed = null ) {
		$this->anchor   = $anchor ? gmdate( 'Y-m-d', strtotime( $anchor ) ) : gmdate( 'Y-m-d', current_time( 'timestamp' ) );
		$this->rng_seed = $rng_seed ? absint( $rng_seed ) : self::DEFAULT_RNG_SEED;
	}

	public function anchor() {
		return $this->anchor;
	}

	public function rng_seed() {
		return $this->rng_seed;
	}

	/** Site-local timestamp for "anchor minus N days at HH:MM". */
	public function local_ts( $days_ago, $hh = 12, $mm = 0 ) {
		return strtotime( $this->anchor . ' 00:00:00 UTC' ) - ( $days_ago * DAY_IN_SECONDS ) + ( $hh * HOUR_IN_SECONDS ) + ( $mm * MINUTE_IN_SECONDS );
	}

	/** Site-local datetime string (matches how the plugin writes reg_date). */
	public function local_datetime( $days_ago, $hh = 12, $mm = 0 ) {
		return gmdate( 'Y-m-d H:i:s', $this->local_ts( $days_ago, $hh, $mm ) );
	}

	/** GMT timestamp for WC order dates (local minus the site gmt_offset). */
	public function gmt_ts( $days_ago, $hh = 12, $mm = 0 ) {
		return $this->local_ts( $days_ago, $hh, $mm ) - (int) round( (float) get_option( 'gmt_offset' ) * HOUR_IN_SECONDS );
	}

	public function build() {
		if ( null !== $this->plan ) {
			return $this->plan;
		}

		mt_srand( $this->rng_seed );

		$names = require PDWCHS_DEMO_TOOLS_DATA_DIR . 'names.php';
		$this->name_pool = array();
		foreach ( $names['first'] as $i => $first ) {
			$this->name_pool[] = array( $first, $names['last'][ $i % count( $names['last'] ) ] );
		}

		$customers = array();
		$customers = array_merge(
			$customers,
			$this->build_champions(),
			$this->build_hero(),
			$this->build_loyal(),
			$this->build_potentials(),
			$this->build_actives(),
			$this->build_new(),
			$this->build_needs_attention(),
			$this->build_at_risk_cast(),
			$this->build_cannot_lose(),
			$this->build_hibernating(),
			$this->build_churned_single(),
			$this->build_prospect()
		);

		$guest_orders = $this->build_guest_orders();

		// Gap repair: guarantee no >2-day order gap in the last 60 days by
		// appending filler orders to recent-heavy customers (fillers only ever
		// ADD recent orders, which keeps those customers' churn decline <= 0).
		$this->fill_order_gaps( $customers, $guest_orders );

		$plan = array(
			'anchor'       => $this->anchor,
			'rng_seed'     => $this->rng_seed,
			'store'        => array(
				'blogname'   => get_option( 'pdwchs_demo_store_name' ) ?: 'Northline Supply Co.',
				'admin_name' => get_option( 'pdwchs_demo_admin_name' ) ?: 'Sarah M.',
				'currency'   => 'USD',
			),
			'customers'    => $customers,
			'guest_orders' => $guest_orders,
			'carts'        => $this->build_carts( $customers ),
			'hero_key'     => 'hero',
			'decliners'    => array( 'hero', 'ar_a', 'loyal_1' ), // Declining-Health widget cast (2-4 incl hero)
		);

		$plan['expectations'] = $this->build_expectations( $plan );

		$this->plan = $plan;
		return $plan;
	}

	// ── archetype builders ──────────────────────────────────────────────

	private $name_pool = array();
	private $name_cursor = 0;

	private function next_name() {
		$n = $this->name_pool[ $this->name_cursor % count( $this->name_pool ) ];
		$this->name_cursor++;
		return $n;
	}

	private function rnd( $min, $max ) {
		return mt_rand( $min, $max );
	}

	private function customer( $key, $archetype, $name, $orders, $visits, $expect, $extra = array() ) {
		$slug = strtolower( $name[0] . '.' . $name[1] );
		$slug = preg_replace( '/[^a-z0-9.]/', '', $slug );
		$oldest = 0;
		foreach ( $orders as $o ) {
			$oldest = max( $oldest, $o['days'] );
		}
		return array_merge( array(
			'key'        => $key,
			'archetype'  => $archetype,
			'first'      => $name[0],
			'last'       => $name[1],
			'email'      => $slug . '@example.com',
			'login'      => str_replace( '.', '_', $slug ),
			'registered' => $oldest + $this->rnd( 1, 5 ),
			'ip'         => sprintf( '%d.%d.%d.%d', 45 + ( $this->name_cursor % 150 ), $this->rnd( 10, 240 ), $this->rnd( 10, 240 ), $this->rnd( 2, 250 ) ),
			'mobile'     => false,
			'orders'     => $orders,
			'visits'     => $visits,
			'pref'       => array( $this->rnd( 0, 5 ), $this->rnd( 6, 11 ), $this->rnd( 12, 16 ) ),
			'expect'     => $expect,
		), $extra );
	}

	private function order( $days, $total, $status = 'completed', $hh = null, $mm = null ) {
		return array(
			'days'   => $days,
			'hh'     => null === $hh ? $this->rnd( 8, 20 ) : $hh,
			'mm'     => null === $mm ? $this->rnd( 0, 59 ) : $mm,
			'total'  => round( $total, 2 ),
			'status' => $status,
			'filler' => false,
		);
	}

	private function visit( $days, $kind = 'browse', $order_idx = null, $opts = array() ) {
		return array_merge( array(
			'days'      => $days,
			'hh'        => $this->rnd( 8, 22 ),
			'mm'        => $this->rnd( 0, 59 ),
			'kind'      => $kind,       // browse | order
			'order_idx' => $order_idx,  // index into the customer's orders for order trails
			'pages'     => $this->rnd( 2, 5 ),
			'search'    => ( 1 === $this->rnd( 1, 3 ) ),
		), $opts );
	}

	/** Order-trail visits for every order newer than $max_days. */
	private function order_visits( $orders, $max_days = 120 ) {
		$visits = array();
		foreach ( $orders as $idx => $o ) {
			if ( $o['days'] <= $max_days && 'completed' === $o['status'] ) {
				$visits[] = $this->visit( $o['days'], 'order', $idx, array( 'hh' => $o['hh'], 'mm' => max( 0, $o['mm'] - 9 ) ) );
			}
		}
		return $visits;
	}

	/** N browse visits in a days-ago window, recent-heavy. */
	private function browse_visits( $count, $min_days, $max_days ) {
		$visits = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$visits[] = $this->visit( $this->rnd( $min_days, $max_days ) );
		}
		return $visits;
	}

	private function build_champions() {
		$out   = array();
		$lasts = array( 5, 18, 25 );
		for ( $i = 0; $i < 3; $i++ ) {
			$orders = array();
			$day    = $lasts[ $i ];
			for ( $k = 0; $k < 11; $k++ ) {
				// Recent baskets slightly richer than old ones => spending trend up.
				$total    = ( $k < 3 ) ? $this->rnd( 112, 125 ) : $this->rnd( 92, 108 );
				$orders[] = $this->order( $day, $total );
				$day     += $this->rnd( 26, 32 );
			}
			$visits = array_merge(
				$this->order_visits( $orders ),
				$this->browse_visits( 4, 1, 28 ),
				$this->browse_visits( 2, 32, 58 )
			);
			$out[] = $this->customer( 'champ_' . $i, 'champion', $this->next_name(), $orders, $visits, array(
				'segment' => 'champions', 'lifecycle' => 'Loyal Customer', 'churn_band' => 'low',
			) );
		}
		return $out;
	}

	private function build_hero() {
		$hero_name = get_option( 'pdwchs_demo_hero_name' ) ?: 'Daniel Mercer';
		$parts     = preg_split( '/\s+/', trim( $hero_name ), 2 );
		$name      = array( $parts[0], isset( $parts[1] ) ? $parts[1] : 'Mercer' );

		// Load-bearing timeline: 11 steady orders then a stretch, last one
		// exactly 51 days ago at 01:15 site time. Computes to churn ~64 (High),
		// days_overdue ~19, RFM champions, lifecycle Loyal, health ~60.
		$days   = array( 372, 350, 321, 292, 263, 234, 205, 176, 149, 122, 95, 51 );
		$orders = array();
		foreach ( $days as $idx => $d ) {
			$is_last  = ( 51 === $d );
			$total    = $is_last ? 64 : $this->rnd( 74, 86 );
			$orders[] = $this->order( $d, $total, 'completed', $is_last ? 1 : null, $is_last ? 15 : null );
		}
		// Sessions: order trails for the last 4 orders, plus browse at 55/46/42
		// days ago — 4 visits inside the 30-60d window, silence since. That is
		// the churn-engagement "gone quiet" signal (decline 100% + no recent).
		$visits = array_merge(
			$this->order_visits( $orders, 150 ),
			array( $this->visit( 55 ), $this->visit( 46 ), $this->visit( 42 ) )
		);
		return array( $this->customer( 'hero', 'hero', $name, $orders, $visits, array(
			'segment' => 'champions', 'lifecycle' => 'Loyal Customer', 'churn_band' => 'high',
			'churn_est' => 64, 'days_overdue_est' => 19,
		) ) );
	}

	private function build_loyal() {
		$out = array();
		foreach ( array( 38, 47 ) as $i => $last ) {
			$orders = array();
			$day    = $last;
			for ( $k = 0; $k < 10; $k++ ) {
				$orders[] = $this->order( $day, $this->rnd( 54, 66 ) );
				$day     += 22; // tight 22d cadence keeps the 0-90 vs 90-180 windows near-balanced (3v4 => Low churn)
			}
			$visits = array_merge(
				$this->order_visits( $orders ),
				$this->browse_visits( 3, 2, 28 ),
				$this->browse_visits( 2, 33, 58 )
			);
			$out[] = $this->customer( 'loyal_' . $i, 'loyal', $this->next_name(), $orders, $visits, array(
				'segment' => 'loyal', 'lifecycle' => 'Loyal Customer', 'churn_band' => 'low',
			) );
		}
		return $out;
	}

	private function build_potentials() {
		$out = array();
		for ( $i = 0; $i < 17; $i++ ) {
			$n      = 4 + ( $i % 3 );            // 4, 5 or 6 orders
			$last   = 2 + ( ( $i * 3 ) % 27 );   // lasts spread across 2-28d (fills the recent chart)
			$orders = array();
			$day    = $last;
			for ( $k = 0; $k < $n; $k++ ) {
				// Recent baskets a touch richer => flat-to-up spending trend.
				$total    = ( 0 === $k ) ? $this->rnd( 68, 84 ) : $this->rnd( 58, 76 );
				$orders[] = $this->order( $day, $total );
				$day     += $this->rnd( 28, 42 );
			}
			$recent = 2 + ( $i % 3 );
			$visits = array_merge(
				$this->order_visits( $orders ),
				$this->browse_visits( $recent + 1, 1, 28 ),
				$this->browse_visits( max( 0, $recent - 1 ), 32, 58 )
			);
			$out[] = $this->customer( 'pot_' . $i, 'potential', $this->next_name(), $orders, $visits, array(
				'segment' => 'potential_loyalist', 'lifecycle' => 'Loyal Customer', 'churn_band' => 'low',
			), array( 'mobile' => ( $i < 3 ) ) ); // 3 designated mobile shoppers
		}
		return $out;
	}

	private function build_actives() {
		$out = array();
		for ( $i = 0; $i < 10; $i++ ) {
			$last   = 4 + ( ( $i * 3 ) % 26 );
			$first  = $last + $this->rnd( 30, 52 );
			$orders = array(
				$this->order( $last, $this->rnd( 55, 75 ) ),
				$this->order( $first, $this->rnd( 52, 70 ) ),
			);
			$visits = array_merge(
				$this->order_visits( $orders ),
				$this->browse_visits( 2, 1, 26 ),
				$this->browse_visits( 1, 33, 55 )
			);
			$out[] = $this->customer( 'act_' . $i, 'active', $this->next_name(), $orders, $visits, array(
				'segment' => 'potential_loyalist', 'lifecycle' => 'Active Customer', 'churn_band' => 'low',
			) );
		}
		return $out;
	}

	private function build_new() {
		$out = array();
		for ( $i = 0; $i < 5; $i++ ) {
			$last   = 3 + ( $i * 5 );
			$orders = array( $this->order( $last, $this->rnd( 50, 90 ) ) );
			$visits = array_merge( $this->order_visits( $orders ), $this->browse_visits( 1, 1, $last ) );
			$out[]  = $this->customer( 'new_' . $i, 'new', $this->next_name(), $orders, $visits, array(
				'segment' => 'new_customer', 'lifecycle' => 'New Customer', 'churn_band' => null,
			) );
		}
		return $out;
	}

	private function build_needs_attention() {
		$out = array();

		// Two balanced-window Lows: 2 orders in 0-90 and 2 in 90-180 => decline 0.
		foreach ( array( 0, 1 ) as $i ) {
			$last   = 63 + ( $i * 9 );
			$orders = array(
				$this->order( $last, $this->rnd( 62, 78 ) ),
				$this->order( $last + 23, $this->rnd( 62, 78 ) ),
				$this->order( $last + 49, $this->rnd( 62, 78 ) ),
				$this->order( $last + 85, $this->rnd( 62, 78 ) ),
			);
			$out[] = $this->customer( 'na_' . $i, 'needs_attention', $this->next_name(), $orders, array(), array(
				'segment' => 'needs_attention', 'lifecycle' => 'Loyal Customer', 'churn_band' => 'low',
			) );
		}

		// One engineered Medium (~58): 1 recent-window order vs 3 previous.
		$orders = array(
			$this->order( 70, 68 ),
			$this->order( 105, 72 ),
			$this->order( 133, 70 ),
			$this->order( 161, 74 ),
		);
		$out[] = $this->customer( 'na_med', 'needs_attention', $this->next_name(), $orders, array(), array(
			'segment' => 'needs_attention', 'lifecycle' => 'Loyal Customer', 'churn_band' => 'medium', 'churn_est' => 58,
		) );

		return $out;
	}

	private function build_at_risk_cast() {
		$out = array();

		// Critical A — guide 0.7 keeps the two existing at-risk names, fixed up.
		$orders = array();
		foreach ( array( 130, 160, 190, 220, 250, 280 ) as $d ) {
			$orders[] = $this->order( $d, $this->rnd( 64, 76 ) );
		}
		$out[] = $this->customer( 'ar_a', 'at_risk', array( 'Everett', 'Cole' ), $orders,
			array( $this->visit( 38 ), $this->visit( 44 ), $this->visit( 52 ) ),
			array( 'segment' => 'at_risk', 'lifecycle' => 'At Risk', 'churn_band' => 'critical', 'churn_est' => 94 )
		);

		// Critical B — zero sessions ever (engagement contributes nothing).
		$orders = array();
		foreach ( array( 152, 178, 208, 238, 268, 298 ) as $d ) {
			$orders[] = $this->order( $d, $this->rnd( 60, 72 ) );
		}
		$out[] = $this->customer( 'ar_b', 'at_risk', array( 'John', 'Walton' ), $orders, array(),
			array( 'segment' => 'at_risk', 'lifecycle' => 'At Risk', 'churn_band' => 'critical', 'churn_est' => 85 )
		);

		// Three single-order lapsed customers: lifecycle At Risk (no churn row,
		// min_orders_for_prediction=2). Stage-date backfill puts them at 33/55/79
		// days-in-stage => the "At Risk (30+ Days)" widget spans medium/high/critical.
		foreach ( array( 'ar_c' => 124, 'ar_d' => 146, 'ar_e' => 170 ) as $key => $d ) {
			$orders = array( $this->order( $d, $this->rnd( 62, 86 ) ) );
			$out[]  = $this->customer( $key, 'at_risk_single', $this->next_name(), $orders,
				array( $this->visit( $d - $this->rnd( 1, 4 ), 'order', 0 ) ),
				array( 'segment' => 'hibernating', 'lifecycle' => 'At Risk', 'churn_band' => null )
			);
		}

		return $out;
	}

	private function build_cannot_lose() {
		$orders = array();
		$day    = 101;
		for ( $k = 0; $k < 10; $k++ ) {
			$orders[] = $this->order( $day, 89 );
			$day     += 29;
		}
		return array( $this->customer( 'cl', 'cannot_lose', $this->next_name(), $orders, array(), array(
			'segment' => 'cannot_lose', 'lifecycle' => 'At Risk', 'churn_band' => 'high', 'churn_est' => 78,
		) ) );
	}

	private function build_hibernating() {
		$a = $this->customer( 'hib_a', 'hibernating', $this->next_name(), array(
			$this->order( 205, $this->rnd( 40, 58 ) ),
			$this->order( 265, $this->rnd( 40, 58 ) ),
		), array(), array(
			'segment' => 'hibernating', 'lifecycle' => 'Churned', 'churn_band' => 'medium', 'churn_est' => 40,
		) );

		// Twin differs by a couple of stale sessions => engagement +20 => ~43,
		// so the two hibernating Mediums don't display identical scores.
		$b = $this->customer( 'hib_b', 'hibernating', $this->next_name(), array(
			$this->order( 248, $this->rnd( 40, 58 ) ),
			$this->order( 333, $this->rnd( 40, 58 ) ),
		), array( $this->visit( 72 ), $this->visit( 78 ) ), array(
			'segment' => 'hibernating', 'lifecycle' => 'Churned', 'churn_band' => 'medium', 'churn_est' => 43,
		) );

		return array( $a, $b );
	}

	private function build_churned_single() {
		return array( $this->customer( 'churned_0', 'churned', $this->next_name(), array(
			$this->order( 290, $this->rnd( 45, 70 ) ),
		), array(), array(
			'segment' => 'lost', 'lifecycle' => 'Churned', 'churn_band' => null,
		) ) );
	}

	private function build_prospect() {
		return array( $this->customer( 'prospect_0', 'prospect', $this->next_name(), array(),
			$this->browse_visits( 2, 2, 20 ),
			array( 'segment' => 'hibernating', 'lifecycle' => 'Prospect', 'churn_band' => null )
		) );
	}

	private function build_guest_orders() {
		$out   = array();
		$specs = array(
			array( 0, 'processing' ), array( 1, 'processing' ), array( 9, 'completed' ),
			array( 33, 'completed' ), array( 70, 'completed' ), array( 130, 'completed' ),
			array( 200, 'completed' ), array( 310, 'completed' ),
		);
		foreach ( $specs as $i => $s ) {
			$name  = $this->next_name();
			$out[] = array(
				'days'   => $s[0],
				'hh'     => $this->rnd( 9, 20 ),
				'mm'     => $this->rnd( 0, 59 ),
				'total'  => $this->rnd( 48, 120 ),
				'status' => $s[1],
				'first'  => $name[0],
				'last'   => $name[1],
				'email'  => strtolower( $name[0] . '.' . $name[1] ) . '@example.com',
				'visits' => 1, // order-day guest trail
			);
		}
		// Browse-only guest traffic: keeps the conversion funnel honest.
		$out['browse'] = array( 'recent_visits' => 70, 'older_visits' => 30, 'atc_no_buy' => 10, 'checkout_no_buy' => 6 );
		return $out;
	}

	private function build_carts( $customers ) {
		$carts = array();
		$user_keys = array();
		foreach ( $customers as $c ) {
			if ( in_array( $c['archetype'], array( 'potential', 'active', 'new', 'needs_attention' ), true ) ) {
				$user_keys[] = $c['key'];
			}
		}

		// 18 abandoned (10 user-linked, 8 guest).
		for ( $i = 0; $i < 18; $i++ ) {
			$is_user = ( $i < 10 );
			$carts[] = array(
				'owner'     => $is_user ? $user_keys[ $i % count( $user_keys ) ] : null,
				'status'    => 'abandoned',
				'days'      => $this->rnd( 2, 55 ),
				'hh'        => $this->rnd( 8, 22 ),
				'total'     => $this->rnd( 45, 260 ),
				'items'     => $this->rnd( 1, 4 ),
				'abandon_h' => $this->rnd( 2, 30 ),
				'order_ref' => null,
			);
		}
		// 6 recovered — linked to a real order of the owning customer.
		for ( $i = 0; $i < 6; $i++ ) {
			$key = $user_keys[ ( $i * 2 + 1 ) % count( $user_keys ) ];
			$carts[] = array(
				'owner'     => $key,
				'status'    => 'recovered',
				'days'      => null, // derived from the linked order at seed time (order date + 1-3 days back)
				'hh'        => $this->rnd( 8, 22 ),
				'total'     => null, // derived from the linked order total
				'items'     => $this->rnd( 1, 3 ),
				'abandon_h' => $this->rnd( 3, 20 ),
				'order_ref' => 0,    // customer's most recent order
			);
		}
		// 8 completed — same-day as a real order.
		for ( $i = 0; $i < 8; $i++ ) {
			$key = $user_keys[ ( $i * 3 + 2 ) % count( $user_keys ) ];
			$carts[] = array(
				'owner'     => $key,
				'status'    => 'completed',
				'days'      => null,
				'hh'        => $this->rnd( 8, 22 ),
				'total'     => null,
				'items'     => $this->rnd( 1, 3 ),
				'abandon_h' => 0,
				'order_ref' => 0,
			);
		}
		// 3 active right now (the product's 15-min cron may later flip them — realistic).
		for ( $i = 0; $i < 3; $i++ ) {
			$carts[] = array(
				'owner'     => ( 0 === $i ) ? null : $user_keys[ ( $i * 5 ) % count( $user_keys ) ],
				'status'    => 'active',
				'days'      => 0,
				'hh'        => null, // "now" at seed time
				'total'     => $this->rnd( 40, 180 ),
				'items'     => $this->rnd( 1, 3 ),
				'abandon_h' => 0,
				'order_ref' => null,
			);
		}
		return $carts;
	}

	/** Append filler orders so no day-gap >2 exists in the last 60 days. */
	private function fill_order_gaps( &$customers, $guest_orders ) {
		$covered = array_fill( 0, 61, 0 );
		foreach ( $customers as $c ) {
			foreach ( $c['orders'] as $o ) {
				if ( $o['days'] <= 60 ) {
					$covered[ (int) $o['days'] ]++;
				}
			}
		}
		foreach ( $guest_orders as $k => $g ) {
			if ( 'browse' !== $k && $g['days'] <= 60 ) {
				$covered[ (int) $g['days'] ]++;
			}
		}

		// Round-robin fillers over the recent-heavy pool (potentials/actives):
		// an extra recent order only makes their decline more negative => still Low.
		$pool = array();
		foreach ( $customers as $idx => $c ) {
			if ( in_array( $c['archetype'], array( 'potential', 'active' ), true ) ) {
				$pool[] = $idx;
			}
		}
		$p = 0;
		$last_covered = 0;
		for ( $d = 0; $d <= 60; $d++ ) {
			if ( $covered[ $d ] > 0 ) {
				$last_covered = $d;
				continue;
			}
			if ( ( $d - $last_covered ) >= 2 ) { // about to open a 3-day hole — plug it
				$idx = $pool[ $p % count( $pool ) ];
				$p++;
				$filler           = $this->order( $d, $this->rnd( 55, 85 ) );
				$filler['filler'] = true;
				$customers[ $idx ]['orders'][] = $filler;
				$customers[ $idx ]['visits'][] = $this->visit( $d, 'order', count( $customers[ $idx ]['orders'] ) - 1 );
				$covered[ $d ]++;
				$last_covered = $d;
			}
		}
	}

	// ── expectations / analytic pre-check ───────────────────────────────

	private function build_expectations( $plan ) {
		$segments  = array();
		$stages    = array();
		$risk      = array( 'low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0 );
		$orders    = count( $plan['guest_orders'] ) - 1; // minus the 'browse' meta row
		$customers = 0;

		foreach ( $plan['customers'] as $c ) {
			$customers++;
			$orders += count( $c['orders'] );
			$segments[ $c['expect']['segment'] ]   = 1 + ( $segments[ $c['expect']['segment'] ] ?? 0 );
			$stages[ $c['expect']['lifecycle'] ]   = 1 + ( $stages[ $c['expect']['lifecycle'] ] ?? 0 );
			if ( ! empty( $c['expect']['churn_band'] ) ) {
				$risk[ $c['expect']['churn_band'] ]++;
			}
		}

		return array(
			'customers'      => $customers,
			'orders'         => $orders,
			'segments'       => $segments,
			'stages'         => $stages,
			'risk'           => $risk,
			'at_risk_rows'   => $risk['medium'] + $risk['high'] + $risk['critical'],
			'alerts'         => $risk['high'] + $risk['critical'],
			'hero'           => array(
				'days_since_last_order' => 51,
				'churn_score_est'       => 64,
				'risk_level'            => 'high',
				'days_overdue_est'      => 19,
				'orders'                => 12,
				'lifetime_est'          => 64 + 11 * 80,
			),
		);
	}

	/**
	 * Analytic hero pre-check: replicates the churn formulas from
	 * pro/Intelligence/Churn.php against the planned hero timeline so
	 * `wp pdwchs-demo plan` can prove the math before anything is written.
	 */
	public function hero_precheck() {
		$plan = $this->build();
		$hero = null;
		foreach ( $plan['customers'] as $c ) {
			if ( 'hero' === $c['key'] ) {
				$hero = $c;
				break;
			}
		}

		$days = array();
		foreach ( $hero['orders'] as $o ) {
			$days[] = $o['days'];
		}
		sort( $days );
		$last     = $days[0];
		$windowed = array_values( array_filter( $days, function ( $d ) { return $d <= 365; } ) );
		$span     = max( $days ) - min( $days );
		$freq     = round( $span / ( count( $windowed ) - 1 ), 1 );
		$overdue  = (int) round( $last - round( $freq ) );

		// Recency (51d, good band) with overdue multiplier.
		$recency = ( ( $last - 30 ) / 30 ) * 30;
		$recency = min( 100, round( $recency * ( 1 + ( min( 60, $overdue ) / 60 ) * 0.3 ) ) );

		// Frequency / monetary: recent 0-90 vs previous 90-180 windows.
		$recent_n = count( array_filter( $days, function ( $d ) { return $d <= 90; } ) );
		$prev_n   = count( array_filter( $days, function ( $d ) { return $d > 90 && $d <= 180; } ) );
		$f_decl   = $prev_n > 0 ? ( ( $prev_n - $recent_n ) / $prev_n ) * 100 : 0;
		$f_score  = $f_decl >= 100 ? 100 : ( $f_decl > 50 ? min( 100, round( 75 + ( $f_decl - 50 ) / 25 * 25 ) ) : round( $f_decl / 25 * 40 ) );

		$recent_s = 64.0;
		$prev_s   = 0.0;
		foreach ( $hero['orders'] as $o ) {
			if ( $o['days'] > 90 && $o['days'] <= 180 ) {
				$prev_s += $o['total'];
			}
		}
		$m_decl  = $prev_s > 0 ? ( ( $prev_s - $recent_s ) / $prev_s ) * 100 : 0;
		$m_score = $m_decl > 75 ? min( 100, round( 85 + ( $m_decl - 75 ) / 25 * 15 ) ) : round( 65 + ( $m_decl - 50 ) / 25 * 20 );

		$e_score = 80; // decline 100% (45+15) + no recent sessions bonus (20)

		$total = (int) min( 100, max( 0, round( ( $recency * 40 + $f_score * 25 + $m_score * 20 + $e_score * 15 ) / 100 ) ) );

		return array(
			'days_since_last_order' => $last,
			'order_frequency_days'  => $freq,
			'days_overdue'          => $overdue,
			'recency_score'         => (int) $recency,
			'frequency_score'       => (int) $f_score,
			'monetary_score'        => (int) $m_score,
			'engagement_score'      => $e_score,
			'churn_score'           => $total,
			'risk_level'            => ( $total > 80 ? 'critical' : ( $total > 60 ? 'high' : ( $total > 30 ? 'medium' : 'low' ) ) ),
		);
	}
}
