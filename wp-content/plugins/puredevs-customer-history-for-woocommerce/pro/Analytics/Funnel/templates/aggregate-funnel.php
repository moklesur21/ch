<?php
defined( 'ABSPATH' ) or exit;

/*
 *  Live Sessions Product Taxonomy Stats
 */

include PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php';
include PDWCHS_PRO_PLUGIN_DIR . 'Stats/templates/top-sub-nav-stats.php';
$pdwchs_search = ! empty( $_GET['search_session'] ) ? sanitize_text_field( wp_unslash( $_GET['search_session'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
?>

<br class="clear">

<div class="wrap pdwchs-aggregate-funnel-wrap">
	<h1><?php esc_html_e( 'Aggregate Conversion Funnel', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

	<div class="pdwchs-funnel-filters">
		<div class="pdwchs-filter-group">
			<label for="pdwchs-funnel-date-range">
				<?php esc_html_e( 'Date Range', 'puredevs-customer-history-for-woocommerce' ); ?>
			</label>
			<input id="pdwchs-funnel-date-range" type="text" readonly class="pdwchs-date-range-input"
			       value="<?php esc_attr_e( 'Last 30 Days', 'puredevs-customer-history-for-woocommerce' ); ?>">
		</div>
		<div class="pdwchs-filter-group pdwchs-granularity-group">
			<span><?php esc_html_e( 'Granularity', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<label><input type="radio" name="pdwchs_funnel_granularity" value="day" checked>
				<?php esc_html_e( 'Day', 'puredevs-customer-history-for-woocommerce' ); ?></label>
			<label><input type="radio" name="pdwchs_funnel_granularity" value="week">
				<?php esc_html_e( 'Week', 'puredevs-customer-history-for-woocommerce' ); ?></label>
			<label><input type="radio" name="pdwchs_funnel_granularity" value="month">
				<?php esc_html_e( 'Month', 'puredevs-customer-history-for-woocommerce' ); ?></label>
		</div>
		<button id="pdwchs-funnel-apply" class="button button-primary">
			<?php esc_html_e( 'Apply', 'puredevs-customer-history-for-woocommerce' ); ?>
		</button>
	</div>

	<div class="pdwchs-funnel-loading" style="display:none;">
		<span class="spinner is-active"></span>
		<p><?php esc_html_e( 'Loading…', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>
	<div class="pdwchs-funnel-error" style="display:none;">
		<p><?php esc_html_e( 'Error loading data. Please try again.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>
	<div class="pdwchs-funnel-empty" style="display:none;">
		<p><?php esc_html_e( 'No funnel data for this period.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
	</div>

	<div class="pdwchs-funnel-content" style="display:none;">
		<div class="pdwchs-funnel-snapshot">
			<div class="pdwchs-chart-container">
				<canvas id="pdwchs-funnel-bar-chart" height="200"
				        aria-label="<?php esc_attr_e( 'Conversion funnel chart', 'puredevs-customer-history-for-woocommerce' ); ?>"
				        role="img"></canvas>
			</div>
			<div id="pdwchs-funnel-stat-cards" class="pdwchs-funnel-stat-cards"></div>
		</div>
		<h2><?php esc_html_e( 'Funnel Over Time', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
		<div class="pdwchs-chart-container">
			<canvas id="pdwchs-funnel-trend-chart" height="300"
			        aria-label="<?php esc_attr_e( 'Funnel trend chart', 'puredevs-customer-history-for-woocommerce' ); ?>"
			        role="img"></canvas>
		</div>
	</div>
</div>

<div id="pd-customer-history">
	<div id="conversion-funnel-visualization" class="wrap">
		<?php
		$num_of_days       = ! empty( $_GET['num-of-days'] ) ? absint( $_GET['num-of-days'] ) : 7; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$pdwchs_show_items = isset( $_GET['show-items'] ) ? sanitize_text_field( wp_unslash( $_GET['show-items'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		?>
		<h1><?php echo esc_html( sprintf( __( 'Conversion Funnel Visualization (Last %d days)', 'puredevs-customer-history-for-woocommerce' ), $num_of_days ) ); ?></h1>

		<form action="" class="search-box">
			<input type="hidden" name="page" value="pd-woo-conversion-funnel-stats">
			<input type="search" id="user-search-input" name="search_session" placeholder="<?php echo esc_attr__( 'Product Search', 'puredevs-customer-history-for-woocommerce' ); ?>" value="<?php echo esc_attr( $pdwchs_search ); ?>">
			<input type="submit" id="search-submit" class="button" value="<?php echo esc_attr__( 'Search Products', 'puredevs-customer-history-for-woocommerce' ); ?>">
		</form>

		<div class="show-results">
			<form action="<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized ?>" name="show-result" id="show-result" method="get">
				<label><?php echo esc_html__( 'Show :', 'puredevs-customer-history-for-woocommerce' ); ?></label>
				<select name="show-items" id="show-items">
					<option value=""><?php echo esc_html__( 'select one', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					<option value="10"  <?php selected( $pdwchs_show_items, '10' );  ?>>10</option>
					<option value="20"  <?php selected( $pdwchs_show_items, '20' );  ?>>20</option>
					<option value="50"  <?php selected( $pdwchs_show_items, '50' );  ?>>50</option>
					<option value="100" <?php selected( $pdwchs_show_items, '100' ); ?>>100</option>
				</select>
				<?php echo esc_html__( 'entries', 'puredevs-customer-history-for-woocommerce' ); ?>
			</form>
		</div>

		<div class="show-results num-of-days-results">
			<form action="<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ?? '' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized ?>" name="show-num-of-days-result" id="show-num-of-days-result" method="get">
				<label><?php echo esc_html__( 'Num of Days :', 'puredevs-customer-history-for-woocommerce' ); ?></label>
				<select name="num-of-days" id="num-of-days">
					<option value=""><?php echo esc_html__( 'select one', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					<option value="3"  <?php selected( $num_of_days, 3 );  ?>>3 days</option>
					<option value="7"  <?php selected( $num_of_days, 7 );  ?>>7 days</option>
					<option value="15" <?php selected( $num_of_days, 15 ); ?>>15 days</option>
					<option value="30" <?php selected( $num_of_days, 30 ); ?>>1 month</option>
				</select>
			</form>
		</div>

		<?php
		$funnel_table = new PDWCHS_Funnel_Session_List_Table();
		$funnel_table->prepare_items( $num_of_days, $pdwchs_search );
		$funnel_table->display();
		?>
	</div>
</div>
