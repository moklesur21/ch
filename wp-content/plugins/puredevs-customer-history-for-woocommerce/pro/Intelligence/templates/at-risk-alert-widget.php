<?php
/**
 * Dashboard Widget: At Risk Alert
 *
 * Shows customers in At Risk stage for 30+ days
 *
 * @since      2.1.0
 * @package    PD_Customer_History
 */

defined('ABSPATH') or exit;
?>

<div id="pdwchs-at-risk-alert-widget" class="pdwchs-dashboard-widget">
    <div class="pdwchs-widget-header">
        <h3>
            <span class="dashicons dashicons-warning" style="color: #f97316;"></span>
            <?php _e('At Risk Customers (30+ Days)', 'puredevs-customer-history-for-woocommerce'); ?>
        </h3>
        <button class="pdwchs-refresh-btn" data-widget="at-risk-alert">
            <span class="dashicons dashicons-update"></span>
        </button>
    </div>

    <div class="pdwchs-widget-subtitle">
        <?php _e('Customers nearing churn - immediate action needed', 'puredevs-customer-history-for-woocommerce'); ?>
    </div>

    <div class="pdwchs-widget-content">
        <div class="pdwchs-at-risk-list">
            <!-- List will be rendered via JavaScript -->
            <div class="pdwchs-at-risk-loading">
                <span class="spinner is-active"></span>
                <p><?php _e('Loading at risk customers...', 'puredevs-customer-history-for-woocommerce'); ?></p>
            </div>
        </div>
    </div>

    <div class="pdwchs-widget-footer">
        <a href="<?php echo admin_url('admin.php?page=pd-woo-customers&lifecycle_stage=At+Risk'); ?>" class="button button-secondary">
            <?php _e('View All At Risk Customers', 'puredevs-customer-history-for-woocommerce'); ?>
        </a>
    </div>
</div>
