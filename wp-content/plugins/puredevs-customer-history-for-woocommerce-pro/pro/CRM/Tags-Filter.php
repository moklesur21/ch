<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Tags.php';

/**
 * Manual Tags — customers-list filter and saved-segment criterion.
 *
 * The consumer half of Feature 15.10 (chunk R15-19): a Manual Tag dropdown on
 * the Customers & Users filter bar, tag_filter as a saved-segment criterion,
 * and a rev-salted member cache.
 *
 * Runs entirely on seams the free build already provides — H9 for the dropdown
 * and R15-18B's three generic criteria seams for everything else — so this
 * chunk edits no free file and this class knows nothing about the list's
 * internals. It also runs no query of its own: PDWCHS_Customer_Tags's two
 * public static readers are the only data access, and the class is never
 * instantiated here (a second instance would double-register R15-16B's five
 * AJAX hooks).
 *
 * Gating is structural: H9 fires inside the pdwchs_is_pro_active() block at
 * admin/partials/templates/customers.php:82, and the whole pro/ tree loads only
 * behind the licence gate — so the dropdown disappears on a lapse with no
 * gating code here, and a saved segment carrying tag_filter degrades to the
 * criterion being stripped at whitelist time rather than fataling.
 *
 * @since 3.1.1
 */
class PDWCHS_Tags_Filter {

	/** Transient prefix for a tag's cached member id set. */
	const CACHE_PREFIX = 'pdwchs_tag_members_';

	/** Member-cache TTL. The rev salt, not this, is what keeps reads fresh. */
	const CACHE_TTL = 10 * MINUTE_IN_SECONDS;

	public function __construct() {
		add_action( 'pdwchs_customers_filters_extra', array( $this, 'pdwchs_render_filter' ) ); // H9.

		// Seam 1 — admits tag_filter to the criteria whitelist and, through the
		// same filter in the admin localizer, to pdwchs_save_segment's allowedKeys.
		add_filter( 'pdwchs_segment_criteria_keys', array( $this, 'pdwchs_register_criterion' ) );

		// Seam 2 — the prune itself. The explicit 2 matters: without it the
		// callback receives $customers and never sees $criteria.
		add_filter( 'pdwchs_segment_apply_extra_criteria', array( $this, 'pdwchs_apply_criterion' ), 10, 2 );

		// Seam 3 — keeps tag_filter alive across the second (lifecycle) filter form.
		add_filter( 'pdwchs_customers_preserve_params', array( $this, 'pdwchs_preserve_param' ) );
	}

	// -------------------------------------------------------------------
	// Render
	// -------------------------------------------------------------------

	/**
	 * H9 — render the Manual Tag dropdown on the customers list filter bar.
	 *
	 * Matches the Intelligence filter groups' markup exactly, including the
	 * always-hidden clear-button wrapper: .pd-wcch-close is wired by a
	 * delegated handler in admin/js/pd-customer-history-admin.js that resets any
	 * .pd-wcch-select, so the × works with no JS of our own. The select sits
	 * inside the page's existing form and submits with Search.
	 *
	 * A single-value select, deliberately: admin/js/pdwchs-save-segment.js reads
	 * criteria with params.get(), which returns only the first value of a
	 * repeated key, so a multi-select would filter the list correctly and then
	 * silently save a segment with one tag.
	 *
	 * @since 3.1.1
	 */
	public function pdwchs_render_filter() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$selected = isset( $_GET['tag_filter'] ) ? absint( wp_unslash( $_GET['tag_filter'] ) ) : 0;

