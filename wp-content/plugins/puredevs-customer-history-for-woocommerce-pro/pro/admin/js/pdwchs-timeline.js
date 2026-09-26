/**
 * Customer Activity Timeline — type filter and "Load older" paging.
 *
 * The presence of .pdwchs-timeline-panel is the switch; this file never reads the
 * page query argument.
 *
 * The endpoint returns server-escaped row HTML, so .append() is correct here and
 * .text() construction is not. Never concatenate a value out of response.data into
 * markup in this file — the two strategies must not be mixed.
 *
 * @since 3.1.0
 */
(function ($) {
	if (typeof pdwchs_timeline_vars === 'undefined' || !pdwchs_timeline_vars.customerId) { return; }
	var $panel = $('.pdwchs-timeline-panel');
	if (!$panel.length) { return; }

	var $list   = $panel.find('.pdwchs-timeline-list');
	var $more   = $panel.find('.pdwchs-timeline-more');
	var $status = $panel.find('.pdwchs-timeline-status-msg');
	var currentType = '';
	var currentCursor = $more.data('cursor') || '';
	var isLoading = false;

	function load(replace) {
		if (isLoading) { return; }
		isLoading = true;
		$status.text(pdwchs_timeline_vars.i18n.loading);
		$panel.addClass('is-loading');

		$.post(pdwchs_timeline_vars.ajaxurl, {
			action: 'pdwchs_timeline_load_more',
			nonce: pdwchs_timeline_vars.nonce,
			user_id: pdwchs_timeline_vars.customerId,
			before: replace ? '' : currentCursor,
			type: currentType
		}).done(function (response) {
			if (!response || !response.success) {
				$status.text(pdwchs_timeline_vars.i18n.error);
				return;
			}
			var d = response.data;
			if (replace) { $list.empty(); }
			$list.append(d.html);                       // server-escaped markup — see the file header
			currentCursor = d.next_cursor || '';
			$more.toggle(!!d.has_more).data('cursor', currentCursor);
			if (replace && !d.count) {
				$status.text(pdwchs_timeline_vars.i18n.emptyFilter);
			} else if (!d.has_more) {
				$status.text(pdwchs_timeline_vars.i18n.noMore);
			} else {
				$status.text('');
			}
		}).fail(function () {
			$status.text(pdwchs_timeline_vars.i18n.error);
		}).always(function () {
			isLoading = false;
			$panel.removeClass('is-loading');
		});
	}

	// A cursor is only meaningful inside one filtered stream, so switching a filter
	// resets it to '' and replaces the list with the newest page of that stream.
	$panel.on('click', '.pdwchs-timeline-filter', function () {
		var $btn = $(this);
		if ($btn.hasClass('is-active')) { return; }
		$panel.find('.pdwchs-timeline-filter').removeClass('is-active');
		$btn.addClass('is-active');
		currentType = String($btn.data('type') || '');
		currentCursor = '';
		load(true);
	});

	$panel.on('click', '.pdwchs-timeline-more', function () { load(false); });
})(jQuery);
