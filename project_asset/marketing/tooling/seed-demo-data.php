<?php
/**
 * Demo-data seeder for marketing screenshots. DEV ONLY — never shipped (docs/ is excluded by build.sh).
 *
 * Run with the Pro tier active (see README.md):
 *   wpc eval-file docs/marketing/tooling/seed-demo-data.php
 *
 * Creates ~60 customers, ~430 orders over 14 months, ~3,000 tracked session rows, ~120 cart rows,
 * manual tags, notes, segments and settings, then runs every nightly intelligence job once.
 * Everything it creates is tagged: user/order meta `_pdwchs_demo_seed`, session/cart IPs `10.42.*`.
 * Existing rows are left alone.
 */
if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	exit( "Run through WP-CLI.\n" );
}
if ( ! function_exists( 'pdwchs_is_pro_active' ) || ! pdwchs_is_pro_active() ) {
	WP_CLI::error( 'Pro tier is not active. Set option pdwchs_capture_tier=pro (mu-plugin) first.' );
}
if ( get_option( 'pdwchs_demo_seed_done' ) ) {
	WP_CLI::error( 'Seed already ran (option pdwchs_demo_seed_done). Delete it to re-run.' );
}

global $wpdb, $NOW, $SCRIPT_START, $SESS_TABLE, $CARTS_TABLE, $ADMIN_ID, $PRODUCTS, $BY_CAT, $HIGH_VALUE, $LOW_VALUE, $CITIES, $GATEWAYS, $SESSION_ROWS, $REFERERS, $MOBILE_REF, $SEARCH_TERMS, $ZERO_TERMS, $UAS, $created_users, $CUR_SESSION_ID; // eval-file runs in a method scope
mt_srand( 20260919 );
$NOW          = time();
$SCRIPT_START = gmdate( 'Y-m-d H:i:s', $NOW - 5 );
$SESS_TABLE   = $wpdb->prefix . 'pd_woo_customer_history_sessions';
$CARTS_TABLE  = $wpdb->prefix . 'pdwchs_carts';
$ADMIN_ID     = 16; // Yeasir — author of notes / tags.

add_filter( 'pre_wp_mail', '__return_false' ); // never send mail while seeding.
$intel = apply_filters( 'pdwchs_intelligence_instance', null );
if ( $intel ) {
	remove_action( 'woocommerce_order_status_completed', array( $intel, 'pdwchs_on_order_completed' ) );
}

function seed_rand( $a, $b ) { return mt_rand( $a, $b ); }
function seed_pick( $arr ) { return $arr[ array_rand( $arr ) ]; }
function seed_chance( $pct ) { return mt_rand( 1, 100 ) <= $pct; }

// ---------------------------------------------------------------- catalogue
$PRODUCTS = array( // id => [slug, price, cat]
	23 => array( 'long-sleeve-tee', 25, 'tshirts' ), 24 => array( 'polo', 20, 'tshirts' ),
	25 => array( 'album', 15, 'music' ),            26 => array( 'single', 2, 'music' ),
	33 => array( 't-shirt-with-logo', 18, 'tshirts' ), 34 => array( 'beanie-with-logo', 18, 'accessories' ),
	14 => array( 'hoodie', 42, 'hoodies' ),          15 => array( 'hoodie-with-logo', 45, 'hoodies' ),
	16 => array( 't-shirt', 18, 'tshirts' ),         18 => array( 'belt', 55, 'accessories' ),
	19 => array( 'cap', 16, 'accessories' ),         20 => array( 'sunglasses', 90, 'accessories' ),
	21 => array( 'hoodie-with-pocket', 35, 'hoodies' ), 22 => array( 'hoodie-with-zipper', 45, 'hoodies' ),
	13 => array( 'v-neck-t-shirt', 15, 'tshirts' ),
);
$BY_CAT = array();
foreach ( $PRODUCTS as $pid => $p ) { $BY_CAT[ $p[2] ][] = $pid; }
$HIGH_VALUE = array( 20, 18, 15, 22, 14, 21 );
$LOW_VALUE  = array( 26, 25, 19, 13, 16 );

// Brands (product_brand taxonomy ships with WooCommerce 9.6+).
$BRANDS = array( 'Northwind' => array( 14, 15, 21, 22, 34 ), 'Atelier Nine' => array( 23, 24, 33, 16, 13 ), 'Basecamp Supply' => array( 18, 19, 20, 25, 26 ) );
if ( taxonomy_exists( 'product_brand' ) ) {
	foreach ( $BRANDS as $brand => $pids ) {
		$term = term_exists( $brand, 'product_brand' );
		if ( ! $term ) { $term = wp_insert_term( $brand, 'product_brand' ); }
		$term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
		foreach ( $pids as $pid ) { wp_set_object_terms( $pid, array( $term_id ), 'product_brand', true ); }
	}
}

// Coupons (WooCommerce coupons created by the seeder only; the plugin itself never creates coupons).
foreach ( array( 'WELCOME10' => array( 'percent', 10 ), 'SAVE5' => array( 'fixed_cart', 5 ) ) as $code => $c ) {
	if ( ! wc_get_coupon_id_by_code( $code ) ) {
		$coupon = new WC_Coupon();
		$coupon->set_code( $code ); $coupon->set_discount_type( $c[0] ); $coupon->set_amount( $c[1] ); $coupon->save();
	}
}

