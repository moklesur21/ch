<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fired during plugin deactivation
 *
 * @link       https://puredevs.com/
 * @since      1.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/includes
 * @author     PureDevs <admin@puredevs.com>
 */
class PD_Customer_History_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function pdwchs_deactivate() {
		// Legacy weekly report hooks
		wp_clear_scheduled_hook( 'pdwchs_generate_weekly_orders_report' );
		wp_clear_scheduled_hook( 'pdwchs_generate_weekly_searches_report' );
		wp_clear_scheduled_hook( 'pdwchs_generate_weekly_most_viewed_report' );
		wp_clear_scheduled_hook( 'pdwchs_generate_weekly_reports' );
	}

}
