(function($) {
    'use strict';

    const LifecycleManager = {
        init: function() {
            this.initFunnelWidget();
            this.initAtRiskWidget();
            this.bindEvents();
        },

        bindEvents: function() {
            $(document).on('click', '.pdwchs-refresh-btn[data-widget="lifecycle-funnel"]', this.refreshFunnel.bind(this));
            $(document).on('click', '.pdwchs-refresh-btn[data-widget="at-risk-alert"]', this.refreshAtRiskAlert.bind(this));
            $(document).on('click', '.pdwchs-recalculate-lifecycle', this.recalculateLifecycle.bind(this));
            $(document).on('click', '.pdwchs-funnel-stage, .pdwchs-issue-stat', this.filterByStage.bind(this));
        },

        initFunnelWidget: function() {
            if ($('#pdwchs-lifecycle-funnel-widget').length) {
                this.loadFunnelData();
            }
        },

        loadFunnelData: function() {
            const $widget = $('#pdwchs-lifecycle-funnel-widget');
            const $container = $widget.find('.pdwchs-funnel-container');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pdwchs_get_lifecycle_funnel',
                    nonce: pdwchs_lifecycle_vars.nonce
                },
                success: function(response) {
                    if (response.success && response.data) {
                        this.renderFunnel(response.data);
                    }
                }.bind(this),
                error: function() {
                    $container.html('<p class="error">Failed to load funnel data</p>');
                }
            });
        },

        renderFunnel: function(data) {
            const $widget = $('#pdwchs-lifecycle-funnel-widget');
            const $container = $widget.find('.pdwchs-funnel-container');

            $container.empty();

            const stages = ['Prospect', 'New Customer', 'Active Customer', 'Loyal Customer'];
            let html = '<div class="pdwchs-funnel">';

            stages.forEach((stageName, index) => {
                const stageData = data.stages.find(s => s.name === stageName);
                if (!stageData) return;

                const width = 100 - (index * 15);

                html += `
                    <div class="pdwchs-funnel-stage" data-stage="${stageName}"
                         style="width: ${width}%; background-color: ${stageData.color};">
                        <div class="pdwchs-stage-content">
                            <span class="pdwchs-stage-name">${stageName}</span>
                            <span class="pdwchs-stage-count">${stageData.count}</span>
                        </div>
                    </div>
                `;
            });

            html += '</div>';
            $container.html(html);

            // Update sidebar stats
            const atRiskData = data.stages.find(s => s.name === 'At Risk');
            const churnedData = data.stages.find(s => s.name === 'Churned');

            $widget.find('.pdwchs-issue-stat[data-stage="At Risk"] .pdwchs-issue-count')
                .text(atRiskData ? atRiskData.count : 0);
            $widget.find('.pdwchs-issue-stat[data-stage="Churned"] .pdwchs-issue-count')
                .text(churnedData ? churnedData.count : 0);

            $widget.find('.pdwchs-customer-count').text(data.total_customers || 0);
        },

        refreshFunnel: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            $btn.addClass('spinning');
            this.loadFunnelData();
            setTimeout(() => $btn.removeClass('spinning'), 1000);
        },

        initAtRiskWidget: function() {
            if ($('#pdwchs-at-risk-alert-widget').length) {
                this.loadAtRiskData();
            }
        },

        loadAtRiskData: function() {
            const $widget = $('#pdwchs-at-risk-alert-widget');
            const $list = $widget.find('.pdwchs-at-risk-list');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pdwchs_get_at_risk_alert',
                    nonce: pdwchs_lifecycle_vars.nonce,
                    limit: 15
                },
                success: function(response) {
                    if (response.success && response.data) {
                        this.renderAtRiskList(response.data);
                    }
                }.bind(this),
                error: function() {
                    $list.html('<p class="error">Failed to load at risk customers</p>');
                }
            });
        },

        renderAtRiskList: function(customers) {
            const $list = $('#pdwchs-at-risk-alert-widget .pdwchs-at-risk-list');

            if (!customers || customers.length === 0) {
                $list.html('<p class="pdwchs-no-results">No at risk customers found</p>');
                return;
            }

            let html = '';
            customers.forEach(customer => {
                const priorityClass = this.getAtRiskPriorityClass(customer);
                const profileUrl = `admin.php?page=pd-woo-customers&action=view&user_id=${customer.user_id}`;

                html += `
                    <div class="pdwchs-at-risk-item ${priorityClass}">
                        <div class="pdwchs-customer-info">
                            <h4>${customer.name}</h4>
                            <div class="pdwchs-customer-meta">
                                <span>At risk for: <strong>${customer.days_at_risk} days</strong></span>
                                <span>${customer.total_orders} orders</span>
                                <span>${customer.lifetime_value_formatted} LTV</span>
                            </div>
                            <p class="pdwchs-last-order">Last order: ${customer.days_since_last} days ago</p>
                        </div>
                        <div class="pdwchs-customer-actions">
                            <a href="${profileUrl}" class="button button-small">View Profile</a>
                        </div>
                    </div>
                `;
            });

            $list.html(html);
        },

        getAtRiskPriorityClass: function(customer) {
            if (customer.days_at_risk >= 60 || customer.total_orders >= 10) {
                return 'priority-critical';
            } else if (customer.days_at_risk >= 45 || customer.total_orders >= 5) {
                return 'priority-high';
            }
            return 'priority-medium';
        },

        refreshAtRiskAlert: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            $btn.addClass('spinning');
            this.loadAtRiskData();
            setTimeout(() => $btn.removeClass('spinning'), 1000);
        },

        recalculateLifecycle: function(e) {
            e.preventDefault();
            const $btn = $(e.currentTarget);
            const userId = $btn.data('user-id');

            $btn.prop('disabled', true).text('Recalculating...');

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pdwchs_recalculate_lifecycle',
                    nonce: pdwchs_lifecycle_vars.nonce,
                    user_id: userId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Failed to recalculate lifecycle stage');
                        $btn.prop('disabled', false).text('Recalculate Now');
                    }
                },
                error: function() {
                    alert('Failed to recalculate lifecycle stage');
                    $btn.prop('disabled', false).text('Recalculate Now');
                }
            });
        },

        filterByStage: function(e) {
            e.preventDefault();
            const $target = $(e.currentTarget);
            const stage = $target.data('stage');
            window.location.href = `admin.php?page=pd-woo-customers&lifecycle_stage=${encodeURIComponent(stage)}`;
        }
    };

    $(document).ready(function() {
        LifecycleManager.init();
    });

})(jQuery);