// ---------------------------------------------------------------- people
$FIRST = array( 'Amelia','Noah','Olivia','Liam','Sophia','Mason','Isabella','Ethan','Mia','Lucas','Charlotte','Elijah','Harper','James','Evelyn','Benjamin','Abigail','Henry','Emily','Alexander','Ella','Daniel','Scarlett','Matthew','Grace','Sebastian','Chloe','Jack','Lily','Owen','Zoe','Leo','Nora','Ryan','Hannah','Nathan','Aria','Isaac','Layla','Caleb','Riley','Adam','Stella','Julian','Violet','Levi','Aurora','Miles','Hazel','Aaron','Lucy','Felix','Ivy','Oscar','Ruby','Theo','Freya','Hugo','Clara','Jonah' );
$LAST  = array( 'Hartley','Bennett','Okafor','Lindqvist','Moreau','Tanaka','Silva','Novak','Reyes','Fischer','Walsh','Petrov','Andersen','Costa','Nakamura','Byrne','Schmidt','Duarte','Larsen','Kowalski','Haddad','Marsh','Ferreira','Weber','Doyle','Rossi','Brennan','Kimura','Lambert','Sato' );
$COUNTRIES = array( 'US','US','US','US','US','US','US','US','US','US','US','US','US','US','US','US','US','US','GB','GB','GB','GB','GB','GB','GB','GB','DE','DE','DE','DE','DE','DE','CA','CA','CA','CA','CA','AU','AU','AU','AU','AU','FR','FR','FR','FR','NL','NL','NL','ES','ES','ES','IT','IT','IT','IN','IN','BR','BR','JP' );
$CITIES = array( 'US' => array( 'Austin','TX','73301' ), 'GB' => array( 'Manchester','','M1 1AE' ), 'DE' => array( 'Hamburg','','20095' ), 'CA' => array( 'Toronto','ON','M5V 2T6' ), 'AU' => array( 'Melbourne','VIC','3000' ), 'FR' => array( 'Lyon','','69001' ), 'NL' => array( 'Utrecht','','3511' ), 'ES' => array( 'Valencia','V','46001' ), 'IT' => array( 'Bologna','BO','40121' ), 'IN' => array( 'Pune','MH','411001' ), 'BR' => array( 'Curitiba','PR','80010-000' ), 'JP' => array( 'Osaka','JP27','530-0001' ) );

$ARCHETYPES = array(
	'champion'        => array( 'n' => 11, 'orders' => array( 12, 20 ), 'first' => array( 300, 420 ), 'last' => array( 3, 20 ),    'value' => 'high', 'eng' => 'high' ),
	'loyal'           => array( 'n' => 6,  'orders' => array( 8, 12 ),  'first' => array( 250, 400 ), 'last' => array( 31, 60 ),   'value' => 'mid',  'eng' => 'mid' ),
	'potential'       => array( 'n' => 5,  'orders' => array( 2, 3 ),   'first' => array( 40, 90 ),   'last' => array( 3, 25 ),    'value' => 'mid',  'eng' => 'high' ),
	'new'             => array( 'n' => 5,  'orders' => array( 1, 1 ),   'first' => array( 5, 25 ),    'last' => array( 5, 25 ),    'value' => 'mid',  'eng' => 'high' ),
	'promising'       => array( 'n' => 3,  'orders' => array( 1, 1 ),   'first' => array( 30, 60 ),   'last' => array( 30, 60 ),   'value' => 'high', 'eng' => 'mid' ),
	'cannot_lose'     => array( 'n' => 3,  'orders' => array( 10, 14 ), 'first' => array( 330, 420 ), 'last' => array( 120, 170 ), 'value' => 'high', 'eng' => 'none' ),
	'at_risk'         => array( 'n' => 5,  'orders' => array( 5, 8 ),   'first' => array( 300, 420 ), 'last' => array( 100, 170 ), 'value' => 'mid',  'eng' => 'low' ),
	'needs_attention' => array( 'n' => 5,  'orders' => array( 3, 6 ),   'first' => array( 200, 400 ), 'last' => array( 65, 120 ),  'value' => 'mid',  'eng' => 'low' ),
	'hibernating'     => array( 'n' => 6,  'orders' => array( 1, 2 ),   'first' => array( 300, 420 ), 'last' => array( 200, 400 ), 'value' => 'low',  'eng' => 'none' ),
	'lost'            => array( 'n' => 5,  'orders' => array( 1, 1 ),   'first' => array( 250, 420 ), 'last' => array( 250, 420 ), 'value' => 'low',  'eng' => 'none' ),
	'prospect'        => array( 'n' => 5,  'orders' => array( 0, 0 ),   'first' => array( 10, 60 ),   'last' => array( 10, 60 ),   'value' => null,   'eng' => 'mid' ),
);
$TRAIT_POOL = array( 'weekend', 'night', 'bulk', 'coupon', 'mobile', 'researcher', 'abandoner', 'category' );

$personas = array();
$name_i = 0;
foreach ( $ARCHETYPES as $type => $a ) {
	for ( $i = 0; $i < $a['n']; $i++ ) {
		$fn = $FIRST[ $name_i % count( $FIRST ) ]; $ln = $LAST[ ( $name_i * 7 ) % count( $LAST ) ]; $name_i++;
		$traits = array();
		foreach ( $TRAIT_POOL as $t ) { if ( seed_chance( 22 ) ) { $traits[] = $t; } }
		$personas[] = array(
			'type' => $type, 'first' => $fn, 'last' => $ln, 'country' => seed_pick( $COUNTRIES ),
			'traits' => $traits, 'cfg' => $a, 'hero' => false,
		);
	}
}
// Hero customer: every profile screenshot uses this user.
$personas[] = array(
	'type' => 'champion', 'first' => 'Amelia', 'last' => 'Hartley', 'country' => 'US',
	'traits' => array( 'weekend', 'coupon', 'category', 'researcher' ), 'hero' => true,
	'cfg' => array( 'orders' => array( 14, 14 ), 'first' => array( 400, 400 ), 'last' => array( 6, 6 ), 'value' => 'high', 'eng' => 'high' ),
);

