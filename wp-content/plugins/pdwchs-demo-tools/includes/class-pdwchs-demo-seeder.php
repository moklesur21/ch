<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Executes a PDWCHS_Demo_Plan against the live WordPress/WooCommerce install.
 *
 * Every public step_* method is idempotent (guarded by the ledger) so the
 * background runner can re-enter after a died tick, and the CLI can run the
 * same steps synchronously.
 */
class PDWCHS_Demo_Seeder {

	const ORDER_BATCH = 40;

	/** @var PDWCHS_Demo_Plan */
	private $plan;

	private $catalog = null;

	public function __construct( PDWCHS_Demo_Plan $plan ) {
		$this->plan = $plan;
	}

	/**
	 * Silence every side effect mass-creation would otherwise fire: all
	 * outgoing mail (WC transactional + churn alert emails) and the product
	 * plugin's per-order intelligence recalc chain. Called once per process
	 * before any mutating step.
	 */
	public static function suppress_side_effects() {
		add_filter( 'pre_wp_mail', '__return_false', PHP_INT_MAX );
		$intel = apply_filters( 'pdwchs_intelligence_instance', null );
		if ( $intel ) {
			remove_action( 'woocommerce_order_status_completed', array( $intel, 'pdwchs_on_order_completed' ) );
		}
	}

	// ── step: store identity (recording-guide Phase 0) ──────────────────

	public function step_identity() {
		$store = $this->plan->build()['store'];

		update_option( 'blogname', $store['blogname'] );
		update_option( 'woocommerce_currency', $store['currency'] );
		update_option( 'woocommerce_calc_taxes', 'no' );

		// Human admin display name ("Howdy, Sarah M." + the Assigned To dropdown).
		$admins = get_users( array( 'role' => 'administrator', 'orderby' => 'ID', 'order' => 'ASC', 'number' => 1 ) );
		if ( $admins ) {
			$parts = preg_split( '/\s+/', trim( $store['admin_name'] ), 2 );
			wp_update_user( array(
				'ID'           => $admins[0]->ID,
				'display_name' => $store['admin_name'],
				'first_name'   => $parts[0],
				'last_name'    => isset( $parts[1] ) ? $parts[1] : '',
				'nickname'     => $store['admin_name'],
			) );
		}
		return 'identity applied: ' . $store['blogname'] . ' / ' . $store['currency'];
	}

	// ── step: product catalog ────────────────────────────────────────────

	/**
	 * Resolved catalog: array of [id, name, slug, price, cat]. Sources, in
	 * order: data/catalog.json (creating any missing product, sideloading its
	 * bundled image), else the store's existing products, else a built-in
	 * minimal catalog created on the spot.
	 */
	public function catalog_products() {
		if ( null !== $this->catalog ) {
			return $this->catalog;
		}

		$entries = array();
		$file    = PDWCHS_DEMO_TOOLS_DATA_DIR . 'catalog.json';
		if ( file_exists( $file ) ) {
			$decoded = json_decode( (string) file_get_contents( $file ), true );
			if ( is_array( $decoded ) && ! empty( $decoded['products'] ) ) {
				$entries = $decoded['products'];
			}
		}

		if ( ! $entries ) {
			$existing = wc_get_products( array( 'limit' => 24, 'status' => 'publish', 'orderby' => 'ID', 'order' => 'ASC' ) );
			if ( $existing ) {
				foreach ( $existing as $p ) {
					$cats = wp_get_post_terms( $p->get_id(), 'product_cat', array( 'fields' => 'names' ) );
					$this->catalog[] = array(
						'id'    => $p->get_id(),
						'name'  => $p->get_name(),
						'slug'  => $p->get_slug(),
						'price' => (float) $p->get_price(),
						'cat'   => $cats ? $cats[0] : 'General',
					);
				}
				return $this->catalog;
			}
			$entries = $this->builtin_catalog();
		}

		$this->catalog = array();
		foreach ( $entries as $entry ) {
			$product_id = $this->find_product_by_slug( $entry['slug'] );
			if ( ! $product_id ) {
				$product_id = $this->create_catalog_product( $entry );
			}
			if ( $product_id ) {
				$this->catalog[] = array(
					'id'    => $product_id,
					'name'  => $entry['name'],
					'slug'  => $entry['slug'],
					'price' => (float) $entry['price'],
					'cat'   => isset( $entry['category'] ) ? $entry['category'] : 'General',
				);
			}
		}
		return $this->catalog;
	}

