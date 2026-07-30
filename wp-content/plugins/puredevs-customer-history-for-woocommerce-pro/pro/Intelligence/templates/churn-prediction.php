<?php
/**
 * Churn Prediction Full Page
 *
 * Admin page with tabs for At-Risk Customers, Alerts, Analytics, and Settings.
 *
 * @link       https://puredevs.com/
 * @since      4.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/admin/partials/templates
 */

defined( 'ABSPATH' ) or exit;

// Get intelligence instance
$intelligence = null;
if ( function_exists( 'pdwchs_get_plugin_instance' ) ) {
	$plugin = pdwchs_get_plugin_instance();
	if ( $plugin && method_exists( $plugin, 'pdwchs_get_intelligence' ) ) {
		$intelligence = $plugin->pdwchs_get_intelligence();
	}
}

$churn_calculator = null;
$alerts_manager   = null;

if ( $intelligence && method_exists( $intelligence, 'pdwchs_get_churn_calculator' ) ) {
	$churn_calculator = $intelligence->pdwchs_get_churn_calculator();
}
if ( $intelligence && method_exists( $intelligence, 'pdwchs_get_alerts_manager' ) ) {
	$alerts_manager = $intelligence->pdwchs_get_alerts_manager();
}

// Get data for the page
$distribution        = $churn_calculator ? $churn_calculator->pdwchs_get_risk_distribution() : array( 'low' => 0, 'medium' => 0, 'high' => 0, 'critical' => 0, 'total' => 0 );
$high_risk_customers = $churn_calculator ? $churn_calculator->pdwchs_get_high_risk_customers( 25 ) : array();
$active_alerts       = $alerts_manager ? $alerts_manager->pdwchs_get_active_alerts( 25 ) : array();
$alert_stats         = $alerts_manager ? $alerts_manager->pdwchs_get_alert_statistics( 30 ) : array();

// Get current settings
$settings = array(
	'risk_threshold_low_max'    => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'risk_threshold_low_max', 30 ) : 30,
	'risk_threshold_medium_max' => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'risk_threshold_medium_max', 60 ) : 60,
	'risk_threshold_high_max'   => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'risk_threshold_high_max', 80 ) : 80,
	'weight_recency'            => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'weight_recency', 40 ) : 40,
	'weight_frequency'          => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'weight_frequency', 25 ) : 25,
	'weight_monetary'           => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'weight_monetary', 20 ) : 20,
	'weight_engagement'         => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'weight_engagement', 15 ) : 15,
	'alerts_enabled'            => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'alerts_enabled', true ) : true,
	'alert_for_high_risk'       => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'alert_for_high_risk', true ) : true,
	'alert_for_critical_risk'   => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'alert_for_critical_risk', true ) : true,
	'alert_email_recipient'     => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'alert_email_recipient', '' ) : '',
	'min_orders_for_prediction' => $churn_calculator ? $churn_calculator->pdwchs_get_setting( 'min_orders_for_prediction', 2 ) : 2,
);

// Get active tab
$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( $_GET['tab'] ) : 'at-risk';

// Get admin users for alert assignment
$admin_users = get_users( array(
	'role__in' => array( 'administrator', 'shop_manager' ),
	'fields'   => array( 'ID', 'display_name' ),
) );

include PDWCHS_CUSTOMER_HISTORY_ADMIN_TEMPLATE_DIR . '/top-nav.php';
?>

