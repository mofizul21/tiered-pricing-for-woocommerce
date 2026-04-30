jQuery(function ($) {
	'use strict';

	// =========================================================================
	// WooLentor filter — auto-AJAX for button-mode groups.
	//
	// Groups configured with apply-action="button" only respond to the
	// "Apply All" button. Individual changes do nothing because
	// processFilterRealApplyAction() returns 'button' for all items in such
	// groups, and WooLentor skips AJAX.
	//
	// We auto-click the group's Apply All button on any filter interaction.
	// jQuery .on() (not native addEventListener) is required so that
	// jQuery-triggered change events from the jQuery UI price slider are also
	// caught — native addEventListener misses those.
	//
	// Change handler covers: checkboxes, selects, price slider inputs.
	// Keyup handler covers: search text field real-time input.
	// =========================================================================

	function wlpfAutoApply(target) {
		var $filterWrap = $(target).closest('.wlpf-filter-wrap');
		if (!$filterWrap.length || $filterWrap.attr('data-wlpf-group-item') !== '1') { return; }

		var $groupWrap = $filterWrap.closest('.wlpf-group-wrap');
		if (!$groupWrap.length || $groupWrap.attr('data-wlpf-apply-action') !== 'button') { return; }

		// Defer so WooLentor's own handler (registered earlier, fires first)
		// finishes updating data-wlpf-selected-terms / data-wlpf-range-*
		// before the group action reads them.
		setTimeout(function () {
			$groupWrap.find('.wlpf-group-apply-action-button').first().trigger('click');
		}, 0);
	}

	$(document).on('change', function (e) { wlpfAutoApply(e.target); });
	$(document).on('keyup', '.wlpf-search-filter .wlpf-search-field', function () { wlpfAutoApply(this); });

	// =========================================================================
	// Product page — order form submit handler (external fallback validation).
	// The inline <script> in the shortcode handles the primary logic; this
	// catches any edge-case submission that bypasses the inline handler.
	// =========================================================================

	$(document).on('submit', '.tpfw-order-form', function () {
		var $form = $(this);
		var $interface = $form.closest('.tpfw-pricing-interface');
		var minimumQuantity = parseInt($interface.data('min-quantity'), 10) || 0;
		var totalQuantity = 0;
		var hasAtLeastOneRow = false;
		var missingColor = false;

		// Validation pass — skip empty rows but never remove them here.
		// Rows are only stripped after all checks pass to avoid disappearing
		// rows when the user dismisses an alert and corrects their input.
		$form.find('.tpfw-order-row').each(function () {
			var qty   = ($(this).find('.tpfw-qty-input').val() || '').trim();
			var color = ($(this).find('.tpfw-color-select').val() || '').trim();

			if (!qty && !color) { return; }

			var n = parseInt(qty, 10);
			if (Number.isInteger(n) && n > 0) {
				hasAtLeastOneRow = true;
				totalQuantity += n;
				if (!color) { missingColor = true; }
			}
		});

		if (!hasAtLeastOneRow) {
			window.alert('Please fill out the Qty.');
			return false;
		}

		if (missingColor) {
			window.alert('Please select a color for each row.');
			return false;
		}

		if (totalQuantity < minimumQuantity) {
			window.alert('The total quantity must be at least ' + minimumQuantity + '.');
			return false;
		}

		// All checks passed — strip blank rows before the form posts.
		$form.find('.tpfw-order-row').each(function () {
			var qty   = ($(this).find('.tpfw-qty-input').val() || '').trim();
			var color = ($(this).find('.tpfw-color-select').val() || '').trim();
			if (!qty && !color) { $(this).remove(); }
		});

		return true;
	});

	// =========================================================================
	// Checkout page — Date in Hand: open native picker on any click.
	// Delegated binding survives WooCommerce AJAX checkout DOM refreshes.
	// showPicker() is called on pointerdown (before the native focus/click
	// sequence) so the browser doesn't suppress a second programmatic open.
	// =========================================================================

	$(document).on('click', '#tpfw_date_in_hand', function () {
		if (typeof this.showPicker === 'function') {
			try { this.showPicker(); } catch (e) {}
		}
	});

	// =========================================================================
	// Checkout page — logo art file upload via AJAX.
	// Uploads the file immediately on selection so no multipart form changes
	// are needed on WooCommerce's own AJAX checkout submission.
	// The resulting attachment ID is stored in a hidden field and saved with
	// the order via CheckoutFields::save_fields().
	// =========================================================================

	var $fileInput = $('#tpfw_logo_file');
	var $hiddenId  = $('#tpfw_logo_attachment_id');
	var $status    = $('.tpfw-upload-status');

	if ($fileInput.length && typeof tpfwCheckout !== 'undefined') {

		$fileInput.on('change', function () {
			var file = this.files[0];
			if (!file) {
				return;
			}

			$status.removeClass('tpfw-upload-ok tpfw-upload-error').text(tpfwCheckout.i18n.uploading);
			$hiddenId.val('');

			var formData = new FormData();
			formData.append('action', 'tpfw_upload_logo');
			formData.append('nonce', tpfwCheckout.nonce);
			formData.append('tpfw_logo_file', file);

			$.ajax({
				url:         tpfwCheckout.ajaxUrl,
				type:        'POST',
				data:        formData,
				processData: false,
				contentType: false,
				success: function (response) {
					if (response.success) {
						$hiddenId.val(response.data.attachment_id);
						$status
							.addClass('tpfw-upload-ok')
							.html(
								tpfwCheckout.i18n.uploaded + ': <strong>' +
								$('<span>').text(response.data.filename).html() +
								'</strong> &nbsp;<a href="#" class="tpfw-remove-file">' +
								tpfwCheckout.i18n.remove + '</a>'
							);
					} else {
						$status
							.addClass('tpfw-upload-error')
							.text(response.data.message || tpfwCheckout.i18n.error);
					}
				},
				error: function () {
					$status.addClass('tpfw-upload-error').text(tpfwCheckout.i18n.error);
				},
			});
		});

		$(document).on('click', '.tpfw-remove-file', function (e) {
			e.preventDefault();
			$fileInput.val('');
			$hiddenId.val('');
			$status.removeClass('tpfw-upload-ok tpfw-upload-error').text('');
		});
	}

	// =========================================================================
	// Request Info — modal open / close and AJAX form submission.
	// =========================================================================

	function tpfwRiClose() {
		$('#tpfw-request-info-modal').attr('aria-hidden', 'true').fadeOut(200);
		$('body').removeClass('tpfw-modal-open');
	}

	$(document).on('click', '.tpfw-request-info-btn', function () {
		$('#tpfw-request-info-modal').attr('aria-hidden', 'false').fadeIn(200);
		$('body').addClass('tpfw-modal-open');
	});

	$(document).on('click', '.tpfw-ri-close, .tpfw-ri-overlay', function () {
		tpfwRiClose();
	});

	$(document).on('keydown', function (e) {
		if (e.key === 'Escape' && $('#tpfw-request-info-modal').is(':visible')) {
			tpfwRiClose();
		}
	});

	$(document).on('submit', '#tpfw-request-info-form', function (e) {
		e.preventDefault();

		if (typeof tpfwRequestInfo === 'undefined') {
			return;
		}

		var $form = $(this);
		var $btn  = $form.find('.tpfw-ri-submit');
		var $msg  = $('#tpfw-ri-message');

		$btn.prop('disabled', true).text(tpfwRequestInfo.i18n.sending);
		$msg.removeClass('tpfw-ri-success tpfw-ri-error').text('').hide();

		$.post(tpfwRequestInfo.ajaxUrl, $form.serialize(), function (response) {
			if (response.success) {
				$msg.addClass('tpfw-ri-success').text(response.data.message).show();
				$form[0].reset();
			} else {
				$msg.addClass('tpfw-ri-error').text(response.data.message).show();
			}
			$btn.prop('disabled', false).text(tpfwRequestInfo.i18n.submit);
		}).fail(function () {
			$msg.addClass('tpfw-ri-error').text(tpfwRequestInfo.i18n.error).show();
			$btn.prop('disabled', false).text(tpfwRequestInfo.i18n.submit);
		});
	});
});
