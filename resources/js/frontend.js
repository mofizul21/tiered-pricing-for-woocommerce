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
	// Product page — order form: row cloning, price preview, validation.
	// =========================================================================

	function tpfwEnsureRow($form) {
		var $rows = $form.find('.tpfw-order-row');
		if (!$rows.length) { return; }

		var hasBlank = $rows.filter(function() {
			var qty   = ($(this).find('.tpfw-qty-input').val() || '').trim();
			var color = ($(this).find('.tpfw-color-select').val() || '').trim();
			return !qty && !color;
		}).length > 0;

		var $last    = $rows.last();
		var lastQty  = ($last.find('.tpfw-qty-input').val() || '').trim();

		if (lastQty && !hasBlank) {
			var $tbody = $form.find('.tpfw-order-rows');
			var idx    = $rows.length;
			var $clone = $last.clone();

			$clone.find('.tpfw-qty-input')
				.val('')
				.attr('name', 'tpfw_order_rows[' + idx + '][quantity]');
			$clone.find('.tpfw-color-select')
				.val('')
				.attr('name', 'tpfw_order_rows[' + idx + '][color]');
			$clone.find('.tpfw-custom-select').each(function(di) {
				$(this).val('').attr('name', 'tpfw_order_rows[' + idx + '][custom][' + di + ']');
			});

			$tbody.append($clone);
		}
	}

	function tpfwUpdatePrices($form) {
		var $iface   = $form.closest('.tpfw-pricing-interface');
		var tiers    = JSON.parse($iface.attr('data-pricing-table') || '[]');
		var symbol   = $iface.attr('data-currency-symbol') || '$';
		var totalQty = 0;

		$form.find('.tpfw-order-row').each(function() {
			totalQty += parseInt($(this).find('.tpfw-qty-input').val(), 10) || 0;
		});

		var tierPrice = 0;
		for (var i = 0; i < tiers.length; i++) {
			if (totalQty >= tiers[i].quantity) {
				tierPrice = parseFloat(tiers[i].price) || 0;
			}
		}

		var grandTotal = 0;
		$form.find('.tpfw-order-row').each(function() {
			var qty = parseInt($(this).find('.tpfw-qty-input').val(), 10) || 0;
			if (qty <= 0) { return; }

			var optionsTotal = 0;
			$(this).find('.tpfw-custom-select').each(function() {
				var p = parseFloat($(this).find(':selected').attr('data-price')) || 0;
				optionsTotal += p;
			});

			grandTotal += qty * (tierPrice + optionsTotal);
		});

		var $summary = $form.find('.tpfw-price-summary');
		if (totalQty > 0 && tierPrice > 0) {
			$summary.text('Estimated total: ' + symbol + grandTotal.toFixed(2)).show();
		} else {
			$summary.hide();
		}
	}

	$(document).on('input change', '.tpfw-qty-input', function() {
		var $form = $(this).closest('.tpfw-order-form');
		if ($form.length) { tpfwEnsureRow($form); tpfwUpdatePrices($form); }
	});

	$(document).on('change', '.tpfw-color-select', function() {
		var $form = $(this).closest('.tpfw-order-form');
		if ($form.length) { tpfwEnsureRow($form); }
	});

	$(document).on('change', '.tpfw-custom-select', function() {
		var $form = $(this).closest('.tpfw-order-form');
		if ($form.length) { tpfwUpdatePrices($form); }
	});

	$(document).on('submit', '.tpfw-order-form', function(e) {
		var $form            = $(this);
		var $interface       = $form.closest('.tpfw-pricing-interface');
		var minimumQuantity  = parseInt($interface.data('min-quantity'), 10) || 0;
		var totalQuantity    = 0;
		var hasAtLeastOneRow = false;
		var missingColor     = false;
		var missingRequired  = false;
		var missingRequiredLabel = '';

		$form.find('.tpfw-order-row').each(function() {
			var qty   = ($(this).find('.tpfw-qty-input').val() || '').trim();
			var color = ($(this).find('.tpfw-color-select').val() || '').trim();

			if (!qty && !color) { return; }

			var n = parseInt(qty, 10);
			if (Number.isInteger(n) && n > 0) {
				hasAtLeastOneRow = true;
				totalQuantity += n;
				if (!color) { missingColor = true; }

				$(this).find('.tpfw-custom-select[data-required="1"]').each(function() {
					if (!($(this).val() || '').trim()) {
						missingRequired = true;
						missingRequiredLabel = $(this).closest('.tpfw-select-wrap').find('.tpfw-select-label').text().replace('*', '').trim();
					}
				});
			}
		});

		if (!hasAtLeastOneRow) {
			e.preventDefault();
			window.alert('Please fill out the Qty.');
			return;
		}
		if (missingColor) {
			e.preventDefault();
			window.alert('Please select a color for each row.');
			return;
		}
		if (missingRequired) {
			e.preventDefault();
			window.alert('Please select an option for "' + missingRequiredLabel + '".');
			return;
		}
		if (totalQuantity < minimumQuantity) {
			e.preventDefault();
			window.alert('The total quantity must be at least ' + minimumQuantity + '.');
			return;
		}

		// Strip blank rows before posting.
		$form.find('.tpfw-order-row').each(function() {
			var qty   = ($(this).find('.tpfw-qty-input').val() || '').trim();
			var color = ($(this).find('.tpfw-color-select').val() || '').trim();
			if (!qty && !color) { $(this).remove(); }
		});

		var $btn = $form.find('[type="submit"]');
		if ($btn.length) {
			$btn.prop('disabled', true).addClass('tpfw-loading').text('Adding to cart…');
		}
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
