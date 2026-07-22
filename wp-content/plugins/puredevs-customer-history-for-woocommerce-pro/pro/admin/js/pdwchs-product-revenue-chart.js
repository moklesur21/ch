( function ( $ ) {
	'use strict';

	if ( typeof pdwchs_product_revenue_vars === 'undefined' ) {
		return;
	}

	// === Date Range Picker (copied from pdwchs-revenue-dashboard.js) ===

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
			$( containerSelector ).val( defaultLabel );
			picker.setStartDate( defaultStartDate.clone() );
			picker.setEndDate( defaultEndDate.clone() );
			fireCallback( defaultStartDate.clone(), defaultEndDate.clone(), defaultLabel );
		} );

		// Fire callback immediately with the default "Last 30 Days" selection.
		fireCallback( defaultStartDate.clone(), defaultEndDate.clone(), defaultLabel );
	}

	if ( typeof window.pdwchsInitDateRangePicker === 'undefined' ) {
		window.pdwchsInitDateRangePicker = pdwchsInitDateRangePicker;
	}

	// === Currency + number formatters (parameterised from pdwchs_product_revenue_vars) ===

	/**
	 * Format a numeric value as currency using pdwchs_product_revenue_vars settings.
	 *
	 * @param {number} value  Raw numeric value from the server.
	 * @return {string}
	 */
	function formatCurrency( value ) {
		var vars      = pdwchs_product_revenue_vars;
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
		return num.toString().replace( /\B(?=(\d{3})+(?!\d))/g, pdwchs_product_revenue_vars.thousand_separator );
	}

	// === Private state ===

	var productRevenueChart = null;
	var currentDates        = {};

	// Fixed color palette for up to 5 datasets.
	var chartColors = [ '#2271b1', '#e05d44', '#16a34a', '#d97706', '#7c3aed' ];

	// === Module ===

	window.pdwchsProductRevenue = {

		init: function () {
			if ( $( '#pdwchs-product-select' ).length === 0 ) {
				return;
			}

			// Show empty state on load; content is hidden until first fetch.
			$( '.pdwchs-product-revenue-empty' ).show();

			// Initialise select2 (SelectWoo) — max 3 selections enforced by the library.
			$( '#pdwchs-product-select' ).select2( {
				maximumSelectionLength: 3,
				placeholder:            pdwchs_product_revenue_vars.i18n.select_placeholder,
				allowClear:             true,
				width:                  '100%'
			} );

			// Wire date range picker — callback stores dates but does NOT auto-fetch.
			pdwchsInitDateRangePicker( '#pdwchs-product-date-range', function ( range ) {
				currentDates = {
					startDate: range.startDate,
					endDate:   range.endDate
				};
			} );

			// Bind Apply button.
			$( '#pdwchs-product-apply' ).on( 'click', function () {
				pdwchsProductRevenue.fetchData();
			} );
		},

		fetchData: function () {
			var productIds = ( $( '#pdwchs-product-select' ).val() || [] ).slice( 0, 3 );

			if ( productIds.length === 0 ) {
				pdwchsProductRevenue.showEmpty();
				return;
			}

			var granularity = $( 'input[name="pdwchs_granularity"]:checked' ).val() || 'day';
			var startDate   = currentDates.startDate ? currentDates.startDate.format( 'YYYY-MM-DD' ) : '';
			var endDate     = currentDates.endDate   ? currentDates.endDate.format( 'YYYY-MM-DD' )   : '';

			pdwchsProductRevenue.setLoading( true );

			$.ajax( {
				url:  pdwchs_product_revenue_vars.ajaxurl,
				type: 'POST',
				data: {
					action:      'pdwchs_get_product_revenue_over_time',
					nonce:       pdwchs_product_revenue_vars.nonce,
					product_ids: productIds,
					start_date:  startDate,
					end_date:    endDate,
					granularity: granularity
				}
			} )
			.done( function ( response ) {
				pdwchsProductRevenue.setLoading( false );
				if ( response && response.success && response.data ) {
					pdwchsProductRevenue.renderChart( response.data.over_time );
					pdwchsProductRevenue.renderSummaryCards( response.data.summary );
					$( '.pdwchs-product-revenue-empty' ).hide();
					$( '.pdwchs-product-revenue-error' ).hide();
					$( '.pdwchs-product-revenue-content' ).show();
				} else {
					pdwchsProductRevenue.showError();
				}
			} )
			.fail( function () {
				pdwchsProductRevenue.setLoading( false );
				pdwchsProductRevenue.showError();
			} );
		},

		renderChart: function ( overTimeData ) {
			if ( productRevenueChart ) {
				productRevenueChart.destroy();
				productRevenueChart = null;
			}

			var productIds = Object.keys( overTimeData );
			if ( productIds.length === 0 ) {
				return;
			}

			// Build labels from the first product's data.
			var firstBuckets = overTimeData[ productIds[ 0 ] ] || [];
			var labels       = firstBuckets.map( function ( b ) { return b.period; } );

			var datasets = productIds.map( function ( productId, index ) {
				var buckets = overTimeData[ productId ] || [];
				var $option = $( '#pdwchs-product-select option[value="' + productId + '"]' );
				var label   = $option.length ? $option.text() : 'Product ' + productId;

				return {
					label:       label,
					data:        buckets.map( function ( b ) { return b.revenue; } ),
					borderColor: chartColors[ index % chartColors.length ],
					fill:        false,
					tension:     0.3,
					pointRadius: labels.length > 60 ? 0 : 3
				};
			} );

			productRevenueChart = new Chart(
				document.getElementById( 'pdwchs-product-revenue-chart' ),
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
							legend: { display: true },
							tooltip: {
								callbacks: {
									label: function ( context ) {
										var datasetIndex = context.datasetIndex;
										var dataIndex    = context.dataIndex;
										var productId    = productIds[ datasetIndex ];
										var buckets      = overTimeData[ productId ] || [];
										var b            = buckets[ dataIndex ] || {};
										return [
											context.dataset.label + ': ' + formatCurrency( b.revenue || 0 ),
											'Units: ' + ( b.units || 0 )
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
				}
			);
		},

		renderSummaryCards: function ( summaryData ) {
			var $container = $( '#pdwchs-product-summary-cards' );
			$container.empty();

			$.each( summaryData, function ( productId, summary ) {
				var $option     = $( '#pdwchs-product-select option[value="' + productId + '"]' );
				var productName = $option.length ? $option.text() : 'Product ' + productId;

				var $card    = $( '<div>' ).addClass( 'pdwchs-product-summary-card' );
				var $nameEl  = $( '<div>' ).addClass( 'pdwchs-product-summary-card__name' ).text( productName );

				var $revMetric = $( '<div>' ).addClass( 'pdwchs-product-summary-metric' );
				$revMetric.append( $( '<span>' ).text( 'Revenue' ) );
				$revMetric.append( $( '<span>' ).addClass( 'pdwchs-product-summary-metric__val' ).text( formatCurrency( summary.revenue ) ) );

				var $unitsMetric = $( '<div>' ).addClass( 'pdwchs-product-summary-metric' );
				$unitsMetric.append( $( '<span>' ).text( 'Units Sold' ) );
				$unitsMetric.append( $( '<span>' ).addClass( 'pdwchs-product-summary-metric__val' ).text( formatNumber( summary.units ) ) );

				var $ordersMetric = $( '<div>' ).addClass( 'pdwchs-product-summary-metric' );
				$ordersMetric.append( $( '<span>' ).text( 'Orders' ) );
				$ordersMetric.append( $( '<span>' ).addClass( 'pdwchs-product-summary-metric__val' ).text( formatNumber( summary.orders ) ) );

				var $aovMetric = $( '<div>' ).addClass( 'pdwchs-product-summary-metric' );
				$aovMetric.append( $( '<span>' ).text( 'AOV' ) );
				$aovMetric.append( $( '<span>' ).addClass( 'pdwchs-product-summary-metric__val' ).text( formatCurrency( summary.aov ) ) );

				$card.append( $nameEl, $revMetric, $unitsMetric, $ordersMetric, $aovMetric );
				$container.append( $card );
			} );
		},

		setLoading: function ( isLoading ) {
			if ( isLoading ) {
				$( '.pdwchs-product-revenue-loading' ).show();
				$( '.pdwchs-product-revenue-content' ).hide();
				$( '.pdwchs-product-revenue-error' ).hide();
				$( '.pdwchs-product-revenue-empty' ).hide();
			} else {
				$( '.pdwchs-product-revenue-loading' ).hide();
			}
		},

		showError: function () {
			$( '.pdwchs-product-revenue-loading' ).hide();
			$( '.pdwchs-product-revenue-content' ).hide();
			$( '.pdwchs-product-revenue-empty' ).hide();
			$( '.pdwchs-product-revenue-error' ).show();
		},

		showEmpty: function () {
			$( '.pdwchs-product-revenue-loading' ).hide();
			$( '.pdwchs-product-revenue-content' ).hide();
			$( '.pdwchs-product-revenue-error' ).hide();
			$( '.pdwchs-product-revenue-empty' ).show();
		}
	};

	$( document ).ready( function () {
		pdwchsProductRevenue.init();
	} );

} )( jQuery );
