<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Free-side upsell UI: screenshot-based locked sections + dismissible banners.
 *
 * wp.org compliance (Guidelines 5 & 11):
 * - Pure advertisement: no disabled code paths, no license checks in Free.
 * - Banners are per-user dismissible (user meta) and capped at ONE per page.
 * - Locked sections are static previews of features that ship ONLY in the
 *   separate Pro add-on plugin (not code locked inside this plugin).
 */
class PDWCHS_Upsell {

	/** Tracks whether a banner already rendered on this page load (R3). */
	private static $banner_rendered = false;

	public function __construct() {
		add_action( 'admin_init', array( $this, 'pdwchs_handle_dismiss' ) );
	}

	/** Registry of locked-section previews. Image may be absent (text-only fallback). */
	public static function pdwchs_features() {
		return array(
			'rfm'        => array(
				'title' => __( 'RFM Segment', 'puredevs-customer-history-for-woocommerce' ),
				'desc'  => __( 'See at a glance whether this customer is a Champion, Loyal, At Risk, or Lost.', 'puredevs-customer-history-for-woocommerce' ),
				'img'   => 'rfm.png',
			),
			'health'     => array(
				'title' => __( 'Customer Health Score', 'puredevs-customer-history-for-woocommerce' ),
				'desc'  => __( 'A 0–100 score combining recency, frequency, spending, and engagement.', 'puredevs-customer-history-for-woocommerce' ),
				'img'   => 'health.png',
			),
			'churn'      => array(
				'title' => __( 'Churn Risk', 'puredevs-customer-history-for-woocommerce' ),
				'desc'  => __( 'Know who is about to stop buying — before they do.', 'puredevs-customer-history-for-woocommerce' ),
				'img'   => 'churn.png',
			),
			'behavioral' => array(
				'title' => __( 'Behavioral Tags', 'puredevs-customer-history-for-woocommerce' ),
				'desc'  => __( 'Automatic badges like Bargain Hunter, Cart Abandoner, and Brand Loyal.', 'puredevs-customer-history-for-woocommerce' ),
				'img'   => 'behavioral.png',
			),
			'lifecycle'  => array(
				'title' => __( 'Lifecycle Stage', 'puredevs-customer-history-for-woocommerce' ),
				'desc'  => __( 'Where this customer sits in their journey: New, Active, Loyal, or Lapsed.', 'puredevs-customer-history-for-woocommerce' ),
				'img'   => 'lifecycle.png',
			),
			'sessions'   => array(
				'title' => __( 'Full Session History', 'puredevs-customer-history-for-woocommerce' ),
				'desc'  => __( 'Every visit, page view, and search this customer has made.', 'puredevs-customer-history-for-woocommerce' ),
				'img'   => 'sessions.png',
			),
		);
	}

	/**
	 * Ready-made Pro segments (advertisement only — names + descriptions, no logic).
	 * These auto-build in Pro from RFM + health scoring; shown as locked teasers in Free.
	 */
	public static function pdwchs_pro_segments() {
		return array(
			array(
				'title' => __( 'High Value At Risk', 'puredevs-customer-history-for-woocommerce' ),
				'desc'  => __( 'Big spenders who have gone quiet — win them back before they churn.', 'puredevs-customer-history-for-woocommerce' ),
			),
			array(
				'title' => __( 'Loyal Champions', 'puredevs-customer-history-for-woocommerce' ),
				'desc'  => __( 'Your most frequent, highest-value repeat buyers.', 'puredevs-customer-history-for-woocommerce' ),
			),
			array(
				'title' => __( 'Needs Attention', 'puredevs-customer-history-for-woocommerce' ),
				'desc'  => __( 'Once-active customers starting to slip toward lapse.', 'puredevs-customer-history-for-woocommerce' ),
			),
			array(
				'title' => __( 'Hibernating', 'puredevs-customer-history-for-woocommerce' ),
				'desc'  => __( 'Long-inactive customers worth a re-engagement push.', 'puredevs-customer-history-for-woocommerce' ),
			),
			array(
				'title' => __( 'High Health Declining', 'puredevs-customer-history-for-woocommerce' ),
				'desc'  => __( 'Healthy customers whose engagement is trending down.', 'puredevs-customer-history-for-woocommerce' ),
			),
		);
	}

