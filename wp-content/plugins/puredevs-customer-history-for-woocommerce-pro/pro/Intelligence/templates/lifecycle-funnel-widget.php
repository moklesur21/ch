<?php
/**
 * Dashboard Widget: Customer Lifecycle Funnel
 *
 * Shows horizontal funnel with stage progression and conversion rates
 *
 * @since      2.1.0
 * @package    PD_Customer_History
 */

defined('ABSPATH') or exit;
?>

<div id="pdwchs-lifecycle-funnel-widget" class="pdwchs-dashboard-widget">
    <div class="pdwchs-widget-header">
        <h3><?php _e('Customer Lifecycle Funnel', 'puredevs-customer-history-for-woocommerce'); ?></h3>
        <button class="pdwchs-refresh-btn" data-widget="lifecycle-funnel">
            <span class="dashicons dashicons-update"></span>
        </button>
    </div>

    <div class="pdwchs-widget-content">
        <div class="pdwchs-funnel-container">
            <!-- Funnel stages will be rendered via JavaScript -->
            <div class="pdwchs-funnel-loading">
                <span class="spinner is-active"></span>
                <p><?php _e('Loading lifecycle funnel...', 'puredevs-customer-history-for-woocommerce'); ?></p>
            </div>
        </div>

        <div class="pdwchs-funnel-sidebar">
            <div class="pdwchs-engagement-issues">
                <h4><?php _e('Engagement Issues', 'puredevs-customer-history-for-woocommerce'); ?></h4>
                <div class="pdwchs-issue-stat" data-stage="At Risk">
                    <span class="pdwchs-issue-icon">⚠️</span>
                    <span class="pdwchs-issue-label"><?php _e('At Risk:', 'puredevs-customer-history-for-woocommerce'); ?></span>
                    <span class="pdwchs-issue-count">--</span>
                </div>
                <div class="pdwchs-issue-stat" data-stage="Churned">
                    <span class="pdwchs-issue-icon">💔</span>
                    <span class="pdwchs-issue-label"><?php _e('Churned:', 'puredevs-customer-history-for-woocommerce'); ?></span>
                    <span class="pdwchs-issue-count">--</span>
                </div>
            </div>
        </div>
    </div>

    <div class="pdwchs-widget-footer">
        <p class="pdwchs-total-customers">
            <?php _e('Total:', 'puredevs-customer-history-for-woocommerce'); ?> <span class="pdwchs-customer-count">--</span> <?php _e('customers tracked', 'puredevs-customer-history-for-woocommerce'); ?>
        </p>
    </div>
</div>
