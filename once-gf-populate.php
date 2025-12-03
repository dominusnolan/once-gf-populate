<?php
/**
 * Plugin Name: Once GF Populate
 * Description: Pre-populate Gravity Forms fields from a custom post type (CPT) using ACF values. Example: Populate Form ID 7, Field ID 32 with unique "state" values from CPT "retail_customers".
 * Version: 1.0.0
 * Author: Once
 * Author URI: https://onceinteractive.com
 * Plugin URI: https://onceinteractive.com
 * License: GPLv2 or later
 * Requires at least: 5.8
 * Requires PHP: 7.4
 * Text Domain: once-gf-populate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'ONCE_GF_POPULATE_FORM_ID', 7 );
define( 'ONCE_GF_POPULATE_FIELD_ID', 32 );
define( 'ONCE_GF_POPULATE_STORE_NAME_FIELD_ID', 7 );
define( 'ONCE_GF_POPULATE_BRAND_FIELD_ID', 10 );
define( 'ONCE_GF_POPULATE_CPT', 'retail_customers' );
define( 'ONCE_GF_POPULATE_ACF_FIELD', 'state' );
define( 'ONCE_GF_POPULATE_MAX_STORES', 500 );

/**
 * Fetch unique state values from CPT retail_customers via ACF/meta.
 *
 * @return array Unique, sorted list of state strings.
 */
function once_gf_populate_get_states() {
	$states = array();

	$args = array(
		'post_type'      => ONCE_GF_POPULATE_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'no_found_rows'  => true,
		'orderby'        => 'ID',
		'order'          => 'ASC',
	);

	$query = new WP_Query( $args );

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post_id ) {
			$state = null;

			if ( function_exists( 'get_field' ) ) {
				$state = get_field( ONCE_GF_POPULATE_ACF_FIELD, $post_id );
			}

			if ( ! $state ) {
				$state = get_post_meta( $post_id, ONCE_GF_POPULATE_ACF_FIELD, true );
			}

			if ( is_string( $state ) ) {
				$state = trim( $state );
			}

			if ( ! empty( $state ) && is_string( $state ) ) {
				$states[] = $state;
			}
		}
	}

	$states = array_unique( $states );
	natsort( $states );

	return array_values( $states );
}

/**
 * Build Gravity Forms choices array from states list.
 *
 * @param array $states List of strings.
 * @return array Choices compatible with GF field->choices.
 */
function once_gf_populate_build_choices( array $states ) {
	$choices = array();

	foreach ( $states as $state ) {
		$choices[] = array(
			'text'       => $state,
			'value'      => $state,
			'isSelected' => false,
		);
	}

	return $choices;
}

add_filter( 'gform_pre_render_' . ONCE_GF_POPULATE_FORM_ID, function ( $form ) {
	return once_gf_populate_populate_field_choices( $form );
} );

add_filter( 'gform_pre_validation_' . ONCE_GF_POPULATE_FORM_ID, function ( $form ) {
	return once_gf_populate_populate_field_choices( $form );
} );

add_filter( 'gform_admin_pre_render_' . ONCE_GF_POPULATE_FORM_ID, function ( $form ) {
	return once_gf_populate_populate_field_choices( $form );
} );

/**
 * Core function to inject choices into the target field.
 *
 * @param array $form GF form array.
 * @return array Modified form.
 */
function once_gf_populate_populate_field_choices( $form ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return $form;
	}

	$states  = once_gf_populate_get_states();
	$choices = once_gf_populate_build_choices( $states );

	foreach ( $form['fields'] as &$field ) {
		if ( intval( $field->id ) === intval( ONCE_GF_POPULATE_FIELD_ID ) ) {
			if ( isset( $field->type ) && in_array( $field->type, array( 'select', 'multiselect' ), true ) ) {
				$field->choices     = $choices;
				$field->placeholder = 'Please Select State';
			}
		}
	}

	return $form;
}

/**
 * Optional: Shortcode to preview states (for debugging): [once_gf_states]
 */
add_shortcode( 'once_gf_states', function () {
	$states = once_gf_populate_get_states();
	if ( empty( $states ) ) {
		return '<p>No states found.</p>';
	}

	$out = '<ul>';
	foreach ( $states as $s ) {
		$out .= '<li>' . esc_html( $s ) . '</li>';
	}
	$out .= '</ul>';

	return $out;
} );

/**
 * Enqueue inline script for AJAX store name and brand population.
 * Bypasses external JS file to avoid caching layers.
 */
