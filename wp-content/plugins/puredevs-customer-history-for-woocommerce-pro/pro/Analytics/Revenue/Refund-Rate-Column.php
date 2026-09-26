<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Refund-Rate-Data.php';

/**
 * Refund rate — the Customers & Users column.
 *
 * The presentation half of the pair whose data half is PDWCHS_Refund_Rate_Data: one column headed
 * "Refund Rate", spliced immediately to the right of free's existing "Refunded Orders", rendering each
 * customer's refunded share as a colour-graded badge.
 *
 * It is a consumer and nothing else. Every number comes from the data class and this file performs no
 * arithmetic beyond formatting; it declares no seam of its own, because R15-24A opened both of the ones
 * it uses.
 *
 * The two columns reading as a pair is the point. Free's "Refunded Orders" counts orders whose *status*
 * is refunded (class-pd-customer-history-customers.php:297-313), so a $2,000 order with $800 refunded
 * shows as 0 there and 40.00% here. That disagreement is the signal, which is why this column is spliced
 * next to it rather than appended, and why the header carries a title explaining the divergence rather
 * than free's column being renamed.
 *
 * Two registrations and no more: the column declaration and its cell filter. No stylesheet — .pdwchs-badge
 * and .pdwchs-badge-small are already on this page from the free stylesheet, and they deliberately set no
 * background, so the consumer supplies it inline exactly as Segments-Labels.php:224 does. No script, no
 * endpoint, no nonce, no AJAX: rendering a column is a read on a page load.
 *
 * Gating is structural — the whole pro/ tree loads only behind the licence gate, so the column disappears
 * on a lapse with no gating code here.
 *
 * @since 3.1.2
 */
class PDWCHS_Refund_Rate_Column {

	/** The column key, and the suffix of the cell seam this class listens on. */
	const COLUMN_KEY = 'refund_rate';

	/** @var PDWCHS_Refund_Rate_Data */
	private $data;

	/** @var array|null Memoised map; null until the first cell asks for it. */
	private $rates = null;

	/**
	 * Two registrations, and no more.
	 *
	 * Both seams are value filters, so both are add_filter and neither is add_action. The explicit 10, 2
	 * on the second matters: without the accepted-args count the callback receives only $value and never
	 * sees $user_id, and every cell silently renders the default — the same trap Tags-Filter.php:46-47
	 * documents for pdwchs_segment_apply_extra_criteria.
	 *
	 * The cell hook name is written out as a literal rather than built from self::COLUMN_KEY, so it stays
	 * greppable in a codebase where hooks are the whole integration contract.
	 */
	public function __construct() {
		$this->data = new PDWCHS_Refund_Rate_Data();

		add_filter( 'pdwchs_customers_columns', array( $this, 'pdwchs_register_column' ) );
		add_filter( 'pdwchs_customers_column_refund_rate', array( $this, 'pdwchs_render_cell' ), 10, 2 );
	}

	/**
	 * Declare the column, immediately after free's status-based count.
	 *
	 * The label carries a title attribute because two things about this number will not reconcile with
	 * the rest of the plugin and a reader who is not told will file a bug: the rate spans completed,
	 * processing *and refunded* orders, and it covers registered customers only.
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
				'Refunded amount as a share of what the customer was billed, across completed, processing and refunded orders, for registered customers only.',
				'puredevs-customer-history-for-woocommerce'
			),
			esc_html__( 'Refund Rate', 'puredevs-customer-history-for-woocommerce' )
		);

		// Splice immediately after free's status-based count, so the two read as a pair.
		$position = array_search( 'refund_orders', array_keys( $columns ), true );

		// false means free moved or removed refund_orders — a free-side change this class cannot see
		// coming. Appending is the safe degradation: the column still renders, just in the wrong place.
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
	 * An absent key and a null rate both mean "no qualifying orders" and read as a grey em-dash. A rate of
	 * 0.0 is a measured result — a customer with orders and no refunds — and reads 0.00% in green. The two
	 * are deliberately kept distinct; a single ! empty() check anywhere in this path collapses them.
	 *
	 * @since 3.1.2
	 *
	 * @param string $value   Cell contents from the seam's default. Unused — this column owns its cell.
	 * @param int    $user_id The row's WordPress user id.
	 * @return string Escaped, display-ready markup.
	 */
	public function pdwchs_render_cell( $value, $user_id ) {
		$rates = $this->pdwchs_get_rates();
		$id    = (int) $user_id;

		if ( ! isset( $rates[ $id ] ) || null === $rates[ $id ]['rate'] ) {
			return $this->pdwchs_badge( null );
		}

		return $this->pdwchs_badge( (float) $rates[ $id ]['rate'] );
	}

	/**
	 * The badge, with the founder-approved bands.
	 *
	 * WP_List_Table echoes column_default()'s return into the <td> without further filtering, so this
	 * method is the last line of defence: esc_attr() on the colour and the title, esc_html() on the text,
	 * and no unescaped concatenation anywhere.
	 *
	 * number_format_i18n(), not number_format() — the decimal separator follows the site locale, exactly
	 * as free's own row values do.
	 *
	 * @param float|null $rate Percentage 0-100, or null for a customer with no qualifying orders.
	 * @return string
	 */
	private function pdwchs_badge( $rate ) {
		if ( null === $rate ) {
			$colour = '#94a3b8';
			$title  = __( 'No completed, processing or refunded orders.', 'puredevs-customer-history-for-woocommerce' );
			$text   = '—';
		} else {
			if ( $rate >= 30.0 ) {
				$colour = '#dc2626';
			} elseif ( $rate >= 10.0 ) {
				$colour = '#d97706';
			} else {
				$colour = '#16a34a';
			}

			$title = __(
				'Refunded share of billing, across completed, processing and refunded orders, for registered customers only.',
				'puredevs-customer-history-for-woocommerce'
			);
			$text  = number_format_i18n( $rate, 2 ) . '%';
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
	 * column_default() fires once per visible row, so without this a fifty-row page makes fifty calls into
	 * the data class. null is the sentinel rather than array(), because an empty store legitimately returns
	 * array() and empty() cannot tell that from "not fetched yet".
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
