<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Average order gap — the Customers & Users column.
 *
 * Surfaces {prefix}pdwchs_churn_scores.order_frequency_days, the mean days between orders that the
 * churn batch already computes and rounds into expected_next_purchase_days (Churn.php:340). This
 * class is a reader and a formatter: it runs one bulk SELECT per request and performs no arithmetic
 * beyond rounding for display.
 *
 * It never computes a gap. A customer with no churn row has not been scored yet, and the cell says so
 * with an em-dash rather than guessing — a second producer for this number would drift from the one
 * churn scoring uses, and a per-row fallback would make a fifty-row page fifty queries.
 *
 * Two consequences the header title states, because neither is fixable here: the value is only as
 * fresh as the last churn batch, and Churn.php queries {$wpdb->prefix}wc_orders directly with no
 * legacy-posts dialect, so on a pre-HPOS store the table stays empty and every cell reads em-dash.
 *
 * Two registrations, no stylesheet, no script, no endpoint. Gating is structural — the whole pro/
 * tree loads only behind the licence gate.
 *
 * @since 3.1.2
 */
class PDWCHS_Order_Gap_Column {

	/** The column key, and the suffix of the cell seam this class listens on. */
	const COLUMN_KEY = 'order_gap';

	/** @var array|null Memoised map of user_id => float|null; null until the first cell asks. */
	private $gaps = null;

	/**
	 * Two registrations, and no more.
	 *
	 * Both seams are value filters, so both are add_filter and neither is add_action. The explicit
	 * 10, 2 on the second matters: without the accepted-args count the callback receives only $value
	 * and never sees $user_id, and every cell silently renders the default — the same trap
	 * Tags-Filter.php:46-47 documents for pdwchs_segment_apply_extra_criteria.
	 *
	 * The cell hook name is written out as a literal rather than built from self::COLUMN_KEY, so it
	 * stays greppable in a codebase where hooks are the whole integration contract.
	 */
	public function __construct() {
		add_filter( 'pdwchs_customers_columns', array( $this, 'pdwchs_register_column' ) );
		add_filter( 'pdwchs_customers_column_order_gap', array( $this, 'pdwchs_render_cell' ), 10, 2 );
	}

	/**
	 * Declare the column, immediately after Refund Rate.
	 *
	 * Anchoring on refund_rate rather than free's refund_orders puts the three behavioural signals —
	 * refunded count, refunded share, purchase cadence — in one run. The label carries a title because
	 * a reader who is not told that the number comes from the nightly churn calculation, and is blank
	 * until that calculation has scored the customer, will file a bug.
	 *
	 * @since 3.1.2
	 *
	 * @param array $columns Column key => display label, in render order.
	 * @return array
	 */
	public function pdwchs_register_column( $columns ) {
		if ( ! is_array( $columns ) ) {
			return $columns;
		}

		$label = sprintf(
			'<span title="%1$s">%2$s</span>',
			esc_attr__(
				'Mean days between orders, from the nightly churn calculation. Blank until that calculation has scored the customer.',
				'puredevs-customer-history-for-woocommerce'
			),
			esc_html__( 'Avg Order Gap', 'puredevs-customer-history-for-woocommerce' )
		);

		// Splice immediately after Refund Rate, so the behavioural signals read as one run.
		$position = array_search( 'refund_rate', array_keys( $columns ), true );

		// false means R15-24B's column is absent — an upstream change this class cannot see coming.
		// Appending is the safe degradation: the column still renders, just in the wrong place.
		if ( false === $position ) {
			$columns[ self::COLUMN_KEY ] = $label;
			return $columns;
		}

		// $preserve_keys = true on both slices, and the + union rather than array_merge: the keys are
		// strings and load-bearing, because column_default() dispatches on them.
		return array_slice( $columns, 0, $position + 1, true )
			+ array( self::COLUMN_KEY => $label )
			+ array_slice( $columns, $position + 1, null, true );
	}

