<?php
defined( 'ABSPATH' ) or exit;
if ( ! pchfwv_fs()->can_use_premium_code() ) {
    return;
}
?>

<!-- Data Integrity Badge (R1-32) -->
<span id="pdwchs-data-integrity-badge"
      class="pdwchs-data-integrity pdwchs-integrity-loading"
      title="<?php echo esc_attr__( 'Revenue data integrity check', 'puredevs-customer-history-for-woocommerce' ); ?>">
    <span class="pdwchs-integrity-dot"></span>
    <span class="pdwchs-integrity-label">
        <?php echo esc_html__( 'Verifying data…', 'puredevs-customer-history-for-woocommerce' ); ?>
    </span>
</span>

<!-- Data Integrity Details Panel (hidden until "View Details" is clicked) -->
<div id="pdwchs-data-integrity-details"
     class="pdwchs-integrity-details-panel"
     hidden
     aria-live="polite">

    <div class="pdwchs-integrity-details-loading" hidden>
        <span class="spinner is-active"></span>
    </div>

    <div class="pdwchs-integrity-details-content" hidden>

        <h4><?php echo esc_html__( 'Orders in unusual statuses', 'puredevs-customer-history-for-woocommerce' ); ?></h4>
        <table class="widefat striped pdwchs-integrity-table" id="pdwchs-integrity-unusual-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Order',  'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Status', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Total',  'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Date',   'puredevs-customer-history-for-woocommerce' ); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <h4><?php echo esc_html__( 'Partially refunded orders', 'puredevs-customer-history-for-woocommerce' ); ?></h4>
        <table class="widefat striped pdwchs-integrity-table" id="pdwchs-integrity-partial-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Order',    'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Total',    'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Refunded', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Date',     'puredevs-customer-history-for-woocommerce' ); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <h4><?php echo esc_html__( 'Currency mismatch', 'puredevs-customer-history-for-woocommerce' ); ?></h4>
        <table class="widefat striped pdwchs-integrity-table" id="pdwchs-integrity-currency-table">
            <thead>
                <tr>
                    <th><?php echo esc_html__( 'Order',    'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Currency', 'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Total',    'puredevs-customer-history-for-woocommerce' ); ?></th>
                    <th><?php echo esc_html__( 'Date',     'puredevs-customer-history-for-woocommerce' ); ?></th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>

        <p class="pdwchs-integrity-no-flagged" hidden>
            <?php echo esc_html__( 'No flagged orders for this range.', 'puredevs-customer-history-for-woocommerce' ); ?>
        </p>

    </div><!-- .pdwchs-integrity-details-content -->

</div><!-- #pdwchs-data-integrity-details -->
