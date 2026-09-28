<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * `wp pdwchs-demo <subcommand>` — the synchronous face of the runner.
 */
class PDWCHS_Demo_CLI {

	private function logger() {
		return function ( $step, $msg ) {
			WP_CLI::log( sprintf( '  [%s] %s', $step, $msg ) );
		};
	}

	private function finish( $state, $label ) {
		if ( is_wp_error( $state ) ) {
			WP_CLI::error( $state->get_error_message() );
		}
		WP_CLI::success( $label . ' complete (run ' . $state['run_id'] . ').' );
	}

	/**
	 * Seed the demo dataset onto a clean store (identity, catalog, users,
	 * backdated orders, sessions, carts, full recalc, fixups, alerts, verify).
	 *
	 * [--anchor=<Y-m-d>]  Pin day-zero (default: today). Pin it for recording continuity.
	 * [--rng-seed=<int>]  Deterministic RNG seed (default 20260722).
	 * [--force]           Tear down an existing seeded dataset first.
	 */
	public function seed( $args, $assoc ) {
		$mode = ! empty( $assoc['force'] ) && PDWCHS_Demo_Ledger::exists() ? 'refresh' : 'seed';
		$this->finish( PDWCHS_Demo_Runner::run_sync( $mode, array(
			'anchor'   => isset( $assoc['anchor'] ) ? $assoc['anchor'] : null,
			'rng_seed' => isset( $assoc['rng-seed'] ) ? $assoc['rng-seed'] : null,
		), $this->logger() ), 'Seed' );
	}

	/**
	 * Teardown + re-seed anchored to today (or --anchor) + recalc + verify.
	 * This is the pre-demo one-liner: newest orders become today/yesterday
	 * and the hero returns to exactly 51 days of silence.
	 *
	 * [--anchor=<Y-m-d>]
	 * [--rng-seed=<int>]
	 */
	public function refresh( $args, $assoc ) {
		$this->finish( PDWCHS_Demo_Runner::run_sync( 'refresh', array(
			'anchor'   => isset( $assoc['anchor'] ) ? $assoc['anchor'] : null,
			'rng_seed' => isset( $assoc['rng-seed'] ) ? $assoc['rng-seed'] : null,
		), $this->logger() ), 'Refresh' );
	}

	/**
	 * Re-run the intelligence recalcs + fixups + alerts on the current dataset.
	 */
	public function recalc( $args, $assoc ) {
		$this->finish( PDWCHS_Demo_Runner::run_sync( 'recalc', array(), $this->logger() ), 'Recalc' );
	}

	/**
	 * Remove ONLY seeder-created data (ledger-driven). Products stay unless
	 * --with-catalog.
	 *
	 * [--with-catalog]
	 */
	public function teardown( $args, $assoc ) {
		$this->finish( PDWCHS_Demo_Runner::run_sync( 'teardown', array(
			'with_catalog' => ! empty( $assoc['with-catalog'] ),
		), $this->logger() ), 'Teardown' );
	}

	/**
	 * One-time purge of PRE-EXISTING junk data: ALL orders, all customer/
	 * subscriber users, and every analytics table. Products and settings stay.
	 * Take a backup first (guide Phase 0.9).
	 *
	 * [--yes-really]  Required.
	 *
	 * @subcommand wipe-legacy
	 */
	public function wipe_legacy( $args, $assoc ) {
		if ( empty( $assoc['yes-really'] ) ) {
			WP_CLI::error( 'Refusing: pass --yes-really (deletes ALL orders + non-admin users on this site). Backup first.' );
		}
		$this->finish( PDWCHS_Demo_Runner::run_sync( 'wipe-legacy', array(), $this->logger() ), 'Legacy wipe' );
	}

	/**
	 * Run the demo-store acceptance checks (read-only): environment, dataset
	 * shape, Pro dashboard widgets alive (warn only) and the per-video checks
	 * (v1_*, v4_*) from project_asset/docs/video-production.md §3.
	 *
	 * [--report]  Also print the answer-frame sheet: what the V1 screens show
	 *             today. Feeds the delivery note, never the narration.
	 */
	public function verify( $args, $assoc ) {
		$verifier = new PDWCHS_Demo_Verifier();
		$result   = $verifier->run();
		update_option( 'pdwchs_demo_last_verify', $result, false );

		WP_CLI\Utils\format_items( 'table', array_map( function ( $c ) {
			return array(
				'status' => strtoupper( $c['status'] ),
				'check'  => $c['label'],
				'actual' => $c['actual'],
			);
		}, $result['checks'] ), array( 'status', 'check', 'actual' ) );

		if ( ! empty( $assoc['report'] ) ) {
			WP_CLI::log( "\nAnswer-frame sheet (what the V1 screens show today):" );
			foreach ( $verifier->report() as $field => $value ) {
				WP_CLI::log( sprintf( '  %-28s %s', $field . ':', is_array( $value ) ? implode( ' | ', $value ) : $value ) );
			}
		}

		$c = $result['counts'];
		if ( $c['fail'] > 0 ) {
			WP_CLI::error( "{$c['fail']} FAIL / {$c['warn']} warn / {$c['pass']} pass — not record-ready." );
		}
		WP_CLI::success( "{$c['pass']} pass / {$c['warn']} warn / 0 fail." );
	}

