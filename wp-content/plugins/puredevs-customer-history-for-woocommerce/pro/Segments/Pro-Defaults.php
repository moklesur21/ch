<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Seeds the 5 Pro-only default customer segments on plugin activation.
 *
 * Hooks onto H15 (pdwchs_register_pro_options) so seeding fires at activation
 * time, after the segments table is guaranteed to exist.
 *
 * @since 2.1.7
 */
class PDWCHS_Pro_Segments_Defaults {

	public function __construct() {
		add_action( 'pdwchs_register_pro_options', array( $this, 'pdwchs_seed' ) );
	}

	/**
	 * Insert the 5 Pro default segments if none exist yet (idempotent).
	 *
	 * @since 2.1.7
	 * @return void
	 */
	public function pdwchs_seed() {
		global $wpdb;
		$table = $wpdb->prefix . 'pdwchs_segments';

		$table_exists = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching
		if ( ! $table_exists ) {
			return;
		}

		$existing = (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE is_default = 1" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		if ( $existing > 0 ) {
			return;
		}

		$now = current_time( 'mysql' );

		$defaults = array(
			array(
				'name'        => 'High Value At Risk',
				'description' => '',
				'criteria'    => wp_json_encode( array( 'rfm_segment' => 'at_risk', 'total_spent' => 'greater_then', 'total_spent_input' => '500' ) ),
				'is_default'  => 1,
				'created_by'  => null,
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array(
				'name'        => 'Loyal Champions',
				'description' => '',
				'criteria'    => wp_json_encode( array( 'rfm_segment' => 'champions' ) ),
				'is_default'  => 1,
				'created_by'  => null,
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array(
				'name'        => 'Needs Attention',
				'description' => '',
				'criteria'    => wp_json_encode( array( 'rfm_segment' => 'needs_attention' ) ),
				'is_default'  => 1,
				'created_by'  => null,
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array(
				'name'        => 'Hibernating',
				'description' => '',
				'criteria'    => wp_json_encode( array( 'rfm_segment' => 'hibernating' ) ),
				'is_default'  => 1,
				'created_by'  => null,
				'created_at'  => $now,
				'updated_at'  => $now,
			),
			array(
				'name'        => 'High Health Declining',
				'description' => '',
				'criteria'    => wp_json_encode( array( 'health_score_range' => 'fair', 'health_trend' => 'down' ) ),
				'is_default'  => 1,
				'created_by'  => null,
				'created_at'  => $now,
				'updated_at'  => $now,
			),
		);

		$format = array( '%s', '%s', '%s', '%d', '%d', '%s', '%s' );

		foreach ( $defaults as $segment ) {
			$wpdb->insert( $table, $segment, $format ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
	}
}
