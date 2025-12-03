/**
 * Frontend AJAX handler for Store Name, Brand, Form, Product Type, Product Details, Manufactured By, and Return Reason field population.
 * Listens to State, Brand, Form, and Product Type field changes and updates dropdowns via AJAX with loading indicators.
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
		var manufacturedByFieldId = config.manufacturedByFieldId;
		var returnReasonFieldId = config.returnReasonFieldId;

		var stateFieldSelector = '#input_' + formId + '_' + stateFieldId;
		var storeFieldSelector = '#input_' + formId + '_' + storeFieldId;
		var brandFieldSelector = '#input_' + formId + '_' + brandFieldId;
		var formFieldSelector = '#input_' + formId + '_' + formFieldId;
		var productTypeFieldSelector = '#input_' + formId + '_' + productTypeFieldId;
		var productDetailsFieldSelector = '#input_' + formId + '_' + productDetailsFieldId;
		var manufacturedByFieldSelector = '#input_' + formId + '_' + manufacturedByFieldId;
		var returnReasonFieldSelector = '#input_' + formId + '_' + returnReasonFieldId;

		function showLoading($field) {
			if ($field.length === 0) return;
			$field.prop('disabled', true);
			$field.empty();
			$field.append($('<option>', {value: '', text: 'Loading...'}));
		}

		function hideLoading($field) {
			if ($field.length === 0) return;
			$field.prop('disabled', false);
		}

		function updateStoreField(choices, preserveValue) {
			var $storeField = $(storeFieldSelector);
			if ($storeField.length === 0) return;
			
			// Determine which value to restore
			var previousValue = preserveValue !== undefined ? preserveValue : $storeField.data('selected') || $storeField.val();
			
			hideLoading($storeField);
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
			
			// Restore previous value if it exists in the new choices (compare as strings for post IDs)
			if (previousValue && $storeField.find('option[value="' + previousValue + '"]').length > 0) {
				$storeField.val(previousValue);
				$storeField.data('selected', previousValue);
			} else {
				$storeField.val('');
				$storeField.data('selected', '');
			}
			
			// Don't trigger change if we're restoring a value to prevent cascading resets
			if (!preserveValue) {
				$storeField.trigger('change');
			}
		}

		function updateBrandField(choices, preserveValue) {
			var $brandField = $(brandFieldSelector);
			if ($brandField.length === 0) return;
			
			// Determine which value to restore
			var previousValue = preserveValue !== undefined ? preserveValue : $brandField.data('selected') || $brandField.val();
			
			hideLoading($brandField);
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
			
			// Restore previous value if it exists in the new choices (taxonomy name/slug comparison)
			if (previousValue && $brandField.find('option[value="' + previousValue + '"]').length > 0) {
				$brandField.val(previousValue);
				$brandField.data('selected', previousValue);
			} else {
				$brandField.val('');
				$brandField.data('selected', '');
			}
			
			// Don't trigger change if we're restoring a value to prevent cascading resets
			if (!preserveValue) {
				$brandField.trigger('change');
			}
		}

		function updateFormField(choices, preserveValue) {
			var $formField = $(formFieldSelector);
			if ($formField.length === 0) return;
			
			// Determine which value to restore
			var previousValue = preserveValue !== undefined ? preserveValue : $formField.data('selected') || $formField.val();
			
			hideLoading($formField);
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
			
			// Restore previous value if it exists in the new choices (taxonomy name/slug comparison)
			if (previousValue && $formField.find('option[value="' + previousValue + '"]').length > 0) {
				$formField.val(previousValue);
				$formField.data('selected', previousValue);
			} else {
				$formField.val('');
				$formField.data('selected', '');
			}
			
			// Don't trigger change if we're restoring a value to prevent cascading resets
			if (!preserveValue) {
				$formField.trigger('change');
			}
		}

		function updateProductTypeField(choices, preserveValue) {
			var $productTypeField = $(productTypeFieldSelector);
			if ($productTypeField.length === 0) return;
			
			// Determine which value to restore
			var previousValue = preserveValue !== undefined ? preserveValue : $productTypeField.data('selected') || $productTypeField.val();
			
			hideLoading($productTypeField);
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
			
			// Restore previous value if it exists in the new choices (taxonomy name/slug comparison)
			if (previousValue && $productTypeField.find('option[value="' + previousValue + '"]').length > 0) {
				$productTypeField.val(previousValue);
				$productTypeField.data('selected', previousValue);
			} else {
				$productTypeField.val('');
				$productTypeField.data('selected', '');
			}
			
			// Don't trigger change if we're restoring a value to prevent cascading resets
			if (!preserveValue) {
				$productTypeField.trigger('change');
			}
		}

		function updateProductDetailsField(choices, preserveValue) {
			var $productDetailsField = $(productDetailsFieldSelector);
			if ($productDetailsField.length === 0) return;
			
			// Determine which value to restore
			var previousValue = preserveValue !== undefined ? preserveValue : $productDetailsField.data('selected') || $productDetailsField.val();
			
			hideLoading($productDetailsField);
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
			
			// Restore previous value if it exists in the new choices (taxonomy name/slug comparison)
			if (previousValue && $productDetailsField.find('option[value="' + previousValue + '"]').length > 0) {
				$productDetailsField.val(previousValue);
				$productDetailsField.data('selected', previousValue);
			} else {
				$productDetailsField.val('');
				$productDetailsField.data('selected', '');
			}
			
			// Don't trigger change if we're restoring a value to prevent cascading resets
			if (!preserveValue) {
				$productDetailsField.trigger('change');
			}
		}

		function updateManufacturedByField(choices, preserveValue) {
			var $manufacturedByField = $(manufacturedByFieldSelector);
			if ($manufacturedByField.length === 0) return;
			
			// Determine which value to restore
			var previousValue = preserveValue !== undefined ? preserveValue : $manufacturedByField.data('selected') || $manufacturedByField.val();
			
			hideLoading($manufacturedByField);
			$manufacturedByField.empty();
			$manufacturedByField.append($('<option>', {value: '', text: 'Please Select'}));
			if (choices && choices.length > 0) {
				$.each(choices, function(_, choice) {
					$manufacturedByField.append($('<option>', {
						value: choice.value,
						text: choice.text
					}));
				});
			}
			
			// Restore previous value if it exists in the new choices
			if (previousValue && $manufacturedByField.find('option[value="' + previousValue + '"]').length > 0) {
				$manufacturedByField.val(previousValue);
				$manufacturedByField.data('selected', previousValue);
			} else {
				$manufacturedByField.val('');
				$manufacturedByField.data('selected', '');
			}
			
			// Don't trigger change if we're restoring a value to prevent cascading resets
			if (!preserveValue) {
				$manufacturedByField.trigger('change');
			}
		}

		function updateReturnReasonField(choices, preserveValue) {
			var $returnReasonField = $(returnReasonFieldSelector);
			if ($returnReasonField.length === 0) return;
			
			// Determine which value to restore
			var previousValue = preserveValue !== undefined ? preserveValue : $returnReasonField.data('selected') || $returnReasonField.val();
			
			hideLoading($returnReasonField);
			$returnReasonField.empty();
			$returnReasonField.append($('<option>', {value: '', text: 'Please Select'}));
			if (choices && choices.length > 0) {
				$.each(choices, function(_, choice) {
					$returnReasonField.append($('<option>', {
						value: choice.value,
						text: choice.text
					}));
				});
			}
			
			// Restore previous value if it exists in the new choices
			if (previousValue && $returnReasonField.find('option[value="' + previousValue + '"]').length > 0) {
				$returnReasonField.val(previousValue);
				$returnReasonField.data('selected', previousValue);
			} else {
				$returnReasonField.val('');
				$returnReasonField.data('selected', '');
			}
			
			// Don't trigger change if we're restoring a value to prevent cascading resets
			if (!preserveValue) {
				$returnReasonField.trigger('change');
			}
		}

		function fetchStores(state, preserveValue) {
			if (!state) { updateStoreField([], preserveValue); return; }
			showLoading($(storeFieldSelector));
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
						updateStoreField(response.data.choices, preserveValue);
					} else {
						updateStoreField([], preserveValue);
					}
				},
				error: function () {
					updateStoreField([], preserveValue);
				}
			});
		}

		function fetchBrands(state, preserveValue) {
			if (!state) { updateBrandField([], preserveValue); return; }
			showLoading($(brandFieldSelector));
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
						updateBrandField(response.data.choices, preserveValue);
					} else {
						updateBrandField([], preserveValue);
					}
				},
				error: function () {
					updateBrandField([], preserveValue);
				}
			});
		}

		function fetchForms(brand, state, preserveValue) {
			if (!brand || !state) {
			updateFormField([], preserveValue);
				return;
			}
			showLoading($(formFieldSelector));
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
						updateFormField(response.data.choices, preserveValue);
					} else {
						updateFormField([], preserveValue);
					}
				},
				error: function () {
					updateFormField([], preserveValue);
				}
			});
		}

		function fetchProductTypes(brand, state, form, preserveValue) {
			if (!brand || !state || !form) {
			updateProductTypeField([], preserveValue);
				return;
			}
			showLoading($(productTypeFieldSelector));
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
						updateProductTypeField(response.data.choices, preserveValue);
					} else {
						updateProductTypeField([], preserveValue);
					}
				},
				error: function () {
					updateProductTypeField([], preserveValue);
				}
			});
		}

		function fetchProductDetails(brand, state, form, productType, preserveValue) {
			if (!brand || !state || !form || !productType) {
			updateProductDetailsField([], preserveValue);
				return;
			}
			showLoading($(productDetailsFieldSelector));
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
						updateProductDetailsField(response.data.choices, preserveValue);
					} else {
						updateProductDetailsField([], preserveValue);
					}
				},
				error: function () {
					updateProductDetailsField([], preserveValue);
				}
			});
		}

		function fetchManufacturedBy(state, preserveValue) {
			if (!state) { updateManufacturedByField([], preserveValue); return; }
			showLoading($(manufacturedByFieldSelector));
			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'once_gf_populate_get_manufactured_by',
					nonce: config.nonce,
					state: state
				},
				cache: false,
				success: function (response) {
					if (response.success && response.data && response.data.choices) {
						updateManufacturedByField(response.data.choices, preserveValue);
					} else {
						updateManufacturedByField([], preserveValue);
					}
				},
				error: function () {
					updateManufacturedByField([], preserveValue);
				}
			});
		}

		function fetchReturnReason(form, preserveValue) {
			if (!form) { updateReturnReasonField([], preserveValue); return; }
			showLoading($(returnReasonFieldSelector));
			$.ajax({
				url: config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'once_gf_populate_get_return_reason',
					nonce: config.nonce,
					form: form
				},
				cache: false,
				success: function (response) {
					if (response.success && response.data && response.data.choices) {
						updateReturnReasonField(response.data.choices, preserveValue);
					} else {
						updateReturnReasonField([], preserveValue);
					}
				},
				error: function () {
					updateReturnReasonField([], preserveValue);
				}
			});
		}

		// Track field values to preserve them on updates
	function trackFieldChanges() {
		$(storeFieldSelector).on('change', function() {
			$(this).data('selected', $(this).val());
		});
		$(brandFieldSelector).on('change', function() {
			$(this).data('selected', $(this).val());
		});
		$(formFieldSelector).on('change', function() {
			$(this).data('selected', $(this).val());
		});
		$(productTypeFieldSelector).on('change', function() {
			$(this).data('selected', $(this).val());
		});
		$(productDetailsFieldSelector).on('change', function() {
			$(this).data('selected', $(this).val());
		});
		$(manufacturedByFieldSelector).on('change', function() {
			$(this).data('selected', $(this).val());
		});
		$(returnReasonFieldSelector).on('change', function() {
			$(this).data('selected', $(this).val());
		});
	}

	// Initialize field value tracking
	trackFieldChanges();

	// Store initial values on page load (for form error rerenders)
	// This captures server-rendered values that were set during validation error rerender
	function storeInitialValues() {
		var $stateField = $(stateFieldSelector);
		var $storeField = $(storeFieldSelector);
		var $brandField = $(brandFieldSelector);
		var $formField = $(formFieldSelector);
		var $productTypeField = $(productTypeFieldSelector);
		var $productDetailsField = $(productDetailsFieldSelector);
		var $manufacturedByField = $(manufacturedByFieldSelector);
		var $returnReasonField = $(returnReasonFieldSelector);

		var stateVal, storeVal, brandVal, formVal, productTypeVal, productDetailsVal, manufacturedByVal, returnReasonVal;

		if ($stateField.length) {
			stateVal = $stateField.val();
			if (stateVal) {
				$stateField.data('selected', stateVal);
			}
		}
		// For AJAX fields, store the selected value if it exists (non-empty and not placeholder)
		if ($storeField.length) {
			storeVal = $storeField.val();
			if (storeVal) {
				$storeField.data('selected', storeVal);
			}
		}
		if ($brandField.length) {
			brandVal = $brandField.val();
			if (brandVal) {
				$brandField.data('selected', brandVal);
			}
		}
		if ($formField.length) {
			formVal = $formField.val();
			if (formVal) {
				$formField.data('selected', formVal);
			}
		}
		if ($productTypeField.length) {
			productTypeVal = $productTypeField.val();
			if (productTypeVal) {
				$productTypeField.data('selected', productTypeVal);
			}
		}
		if ($productDetailsField.length) {
			productDetailsVal = $productDetailsField.val();
			if (productDetailsVal) {
				$productDetailsField.data('selected', productDetailsVal);
			}
		}
		if ($manufacturedByField.length) {
			manufacturedByVal = $manufacturedByField.val();
			if (manufacturedByVal) {
				$manufacturedByField.data('selected', manufacturedByVal);
			}
		}
		if ($returnReasonField.length) {
			returnReasonVal = $returnReasonField.val();
			if (returnReasonVal) {
				$returnReasonField.data('selected', returnReasonVal);
			}
		}
	}

	// Store initial values
	storeInitialValues();

	// On form error rerender, trigger cascading repopulation
	function handleFormRerender() {
		var $stateField = $(stateFieldSelector);
		var $brandField = $(brandFieldSelector);
		var $formField = $(formFieldSelector);
		var $productTypeField = $(productTypeFieldSelector);

		// Check if we have validation errors (Gravity Forms adds this class)
		if ($('.gform_validation_error').length > 0 || $('.gfield_error').length > 0) {
			// Repopulate dependent fields with stored values
			var stateVal = $stateField.val();
			var brandVal = $brandField.data('selected') || $brandField.val();
			var formVal = $formField.data('selected') || $formField.val();
			var productTypeVal = $productTypeField.data('selected') || $productTypeField.val();

			if (stateVal) {
				// Fetch all state-dependent fields
				fetchStores(stateVal, $(storeFieldSelector).data('selected'));
				fetchBrands(stateVal, brandVal);
				fetchManufacturedBy(stateVal, $(manufacturedByFieldSelector).data('selected'));
				
				if (brandVal) {
					fetchForms(brandVal, stateVal, formVal);
					
					if (formVal) {
						fetchProductTypes(brandVal, stateVal, formVal, productTypeVal);
						fetchReturnReason(formVal, $(returnReasonFieldSelector).data('selected'));
						
						if (productTypeVal) {
							fetchProductDetails(brandVal, stateVal, formVal, productTypeVal, $(productDetailsFieldSelector).data('selected'));
						}
					}
				}
			}
		}
	}

	// Trigger rerender handling on page load
	setTimeout(handleFormRerender, 100);

	$(document).on('change', stateFieldSelector, function () {
			var selectedState = $(this).val();
			$(this).data('selected', selectedState);
			
			// When state changes, repopulate dependent fields but clear their stored selections
			// (user intentionally changed state, so downstream selections should reset)
			$(storeFieldSelector).data('selected', '');
			$(brandFieldSelector).data('selected', '');
			$(formFieldSelector).data('selected', '');
			$(productTypeFieldSelector).data('selected', '');
			$(productDetailsFieldSelector).data('selected', '');
			$(manufacturedByFieldSelector).data('selected', '');
			$(returnReasonFieldSelector).data('selected', '');
			
			fetchStores(selectedState);
			fetchBrands(selectedState);
			fetchManufacturedBy(selectedState);
			updateFormField([]);
			updateProductTypeField([]);
			updateProductDetailsField([]);
			updateReturnReasonField([]);
		});

		$(document).on('change', brandFieldSelector, function () {
			var selectedBrand = $(this).val();
			$(this).data('selected', selectedBrand);
			
			var selectedState = $(stateFieldSelector).val();
			
			// When brand changes, clear downstream field selections
			$(formFieldSelector).data('selected', '');
			$(productTypeFieldSelector).data('selected', '');
			$(productDetailsFieldSelector).data('selected', '');
			$(returnReasonFieldSelector).data('selected', '');
			
			fetchForms(selectedBrand, selectedState);
			updateProductTypeField([]);
			updateProductDetailsField([]);
			updateReturnReasonField([]);
		});

		// When "Form" changes, update Product Type and Return Reason
		$(document).on('change', formFieldSelector, function () {
			var selectedForm = $(this).val();
			$(this).data('selected', selectedForm);
			
			var selectedState = $(stateFieldSelector).val();
			var selectedBrand = $(brandFieldSelector).val();
			
			// When form changes, clear downstream field selections
			$(productTypeFieldSelector).data('selected', '');
			$(productDetailsFieldSelector).data('selected', '');
			$(returnReasonFieldSelector).data('selected', '');
			
			fetchProductTypes(selectedBrand, selectedState, selectedForm);
			fetchReturnReason(selectedForm);
			updateProductDetailsField([]);
		});

		// When "Product Type" changes, update Product Details
		$(document).on('change', productTypeFieldSelector, function () {
			var selectedProductType = $(this).val();
			$(this).data('selected', selectedProductType);
			
			var selectedState = $(stateFieldSelector).val();
			var selectedBrand = $(brandFieldSelector).val();
			var selectedForm = $(formFieldSelector).val();
			
			// When product type changes, clear product details selection
			$(productDetailsFieldSelector).data('selected', '');
			
			fetchProductDetails(selectedBrand, selectedState, selectedForm, selectedProductType);
		});
	});
})(jQuery);