<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PDWCHS_Segments_List_Table extends WP_List_Table {

	public function __construct() {
		parent::__construct( array(
			'singular' => 'segment',
			'plural'   => 'segments',
			'ajax'     => false,
		) );
	}

	public function get_columns() {
		return array(
			'name'           => esc_html__( 'Customer Segment', 'puredevs-customer-history-for-woocommerce' ),
			'description'    => esc_html__( 'Description', 'puredevs-customer-history-for-woocommerce' ),
			'customer_count' => esc_html__( 'Customers', 'puredevs-customer-history-for-woocommerce' ),
			'created_by'     => esc_html__( 'Created By', 'puredevs-customer-history-for-woocommerce' ),
			'created_at'     => esc_html__( 'Created', 'puredevs-customer-history-for-woocommerce' ),
			'actions'        => esc_html__( 'Actions', 'puredevs-customer-history-for-woocommerce' ),
		);
	}

	public function prepare_items() {
		if ( ! class_exists( 'PDWCHS_Segments_Manager' ) ) {
			require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/segments/class-pdwchs-segments-manager.php';
		}
		$manager = new PDWCHS_Segments_Manager();

		$rows = $manager->pdwchs_get_segments();

		foreach ( $rows as $row ) {
			$row->customer_count = $manager->pdwchs_count_segment_members(
				json_decode( $row->criteria, true ) ?? array()
			);
		}

		$this->_column_headers = array( $this->get_columns(), array(), array() );
		$this->items = $rows;
	}

	public function column_name( $item ) {
		$output = esc_html( $item->name );
		if ( $item->is_default ) {
			$output .= ' <span class="pdwchs-default-badge">'
					 . esc_html__( 'Default', 'puredevs-customer-history-for-woocommerce' )
					 . '</span>';
		}
		return $output;
	}

	public function column_actions( $item ) {
		$output = '';

		$output .= '<button type="button"'
				 . ' class="button pdwchs-load-segment"'
				 . ' data-segment-id="' . esc_attr( $item->id ) . '"'
				 . ' data-criteria="' . esc_attr( $item->criteria ) . '">'
				 . esc_html__( 'Load', 'puredevs-customer-history-for-woocommerce' )
				 . '</button> ';

		$output .= '<button type="button"'
				 . ' class="button pdwchs-export-segment"'
				 . ' data-segment-id="' . esc_attr( $item->id ) . '">'
				 . esc_html__( 'Export CSV', 'puredevs-customer-history-for-woocommerce' )
				 . '</button>';

		if ( ! $item->is_default ) {
			$output .= ' <button type="button"'
					 . ' class="button pdwchs-delete-segment"'
					 . ' data-segment-id="' . esc_attr( $item->id ) . '">'
					 . esc_html__( 'Delete', 'puredevs-customer-history-for-woocommerce' )
					 . '</button>';
		}

		return $output;
	}

	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'description':
				return esc_html( $item->description ?: '—' );

			case 'customer_count':
				return esc_html( number_format_i18n( (int) $item->customer_count ) );

			case 'created_by':
				if ( $item->created_by ) {
					$user = get_userdata( (int) $item->created_by );
					if ( $user ) {
						return esc_html( $user->display_name );
					}
				}
				return esc_html__( 'System', 'puredevs-customer-history-for-woocommerce' );

			case 'created_at':
				return esc_html(
					date_i18n( get_option( 'date_format' ), strtotime( $item->created_at ) )
				);
		}
		return '';
	}

	public function no_items() {
		echo esc_html__(
			'No segments saved yet. Use the "Save as Segment" button on the Customers page to create your first segment.',
			'puredevs-customer-history-for-woocommerce'
		);
	}
}
