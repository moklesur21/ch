<?php
/**
 * Customer Churn Prediction Card
 *
 * Displays churn risk information on customer detail page.
 *
 * @link       https://puredevs.com/
 * @since      4.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/admin/partials/templates
 */

defined( 'ABSPATH' ) or exit;

// Ensure user_id is set
if ( ! isset( $user_id ) || ! $user_id ) {
	return;
}

// Get churn calculator instance
$intelligence = null;
if ( function_exists( 'pdwchs_get_plugin_instance' ) ) {
	$plugin = pdwchs_get_plugin_instance();
	if ( $plugin && method_exists( $plugin, 'pdwchs_get_intelligence' ) ) {
		$intelligence = $plugin->pdwchs_get_intelligence();
	}
}

$churn_calculator = null;
if ( $intelligence && method_exists( $intelligence, 'pdwchs_get_churn_calculator' ) ) {
	$churn_calculator = $intelligence->pdwchs_get_churn_calculator();
}

$churn_predictor = null;
if ( $intelligence && method_exists( $intelligence, 'pdwchs_get_churn_predictor' ) ) {
	$churn_predictor = $intelligence->pdwchs_get_churn_predictor();
}

if ( ! $churn_calculator ) {
	return;
}

// Get or calculate churn data
$churn_data = $churn_calculator->pdwchs_get_customer_churn_data( $user_id );

if ( ! $churn_data ) {
	// Calculate if not exists
	$churn_data = $churn_calculator->pdwchs_calculate_churn_score( $user_id );
	if ( is_array( $churn_data ) ) {
		$churn_data = (object) $churn_data;
	}
}

// Get trend data from history
$trend_data = null;
if ( $churn_data && $churn_predictor ) {
	$trend_data = $churn_predictor->analyze_churn_trends( $user_id, 90 );
}