<div class="wrap pdwchs-churn-prediction-wrap">
	<h1 class="wp-heading-inline"><?php esc_html_e( 'Churn Prediction', 'puredevs-customer-history-for-woocommerce' ); ?></h1>

	<!-- Summary Stats -->
	<div class="pdwchs-prediction-summary">
		<div class="pdwchs-churn-stats-grid">
			<div class="pdwchs-churn-stat-box critical">
				<span class="pdwchs-stat-number"><?php echo esc_html( $distribution['critical'] ); ?></span>
				<span class="pdwchs-stat-label"><?php esc_html_e( 'Critical Risk', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			</div>
			<div class="pdwchs-churn-stat-box high">
				<span class="pdwchs-stat-number"><?php echo esc_html( $distribution['high'] ); ?></span>
				<span class="pdwchs-stat-label"><?php esc_html_e( 'High Risk', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			</div>
			<div class="pdwchs-churn-stat-box medium">
				<span class="pdwchs-stat-number"><?php echo esc_html( $distribution['medium'] ); ?></span>
				<span class="pdwchs-stat-label"><?php esc_html_e( 'Medium Risk', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			</div>
			<div class="pdwchs-churn-stat-box alerts">
				<span class="pdwchs-stat-number"><?php echo esc_html( count( $active_alerts ) ); ?></span>
				<span class="pdwchs-stat-label"><?php esc_html_e( 'Active Alerts', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			</div>
		</div>
	</div>

	<!-- Tab Navigation -->
	<nav class="nav-tab-wrapper pdwchs-prediction-tabs">
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-churn-prediction&tab=at-risk' ) ); ?>"
		   class="nav-tab <?php echo $active_tab === 'at-risk' ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-warning"></span>
			<?php esc_html_e( 'At-Risk Customers', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-churn-prediction&tab=alerts' ) ); ?>"
		   class="nav-tab <?php echo $active_tab === 'alerts' ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-bell"></span>
			<?php esc_html_e( 'Alerts', 'puredevs-customer-history-for-woocommerce' ); ?>
			<?php if ( count( $active_alerts ) > 0 ) : ?>
				<span class="pdwchs-tab-badge"><?php echo esc_html( count( $active_alerts ) ); ?></span>
			<?php endif; ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-churn-prediction&tab=analytics' ) ); ?>"
		   class="nav-tab <?php echo $active_tab === 'analytics' ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-chart-pie"></span>
			<?php esc_html_e( 'Analytics', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=pd-woo-churn-prediction&tab=settings' ) ); ?>"
		   class="nav-tab <?php echo $active_tab === 'settings' ? 'nav-tab-active' : ''; ?>">
			<span class="dashicons dashicons-admin-generic"></span>
			<?php esc_html_e( 'Settings', 'puredevs-customer-history-for-woocommerce' ); ?>
		</a>
	</nav>

	<div class="pdwchs-prediction-tab-content">

		<?php if ( $active_tab === 'at-risk' ) : ?>
		<!-- At-Risk Customers Tab -->
		<div class="pdwchs-prediction-panel" id="pdwchs-tab-at-risk">
			<div class="pdwchs-panel-header">
				<h2><?php esc_html_e( 'At-Risk Customers', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
				<div class="pdwchs-filter-controls">
					<select id="pdwchs-risk-filter" class="pdwchs-filter-select">
						<option value=""><?php esc_html_e( 'All Risk Levels', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="critical"><?php esc_html_e( 'Critical', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="high"><?php esc_html_e( 'High', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="medium"><?php esc_html_e( 'Medium', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					</select>
					<select id="pdwchs-sort-filter" class="pdwchs-filter-select">
						<option value="score_desc"><?php esc_html_e( 'Highest Risk First', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="score_asc"><?php esc_html_e( 'Lowest Risk First', 'puredevs-customer-history-for-woocommerce' ); ?></option>
						<option value="value_desc"><?php esc_html_e( 'Highest Value First', 'puredevs-customer-history-for-woocommerce' ); ?></option>
					</select>
				</div>
			</div>

			<?php if ( ! empty( $high_risk_customers ) ) : ?>
			<table class="wp-list-table widefat fixed striped pdwchs-at-risk-table">
				<thead>
					<tr>
						<th class="column-customer"><?php esc_html_e( 'Customer', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-email"><?php esc_html_e( 'Email', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-risk"><?php esc_html_e( 'Risk Score', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-level"><?php esc_html_e( 'Risk Level', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-indicator"><?php esc_html_e( 'Primary Indicator', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-value"><?php esc_html_e( 'Customer Value', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-lastorder"><?php esc_html_e( 'Last Order', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-actions"><?php esc_html_e( 'Actions', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody id="pdwchs-at-risk-tbody">
					<?php foreach ( $high_risk_customers as $customer ) :
						$customer_url = admin_url( 'admin.php?page=pd-woo-customer&user_id=' . $customer->customer_id );
						$email_url    = admin_url( 'admin.php?page=pd-woo-customer-email&user_id=' . $customer->customer_id );
						$indicator    = $churn_calculator ? $churn_calculator->pdwchs_get_indicator_label( $customer->primary_churn_indicator ) : $customer->primary_churn_indicator;
					?>
					<tr data-risk-level="<?php echo esc_attr( $customer->risk_level ); ?>" data-score="<?php echo esc_attr( $customer->churn_score ); ?>" data-value="<?php echo esc_attr( $customer->total_spent ); ?>">
						<td class="column-customer">
							<a href="<?php echo esc_url( $customer_url ); ?>">
								<strong><?php echo esc_html( $customer->display_name ?: __( 'Guest', 'puredevs-customer-history-for-woocommerce' ) ); ?></strong>
							</a>
						</td>
						<td class="column-email">
							<?php echo esc_html( $customer->user_email ); ?>
						</td>
						<td class="column-risk">
							<span class="pdwchs-score-display pdwchs-risk-<?php echo esc_attr( $customer->risk_level ); ?>">
								<?php echo esc_html( $customer->churn_score ); ?>%
							</span>
						</td>
						<td class="column-level">
							<span class="pdwchs-risk-badge pdwchs-risk-<?php echo esc_attr( $customer->risk_level ); ?>">
								<?php echo esc_html( ucfirst( $customer->risk_level ) ); ?>
							</span>
						</td>
						<td class="column-indicator">
							<?php echo esc_html( $indicator ); ?>
						</td>
						<td class="column-value">
							<?php echo wc_price( $customer->total_spent ); ?>
							<br><small><?php printf(
								/* translators: %d: Number of orders */
								esc_html__( '%d orders', 'puredevs-customer-history-for-woocommerce' ),
								$customer->total_orders
							); ?></small>
						</td>
						<td class="column-lastorder">
							<?php printf(
								/* translators: %d: Number of days */
								esc_html__( '%d days ago', 'puredevs-customer-history-for-woocommerce' ),
								$customer->days_since_last_order
							); ?>
						</td>
						<td class="column-actions">
							<a href="<?php echo esc_url( $customer_url ); ?>" class="button button-small" title="<?php esc_attr_e( 'View Profile', 'puredevs-customer-history-for-woocommerce' ); ?>">
								<span class="dashicons dashicons-visibility"></span>
							</a>
							<a href="<?php echo esc_url( $email_url ); ?>" class="button button-small" title="<?php esc_attr_e( 'Send Email', 'puredevs-customer-history-for-woocommerce' ); ?>">
								<span class="dashicons dashicons-email"></span>
							</a>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?>
			<div class="pdwchs-churn-no-data">
				<span class="dashicons dashicons-shield"></span>
				<p><?php esc_html_e( 'No at-risk customers detected. Your customers are healthy!', 'puredevs-customer-history-for-woocommerce' ); ?></p>
			</div>
			<?php endif; ?>
		</div>

		<?php elseif ( $active_tab === 'alerts' ) : ?>
		<!-- Alerts Tab -->
		<div class="pdwchs-prediction-panel" id="pdwchs-tab-alerts">
			<div class="pdwchs-panel-header">
				<h2><?php esc_html_e( 'Churn Alerts', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
				<div class="pdwchs-alert-summary">
					<span class="pdwchs-alert-stat">
						<strong><?php echo esc_html( isset( $alert_stats['new'] ) ? $alert_stats['new'] : 0 ); ?></strong>
						<?php esc_html_e( 'New', 'puredevs-customer-history-for-woocommerce' ); ?>
					</span>
					<span class="pdwchs-alert-stat">
						<strong><?php echo esc_html( isset( $alert_stats['in_progress'] ) ? $alert_stats['in_progress'] : 0 ); ?></strong>
						<?php esc_html_e( 'In Progress', 'puredevs-customer-history-for-woocommerce' ); ?>
					</span>
					<span class="pdwchs-alert-stat">
						<strong><?php echo esc_html( isset( $alert_stats['resolved'] ) ? $alert_stats['resolved'] : 0 ); ?></strong>
						<?php esc_html_e( 'Resolved', 'puredevs-customer-history-for-woocommerce' ); ?>
					</span>
				</div>
			</div>

			<?php if ( ! empty( $active_alerts ) ) : ?>
			<table class="wp-list-table widefat fixed striped pdwchs-alerts-table">
				<thead>
					<tr>
						<th class="column-priority"><?php esc_html_e( 'Priority', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-customer"><?php esc_html_e( 'Customer', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-alert"><?php esc_html_e( 'Alert', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-risk"><?php esc_html_e( 'Risk', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-value"><?php esc_html_e( 'Value', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-status"><?php esc_html_e( 'Status', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-assigned"><?php esc_html_e( 'Assigned To', 'puredevs-customer-history-for-woocommerce' ); ?></th>
						<th class="column-created"><?php esc_html_e( 'Created', 'puredevs-customer-history-for-woocommerce' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ( $active_alerts as $alert ) :
						$customer_url = admin_url( 'admin.php?page=pd-woo-customer&user_id=' . $alert->customer_id );
					?>
					<tr class="pdwchs-alert-row status-<?php echo esc_attr( $alert->status ); ?>">
						<td class="column-priority">
							<span class="pdwchs-priority-badge pdwchs-priority-<?php echo $alert->priority >= 8 ? 'urgent' : ( $alert->priority >= 5 ? 'high' : 'normal' ); ?>">
								P<?php echo esc_html( $alert->priority ); ?>
							</span>
						</td>
						<td class="column-customer">
							<a href="<?php echo esc_url( $customer_url ); ?>">
								<?php echo esc_html( $alert->display_name ); ?>
							</a>
							<br><small><?php echo esc_html( $alert->user_email ); ?></small>
						</td>
						<td class="column-alert">
							<strong><?php echo esc_html( $alert->title ); ?></strong>
						</td>
						<td class="column-risk">
							<span class="pdwchs-risk-badge pdwchs-risk-<?php echo esc_attr( $alert->risk_level ); ?>">
								<?php echo esc_html( $alert->churn_score ); ?>%
							</span>
						</td>
						<td class="column-value">
							<?php echo wc_price( $alert->customer_value ); ?>
						</td>
						<td class="column-status">
							<select class="pdwchs-alert-status-select" data-alert-id="<?php echo esc_attr( $alert->id ); ?>" data-original-value="<?php echo esc_attr( $alert->status ); ?>">
								<option value="new" <?php selected( $alert->status, 'new' ); ?>><?php esc_html_e( 'New', 'puredevs-customer-history-for-woocommerce' ); ?></option>
								<option value="viewed" <?php selected( $alert->status, 'viewed' ); ?>><?php esc_html_e( 'Viewed', 'puredevs-customer-history-for-woocommerce' ); ?></option>
								<option value="in_progress" <?php selected( $alert->status, 'in_progress' ); ?>><?php esc_html_e( 'In Progress', 'puredevs-customer-history-for-woocommerce' ); ?></option>
								<option value="resolved" <?php selected( $alert->status, 'resolved' ); ?>><?php esc_html_e( 'Resolved', 'puredevs-customer-history-for-woocommerce' ); ?></option>
								<option value="dismissed" <?php selected( $alert->status, 'dismissed' ); ?>><?php esc_html_e( 'Dismissed', 'puredevs-customer-history-for-woocommerce' ); ?></option>
							</select>
						</td>
						<td class="column-assigned">
							<select class="pdwchs-alert-assign-select" data-alert-id="<?php echo esc_attr( $alert->id ); ?>">
								<option value="0"><?php esc_html_e( 'Unassigned', 'puredevs-customer-history-for-woocommerce' ); ?></option>
								<?php foreach ( $admin_users as $admin ) : ?>
									<option value="<?php echo esc_attr( $admin->ID ); ?>" <?php selected( $alert->assigned_to, $admin->ID ); ?>>
										<?php echo esc_html( $admin->display_name ); ?>
									</option>
								<?php endforeach; ?>
							</select>
						</td>
						<td class="column-created">
							<?php echo esc_html( human_time_diff( strtotime( $alert->created_at ) ) ); ?>
							<?php esc_html_e( 'ago', 'puredevs-customer-history-for-woocommerce' ); ?>
						</td>
					</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
			<?php else : ?>
			<div class="pdwchs-churn-no-data">
				<span class="dashicons dashicons-bell"></span>
				<p><?php esc_html_e( 'No active alerts. Churn alerts will appear here when customers are at risk.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
			</div>
			<?php endif; ?>
		</div>

		<?php elseif ( $active_tab === 'analytics' ) : ?>
		<!-- Analytics Tab -->
		<div class="pdwchs-prediction-panel" id="pdwchs-tab-analytics">
			<div class="pdwchs-panel-header">
				<h2><?php esc_html_e( 'Churn Analytics', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
			</div>

			<div class="pdwchs-analytics-grid">
				<!-- Risk Distribution Chart -->
				<div class="pdwchs-analytics-card">
					<h3><?php esc_html_e( 'Risk Distribution', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
					<div class="pdwchs-chart-container">
						<canvas id="pdwchs-churn-distribution-chart"
								data-distribution="<?php echo esc_attr( wp_json_encode( $distribution ) ); ?>"
								height="250"></canvas>
					</div>
					<div class="pdwchs-chart-legend">
						<div class="pdwchs-legend-item">
							<span class="pdwchs-legend-dot" style="background: #22c55e;"></span>
							<?php printf( esc_html__( 'Low: %d', 'puredevs-customer-history-for-woocommerce' ), $distribution['low'] ); ?>
						</div>
						<div class="pdwchs-legend-item">
							<span class="pdwchs-legend-dot" style="background: #f59e0b;"></span>
							<?php printf( esc_html__( 'Medium: %d', 'puredevs-customer-history-for-woocommerce' ), $distribution['medium'] ); ?>
						</div>
						<div class="pdwchs-legend-item">
							<span class="pdwchs-legend-dot" style="background: #ef4444;"></span>
							<?php printf( esc_html__( 'High: %d', 'puredevs-customer-history-for-woocommerce' ), $distribution['high'] ); ?>
						</div>
						<div class="pdwchs-legend-item">
							<span class="pdwchs-legend-dot" style="background: #dc2626;"></span>
							<?php printf( esc_html__( 'Critical: %d', 'puredevs-customer-history-for-woocommerce' ), $distribution['critical'] ); ?>
						</div>
					</div>
				</div>

				<!-- Alert Statistics -->
				<div class="pdwchs-analytics-card">
					<h3><?php esc_html_e( 'Alert Statistics (30 Days)', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
					<div class="pdwchs-alert-analytics">
						<div class="pdwchs-analytics-stat">
							<span class="pdwchs-analytics-number"><?php echo esc_html( isset( $alert_stats['total'] ) ? $alert_stats['total'] : 0 ); ?></span>
							<span class="pdwchs-analytics-label"><?php esc_html_e( 'Total Alerts', 'puredevs-customer-history-for-woocommerce' ); ?></span>
						</div>
						<div class="pdwchs-analytics-stat">
							<span class="pdwchs-analytics-number"><?php echo esc_html( isset( $alert_stats['resolved'] ) ? $alert_stats['resolved'] : 0 ); ?></span>
							<span class="pdwchs-analytics-label"><?php esc_html_e( 'Resolved', 'puredevs-customer-history-for-woocommerce' ); ?></span>
						</div>
						<div class="pdwchs-analytics-stat">
							<span class="pdwchs-analytics-number">
								<?php
								$avg_hours = isset( $alert_stats['avg_resolution_time'] ) ? $alert_stats['avg_resolution_time'] : 0;
								if ( $avg_hours > 24 ) {
									printf( esc_html__( '%s days', 'puredevs-customer-history-for-woocommerce' ), number_format( $avg_hours / 24, 1 ) );
								} else {
									printf( esc_html__( '%s hrs', 'puredevs-customer-history-for-woocommerce' ), number_format( $avg_hours, 1 ) );
								}
								?>
							</span>
							<span class="pdwchs-analytics-label"><?php esc_html_e( 'Avg Resolution', 'puredevs-customer-history-for-woocommerce' ); ?></span>
						</div>
						<div class="pdwchs-analytics-stat">
							<?php
							$resolution_rate = 0;
							if ( isset( $alert_stats['total'] ) && $alert_stats['total'] > 0 ) {
								$resolved = isset( $alert_stats['resolved'] ) ? $alert_stats['resolved'] : 0;
								$resolution_rate = round( ( $resolved / $alert_stats['total'] ) * 100 );
							}
							?>
							<span class="pdwchs-analytics-number"><?php echo esc_html( $resolution_rate ); ?>%</span>
							<span class="pdwchs-analytics-label"><?php esc_html_e( 'Resolution Rate', 'puredevs-customer-history-for-woocommerce' ); ?></span>
						</div>
					</div>
				</div>

				<!-- Overall Summary -->
				<div class="pdwchs-analytics-card pdwchs-analytics-full-width">
					<h3><?php esc_html_e( 'Customer Risk Summary', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
					<div class="pdwchs-risk-bar-container">
						<?php
						$total = max( 1, $distribution['total'] );
						$segments = array(
							'low'      => array( 'label' => __( 'Low', 'puredevs-customer-history-for-woocommerce' ), 'color' => '#22c55e' ),
							'medium'   => array( 'label' => __( 'Medium', 'puredevs-customer-history-for-woocommerce' ), 'color' => '#f59e0b' ),
							'high'     => array( 'label' => __( 'High', 'puredevs-customer-history-for-woocommerce' ), 'color' => '#ef4444' ),
							'critical' => array( 'label' => __( 'Critical', 'puredevs-customer-history-for-woocommerce' ), 'color' => '#dc2626' ),
						);
						?>
						<div class="pdwchs-risk-stacked-bar">
							<?php foreach ( $segments as $key => $seg ) :
								$pct = round( ( $distribution[ $key ] / $total ) * 100, 1 );
								if ( $pct > 0 ) :
							?>
								<div class="pdwchs-bar-segment"
									 style="width: <?php echo esc_attr( $pct ); ?>%; background: <?php echo esc_attr( $seg['color'] ); ?>;"
									 title="<?php echo esc_attr( $seg['label'] . ': ' . $distribution[ $key ] . ' (' . $pct . '%)' ); ?>">
								</div>
							<?php endif; endforeach; ?>
						</div>
						<div class="pdwchs-risk-bar-labels">
							<?php foreach ( $segments as $key => $seg ) :
								$pct = round( ( $distribution[ $key ] / $total ) * 100, 1 );
							?>
								<span class="pdwchs-bar-label">
									<span class="pdwchs-legend-dot" style="background: <?php echo esc_attr( $seg['color'] ); ?>;"></span>
									<?php echo esc_html( $seg['label'] ); ?>: <?php echo esc_html( $pct ); ?>%
								</span>
							<?php endforeach; ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<?php elseif ( $active_tab === 'settings' ) : ?>
		<!-- Settings Tab -->
		<div class="pdwchs-prediction-panel" id="pdwchs-tab-settings">
			<div class="pdwchs-panel-header">
				<h2><?php esc_html_e( 'Churn Prediction Settings', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
			</div>

			<form id="pdwchs-churn-settings-form" class="pdwchs-settings-form">
				<?php wp_nonce_field( 'pdwchs_churn_nonce', 'pdwchs_churn_settings_nonce' ); ?>

				<!-- Risk Thresholds -->
				<div class="pdwchs-settings-section">
					<h3><?php esc_html_e( 'Risk Level Thresholds', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Define the score ranges for each risk level (0-100 scale).', 'puredevs-customer-history-for-woocommerce' ); ?></p>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="risk_threshold_low_max"><?php esc_html_e( 'Low Risk Maximum', 'puredevs-customer-history-for-woocommerce' ); ?></label>
							</th>
							<td>
								<input type="number" id="risk_threshold_low_max" name="risk_threshold_low_max"
									   value="<?php echo esc_attr( $settings['risk_threshold_low_max'] ); ?>"
									   min="0" max="100" class="small-text">
								<p class="description"><?php esc_html_e( 'Scores 0 to this value are Low Risk.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="risk_threshold_medium_max"><?php esc_html_e( 'Medium Risk Maximum', 'puredevs-customer-history-for-woocommerce' ); ?></label>
							</th>
							<td>
								<input type="number" id="risk_threshold_medium_max" name="risk_threshold_medium_max"
									   value="<?php echo esc_attr( $settings['risk_threshold_medium_max'] ); ?>"
									   min="0" max="100" class="small-text">
								<p class="description"><?php esc_html_e( 'Scores above Low to this value are Medium Risk.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="risk_threshold_high_max"><?php esc_html_e( 'High Risk Maximum', 'puredevs-customer-history-for-woocommerce' ); ?></label>
							</th>
							<td>
								<input type="number" id="risk_threshold_high_max" name="risk_threshold_high_max"
									   value="<?php echo esc_attr( $settings['risk_threshold_high_max'] ); ?>"
									   min="0" max="100" class="small-text">
								<p class="description"><?php esc_html_e( 'Scores above Medium to this value are High Risk. Above this is Critical.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Scoring Weights -->
				<div class="pdwchs-settings-section">
					<h3><?php esc_html_e( 'Scoring Weights', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Adjust the weight of each factor in churn score calculation. Weights must total 100.', 'puredevs-customer-history-for-woocommerce' ); ?></p>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="weight_recency"><?php esc_html_e( 'Recency Weight', 'puredevs-customer-history-for-woocommerce' ); ?></label>
							</th>
							<td>
								<input type="number" id="weight_recency" name="weight_recency"
									   value="<?php echo esc_attr( $settings['weight_recency'] ); ?>"
									   min="0" max="100" class="small-text pdwchs-weight-input">%
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="weight_frequency"><?php esc_html_e( 'Frequency Weight', 'puredevs-customer-history-for-woocommerce' ); ?></label>
							</th>
							<td>
								<input type="number" id="weight_frequency" name="weight_frequency"
									   value="<?php echo esc_attr( $settings['weight_frequency'] ); ?>"
									   min="0" max="100" class="small-text pdwchs-weight-input">%
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="weight_monetary"><?php esc_html_e( 'Monetary Weight', 'puredevs-customer-history-for-woocommerce' ); ?></label>
							</th>
							<td>
								<input type="number" id="weight_monetary" name="weight_monetary"
									   value="<?php echo esc_attr( $settings['weight_monetary'] ); ?>"
									   min="0" max="100" class="small-text pdwchs-weight-input">%
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="weight_engagement"><?php esc_html_e( 'Engagement Weight', 'puredevs-customer-history-for-woocommerce' ); ?></label>
							</th>
							<td>
								<input type="number" id="weight_engagement" name="weight_engagement"
									   value="<?php echo esc_attr( $settings['weight_engagement'] ); ?>"
									   min="0" max="100" class="small-text pdwchs-weight-input">%
								<p class="description pdwchs-weight-total">
									<?php esc_html_e( 'Total:', 'puredevs-customer-history-for-woocommerce' ); ?>
									<strong id="pdwchs-weight-total-value">100</strong>%
								</p>
							</td>
						</tr>
					</table>
				</div>

				<!-- Alert Settings -->
				<div class="pdwchs-settings-section">
					<h3><?php esc_html_e( 'Alert Settings', 'puredevs-customer-history-for-woocommerce' ); ?></h3>

					<table class="form-table">
						<tr>
							<th scope="row"><?php esc_html_e( 'Enable Alerts', 'puredevs-customer-history-for-woocommerce' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="alerts_enabled" value="1" <?php checked( $settings['alerts_enabled'] ); ?>>
									<?php esc_html_e( 'Enable churn alerts and email notifications', 'puredevs-customer-history-for-woocommerce' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row"><?php esc_html_e( 'Alert Triggers', 'puredevs-customer-history-for-woocommerce' ); ?></th>
							<td>
								<label>
									<input type="checkbox" name="alert_for_high_risk" value="1" <?php checked( $settings['alert_for_high_risk'] ); ?>>
									<?php esc_html_e( 'Alert for High Risk customers', 'puredevs-customer-history-for-woocommerce' ); ?>
								</label>
								<br>
								<label>
									<input type="checkbox" name="alert_for_critical_risk" value="1" <?php checked( $settings['alert_for_critical_risk'] ); ?>>
									<?php esc_html_e( 'Alert for Critical Risk customers', 'puredevs-customer-history-for-woocommerce' ); ?>
								</label>
							</td>
						</tr>
						<tr>
							<th scope="row">
								<label for="alert_email_recipient"><?php esc_html_e( 'Alert Email Recipient', 'puredevs-customer-history-for-woocommerce' ); ?></label>
							</th>
							<td>
								<input type="email" id="alert_email_recipient" name="alert_email_recipient"
									   value="<?php echo esc_attr( $settings['alert_email_recipient'] ); ?>"
									   class="regular-text"
									   placeholder="<?php echo esc_attr( get_option( 'admin_email' ) ); ?>">
								<p class="description"><?php esc_html_e( 'Leave empty to use the site admin email.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<!-- General Settings -->
				<div class="pdwchs-settings-section">
					<h3><?php esc_html_e( 'General Settings', 'puredevs-customer-history-for-woocommerce' ); ?></h3>

					<table class="form-table">
						<tr>
							<th scope="row">
								<label for="min_orders_for_prediction"><?php esc_html_e( 'Minimum Orders', 'puredevs-customer-history-for-woocommerce' ); ?></label>
							</th>
							<td>
								<input type="number" id="min_orders_for_prediction" name="min_orders_for_prediction"
									   value="<?php echo esc_attr( $settings['min_orders_for_prediction'] ); ?>"
									   min="1" max="10" class="small-text">
								<p class="description"><?php esc_html_e( 'Minimum number of orders required before churn prediction is calculated.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
							</td>
						</tr>
					</table>
				</div>

				<p class="submit">
					<button type="submit" class="button button-primary" id="pdwchs-save-churn-settings">
						<?php esc_html_e( 'Save Settings', 'puredevs-customer-history-for-woocommerce' ); ?>
					</button>
					<span class="spinner" id="pdwchs-settings-spinner"></span>
				</p>
			</form>
		</div>
		<?php endif; ?>

	</div>
</div>