	public function step_catalog() {
		$products = $this->catalog_products();
		return 'catalog ready: ' . count( $products ) . ' products';
	}

	private function find_product_by_slug( $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		return $post ? (int) $post->ID : 0;
	}

	private function create_catalog_product( $entry ) {
		$product = new WC_Product_Simple();
		$product->set_name( $entry['name'] );
		$product->set_slug( $entry['slug'] );
		$product->set_regular_price( (string) $entry['price'] );
		$product->set_status( 'publish' );
		if ( ! empty( $entry['sku'] ) && ! wc_get_product_id_by_sku( $entry['sku'] ) ) {
			$product->set_sku( $entry['sku'] );
		}
		$product->add_meta_data( '_pdwchs_demo', 1, true );
		$product_id = $product->save();

		if ( ! empty( $entry['category'] ) ) {
			wp_set_object_terms( $product_id, $entry['category'], 'product_cat' );
		}

		if ( ! empty( $entry['image'] ) ) {
			$src = PDWCHS_DEMO_TOOLS_DATA_DIR . 'images/' . basename( $entry['image'] );
			if ( file_exists( $src ) ) {
				$bits = wp_upload_bits( basename( $src ), null, (string) file_get_contents( $src ) );
				if ( empty( $bits['error'] ) ) {
					$attachment_id = wp_insert_attachment( array(
						'post_mime_type' => (string) wp_check_filetype( $bits['file'] )['type'],
						'post_title'     => $entry['name'],
						'post_status'    => 'inherit',
					), $bits['file'], $product_id );
					if ( $attachment_id && ! is_wp_error( $attachment_id ) ) {
						require_once ABSPATH . 'wp-admin/includes/image.php';
						wp_update_attachment_metadata( $attachment_id, wp_generate_attachment_metadata( $attachment_id, $bits['file'] ) );
						set_post_thumbnail( $product_id, $attachment_id );
					}
				}
			}
		}

		$ledger = PDWCHS_Demo_Ledger::get();
		if ( $ledger ) {
			$ledger['product_ids'][] = $product_id;
			PDWCHS_Demo_Ledger::save( $ledger );
		}
		return $product_id;
	}

	/** Last-resort catalog for a completely empty store. */
	private function builtin_catalog() {
		$items = array(
			array( 'Harbor Canvas Tote', 34 ), array( 'Northline Field Jacket', 129 ),
			array( 'Cedar Trail Water Bottle', 24 ), array( 'Summit Wool Beanie', 22 ),
			array( 'Coastal Enamel Mug', 18 ), array( 'Redwood Utility Belt', 42 ),
			array( 'Basecamp Duffel 40L', 98 ), array( 'Granite Trekking Socks', 16 ),
			array( 'Lakeside Flannel Shirt', 58 ), array( 'Drift Merino Hoodie', 88 ),
			array( 'Outpost Camp Knife', 64 ), array( 'Meridian Day Pack', 74 ),
		);
		$out = array();
		foreach ( $items as $i => $item ) {
			$out[] = array(
				'name'     => $item[0],
				'slug'     => sanitize_title( $item[0] ),
				'price'    => $item[1],
				'sku'      => sprintf( 'PDDEMO-%03d', $i + 1 ),
				'category' => ( $i % 3 === 0 ) ? 'Outdoor Gear' : ( ( $i % 3 === 1 ) ? 'Apparel' : 'Camp Kitchen' ),
			);
		}
		return $out;
	}

	// ── step: customers ──────────────────────────────────────────────────