if ( ! $churn_data || ( isset( $churn_data->has_sufficient_data ) && ! $churn_data->has_sufficient_data ) ) {
	// Not enough data
	?>
	<div class="pdwchs-churn-card pdwchs-churn-insufficient">
		<div class="pdwchs-churn-card-header">
			<h3><?php esc_html_e( 'Churn Risk', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
		</div>
		<div class="pdwchs-churn-card-body">
			<div class="pdwchs-no-data-message">
				<span class="dashicons dashicons-info-outline"></span>
				<p><?php esc_html_e( 'Insufficient order history for churn prediction. Minimum 2 orders required.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
			</div>
		</div>
	</div>
	<?php
	return;
}

// Get risk level info
$risk_info = isset( $churn_data->risk_level_info )
	? $churn_data->risk_level_info
	: $churn_calculator->pdwchs_get_risk_level_info( $churn_data->risk_level );

$churn_score = isset( $churn_data->churn_score ) ? intval( $churn_data->churn_score ) : 0;
$risk_level = isset( $churn_data->risk_level ) ? $churn_data->risk_level : 'low';

// Calculate SVG parameters for circular progress
$radius = 45;
$circumference = 2 * M_PI * $radius;
$score_offset = $circumference - ( $churn_score / 100 ) * $circumference;

// Determine score color based on risk
$score_color = $risk_info['color'];
?>

<div class="pdwchs-churn-card pdwchs-churn-<?php echo esc_attr( $risk_level ); ?>">
	<div class="pdwchs-churn-card-header">
		<h3><?php esc_html_e( 'Churn Risk Analysis', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
		<button type="button" class="pdwchs-recalculate-churn" data-user-id="<?php echo esc_attr( $user_id ); ?>" title="<?php esc_attr_e( 'Recalculate', 'puredevs-customer-history-for-woocommerce' ); ?>">
			<span class="dashicons dashicons-update"></span>
		</button>
	</div>

	<div class="pdwchs-churn-card-body">
		<!-- Risk Score Visualization -->
		<div class="pdwchs-churn-score-section">
			<div class="pdwchs-churn-score-circle">
				<svg viewBox="0 0 100 100" class="pdwchs-score-svg">
					<!-- Background circle -->
					<circle
						cx="50"
						cy="50"
						r="<?php echo esc_attr( $radius ); ?>"
						fill="none"
						stroke="#e5e7eb"
						stroke-width="8"
					/>
					<!-- Score circle -->
					<circle
						cx="50"
						cy="50"
						r="<?php echo esc_attr( $radius ); ?>"
						fill="none"
						stroke="<?php echo esc_attr( $score_color ); ?>"
						stroke-width="8"
						stroke-linecap="round"
						stroke-dasharray="<?php echo esc_attr( $circumference ); ?>"
						stroke-dashoffset="<?php echo esc_attr( $score_offset ); ?>"
						transform="rotate(-90 50 50)"
					/>
					<!-- Score text -->
					<text x="50" y="45" text-anchor="middle" class="pdwchs-score-number" fill="<?php echo esc_attr( $score_color ); ?>"><?php echo esc_html( $churn_score ); ?></text>
					<text x="50" y="62" text-anchor="middle" class="pdwchs-score-label"><?php esc_html_e( 'Risk Score', 'puredevs-customer-history-for-woocommerce' ); ?></text>
				</svg>
			</div>

			<div class="pdwchs-risk-info">
				<span class="pdwchs-risk-badge pdwchs-risk-<?php echo esc_attr( $risk_level ); ?>" style="background-color: <?php echo esc_attr( $risk_info['bg_color'] ); ?>; color: <?php echo esc_attr( $risk_info['color'] ); ?>;">
					<?php echo esc_html( $risk_info['label'] ); ?>
				</span>
				<p class="pdwchs-risk-description"><?php echo esc_html( $risk_info['description'] ); ?></p>

				<?php if ( isset( $churn_data->prediction_confidence ) ) : ?>
				<div class="pdwchs-confidence">
					<span class="pdwchs-confidence-label"><?php esc_html_e( 'Confidence:', 'puredevs-customer-history-for-woocommerce' ); ?></span>
					<span class="pdwchs-confidence-value"><?php echo esc_html( $churn_data->prediction_confidence ); ?>%</span>
				</div>
				<?php endif; ?>

				<?php if ( $trend_data && isset( $trend_data['trend'] ) ) :
					$trend         = $trend_data['trend'];
					$trend_configs = array(
						'worsening' => array( 'icon' => 'arrow-up-alt',   'label' => __( 'Worsening', 'puredevs-customer-history-for-woocommerce' ), 'class' => 'pdwchs-trend-worsening' ),
						'improving' => array( 'icon' => 'arrow-down-alt', 'label' => __( 'Improving', 'puredevs-customer-history-for-woocommerce' ), 'class' => 'pdwchs-trend-improving' ),
						'stable'    => array( 'icon' => 'minus',          'label' => __( 'Stable',    'puredevs-customer-history-for-woocommerce' ), 'class' => 'pdwchs-trend-stable' ),
					);
					$current_trend = isset( $trend_configs[ $trend ] ) ? $trend_configs[ $trend ] : $trend_configs['stable'];
				?>
				<div class="pdwchs-trend <?php echo esc_attr( $current_trend['class'] ); ?>">
					<span class="pdwchs-trend-label"><?php esc_html_e( 'Trend:', 'puredevs-customer-history-for-woocommerce' ); ?></span>
					<span class="pdwchs-trend-value">
						<span class="dashicons dashicons-<?php echo esc_attr( $current_trend['icon'] ); ?>"></span>
						<?php echo esc_html( $current_trend['label'] ); ?>
					</span>
				</div>
				<?php endif; ?>
			</div>
		</div>

		<!-- Component Scores -->
		<div class="pdwchs-churn-components">
			<h4><?php esc_html_e( 'Risk Components', 'puredevs-customer-history-for-woocommerce' ); ?></h4>

			<div class="pdwchs-component-bar">
				<span class="pdwchs-component-label"><?php esc_html_e( 'Recency', 'puredevs-customer-history-for-woocommerce' ); ?></span>
				<div class="pdwchs-bar-container">
					<div class="pdwchs-bar-fill" style="width: <?php echo esc_attr( $churn_data->recency_score ?? 0 ); ?>%; background-color: <?php echo esc_attr( pdwchs_get_component_color( $churn_data->recency_score ?? 0 ) ); ?>;"></div>
				</div>
				<span class="pdwchs-component-value"><?php echo esc_html( $churn_data->recency_score ?? 0 ); ?></span>
			</div>

			<div class="pdwchs-component-bar">
				<span class="pdwchs-component-label"><?php esc_html_e( 'Frequency', 'puredevs-customer-history-for-woocommerce' ); ?></span>
				<div class="pdwchs-bar-container">
					<div class="pdwchs-bar-fill" style="width: <?php echo esc_attr( $churn_data->frequency_score ?? 0 ); ?>%; background-color: <?php echo esc_attr( pdwchs_get_component_color( $churn_data->frequency_score ?? 0 ) ); ?>;"></div>
				</div>
				<span class="pdwchs-component-value"><?php echo esc_html( $churn_data->frequency_score ?? 0 ); ?></span>
			</div>

			<div class="pdwchs-component-bar">
				<span class="pdwchs-component-label"><?php esc_html_e( 'Monetary', 'puredevs-customer-history-for-woocommerce' ); ?></span>
				<div class="pdwchs-bar-container">
					<div class="pdwchs-bar-fill" style="width: <?php echo esc_attr( $churn_data->monetary_score ?? 0 ); ?>%; background-color: <?php echo esc_attr( pdwchs_get_component_color( $churn_data->monetary_score ?? 0 ) ); ?>;"></div>
				</div>
				<span class="pdwchs-component-value"><?php echo esc_html( $churn_data->monetary_score ?? 0 ); ?></span>
			</div>

			<div class="pdwchs-component-bar">
				<span class="pdwchs-component-label"><?php esc_html_e( 'Engagement', 'puredevs-customer-history-for-woocommerce' ); ?></span>
				<div class="pdwchs-bar-container">
					<div class="pdwchs-bar-fill" style="width: <?php echo esc_attr( $churn_data->engagement_score ?? 0 ); ?>%; background-color: <?php echo esc_attr( pdwchs_get_component_color( $churn_data->engagement_score ?? 0 ) ); ?>;"></div>
				</div>
				<span class="pdwchs-component-value"><?php echo esc_html( $churn_data->engagement_score ?? 0 ); ?></span>
			</div>
		</div>

		<?php
		// Predicted next-order date. Derivation and its guards live on the churn
		// calculator so the 1.5 order-screen panel can reuse them.
		$pdwchs_predicted_ts = method_exists( $churn_calculator, 'pdwchs_get_predicted_next_order_ts' )
			? $churn_calculator->pdwchs_get_predicted_next_order_ts( $churn_data )
			: null;
		?>

		<!-- Key Indicators -->
		<div class="pdwchs-churn-indicators">
			<h4><?php esc_html_e( 'Key Indicators', 'puredevs-customer-history-for-woocommerce' ); ?></h4>
			<ul class="pdwchs-indicators-list">
				<?php if ( isset( $churn_data->days_since_last_order ) ) : ?>
				<li>
					<span class="pdwchs-indicator-icon dashicons dashicons-calendar-alt"></span>
					<span class="pdwchs-indicator-text">
						<?php
						printf(
							/* translators: %d: Days since last order */
							esc_html__( '%d days since last order', 'puredevs-customer-history-for-woocommerce' ),
							intval( $churn_data->days_since_last_order )
						);
						?>
					</span>
				</li>
				<?php endif; ?>

				<?php if ( isset( $churn_data->days_overdue ) && $churn_data->days_overdue > 0 ) : ?>
				<li class="pdwchs-indicator-warning">
					<span class="pdwchs-indicator-icon dashicons dashicons-warning"></span>
					<span class="pdwchs-indicator-text">
						<?php
						printf(
							/* translators: %d: Days overdue */
							esc_html__( '%d days overdue for purchase', 'puredevs-customer-history-for-woocommerce' ),
							intval( $churn_data->days_overdue )
						);
						?>
					</span>
				</li>
				<?php endif; ?>

				<?php
				if ( $pdwchs_predicted_ts ) :
					$pdwchs_predicted_overdue = ( $pdwchs_predicted_ts < time() );
					?>
				<li class="<?php echo esc_attr( $pdwchs_predicted_overdue ? 'pdwchs-indicator-warning' : '' ); ?>">
					<span class="pdwchs-indicator-icon dashicons dashicons-clock"></span>
					<span class="pdwchs-indicator-text">
						<?php
						printf(
							/* translators: %s: Predicted date of this customer's next order */
							esc_html__( 'Predicted next order: ~ %s', 'puredevs-customer-history-for-woocommerce' ),
							esc_html( wp_date( get_option( 'date_format' ), $pdwchs_predicted_ts ) )
						);
						?>
					</span>
				</li>
				<?php endif; ?>

				<?php if ( isset( $churn_data->frequency_decline_pct ) && $churn_data->frequency_decline_pct > 0 ) : ?>
				<li class="pdwchs-indicator-warning">
					<span class="pdwchs-indicator-icon dashicons dashicons-chart-line"></span>
					<span class="pdwchs-indicator-text">
						<?php
						printf(
							/* translators: %s: Decline percentage */
							esc_html__( 'Purchase frequency down %s%%', 'puredevs-customer-history-for-woocommerce' ),
							number_format( $churn_data->frequency_decline_pct, 0 )
						);
						?>
					</span>
				</li>
				<?php endif; ?>

				<?php if ( isset( $churn_data->spending_decline_pct ) && $churn_data->spending_decline_pct > 0 ) : ?>
				<li class="pdwchs-indicator-warning">
					<span class="pdwchs-indicator-icon dashicons dashicons-money-alt"></span>
					<span class="pdwchs-indicator-text">
						<?php
						printf(
							/* translators: %s: Decline percentage */
							esc_html__( 'Spending down %s%%', 'puredevs-customer-history-for-woocommerce' ),
							number_format( $churn_data->spending_decline_pct, 0 )
						);
						?>
					</span>
				</li>
				<?php endif; ?>

				<?php if ( isset( $churn_data->sessions_last_30_days ) && $churn_data->sessions_last_30_days == 0 && ( $churn_data->total_sessions ?? 0 ) > 0 ) : ?>
				<li class="pdwchs-indicator-warning">
					<span class="pdwchs-indicator-icon dashicons dashicons-visibility"></span>
					<span class="pdwchs-indicator-text">
						<?php esc_html_e( 'No site visits in 30 days', 'puredevs-customer-history-for-woocommerce' ); ?>
					</span>
				</li>
				<?php endif; ?>

				<?php if ( isset( $churn_data->primary_churn_indicator ) ) : ?>
				<li>
					<span class="pdwchs-indicator-icon dashicons dashicons-flag"></span>
					<span class="pdwchs-indicator-text">
						<?php
						printf(
							/* translators: %s: Primary indicator */
							esc_html__( 'Primary concern: %s', 'puredevs-customer-history-for-woocommerce' ),
							esc_html( $churn_calculator->pdwchs_get_indicator_label( $churn_data->primary_churn_indicator ) )
						);
						?>
					</span>
				</li>
				<?php endif; ?>
			</ul>
		</div>

		<?php if ( $risk_level === 'high' || $risk_level === 'critical' ) : ?>
		<!-- Follow Up -->
		<div class="pdwchs-churn-actions">
			<h4><?php esc_html_e( 'Follow Up', 'puredevs-customer-history-for-woocommerce' ); ?></h4>
			<div class="pdwchs-action-buttons">
				<?php
				$email_url = admin_url( 'admin.php?page=pd-woo-customer-email&user_id=' . $user_id );
				?>
				<a href="<?php echo esc_url( $email_url ); ?>" class="button pdwchs-retention-btn">
					<span class="dashicons dashicons-email"></span>
					<?php esc_html_e( 'Email this customer', 'puredevs-customer-history-for-woocommerce' ); ?>
				</a>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( isset( $churn_data->last_calculated ) ) : ?>
		<div class="pdwchs-churn-footer">
			<small>
				<?php
				printf(
					/* translators: %s: Time since calculation */
					esc_html__( 'Last calculated: %s ago', 'puredevs-customer-history-for-woocommerce' ),
					human_time_diff( strtotime( $churn_data->last_calculated ) )
				);
				?>
			</small>
		</div>
		<?php endif; ?>
	</div>
</div>

<?php
/**
 * Helper function to get component color based on score
 *
 * @param int $score Component score 0-100
 * @return string Color hex code
 */
function pdwchs_get_component_color( $score ) {
	if ( $score <= 30 ) {
		return '#22c55e'; // Green
	} elseif ( $score <= 60 ) {
		return '#f59e0b'; // Amber
	} elseif ( $score <= 80 ) {
		return '#ef4444'; // Red
	} else {
		return '#dc2626'; // Dark red
	}
}
?>
