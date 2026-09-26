<?php
/**
 * Removes everything seed-demo-data.php created (users/orders tagged `_pdwchs_demo_seed`,
 * session and cart rows from the 10.42.* IP range, seeded tags/notes/segments, seed options).
 *   wpc eval-file docs/marketing/tooling/unseed-demo-data.php
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) { exit( "Run through WP-CLI.\n" ); }
global $wpdb;
$p = $wpdb->prefix;
$n = 0;
foreach ( wc_get_orders( array( 'limit' => -1, 'type' => 'shop_order', 'meta_key' => '_pdwchs_demo_seed', 'return' => 'ids' ) ) as $oid ) {
	$o = wc_get_order( $oid );
	if ( $o ) { foreach ( $o->get_refunds() as $r ) { $r->delete( true ); } $o->delete( true ); $n++; }
}
WP_CLI::log( "orders deleted: $n" );
$hero = (int) get_option( 'pdwchs_demo_seed_hero_id' );
$users = get_users( array( 'meta_key' => '_pdwchs_demo_seed', 'fields' => 'ID' ) );
foreach ( $users as $uid ) {
	$wpdb->delete( "{$p}pdwchs_customer_intelligence", array( 'user_id' => $uid ) );
	$wpdb->delete( "{$p}pdwchs_lifecycle_history", array( 'user_id' => $uid ) );
	$wpdb->delete( "{$p}pdwchs_behavioral_tags", array( 'user_id' => $uid ) );
	$wpdb->delete( "{$p}pdwchs_behavioral_patterns", array( 'user_id' => $uid ) );
	$wpdb->delete( "{$p}pdwchs_churn_scores", array( 'customer_id' => $uid ) );
	$wpdb->delete( "{$p}pdwchs_churn_history", array( 'customer_id' => $uid ) );
	$wpdb->delete( "{$p}pdwchs_churn_alerts", array( 'customer_id' => $uid ) );
	$wpdb->delete( "{$p}pdwchs_customer_notes", array( 'customer_id' => $uid ) );
	$wpdb->delete( "{$p}pdwchs_customer_tags", array( 'customer_id' => $uid ) );
	$wpdb->delete( "{$p}pd_woo_customer_history_sessions", array( 'user_id' => $uid ) );
	wp_delete_user( $uid );
}
WP_CLI::log( 'users deleted: ' . count( $users ) );
WP_CLI::log( 'sessions deleted: ' . $wpdb->query( "DELETE FROM {$p}pd_woo_customer_history_sessions WHERE ip LIKE '10.42.%'" ) );
WP_CLI::log( 'carts deleted: ' . $wpdb->query( "DELETE FROM {$p}pdwchs_carts WHERE ip_address LIKE '10.42.%'" ) );
WP_CLI::log( 'tags deleted: ' . $wpdb->query( "DELETE FROM {$p}pdwchs_tags WHERE slug IN ('vip','wholesale','newsletter','trade-show-2026')" ) );
$wpdb->query( "DELETE FROM {$p}pdwchs_customer_tags WHERE tag_id NOT IN (SELECT id FROM {$p}pdwchs_tags)" );
WP_CLI::log( 'segments deleted: ' . $wpdb->query( "DELETE FROM {$p}pdwchs_segments WHERE is_default = 0 AND name IN ('Repeat buyers over \$500','US customers','Hoodie buyers')" ) );
foreach ( array( 'pdwchs_demo_seed_done', 'pdwchs_demo_seed_hero_id', 'pdwchs_cohort_matrix', 'pdwchs_digest_segment_enabled', 'pdwchs_digest_segment_id' ) as $opt ) { delete_option( $opt ); }
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pdwchs_%' OR option_name LIKE '_transient_timeout_pdwchs_%'" );
WP_CLI::success( 'Unseeded.' );