		// false skips the LEFT JOIN COUNT — a dropdown needs names and ids only.
		$tags = PDWCHS_Customer_Tags::pdwchs_get_all_tags( false );
		?>
		<div class="pd-wcch-form-group">
			<label class="pd-wcch-title pd-wcch-inline-block pd-wcch-mr-30"><?php echo esc_html__( 'Manual Tag :', 'puredevs-customer-history-for-woocommerce' ); ?></label>
			<select name="tag_filter" id="tag_filter" class="pd-wcch-select pd-wcch-inline-block" style="min-width: 300px;">
				<option value="">-- <?php echo esc_html__( 'All Tags', 'puredevs-customer-history-for-woocommerce' ); ?> --</option>
				<?php foreach ( $tags as $tag_row ) : ?>
					<option value="<?php echo esc_attr( $tag_row->id ); ?>" <?php selected( $selected, (int) $tag_row->id ); ?>>
						<?php echo esc_html( $tag_row->name ); ?>
					</option>
				<?php endforeach; ?>
			</select>
			<div class="pd-wcch-inpu-wrapper d-none">
				<button type="button" class="pd-wcch-btn pd-wcch-inline-block pd-wcch-close button btn">&#10005;</button>
			</div>
		</div>
		<?php
	}

	// -------------------------------------------------------------------
	// Criterion
	// -------------------------------------------------------------------

	/**
	 * Seam 1 — admit tag_filter to the allowed criteria keys.
	 *
	 * @param array $keys Allowed criteria keys.
	 * @return array
	 */
	public function pdwchs_register_criterion( $keys ) {
		$keys[] = 'tag_filter';
		return $keys;
	}

	/**
	 * Seam 3 — preserve tag_filter across the lifecycle filter form.
	 *
	 * extra_tablenav() renders its own GET form; a parameter missing from this
	 * list is dropped the moment that form is submitted.
	 *
	 * @param array $params Query parameter names to preserve.
	 * @return array
	 */
	public function pdwchs_preserve_param( $params ) {
		$params[] = 'tag_filter';
		return $params;
	}

	/**
	 * Seam 2 — prune the customer set to the members of the selected tag.
	 *
	 * $criteria arrives in two shapes: a whitelisted, sanitised array from the
	 * segments manager (saved-segment count and CSV export), and a raw $_GET
	 * superset from the customers list. So the value is sanitised here on every
	 * path, and a non-scalar is rejected before absint() ever sees it — an array
	 * in the query string is a legitimate thing for an attacker to try, and
	 * absint( array() ) is not a safe no-op on every PHP version.
	 *
	 * A tag id of 0 — missing, '', '0', 'abc' or an array — means "no filter",
	 * never "no results": $customers comes back untouched and no read is made.
	 *
	 * Keys are left as they are. Free code re-indexes on the next line and
	 * R15-18B's contract says a listener may return the array either way.
	 *
	 * @param array $customers WP_User objects, keys not guaranteed contiguous.
	 * @param array $criteria  Whitelisted criteria, or a raw $_GET superset.
	 * @return array
	 */
	public function pdwchs_apply_criterion( $customers, $criteria ) {
		if ( ! is_array( $criteria ) || ! isset( $criteria['tag_filter'] ) || ! is_scalar( $criteria['tag_filter'] ) ) {
			return $customers;
		}

		$tag_id = absint( $criteria['tag_filter'] );
		if ( 0 === $tag_id ) {
			return $customers;
		}

		$ids = $this->pdwchs_get_tagged_ids( $tag_id );

		foreach ( $customers as $key => $user ) {
			// Strict, so a string id in the set cannot match by coercion.
			if ( ! in_array( (int) $user->ID, $ids, true ) ) {
				unset( $customers[ $key ] );
			}
		}

		return $customers;
	}

	// -------------------------------------------------------------------
	// Cache
	// -------------------------------------------------------------------

	/**
	 * A tag's member ids, cached under a key salted with the tags revision.
	 *
	 * Every mutation in PDWCHS_Customer_Tags bumps pdwchs_tags_rev, so a create,
	 * rename, delete, assign or unassign rotates every key at once: the old
	 * entries are orphaned and expire on their own TTL, and the next read misses
	 * and fetches fresh data. That is what makes a new assignment visible on the
	 * very next request instead of after the TTL — there is no explicit
	 * invalidation anywhere, and the salt must stay in the key.
	 *
	 * Accepted staleness: a hard user deletion cascades the junction rows away
	 * without bumping the rev, so a deleted user can linger in a cached id set
	 * for up to the TTL. They no longer exist as a WP_User and so cannot appear
	 * in the list; the only effect is a slightly larger id array.
	 *
	 * @param int $tag_id Tag id, already sanitised by the caller.
	 * @return int[]
	 */
	private function pdwchs_get_tagged_ids( $tag_id ) {
		$rev = (int) get_option( 'pdwchs_tags_rev', 0 );
		$key = self::CACHE_PREFIX . $tag_id . '_r' . $rev;

		$ids = get_transient( $key );
		if ( false !== $ids ) {
			return $ids;
		}

		$ids = PDWCHS_Customer_Tags::pdwchs_get_customer_ids_by_tag( $tag_id );
		set_transient( $key, $ids, self::CACHE_TTL );

		return $ids;
	}
}