// ---------------------------------------------------------------- helpers
function seed_address( $first, $last, $email, $country ) {
	global $CITIES;
	$c = isset( $CITIES[ $country ] ) ? $CITIES[ $country ] : array( 'Springfield', '', '00000' );
	return array(
		'first_name' => $first, 'last_name' => $last, 'email' => $email, 'phone' => '+1 555 01' . seed_rand( 10, 99 ) . ' ' . seed_rand( 1000, 9999 ),
		'address_1' => seed_rand( 2, 980 ) . ' ' . seed_pick( array( 'Maple', 'Harbor', 'Elm', 'Station', 'Mill', 'Park' ) ) . ' ' . seed_pick( array( 'St', 'Ave', 'Rd', 'Lane' ) ),
		'city' => $c[0], 'state' => $c[1], 'postcode' => $c[2], 'country' => $country,
	);
}
function seed_items( $persona, $count ) {
	global $PRODUCTS, $BY_CAT, $HIGH_VALUE, $LOW_VALUE;
	$items = array();
	$cat   = in_array( 'category', $persona['traits'], true ) ? ( $persona['hero'] ? 'hoodies' : seed_pick( array_keys( $BY_CAT ) ) ) : null;
	for ( $i = 0; $i < $count; $i++ ) {
		if ( $cat && seed_chance( 80 ) ) { $pid = seed_pick( $BY_CAT[ $cat ] ); }
		elseif ( 'high' === $persona['cfg']['value'] && seed_chance( 60 ) ) { $pid = seed_pick( $HIGH_VALUE ); }
		elseif ( 'low' === $persona['cfg']['value'] ) { $pid = seed_pick( $LOW_VALUE ); }
		else { $pid = array_rand( $PRODUCTS ); }
		$qty = in_array( 'bulk', $persona['traits'], true ) ? seed_rand( 2, 5 ) : ( seed_chance( 80 ) ? 1 : 2 );
		$items[] = array( $pid, $qty );
	}
	return $items;
}
function seed_order_ts( $persona, $n ) {
	global $NOW;
	$first = $NOW - seed_rand( $persona['cfg']['first'][0], $persona['cfg']['first'][1] ) * DAY_IN_SECONDS;
	$last  = $NOW - seed_rand( $persona['cfg']['last'][0], $persona['cfg']['last'][1] ) * DAY_IN_SECONDS;
	if ( $last < $first ) { $first = $last - 30 * DAY_IN_SECONDS; }
	$out = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$ts = ( 1 === $n ) ? $last : (int) ( $first + ( $last - $first ) * $i / ( $n - 1 ) + seed_rand( -3, 3 ) * DAY_IN_SECONDS );
		if ( $i === $n - 1 ) { $ts = $last; }
		$ts = min( $ts, $NOW - 3600 );
		// time of day
		$hour = in_array( 'night', $persona['traits'], true ) && seed_chance( 70 ) ? seed_pick( array( 21, 22, 23, 0, 1, 2 ) ) : seed_rand( 8, 20 );
		$ts   = strtotime( gmdate( 'Y-m-d', $ts ) . ' ' . sprintf( '%02d:%02d:00', $hour, seed_rand( 0, 59 ) ) . ' UTC' );
		if ( in_array( 'weekend', $persona['traits'], true ) && seed_chance( 75 ) ) {
			$dow = (int) gmdate( 'N', $ts ); // 6 = Sat
			if ( $dow < 6 ) { $ts += ( 6 - $dow ) * DAY_IN_SECONDS; if ( $ts > $NOW - 3600 ) { $ts -= 7 * DAY_IN_SECONDS; } }
		}
		$out[] = $ts;
	}
	sort( $out );
	return $out;
}
$GATEWAYS = array( 'cod' => 'Cash on delivery', 'bacs' => 'Direct bank transfer', 'cheque' => 'Check payments', 'ppcp-gateway' => 'PayPal', 'stripe' => 'Credit card (Stripe)' );
function seed_create_order( $customer_id, $address, $ts, $items, $status, $opts = array() ) {
	global $GATEWAYS, $PRODUCTS;
	$order = wc_create_order( array( 'customer_id' => $customer_id, 'created_via' => 'checkout' ) );
	foreach ( $items as $it ) {
		$product = wc_get_product( $it[0] );
		if ( ! $product ) { continue; }
		if ( $product->is_type( 'variable' ) ) {
			$children = $product->get_children();
			if ( $children ) { $product = wc_get_product( seed_pick( $children ) ); }
		}
		$order->add_product( $product, $it[1] );
	}
	$order->set_address( $address, 'billing' );
	$order->set_address( $address, 'shipping' );
	$gw = array_rand( $GATEWAYS );
	$order->set_payment_method( $gw );
	$order->set_payment_method_title( $GATEWAYS[ $gw ] );
	if ( ! empty( $opts['currency'] ) ) { $order->set_currency( $opts['currency'] ); }
	if ( ! empty( $opts['coupon'] ) ) { $order->apply_coupon( $opts['coupon'] ); }
	$order->calculate_totals();
	$order->set_date_created( $ts );
	$order->set_date_modified( $ts + 60 );
	if ( in_array( $status, array( 'completed', 'processing', 'refunded' ), true ) ) { $order->set_date_paid( $ts + 90 ); }
	if ( in_array( $status, array( 'completed', 'refunded' ), true ) ) { $order->set_date_completed( $ts + DAY_IN_SECONDS ); }
	$order->update_meta_data( '_pdwchs_demo_seed', 1 );
	$order->set_status( $status );
	$order->save();
	return $order;
}
$SESSION_ROWS = array();
function seed_session_row( $user_id, $url, $ts, $referer = '', $results = null, $ip = null ) {
	global $SESSION_ROWS;
	$SESSION_ROWS[] = array( $user_id, $ip ? $ip : ( '10.42.' . seed_rand( 0, 255 ) . '.' . seed_rand( 1, 254 ) ), $url, $referer, gmdate( 'Y-m-d H:i:s', $ts ), $GLOBALS['CUR_SESSION_ID'], $results );
}
function seed_flush_sessions() {
	global $wpdb, $SESSION_ROWS, $SESS_TABLE;
	foreach ( array_chunk( $SESSION_ROWS, 400 ) as $chunk ) {
		$vals = array(); $ph = array();
		foreach ( $chunk as $r ) {
			$ph[] = '(%d,%s,%s,%s,%s,0,%s,' . ( null === $r[6] ? 'NULL' : '%d' ) . ')';
			array_push( $vals, $r[0], $r[1], $r[2], $r[3], $r[4], $r[5] );
			if ( null !== $r[6] ) { $vals[] = (int) $r[6]; }
		}
		$wpdb->query( $wpdb->prepare( "INSERT INTO {$SESS_TABLE} (user_id, ip, url, referer, reg_date, del, session_id, results_count) VALUES " . implode( ',', $ph ), $vals ) );
	}
	$SESSION_ROWS = array();
}
$REFERERS   = array( '', '', '', 'https://www.google.com/', 'https://www.google.com/', 'https://www.bing.com/', 'https://www.instagram.com/', 'https://www.facebook.com/', 'https://t.co/8xk2pQ', 'https://www.pinterest.com/', 'https://duckduckgo.com/' );
$MOBILE_REF = 'android-app://com.google.android.googlequicksearchbox/';
$SEARCH_TERMS = array( 'hoodie' => 5, 't-shirt' => 5, 'cap' => 1, 'belt' => 1, 'sunglasses' => 1, 'beanie' => 1, 'polo' => 1, 'album' => 1, 'logo' => 3, 'shirt' => 6, 'zipper' => 1, 'tee' => 2 );
$ZERO_TERMS   = array( 'linen shirt', 'gift card', 'leather jacket', 'kids hoodie', 'xl beanie', 'socks', 'tote bag', 'rain jacket', 'sweatpants', 'scarf', 'phone case', 'water bottle', 'wool hat', 'denim', 'sneakers', 'hoodie dress', 'oversized hoodie', 'crop top', 'bucket hat', 'gloves', 'backpack', 'poster', 'vinyl', 'sticker pack', 'mug' );
function seed_session( $user_id, $ts, $persona, $opts = array() ) {
	global $PRODUCTS, $BY_CAT, $REFERERS, $MOBILE_REF, $SEARCH_TERMS, $ZERO_TERMS;
	$GLOBALS['CUR_SESSION_ID'] = substr( md5( $user_id . $ts . mt_rand() ), 0, 26 );
	$mobile  = $persona && in_array( 'mobile', $persona['traits'], true ) && seed_chance( 85 );
	$referer = $mobile ? $MOBILE_REF : seed_pick( $REFERERS );
	$t       = $ts;
	$ip      = '10.42.' . seed_rand( 0, 255 ) . '.' . seed_rand( 1, 254 );
	seed_session_row( $user_id, seed_chance( 60 ) ? 'shop' : '', $t, $referer, null, $ip );
	$cat = seed_pick( array_keys( $BY_CAT ) );
	if ( seed_chance( 55 ) ) { $t += seed_rand( 20, 90 ); seed_session_row( $user_id, 'product-category/' . $cat, $t, $referer, null, $ip ); }
	if ( seed_chance( 45 ) || ! empty( $opts['zero'] ) ) {
		$t += seed_rand( 15, 60 );
		if ( ! empty( $opts['zero'] ) || seed_chance( 25 ) ) { $term = seed_pick( $ZERO_TERMS ); $n = 0; }
		else { $term = array_rand( $SEARCH_TERMS ); $n = $SEARCH_TERMS[ $term ]; }
		seed_session_row( $user_id, 'ACTION::search::' . $term, $t, $referer, $n, $ip );
		seed_session_row( $user_id, '?s=' . rawurlencode( $term ) . '&post_type=product', $t + 1, $referer, null, $ip );
	}
	$views = seed_rand( 1, 4 );
	$last_pid = null;
	for ( $i = 0; $i < $views; $i++ ) {
		$pid = seed_chance( 60 ) ? seed_pick( $BY_CAT[ $cat ] ) : array_rand( $PRODUCTS );
		$t  += seed_rand( 25, 140 );
		seed_session_row( $user_id, 'product/' . $PRODUCTS[ $pid ][0], $t, $referer, null, $ip );
		$last_pid = $pid;
	}
	if ( ! empty( $opts['cart'] ) || seed_chance( 28 ) ) {
		$t += seed_rand( 10, 60 );
		seed_session_row( $user_id, 'ACTION::add_to_cart::' . $last_pid . '::' . ( seed_chance( 80 ) ? 1 : 2 ), $t, $referer, null, $ip );
		seed_session_row( $user_id, 'cart', $t + 5, $referer, null, $ip );
		if ( ! empty( $opts['checkout'] ) || seed_chance( 40 ) ) { $t += seed_rand( 30, 120 ); seed_session_row( $user_id, 'checkout', $t, $referer, null, $ip ); }
	}
	if ( ! empty( $opts['order_id'] ) ) {
		$t += seed_rand( 60, 240 );
		seed_session_row( $user_id, 'checkout', $t - 30, $referer, null, $ip );
		seed_session_row( $user_id, 'ACTION::new_order::' . $opts['order_id'], $t, $referer, null, $ip );
		seed_session_row( $user_id, 'checkout/order-received/' . $opts['order_id'] . '/', $t + 2, $referer, null, $ip );
	}
	return $t;
}

