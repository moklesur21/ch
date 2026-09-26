<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Stats list-table and UI coordinator for the Pro Statistics pages.
 *
 * Wires H3 (Statistics menu + sub-pages), H4 (styles), H5 (scripts).
 * Data reads delegate to PDWCHS_Search_Stats_Data (Free data layer).
 *
 * @since 2.2.0
 */
class PDWCHS_Stats_List_Table extends WP_List_Table {

	/** @var array Processed rows ready for display. */
	protected $session_array = array();

	/** @var array WP_List_Table items. */
	public $items = array();

	/**
	 * Constructor: initialize parent list-table and register all hook listeners.
	 */
	public function __construct() {
		if ( function_exists( 'convert_to_screen' ) ) {
			parent::__construct();
		}
		$this->pdwchs_register_hooks();
	}

	public function pdwchs_init_table() {
		parent::__construct();
	}

	/**
	 * Wire H3 / H4 / H5 action listeners.
	 */
	public function pdwchs_register_hooks() {
		add_action( 'pdwchs_admin_menu_late',       array( $this, 'pdwchs_register_menus' ) );   // H3
		add_action( 'pdwchs_enqueue_admin_styles',  array( $this, 'pdwchs_enqueue_styles' ) );   // H4
		add_action( 'pdwchs_enqueue_admin_scripts', array( $this, 'pdwchs_enqueue_scripts' ) );  // H5
	}

	// -------------------------------------------------------------------------
	// H3 — admin menu
	// -------------------------------------------------------------------------

