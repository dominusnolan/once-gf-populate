/**
 * Frontend AJAX handler for Store Name and Brand field population.
 * Listens to State field changes and updates Store Name and Brand dropdowns via AJAX.
 */
(function ($) {
	'use strict';

	/**
	 * Initialize the AJAX population when document is ready.
	 */
	$(document).ready(function () {
		// Ensure the configuration is available
		if (typeof onceGfPopulate === 'undefined') {
			return;
		}

		var config = onceGfPopulate;
		var formId = config.formId;
		var stateFieldId = config.stateFieldId;
		var storeFieldId = config.storeFieldId;
		var brandFieldId = config.brandFieldId;

		// Construct field selectors for Gravity Forms
		var stateFieldSelector = '#input_' + formId + '_' + stateFieldId;
		var storeFieldSelector = '#input_' + formId + '_' + storeFieldId;
		var brandFieldSelector = '#input_' + formId + '_' + brandFieldId;

		/**
		 * Update Store Name dropdown with new choices.
		 *
		 * @param {Array} choices Array of choice objects with value and text properties.
		 */
		function updateStoreField(choices) {
			var $storeField = $(storeFieldSelector);
			
			if ($storeField.length === 0) {
				return;
			}

			// Clear existing options
			$storeField.empty();

			// Add placeholder option
			$storeField.append(
				$('<option>', {
					value: '',
					text: 'Please Select'
				})
			);

			// Add store options
			if (choices && choices.length > 0) {
				$.each(choices, function (index, choice) {
					$storeField.append(
						$('<option>', {
							value: choice.value,
							text: choice.text
						})
					);
				});
			}

			// Trigger change event to update Gravity Forms
			$storeField.trigger('change');
		}

		/**
		 * Update Brand field dropdown with new choices.
		 *
		 * @param {Array} choices Array of choice objects with value and text properties.
		 */
		function updateBrandField(choices) {
			var $brandField = $(brandFieldSelector);

			if ($brandField.length === 0) {
				return;
			}

			// Clear existing options
			$brandField.empty();

			// Add placeholder option
			$brandField.append(
				$('<option>', {
					value: '',
					text: 'Please Select'
				})
			);

			// Add brand options
			if (choices && choices.length > 0) {
				$.each(choices, function (index, choice) {
					$brandField.append(
						$('<option>', {
							value: choice.value,
							text: choice.text
						})
					);
				});
			}

			// Trigger change event to update Gravity Forms
			$brandField.trigger('change');
		}

		/**
		 * Fetch stores for the selected state via AJAX.
		 *
		 * @param {string} state The selected state value.
		 */
		function fetchStores(state) {
			if (!state) {
				// If no state selected, reset to placeholder only
				updateStoreField([]);
				return;
			}

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
					// On error, reset to placeholder
					updateStoreField([]);
				}
			});
		}

		/**
		 * Fetch brands for the selected state via AJAX.
		 *
		 * @param {string} state The selected state value.
		 */
		function fetchBrands(state) {
			if (!state) {
				updateBrandField([]);
				return;
			}

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

		/**
		 * Attach change event listener to State field.
		 * Triggers both Store and Brand updates.
		 */
		$(document).on('change', stateFieldSelector, function () {
			var selectedState = $(this).val();
			fetchStores(selectedState);
			fetchBrands(selectedState); // new addition: triggers Brand update
		});
	});

})(jQuery);
