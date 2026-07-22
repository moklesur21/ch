<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Per-session conversion funnel list-table.
 *
 * Shows individual session-level conversion paths:
 * Product View → Add to Cart → Checkout Start → Purchase.
 *
 * Data reads delegate to PDWCHS_Sessions_Data (Free data layer).
 *
 * @since 2.2.0
 */
class PDWCHS_Funnel_Session_List_Table extends WP_List_Table {

	/** @var array Processed rows ready for display. */
	protected $session_array = array();

	/** @var array WP_List_Table items. */
	public $items = array();

	public function __construct() {
		parent::__construct();
	}

	/**
	 * @return array Column headers.
	 */
	public function get_columns() {
		return array(
			'product_name'   => esc_html__( 'Product Name', 'puredevs-customer-history-for-woocommerce' ),
			'funnel_roadmap' => esc_html__( 'Funnel Roadmap', 'puredevs-customer-history-for-woocommerce' ),
			'conversion'     => esc_html__( 'Conversion', 'puredevs-customer-history-for-woocommerce' ),
		);
	}

	/**
	 * @return array Sortable columns.
	 */
	public function get_sortable_columns() {
		return array(
			'product_name'   => array( 'product_name', false ),
			'funnel_roadmap' => array( 'funnel_roadmap', false ),
			'conversion'     => array( 'conversion', false ),
		);
	}

	/**
	 * Populate $this->items from PDWCHS_Sessions_Data.
	 *
	 * @param int    $num_days Number of days to look back.
	 * @param string $search   Optional product name filter.
	 */
	public function prepare_items( $num_days = 7, $search = '' ) {
		$this->session_array = array();

		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$distinct_sessions = PDWCHS_Sessions_Data::get_distinct_sessions_for_funnel( $num_days );

		if ( ! empty( $distinct_sessions ) ) {
			foreach ( $distinct_sessions as $k => $distinct_session ) {
				$session_data = PDWCHS_Sessions_Data::get_session_funnel_data( $distinct_session->session_id );
				if ( empty( $session_data ) ) {
					continue;
				}

				$product_ids            = array();
				$cart_item_ids          = array();
				$initial_order_ids      = array();
				$received_order_ids     = array();
				$initial_order_products = array();
				$received_order_products = array();

				foreach ( $session_data as $s_data ) {
					if ( str_starts_with( $s_data->url, 'product/' ) ) {
						$parts    = explode( '/', $s_data->url );
						$posts    = get_posts( array(
							'name'           => end( $parts ),
							'posts_per_page' => 1,
							'post_type'      => 'product',
							'post_status'    => 'publish',
						) );
						if ( ! empty( $posts ) ) {
							$product_ids[] = $posts[0]->ID;
						}
					}
					if ( str_starts_with( $s_data->url, 'ACTION::add_to_cart' ) ) {
						$parts = explode( '::', $s_data->url );
						if ( ! empty( $parts[2] ) ) {
							$cart_item_ids[] = $parts[2];
						}
					}
					if ( str_starts_with( $s_data->url, 'ACTION::new_order' ) ) {
						$parts = explode( '::', $s_data->url );
						if ( ! empty( $parts[2] ) ) {
							$initial_order_ids[] = $parts[2];
						}
					}
					if ( str_starts_with( $s_data->url, 'checkout/order-received/' ) ) {
						$parts = explode( '/', $s_data->url );
						if ( ! empty( $parts[2] ) ) {
							$received_order_ids[] = $parts[2];
						}
					}
				}

				foreach ( $initial_order_ids as $order_id ) {
					$order = wc_get_order( $order_id );
					if ( $order ) {
						foreach ( $order->get_items() as $item ) {
							$initial_order_products[] = $item['product_id'] ? $item['product_id'] : 0;
						}
					}
				}
				foreach ( $received_order_ids as $order_id ) {
					$order = wc_get_order( $order_id );
					if ( $order ) {
						foreach ( $order->get_items() as $item ) {
							$received_order_products[] = $item['product_id'] ? $item['product_id'] : 0;
						}
					}
				}

				$product_ids = array_unique( array_merge( $product_ids, $cart_item_ids ) );

				foreach ( $product_ids as $key => $product_id ) {
					$roadmap    = 'Product View ';
					$conversion = '<span class="dashicons dashicons-no"></span>';

					if ( ! empty( $cart_item_ids ) && in_array( $product_id, $cart_item_ids, true ) ) {
						$roadmap .= '<span class="dashicons dashicons-arrow-right-alt"></span> Add to Cart ';
					}
					if ( ! empty( $initial_order_products ) && in_array( $product_id, $initial_order_products, true ) ) {
						$roadmap .= '<span class="dashicons dashicons-arrow-right-alt"></span> Checkout Start ';
					}
					if ( ! empty( $received_order_products ) && in_array( $product_id, $received_order_products, true ) ) {
						$roadmap    .= '<span class="dashicons dashicons-arrow-right-alt"></span> Purchase';
						$conversion  = '<span class="dashicons dashicons-yes"></span>';
					}

					$this->session_array[ $k ][ $key ] = array(
						'product_name'   => '<a href="' . get_the_permalink( $product_id ) . '" target="_blank">' . get_the_title( $product_id ) . '</a>',
						'funnel_roadmap' => $roadmap,
						'conversion'     => $conversion,
					);
				}
			}
		}

		$this->session_array = array_reduce( $this->session_array, 'array_merge', array() );

		if ( ! empty( $search ) ) {
			$this->session_array = array_values( array_filter( $this->session_array, function( $item ) use ( $search ) {
				return stripos( strip_tags( $item['product_name'] ), $search ) !== false;
			} ) );
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
	 * @param array  $item
	 * @param string $column_name
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'product_name':
			case 'funnel_roadmap':
			case 'conversion':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Comparator for usort.
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sort_data( $a, $b ) {
		$orderby = 'product_name';
		$order   = 'asc';
		if ( ! empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order = sanitize_text_field( wp_unslash( $_GET['order'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$result = strcmp( $a[ $orderby ] ?? '', $b[ $orderby ] ?? '' );
		return ( 'asc' === $order ) ? $result : -$result;
	}
}
