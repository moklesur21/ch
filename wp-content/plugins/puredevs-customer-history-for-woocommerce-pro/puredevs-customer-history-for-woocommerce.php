<?php

/**
 * @link              https://puredevs.com/
 * @since             2.0.0
 * @package           PD_Customer_History
 *
 * @wordpress-plugin
 * Plugin Name: PureDevs Customer History for WooCommerce Pro
 * Plugin URI:        https://puredevs.com/plugin/puredevs-customer-history-for-woocommerce/
 * Description:       Customer intelligence for WooCommerce: customer profiles, order history, session & search tracking, segments, and a customer-first dashboard.
 * Version:           3.0.0
 * Update URI: https://api.freemius.com
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            PureDevs
 * Author URI:        https://puredevs.com/
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       puredevs-customer-history-for-woocommerce
 * Domain Path:       /languages
 * Requires Plugins:  woocommerce
 * WC requires at least: 7.0
 * WC tested up to:   9.8
 */
if ( !defined( 'WPINC' ) ) {
    die;
}
if ( function_exists( 'pchfwv_fs' ) ) {
    pchfwv_fs()->set_basename( true, __FILE__ );
} else {
    require_once __DIR__ . '/vendor/autoload.php';
    /**
     * Premium-build detection. The Pro build ships the pro/ directory; the free
     * build (WordPress.org) has it stripped by build.sh. This single flag drives
     * both the Freemius is_premium setting and the pro/ loader below, so neither
     * needs per-build editing.
     */
    if ( !defined( 'PDWCHS_IS_PREMIUM' ) ) {
        define( 'PDWCHS_IS_PREMIUM', file_exists( __DIR__ . '/pro/pro.php' ) );
    }
    add_action( 'before_woocommerce_init', function () {
        if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
            \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
        }
    } );
    if ( !function_exists( 'pchfwv_fs' ) ) {
        // Create a helper function for easy SDK access.
        function pchfwv_fs() {
            global $pchfwv_fs;
            if ( !isset( $pchfwv_fs ) ) {
                // SDK auto-loaded through Composer.
                $pchfwv_fs = fs_dynamic_init( array(
                    'id'               => '7511',
                    'slug'             => 'puredevs-customer-history-for-woocommerce',
                    'premium_slug'     => 'puredevs-customer-history-for-woocommerce-pro',
                    'type'             => 'plugin',
                    'public_key'       => 'pk_e6c1736a09c5350195f3ba55a1493',
                    'is_premium'       => true,
                    'is_premium_only'  => false,
                    'premium_suffix'   => 'Pro',
                    'has_paid_plans'   => true,
                    'is_org_compliant' => true,
                    'trial'            => array(
                        'days'               => 30,
                        'is_require_payment' => false,
                    ),
                    'menu'             => array(
                        'slug'    => 'pd-woo-dashboard',
                        'support' => false,
                        'pricing' => true,
                    ),
                    'is_live'          => true,
                ) );
            }
            return $pchfwv_fs;
        }

        // Init Freemius.
        pchfwv_fs();
        // Signal that the parent SDK was initiated (the Pro add-on waits on this).
        do_action( 'pchfwv_fs_loaded' );
    }
    pchfwv_fs()->add_action( 'after_uninstall', 'pchfwv_fs_uninstall_cleanup' );
    if ( !function_exists( 'pchfwv_fs_uninstall_cleanup' ) ) {
        /**
         * Uninstall cleanup for FREE-owned options and tables only.
         * Pro tables/options are cleaned by the Pro add-on's own uninstall hook.
         */
        function pchfwv_fs_uninstall_cleanup() {
            $options = array(
                'pd-woo-results_per_page',
                'pd-woo-default_save_admin_session',
                'pd-woo-hide_users_with_no_orders',
                'pd-woo-show_bot_sessions',
                'pd-woo-generate_weekly_report',
                'pd-woo-send_weekly_report_to'
            );
            foreach ( $options as $option_name ) {
                delete_option( $option_name );
            }
            global $wpdb;
            $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pd_woo_customer_history_sessions" );
            $wpdb->query( "DROP TABLE IF EXISTS {$wpdb->prefix}pdwchs_segments" );
        }

    }
    /**
     * Load-order-proof Pro gate. The Pro add-on registers a callback on the
     * 'pdwchs_is_pro_active' filter (wrapping its Freemius license check); in a
     * Free-only install the filter has no listeners and this always returns false.
     * Free code contains no license logic of its own.
     */
    if ( !function_exists( 'pdwchs_is_pro_active' ) ) {
        function pdwchs_is_pro_active() {
            return (bool) apply_filters( 'pdwchs_is_pro_active', false );
        }

    }
    if ( !function_exists( 'pdwchs_woo_customer_history_admin_notice' ) ) {
        /**
         * Show an admin notice if WooCommerce is deactivated.
         *
         * @return void
         */
        function pdwchs_woo_customer_history_admin_notice() {
            ?>
        <div class="error">
            <p><?php 
            esc_html_e( 'PureDevs Customer History for WooCommerce is enabled but not effective. In order to work it requires WooCommerce.', 'puredevs-customer-history-for-woocommerce' );
            ?></p>
        </div>
        <?php 
        }

    }
    add_action( 'plugins_loaded', 'pdwchs_woo_customer_history_install', 12 );
    /**
     * Currently plugin version.
     * Start at version 1.0.0 and use SemVer - https://semver.org
     * Rename this for your plugin and update it as you release new versions.
     */
    define( 'PDWCHS_CUSTOMER_HISTORY_VERSION', '3.0.0' );
    define( 'PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
    define( 'PDWCHS_CUSTOMER_HISTORY_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
    define( 'PDWCHS_CUSTOMER_HISTORY_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
    define( 'PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR', PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'admin/partials/templates' );
    /**
     * Load the Pro module when present (Pro build only). The free build has no pro/
     * directory, so this is skipped and only free features run. Pro self-registers
     * on the existing pdwchs_* action/filter seams — no other wiring is needed here.
     */
    if ( PDWCHS_IS_PREMIUM ) {
        require_once __DIR__ . '/pro/pro.php';
    }
    /**
     * Runs code during plugin activation.
     *
     * @return void
     */
    function pdwchs_activate_customer_history() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-pd-customer-history-activator.php';
        PD_Customer_History_Activator::pdwchs_activate();
    }

    /**
     * Runs code during plugin deactivation.
     *
     * @return void
     */
    function pdwchs_deactivate_customer_history() {
        require_once plugin_dir_path( __FILE__ ) . 'includes/class-pd-customer-history-deactivator.php';
        PD_Customer_History_Deactivator::pdwchs_deactivate();
    }

    register_activation_hook( __FILE__, 'pdwchs_activate_customer_history' );
    register_deactivation_hook( __FILE__, 'pdwchs_deactivate_customer_history' );
    /**
     * The core plugin class that is used to define internationalization,
     * admin-specific hooks, and public-facing site hooks.
     */
    require plugin_dir_path( __FILE__ ) . 'includes/class-pd-customer-history.php';
    /**
     * Begins execution of the plugin.
     *
     * @return void
     */
    function pdwchs_run_customer_history() {
        global $pd_customer_history;
        $pd_customer_history = new PD_Customer_History();
        $pd_customer_history->pdwchs_run();
    }

    /**
     * Returns the main plugin instance.
     *
     * This function provides access to the main plugin class instance,
     * allowing templates and other code to access plugin functionality.
     *
     * @since    4.0.0
     * @return   PD_Customer_History|null    The main plugin instance or null if not initialized.
     */
    function pdwchs_get_plugin_instance() {
        global $pd_customer_history;
        return ( isset( $pd_customer_history ) ? $pd_customer_history : null );
    }

    /**
     * Installs the plugin and checks for WooCommerce dependency.
     *
     * @return void
     */
    function pdwchs_woo_customer_history_install() {
        if ( !function_exists( 'WC' ) ) {
            add_action( 'admin_notices', 'pdwchs_woo_customer_history_admin_notice' );
            // WooCommerce is absent — prevent Freemius from redirecting to pd-woo-dashboard
            // (that page is never registered without WC, so the redirect yields a white "not allowed" screen).
            pchfwv_fs()->add_filter( 'redirect_on_activation', '__return_false' );
        } else {
            // Check for version upgrades on every load to handle plugin updates
            require_once plugin_dir_path( __FILE__ ) . 'includes/class-pd-customer-history-activator.php';
            PD_Customer_History_Activator::pdwchs_check_version_upgrade();
            pdwchs_run_customer_history();
        }
    }

}
// end if ( function_exists( 'pchfwv_fs' ) ) else