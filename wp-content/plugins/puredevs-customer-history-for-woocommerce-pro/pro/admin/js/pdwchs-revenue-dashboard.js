( function ( $ ) {
	'use strict';

	// === R1-03: Date Range Picker Initialization ===

	/**
	 * Attach and configure a daterangepicker instance to any input element.
	 *
	 * @param {string}   containerSelector  jQuery selector for the input element.
	 * @param {function} onApplyCallback    Called on every date selection (apply event).
	 *                                      Receives one object: { startDate, endDate, label,
	 *                                      comparisonStartDate, comparisonEndDate }.
	 */
	function pdwchsInitDateRangePicker( containerSelector, onApplyCallback ) {
		if ( typeof $.fn.daterangepicker === 'undefined' ) {
			return;
		}

		if ( $( containerSelector ).length === 0 ) {
			return;
		}

		var defaultLabel      = 'Last 30 Days';
		var defaultStartDate  = moment().subtract( 29, 'days' ).startOf( 'day' );
		var defaultEndDate    = moment().endOf( 'day' );

		var ranges = {
			'Today': [
				moment().startOf( 'day' ),
				moment().endOf( 'day' )
			],
			'Last 7 Days': [
				moment().subtract( 6, 'days' ).startOf( 'day' ),
				moment().endOf( 'day' )
			],
			'Last 30 Days': [
				moment().subtract( 29, 'days' ).startOf( 'day' ),
				moment().endOf( 'day' )
			],
			'This Month': [
				moment().startOf( 'month' ),
				moment().endOf( 'day' )
			],
			'Last Month': [
				moment().subtract( 1, 'month' ).startOf( 'month' ),
				moment().subtract( 1, 'month' ).endOf( 'month' )
			]
		};

		$( containerSelector ).daterangepicker(
			{
				autoUpdateInput: true,
				opens:           'left',
				maxDate:         moment(),
				startDate:       defaultStartDate,
				endDate:         defaultEndDate,
				locale: {
					format:      'MMM D, YYYY',
					cancelLabel: 'Clear'
				},
				ranges: ranges
			}
		);

		/**
		 * Build and fire the callback payload for a given date range.
		 *
		 * @param {moment} startDate  Start of selected range (start of day).
		 * @param {moment} endDate    End of selected range (end of day).
		 * @param {string} label      Preset label or 'Custom Range'.
		 */
		function fireCallback( startDate, endDate, label ) {
			var rangeDays           = endDate.diff( startDate, 'days' ) + 1;
			var comparisonEndDate   = startDate.clone().subtract( 1, 'day' ).endOf( 'day' );
			var comparisonStartDate = comparisonEndDate.clone().subtract( rangeDays - 1, 'days' ).startOf( 'day' );

			onApplyCallback( {
				startDate:           startDate,
				endDate:             endDate,
				label:               label,
				comparisonStartDate: comparisonStartDate,
				comparisonEndDate:   comparisonEndDate
			} );
		}

		$( containerSelector ).on( 'apply.daterangepicker', function ( ev, picker ) {
			var label = picker.chosenLabel || 'Custom Range';
			fireCallback(
				picker.startDate.clone().startOf( 'day' ),
				picker.endDate.clone().endOf( 'day' ),
				label
			);
		} );

		$( containerSelector ).on( 'cancel.daterangepicker', function ( ev, picker ) {
			// Reset display text to default label and re-fire with Last 30 Days.
			$( containerSelector ).val( defaultLabel );
			picker.setStartDate( defaultStartDate.clone() );
			picker.setEndDate( defaultEndDate.clone() );
			fireCallback( defaultStartDate.clone(), defaultEndDate.clone(), defaultLabel );
		} );

		// Fire callback immediately with the default "Last 30 Days" selection.
		fireCallback( defaultStartDate.clone(), defaultEndDate.clone(), defaultLabel );
	}

	// Expose globally so R1-05 and callers can use it.
	window.pdwchsInitDateRangePicker = pdwchsInitDateRangePicker;

	// === R1-05: Revenue Chart JS + Card Population ===

	// --- Private state ---

	var currentStartDate   = null;
	var currentEndDate     = null;
	var currentGranularity = 'daily';
	var revenueChart       = null;

	// --- Private helpers ---

	/**
	 * Format a numeric value as currency using pdwchs_revenue_vars settings.
	 *
	 * @param {number} value  Raw numeric value from the server.
	 * @return {string}
	 */
	function formatCurrency( value ) {
		var vars      = pdwchs_revenue_vars;
		var num       = parseFloat( value ) || 0;
		var fixed     = num.toFixed( 2 );
		var parts     = fixed.split( '.' );
		var intPart   = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, vars.thousand_separator );
		var formatted = intPart + vars.decimal_separator + parts[ 1 ];

		switch ( vars.currency_position ) {
			case 'left':        return vars.currency_symbol + formatted;
			case 'right':       return formatted + vars.currency_symbol;
			case 'left_space':  return vars.currency_symbol + ' ' + formatted;
			case 'right_space': return formatted + ' ' + vars.currency_symbol;
			default:            return vars.currency_symbol + formatted;
		}
	}

	/**
	 * Format an integer value with thousand separators and no decimals.
	 *
	 * @param {number} value  Raw integer from the server.
	 * @return {string}
	 */
	function formatNumber( value ) {
		var num = parseInt( value, 10 ) || 0;
		return num.toString().replace( /\B(?=(\d{3})+(?!\d))/g, pdwchs_revenue_vars.thousand_separator );
	}

	// --- Module ---

	var pdwchsRevenueDashboard = {

		/**
		 * Initialize the revenue dashboard widget.
		 * Guards: exits if the widget is not in the DOM or vars are missing.
		 */
		init: function () {
			if ( $( '.pdwchs-revenue-widget' ).length === 0 ) {
				return;
			}

			if ( typeof pdwchs_revenue_vars === 'undefined' ) {
				return;
			}

			// Wire date range picker — on each apply, store dates and refresh all data.
			pdwchsInitDateRangePicker( '#pdwchs-revenue-date-picker', function ( range ) {
				currentStartDate = range.startDate;
				currentEndDate   = range.endDate;
				pdwchsRevenueDashboard.fetchAll( currentStartDate, currentEndDate );
			} );

			// Wire granularity toggle — only re-fetches the chart, not cards or split.
			$( document ).on( 'click', '.pdwchs-granularity-btn', function () {
				var $btn = $( this );
				currentGranularity = $btn.data( 'granularity' );
				$( '.pdwchs-granularity-btn' ).removeClass( 'pdwchs-granularity-btn--active' );
				$btn.addClass( 'pdwchs-granularity-btn--active' );
				pdwchsRevenueDashboard.fetchOverTime( currentStartDate, currentEndDate, currentGranularity );
			} );
		},

		/**
		 * Kick off all three AJAX calls in parallel.
		 * Shows loading state before, shows content after all succeed.
		 * Shows error if any call fails.
		 *
		 * @param {moment} startDate
		 * @param {moment} endDate
		 */
		fetchAll: function ( startDate, endDate ) {
			pdwchsRevenueDashboard.setLoading( true );

			$.when(
				pdwchsRevenueDashboard.fetchOverview( startDate, endDate ),
				pdwchsRevenueDashboard.fetchOverTime( startDate, endDate, currentGranularity ),
				pdwchsRevenueDashboard.fetchSplit( startDate, endDate )
			).then(
				function () {
					pdwchsRevenueDashboard.setLoading( false );
					$( '.pdwchs-revenue-content' ).show();
				},
				function () {
					pdwchsRevenueDashboard.showError();
				}
			);
		},

		/**
		 * POST to pdwchs_get_revenue_overview. On success, calls populateCards().
		 *
		 * @param {moment} startDate
		 * @param {moment} endDate
		 * @return {jqXHR}
		 */
		fetchOverview: function ( startDate, endDate ) {
			var xhr = $.ajax( {
				url:  pdwchs_revenue_vars.ajaxurl,
				type: 'POST',
				data: {
					action:     'pdwchs_get_revenue_overview',
					nonce:      pdwchs_revenue_vars.nonce,
					start_date: startDate.format( 'YYYY-MM-DD' ),
					end_date:   endDate.format( 'YYYY-MM-DD' )
				}
			} );

			xhr.done( function ( response ) {
				if ( response && response.success && response.data ) {
					pdwchsRevenueDashboard.populateCards( response.data );
				}
			} );

			return xhr;
		},

		/**
		 * POST to pdwchs_get_revenue_over_time. On success, calls renderChart().
		 *
		 * @param {moment} startDate
		 * @param {moment} endDate
		 * @param {string} granularity  'daily' | 'weekly' | 'monthly'
		 * @return {jqXHR}
		 */
		fetchOverTime: function ( startDate, endDate, granularity ) {
			var xhr = $.ajax( {
				url:  pdwchs_revenue_vars.ajaxurl,
				type: 'POST',
				data: {
					action:      'pdwchs_get_revenue_over_time',
					nonce:       pdwchs_revenue_vars.nonce,
					start_date:  startDate.format( 'YYYY-MM-DD' ),
					end_date:    endDate.format( 'YYYY-MM-DD' ),
					granularity: granularity
				}
			} );

			xhr.done( function ( response ) {
				if ( response && response.success && response.data ) {
					pdwchsRevenueDashboard.renderChart( response.data );
				}
			} );

			return xhr;
		},

		/**
		 * POST to pdwchs_get_new_vs_returning_revenue. On success, calls populateSplit().
		 *
		 * @param {moment} startDate
		 * @param {moment} endDate
		 * @return {jqXHR}
		 */
		fetchSplit: function ( startDate, endDate ) {
			var xhr = $.ajax( {
				url:  pdwchs_revenue_vars.ajaxurl,
				type: 'POST',
				data: {
					action:     'pdwchs_get_new_vs_returning_revenue',
					nonce:      pdwchs_revenue_vars.nonce,
					start_date: startDate.format( 'YYYY-MM-DD' ),
					end_date:   endDate.format( 'YYYY-MM-DD' )
				}
			} );

			xhr.done( function ( response ) {
				if ( response && response.success && response.data ) {
					pdwchsRevenueDashboard.populateSplit( response.data );
				}
			} );

			return xhr;
		},

		/**
		 * Write formatted values into each of the four KPI cards.
		 *
		 * @param {Object} data  response.data from pdwchs_get_revenue_overview.
		 */
		populateCards: function ( data ) {
			var comparison = data.comparison || {};

			var metrics = {
				net_revenue:     {
					value:  formatCurrency( data.net_revenue ),
					change: comparison.net_revenue_change !== undefined ? comparison.net_revenue_change : null
				},
				total_orders:    {
					value:  formatNumber( data.total_orders ),
					change: comparison.total_orders_change !== undefined ? comparison.total_orders_change : null
				},
				aov:             {
					value:  formatCurrency( data.aov ),
					change: comparison.aov_change !== undefined ? comparison.aov_change : null
				},
				total_customers: {
					value:  formatNumber( data.total_customers ),
					change: comparison.total_customers_change !== undefined ? comparison.total_customers_change : null
				}
			};

			$.each( metrics, function ( metric, info ) {
				var $card = $( '[data-metric="' + metric + '"]' );
				$card.find( '.pdwchs-revenue-card-value' ).text( info.value );
				pdwchsRevenueDashboard.renderChangeBadge(
					$card.find( '.pdwchs-revenue-card-change' ),
					info.change
				);
			} );
		},

		/**
		 * Render the change-percent badge on a card element.
		 *
		 * @param {jQuery} $el           The `.pdwchs-revenue-card-change` element.
		 * @param {number|null} changePercent  Percent change value, or null for no data.
		 */
		renderChangeBadge: function ( $el, changePercent ) {
			$el.removeClass( 'pdwchs-change-up pdwchs-change-down pdwchs-change-neutral' );

			if ( changePercent === null || typeof changePercent === 'undefined' ) {
				$el.text( '\u2014' );
			} else if ( changePercent > 0 ) {
				$el.addClass( 'pdwchs-change-up' ).text( '\u25b2 ' + changePercent + '%' );
			} else if ( changePercent < 0 ) {
				$el.addClass( 'pdwchs-change-down' ).text( '\u25bc ' + Math.abs( changePercent ) + '%' );
			} else {
				$el.addClass( 'pdwchs-change-neutral' ).text( '\u2192 0%' );
			}
		},

		/**
		 * Format a value as currency. Exposed on module for testability.
		 *
		 * @param {number} value
		 * @return {string}
		 */
		formatCurrency: function ( value ) {
			return formatCurrency( value );
		},

		/**
		 * Format an integer value. Exposed on module for testability.
		 *
		 * @param {number} value
		 * @return {string}
		 */
		formatNumber: function ( value ) {
			return formatNumber( value );
		},

		/**
		 * Destroy any existing Chart.js instance and render a new line chart.
		 *
		 * @param {Object} data  response.data from pdwchs_get_revenue_over_time.
		 */
		renderChart: function ( data ) {
			if ( revenueChart ) {
				revenueChart.destroy();
				revenueChart = null;
			}

			var buckets = data.buckets || [];

			revenueChart = new Chart( document.getElementById( 'pdwchs-revenue-chart' ), {
				type: 'line',
				data: {
					labels:   buckets.map( function ( b ) { return b.date; } ),
					datasets: [ {
						label:            'Net Revenue',
						data:             buckets.map( function ( b ) { return b.net_revenue; } ),
						borderColor:      '#2271b1',
						backgroundColor:  'rgba(34, 113, 177, 0.08)',
						fill:             true,
						tension:          0.3,
						pointRadius:      buckets.length > 60 ? 0 : 3,
						pointHoverRadius: 5
					} ]
				},
				options: {
					responsive:          true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false },
						tooltip: {
							callbacks: {
								label: function ( context ) {
									var i = context.dataIndex;
									var b = buckets[ i ];
									return [
										'Net: '     + formatCurrency( b.net_revenue ),
										'Gross: '   + formatCurrency( b.gross_revenue ),
										'Refunds: ' + formatCurrency( b.refunds ),
										'Orders: '  + b.order_count
									];
								}
							}
						}
					},
					scales: {
						x: {
							grid:  { display: false },
							ticks: { maxTicksLimit: 10 }
						},
						y: {
							beginAtZero: true,
							ticks: {
								callback: function ( value ) {
									return formatCurrency( value );
								}
							}
						}
					}
				}
			} );
		},

		/**
		 * Populate the new/returning/guest split cards.
		 *
		 * @param {Object} data  response.data from pdwchs_get_new_vs_returning_revenue.
		 */
		populateSplit: function ( data ) {
			$( '.pdwchs-revenue-split-new .pdwchs-revenue-split-value' )
				.text( formatCurrency( data.new.revenue ) );
			$( '.pdwchs-revenue-split-new .pdwchs-revenue-split-orders' )
				.text( data.new.orders + ' orders' );

			$( '.pdwchs-revenue-split-returning .pdwchs-revenue-split-value' )
				.text( formatCurrency( data.returning.revenue ) );
			$( '.pdwchs-revenue-split-returning .pdwchs-revenue-split-orders' )
				.text( data.returning.orders + ' orders' );

			$( '.pdwchs-revenue-split-guest .pdwchs-revenue-split-value' )
				.text( formatCurrency( data.guest.revenue ) );
			$( '.pdwchs-revenue-split-guest .pdwchs-revenue-split-orders' )
				.text( data.guest.orders + ' orders' );
		},

		/**
		 * Toggle the loading spinner and content/error visibility.
		 *
		 * @param {boolean} isLoading
		 */
		setLoading: function ( isLoading ) {
			if ( isLoading ) {
				$( '.pdwchs-revenue-loading' ).show();
				$( '.pdwchs-revenue-content' ).hide();
				$( '.pdwchs-revenue-error' ).hide();
			} else {
				$( '.pdwchs-revenue-loading' ).hide();
			}
		},

		/**
		 * Show the error state, hiding loading and content.
		 */
		showError: function () {
			$( '.pdwchs-revenue-loading' ).hide();
			$( '.pdwchs-revenue-content' ).hide();
			$( '.pdwchs-revenue-error' ).show();
		}
	};

	// Expose on window for testability.
	window.pdwchsRevenueDashboard = pdwchsRevenueDashboard;

	$( document ).ready( function () {
		pdwchsRevenueDashboard.init();
	} );

} )( jQuery );

