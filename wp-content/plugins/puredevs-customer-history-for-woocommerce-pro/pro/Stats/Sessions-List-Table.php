<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sessions list-table and UI coordinator for the Pro Sessions/Searches/Trash pages.
 *
 * Wires H3 (menu), H4 (styles), H5 (scripts), H7 (customer-detail sessions section).
 * Data reads delegate to PDWCHS_Sessions_Data (Free data layer).
 *
 * @since 2.2.0
 */
class PDWCHS_Sessions_List_Table extends WP_List_Table {

	/** @var array Processed session rows ready for display. */
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
	 * Wire H3 / H4 / H5 / H7 action listeners.
	 */
	public function pdwchs_register_hooks() {
		add_action( 'pdwchs_admin_menu_late',                    array( $this, 'pdwchs_register_menus' ) );    // H3
		add_action( 'pdwchs_enqueue_admin_styles',               array( $this, 'pdwchs_enqueue_styles' ) );    // H4
		add_action( 'pdwchs_enqueue_admin_scripts',              array( $this, 'pdwchs_enqueue_scripts' ) );   // H5
		add_action( 'pdwchs_customer_details_sessions_section',  array( $this, 'pdwchs_customer_sessions_section' ) ); // H7
	}

	// -------------------------------------------------------------------------
	// H3 — admin menu
	// -------------------------------------------------------------------------

