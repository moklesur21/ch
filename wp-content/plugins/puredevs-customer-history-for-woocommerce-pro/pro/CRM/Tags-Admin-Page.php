<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/Tags.php';

/**
 * Manual Tags — vocabulary management page.
 *
 * The one page Feature 15.10 adds (chunk R15-17): every tag with its usage
 * count, plus inline create, rename and delete. Everything else in the feature
 * mounts on an existing screen.
 *
 * Reads through PDWCHS_Customer_Tags's public static readers and never
 * instantiates it — a second instance would double-register R15-16B's five
 * AJAX hooks. Every mutation goes through that class's existing nonce- and
 * capability-guarded endpoints; this class adds no endpoint of its own.
 *
 * @since 3.1.1
 */
class PDWCHS_Tags_Admin_Page {

	/** Admin page slug this page and its assets belong to. */
	const PAGE_SLUG = 'pd-woo-manual-tags';

	public function __construct() {
		add_action( 'pdwchs_admin_menu_late', array( $this, 'pdwchs_register_menu' ) );        // H3.
		add_action( 'pdwchs_enqueue_admin_styles', array( $this, 'pdwchs_enqueue_styles' ) );   // H4.
		add_action( 'pdwchs_enqueue_admin_scripts', array( $this, 'pdwchs_enqueue_scripts' ) ); // H5.
	}

	// -------------------------------------------------------------------
	// Menu
	// -------------------------------------------------------------------

	/**
	 * H3 — register the Manual Tags submenu under Customer History.
	 *
	 * The capability is forwarded verbatim from the seam rather than
	 * hard-coded, so a change at admin/class-pd-customer-history-admin.php:216
	 * is honoured automatically.
	 *
	 * The menu label is a plain translated string: markup inside a __() call
	 * hands translators HTML to break, and nothing here targets the menu item.
	 *
	 * @param string $capability Capability string passed by pdwchs_admin_menu_late.
	 * @return void
	 */
	public function pdwchs_register_menu( $capability ) {
		add_submenu_page(
			'pd-woo-dashboard',
			__( 'Manual Tags', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Manual Tags', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			self::PAGE_SLUG,
			array( $this, 'pdwchs_render_page' )
		);
	}

	// -------------------------------------------------------------------
	// Render
	// -------------------------------------------------------------------

	/**
	 * Page callback — the tag table.
	 *
	 * add_submenu_page() already gates the menu entry; this repeats the check
	 * as defence in depth so a direct URL hit cannot render the page.
	 *
	 * @return void
	 */
	public function pdwchs_render_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		// true takes the LEFT JOIN COUNT ... GROUP BY variant: one query for the
		// whole table, no N+1. The count column is the point of this page.
		$tags = PDWCHS_Customer_Tags::pdwchs_get_all_tags( true );

		require __DIR__ . '/templates/tag-management.php';
	}

	// -------------------------------------------------------------------
	// Assets
	// -------------------------------------------------------------------

	/**
	 * H4 — enqueue the management stylesheet on this page only.
	 *
	 * custom-style is declared as a dependency so these rules order after the
	 * plugin-wide admin styles they sit alongside.
	 *
	 * @return void
	 */
	public function pdwchs_enqueue_styles() {
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		wp_enqueue_style(
			'pdwchs-tags-admin-css',
			PDWCHS_PRO_PLUGIN_URL . 'admin/css/pdwchs-tags-admin.css',
			array( 'custom-style' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION
		);
	}

	/**
	 * H5 — enqueue and localize the management script on this page only.
	 *
	 * The localized object carries no customerId: this page acts on tag
	 * definitions, not on a customer, and the key would invite a handler that
	 * does not exist. That is why it is a separate object from the profile
	 * panel's.
	 *
	 * The nonce action must match the five check_ajax_referer() calls in
	 * pro/CRM/Tags.php exactly — a mismatch presents as "the buttons do
	 * nothing", a bare 403 with an empty console.
	 *
	 * @return void
	 */
	public function pdwchs_enqueue_scripts() {
		if ( ! isset( $_GET['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( self::PAGE_SLUG !== $page ) {
			return;
		}

		wp_enqueue_script(
			'pdwchs-tags-admin-js',
			PDWCHS_PRO_PLUGIN_URL . 'admin/js/pdwchs-tags-admin.js',
			array( 'jquery' ),
			PDWCHS_CUSTOMER_HISTORY_VERSION,
			true
		);
		wp_localize_script( 'pdwchs-tags-admin-js', 'pdwchs_tags_admin_vars', array(
			'ajaxurl' => admin_url( 'admin-ajax.php' ),
			'nonce'   => wp_create_nonce( 'pdwchs_tags_nonce' ),
			// The labels below the first four are what renderRow() and the
			// inline rename state write into the DOM; without them the script
			// would ship untranslated English into a translated page.
			'i18n'    => array(
				/* translators: %d: number of customers currently carrying the tag. */
				'confirmDelete' => __( 'Delete this tag? This removes it from %d customers.', 'puredevs-customer-history-for-woocommerce' ),
				'emptyName'     => __( 'Please enter a tag name first.', 'puredevs-customer-history-for-woocommerce' ),
				'renamePrompt'  => __( 'Enter the new tag name', 'puredevs-customer-history-for-woocommerce' ),
				'errorFallback' => __( 'Something went wrong. Please try again.', 'puredevs-customer-history-for-woocommerce' ),
				/* translators: %s: the existing tag's display name. */
				'alreadyExists' => __( 'A tag named %s already exists.', 'puredevs-customer-history-for-woocommerce' ),
				'rename'        => __( 'Rename', 'puredevs-customer-history-for-woocommerce' ),
				'delete'        => __( 'Delete', 'puredevs-customer-history-for-woocommerce' ),
				'save'          => __( 'Save', 'puredevs-customer-history-for-woocommerce' ),
				'cancel'        => __( 'Cancel', 'puredevs-customer-history-for-woocommerce' ),
			),
		) );
	}
}
