/**
 * Customer notes panel (R15-05).
 *
 * Consumes the AJAX contract registered by PDWCHS_Customer_Notes (R15-04B).
 * Response values are inserted via .text() only — never .html().
 */
(function ($) {
	if (typeof pdwchs_notes_vars === 'undefined' || !pdwchs_notes_vars.customerId) {
		return;
	}
	var $panel = $('.pdwchs-notes-panel');
	if (!$panel.length) {
		return;
	}

	function showError(message) {
		$panel.find('.pdwchs-crm-error').text(message).show();
	}
	function clearError() {
		$panel.find('.pdwchs-crm-error').hide().text('');
	}

	$panel.on('click', '.pdwchs-note-add-btn', function () {
		clearError();
		var $textarea = $panel.find('.pdwchs-note-input');
		var noteText = $.trim($textarea.val());
		if (!noteText) {
			showError(pdwchs_notes_vars.i18n.emptyNote);
			return;
		}
		$.post(pdwchs_notes_vars.ajaxurl, {
			action: 'pdwchs_add_customer_note',
			nonce: pdwchs_notes_vars.nonce,
			customer_id: pdwchs_notes_vars.customerId,
			note_text: noteText
		}).done(function (response) {
			if (!response.success) {
				showError((response.data && response.data.message) || pdwchs_notes_vars.i18n.errorFallback);
				return;
			}
			var d = response.data;
			var $row = $('<li class="pdwchs-note-row"></li>').attr('data-note-id', d.id);
			var $meta = $('<div class="pdwchs-note-meta"></div>');
			$meta.append($('<span class="pdwchs-note-author"></span>').text(d.author));
			$meta.append($('<span class="pdwchs-note-date"></span>').text(d.date_display).attr('title', d.time_ago));
			$meta.append($('<button type="button" class="pdwchs-note-delete">&times;</button>'));
			$row.append($meta);
			$row.append($('<div class="pdwchs-note-body"></div>').text(d.note_text));
			$panel.find('.pdwchs-notes-list').prepend($row);
			$textarea.val('');
			$panel.find('.pdwchs-notes-count').text(d.count);
			$panel.find('.pdwchs-notes-empty').hide();
		}).fail(function () {
			showError(pdwchs_notes_vars.i18n.errorFallback);
		});
	});

	$panel.on('click', '.pdwchs-note-delete', function () {
		if (!window.confirm(pdwchs_notes_vars.i18n.confirmDeleteNote)) {
			return;
		}
		var $row = $(this).closest('.pdwchs-note-row');
		var noteId = $row.data('note-id');
		$.post(pdwchs_notes_vars.ajaxurl, {
			action: 'pdwchs_delete_customer_note',
			nonce: pdwchs_notes_vars.nonce,
			note_id: noteId
		}).done(function (response) {
			if (!response.success) {
				showError((response.data && response.data.message) || pdwchs_notes_vars.i18n.errorFallback);
				return;
			}
			$row.remove();
			$panel.find('.pdwchs-notes-count').text(response.data.count);
			if (!response.data.count) {
				$panel.find('.pdwchs-notes-empty').show();
			}
		}).fail(function () {
			showError(pdwchs_notes_vars.i18n.errorFallback);
		});
	});
})(jQuery);
