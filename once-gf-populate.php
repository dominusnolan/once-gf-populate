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
define( 'ONCE_GF_POPULATE_CPT', 'retail_customers' );
define( 'ONCE_GF_POPULATE_ACF_FIELD', 'state' );

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