<?php
/**
 * Plugin Name: Once GF Populate
 * Description: Pre-populate Gravity Forms fields from a custom post type (CPT) using taxonomy terms. Populates Form ID 7, Field ID 32 with unique "state" values from CPT "products", and dynamically populates Field ID 10 (Brand) based on selected state.
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
define( 'ONCE_GF_POPULATE_STORE_NAME_FIELD_ID', 10 );
define( 'ONCE_GF_POPULATE_CPT', 'products' );
define( 'ONCE_GF_POPULATE_ACF_FIELD', 'state' );
define( 'ONCE_GF_POPULATE_MAX_STORES', 500 );

/**
 * Fetch unique state values from CPT products via ACF/meta.
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

	$choices[] = array(
		'text'       => 'Please Select State',
		'value'      => '',
		'isSelected' => false,
	);

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
 * Enqueue frontend script for AJAX store name population.
 */
add_action( 'gform_enqueue_scripts_' . ONCE_GF_POPULATE_FORM_ID, function ( $form ) {
	if ( ! is_admin() ) {
		$script_url = plugin_dir_url( __FILE__ ) . 'once-gf-populate.js';
		$script_path = plugin_dir_path( __FILE__ ) . 'once-gf-populate.js';
		$script_mtime = file_exists( $script_path ) ? filemtime( $script_path ) : false;
		$script_version = ( false !== $script_mtime ) ? $script_mtime : '1.0.0';
		
		wp_enqueue_script(
			'once-gf-populate-ajax',
			$script_url,
			array( 'jquery' ),
			$script_version,
			true
		);

		wp_localize_script(
			'once-gf-populate-ajax',
			'onceGfPopulate',
			array(
				'ajaxUrl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'once_gf_populate_nonce' ),
				'formId'         => ONCE_GF_POPULATE_FORM_ID,
				'stateFieldId'   => ONCE_GF_POPULATE_FIELD_ID,
				'storeFieldId'   => ONCE_GF_POPULATE_STORE_NAME_FIELD_ID,
			)
		);
	}
}, 10, 1 );

/**
 * AJAX handler to fetch brands by state.
 * Returns unique product_brand term names for products in the selected product_state.
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

	// Resolve product_state term by name
	$state_term = get_term_by( 'name', $state, 'product_state' );

	if ( ! $state_term || is_wp_error( $state_term ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Query products filtered by product_state taxonomy
	$args = array(
		'post_type'      => ONCE_GF_POPULATE_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_STORES,
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

	$brand_names = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post_id ) {
			// Get product_brand terms for this product
			$brand_terms = wp_get_post_terms( $post_id, 'product_brand', array( 'fields' => 'names' ) );
			
			if ( ! is_wp_error( $brand_terms ) && ! empty( $brand_terms ) ) {
				foreach ( $brand_terms as $brand_name ) {
					$brand_names[] = $brand_name;
				}
			}
		}
	}

	wp_reset_postdata();

	// Get unique brand names and sort naturally
	$brand_names = array_unique( $brand_names );
	natsort( $brand_names );
	$brand_names = array_values( $brand_names );

	// Build choices array
	$choices = array();
	foreach ( $brand_names as $brand_name ) {
		$choices[] = array(
			'value' => $brand_name,
			'text'  => $brand_name,
		);
	}

	wp_send_json_success( array( 'choices' => $choices ) );
}

add_action( 'wp_ajax_once_gf_populate_get_stores', 'once_gf_populate_ajax_get_stores' );
add_action( 'wp_ajax_nopriv_once_gf_populate_get_stores', 'once_gf_populate_ajax_get_stores' );

/**
 * Initialize Brand field with placeholder on form render.
 */
add_filter( 'gform_pre_render_' . ONCE_GF_POPULATE_FORM_ID, function ( $form ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return $form;
	}

	foreach ( $form['fields'] as &$field ) {
		if ( intval( $field->id ) === intval( ONCE_GF_POPULATE_STORE_NAME_FIELD_ID ) ) {
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