// ---------------------------------------------------------------- create customers + orders
$created_users  = array();
$all_orders     = array(); // order_id => [user_id, ts, status]
$protected      = array(); // last completed order per user — never refund these
$hero_id        = 0;
WP_CLI::log( 'Creating customers and orders…' );
foreach ( $personas as $idx => $persona ) {
	$T0 = microtime( true );
	$login = strtolower( $persona['first'] . '.' . $persona['last'] . ( $idx > 0 ? $idx : '' ) );
	$email = strtolower( $persona['first'] . '.' . $persona['last'] . $idx . '@example.com' );
	$n     = seed_rand( $persona['cfg']['orders'][0], $persona['cfg']['orders'][1] );
	$tss   = $n > 0 ? seed_order_ts( $persona, $n ) : array();
	$registered = $n > 0 ? $tss[0] - seed_rand( 0, 5 ) * DAY_IN_SECONDS : $NOW - seed_rand( $persona['cfg']['first'][0], $persona['cfg']['first'][1] ) * DAY_IN_SECONDS;
	$user_id = wp_insert_user( array(
		'user_login' => $login, 'user_email' => $email, 'user_pass' => wp_generate_password( 20 ), 'role' => 'customer',
		'first_name' => $persona['first'], 'last_name' => $persona['last'], 'display_name' => $persona['first'] . ' ' . $persona['last'],
		'user_registered' => gmdate( 'Y-m-d H:i:s', $registered ),
	) );
	if ( is_wp_error( $user_id ) ) { WP_CLI::warning( "user $login: " . $user_id->get_error_message() ); continue; }
	update_user_meta( $user_id, '_pdwchs_demo_seed', 1 );
	$address  = seed_address( $persona['first'], $persona['last'], $email, $persona['country'] );
	$customer = new WC_Customer( $user_id );
	foreach ( $address as $k => $v ) { $customer->{"set_billing_$k"}( $v ); if ( 'email' !== $k && 'phone' !== $k ) { $customer->{"set_shipping_$k"}( $v ); } }
	$customer->save();
	if ( $persona['hero'] ) { $hero_id = $user_id; }
	$created_users[ $user_id ] = $persona;

	$use_coupon = in_array( 'coupon', $persona['traits'], true );
	$abandoner  = in_array( 'abandoner', $persona['traits'], true ) && $n >= 5;
	foreach ( $tss as $i => $ts ) {
		$is_last = ( $i === $n - 1 );
		$count   = 'high' === $persona['cfg']['value'] ? seed_rand( 2, 5 ) : ( 'low' === $persona['cfg']['value'] ? 1 : seed_rand( 1, 3 ) );
		if ( in_array( 'bulk', $persona['traits'], true ) ) { $count = seed_rand( 3, 6 ); }
		$items = seed_items( $persona, $count );
		if ( $is_last ) { $status = 'completed'; }
		elseif ( $abandoner && seed_chance( 40 ) ) { $status = seed_pick( array( 'cancelled', 'pending' ) ); }
		else { $r = seed_rand( 1, 100 ); $status = $r <= 88 ? 'completed' : ( $r <= 92 ? 'on-hold' : ( $r <= 96 ? 'cancelled' : 'failed' ) ); }
		if ( 'completed' === $status && $ts > $NOW - 4 * DAY_IN_SECONDS && ! $is_last ) { $status = 'processing'; }
		$opts  = array();
		if ( $use_coupon && seed_chance( 85 ) ) { $opts['coupon'] = seed_pick( array( 'WELCOME10', 'SAVE5' ) ); }
		$order = seed_create_order( $user_id, $address, $ts, $items, $status, $opts );
		$all_orders[ $order->get_id() ] = array( $user_id, $ts, $status );
		if ( $is_last ) { $protected[] = $order->get_id(); }
		// Browsing before the order (researchers browse over 3 days, everyone else has one purchase session).
		if ( in_array( 'researcher', $persona['traits'], true ) ) {
			for ( $s = 0; $s < seed_rand( 5, 7 ); $s++ ) { seed_session( $user_id, $ts - seed_rand( 3, 72 ) * HOUR_IN_SECONDS, $persona ); }
		}
		seed_session( $user_id, $ts - seed_rand( 8, 40 ) * MINUTE_IN_SECONDS, $persona, array( 'cart' => 1, 'order_id' => $order->get_id() ) );
	}
	// General browsing by engagement level.
	$eng = $persona['cfg']['eng'];
	$recent = 'high' === $eng ? seed_rand( 8, 14 ) : ( 'mid' === $eng ? seed_rand( 3, 6 ) : ( 'low' === $eng ? seed_rand( 0, 1 ) : 0 ) );
	$older  = 'high' === $eng ? seed_rand( 10, 20 ) : ( 'mid' === $eng ? seed_rand( 6, 10 ) : ( 'low' === $eng ? seed_rand( 3, 6 ) : seed_rand( 0, 3 ) ) );
	for ( $s = 0; $s < $recent; $s++ ) { seed_session( $user_id, $NOW - seed_rand( 1, 29 ) * DAY_IN_SECONDS - seed_rand( 0, 86000 ), $persona ); }
	for ( $s = 0; $s < $older; $s++ )  { seed_session( $user_id, $NOW - seed_rand( 31, 120 ) * DAY_IN_SECONDS - seed_rand( 0, 86000 ), $persona ); }
	if ( $persona['hero'] ) {
		// A visit from today so "Last seen: Today" shows on the order panel, plus a zero-result search.
		seed_session( $user_id, $NOW - 2 * HOUR_IN_SECONDS, $persona, array( 'zero' => 1, 'cart' => 1 ) );
		seed_session( $user_id, $NOW - 30 * MINUTE_IN_SECONDS, $persona );
	}
	$T1 = microtime( true );
	seed_flush_sessions();
	WP_CLI::log( sprintf( '  %-28s %-16s orders=%2d  build=%5.1fs flush=%4.1fs', $login, $persona['type'], $n, $T1 - $T0, microtime( true ) - $T1 ) );
}
WP_CLI::log( 'Registered customers: ' . count( $created_users ) . ' | orders so far: ' . count( $all_orders ) );