// === Data Integrity Module (R1-32) ===

( function ( $ ) {
	'use strict';

	// Module-level cache state
	var lastDetails      = null;
	var lastDetailsStart = null;
	var lastDetailsEnd   = null;

	function renderBadge( data ) {
		var i18n   = pdwchs_revenue_vars.integrity_i18n;
		var $badge = $( '#pdwchs-data-integrity-badge' );
		if ( ! $badge.length ) { return; }

		$badge.removeClass(
			'pdwchs-integrity-loading pdwchs-integrity-verified ' +
			'pdwchs-integrity-mismatch--low pdwchs-integrity-mismatch--high'
		);
		$badge.find( '.pdwchs-integrity-view-details' ).remove();

		if ( data.is_verified ) {
			$badge.addClass( 'pdwchs-integrity-verified' );
			$badge.attr( 'title',
				'Δ ' + data.diff_pct.gross_revenue + '% / ' +
				data.diff_pct.net_revenue + '% (threshold ' + data.threshold_pct + '%)'
			);
			$badge.find( '.pdwchs-integrity-label' ).text( i18n.verified );
		} else {
			var cls = data.severity === 'high'
				? 'pdwchs-integrity-mismatch--high'
				: 'pdwchs-integrity-mismatch--low';
			$badge.addClass( cls );
			$badge.find( '.pdwchs-integrity-label' ).text( i18n.mismatch );
			$badge.append(
				$( '<button type="button" class="pdwchs-integrity-view-details"></button>' )
					.text( i18n.view_details )
			);
		}
	}

	function runCheck( startDate, endDate ) {
		var $badge = $( '#pdwchs-data-integrity-badge' );
		if ( ! $badge.length ) { return; }

		$badge.removeClass(
			'pdwchs-integrity-verified pdwchs-integrity-mismatch--low ' +
			'pdwchs-integrity-mismatch--high'
		).addClass( 'pdwchs-integrity-loading' );
		$badge.find( '.pdwchs-integrity-label' )
		      .text( pdwchs_revenue_vars.integrity_i18n.verifying );
		$badge.find( '.pdwchs-integrity-view-details' ).remove();

		$( '#pdwchs-data-integrity-details' ).prop( 'hidden', true );
		lastDetails = lastDetailsStart = lastDetailsEnd = null;

		$.ajax( {
			url:  pdwchs_revenue_vars.ajaxurl,
			type: 'POST',
			data: {
				action:     'pdwchs_run_data_integrity_check',
				nonce:      pdwchs_revenue_vars.data_integrity_nonce,
				start_date: startDate,
				end_date:   endDate
			}
		} ).done( function ( response ) {
			if ( response && response.success && response.data ) {
				renderBadge( response.data );
			} else {
				$badge.find( '.pdwchs-integrity-label' )
				      .text( pdwchs_revenue_vars.integrity_i18n.fetch_error );
			}
		} ).fail( function () {
			$badge.find( '.pdwchs-integrity-label' )
			      .text( pdwchs_revenue_vars.integrity_i18n.fetch_error );
		} );
	}

	function populateTable( tableId, rows, bucketType ) {
		var $tbody = $( '#' + tableId + ' tbody' );
		$tbody.empty();
		if ( ! rows || rows.length === 0 ) { return; }

		$.each( rows, function ( i, row ) {
			var $tr   = $( '<tr>' );
			var $link = $( '<a>' )
				.attr( 'href', row.edit_url )
				.attr( 'target', '_blank' )
				.text( '#' + row.order_id );

			$tr.append( $( '<td>' ).append( $link ) );

			if ( bucketType === 'unusual' ) {
				$tr.append( $( '<td>' ).text( row.status ) );
				$tr.append( $( '<td>' ).text( row.total ) );
			} else if ( bucketType === 'partial' ) {
				$tr.append( $( '<td>' ).text( row.order_total ) );
				$tr.append( $( '<td>' ).text( row.refunded + ' (' + row.pct + '%)' ) );
			} else if ( bucketType === 'currency' ) {
				$tr.append( $( '<td>' ).text( row.currency ) );
				$tr.append( $( '<td>' ).text( row.total ) );
			}

			$tr.append( $( '<td>' ).text( row.date ) );
			$tbody.append( $tr );
		} );
	}

	function renderDetails( data ) {
		var $panel = $( '#pdwchs-data-integrity-details' );
		$panel.find( '.pdwchs-integrity-details-loading' ).prop( 'hidden', true );
		$panel.find( '.pdwchs-integrity-details-content' ).prop( 'hidden', false );

		populateTable( 'pdwchs-integrity-unusual-table',  data.unusual_statuses,    'unusual' );
		populateTable( 'pdwchs-integrity-partial-table',  data.partial_refunds,     'partial' );
		populateTable( 'pdwchs-integrity-currency-table', data.currency_mismatches, 'currency' );

		var hasAny = data.total_flagged > 0;
		$panel.find( '.pdwchs-integrity-no-flagged' ).prop( 'hidden', hasAny );
		$panel.find( '.pdwchs-integrity-table' ).toggle( hasAny );
	}

	function showDetails( startDate, endDate ) {
		var $panel = $( '#pdwchs-data-integrity-details' );
		if ( ! $panel.length ) { return; }

		// Toggle off if same range already visible
		if ( ! $panel.prop( 'hidden' ) &&
		     lastDetailsStart === startDate && lastDetailsEnd === endDate ) {
			$panel.prop( 'hidden', true );
			$( '.pdwchs-integrity-view-details' )
				.text( pdwchs_revenue_vars.integrity_i18n.view_details );
			return;
		}

		$panel.prop( 'hidden', false );
		$panel.find( '.pdwchs-integrity-details-loading' ).prop( 'hidden', false );
		$panel.find( '.pdwchs-integrity-details-content' ).prop( 'hidden', true );
		$( '.pdwchs-integrity-view-details' )
			.text( pdwchs_revenue_vars.integrity_i18n.hide_details );

		// Serve from cache if available
		if ( lastDetails && lastDetailsStart === startDate && lastDetailsEnd === endDate ) {
			renderDetails( lastDetails );
			return;
		}

		$.ajax( {
			url:  pdwchs_revenue_vars.ajaxurl,
			type: 'POST',
			data: {
				action:     'pdwchs_get_data_integrity_details',
				nonce:      pdwchs_revenue_vars.data_integrity_nonce,
				start_date: startDate,
				end_date:   endDate,
				limit:      100
			}
		} ).done( function ( response ) {
			if ( response && response.success && response.data ) {
				lastDetails      = response.data;
				lastDetailsStart = startDate;
				lastDetailsEnd   = endDate;
				renderDetails( response.data );
			} else {
				$panel.find( '.pdwchs-integrity-details-loading' ).prop( 'hidden', true );
			}
		} ).fail( function () {
			$panel.find( '.pdwchs-integrity-details-loading' ).prop( 'hidden', true );
		} );
	}

	$( document ).ready( function () {

		// Guard: nonce missing — module disabled
		if ( typeof pdwchs_revenue_vars === 'undefined' ||
		     ! pdwchs_revenue_vars.data_integrity_nonce ) {
			if ( window.console && window.console.warn ) {
				window.console.warn(
					'pdwchsDataIntegrity: data_integrity_nonce not found — module disabled.'
				);
			}
			return;
		}

		// Wrap fetchAll so every future date-range change also triggers the integrity check.
		// The dashboard's own $(document).ready already fired and called init(), so we wrap
		// the already-live fetchAll function on the exposed window reference.
		if ( window.pdwchsRevenueDashboard && window.pdwchsRevenueDashboard.fetchAll ) {
			var _orig = pdwchsRevenueDashboard.fetchAll.bind( pdwchsRevenueDashboard );
			pdwchsRevenueDashboard.fetchAll = function ( startDate, endDate ) {
				_orig( startDate, endDate );
				runCheck( startDate.format( 'YYYY-MM-DD' ), endDate.format( 'YYYY-MM-DD' ) );
			};
		}

		// Trigger check for the initial date range already applied during init().
		var $picker = $( '#pdwchs-revenue-date-picker' );
		if ( $picker.length && $picker.data( 'daterangepicker' ) ) {
			var drp = $picker.data( 'daterangepicker' );
			runCheck(
				drp.startDate.format( 'YYYY-MM-DD' ),
				drp.endDate.format( 'YYYY-MM-DD' )
			);
		}

		// "View Details" — delegated because the button is inserted by JS after page load.
		$( document ).on( 'click', '.pdwchs-integrity-view-details', function () {
			var $p = $( '#pdwchs-revenue-date-picker' );
			if ( $p.length && $p.data( 'daterangepicker' ) ) {
				var d = $p.data( 'daterangepicker' );
				showDetails( d.startDate.format( 'YYYY-MM-DD' ), d.endDate.format( 'YYYY-MM-DD' ) );
			}
		} );

	} );

} )( jQuery );
