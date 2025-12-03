# Once GF Populate

Pre-populate Gravity Forms field choices from a Custom Post Type using ACF field values with localStorage persistence.

- Plugin Name: Once GF Populate
- Author: Once
- Link: [Once Interactive](https://onceinteractive.com)

## What it does

For Gravity Forms Form ID `7` and Field ID `32` (Drop Down), this plugin:
- Queries CPT `retail_customers`
- Reads the ACF/meta field `state`
- Builds a unique, sorted list of states
- Populates the field choices with a placeholder "Please Select State" followed by the unique states

Additionally, it provides **AJAX-based cascading dropdowns** with **localStorage persistence**:
- Store Name (depends on State)
- Brand (depends on State)
- Form (depends on State + Brand)
- Product Type (depends on State + Brand + Form)
- Product Details (depends on State + Brand + Form + Product Type)
- Manufactured By (depends on State)
- Return Reason (depends on Form)

![image1](image1)

## localStorage Persistence Feature

User selections for all AJAX-prepopulated fields are automatically saved to the browser's localStorage and restored on page refresh.

### How It Works

1. **Automatic Saving**: When a user selects a value in any AJAX-prepopulated dropdown, the selection is immediately saved to localStorage
2. **Smart Restoration**: On page load, saved selections are restored and trigger cascading AJAX updates to repopulate dependent fields
3. **Intelligent Cleanup**: Selections are cleared when:
   - A parent field changes (dependent fields are reset)
   - The form is successfully submitted
4. **Graceful Degradation**: If localStorage is unavailable (e.g., private browsing), the feature fails silently without breaking functionality

### localStorage Key

Selections are stored with the key: `onceGfPopulate_form_{formId}_selections`

Example for Form ID 7: `onceGfPopulate_form_7_selections`

### Manual Testing

To test localStorage persistence:

1. **Fill the form**: Select values in multiple cascading dropdowns
2. **Refresh the page**: Press F5 or reload
3. **Verify**: All previously selected values should be restored
4. **Change parent field**: Select a different State - dependent fields should reset
5. **Submit successfully**: Complete and submit the form
6. **Verify cleanup**: Refresh and verify selections are cleared

To inspect stored data in browser DevTools:
```javascript
// View current selections
JSON.parse(localStorage.getItem('onceGfPopulate_form_7_selections'))

// Clear selections manually
localStorage.removeItem('onceGfPopulate_form_7_selections')
```

## Requirements

- WordPress 5.8+
- PHP 7.4+
- Gravity Forms active
- ACF active (optional; will fallback to post meta)
- CPT `retail_customers` with posts containing `state` values

## Installation

1. Create a directory `once-gf-populate` in `wp-content/plugins/`.
2. Place `once-gf-populate.php` in that directory.
3. Activate the plugin via WordPress Admin → Plugins.

## Configuration

Defaults:
- Form ID: `7`
- Field ID: `32`
- CPT: `retail_customers`
- ACF/meta key: `state`

To change IDs or keys, edit the `define()` values near the top of `once-gf-populate.php`.

## Notes

- The first choice is a placeholder with empty value.
- If your field is not a Drop Down (`select`), change it to Drop Down or adapt the code.
- The plugin also hooks into pre-validation and admin pre-render so values are consistent server-side and in the editor.

## Troubleshooting

- Ensure CPT posts exist with a populated `state`.
- Confirm Gravity Forms field type is `Drop Down` and Field ID matches.
- Clear caches if using a caching plugin.
- If ACF is not active, ensure `state` is saved as standard post meta.

## License

GPLv2 or later.