	/**
	 * Register Statistics (visible) and all hidden sub-page submenus.
	 *
	 * @param string $capability WP capability string passed by the hook.
	 */
	public function pdwchs_register_menus( $capability ) {
		add_submenu_page(
			'pd-woo-dashboard',
			__( 'PD Statistics', 'puredevs-customer-history-for-woocommerce' ),
			__( '<span id="pd-keyword-stats-menu">Statistics</span>', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			'pd-woo-stats',
			array( $this, 'pdwchs_stats_pages_callback' )
		);
		add_submenu_page(
			'',
			__( 'PD Keyword Searches', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Keyword Searches', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			'pd-woo-search-stats',
			array( $this, 'pdwchs_search_keywords_callback' )
		);
		add_submenu_page(
			'',
			__( 'Zero-Result Searches', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Zero-Result Searches', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			'pd-woo-zero-result-searches',
			array( $this, 'pdwchs_zero_result_callback' )
		);
		add_submenu_page(
			'',
			__( 'Product Views', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Product Views', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			'pd-woo-product-view-stats',
			array( $this, 'pdwchs_product_view_callback' )
		);
		add_submenu_page(
			'',
			__( 'Top Selling Products', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Top Selling Products', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			'pd-woo-top-selling-stats',
			array( $this, 'pdwchs_top_selling_callback' )
		);
		foreach ( PDWCHS_Pro_Helpers::pdwchs_get_product_taxonomies() as $taxonomy ) {
			$tax = get_taxonomy( $taxonomy );
			add_submenu_page(
				'',
				__( 'PD ' . $tax->labels->singular_name . ' View', 'puredevs-customer-history-for-woocommerce' ),
				__( $tax->labels->singular_name . ' View', 'puredevs-customer-history-for-woocommerce' ),
				$capability,
				'pd-woo-' . strtolower( $tax->labels->singular_name ) . '-stats',
				array( $this, 'pdwchs_tax_view_callback' )
			);
		}
		add_submenu_page(
			'',
			__( 'PD Total Spent', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Total Spent', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			'pd-woo-stats-spent',
			array( $this, 'pdwchs_stats_spent_callback' )
		);
	}

	// -------------------------------------------------------------------------
	// H4 / H5 — enqueue stubs (global admin CSS covers stats styling)
	// -------------------------------------------------------------------------

	/** @param string $version Plugin version. */
	public function pdwchs_enqueue_styles( $version ) {}

	/** @param string $version Plugin version. */
	public function pdwchs_enqueue_scripts( $version ) {}

	// -------------------------------------------------------------------------
	// Page callbacks
	// -------------------------------------------------------------------------

	public function pdwchs_stats_pages_callback() {
		$this->pdwchs_init_table();
		include __DIR__ . '/templates/stats-pages.php';
	}

	public function pdwchs_search_keywords_callback() {
		$this->pdwchs_init_table();
		include __DIR__ . '/templates/search-keywords-page.php';
	}

	public function pdwchs_zero_result_callback() {
		$this->pdwchs_init_table();
		include __DIR__ . '/templates/zero-result-searches-page.php';
	}

	public function pdwchs_product_view_callback() {
		$this->pdwchs_init_table();
		include __DIR__ . '/templates/product-view-stats-page.php';
	}

	public function pdwchs_top_selling_callback() {
		$this->pdwchs_init_table();
		include __DIR__ . '/templates/top-selling-products.php';
	}

	public function pdwchs_tax_view_callback() {
		$this->pdwchs_init_table();
		include __DIR__ . '/templates/stats-tax-view.php';
	}

	public function pdwchs_stats_spent_callback() {
		$this->pdwchs_init_table();
		include __DIR__ . '/templates/stats-spent.php';
	}

	// -------------------------------------------------------------------------
	// WP_List_Table interface
	// -------------------------------------------------------------------------

	/**
	 * Return column headers based on the current page slug.
	 *
	 * @return array
	 */
	public function get_columns() {
		$columns = array();
		$page    = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'pd-woo-stats' === $page ) {
			$columns = array(
				'sessions' => esc_html__( 'Sessions', 'puredevs-customer-history-for-woocommerce' ),
				'url'      => esc_html__( 'URL', 'puredevs-customer-history-for-woocommerce' ),
			);
		}
		if ( 'pd-woo-search-stats' === $page ) {
			$columns = array(
				'searches' => esc_html__( 'Searches', 'puredevs-customer-history-for-woocommerce' ),
				'keywords' => esc_html__( 'Keywords', 'puredevs-customer-history-for-woocommerce' ),
			);
		}
		if ( 'pd-woo-zero-result-searches' === $page ) {
			$columns = array(
				'keywords'      => esc_html__( 'Search Term', 'puredevs-customer-history-for-woocommerce' ),
				'searches'      => esc_html__( 'Times Searched', 'puredevs-customer-history-for-woocommerce' ),
				'last_searched' => esc_html__( 'Last Searched', 'puredevs-customer-history-for-woocommerce' ),
			);
		}
		if ( 'pd-woo-product-view-stats' === $page ) {
			$columns = array(
				'product_name'        => esc_html__( 'Product Name', 'puredevs-customer-history-for-woocommerce' ),
				'view_count'          => esc_html__( 'View Count', 'puredevs-customer-history-for-woocommerce' ),
				'purchase_count'      => esc_html__( 'Purchase Count', 'puredevs-customer-history-for-woocommerce' ),
				'view_purchase_ratio' => esc_html__( 'View vs Purchase Ratio', 'puredevs-customer-history-for-woocommerce' ),
			);
		}
		if ( 'pd-woo-top-selling-stats' === $page ) {
			$columns = array(
				'product_name'   => esc_html__( 'Product Name', 'puredevs-customer-history-for-woocommerce' ),
				'purchase_count' => esc_html__( 'Purchase Count', 'puredevs-customer-history-for-woocommerce' ),
			);
		}
		if ( 'pd-woo-stats-spent' === $page ) {
			$columns = array(
				'total_spent'  => esc_html__( 'Total spent', 'puredevs-customer-history-for-woocommerce' ),
				'customer'     => esc_html__( 'Users', 'puredevs-customer-history-for-woocommerce' ),
				'total_orders' => esc_html__( 'Total Orders', 'puredevs-customer-history-for-woocommerce' ),
				'avg_orders'   => esc_html__( 'Orders Average', 'puredevs-customer-history-for-woocommerce' ),
			);
		}
		return $columns;
	}

	/**
	 * Populate $this->items from PDWCHS_Search_Stats_Data.
	 */
	public function prepare_items() {
		$this->session_array = array();

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$search = isset( $_GET['search_session'] ) ? sanitize_text_field( wp_unslash( $_GET['search_session'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page   = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'pd-woo-stats' === $page ) {
			$stats = PDWCHS_Search_Stats_Data::get_url_counts( array( 'search' => $search ) );
			foreach ( $stats as $stat ) {
				$session_url = PD_Customer_History_Helper::pdwchs_session_url( $stat->url );
				$this->session_array[] = array(
					'sessions' => '<span class="dashicons dashicons-media-document mr-5"></span> <strong>' . $stat->qta . '</strong>',
					'url'      => $session_url['session_url'],
				);
			}
		}

		if ( 'pd-woo-search-stats' === $page ) {
			$stats = PDWCHS_Search_Stats_Data::get_keyword_counts( array( 'search' => $search ) );
			foreach ( $stats as $stat ) {
				$url_array = explode( '::', $stat->url );
				if ( isset( $url_array[1] ) && 'search' === $url_array[1] && ! empty( $url_array[2] ) ) {
					$this->session_array[] = array(
						'searches' => '<span class="dashicons dashicons-chart-bar mr-5"></span> <strong>' . $stat->qta . '</strong>',
						// The keyword is raw visitor input from the storefront search box, and
						// WP_List_Table echoes this cell as markup — escape it (CLAUDE.md rule 5).
						'keywords' => '<span class="dashicons dashicons-search mr-5"></span> <strong>' . esc_html( $url_array[2] ) . '</strong>',
					);
				}
			}
		}

		// Bridge page: closes the Pro-side blind spot Feature 15.2 left open (its free widget lives on
		// a page Pro hides). Retire this branch, its siblings in get_columns()/sort_data()/no_items(),
		// its template and its sub-nav entry when R20-03/R20-04 ship the Search Gap Report, which is a
		// strict superset of what this shows.
		if ( 'pd-woo-zero-result-searches' === $page ) {
			// limit 0 = every term, not the free widget's top 20. The reader only applies its
			// ORDER BY when a limit is set, so these rows arrive unordered and sort_data() below
			// is what imposes most-searched-first.
			$stats = PDWCHS_Search_Stats_Data::pdwchs_get_zero_result_terms(
				array(
					'search' => $search,
					'limit'  => 0,
				)
			);

			foreach ( $stats as $stat ) {
				$url_array = explode( '::', $stat->url );
				if ( isset( $url_array[1] ) && 'search' === $url_array[1] && ! empty( $url_array[2] ) ) {
					$last_ts = strtotime( $stat->last_searched );

					$this->session_array[] = array(
						// The term is raw visitor input from the storefront search box — escaped, always.
						'keywords'      => '<span class="dashicons dashicons-search mr-5"></span> <strong>'
										 . esc_html( $url_array[2] ) . '</strong>',
						'searches'      => '<span class="dashicons dashicons-chart-bar mr-5"></span> <strong>'
										 . esc_html( number_format_i18n( (int) $stat->qta ) ) . '</strong>',
						'last_searched' => esc_html(
							$last_ts
								? date_i18n( get_option( 'date_format' ), $last_ts, true )
								: $stat->last_searched
						),
						// Hidden sort keys — get_columns() does not list them, so they never render.
						'zr_count'      => (int) $stat->qta,
						'zr_term'       => $url_array[2],
						'zr_time'       => $last_ts ? (int) $last_ts : 0,
					);
				}
			}
		}

		if ( 'pd-woo-product-view-stats' === $page ) {
			$site_pages      = PDWCHS_Search_Stats_Data::get_url_counts( array( 'search' => $search ) );
			$viewed_products = PD_Customer_History_Helper::pdwchs_get_view_products( $site_pages );

			usort( $viewed_products, function( $a, $b ) {
				if ( $a->qta < $b->qta ) return 1;
				if ( $a->qta > $b->qta ) return -1;
				return 0;
			} );

			if ( ! empty( $viewed_products ) ) {
				foreach ( $viewed_products as $vp ) {
					$search_url = explode( '/', $vp->url );
					$posts = get_posts( array(
						'name'           => end( $search_url ),
						'posts_per_page' => 1,
						'post_type'      => 'product',
						'post_status'    => 'publish',
					) );
					if ( ! empty( $posts ) ) {
						$total_sales = get_post_meta( $posts[0]->ID, 'total_sales', true );
						if ( $total_sales > 0 ) {
							$ratio = number_format( ( $total_sales / $vp->qta ) * 100, 2, '.', '' );
						} else {
							$ratio = 0;
						}
						$this->session_array[] = array(
							'product_name'        => '<a href="' . get_permalink( $posts[0] ) . '" target="_blank"><span class="dashicons dashicons-products"></span> <strong>' . $posts[0]->post_title . '</strong></a>',
							'view_count'          => $vp->qta,
							'purchase_count'      => $total_sales,
							'view_purchase_ratio' => $ratio . '%',
						);
					}
				}
			}
		}

		if ( 'pd-woo-top-selling-stats' === $page ) {
			$top_selling = PD_Customer_History::pdwchs_top_selling_products_by_month( 99999999 );
			if ( ! empty( $top_selling ) ) {
				foreach ( $top_selling as $key => $value ) {
					$product = get_post( $key );
					if ( ! empty( $product ) ) {
						if ( ! empty( $search ) && stripos( $product->post_title, $search ) === false ) {
							continue;
						}
						$this->session_array[] = array(
							'product_name'   => '<a href="' . get_permalink( $product->ID ) . '" target="_blank"><span class="dashicons dashicons-products"></span> <strong>' . $product->post_title . '</strong></a>',
							'purchase_count' => $value,
						);
					}
				}
			}
		}

		if ( 'pd-woo-stats-spent' === $page ) {
			$users = get_users();
			foreach ( $users as $user ) {
				$spent    = PD_Customer_History_Customers::pdwchs_woo_get_customer_total_spent( $user->ID );
				$username = ( '' !== $user->first_name || '' !== $user->last_name )
					? esc_html( $user->first_name . ' ' . $user->last_name )
					: esc_html( $user->display_name );

				$data_sync_enabled = get_option( 'woocommerce_custom_orders_table_data_sync_enabled' );
				if ( isset( $data_sync_enabled ) && 'yes' === $data_sync_enabled ) {
					$order_count = count( get_posts( array(
						'numberposts' => -1,
						'meta_key'    => '_customer_user',
						'meta_value'  => $user->ID,
						'post_type'   => 'shop_order',
						'post_status' => 'any',
						'post_parent' => '0',
					) ) );
				} else {
					$order_count = wc_get_customer_order_count( $user->ID );
				}

				$this->session_array[] = array(
					'total_spent'  => '<span class="dashicons dashicons-money mr-5"></span>' . wc_price( $spent ),
					'customer'     => '<span class="dashicons dashicons-admin-users mr-5"></span><strong><a href="admin.php?page=pd-woo-customer&user_id=' . $user->ID . '" target="_blank">' . $username . '</a></strong>',
					'total_orders' => $order_count,
					'avg_orders'   => $order_count > 0 ? wc_price( $spent / $order_count ) : wc_price( $spent ),
				);
			}
		}

		usort( $this->session_array, array( $this, 'sort_data' ) );

		$per_page = ! empty( $_GET['show-items'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? absint( $_GET['show-items'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			: ( get_option( 'pd-woo-results_per_page' ) ? (int) get_option( 'pd-woo-results_per_page' ) : 10 );

		$current_page = $this->get_pagenum();
		$total_items  = count( $this->session_array );
		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
		) );
		$this->session_array = array_slice( $this->session_array, ( $current_page - 1 ) * $per_page, $per_page );
		$this->items = $this->session_array;
	}

	/**
	 * @return array Sortable columns.
	 */
	public function get_sortable_columns() {
		return array(
			'sessions'            => array( 'sessions', false ),
			'url'                 => array( 'url', false ),
			'searches'            => array( 'searches', false ),
			'keywords'            => array( 'keywords', false ),
			'last_searched'       => array( 'last_searched', false ),
			'product_name'        => array( 'product_name', false ),
			'view_count'          => array( 'view_count', false ),
			'purchase_count'      => array( 'purchase_count', false ),
			'view_purchase_ratio' => array( 'view_purchase_ratio', false ),
			'total_spent'         => array( 'total_spent', false ),
			'customer'            => array( 'customer', false ),
			'total_orders'        => array( 'total_orders', false ),
			'avg_orders'          => array( 'avg_orders', false ),
		);
	}

	/**
	 * @param array  $item
	 * @param string $column_name
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'sessions':
			case 'url':
			case 'searches':
			case 'keywords':
			case 'last_searched':
			case 'product_name':
			case 'view_count':
			case 'purchase_count':
			case 'view_purchase_ratio':
			case 'total_spent':
			case 'customer':
			case 'total_orders':
			case 'avg_orders':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Empty-state copy.
	 *
	 * Core's "No items found." is a lie on the zero-result page: an empty table there means
	 * collection has started and nothing has failed yet, not that the report is broken. Every
	 * other Statistics page delegates to core untouched.
	 *
	 * The two copy variants are the free widget's strings character for character
	 * (admin/partials/templates/search-analytics.php:136-144) so both builds say the same thing
	 * and the translation memory matches.
	 */
	public function no_items() {
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'pd-woo-zero-result-searches' !== $page ) {
			parent::no_items();
			return;
		}

		$since = get_option( 'pdwchs_results_count_since' );

		if ( $since ) {
			echo esc_html(
				sprintf(
					/* translators: %s: the date on which zero-result collection began on this site. */
					__( 'No zero-result searches recorded yet. Collecting since %s — terms appear here as visitors search your store and find nothing.', 'puredevs-customer-history-for-woocommerce' ),
					date_i18n( get_option( 'date_format' ), (int) $since )
				)
			);
			return;
		}

		echo esc_html__( 'No zero-result searches recorded yet. Terms appear here as visitors search your store and find nothing.', 'puredevs-customer-history-for-woocommerce' );
	}

	/**
	 * Comparator for usort — sorts rows by the requested column/order.
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sort_data( $a, $b ) {
		$order = 'desc';
		$page  = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'pd-woo-stats-spent' === $page ) {
			$orderby = 'total_spent';
			$order   = 'asc';
		} elseif ( 'pd-woo-search-stats' === $page ) {
			$orderby = 'keywords';
		} elseif ( 'pd-woo-product-view-stats' === $page ) {
			$orderby = 'view_count';
		} elseif ( 'pd-woo-top-selling-stats' === $page ) {
			$orderby = 'purchase_count';
		} else {
			$orderby = 'url';
		}

		if ( ! empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order = sanitize_text_field( wp_unslash( $_GET['order'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}

		// The zero-result page's visible cells are HTML, and strcmp on "<strong>9</strong>" versus
		// "<strong>12</strong>" puts 9 above 12. Sort on its hidden scalar keys instead — for the
		// default (no orderby in the URL, which falls through to zr_count) and for every column
		// header the user can click. One map, so this page's ordering is decided in one place.
		if ( 'pd-woo-zero-result-searches' === $page ) {
			$zr_map  = array(
				'keywords'      => 'zr_term',
				'searches'      => 'zr_count',
				'last_searched' => 'zr_time',
			);
			$orderby = isset( $zr_map[ $orderby ] ) ? $zr_map[ $orderby ] : 'zr_count';
		}

		$a_val = $a[ $orderby ] ?? '';
		$b_val = $b[ $orderby ] ?? '';

		// Numeric only when BOTH sides are numeric, so the sibling pages — whose cells are all HTML
		// strings — keep the exact strcmp behaviour they had before this branch existed.
		$result = ( is_numeric( $a_val ) && is_numeric( $b_val ) )
			? ( $a_val <=> $b_val )
			: strcmp( (string) $a_val, (string) $b_val );

		return ( 'asc' === $order ) ? $result : -$result;
	}

	/**
	 * Filter an array of items to those whose 'product_name' matches the search string.
	 *
	 * @param array  $array  Items with 'product_name' key.
	 * @param string $search Search string.
	 * @return array Filtered items.
	 */
	public function pdwchs_woo_search_on_array_items( $array, $search ) {
		return array_values( array_filter( $array, function( $item ) use ( $search ) {
			return stripos( strip_tags( $item['product_name'] ), $search ) !== false;
		} ) );
	}
}
