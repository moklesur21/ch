<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cohort matrix CSV export.
 *
 * Streams the stored matrix — never recomputes it — as a CSV over admin-post.
 *
 * @since 3.1.0
 */
class PDWCHS_Cohort_Export {

	const ACTION = 'pdwchs_export_cohorts_csv';
	const NONCE  = 'pdwchs_export_cohorts_nonce';

	/**
	 * Register the privileged admin-post listener.
	 *
	 * Only the privileged action is registered: a logged-out request finds no
	 * listener and reaches no code of ours.
	 *
	 * @since 3.1.0
	 */
	public function __construct() {
		add_action( 'admin_post_' . self::ACTION, array( $this, 'pdwchs_handle_export' ) );
	}

	/**
	 * Handle the export request. Streams the file to the browser.
	 *
	 * Nonce first, capability second, work third — nothing may touch request
	 * data before check_admin_referer() returns.
	 *
	 * @since 3.1.0
	 *
	 * @return void
	 */
	public function pdwchs_handle_export() {
		check_admin_referer( self::NONCE, 'nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( 'Unauthorized.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$matrix = PDWCHS_Cohort_Cron::pdwchs_get_matrix();
		if ( null === $matrix || empty( $matrix['cohorts'] ) ) {
			wp_die( esc_html__( 'No cohort data available to export.', 'puredevs-customer-history-for-woocommerce' ) );
		}

		$view = isset( $_POST['view'] ) ? sanitize_text_field( wp_unslash( $_POST['view'] ) ) : '';
		if ( ! in_array( $view, PDWCHS_Cohort_Page::VIEWS, true ) ) {
			$view = 'customers';
		}

		$date     = gmdate( 'Y-m-d' );
		$filename = "cohorts-{$view}-{$date}.csv";

		ob_end_clean();
		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );
		header( 'Pragma: no-cache' );
		header( 'Expires: 0' );

		$output = fopen( 'php://output', 'w' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- output stream, not filesystem
		fputs( $output, "\xEF\xBB\xBF" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fputs -- output stream for CSV BOM

		$header = array(
			__( 'Cohort', 'puredevs-customer-history-for-woocommerce' ),
			__( 'Customers', 'puredevs-customer-history-for-woocommerce' ),
		);
		for ( $offset = 0; $offset <= 12; $offset++ ) {
			/* translators: %d: number of months since acquisition. */
			$header[] = sprintf( __( 'M+%d', 'puredevs-customer-history-for-woocommerce' ), $offset );
		}
		fputcsv( $output, $header );

		foreach ( $matrix['cohorts'] as $cohort ) {
			$size = (int) $cohort['size'];
			$row  = array( $cohort['month'], $size );

			for ( $offset = 0; $offset <= 12; $offset++ ) {
				$cell = $cohort['cells'][ $offset ];

				if ( false === $cell['complete'] || 0 === $size ) {
					$row[] = '';
					continue;
				}

				if ( 'revenue' === $view ) {
					$row[] = number_format( (float) $cell['revenue'], 2, '.', '' );
				} else {
					$row[] = number_format( $cell['customers'] / $size * 100, 1, '.', '' );
				}
			}

			fputcsv( $output, $row );
		}

		fclose( $output ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- output stream handle
		exit;
	}
}
