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
//add_action( 'admin_footer', 'wpsc_meta_boxes' );
add_action( 'admin_footer', 'wpsc_setup_product_editor_meta_boxes' );

function wpsc_setup_product_editor_meta_boxes(){

	remove_meta_box( 'wpsc-variationdiv', 'wpsc-product', 'side' );

	wpsc_product_pricing_metabox();
return;
	wpsc_product_tax_metabox();
	wpsc_product_inventory_metabox();
	wpsc_product_delivery_metabox();
	wpsc_product_details_metabox();
}

function wpsc_product_pricing_metabox(){

	$product_pricing = new WPEC_MetaBox('wpsc-product', 'wpsc_price_control_form', array(
		'metabox_title'	=> __('Product Pricing', 'wpsc'),
		'location'	=> array('side', 'high'),
		'title_callback'	=> 'wpsc_product_pricing_metabar'
	));

	$price_fields = $product_pricing->add_fieldset('product_price');
			$price_fields->add_field('price')
				->set_label( __('Price', 'wpsc').' '. wpsc_get_currency_symbol() )
				->set_number($min=0, $max=9999999999, $step=.01, $float=true )
				->set_width(12);
			$price_fields->add_field('sale_price')
				->set_label( __('Sale Price', 'wpsc') . ' ' . wpsc_get_currency_symbol() )
				->set_number($min=0, $max=9999999999, $step=.01, $float=true)
				->set_width(12);

	$product_pricing->add_fieldset('pricing_alt_currencies')
		->set_callback('wpsc_price_alt_currencies');

	$product_pricing->add_fieldset('pricing_qty_discounts')
		->set_callback('wpsc_price_qty_discounts');

	$product_pricing->add_field('product_donation')
		->set_checkbox(false)
		->set_label( __('Purchase is a donation', 'wpsc'));
}

		function wpsc_product_pricing_metabar($title){
			return '<em><del>$12.34</del>&nbsp;$11.11</em>';
		}

		function wpsc_price_alt_currencies(){

		}

		function wpsc_price_qty_discounts(){
		}

/**
 *	Product Inventory Metabox
 */
function wpsc_product_inventory_metabox(){

	$stock_inventory = new WPEC_MetaBox('wpsc-product', 'wpsc_stock_control_forms');
	$stock_inventory->set_metabox_title( __('Stock Inventory', 'wpsc') )
		->set_location( 'side', 'low')
		->set_callback('wpsc_inventory_metabar');

		$stock_inventory->add_field('product_sku')
			->set_label( __('SKU', 'wpsc') )
			->set_type('product_sku');
		$stock_inventory->add_field('limited_stock')
			->set_label( __('Product has limited stock', 'wpsc') )
			->set_checkbox(false);
		$stock_inventory->add_field('qty')
			->set_label( __('Quantity in Stock', 'wpsc') )
			->set_number($min=1, $max=9999999999, $step=1, $float=false)
			->set_width(12);
		$on_zero = $stock_inventory->fieldgroup( __('When stock reduces to zero:', 'wpsc') )
			->add_field('notify_by_email')
				->set_label( __('Notify site owner via email', 'wpsc') )
				->set_checkbox(false);
		$on_zero->add_field('unpublish')
			->set_label( __('Unpublish product from website', 'wpsc') );
}

function wpsc_product_tax_metabox(){

	$product_tax = new WPEC_MetaBox('wpsc-product', 'wpsc_product_taxes_forms');
	$product_tax->set_metabox_title( __('Sales Tax', 'wpsc') )
		->set_location('side', 'low')
		->set_callback('wpsc_product_tax_metabar');

	$product_tax->add_field('tax_exempt')
		->set_label( __('Product is exempt from taxation', 'wpsc') )
		->set_checkbox(false);
	$product_tax->add_field('taxable_amount')
		->set_label( __('Taxable Amount', 'wpsc') )
		->set_number($min=1, $max=9999999999, $step=1, $float=false)
		->set_input_prefix( wpsc_get_currency_symbol() );
}

function wpsc_product_delivery_metabox(){

	$product_delivery = new WPEC_MetaBox('wpsc-product', 'wpsc_product_delivery_forms');
		$product_delivery->metabox_title( __('Product Delivery', 'wpsc') )
			->set_location('normal', 'high')
			->set_callback('wpsc_delivery_metabar');

		$product_delivery->add_panel('shipping')
			->add_panel('download')
			->add_panel('personalization')
			->add_panel('external-link');

	$shipping_panel = $product_delivery->get_panel('shipping');
		$shipping_panel->add_field('ship-to-cusomter')
			->set_label( __('Product will be shipped to customer', 'wpsc') )
			->set_checkbox(false);
		$shipping_panel->add_fieldset('ship-calculate')
			->set_callback('wpsc_show_product_shipping_options');

	$downloads_panel = $product_delivery->get_panel('download');
	$downloads_panel->add_field('product_downloadable')
		->set_label( __('Product will be downloadable by customer', 'wpsc') )
		->set_checkbox(false);
	$downloads_panel->add_fieldset('list_downloadable_product')
		->set_callback('wpsc_list_downloadable_products');

	$personalization_panel = $product_delivery->get_panel('personalization');
		$personalization_panel->add_field('personalize_text')
			->set_checkbox(false)
			->set_label( __('Allow personalization with text', 'wpsc') );
		$personalization_panel->add_field('personalize-image')
			->set_checkbox(false)
			->set_label( __('Allow personalization with images', 'wpsc') );

	$links_panel = $product_delivery->get_panel('external-link');
		$links_panel->add_field('external_url')
			->set_label( _x('URL', 'url of external product', 'wpsc') )
			->set_placeholder('http://')
			->set_sanitize_callback('esc_url_raw');
		$links_panel->add_field('external_label')
			->set_label( _x('Label', 'label for external url', 'wpsc') )
			->set_placeholder( __('Buy Now', 'wpsc'));
		$links_panel->add_field('external_target')
			->set_label( _x('Target', 'target attribute for external link', 'wpsc') )
			->set_radio_buttons(array(
				'default' => __('Default (set by theme)', 'wpsc'),
				'_blank'	=> __('Force open in new window', 'wpsc'),
				'_self'		=> __('Force open in same window', 'wpsc')
			));
		$links_panel->set_prompt( __('This option overrides the "Buy Now" and "Add to Cart" buttons, replacing them with the link you describe here.', 'wpsc') );
}

/**
 * Setup Product Details Metabox
 *
 * @package		WordPress
 * @subpackage	wp-e-commerce
 */
function wpsc_product_details_metabox(){

	$product_details = new WPEC_MetaBox('wpsc-product', 'wpsc_product_details_forms');
	$product_details->set_metabox_title( __('Product Details', 'wpsc') )
		->set_location('normal', 'high')
		->set_callback('wpsc_detail_metabar');

	$panel = $product_details->add_panel('short-desc');
		$panel->add_field('addl_desc')
			->set_type('excerpt')
			->set_prompt( __('Short Descriptions are optional hand-crafted summaries of your content that can be used in your theme.', 'wpsc') );

	$panel = $product_details->add_panel('image-gallery');
		$panel->set_callback('wpsc_product_image_gallery');

	$panel = $product_details->add_panel('metadata');
		$fieldset = $panel->add_fieldset('metadata_table', array('reorder' => true));
		$fieldset->add_field('meta_key')
			->set_label( __('Name', 'wpsc') )
			->set_text_input();
		$fieldset->add_field('meta_value')
			->set_label( __('Value', 'wpsc') )
			->set_textarea_input();
}