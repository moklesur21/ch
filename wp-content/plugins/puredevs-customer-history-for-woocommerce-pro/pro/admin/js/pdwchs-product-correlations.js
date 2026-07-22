/* global pdwchs_product_correlations_vars, jQuery */
( function ( $ ) {
	'use strict';

	if ( typeof pdwchs_product_correlations_vars === 'undefined' ) {
		return;
	}

	var vars = pdwchs_product_correlations_vars;

	window.pdwchsProductCorrelations = {

		init: function () {
			if ( $( '#pdwchs-anchor-product' ).length === 0 ) {
				return;
			}

			$( '#pdwchs-anchor-product' ).on( 'change', function () {
				pdwchsProductCorrelations.fetchData();
			} );

			$( 'input[name="pdwchs_window"]' ).on( 'change', function () {
				if ( $( '#pdwchs-anchor-product' ).val() ) {
					pdwchsProductCorrelations.fetchData();
				}
			} );

			$( 'input[name="pdwchs_limit"]' ).on( 'change', function () {
				if ( $( '#pdwchs-anchor-product' ).val() ) {
					pdwchsProductCorrelations.fetchData();
				}
			} );

			pdwchsProductCorrelations.showEmpty();
		},

		fetchData: function () {
			var product_id = $( '#pdwchs-anchor-product' ).val();

			if ( ! product_id ) {
				pdwchsProductCorrelations.showEmpty();
				return;
			}

			var window_days = $( 'input[name="pdwchs_window"]:checked' ).val() || 90;
			var limit       = $( 'input[name="pdwchs_limit"]:checked' ).val() || 10;

			pdwchsProductCorrelations.setLoading( true );

			$.ajax( {
				url:  vars.ajaxurl,
				type: 'POST',
				data: {
					action:      'pdwchs_get_product_correlations',
					nonce:       vars.nonce,
					product_id:  product_id,
					window_days: window_days,
					limit:       limit,
				},
			} )
			.done( function ( response ) {
				if ( response.success && response.data.correlations.length > 0 ) {
					pdwchsProductCorrelations.renderTable( response.data.correlations );
				} else if ( response.success ) {
					pdwchsProductCorrelations.showEmpty( vars.i18n.no_data_text );
				} else {
					pdwchsProductCorrelations.showError();
				}
			} )
			.fail( function () {
				pdwchsProductCorrelations.showError();
			} )
			.always( function () {
				pdwchsProductCorrelations.setLoading( false );
			} );
		},

		renderTable: function ( rows ) {
			$( '.pdwchs-correlations-empty, .pdwchs-correlations-loading, .pdwchs-correlations-error' ).hide();
			$( '.pdwchs-correlations-content' ).show();

			var $tbody = $( '#pdwchs-correlations-tbody' );
			$tbody.empty();

			$.each( rows, function ( index, row ) {
				var $tr = $( '<tr>' );

				$tr.append( $( '<td>' ).text( index + 1 ) );

				var $a = $( '<a>' )
					.attr( 'href', vars.edit_link_base + row.product_id )
					.attr( 'target', '_blank' )
					.text( row.product_name );
				$tr.append( $( '<td>' ).append( $a ) );

				$tr.append( $( '<td>' ).text( row.co_count ) );
				$tr.append( $( '<td>' ).text( parseFloat( row.confidence * 100 ).toFixed( 1 ) + '%' ) );

				$tbody.append( $tr );
			} );
		},

		setLoading: function ( bool ) {
			if ( bool ) {
				$( '.pdwchs-correlations-empty, .pdwchs-correlations-error, .pdwchs-correlations-content' ).hide();
				$( '.pdwchs-correlations-loading' ).show();
			} else {
				$( '.pdwchs-correlations-loading' ).hide();
			}
		},

		showError: function () {
			$( '.pdwchs-correlations-empty, .pdwchs-correlations-loading, .pdwchs-correlations-content' ).hide();
			$( '.pdwchs-correlations-error' ).show();
		},

		showEmpty: function ( msg ) {
			$( '.pdwchs-correlations-loading, .pdwchs-correlations-error, .pdwchs-correlations-content' ).hide();
			var $empty = $( '.pdwchs-correlations-empty' );
			$empty.show();
			if ( msg ) {
				$empty.find( 'p' ).text( msg );
			}
		},

	};

	$( document ).ready( function () {
		pdwchsProductCorrelations.init();
	} );

}( jQuery ) );
