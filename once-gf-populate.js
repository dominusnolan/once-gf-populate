/**
 * Frontend AJAX handler for Store Name, Brand, and Form field population.
 * Listens to State and Brand field changes and updates dropdowns via AJAX.
 */
(function ($) {
	'use strict';

	$(document).ready(function () {
		if (typeof onceGfPopulate === 'undefined') return;

		var config = onceGfPopulate;
		var formId = config.formId;
		var stateFieldId = config.stateFieldId;
		var storeFieldId = config.storeFieldId;
		var brandFieldId = config.brandFieldId;
		var formFieldId = config.formFieldId;

		var stateFieldSelector = '#input_' + formId + '_' + stateFieldId;
		var storeFieldSelector = '#input_' + formId + '_' + storeFieldId;
		var brandFieldSelector = '#input_' + formId + '_' + brandFieldId;
		var formFieldSelector = '#input_' + formId + '_' + formFieldId;

		function updateStoreField(choices) {
			var $storeField = $(storeFieldSelector);
			if ($storeField.length === 0) return;
			$storeField.empty();
			$storeField.append($('<option>', {value: '', text: 'Please Select'}));
			if (choices && choices.length > 0) {
				$.each(choices, function (_, choice) {
					$storeField.append($('<option>', {
						value: choice.value,
						text: choice.text
					}));
				});
			}
			$storeField.trigger('change');
		}

		function updateBrandField(choices) {
			var $brandField = $(brandFieldSelector);
			if ($brandField.length === 0) return;
			$brandField.empty();
			$brandField.append($('<option>', {value: '', text: 'Please Select'}));
			if (choices && choices.length > 0) {
				$.each(choices, function (_, choice) {
					$brandField.append($('<option>', {
						value: choice.value,
						text: choice.text
					}));
				});
			}
			$brandField.trigger('change');
		}

		function updateFormField(choices) {
			var $formField = $(formFieldSelector);
			if ($formField.length === 0) return;
			$formField.empty();
			$formField.append($('<option>', {value: '', text: 'Please Select'}));
			if (choices && choices.length > 0) {
				$.each(choices, function (_, choice) {
					$formField.append($('<option>', {
						value: choice.value,
						text: choice.text
					}));
				});
			}
			$formField.trigger('change');
		}

		function fetchStores(state) {
			if (!state) { updateStoreField([]); return; }
			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'once_gf_populate_get_stores',
					nonce: config.nonce,
					state: state
				},
				cache: false,
				success: function (response) {
					if (response.success && response.data && response.data.choices) {
						updateStoreField(response.data.choices);
					} else {
						updateStoreField([]);
					}
				},
				error: function () {
					updateStoreField([]);
				}
			});
		}

		function fetchBrands(state) {
			if (!state) { updateBrandField([]); return; }
			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'once_gf_populate_get_brands',
					nonce: config.nonce,
					state: state
				},
				cache: false,
				success: function (response) {
					if (response.success && response.data && response.data.choices) {
						updateBrandField(response.data.choices);
					} else {
						updateBrandField([]);
					}
				},
				error: function () {
					updateBrandField([]);
				}
			});
		}

		function fetchForms(brand, state) {
			if (!brand || !state) {
				updateFormField([]);
				return;
			}
			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'once_gf_populate_get_forms',
					nonce: config.nonce,
					brand: brand,
					state: state
				},
				cache: false,
				success: function (response) {
					if (response.success && response.data && response.data.choices) {
						updateFormField(response.data.choices);
					} else {
						updateFormField([]);
					}
				},
				error: function () {
					updateFormField([]);
				}
			});
		}

		$(document).on('change', stateFieldSelector, function () {
			var selectedState = $(this).val();
			var selectedBrand = $(brandFieldSelector).val();
			fetchStores(selectedState);
			fetchBrands(selectedState);
			fetchForms(selectedBrand, selectedState);
		});

		$(document).on('change', brandFieldSelector, function () {
			var selectedBrand = $(this).val();
			var selectedState = $(stateFieldSelector).val();
			fetchForms(selectedBrand, selectedState);
		});
	});
})(jQuery);