	public function step_users() {
		$plan   = $this->plan->build();
		$ledger = PDWCHS_Demo_Ledger::get();
		$made   = 0;

		foreach ( $plan['customers'] as $c ) {
			if ( isset( $ledger['user_map'][ $c['key'] ] ) ) {
				continue;
			}
			$existing = get_user_by( 'email', $c['email'] );
			if ( $existing ) {
				$user_id = $existing->ID;
			} else {
				$user_id = wp_insert_user( array(
					'user_login'      => $c['login'],
					'user_pass'       => wp_generate_password( 24 ),
					'user_email'      => $c['email'],
					'first_name'      => $c['first'],
					'last_name'       => $c['last'],
					'display_name'    => $c['first'] . ' ' . $c['last'],
					'role'            => 'customer',
					'user_registered' => gmdate( 'Y-m-d H:i:s', $this->plan->gmt_ts( $c['registered'], 10 ) ),
				) );
				if ( is_wp_error( $user_id ) ) {
					continue;
				}
			}
			update_user_meta( $user_id, '_pdwchs_demo', 1 );
			$addr = $this->address_for( $c['key'] );
			update_user_meta( $user_id, 'billing_first_name', $c['first'] );
			update_user_meta( $user_id, 'billing_last_name', $c['last'] );
			update_user_meta( $user_id, 'billing_email', $c['email'] );
			foreach ( $addr as $meta => $value ) {
				update_user_meta( $user_id, 'billing_' . $meta, $value );
			}
			$ledger['user_map'][ $c['key'] ] = $user_id;
			$made++;
		}
		PDWCHS_Demo_Ledger::save( $ledger );
		return "users ready: {$made} created, " . count( $ledger['user_map'] ) . ' total';
	}

	private function address_for( $key ) {
		$pool = array(
			array( '412 Birchwood Ave', 'Portland', 'OR', '97205' ),
			array( '88 Camden Row', 'Austin', 'TX', '78704' ),
			array( '1520 Willow Creek Dr', 'Denver', 'CO', '80211' ),
			array( '77 Beacon Hill Rd', 'Boston', 'MA', '02108' ),
			array( '2311 Sycamore Ln', 'Nashville', 'TN', '37203' ),
			array( '605 Harborview St', 'Seattle', 'WA', '98107' ),
			array( '341 Juniper Ct', 'Madison', 'WI', '53703' ),
			array( '19 Fulton Market St', 'Savannah', 'GA', '31401' ),
		);
		$a = $pool[ abs( crc32( $key ) ) % count( $pool ) ];
		return array( 'address_1' => $a[0], 'city' => $a[1], 'state' => $a[2], 'postcode' => $a[3], 'country' => 'US' );
	}

	// ── step: orders (chunked) ───────────────────────────────────────────

	/** Deterministic flat creation list, oldest order first (IDs ascend with dates). */
	public function order_specs() {
		$plan  = $this->plan->build();
		$specs = array();
		foreach ( $plan['customers'] as $c ) {
			foreach ( $c['orders'] as $idx => $o ) {
				$specs[] = array( 'type' => 'c', 'key' => $c['key'], 'idx' => $idx, 'spec' => $o, 'customer' => $c );
			}
		}
		foreach ( $plan['guest_orders'] as $gidx => $g ) {
			if ( 'browse' === $gidx ) {
				continue;
			}
			$specs[] = array( 'type' => 'g', 'key' => 'guest', 'idx' => $gidx, 'spec' => $g, 'customer' => null );
		}
		usort( $specs, function ( $a, $b ) {
			if ( $a['spec']['days'] !== $b['spec']['days'] ) {
				return ( $a['spec']['days'] < $b['spec']['days'] ) ? 1 : -1; // larger days-ago (older) first
			}
			return strcmp( $a['key'] . $a['idx'], $b['key'] . $b['idx'] );
		} );
		return $specs;
	}

	public function order_batches() {
		return (int) ceil( count( $this->order_specs() ) / self::ORDER_BATCH );
	}

	public function step_orders( $batch ) {
		$specs  = $this->order_specs();
		$slice  = array_slice( $specs, $batch * self::ORDER_BATCH, self::ORDER_BATCH );
		$ledger = PDWCHS_Demo_Ledger::get();
		$made   = 0;

		foreach ( $slice as $row ) {
			if ( 'c' === $row['type'] ) {
				if ( isset( $ledger['order_map'][ $row['key'] ][ $row['idx'] ] ) ) {
					continue;
				}
				$user_id  = isset( $ledger['user_map'][ $row['key'] ] ) ? (int) $ledger['user_map'][ $row['key'] ] : 0;
				$order_id = $this->create_order( $row['spec'], $user_id, $row['customer'] );
				if ( $order_id ) {
					$ledger['order_map'][ $row['key'] ][ $row['idx'] ] = $order_id;
					$made++;
				}
			} else {
				if ( isset( $ledger['guest_order_ids'][ $row['idx'] ] ) ) {
					continue;
				}
				$order_id = $this->create_order( $row['spec'], 0, null );
				if ( $order_id ) {
					$ledger['guest_order_ids'][ $row['idx'] ] = $order_id;
					$made++;
				}
			}
		}
		PDWCHS_Demo_Ledger::save( $ledger );
		return "orders batch {$batch}: {$made} created";
	}