// Guest orders (~30) — a few emails repeat, three match registered customers (merge story).
$guest_orders = array();
$reg_emails   = array_values( array_map( function ( $uid ) { return get_userdata( $uid )->user_email; }, array_keys( $created_users ) ) );
for ( $g = 0; $g < 30; $g++ ) {
	$fn = seed_pick( $FIRST ); $ln = seed_pick( $LAST ); $country = seed_pick( $COUNTRIES );
	if ( $g < 3 ) { $email = $reg_emails[ $g * 5 ]; } elseif ( $g < 9 ) { $email = 'guest' . ( $g % 3 ) . '@example.org'; } else { $email = strtolower( $fn . '.' . $ln . '.guest' . $g . '@example.org' ); }
	$ts    = $NOW - seed_rand( 1, 400 ) * DAY_IN_SECONDS - seed_rand( 0, 86000 );
	$items = array( array( array_rand( $PRODUCTS ), seed_chance( 75 ) ? 1 : 2 ) );
	if ( seed_chance( 40 ) ) { $items[] = array( array_rand( $PRODUCTS ), 1 ); }
	$status = seed_chance( 90 ) ? ( $ts > $NOW - 3 * DAY_IN_SECONDS ? 'processing' : 'completed' ) : 'cancelled';
	$order  = seed_create_order( 0, seed_address( $fn, $ln, $email, $country ), $ts, $items, $status, $g < 2 ? array( 'currency' => 'EUR' ) : array() );
	$guest_orders[ $order->get_id() ] = $ts;
	$GLOBALS['CUR_SESSION_ID'] = substr( md5( 'guest' . $g ), 0, 26 );
	seed_session( 0, $ts - seed_rand( 10, 50 ) * MINUTE_IN_SECONDS, null, array( 'cart' => 1, 'order_id' => $order->get_id() ) );
}
seed_flush_sessions();

