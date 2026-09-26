<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order-screen intelligence panel — payload assembler.
 *
 * Takes a customer id and returns everything the order-screen panel displays, as plain
 * data. It adds no data of its own: every field already exists in the database, written
 * by the nightly crons. Three indexed reads, a cached WooCommerce meta read, and four
 * label lookups that issue no query at all.
 *
 * Read-only by construction. It never calculates a missing score, never queries directly,
 * never touches an order, and never emits markup — R15-14B owns the meta box, the
 * template and every esc_* call.
 *
 * The constructor registers nothing; the class is inert until R15-14B instantiates it.
 *
 * @since 3.1.0
 */
class PDWCHS_Order_Panel_Data {

	/** Transient prefix; the user id is appended. */
	const CACHE_PREFIX = 'pdwchs_order_panel_';

	/**
	 * Payload TTL. Short: the panel is a read of nightly data, but an order screen
	 * is revisited within minutes and the store owner should not see a stale LTV
	 * after a refund lands.
	 */
	const CACHE_TTL = 5 * MINUTE_IN_SECONDS;

	/** Bot sentinel written by the public tracker. */
	const BOT_USER_ID = 999999999999999;

	/**
	 * No hooks, no queries, no properties primed from the database.
	 *
	 * R15-14B registers the hooks; this class only answers questions. An empty
	 * constructor is what lets the unit test instantiate it with no WordPress loaded.
	 */
	public function __construct() {
	}

	/**
	 * Assemble the panel payload for one customer.
	 *
	 * Always returns the full payload shape (see pdwchs_empty_payload()), never null and
	 * never false, so the template can read keys directly without an isset() on each one.
	 *
	 * @param int $user_id WP user id, as resolved from the order.
	 * @return array Payload; has_customer is false when the id is not a live customer.
	 */
	public function pdwchs_get_panel_data( $user_id ) {
		$user_id = absint( $user_id );

		// Guests hit this on every order-screen load, so the guard runs before the
		// transient read: an invalid id must cost nothing at all. get_userdata() is the
		// deleted-user check — an order outlives its customer, and without it the panel
		// would render a customer who no longer exists.
		if ( $user_id <= 0 || self::BOT_USER_ID === $user_id || ! get_userdata( $user_id ) ) {
			return $this->pdwchs_empty_payload();
		}

		$cached = get_transient( self::CACHE_PREFIX . $user_id );
		if ( false !== $cached ) {
			return $cached;
		}

		$intelligence = apply_filters( 'pdwchs_intelligence_instance', null );

		// Read 1 — the intelligence row. One whole-row read supplies all three score
		// fields; the health and lifecycle getters re-read this same row under their own
		// cache keys, so calling them would turn a one-row panel into three queries.
		$rfm_row = null;
		if ( $intelligence && method_exists( $intelligence, 'pdwchs_get_rfm_calculator' ) ) {
			$rfm_calculator = $intelligence->pdwchs_get_rfm_calculator();
			if ( $rfm_calculator ) {
				$rfm_row = $rfm_calculator->pdwchs_get_customer_rfm( $user_id ); // ARRAY_A|null.
			}
		}

		// Read 2 — the churn row. Returns an object, unlike read 1's array.
		$churn_row        = null;
		$churn_calculator = null;
		if ( $intelligence && method_exists( $intelligence, 'pdwchs_get_churn_calculator' ) ) {
			$churn_calculator = $intelligence->pdwchs_get_churn_calculator();
			if ( $churn_calculator ) {
				$churn_row = $churn_calculator->pdwchs_get_customer_churn_data( $user_id ); // object|null.
			}
		}

		$scores = $this->pdwchs_resolve_scores( $intelligence, $rfm_row );
		$ltv    = $this->pdwchs_resolve_ltv( $churn_row, $user_id );

		$payload = array(
			'has_customer'      => true,
			'user_id'           => $user_id,
			'health_score'      => $scores['health_score'],
			'health_status'     => $scores['health_status'],
			'rfm_segment'       => $scores['rfm_segment'],
			'rfm_config'        => $scores['rfm_config'],
			'lifecycle_stage'   => $scores['lifecycle_stage'],
			'lifecycle_config'  => $scores['lifecycle_config'],
			'ltv'               => $ltv['ltv'],
			'ltv_source'        => $ltv['ltv_source'],
			'last_seen_days'    => $this->pdwchs_resolve_last_seen( $user_id ),
			'predicted_next_ts' => $this->pdwchs_resolve_predicted_next( $churn_row, $churn_calculator ),
			'scores_calculated' => null !== $rfm_row,
		);

		set_transient( self::CACHE_PREFIX . $user_id, $payload, self::CACHE_TTL );

		return $payload;
	}

	/**
	 * The payload shape, with nothing in it.
	 *
	 * Every other path returns these exact keys with fields filled in, which is what
	 * keeps the template free of per-field isset() checks.
	 *
	 * @return array
	 */
	private function pdwchs_empty_payload() {
		return array(
			'has_customer'      => false,
			'user_id'           => 0,
			'health_score'      => null,
			'health_status'     => null,
			'rfm_segment'       => null,
			'rfm_config'        => null,
			'lifecycle_stage'   => null,
			'lifecycle_config'  => null,
			'ltv'               => null,
			'ltv_source'        => null,
			'last_seen_days'    => null,
			'predicted_next_ts' => null,
			'scores_calculated' => false,
		);
	}

