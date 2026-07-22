<?php
/**
 * Storefront setup: turn the bare WooCommerce install into a branded, believable
 * shop ("Northline Supply Co.") for on-camera browsing.
 *
 * Companion to the data seeder. The seeder builds customers/orders/analytics; this
 * builds the *look*: activates the Northline child theme, gives products real copy
 * and clean categories, sets currency/brand, and creates the About/Contact/shipping
 * a real store has. Idempotent — safe to re-run. Mutating, so gated on
 * pdwchs_demo_allowed() like every other write path in this plugin.
 *
 * Run:  wp pdwchs-demo setup-storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PDWCHS_Demo_Storefront {

	const THEME       = 'northline';
	const STORE_NAME  = 'Northline Supply Co.';
	const TAGLINE     = 'Well-made everyday essentials';

	/** Product slugs to retire from the storefront (kept as drafts, not deleted). */
	const DRAFT_SLUGS = array( 'album', 'single', 'logo-collection', 'wordpress-pennant' );

	/** Categories kept on the storefront; every other product_cat is pruned. */
	const KEEP_CATS = array( 'Hoodies', 'Tees', 'Accessories', 'Uncategorized' );

	/** @var string[] Progress log, returned to the caller (CLI prints it). */
	private static $log = array();

	/**
	 * Orchestrate the whole storefront setup.
	 *
	 * @return array{ok:bool,log:string[],message?:string}
	 */
	public static function setup() {
		self::$log = array();

		if ( ! pdwchs_demo_allowed() ) {
			return array( 'ok' => false, 'log' => self::$log, 'message' => pdwchs_demo_blocked_message() );
		}
		if ( ! function_exists( 'WC' ) ) {
			return array( 'ok' => false, 'log' => self::$log, 'message' => 'WooCommerce is not active.' );
		}

		self::activate_theme();
		self::configure_store();
		self::upsert_products();
		self::draft_oddities();
		self::prune_categories();
		self::clean_brands();
		self::ensure_pages();
		self::ensure_shipping();
		self::cleanup();

		self::say( 'Storefront setup complete.' );
		return array( 'ok' => true, 'log' => self::$log );
	}

	private static function say( $msg ) {
		self::$log[] = $msg;
	}

	/* --------------------------------------------------------------------- */

	private static function activate_theme() {
		$theme = wp_get_theme( self::THEME );
		if ( ! $theme->exists() ) {
			self::say( "Theme '" . self::THEME . "' not found in wp-content/themes — skipping activation." );
			return;
		}
		if ( get_stylesheet() !== self::THEME ) {
			switch_theme( self::THEME );
			self::say( "Activated theme: " . $theme->get( 'Name' ) . '.' );
		} else {
			self::say( 'Theme already active: ' . $theme->get( 'Name' ) . '.' );
		}
	}

	private static function configure_store() {
		// Keep the seeder and the storefront in agreement on the store name.
		update_option( 'pdwchs_demo_store_name', self::STORE_NAME );
		update_option( 'blogname', self::STORE_NAME );
		update_option( 'blogdescription', self::TAGLINE );

		update_option( 'woocommerce_currency', 'USD' );
		update_option( 'woocommerce_default_country', 'US:CA' );
		update_option( 'woocommerce_currency_pos', 'left' );
		update_option( 'woocommerce_price_thousand_sep', ',' );
		update_option( 'woocommerce_price_decimal_sep', '.' );
		update_option( 'woocommerce_calc_taxes', 'no' );

		// Tidy shop presentation.
		update_option( 'woocommerce_catalog_columns', 4 );
		update_option( 'woocommerce_catalog_rows', 4 );

		self::say( 'Store identity set (name, tagline, USD currency, US base).' );
	}

	/**
	 * Give each catalog product real copy, a clean category, and a featured flag,
	 * from data/catalog.json. Matches existing products by slug (created earlier by
	 * the seeder); creates them if missing. This is why a rebrand needs no reseed.
	 */
	private static function upsert_products() {
		$file = PDWCHS_DEMO_TOOLS_DATA_DIR . 'catalog.json';
		if ( ! is_readable( $file ) ) {
			self::say( 'catalog.json not readable — skipping product copy.' );
			return;
		}
		$decoded = json_decode( (string) file_get_contents( $file ), true );
		$entries = isset( $decoded['products'] ) && is_array( $decoded['products'] ) ? $decoded['products'] : array();
		if ( ! $entries ) {
			self::say( 'catalog.json has no products — skipping product copy.' );
			return;
		}

		$updated = 0;
		foreach ( $entries as $entry ) {
			$slug = isset( $entry['slug'] ) ? $entry['slug'] : '';
			if ( '' === $slug ) {
				continue;
			}
			$product_id = self::product_id_by_slug( $slug );
			if ( ! $product_id ) {
				continue; // Seeder owns creation; nothing to re-describe yet.
			}
			$product = wc_get_product( $product_id );
			if ( ! $product ) {
				continue;
			}

			if ( ! empty( $entry['name'] ) ) {
				$product->set_name( $entry['name'] );
			}
			if ( isset( $entry['description'] ) ) {
				$product->set_description( $entry['description'] );
			}
			if ( isset( $entry['short_description'] ) ) {
				$product->set_short_description( $entry['short_description'] );
			}
			if ( isset( $entry['featured'] ) ) {
				$product->set_featured( (bool) $entry['featured'] );
			}
			if ( ! empty( $entry['category'] ) ) {
				$term_id = self::ensure_category( $entry['category'] );
				if ( $term_id ) {
					$product->set_category_ids( array( $term_id ) );
				}
			}
			// Coherent apparel pricing on simple products; variable products keep
			// their per-variation prices. Clear stray sample sale prices either way.
			if ( $product->is_type( 'simple' ) && isset( $entry['price'] ) ) {
				$product->set_regular_price( (string) $entry['price'] );
				$product->set_sale_price( '' );
			}
			$product->set_catalog_visibility( 'visible' );
			$product->set_status( 'publish' );
			$product->save();

			// Variable products: clear stray sample sale prices on each variation
			// so no unexpected "Sale!" badge appears.
			if ( $product->is_type( 'variable' ) ) {
				foreach ( $product->get_children() as $variation_id ) {
					$variation = wc_get_product( $variation_id );
					if ( $variation && $variation->get_sale_price() !== '' ) {
						$variation->set_sale_price( '' );
						$variation->save();
					}
				}
			}
			$updated++;
		}
		self::say( "Applied copy + categories to {$updated} products." );
	}

	/** Retire off-brand sample products (Album, Single, etc.) to draft. */
	private static function draft_oddities() {
		$drafted = 0;
		foreach ( self::DRAFT_SLUGS as $slug ) {
			$id = self::product_id_by_slug( $slug );
			if ( $id && get_post_status( $id ) !== 'draft' ) {
				wp_update_post( array( 'ID' => $id, 'post_status' => 'draft' ) );
				$drafted++;
			}
		}
		if ( $drafted ) {
			self::say( "Retired {$drafted} off-brand sample products to draft." );
		}
	}

	/** Remove the leftover sample categories (Clothing, Music, Decor, Tshirts…). */
	private static function prune_categories() {
		$pruned = 0;
		$terms  = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return;
		}
		foreach ( $terms as $term ) {
			if ( in_array( $term->name, self::KEEP_CATS, true ) ) {
				continue;
			}
			wp_delete_term( $term->term_id, 'product_cat' );
			$pruned++;
		}
		if ( function_exists( '_wc_term_recount' ) ) {
			$kept = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
			if ( ! is_wp_error( $kept ) ) {
				_wc_term_recount( $kept, get_taxonomy( 'product_cat' ), true, false );
			}
		}
		if ( $pruned ) {
			self::say( "Pruned {$pruned} leftover sample categories." );
		}
	}

	/** Single-brand store: drop the sample "Brands" terms (Hype, Luxe Leather…). */
	private static function clean_brands() {
		if ( ! taxonomy_exists( 'product_brand' ) ) {
			return;
		}
		$terms = get_terms( array( 'taxonomy' => 'product_brand', 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) || ! $terms ) {
			return;
		}
		foreach ( $terms as $term ) {
			wp_delete_term( $term->term_id, 'product_brand' );
		}
		self::say( 'Cleared ' . count( $terms ) . ' sample brand tags.' );
	}

	private static function ensure_pages() {
		self::ensure_page( 'about', 'About', self::about_content() );
		self::ensure_page( 'contact', 'Contact', self::contact_content() );
	}

	private static function ensure_shipping() {
		if ( ! class_exists( 'WC_Shipping_Zone' ) ) {
			return;
		}
		$zone     = new WC_Shipping_Zone( 0 ); // "Rest of the world" — always matches.
		$existing = array();
		foreach ( $zone->get_shipping_methods() as $method ) {
			$existing[] = $method->id;
		}

		if ( ! in_array( 'flat_rate', $existing, true ) ) {
			$instance = $zone->add_shipping_method( 'flat_rate' );
			update_option(
				'woocommerce_flat_rate_' . $instance . '_settings',
				array( 'title' => 'Flat rate', 'tax_status' => 'none', 'cost' => '6' )
			);
			self::say( 'Added flat-rate shipping ($6).' );
		}
		if ( ! in_array( 'free_shipping', $existing, true ) ) {
			$instance = $zone->add_shipping_method( 'free_shipping' );
			update_option(
				'woocommerce_free_shipping_' . $instance . '_settings',
				array( 'title' => 'Free shipping', 'requires' => 'min_amount', 'min_amount' => '50', 'ignore_discounts' => 'no' )
			);
			self::say( 'Added free shipping over $50.' );
		}
	}

	private static function cleanup() {
		$sample = get_page_by_path( 'sample-page' );
		if ( $sample && get_post_status( $sample ) !== 'trash' ) {
			wp_trash_post( $sample->ID );
			self::say( 'Trashed the default Sample Page.' );
		}
	}

	/* --------------------------- helpers --------------------------------- */

	private static function product_id_by_slug( $slug ) {
		$post = get_page_by_path( $slug, OBJECT, 'product' );
		return $post ? (int) $post->ID : 0;
	}

	private static function ensure_category( $name ) {
		$term = get_term_by( 'name', $name, 'product_cat' );
		if ( $term ) {
			return (int) $term->term_id;
		}
		$created = wp_insert_term( $name, 'product_cat' );
		return is_wp_error( $created ) ? 0 : (int) $created['term_id'];
	}

	/**
	 * Create a page by slug if absent; refresh its content if present. Idempotent.
	 */
	private static function ensure_page( $slug, $title, $content ) {
		$existing = get_page_by_path( $slug );
		if ( $existing ) {
			wp_update_post( array(
				'ID'           => $existing->ID,
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'publish',
			) );
			self::say( "Updated page: {$title}." );
			return;
		}
		wp_insert_post( array(
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => $content,
			'post_status'  => 'publish',
			'post_type'    => 'page',
		) );
		self::say( "Created page: {$title}." );
	}

	private static function about_content() {
		return <<<'HTML'
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"backgroundColor":"contrast","textColor":"base","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-base-color has-contrast-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontFamily":"var:preset|font-family|fira-code","letterSpacing":"0.2em","textTransform":"uppercase","fontSize":"0.75rem"}},"textColor":"accent-2"} -->
<p class="has-text-align-center has-accent-2-color has-text-color" style="font-family:var(--wp--preset--font-family--fira-code);font-size:0.75rem;letter-spacing:0.2em;text-transform:uppercase">48°N — Field Supply · Est. MMXIX</p>
<!-- /wp:paragraph --><!-- wp:heading {"textAlign":"center","level":1,"style":{"typography":{"fontSize":"clamp(2.25rem, 6vw, 4rem)","fontWeight":"800","letterSpacing":"-0.02em"}},"textColor":"base"} -->
<h1 class="wp-block-heading has-text-align-center has-base-color has-text-color" style="font-size:clamp(2.25rem, 6vw, 4rem);font-weight:800;letter-spacing:-0.02em">Built on the North Line.</h1>
<!-- /wp:heading --></div>
<!-- /wp:group -->

