/**
 * Churn Prediction JavaScript
 *
 * Handles AJAX functionality for churn prediction features.
 *
 * @package    PD_Customer_History
 * @since      4.0.0
 */

(function($) {
	'use strict';

	/**
	 * Churn Prediction Handler
	 */
	var PDWCHSChurn = {

		/**
		 * Initialize
		 */
		init: function() {
			this.bindEvents();

			// Initialize prediction page features
			if (pdwchs_churn_vars.is_prediction_page) {
				this.initPredictionPage();
			}
		},

		/**
		 * Bind event handlers
		 */
		bindEvents: function() {
			// Dashboard widget refresh
			$(document).on('click', '.pdwchs-refresh-churn', this.refreshDashboardWidget);

			// Recalculate customer churn score
			$(document).on('click', '.pdwchs-recalculate-churn', this.recalculateCustomerChurn);

			// Alert status update
			$(document).on('change', '.pdwchs-alert-status-select', this.updateAlertStatus);

			// Alert assignment
			$(document).on('change', '.pdwchs-alert-assign-select', this.assignAlert);

			// Settings form submission
			$(document).on('submit', '#pdwchs-churn-settings-form', this.saveSettings);

			// Weight input change - update total
			$(document).on('input', '.pdwchs-weight-input', this.updateWeightTotal);

			// At-risk table filters
			$(document).on('change', '#pdwchs-risk-filter', this.filterAtRiskTable);
			$(document).on('change', '#pdwchs-sort-filter', this.sortAtRiskTable);
		},

		/**
		 * Initialize prediction page features
		 */
		initPredictionPage: function() {
			// Calculate initial weight total
			this.updateWeightTotal();
		},

		/**
		 * Refresh dashboard widget
		 */
		refreshDashboardWidget: function(e) {
			e.preventDefault();

			var $btn = $(this);
			var $widget = $btn.closest('.pdwchs-churn-dashboard-widget');

			$btn.addClass('loading');

			$.ajax({
				url: pdwchs_churn_vars.ajaxurl,
				type: 'POST',
				data: {
					action: 'pdwchs_refresh_churn_widget',
					nonce: pdwchs_churn_vars.nonce
				},
				success: function(response) {
					if (response.success && response.data.html) {
						$widget.replaceWith(response.data.html);
					}
				},
				error: function() {
					console.error('Error refreshing churn widget');
				},
				complete: function() {
					$btn.removeClass('loading');
				}
			});
		},

		/**
		 * Recalculate customer churn score
		 */
		recalculateCustomerChurn: function(e) {
			e.preventDefault();

			var $btn = $(this);
			var userId = $btn.data('user-id');

			if (!userId) {
				return;
			}

			$btn.find('.dashicons').addClass('pdwchs-spinning');

			$.ajax({
				url: pdwchs_churn_vars.ajaxurl,
				type: 'POST',
				data: {
					action: 'pdwchs_recalculate_churn',
					nonce: pdwchs_churn_vars.nonce,
					user_id: userId
				},
				success: function(response) {
					if (response.success) {
						// Reload the page to show updated data
						location.reload();
					} else {
						alert(response.data.message || pdwchs_churn_vars.error_text);
					}
				},
				error: function() {
					alert(pdwchs_churn_vars.error_text);
				},
				complete: function() {
					$btn.find('.dashicons').removeClass('pdwchs-spinning');
				}
			});
		},

		/**
		 * Update alert status
		 */
		updateAlertStatus: function() {
			var $select = $(this);
			var alertId = $select.data('alert-id');
			var newStatus = $select.val();
			var $row = $select.closest('tr');

			$.ajax({
				url: pdwchs_churn_vars.ajaxurl,
				type: 'POST',
				data: {
					action: 'pdwchs_update_alert_status',
					nonce: pdwchs_churn_vars.nonce,
					alert_id: alertId,
					status: newStatus
				},
				success: function(response) {
					if (response.success) {
						// Update row styling based on new status
						$row.removeClass('status-new status-viewed status-in_progress status-resolved status-dismissed');
						$row.addClass('status-' + newStatus);

						// Show success feedback
						PDWCHSChurn.showNotice('success', response.data.message);
					} else {
						// Revert select on error
						$select.val($select.data('original-value'));
						PDWCHSChurn.showNotice('error', response.data.message);
					}
				},
				error: function() {
					$select.val($select.data('original-value'));
					PDWCHSChurn.showNotice('error', pdwchs_churn_vars.error_text);
				}
			});

			// Store original value for reverting on error
			$select.data('original-value', newStatus);
		},

		/**
		 * Assign alert to user
		 */
		assignAlert: function() {
			var $select = $(this);
			var alertId = $select.data('alert-id');
			var assigneeId = $select.val();

			$.ajax({
				url: pdwchs_churn_vars.ajaxurl,
				type: 'POST',
				data: {
					action: 'pdwchs_assign_alert',
					nonce: pdwchs_churn_vars.nonce,
					alert_id: alertId,
					user_id: assigneeId
				},
				success: function(response) {
					if (response.success) {
						PDWCHSChurn.showNotice('success', response.data.message);
					} else {
						PDWCHSChurn.showNotice('error', response.data.message);
					}
				},
				error: function() {
					PDWCHSChurn.showNotice('error', pdwchs_churn_vars.error_text);
				}
			});
		},

		/**
		 * Save churn prediction settings
		 */
		saveSettings: function(e) {
			e.preventDefault();

			var $form = $(this);
			var $btn = $('#pdwchs-save-churn-settings');
			var $spinner = $('#pdwchs-settings-spinner');

			// Validate weight total
			var total = PDWCHSChurn.getWeightTotal();
			if (total !== 100) {
				PDWCHSChurn.showNotice('error', 'Scoring weights must total 100%. Current total: ' + total + '%');
				return;
			}

			$btn.prop('disabled', true);
			$spinner.addClass('is-active');

			var formData = $form.serializeArray();
			var data = {
				action: 'pdwchs_save_churn_settings',
				nonce: pdwchs_churn_vars.nonce
			};

			// Build data from form
			$.each(formData, function(i, field) {
				if (field.name !== 'pdwchs_churn_settings_nonce' && field.name !== '_wp_http_referer') {
					data[field.name] = field.value;
				}
			});

			// Handle unchecked checkboxes
			$form.find('input[type="checkbox"]').each(function() {
				if (!$(this).is(':checked')) {
					data[$(this).attr('name')] = '0';
				}
			});

			$.ajax({
				url: pdwchs_churn_vars.ajaxurl,
				type: 'POST',
				data: data,
				success: function(response) {
					if (response.success) {
						PDWCHSChurn.showNotice('success', pdwchs_churn_vars.settings_saved_text || response.data.message);
					} else {
						PDWCHSChurn.showNotice('error', response.data.message || pdwchs_churn_vars.error_text);
					}
				},
				error: function() {
					PDWCHSChurn.showNotice('error', pdwchs_churn_vars.error_text);
				},
				complete: function() {
					$btn.prop('disabled', false);
					$spinner.removeClass('is-active');
				}
			});
		},

		/**
		 * Update weight total display
		 */
		updateWeightTotal: function() {
			var total = PDWCHSChurn.getWeightTotal();
			var $totalEl = $('#pdwchs-weight-total-value');
			var $totalContainer = $totalEl.closest('.pdwchs-weight-total');

			$totalEl.text(total);

			if (total !== 100) {
				$totalContainer.addClass('pdwchs-weight-error');
			} else {
				$totalContainer.removeClass('pdwchs-weight-error');
			}
		},

		/**
		 * Get current weight total
		 */
		getWeightTotal: function() {
			var total = 0;
			$('.pdwchs-weight-input').each(function() {
				total += parseInt($(this).val(), 10) || 0;
			});
			return total;
		},

		/**
		 * Filter at-risk table by risk level
		 */
		filterAtRiskTable: function() {
			var riskLevel = $(this).val();
			var $tbody = $('#pdwchs-at-risk-tbody');

			$tbody.find('tr').each(function() {
				var $row = $(this);
				if (!riskLevel || $row.data('risk-level') === riskLevel) {
					$row.show();
				} else {
					$row.hide();
				}
			});
		},

		/**
		 * Sort at-risk table
		 */
		sortAtRiskTable: function() {
			var sortBy = $(this).val();
			var $tbody = $('#pdwchs-at-risk-tbody');
			var rows = $tbody.find('tr').get();

			rows.sort(function(a, b) {
				var $a = $(a);
				var $b = $(b);

				switch (sortBy) {
					case 'score_desc':
						return parseFloat($b.data('score')) - parseFloat($a.data('score'));
					case 'score_asc':
						return parseFloat($a.data('score')) - parseFloat($b.data('score'));
					case 'value_desc':
						return parseFloat($b.data('value')) - parseFloat($a.data('value'));
					default:
						return 0;
				}
			});

			$.each(rows, function(idx, row) {
				$tbody.append(row);
			});
		},

		/**
		 * Show admin notice
		 */
		showNotice: function(type, message) {
			// Remove any existing notices
			$('.pdwchs-churn-notice').remove();

			var $notice = $('<div class="notice notice-' + type + ' is-dismissible pdwchs-churn-notice"><p>' + message + '</p><button type="button" class="notice-dismiss"></button></div>');

			// Insert after header
			$('.wrap h1').first().after($notice);

			// Bind dismiss button
			$notice.find('.notice-dismiss').on('click', function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			});

			// Auto-dismiss after 5 seconds
			setTimeout(function() {
				$notice.fadeOut(function() {
					$(this).remove();
				});
			}, 5000);
		},

		/**
		 * Initialize charts if Chart.js is available
		 */
		initCharts: function() {
			if (typeof Chart === 'undefined') {
				return;
			}

			// Risk distribution chart
			var $canvas = $('#pdwchs-churn-distribution-chart');
			if ($canvas.length && $canvas.data('distribution')) {
				var distribution = $canvas.data('distribution');

				new Chart($canvas[0].getContext('2d'), {
					type: 'doughnut',
					data: {
						labels: ['Low', 'Medium', 'High', 'Critical'],
						datasets: [{
							data: [
								distribution.low || 0,
								distribution.medium || 0,
								distribution.high || 0,
								distribution.critical || 0
							],
							backgroundColor: [
								'#22c55e',
								'#f59e0b',
								'#ef4444',
								'#dc2626'
							],
							borderWidth: 0
						}]
					},
					options: {
						responsive: true,
						maintainAspectRatio: false,
						plugins: {
							legend: {
								position: 'bottom',
								labels: {
									padding: 15,
									usePointStyle: true
								}
							}
						},
						cutout: '60%'
					}
				});
			}
		}
	};

	// Initialize when DOM is ready
	$(document).ready(function() {
		PDWCHSChurn.init();
		PDWCHSChurn.initCharts();
	});

	// Add CSS for spinning animation
	$('<style>')
		.text('.pdwchs-spinning { animation: pdwchs-spin 1s linear infinite; } @keyframes pdwchs-spin { 100% { transform: rotate(360deg); } }')
		.appendTo('head');

})(jQuery);
