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
define( 'ONCE_GF_POPULATE_CPT', 'retail_customers' );
define( 'ONCE_GF_POPULATE_ACF_FIELD', 'state' );
define( 'ONCE_GF_POPULATE_MAX_STORES', 500 );

// --- PRODUCT/BRAND additions ---
define( 'ONCE_GF_POPULATE_BRAND_FIELD_ID', 10 );
define( 'ONCE_GF_POPULATE_FORM_FIELD_ID', 11 );
define( 'ONCE_GF_POPULATE_PRODUCT_TYPE_FIELD_ID', 12 );
define( 'ONCE_GF_POPULATE_PRODUCT_DETAILS_FIELD_ID', 13 );
define( 'ONCE_GF_POPULATE_RETURN_REASON_FIELD_ID', 18 );
define( 'ONCE_GF_POPULATE_MANUFACTURED_BY_FIELD_ID', 39 );
define( 'ONCE_GF_POPULATE_PRODUCTS_CPT', 'products' );
define( 'ONCE_GF_POPULATE_PRODUCT_STATE_ACF', 'product_state' );
define( 'ONCE_GF_POPULATE_PRODUCT_BRAND_TAX', 'product_brand' );
define( 'ONCE_GF_POPULATE_PRODUCT_FORM_TAX', 'product_form' );
define( 'ONCE_GF_POPULATE_PRODUCT_TYPE_TAX', 'product_type' );
define( 'ONCE_GF_POPULATE_PRODUCT_DETAILS_TAX', 'product_details' );
define( 'ONCE_GF_POPULATE_MAX_PRODUCTS', 500 );

/**
 * Fetch unique state values from CPT retail_customers via ACF/meta.
 * Only includes states that have at least one associated product in the products CPT.
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
	
	// Filter out states that don't have any products in the products CPT
	// Use a single query to get all states that have products for efficiency
	$product_args = array(
		'post_type'      => ONCE_GF_POPULATE_PRODUCTS_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_PRODUCTS,
		'fields'         => 'ids',
	);
	
	$product_query = new WP_Query( $product_args );
	$states_with_products = array();
	
	if ( $product_query->have_posts() ) {
		foreach ( $product_query->posts as $product_id ) {
			$product_state_terms = wp_get_post_terms( $product_id, ONCE_GF_POPULATE_PRODUCT_STATE_ACF );
			if ( ! is_wp_error( $product_state_terms ) && ! empty( $product_state_terms ) ) {
				foreach ( $product_state_terms as $term ) {
					if ( is_object( $term ) && isset( $term->name ) ) {
						$states_with_products[ $term->name ] = true;
					}
				}
			}
		}
	}
	wp_reset_postdata();
	
	// Filter the original states list to only include those with products
	$filtered_states = array();
	foreach ( $states as $state ) {
		if ( isset( $states_with_products[ $state ] ) ) {
			$filtered_states[] = $state;
		}
	}
	
	natsort( $filtered_states );

	return array_values( $filtered_states );
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
	return once_gf_populate_pre_validation_handler( $form );
} );

add_filter( 'gform_admin_pre_render_' . ONCE_GF_POPULATE_FORM_ID, function ( $form ) {
	return once_gf_populate_populate_field_choices( $form );
} );

/**
 * Bypass validation for AJAX-populated select fields.
 * This prevents "Invalid selection" errors for dynamically populated dropdowns.
 */
add_filter( 'gform_field_validation_' . ONCE_GF_POPULATE_FORM_ID, function ( $result, $value, $form, $field ) {
	$skip_validation = array(
		intval( ONCE_GF_POPULATE_STORE_NAME_FIELD_ID ),
		intval( ONCE_GF_POPULATE_BRAND_FIELD_ID ),
		intval( ONCE_GF_POPULATE_FORM_FIELD_ID ),
		intval( ONCE_GF_POPULATE_PRODUCT_TYPE_FIELD_ID ),
		intval( ONCE_GF_POPULATE_PRODUCT_DETAILS_FIELD_ID ),
		intval( ONCE_GF_POPULATE_MANUFACTURED_BY_FIELD_ID ),
		intval( ONCE_GF_POPULATE_RETURN_REASON_FIELD_ID ),
	);
	if ( in_array( intval( $field->id ), $skip_validation ) ) {
		$result['is_valid'] = true;
		$result['message'] = '';
	}
	return $result;
}, 10, 4 );

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
				$field->choices = $choices;
				// Do not set placeholder for State field - the choices array already includes "Please Select State"
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
 * Enqueue frontend script for AJAX store name and brand population.
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
				'brandFieldId'   => ONCE_GF_POPULATE_BRAND_FIELD_ID,
				'formFieldId'    => ONCE_GF_POPULATE_FORM_FIELD_ID,
				'productTypeFieldId' => ONCE_GF_POPULATE_PRODUCT_TYPE_FIELD_ID,
				'productDetailsFieldId' => ONCE_GF_POPULATE_PRODUCT_DETAILS_FIELD_ID,
				'manufacturedByFieldId' => ONCE_GF_POPULATE_MANUFACTURED_BY_FIELD_ID,
				'returnReasonFieldId' => ONCE_GF_POPULATE_RETURN_REASON_FIELD_ID,
			)
		);
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
 * AJAX handler to fetch brands (taxonomy "product_brand") by state, from products CPT and product_state taxonomy.
 * Returns brands with Term name as value and label.
 */
