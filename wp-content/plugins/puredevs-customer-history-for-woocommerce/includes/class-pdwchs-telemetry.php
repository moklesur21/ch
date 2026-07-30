<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free-tier decision telemetry — entirely local, no data leaves the site.
 *
 * Answers two product questions ahead of the 1.5 free/Pro line decision:
 *   1. When was the plugin first activated on this site?  (pdwchs_first_activated_at)
 *   2. Which free-tier limit did the user run into first?  (pdwchs_first_cap_hit)
 *
 * Both are written **once** and never overwritten, so they record the *first*
 * occurrence rather than the most recent one. Values are stored in wp_options and
 * contain no customer data or PII. They are surfaced read-only under
 * Tools > Site Health > Info so a store owner (or support) can read them back
 * without database access.
 *
 * No external endpoint is involved. Pushing the aggregate signal to Freemius is
 * intentionally NOT implemented yet: SDK 2.13.4's Freemius::track_event() is enabled
 * per-client on Freemius's side ("the API will simply ignore your request based on the
 * plugin ID"), so it would be a silent no-op for this product until Freemius enables
 * custom events for it. When that happens, the correct guard is
 * `pchfwv_fs()->is_registered() && pchfwv_fs()->is_tracking_allowed()` — is_registered()
 * alone still returns true for a user who later opted out.
 *
 * @since 3.0.2
 */
class PDWCHS_Telemetry {

	/** Option holding the first-ever activation timestamp (Unix, UTC). */
	const OPTION_FIRST_ACTIVATED = 'pdwchs_first_activated_at';

	/** Option holding which free-tier cap was hit first. */
	const OPTION_FIRST_CAP_HIT = 'pdwchs_first_cap_hit';

	/** The only cap identifiers accepted by pdwchs_record_first_cap_hit(). */
	const CAPS = array( 'customer_cap', 'data_window', 'segment_cap', 'locked_feature' );

	public function __construct() {
		add_filter( 'debug_information', array( $this, 'pdwchs_site_health_info' ) );
	}

	/**
	 * Record the first-ever activation time. Set-once: never overwritten on
	 * re-activation or update, so the 7-day retention window stays anchored to the
	 * true first activation.
	 *
	 * @since 3.0.2
	 * @return void
	 */
	public static function pdwchs_record_first_activation() {
		if ( ! get_option( self::OPTION_FIRST_ACTIVATED ) ) {
			add_option( self::OPTION_FIRST_ACTIVATED, time() );
		}
	}

	/**
	 * Record which free-tier cap the user hit first. Set-once and free-users-only.
	 *
	 * Call this only at the moment a wall is actually reached — not merely when the
	 * cap is evaluated — otherwise every page load would register a hit.
	 *
	 * @since 3.0.2
	 * @param string $cap One of self::CAPS.
	 * @return void
	 */
	public static function pdwchs_record_first_cap_hit( string $cap ) {
		// Pro users have no caps to hit; nothing to learn from them here.
		if ( function_exists( 'pdwchs_is_pro_active' ) && pdwchs_is_pro_active() ) {
			return;
		}

		if ( ! in_array( $cap, self::CAPS, true ) ) {
			return;
		}

		// Set-once: the first wall the user met is the signal we want.
		if ( get_option( self::OPTION_FIRST_CAP_HIT ) ) {
			return;
		}

		add_option( self::OPTION_FIRST_CAP_HIT, $cap );
	}

	/**
	 * Expose both values read-only under Tools > Site Health > Info.
	 *
	 * @since 3.0.2
	 * @param array $info Site Health debug information.
	 * @return array
	 */
	public function pdwchs_site_health_info( $info ) {
		$first_activated = get_option( self::OPTION_FIRST_ACTIVATED );
		$first_cap_hit   = get_option( self::OPTION_FIRST_CAP_HIT );

		if ( $first_activated ) {
			$activated_display = sprintf(
				/* translators: 1: Formatted date and time, 2: Raw Unix timestamp */
				__( '%1$s (timestamp %2$s)', 'puredevs-customer-history-for-woocommerce' ),
				wp_date( 'Y-m-d H:i:s', (int) $first_activated ),
				(int) $first_activated
			);
		} else {
			$activated_display = __( 'Not recorded yet', 'puredevs-customer-history-for-woocommerce' );
		}

		$info['pdwchs_customer_history'] = array(
			'label'       => __( 'PureDevs Customer History', 'puredevs-customer-history-for-woocommerce' ),
			'description' => __( 'Local, non-identifying counters used to understand free-tier usage. This information stays on your server.', 'puredevs-customer-history-for-woocommerce' ),
			'fields'      => array(
				'version'           => array(
					'label' => __( 'Plugin version', 'puredevs-customer-history-for-woocommerce' ),
					'value' => defined( 'PDWCHS_CUSTOMER_HISTORY_VERSION' ) ? PDWCHS_CUSTOMER_HISTORY_VERSION : __( 'Unknown', 'puredevs-customer-history-for-woocommerce' ),
				),
				'tier'              => array(
					'label' => __( 'Active tier', 'puredevs-customer-history-for-woocommerce' ),
					'value' => ( function_exists( 'pdwchs_is_pro_active' ) && pdwchs_is_pro_active() )
						? __( 'Pro', 'puredevs-customer-history-for-woocommerce' )
						: __( 'Free', 'puredevs-customer-history-for-woocommerce' ),
				),
				'first_activated_at' => array(
					'label' => __( 'First activated', 'puredevs-customer-history-for-woocommerce' ),
					'value' => $activated_display,
				),
				'first_cap_hit'     => array(
					'label' => __( 'First free-tier limit reached', 'puredevs-customer-history-for-woocommerce' ),
					'value' => $first_cap_hit ? $first_cap_hit : __( 'None reached yet', 'puredevs-customer-history-for-woocommerce' ),
				),
			),
		);

		return $info;
	}
}
