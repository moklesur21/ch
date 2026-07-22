(function($) {
	'use strict';

	/**
	 * RFM Analysis Dashboard Widget
	 *
	 * Handles chart rendering, data loading, and user interactions
	 * for the RFM analysis dashboard widget.
	 */
	var PDWCHS_RFM = {

		chart: null,

		/**
		 * Initialize the RFM dashboard widget
		 */
		init: function() {
			if ($('#pdwchs-rfm-chart').length > 0) {
				this.loadData();
				this.bindEvents();
			}

			// Initialize recalculate button on customer profile
			if ($('.pdwchs-recalculate-rfm').length > 0) {
				this.bindRecalculateButton();
			}
		},

		/**
		 * Bind event listeners
		 */
		bindEvents: function() {
			$('.pdwchs-refresh-rfm').on('click', function(e) {
				e.preventDefault();
				PDWCHS_RFM.refreshData();
			});
		},

		/**
		 * Load RFM distribution data via AJAX
		 */
		loadData: function() {
			$.ajax({
				url: ajaxurl,
				method: 'GET',
				data: {
					action: 'pdwchs_get_rfm_distribution',
					nonce: pdwchs_rfm_vars.nonce
				},
				success: function(response) {
					if (response.success && response.data) {
						PDWCHS_RFM.renderChart(response.data);
						PDWCHS_RFM.renderLegend(response.data);
						$('.pdwchs-rfm-loading').hide();
						$('.pdwchs-rfm-content').show();
						$('.pdwchs-rfm-footer').show();
					} else {
						PDWCHS_RFM.showError();
					}
				},
				error: function() {
					PDWCHS_RFM.showError();
				}
			});
		},

		/**
		 * Render the doughnut chart
		 */
		renderChart: function(data) {
			var chartElement = document.getElementById('pdwchs-rfm-chart');

			// Check if the chart element exists
			if (!chartElement) {
				console.warn('RFM chart canvas element not found in DOM');
				return;
			}

			var ctx = chartElement.getContext('2d');

			// Filter out segments with 0 count
			var activeSegments = data.segments.filter(function(segment) {
				return segment.count > 0;
			});

			// If no active segments, show message
			if (activeSegments.length === 0) {
				$('.pdwchs-rfm-content').html('<p>' + pdwchs_rfm_vars.no_data_text + '</p>');
				return;
			}

			var chartData = {
				labels: activeSegments.map(function(s) {
					return s.icon + ' ' + s.name;
				}),
				datasets: [{
					data: activeSegments.map(function(s) {
						return s.count;
					}),
					backgroundColor: activeSegments.map(function(s) {
						return s.color;
					}),
					borderWidth: 2,
					borderColor: '#ffffff'
				}]
			};

			// Destroy existing chart if it exists
			if (this.chart) {
				this.chart.destroy();
			}

			// Create new chart
			this.chart = new Chart(ctx, {
				type: 'doughnut',
				data: chartData,
				options: {
					responsive: true,
					maintainAspectRatio: true,
					plugins: {
						legend: {
							display: false
						},
						tooltip: {
							callbacks: {
								label: function(context) {
									var label = context.label || '';
									var value = context.parsed;
									var total = context.dataset.data.reduce(function(a, b) {
										return a + b;
									}, 0);
									var percentage = ((value / total) * 100).toFixed(1);
									return label + ': ' + value + ' (' + percentage + '%)';
								}
							}
						}
					},
					onClick: function(evt, elements) {
						if (elements.length > 0) {
							var index = elements[0].index;
							var segment = activeSegments[index];
							PDWCHS_RFM.filterBySegment(segment.slug);
						}
					}
				}
			});

			// Update last calculated timestamp
			if (data.last_calculated) {
				$('#pdwchs-rfm-last-updated').text(data.last_calculated);
			}
		},

		/**
		 * Render the legend with segment counts
		 */
		renderLegend: function(data) {
			var legendHtml = '';

			data.segments.forEach(function(segment) {
				legendHtml += '<li class="pdwchs-legend-item" data-segment="' + segment.slug + '">';
				legendHtml += '<span class="pdwchs-legend-color" style="background-color: ' + segment.color + ';"></span>';
				legendHtml += '<span class="pdwchs-legend-icon">' + segment.icon + '</span>';
				legendHtml += '<span class="pdwchs-legend-name">' + segment.name + '</span>';
				legendHtml += '<span class="pdwchs-legend-count">' + segment.count + '</span>';
				legendHtml += '</li>';
			});

			$('#pdwchs-rfm-legend-list').html(legendHtml);

			// Bind click events to legend items
			$('.pdwchs-legend-item').on('click', function() {
				var segment = $(this).data('segment');
				PDWCHS_RFM.filterBySegment(segment);
			});
		},

		/**
		 * Navigate to customers page filtered by segment
		 */
		filterBySegment: function(segment) {
			window.location.href = 'admin.php?page=pd-woo-customers&rfm_segment=' + segment;
		},

		/**
		 * Refresh the chart data
		 */
		refreshData: function() {
			$('.pdwchs-rfm-content').hide();
			$('.pdwchs-rfm-footer').hide();
			$('.pdwchs-rfm-error').hide();
			$('.pdwchs-rfm-loading').show();

			if (this.chart) {
				this.chart.destroy();
				this.chart = null;
			}

			this.loadData();
		},

		/**
		 * Show error message
		 */
		showError: function() {
			$('.pdwchs-rfm-loading').hide();
			$('.pdwchs-rfm-content').hide();
			$('.pdwchs-rfm-error').show();
		},

		/**
		 * Bind recalculate RFM button
		 */
		bindRecalculateButton: function() {
			$(document).on('click', '.pdwchs-recalculate-rfm', function(e) {
				e.preventDefault();

				var $button = $(this);
				var userId = $button.data('user-id');
				var originalText = $button.text();

				$button.prop('disabled', true).text(pdwchs_rfm_vars.calculating_text || 'Calculating...');

				$.ajax({
					url: ajaxurl,
					method: 'POST',
					data: {
						action: 'pdwchs_recalculate_rfm',
						user_id: userId,
						nonce: pdwchs_rfm_vars.nonce
					},
					success: function(response) {
						if (response.success) {
							location.reload(); // Reload to show updated data
						} else {
							alert(response.data.message || 'Failed to recalculate RFM');
							$button.prop('disabled', false).text(originalText);
						}
					},
					error: function() {
						alert('Failed to recalculate RFM. Please try again.');
						$button.prop('disabled', false).text(originalText);
					}
				});
			});
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		PDWCHS_RFM.init();
	});

})(jQuery);
