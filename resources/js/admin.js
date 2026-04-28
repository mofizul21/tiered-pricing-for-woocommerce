jQuery(function($) {
	"use strict";

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
});
