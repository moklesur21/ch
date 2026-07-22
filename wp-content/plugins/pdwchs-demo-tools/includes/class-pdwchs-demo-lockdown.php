<?php
/**
 * Demo-site lockdown: a public read-only viewer user for the live demo.
 *
 * The product plugin gates every admin screen AND every write action behind the
 * single capability `manage_woocommerce` (no read/write split, not filterable).
 * A user who can view the analytics can therefore also mutate data. Read-only is
 * enforced here instead: the demo role keeps `manage_woocommerce` so it can SEE
 * everything, and this module blocks the write paths, keyed off the role.
 *
 * The entire module is inert unless wp-config.php defines:
 *
 *   define( 'PDWCHS_DEMO_LOCKDOWN', true );
 *
 * Optional:
 *   define( 'PDWCHS_DEMO_USER_PASS', '...' );  // demo user password (default demo1234)
 *
 * NEVER define PDWCHS_DEMO_LOCKDOWN on a real customer site.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PDWCHS_Demo_Lockdown {

	/** Custom role slug for the read-only demo viewer. */
	const ROLE = 'pdwchs_demo_viewer';

	/** Login of the canonical demo user. */
	const DEMO_LOGIN = 'demo';

	/** Bumped when role caps / provisioning change, to re-run setup once. */
	const PROVISION_VERSION = 1;

	/** Option storing the last-provisioned version (non-autoloaded). */
	const PROVISION_OPTION = 'pdwchs_demo_lockdown_ver';

	/**
	 * admin-ajax actions a demo user is allowed to call. These are the plugin's
	 * read/getter endpoints that power the dashboards. Any NEW read endpoint must
	 * be added here or it will break for the demo user. Everything else is blocked.
	 */
	const ALLOWED_AJAX = array(
		// WordPress internals needed for a normal admin session.
		'heartbeat',
		'wp-auth-check',
		'closed-postboxes',
		'meta-box-order',
		'hidden-columns',
		// Segments (read).
		'pdwchs_get_segments_list',
		// Intelligence / RFM / health / lifecycle / churn (read).
		'pdwchs_get_rfm_distribution',
		'pdwchs_get_health_distribution',
		'pdwchs_get_declining_health',
		'pdwchs_get_lifecycle_funnel',
		'pdwchs_get_at_risk_alert',
		'pdwchs_get_churn_distribution',
		'pdwchs_refresh_churn_widget',
		'pdwchs_get_at_risk_customers',
		'pdwchs_get_churn_alerts',
		// Behavioral (read).
		'pdwchs_behavioral_segments_data',
		'pdwchs_filter_by_behavior',
		// Product reports (read).
		'pdwchs_get_product_revenue_over_time',
		'pdwchs_get_product_correlations',
		'pdwchs_get_revenue_patterns',
		// Revenue (read).
		'pdwchs_get_revenue_over_time',
		'pdwchs_get_revenue_overview',
		'pdwchs_get_new_vs_returning_revenue',
		// Data integrity (read/compute only).
		'pdwchs_run_data_integrity_check',
		'pdwchs_get_data_integrity_details',
		// Funnel (read).
		'pdwchs_get_aggregate_funnel',
		'pdwchs_get_funnel_over_time',
	);

	/**
	 * admin-post actions a demo user may run despite being writes-by-method.
	 * Both are nonce-checked, produce a CSV download of fake demo data, and mutate
	 * nothing.
	 */
	const ALLOWED_ADMIN_POST = array(
		'pdwchs_export_segment_csv',
	);

	/**
	 * Register all hooks. Called from the main plugin file only when
	 * PDWCHS_DEMO_LOCKDOWN is true.
	 */
	public static function init() {
		// Provisioning (role + demo user), runs once per PROVISION_VERSION.
		add_action( 'init', array( __CLASS__, 'maybe_provision' ) );

		// Login allowlist + hardening.
		add_filter( 'authenticate', array( __CLASS__, 'restrict_login' ), 99, 1 );
		add_filter( 'allow_password_reset', array( __CLASS__, 'filter_password_reset' ), 10, 2 );
		add_filter( 'xmlrpc_enabled', '__return_false' );
		add_filter( 'wp_is_application_passwords_available', '__return_false' );
		add_filter( 'login_redirect', array( __CLASS__, 'login_redirect' ), 10, 3 );

		// Defense-in-depth: strip dangerous caps from the demo user.
		add_filter( 'user_has_cap', array( __CLASS__, 'strip_caps' ), 10, 4 );

		// Write blocking, split by request context.
		add_filter( 'rest_pre_dispatch', array( __CLASS__, 'block_rest_writes' ), 10, 3 );
		// Priority 0 so we run before the product plugin's own admin_init handlers
		// (e.g. the cap-less CSV import at default priority 10).
		add_action( 'admin_init', array( __CLASS__, 'block_admin_writes' ), 0 );

		// Trim the admin surface for the demo user.
		add_action( 'admin_menu', array( __CLASS__, 'trim_menus' ), 999 );
		add_action( 'admin_bar_menu', array( __CLASS__, 'trim_admin_bar' ), 999 );

		// Read-only banner.
		add_action( 'admin_notices', array( __CLASS__, 'read_only_notice' ) );
	}

	/* --------------------------------------------------------------------- */
	/* Provisioning                                                          */
	/* --------------------------------------------------------------------- */

	/** Create/repair the demo role and user once per provisioning version. */
	public static function maybe_provision() {
		if ( (int) get_option( self::PROVISION_OPTION ) === self::PROVISION_VERSION ) {
			return;
		}

		self::provision_role();
		self::provision_user();

		update_option( self::PROVISION_OPTION, self::PROVISION_VERSION, false );
	}

	/** (Re)create the read-only viewer role with exactly the caps it needs. */
	private static function provision_role() {
		remove_role( self::ROLE );

		add_role(
			self::ROLE,
			'Demo Viewer (read-only)',
			array(
				'read'                    => true,
				// Grants visibility into the product plugin's screens.
				'manage_woocommerce'      => true,
				// Distinct cap; needed for the WooCommerce Analytics menu.
				'view_woocommerce_reports' => true,
			)
		);
	}

	/** Create the demo user, or repair its role/password if it already exists. */
	private static function provision_user() {
		$password = ( defined( 'PDWCHS_DEMO_USER_PASS' ) && PDWCHS_DEMO_USER_PASS )
			? PDWCHS_DEMO_USER_PASS
			: 'demo1234';

		$user = get_user_by( 'login', self::DEMO_LOGIN );

		if ( $user ) {
			// Repair role in case it drifted; refresh password to the configured one.
			$user->set_role( self::ROLE );
			wp_set_password( $password, $user->ID );
			return;
		}

		wp_insert_user(
			array(
				'user_login'   => self::DEMO_LOGIN,
				'user_pass'    => $password,
				'user_email'   => 'demo@ch.puredevs.com',
				'display_name' => 'Demo Viewer',
				'role'         => self::ROLE,
			)
		);
	}

	/* --------------------------------------------------------------------- */
	/* Login control                                                         */
	/* --------------------------------------------------------------------- */

	/** True if the given user object is our read-only demo viewer. */
	private static function is_demo_user( $user ) {
		return ( $user instanceof WP_User )
			&& in_array( self::ROLE, (array) $user->roles, true );
	}

	/** True if the CURRENT request is being made by the demo viewer. */
	private static function current_is_demo() {
		return self::is_demo_user( wp_get_current_user() );
	}

	/**
	 * Only administrators and the demo user may log in. Runs late (priority 99),
	 * after core resolves the user from username/password, and passes any prior
	 * WP_Error straight through.
	 *
	 * @param WP_User|WP_Error|null $user
	 * @return WP_User|WP_Error|null
	 */
	public static function restrict_login( $user ) {
		if ( ! ( $user instanceof WP_User ) ) {
			return $user; // Already an error / not yet resolved — leave it be.
		}

		if ( user_can( $user, 'administrator' ) || self::is_demo_user( $user ) ) {
			return $user;
		}

		return new WP_Error(
			'pdwchs_demo_locked',
			__( 'This is a read-only demo site. Only the demo account and administrators can sign in.', 'pdwchs-demo-tools' )
		);
	}

	/** Disable password resets for everyone except administrators. */
	public static function filter_password_reset( $allow, $user_id ) {
		return user_can( $user_id, 'manage_options' ) ? $allow : false;
	}

	/** Send the demo user to the plugin dashboard after login. */
	public static function login_redirect( $redirect_to, $requested, $user ) {
		if ( self::is_demo_user( $user ) ) {
			return admin_url( 'admin.php?page=pd-woo-dashboard' );
		}
		return $redirect_to;
	}

	/* --------------------------------------------------------------------- */
	/* Capability hardening                                                  */
	/* --------------------------------------------------------------------- */

	/**
	 * Defense-in-depth: even if some plugin grants the demo role a dangerous cap,
	 * strip it. The role itself never has these, but this guarantees it.
	 */
	public static function strip_caps( $allcaps, $caps, $args, $user ) {
		if ( ! self::is_demo_user( $user ) ) {
			return $allcaps;
		}

		$deny_exact = array(
			'edit_users', 'delete_users', 'create_users', 'list_users', 'promote_users',
			'edit_plugins', 'install_plugins', 'activate_plugins', 'delete_plugins', 'update_plugins',
			'edit_themes', 'install_themes', 'switch_themes', 'delete_themes', 'update_themes',
			'edit_files', 'manage_options', 'unfiltered_html', 'export', 'import',
			'edit_shop_orders', 'delete_shop_orders', 'edit_products', 'delete_products',
		);
		foreach ( $deny_exact as $cap ) {
			unset( $allcaps[ $cap ] );
		}

		return $allcaps;
	}

	/* --------------------------------------------------------------------- */
	/* Write blocking                                                        */
	/* --------------------------------------------------------------------- */

	/**
	 * Block any non-read HTTP method on the REST API for the demo user. This is
	 * load-bearing: `manage_woocommerce` is the permission callback for
	 * PUT /wc/v3/settings/* and system_status/tools/*, callable from any admin
	 * page's REST nonce.
	 *
	 * The demo-data refresh route (pdwchs-demo/v1) authenticates by token and runs
	 * as user 0, so it never has the demo role and is unaffected.
	 *
	 * @param mixed           $result
	 * @param WP_REST_Server  $server
	 * @param WP_REST_Request $request
	 * @return mixed
	 */
	public static function block_rest_writes( $result, $server, $request ) {
		if ( ! self::current_is_demo() ) {
			return $result;
		}

		$method = $request->get_method();
		if ( in_array( $method, array( 'GET', 'HEAD', 'OPTIONS' ), true ) ) {
			return $result;
		}

		return new WP_Error(
			'pdwchs_demo_readonly',
			__( 'This is a read-only demo. Changes are disabled.', 'pdwchs-demo-tools' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * Guards non-REST writes for the demo user. Runs at admin_init priority 0.
	 * Branches: admin-ajax (action allowlist) vs. everything else (block writes by
	 * HTTP method, plus the GET-based session trash/restore param).
	 */
	public static function block_admin_writes() {
		if ( ! self::current_is_demo() ) {
			return;
		}

		if ( wp_doing_ajax() ) {
			self::guard_ajax();
			return;
		}

		self::guard_admin_request();
	}

	/** admin-ajax arm: allow only the read-only action allowlist. */
	private static function guard_ajax() {
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';

		if ( in_array( $action, self::ALLOWED_AJAX, true ) ) {
			return;
		}

		wp_send_json_error(
			array( 'message' => __( 'This is a read-only demo. Changes are disabled.', 'pdwchs-demo-tools' ) ),
			403
		);
	}

	/** Non-AJAX admin arm: block write-method form posts and GET-based session writes. */
	private static function guard_admin_request() {
		// GET-based session trash/restore on the sessions/searches/trash pages.
		if ( isset( $_GET['act'] ) ) {
			$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : '';
			$targets = array( 'pd-woo-sessions', 'pd-woo-searches', 'pd-woo-trash' );
			if ( in_array( $page, $targets, true ) ) {
				self::deny();
			}
		}

		$method = isset( $_SERVER['REQUEST_METHOD'] ) ? strtoupper( $_SERVER['REQUEST_METHOD'] ) : 'GET';
		if ( in_array( $method, array( 'GET', 'HEAD', 'OPTIONS' ), true ) ) {
			return;
		}

		// Allow the read-only CSV export(s) that flow through admin-post.php.
		$action = isset( $_REQUEST['action'] ) ? sanitize_key( wp_unslash( $_REQUEST['action'] ) ) : '';
		if ( in_array( $action, self::ALLOWED_ADMIN_POST, true ) ) {
			return;
		}

		self::deny();
	}

	/** Branded refusal for a blocked write. */
	private static function deny() {
		wp_die(
			esc_html__( 'This is a read-only demo site. Changes are disabled.', 'pdwchs-demo-tools' ),
			esc_html__( 'Read-only demo', 'pdwchs-demo-tools' ),
			array( 'response' => 403, 'back_link' => true )
		);
	}

	/* --------------------------------------------------------------------- */
	/* Admin surface trimming                                                */
	/* --------------------------------------------------------------------- */

	/**
	 * Hide menus the demo user shouldn't roam. Keep the product plugin's screens,
	 * plus WooCommerce Orders + Analytics and the Products list.
	 */
	public static function trim_menus() {
		if ( ! self::current_is_demo() ) {
			return;
		}

		// Top-level menus to remove entirely.
		$remove_top = array(
			'edit.php',                 // Posts
			'edit-comments.php',        // Comments
			'themes.php',               // Appearance
			'plugins.php',              // Plugins
			'users.php',                // Users
			'tools.php',                // Tools
			'options-general.php',      // Settings
		);
		foreach ( $remove_top as $slug ) {
			remove_menu_page( $slug );
		}

		// WooCommerce submenus to remove (keep Orders + the rest of the read UI).
		remove_submenu_page( 'woocommerce', 'wc-settings' );
		remove_submenu_page( 'woocommerce', 'wc-status' );
		remove_submenu_page( 'woocommerce', 'wc-addons' );
	}

	/** Strip creation/edit shortcuts from the admin bar for the demo user. */
	public static function trim_admin_bar( $wp_admin_bar ) {
		if ( ! self::current_is_demo() ) {
			return;
		}
		foreach ( array( 'new-content', 'comments', 'wp-logo', 'updates' ) as $node ) {
			$wp_admin_bar->remove_node( $node );
		}
	}

	/** Persistent read-only banner on every admin page for the demo user. */
	public static function read_only_notice() {
		if ( ! self::current_is_demo() ) {
			return;
		}
		echo '<div class="notice notice-info"><p><strong>'
			. esc_html__( 'Read-only demo', 'pdwchs-demo-tools' ) . '</strong> &mdash; '
			. esc_html__( 'you can explore all the data, but changes are disabled.', 'pdwchs-demo-tools' )
			. '</p></div>';
	}
}
