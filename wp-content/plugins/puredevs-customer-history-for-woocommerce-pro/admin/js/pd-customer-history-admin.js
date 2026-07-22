(function( $ ) {
	'use strict';

	/**
	 * All of the code for your admin-facing JavaScript source
	 * should reside in this file.
	 *
	 * Note: It has been assumed you will write jQuery code here, so the
	 * $ function reference has been prepared for usage within the scope
	 * of this function.
	 *
	 * This enables you to define handlers, for when the DOM is ready:
	 *
	 * $(function() {
	 *
	 * });
	 *
	 * When the window is loaded:
	 *
	 * $( window ).load(function() {
	 *
	 * });
	 *
	 * ...and/or other possibilities.
	 *
	 * Ideally, it is not considered best practise to attach more than a
	 * single DOM-ready or window-load handler for a particular page.
	 * Although scripts in the WordPress core, Plugins and Themes may be
	 * practising this, we should strive to set a better example in our own work.
	 */

    var $win = $(window);
    var $doc = $(document);

    $doc.ready(function () {
        // Initialize Select2/SelectWoo on product filter select
        if ( $('#product_filter').length > 0 ) {
            // Prefer selectWoo (WooCommerce's version) for better compatibility
            if ( typeof $.fn.selectWoo !== 'undefined' ) {
                $('#product_filter').selectWoo({
                    placeholder: 'Search and select a product',
                    allowClear: true,
                    width: '300px',
                    minimumResultsForSearch: 0 // Always show search box
                });
            }
            // Fallback to select2 if selectWoo is not available
            else if ( typeof $.fn.select2 !== 'undefined' ) {
                $('#product_filter').select2({
                    placeholder: 'Search and select a product',
                    allowClear: true,
                    width: '300px',
                    minimumResultsForSearch: 0 // Always show search box
                });
            }
        }

        // Initialize Select2/SelectWoo on customer_country select
        if ( $('#customer_country').length > 0 ) {
            // Prefer selectWoo (WooCommerce's version) for better compatibility
            if ( typeof $.fn.selectWoo !== 'undefined' ) {
                $('#customer_country').selectWoo({
                    placeholder: 'Select a country…',
                    allowClear: true,
                    width: '300px',
                    minimumResultsForSearch: 0 // Always show search box
                });
            }
            // Fallback to select2 if selectWoo is not available
            else if ( typeof $.fn.select2 !== 'undefined' ) {
                $('#customer_country').select2({
                    placeholder: 'Select a country…',
                    allowClear: true,
                    width: '300px',
                    minimumResultsForSearch: 0 // Always show search box
                });
            }
        }

        // Initialize Select2/SelectWoo on rfm_segment select
        if ( $('#rfm_segment').length > 0 ) {
            // Prefer selectWoo (WooCommerce's version) for better compatibility
            if ( typeof $.fn.selectWoo !== 'undefined' ) {
                $('#rfm_segment').selectWoo({
                    placeholder: 'All Segments',
                    allowClear: true,
                    width: '300px',
                    minimumResultsForSearch: 0 // Always show search box
                });
            }
            // Fallback to select2 if selectWoo is not available
            else if ( typeof $.fn.select2 !== 'undefined' ) {
                $('#rfm_segment').select2({
                    placeholder: 'All Segments',
                    allowClear: true,
                    width: '300px',
                    minimumResultsForSearch: 0 // Always show search box
                });
            }
        }

        var params = getUrlVars();
        if ( typeof params['user_id']  !== "undefined" && params['page'] == 'pd-woo-customer' ) {
            pdwchs_woo_open_admin_menu( 'toplevel_page_pd-woo-dashboard', 'pd-customers-menu' );
        }

        if( typeof( params['page'] ) !== "undefined" && params['page'] !== null && ( params['page'] == 'pd-woo-searches' || params['page'] == 'pd-woo-trash' ) ) {
            pdwchs_woo_open_admin_menu( 'toplevel_page_pd-woo-dashboard', 'pd-sessions-menu', 'pd-sessions-tab' );
        }

        if( typeof( params['page'] ) !== "undefined" && params['page'] !== null && ( params['page'] == 'pd-woo-search-stats' || params['page'] == 'pd-woo-stats-spent' || params['page'] == 'pd-woo-product-view-stats' || params['page'] == 'pd-woo-top-selling-stats' || params['page'] == 'pd-woo-conversion-funnel-stats' ) ) {
            pdwchs_woo_open_admin_menu( 'toplevel_page_pd-woo-dashboard', 'pd-keyword-stats-menu', 'pd-stats-tab' );
        }

        if( typeof( params['pd_taxonomy'] ) !== "undefined" && params['pd_taxonomy'] !== null ) {
            pdwchs_woo_open_admin_menu( 'toplevel_page_pd-woo-dashboard', 'pd-keyword-stats-menu', 'pd-stats-tab' );
        }


        $('.pd-wcch-close').on('click', function() {
            var $formGroup = $(this).parents('.pd-wcch-form-group');

            // Hide the close button wrapper
            $formGroup.find('.pd-wcch-inpu-wrapper').hide();

            // Reset regular select fields
            $formGroup.find(".pd-wcch-select").prop('selectedIndex',0);

            // Reset Select2/SelectWoo fields (for product filter and country selector)
            var $select2Field = $formGroup.find(".pd-wcch-select");
            if ($select2Field.length > 0) {
                // Check if it's a Select2/SelectWoo field and reset it properly
                if ($select2Field.hasClass('select2-hidden-accessible')) {
                    $select2Field.val(null).trigger('change');
                }
            }

            // Reset form control inputs
            $formGroup.find('.pd-wcch-form-control').val('');
        });
        $('.pd-wcch-close-cus').on('click', function() {
            $(this).parents('.pd-wcch-form-group').find('.pd-wcch-inpu-wrapper').hide();
            $('.drp-buttons .cancelBtn').trigger('click');
        });
        $('.pd-wcch-select').on('change', function() {
            if(this.value.length > 0){
                $(this).parents('.pd-wcch-form-group').find('.pd-wcch-inpu-wrapper').show();
            }else{
				$(this).parents('.pd-wcch-form-group').find('.pd-wcch-inpu-wrapper').hide();
			}
        });

        $( window ).load(function() {
            $( ".pd-wcch-select" ).each(function() {
                var selectedCountry = $(this).children("option:selected").val();
                if(selectedCountry){
                    $(this).parents('.pd-wcch-form-group').find('.pd-wcch-inpu-wrapper').show();
                }
            });

            if ( $('#orders .pd-wcch-form-group #daterange').length > 0 ) {
                var daterange = $('#orders .pd-wcch-form-group #daterange').val();
                if(daterange){
                    $('#orders .pd-wcch-form-group .pd-wcch-close-cus-wrap').show();
                }
            }
        });

        $(function() {
            var pdwchsDrpConfig = {
                autoUpdateInput: false,
                opens: 'center',
                maxDate: moment(),
                locale: { cancelLabel: 'Clear' }
            };
            if ( typeof pdwchsAdminVars !== 'undefined' && pdwchsAdminVars.minDate ) {
                pdwchsDrpConfig.minDate = moment( pdwchsAdminVars.minDate );
            }
            $('input#daterange').daterangepicker( pdwchsDrpConfig, function() {
                $('input#daterange').parent('.pd-wcch-form-group').find('.pd-wcch-inpu-wrapper').show();
            });

            $('input#daterange').on('apply.daterangepicker', function(ev, picker) {
                $('input#start-date').val('');
                $('input#end-date').val('');
                $(this).val(picker.startDate.format('MM/DD/YYYY') + ' - ' + picker.endDate.format('MM/DD/YYYY'));
                $('input#start-date').val(picker.startDate.format('YYYY-MM-DD') + ' 00:00:00');
                $('input#end-date').val(picker.endDate.format('YYYY-MM-DD') + ' 23:59:59');
                $('input#daterange').parent('.pd-wcch-form-group').find('.pd-wcch-inpu-wrapper').show();
            });

            $('input#daterange').on('cancel.daterangepicker', function(ev, picker) {
                $(this).val('');
                $('input#start-date').val('');
                $('input#end-date').val('');
            });
        });

        //TOGGLE FONT AWESOME ON CLICK
        $('.toggle-section').click(function(){
            $(this).find('.dashicons').toggleClass('dashicons-arrow-up-alt2 dashicons-arrow-down-alt2');
            $(`#${ $(this).data("toggle-content") }`).stop().slideToggle();
        });
        $('.toggle-section').blur(function(){
            $(this).find('.dashicons').toggleClass('dashicons-arrow-up-alt2 dashicons-arrow-down-alt2');
            $(`#${ $(this).data("toggle-content") }`).stop().slideToggle();
        });

        $(".delete-custom-btn").on('click', function(e) {
            e.preventDefault();
            $('#daterange,.delete-custom-btn-confirm').removeClass('d-none');
            $(this).addClass('d-none');
        });

        $(".pd-toggle-btn").click(function(e){
            var target = $(this).attr("data-target");
            $(this).siblings(".pd-toggle-btn").removeClass("pd-active-btn");
            $(this).addClass("pd-active-btn");

            if(target=='pd-table-content'){
                $(this).parents(".pd-woo-panel").find(".pd-chart-content").hide();
            }else{
                $(this).parents(".pd-woo-panel").find(".pd-table-content").hide();
            }
            $(this).parents(".pd-woo-panel").find("."+target).show();

        });

        $('select#show-items').on('change', function() {
            if( this.value ){
                var currentUrl = window.location.href;
                var url = new URL(currentUrl);
                url.searchParams.set("show-items", this.value);
                var newUrl = url.href;
                window.location.href = newUrl;
            }
        });
		
		$('.send_weekly_orders_report_to').css('display','none');
		$('.send_weekly_searches_report_to').css('display','none');
		$('.send_weekly_most_viewed_report_to').css('display','none');

		var generate_weekly_orders_report = $("#generate_weekly_orders_report option:selected").val();
		if(generate_weekly_orders_report == 1){
			$('.send_weekly_orders_report_to').show();
		}
		
		var generate_weekly_searches_report = $("#generate_weekly_searches_report option:selected").val();
		if(generate_weekly_searches_report == 1){
			$('.send_weekly_searches_report_to').show();
		}
		
		var generate_weekly_most_viewed_report = $("#generate_weekly_most_viewed_report option:selected").val();
		if(generate_weekly_most_viewed_report == 1){
			$('.send_weekly_most_viewed_report_to').show();
		}
		
    });

    $win.load(function () {
        $('.pd-woo-main-container:nth-child(even)').after('<div class="pd-woo-clearfix"></div>');
    });

	$(document).on('change','#generate_weekly_orders_report',function(){
		var selectVal = $("#generate_weekly_orders_report option:selected").val();
		if(selectVal == 1){
			$('.send_weekly_orders_report_to').show();
		}else{
			$('.send_weekly_orders_report_to').hide();
		}
	});
	
	$(document).on('change','#generate_weekly_searches_report',function(){
		var selectVal = $("#generate_weekly_searches_report option:selected").val();
		if(selectVal == 1){
			$('.send_weekly_searches_report_to').show();
		}else{
			$('.send_weekly_searches_report_to').hide();
		}
	});
	
	$(document).on('change','#generate_weekly_most_viewed_report',function(){
		var selectVal = $("#generate_weekly_most_viewed_report option:selected").val();
		if(selectVal == 1){
			$('.send_weekly_most_viewed_report_to').show();
		}else{
			$('.send_weekly_most_viewed_report_to').hide();
		}
	});
	
	$(document).on('change','#num-of-days',function(){
		pdwchs_updateQueryString('num-of-days', this.value);
	});
	
	function pdwchs_updateQueryString(key, value) {
		if (history.pushState) {
			let searchParams = new URLSearchParams(window.location.search);
			searchParams.set(key, value);
			let newurl = window.location.protocol + "//" + window.location.host + window.location.pathname + '?' + searchParams.toString();
			window.history.pushState({path: newurl}, '', newurl);
			location.reload();
		}
	} 
	
})( jQuery );
/*
 *	Open Admin Menu
 */

