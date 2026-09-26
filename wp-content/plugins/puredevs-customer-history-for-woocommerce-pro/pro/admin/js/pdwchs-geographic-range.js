/* global pdwchs_geographic_range_vars, jQuery, moment */
/**
 * Geographic Performance — the date range picker.
 *
 * R15-21A rendered the range field readonly and unnamed, which is the house pattern and is what
 * stops a browser submitting the display string ("July 16, 2026 - August 14, 2026") as if it were
 * a date. The real values travel in the form's hidden start_date / end_date inputs. The pattern
 * only works when a picker is bound to the visible field; this is that binding.
 *
 * Loaded on every state of the page, including the empty one — the range form sits in the toolbar,
 * outside the has-data branch, so a reader looking at "no orders in this range" must still be able
 * to widen it. That is why this is a separate file from pdwchs-geographic.js, which is deliberately
 * not downloaded when there are fewer than two countries to compare.
 *
 * @since 3.1.1
 */
( function ( $ ) {
	'use strict';

	if ( typeof pdwchs_geographic_range_vars === 'undefined' ) {
		return;
	}

	var vars = pdwchs_geographic_range_vars;

	$( function () {
		var $input = $( '#pdwchs-geo-date-range' );

		if ( $input.length === 0 ) {
			return;
		}

		var $form  = $input.closest( 'form' );
		var $start = $form.find( '[name="start_date"]' );
		var $end   = $form.find( '[name="end_date"]' );

		// daterangepicker parses a string startDate against locale.format, which defaults to
		// MM/DD/YYYY — so handing it a raw 'YYYY-MM-DD' string produces an invalid moment and a
		// calendar rendered entirely as NaN. Parse explicitly instead, in strict mode, and declare
		// the format so the picker's own labels agree with what the server sends.
		var config = {
			autoUpdateInput: false,
			locale:          { format: 'YYYY-MM-DD' }
		};

		// Seeded only when both dates parse. An unseeded picker opens on today, which is a poor
		// default but a working one; a malformed pair would take the whole calendar down with it.
		if ( typeof moment !== 'undefined' ) {
			var start = moment( vars.startDate, 'YYYY-MM-DD', true );
			var end   = moment( vars.endDate, 'YYYY-MM-DD', true );

			if ( start.isValid() && end.isValid() ) {
				config.startDate = start;
				config.endDate   = end;
			}
		}

		// Opens on the range the page is currently showing, so the picker starts where the reader
		// is rather than on today.
		$input.daterangepicker( config );

		// Fills the form; it does not submit it. This page has a server-rendered Apply button, and
		// auto-submitting would leave a visible control with nothing to do.
		$input.on( 'apply.daterangepicker', function ( e, picker ) {
			var start = picker.startDate.format( 'YYYY-MM-DD' );
			var end   = picker.endDate.format( 'YYYY-MM-DD' );

			$start.val( start );
			$end.val( end );
			$input.val( start + ' - ' + end );
		} );

		// Cancel writes nothing. The range in force stays in force.
	} );
} )( jQuery );
