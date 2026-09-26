/* global pdwchs_cohort_vars, Chart, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof pdwchs_cohort_vars === 'undefined' ) {
		return;
	}

	var $canvas = $( '#pdwchs-cohort-chart' );
	if ( $canvas.length === 0 ) {
		return;
	}

	if ( typeof Chart === 'undefined' ) {
		return;
	}

	var vars     = pdwchs_cohort_vars;
	var averages = vars.averages || [];

	// Offset 0 is 100 % by definition; plotting it pins the scale and flattens
	// the part of the curve anyone is reading.
	var series = averages.slice( 1 );

	var labels  = [];
	var data    = [];
	var counted = [];

	series.forEach( function ( row ) {
		var cohorts = Number( row.cohorts_counted );

		labels.push( vars.i18n.offset_label.replace( '%d', Number( row.offset ) ) );

		// An offset no cohort has lived through yet is unknown, not zero — a
		// null leaves a visible gap instead of a line falling off a cliff.
		data.push( cohorts === 0 ? null : Number( row.retention_pct ) );
		counted.push( cohorts );
	} );

	var cohortChart = new Chart(
		$canvas.get( 0 ),
		{
			type: 'line',
			data: {
				labels:   labels,
				datasets: [ {
					label:           vars.i18n.series_label,
					data:            data,
					borderColor:     '#2271b1',
					backgroundColor: '#2271b1',
					spanGaps:        false,
					fill:            false,
					tension:         0.3
				} ]
			},
			options: {
				responsive:          true,
				maintainAspectRatio: false,
				plugins: {
					legend: { display: false },
					title: {
						display: true,
						text:    vars.i18n.chart_title
					},
					tooltip: {
						callbacks: {
							label: function ( context ) {
								return context.parsed.y + '% (' +
									vars.i18n.cohorts_label.replace( '%d', counted[ context.dataIndex ] ) + ')';
							}
						}
					}
				},
				scales: {
					y: {
						beginAtZero: true,
						max:         100,
						ticks: {
							callback: function ( value ) {
								return value + '%';
							}
						}
					}
				}
			}
		}
	);

	// Held so the instance is not garbage-collected; the page fires no event
	// that would re-instantiate it.
	$canvas.data( 'pdwchsCohortChart', cohortChart );
} )( jQuery );
