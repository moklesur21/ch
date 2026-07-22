<?php
/**
 * Revenue Analytics Dashboard Widget Template
 *
 * Shell template for the revenue KPI cards, time-series chart,
 * and new-vs-returning split. Populated entirely by JS (R1-05).
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/admin/partials/templates
 * @since      2.2.0
 */

defined( 'ABSPATH' ) or exit;

if ( ! pchfwv_fs()->can_use_premium_code() ) {
    return;
}
?>

<div class="pd-woo-panel pdwchs-revenue-widget">

    <div class="pd-woo-panel-header pdwchs-revenue-header">
        <div class="pdwchs-revenue-title-row">
            <h2><?php echo esc_html__( 'Revenue Analytics', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
        </div>
        <div class="pdwchs-revenue-controls">
            <input type="text"
                   id="pdwchs-revenue-date-picker"
                   class="pdwchs-revenue-date-picker"
                   readonly
                   aria-label="<?php echo esc_attr__( 'Select date range', 'puredevs-customer-history-for-woocommerce' ); ?>" />
        </div>

        <?php include __DIR__ . '/data-integrity-indicator.php'; ?>

    </div>

    <div class="pd-woo-panel-body">

        <!-- Loading state -->
        <div class="pdwchs-revenue-loading">
            <span class="spinner is-active"></span>
            <p><?php echo esc_html__( 'Loading revenue data...', 'puredevs-customer-history-for-woocommerce' ); ?></p>
        </div>

        <!-- Main content (hidden until R1-05 populates) -->
        <div class="pdwchs-revenue-content" style="display: none;">

            <!-- KPI Cards -->
            <div class="pdwchs-revenue-cards-grid">

                <div class="pdwchs-revenue-card" data-metric="net_revenue">
                    <span class="pdwchs-revenue-card-label">
                        <?php echo esc_html__( 'Net Revenue', 'puredevs-customer-history-for-woocommerce' ); ?>
                    </span>
                    <span class="pdwchs-revenue-card-value">&mdash;</span>
                    <span class="pdwchs-revenue-card-change" aria-live="polite"></span>
                </div>

                <div class="pdwchs-revenue-card" data-metric="total_orders">
                    <span class="pdwchs-revenue-card-label">
                        <?php echo esc_html__( 'Total Orders', 'puredevs-customer-history-for-woocommerce' ); ?>
                    </span>
                    <span class="pdwchs-revenue-card-value">&mdash;</span>
                    <span class="pdwchs-revenue-card-change" aria-live="polite"></span>
                </div>

                <div class="pdwchs-revenue-card" data-metric="aov">
                    <span class="pdwchs-revenue-card-label">
                        <?php echo esc_html__( 'Avg. Order Value', 'puredevs-customer-history-for-woocommerce' ); ?>
                    </span>
                    <span class="pdwchs-revenue-card-value">&mdash;</span>
                    <span class="pdwchs-revenue-card-change" aria-live="polite"></span>
                </div>

                <div class="pdwchs-revenue-card" data-metric="total_customers">
                    <span class="pdwchs-revenue-card-label">
                        <?php echo esc_html__( 'Total Customers', 'puredevs-customer-history-for-woocommerce' ); ?>
                    </span>
                    <span class="pdwchs-revenue-card-value">&mdash;</span>
                    <span class="pdwchs-revenue-card-change" aria-live="polite"></span>
                </div>

            </div><!-- .pdwchs-revenue-cards-grid -->

            <!-- Revenue Over Time Chart -->
            <div class="pdwchs-revenue-chart-section">

                <div class="pdwchs-revenue-chart-header">
                    <h3 class="pdwchs-revenue-chart-title">
                        <?php echo esc_html__( 'Revenue Over Time', 'puredevs-customer-history-for-woocommerce' ); ?>
                    </h3>
                    <div class="pdwchs-granularity-toggle">
                        <button type="button"
                                class="pdwchs-granularity-btn pdwchs-granularity-btn--active"
                                data-granularity="daily">
                            <?php echo esc_html__( 'Daily', 'puredevs-customer-history-for-woocommerce' ); ?>
                        </button>
                        <button type="button"
                                class="pdwchs-granularity-btn"
                                data-granularity="weekly">
                            <?php echo esc_html__( 'Weekly', 'puredevs-customer-history-for-woocommerce' ); ?>
                        </button>
                        <button type="button"
                                class="pdwchs-granularity-btn"
                                data-granularity="monthly">
                            <?php echo esc_html__( 'Monthly', 'puredevs-customer-history-for-woocommerce' ); ?>
                        </button>
                    </div>
                </div>

                <div class="pdwchs-revenue-chart-wrap">
                    <canvas id="pdwchs-revenue-chart"
                            aria-label="<?php echo esc_attr__( 'Revenue over time chart', 'puredevs-customer-history-for-woocommerce' ); ?>"
                            role="img"></canvas>
                </div>

            </div><!-- .pdwchs-revenue-chart-section -->

            <!-- New vs Returning Split -->
            <div class="pdwchs-revenue-split-section">

                <h3 class="pdwchs-revenue-split-title">
                    <?php echo esc_html__( 'Revenue by Customer Type', 'puredevs-customer-history-for-woocommerce' ); ?>
                </h3>

                <div class="pdwchs-revenue-split-grid">

                    <div class="pdwchs-revenue-split-card pdwchs-revenue-split-new">
                        <span class="pdwchs-revenue-split-label">
                            <?php echo esc_html__( 'New Customers', 'puredevs-customer-history-for-woocommerce' ); ?>
                        </span>
                        <span class="pdwchs-revenue-split-value">&mdash;</span>
                        <span class="pdwchs-revenue-split-orders"></span>
                    </div>

                    <div class="pdwchs-revenue-split-card pdwchs-revenue-split-returning">
                        <span class="pdwchs-revenue-split-label">
                            <?php echo esc_html__( 'Returning Customers', 'puredevs-customer-history-for-woocommerce' ); ?>
                        </span>
                        <span class="pdwchs-revenue-split-value">&mdash;</span>
                        <span class="pdwchs-revenue-split-orders"></span>
                    </div>

                    <div class="pdwchs-revenue-split-card pdwchs-revenue-split-guest">
                        <span class="pdwchs-revenue-split-label">
                            <?php echo esc_html__( 'Guest Orders', 'puredevs-customer-history-for-woocommerce' ); ?>
                        </span>
                        <span class="pdwchs-revenue-split-value">&mdash;</span>
                        <span class="pdwchs-revenue-split-orders"></span>
                    </div>

                </div><!-- .pdwchs-revenue-split-grid -->

            </div><!-- .pdwchs-revenue-split-section -->

        </div><!-- .pdwchs-revenue-content -->

        <!-- Error state -->
        <div class="pdwchs-revenue-error" style="display: none;">
            <p class="error">
                <?php echo esc_html__( 'Failed to load revenue data. Please try again.', 'puredevs-customer-history-for-woocommerce' ); ?>
            </p>
        </div>

    </div><!-- .pd-woo-panel-body -->

</div><!-- .pdwchs-revenue-widget -->
