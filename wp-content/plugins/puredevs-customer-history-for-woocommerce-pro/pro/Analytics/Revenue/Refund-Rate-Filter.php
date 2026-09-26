<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Refund-Rate-Data.php';

/**
 * Refund rate — the Customers & Users filter control and saved-segment criterion.
 *
 * The actionable half of Feature 15.13: an operator select and a threshold input on the Customers &
 * Users filter bar, and the same pair as a saved-segment criterion, so "customers refunding more than
 * 30%" can be filtered, saved, counted and exported.
 *
 * Runs entirely on seams the free build already provides — H9 for the control and R15-18B's three
 * generic criteria seams for everything else — so this chunk edits no free file and this class knows
 * nothing about the list's internals. It runs no query of its own either: PDWCHS_Refund_Rate_Data is
 * the only data access, and it caches its single read in a transient.
 *
 * Two scalar keys rather than one compound value, mirroring free's total_spent / total_spent_input pair.
 * The save-segment script reads criteria with params.get(), which returns only the first value of a
 * repeated key, so a bracketed pair would filter the list correctly and then silently save a segment
 * carrying half the condition.
 *
 * Gating is structural: H9 fires inside the licensed block on the customers template, and the whole
 * pro/ tree loads only behind the licence gate — so the control disappears on a lapse with no gating
 * code here, and a saved segment carrying either key degrades to the criterion being stripped at
 * whitelist time rather than fataling.
 *
 * @since 3.1.2
 */
class PDWCHS_Refund_Rate_Filter {

	/** Operator key. */
	const OPERATOR_KEY = 'refund_rate';

	/** Threshold key. */
	const VALUE_KEY = 'refund_rate_input';

	/**
	 * The three spellings free already uses — misspelling included.
	 *
	 * Not "fixed" to greater_than on purpose: a segment saved with free's total_spent control and one
	 * saved with this one must carry the same vocabulary for the same idea, or any later consolidation
	 * has to migrate stored JSON.
	 */
	const OPERATORS = array( 'equal', 'greater_then', 'less_then' );

	/** @var PDWCHS_Refund_Rate_Data */
	private $data;

	/** @var array|null Memoised map; null until a prune actually needs it. */
	private $rates = null;

	/**
	 * Four registrations, and no more.
	 *
	 * H9 is a do_action, so it takes add_action; the other three are apply_filters. Getting that wrong
	 * on seam 2 is the classic version of the bug — the callback runs, returns nothing useful, and every
	 * customer is pruned.
	 *
	 * Nothing is registered on either of the column seams: the column is R15-24B's, on different seams,
	 * in a different file.
	 */
	public function __construct() {
		$this->data = new PDWCHS_Refund_Rate_Data();

		add_action( 'pdwchs_customers_filters_extra', array( $this, 'pdwchs_render_filter' ) ); // H9.

		// Seam 1 — admits both keys to the criteria whitelist and, through the same filter in
		// the admin localizer, to pdwchs_save_segment's allowedKeys.
		add_filter( 'pdwchs_segment_criteria_keys', array( $this, 'pdwchs_register_criterion' ) );

		// Seam 2 — the prune itself. The explicit 2 matters: without it the callback
		// receives $customers and never sees $criteria.
		add_filter( 'pdwchs_segment_apply_extra_criteria', array( $this, 'pdwchs_apply_criterion' ), 10, 2 );

		// Seam 3 — keeps both keys alive across the second (lifecycle) filter form.
		add_filter( 'pdwchs_customers_preserve_params', array( $this, 'pdwchs_preserve_params' ) );
	}

	// -------------------------------------------------------------------
	// Render
	// -------------------------------------------------------------------

