/* global pdwchs_ltv_vars, Chart, jQuery */
/**
 * LTV distribution histogram.
 *
 * One bar per lifetime-spend bracket, drawn once from the payload PDWCHS_LTV_Panel localized. There
 * is no request in this file: the panel is all-time, so there is nothing to switch and nothing to
 * reload. No endpoint, no nonce, no loading state and no error state — nothing can fail after first
 * paint because nothing is ever asked for.
 *
 * The cards, the customer total and the bracket table are server-rendered and stay exactly as they
 * are. This script only ever adds a picture of the table beneath it; it never repopulates it.
 *
 * @since 3.1.1
 */
( function ( $ ) {
	'use strict';

	if ( typeof pdwchs_ltv_vars === 'undefined' ) {
		return;
	}

	var vars = pdwchs_ltv_vars;

	/**
	 * Format a bracket edge with the store's currency settings.
	 *
	 * Whole amounts drop the decimals: the ladder edges are round numbers, and "£0.00" repeated
	 * across every tick is noise on an axis that is only naming ranges.
	 *
	 * @param {number} value Amount from the payload.
	 * @return {string}
	 */
	function formatMoney( value ) {
		var num       = parseFloat( value ) || 0;
		var decimals  = ( 0 === num % 1 ) ? 0 : 2;
		var parts     = num.toFixed( decimals ).split( '.' );
		var formatted = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, vars.currency.thousand_separator );

		if ( parts.length > 1 ) {
			formatted += vars.currency.decimal_separator + parts[ 1 ];
		}

		switch ( vars.currency.position ) {
			case 'left':        return vars.currency.symbol + formatted;
			case 'right':       return formatted + vars.currency.symbol;
			case 'left_space':  return vars.currency.symbol + ' ' + formatted;
			case 'right_space': return formatted + ' ' + vars.currency.symbol;
			default:            return vars.currency.symbol + formatted;
		}
	}

	/**
	 * Format a customer count with the store's thousand separator.
	 *
	 * @param {number} value Count from the payload.
	 * @return {string}
	 */
	function formatCount( value ) {
		var num = parseInt( value, 10 ) || 0;
		return num.toString().replace( /\B(?=(\d{3})+(?!\d))/g, vars.currency.thousand_separator );
	}

	/**
	 * One bracket as an axis label. A null max is the open-ended top of the ladder.
	 *
	 * @param {Object} bracket Bracket from the payload.
	 * @return {string}
	 */
	function bracketLabel( bracket ) {
		if ( null === bracket.max || typeof bracket.max === 'undefined' ) {
			return formatMoney( bracket.min ) + '+';
		}

		return formatMoney( bracket.min ) + ' – ' + formatMoney( bracket.max );
	}

	var pdwchsLtvDistribution = {

		/**
		 * Draw the histogram, or do nothing at all.
		 *
		 * The canvas is absent whenever the panel rendered its empty state, and the payload is null
		 * for a user without the capability. Either way there is nothing to plot.
		 */
		init: function () {
			var canvas = document.getElementById( 'pdwchs-ltv-chart' );

			if ( ! canvas || typeof Chart === 'undefined' ) {
				return;
			}

			if ( ! vars.initial || ! vars.initial.brackets ) {
				return;
			}

			this.renderChart( canvas, vars.initial.brackets );
		},

		/**
		 * One bar per bracket, in ladder order, matching the table below it exactly.
		 *
		 * Drawn once and never again — there is no second state to move to, so nothing has to be
		 * destroyed first and one page load makes exactly one instance.
		 *
		 * @param {Object} canvas   The canvas element.
		 * @param {Array}  brackets Brackets from the payload.
		 */
		renderChart: function ( canvas, brackets ) {
			return new Chart( canvas, {
				type: 'bar',
				data: {
					labels: brackets.map( function ( bracket ) {
						return bracketLabel( bracket );
					} ),
					datasets: [ {
						label:           vars.i18n.chart_title,
						data:            brackets.map( function ( bracket ) {
							return parseInt( bracket.customers, 10 ) || 0;
						} ),
						borderColor:     '#2271b1',
						backgroundColor: 'rgba(34, 113, 177, 0.75)',
						borderWidth:     1
					} ]
				},
				options: {
					responsive:          true,
					maintainAspectRatio: false,
					plugins: {
						legend: { display: false },
						tooltip: {
							callbacks: {
								title: function ( context ) {
									var bracket = context.length ? brackets[ context[ 0 ].dataIndex ] : null;

									if ( ! bracket ) {
										return '';
									}

									return vars.i18n.bracket_label + ': ' + bracketLabel( bracket );
								},
								label: function ( context ) {
									var bracket = brackets[ context.dataIndex ];

									if ( ! bracket ) {
										return '';
									}

									return vars.i18n.customers_label + ': ' + formatCount( bracket.customers );
								}
							}
						}
					},
					scales: {
						x: { grid: { display: false } },
						y: {
							beginAtZero: true,
							ticks: {
								// These are people, so a 2.5 gridline would be meaningless.
								precision: 0,
								callback: function ( value ) {
									if ( 0 !== value % 1 ) {
										return '';
									}

									return formatCount( value );
								}
							}
						}
					}
				}
			} );
		}
	};

	// Expose on window for testability.
	window.pdwchsLtvDistribution = pdwchsLtvDistribution;

	$( document ).ready( function () {
		pdwchsLtvDistribution.init();
	} );

} )( jQuery );