	/** Upgrade URL — the single freemium product's pricing/checkout page. */
	public static function pdwchs_upgrade_url() {
		if ( function_exists( 'pchfwv_fs' ) ) {
			return pchfwv_fs()->get_upgrade_url();
		}
		// Freemius unavailable (should not happen): fall back to the dashboard pricing tab.
		return admin_url( 'admin.php?page=pd-woo-dashboard-pricing' );
	}

	/** Screenshot card: dimmed/blurred PNG + lock + title + desc + CTA. */
	public static function pdwchs_render_locked_section( $feature_key ) {
		$features = self::pdwchs_features();
		if ( ! isset( $features[ $feature_key ] ) ) {
			return;
		}
		// A free user is looking at a locked Pro preview — record it once (local only).
		pdwchs_record_first_cap_hit( 'locked_feature' );
		$feature  = $features[ $feature_key ];
		$img_path = PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'admin/img/pro-previews/' . $feature['img'];
		$img_url  = plugins_url( '../admin/img/pro-previews/' . $feature['img'], __FILE__ );
		$has_img  = file_exists( $img_path );
		?>
		<div class="pdwchs-locked-section">
			<?php if ( $has_img ) : ?>
				<div class="pdwchs-locked-preview">
					<img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo esc_attr( $feature['title'] ); ?>" />
					<div class="pdwchs-locked-overlay">
						<span class="dashicons dashicons-lock"></span>
					</div>
				</div>
			<?php endif; ?>
			<div class="pdwchs-locked-body">
				<h3><?php echo esc_html( $feature['title'] ); ?></h3>
				<p><?php echo esc_html( $feature['desc'] ); ?></p>
				<a href="<?php echo esc_url( self::pdwchs_upgrade_url() ); ?>" class="button pdwchs-upsell-cta" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'Unlock with Pro', 'puredevs-customer-history-for-woocommerce' ); ?>
				</a>
			</div>
		</div>
		<?php
	}

	/** Dismissible one-per-page banner. $banner_id keys the user-meta dismissal. */
	public static function pdwchs_render_banner( $banner_id, $message_html ) {
		if ( self::$banner_rendered || self::pdwchs_is_dismissed( $banner_id ) ) {
			return;
		}
		self::$banner_rendered = true;
		$dismiss_url = wp_nonce_url(
			add_query_arg( 'pdwchs_dismiss_upsell', sanitize_key( $banner_id ) ),
			'pdwchs_dismiss_upsell_' . $banner_id
		);
		?>
		<div class="notice notice-info pdwchs-upsell-banner">
			<p><?php echo wp_kses_post( $message_html ); ?></p>
			<p>
				<a href="<?php echo esc_url( self::pdwchs_upgrade_url() ); ?>" class="button pdwchs-upsell-cta" target="_blank" rel="noopener noreferrer">
					<?php esc_html_e( 'See what Pro adds', 'puredevs-customer-history-for-woocommerce' ); ?>
				</a>
				&nbsp;
				<a href="<?php echo esc_url( $dismiss_url ); ?>" class="pdwchs-upsell-dismiss">
					<?php esc_html_e( 'Dismiss', 'puredevs-customer-history-for-woocommerce' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	public static function pdwchs_is_dismissed( $banner_id ) {
		return (bool) get_user_meta( get_current_user_id(), 'pdwchs_upsell_dismissed_' . sanitize_key( $banner_id ), true );
	}

	/** admin_init: nonce-verified, capability-checked dismiss handler. */
	public function pdwchs_handle_dismiss() {
		if ( empty( $_GET['pdwchs_dismiss_upsell'] ) ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		$banner_id = sanitize_key( wp_unslash( $_GET['pdwchs_dismiss_upsell'] ) );
		check_admin_referer( 'pdwchs_dismiss_upsell_' . $banner_id );
		update_user_meta( get_current_user_id(), 'pdwchs_upsell_dismissed_' . $banner_id, 1 );
		wp_safe_redirect( remove_query_arg( array( 'pdwchs_dismiss_upsell', '_wpnonce' ) ) );
		exit;
	}
}
