/* global pdwchs_funnel_vars, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof pdwchs_funnel_vars === 'undefined' ) {
		return;
	}

	var vars = pdwchs_funnel_vars;

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
				'Today':        [ moment().startOf( 'day' ), moment().endOf( 'day' ) ],
				'Last 7 Days':  [ moment().subtract( 6, 'days' ).startOf( 'day' ), moment().endOf( 'day' ) ],
				'Last 30 Days': [ moment().subtract( 29, 'days' ).startOf( 'day' ), moment().endOf( 'day' ) ],
				'This Month':   [ moment().startOf( 'month' ), moment().endOf( 'day' ) ],
				'Last Month':   [
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

	// === Number helper ===

	function formatNumber( value ) {
		var num = parseInt( value, 10 ) || 0;
		return num.toString().replace( /\B(?=(\d{3})+(?!\d))/g, vars.thousand_separator );
	}

	// === Private state ===

	var currentDates       = {};
	var currentGranularity = 'day';
	var barChart           = null;
	var trendChart         = null;

	// === Module ===

	window.pdwchsAggregateFunnel = {

		init: function () {
			if ( $( '#pdwchs-funnel-date-range' ).length === 0 ) {
				return;
			}

			pdwchsInitDateRangePicker( '#pdwchs-funnel-date-range', function ( range ) {
				currentDates = {
					startDate: range.startDate,
					endDate:   range.endDate
				};
			} );

			$( '#pdwchs-funnel-apply' ).on( 'click', function () {
				pdwchsAggregateFunnel.fetchAll();
			} );

			$( '[name="pdwchs_funnel_granularity"]' ).on( 'change', function () {
				currentGranularity = $( this ).val();
			} );

			pdwchsAggregateFunnel.fetchAll();
		},

		fetchAll: function () {
			if ( ! currentDates.startDate ) {
				pdwchsAggregateFunnel.showEmpty();
				return;
			}

			pdwchsAggregateFunnel.setLoading( true );

			var startDate = currentDates.startDate.format( 'YYYY-MM-DD' );
			var endDate   = currentDates.endDate.format( 'YYYY-MM-DD' );

			$.when(
				$.ajax( {
					url:  vars.ajaxurl,
					type: 'POST',
					data: {
						action:     'pdwchs_get_aggregate_funnel',
						nonce:      vars.nonce,
						start_date: startDate,
						end_date:   endDate
					}
				} ),
				$.ajax( {
					url:  vars.ajaxurl,
					type: 'POST',
					data: {
						action:      'pdwchs_get_funnel_over_time',
						nonce:       vars.nonce,
						start_date:  startDate,
						end_date:    endDate,
						granularity: currentGranularity
					}
				} )
			).done( function ( funnelResult, trendResult ) {
				var funnelResponse = funnelResult[ 0 ];
				var trendResponse  = trendResult[ 0 ];

				if ( funnelResponse && funnelResponse.success && trendResponse && trendResponse.success ) {
					pdwchsAggregateFunnel.renderSnapshot( funnelResponse.data );
					pdwchsAggregateFunnel.renderTrend( trendResponse.data.series );
					pdwchsAggregateFunnel.setLoading( false );
					$( '.pdwchs-funnel-content' ).show();
				} else {
					pdwchsAggregateFunnel.showError();
				}
			} ).fail( function () {
				pdwchsAggregateFunnel.showError();
			} );
		},

		renderSnapshot: function ( data ) {
			if ( barChart ) {
				barChart.destroy();
				barChart = null;
			}

			var labels = [
				vars.i18n.visitors_label,
				vars.i18n.cart_label,
				vars.i18n.checkout_label,
				vars.i18n.purchased_label
			];
			var counts = [
				parseInt( data.visitors,         10 ) || 0,
				parseInt( data.add_to_cart,      10 ) || 0,
				parseInt( data.checkout_started, 10 ) || 0,
				parseInt( data.purchased,        10 ) || 0
			];
			var colors = [ '#2271b1', '#72aee6', '#f0c33c', '#00a32a' ];

			barChart = new Chart(
				document.getElementById( 'pdwchs-funnel-bar-chart' ),
				{
					type: 'bar',
					data: {
						labels:   labels,
						datasets: [ {
							data:            counts,
							backgroundColor: colors
						} ]
					},
					options: {
						indexAxis:           'y',
						responsive:          true,
						maintainAspectRatio: false,
						plugins: {
							legend: { display: false },
							tooltip: {
								callbacks: {
									label: function ( context ) {
										return formatNumber( context.parsed.x );
									}
								}
							}
						}
					}
				}
			);

			var $cards   = $( '#pdwchs-funnel-stat-cards' );
			$cards.empty();

			var dropOffs = data.drop_offs || {};

			function buildCard( label, value ) {
				var $card  = $( '<div>' ).addClass( 'pdwchs-funnel-stat-card' );
				var $label = $( '<div>' ).addClass( 'pdwchs-funnel-stat-card__label' ).text( label );
				var $value = $( '<div>' ).addClass( 'pdwchs-funnel-stat-card__value' ).text( value );
				$card.append( $value, $label );
				return $card;
			}

			$cards.append( buildCard(
				vars.i18n.visitors_label + ' → ' + vars.i18n.cart_label,
				( parseFloat( dropOffs.visitor_to_cart ) || 0 ).toFixed( 1 ) + '%'
			) );
			$cards.append( buildCard(
				vars.i18n.cart_label + ' → ' + vars.i18n.checkout_label,
				( parseFloat( dropOffs.cart_to_checkout ) || 0 ).toFixed( 1 ) + '%'
			) );
			$cards.append( buildCard(
				vars.i18n.checkout_label + ' → ' + vars.i18n.purchased_label,
				( parseFloat( dropOffs.checkout_to_purchase ) || 0 ).toFixed( 1 ) + '%'
			) );
			$cards.append( buildCard(
				vars.i18n.conversion_label,
				( parseFloat( data.overall_conversion ) || 0 ).toFixed( 1 ) + '%'
			) );
		},

		renderTrend: function ( series ) {
			if ( trendChart ) {
				trendChart.destroy();
				trendChart = null;
			}

			var labels  = series.map( function ( row ) { return row.period; } );
			var colors  = [ '#2271b1', '#72aee6', '#f0c33c', '#00a32a' ];
			var stages  = [
				{ key: 'visitors',         label: vars.i18n.visitors_label },
				{ key: 'add_to_cart',      label: vars.i18n.cart_label },
				{ key: 'checkout_started', label: vars.i18n.checkout_label },
				{ key: 'purchased',        label: vars.i18n.purchased_label }
			];

			var datasets = stages.map( function ( stage, idx ) {
				return {
					label:           stage.label,
					data:            series.map( function ( row ) { return parseInt( row[ stage.key ], 10 ) || 0; } ),
					borderColor:     colors[ idx ],
					backgroundColor: colors[ idx ],
					fill:            false,
					tension:         0.1
				};
			} );

			trendChart = new Chart(
				document.getElementById( 'pdwchs-funnel-trend-chart' ),
				{
					type: 'line',
					data: {
						labels:   labels,
						datasets: datasets
					},
					options: {
						responsive:          true,
						maintainAspectRatio: false,
						plugins: {
							legend: { display: true }
						},
						scales: {
							y: {
								beginAtZero: true,
								min:         0,
								ticks:       { precision: 0 }
							}
						}
					}
				}
			);
		},

		setLoading: function ( isLoading ) {
			if ( isLoading ) {
				$( '.pdwchs-funnel-empty, .pdwchs-funnel-error, .pdwchs-funnel-content' ).hide();
				$( '.pdwchs-funnel-loading' ).show();
			} else {
				$( '.pdwchs-funnel-loading' ).hide();
			}
		},

		showError: function () {
			$( '.pdwchs-funnel-loading, .pdwchs-funnel-empty, .pdwchs-funnel-content' ).hide();
			$( '.pdwchs-funnel-error' ).show();
		},

		showEmpty: function () {
			$( '.pdwchs-funnel-loading, .pdwchs-funnel-error, .pdwchs-funnel-content' ).hide();
			$( '.pdwchs-funnel-empty' ).show();
		}
	};

	$( document ).ready( function () {
		pdwchsAggregateFunnel.init();
	} );

} )( jQuery );