	/**
	 * Print the census + the analytic hero pre-check WITHOUT touching the DB.
	 *
	 * [--anchor=<Y-m-d>]
	 * [--rng-seed=<int>]
	 */
	public function plan( $args, $assoc ) {
		$plan = new PDWCHS_Demo_Plan(
			isset( $assoc['anchor'] ) ? $assoc['anchor'] : null,
			isset( $assoc['rng-seed'] ) ? $assoc['rng-seed'] : null
		);
		$data = $plan->build();
		$e    = $data['expectations'];

		WP_CLI::log( 'Anchor: ' . $data['anchor'] . '   RNG seed: ' . $data['rng_seed'] );
		WP_CLI::log( 'Customers: ' . $e['customers'] . ' (target 70-110)   Orders: ~' . $e['orders'] . ' (target 220-400)   Carts: ' . count( $data['carts'] ) );
		WP_CLI::log( 'Planned churn census: ' . wp_json_encode( $e['risk'] ) . ' → at-risk list rows: ' . $e['at_risk_rows'] . ' (target 6-8), alerts: ' . $e['alerts'] );
		WP_CLI::log( 'Planned RFM: ' . wp_json_encode( $e['segments'] ) );
		WP_CLI::log( 'Planned lifecycle: ' . wp_json_encode( $e['stages'] ) );
		WP_CLI::log( '' );
		WP_CLI::log( 'Hero analytic pre-check (replicates Churn.php formulas):' );
		foreach ( $plan->hero_precheck() as $k => $v ) {
			WP_CLI::log( sprintf( '  %-24s %s', $k . ':', $v ) );
		}
		$pre = $plan->hero_precheck();
		if ( 'high' === $pre['risk_level'] && 51 === $pre['days_since_last_order'] ) {
			WP_CLI::success( 'Hero math lands in the High band with 51 days of silence.' );
		} else {
			WP_CLI::warning( 'Hero pre-check off-target — inspect before seeding.' );
		}
	}

	/**
	 * Ledger, run-state and last-verify summary.
	 */
	public function status( $args, $assoc ) {
		$ledger = PDWCHS_Demo_Ledger::get();
		$state  = PDWCHS_Demo_Runner::state();
		$verify = get_option( 'pdwchs_demo_last_verify', null );

		WP_CLI::log( 'Latch (PDWCHS_DEMO_ALLOW): ' . ( pdwchs_demo_allowed() ? 'ENABLED' : 'disabled — mutating commands blocked' ) );
		WP_CLI::log( 'REST token (PDWCHS_DEMO_TOKEN): ' . ( defined( 'PDWCHS_DEMO_TOKEN' ) ? 'defined' : 'not defined — REST trigger disabled' ) );
		if ( $ledger ) {
			WP_CLI::log( sprintf( 'Ledger: anchor %s, seeded %s — %d users, %d orders, %d session rows, %d carts',
				$ledger['anchor'], $ledger['seeded_at'], count( $ledger['user_map'] ),
				count( PDWCHS_Demo_Teardown::ledger_order_ids() ), count( $ledger['session_ids'] ), count( $ledger['cart_ids'] ) ) );
		} else {
			WP_CLI::log( 'Ledger: none (not seeded)' );
		}
		if ( $state ) {
			WP_CLI::log( sprintf( 'Run: %s [%s] %s %d/%d%s', $state['run_id'], $state['mode'], $state['status'], $state['next'], count( $state['steps'] ), $state['error'] ? ' — ' . $state['error'] : '' ) );
		}
		if ( $verify ) {
			WP_CLI::log( sprintf( 'Last verify: %s — %d pass / %d fail / %d warn (staleness %dd)', $verify['time'], $verify['counts']['pass'], $verify['counts']['fail'], $verify['counts']['warn'], $verify['staleness'] ) );
		}
	}