function once_gf_populate_ajax_get_brands() {
	nocache_headers();

	// Verify nonce
	if (
		! isset( $_POST['nonce'] )
		|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'once_gf_populate_nonce' )
	) {
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

	// Get term object for product_state taxonomy
	$product_state_term = get_term_by( 'name', $state, ONCE_GF_POPULATE_PRODUCT_STATE_ACF );
	if ( ! $product_state_term || is_wp_error( $product_state_term ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Query products that have this state
	$args = array(
		'post_type'      => ONCE_GF_POPULATE_PRODUCTS_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_PRODUCTS,
		'tax_query'      => array(
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_STATE_ACF,
				'field'    => 'term_id',
				'terms'    => $product_state_term->term_id,
			),
		),
		'fields'         => 'ids',
	);

	$products_query = new WP_Query( $args );

	$brand_names = array();

	if ( $products_query->have_posts() ) {
		foreach ( $products_query->posts as $product_id ) {
			$brands = wp_get_post_terms( $product_id, ONCE_GF_POPULATE_PRODUCT_BRAND_TAX );
			foreach ( $brands as $brand ) {
				if ( is_object( $brand ) && $brand->name ) {
					$brand_names[ $brand->name ] = true;
				}
			}
		}
	}

	$choices = array();
	foreach ( array_keys( $brand_names ) as $brand_name ) {
		$choices[] = array(
			'value' => $brand_name,
			'text'  => html_entity_decode( $brand_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		);
	}

	wp_send_json_success( array( 'choices' => $choices ) );
}
add_action( 'wp_ajax_once_gf_populate_get_brands', 'once_gf_populate_ajax_get_brands' );
add_action( 'wp_ajax_nopriv_once_gf_populate_get_brands', 'once_gf_populate_ajax_get_brands' );

/**
 * AJAX handler to fetch forms (taxonomy "product_form") by brand and state, from products CPT using product_state and product_brand taxonomy.
 * Returns forms with Term name as value and label.
 */
function once_gf_populate_ajax_get_forms() {
	nocache_headers();

	if (
		! isset( $_POST['nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'once_gf_populate_nonce' )
	) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		return;
	}
	$brand = isset( $_POST['brand'] ) ? sanitize_text_field( wp_unslash( $_POST['brand'] ) ) : '';
	$state = isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '';
	if ( empty( $brand ) || empty( $state ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	$state_term = get_term_by( 'name', $state, ONCE_GF_POPULATE_PRODUCT_STATE_ACF );
	if ( ! $state_term || is_wp_error( $state_term ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}
	$args = array(
		'post_type'      => ONCE_GF_POPULATE_PRODUCTS_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_PRODUCTS,
		'tax_query'      => array(
			'relation' => 'AND',
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_STATE_ACF,
				'field'    => 'term_id',
				'terms'    => $state_term->term_id,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_BRAND_TAX,
				'field'    => 'name',
				'terms'    => $brand,
			),
		),
		'fields' => 'ids',
	);
	$query = new WP_Query( $args );
	$form_terms = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $product_id ) {
			$forms = wp_get_post_terms( $product_id, ONCE_GF_POPULATE_PRODUCT_FORM_TAX );
			foreach ( $forms as $form_term ) {
				if ( is_object( $form_term ) && $form_term->name ) {
					$form_terms[ $form_term->name ] = true;
				}
			}
		}
	}

	$choices = array();
	foreach ( array_keys( $form_terms ) as $form_name ) {
		$choices[] = array(
			'value' => $form_name,
			'text'  => html_entity_decode( $form_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		);
	}
	wp_send_json_success( array( 'choices' => $choices ) );
}
add_action( 'wp_ajax_once_gf_populate_get_forms', 'once_gf_populate_ajax_get_forms' );
add_action( 'wp_ajax_nopriv_once_gf_populate_get_forms', 'once_gf_populate_ajax_get_forms' );


/**
 * AJAX handler to fetch product types (taxonomy "product_type") by brand, state, and form from products CPT.
 * Returns product types with Term name as value and label.
 */
function once_gf_populate_ajax_get_product_types() {
	nocache_headers();

	// Security check
	if (
		! isset( $_POST['nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'once_gf_populate_nonce' )
	) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		return;
	}

	$brand = isset( $_POST['brand'] ) ? sanitize_text_field( wp_unslash( $_POST['brand'] ) ) : '';
	$state = isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '';
	$form  = isset( $_POST['form'] )  ? sanitize_text_field( wp_unslash( $_POST['form'] ) )  : '';

	if ( empty( $brand ) || empty( $state ) || empty( $form ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Lookup term objects
	$state_term = get_term_by( 'name', $state, ONCE_GF_POPULATE_PRODUCT_STATE_ACF );
	if ( ! $state_term || is_wp_error( $state_term ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Query products that match taxonomies
	$args = array(
		'post_type' => ONCE_GF_POPULATE_PRODUCTS_CPT,
		'post_status' => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_PRODUCTS,
		'tax_query' => array(
			'relation' => 'AND',
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_STATE_ACF,
				'field'    => 'term_id',
				'terms'    => $state_term->term_id,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_BRAND_TAX,
				'field'    => 'name',
				'terms'    => $brand,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_FORM_TAX,
				'field'    => 'name',
				'terms'    => $form,
			),
		),
		'fields' => 'ids',
	);

	$query = new WP_Query( $args );
	$type_names = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $product_id ) {
			$types = wp_get_post_terms( $product_id, ONCE_GF_POPULATE_PRODUCT_TYPE_TAX );
			foreach ( $types as $type ) {
				if ( is_object( $type ) && $type->name ) {
					$type_names[ $type->name ] = true;
				}
			}
		}
	}

	$choices = array();
	foreach ( array_keys( $type_names ) as $type_name ) {
		$choices[] = array(
			'value' => $type_name,
			'text'  => html_entity_decode( $type_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		);
	}

	wp_send_json_success( array( 'choices' => $choices ) );
}
add_action( 'wp_ajax_once_gf_populate_get_product_types', 'once_gf_populate_ajax_get_product_types' );
add_action( 'wp_ajax_nopriv_once_gf_populate_get_product_types', 'once_gf_populate_ajax_get_product_types' );

/**
 * AJAX handler to fetch product details (taxonomy "product_details") by brand, state, form, and product type from products CPT.
 * Returns product details with Term name as value and label.
 */
function once_gf_populate_ajax_get_product_details() {
	nocache_headers();

	// Security check
	if (
		! isset( $_POST['nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'once_gf_populate_nonce' )
	) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		return;
	}

	$brand        = isset( $_POST['brand'] )        ? sanitize_text_field( wp_unslash( $_POST['brand'] ) )        : '';
	$state        = isset( $_POST['state'] )        ? sanitize_text_field( wp_unslash( $_POST['state'] ) )        : '';
	$form         = isset( $_POST['form'] )         ? sanitize_text_field( wp_unslash( $_POST['form'] ) )         : '';
	$product_type = isset( $_POST['product_type'] ) ? sanitize_text_field( wp_unslash( $_POST['product_type'] ) ) : '';

	if ( empty( $brand ) || empty( $state ) || empty( $form ) || empty( $product_type ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Lookup term objects
	$state_term = get_term_by( 'name', $state, ONCE_GF_POPULATE_PRODUCT_STATE_ACF );
	if ( ! $state_term || is_wp_error( $state_term ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Query products that match all taxonomies
	$args = array(
		'post_type' => ONCE_GF_POPULATE_PRODUCTS_CPT,
		'post_status' => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_PRODUCTS,
		'tax_query' => array(
			'relation' => 'AND',
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_STATE_ACF,
				'field'    => 'term_id',
				'terms'    => $state_term->term_id,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_BRAND_TAX,
				'field'    => 'name',
				'terms'    => $brand,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_FORM_TAX,
				'field'    => 'name',
				'terms'    => $form,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_TYPE_TAX,
				'field'    => 'name',
				'terms'    => $product_type,
			),
		),
		'fields' => 'ids',
	);

	$query = new WP_Query( $args );
	$details_names = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $product_id ) {
			$details = wp_get_post_terms( $product_id, ONCE_GF_POPULATE_PRODUCT_DETAILS_TAX );
			if ( is_wp_error( $details ) ) {
				continue;
			}
			foreach ( $details as $detail ) {
				if ( is_object( $detail ) && $detail->name ) {
					$details_names[ $detail->name ] = true;
				}
			}
		}
	}

	$choices = array();
	foreach ( array_keys( $details_names ) as $detail_name ) {
		$choices[] = array(
			'value' => $detail_name,
			'text'  => html_entity_decode( $detail_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
		);
	}

	wp_send_json_success( array( 'choices' => $choices ) );
}
add_action( 'wp_ajax_once_gf_populate_get_product_details', 'once_gf_populate_ajax_get_product_details' );
add_action( 'wp_ajax_nopriv_once_gf_populate_get_product_details', 'once_gf_populate_ajax_get_product_details' );

/**
 * AJAX handler to fetch manufactured by values from products CPT filtered by state.
 * Extracts unique values from ACF field 'state_mfg' for products matching the selected state via product_state taxonomy.
 */
function once_gf_populate_ajax_get_manufactured_by() {
	nocache_headers();

	// Security check
	if (
		! isset( $_POST['nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'once_gf_populate_nonce' )
	) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		return;
	}

	$state = isset( $_POST['state'] ) ? sanitize_text_field( wp_unslash( $_POST['state'] ) ) : '';

	if ( empty( $state ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Get term object for product_state taxonomy
	$state_term = get_term_by( 'name', $state, ONCE_GF_POPULATE_PRODUCT_STATE_ACF );
	if ( ! $state_term || is_wp_error( $state_term ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Get ACF field 'state_mfg' from taxonomy term object
	$state_mfg = null;

	if ( function_exists( 'get_field' ) ) {
		// For taxonomy term, the ACF field key is: {taxonomy}_{term_id}
		$state_mfg = get_field( 'state_mfg', ONCE_GF_POPULATE_PRODUCT_STATE_ACF . '_' . $state_term->term_id );
	}

	if ( ! $state_mfg ) {
		$state_mfg = get_term_meta( $state_term->term_id, 'state_mfg', true );
	}

	$choices = array();

	// Handle state_mfg as array or string
	if ( is_array( $state_mfg ) && ! empty( $state_mfg ) ) {
		foreach ( $state_mfg as $mfg ) {
			if ( is_string( $mfg ) ) {
				$mfg = trim( $mfg );
				if ( ! empty( $mfg ) ) {
					$choices[] = array(
						'value' => $mfg,
						'text'  => html_entity_decode( $mfg, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					);
				}
			}
		}
	} elseif ( is_string( $state_mfg ) ) {
		$state_mfg = trim( $state_mfg );
		if ( ! empty( $state_mfg ) ) {
			// Split by newlines or commas
			$mfgs = preg_split( '/[\r\n,]+/', $state_mfg );
			foreach ( $mfgs as $mfg ) {
				$mfg = trim( $mfg );
				if ( ! empty( $mfg ) ) {
					$choices[] = array(
						'value' => $mfg,
						'text'  => html_entity_decode( $mfg, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					);
				}
			}
		}
	}

	wp_send_json_success( array( 'choices' => $choices ) );
}
add_action( 'wp_ajax_once_gf_populate_get_manufactured_by', 'once_gf_populate_ajax_get_manufactured_by' );
add_action( 'wp_ajax_nopriv_once_gf_populate_get_manufactured_by', 'once_gf_populate_ajax_get_manufactured_by' );

/**
 * AJAX handler to fetch return reason values from product_form taxonomy term.
 * Extracts ACF field 'return_reason' from the taxonomy term matching the selected Form value.
 */
function once_gf_populate_ajax_get_return_reason() {
	nocache_headers();

	// Security check
	if (
		! isset( $_POST['nonce'] ) ||
		! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['nonce'] ) ), 'once_gf_populate_nonce' )
	) {
		wp_send_json_error( array( 'message' => 'Invalid nonce' ) );
		return;
	}

	$form = isset( $_POST['form'] ) ? sanitize_text_field( wp_unslash( $_POST['form'] ) ) : '';

	if ( empty( $form ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Get term object for product_form taxonomy
	$form_term = get_term_by( 'name', $form, ONCE_GF_POPULATE_PRODUCT_FORM_TAX );
	if ( ! $form_term || is_wp_error( $form_term ) ) {
		wp_send_json_success( array( 'choices' => array() ) );
		return;
	}

	// Get return_reason ACF field from the taxonomy term
	$return_reason = null;

	if ( function_exists( 'get_field' ) ) {
		$return_reason = get_field( 'return_reason', ONCE_GF_POPULATE_PRODUCT_FORM_TAX . '_' . $form_term->term_id );
	}

	if ( ! $return_reason ) {
		$return_reason = get_term_meta( $form_term->term_id, 'return_reason', true );
	}

	$choices = array();

	// Handle return_reason as array or string
	if ( is_array( $return_reason ) && ! empty( $return_reason ) ) {
		foreach ( $return_reason as $reason ) {
			if ( is_string( $reason ) ) {
				$reason = trim( $reason );
				if ( ! empty( $reason ) ) {
					$choices[] = array(
						'value' => $reason,
						'text'  => html_entity_decode( $reason, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					);
				}
			}
		}
	} elseif ( is_string( $return_reason ) ) {
		$return_reason = trim( $return_reason );
		if ( ! empty( $return_reason ) ) {
			// Split by newlines or commas if it's a delimited string
			$reasons = preg_split( '/[\r\n,]+/', $return_reason );
			foreach ( $reasons as $reason ) {
				$reason = trim( $reason );
				if ( ! empty( $reason ) ) {
					$choices[] = array(
						'value' => $reason,
						'text'  => html_entity_decode( $reason, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
					);
				}
			}
		}
	}

	wp_send_json_success( array( 'choices' => $choices ) );
}
add_action( 'wp_ajax_once_gf_populate_get_return_reason', 'once_gf_populate_ajax_get_return_reason' );
add_action( 'wp_ajax_nopriv_once_gf_populate_get_return_reason', 'once_gf_populate_ajax_get_return_reason' );

/**
 * Helper function to get sanitized POST value for a Gravity Forms field.
 *
 * @param int $field_id The Gravity Forms field ID.
 * @return string Sanitized field value or empty string.
 */
function once_gf_populate_get_post_value( $field_id ) {
	$input_key = 'input_' . $field_id;
	if ( isset( $_POST[ $input_key ] ) ) {
		return sanitize_text_field( wp_unslash( $_POST[ $input_key ] ) );
	}
	return '';
}

/**
 * Helper function to get store choices by state (server-side, mirrors AJAX handler).
 *
 * @param string $state The state value.
 * @param string $selected_value Optional. The value to mark as selected.
 * @return array Array of choices compatible with GF field->choices.
 */
function once_gf_populate_get_store_choices( $state, $selected_value = '' ) {
	$choices = array(
		array(
			'text'       => 'Please Select',
			'value'      => '',
			'isSelected' => ( $selected_value === '' ),
		),
	);

	if ( empty( $state ) ) {
		return $choices;
	}

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

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $post ) {
			$decoded_title = html_entity_decode( $post->post_title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$is_selected = ( $selected_value !== '' && strval( $post->ID ) === strval( $selected_value ) );
			
			$choices[] = array(
				'text'       => $decoded_title,
				'value'      => $post->ID,
				'isSelected' => $is_selected,
			);
		}
	}

	wp_reset_postdata();
	return $choices;
}

/**
 * Helper function to get brand choices by state (server-side, mirrors AJAX handler).
 *
 * @param string $state The state value.
 * @param string $selected_value Optional. The value to mark as selected.
 * @return array Array of choices compatible with GF field->choices.
 */
function once_gf_populate_get_brand_choices( $state, $selected_value = '' ) {
	$choices = array(
		array(
			'text'       => 'Please Select',
			'value'      => '',
			'isSelected' => ( $selected_value === '' ),
		),
	);

	if ( empty( $state ) ) {
		return $choices;
	}

	$product_state_term = get_term_by( 'name', $state, ONCE_GF_POPULATE_PRODUCT_STATE_ACF );
	if ( ! $product_state_term || is_wp_error( $product_state_term ) ) {
		return $choices;
	}

	$args = array(
		'post_type'      => ONCE_GF_POPULATE_PRODUCTS_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_PRODUCTS,
		'tax_query'      => array(
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_STATE_ACF,
				'field'    => 'term_id',
				'terms'    => $product_state_term->term_id,
			),
		),
		'fields'         => 'ids',
	);

	$products_query = new WP_Query( $args );
	$brand_names = array();

	if ( $products_query->have_posts() ) {
		foreach ( $products_query->posts as $product_id ) {
			$brands = wp_get_post_terms( $product_id, ONCE_GF_POPULATE_PRODUCT_BRAND_TAX );
			foreach ( $brands as $brand ) {
				if ( is_object( $brand ) && $brand->name ) {
					$brand_names[ $brand->name ] = true;
				}
			}
		}
	}

	foreach ( array_keys( $brand_names ) as $brand_name ) {
		$decoded_name = html_entity_decode( $brand_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$is_selected = ( $selected_value !== '' && $brand_name === $selected_value );
		
		$choices[] = array(
			'text'       => $decoded_name,
			'value'      => $brand_name,
			'isSelected' => $is_selected,
		);
	}

	return $choices;
}

/**
 * Helper function to get form choices by state and brand (server-side, mirrors AJAX handler).
 *
 * @param string $state The state value.
 * @param string $brand The brand value.
 * @param string $selected_value Optional. The value to mark as selected.
 * @return array Array of choices compatible with GF field->choices.
 */
function once_gf_populate_get_form_choices( $state, $brand, $selected_value = '' ) {
	$choices = array(
		array(
			'text'       => 'Please Select',
			'value'      => '',
			'isSelected' => ( $selected_value === '' ),
		),
	);

	if ( empty( $brand ) || empty( $state ) ) {
		return $choices;
	}

	$state_term = get_term_by( 'name', $state, ONCE_GF_POPULATE_PRODUCT_STATE_ACF );
	if ( ! $state_term || is_wp_error( $state_term ) ) {
		return $choices;
	}

	$args = array(
		'post_type'      => ONCE_GF_POPULATE_PRODUCTS_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_PRODUCTS,
		'tax_query'      => array(
			'relation' => 'AND',
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_STATE_ACF,
				'field'    => 'term_id',
				'terms'    => $state_term->term_id,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_BRAND_TAX,
				'field'    => 'name',
				'terms'    => $brand,
			),
		),
		'fields' => 'ids',
	);

	$query = new WP_Query( $args );
	$form_terms = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $product_id ) {
			$forms = wp_get_post_terms( $product_id, ONCE_GF_POPULATE_PRODUCT_FORM_TAX );
			foreach ( $forms as $form_term ) {
				if ( is_object( $form_term ) && $form_term->name ) {
					$form_terms[ $form_term->name ] = true;
				}
			}
		}
	}

	foreach ( array_keys( $form_terms ) as $form_name ) {
		$decoded_name = html_entity_decode( $form_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$is_selected = ( $selected_value !== '' && $form_name === $selected_value );
		
		$choices[] = array(
			'text'       => $decoded_name,
			'value'      => $form_name,
			'isSelected' => $is_selected,
		);
	}

	return $choices;
}

/**
 * Helper function to get product type choices by state, brand, and form (server-side, mirrors AJAX handler).
 *
 * @param string $state The state value.
 * @param string $brand The brand value.
 * @param string $form The form value.
 * @param string $selected_value Optional. The value to mark as selected.
 * @return array Array of choices compatible with GF field->choices.
 */
function once_gf_populate_get_product_type_choices( $state, $brand, $form, $selected_value = '' ) {
	$choices = array(
		array(
			'text'       => 'Please Select',
			'value'      => '',
			'isSelected' => ( $selected_value === '' ),
		),
	);

	if ( empty( $brand ) || empty( $state ) || empty( $form ) ) {
		return $choices;
	}

	$state_term = get_term_by( 'name', $state, ONCE_GF_POPULATE_PRODUCT_STATE_ACF );
	if ( ! $state_term || is_wp_error( $state_term ) ) {
		return $choices;
	}

	$args = array(
		'post_type'      => ONCE_GF_POPULATE_PRODUCTS_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_PRODUCTS,
		'tax_query'      => array(
			'relation' => 'AND',
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_STATE_ACF,
				'field'    => 'term_id',
				'terms'    => $state_term->term_id,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_BRAND_TAX,
				'field'    => 'name',
				'terms'    => $brand,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_FORM_TAX,
				'field'    => 'name',
				'terms'    => $form,
			),
		),
		'fields' => 'ids',
	);

	$query = new WP_Query( $args );
	$type_names = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $product_id ) {
			$types = wp_get_post_terms( $product_id, ONCE_GF_POPULATE_PRODUCT_TYPE_TAX );
			foreach ( $types as $type ) {
				if ( is_object( $type ) && $type->name ) {
					$type_names[ $type->name ] = true;
				}
			}
		}
	}

	foreach ( array_keys( $type_names ) as $type_name ) {
		$decoded_name = html_entity_decode( $type_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$is_selected = ( $selected_value !== '' && $type_name === $selected_value );
		
		$choices[] = array(
			'text'       => $decoded_name,
			'value'      => $type_name,
			'isSelected' => $is_selected,
		);
	}

	return $choices;
}

/**
 * Helper function to get product details choices by state, brand, form, and product type (server-side, mirrors AJAX handler).
 *
 * @param string $state The state value.
 * @param string $brand The brand value.
 * @param string $form The form value.
 * @param string $product_type The product type value.
 * @param string $selected_value Optional. The value to mark as selected.
 * @return array Array of choices compatible with GF field->choices.
 */
function once_gf_populate_get_product_details_choices( $state, $brand, $form, $product_type, $selected_value = '' ) {
	$choices = array(
		array(
			'text'       => 'Please Select',
			'value'      => '',
			'isSelected' => ( $selected_value === '' ),
		),
	);

	if ( empty( $brand ) || empty( $state ) || empty( $form ) || empty( $product_type ) ) {
		return $choices;
	}

	$state_term = get_term_by( 'name', $state, ONCE_GF_POPULATE_PRODUCT_STATE_ACF );
	if ( ! $state_term || is_wp_error( $state_term ) ) {
		return $choices;
	}

	$args = array(
		'post_type'      => ONCE_GF_POPULATE_PRODUCTS_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_PRODUCTS,
		'tax_query'      => array(
			'relation' => 'AND',
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_STATE_ACF,
				'field'    => 'term_id',
				'terms'    => $state_term->term_id,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_BRAND_TAX,
				'field'    => 'name',
				'terms'    => $brand,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_FORM_TAX,
				'field'    => 'name',
				'terms'    => $form,
			),
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_TYPE_TAX,
				'field'    => 'name',
				'terms'    => $product_type,
			),
		),
		'fields' => 'ids',
	);

	$query = new WP_Query( $args );
	$details_names = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $product_id ) {
			$details = wp_get_post_terms( $product_id, ONCE_GF_POPULATE_PRODUCT_DETAILS_TAX );
			if ( is_wp_error( $details ) ) {
				continue;
			}
			foreach ( $details as $detail ) {
				if ( is_object( $detail ) && $detail->name ) {
					$details_names[ $detail->name ] = true;
				}
			}
		}
	}

	foreach ( array_keys( $details_names ) as $detail_name ) {
		$decoded_name = html_entity_decode( $detail_name, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		$is_selected = ( $selected_value !== '' && $detail_name === $selected_value );
		
		$choices[] = array(
			'text'       => $decoded_name,
			'value'      => $detail_name,
			'isSelected' => $is_selected,
		);
	}

	return $choices;
}

/**
 * Helper function to get manufactured by choices by state (server-side, mirrors AJAX handler).
 *
 * @param string $state The state value.
 * @param string $selected_value Optional. The value to mark as selected.
 * @return array Array of choices compatible with GF field->choices.
 */
function once_gf_populate_get_manufactured_by_choices( $state, $selected_value = '' ) {
	$choices = array(
		array(
			'text'       => 'Please Select',
			'value'      => '',
			'isSelected' => ( $selected_value === '' ),
		),
	);

	if ( empty( $state ) ) {
		return $choices;
	}

	$state_term = get_term_by( 'name', $state, ONCE_GF_POPULATE_PRODUCT_STATE_ACF );
	if ( ! $state_term || is_wp_error( $state_term ) ) {
		return $choices;
	}

	$state_mfg = null;

	if ( function_exists( 'get_field' ) ) {
		$state_mfg = get_field( 'state_mfg', ONCE_GF_POPULATE_PRODUCT_STATE_ACF . '_' . $state_term->term_id );
	}

	if ( ! $state_mfg ) {
		$state_mfg = get_term_meta( $state_term->term_id, 'state_mfg', true );
	}

	if ( is_array( $state_mfg ) && ! empty( $state_mfg ) ) {
		foreach ( $state_mfg as $mfg ) {
			if ( is_string( $mfg ) ) {
				$mfg = trim( $mfg );
				if ( ! empty( $mfg ) ) {
					$decoded_name = html_entity_decode( $mfg, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					$is_selected = ( $selected_value !== '' && $mfg === $selected_value );
					
					$choices[] = array(
						'text'       => $decoded_name,
						'value'      => $mfg,
						'isSelected' => $is_selected,
					);
				}
			}
		}
	} elseif ( is_string( $state_mfg ) ) {
		$state_mfg = trim( $state_mfg );
		if ( ! empty( $state_mfg ) ) {
			$mfgs = preg_split( '/[\r\n,]+/', $state_mfg );
			foreach ( $mfgs as $mfg ) {
				$mfg = trim( $mfg );
				if ( ! empty( $mfg ) ) {
					$decoded_name = html_entity_decode( $mfg, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					$is_selected = ( $selected_value !== '' && $mfg === $selected_value );
					
					$choices[] = array(
						'text'       => $decoded_name,
						'value'      => $mfg,
						'isSelected' => $is_selected,
					);
				}
			}
		}
	}

	return $choices;
}

/**
 * Helper function to get return reason choices by form (server-side, mirrors AJAX handler).
 *
 * @param string $form The form value.
 * @param string $selected_value Optional. The value to mark as selected.
 * @return array Array of choices compatible with GF field->choices.
 */
function once_gf_populate_get_return_reason_choices( $form, $selected_value = '' ) {
	$choices = array(
		array(
			'text'       => 'Please Select',
			'value'      => '',
			'isSelected' => ( $selected_value === '' ),
		),
	);

	if ( empty( $form ) ) {
		return $choices;
	}

	$form_term = get_term_by( 'name', $form, ONCE_GF_POPULATE_PRODUCT_FORM_TAX );
	if ( ! $form_term || is_wp_error( $form_term ) ) {
		return $choices;
	}

	$return_reason = null;

	if ( function_exists( 'get_field' ) ) {
		$return_reason = get_field( 'return_reason', ONCE_GF_POPULATE_PRODUCT_FORM_TAX . '_' . $form_term->term_id );
	}

	if ( ! $return_reason ) {
		$return_reason = get_term_meta( $form_term->term_id, 'return_reason', true );
	}

	if ( is_array( $return_reason ) && ! empty( $return_reason ) ) {
		foreach ( $return_reason as $reason ) {
			if ( is_string( $reason ) ) {
				$reason = trim( $reason );
				if ( ! empty( $reason ) ) {
					$decoded_name = html_entity_decode( $reason, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					$is_selected = ( $selected_value !== '' && $reason === $selected_value );
					
					$choices[] = array(
						'text'       => $decoded_name,
						'value'      => $reason,
						'isSelected' => $is_selected,
					);
				}
			}
		}
	} elseif ( is_string( $return_reason ) ) {
		$return_reason = trim( $return_reason );
		if ( ! empty( $return_reason ) ) {
			$reasons = preg_split( '/[\r\n,]+/', $return_reason );
			foreach ( $reasons as $reason ) {
				$reason = trim( $reason );
				if ( ! empty( $reason ) ) {
					$decoded_name = html_entity_decode( $reason, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
					$is_selected = ( $selected_value !== '' && $reason === $selected_value );
					
					$choices[] = array(
						'text'       => $decoded_name,
						'value'      => $reason,
						'isSelected' => $is_selected,
					);
				}
			}
		}
	}

	return $choices;
}

/**
 * Enhanced pre-validation handler to repopulate AJAX field choices with selected values.
 * This ensures that after validation errors, users see their selections restored.
 *
 * @param array $form GF form array.
 * @return array Modified form.
 */
function once_gf_populate_pre_validation_handler( $form ) {
	// First, populate the state field choices (non-AJAX field)
	$form = once_gf_populate_populate_field_choices( $form );

	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) {
		return $form;
	}

	// Get submitted values from POST using helper function
	$state_value = once_gf_populate_get_post_value( ONCE_GF_POPULATE_FIELD_ID );
	$store_value = once_gf_populate_get_post_value( ONCE_GF_POPULATE_STORE_NAME_FIELD_ID );
	$brand_value = once_gf_populate_get_post_value( ONCE_GF_POPULATE_BRAND_FIELD_ID );
	$form_value = once_gf_populate_get_post_value( ONCE_GF_POPULATE_FORM_FIELD_ID );
	$product_type_value = once_gf_populate_get_post_value( ONCE_GF_POPULATE_PRODUCT_TYPE_FIELD_ID );
	$product_details_value = once_gf_populate_get_post_value( ONCE_GF_POPULATE_PRODUCT_DETAILS_FIELD_ID );
	$manufactured_by_value = once_gf_populate_get_post_value( ONCE_GF_POPULATE_MANUFACTURED_BY_FIELD_ID );
	$return_reason_value = once_gf_populate_get_post_value( ONCE_GF_POPULATE_RETURN_REASON_FIELD_ID );

	// Repopulate each AJAX field with choices based on dependencies
	foreach ( $form['fields'] as &$field ) {
		$field_id = intval( $field->id );

		// Store Name field - depends on State
		if ( $field_id === intval( ONCE_GF_POPULATE_STORE_NAME_FIELD_ID ) ) {
			if ( isset( $field->type ) && in_array( $field->type, array( 'select', 'multiselect' ), true ) ) {
				$field->choices = once_gf_populate_get_store_choices( $state_value, $store_value );
			}
		}

		// Brand field - depends on State
		if ( $field_id === intval( ONCE_GF_POPULATE_BRAND_FIELD_ID ) ) {
			if ( isset( $field->type ) && in_array( $field->type, array( 'select', 'multiselect' ), true ) ) {
				$field->choices = once_gf_populate_get_brand_choices( $state_value, $brand_value );
			}
		}

		// Form field - depends on State + Brand
		if ( $field_id === intval( ONCE_GF_POPULATE_FORM_FIELD_ID ) ) {
			if ( isset( $field->type ) && in_array( $field->type, array( 'select', 'multiselect' ), true ) ) {
				$field->choices = once_gf_populate_get_form_choices( $state_value, $brand_value, $form_value );
			}
		}

		// Product Type field - depends on State + Brand + Form
		if ( $field_id === intval( ONCE_GF_POPULATE_PRODUCT_TYPE_FIELD_ID ) ) {
			if ( isset( $field->type ) && in_array( $field->type, array( 'select', 'multiselect' ), true ) ) {
				$field->choices = once_gf_populate_get_product_type_choices( $state_value, $brand_value, $form_value, $product_type_value );
			}
		}

		// Product Details field - depends on State + Brand + Form + Product Type
		if ( $field_id === intval( ONCE_GF_POPULATE_PRODUCT_DETAILS_FIELD_ID ) ) {
			if ( isset( $field->type ) && in_array( $field->type, array( 'select', 'multiselect' ), true ) ) {
				$field->choices = once_gf_populate_get_product_details_choices( $state_value, $brand_value, $form_value, $product_type_value, $product_details_value );
			}
		}

		// Manufactured By field - depends on State
		if ( $field_id === intval( ONCE_GF_POPULATE_MANUFACTURED_BY_FIELD_ID ) ) {
			if ( isset( $field->type ) && in_array( $field->type, array( 'select', 'multiselect' ), true ) ) {
				$field->choices = once_gf_populate_get_manufactured_by_choices( $state_value, $manufactured_by_value );
			}
		}

		// Return Reason field - depends on Form
		if ( $field_id === intval( ONCE_GF_POPULATE_RETURN_REASON_FIELD_ID ) ) {
			if ( isset( $field->type ) && in_array( $field->type, array( 'select', 'multiselect' ), true ) ) {
				$field->choices = once_gf_populate_get_return_reason_choices( $form_value, $return_reason_value );
			}
		}
	}

	return $form;
}

/**
 * Initialize Store Name, Brand, Form, Product Type, Product Details, Manufactured By, and Return Reason fields with placeholder on form render
 */
add_filter( 'gform_pre_render_' . ONCE_GF_POPULATE_FORM_ID, function ( $form ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) return $form;

	// Check if this is a form rerender after validation (POST request with form data)
	$is_form_submission = ! empty( $_POST ) && isset( $_POST['is_submit_' . ONCE_GF_POPULATE_FORM_ID] );
	
	if ( $is_form_submission ) {
		// If form was submitted (validation error rerender), populate choices with submitted values
		$form = once_gf_populate_pre_validation_handler( $form );
	} else {
		// Initial render: set placeholder choices
		foreach ( $form['fields'] as &$field ) {
			if ( intval( $field->id ) === intval( ONCE_GF_POPULATE_STORE_NAME_FIELD_ID )
				|| intval( $field->id ) === intval( ONCE_GF_POPULATE_BRAND_FIELD_ID )
				|| intval( $field->id ) === intval( ONCE_GF_POPULATE_FORM_FIELD_ID )
				|| intval( $field->id ) === intval( ONCE_GF_POPULATE_PRODUCT_TYPE_FIELD_ID )
				|| intval( $field->id ) === intval( ONCE_GF_POPULATE_PRODUCT_DETAILS_FIELD_ID )
				|| intval( $field->id ) === intval( ONCE_GF_POPULATE_MANUFACTURED_BY_FIELD_ID )
				|| intval( $field->id ) === intval( ONCE_GF_POPULATE_RETURN_REASON_FIELD_ID )
			) {
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
	}
	return $form;
}, 5 );