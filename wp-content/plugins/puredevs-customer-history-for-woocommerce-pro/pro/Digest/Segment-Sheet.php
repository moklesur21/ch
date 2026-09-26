<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Digest segment sheet — attach a chosen segment's CSV to the weekly or monthly digest.
 *
 * Three registrations: one to register two options, one to render their settings section, and one to
 * write the file when the digest runs. The CSV itself comes from PDWCHS_Segments_Export's static row
 * builder, called with the default preset — this class owns no column layout and no member query.
 *
 * The sheet RIDES an existing digest. If no report type is enabled on the Settings page,
 * pd-woo-generate_weekly_report is deleted, the cron is never scheduled
 * (class-pd-customer-history.php:106-112) and no mail is sent, so no sheet is built either. The
 * settings description says so.
 *
 * Segment membership is evaluated at send time against the segment's current criteria, so $start and
 * $end are accepted and deliberately unused: a segment is a live query, not a period report.
 *
 * The exporter is called statically and never constructed — its constructor registers an admin_post
 * action, and a second instance would make every manual export write its CSV twice.
 *
 * Nothing here echoes outside the settings renderer. This runs unattended from cron, where anything
 * printed lands in the cron response — which is why the file is built in php://temp and written once
 * through WP_Filesystem, and never streamed straight out of a file handle.
 *
 * @since 3.1.2
 */
class PDWCHS_Digest_Segment_Sheet {

	/** Settings group, distinct from the free form's and from the carts group. */
	const SETTINGS_GROUP = 'pdwchs_digest_segment_settings';

	/** On/off. Default 0 — this option ships disabled. */
	const OPTION_ENABLED = 'pdwchs_digest_segment_enabled';

	/** Chosen segment id. 0 means none chosen. */
	const OPTION_SEGMENT = 'pdwchs_digest_segment_id';

	/**
	 * Three registrations, and no more.
	 *
	 * The accepted-args counts are both mandatory. pdwchs_register_pro_settings fires with
	 * ( $loader, $plugin_admin ) and the callback needs the loader; pdwchs_weekly_report_extra_sheets
	 * fires with ( $extra, $start, $end ). Omit either count and the callback silently receives fewer
	 * arguments — the same trap pro/CRM/Tags-Filter.php:46-47 documents.
	 *
	 * H19 is a value filter, so it is add_filter; the two settings seams are do_action.
	 */
	public function __construct() {
		add_action( 'pdwchs_register_pro_settings', array( $this, 'pdwchs_register_settings' ), 10, 2 );
		add_action( 'pdwchs_settings_extra_sections', array( $this, 'pdwchs_render_settings_section' ) );
		add_filter( 'pdwchs_weekly_report_extra_sheets', array( $this, 'pdwchs_add_segment_sheet' ), 10, 3 );
	}

	/**
	 * H17 — wire the option registration to admin_init through the loader.
	 *
	 * H17 fires during pdwchs_define_admin_hooks(), before $this->loader->run(). Registering directly
	 * on admin_init at that moment would work today and break the moment boot order changes; the
	 * loader is the contract. Abandoned-Carts-List-Table.php:98-100 is the precedent.
	 *
	 * @since 3.1.2
	 *
	 * @param object $loader       The plugin's hook loader.
	 * @param object $plugin_admin The admin class. Accepted because the seam passes it; unused here.
	 * @return void
	 */
	public function pdwchs_register_settings( $loader, $plugin_admin ) {
		$loader->add_action( 'admin_init', $this, 'pdwchs_do_register_settings' );
	}

	/**
	 * Register the two options.
	 *
	 * Both default to 0 — off, and no segment. This option ships disabled because it puts customer
	 * names and email addresses into an email attachment, and that is a choice the store owner makes
	 * deliberately.
	 *
	 * absint at the register_setting() layer means the Settings API sanitises before the option is
	 * written, so nothing downstream has to re-sanitise a stored value.
	 *
	 * @since 3.1.2
	 *
	 * @return void
	 */
	public function pdwchs_do_register_settings() {
		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_ENABLED,
			array(
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);

		register_setting(
			self::SETTINGS_GROUP,
			self::OPTION_SEGMENT,
			array(
				'sanitize_callback' => 'absint',
				'default'           => 0,
			)
		);
	}