	/**
	 * Brand the storefront: activate the Northline theme, give products real copy
	 * and clean categories, set currency + store identity, create the About /
	 * Contact pages and flat-rate + free shipping. Idempotent; safe to re-run.
	 * Requires PDWCHS_DEMO_ALLOW.
	 *
	 * @subcommand setup-storefront
	 */
	public function setup_storefront( $args, $assoc ) {
		$result = PDWCHS_Demo_Storefront::setup();
		foreach ( $result['log'] as $line ) {
			WP_CLI::log( '  ' . $line );
		}
		if ( empty( $result['ok'] ) ) {
			WP_CLI::error( isset( $result['message'] ) ? $result['message'] : 'Storefront setup failed.' );
		}
		WP_CLI::success( 'Storefront ready — ' . PDWCHS_Demo_Storefront::STORE_NAME . '.' );
	}

	/**
	 * Dev-time: export the current store's products to data/catalog.json and
	 * copy their images into data/images/ so a fresh site can be bootstrapped
	 * into an identical catalog.
	 *
	 * @subcommand export-catalog
	 */
	public function export_catalog( $args, $assoc ) {
		$products = wc_get_products( array( 'limit' => 100, 'status' => 'publish', 'orderby' => 'ID', 'order' => 'ASC' ) );
		if ( ! $products ) {
			WP_CLI::error( 'No published products to export.' );
		}
		if ( ! wp_mkdir_p( PDWCHS_DEMO_TOOLS_DATA_DIR . 'images' ) ) {
			WP_CLI::error( 'Cannot create data/images/.' );
		}

		// Round-trip guard: catalog.json carries hand-curated enrichment the
		// store itself can't fully express (copy + featured for setup-storefront,
		// brand for the Brand Loyal tag, the note). Re-exporting must never
		// strip them — carry existing per-slug extras over, live values win
		// only for the fields the exporter actually reads from the store.
		$existing_note   = '';
		$existing_extras = array();
		$file            = PDWCHS_DEMO_TOOLS_DATA_DIR . 'catalog.json';
		if ( file_exists( $file ) ) {
			$old = json_decode( (string) file_get_contents( $file ), true );
			if ( is_array( $old ) ) {
				$existing_note = isset( $old['note'] ) ? (string) $old['note'] : '';
				foreach ( (array) ( $old['products'] ?? array() ) as $entry ) {
					if ( ! empty( $entry['slug'] ) ) {
						$existing_extras[ $entry['slug'] ] = array_diff_key(
							$entry,
							array_flip( array( 'name', 'slug', 'sku', 'price', 'category', 'image' ) )
						);
					}
				}
			}
		}

		$entries = array();
		$prices  = array();
		foreach ( $products as $p ) {
			$cats  = wp_get_post_terms( $p->get_id(), 'product_cat', array( 'fields' => 'names' ) );
			$image = '';
			$thumb = get_post_thumbnail_id( $p->get_id() );
			if ( $thumb ) {
				$src = get_attached_file( $thumb );
				if ( $src && file_exists( $src ) ) {
					$image = $p->get_slug() . '.' . strtolower( pathinfo( $src, PATHINFO_EXTENSION ) );
					copy( $src, PDWCHS_DEMO_TOOLS_DATA_DIR . 'images/' . $image );
				}
			}
			$price     = (float) $p->get_price();
			$prices[]  = $price;
			$entry     = array(
				'name'     => $p->get_name(),
				'slug'     => $p->get_slug(),
				'sku'      => $p->get_sku(),
				'price'    => $price,
				'category' => $cats ? $cats[0] : 'General',
				'image'    => $image,
			);
			// Fresher live values for copy/featured; preserved extras (brand,
			// anything else curated) fill whatever the store doesn't provide.
			$live = array_filter( array(
				'featured'          => $p->get_featured() ? true : null,
				'short_description' => $p->get_short_description() ?: null,
				'description'       => $p->get_description() ?: null,
			), function ( $v ) { return null !== $v; } );
			$extras    = isset( $existing_extras[ $entry['slug'] ] ) ? $existing_extras[ $entry['slug'] ] : array();
			$entries[] = array_merge( $entry, $extras, $live );
		}

		$payload = array( 'exported_from' => home_url(), 'products' => $entries );
		if ( '' !== $existing_note ) {
			$payload = array( 'exported_from' => home_url(), 'note' => $existing_note, 'products' => $entries );
		}
		file_put_contents( // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			$file,
			wp_json_encode( $payload, JSON_PRETTY_PRINT )
		);

		sort( $prices );
		$median = $prices[ (int) floor( count( $prices ) / 2 ) ];
		WP_CLI::success( count( $entries ) . ' products exported to data/catalog.json (+' . count( array_filter( wp_list_pluck( $entries, 'image' ) ) ) . ' images).' );
		if ( $median > 200 ) {
			WP_CLI::warning( "Median price \${$median} looks non-USD-realistic — fix product prices (or edit catalog.json) before seeding a fresh site. Order totals are unaffected (line totals are overridden), but product pages show these prices." );
		}
	}
}
