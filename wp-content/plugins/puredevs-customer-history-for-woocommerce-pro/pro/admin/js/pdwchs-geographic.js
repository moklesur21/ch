/* global pdwchs_geographic_vars, jQuery, Chart */
/**
 * Geographic Performance — the country comparison layer.
 *
 * A layer over a page that is already complete without it. R15-21A's table, its sort and its range
 * form are server-rendered and untouched here; this file only repaints the two comparison panels
 * and the chart above that table.
 *
 * The first paint costs no network request: the whole range is localized in
 * pdwchs_geographic_vars.rows, and the panels are already server-rendered from the same numbers.
 * The endpoint is used for subsequent switches only.
 *
 * @since 3.1.1
 */
( function ( $ ) {
	'use strict';

	if ( typeof pdwchs_geographic_vars === 'undefined' ) {
		return;
	}

	var vars = pdwchs_geographic_vars;

	/**
	 * The four metrics, in panel and chart order. Keys index both a payload row and its display
	 * strings, and match the data-metric attributes the template rendered.
	 */
	var METRICS = [ 'net_sales', 'orders', 'items', 'unique_products' ];

	/** The live Chart.js instance, or null. Destroyed and recreated on every repaint. */
	var compareChart = null;

	var pdwchsGeographic = {

		init: function () {
			// No canvas means the template rendered no comparison block — a store selling to fewer
			// than two countries. Nothing to draw and nothing to bind.
			if ( $( '#pdwchs-geo-compare-chart' ).length === 0 ) {
				return;
			}

			var rowA = pdwchsGeographic.findRow( $( '#pdwchs-geo-compare-a' ).val() );
			var rowB = pdwchsGeographic.findRow( $( '#pdwchs-geo-compare-b' ).val() );

			if ( ! rowA || ! rowB ) {
				return;
			}

			// The panels are already correct — the server rendered this same pair. Only the chart
			// is missing.
			pdwchsGeographic.renderChart( rowA, rowB );

			$( '#pdwchs-geo-compare-a, #pdwchs-geo-compare-b' ).on( 'change', function () {
				pdwchsGeographic.request();
			} );
		},

		/**
		 * A localized row by country code.
		 *
		 * '' is a real code here — the no-billing-country bucket — so the comparison is strict and
		 * a missing selection returns null rather than falling through to the first row.
		 *
		 * @param {string} code  ISO-2 code, or '' for Unknown.
		 * @return {Object|null}
		 */
		findRow: function ( code ) {
			var i;

			if ( typeof code !== 'string' ) {
				return null;
			}

			for ( i = 0; i < vars.rows.length; i++ ) {
				if ( vars.rows[ i ].country_code === code ) {
					return vars.rows[ i ];
				}
			}

			return null;
		},

		/**
		 * Fetch the selected pair and repaint. The range travels with the request because the
		 * numbers are range-specific and the localized dates are the ones on screen.
		 */
		request: function () {
			pdwchsGeographic.setLoading( true );

			$.ajax( {
				url:  vars.ajaxurl,
				type: 'POST',
				data: {
					action:     'pdwchs_geographic_compare',
					nonce:      vars.nonce,
					start_date: vars.startDate,
					end_date:   vars.endDate,
					country_a:  $( '#pdwchs-geo-compare-a' ).val(),
					country_b:  $( '#pdwchs-geo-compare-b' ).val()
				}
			} )
			.done( function ( response ) {
				if ( ! response || ! response.success || ! response.data || ! response.data.a || ! response.data.b ) {
					pdwchsGeographic.showError();
					return;
				}

				pdwchsGeographic.paintPanel( 'a', response.data.a );
				pdwchsGeographic.paintPanel( 'b', response.data.b );
				pdwchsGeographic.renderChart( response.data.a, response.data.b );

				pdwchsGeographic.setLoading( false );
				$( '.pdwchs-geo-compare-content' ).show();
			} )
			.fail( function () {
				pdwchsGeographic.showError();
			} );
		},

		/**
		 * Write one country's four values into its panel.
		 *
		 * Everything goes in with .text(), because these values arrived over HTTP. The single
		 * exception is net_sales: WooCommerce's wc_price() output is markup by design, so the
		 * server sends it already run through wp_kses_post() and it is the one — and only — field
		 * written with jQuery's markup setter below. Its plain-text twin (display.net_sales_text)
		 * is what the chart tooltip uses.
		 *
		 * @param {string} side  'a' or 'b'.
		 * @param {Object} row   A decorated row from the localize payload or the endpoint.
		 */
		paintPanel: function ( side, row ) {
			var $panel = $( '.pdwchs-geo-compare-panel[data-side="' + side + '"]' );
			var i;
			var metric;
			var $value;

			$panel.find( '.pdwchs-geo-compare-country' ).text( row.country_name );

			for ( i = 0; i < METRICS.length; i++ ) {
				metric = METRICS[ i ];
				$value = $panel.find( '.pdwchs-geo-compare-value[data-metric="' + metric + '"]' );

				if ( metric === 'net_sales' ) {
					$value.html( row.display.net_sales );
				} else {
					$value.text( row.display[ metric ] );
				}
			}
		},

		/**
		 * One metric as a percentage of the larger of the two values.
		 *
		 * Net sales are money and the other three are counts, so the four cannot share one linear
		 * axis meaningfully and a grouped bar chart gives every group one shared scale. Normalising
		 * per metric is the honest resolution: the axis says so, and the exact figures are in the
		 * panels.
		 *
		 * The denominator is the larger magnitude, so a fully refunded country (negative net sales)
		 * still plots. Both values zero gives zero, never NaN; the same country on both sides gives
		 * two identical bars, which is a correct answer to a coherent question.
		 *
		 * @param {number} value  This country's value.
		 * @param {number} other  The other country's value.
		 * @return {number} -100 to 100.
		 */
		percentOf: function ( value, other ) {
			var mine  = parseFloat( value ) || 0;
			var yours = parseFloat( other ) || 0;
			var scale = Math.max( Math.abs( mine ), Math.abs( yours ) );

			if ( scale === 0 ) {
				return 0;
			}

			return ( mine / scale ) * 100;
		},

		/**
		 * Destroy any existing Chart.js instance and draw a new grouped bar chart.
		 *
		 * Destroy-then-create, never update-in-place: Chart.js keeps the canvas registered against
		 * the old instance, and re-instantiating over a live one leaks it and warns in the console.
		 *
		 * @param {Object} rowA  Decorated row for side A.
		 * @param {Object} rowB  Decorated row for side B.
		 */
		renderChart: function ( rowA, rowB ) {
			var labels = [ vars.i18n.netSales, vars.i18n.orders, vars.i18n.items, vars.i18n.uniqueProducts ];
			var i;
			var metric;
			var dataA = [];
			var dataB = [];

			for ( i = 0; i < METRICS.length; i++ ) {
				metric = METRICS[ i ];
				dataA.push( pdwchsGeographic.percentOf( rowA[ metric ], rowB[ metric ] ) );
				dataB.push( pdwchsGeographic.percentOf( rowB[ metric ], rowA[ metric ] ) );
			}

			if ( compareChart ) {
				compareChart.destroy();
				compareChart = null;
			}

			compareChart = new Chart( document.getElementById( 'pdwchs-geo-compare-chart' ), {
				type: 'bar',
				data: {
					labels: labels,
					datasets: [
						{
							label:           rowA.country_name,
							data:            dataA,
							backgroundColor: 'rgba(34, 113, 177, 0.75)',
							borderColor:     '#2271b1',
							borderWidth:     1
						},
						{
							label:           rowB.country_name,
							data:            dataB,
							backgroundColor: 'rgba(219, 116, 42, 0.75)',
							borderColor:     '#db742a',
							borderWidth:     1
						}
					]
				},
				options: {
					responsive:          true,
					maintainAspectRatio: false,
					plugins: {
						legend: { position: 'bottom' },
						title:  {
							display: true,
							text:    vars.i18n.chartTitle
						},
						tooltip: {
							callbacks: {
								// The bars are percentages; the tooltip carries the real figure, so
								// no reader has to infer a currency amount from a bar height.
								label: function ( context ) {
									var row     = context.datasetIndex === 0 ? rowA : rowB;
									var key     = METRICS[ context.dataIndex ];
									var display = key === 'net_sales' ? row.display.net_sales_text : row.display[ key ];

									return row.country_name + ': ' + display +
										' (' + Math.round( context.parsed.y ) + '%)';
								}
							}
						}
					},
					scales: {
						x: {
							grid: { display: false }
						},
						y: {
							beginAtZero: true,
							title: {
								display: true,
								text:    vars.i18n.chartTitle
							},
							ticks: {
								callback: function ( value ) {
									return value + '%';
								}
							}
						}
					}
				}
			} );
		},

		/**
		 * Toggle the loading state, hiding content and any previous error.
		 *
		 * @param {boolean} isLoading
		 */
		setLoading: function ( isLoading ) {
			if ( isLoading ) {
				$( '.pdwchs-geo-compare-loading' ).show();
				$( '.pdwchs-geo-compare-content' ).hide();
				$( '.pdwchs-geo-compare-error' ).hide();
			} else {
				$( '.pdwchs-geo-compare-loading' ).hide();
			}
		},

		/**
		 * Show the error state with the localized fallback, hiding loading and content.
		 *
		 * The message is always the localized string, never anything the server echoed back.
		 */
		showError: function () {
			$( '.pdwchs-geo-compare-loading' ).hide();
			$( '.pdwchs-geo-compare-content' ).hide();
			$( '.pdwchs-geo-compare-error' ).text( vars.i18n.errorFallback ).show();
		}
	};

	// Expose on window for testability.
	window.pdwchsGeographic = pdwchsGeographic;

	$( document ).ready( function () {
		pdwchsGeographic.init();
	} );
} )( jQuery );
