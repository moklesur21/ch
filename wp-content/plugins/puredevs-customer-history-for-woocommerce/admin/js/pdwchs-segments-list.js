jQuery( function ( $ ) {

	$( '#segments' ).on( 'click', '.pdwchs-load-segment', function () {
		var criteria = $( this ).data( 'criteria' );
		var params   = $.param( criteria );
		window.location.href = pdwchs_segments_vars.customersUrl + '&' + params;
	} );

	$( '#segments' ).on( 'click', '.pdwchs-delete-segment', function () {
		if ( ! window.confirm( pdwchs_segments_vars.i18n.confirmDelete ) ) return;

		var $btn = $( this );
		var id   = $btn.data( 'segment-id' );
		$btn.prop( 'disabled', true );

		$.post( pdwchs_segments_vars.ajaxurl, {
			action:     'pdwchs_delete_segment',
			nonce:      pdwchs_segments_vars.nonce,
			segment_id: id,
		} )
		.done( function ( response ) {
			if ( response.success ) {
				$btn.closest( 'tr' ).remove();
			} else {
				var msg = ( response.data && response.data.message )
					? response.data.message : pdwchs_segments_vars.i18n.deleteError;
				window.alert( msg );
				$btn.prop( 'disabled', false );
			}
		} )
		.fail( function () {
			window.alert( pdwchs_segments_vars.i18n.deleteError );
			$btn.prop( 'disabled', false );
		} );
	} );

	$( '#segments' ).on( 'click', '.pdwchs-export-segment', function () {
		var id = $( this ).data( 'segment-id' );

		var $form = $( '<form>', {
			method: 'post',
			action: pdwchs_segments_vars.adminPostUrl,
		} );
		$form.append( $( '<input>', { type: 'hidden', name: 'action',     value: 'pdwchs_export_segment_csv' } ) );
		$form.append( $( '<input>', { type: 'hidden', name: 'nonce',      value: pdwchs_segments_vars.exportNonce } ) );
		$form.append( $( '<input>', { type: 'hidden', name: 'segment_id', value: id } ) );
		$( 'body' ).append( $form );
		$form.trigger( 'submit' );
		$form.remove();
	} );

} );
