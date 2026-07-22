<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Taxonomy stats list-table for Pro Statistics taxonomy sub-pages.
 *
 * Data reads delegate to PDWCHS_Search_Stats_Data (Free data layer).
 * Hook/menu registration is handled by Stats-List-Table.php.
 *
 * @since 2.2.0
 */
class PDWCHS_Taxonomy_Stats_List_Table extends WP_List_Table {

	/** @var array Processed rows ready for display. */
	protected $session_array = array();

	/** @var array WP_List_Table items. */
	public $items = array();

	/** @var object|null Current taxonomy object. */
	protected $taxonomy = null;

	/**
	 * @param object|string $current_taxonomy Taxonomy object or empty string.
	 */
	public function __construct( $current_taxonomy = '' ) {
		$this->taxonomy = $current_taxonomy;
		if ( function_exists( 'convert_to_screen' ) ) {
			parent::__construct();
		}
	}

	/**
	 * @return array Column headers.
	 */
	public function get_columns() {
		$name = ( $this->taxonomy && ! empty( $this->taxonomy->labels->singular_name ) )
			? $this->taxonomy->labels->singular_name
			: __( 'Term', 'puredevs-customer-history-for-woocommerce' );
		return array(
			'count'    => esc_html__( 'Count', 'puredevs-customer-history-for-woocommerce' ),
			'category' => esc_html( $name ),
		);
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

		if ( $this->taxonomy ) {
			$stats = PDWCHS_Search_Stats_Data::get_url_counts_for_taxonomy(
				$this->taxonomy->labels->singular_name,
				array( 'search' => $search )
			);
			foreach ( $stats as $stat ) {
				$stat_url = explode( '/', $stat->url );
				$term     = get_term_by( 'slug', end( $stat_url ), $this->taxonomy->name );
				if ( $term && ! is_wp_error( $term ) ) {
					$this->session_array[] = array(
						'count'    => '<span class="dashicons dashicons-chart-bar"></span>&nbsp;<strong>' . $stat->qta . '</strong>',
						'category' => '<span class="dashicons dashicons-category"></span>&nbsp;<strong>' . $term->name . '</strong>',
					);
				}
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
			'count'    => array( 'count', false ),
			'category' => array( 'category', false ),
		);
	}

	/**
	 * @param array  $item
	 * @param string $column_name
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'count':
			case 'category':
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
		$orderby = 'count';
		$order   = 'desc';
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
