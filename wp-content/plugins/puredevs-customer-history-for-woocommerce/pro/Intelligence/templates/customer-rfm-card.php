<?php

defined( 'ABSPATH' ) || exit;
                        // Get RFM data for this customer
                        $rfm_calculator = new PD_Customer_History_RFM();
                        $rfm_data = $rfm_calculator->pdwchs_get_customer_rfm( $user_id );

                        // If no RFM data, calculate it now
                        if ( ! $rfm_data ) {
                            $rfm_data = $rfm_calculator->pdwchs_calculate_customer_rfm( $user_id );
                        }

                        $segment_slug = isset( $rfm_data['rfm_segment'] ) ? $rfm_data['rfm_segment'] : null;
                        $segment_config = $segment_slug ? PD_Customer_History_Segments::pdwchs_get_segment( $segment_slug ) : null;
                    ?>
                    <section class="pd-ch-panel pdwchs-rfm-analysis-card">
                        <div class="pdwchs-rfm-card-header">
                            <h2><?php echo esc_html__( 'RFM Analysis', 'puredevs-customer-history-for-woocommerce' ); ?></h2>
                            <?php if ( $segment_config ) : ?>
                                <?php echo wp_kses_post( PD_Customer_History_Segments::pdwchs_get_segment_badge( $segment_slug, 'medium' ) ); ?>
                            <?php else : ?>
                                <span class="pdwchs-badge pdwchs-badge-undefined pdwchs-badge-medium">
                                    <?php echo esc_html__( 'No Data', 'puredevs-customer-history-for-woocommerce' ); ?>
                                </span>
                            <?php endif; ?>
                        </div>

                        <div class="pdwchs-rfm-scores">
                            <?php
                            $recency_score = isset( $rfm_data['rfm_recency'] ) ? intval( $rfm_data['rfm_recency'] ) : 0;
                            $frequency_score = isset( $rfm_data['rfm_frequency'] ) ? intval( $rfm_data['rfm_frequency'] ) : 0;
                            $monetary_score = isset( $rfm_data['rfm_monetary'] ) ? intval( $rfm_data['rfm_monetary'] ) : 0;

                            // Get description texts
                            $recency_text = '';
                            $frequency_text = '';
                            $monetary_text = '';

                            if ( $rfm_data ) {
                                // Calculate days since last order for recency description
                                $orders = wc_get_orders( array(
                                    'customer_id' => $user_id,
                                    'status'      => 'completed',
                                    'limit'       => 1,
                                    'orderby'     => 'date',
                                    'order'       => 'DESC',
                                ) );

                                $days_since = null;
                                if ( ! empty( $orders ) ) {
                                    $last_order_date = $orders[0]->get_date_created();
                                    $days_since = floor( ( current_time( 'timestamp' ) - $last_order_date->getTimestamp() ) / DAY_IN_SECONDS );
                                }

                                $total_orders = wc_get_customer_order_count( $user_id );
                                $total_spent = wc_get_customer_total_spent( $user_id );
                                $store_average = $rfm_calculator->pdwchs_get_store_average_spend();
                                $vs_average = $store_average > 0 ? ( $total_spent / $store_average ) : 0;

                                $recency_text = $rfm_calculator->pdwchs_get_recency_description( $recency_score, $days_since );
                                $frequency_text = $rfm_calculator->pdwchs_get_frequency_description( $frequency_score, $total_orders );
                                $monetary_text = $rfm_calculator->pdwchs_get_monetary_description( $monetary_score, $total_spent, $vs_average );
                            }
                            ?>

                            <!-- Recency Score -->
                            <div class="pdwchs-score-row">
                                <div class="pdwchs-score-label">
                                    <strong>R</strong> <?php echo esc_html__( 'Recency', 'puredevs-customer-history-for-woocommerce' ); ?>
                                    <span class="pdwchs-score-value"><?php echo intval( $recency_score ); ?>/5</span>
                                </div>
                                <div class="pdwchs-progress-bar">
                                    <div class="pdwchs-progress-fill" data-score="<?php echo intval( $recency_score ); ?>" style="width: <?php echo esc_attr( ( $recency_score / 5 ) * 100 ); ?>%; background-color: <?php echo $segment_config ? esc_attr( $segment_config['color'] ) : '#94a3b8'; ?>;"></div>
                                </div>
                                <div class="pdwchs-score-description"><?php echo esc_html( $recency_text ); ?></div>
                            </div>

                            <!-- Frequency Score -->
                            <div class="pdwchs-score-row">
                                <div class="pdwchs-score-label">
                                    <strong>F</strong> <?php echo esc_html__( 'Frequency', 'puredevs-customer-history-for-woocommerce' ); ?>
                                    <span class="pdwchs-score-value"><?php echo intval( $frequency_score ); ?>/5</span>
                                </div>
                                <div class="pdwchs-progress-bar">
                                    <div class="pdwchs-progress-fill" data-score="<?php echo intval( $frequency_score ); ?>" style="width: <?php echo esc_attr( ( $frequency_score / 5 ) * 100 ); ?>%; background-color: <?php echo $segment_config ? esc_attr( $segment_config['color'] ) : '#94a3b8'; ?>;"></div>
                                </div>
                                <div class="pdwchs-score-description"><?php echo esc_html( $frequency_text ); ?></div>
                            </div>

                            <!-- Monetary Score -->
                            <div class="pdwchs-score-row">
                                <div class="pdwchs-score-label">
                                    <strong>M</strong> <?php echo esc_html__( 'Monetary', 'puredevs-customer-history-for-woocommerce' ); ?>
                                    <span class="pdwchs-score-value"><?php echo intval( $monetary_score ); ?>/5</span>
                                </div>
                                <div class="pdwchs-progress-bar">
                                    <div class="pdwchs-progress-fill" data-score="<?php echo intval( $monetary_score ); ?>" style="width: <?php echo esc_attr( ( $monetary_score / 5 ) * 100 ); ?>%; background-color: <?php echo $segment_config ? esc_attr( $segment_config['color'] ) : '#94a3b8'; ?>;"></div>
                                </div>
                                <div class="pdwchs-score-description"><?php echo wp_kses_post( $monetary_text ); ?></div>
                            </div>
                        </div>

                        <?php if ( $segment_config ) : ?>
                        <div class="pdwchs-rfm-recommendations">
                            <h3><span class="dashicons dashicons-lightbulb"></span> <?php echo esc_html__( 'Recommended Actions:', 'puredevs-customer-history-for-woocommerce' ); ?></h3>
                            <ul>
                                <?php foreach ( $segment_config['recommendations'] as $recommendation ) : ?>
                                    <li><?php echo esc_html( $recommendation ); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <?php endif; ?>

                        <div class="pdwchs-rfm-card-footer">
                            <div class="pdwchs-rfm-timestamp">
                                <?php
                                $calculated_at = isset( $rfm_data['rfm_calculated_at'] ) ? $rfm_data['rfm_calculated_at'] : null;
                                if ( $calculated_at ) {
                                    $time_ago = human_time_diff( strtotime( $calculated_at ), current_time( 'timestamp' ) );
                                    echo esc_html( sprintf( __( 'Last calculated: %s ago', 'puredevs-customer-history-for-woocommerce' ), $time_ago ) );
                                } else {
                                    echo esc_html__( 'Not calculated yet', 'puredevs-customer-history-for-woocommerce' );
                                }
                                ?>
                            </div>
                            <button type="button" class="button pdwchs-recalculate-rfm-btn" data-user-id="<?php echo intval( $user_id ); ?>">
                                <span class="dashicons dashicons-update"></span>
                                <?php echo esc_html__( 'Recalculate Now', 'puredevs-customer-history-for-woocommerce' ); ?>
                            </button>
                        </div>
                    </section>
                    <script type="text/javascript">
                    // Pass RFM nonce to JavaScript
                    var pdwchs_rfm_nonce = '<?php echo esc_js( wp_create_nonce( 'pdwchs_rfm_nonce' ) ); ?>';
                    </script>
