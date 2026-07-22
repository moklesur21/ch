<?php
/**
 * Customer Behavioral Segmentation Card
 *
 * Displays behavioral tags, preferences, and next-best-action
 * on the customer details sidebar.
 *
 * @link       https://puredevs.com/
 * @since      3.0.0
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/admin/partials/templates
 */

defined( 'ABSPATH' ) or exit;

if ( ! pchfwv_fs()->can_use_premium_code() ) {
	return;
}

$behavioral   = new PD_Customer_History_Behavioral();
$insights     = new PD_Customer_History_Behavioral_Insights();
$tags         = $behavioral->get_customer_tags( $user_id );
$patterns     = $behavioral->get_customer_patterns( $user_id );
$next_action  = $insights->get_next_best_action( $user_id );
$behavior_types = $behavioral->get_behavior_types();
?>

<section class="pd-ch-panel pdwchs-behavioral-card">
	<div class="pdwchs-behavioral-card-header">
		<h2>
			<span class="dashicons dashicons-groups"></span>
			<?php echo esc_html__( 'Behavioral Profile', 'puredevs-customer-history-for-woocommerce' ); ?>
		</h2>
		<span class="pdwchs-behavioral-tag-count">
			<?php
			if ( ! empty( $tags ) ) {
				echo esc_html( sprintf(
					_n( '%d tag', '%d tags', count( $tags ), 'puredevs-customer-history-for-woocommerce' ),
					count( $tags )
				) );
			}
			?>
		</span>
	</div>

	<!-- Behavioral Tags -->
	<div class="pdwchs-behavioral-tags" id="pdwchs-behavioral-tags">
		<?php if ( ! empty( $tags ) ) : ?>
			<?php foreach ( $tags as $tag ) :
				$type_config = isset( $behavior_types[ $tag->behavior_type ] ) ? $behavior_types[ $tag->behavior_type ] : null;
				$insight     = $insights->get_behavior_insight( $tag );

				// Confidence-based coloring
				$confidence = intval( $tag->confidence_score );
				if ( $confidence >= 80 ) {
					$conf_class = 'pdwchs-confidence-high';
				} elseif ( $confidence >= 60 ) {
					$conf_class = 'pdwchs-confidence-medium';
				} else {
					$conf_class = 'pdwchs-confidence-low';
				}
			?>
				<div class="pdwchs-behavioral-tag <?php echo esc_attr( $conf_class ); ?>"
					title="<?php echo esc_attr( $insight ? $insight['description'] : '' ); ?>">
					<?php if ( $type_config ) : ?>
						<span class="dashicons <?php echo esc_attr( $type_config['icon'] ); ?> pdwchs-tag-icon"></span>
					<?php endif; ?>
					<span class="pdwchs-tag-label"><?php echo esc_html( $tag->behavior_label ); ?></span>
					<span class="pdwchs-tag-confidence"><?php echo intval( $confidence ); ?>%</span>
				</div>
			<?php endforeach; ?>
		<?php else : ?>
			<div class="pdwchs-no-tags">
				<span class="dashicons dashicons-info-outline"></span>
				<p><?php echo esc_html__( 'No behavioral patterns detected yet. Click Refresh Analysis below.', 'puredevs-customer-history-for-woocommerce' ); ?></p>
			</div>
		<?php endif; ?>
	</div>

	<!-- Next Best Action -->
	<?php if ( $next_action ) : ?>
	<div class="pdwchs-next-action">
		<h3>
			<span class="dashicons dashicons-lightbulb"></span>
			<?php echo esc_html__( 'Next Best Action', 'puredevs-customer-history-for-woocommerce' ); ?>
		</h3>
		<div class="pdwchs-next-action-content">
			<p class="pdwchs-action-text"><?php echo esc_html( $next_action['action'] ); ?></p>
		</div>
	</div>
	<?php endif; ?>

	<!-- Shopping Preferences -->
	<?php if ( $patterns ) : ?>
	<div class="pdwchs-preferences">
		<h3>
			<span class="dashicons dashicons-chart-pie"></span>
			<?php echo esc_html__( 'Shopping Preferences', 'puredevs-customer-history-for-woocommerce' ); ?>
		</h3>

		<?php
		$categories   = json_decode( $patterns->preferred_categories, true );
		$price_range  = json_decode( $patterns->preferred_price_range, true );
		$shop_times   = json_decode( $patterns->preferred_shopping_times, true );
		?>

		<?php if ( ! empty( $categories ) ) : ?>
		<div class="pdwchs-pref-row">
			<span class="pdwchs-pref-label"><?php echo esc_html__( 'Top Categories:', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<span class="pdwchs-pref-value"><?php echo esc_html( implode( ', ', array_keys( array_slice( $categories, 0, 3, true ) ) ) ); ?></span>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $price_range ) && $price_range['avg'] > 0 ) : ?>
		<div class="pdwchs-pref-row">
			<span class="pdwchs-pref-label"><?php echo esc_html__( 'Avg. Price:', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<span class="pdwchs-pref-value"><?php echo wp_kses_post( wc_price( $price_range['avg'] ) ); ?></span>
		</div>
		<div class="pdwchs-pref-row">
			<span class="pdwchs-pref-label"><?php echo esc_html__( 'Price Range:', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<span class="pdwchs-pref-value">
				<?php echo wp_kses_post( wc_price( $price_range['min'] ) . ' - ' . wc_price( $price_range['max'] ) ); ?>
			</span>
		</div>
		<?php endif; ?>

		<?php if ( ! empty( $shop_times ) ) : ?>
		<div class="pdwchs-pref-row">
			<span class="pdwchs-pref-label"><?php echo esc_html__( 'Peak Day:', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<span class="pdwchs-pref-value"><?php echo esc_html( isset( $shop_times['peak_day'] ) ? $shop_times['peak_day'] : 'N/A' ); ?></span>
		</div>
		<div class="pdwchs-pref-row">
			<span class="pdwchs-pref-label"><?php echo esc_html__( 'Peak Hour:', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<span class="pdwchs-pref-value"><?php echo esc_html( isset( $shop_times['peak_hour'] ) ? $shop_times['peak_hour'] . ':00' : 'N/A' ); ?></span>
		</div>
		<?php endif; ?>

		<?php if ( $patterns->avg_items_per_order ) : ?>
		<div class="pdwchs-pref-row">
			<span class="pdwchs-pref-label"><?php echo esc_html__( 'Avg. Items/Order:', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<span class="pdwchs-pref-value"><?php echo esc_html( number_format( $patterns->avg_items_per_order, 1 ) ); ?></span>
		</div>
		<?php endif; ?>

		<?php if ( $patterns->coupon_usage_rate !== null ) : ?>
		<div class="pdwchs-pref-row">
			<span class="pdwchs-pref-label"><?php echo esc_html__( 'Coupon Usage:', 'puredevs-customer-history-for-woocommerce' ); ?></span>
			<span class="pdwchs-pref-value"><?php echo esc_html( number_format( $patterns->coupon_usage_rate, 0 ) . '%' ); ?></span>
		</div>
		<?php endif; ?>
	</div>
	<?php endif; ?>

	<!-- Footer -->
	<div class="pdwchs-behavioral-footer">
		<div class="pdwchs-behavioral-timestamp">
			<?php
			if ( $patterns && $patterns->last_analyzed ) {
				$time_ago = human_time_diff( strtotime( $patterns->last_analyzed ), current_time( 'timestamp' ) );
				echo esc_html( sprintf( __( 'Last analyzed: %s ago', 'puredevs-customer-history-for-woocommerce' ), $time_ago ) );
			} else {
				echo esc_html__( 'Not analyzed yet', 'puredevs-customer-history-for-woocommerce' );
			}
			?>
		</div>
		<button type="button" class="button pdwchs-refresh-behavioral-btn" data-user-id="<?php echo intval( $user_id ); ?>">
			<span class="dashicons dashicons-update"></span>
			<?php echo esc_html__( 'Refresh Analysis', 'puredevs-customer-history-for-woocommerce' ); ?>
		</button>
	</div>
</section>

<script type="text/javascript">
var pdwchs_behavioral_nonce = '<?php echo wp_create_nonce( 'pdwchs_behavioral_nonce' ); ?>';
</script>
