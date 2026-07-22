<?php
/**
 * Customer Profile: Lifecycle Stage Card
 *
 * Shows customer's current stage, history, and recommendations
 *
 * @since      2.1.0
 * @package    PD_Customer_History
 */

defined('ABSPATH') or exit;

$user_id = isset($user_id) ? intval($user_id) : 0;
if (!$user_id) return;

global $pd_customer_history;
$intelligence = null;
if ( $pd_customer_history && method_exists( $pd_customer_history, 'pdwchs_get_intelligence' ) ) {
	$intelligence = $pd_customer_history->pdwchs_get_intelligence();
}
if ( ! $intelligence ) return;

$lifecycle_calculator = $intelligence->pdwchs_get_lifecycle_calculator();
$lifecycle_data = $lifecycle_calculator->pdwchs_get_customer_lifecycle($user_id);

if (!$lifecycle_data || !isset($lifecycle_data['lifecycle_stage'])) {
    $lifecycle_data = $lifecycle_calculator->pdwchs_calculate_customer_lifecycle($user_id);
}

if (!$lifecycle_data) return;

$stage = $lifecycle_data['lifecycle_stage'] ?? 'Undefined';
$stage_config = $lifecycle_calculator->pdwchs_get_stage_config($stage);
$days_in_stage = $lifecycle_data['lifecycle_days_in_stage'] ?? 0;

// Get stage history
global $wpdb;
$history = $wpdb->get_results($wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}pdwchs_lifecycle_history
     WHERE user_id = %d
     ORDER BY entered_date ASC",
    $user_id
), ARRAY_A);
?>

