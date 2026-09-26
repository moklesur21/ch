<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Marketing-ready column layouts for the segment CSV export.
 *
 * Registers two presets on pdwchs_segment_export_presets: a Klaviyo layout and a Mailchimp layout.
 * Each is a label and an ordered map of field key => header, drawn entirely from the row bag
 * PDWCHS_Segments_Export builds. This class runs no query, computes no value and edits no free file.
 *
 * The two differ in exactly one place — the header on the email column, which Klaviyo documents as
 * "Email" and Mailchimp as "Email Address". That single difference is why there are two presets and
 * not one; both vendors key their import on that column and both are particular about its spelling.
 *
 * This produces a FILE. There is no API call here and there must never be one: the development plan
 * (:303) and the cut list both put external-service syncs outside this plugin permanently. No
 * wp_remote_*, no cURL, no API key, no OAuth, no vendor hostname.
 *
 * No consent or subscription-status column: the plugin does not track marketing consent, both
 * vendors treat such a column as authoritative on import, and a fabricated value there would be
 * worse than an absent one. The merchant sets subscription status during their own import.
 *
 * @since 3.1.2
 */
class PDWCHS_Segment_Export_Presets {

	/**
	 * One registration, and no more.
	 *
	 * The seam passes only $presets, so there is no accepted-args count to declare — and no action,
	 * no wp_ajax_, no admin_menu, no admin_post_, no cron and no settings seam to register on.
	 * Pro-Defaults.php:18-20 is the same shape: the constructor registers, the callback does the work.
	 *
	 * @since 3.1.2
	 */
	public function __construct() {
		add_filter( 'pdwchs_segment_export_presets', array( $this, 'pdwchs_register_presets' ) );
	}

	/**
	 * Merge the vendor layouts into the registry.
	 *
	 * Every field key below must exist in the row bag PDWCHS_Segments_Export::pdwchs_get_export_rows()
	 * builds; an unknown key renders an empty column rather than an error, which would be a silent
	 * degradation, so the unit test asserts the intersection instead of relying on that fallback.
	 *
	 * The header strings are the vendors' documented import column names and are deliberately NOT
	 * translated: they are matched by the receiving system, not read by the merchant. Translating
	 * "Email Address" would break a German store's Mailchimp import. The labels ARE translated, because
	 * those are read by a human in the selector.
	 *
	 * Direct assignment rather than array_merge(), so that replacing a preset already registered under
	 * the same key is visible, and so 'default' keeps its position at the front of the registry — which
	 * is the order the selector renders in.
	 *
	 * @since 3.1.2
	 *
	 * @param array $presets Existing registry, keyed by preset slug.
	 * @return array
	 */
	public function pdwchs_register_presets( $presets ) {
		// An upstream listener can hand a filter anything. Returning the input unchanged is the
		// correct degradation.
		if ( ! is_array( $presets ) ) {
			return $presets;
		}

		$presets['klaviyo'] = array(
			'label'   => __( 'Klaviyo', 'puredevs-customer-history-for-woocommerce' ),
			// DO NOT wrap these headers in __(). They are parsed by Klaviyo on import, not read by
			// the store owner: a translated 'Email' is a file Klaviyo cannot map, and the merchant
			// gets a broken import with no visible cause. Email is first because Klaviyo's import
			// documentation specifies it as the first column.
			'columns' => array(
				'user_email'      => 'Email',
				'first_name'      => 'First Name',
				'last_name'       => 'Last Name',
				'orders'          => 'Total Orders',
				'total_spent'     => 'Total Spent',
				'last_order_date' => 'Last Order Date',
				'rfm_segment'     => 'RFM Segment',
			),
		);

		$presets['mailchimp'] = array(
			'label'   => __( 'Mailchimp', 'puredevs-customer-history-for-woocommerce' ),
			// DO NOT wrap these headers in __(), for the same reason as above. 'Email Address' is
			// Mailchimp's required field for an email contact import and is the one and only
			// difference between this layout and the Klaviyo one.
			'columns' => array(
				'user_email'      => 'Email Address',
				'first_name'      => 'First Name',
				'last_name'       => 'Last Name',
				'orders'          => 'Total Orders',
				'total_spent'     => 'Total Spent',
				'last_order_date' => 'Last Order Date',
				'rfm_segment'     => 'RFM Segment',
			),
		);

		return $presets;
	}
}
