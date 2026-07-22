/* global jQuery, pdwchsCartsVars */
( function ( $ ) {
	'use strict';

	$( function () {
		var $input = $( '#pdwchs-carts-date-range' );
		var $form  = $input.closest( 'form' );
		var $start = $form.find( '[name="date_start"]' );
		var $end   = $form.find( '[name="date_end"]' );

		// Pre-populate display field from current filter values.
		if ( pdwchsCartsVars.dateStart && pdwchsCartsVars.dateEnd ) {
			$input.val( pdwchsCartsVars.dateStart + ' – ' + pdwchsCartsVars.dateEnd );
		}

		$input.daterangepicker( {
			autoUpdateInput: false,
			locale: { cancelLabel: 'Clear' }
		} );

		$input.on( 'apply.daterangepicker', function ( e, picker ) {
			$input.val( picker.startDate.format( 'YYYY-MM-DD' ) + ' – ' + picker.endDate.format( 'YYYY-MM-DD' ) );
			$start.val( picker.startDate.format( 'YYYY-MM-DD' ) );
			$end.val( picker.endDate.format( 'YYYY-MM-DD' ) );
			$form.submit();
		} );

		$input.on( 'cancel.daterangepicker', function () {
			$input.val( '' );
			$start.val( '' );
			$end.val( '' );
			$form.submit();
		} );
	} );
} )( jQuery );
