<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pro side of the load-order-proof gate. Free's main file defines
 * pdwchs_is_pro_active() as apply_filters( 'pdwchs_is_pro_active', false );
 * this callback supplies the real Freemius license/trial answer. Guarded on
 * pchfwv_fs_loaded so it never force-initialises the add-on SDK when the Free
 * parent is absent.
 */
add_filter( 'pdwchs_is_pro_active', 'pdwchs_pro_gate_filter' );
function pdwchs_pro_gate_filter( $is_active ) {
	if ( ! function_exists( 'pchfwv_fs' ) || ! did_action( 'pchfwv_fs_loaded' ) ) {
		return $is_active;
	}
	return (bool) pchfwv_fs()->can_use_premium_code();
}

// The trial notice is Pro-owned now: hook it from here (Free no longer does).
add_action( 'admin_notices', 'pdwchs_render_trial_gate_notice' );
add_action( 'admin_init',    'pdwchs_handle_trial_notice_dismiss' );

/**
 * Handles the per-user per-version trial notice dismissal via a nonce-protected
 * GET redirect. Hooked to admin_init so the redirect fires before any output.
 */
if ( ! function_exists( 'pdwchs_handle_trial_notice_dismiss' ) ) {
	function pdwchs_handle_trial_notice_dismiss() {
		if ( ! isset( $_GET['pdwchs_dismiss_trial_notice'] ) ) {
			return;
		}
		if ( ! isset( $_GET['_wpnonce'] )
			|| ! wp_verify_nonce(
				sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ),
				'pdwchs_dismiss_trial_notice'
			)
		) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		update_user_meta(
			get_current_user_id(),
			'pdwchs_trial_notice_dismissed_' . PDWCHS_PRO_VERSION,
			'1'
		);
		wp_safe_redirect( remove_query_arg( array( 'pdwchs_dismiss_trial_notice', '_wpnonce' ) ) );
		exit;
	}
}

/**
 * Renders the trial-gate admin notice on plugin pages, the WP dashboard, and the
 * plugins screen. Shows two states: trial lapsed (yellow) or never trialled (blue).
 * Dismissible per-user per-plugin-version via a nonce-protected redirect.
 */
if ( ! function_exists( 'pdwchs_render_trial_gate_notice' ) ) {
	function pdwchs_render_trial_gate_notice() {
		if ( ! did_action( 'pchfwv_fs_loaded' ) ) {
			return;
		}
		if ( pdwchs_is_pro_active() ) {
			return;
		}
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}
		if ( get_user_meta(
			get_current_user_id(),
			'pdwchs_trial_notice_dismissed_' . PDWCHS_PRO_VERSION,
			true
		) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}
		$on_core_screen   = in_array( $screen->id, array( 'dashboard', 'plugins' ), true );
		$on_plugin_screen = false !== strpos( $screen->id, 'pd-woo-' );
		if ( ! $on_core_screen && ! $on_plugin_screen ) {
			return;
		}
		$dismiss_url = wp_nonce_url(
			add_query_arg( 'pdwchs_dismiss_trial_notice', '1' ),
			'pdwchs_dismiss_trial_notice'
		);
		if ( pchfwv_fs()->is_trial_utilized() ) {
			$type      = 'warning';
			$message   = __( 'Your trial has ended — upgrade to keep premium features.', 'puredevs-customer-history-for-woocommerce' );
			$cta_url   = pchfwv_fs()->get_upgrade_url();
			$cta_label = __( 'Upgrade now', 'puredevs-customer-history-for-woocommerce' );
		} else {
			$type      = 'info';
			$message   = __( 'Start your free 30-day trial to unlock all premium features.', 'puredevs-customer-history-for-woocommerce' );
			$cta_url   = pchfwv_fs()->get_trial_url();
			$cta_label = __( 'Start free trial', 'puredevs-customer-history-for-woocommerce' );
		}
		?>
		<div class="notice notice-<?php echo esc_attr( $type ); ?> pdwchs-trial-gate-notice">
			<p>
				<strong><?php esc_html_e( 'PureDevs Customer History', 'puredevs-customer-history-for-woocommerce' ); ?></strong>
				—
				<?php echo esc_html( $message ); ?>
				<a href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a>
				&nbsp;&middot;&nbsp;
				<a href="<?php echo esc_url( $dismiss_url ); ?>"><?php esc_html_e( 'Dismiss', 'puredevs-customer-history-for-woocommerce' ); ?></a>
			</p>
		</div>
		<?php
	}
}
