jQuery(function ($) {
	'use strict';

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

		$form.find('.tpfw-order-row').each(function () {
			var qty = $(this).find('.tpfw-qty-input').val();
			var color = $(this).find('.tpfw-color-select').val();

			if (qty || color) {
				hasAtLeastOneRow = true;
				var n = parseInt(qty, 10);
				if (Number.isInteger(n) && n > 0) {
					totalQuantity += n;
				}
			} else {
				$(this).remove();
			}
		});

		if (!hasAtLeastOneRow) {
			window.alert('Please fill out the Qty.');
			return false;
		}

		if (totalQuantity < minimumQuantity) {
			window.alert('The total quantity must be at least ' + minimumQuantity + '.');
			return false;
		}

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
});