	private function create_order( $spec, $user_id, $customer ) {
		$order = wc_create_order( array( 'customer_id' => $user_id, 'created_via' => 'checkout' ) );
		if ( is_wp_error( $order ) ) {
			return 0;
		}

		foreach ( $this->basket_lines( $spec, $customer ) as $line ) {
			$product = wc_get_product( $line['product_id'] );
			if ( ! $product ) {
				continue;
			}
			$order->add_product( $product, $line['qty'], array(
				'subtotal' => $line['total'],
				'total'    => $line['total'],
			) );
		}

		$first = $customer ? $customer['first'] : $spec['first'];
		$last  = $customer ? $customer['last'] : $spec['last'];
		$email = $customer ? $customer['email'] : $spec['email'];
		$addr  = $this->address_for( $customer ? $customer['key'] : 'guest' . $spec['days'] );

		$address = array_merge( array( 'first_name' => $first, 'last_name' => $last, 'email' => $email ), $addr );
		$order->set_address( $address, 'billing' );
		unset( $address['email'] );
		$order->set_address( $address, 'shipping' );
		$order->set_payment_method( 'bacs' );
		$order->set_payment_method_title( 'Credit Card' );
		$order->calculate_totals( false );

		$order->set_status( $spec['status'] );
		$ts = $this->plan->gmt_ts( $spec['days'], $spec['hh'], $spec['mm'] );
		$order->set_date_created( $ts );
		$order->set_date_paid( $ts + 240 );
		if ( 'completed' === $spec['status'] ) {
			$order->set_date_completed( $ts + 2 * HOUR_IN_SECONDS );
		}
		$order->add_meta_data( '_pdwchs_demo', 1, true );
		$order->save();

		return $order->get_id();
	}

	/** Split a target total into 1-2 deterministic product lines (line totals overridden to hit the target exactly). */
	private function basket_lines( $spec, $customer ) {
		$catalog = $this->catalog_products();
		if ( ! $catalog ) {
			return array();
		}
		$n    = count( $catalog );
		$pref = $customer ? $customer['pref'] : array( (int) $spec['days'] % $n, ( (int) $spec['days'] + 5 ) % $n, 0 );

		$total = (float) $spec['total'];
		if ( $total >= 105 ) {
			$a = round( $total * 0.62, 2 );
			return array(
				array( 'product_id' => $catalog[ $pref[0] % $n ]['id'], 'qty' => 1, 'total' => $a ),
				array( 'product_id' => $catalog[ $pref[1] % $n ]['id'], 'qty' => 1, 'total' => round( $total - $a, 2 ) ),
			);
		}
		return array(
			array( 'product_id' => $catalog[ $pref[0] % $n ]['id'], 'qty' => 1, 'total' => round( $total, 2 ) ),
		);
	}

	// ── step: sessions ───────────────────────────────────────────────────