<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"640px"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"style":{"typography":{"fontSize":"1.15rem","lineHeight":"1.7"}}} -->
<p style="font-size:1.15rem;line-height:1.7">Northline started with one heavyweight hoodie and a simple frustration: everyday clothes that gave out after a season. We wanted the opposite — a small line of hard-wearing basics you buy once and keep reaching for.</p>
<!-- /wp:paragraph --><!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.7"}}} -->
<p style="line-height:1.7">Everything we make is cut for weather and built to last: dense fleece, cotton in weights that hold their shape, and hardware that doesn't quit. No seasonal churn, no fast-fashion drops — just the pieces that earn a permanent spot in your rotation.</p>
<!-- /wp:paragraph --><!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.7"}}} -->
<p style="line-height:1.7">We ship from the North Line, restock a few times a season, and stand behind every piece with a 30-day return and a made-to-last guarantee.</p>
<!-- /wp:paragraph --><!-- wp:buttons {"style":{"spacing":{"margin":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-buttons" style="margin-top:var(--wp--preset--spacing--30)"><!-- wp:button --><div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/shop/">Shop the line</a></div><!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
HTML;
	}

	private static function contact_content() {
		return <<<'HTML'
<!-- wp:group {"style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"640px"}} -->
<div class="wp-block-group" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:paragraph {"style":{"typography":{"fontFamily":"var:preset|font-family|fira-code","letterSpacing":"0.2em","textTransform":"uppercase","fontSize":"0.75rem"}},"textColor":"accent-1"} -->
<p class="has-accent-1-color has-text-color" style="font-family:var(--wp--preset--font-family--fira-code);font-size:0.75rem;letter-spacing:0.2em;text-transform:uppercase">Get in touch</p>
<!-- /wp:paragraph --><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Contact us</h1>
<!-- /wp:heading --><!-- wp:paragraph {"style":{"typography":{"lineHeight":"1.7"}}} -->
<p style="line-height:1.7">Questions about sizing, an order, or a return? We answer every message within one business day.</p>
<!-- /wp:paragraph --><!-- wp:list {"className":"is-style-none","style":{"typography":{"lineHeight":"2","fontFamily":"var:preset|font-family|fira-code","fontSize":"0.85rem","letterSpacing":"0.04em"}}} -->
<ul class="wp-block-list is-style-none" style="font-family:var(--wp--preset--font-family--fira-code);font-size:0.85rem;letter-spacing:0.04em;line-height:2"><!-- wp:list-item -->
<li>EMAIL — hello@northlinesupply.co</li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li>HOURS — Mon–Fri, 9–5 (48°N)</li>
<!-- /wp:list-item --><!-- wp:list-item -->
<li>RETURNS — 30 days, on us</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list --><!-- wp:paragraph {"style":{"typography":{"fontSize":"0.85rem"},"color":{"text":"#5a636b"}}} -->
<p style="color:#5a636b;font-size:0.85rem">This is a demo storefront running in test mode — messages and orders aren't real.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
HTML;
	}
}