add_action( 'gform_enqueue_scripts_' . ONCE_GF_POPULATE_FORM_ID, function ( $form ) {
	if ( ! is_admin() ) {
		// Register an empty handle to attach inline script
		wp_register_script( 'once-gf-populate-ajax', '', array( 'jquery' ), '1.0.0', true );
		wp_enqueue_script( 'once-gf-populate-ajax' );
		
		// Prepare configuration
		$config = array(
			'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
			'nonce'          => wp_create_nonce( 'once_gf_populate_nonce' ),
			'formId'         => ONCE_GF_POPULATE_FORM_ID,
			'stateFieldId'   => ONCE_GF_POPULATE_FIELD_ID,
			'storeFieldId'   => ONCE_GF_POPULATE_STORE_NAME_FIELD_ID,
			'brandFieldId'   => ONCE_GF_POPULATE_BRAND_FIELD_ID,
		);
		
		// Build inline script
		$inline_script = "
(function($) {
	'use strict';
	
	var config = " . wp_json_encode( $config ) . ";
	
	$(document).ready(function() {
		var formId = config.formId;
		var stateFieldId = config.stateFieldId;
		var storeFieldId = config.storeFieldId;
		var brandFieldId = config.brandFieldId;
		
		// Construct field selectors
		var stateFieldSelector = '#input_' + formId + '_' + stateFieldId;
		var storeFieldSelector = '#input_' + formId + '_' + storeFieldId;
		var brandFieldSelector = '#input_' + formId + '_' + brandFieldId;
		
		/**
		 * Update select field with new choices.
		 * @param {string} selector - jQuery selector for the field
		 * @param {Array} choices - Array of choice objects with value and text
		 */
		function updateSelect(selector, choices) {
			var \$field = $(selector);
			
			if (\$field.length === 0) {
				return;
			}
			
			// Clear existing options
			\$field.empty();
			
			// Add placeholder option
			\$field.append(
				\$('<option>', {
					value: '',
					text: 'Please Select'
				})
			);
			
			// Add choices
			if (choices && choices.length > 0) {
				$.each(choices, function(index, choice) {
					\$field.append(
						\$('<option>', {
							value: choice.value,
							text: choice.text
						})
					);
				});
			}
			
			// Trigger change event to update Gravity Forms
			\$field.trigger('change');
		}
		
		/**
		 * Fetch stores for the selected state via AJAX.
		 * @param {string} state - The selected state value
		 */
		function fetchStores(state) {
			if (!state) {
				updateSelect(storeFieldSelector, []);
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
				headers: {
					'Cache-Control': 'no-cache, no-store, must-revalidate',
					'Pragma': 'no-cache',
					'Expires': '0'
				},
				success: function(response) {
					if (response.success && response.data && response.data.choices) {
						updateSelect(storeFieldSelector, response.data.choices);
					} else {
						updateSelect(storeFieldSelector, []);
					}
				},
				error: function() {
					updateSelect(storeFieldSelector, []);
				}
			});
		}
		
		/**
		 * Fetch brands for the selected state via AJAX.
		 * @param {string} state - The selected state value
		 */
		function fetchBrands(state) {
			if (!state) {
				updateSelect(brandFieldSelector, []);
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
				headers: {
					'Cache-Control': 'no-cache, no-store, must-revalidate',
					'Pragma': 'no-cache',
					'Expires': '0'
				},
				success: function(response) {
					if (response.success && response.data && response.data.choices) {
						updateSelect(brandFieldSelector, response.data.choices);
					} else {
						updateSelect(brandFieldSelector, []);
					}
				},
				error: function() {
					updateSelect(brandFieldSelector, []);
				}
			});
		}
		
		/**
		 * Attach change event listener to State field.
		 */
		$(document).on('change', stateFieldSelector, function() {
			var selectedState = $(this).val();
			// Fetch both stores and brands in parallel
			fetchStores(selectedState);
			fetchBrands(selectedState);
		});
	});
})(jQuery);
		";
		
		wp_add_inline_script( 'once-gf-populate-ajax', $inline_script );
	}
}, 10, 1 );

/**
 * AJAX handler to fetch stores by state.
 * Returns stores with Post ID as value and decoded Post Title as label.
 */