	public function step_sessions() {
		global $wpdb;
		$table  = $wpdb->prefix . 'pd_woo_customer_history_sessions';
		$ledger = PDWCHS_Demo_Ledger::get();

		// Self-heal a half-written run: wipe previously recorded rows, redo fully.
		if ( ! PDWCHS_Demo_Ledger::has_flag( 'sessions' ) && ! empty( $ledger['session_ids'] ) ) {
			$ids = implode( ',', array_map( 'absint', $ledger['session_ids'] ) );
			$wpdb->query( "DELETE FROM {$table} WHERE id IN ({$ids})" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$ledger['session_ids'] = array();
		}
		if ( PDWCHS_Demo_Ledger::has_flag( 'sessions' ) ) {
			return 'sessions: already done (' . count( $ledger['session_ids'] ) . ' rows)';
		}

		mt_srand( $this->plan->rng_seed() + 77 );
		$plan    = $this->plan->build();
		$catalog = $this->catalog_products();
		$rows    = array();

		foreach ( $plan['customers'] as $c ) {
			$user_id = isset( $ledger['user_map'][ $c['key'] ] ) ? (int) $ledger['user_map'][ $c['key'] ] : 0;
			if ( ! $user_id ) {
				continue;
			}
			foreach ( $c['visits'] as $vi => $visit ) {
				$order_id = null;
				if ( 'order' === $visit['kind'] && null !== $visit['order_idx'] && isset( $ledger['order_map'][ $c['key'] ][ $visit['order_idx'] ] ) ) {
					$order_id = (int) $ledger['order_map'][ $c['key'] ][ $visit['order_idx'] ];
				}
				$rows = array_merge( $rows, $this->visit_rows( $c, $user_id, $vi, $visit, $order_id, $catalog ) );
			}
		}

		// Guest traffic: order trails + browse-only volume for the funnel.
		foreach ( $plan['guest_orders'] as $gidx => $g ) {
			if ( 'browse' === $gidx || ! isset( $ledger['guest_order_ids'][ $gidx ] ) ) {
				continue;
			}
			$visit = array( 'days' => $g['days'], 'hh' => $g['hh'], 'mm' => max( 0, $g['mm'] - 8 ), 'kind' => 'order', 'order_idx' => null, 'pages' => 3, 'search' => false );
			$rows  = array_merge( $rows, $this->visit_rows( null, 0, 900 + $gidx, $visit, (int) $ledger['guest_order_ids'][ $gidx ], $catalog ) );
		}
		$browse = $plan['guest_orders']['browse'];
		for ( $i = 0; $i < $browse['recent_visits'] + $browse['older_visits']; $i++ ) {
			$days  = ( $i < $browse['recent_visits'] ) ? mt_rand( 0, 29 ) : mt_rand( 30, 60 );
			$visit = array( 'days' => $days, 'hh' => mt_rand( 8, 23 ), 'mm' => mt_rand( 0, 59 ), 'kind' => 'browse', 'order_idx' => null, 'pages' => mt_rand( 1, 4 ), 'search' => ( 0 === $i % 5 ) );
			if ( $i < $browse['atc_no_buy'] ) {
				$visit['kind'] = 'atc_only';
			} elseif ( $i >= $browse['atc_no_buy'] && $i < $browse['atc_no_buy'] + $browse['checkout_no_buy'] ) {
				$visit['kind'] = 'checkout_only';
			}
			$rows = array_merge( $rows, $this->visit_rows( null, 0, 2000 + $i, $visit, null, $catalog ) );
		}

		$ids = array();
		foreach ( $rows as $r ) {
			$wpdb->insert( $table, $r, array( '%d', '%s', '%s', '%s', '%s', '%d', '%s' ) );
			if ( $wpdb->insert_id ) {
				$ids[] = $wpdb->insert_id;
			}
		}
		$ledger                = PDWCHS_Demo_Ledger::get();
		$ledger['session_ids'] = $ids;
		$ledger['flags']['sessions'] = true;
		PDWCHS_Demo_Ledger::save( $ledger );

		return 'sessions: ' . count( $ids ) . ' rows inserted';
	}

	/** Build the row sequence of one visit, mimicking the live tracker's URL grammar. */
	private function visit_rows( $customer, $user_id, $visit_index, $visit, $order_id, $catalog ) {
		$n          = count( $catalog );
		$key        = $customer ? $customer['key'] : 'guest';
		$session_id = substr( md5( $this->plan->rng_seed() . '|' . $key . '|' . $visit_index . '|' . $visit['days'] ), 0, 16 );
		$ip         = $customer ? $customer['ip'] : sprintf( '203.%d.%d.%d', mt_rand( 10, 240 ), mt_rand( 10, 240 ), mt_rand( 2, 250 ) );

		if ( $customer && ! empty( $customer['mobile'] ) ) {
			$referer = 'Mozilla/5.0 (iPhone; CPU iPhone OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Mobile/15E148';
		} else {
			$roll    = mt_rand( 1, 10 );
			$referer = $roll <= 4 ? 'https://www.google.com/' : ( $roll <= 7 ? home_url( '/' ) : '' );
		}

		$pref = $customer ? $customer['pref'] : array( mt_rand( 0, $n - 1 ), mt_rand( 0, $n - 1 ), 0 );
		$p0   = $catalog[ $pref[0] % $n ];
		$p1   = $catalog[ $pref[1] % $n ];

		$urls   = array();
		$urls[] = ( 0 === mt_rand( 0, 1 ) ) ? 'shop' : 'product-category/' . sanitize_title( $p0['cat'] );
		$urls[] = 'product/' . $p0['slug'];
		if ( $visit['pages'] >= 3 ) {
			$urls[] = 'product/' . $p1['slug'];
		}
		if ( ! empty( $visit['search'] ) ) {
			$term   = strtolower( strtok( $p1['name'], ' ' ) );
			$urls[] = 'ACTION::search::' . $term;
		}
		if ( 'atc_only' === $visit['kind'] || 'checkout_only' === $visit['kind'] || ( 'order' === $visit['kind'] && $order_id ) ) {
			$urls[] = 'ACTION::add_to_cart::' . $p0['id'] . '::1';
		}
		if ( 'checkout_only' === $visit['kind'] || ( 'order' === $visit['kind'] && $order_id ) ) {
			$urls[] = 'checkout';
		}
		if ( 'order' === $visit['kind'] && $order_id ) {
			$urls[] = 'checkout/order-received/' . $order_id;
			$urls[] = 'ACTION::new_order::' . $order_id;
		}

		$rows = array();
		foreach ( $urls as $i => $url ) {
			$rows[] = array(
				'user_id'    => $user_id,
				'ip'         => $ip,
				'url'        => $url,
				'referer'    => $referer,
				'reg_date'   => $this->plan->local_datetime( $visit['days'], $visit['hh'], min( 59, $visit['mm'] + $i ) ),
				'del'        => 0,
				'session_id' => $session_id,
			);
		}
		return $rows;
	}

	// ── step: carts ──────────────────────────────────────────────────────

	public function step_carts() {
		global $wpdb;
		$table  = $wpdb->prefix . 'pdwchs_carts';
		$ledger = PDWCHS_Demo_Ledger::get();

		if ( ! PDWCHS_Demo_Ledger::has_flag( 'carts' ) && ! empty( $ledger['cart_ids'] ) ) {
			$ids = implode( ',', array_map( 'absint', $ledger['cart_ids'] ) );
			$wpdb->query( "DELETE FROM {$table} WHERE id IN ({$ids})" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$ledger['cart_ids'] = array();
		}
		if ( PDWCHS_Demo_Ledger::has_flag( 'carts' ) ) {
			return 'carts: already done (' . count( $ledger['cart_ids'] ) . ' rows)';
		}

		mt_srand( $this->plan->rng_seed() + 177 );
		$plan    = $this->plan->build();
		$catalog = $this->catalog_products();
		$now_ts  = current_time( 'timestamp' );
		$ids     = array();

		foreach ( $plan['carts'] as $ci => $cart ) {
			$user_id = null;
			$email   = null;
			$cust    = null;
			if ( $cart['owner'] ) {
				foreach ( $plan['customers'] as $c ) {
					if ( $c['key'] === $cart['owner'] ) {
						$cust = $c;
						break;
					}
				}
				$user_id = isset( $ledger['user_map'][ $cart['owner'] ] ) ? (int) $ledger['user_map'][ $cart['owner'] ] : null;
				$email   = $cust ? $cust['email'] : null;
			} elseif ( 1 === mt_rand( 0, 1 ) ) {
				$email = 'shopper' . $ci . '@example.com';
			}

			$order_id = null;
			$total    = $cart['total'];
			$days     = $cart['days'];
			$order_local_ts = null;
			if ( null !== $cart['order_ref'] && $cust && isset( $ledger['order_map'][ $cust['key'] ][0] ) ) {
				$order_id       = (int) $ledger['order_map'][ $cust['key'] ][0];
				$order_spec     = $cust['orders'][0];
				$total          = $order_spec['total'];
				$days           = $order_spec['days'];
				$order_local_ts = $this->plan->local_ts( $order_spec['days'], $order_spec['hh'], $order_spec['mm'] );
			}

			if ( 'active' === $cart['status'] ) {
				$created_ts = $now_ts - 25 * MINUTE_IN_SECONDS - $ci * 60;
			} else {
				$created_ts = $order_local_ts
					? $order_local_ts - ( $cart['abandon_h'] + 26 ) * HOUR_IN_SECONDS
					: $this->plan->local_ts( $days, $cart['hh'], mt_rand( 0, 59 ) );
			}

			$contents = $this->cart_contents_json( $catalog, $cust, $cart['items'], (float) $total );
			$row      = array(
				'user_id'       => $user_id,
				'session_key'   => $user_id ? (string) $user_id : 't_' . substr( md5( 'cart' . $ci . $this->plan->rng_seed() ), 0, 24 ),
				'email'         => $email,
				'status'        => $cart['status'],
				'cart_contents' => $contents,
				'contents_hash' => md5( $contents ),
				'cart_hash'     => md5( 'demo' . $ci ),
				'items_count'   => (int) $cart['items'],
				'cart_total'    => round( (float) $total, 2 ),
				'currency'      => 'USD',
				'ip_address'    => $cust ? $cust['ip'] : sprintf( '203.0.%d.%d', mt_rand( 10, 240 ), mt_rand( 2, 250 ) ),
				'user_agent'    => 'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36',
				'created_at'    => gmdate( 'Y-m-d H:i:s', $created_ts ),
				'updated_at'    => gmdate( 'Y-m-d H:i:s', $created_ts ),
				'abandoned_at'  => null,
				'recovered_at'  => null,
				'completed_at'  => null,
				'order_id'      => null,
			);

			if ( 'abandoned' === $cart['status'] || 'recovered' === $cart['status'] ) {
				$abandoned_ts        = $created_ts + $cart['abandon_h'] * HOUR_IN_SECONDS;
				$row['abandoned_at'] = gmdate( 'Y-m-d H:i:s', $abandoned_ts );
				$row['updated_at']   = $row['abandoned_at'];
			}
			if ( 'recovered' === $cart['status'] && $order_id ) {
				$row['recovered_at'] = gmdate( 'Y-m-d H:i:s', $order_local_ts );
				$row['updated_at']   = $row['recovered_at'];
				$row['order_id']     = $order_id;
			}
			if ( 'completed' === $cart['status'] && $order_id ) {
				$row['completed_at'] = gmdate( 'Y-m-d H:i:s', $order_local_ts );
				$row['created_at']   = gmdate( 'Y-m-d H:i:s', $order_local_ts - 2 * HOUR_IN_SECONDS );
				$row['updated_at']   = $row['completed_at'];
				$row['order_id']     = $order_id;
			}

			$wpdb->insert( $table, $row );
			if ( $wpdb->insert_id ) {
				$ids[] = $wpdb->insert_id;
			}
		}

		$ledger                   = PDWCHS_Demo_Ledger::get();
		$ledger['cart_ids']       = $ids;
		$ledger['flags']['carts'] = true;
		PDWCHS_Demo_Ledger::save( $ledger );

		return 'carts: ' . count( $ids ) . ' rows inserted';
	}

	/** WC()->cart->get_cart() shaped JSON, enough for the Abandoned Carts list table. */
	private function cart_contents_json( $catalog, $cust, $items_count, $total ) {
		$n     = count( $catalog );
		$pref  = $cust ? $cust['pref'] : array( mt_rand( 0, $n - 1 ), mt_rand( 0, $n - 1 ), mt_rand( 0, $n - 1 ) );
		$share = $items_count > 0 ? round( $total / $items_count, 2 ) : 0;
		$out   = array();
		for ( $i = 0; $i < $items_count; $i++ ) {
			$p    = $catalog[ $pref[ $i % 3 ] % $n ];
			$line = ( $i === $items_count - 1 ) ? round( $total - $share * ( $items_count - 1 ), 2 ) : $share;
			$key  = md5( $p['id'] . '|' . $i );
			$out[ $key ] = array(
				'key'               => $key,
				'product_id'        => $p['id'],
				'variation_id'      => 0,
				'variation'         => array(),
				'quantity'          => 1,
				'line_tax_data'     => array( 'subtotal' => array(), 'total' => array() ),
				'line_subtotal'     => $line,
				'line_subtotal_tax' => 0,
				'line_total'        => $line,
				'line_tax'          => 0,
			);
		}
		return (string) wp_json_encode( $out );
	}
}
