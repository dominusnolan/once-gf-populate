/**
 * Frontend AJAX handler for Brand field population.
 * Listens to State field changes and updates Brand dropdown via AJAX.
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
		 * Update Brand dropdown with new choices.
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

			// Add brand options
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
		 * Fetch brands for the selected state via AJAX.
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
	});

})(jQuery);