	/**
	 * Fill the cell.
	 *
	 * An absent key means the churn batch has not reached this customer; a null value means it has,
	 * and found a single order — you cannot measure a gap between one order and nothing. Both read as
	 * a grey em-dash today, and both are kept distinct anyway: array_key_exists(), not isset(), because
	 * the moment anyone splits the two states an isset() bug appears silently.
	 *
	 * A stored 0.40 is a measured, very short gap and renders "0 days" in green. A single
	 * ! empty() check anywhere in this path collapses it into the em-dash.
	 *
	 * @since 3.1.2
	 *
	 * @param string $value   Cell contents from the seam's default. Unused — this column owns its cell.
	 * @param int    $user_id The row's WordPress user id.
	 * @return string Escaped, display-ready markup.
	 */
	public function pdwchs_render_cell( $value, $user_id ) {
		$gaps = $this->pdwchs_get_gaps();
		$id   = (int) $user_id;

		if ( ! array_key_exists( $id, $gaps ) || null === $gaps[ $id ] ) {
			return $this->pdwchs_badge( null );
		}

		return $this->pdwchs_badge( (float) $gaps[ $id ] );
	}

	/**
	 * The badge, with the founder-approved bands.
	 *
	 * WP_List_Table echoes column_default()'s return into the <td> without further filtering, so this
	 * method is the last line of defence: esc_attr() on the colour and the title, esc_html() on the
	 * text, and no unescaped concatenation anywhere.
	 *
	 * The value is rounded to whole days exactly as Churn.php:340 rounds it into
	 * expected_next_purchase_days, so the column and the churn card never disagree — and the band is
	 * chosen from the rounded figure, so a cell reading "30 days" is never coloured as if it read 29.
	 *
	 * @param float|null $gap Mean days between orders, or null for a customer with no measurable gap.
	 * @return string
	 */
	private function pdwchs_badge( $gap ) {
		if ( null === $gap ) {
			$colour = '#94a3b8';
			$title  = __( 'Not yet scored by the churn calculation, or only one order.', 'puredevs-customer-history-for-woocommerce' );
			$text   = '—';
		} else {
			$days = (int) round( $gap );

			if ( $days >= 90 ) {
				$colour = '#dc2626';
			} elseif ( $days >= 30 ) {
				$colour = '#d97706';
			} else {
				$colour = '#16a34a';
			}

			$title = __(
				'Mean days between orders, from the nightly churn calculation.',
				'puredevs-customer-history-for-woocommerce'
			);

			$text = sprintf(
				/* translators: %s: number of days between orders. */
				_n( '%s day', '%s days', $days, 'puredevs-customer-history-for-woocommerce' ),
				number_format_i18n( $days )
			);
		}

		return sprintf(
			'<span class="pdwchs-badge pdwchs-badge-small" style="background-color: %1$s;" title="%2$s">%3$s</span>',
			esc_attr( $colour ),
			esc_attr( $title ),
			esc_html( $text )
		);
	}

	/**
	 * The map, fetched once per request whatever the page size.
	 *
	 * column_default() fires once per visible row, so without this a fifty-row page makes fifty
	 * queries. null is the sentinel rather than array(), because a store whose churn batch has never
	 * run legitimately returns array() and empty() cannot tell that from "not fetched yet".
	 *
	 * The whole table, not an IN (…) of the visible ids: the row ids are not available at construction
	 * time and building the list would mean a row-data seam in the free file. Two narrow columns over
	 * one row per scored customer is cheap.
	 *
	 * null is preserved rather than collapsed to 0.0 — Churn.php:325-336 leaves the value null for a
	 * single-order customer, and (float) null would render "0 days", which reads as "buys every day".
	 *
	 * @return array Map of user_id => float|null.
	 */
	private function pdwchs_get_gaps() {
		if ( null === $this->gaps ) {
			global $wpdb;

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Plugin-owned churn table with no core API; the only interpolated value is $wpdb->prefix and the read takes no user input.
			$rows = $wpdb->get_results( "SELECT customer_id, order_frequency_days FROM {$wpdb->prefix}pdwchs_churn_scores" );

			$map = array();

			foreach ( (array) $rows as $row ) {
				$map[ (int) $row->customer_id ] = ( null === $row->order_frequency_days )
					? null
					: (float) $row->order_frequency_days;
			}

			$this->gaps = $map;
		}

		return $this->gaps;
	}
}
