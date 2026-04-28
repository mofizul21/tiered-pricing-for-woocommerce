jQuery(function ($) {
	'use strict';

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
});
