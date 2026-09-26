<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class PDWCHS_Abandoned_Carts_List_Table extends WP_List_Table {

	public function __construct() {
		if ( function_exists( 'convert_to_screen' ) ) {
			parent::__construct( array(
				'singular' => 'cart',
				'plural'   => 'carts',
				'ajax'     => false,
			) );
		}
		add_action( 'pdwchs_admin_menu_early',        [ $this, 'pdwchs_register_menu' ] );
		add_action( 'pdwchs_enqueue_admin_styles',    [ $this, 'pdwchs_enqueue_styles' ] );
		add_action( 'pdwchs_enqueue_admin_scripts',   [ $this, 'pdwchs_enqueue_scripts' ] );
		add_action( 'pdwchs_register_pro_settings',   [ $this, 'pdwchs_register_settings' ], 10, 2 );
		add_action( 'pdwchs_settings_extra_sections', [ $this, 'pdwchs_render_settings_section' ] );
	}

	public function pdwchs_init_table() {
		parent::__construct( array(
			'singular' => 'cart',
			'plural'   => 'carts',
			'ajax'     => false,
		) );
	}

	// H2 — register Abandoned Carts submenu.
	public function pdwchs_register_menu( $capability ) {
		if ( ! get_option( 'pdwchs_cart_tracking_enabled', 1 ) ) {
			return;
		}
		add_submenu_page(
			'pd-woo-dashboard',
			__( 'Abandoned Carts', 'puredevs-customer-history-for-woocommerce' ),
			__( '<span id="pd-abandoned-carts-menu">Abandoned Carts</span>', 'puredevs-customer-history-for-woocommerce' ),
			$capability,
			'pd-woo-abandoned-carts',
			[ $this, 'pdwchs_abandoned_carts_page' ]
		);
	}

	public function pdwchs_abandoned_carts_page() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'puredevs-customer-history-for-woocommerce' ) );
		}
		$this->pdwchs_init_table();
		require __DIR__ . '/templates/abandoned-carts-list.php';
	}

	// H4 — enqueue carts CSS on abandoned-carts and dashboard pages.
	public function pdwchs_enqueue_styles( $version ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || ! in_array( sanitize_text_field( wp_unslash( $_GET['page'] ) ), array( 'pd-woo-abandoned-carts', 'pd-woo-dashboard' ), true ) ) {
			return;
		}
		$admin_url = PDWCHS_PRO_PLUGIN_URL . 'admin/';
		wp_enqueue_style(
			'pdwchs-carts-css',
			$admin_url . 'css/pd-customer-history-carts.css',
			array(),
			$version,
			'all'
		);
	}

	// H5 — enqueue abandoned-carts JS on the list page.
	public function pdwchs_enqueue_scripts( $version ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || 'pd-woo-abandoned-carts' !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}
		$admin_url = PDWCHS_PRO_PLUGIN_URL . 'admin/';
		wp_enqueue_script(
			'pdwchs-abandoned-carts',
			$admin_url . 'js/pdwchs-abandoned-carts.js',
			array( 'jquery', 'date-picker' ),
			$version,
			true
		);
		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		wp_localize_script( 'pdwchs-abandoned-carts', 'pdwchsCartsVars', array(
			'ajaxurl'   => admin_url( 'admin-ajax.php' ),
			'nonce'     => wp_create_nonce( 'pdwchs_cart_nonce' ),
			'dateStart' => sanitize_text_field( wp_unslash( $_GET['date_start'] ?? '' ) ),
			'dateEnd'   => sanitize_text_field( wp_unslash( $_GET['date_end'] ?? '' ) ),
			'i18n'      => array(
				'confirmDelete' => __( 'Are you sure? This cannot be undone.', 'puredevs-customer-history-for-woocommerce' ),
			),
		) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
	}

	// H17 — wire cart settings registration to admin_init via loader.
	public function pdwchs_register_settings( $loader, $plugin_admin ) {
		$loader->add_action( 'admin_init', $this, 'pdwchs_do_register_settings' );
	}

	public function pdwchs_do_register_settings() {
		register_setting(
			'pdwchs_cart_settings',
			'pdwchs_cart_tracking_enabled',
			[ 'sanitize_callback' => 'absint', 'default' => 1 ]
		);
		register_setting(
			'pdwchs_cart_settings',
			'pdwchs_cart_email_capture_enabled',
			[ 'sanitize_callback' => 'absint', 'default' => 1 ]
		);
		register_setting(
			'pdwchs_cart_settings',
			'pdwchs_cart_abandonment_timeout_minutes',
			[ 'sanitize_callback' => [ $this, 'pdwchs_sanitize_cart_timeout' ], 'default' => 60 ]
		);
		register_setting(
			'pdwchs_cart_settings',
			'pdwchs_cart_cleanup_days',
			[ 'sanitize_callback' => [ $this, 'pdwchs_sanitize_cart_cleanup_days' ], 'default' => 90 ]
		);
	}

	public function pdwchs_sanitize_cart_timeout( $value ) {
		return max( 5, absint( $value ) );
	}

	public function pdwchs_sanitize_cart_cleanup_days( $value ) {
		return max( 7, absint( $value ) );
	}

	// H18 — render cart settings section on the Settings page.
	public function pdwchs_render_settings_section() {
		if ( ! pdwchs_is_pro_active() ) {
			return;
		}
		?>
		<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post" class="settings-form pdwchs-settings-card cart-settings-form">
			<?php settings_fields( 'pdwchs_cart_settings' ); ?>

			<div class="pdwchs-settings-card__head">
				<span class="dashicons dashicons-cart" aria-hidden="true"></span>
				<div class="pdwchs-settings-card__head-text">
					<h2><?php echo esc_html__( 'Abandoned Cart Tracking', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
					<p><?php echo esc_html__( 'Observe and store WooCommerce cart activity for reporting.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
				</div>
			</div>

			<div class="pdwchs-settings-card__body">
				<p class="pdwchs-settings-note">
					<?php echo esc_html__( 'Configure how WooCommerce cart activity is observed and stored. This feature records cart snapshots for reporting only — no recovery emails are sent and no coupons are generated. Recovery actions belong in the Automation Add-on.', 'puredevs-customer-history-for-woocommerce' ); ?>
				</p>

				<div class="pdwchs-field">
					<div class="pdwchs-field__label">
						<label for="pdwchs_cart_tracking_enabled">
							<?php echo esc_html__( 'Cart tracking', 'puredevs-customer-history-for-woocommerce' ); ?>
						</label>
					</div>
					<div class="pdwchs-field__control">
						<label class="pdwchs-check" for="pdwchs_cart_tracking_enabled">
							<input type="checkbox"
							       id="pdwchs_cart_tracking_enabled"
							       name="pdwchs_cart_tracking_enabled"
							       value="1"
							       <?php checked( 1, get_option( 'pdwchs_cart_tracking_enabled', 1 ) ); ?>>
							<span><?php echo esc_html__( 'Record cart snapshots', 'puredevs-customer-history-for-woocommerce' ); ?></span>
						</label>
						<p class="description">
							<?php echo esc_html__( 'When unchecked, no cart rows are written or updated.', 'puredevs-customer-history-for-woocommerce' ); ?>
						</p>
					</div>
				</div>

				<div class="pdwchs-field">
					<div class="pdwchs-field__label">
						<label for="pdwchs_cart_email_capture_enabled">
							<?php echo esc_html__( 'Email early capture', 'puredevs-customer-history-for-woocommerce' ); ?>
						</label>
					</div>
					<div class="pdwchs-field__control">
						<label class="pdwchs-check" for="pdwchs_cart_email_capture_enabled">
							<input type="checkbox"
							       id="pdwchs_cart_email_capture_enabled"
							       name="pdwchs_cart_email_capture_enabled"
							       value="1"
							       <?php checked( 1, get_option( 'pdwchs_cart_email_capture_enabled', 1 ) ); ?>>
							<span><?php echo esc_html__( 'Capture the billing email at checkout', 'puredevs-customer-history-for-woocommerce' ); ?></span>
						</label>
						<p class="description">
							<?php echo esc_html__( 'Capture the billing email on the checkout page before the order is placed.', 'puredevs-customer-history-for-woocommerce' ); ?>
						</p>
					</div>
				</div>

				<div class="pdwchs-field">
					<div class="pdwchs-field__label">
						<label for="pdwchs_cart_abandonment_timeout_minutes">
							<?php echo esc_html__( 'Abandonment timeout (minutes)', 'puredevs-customer-history-for-woocommerce' ); ?>
						</label>
					</div>
					<div class="pdwchs-field__control">
						<input type="number"
						       id="pdwchs_cart_abandonment_timeout_minutes"
						       name="pdwchs_cart_abandonment_timeout_minutes"
						       class="small-text"
						       min="5"
						       value="<?php echo esc_attr( get_option( 'pdwchs_cart_abandonment_timeout_minutes', 60 ) ); ?>">
						<p class="description">
							<?php echo esc_html__( 'Minutes of inactivity before a cart is marked abandoned. Minimum 5.', 'puredevs-customer-history-for-woocommerce' ); ?>
						</p>
					</div>
				</div>

				<div class="pdwchs-field">
					<div class="pdwchs-field__label">
						<label for="pdwchs_cart_cleanup_days">
							<?php echo esc_html__( 'Data retention (days)', 'puredevs-customer-history-for-woocommerce' ); ?>
						</label>
					</div>
					<div class="pdwchs-field__control">
						<input type="number"
						       id="pdwchs_cart_cleanup_days"
						       name="pdwchs_cart_cleanup_days"
						       class="small-text"
						       min="7"
						       value="<?php echo esc_attr( get_option( 'pdwchs_cart_cleanup_days', 90 ) ); ?>">
						<p class="description">
							<?php echo esc_html__( 'Cart rows older than this many days are deleted by the cleanup cron. Minimum 7.', 'puredevs-customer-history-for-woocommerce' ); ?>
						</p>
					</div>
				</div>
			</div>

			<div class="pdwchs-settings-card__foot">
				<input type="submit"
				       value="<?php echo esc_attr__( 'Save Cart Settings', 'puredevs-customer-history-for-woocommerce' ); ?>"
				       class="button button-primary button-large">
			</div>
		</form>
		<?php
	}

	// --- WP_List_Table methods (moved verbatim from admin/class-pdwchs-abandoned-carts-list-table.php) ---

	public function get_columns() {
		return array(
			'customer' => __( 'Customer', 'puredevs-customer-history-for-woocommerce' ),
			'items'    => __( 'Items', 'puredevs-customer-history-for-woocommerce' ),
			'total'    => __( 'Total', 'puredevs-customer-history-for-woocommerce' ),
			'status'   => __( 'Status', 'puredevs-customer-history-for-woocommerce' ),
			'updated'  => __( 'Last Updated', 'puredevs-customer-history-for-woocommerce' ),
			'actions'  => __( 'Actions', 'puredevs-customer-history-for-woocommerce' ),
		);
	}

	public function get_sortable_columns() {
		return array(
			'total'   => array( 'cart_total', false ),
			'items'   => array( 'items_count', false ),
			'updated' => array( 'updated_at', true ),
		);
	}

	public function prepare_items() {
		global $wpdb;

		$allowed_orderby = array( 'updated_at', 'cart_total', 'items_count' );
		$allowed_status  = array( 'active', 'abandoned', 'recovered', 'completed' );

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- WP_List_Table filter forms use GET without nonces; this is the WordPress core pattern.
		$status     = sanitize_text_field( wp_unslash( $_GET['status'] ?? '' ) );
		$date_start = sanitize_text_field( wp_unslash( $_GET['date_start'] ?? '' ) );
		$date_end   = sanitize_text_field( wp_unslash( $_GET['date_end'] ?? '' ) );
		$min_total  = max( 0, floatval( wp_unslash( $_GET['min_total'] ?? 0 ) ) );

		$raw_orderby = sanitize_text_field( wp_unslash( $_GET['orderby'] ?? 'updated_at' ) );
		$orderby     = in_array( $raw_orderby, $allowed_orderby, true ) ? $raw_orderby : 'updated_at';

		$raw_order = strtoupper( sanitize_text_field( wp_unslash( $_GET['order'] ?? 'DESC' ) ) );
		$order     = ( 'ASC' === $raw_order ) ? 'ASC' : 'DESC';

		$per_page = 20;
		$paged    = max( 1, absint( wp_unslash( $_GET['paged'] ?? 1 ) ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended
		$offset   = ( $paged - 1 ) * $per_page;

		// Validate date format.
		if ( ! empty( $date_start ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_start ) ) {
			$date_start = '';
		}
		if ( ! empty( $date_end ) && ! preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date_end ) ) {
			$date_end = '';
		}

		// Validate status against allowlist.
		if ( ! in_array( $status, $allowed_status, true ) ) {
			$status = '';
		}

		$table = $wpdb->prefix . 'pdwchs_carts';

		// Build WHERE clauses.
		$where      = 'WHERE 1=1';
		$where_args = array();

		if ( '' !== $status ) {
			$where      .= ' AND status = %s';
			$where_args[] = $status;
		}
		if ( '' !== $date_start ) {
			$where      .= ' AND updated_at >= %s';
			$where_args[] = $date_start . ' 00:00:00';
		}
		if ( '' !== $date_end ) {
			$where      .= ' AND updated_at <= %s';
			$where_args[] = $date_end . ' 23:59:59';
		}
		if ( $min_total > 0 ) {
			$where      .= ' AND cart_total >= %f';
			$where_args[] = $min_total;
		}

		// Count query.
		$count_sql = "SELECT COUNT(*) FROM {$table} {$where}";
		if ( ! empty( $where_args ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total_items = (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $where_args ) );
		} else {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$total_items = (int) $wpdb->get_var( $count_sql );
		}

		// Data query — $orderby and $order come from validated allowlists.
		$data_sql  = "SELECT * FROM {$table} {$where} ORDER BY {$orderby} {$order} LIMIT %d OFFSET %d";
		$data_args = array_merge( $where_args, array( $per_page, $offset ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$items = $wpdb->get_results( $wpdb->prepare( $data_sql, $data_args ) );

		$this->_column_headers = array( $this->get_columns(), array(), $this->get_sortable_columns() );
		$this->items           = $items;

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page,
			'total_pages' => ceil( $total_items / $per_page ),
		) );
	}

	public function column_customer( $item ) {
		if ( (int) $item->user_id > 0 ) {
			$user = get_userdata( (int) $item->user_id );
			if ( $user ) {
				$url = admin_url( 'admin.php?page=pd-woo-customer&user_id=' . absint( $item->user_id ) );
				return '<a href="' . esc_url( $url ) . '">' . esc_html( $user->display_name ) . '</a>'
					 . '<br><small>' . esc_html( $user->user_email ) . '</small>';
			}
		}

		$email = ! empty( $item->email ) ? esc_html( $item->email ) : '&mdash;';
		return '<span class="pdwchs-guest-badge">' . esc_html__( 'Guest', 'puredevs-customer-history-for-woocommerce' ) . '</span>'
			 . '<br><small>' . $email . '</small>';
	}

	public function column_items( $item ) {
		return esc_html( number_format_i18n( (int) $item->items_count ) );
	}

	public function column_total( $item ) {
		return wc_price( $item->cart_total, array( 'currency' => $item->currency ) );
	}

	public function column_status( $item ) {
		$map = array(
			'active'    => __( 'Active',    'puredevs-customer-history-for-woocommerce' ),
			'abandoned' => __( 'Abandoned', 'puredevs-customer-history-for-woocommerce' ),
			'recovered' => __( 'Recovered', 'puredevs-customer-history-for-woocommerce' ),
			'completed' => __( 'Completed', 'puredevs-customer-history-for-woocommerce' ),
		);
		$label = $map[ $item->status ] ?? esc_html( $item->status );
		return '<span class="pdwchs-cart-status pdwchs-status-' . esc_attr( $item->status ) . '">'
			 . esc_html( $label )
			 . '</span>';
	}

	public function column_updated( $item ) {
		$ts    = strtotime( $item->updated_at );
		$human = human_time_diff( $ts, current_time( 'timestamp' ) );
		$full  = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $ts );
		return '<span title="' . esc_attr( $full ) . '">'
			 . sprintf( esc_html__( '%s ago', 'puredevs-customer-history-for-woocommerce' ), esc_html( $human ) )
			 . '</span>';
	}

	public function column_actions( $item ) {
		$url = admin_url( 'admin.php?page=pd-woo-abandoned-carts&cart_id=' . absint( $item->id ) );
		return '<a href="' . esc_url( $url ) . '" class="button button-small">'
			 . esc_html__( 'View Detail', 'puredevs-customer-history-for-woocommerce' )
			 . '</a>';
	}

	public function column_default( $item, $column_name ) {
		return '';
	}

	public function extra_tablenav( $which ) {
		if ( 'top' !== $which ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Pre-populating filter form fields from GET; read-only, no state change.
		$current_status     = sanitize_text_field( wp_unslash( $_GET['status'] ?? '' ) );
		$current_date_start = sanitize_text_field( wp_unslash( $_GET['date_start'] ?? '' ) );
		$current_date_end   = sanitize_text_field( wp_unslash( $_GET['date_end'] ?? '' ) );
		$current_min_total  = floatval( wp_unslash( $_GET['min_total'] ?? 0 ) );
		// phpcs:enable WordPress.Security.NonceVerification.Recommended

		$display_range = '';
		if ( $current_date_start && $current_date_end ) {
			$display_range = esc_attr( $current_date_start . ' – ' . $current_date_end );
		}
		?>
		<div class="pdwchs-carts-filter-form">
			<form method="get">
				<input type="hidden" name="page" value="pd-woo-abandoned-carts">

				<select name="status">
					<option value=""><?php esc_html_e( 'All Statuses', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					<?php
					$statuses = array(
						'active'    => __( 'Active',    'puredevs-customer-history-for-woocommerce' ),
						'abandoned' => __( 'Abandoned', 'puredevs-customer-history-for-woocommerce' ),
						'recovered' => __( 'Recovered', 'puredevs-customer-history-for-woocommerce' ),
						'completed' => __( 'Completed', 'puredevs-customer-history-for-woocommerce' ),
					);
					foreach ( $statuses as $val => $label ) {
						printf(
							'<option value="%s"%s>%s</option>',
							esc_attr( $val ),
							selected( $current_status, $val, false ),
							esc_html( $label )
						);
					}
					?>
				</select>

				<input
					type="text"
					name="date_range"
					id="pdwchs-carts-date-range"
					placeholder="<?php esc_attr_e( 'Date range', 'puredevs-customer-history-for-woocommerce' ); ?>"
					value="<?php echo esc_attr( $display_range ); ?>"
					autocomplete="off"
				>
				<input type="hidden" name="date_start" value="<?php echo esc_attr( $current_date_start ); ?>">
				<input type="hidden" name="date_end"   value="<?php echo esc_attr( $current_date_end ); ?>">

				<input
					type="number"
					name="min_total"
					placeholder="<?php esc_attr_e( 'Min total', 'puredevs-customer-history-for-woocommerce' ); ?>"
					step="0.01"
					min="0"
					value="<?php echo esc_attr( $current_min_total > 0 ? $current_min_total : '' ); ?>"
				>

				<button type="submit" class="button"><?php esc_html_e( 'Filter', 'puredevs-customer-history-for-woocommerce' ); ?></button>
			</form>
		</div>
		<?php
	}

	public function no_items() {
		echo esc_html__( 'No carts found matching your filters.', 'puredevs-customer-history-for-woocommerce' );
	}
}
