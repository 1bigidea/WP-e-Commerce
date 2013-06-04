<?php

/**
 * Sets up the WPEC metaboxes
 *
 * @uses remove_meta_box()    Removes the default taxonomy meta box so our own can be added
 * @uses add_meta_bax()       Adds metaboxes to the WordPress admin interface
 */
function wpsc_meta_boxes() {
	global $post;
	$pagename = 'wpsc-product';
	remove_meta_box( 'wpsc-variationdiv', 'wpsc-product', 'side' );

	//if a variation page do not show these metaboxes
	if ( is_object( $post ) && $post->post_parent == 0 ) {
		add_meta_box( 'wpsc_product_variation_forms'    , __( 'Variations', 'wpsc' )           , 'wpsc_product_variation_forms'    , $pagename, 'normal', 'high' );
		add_meta_box( 'wpsc_product_external_link_forms', __( 'Off Site Product link', 'wpsc' ), 'wpsc_product_external_link_forms', $pagename, 'normal', 'high' );
	} else if( is_object( $post ) && $post->post_status == "inherit" ) {
		remove_meta_box( 'tagsdiv-product_tag'             , 'wpsc-product', 'core' );
		remove_meta_box( 'wpsc_product_external_link_forms', 'wpsc-product', 'core' );
		remove_meta_box( 'wpsc_product_categorydiv'        , 'wpsc-product', 'core' );
	}

	add_meta_box( 'wpsc_price_control_forms', __('Price Control', 'wpsc'), 'wpsc_price_control_forms', $pagename, 'side', 'low' );
	add_meta_box( 'wpsc_stock_control_forms', __('Stock Control', 'wpsc'), 'wpsc_stock_control_forms', $pagename, 'side', 'low' );
	add_meta_box( 'wpsc_product_taxes_forms', __('Taxes', 'wpsc'), 'wpsc_product_taxes_forms', $pagename, 'side', 'low' );
	add_meta_box( 'wpsc_additional_desc', __('Additional Description', 'wpsc'), 'wpsc_additional_desc', $pagename, 'normal', 'high' );
	add_meta_box( 'wpsc_product_download_forms', __('Product Download', 'wpsc'), 'wpsc_product_download_forms', $pagename, 'normal', 'high' );
	add_meta_box( 'wpsc_product_image_forms', __('Product Images', 'wpsc'), 'wpsc_product_image_forms', $pagename, 'normal', 'high' );
	if ( ! empty( $post->ID ) && ! wpsc_product_has_variations( $post->ID ) )
		add_meta_box( 'wpsc_product_shipping_forms', __('Shipping', 'wpsc'), 'wpsc_product_shipping_forms_metabox', $pagename, 'normal', 'high' );
	add_meta_box( 'wpsc_product_advanced_forms', __('Advanced Settings', 'wpsc'), 'wpsc_product_advanced_forms', $pagename, 'normal', 'high' );
}
add_action( 'admin_footer', 'wpsc_meta_boxes' );

function wpsc_setup_product_editor_meta_boxes(){

	wpsc_product_pricing_metabox();
	wpsc_product_tax_metabox();
	wpsc_product_inventory_metabox();
	wpsc_product_delivery_metabox();
	wpsc_product_details_metabox();
}

function wpsc_product_pricing_metabox(){

	$product_pricing = new WPEC_Meta_Box('pricing');
}

function wpsc_product_inventory_metabox(){

	$stock_inventory = new WPEC_Meta_Box('inventory');
		$stock_inventory->add_field('product_sku')
			->label( __('SKU', 'wpsc') )
			->input('product_sku');
		$stock_inventory->add_field('limited_stock')
			->label( __('Product has limited stock', 'wpsc') )
			->checkbox(false);
		$stock_inventory->add_field('qty')
			->label( __('Quantity in Stock', 'wpsc') )
			->number($min=1, $max=9999999999, $step=1, $float=false)
			->width(12);
		$on_zero = $stock_inventory->fieldgroup( __('When stock reduces to zero:', 'wpsc') )
			->add_field('notify_by_email')
				->label( __('Notify site owner via email', 'wpsc') )
				->checkbox(false);
		$on_zero->add_field('unpublish')
			->label( __('Unpublish product from website', 'wpsc') )
}

function wpsc_product_tax_metabox(){

	$product_tax = new WPEC_Meta_Box('taxation');
	$product_tax->add_field('tax_exempt')
		->label( __('Product is exempt from taxation', 'wpsc') )
		->checkbox(false);
	$product_tax->add_field('taxable_amount')
		->label( __('Taxable Amount', 'wpsc') )
		->text_input()
		->input_prefix( wpsc_get_currency_symbol() );
}

function wpsc_product_delivery_metabox(){

	$product_delivery = new WPEC_Meta_Box('delivery')
		->add_panel('shipping')
		->add_panel('download')
		->add_panel('personalization')
		->add_panel('external-link');

	$shipping_panel = $product_delivery->get_panel('shipping');

	$downloads_panel = $product_delivery->get_panel('download');
	$downloads_panel->add_field('product_downloadable')
		->label( __('Product will be downloadable by customer', 'wpsc') )
		->checkbox(false);
	$downloads_panel->add_field( array('callback' => 'wpsc_list_downloadable_products') );

	$personalization_panel = $product_delivery->get_panel('personalization');

	$links_panel = $product_delivery->get_panel('external-link');
}

/**
 * Setup Product Details Metabox
 *
 * @package		WordPress
 * @subpackage	wp-e-commerce
 */
function wpsc_product_details_metabox(){

	$product_details = new WPEC_Meta_Box('details')
		->add_panel('short-desc')
		->add_panel('image-gallery')
		->add_panel('metadata');

		$product_details->get_panel('short_desc')
			->add_field('addl_desc')
			->input('editor');

		$product_details->get_panel('image-gallery')
			->add_callback('oh_crap');

		$details_metadata = $product_details->get_panel('metadata')
			-> $details_metadata->add_fieldset(array('reorder' => true));
		$details_metadata->add_field('meta_key')
			->label( __('Name', 'wpsc') )
			->text_input();
		$details_metadata->add_field('meta_value')
			->label( __('Value', 'wpsc') )
			->textarea_input();
}