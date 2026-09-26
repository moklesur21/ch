/* global pdwchs_tags_panel_vars, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof pdwchs_tags_panel_vars === 'undefined' ) {
		return;
	}

	var vars = pdwchs_tags_panel_vars;

	var pdwchsTagsPanel = {

		init: function () {
			if ( $( '.pdwchs-tags-panel' ).length === 0 ) {
				return;
			}

			$( '.pdwchs-tags-panel' ).on( 'click', '.pdwchs-tag-add-btn', function () {
				pdwchsTagsPanel.addTag( $( '.pdwchs-tags-panel .pdwchs-tag-input' ).val() );
			} );

			// Enter in the input adds, so the button is not the only path.
			$( '.pdwchs-tags-panel' ).on( 'keydown', '.pdwchs-tag-input', function ( e ) {
				if ( e.which === 13 ) {
					e.preventDefault();
					pdwchsTagsPanel.addTag( $( this ).val() );
				}
			} );

			$( '.pdwchs-tags-panel' ).on( 'click', '.pdwchs-tag-remove', function () {
				var $chip = $( this ).closest( '.pdwchs-tag-chip' );

				if ( ! window.confirm( vars.i18n.confirmRemove ) ) {
					return;
				}

				pdwchsTagsPanel.removeTag( $chip.data( 'tag-id' ), $chip );
			} );
		},

		addTag: function ( name ) {
			name = $.trim( name || '' );

			if ( name === '' ) {
				pdwchsTagsPanel.showError( vars.i18n.emptyTag );
				return;
			}

			pdwchsTagsPanel.clearError();

			// tag_name, never tag_id: the server resolves an existing tag or
			// creates one, so dedupe stays in one place.
			$.ajax( {
				url:  vars.ajaxurl,
				type: 'POST',
				data: {
					action:      'pdwchs_assign_tag',
					nonce:       vars.nonce,
					customer_id: vars.customerId,
					tag_name:    name
				}
			} )
			.done( function ( response ) {
				if ( ! response || ! response.success || ! response.data || ! response.data.tag ) {
					pdwchsTagsPanel.showError( vars.i18n.errorFallback );
					return;
				}

				$( '.pdwchs-tags-panel .pdwchs-tag-input' ).val( '' );

				// A duplicate add is a no-op, not an error: no second chip and
				// no banner.
				if ( ! response.data.already_assigned ) {
					pdwchsTagsPanel.renderChip( response.data.tag );
				}

				if ( response.data.created ) {
					pdwchsTagsPanel.addToDatalist( response.data.tag );
				}

				pdwchsTagsPanel.refreshCount();
			} )
			.fail( function () {
				pdwchsTagsPanel.showError( vars.i18n.errorFallback );
			} );
		},

		removeTag: function ( tagId, $chip ) {
			pdwchsTagsPanel.clearError();

			$.ajax( {
				url:  vars.ajaxurl,
				type: 'POST',
				data: {
					action:      'pdwchs_unassign_tag',
					nonce:       vars.nonce,
					customer_id: vars.customerId,
					tag_id:      tagId
				}
			} )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					pdwchsTagsPanel.showError( vars.i18n.errorFallback );
					return;
				}

				$chip.remove();
				pdwchsTagsPanel.refreshCount();
			} )
			.fail( function () {
				pdwchsTagsPanel.showError( vars.i18n.errorFallback );
			} );
		},

		renderChip: function ( tag ) {
			var $chip = $( '<li>' )
				.addClass( 'pdwchs-tag-chip' )
				.attr( 'data-tag-id', tag.id );

			$chip.append( $( '<span>' ).addClass( 'pdwchs-tag-name' ).text( tag.name ) );

			$chip.append(
				$( '<button>' )
					.attr( 'type', 'button' )
					.addClass( 'pdwchs-tag-remove' )
					.text( '×' )
			);

			$( '.pdwchs-tags-panel .pdwchs-tags-list' ).append( $chip );
		},

		addToDatalist: function ( tag ) {
			$( '#pdwchs-tags-datalist' ).append( $( '<option>' ).attr( 'value', tag.name ) );
		},

		refreshCount: function () {
			var count = $( '.pdwchs-tags-panel .pdwchs-tag-chip' ).length;

			$( '.pdwchs-tags-panel .pdwchs-tags-count' ).text( count );
			$( '.pdwchs-tags-panel .pdwchs-tags-empty' ).toggle( count === 0 );
		},

		showError: function ( message ) {
			$( '.pdwchs-tags-panel .pdwchs-crm-error' ).text( message ).show();
		},

		clearError: function () {
			$( '.pdwchs-tags-panel .pdwchs-crm-error' ).text( '' ).hide();
		}
	};

	// Expose on window for testability.
	window.pdwchsTagsPanel = pdwchsTagsPanel;

	$( document ).ready( function () {
		pdwchsTagsPanel.init();
	} );
} )( jQuery );
