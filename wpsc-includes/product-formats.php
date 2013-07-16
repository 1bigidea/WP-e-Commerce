<?php
/**
 * Product format functions.
 *
 * @package WordPress
 * @subpackage Post
 */

/**
 * Retrieve the format slug for a post
 *
 * @since 3.1.0
 *
 * @param int|object $post Post ID or post object. Optional, default is the current post from the loop.
 * @return mixed The format if successful. False otherwise.
 */
function wpsc_get_product_format( $post = null ) {
	if ( ! $post = get_post( $post ) )
		return false;

	if ( ! post_type_supports( $post->post_type, 'wpec-product-formats' ) )
		return false;

	$_format = get_the_terms( $post->ID, 'product_format' );

	if ( empty( $_format ) )
		return false;

	$format = array_shift( $_format );

	return str_replace('wpec-product-format-', '', $format->slug );
}

/**
 * Check if a post has a particular format
 *
 * @since 3.1.0
 *
 * @uses has_term()
 *
 * @param string $format The format to check for.
 * @param object|int $post The post to check. If not supplied, defaults to the current post if used in the loop.
 * @return bool True if the post has the format, false otherwise.
 */
function wpsc_has_product_format( $format, $post = null ) {
	return has_term('wpec-product-format-' . sanitize_key($format), 'product_format', $post);
}

/**
 * Assign a format to a post
 *
 * @since 3.1.0
 *
 * @param int|object $post The post for which to assign a format.
 * @param string $format A format to assign. Use an empty string or array to remove all formats from the post.
 * @return mixed WP_Error on error. Array of affected term IDs on success.
 */
function wpsc_set_product_format( $post, $format ) {
	$post = get_post( $post );

	if ( empty( $post ) )
		return new WP_Error( 'invalid_post', __( 'Invalid post' ) );

	if ( ! empty( $format ) ) {
		$format = sanitize_key( $format );
		if ( 'standard' === $format || ! in_array( $format, get_product_format_slugs() ) )
			$format = '';
		else
			$format = 'wpec-product-format-' . $format;
	}

	return wp_set_post_terms( $post->ID, $format, 'product_format' );
}

/**
 * Returns an array of product format slugs to their translated and pretty display versions
 *
 * @since 3.1.0
 *
 * @return array The array of translated product format names.
 */
function wpsc_get_product_format_strings() {
	$strings = array(
		'standard' => _x( 'Standard', 'Product format' ), // Special case. any value that evals to false will be considered standard
		'physical' => _x( 'Physical', 'Product format' ),
		'digital'  => _x( 'Digital',  'Product format' ),
		'group'    => _x( 'Group',    'Product format' ),
	);
	return $strings;
}

/**
 * Retrieves an array of product format slugs.
 *
 * @since 3.1.0
 *
 * @uses get_product_format_strings()
 *
 * @return array The array of product format slugs.
 */
function wpsc_get_product_format_slugs() {
	$slugs = array_keys( get_product_format_strings() );
	return array_combine( $slugs, $slugs );
}

/**
 * Returns a pretty, translated version of a product format slug
 *
 * @since 3.1.0
 *
 * @uses get_product_format_strings()
 *
 * @param string $slug A product format slug.
 * @return string The translated product format name.
 */
function wpsc_get_product_format_string( $slug ) {
	$strings = get_product_format_strings();
	if ( !$slug )
		return $strings['standard'];
	else
		return ( isset( $strings[$slug] ) ) ? $strings[$slug] : '';
}

/**
 * Returns a link to a product format index.
 *
 * @since 3.1.0
 *
 * @param string $format The product format slug.
 * @return string The product format term link.
 */
function wpsc_get_product_format_link( $format ) {
	$term = get_term_by('slug', 'wpec-product-format-' . $format, 'product_format' );
	if ( ! $term || is_wp_error( $term ) )
		return false;
	return get_term_link( $term );
}

/**
 * Filters the request to allow for the format prefix.
 *
 * @access private
 * @since 3.1.0
 */
function _wpsc_product_format_request( $qvs ) {
	if ( ! isset( $qvs['product_format'] ) )
		return $qvs;
	$slugs = get_product_format_slugs();
	if ( isset( $slugs[ $qvs['product_format'] ] ) )
		$qvs['product_format'] = 'wpec-product-format-' . $slugs[ $qvs['product_format'] ];
	$tax = get_taxonomy( 'product_format' );
	if ( ! is_admin() )
		$qvs['post_type'] = $tax->object_type;
	return $qvs;
}
add_filter( 'request', '_wpsc_product_format_request' );