// Refunds: 15 partial + 8 full, never on a persona's protected last order.
$candidates = array_keys( array_filter( $all_orders, function ( $o ) { return 'completed' === $o[2]; } ) );
$candidates = array_values( array_diff( $candidates, $protected ) );
shuffle( $candidates );
$partials = array_slice( $candidates, 0, 15 );
$fulls    = array_slice( $candidates, 15, 8 );
foreach ( $partials as $oid ) {
	$order  = wc_get_order( $oid );
	$amount = round( $order->get_total() * seed_pick( array( 0.2, 0.3, 0.4 ) ), 2 );
	if ( $amount > 0 ) {
		$r = wc_create_refund( array( 'amount' => $amount, 'reason' => seed_pick( array( 'Damaged item', 'Wrong size', 'Customer changed mind' ) ), 'order_id' => $oid, 'refund_payment' => false ) );
		if ( ! is_wp_error( $r ) ) { $r->set_date_created( $all_orders[ $oid ][1] + 3 * DAY_IN_SECONDS ); $r->save(); }
	}
}
foreach ( $fulls as $oid ) {
	$order = wc_get_order( $oid );
	$r = wc_create_refund( array( 'amount' => $order->get_total(), 'reason' => 'Returned', 'order_id' => $oid, 'refund_payment' => false ) );
	if ( ! is_wp_error( $r ) ) { $r->set_date_created( $all_orders[ $oid ][1] + 5 * DAY_IN_SECONDS ); $r->save(); }
	$order = wc_get_order( $oid ); $order->set_status( 'refunded' ); $order->save();
	$all_orders[ $oid ][2] = 'refunded';
}

// Remove the now-dated ACTION::new_order rows the public tracker inserted while we created orders.
$wpdb->query( $wpdb->prepare( "DELETE FROM {$SESS_TABLE} WHERE url LIKE 'ACTION::new_order::%%' AND ip NOT LIKE '10.42.%%' AND reg_date >= %s", $SCRIPT_START ) );

// Guest browsing, zero-result searches, bots.
WP_CLI::log( 'Guest sessions, zero-result searches, bots…' );
for ( $g = 0; $g < 90; $g++ ) {
	$GLOBALS['CUR_SESSION_ID'] = substr( md5( 'gs' . $g ), 0, 26 );
	seed_session( 0, $NOW - seed_rand( 0, 120 ) * DAY_IN_SECONDS - seed_rand( 0, 86000 ), null, array( 'zero' => $g % 3 === 0 ? 1 : 0 ) );
}
foreach ( $ZERO_TERMS as $zi => $term ) {
	for ( $k = 0; $k < seed_rand( 1, 6 ); $k++ ) {
		$GLOBALS['CUR_SESSION_ID'] = substr( md5( 'zr' . $zi . $k ), 0, 26 );
		seed_session_row( seed_chance( 60 ) ? 0 : (int) array_rand( $created_users ), 'ACTION::search::' . $term, $NOW - seed_rand( 0, 60 ) * DAY_IN_SECONDS - seed_rand( 0, 86000 ), seed_pick( $REFERERS ), 0 );
	}
}
for ( $b = 0; $b < 20; $b++ ) {
	$GLOBALS['CUR_SESSION_ID'] = substr( md5( 'bot' . $b ), 0, 26 );
	seed_session_row( 999999999999999, 'product/' . $PRODUCTS[ array_rand( $PRODUCTS ) ][0], $NOW - seed_rand( 0, 30 ) * DAY_IN_SECONDS, '', null, '10.42.250.' . seed_rand( 1, 254 ) );
}
seed_flush_sessions();
if ( ! get_option( 'pdwchs_results_count_since' ) ) { update_option( 'pdwchs_results_count_since', gmdate( 'Y-m-d H:i:s', $NOW - 60 * DAY_IN_SECONDS ) ); }

// ---------------------------------------------------------------- carts
WP_CLI::log( 'Carts…' );
$UAS = array(
	'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/128.0 Safari/537.36',
	'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/127.0 Safari/537.36',
	'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
	'Mozilla/5.0 (Linux; Android 14; Pixel 8) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Mobile Safari/537.36',
);
function seed_cart_contents( $n ) {
	global $PRODUCTS;
	$contents = array(); $total = 0; $count = 0;
	for ( $i = 0; $i < $n; $i++ ) {
		$pid = array_rand( $PRODUCTS ); $qty = seed_chance( 75 ) ? 1 : 2; $line = $PRODUCTS[ $pid ][1] * $qty;
		$contents[ md5( $pid . $i . mt_rand() ) ] = array( 'key' => md5( $pid . $i ), 'product_id' => $pid, 'variation_id' => 0, 'variation' => array(), 'quantity' => $qty, 'data_hash' => '', 'line_tax_data' => array(), 'line_subtotal' => $line, 'line_subtotal_tax' => 0, 'line_total' => $line, 'line_tax' => 0 );
		$total += $line; $count += $qty;
	}
	return array( wp_json_encode( $contents ), $total, $count );
}
$user_ids   = array_keys( $created_users );
$user_orders = array();
foreach ( $all_orders as $oid => $o ) { $user_orders[ $o[0] ][] = $oid; }
function seed_cart_row( $status, $user_id, $created, $extra = array() ) {
	global $wpdb, $CARTS_TABLE, $UAS, $created_users;
	list( $json, $total, $count ) = seed_cart_contents( seed_rand( 1, 4 ) );
	$email = null;
	if ( $user_id ) { $email = get_userdata( $user_id )->user_email; }
	elseif ( seed_chance( 55 ) ) { $email = strtolower( seed_pick( array( 'sam', 'lee', 'kim', 'pat', 'alex', 'jo' ) ) . seed_rand( 10, 999 ) . '@example.org' ); }
	$row = array_merge( array(
		'user_id' => $user_id ? $user_id : null, 'session_key' => substr( md5( 'ck' . $created . mt_rand() ), 0, 32 ), 'email' => $email,
		'status' => $status, 'cart_contents' => $json, 'contents_hash' => md5( $json ), 'cart_hash' => substr( md5( $json . 'h' ), 0, 32 ),
		'items_count' => $count, 'cart_total' => $total, 'currency' => 'USD', 'ip_address' => '10.42.' . seed_rand( 0, 255 ) . '.' . seed_rand( 1, 254 ),
		'user_agent' => seed_pick( $UAS ), 'created_at' => gmdate( 'Y-m-d H:i:s', $created ), 'updated_at' => gmdate( 'Y-m-d H:i:s', $created + seed_rand( 60, 1500 ) ),
		'php_session_id' => substr( md5( 'ps' . $created ), 0, 26 ),
	), $extra );
	$wpdb->insert( $CARTS_TABLE, $row );
}
for ( $i = 0; $i < 15; $i++ ) { seed_cart_row( 'active', seed_chance( 60 ) ? seed_pick( $user_ids ) : 0, $NOW - seed_rand( 5, 40 ) * MINUTE_IN_SECONDS ); }
for ( $i = 0; $i < 55; $i++ ) {
	$c = $NOW - ( $i < 25 ? seed_rand( 1, 6 ) : seed_rand( 7, 30 ) ) * DAY_IN_SECONDS - seed_rand( 0, 80000 );
	seed_cart_row( 'abandoned', seed_chance( 55 ) ? seed_pick( $user_ids ) : 0, $c, array( 'abandoned_at' => gmdate( 'Y-m-d H:i:s', $c + seed_rand( 60, 95 ) * MINUTE_IN_SECONDS ) ) );
}
for ( $i = 0; $i < 20; $i++ ) {
	$uid = seed_pick( array_keys( $user_orders ) ); $c = $NOW - seed_rand( 10, 60 ) * DAY_IN_SECONDS;
	seed_cart_row( 'recovered', $uid, $c, array( 'abandoned_at' => gmdate( 'Y-m-d H:i:s', $c + 70 * MINUTE_IN_SECONDS ), 'recovered_at' => gmdate( 'Y-m-d H:i:s', $c + seed_rand( 3, 40 ) * HOUR_IN_SECONDS ), 'order_id' => seed_pick( $user_orders[ $uid ] ) ) );
}
for ( $i = 0; $i < 30; $i++ ) {
	$uid = seed_pick( array_keys( $user_orders ) ); $c = $NOW - seed_rand( 1, 90 ) * DAY_IN_SECONDS;
	seed_cart_row( 'completed', $uid, $c, array( 'completed_at' => gmdate( 'Y-m-d H:i:s', $c + seed_rand( 5, 40 ) * MINUTE_IN_SECONDS ), 'order_id' => seed_pick( $user_orders[ $uid ] ) ) );
}
// Hero: one abandoned cart 2 days ago and one recovered cart last week.
$c = $NOW - 2 * DAY_IN_SECONDS - 5000; seed_cart_row( 'abandoned', $hero_id, $c, array( 'abandoned_at' => gmdate( 'Y-m-d H:i:s', $c + 65 * MINUTE_IN_SECONDS ) ) );
$c = $NOW - 8 * DAY_IN_SECONDS;        seed_cart_row( 'recovered', $hero_id, $c, array( 'abandoned_at' => gmdate( 'Y-m-d H:i:s', $c + 70 * MINUTE_IN_SECONDS ), 'recovered_at' => gmdate( 'Y-m-d H:i:s', $c + 6 * HOUR_IN_SECONDS ), 'order_id' => end( $user_orders[ $hero_id ] ) ) );

