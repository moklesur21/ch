/**
 * Customer Behavioral Segmentation JavaScript
 *
 * Handles AJAX calls for behavioral analysis, dashboard widget rendering,
 * and customer detail card interactions.
 *
 * @package    PD_Customer_History
 * @subpackage PD_Customer_History/admin/js
 * @since      3.0.0
 */

(function ($) {
    'use strict';

    /**
     * Initialize when DOM is ready
     */
    $(document).ready(function () {
        // Dashboard widget
        pdwchsBehavioralDashboard.init();

        // Customer detail card
        pdwchsBehavioralCard.init();
    });

    /**
     * Dashboard Widget Controller
     */
    var pdwchsBehavioralDashboard = {

        init: function () {
            var $widget = $('.pdwchs-behavioral-distribution-widget');
            if ($widget.length === 0) {
                return;
            }

            this.loadSegmentsData();
            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;
            $(document).on('click', '.pdwchs-refresh-behavioral-widget', function (e) {
                e.preventDefault();
                var $btn = $(this);
                $btn.find('.dashicons').addClass('pdwchs-spinning');
                self.loadSegmentsData(function () {
                    $btn.find('.dashicons').removeClass('pdwchs-spinning');
                });
            });
        },

        loadSegmentsData: function (callback) {
            var nonce = typeof pdwchs_behavioral_nonce !== 'undefined' ? pdwchs_behavioral_nonce : '';
            if (!nonce) {
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pdwchs_behavioral_segments_data',
                    nonce: nonce
                },
                success: function (response) {
                    if (response.success) {
                        pdwchsBehavioralDashboard.renderSegments(response.data);
                    } else {
                        $('#pdwchs-behavioral-segments-chart').html(
                            '<div class="pdwchs-no-data">' + (response.data && response.data.message ? response.data.message : 'No behavioral data available.') + '</div>'
                        );
                    }
                    if (typeof callback === 'function') callback();
                },
                error: function () {
                    $('#pdwchs-behavioral-segments-chart').html(
                        '<div class="pdwchs-no-data">Failed to load behavioral data.</div>'
                    );
                    if (typeof callback === 'function') callback();
                }
            });
        },

        renderSegments: function (data) {
            var segments = data.segments || [];
            var insights = data.insights || [];
            var totalCustomers = data.total_customers || 0;
            var $container = $('#pdwchs-behavioral-segments-chart');

            if (segments.length === 0 || totalCustomers === 0) {
                $container.html(
                    '<div class="pdwchs-no-data">No behavioral data yet. Behavioral analysis runs daily or when orders are completed.</div>'
                );
                return;
            }

            var html = '';

            // Filter segments with count > 0
            var activeSegments = segments.filter(function (s) { return s.count > 0; });

            if (activeSegments.length === 0) {
                $container.html(
                    '<div class="pdwchs-no-data">No behavioral patterns detected yet. Analysis will run automatically.</div>'
                );
                return;
            }

            var maxCount = activeSegments.length > 0 ? activeSegments[0].count : 1;

            activeSegments.forEach(function (segment) {
                var barWidth = maxCount > 0 ? Math.max(2, (segment.count / maxCount) * 100) : 0;
                html += '<div class="pdwchs-behavioral-bar-row">';
                html += '<div class="pdwchs-behavioral-bar-icon"><span class="dashicons ' + segment.icon + '"></span></div>';
                html += '<div class="pdwchs-behavioral-bar-label">' + segment.label + '</div>';
                html += '<div class="pdwchs-behavioral-bar-container">';
                html += '<div class="pdwchs-behavioral-bar" style="width: ' + barWidth + '%;"></div>';
                html += '</div>';
                html += '<div class="pdwchs-behavioral-bar-count">' + segment.count;
                html += '<span class="pdwchs-behavioral-bar-percent"> (' + segment.percentage + '%)</span>';
                html += '</div>';
                html += '</div>';
            });

            $container.html(html);

            // Render insights
            if (insights.length > 0) {
                var $insightsSection = $('#pdwchs-behavioral-insights');
                var $list = $insightsSection.find('.pdwchs-insight-list');
                $list.empty();

                insights.forEach(function (insight) {
                    $list.append('<li>' + insight + '</li>');
                });

                $insightsSection.show();
            }
        }
    };

    /**
     * Customer Detail Behavioral Card Controller
     */
    var pdwchsBehavioralCard = {

        init: function () {
            var $card = $('.pdwchs-behavioral-card');
            if ($card.length === 0) {
                return;
            }

            this.bindEvents();
        },

        bindEvents: function () {
            var self = this;

            // Refresh analysis button
            $(document).on('click', '.pdwchs-refresh-behavioral-btn', function (e) {
                e.preventDefault();
                var $btn = $(this);
                var userId = $btn.data('user-id');

                if (!userId) {
                    return;
                }

                $btn.addClass('pdwchs-loading-btn').prop('disabled', true);

                self.runAnalysis(userId, function (success, data) {
                    $btn.removeClass('pdwchs-loading-btn').prop('disabled', false);

                    if (success) {
                        self.updateCard(data);
                    } else {
                        alert(data && data.message ? data.message : 'Analysis failed. Please try again.');
                    }
                });
            });
        },

        runAnalysis: function (userId, callback) {
            var nonce = typeof pdwchs_behavioral_nonce !== 'undefined' ? pdwchs_behavioral_nonce : '';
            if (!nonce) {
                callback(false, { message: 'Security token not found.' });
                return;
            }

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'pdwchs_analyze_behavior',
                    nonce: nonce,
                    user_id: userId
                },
                success: function (response) {
                    if (response.success) {
                        callback(true, response.data);
                    } else {
                        callback(false, response.data);
                    }
                },
                error: function () {
                    callback(false, { message: 'Server error. Please try again.' });
                }
            });
        },

        updateCard: function (data) {
            var tags = data.tags || [];
            var preferences = data.preferences || {};
            var nextAction = data.next_action || null;

            // Update tags
            var $tagsContainer = $('#pdwchs-behavioral-tags');
            if (tags.length > 0) {
                var html = '';
                tags.forEach(function (tag) {
                    var confClass = 'pdwchs-confidence-low';
                    if (tag.confidence_score >= 80) {
                        confClass = 'pdwchs-confidence-high';
                    } else if (tag.confidence_score >= 60) {
                        confClass = 'pdwchs-confidence-medium';
                    }

                    html += '<div class="pdwchs-behavioral-tag ' + confClass + '" title="' + (tag.description || '') + '">';
                    if (tag.icon) {
                        html += '<span class="dashicons ' + tag.icon + ' pdwchs-tag-icon"></span>';
                    }
                    html += '<span class="pdwchs-tag-label">' + tag.label + '</span>';
                    html += '<span class="pdwchs-tag-confidence">' + tag.confidence_score + '%</span>';
                    html += '</div>';
                });
                $tagsContainer.html(html);

                // Update tag count
                var countText = tags.length === 1 ? tags.length + ' tag' : tags.length + ' tags';
                $('.pdwchs-behavioral-tag-count').text(countText);
            } else {
                $tagsContainer.html(
                    '<div class="pdwchs-no-tags">' +
                    '<span class="dashicons dashicons-info-outline"></span>' +
                    '<p>No behavioral patterns detected. This customer may need more order history.</p>' +
                    '</div>'
                );
                $('.pdwchs-behavioral-tag-count').text('');
            }

            // Update next action
            if (nextAction) {
                var $actionSection = $('.pdwchs-next-action');
                if ($actionSection.length) {
                    $actionSection.find('.pdwchs-action-text').text(nextAction.action || '');
                } else {
                    var actionHtml = '<div class="pdwchs-next-action">';
                    actionHtml += '<h3><span class="dashicons dashicons-lightbulb"></span> Next Best Action</h3>';
                    actionHtml += '<div class="pdwchs-next-action-content">';
                    actionHtml += '<p class="pdwchs-action-text">' + (nextAction.action || '') + '</p>';
                    actionHtml += '</div></div>';
                    $tagsContainer.after(actionHtml);
                }
            }

            // Update timestamp
            $('.pdwchs-behavioral-timestamp').text('Last analyzed: just now');
        }
    };

    /**
     * Add spinning animation class
     */
    $('<style>')
        .text('.pdwchs-spinning { animation: pdwchs-spin 1s linear infinite; }')
        .appendTo('head');

})(jQuery);