	/**
	 * Render the settings section, below the free form.
	 *
	 * pdwchs_settings_extra_sections fires at plugin-settings.php:176, outside the free </form>, so
	 * this section carries its own form posting to options.php with its own settings_fields() and its
	 * own submit — exactly as the carts section does, and why no free template is edited.
	 *
	 * The capability check is defence in depth: the Settings page is registered under
	 * manage_woocommerce, but the section is rendered by an action anyone could fire.
	 *
	 * @since 3.1.2
	 *
	 * @return void
	 */
	public function pdwchs_render_settings_section() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		// Defensive, the way segments-list.php:44-46 does it: this section can render before the
		// free bootstrap has touched the manager. Its constructor registers no hooks.
		require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/segments/class-pdwchs-segments-manager.php';

		$manager  = new PDWCHS_Segments_Manager();
		$segments = $manager->pdwchs_get_segments();
		$chosen   = (int) get_option( self::OPTION_SEGMENT, 0 );
		?>
		<form action="<?php echo esc_url( admin_url( 'options.php' ) ); ?>" method="post" class="settings-form pdwchs-settings-card digest-segment-settings-form">
			<?php settings_fields( self::SETTINGS_GROUP ); ?>

			<div class="pdwchs-settings-card__head">
				<span class="dashicons dashicons-media-spreadsheet" aria-hidden="true"></span>
				<div class="pdwchs-settings-card__head-text">
					<h2><?php echo esc_html__( 'Digest Segment Sheet', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
					<p><?php echo esc_html__( 'Attach a segment\'s member list to your report digest as a CSV file.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
				</div>
			</div>

			<div class="pdwchs-settings-card__body">
				<p class="pdwchs-settings-note">
					<?php echo esc_html__( 'The file is attached to the digest you already receive — enable at least one report type above, or no digest is sent.', 'puredevs-customer-history-for-woocommerce' ); ?>
					<?php echo esc_html__( 'The segment\'s members are evaluated when the digest runs, not over the digest\'s date range.', 'puredevs-customer-history-for-woocommerce' ); ?>
				</p>

				<p class="pdwchs-settings-warning">
					<span class="dashicons dashicons-privacy" aria-hidden="true"></span>
					<span><?php echo esc_html__( 'The file contains customer names and email addresses. Only enable this if the digest recipient should receive them.', 'puredevs-customer-history-for-woocommerce' ); ?></span>
				</p>

				<div class="pdwchs-field">
					<div class="pdwchs-field__label">
						<label for="<?php echo esc_attr( self::OPTION_ENABLED ); ?>">
							<?php echo esc_html__( 'Attach a segment CSV', 'puredevs-customer-history-for-woocommerce' ); ?>
						</label>
					</div>
					<div class="pdwchs-field__control">
						<label class="pdwchs-check" for="<?php echo esc_attr( self::OPTION_ENABLED ); ?>">
							<input type="checkbox"
								id="<?php echo esc_attr( self::OPTION_ENABLED ); ?>"
								name="<?php echo esc_attr( self::OPTION_ENABLED ); ?>"
								value="1"
								<?php checked( 1, get_option( self::OPTION_ENABLED, 0 ) ); ?>>
							<span><?php echo esc_html__( 'Attach the sheet to every digest', 'puredevs-customer-history-for-woocommerce' ); ?></span>
						</label>
						<p class="description">
							<?php echo esc_html__( 'When unchecked, no file is built and the digest is unchanged.', 'puredevs-customer-history-for-woocommerce' ); ?>
						</p>
					</div>
				</div>

				<div class="pdwchs-field">
					<div class="pdwchs-field__label">
						<label for="<?php echo esc_attr( self::OPTION_SEGMENT ); ?>">
							<?php echo esc_html__( 'Segment', 'puredevs-customer-history-for-woocommerce' ); ?>
						</label>
					</div>
					<div class="pdwchs-field__control">
						<select id="<?php echo esc_attr( self::OPTION_SEGMENT ); ?>"
							name="<?php echo esc_attr( self::OPTION_SEGMENT ); ?>">
							<option value="0" <?php selected( 0, $chosen ); ?>>
								<?php echo esc_html__( '— Select a segment —', 'puredevs-customer-history-for-woocommerce' ); ?>
							</option>
							<?php foreach ( (array) $segments as $segment ) : ?>
								<option value="<?php echo esc_attr( $segment->id ); ?>" <?php selected( (int) $segment->id, $chosen ); ?>>
									<?php echo esc_html( $segment->name ); ?>
								</option>
							<?php endforeach; ?>
						</select>
						<p class="description">
							<?php echo esc_html__( 'Saved segments come from the Segments page. The digest uses the segment\'s current criteria.', 'puredevs-customer-history-for-woocommerce' ); ?>
						</p>
					</div>
				</div>
			</div>

			<div class="pdwchs-settings-card__foot">
				<input type="submit"
					value="<?php echo esc_attr__( 'Save Digest Segment Settings', 'puredevs-customer-history-for-woocommerce' ); ?>"
					class="button button-primary button-large">
			</div>
		</form>
		<?php
	}

	/**
	 * H19 — add the segment sheet to the digest.
	 *
	 * Four early returns, each costing zero work: a non-array $extra, the option off, no segment
	 * chosen, and a segment deleted since it was chosen. None of them queries members, writes a file
	 * or logs anything.
	 *
	 * $start and $end are accepted and deliberately unused. Removing them from the signature would
	 * break the 10, 3 contract; using them would turn a live segment into a period report.
	 *
	 * Nothing is pushed into $extra['html']. The other listeners build a preview table for the email
	 * body; a three-row preview of customer emails is more PII in more places for no gain.
	 *
	 * @since 3.1.2
	 *
	 * @param array  $extra Sheet payload with 'reports', 'attachments' and 'html' keys.
	 * @param mixed  $start Digest window start. Unused — membership is evaluated at send time.
	 * @param mixed  $end   Digest window end. Unused — membership is evaluated at send time.
	 * @return array
	 */
	public function pdwchs_add_segment_sheet( $extra, $start, $end ) {
		if ( ! is_array( $extra ) ) {
			return $extra;
		}

		if ( ! get_option( self::OPTION_ENABLED, 0 ) ) {
			return $extra;
		}

		$segment_id = (int) get_option( self::OPTION_SEGMENT, 0 );

		if ( $segment_id <= 0 ) {
			return $extra;
		}

		require_once PDWCHS_CUSTOMER_HISTORY_PLUGIN_DIR . 'includes/segments/class-pdwchs-segments-manager.php';

		$manager = new PDWCHS_Segments_Manager();
		$segment = $manager->pdwchs_get_segment( $segment_id );

		if ( ! $segment ) {
			return $extra;
		}

		$criteria = json_decode( $segment->criteria, true );
		$criteria = is_array( $criteria ) ? $criteria : array();

		$path = $this->pdwchs_write_csv( $segment, $criteria );

		if ( ! $path ) {
			// The filesystem refused. The digest still goes out with its other sheets — a segment
			// attachment is an add-on, not a precondition.
			return $extra;
		}

		$extra['attachments'][] = $path;

		/* translators: %s: saved segment name. */
		$extra['reports'][] = sprintf( __( 'Segment: %s', 'puredevs-customer-history-for-woocommerce' ), $segment->name );

		return $extra;
	}

	/**
	 * Build the CSV in memory and write it once through WP_Filesystem.
	 *
	 * This is the free digest's own pattern (class-pd-customer-history.php:428-451). The sibling H19
	 * listener streams its file straight to the response, which prints the CSV into the cron output.
	 * Nothing here passes the file through.
	 *
	 * The header row is written even when there are no rows: an empty segment attaches a header-only
	 * file and is still named in the report list, because a missing attachment reads as a broken
	 * feature rather than an empty segment.
	 *
	 * The filename mirrors the manual download's, so a recipient comparing the two sees the same
	 * name. sanitize_title() on the user-supplied segment name is what keeps a path separator out.
	 *
	 * No capability check and no nonce, deliberately: this runs unattended from cron, which is the
	 * whole reason R15-27A's builders are callable without a request.
	 *
	 * @since 3.1.2
	 *
	 * @param object $segment  The saved segment row.
	 * @param array  $criteria Its decoded criteria.
	 * @return string Absolute path on success, empty string on failure.
	 */
	private function pdwchs_write_csv( $segment, $criteria ) {
		$columns = PDWCHS_Segments_Export::pdwchs_get_export_columns( 'default' );
		$rows    = PDWCHS_Segments_Export::pdwchs_get_export_rows( $criteria, 'default' );

		$handle = fopen( 'php://temp', 'r+' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- memory stream, not filesystem.

		if ( ! $handle ) {
			return '';
		}

		fputcsv( $handle, array_values( $columns ) );

		foreach ( $rows as $row ) {
			fputcsv( $handle, $row );
		}

		rewind( $handle );
		$contents = stream_get_contents( $handle );
		fclose( $handle ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- memory stream handle.

		if ( ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}

		global $wp_filesystem;
		WP_Filesystem();

		$upload = wp_upload_dir();
		$path   = trailingslashit( $upload['path'] ) . 'segment-' . sanitize_title( $segment->name ) . '-' . gmdate( 'Y-m-d' ) . '.csv';

		return $wp_filesystem->put_contents( $path, $contents, FS_CHMOD_FILE ) ? $path : '';
	}
}