function pdwchs_woo_open_admin_menu( menu_id = null, submenu_id = null, tab_id = null ) {
    jQuery( '#' + menu_id ).removeClass('wp-not-current-submenu').addClass('current wp-has-current-submenu');
    jQuery( '#' + menu_id + '>a' ).removeClass('wp-not-current-submenu').addClass('current wp-has-current-submenu');
    jQuery( '#' + menu_id + ' > ul.wp-submenu' ).css('position','static');
    jQuery( '#' + submenu_id ).closest('li').addClass('current');
    jQuery( '#' + tab_id ).addClass('nav-tab-active');
}

function getUrlVars() {
    var vars = {};
    var parts = window.location.href.replace(/[?&]+([^=&]+)=([^&]*)/gi, function(m,key,value) {
        vars[key] = value;
    });
    return vars;
}

/**
 * RFM Analysis Card - Recalculate Now Button
 */
jQuery(document).on('click', '.pdwchs-recalculate-rfm-btn', function(e) {
    e.preventDefault();

    var $btn = jQuery(this);
    var userId = $btn.data('user-id');

    // Disable button and show loading state
    $btn.prop('disabled', true).addClass('loading');
    $btn.find('.dashicons').addClass('dashicons-update-alt');

    // Get nonce - check for RFM-specific nonce first, then fall back to admin nonce
    var nonce = typeof pdwchs_rfm_nonce !== 'undefined' ? pdwchs_rfm_nonce :
                (typeof pdwchs_admin !== 'undefined' ? pdwchs_admin.nonce : '');

    jQuery.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'pdwchs_recalculate_rfm',
            user_id: userId,
            nonce: nonce
        },
        success: function(response) {
            if (response.success) {
                // Show success message
                var $card = $btn.closest('.pdwchs-rfm-analysis-card');

                // Create success message
                var $successMsg = jQuery('<div class="notice notice-success is-dismissible" style="margin: 15px 0;"><p>' + response.data.message + '</p></div>');
                $card.prepend($successMsg);

                // Auto-dismiss after 3 seconds
                setTimeout(function() {
                    $successMsg.fadeOut(function() {
                        jQuery(this).remove();
                    });
                }, 3000);

                // Reload page after a short delay to show updated RFM data
                setTimeout(function() {
                    window.location.reload();
                }, 1500);
            } else {
                // Show error message
                alert(response.data.message || 'Failed to recalculate RFM scores. Please try again.');

                // Re-enable button
                $btn.prop('disabled', false).removeClass('loading');
            }
        },
        error: function() {
            alert('An error occurred. Please try again.');

            // Re-enable button
            $btn.prop('disabled', false).removeClass('loading');
        }
    });
});


