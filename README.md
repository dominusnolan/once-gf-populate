# Once GF Populate

Pre-populate Gravity Forms field choices from a Custom Post Type using ACF field values.

- Plugin Name: Once GF Populate
- Author: Once
- Link: [Once Interactive](https://onceinteractive.com)

## What it does

For Gravity Forms Form ID `7` and Field ID `32` (Drop Down), this plugin:
- Queries CPT `retail_customers`
- Reads the ACF/meta field `state`
- Builds a unique, sorted list of states
- Populates the field choices with a placeholder "Please Select State" followed by the unique states

![image1](image1)

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