// ---------------------------------------------------------------- CRM: tags, notes, segments, settings
WP_CLI::log( 'Tags, notes, segments, settings…' );
if ( class_exists( 'PDWCHS_Customer_Tags' ) ) {
	$tags = new PDWCHS_Customer_Tags();
	$tag_ids = array();
	foreach ( array( 'VIP', 'Wholesale', 'Newsletter', 'Trade show 2026' ) as $t ) { $tag_ids[ $t ] = $tags->pdwchs_create_tag( $t, $ADMIN_ID ); }
	$pool = $user_ids; shuffle( $pool );
	foreach ( array_slice( $pool, 0, 15 ) as $k => $uid ) {
		$tags->pdwchs_assign_tag( $uid, $tag_ids[ array_keys( $tag_ids )[ $k % 4 ] ], $ADMIN_ID );
		if ( $k % 3 === 0 ) { $tags->pdwchs_assign_tag( $uid, $tag_ids['Newsletter'], $ADMIN_ID ); }
	}
	$tags->pdwchs_assign_tag( $hero_id, $tag_ids['VIP'], $ADMIN_ID );
	$tags->pdwchs_assign_tag( $hero_id, $tag_ids['Newsletter'], $ADMIN_ID );
}
if ( class_exists( 'PDWCHS_Customer_Notes' ) ) {
	$notes = new PDWCHS_Customer_Notes();
	$notes_table = $wpdb->prefix . 'pdwchs_customer_notes';
	foreach ( array(
		array( 'Called about a delayed hoodie order — resolved with a replacement. Very understanding.', 40 ),
		array( 'Asked whether we will restock the zip hoodie in navy. Let her know when it lands.', 12 ),
		array( 'Prefers weekend delivery. Mentioned buying gifts for her team in December.', 2 ),
	) as $n ) {
		$nid = $notes->pdwchs_add_note( $hero_id, $n[0], $ADMIN_ID );
		if ( is_int( $nid ) ) { $wpdb->update( $notes_table, array( 'created_at' => gmdate( 'Y-m-d H:i:s', $NOW - $n[1] * DAY_IN_SECONDS ) ), array( 'id' => $nid ) ); }
	}
}
if ( class_exists( 'PDWCHS_Segments_Manager' ) ) {
	wp_set_current_user( $ADMIN_ID );
	$mgr = new PDWCHS_Segments_Manager();
	$mgr->pdwchs_create_segment( 'Repeat buyers over $500', 'Customers with 3+ orders and more than $500 lifetime spend.', array( 'order_number' => 'greater_then', 'order_number_input' => '2', 'total_spent' => 'greater_then', 'total_spent_input' => '500' ) );
	$mgr->pdwchs_create_segment( 'US customers', 'Everyone with a United States billing address.', array( 'customer_country' => 'US' ) );
	$mgr->pdwchs_create_segment( 'Hoodie buyers', 'Anyone who has bought the Hoodie with Zipper.', array( 'product_filter' => '22' ) );
}
update_option( 'pd-woo-results_per_page', 20 );
update_option( 'pd-woo-hide_users_with_no_orders', 0 );
update_option( 'pd-woo-show_bot_sessions', 0 );
update_option( 'pd-woo-default_save_admin_session', 0 );
update_option( 'pd-woo-generate_weekly_report', array( 'order', 'top_customer', 'search', 'most_viewed_products', 'most_viewed_categories' ) );
update_option( 'pdwchs_digest_interval', 'weekly' );
update_option( 'pd-woo-send_weekly_report_to', 'owner@example.com' );
update_option( 'pdwchs_cart_tracking_enabled', 1 );
update_option( 'pdwchs_cart_email_capture_enabled', 1 );
update_option( 'pdwchs_cart_abandonment_timeout_minutes', 60 );
update_option( 'pdwchs_cart_cleanup_days', 90 );
$seg_for_digest = $wpdb->get_var( "SELECT id FROM {$wpdb->prefix}pdwchs_segments WHERE name = 'Repeat buyers over \$500' LIMIT 1" );
if ( $seg_for_digest ) { update_option( 'pdwchs_digest_segment_enabled', 1 ); update_option( 'pdwchs_digest_segment_id', (int) $seg_for_digest ); }