<div class="pdwchs-lifecycle-card pdwchs-profile-card" style="background: #fff; border: 1px solid #ccd0d4; border-radius: 4px; padding: 20px; margin-bottom: 20px;">
    <div class="pdwchs-card-header" style="border-bottom: 1px solid #eee; padding-bottom: 15px; margin-bottom: 20px;">
        <h3 style="margin: 0; font-size: 16px;"><?php _e('Customer Lifecycle', 'puredevs-customer-history-for-woocommerce'); ?></h3>
    </div>

    <div class="pdwchs-card-body">
        <!-- Current Stage Badge -->
        <div class="pdwchs-current-stage" style="text-align: center; margin-bottom: 30px;">
            <div class="pdwchs-stage-badge-large" style="display: inline-flex; align-items: center; gap: 10px; padding: 15px 30px; border-radius: 24px; color: #fff; font-size: 18px; font-weight: 600; background-color: <?php echo esc_attr($stage_config['color'] ?? '#94a3b8'); ?>;">
                <span class="pdwchs-stage-icon" style="font-size: 24px;"><?php echo $stage_config['icon'] ?? '👤'; ?></span>
                <span class="pdwchs-stage-name"><?php echo esc_html($stage); ?></span>
            </div>
            <p class="pdwchs-stage-description" style="color: #666; margin-top: 15px;">
                <?php echo esc_html($stage_config['description'] ?? ''); ?>
            </p>
        </div>

        <!-- Time in Current Stage -->
        <div class="pdwchs-stage-duration" style="margin: 30px 0;">
            <h4 style="font-size: 14px; margin-bottom: 10px;"><?php _e('Time in Current Stage:', 'puredevs-customer-history-for-woocommerce'); ?></h4>
            <p class="pdwchs-days-count" style="font-size: 24px; font-weight: bold; color: #2271b1;">
                <?php echo esc_html($days_in_stage); ?> <?php _e('days', 'puredevs-customer-history-for-woocommerce'); ?>
            </p>
        </div>

        <!-- Customer Journey Timeline -->
        <?php if (!empty($history)): ?>
        <div class="pdwchs-journey-timeline" style="margin: 30px 0;">
            <h4 style="font-size: 14px; margin-bottom: 15px;"><?php _e('Customer Journey:', 'puredevs-customer-history-for-woocommerce'); ?></h4>
            <div class="pdwchs-timeline" style="position: relative; padding-left: 30px;">
                <?php foreach ($history as $history_item):
                    $is_current = is_null($history_item['exited_date']);
                    $timeline_config = $lifecycle_calculator->pdwchs_get_stage_config($history_item['stage']);
                ?>
                <div class="pdwchs-timeline-item" style="position: relative; padding-bottom: 20px;">
                    <div class="pdwchs-timeline-marker" style="position: absolute; left: -25px; top: 5px; width: <?php echo $is_current ? '16px' : '12px'; ?>; height: <?php echo $is_current ? '16px' : '12px'; ?>; border-radius: 50%; border: <?php echo $is_current ? '3px' : '2px'; ?> solid <?php echo $is_current ? esc_attr($timeline_config['color']) : '#e5e7eb'; ?>; background: <?php echo $is_current ? '#fff' : '#9ca3af'; ?>;"></div>
                    <div class="pdwchs-timeline-content">
                        <p style="font-weight: 600; font-size: 14px; margin-bottom: 4px; <?php echo $is_current ? '' : 'color: #6b7280;'; ?>">
                            <?php if ($is_current): ?>
                                <span style="color: <?php echo esc_attr($timeline_config['color']); ?>;">
                                    <?php echo esc_html($timeline_config['icon']); ?>
                                </span>
                            <?php endif; ?>
                            <?php echo esc_html($history_item['stage']); ?>
                            <?php echo $is_current ? ' (' . __('Current', 'puredevs-customer-history-for-woocommerce') . ')' : ''; ?>
                        </p>
                        <p style="font-size: 12px; color: #888; margin: 0;">
                            <?php
                            echo esc_html(date_i18n(get_option('date_format'), strtotime($history_item['entered_date'])));
                            if (!$is_current && $history_item['exited_date']) {
                                echo ' - ' . esc_html(date_i18n(get_option('date_format'), strtotime($history_item['exited_date'])));
                                echo ' (' . $history_item['days_in_stage'] . ' ' . __('days', 'puredevs-customer-history-for-woocommerce') . ')';
                            } else {
                                echo ' - ' . __('Present', 'puredevs-customer-history-for-woocommerce');
                                echo ' (' . $days_in_stage . ' ' . __('days', 'puredevs-customer-history-for-woocommerce') . ')';
                            }
                            ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recommended Actions -->
        <?php if (!empty($stage_config['recommendations'])): ?>
        <div class="pdwchs-recommendations" style="margin: 30px 0; background: #f0f9ff; border: 1px solid #bfdbfe; border-radius: 4px; padding: 15px;">
            <h4 style="font-size: 14px; margin: 0 0 10px 0; color: #1e40af;">💡 <?php _e('Recommended Actions:', 'puredevs-customer-history-for-woocommerce'); ?></h4>
            <ul style="margin: 0; padding-left: 20px;">
                <?php foreach ($stage_config['recommendations'] as $recommendation): ?>
                    <li style="font-size: 13px; color: #374151; margin-bottom: 5px;"><?php echo esc_html($recommendation); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <div class="pdwchs-card-footer" style="display: flex; justify-content: space-between; align-items: center; padding-top: 15px; border-top: 1px solid #eee; margin-top: 20px;">
        <p style="font-size: 12px; color: #888; margin: 0;">
            <?php
            if (isset($lifecycle_data['lifecycle_calculated_at'])) {
                printf(
                    __('Last calculated: %s', 'puredevs-customer-history-for-woocommerce'),
                    human_time_diff(strtotime($lifecycle_data['lifecycle_calculated_at']), current_time('timestamp')) . ' ' . __('ago', 'puredevs-customer-history-for-woocommerce')
                );
            }
            ?>
        </p>
        <button class="button pdwchs-recalculate-lifecycle" data-user-id="<?php echo esc_attr($user_id); ?>">
            <?php _e('Recalculate Now', 'puredevs-customer-history-for-woocommerce'); ?>
        </button>
    </div>
</div>
