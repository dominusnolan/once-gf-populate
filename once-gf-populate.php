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
			'text'  => $brand_name,
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
			'text'  => $form_name,
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
			'text'  => $type_name,
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
			'text'  => $detail_name,
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

	// Query products that have this state
	$args = array(
		'post_type'      => ONCE_GF_POPULATE_PRODUCTS_CPT,
		'post_status'    => 'publish',
		'posts_per_page' => ONCE_GF_POPULATE_MAX_PRODUCTS,
		'tax_query'      => array(
			array(
				'taxonomy' => ONCE_GF_POPULATE_PRODUCT_STATE_ACF,
				'field'    => 'term_id',
				'terms'    => $state_term->term_id,
			),
		),
		'fields' => 'ids',
	);

	$query = new WP_Query( $args );
	$manufactured_by_values = array();

	if ( $query->have_posts() ) {
		foreach ( $query->posts as $product_id ) {
			$state_mfg = null;

			if ( function_exists( 'get_field' ) ) {
				$state_mfg = get_field( 'state_mfg', $product_id );
			}

			if ( ! $state_mfg ) {
				$state_mfg = get_post_meta( $product_id, 'state_mfg', true );
			}

			if ( is_string( $state_mfg ) ) {
				$state_mfg = trim( $state_mfg );
			}

			if ( ! empty( $state_mfg ) && is_string( $state_mfg ) ) {
				$manufactured_by_values[ $state_mfg ] = true;
			}
		}
	}

	wp_reset_postdata();

	$choices = array();
	foreach ( array_keys( $manufactured_by_values ) as $mfg_value ) {
		$choices[] = array(
			'value' => $mfg_value,
			'text'  => $mfg_value,
		);
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
						'text'  => $reason,
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
						'text'  => $reason,
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
 * Initialize Store Name, Brand, Form, Product Type, Product Details, Manufactured By, and Return Reason fields with placeholder on form render
 */
add_filter( 'gform_pre_render_' . ONCE_GF_POPULATE_FORM_ID, function ( $form ) {
	if ( empty( $form['fields'] ) || ! is_array( $form['fields'] ) ) return $form;

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
	return $form;
}, 5 );