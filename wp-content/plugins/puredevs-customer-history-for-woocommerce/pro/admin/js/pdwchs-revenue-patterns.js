/* global pdwchs_revenue_patterns_vars, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof pdwchs_revenue_patterns_vars === 'undefined' ) {
		return;
	}

	var vars = pdwchs_revenue_patterns_vars;

	// === Date Range Picker ===
	if ( typeof window.pdwchsInitDateRangePicker === 'undefined' ) {
		window.pdwchsInitDateRangePicker = function ( containerSelector, onApplyCallback ) {
			if ( typeof $.fn.daterangepicker === 'undefined' ) {
				return;
			}

			if ( $( containerSelector ).length === 0 ) {
				return;
			}

			var defaultLabel     = 'Last 30 Days';
			var defaultStartDate = moment().subtract( 29, 'days' ).startOf( 'day' );
			var defaultEndDate   = moment().endOf( 'day' );

			var ranges = {
				'Today':       [ moment().startOf( 'day' ), moment().endOf( 'day' ) ],
				'Last 7 Days': [ moment().subtract( 6, 'days' ).startOf( 'day' ), moment().endOf( 'day' ) ],
				'Last 30 Days': [ moment().subtract( 29, 'days' ).startOf( 'day' ), moment().endOf( 'day' ) ],
				'This Month':  [ moment().startOf( 'month' ), moment().endOf( 'day' ) ],
				'Last Month':  [
					moment().subtract( 1, 'month' ).startOf( 'month' ),
					moment().subtract( 1, 'month' ).endOf( 'month' )
				]
			};

			$( containerSelector ).daterangepicker( {
				autoUpdateInput: true,
				opens:           'left',
				maxDate:         moment(),
				startDate:       defaultStartDate,
				endDate:         defaultEndDate,
				locale:          { format: 'MMM D, YYYY', cancelLabel: 'Clear' },
				ranges:          ranges
			} );

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
				fireCallback(
					picker.startDate.clone().startOf( 'day' ),
					picker.endDate.clone().endOf( 'day' ),
					picker.chosenLabel || 'Custom Range'
				);
			} );

			$( containerSelector ).on( 'cancel.daterangepicker', function ( ev, picker ) {
				$( containerSelector ).val( defaultLabel );
				picker.setStartDate( defaultStartDate.clone() );
				picker.setEndDate( defaultEndDate.clone() );
				fireCallback( defaultStartDate.clone(), defaultEndDate.clone(), defaultLabel );
			} );

			fireCallback( defaultStartDate.clone(), defaultEndDate.clone(), defaultLabel );
		};
	}

	// === Currency helpers ===

	function formatCurrency( value ) {
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

	function formatNumber( value ) {
		var num = parseInt( value, 10 ) || 0;
		return num.toString().replace( /\B(?=(\d{3})+(?!\d))/g, vars.thousand_separator );
	}

	// === Private state ===

	var currentDates = {};
	var activeView   = 'dow';
	var dowChart     = null;
	var hourChart    = null;

	// === Module ===

	window.pdwchsRevenuePatterns = {

		init: function () {
			if ( $( '#pdwchs-patterns-date-range' ).length === 0 ) {
				return;
			}

			pdwchsInitDateRangePicker( '#pdwchs-patterns-date-range', function ( range ) {
				currentDates = {
					startDate: range.startDate,
					endDate:   range.endDate
				};
			} );

			$( '#pdwchs-patterns-apply' ).on( 'click', function () {
				pdwchsRevenuePatterns.fetchData();
			} );

			$( '.pdwchs-view-btn' ).on( 'click', function () {
				activeView = $( this ).data( 'view' );
				$( '.pdwchs-view-btn' ).removeClass( 'pdwchs-view-btn--active' );
				$( this ).addClass( 'pdwchs-view-btn--active' );
				if ( currentDates.startDate ) {
					pdwchsRevenuePatterns.fetchData();
				}
			} );
		},

		fetchData: function () {
			if ( ! currentDates.startDate || ! currentDates.endDate ) {
				pdwchsRevenuePatterns.showEmpty();
				return;
			}

			pdwchsRevenuePatterns.setLoading( true );

			$.ajax( {
				url:  vars.ajaxurl,
				type: 'POST',
				data: {
					action:     'pdwchs_get_revenue_patterns',
					nonce:      vars.nonce,
					start_date: currentDates.startDate.format( 'YYYY-MM-DD' ),
					end_date:   currentDates.endDate.format( 'YYYY-MM-DD' ),
					view:       activeView
				}
			} )
			.done( function ( response ) {
				if ( response && response.success ) {
					$( '#pdwchs-patterns-dow-wrap, #pdwchs-patterns-hour-wrap, #pdwchs-patterns-heatmap-wrap' ).hide();
					$( '#pdwchs-patterns-summary' ).empty();

					var view = response.data.view;
					if ( view === 'dow' ) {
						pdwchsRevenuePatterns.renderDow( response.data.data );
					} else if ( view === 'hour' ) {
						pdwchsRevenuePatterns.renderHour( response.data.data );
					} else if ( view === 'heatmap' ) {
						pdwchsRevenuePatterns.renderHeatmap( response.data.data );
					}

					$( '.pdwchs-patterns-content' ).show();
				} else {
					pdwchsRevenuePatterns.showError();
				}
			} )
			.fail( function () {
				pdwchsRevenuePatterns.showError();
			} )
			.always( function () {
				pdwchsRevenuePatterns.setLoading( false );
			} );
		},

		renderDow: function ( data ) {
			var labels  = vars.i18n.day_labels;
			var revenue = [];
			var d;

			for ( d = 0; d < 7; d++ ) {
				revenue.push( data[ d ] ? ( parseFloat( data[ d ].revenue ) || 0 ) : 0 );
			}

			if ( dowChart ) {
				dowChart.destroy();
				dowChart = null;
			}

			dowChart = new Chart(
				document.getElementById( 'pdwchs-patterns-dow-chart' ),
				{
					type: 'bar',
					data: {
						labels:   labels,
						datasets: [ {
							label:           vars.i18n.revenue_label,
							data:            revenue,
							backgroundColor: '#2271b1'
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
										var idx     = context.dataIndex;
										var dayData = data[ idx ] || {};
										return [
											vars.i18n.revenue_label + ': ' + formatCurrency( dayData.revenue || 0 ),
											vars.i18n.orders_label  + ': ' + formatNumber(  dayData.orders  || 0 )
										];
									}
								}
							}
						},
						scales: {
							x: { grid: { display: false } },
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
				}
			);

			$( '#pdwchs-patterns-dow-wrap' ).show();
			pdwchsRevenuePatterns.renderSummary( data, 'dow' );
		},

		renderHour: function ( data ) {
			var labels  = [];
			var revenue = [];
			var h;

			for ( h = 0; h < 24; h++ ) {
				labels.push( ( h < 10 ? '0' + h : '' + h ) + ':00' );
				revenue.push( data[ h ] ? ( parseFloat( data[ h ].revenue ) || 0 ) : 0 );
			}

			if ( hourChart ) {
				hourChart.destroy();
				hourChart = null;
			}

			hourChart = new Chart(
				document.getElementById( 'pdwchs-patterns-hour-chart' ),
				{
					type: 'bar',
					data: {
						labels:   labels,
						datasets: [ {
							label:           vars.i18n.revenue_label,
							data:            revenue,
							backgroundColor: '#2271b1'
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
										var idx      = context.dataIndex;
										var hourData = data[ idx ] || {};
										return [
											vars.i18n.revenue_label + ': ' + formatCurrency( hourData.revenue || 0 ),
											vars.i18n.orders_label  + ': ' + formatNumber(   hourData.orders  || 0 )
										];
									}
								}
							}
						},
						scales: {
							x: { grid: { display: false } },
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
				}
			);

			$( '#pdwchs-patterns-hour-wrap' ).show();
			pdwchsRevenuePatterns.renderSummary( data, 'hour' );
		},

		renderHeatmap: function ( data ) {
			var maxRevenue = 0;
			var d, h;

			for ( d = 0; d < 7; d++ ) {
				for ( h = 0; h < 24; h++ ) {
					var cellRev = data[ d ] && data[ d ][ h ] ? ( parseFloat( data[ d ][ h ].revenue ) || 0 ) : 0;
					if ( cellRev > maxRevenue ) {
						maxRevenue = cellRev;
					}
				}
			}

			var $grid = $( '#pdwchs-patterns-heatmap' );
			$grid.empty();

			$grid.append(
				$( '<div>' ).addClass( 'pdwchs-patterns-heatmap-cell pdwchs-patterns-heatmap-cell--label' )
			);

			for ( h = 0; h < 24; h++ ) {
				$grid.append(
					$( '<div>' )
						.addClass( 'pdwchs-patterns-heatmap-cell pdwchs-patterns-heatmap-cell--label' )
						.text( h < 10 ? '0' + h : '' + h )
				);
			}

			for ( d = 0; d < 7; d++ ) {
				$grid.append(
					$( '<div>' )
						.addClass( 'pdwchs-patterns-heatmap-cell pdwchs-patterns-heatmap-cell--label' )
						.text( vars.i18n.day_labels[ d ] || '' )
				);

				for ( h = 0; h < 24; h++ ) {
					var cellData = data[ d ] && data[ d ][ h ] ? data[ d ][ h ] : { revenue: 0, orders: 0 };
					var rev      = parseFloat( cellData.revenue ) || 0;
					var orders   = parseInt( cellData.orders, 10 ) || 0;
					var opacity  = maxRevenue > 0 ? Math.max( 0.05, rev / maxRevenue ) : 0.05;

					$grid.append(
						$( '<div>' )
							.addClass( 'pdwchs-patterns-heatmap-cell' )
							.css( 'background', 'rgba(34, 113, 177, ' + opacity + ')' )
							.attr( 'title', formatCurrency( rev ) + ' — ' + orders + ' orders' )
					);
				}
			}

			$( '#pdwchs-patterns-summary' ).empty();
			$( '#pdwchs-patterns-heatmap-wrap' ).show();
		},

		renderSummary: function ( data, view ) {
			var $container = $( '#pdwchs-patterns-summary' );
			$container.empty();

			var bestIdx    = 0;
			var bestRev    = -Infinity;
			var worstIdx   = null;
			var worstRev   = Infinity;
			var hasNonZero = false;
			var keys       = Object.keys( data );

			keys.forEach( function ( k ) {
				var rev = parseFloat( data[ k ].revenue ) || 0;
				var idx = parseInt( k, 10 );
				if ( rev > bestRev ) {
					bestRev = rev;
					bestIdx = idx;
				}
				if ( rev > 0 && rev < worstRev ) {
					worstRev   = rev;
					worstIdx   = idx;
					hasNonZero = true;
				}
			} );

			function formatLabel( idx ) {
				if ( view === 'dow' ) {
					return vars.i18n.day_labels[ idx ] || '';
				}
				var h = String( idx );
				return ( h.length < 2 ? '0' + h : h ) + ':00';
			}

			function buildCard( labelText, valueText, subText ) {
				var $card  = $( '<div>' ).addClass( 'pdwchs-patterns-summary-card' );
				var $label = $( '<div>' ).addClass( 'pdwchs-patterns-summary-card__label' ).text( labelText );
				var $value = $( '<div>' ).addClass( 'pdwchs-patterns-summary-card__value' ).text( valueText );
				var $sub   = $( '<div>' ).addClass( 'pdwchs-patterns-summary-card__sub' ).text( subText );
				$card.append( $label, $value, $sub );
				return $card;
			}

			if ( bestRev !== -Infinity ) {
				$container.append( buildCard(
					vars.i18n.best_label,
					formatLabel( bestIdx ),
					formatCurrency( bestRev )
				) );
			}

			if ( hasNonZero && worstIdx !== null ) {
				$container.append( buildCard(
					vars.i18n.worst_label,
					formatLabel( worstIdx ),
					formatCurrency( worstRev )
				) );
			}
		},

		setLoading: function ( isLoading ) {
			if ( isLoading ) {
				$( '.pdwchs-patterns-empty, .pdwchs-patterns-error, .pdwchs-patterns-content' ).hide();
				$( '.pdwchs-patterns-loading' ).show();
			} else {
				$( '.pdwchs-patterns-loading' ).hide();
			}
		},

		showError: function () {
			$( '.pdwchs-patterns-loading, .pdwchs-patterns-empty, .pdwchs-patterns-content' ).hide();
			$( '.pdwchs-patterns-error' ).show();
		},

		showEmpty: function () {
			$( '.pdwchs-patterns-loading, .pdwchs-patterns-error, .pdwchs-patterns-content' ).hide();
			$( '.pdwchs-patterns-empty' ).show();
		}
	};

	$( document ).ready( function () {
		pdwchsRevenuePatterns.init();
	} );

} )( jQuery );
