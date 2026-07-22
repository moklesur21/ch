<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PDWCHS_Segment_Cap {
	public function __construct() {
		$this->pdwchs_register_hooks();
	}
	public function pdwchs_register_hooks() {
		add_filter( 'pdwchs_segment_creation_cap', array( $this, 'pdwchs_remove_cap' ) );
	}
	public function pdwchs_remove_cap() {
		return PHP_INT_MAX;
	}
}