	/**
	 * Register Sessions (visible), Searches (hidden), and Trash (hidden) submenus.
	 *
	 * @param string $capability WP capability string passed by the hook.
	 */
	public function pdwchs_register_menus( $capability ) {
		add_submenu_page(
			'pd-woo-dashboard',
			__( 'PD Sessions', 'puredevs-customer-history-for-woocommerce' ),
			__( '<span id="pd-sessions-menu">Sessions</span>', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			'pd-woo-sessions',
			array( $this, 'pdwchs_page_callback' )
		);
		add_submenu_page(
			'',
			__( 'PD Searches', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Searches', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			'pd-woo-searches',
			array( $this, 'pdwchs_page_callback' )
		);
		add_submenu_page(
			'',
			__( 'PD Trash', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Trash', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			'pd-woo-trash',
			array( $this, 'pdwchs_page_callback' )
		);
	}

	// -------------------------------------------------------------------------
	// H4 / H5 — enqueue stubs (no session-specific assets currently)
	// -------------------------------------------------------------------------

	/** @param string $version Plugin version. */
	public function pdwchs_enqueue_styles( $version ) {}

	/** @param string $version Plugin version. */
	public function pdwchs_enqueue_scripts( $version ) {}

	// -------------------------------------------------------------------------
	// Page callback (Sessions / Searches / Trash)
	// -------------------------------------------------------------------------

	/**
	 * Shared page callback for all three session sub-pages.
	 * The template renders the correct view based on $_GET['page'].
	 */
	public function pdwchs_page_callback() {
		$this->pdwchs_init_table();
		include __DIR__ . '/templates/live-sessions.php';
	}

	// -------------------------------------------------------------------------
	// H7 — customer-details inline sessions section
	// -------------------------------------------------------------------------

	/**
	 * Render search history and sessions history panels on the customer detail page.
	 *
	 * @param int $user_id WP user ID.
	 */
	public function pdwchs_customer_sessions_section( $user_id ) {
		$user_id  = absint( $user_id );
		$timezone = (float) get_option( 'gmt_offset', 0 );

		// Search history.
		$searches = PDWCHS_Sessions_Data::get_user_searches( $user_id, 10 );

		// Sessions history — temporarily pass user_id via $_GET so the data
		// method applies the user scope.
		$prev_uid = isset( $_GET['user_id'] ) ? sanitize_text_field( wp_unslash( $_GET['user_id'] ) ) : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$_GET['user_id'] = $user_id;                                      // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$sessions = array_slice( (array) PDWCHS_Sessions_Data::pdwchs_get_all_live_sessions(), 0, 10 );
		if ( null === $prev_uid ) {
			unset( $_GET['user_id'] );
		} else {
			$_GET['user_id'] = $prev_uid; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		?>
		<div class="pd-ch-panel section-searches">
			<h2>
				<span class="dashicons dashicons-search"></span>&nbsp;<?php echo esc_html__( 'Search History', 'puredevs-customer-history-for-woocommerce' ); ?>&nbsp;&nbsp;&nbsp;
				(<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-searches&user_id=' . $user_id ) ); ?>"><?php echo esc_html__( 'Complete History', 'puredevs-customer-history-for-woocommerce' ); ?></a>)
			</h2>
			<span class="toggle-section toggle-btn pd-ch-toggle-btn pd-woo-toggle-btn" data-toggle-content="section-searches-pro">
				<span class="dashicons dashicons-arrow-down-alt2" title="<?php echo esc_attr__( 'Click to Expand', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
			</span>
			<div id="section-searches-pro" class="pd-ch-clearfix">
				<table class="wp-list-table widefat fixed striped posts">
					<tr>
						<th class="date"><?php echo esc_html__( 'Date', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th><?php echo esc_html__( 'Key', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					</tr>
					<?php if ( ! empty( $searches ) ) :
						foreach ( $searches as $row ) :
							$url_parts = explode( '::', $row->url );
							$reg_date  = date( 'Y-m-d H:i:s', strtotime( $row->reg_date ) + 3600 * $timezone );
							?>
							<tr class="action action-search">
								<td class="date"><span class="dashicons dashicons-calendar-alt"></span>&nbsp;<?php echo esc_html( $reg_date ); ?></td>
								<td><strong><?php echo esc_html( isset( $url_parts[2] ) ? $url_parts[2] : '' ); ?></strong></td>
							</tr>
						<?php endforeach;
					endif; ?>
				</table>
			</div>
		</div>

		<div class="pd-ch-panel section-sessions">
			<h2>
				<span class="dashicons dashicons-welcome-view-site"></span>&nbsp;<?php echo esc_html__( 'Sessions History', 'puredevs-customer-history-for-woocommerce' ); ?>&nbsp;&nbsp;&nbsp;
				(<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-sessions&user_id=' . $user_id ) ); ?>"><?php echo esc_html__( 'All Sessions History', 'puredevs-customer-history-for-woocommerce' ); ?></a>)
			</h2>
			<span class="toggle-section toggle-btn pd-ch-toggle-btn pd-woo-toggle-btn" data-toggle-content="section-sessions-pro">
				<span class="dashicons dashicons-arrow-down-alt2" title="<?php echo esc_attr__( 'Click to Expand', 'puredevs-customer-history-for-woocommerce' ); ?>"></span>
			</span>
			<div id="section-sessions-pro" class="pd-ch-clearfix">
				<table class="wp-list-table widefat fixed striped posts">
					<tr>
						<th class="date"><?php echo esc_html__( 'Date', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th><?php echo esc_html__( 'URL', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					</tr>
					<?php if ( ! empty( $sessions ) ) :
						foreach ( $sessions as $value ) :
							$url_array         = array();
							$is_session_action = false;
							if ( 1 === strpos( $value->url, 'CTION::' ) ) {
								$is_session_action = true;
								$url_array = explode( '::', $value->url );
								switch ( $url_array[1] ) {
									case 'search':
										$display_url = esc_html__( 'Search', 'puredevs-customer-history-for-woocommerce' ) . ': ' . esc_html( isset( $url_array[2] ) ? $url_array[2] : '' );
										break;
									case 'add_to_cart':
										$display_url = esc_html__( 'Add to cart', 'puredevs-customer-history-for-woocommerce' ) . ': x' . esc_html( isset( $url_array[3] ) ? $url_array[3] : '' ) . ' products #' . esc_html( isset( $url_array[2] ) ? $url_array[2] : '' );
										break;
									case 'new_order':
										$display_url = esc_html__( 'New order', 'puredevs-customer-history-for-woocommerce' ) . ': #' . esc_html( isset( $url_array[2] ) ? $url_array[2] : '' );
										break;
									default:
										$display_url = esc_html( $value->url );
										break;
								}
							} else {
								$display_url = esc_url( get_site_url() . '/' . $value->url );
							}

							// Skip search action rows (shown in search panel above).
							if ( $is_session_action && isset( $url_array[1] ) && 'search' === $url_array[1] ) {
								continue;
							}

							$reg_date = date( 'Y-m-d H:i:s', strtotime( $value->reg_date ) + 3600 * $timezone );
							?>
							<tr>
								<td class="date"><span class="dashicons dashicons-calendar-alt"></span>&nbsp;<?php echo esc_html( $reg_date ); ?></td>
								<td><?php echo wp_kses_post( $display_url ); ?></td>
							</tr>
						<?php endforeach;
					endif; ?>
				</table>
			</div>
		</div>
		<?php
	}

	// -------------------------------------------------------------------------
	// WP_List_Table interface
	// -------------------------------------------------------------------------

	/**
	 * @return array Column headers.
	 */
	public function get_columns() {
		return array(
			'user'    => esc_html__( 'User', 'puredevs-customer-history-for-woocommerce' ),
			'url'     => esc_html__( 'URL', 'puredevs-customer-history-for-woocommerce' ),
			'referer' => esc_html__( 'Referer', 'puredevs-customer-history-for-woocommerce' ),
			'date'    => esc_html__( 'Date', 'puredevs-customer-history-for-woocommerce' ),
			'actions' => esc_html__( 'Actions', 'puredevs-customer-history-for-woocommerce' ),
		);
	}

	/**
	 * Populate $this->items from PDWCHS_Sessions_Data.
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$search     = isset( $_GET['search_session'] ) ? sanitize_text_field( wp_unslash( $_GET['search_session'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$start_date = isset( $_GET['start_date'] )     ? sanitize_text_field( wp_unslash( $_GET['start_date'] ) )     : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$end_date   = isset( $_GET['end_date'] )       ? sanitize_text_field( wp_unslash( $_GET['end_date'] ) )       : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$sessions = PDWCHS_Sessions_Data::pdwchs_get_all_live_sessions( $search, $start_date, $end_date );
		$timezone = get_option( 'gmt_offset' ) ? get_option( 'gmt_offset' ) : 0;

		$current_page_slug = ! empty( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		foreach ( $sessions as $session ) {
			$user_cell   = $this->pdwchs_get_user( $session->user_id );
			$session_url = PDWCHS_Helper::pdwchs_session_url( $session->url );
			$reg_date    = isset( $session->reg_date ) ? date( 'Y-m-d  H:i:s', strtotime( $session->reg_date ) + 3600 * $timezone ) : '';
			$referer     = isset( $session->referer ) ? $session->referer : '';

			if ( 'pd-woo-trash' === $current_page_slug ) {
				$nonce  = wp_create_nonce( 'move-from-trash' );
				$action = '<a href="admin.php?page=pd-woo-trash&act=restore&id=' . intval( $session->id ) . '&_wpnonce=' . esc_attr( $nonce ) . '" class="button mr-5" title="' . esc_attr__( 'Restore', 'puredevs-customer-history-for-woocommerce' ) . '"><span class="dashicons dashicons-controls-repeat vertical-align-middle"></span></a>';
			} else {
				$nonce  = wp_create_nonce( 'move-to-trash' );
				$action = '<a href="admin.php?page=pd-woo-sessions&act=hide&id=' . intval( $session->id ) . '&_wpnonce=' . esc_attr( $nonce ) . '" class="button mr-5" title="' . esc_attr__( 'Delete', 'puredevs-customer-history-for-woocommerce' ) . '"><span class="dashicons dashicons-trash vertical-align-middle"></span></a>';
			}

			if ( ! empty( $session_url['session_url'] ) ) {
				$this->session_array[] = array(
					'user'    => $user_cell,
					'url'     => $session_url['session_url'],
					'referer' => '<a href="' . esc_attr( $referer ) . '" target="_blank">' . esc_html( $referer ) . '</a>',
					'date'    => '<span class="dashicons dashicons-calendar-alt mr-5"></span>' . esc_html( $reg_date ),
					'actions' => $action,
				);
			}
		}

		usort( $this->session_array, array( $this, 'sort_data' ) );

		if ( ! empty( $_GET['show-items'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$per_page = absint( $_GET['show-items'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		} else {
			$per_page = get_option( 'pd-woo-results_per_page' ) ? (int) get_option( 'pd-woo-results_per_page' ) : 10;
		}

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
	 * @return array Sortable columns definition.
	 */
	public function get_sortable_columns() {
		return array(
			'user'    => array( 'user', false ),
			'url'     => array( 'url', false ),
			'referer' => array( 'referer', false ),
			'date'    => array( 'date', false ),
		);
	}

	/**
	 * @param array  $item        Row data.
	 * @param string $column_name Column key.
	 * @return mixed
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'user':
			case 'url':
			case 'referer':
			case 'date':
			case 'actions':
				return $item[ $column_name ];
			default:
				return print_r( $item, true );
		}
	}

	/**
	 * Comparator for usort — sorts rows by the requested column/order.
	 *
	 * @param array $a
	 * @param array $b
	 * @return int
	 */
	private function sort_data( $a, $b ) {
		$orderby = 'date';
		$order   = 'desc';
		if ( ! empty( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		if ( ! empty( $_GET['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$order = sanitize_text_field( wp_unslash( $_GET['order'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		}
		$result = strcmp( $a[ $orderby ], $b[ $orderby ] );
		return ( 'asc' === $order ) ? $result : -$result;
	}

	/**
	 * Return an HTML display string for a user table cell.
	 *
	 * @param int|string $user_id WP user ID (999999999999999 = bot sentinel).
	 * @return string Escaped HTML.
	 */
	public function pdwchs_get_user( $user_id ) {
		$user = null;

		if ( $user_id != 999999999999999 ) {
			$user = get_user_by( 'id', $user_id );
		} elseif ( $user_id > 0 ) {
			$user = 'bot';
		}

		// Handle guest (no user found)
		if ( ! $user ) {
			return '<span class="dashicons dashicons-businessman mr-5"></span> Guest';
		}

		// Handle bot
		if ( 'bot' === $user ) {
			return '<span class="dashicons dashicons-businessman mr-5"></span> Bot';
		}

		// At this point $user should be a WP_User object
		if ( $user instanceof WP_User ) {
			$first_name = ! empty( $user->first_name ) ? $user->first_name : '';
			$last_name  = ! empty( $user->last_name ) ? $user->last_name : '';

			$user_name = ( $first_name || $last_name )
				? esc_html( trim( $first_name . ' ' . $last_name ) )
				: esc_html( $user->display_name );

			return '<span class="dashicons dashicons-admin-users mr-5"></span><a href="admin.php?page=pd-woo-customer&user_id=' 
				. esc_html( $user->ID ) . '">' . $user_name . '</a>';
		}

		// Fallback if something unexpected slips through
		return '<span class="dashicons dashicons-businessman mr-5"></span> Unknown';
	}
}