	/**
	 * H9 — render the Refund Rate control on the customers list filter bar.
	 *
	 * Matches free's total_spent group exactly, including the d-none wrapper and the clear button:
	 * .pd-wcch-close, .pd-wcch-select and .pd-wcch-inpu-wrapper are wired by the page's existing admin
	 * script, so the clear button and the show/hide work with no JS of our own. Those handlers are
	 * direct binds inside the ready callback, not delegated ones — which is another reason this control
	 * is server-rendered rather than injected.
	 *
	 * The control sits inside the page's existing GET form, so it needs no form of its own and no submit
	 * button — it goes out with Search.
	 *
	 * type="number" with min, max and step is free browser UX, not validation: the same keys arrive from
	 * a saved segment where no browser was involved, so pdwchs_read_threshold() sanitises on every path.
	 *
	 * The inline min-width is load-bearing, not decoration. .pd-wcch-form-group is display:flex and
	 * .pd-wcch-form-control is width:30% — a percentage resolved against a shrink-to-fit flex item. A
	 * text input props that up with its intrinsic size, so free's total_spent_input renders as a normal
	 * field; a number input has no comparable intrinsic width, so the 30% collapses to the spinner alone
	 * and the control is unusable. The fix belongs here rather than in the stylesheet because
	 * admin/css/pd-customer-history-admin.css is a free file and this chunk edits none. Inline sizing on
	 * a filter control is the house pattern regardless — customers.php:70 and :86 and every Pro control
	 * (Tags-Filter.php:83, Intelligence.php:681-736) size themselves the same way.
	 *
	 * @since 3.1.2
	 *
	 * @return void
	 */
	public function pdwchs_render_filter() {
		// is_scalar() before the sanitiser on both reads: a bracketed value in the query string would
		// otherwise reach sanitize_text_field() as an array and raise an array-to-string notice.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$operator = ( isset( $_GET[ self::OPERATOR_KEY ] ) && is_scalar( $_GET[ self::OPERATOR_KEY ] ) )
			? sanitize_text_field( wp_unslash( $_GET[ self::OPERATOR_KEY ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';

		if ( ! in_array( $operator, self::OPERATORS, true ) ) {
			$operator = '';
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw_value = ( isset( $_GET[ self::VALUE_KEY ] ) && is_scalar( $_GET[ self::VALUE_KEY ] ) )
			? sanitize_text_field( wp_unslash( $_GET[ self::VALUE_KEY ] ) ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: '';

		$value = is_numeric( $raw_value ) ? (string) floatval( $raw_value ) : '';
		?>
		<div class="pd-wcch-form-group">
			<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Refund Rate :', 'puredevs-customer-history-for-woocommerce' ); ?> </label>
			<select name="refund_rate" id="refund_rate" class="pd-wcch-select pd-wcch-inline-block">
				<option value="">-- Select One --</option>
				<option value="<?php echo esc_attr( 'equal' ); ?>" <?php selected( $operator, 'equal' ); ?>><?php echo esc_html__( 'Equal', 'puredevs-customer-history-for-woocommerce' ); ?></option>
				<option value="<?php echo esc_attr( 'greater_then' ); ?>" <?php selected( $operator, 'greater_then' ); ?>><?php echo esc_html__( 'Greater then', 'puredevs-customer-history-for-woocommerce' ); ?></option>
				<option value="<?php echo esc_attr( 'less_then' ); ?>" <?php selected( $operator, 'less_then' ); ?>><?php echo esc_html__( 'Less then', 'puredevs-customer-history-for-woocommerce' ); ?></option>
			</select>

			<div class="pd-wcch-inpu-wrapper <?php if ( '' === $value ) { echo esc_attr( 'd-none' ); } ?>">
				<input type="number" min="0" max="100" step="0.01" class="pd-wcch-form-control pd-wcch-inline-block" style="min-width: 80px;" name="refund_rate_input" value="<?php echo esc_attr( $value ); ?>">
				<button type="button" class="pd-wcch-btn pd-wcch-inline-block  pd-wcch-close button btn">&#10005;</button>
			</div>
		</div>
		<?php
	}

	// -------------------------------------------------------------------
	// Criterion
	// -------------------------------------------------------------------

	/**
	 * Seam 1 — admit both keys to the allowed criteria keys.
	 *
	 * Both, in one callback: miss the threshold key and the operator survives into a saved segment while
	 * the threshold does not, so the segment silently means something else.
	 *
	 * @since 3.1.2
	 *
	 * @param array $keys Allowed criteria keys.
	 * @return array
	 */
	public function pdwchs_register_criterion( $keys ) {
		$keys[] = self::OPERATOR_KEY;
		$keys[] = self::VALUE_KEY;

		return $keys;
	}

	/**
	 * Seam 3 — preserve both keys across the lifecycle filter form.
	 *
	 * extra_tablenav() renders its own GET form; a parameter missing from this list is dropped the moment
	 * that form is submitted.
	 *
	 * @since 3.1.2
	 *
	 * @param array $params Query parameter names to preserve.
	 * @return array
	 */
	public function pdwchs_preserve_params( $params ) {
		$params[] = self::OPERATOR_KEY;
		$params[] = self::VALUE_KEY;

		return $params;
	}

	/**
	 * Seam 2 — prune the customer set to those whose refund rate meets the threshold.
	 *
	 * Every degenerate input is "no filter", never "no results": the reader returns null and $customers
	 * comes back untouched, before pdwchs_get_rates() is called. A filter that is not applied must not
	 * cost a read.
	 *
	 * Keys are left as they are. Free code re-indexes on the next line on the segments path and does not
	 * need to on the list path — count() and array_slice() both tolerate gaps — and R15-18B's contract
	 * says a listener may return the array either way.
	 *
	 * @since 3.1.2
	 *
	 * @param array $customers WP_User objects, keys not guaranteed contiguous.
	 * @param array $criteria  Whitelisted criteria, or a raw $_GET superset.
	 * @return array
	 */
	public function pdwchs_apply_criterion( $customers, $criteria ) {
		$threshold = $this->pdwchs_read_threshold( $criteria );

		if ( null === $threshold ) {
			return $customers;
		}

		$rates = $this->pdwchs_get_rates();

		foreach ( $customers as $key => $user ) {
			$id   = (int) $user->ID;
			$rate = isset( $rates[ $id ] ) ? $rates[ $id ]['rate'] : null;

			if ( ! $this->pdwchs_matches( $rate, $threshold['operator'], $threshold['value'] ) ) {
				unset( $customers[ $key ] );
			}
		}

		return $customers;
	}

	/**
	 * The operator/value pair, or null for "no filter applied".
	 *
	 * $criteria arrives in two shapes: a whitelisted array from the segments manager (the saved-segment
	 * count and the CSV export) and a raw $_GET superset from the list page. R15-18B's contract puts
	 * sanitisation on the listener, and the whitelist is key-level only — it never touches values — so
	 * both are sanitised here, on both paths.
	 *
	 * is_scalar() before the cast, always: a bracketed value in the query string is a legitimate thing
	 * for an attacker to try, and floatval() on an array is not a safe no-op on every PHP version.
	 * in_array()'s third argument is not optional either — without it PHP's loose comparison accepts a
	 * posted 0 as a valid operator string.
	 *
	 * A value over 100 is a real filter, not a degenerate one: a rate cannot exceed 100 on real data, so
	 * "greater then 150" legitimately returns an empty list. Only a negative value is rejected, because
	 * it cannot be meant.
	 *
	 * @param mixed $criteria Whitelisted criteria, or a raw $_GET superset.
	 * @return array|null Array with operator and value keys, or null for no filter.
	 */
	private function pdwchs_read_threshold( $criteria ) {
		if ( ! is_array( $criteria ) ) {
			return null;
		}

		if ( ! isset( $criteria[ self::OPERATOR_KEY ], $criteria[ self::VALUE_KEY ] ) ) {
			return null;
		}

		if ( ! is_scalar( $criteria[ self::OPERATOR_KEY ] ) || ! is_scalar( $criteria[ self::VALUE_KEY ] ) ) {
			return null;
		}

		$operator = sanitize_text_field( $criteria[ self::OPERATOR_KEY ] );

		if ( ! in_array( $operator, self::OPERATORS, true ) ) {
			return null;
		}

		if ( ! is_numeric( $criteria[ self::VALUE_KEY ] ) ) {
			return null;
		}

		$value = floatval( $criteria[ self::VALUE_KEY ] );

		if ( $value < 0 ) {
			return null;
		}

		return array(
			'operator' => $operator,
			'value'    => $value,
		);
	}

	/**
	 * Whether one customer's rate meets the threshold.
	 *
	 * A null rate matches no operator, less_then included. A customer with no qualifying orders has no
	 * rate; treating null as 0 would sweep every never-ordered customer into "refund rate under 10%",
	 * which is exactly wrong. Feature spec Decision 9.
	 *
	 * The comparisons mirror free's total_spent block — strictly greater, strictly less.
	 *
	 * equal compares with a half-of-the-last-decimal epsilon rather than ==, because both sides are
	 * floats. The rate is not rounded again: PDWCHS_Refund_Rate_Data already rounded it to two decimals,
	 * and rounding twice is how 40.005 becomes a bug report.
	 *
	 * @param float|null $rate     The customer's rate, or null for no qualifying orders.
	 * @param string     $operator One of self::OPERATORS.
	 * @param float      $value    The threshold, 0 or above.
	 * @return bool
	 */
	private function pdwchs_matches( $rate, $operator, $value ) {
		if ( null === $rate ) {
			return false;
		}

		$rate = (float) $rate;

		if ( 'equal' === $operator ) {
			return abs( $rate - $value ) < 0.005;
		}

		if ( 'greater_then' === $operator ) {
			return $rate > $value;
		}

		if ( 'less_then' === $operator ) {
			return $rate < $value;
		}

		return false;
	}

	// -------------------------------------------------------------------
	// Memo
	// -------------------------------------------------------------------

	/**
	 * The map, fetched once per request.
	 *
	 * Seam 2 fires on the list path and on the segments path, and both can happen in one request when a
	 * saved segment is loaded. The memo is what makes that one read. null is the sentinel rather than
	 * array(), because an empty store legitimately returns array() and empty() cannot tell that from
	 * "not fetched yet".
	 *
	 * @return array Map of user_id => row, as PDWCHS_Refund_Rate_Data returns it.
	 */
	private function pdwchs_get_rates() {
		if ( null === $this->rates ) {
			$this->rates = $this->data->pdwchs_get_refund_rates();
		}

		return $this->rates;
	}
}
