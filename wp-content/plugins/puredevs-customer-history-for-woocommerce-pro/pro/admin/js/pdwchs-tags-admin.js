/* global pdwchs_tags_admin_vars, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof pdwchs_tags_admin_vars === 'undefined' ) {
		return;
	}

	var vars = pdwchs_tags_admin_vars;

	var pdwchsTagsAdmin = {

		init: function () {
			if ( $( '.pdwchs-tags-admin' ).length === 0 ) {
				return;
			}

			$( '.pdwchs-tags-admin' ).on( 'click', '.pdwchs-tag-create-btn', function () {
				pdwchsTagsAdmin.createTag( $( '.pdwchs-tags-admin .pdwchs-tag-new-name' ).val() );
			} );

			$( '.pdwchs-tags-admin' ).on( 'keydown', '.pdwchs-tag-new-name', function ( e ) {
				if ( e.which === 13 ) {
					e.preventDefault();
					pdwchsTagsAdmin.createTag( $( this ).val() );
				}
			} );

			$( '.pdwchs-tags-admin' ).on( 'click', '.pdwchs-tag-rename-btn', function () {
				pdwchsTagsAdmin.startRename( $( this ).closest( 'tr' ) );
			} );

			$( '.pdwchs-tags-admin' ).on( 'click', '.pdwchs-tag-rename-save', function () {
				var $row = $( this ).closest( 'tr' );
				pdwchsTagsAdmin.renameTag( $row.data( 'tag-id' ), $row.find( '.pdwchs-tag-rename-input' ).val(), $row );
			} );

			$( '.pdwchs-tags-admin' ).on( 'click', '.pdwchs-tag-rename-cancel', function () {
				pdwchsTagsAdmin.cancelRename( $( this ).closest( 'tr' ) );
			} );

			$( '.pdwchs-tags-admin' ).on( 'click', '.pdwchs-tag-delete-btn', function () {
				var $row = $( this ).closest( 'tr' );
				pdwchsTagsAdmin.deleteTag( $row.data( 'tag-id' ), $row );
			} );
		},

		createTag: function ( name ) {
			name = $.trim( name || '' );

			if ( name === '' ) {
				pdwchsTagsAdmin.showError( vars.i18n.emptyName );
				return;
			}

			pdwchsTagsAdmin.clearError();

			$.ajax( {
				url:  vars.ajaxurl,
				type: 'POST',
				data: {
					action: 'pdwchs_create_tag',
					nonce:  vars.nonce,
					name:   name
				}
			} )
			.done( function ( response ) {
				if ( ! response || ! response.success || ! response.data || ! response.data.tag ) {
					pdwchsTagsAdmin.showError( pdwchsTagsAdmin.messageFrom( response ) );
					return;
				}

				$( '.pdwchs-tags-admin .pdwchs-tag-new-name' ).val( '' );

				// created === false means the slug already resolved to an
				// existing tag: say so rather than adding a duplicate row.
				if ( ! response.data.created ) {
					pdwchsTagsAdmin.showError( vars.i18n.alreadyExists.replace( '%s', response.data.tag.name ) );
					return;
				}

				pdwchsTagsAdmin.renderRow( response.data.tag );
				pdwchsTagsAdmin.refreshEmptyState();
			} )
			.fail( function () {
				pdwchsTagsAdmin.showError( vars.i18n.errorFallback );
			} );
		},

		startRename: function ( $row ) {
			if ( $row.find( '.pdwchs-tag-rename-input' ).length > 0 ) {
				return;
			}

			var current = $row.find( '.pdwchs-tag-name-cell' ).text();
			var $cell   = $row.find( '.pdwchs-tag-name-cell' );

			$cell.data( 'previous-name', current ).empty();

			$cell.append(
				$( '<input>' )
					.attr( 'type', 'text' )
					.addClass( 'pdwchs-tag-rename-input' )
					.attr( 'placeholder', vars.i18n.renamePrompt )
					.attr( 'value', current )
			);

			$cell.append(
				$( '<button>' )
					.attr( 'type', 'button' )
					.addClass( 'button-link pdwchs-tag-rename-save' )
					.text( vars.i18n.save )
			);

			$cell.append(
				$( '<button>' )
					.attr( 'type', 'button' )
					.addClass( 'button-link pdwchs-tag-rename-cancel' )
					.text( vars.i18n.cancel )
			);
		},

		cancelRename: function ( $row ) {
			var $cell = $row.find( '.pdwchs-tag-name-cell' );
			$cell.text( $cell.data( 'previous-name' ) || '' );
		},

		renameTag: function ( tagId, name, $row ) {
			name = $.trim( name || '' );

			if ( name === '' ) {
				pdwchsTagsAdmin.showRowError( $row, vars.i18n.emptyName );
				return;
			}

			pdwchsTagsAdmin.clearRowError( $row );

			$.ajax( {
				url:  vars.ajaxurl,
				type: 'POST',
				data: {
					action: 'pdwchs_rename_tag',
					nonce:  vars.nonce,
					tag_id: tagId,
					name:   name
				}
			} )
			.done( function ( response ) {
				if ( ! response || ! response.success || ! response.data || ! response.data.tag ) {
					// Server-side collision / empty / too-long messages land here.
					pdwchsTagsAdmin.showRowError( $row, pdwchsTagsAdmin.messageFrom( response ) );
					return;
				}

				$row.find( '.pdwchs-tag-name-cell' ).text( response.data.tag.name );
				$row.find( '.pdwchs-tag-slug-cell' ).text( response.data.tag.slug );
			} )
			.fail( function () {
				pdwchsTagsAdmin.showRowError( $row, vars.i18n.errorFallback );
			} );
		},

		deleteTag: function ( tagId, $row ) {
			// The real count is what makes this dialog worth showing.
			var count = parseInt( $row.find( '.pdwchs-tag-count-cell' ).text(), 10 ) || 0;

			if ( ! window.confirm( vars.i18n.confirmDelete.replace( '%d', count ) ) ) {
				return;
			}

			pdwchsTagsAdmin.clearRowError( $row );

			$.ajax( {
				url:  vars.ajaxurl,
				type: 'POST',
				data: {
					action: 'pdwchs_delete_tag',
					nonce:  vars.nonce,
					tag_id: tagId
				}
			} )
			.done( function ( response ) {
				if ( ! response || ! response.success ) {
					pdwchsTagsAdmin.showRowError( $row, pdwchsTagsAdmin.messageFrom( response ) );
					return;
				}

				$row.remove();
				pdwchsTagsAdmin.refreshEmptyState();
			} )
			.fail( function () {
				pdwchsTagsAdmin.showRowError( $row, vars.i18n.errorFallback );
			} );
		},

		renderRow: function ( tag ) {
			var $row = $( '<tr>' ).attr( 'data-tag-id', tag.id );

			$row.append( $( '<td>' ).addClass( 'pdwchs-tag-name-cell' ).text( tag.name ) );
			$row.append( $( '<td>' ).addClass( 'pdwchs-tag-slug-cell' ).text( tag.slug ) );
			$row.append( $( '<td>' ).addClass( 'pdwchs-tag-count-cell' ).text( '0' ) );
			$row.append( $( '<td>' ).addClass( 'pdwchs-tag-created-cell' ).text( '' ) );

			var $actions = $( '<td>' ).addClass( 'pdwchs-tag-actions-cell' );

			$actions.append(
				$( '<button>' ).attr( 'type', 'button' ).addClass( 'button-link pdwchs-tag-rename-btn' ).text( vars.i18n.rename )
			);
			$actions.append(
				$( '<button>' ).attr( 'type', 'button' ).addClass( 'button-link pdwchs-tag-delete-btn' ).text( vars.i18n.delete )
			);
			$actions.append(
				$( '<div>' ).addClass( 'pdwchs-crm-error pdwchs-tag-row-error' ).hide()
			);

			$row.append( $actions );

			$( '.pdwchs-tags-admin .pdwchs-tags-table tbody' ).prepend( $row );
		},

		refreshEmptyState: function () {
			var rows = $( '.pdwchs-tags-admin .pdwchs-tags-table tbody tr' ).length;
			$( '.pdwchs-tags-admin .pdwchs-tags-empty' ).toggle( rows === 0 );
		},

		messageFrom: function ( response ) {
			if ( response && response.data && response.data.message ) {
				return response.data.message;
			}
			return vars.i18n.errorFallback;
		},

		showError: function ( message ) {
			$( '.pdwchs-tags-admin .pdwchs-tags-create .pdwchs-crm-error' ).text( message ).show();
		},

		clearError: function () {
			$( '.pdwchs-tags-admin .pdwchs-tags-create .pdwchs-crm-error' ).text( '' ).hide();
		},

		showRowError: function ( $row, message ) {
			$row.find( '.pdwchs-tag-row-error' ).text( message ).show();
		},

		clearRowError: function ( $row ) {
			$row.find( '.pdwchs-tag-row-error' ).text( '' ).hide();
		}
	};

	// Expose on window for testability.
	window.pdwchsTagsAdmin = pdwchsTagsAdmin;

	$( document ).ready( function () {
		pdwchsTagsAdmin.init();
	} );
} )( jQuery );