// ---------------------------------------------------------------- WooCommerce analytics lookup tables (Product Revenue / Frequently Bought Together read them)
WP_CLI::log( 'Syncing WooCommerce analytics lookup tables…' );
$all_ids = array_merge( array_keys( $all_orders ), array_keys( $guest_orders ) );
foreach ( $all_ids as $oid ) {
	if ( class_exists( '\Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler' ) ) {
		\Automattic\WooCommerce\Internal\Admin\Schedulers\OrdersScheduler::import( $oid );
	}
}

// ---------------------------------------------------------------- recompute intelligence
WP_CLI::log( 'Running nightly intelligence jobs…' );
$intel_table = $wpdb->prefix . 'pdwchs_customer_intelligence';
do_action( 'pdwchs_update_store_average' );
do_action( 'pdwchs_daily_rfm_calculation' );
do_action( 'pdwchs_daily_churn_calculation' );
do_action( 'pdwchs_daily_health_calculation' );
// Force visible health trends: nudge stored scores, then recalculate so the delta shows as ↗ / ↘.
$ups   = array_slice( array_keys( array_filter( $created_users, function ( $p ) { return in_array( $p['type'], array( 'champion', 'loyal' ), true ); } ) ), 0, 6 );
$downs = array_slice( array_keys( array_filter( $created_users, function ( $p ) { return in_array( $p['type'], array( 'at_risk', 'needs_attention', 'cannot_lose' ), true ); } ) ), 0, 6 );
foreach ( $ups as $uid )   { $wpdb->query( $wpdb->prepare( "UPDATE {$intel_table} SET health_score = GREATEST(0, health_score - 14) WHERE user_id = %d", $uid ) ); }
foreach ( $downs as $uid ) { $wpdb->query( $wpdb->prepare( "UPDATE {$intel_table} SET health_score = LEAST(100, health_score + 16) WHERE user_id = %d", $uid ) ); }
do_action( 'pdwchs_daily_health_calculation' );
do_action( 'pdwchs_daily_lifecycle_calculation' );
do_action( 'pdwchs_daily_behavior_analysis' );
do_action( 'pdwchs_daily_cohort_calculation' );
do_action( 'pdwchs_cart_abandonment_check' );
do_action( 'pdwchs_hourly_alert_check' );
for ( $i = 0; $i < 30 && ! get_option( 'pdwchs_session_merge_backfill_completed' ); $i++ ) { do_action( 'pdwchs_session_merge_backfill' ); }
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pdwchs_%' OR option_name LIKE '_transient_timeout_pdwchs_%'" );
update_option( 'pdwchs_demo_seed_done', gmdate( 'c' ) );
update_option( 'pdwchs_demo_seed_hero_id', $hero_id );

// ---------------------------------------------------------------- report
$counts = array(
	'customers'   => count( $created_users ),
	'orders'      => count( $all_orders ) + count( $guest_orders ),
	'sessions'    => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$SESS_TABLE} WHERE ip LIKE '10.42.%'" ),
	'zero_terms'  => (int) $wpdb->get_var( "SELECT COUNT(DISTINCT url) FROM {$SESS_TABLE} WHERE results_count = 0" ),
	'carts'       => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$CARTS_TABLE} WHERE ip_address LIKE '10.42.%'" ),
	'rfm'         => $wpdb->get_results( "SELECT rfm_segment, COUNT(*) c FROM {$intel_table} GROUP BY rfm_segment", ARRAY_A ),
	'lifecycle'   => $wpdb->get_results( "SELECT lifecycle_stage, COUNT(*) c FROM {$intel_table} GROUP BY lifecycle_stage", ARRAY_A ),
	'health_trend'=> $wpdb->get_results( "SELECT health_trend, COUNT(*) c FROM {$intel_table} GROUP BY health_trend", ARRAY_A ),
	'tags'        => $wpdb->get_results( "SELECT behavior_type, COUNT(*) c FROM {$wpdb->prefix}pdwchs_behavioral_tags WHERE is_active = 1 GROUP BY behavior_type", ARRAY_A ),
	'churn'       => $wpdb->get_results( "SELECT risk_level, COUNT(*) c FROM {$wpdb->prefix}pdwchs_churn_scores GROUP BY risk_level", ARRAY_A ),
	'alerts'      => (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->prefix}pdwchs_churn_alerts" ),
	'cohort_rows' => is_array( get_option( 'pdwchs_cohort_matrix' ) ) ? count( get_option( 'pdwchs_cohort_matrix' ) ) : 'n/a',
	'hero_id'     => $hero_id,
);
WP_CLI::log( wp_json_encode( $counts, JSON_PRETTY_PRINT ) );
WP_CLI::success( 'Seed complete.' );

// ---------------------------------------------------------------- backdate lifecycle stage dates
// The nightly job stamps today as the stage date on a fresh site, so every "days in stage" reads 0 and
// the At Risk (30+ days) widget stays empty. Give each customer a plausible stage entry date instead.
$rows = $wpdb->get_results( "SELECT user_id, lifecycle_stage FROM {$intel_table} WHERE lifecycle_stage IS NOT NULL" );
foreach ( $rows as $row ) {
	$days = in_array( $row->lifecycle_stage, array( 'At Risk', 'Churned' ), true ) ? seed_rand( 31, 120 ) : seed_rand( 3, 60 );
	$date = gmdate( 'Y-m-d H:i:s', $NOW - $days * DAY_IN_SECONDS );
	$wpdb->update( $intel_table, array( 'lifecycle_stage_date' => $date, 'lifecycle_days_in_stage' => $days ), array( 'user_id' => $row->user_id ) );
	$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->prefix}pdwchs_lifecycle_history SET entered_date = %s, days_in_stage = %d WHERE user_id = %d AND exited_date IS NULL", $date, $days, $row->user_id ) );
}
$wpdb->query( "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_pdwchs_%' OR option_name LIKE '_transient_timeout_pdwchs_%'" );
WP_CLI::log( 'Lifecycle stage dates backdated for ' . count( $rows ) . ' customers.' );
