jQuery(function($) {
	"use strict";

	// =========================================================================
	// Pricing table — add / remove rows
	// =========================================================================

	function addPricingRow($wrap) {
		const $tbody = $wrap.find("tbody");
		const $lastRow = $tbody.find(".tpfw-pricing-row").last();

		if (!$tbody.length || !$lastRow.length) {
			return;
		}

		const index = parseInt($wrap.find(".tpfw-pricing-row-count").val(), 10) || $tbody.find("tr").length;
		const $newRow = $lastRow.clone();

		$newRow.find('input').each(function() {
			const $input = $(this);
			const name = $input.attr('name') || '';
			const updatedName = name.replace(/\[\d+\]/, '[' + index + ']');

			$input.attr('name', updatedName).val('');
		});

		$tbody.append($newRow);
		$wrap.find(".tpfw-pricing-row-count").val(index + 1);
	}

	$(document).on("click", ".tpfw-add-pricing-row", function() {
		addPricingRow($(this).closest(".tpfw-product-pricing-group"));
	});

	$(document).on("click", ".tpfw-remove-pricing-row", function() {
		const $wrap = $(this).closest(".tpfw-product-pricing-group");
		const $tbody = $(this).closest("tbody");

		$(this).closest("tr").remove();

		if (!$tbody.find("tr").length) {
			addPricingRow($wrap);
		}
	});

	// =========================================================================
	// Custom dropdown groups — add / remove groups and options
	// =========================================================================

	function getNextGroupIndex($wrap) {
		let max = -1;
		$wrap.find(".tpfw-dropdown-group").each(function() {
			const di = parseInt($(this).attr("data-di"), 10);
			if (!isNaN(di) && di > max) { max = di; }
		});
		return max + 1;
	}

	function getNextOptionIndex($group) {
		let max = -1;
		$group.find(".tpfw-dropdown-option-row input[name]").each(function() {
			const m = ($(this).attr("name") || "").match(/\[options\]\[(\d+)\]/);
			if (m) {
				const oi = parseInt(m[1], 10);
				if (oi > max) { max = oi; }
			}
		});
		return max + 1;
	}

	$(document).on("click", ".tpfw-add-dropdown-group", function() {
		const $wrap = $(this).closest(".tpfw-custom-dropdowns-inner").find(".tpfw-custom-dropdowns-wrap");
		const di = getNextGroupIndex($wrap);

		const $group = $('<div class="tpfw-dropdown-group" data-di="' + di + '"></div>');

		const labelPlaceholder = "Label (e.g. Size)";

		$group.append(
			'<div class="tpfw-dropdown-group-header">' +
				'<input type="text" name="tpfw_custom_dropdowns[' + di + '][label]" value="" placeholder="' + labelPlaceholder + '" class="regular-text tpfw-dropdown-label">' +
				'<label class="tpfw-dropdown-required-label"><input type="checkbox" name="tpfw_custom_dropdowns[' + di + '][required]" value="1"> Required</label>' +
				'<button type="button" class="button-link-delete tpfw-remove-dropdown-group">Remove Group</button>' +
			'</div>' +
			'<table class="widefat striped tpfw-dropdown-options-table">' +
				'<thead><tr><th>Option Label</th><th>Price Add-on (+$)</th><th>Actions</th></tr></thead>' +
				'<tbody>' +
					'<tr class="tpfw-dropdown-option-row">' +
						'<td><input type="text" name="tpfw_custom_dropdowns[' + di + '][options][0][label]" value="" class="regular-text"></td>' +
						'<td><input type="number" min="0" step="0.01" placeholder="0.00" name="tpfw_custom_dropdowns[' + di + '][options][0][price]" value="" class="short wc_input_price"></td>' +
						'<td><button type="button" class="button-link-delete tpfw-remove-dropdown-option">Remove</button></td>' +
					'</tr>' +
				'</tbody>' +
			'</table>' +
			'<button type="button" class="button tpfw-add-dropdown-option" style="margin:4px 0 0;">Add Option</button>'
		);

		$wrap.append($group);
	});

	$(document).on("click", ".tpfw-remove-dropdown-group", function() {
		$(this).closest(".tpfw-dropdown-group").remove();
	});

	$(document).on("click", ".tpfw-add-dropdown-option", function() {
		const $group = $(this).closest(".tpfw-dropdown-group");
		const di = $group.attr("data-di");
		const oi = getNextOptionIndex($group);
		const $tbody = $group.find("tbody");

		const $row = $(
			'<tr class="tpfw-dropdown-option-row">' +
				'<td><input type="text" name="tpfw_custom_dropdowns[' + di + '][options][' + oi + '][label]" value="" class="regular-text"></td>' +
				'<td><input type="number" min="0" step="0.01" placeholder="0.00" name="tpfw_custom_dropdowns[' + di + '][options][' + oi + '][price]" value="" class="short wc_input_price"></td>' +
				'<td><button type="button" class="button-link-delete tpfw-remove-dropdown-option">Remove</button></td>' +
			'</tr>'
		);

		$tbody.append($row);
	});

	$(document).on("click", ".tpfw-remove-dropdown-option", function() {
		const $tbody = $(this).closest("tbody");
		$(this).closest("tr").remove();

		if (!$tbody.find("tr").length) {
			const $group = $tbody.closest(".tpfw-dropdown-group");
			const di = $group.attr("data-di");
			$tbody.append(
				'<tr class="tpfw-dropdown-option-row">' +
					'<td><input type="text" name="tpfw_custom_dropdowns[' + di + '][options][0][label]" value="" class="regular-text"></td>' +
					'<td><input type="number" min="0" step="0.01" placeholder="0.00" name="tpfw_custom_dropdowns[' + di + '][options][0][price]" value="" class="short wc_input_price"></td>' +
					'<td><button type="button" class="button-link-delete tpfw-remove-dropdown-option">Remove</button></td>' +
				'</tr>'
			);
		}
	});
});