	/**
	 * Split the intelligence row into its three scores and resolve each label.
	 *
	 * The label helpers are array lookups and if/else ladders — no query between them.
	 * A missing score stays null; it is a display state, never something to compute here.
	 *
	 * @param object|null $intelligence Coordinator from the seam, or null.
	 * @param array|null  $rfm_row      Read 1, or null when the crons have not reached this customer.
	 * @return array Six keys: the three scores and their three config arrays.
	 */
	private function pdwchs_resolve_scores( $intelligence, $rfm_row ) {
		$scores = array(
			'health_score'     => null,
			'health_status'    => null,
			'rfm_segment'      => null,
			'rfm_config'       => null,
			'lifecycle_stage'  => null,
			'lifecycle_config' => null,
		);

		if ( ! is_array( $rfm_row ) ) {
			return $scores;
		}

		if ( isset( $rfm_row['health_score'] ) && '' !== $rfm_row['health_score'] ) {
			$scores['health_score'] = (int) $rfm_row['health_score'];
		}

		if ( ! empty( $rfm_row['rfm_segment'] ) ) {
			$scores['rfm_segment'] = (string) $rfm_row['rfm_segment'];
		}

		if ( ! empty( $rfm_row['lifecycle_stage'] ) ) {
			$scores['lifecycle_stage'] = (string) $rfm_row['lifecycle_stage'];
		}

		// The segment config is the array-returning helper. Its badge sibling returns a
		// styled <span>, which would put markup in the payload and break the escaping
		// contract — if the template wants the badge it calls the badge helper itself.
		if ( null !== $scores['rfm_segment'] && class_exists( 'PD_Customer_History_Segments' ) ) {
			$scores['rfm_config'] = PD_Customer_History_Segments::pdwchs_get_segment( $scores['rfm_segment'] );
		}

		// pdwchs_get_health_status() and pdwchs_get_stage_config() are instance methods,
		// so the two calculators are fetched from the coordinator. Fetching an instance
		// costs nothing — they were all constructed when the coordinator was.
		if ( null !== $scores['health_score']
			&& $intelligence
			&& method_exists( $intelligence, 'pdwchs_get_health_calculator' ) ) {
			$health_calculator = $intelligence->pdwchs_get_health_calculator();
			if ( $health_calculator && method_exists( $health_calculator, 'pdwchs_get_health_status' ) ) {
				$scores['health_status'] = $health_calculator->pdwchs_get_health_status( $scores['health_score'] );
			}
		}

		if ( null !== $scores['lifecycle_stage']
			&& $intelligence
			&& method_exists( $intelligence, 'pdwchs_get_lifecycle_calculator' ) ) {
			$lifecycle_calculator = $intelligence->pdwchs_get_lifecycle_calculator();
			if ( $lifecycle_calculator && method_exists( $lifecycle_calculator, 'pdwchs_get_stage_config' ) ) {
				$scores['lifecycle_config'] = $lifecycle_calculator->pdwchs_get_stage_config( $scores['lifecycle_stage'] );
			}
		}

		return $scores;
	}

	/**
	 * Lifetime value, and which of the two sources produced it.
	 *
	 * The churn row wins. WC_Customer::get_total_spent() is the fallback because it reads
	 * WooCommerce's own _money_spent user meta — O(1), HPOS-safe, the same figure
	 * WooCommerce > Customers shows. The two treat refunds differently and will not always
	 * agree, which is why the winning source is recorded.
	 *
	 * @param object|null $churn_row Read 2, or null.
	 * @param int         $user_id   WP user id.
	 * @return array { @type float|null $ltv, @type string|null $ltv_source }
	 */
	private function pdwchs_resolve_ltv( $churn_row, $user_id ) {
		$ltv        = null;
		$ltv_source = null;

		if ( $churn_row && isset( $churn_row->total_spent ) ) {
			$ltv        = (float) $churn_row->total_spent;
			$ltv_source = 'churn';
		} elseif ( class_exists( 'WC_Customer' ) ) {
			try {
				$customer   = new WC_Customer( $user_id );
				$ltv        = (float) $customer->get_total_spent();
				$ltv_source = 'woocommerce';
			} catch ( Exception $e ) {
				// WC_Customer throws on a data-store failure. An exception escaping here
				// would fatal the order edit screen — the one screen this must not break.
				$ltv = null;
			}
		}

		return array(
			'ltv'        => $ltv,
			'ltv_source' => $ltv_source,
		);
	}

	/**
	 * Read 3 — days since the customer's most recent session.
	 *
	 * null and 0 are different answers and must stay different: 0 is "seen today", null is
	 * "never seen on this site". No intval(), no ?: 0 — R15-14B renders the two differently.
	 *
	 * @param int $user_id WP user id.
	 * @return int|null
	 */
	private function pdwchs_resolve_last_seen( $user_id ) {
		if ( ! class_exists( 'PDWCHS_Sessions_Data' ) ) {
			return null;
		}

		return PDWCHS_Sessions_Data::get_last_session_days_ago( $user_id );
	}

	/**
	 * Predicted next order date, as a UTC timestamp.
	 *
	 * Costs no query — it is arithmetic on the churn row already read. Its own null guards
	 * cover the zero-order customer (the days_since_last_order = 999 sentinel), so they are
	 * neither duplicated nor substituted here: null means "omit the row". The UTC integer
	 * is passed through unformatted; R15-14B formats it on the site's date format.
	 *
	 * @param object|null $churn_row        Read 2, or null.
	 * @param object|null $churn_calculator Churn calculator from the coordinator, or null.
	 * @return int|null
	 */
	private function pdwchs_resolve_predicted_next( $churn_row, $churn_calculator ) {
		if ( ! $churn_row || ! $churn_calculator
			|| ! method_exists( $churn_calculator, 'pdwchs_get_predicted_next_order_ts' ) ) {
			return null;
		}

		return $churn_calculator->pdwchs_get_predicted_next_order_ts( $churn_row );
	}
}
