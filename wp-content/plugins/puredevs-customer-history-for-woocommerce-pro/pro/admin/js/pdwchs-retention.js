/* global pdwchs_retention_vars, Chart, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof pdwchs_retention_vars === 'undefined' ) {
		return;
	}

	var vars = pdwchs_retention_vars;

	// Module level, so a preset switch destroys the previous instance rather than
	// stacking a new chart on the same canvas.
	var retentionChart = null;

	// A fast reader flicking through eight options would otherwise queue eight
	// requests whose responses can land out of order.
	var isLoading = false;

	/**
	 * Format a churn rate as a percentage string, or the dash for an undefined one.
	 *
	 * @param {number|null} value Rate from the payload, already a percentage.
	 * @return {string}
	 */
	function formatPercent( value ) {
		if ( value === null || typeof value === 'undefined' ) {
			return vars.i18n.undefined_dash;
		}

		var num = parseFloat( value );

		if ( isNaN( num ) ) {
			return vars.i18n.undefined_dash;
		}

		return num.toFixed( 1 ).replace( '.', vars.currency.decimal_separator ) + '%';
	}

	/**
	 * Format a money value with the store's currency settings.
	 *
	 * @param {number|null} value Amount from the payload.
	 * @return {string}
	 */
	function formatCurrency( value ) {
		if ( value === null || typeof value === 'undefined' ) {
			return vars.i18n.undefined_dash;
		}

		var num       = parseFloat( value ) || 0;
		var parts     = num.toFixed( 2 ).split( '.' );
		var intPart   = parts[ 0 ].replace( /\B(?=(\d{3})+(?!\d))/g, vars.currency.thousand_separator );
		var formatted = intPart + vars.currency.decimal_separator + parts[ 1 ];

		switch ( vars.currency.position ) {
			case 'left':        return vars.currency.symbol + formatted;
			case 'right':       return formatted + vars.currency.symbol;
			case 'left_space':  return vars.currency.symbol + ' ' + formatted;
			case 'right_space': return formatted + ' ' + vars.currency.symbol;
			default:            return vars.currency.symbol + formatted;
		}
	}

	/**
	 * Format an integer with the store's thousand separator.
	 *
	 * @param {number} value Count from the payload.
	 * @return {string}
	 */
	function formatCount( value ) {
		var num = parseInt( value, 10 ) || 0;
		return num.toString().replace( /\B(?=(\d{3})+(?!\d))/g, vars.currency.thousand_separator );
	}

	/**
	 * The display label for a preset slug, falling back to the payload's own label.
	 *
	 * @param {Object} payload Response payload.
	 * @return {string}
	 */
	function windowLabel( payload ) {
		if ( payload.preset && vars.presets[ payload.preset ] ) {
			return vars.presets[ payload.preset ];
		}

		return payload.window_label || '';
	}

	var pdwchsRetention = {

		/**
		 * Bind the selector and draw the first chart. Inert on any page where the panel did
		 * not render — including when the capability check bounced it.
		 *
		 * The cards and the table arrive server-rendered; the canvas cannot, so the default
		 * preset's payload is handed over at localize time and drawn here. No request on load.
		 */
		init: function () {
			if ( $( '.pdwchs-retention-widget' ).length === 0 ) {
				return;
			}

			$( '#pdwchs-retention-preset' ).on( 'change', function () {
				pdwchsRetention.fetch( $( this ).val() );
			} );

			if ( vars.initial && vars.initial.series ) {
				pdwchsRetention.renderChart( vars.initial.series );
			}
		},

		/**
		 * Fetch one preset and repaint the panel from the response.
		 *
		 * @param {string} preset Selected slug.
		 */
		fetch: function ( preset ) {
			if ( isLoading || ! preset ) {
				return;
			}

			pdwchsRetention.setBusy( true );
			$( '.pdwchs-retention-error' ).hide();

			$.ajax( {
				url:  vars.ajaxurl,
				type: 'POST',
				data: {
					action: 'pdwchs_get_retention_metrics',
					nonce:  vars.nonce,
					preset: preset
				}
			} )
			.done( function ( response ) {
				if ( ! response || ! response.success || ! response.data ) {
					pdwchsRetention.showError();
					return;
				}

				var payload = response.data;
				var series  = payload.series || [];

				pdwchsRetention.renderCards( payload );
				pdwchsRetention.renderTable( series );
				pdwchsRetention.renderChart( series );
			} )
			.fail( function () {
				pdwchsRetention.showError();
			} )
			.always( function () {
				pdwchsRetention.setBusy( false );
			} );
		},

		/**
		 * Repaint the heading, the CLV card and the churn-rate card.
		 *
		 * Both values come from the payload. Nothing is recomputed here: the expected CLV and
		 * every rate are calculated and tested server-side, and a second implementation drifts.
		 *
		 * @param {Object} payload Response payload.
		 */
		renderCards: function ( payload ) {
			var label = windowLabel( payload );

			$( '.pdwchs-retention-title-row h2' ).text(
				vars.i18n.window_heading.replace( '%s', label )
			);

			var $labels = $( '.pdwchs-retention-card-label' );
			var $values = $( '.pdwchs-retention-card-value' );

			if ( $values.length < 2 ) {
				return;
			}

			$labels.eq( 0 ).text( vars.i18n.clv_label.replace( '%s', label ) );

			var clvDefined = ( 'ok' === payload.clv_state && payload.expected_clv !== null );
			var clvText    = clvDefined ? formatCurrency( payload.expected_clv ) : vars.i18n.undefined_dash;

			$values.eq( 0 )
				.toggleClass( 'pdwchs-retention-undefined', ! clvDefined )
				.text( clvText );

			var rateDefined = ( 'ok' === payload.current_state && payload.current_churn_rate !== null );
			var rateText    = rateDefined ? formatPercent( payload.current_churn_rate ) : vars.i18n.undefined_dash;

			$values.eq( 1 )
				.toggleClass( 'pdwchs-retention-undefined', ! rateDefined )
				.text( rateText );
		},

		/**
		 * Rebuild the series table, which stays as the accessible view of the same data.
		 *
		 * Cells are built with .text(), never string-concatenated markup.
		 *
		 * @param {Array} series Series points from the payload.
		 */
		renderTable: function ( series ) {
			var $table = $( '.pdwchs-retention-series-table' );
			var $tbody = $table.find( 'tbody' );

			if ( $tbody.length === 0 ) {
				return;
			}

			$tbody.empty();

			if ( series.length === 0 ) {
				$table.hide();
				return;
			}

			$table.show();

			$.each( series, function ( index, point ) {
				var defined = ( 'ok' === point.state && point.churn_rate !== null );
				var $row    = $( '<tr>' );

				$row.append( $( '<td>' ).addClass( 'pdwchs-retention-cell-period' ).text( point.label ) );
				$row.append( $( '<td>' ).addClass( 'pdwchs-retention-cell-num' ).text( formatCount( point.base ) ) );
				$row.append( $( '<td>' ).addClass( 'pdwchs-retention-cell-num' ).text( formatCount( point.churned ) ) );

				$row.append(
					$( '<td>' )
						.addClass( 'pdwchs-retention-cell-num' )
						.toggleClass( 'pdwchs-retention-undefined', ! defined )
						.text( defined ? formatPercent( point.churn_rate ) : vars.i18n.undefined_dash )
				);

				$tbody.append( $row );
			} );
		},

		/**
		 * Destroy any existing instance and draw the churn series.
		 *
		 * A point with state no_base carries a null rate and is mapped to null, not 0:
		 * spanGaps false then draws a gap, which is the truthful rendering. A line dipping
		 * to zero would say the opposite of "there was nobody to churn".
		 *
		 * @param {Array} series Series points from the payload.
		 */
		renderChart: function ( series ) {
			if ( retentionChart ) {
				retentionChart.destroy();
				retentionChart = null;
			}

			var canvas = document.getElementById( 'pdwchs-retention-chart' );

			// Absent whenever the panel rendered its empty state.
			if ( ! canvas || typeof Chart === 'undefined' ) {
				return;
			}

			retentionChart = new Chart( canvas, {
				type: 'line',
				data: {
					labels: series.map( function ( point ) {
						return point.label;
					} ),
					datasets: [ {
						data: series.map( function ( point ) {
							if ( 'ok' !== point.state || point.churn_rate === null ) {
								return null;
							}
							return parseFloat( point.churn_rate );
						} ),
						borderColor:      '#2271b1',
						backgroundColor:  'rgba(34, 113, 177, 0.08)',
						fill:             true,
						tension:          0.3,
						spanGaps:         false,
						pointRadius:      3,
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
									var point = series[ context.dataIndex ];

									if ( ! point ) {
										return '';
									}

									// Symbols rather than words: the period is already the tooltip
									// title, and naming the two counts would need translation
									// keys the panel does not carry.
									return [
										formatPercent( point.churn_rate ),
										formatCount( point.churned ) + ' / ' + formatCount( point.base )
									];
								}
							}
						}
					},
					scales: {
						x: {
							grid:  { display: false },
							ticks: { maxTicksLimit: 8 }
						},
						y: {
							beginAtZero: true,
							ticks: {
								callback: function ( value ) {
									return formatPercent( value );
								}
							}
						}
					}
				}
			} );
		},

		/**
		 * Dim the panel rather than blanking it — it arrives populated and stays readable.
		 *
		 * @param {boolean} busy Whether a request is in flight.
		 */
		setBusy: function ( busy ) {
			isLoading = busy;

			$( '.pdwchs-retention-body' ).toggleClass( 'pdwchs-retention-is-busy', busy );
			$( '#pdwchs-retention-preset' ).prop( 'disabled', busy );
		},

		/**
		 * Show the error block. The previously rendered panel is left as it was.
		 */
		showError: function () {
			$( '.pdwchs-retention-error' ).show();
		}
	};

	// Expose on window for testability.
	window.pdwchsRetention = pdwchsRetention;

	$( document ).ready( function () {
		pdwchsRetention.init();
	} );

} )( jQuery );
