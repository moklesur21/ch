(function($) {
    'use strict';

    const HealthScore = {

        /**
         * Initialize Health Score functionality
         */
        init: function() {
            // Only run on relevant pages
            if (typeof pdwchs_health_vars === 'undefined') {
                return;
            }

            this.loadHealthDistribution();
            this.loadDecliningHealth();
            this.bindEvents();
            this.animateGauge();
            this.animateProgressBars();
        },

        /**
         * Load health distribution data via AJAX
         */
        loadHealthDistribution: function() {
            const $container = $('#pdwchs-health-distribution-chart');
            if ($container.length === 0) {
                return;
            }

            $.ajax({
                url: pdwchs_health_vars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'pdwchs_get_health_distribution',
                    nonce: pdwchs_health_vars.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        HealthScore.renderDistribution(response.data);
                    } else {
                        $container.html('<p class="pdwchs-no-data">' + pdwchs_health_vars.no_data_text + '</p>');
                    }
                },
                error: function() {
                    $container.html('<p class="pdwchs-error">' + pdwchs_health_vars.error_text + '</p>');
                }
            });
        },

        /**
         * Render health distribution chart
         */
        renderDistribution: function(data) {
            // Update average score
            $('.pdwchs-avg-score').text(data.average_score);

            // Render bars
            let html = '';
            if (data.ranges && data.ranges.length > 0) {
                data.ranges.forEach(function(range) {
                    const width = data.total_customers > 0
                        ? (range.count / data.total_customers * 100)
                        : 0;

                    html += '<div class="pdwchs-health-bar-row" data-range="' + range.label + '">';
                    html += '  <div class="pdwchs-health-bar-label">' + range.label + '</div>';
                    html += '  <div class="pdwchs-health-bar-container">';
                    html += '    <div class="pdwchs-health-bar" style="width: ' + width + '%; background-color: ' + range.color + ';"></div>';
                    html += '  </div>';
                    html += '  <div class="pdwchs-health-bar-count">' + range.count + ' (' + range.percentage + '%)</div>';
                    html += '</div>';
                });
            } else {
                html = '<p class="pdwchs-no-data">' + pdwchs_health_vars.no_data_text + '</p>';
            }

            $('#pdwchs-health-distribution-chart').html(html);

            // Show critical alert if exists
            if (data.critical_count && data.critical_count > 0) {
                $('.pdwchs-critical-message').text(
                    data.critical_count + ' ' + pdwchs_health_vars.critical_customers_text
                );
                $('.pdwchs-health-critical-alert').show();
            }
        },

        /**
         * Load declining health customers via AJAX
         */
        loadDecliningHealth: function() {
            const $container = $('#pdwchs-declining-health-list');
            if ($container.length === 0) {
                return;
            }

            $.ajax({
                url: pdwchs_health_vars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'pdwchs_get_declining_health',
                    nonce: pdwchs_health_vars.nonce,
                    limit: 10
                },
                success: function(response) {
                    if (response.success && response.data) {
                        HealthScore.renderDecliningList(response.data);
                    } else {
                        $container.html('<p class="pdwchs-no-data">' + pdwchs_health_vars.no_declining_text + '</p>');
                    }
                },
                error: function() {
                    $container.html('<p class="pdwchs-error">' + pdwchs_health_vars.error_text + '</p>');
                }
            });
        },

        /**
         * Render declining customers list
         */
        renderDecliningList: function(data) {
            if (!data.customers || data.customers.length === 0) {
                $('#pdwchs-declining-health-list').html(
                    '<p class="pdwchs-no-data">' + pdwchs_health_vars.no_declining_text + '</p>'
                );
                return;
            }

            let html = '<div class="pdwchs-declining-list">';

            data.customers.forEach(function(customer) {
                const declineClass = customer.decline >= 20 ? 'severe' :
                                   customer.decline >= 15 ? 'high' : 'medium';

                html += '<div class="pdwchs-declining-item pdwchs-decline-' + declineClass + '">';
                html += '  <div class="pdwchs-declining-name">' + customer.name + '</div>';
                html += '  <div class="pdwchs-declining-scores">';
                html += '    <span class="pdwchs-score-change">' + customer.previous_score + ' → ' + customer.current_score + '</span>';
                html += '    <span class="pdwchs-decline-amount">↓ ' + customer.decline + ' ' + pdwchs_health_vars.points_text + '</span>';
                html += '  </div>';

                if (customer.days_since_order !== null) {
                    html += '  <div class="pdwchs-declining-meta">';
                    html += pdwchs_health_vars.last_order_text + ' ' + customer.days_since_order + ' ' + pdwchs_health_vars.days_ago_text;
                    html += '  </div>';
                }

                html += '  <a href="' + customer.profile_url + '" class="button button-small">';
                html += pdwchs_health_vars.view_profile_text;
                html += '  </a>';
                html += '</div>';
            });

            html += '</div>';

            $('#pdwchs-declining-health-list').html(html);
        },

        /**
         * Recalculate health for a customer
         */
        recalculateHealth: function(userId) {
            const $button = $('.pdwchs-recalculate-health');

            $.ajax({
                url: pdwchs_health_vars.ajaxurl,
                type: 'POST',
                data: {
                    action: 'pdwchs_recalculate_health',
                    nonce: pdwchs_health_vars.nonce,
                    user_id: userId
                },
                beforeSend: function() {
                    $button.prop('disabled', true);
                    $button.find('.dashicons').addClass('spin');
                    $button.append('<span class="pdwchs-calculating">' + pdwchs_health_vars.calculating_text + '</span>');
                },
                success: function(response) {
                    if (response.success) {
                        // Reload the page to show updated data
                        location.reload();
                    } else {
                        alert(response.data.message || pdwchs_health_vars.error_text);
                        $button.prop('disabled', false);
                        $button.find('.dashicons').removeClass('spin');
                        $button.find('.pdwchs-calculating').remove();
                    }
                },
                error: function() {
                    alert(pdwchs_health_vars.error_text);
                    $button.prop('disabled', false);
                    $button.find('.dashicons').removeClass('spin');
                    $button.find('.pdwchs-calculating').remove();
                }
            });
        },

        /**
         * Animate circular gauge
         */
        animateGauge: function() {
            $('.pdwchs-gauge-fill').each(function() {
                const $gauge = $(this);
                const finalOffset = $gauge.attr('stroke-dashoffset');

                // Start from full circle (hidden)
                $gauge.attr('stroke-dashoffset', '502.65');

                // Animate to final value
                setTimeout(function() {
                    $gauge.css('transition', 'stroke-dashoffset 1s ease-out');
                    $gauge.attr('stroke-dashoffset', finalOffset);
                }, 100);
            });
        },

        /**
         * Animate progress bars
         */
        animateProgressBars: function() {
            $('.pdwchs-component-fill').each(function() {
                const $bar = $(this);
                const finalWidth = $bar.css('width');

                // Start from 0
                $bar.css('width', '0');

                // Animate to final value
                setTimeout(function() {
                    $bar.css({
                        'transition': 'width 0.8s ease-out',
                        'width': finalWidth
                    });
                }, 200);
            });
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;

            // Refresh button
            $(document).on('click', '.pdwchs-refresh-health', function(e) {
                e.preventDefault();
                self.loadHealthDistribution();
                self.loadDecliningHealth();

                // Visual feedback
                $(this).find('.dashicons').addClass('spin');
                setTimeout(function() {
                    $('.pdwchs-refresh-health .dashicons').removeClass('spin');
                }, 1000);
            });

            // Recalculate button on customer profile
            $(document).on('click', '.pdwchs-recalculate-health', function(e) {
                e.preventDefault();
                const userId = $(this).data('user-id');
                if (userId) {
                    self.recalculateHealth(userId);
                }
            });

            // Click on bar to filter customers (future enhancement)
            $(document).on('click', '.pdwchs-health-bar-row', function() {
                const range = $(this).data('range');
                // This could navigate to customers page with filter
                console.log('Filter by range:', range);
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        HealthScore.init();
    });

    // Add spin animation CSS class
    if (!$('head').find('style#pdwchs-health-spin').length) {
        $('head').append(
            '<style id="pdwchs-health-spin">' +
            '.dashicons.spin { animation: pdwchs-spin 1s linear infinite; } ' +
            '@keyframes pdwchs-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }' +
            '</style>'
        );
    }

})(jQuery);
