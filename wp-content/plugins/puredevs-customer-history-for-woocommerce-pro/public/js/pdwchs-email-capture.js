( function ( $ ) {
	'use strict';

	if ( ! window.pdwchsCartCapture || ! pdwchsCartCapture.enabled ) {
		return;
	}

	var lastSent = '';
	var timer    = null;

	$( document ).on( 'blur', '#billing_email, #email', function () {
		var email = $( this ).val().trim();
		clearTimeout( timer );
		timer = setTimeout( function () {
			if ( ! /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email ) ) { return; }
			if ( email === lastSent ) { return; }
			$.post(
				pdwchsCartCapture.ajaxurl,
				{
					action : 'pdwchs_capture_cart_email',
					nonce  : pdwchsCartCapture.nonce,
					email  : email
				},
				function ( response ) {
					if ( response && response.success ) {
						lastSent = email;
					}
				}
			);
		}, 500 );
	} );
} )( jQuery );