function once_gf_populate_ajax_get_stores() {
	// Set no-cache headers
	nocache_headers();

	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'once_gf_populate_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		return;
	}

	// Get and sanitize state parameter
	if ( ! isset( $_POST['state'] ) ) {
		wp_send_json_error( array( 'message' => 'Missing state parameter' ) );
		return;
	}

	$state = sanitize_text_field( wp_unslash( $_POST['state'] ) );

	if ( empty( $state ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Query stores filtered by state
	$args = array(
		'post_type'      => ONCE_GF_POPULATE_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_STORES,
		'orderby'        => 'title',
		'order'          => 'ASC',
		'meta_query'     => array(
			array(
				'key'     => ONCE_GF_POPULATE_ACF_FIELD,
				'value'   => $state,
				'compare' => '=',
			),
		),
	);

	$query = new WP_Query( $args );

	$choices = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post ) {
			// Decode HTML entities in post title
			$decoded_title = html_entity_decode( $post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			
			$choices[] = array(
				'value' => $post->ID,
				'text'  => $decoded_title,
			);
		}
	}

	wp_reset_postdata();

	wp_send_json_success( array( 'choices' => $choices ) );
}

add_action( 'wp_ajax_once_gf_populate_get_stores', 'once_gf_populate_ajax_get_stores' );
add_action( 'wp_ajax_nopriv_once_gf_populate_get_stores', 'once_gf_populate_ajax_get_stores' );

/**
 * AJAX handler to fetch brands by state.
 * Returns brands from products CPT filtered by product_state taxonomy.
 */
function once_gf_populate_ajax_get_brands() {
	// Set no-cache headers
	nocache_headers();

	// Verify nonce
	if ( ! isset( $_POST['nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'once_gf_populate_nonce' ) ) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		return;
	}

	// Get and sanitize state parameter
	if ( ! isset( $_POST['state'] ) ) {
		wp_send_json_error( array( 'message' => 'Missing state parameter' ) );
		return;
	}

	$state = sanitize_text_field( wp_unslash( $_POST['state'] ) );

	if ( empty( $state ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Try to find the product_state term by name first, then by slug
	$state_term = get_term_by( 'name', $state, 'product_state' );
	if ( ! $state_term ) {
		$state_term = get_term_by( 'slug', $state, 'product_state' );
	}

	if ( ! $state_term ) {
		// No matching term found
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Query products CPT filtered by product_state taxonomy
	$args = array(
		'post_type'      => 'products',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'fields'         => 'ids',
		'tax_query'      => array(
			array(
				'taxonomy' => 'product_state',
				'field'    => 'term_id',
				'terms'    => $state_term->term_id,
			),
		),
	);

	$query = new WP_Query( $args );

	$brand_terms = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $product_id ) {
			// Get product_brand terms for this product
			$brands = wp_get_post_terms( $product_id, 'product_brand', array( 'fields' => 'all' ) );
			
			if ( ! is_wp_error( $brands ) && ! empty( $brands ) ) {
				foreach ( $brands as $brand ) {
					// Use term name as key to avoid duplicates
					$brand_terms[ $brand->name ] = $brand->name;
				}
			}
		}
	}

	wp_reset_postdata();

	// Build choices array
	$choices = array();
	foreach ( $brand_terms as $brand_name ) {
		$choices[] = array(
			'value' => $brand_name,
			'text'  => $brand_name,
		);
	}

	// Sort choices alphabetically by name
	usort( $choices, function( $a, $b ) {
		return strcmp( $a['text'], $b['text'] );
	} );

	wp_send_json_success( array( 'choices' => $choices ) );
}

add_action( 'wp_ajax_once_gf_populate_get_brands', 'once_gf_populate_ajax_get_brands' );
add_action( 'wp_ajax_nopriv_once_gf_populate_get_brands', 'once_gf_populate_ajax_get_brands' );

/**
 * Initialize Store Name and Brand fields with placeholder on form render.
 */
add_filter( 'gform_pre_render_' . ONCE_GF_POPULATE_FORM_ID, function ( $form ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return $form;
	}

	foreach ( $form['fields'] as &$field ) {
		$field_id = intval( $field->id );
		
		// Initialize Store Name field
		if ( $field_id === intval( ONCE_GF_POPULATE_STORE_NAME_FIELD_ID ) ) {
			if ( isset( $field->type ) && in_array( $field->type, array( 'select', 'multiselect' ), true ) ) {
				$field->choices = array(
					array(
						'text'       => 'Please Select',
						'value'      => '',
						'isSelected' => false,
					),
				);
				$field->placeholder = 'Please Select';
			}
		}
		
		// Initialize Brand field
		if ( $field_id === intval( ONCE_GF_POPULATE_BRAND_FIELD_ID ) ) {
			if ( isset( $field->type ) && in_array( $field->type, array( 'select', 'multiselect' ), true ) ) {
				$field->choices = array(
					array(
						'text'       => 'Please Select',
						'value'      => '',
						'isSelected' => false,
					),
				);
				$field->placeholder = 'Please Select';
			}
		}
	}

	return $form;
}, 5 );