/**
 * Filters the product format term link to remove the format prefix.
 *
 * @access private
 * @since 3.1.0
 */
function _wpsc_product_format_link( $link, $term, $taxonomy ) {
	global $wp_rewrite;
	if ( 'product_format' != $taxonomy )
		return $link;
	if ( $wp_rewrite->get_extra_permastruct( $taxonomy ) ) {
		return str_replace( "/{$term->slug}", '/' . str_replace( 'wpec-product-format-', '', $term->slug ), $link );
	} else {
		$link = remove_query_arg( 'product_format', $link );
		return add_query_arg( 'product_format', str_replace( 'wpec-product-format-', '', $term->slug ), $link );
	}
}
add_filter( 'term_link', '_wpsc_product_format_link', 10, 3 );

/**
 * Remove the product format prefix from the name property of the term object created by get_term().
 *
 * @access private
 * @since 3.1.0
 */
function _wpsc_product_format_get_term( $term ) {
	if ( isset( $term->slug ) ) {
		$term->name = get_product_format_string( str_replace( 'wpec-product-format-', '', $term->slug ) );
	}
	return $term;
}
add_filter( 'get_product_format', '_wpsc_product_format_get_term' );

/**
 * Remove the product format prefix from the name property of the term objects created by get_terms().
 *
 * @access private
 * @since 3.1.0
 */
function _wpsc_product_format_get_terms( $terms, $taxonomies, $args ) {
	if ( in_array( 'product_format', (array) $taxonomies ) ) {
		if ( isset( $args['fields'] ) && 'names' == $args['fields'] ) {
			foreach( $terms as $order => $name ) {
				$terms[$order] = get_product_format_string( str_replace( 'wpec-product-format-', '', $name ) );
			}
		} else {
			foreach ( (array) $terms as $order => $term ) {
				if ( isset( $term->taxonomy ) && 'product_format' == $term->taxonomy ) {
					$terms[$order]->name = get_product_format_string( str_replace( 'wpec-product-format-', '', $term->slug ) );
				}
			}
		}
	}
	return $terms;
}
add_filter( 'get_terms', '_wpsc_product_format_get_terms', 10, 3 );

/**
 * Remove the product format prefix from the name property of the term objects created by wp_get_object_terms().
 *
 * @access private
 * @since 3.1.0
 */
function _wpsc_product_format_wp_get_object_terms( $terms ) {
	foreach ( (array) $terms as $order => $term ) {
		if ( isset( $term->taxonomy ) && 'product_format' == $term->taxonomy ) {
			$terms[$order]->name = get_product_format_string( str_replace( 'wpec-product-format-', '', $term->slug ) );
		}
	}
	return $terms;
}
add_filter( 'wp_get_object_terms', '_wpsc_product_format_wp_get_object_terms' );

/**
 * Generate a title from the post content or format.
 *
 * @since 3.6.0
 * @access private
 */
function _wpsc_product_formats_generate_title( $content, $product_format = '' ) {
	$title = wp_trim_words( strip_shortcodes( $content ), 8, '' );

	if ( empty( $title ) )
		$title = get_product_format_string( $product_format );

	return $title;
}

/**
 * Fixes empty titles for aside and status formats.
 *
 * Passes a generated post title to the 'wp_insert_post_data' filter.
 *
 * @since 3.6.0
 * @access private
 *
 * @uses _product_formats_generate_title()
 */
function _wpsc_product_formats_fix_empty_title( $data, $postarr ) {
	if ( 'auto-draft' == $data['post_status'] || ! post_type_supports( $data['post_type'], 'wpec-product-formats' ) )
		return $data;

	$post_id = ( isset( $postarr['ID'] ) ) ? absint( $postarr['ID'] ) : 0;
	$product_format = '';

	if ( $post_id )
		$product_format = get_product_format( $post_id );

	if ( isset( $postarr['product_format'] ) )
		$product_format = ( in_array( $postarr['product_format'], get_product_format_slugs() ) ) ? $postarr['product_format'] : '';

	if ( ! in_array( $product_format, array( 'aside', 'status' ) ) )
		return $data;

	if ( $data['post_title'] == _product_formats_generate_title( $data['post_content'], $product_format ) )
		return $data;

	// If updating an existing post, check whether the title was auto-generated.
	if ( $post_id && $post = get_post( $post_id ) )
		if ( $post->post_title == $data['post_title'] && $post->post_title == _product_formats_generate_title( $post->post_content, get_product_format( $post->ID ) ) )
			$data['post_title'] = '';

	if ( empty( $data['post_title'] ) )
		$data['post_title'] = _product_formats_generate_title( $data['post_content'], $product_format );

	return $data;
}
