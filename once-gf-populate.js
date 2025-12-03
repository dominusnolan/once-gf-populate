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

		// Configuration constants
		var MAX_FORM_READY_ATTEMPTS = 20; // Max attempts for DOM readiness check
		var FORM_READY_CHECK_INTERVAL = 100; // milliseconds between checks
		
		// localStorage key for storing field selections
		var localStorageKey = 'onceGfPopulate_form_' + formId + '_selections';

		/**
		 * Load saved selections from localStorage
		 */
		function loadSelections() {
			try {
				var saved = localStorage.getItem(localStorageKey);
				return saved ? JSON.parse(saved) : {};
			} catch (e) {
				return {};
			}
		}

		/**
		 * Save selections to localStorage
		 */
		function saveSelections(selections) {
			try {
				localStorage.setItem(localStorageKey, JSON.stringify(selections));
			} catch (e) {
				// Silent fail if localStorage is not available
			}
		}

		/**
		 * Clear all saved selections from localStorage
		 */
		function clearSelections() {
			try {
				localStorage.removeItem(localStorageKey);
			} catch (e) {
				// Silent fail
			}
		}

		/**
		 * Save a single field value to localStorage
		 */
		function saveFieldValue(fieldId, value) {
			var selections = loadSelections();
			selections[fieldId] = value;
			saveSelections(selections);
		}

		/**
		 * Get a saved field value from localStorage
		 */
		function getSavedFieldValue(fieldId) {
			var selections = loadSelections();
			return selections[fieldId] || '';
		}

		/**
		 * Check if there are any saved selections
		 */
		function hasSavedSelections(selections) {
			return Object.keys(selections).some(function(key) {
				return selections[key];
			});
		}

		/**
		 * Check if we're on a form submission confirmation page
		 */
		function isFormSubmissionConfirmationPage() {
			var search = window.location.search;
			var referrer = document.referrer || '';
			return search.indexOf('gf_page=preview') === -1 && 
			       (search.indexOf('gform_confirmation') !== -1 || 
			        referrer.indexOf('gform_confirmation') !== -1);
		}

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
			
			// Track if we're in preserve mode (even if value is empty)
			var isPreserving = (preserveValue !== undefined);
			
			// Determine which value to restore
			var previousValue = isPreserving ? preserveValue : ($storeField.data('selected') || $storeField.val());
			
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
			
			// Don't trigger change if we're in preserving mode to prevent cascading resets
			if (!isPreserving) {
				$storeField.trigger('change');
			}
		}

		function updateBrandField(choices, preserveValue) {
			var $brandField = $(brandFieldSelector);
			if ($brandField.length === 0) return;
			
			// Track if we're in preserve mode (even if value is empty)
			var isPreserving = (preserveValue !== undefined);
			
			// Determine which value to restore
			var previousValue = isPreserving ? preserveValue : ($brandField.data('selected') || $brandField.val());
			
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
			
			// Don't trigger change if we're in preserving mode to prevent cascading resets
			if (!isPreserving) {
				$brandField.trigger('change');
			}
		}

		function updateFormField(choices, preserveValue) {
			var $formField = $(formFieldSelector);
			if ($formField.length === 0) return;
			
			// Track if we're in preserve mode (even if value is empty)
			var isPreserving = (preserveValue !== undefined);
			
			// Determine which value to restore
			var previousValue = isPreserving ? preserveValue : ($formField.data('selected') || $formField.val());
			
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
			
			// Don't trigger change if we're in preserving mode to prevent cascading resets
			if (!isPreserving) {
				$formField.trigger('change');
			}
		}

		function updateProductTypeField(choices, preserveValue) {
			var $productTypeField = $(productTypeFieldSelector);
			if ($productTypeField.length === 0) return;
			
			// Track if we're in preserve mode (even if value is empty)
			var isPreserving = (preserveValue !== undefined);
			
			// Determine which value to restore
			var previousValue = isPreserving ? preserveValue : ($productTypeField.data('selected') || $productTypeField.val());
			
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
			
			// Don't trigger change if we're in preserving mode to prevent cascading resets
			if (!isPreserving) {
				$productTypeField.trigger('change');
			}
		}

		function updateProductDetailsField(choices, preserveValue) {
			var $productDetailsField = $(productDetailsFieldSelector);
			if ($productDetailsField.length === 0) return;
			
			// Track if we're in preserve mode (even if value is empty)
			var isPreserving = (preserveValue !== undefined);
			
			// Determine which value to restore
			var previousValue = isPreserving ? preserveValue : ($productDetailsField.data('selected') || $productDetailsField.val());
			
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
			
			// Don't trigger change if we're in preserving mode to prevent cascading resets
			if (!isPreserving) {
				$productDetailsField.trigger('change');
			}
		}

		function updateManufacturedByField(choices, preserveValue) {
			var $manufacturedByField = $(manufacturedByFieldSelector);
			if ($manufacturedByField.length === 0) return;
			
			// Track if we're in preserve mode (even if value is empty)
			var isPreserving = (preserveValue !== undefined);
			
			// Determine which value to restore
			var previousValue = isPreserving ? preserveValue : ($manufacturedByField.data('selected') || $manufacturedByField.val());
			
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
			
			// Don't trigger change if we're in preserving mode to prevent cascading resets
			if (!isPreserving) {
				$manufacturedByField.trigger('change');
			}
		}

		function updateReturnReasonField(choices, preserveValue) {
			var $returnReasonField = $(returnReasonFieldSelector);
			if ($returnReasonField.length === 0) return;
			
			// Track if we're in preserve mode (even if value is empty)
			var isPreserving = (preserveValue !== undefined);
			
			// Determine which value to restore
			var previousValue = isPreserving ? preserveValue : ($returnReasonField.data('selected') || $returnReasonField.val());
			
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
			
			// Don't trigger change if we're in preserving mode to prevent cascading resets
			if (!isPreserving) {
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

		// Track field values to preserve them on updates and save to localStorage
	function trackFieldChanges() {
		$(storeFieldSelector).on('change', function() {
			var val = $(this).val();
			$(this).data('selected', val);
			saveFieldValue(storeFieldId, val);
		});
		$(brandFieldSelector).on('change', function() {
			var val = $(this).val();
			$(this).data('selected', val);
			saveFieldValue(brandFieldId, val);
		});
		$(formFieldSelector).on('change', function() {
			var val = $(this).val();
			$(this).data('selected', val);
			saveFieldValue(formFieldId, val);
		});
		$(productTypeFieldSelector).on('change', function() {
			var val = $(this).val();
			$(this).data('selected', val);
			saveFieldValue(productTypeFieldId, val);
		});
		$(productDetailsFieldSelector).on('change', function() {
			var val = $(this).val();
			$(this).data('selected', val);
			saveFieldValue(productDetailsFieldId, val);
		});
		$(manufacturedByFieldSelector).on('change', function() {
			var val = $(this).val();
			$(this).data('selected', val);
			saveFieldValue(manufacturedByFieldId, val);
		});
		$(returnReasonFieldSelector).on('change', function() {
			var val = $(this).val();
			$(this).data('selected', val);
			saveFieldValue(returnReasonFieldId, val);
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

	/**
	 * Restore selections from localStorage and trigger cascading AJAX updates
	 */
	function restoreSelectionsFromLocalStorage() {
		var $stateField = $(stateFieldSelector);
		var $storeField = $(storeFieldSelector);
		var $brandField = $(brandFieldSelector);
		var $formField = $(formFieldSelector);
		var $productTypeField = $(productTypeFieldSelector);
		var $productDetailsField = $(productDetailsFieldSelector);
		var $manufacturedByField = $(manufacturedByFieldSelector);
		var $returnReasonField = $(returnReasonFieldSelector);

		// Load saved selections
		var savedStore = getSavedFieldValue(storeFieldId);
		var savedBrand = getSavedFieldValue(brandFieldId);
		var savedForm = getSavedFieldValue(formFieldId);
		var savedProductType = getSavedFieldValue(productTypeFieldId);
		var savedProductDetails = getSavedFieldValue(productDetailsFieldId);
		var savedManufacturedBy = getSavedFieldValue(manufacturedByFieldId);
		var savedReturnReason = getSavedFieldValue(returnReasonFieldId);

		// Get current state value (not AJAX-managed, so check field directly)
		var stateVal = $stateField.val();

		// Only restore if we have a state value and at least one saved selection
		var savedSelections = {
			store: savedStore,
			brand: savedBrand,
			form: savedForm,
			productType: savedProductType,
			productDetails: savedProductDetails,
			manufacturedBy: savedManufacturedBy,
			returnReason: savedReturnReason
		};
		
		if (stateVal && hasSavedSelections(savedSelections)) {
			// Store the saved values in data attributes for preservation
			if (savedStore) $storeField.data('selected', savedStore);
			if (savedBrand) $brandField.data('selected', savedBrand);
			if (savedForm) $formField.data('selected', savedForm);
			if (savedProductType) $productTypeField.data('selected', savedProductType);
			if (savedProductDetails) $productDetailsField.data('selected', savedProductDetails);
			if (savedManufacturedBy) $manufacturedByField.data('selected', savedManufacturedBy);
			if (savedReturnReason) $returnReasonField.data('selected', savedReturnReason);

			// Trigger cascading AJAX repopulation with saved values
			// This will repopulate all dependent fields
			fetchStores(stateVal, savedStore);
			fetchBrands(stateVal, savedBrand);
			fetchManufacturedBy(stateVal, savedManufacturedBy);

			if (savedBrand) {
				fetchForms(savedBrand, stateVal, savedForm);
				
				if (savedForm) {
					fetchReturnReason(savedForm, savedReturnReason);
					fetchProductTypes(savedBrand, stateVal, savedForm, savedProductType);
					
					if (savedProductType) {
						fetchProductDetails(savedBrand, stateVal, savedForm, savedProductType, savedProductDetails);
					}
				}
			}
		}
	}

	// Restore selections from localStorage on initial page load
	// Wait for form to be fully rendered with a reasonable timeout
	// Check if state field is already populated before restoring
	function waitForFormReady(callback, maxAttempts) {
		maxAttempts = maxAttempts || MAX_FORM_READY_ATTEMPTS;
		var attempts = 0;
		
		function checkReady() {
			var $stateField = $(stateFieldSelector);
			if ($stateField.length > 0 && $stateField.find('option').length > 1) {
				// State field is ready with options
				callback();
			} else if (attempts < maxAttempts) {
				attempts++;
				setTimeout(checkReady, FORM_READY_CHECK_INTERVAL);
			} else {
				// Fallback: run anyway after max attempts (form should be ready by now)
				// This ensures restoration happens even if DOM checks fail
				if (console && console.warn) {
					console.warn('Once GF Populate: Form readiness check timed out, attempting restoration anyway');
				}
				callback();
			}
		}
		
		checkReady();
	}
	
	waitForFormReady(restoreSelectionsFromLocalStorage);

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
			saveFieldValue(stateFieldId, selectedState);
			
			// When state changes, repopulate dependent fields but clear their stored selections
			// (user intentionally changed state, so downstream selections should reset)
			$(storeFieldSelector).data('selected', '');
			$(brandFieldSelector).data('selected', '');
			$(formFieldSelector).data('selected', '');
			$(productTypeFieldSelector).data('selected', '');
			$(productDetailsFieldSelector).data('selected', '');
			$(manufacturedByFieldSelector).data('selected', '');
			$(returnReasonFieldSelector).data('selected', '');
			
			// Clear dependent field selections from localStorage (batch update)
			var selections = loadSelections();
			selections[stateFieldId] = selectedState;
			selections[storeFieldId] = '';
			selections[brandFieldId] = '';
			selections[formFieldId] = '';
			selections[productTypeFieldId] = '';
			selections[productDetailsFieldId] = '';
			selections[manufacturedByFieldId] = '';
			selections[returnReasonFieldId] = '';
			saveSelections(selections);
			
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
			
			// Clear dependent field selections from localStorage (batch update)
			var selections = loadSelections();
			selections[formFieldId] = '';
			selections[productTypeFieldId] = '';
			selections[productDetailsFieldId] = '';
			selections[returnReasonFieldId] = '';
			saveSelections(selections);
			
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
			
			// Clear dependent field selections from localStorage (batch update)
			var selections = loadSelections();
			selections[productTypeFieldId] = '';
			selections[productDetailsFieldId] = '';
			selections[returnReasonFieldId] = '';
			saveSelections(selections);
			
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
			
			// Clear dependent field selection from localStorage
			saveFieldValue(productDetailsFieldId, '');
			
			fetchProductDetails(selectedBrand, selectedState, selectedForm, selectedProductType);
		});

		// Clear localStorage on successful form submission
		$(document).on('gform_confirmation_loaded', function(event, confirmedFormId) {
			// Compare as strings to handle both string and number types
			if (String(confirmedFormId) === String(config.formId)) {
				clearSelections();
			}
		});

		// Alternative: Clear on form submission page (if Gravity Forms redirects)
		// This handles cases where confirmation is on a different page
		if (isFormSubmissionConfirmationPage()) {
			// Check if we just submitted the form (looking for confirmation message)
			// Scope to the specific form to avoid conflicts
			if ($('#gform_confirmation_wrapper_' + config.formId).length > 0 || 
			    $('#gform_' + config.formId + ' .gform_confirmation_message').length > 0) {
				clearSelections();
			}
		}
	});
})(jQuery);