/**
 * Frontend AJAX handler for Store Name field population.
 * Listens to State field changes and updates Store Name dropdown via AJAX.
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

		// Construct field selectors for Gravity Forms
		var stateFieldSelector = '#input_' + formId + '_' + stateFieldId;
		var storeFieldSelector = '#input_' + formId + '_' + storeFieldId;

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
		 * Attach change event listener to State field.
		 */
		$(document).on('change', stateFieldSelector, function () {
			var selectedState = $(this).val();
			fetchStores(selectedState);
		});

		// Also listen for Gravity Forms field change event
		$(document).on('gform_post_conditional_logic', function (event, formId, fields, isInit) {
			if (parseInt(formId) === parseInt(config.formId)) {
				var $stateField = $(stateFieldSelector);
				if ($stateField.length > 0) {
					var selectedState = $stateField.val();
					if (selectedState) {
						fetchStores(selectedState);
					}
				}
			}
		});
	});

})(jQuery);
