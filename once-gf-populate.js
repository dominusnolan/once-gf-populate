/**
 * Frontend AJAX handler for Store Name, Brand, Form, Product Type, and Product Details field population.
 * Listens to State, Brand, Form, and Product Type field changes and updates dropdowns via AJAX.
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
		var productTypeFieldId = config.productTypeFieldId;
		var productDetailsFieldId = config.productDetailsFieldId;

		var stateFieldSelector = '#input_' + formId + '_' + stateFieldId;
		var storeFieldSelector = '#input_' + formId + '_' + storeFieldId;
		var brandFieldSelector = '#input_' + formId + '_' + brandFieldId;
		var formFieldSelector = '#input_' + formId + '_' + formFieldId;
		var productTypeFieldSelector = '#input_' + formId + '_' + productTypeFieldId;
		var productDetailsFieldSelector = '#input_' + formId + '_' + productDetailsFieldId;

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

		function updateProductTypeField(choices) {
			var $productTypeField = $(productTypeFieldSelector);
			if ($productTypeField.length === 0) return;
			$productTypeField.empty();
			$productTypeField.append($('<option>', {value: '', text: 'Please Select'}));
			if (choices && choices.length > 0) {
				$.each(choices, function(_, choice) {
					$productTypeField.append($('<option>', {
						value: choice.value,
						text: choice.text
					}));
				});
			}
			$productTypeField.trigger('change');
		}

		function updateProductDetailsField(choices) {
			var $productDetailsField = $(productDetailsFieldSelector);
			if ($productDetailsField.length === 0) return;
			$productDetailsField.empty();
			$productDetailsField.append($('<option>', {value: '', text: 'Please Select'}));
			if (choices && choices.length > 0) {
				$.each(choices, function(_, choice) {
					$productDetailsField.append($('<option>', {
						value: choice.value,
						text: choice.text
					}));
				});
			}
			$productDetailsField.trigger('change');
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

		function fetchProductTypes(brand, state, form) {
			if (!brand || !state || !form) {
				updateProductTypeField([]);
				return;
			}
			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'once_gf_populate_get_product_types',
					nonce: config.nonce,
					brand: brand,
					state: state,
					form: form
				},
				cache: false,
				success: function(response) {
					if (response.success && response.data && response.data.choices) {
						updateProductTypeField(response.data.choices);
					} else {
						updateProductTypeField([]);
					}
				},
				error: function () {
					updateProductTypeField([]);
				}
			});
		}

		function fetchProductDetails(brand, state, form, productType) {
			if (!brand || !state || !form || !productType) {
				updateProductDetailsField([]);
				return;
			}
			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'once_gf_populate_get_product_details',
					nonce: config.nonce,
					brand: brand,
					state: state,
					form: form,
					product_type: productType
				},
				cache: false,
				success: function(response) {
					if (response.success && response.data && response.data.choices) {
						updateProductDetailsField(response.data.choices);
					} else {
						updateProductDetailsField([]);
					}
				},
				error: function () {
					updateProductDetailsField([]);
				}
			});
		}

		$(document).on('change', stateFieldSelector, function () {
			var selectedState = $(this).val();
			var selectedBrand = $(brandFieldSelector).val();
			var selectedForm = $(formFieldSelector).val();
			fetchStores(selectedState);
			fetchBrands(selectedState);
			fetchForms(selectedBrand, selectedState);
			updateProductTypeField([]); // Reset Product Type
			updateProductDetailsField([]); // Reset Product Details
		});

		$(document).on('change', brandFieldSelector, function () {
			var selectedBrand = $(this).val();
			var selectedState = $(stateFieldSelector).val();
			var selectedForm = $(formFieldSelector).val();
			fetchForms(selectedBrand, selectedState);
			updateProductTypeField([]); // Reset Product Type
			updateProductDetailsField([]); // Reset Product Details
		});

		// When "Form" changes, update Product Type
		$(document).on('change', formFieldSelector, function () {
			var selectedForm = $(this).val();
			var selectedState = $(stateFieldSelector).val();
			var selectedBrand = $(brandFieldSelector).val();
			fetchProductTypes(selectedBrand, selectedState, selectedForm);
			updateProductDetailsField([]); // Reset Product Details
		});

		// When "Product Type" changes, update Product Details
		$(document).on('change', productTypeFieldSelector, function () {
			var selectedProductType = $(this).val();
			var selectedState = $(stateFieldSelector).val();
			var selectedBrand = $(brandFieldSelector).val();
			var selectedForm = $(formFieldSelector).val();
			fetchProductDetails(selectedBrand, selectedState, selectedForm, selectedProductType);
		});
	});
})(jQuery);