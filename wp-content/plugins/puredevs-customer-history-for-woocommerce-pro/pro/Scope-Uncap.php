<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pro scope uncap: lifts both Free visibility filters when the licensed Pro add-on boots.
 * 0 = unlimited per the filter contract (Free defaults: 100 customers, 90 days).
 */
class PDWCHS_Scope_Uncap {
	public function __construct() {
		add_filter( 'pdwchs_customer_list_limit', '__return_zero' );
		add_filter( 'pdwchs_data_window_days', '__return_zero' );
	}
}
