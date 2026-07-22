jQuery( function ( $ ) {

	var $overlay     = $( '#pdwchs-modal-overlay' );
	var $nameInput   = $( '#pdwchs-segment-name' );
	var $descInput   = $( '#pdwchs-segment-description' );
	var $nameError   = $( '#pdwchs-name-error' );
	var $criteriaErr = $( '#pdwchs-criteria-error' );
	var $saveBtn     = $( '#pdwchs-modal-save' );

	function closeModal() {
		$overlay.hide();
		$nameInput.val( '' );
		$descInput.val( '' );
		$nameError.hide().text( '' );
		$criteriaErr.hide().text( '' );
	}

	// Focused upgrade modal shown when the free segment cap is reached.
	// All strings come from the server (already translated); built with jQuery
	// text:/attr so dynamic values are escaped.
	function showSegmentUpsell( data ) {
		closeModal();
		$( '#pdwchs-upsell-overlay' ).remove();

		var $box = $( '<div>', { 'class': 'pdwchs-upsell-box', style: 'background:#fff;max-width:460px;width:92%;border-radius:8px;padding:24px;box-shadow:0 10px 40px rgba(0,0,0,.3);' } );
		$box.append( $( '<h2>', { text: data.title || '', style: 'margin-top:0;' } ) );
		$box.append( $( '<p>', { text: data.message || '' } ) );

		if ( data.pro_segments && data.pro_segments.length ) {
			$box.append( $( '<p>', { text: data.ready_label || '', style: 'margin:8px 0 4px;font-weight:600;' } ) );
			var $ul = $( '<ul>', { style: 'margin:0 0 12px 18px;list-style:disc;' } );
			$.each( data.pro_segments, function ( i, name ) {
				$ul.append( $( '<li>', { text: name } ) );
			} );
			$box.append( $ul );
		}

		var $actions = $( '<p>', { style: 'margin-top:16px;' } );
		$actions.append( $( '<a>', { href: data.upgrade_url || '#', target: '_blank', rel: 'noopener noreferrer', 'class': 'button button-primary', text: data.cta || 'Upgrade to Pro' } ) );
		$actions.append( document.createTextNode( ' ' ) );
		$actions.append( $( '<a>', { href: '#', 'class': 'pdwchs-upsell-close', style: 'margin-left:8px;', text: data.dismiss || 'Maybe later' } ) );
		$box.append( $actions );

		var $up = $( '<div>', { id: 'pdwchs-upsell-overlay', style: 'position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:100000;display:flex;align-items:center;justify-content:center;' } );
		$up.append( $box );
		$( 'body' ).append( $up );

		$up.on( 'click', function ( e ) {
			if ( $( e.target ).is( $up ) || $( e.target ).hasClass( 'pdwchs-upsell-close' ) ) {
				e.preventDefault();
				$up.remove();
			}
		} );
	}

	$( '#pdwchs-save-segment-btn' ).on( 'click', function () {
		$( '.select2-container--open, .selectWoo-container--open' ).find( 'select' ).trigger( 'select2:close' );
		$( 'select.select2-hidden-accessible, select.selectWoo-hidden-accessible' ).trigger( 'select2:close' );
		$overlay.show();
		$nameInput.trigger( 'focus' );
	} );

	$( '#pdwchs-modal-cancel' ).on( 'click', closeModal );

	$overlay.on( 'click', function ( e ) {
		if ( $( e.target ).is( $overlay ) ) {
			closeModal();
		}
	} );

	$saveBtn.on( 'click', function () {
		$nameError.hide().text( '' );
		$criteriaErr.hide().text( '' );

		var name = $nameInput.val().trim();

		if ( name === '' ) {
			$nameError.text( pdwchs_segments_vars.i18n.emptyName ).show();
			return;
		}

		if ( name.length > 191 ) {
			$nameError.text( pdwchs_segments_vars.i18n.nameTooLong ).show();
			return;
		}

		var params      = new URLSearchParams( window.location.search );
		var criteria    = {};
		var allowedKeys = pdwchs_segments_vars.allowedKeys;

		for ( var i = 0; i < allowedKeys.length; i++ ) {
			var key = allowedKeys[ i ];
			var val = params.get( key );
			if ( val !== null && val !== '' ) {
				criteria[ key ] = val;
			}
		}

		if ( Object.keys( criteria ).length === 0 ) {
			$criteriaErr.text( pdwchs_segments_vars.i18n.noFilters ).show();
			return;
		}

		$saveBtn.prop( 'disabled', true );

		$.post(
			pdwchs_segments_vars.ajaxurl,
			{
				action:               'pdwchs_save_segment',
				nonce:                pdwchs_segments_vars.nonce,
				segment_name:         name,
				segment_description:  $descInput.val().trim(),
				criteria:             JSON.stringify( criteria ),
			}
		)
		.done( function ( response ) {
			if ( response.success === true ) {
				closeModal();
				var $notice = $( '<div class="notice notice-success is-dismissible"><p></p></div>' );
				$notice.find( 'p' ).text( pdwchs_segments_vars.i18n.saved );
				$( '#pd-woocommerce-customer-history' ).before( $notice );
			} else if ( response.data && response.data.upsell ) {
				showSegmentUpsell( response.data );
			} else {
				var msg = ( response.data && response.data.message )
					? response.data.message
					: pdwchs_segments_vars.i18n.errorFallback;
				$criteriaErr.text( msg ).show();
			}
		} )
		.fail( function () {
			$criteriaErr.text( pdwchs_segments_vars.i18n.errorFallback ).show();
		} )
		.always( function () {
			$saveBtn.prop( 'disabled', false );
		} );
	} );

} );
