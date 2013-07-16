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
    global $post;

    // Only needed on the Product Add/Edit screen
    $current_screen = get_current_screen();
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || empty( $current_screen ) || $current_screen->id != 'wpsc-product' || $post->post_type != 'wpsc-product' )
        return;

    $product = (array) $post;

    $product_data = get_post_custom( $post->ID );
    $product_meta = maybe_unserialize( $product_data );

    foreach ( $product_meta as $meta_key => $meta_value )
        $product['_meta'][$meta_key] = maybe_unserialize($meta_value[0]);

kickout('wpsc_setup_product_editor_meta_boxes', $product, $product_data, $product_meta);

    remove_meta_box( 'wpsc-variationdiv', 'wpsc-product', 'side' );

	wpsc_product_pricing_metabox($product);
    wpsc_product_inventory_metabox($product);
return;
    wpsc_product_details_metabox($product);
	wpsc_product_tax_metabox($product);
	wpsc_product_delivery_metabox($product);
}

/**
 * Assemble the Product Pricing Metabox
 *
 * @param   array   $product    Array of all the elements for this product
 */
function wpsc_product_pricing_metabox($product){
    global $wpsc_product_defaults;

    if ( !isset( $product['special'] ) )
        $product['special'] = $wpsc_product_defaults['special'];

    if ( !isset( $product['_meta']['_wpsc_is_donation'] ) )
        $product['_meta']['_wpsc_is_donation'] = $wpsc_product_defaults['donation'];

    if ( !isset( $product['_meta']['_wpsc_price'] ) )
        $product['_meta']['_wpsc_price'] = $wpsc_product_defaults['price'];

    if ( !isset( $product['_meta']['_wpsc_special_price'] ) )
        $product['_meta']['_wpsc_special_price'] = $wpsc_product_defaults['special_price'];

    $product['_meta']['_wpsc_price']            = wpsc_format_number( $product['_meta']['_wpsc_price'] );
    $product['_meta']['_wpsc_special_price']    = wpsc_format_number( $product['_meta']['_wpsc_special_price'] );

    $product_pricing = new WPSC_MetaBox('wpsc-product', 'wpsc_price_control_forms', array(
		'metabox_title'	=> __('Product Pricing', 'wpsc'),
		'location'	=> array('side', 'high'),
		'title_callback'	=> 'wpsc_product_pricing_metabar',
        'title_args'    => array('product' => $product)
	));

    $symbol = wpsc_get_currency_symbol();
    if( ! empty($symbol) ) $symbol = " ({$symbol}) ";
	$price_fields = $product_pricing->add_fieldset('product_price');
			$price_fields->add_field('wpsc_price')
                ->set_name('meta[_wpsc_price]')
                ->set_money($product['_meta']['_wpsc_price'])
				->set_label( sprintf( __('Price', 'wpsc').'%s', $symbol ) )
				->set_width(10);
			$price_fields->add_field('wpsc_special_price')
                ->set_name('meta[_wpsc_special_price]')
                ->set_money($product['_meta']['_wpsc_special_price'])
				->set_label(sprintf( __('Sale Price', 'wpsc').'%s', $symbol ) )
				->set_width(10);

	$product_pricing->add_fieldset('pricing_alt_currencies')
		->set_callback('wpsc_price_alt_currencies', $product);

	$product_pricing->add_fieldset('pricing_table_rates')
		->set_callback('wpsc_price_table_rates', $product);

	$product_pricing->add_field('add_form_donation')
        ->set_name('meta[_wpsc_is_donation]')
		->set_checkbox($product['_meta']['_wpsc_is_donation'])
		->set_label( __('Purchase is a donation', 'wpsc'));

}

        /**
         * Sanitize inputs in the Product Pricing Metabox
         *
         * Executes on wpsc_admin_submit_product filter
         *
         * @param   array $post_data    fields from the submitted form
         * @return  array $post_data
         */
        function wpsc_product_pricing_metabox_sanitize($post_data){
kickout('wpsc_product_pricing_metabox_sanitize', $post_data);
            if ( isset( $post_data['meta']['_wpsc_price'] ) )
                $post_data['meta']['_wpsc_price'] = wpsc_string_to_float( $post_data['meta']['_wpsc_price'] );
            if ( isset( $post_data['meta']['_wpsc_special_price'] ) )
                $post_data['meta']['_wpsc_special_price'] = wpsc_string_to_float( $post_data['meta']['_wpsc_special_price'] );

            /**
             *  Update table rate price
             */
            $post_data['meta']['_wpsc_product_metadata']['table_rate_price'] = isset( $post_data['table_rate_price'] ) ? $post_data['table_rate_price'] : array();

            // Is the Table Rate Box checked?
            if ( $post_data['table_rate_price']['state'] ) {
                // Remove empty rows
                if ( ! empty( $post_data['meta']['_wpsc_product_metadata']['table_rate_price']['table_price'] ) ) {
                    foreach ( (array) $post_data['meta']['_wpsc_product_metadata']['table_rate_price']['table_price'] as $key => $value ){
                        if(empty($value)){
                            unset($post_data['meta']['_wpsc_product_metadata']['table_rate_price']['table_price'][$key]);
                            unset($post_data['meta']['_wpsc_product_metadata']['table_rate_price']['quantity'][$key]);
                        }
                    }
                }
            } else {
                // if table_rate_price is unticked, wipe the table rate prices
                $post_data['meta']['_wpsc_product_metadata']['table_rate_price']['table_price'] = array();
                $post_data['meta']['_wpsc_product_metadata']['table_rate_price']['quantity'] = array();
            }

            return $post_data;
        }
        add_filter('wpsc_admin_submit_product', 'wpsc_product_pricing_metabox_sanitize');

        /**
         * Builds the titlebar for the Product Pricing Metabox
         *
         * @param string    $title      Default title for Metabox
         * @param object    $meta_box   The current metabox
         * @return string
         */
        function wpsc_product_pricing_metabar($title, $meta_box){

            $product = $meta_box->get_title_args('product');

            $special_price = $product['_meta']['_wpsc_special_price'];
            $reg_price = wpsc_currency_display($product['_meta']['_wpsc_price']);
            if( !empty($special_price) && 0.0 != (float) $special_price ) {
                $reg_price = '<del>'.$reg_price.'</del>';
                $special_price = wpsc_currency_display($special_price);
            } else {
                $special_price = '';
            }

			return wp_kses_post( sprintf('<em>%1$s&nbsp;%2$s</em>',
                $reg_price,
                $special_price
            ));
		}

        /**
         * Outout the Alternate Currency Section for the Product Pricing Metabox
         *
         * @param object $fieldset_obj  The current fieldset object
         */
        function wpsc_price_alt_currencies($fieldset_obj){

            $product = $fieldset_obj->get_callback_arg();

            if ( isset( $product['_meta']['_wpsc_currency'] ) )
                $product_alt_currency = maybe_unserialize( $product['_meta']['_wpsc_currency'] );

            $countries = WPSC_Country::get_all();
?>
            <a href='#' class='wpsc_add_new_currency'><?php esc_html_e( '+ New Currency', 'wpsc' ); ?></a>
            <br />
                <!-- add new currency layer -->
            <div class='new_layer'>
                <label for='newCurrency[]'><?php esc_html_e( 'Currency type', 'wpsc' ); ?>:</label><br />
                <select name='newCurrency[]' class='newCurrency' style='width:42%'>
                    <option value="" selected="selected"><?php esc_html_e('Choose Currency', 'wpsc'); ?></option>
<?php
                    foreach ( (array)$countries as $country_obj ) { $currency = $country_obj->get_data(); ?>

                        <option value='<?php esc_attr_e($currency['id']); ?>' >
                            <?php echo esc_html_e( $currency['country'] ); ?> (<?php esc_html_e($currency['currency']); ?>)
                        </option> <?php
                    }
?>
                </select>
                <?php esc_html_e( 'Price', 'wpsc' ); ?> :
                <input type='text' class='wpec_text_money' size='8' name='newCurrPrice[]' value='0.00' style='display:inline' />
                <a href='' class='wpsc_delete_currency_layer'><img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/cross.png' /></a>

            </div> <!-- close new_layer -->
<?php
            if ( isset( $product_alt_currency ) && is_array( $product_alt_currency ) ) :
                $i = 0;
                foreach ( $product_alt_currency as $iso => $alt_price ) {
                    $i++; ?>
                    <div class='wpsc_additional_currency'>
                        <label for='newCurrency[]'><?php esc_html_e( 'Currency type', 'wpsc' ); ?>:</label><br />
                        <select name='newCurrency[]' class='newCurrency' style='width:42%'> <?php
                            foreach ( $countries as $country_obj ) { $currency = $country_obj->get_data();
                                if ( $iso == $currency['isocode'] )
                                    $selected = "selected='selected'";
                                else
                                    $selected = ""; ?>
                                <option value='<?php echo $currency['id']; ?>' <?php echo $selected; ?> >
                                    <?php echo htmlspecialchars( $currency['country'] ); ?> (<?php echo $currency['currency']; ?>)
                                </option> <?php
                            } ?>
                        </select>
                        <?php esc_html_e( 'Price:', 'wpsc' ); ?> <input type='text' class='text' size='8' name='newCurrPrice[]' value='<?php echo $alt_price; ?>' style=' display:inline' />
                        <a href='' class='wpsc_delete_currency_layer' rel='<?php echo $iso; ?>'><img src='<?php echo WPSC_CORE_IMAGES_URL; ?>/cross.png' /></a></div>
<?php
            }

            endif;
		}

        /**
         * Outout the Table Rate Section for the Product Pricing Metabox
         *
         * @param object $fieldset_obj  The current fieldset object
         */
		function wpsc_price_table_rates($fieldset_obj){
            global $wpsc_product_defaults;

            $product = $fieldset_obj->get_callback_arg();

            if ( !isset( $product['_meta']['_wpsc_table_rate_price'] ) ) {
                $product['_meta']['_wpsc_table_rate_price'] = $wpsc_product_defaults['meta']['table_rate_price'];
            }
            if ( isset( $product['_meta']['product_data']['_wpsc_table_rate_price'] ) ) {
                $product['_meta']['product_data']['table_rate_price']['state'] = 1;
                $product['_meta']['product_data']['table_rate_price'] += $product['_meta']['product_data']['_wpsc_table_rate_price'];
                $product['_meta']['_wpsc_table_rate_price'] = $product['_meta']['product_data']['_wpsc_table_rate_price'];
            }

            if ( !isset( $product['_meta']['product_data']['table_rate_price']['state'] ) )
                $product['_meta']['product_data']['table_rate_price']['state'] = null;

            if ( !isset( $product['_meta']['product_data']['table_rate_price']['quantity'] ) )
                $product['_meta']['product_data']['table_rate_price']['quantity'] = $wpsc_product_defaults['meta']['table_rate_price']['quantity'][0];

?>
            <input type="hidden" name="table_rate_price[state]" value="0" />
            <input type='checkbox' value='1' name='table_rate_price[state]' id='table_rate_price'  <?php echo ( ( isset($product['_meta']['table_rate_price']['state']) && (bool)$product['_meta']['table_rate_price']['state'] == true ) ? 'checked=\'checked\'' : '' ); ?> />
            <label for='table_rate_price'><?php esc_html_e( 'Table Rate Price', 'wpsc' ); ?></label>
            <div id='table_rate'>
                <a class='add_level' style='cursor:pointer;'><?php esc_html_e( '+ Add level', 'wpsc' ); ?></a><br />
                <br style='clear:both' />
                <table>
                    <tr>
                        <th><?php esc_html_e( 'Quantity In Cart', 'wpsc' ); ?></th>
                        <th colspan='2'><?php esc_html_e( 'Discounted Price', 'wpsc' ); ?></th>
                    </tr>
                    <?php
                    if ( count( $product['_meta']['table_rate_price']['quantity'] ) > 0 ) {
                        foreach ( (array)$product['_meta']['table_rate_price']['quantity'] as $key => $quantity ) {
                            if ( $quantity != '' ) {
                                $table_price = number_format( $product['_meta']['table_rate_price']['table_price'][$key], 2, '.', '' );
                                ?>
                                <tr>
                                    <td>
                                        <input type="text" size="5" value="<?php echo $quantity; ?>" name="table_rate_price[quantity][]"/><span class='description'><?php esc_html_e( 'and above', 'wpsc' ); ?></span>
                                    </td>
                                    <td>
                                        <input type="text" size="10" value="<?php echo $table_price; ?>" name="table_rate_price[table_price][]" />
                                    </td>
                                    <td><img src="<?php echo WPSC_CORE_IMAGES_URL; ?>/cross.png" class="remove_line" /></td>
                                </tr>
                            <?php
                            }
                        }
                    }
                    ?>
                    <tr>
                        <td><input type="text" size="5" value="" name="table_rate_price[quantity][]"/><span class='description'><?php esc_html_e( 'and above', 'wpsc' ); ?></span> </td>
                        <td><input type='text' size='10' value='' name='table_rate_price[table_price][]'/></td>
                    </tr>
                </table>
            </div>
<?php
        }

/**
 *	Product Inventory Metabox
 */
function wpsc_product_inventory_metabox($product){

    $stock_inventory = new WPSC_MetaBox('wpsc-product', 'wpsc_stock_control_forms', array(
        'metabox_title'	=> __('Stock Inventory', 'wpsc'),
        'location'	=> array('side', 'low'),
        'title_callback'	=> 'wpsc_inventory_metabar',
        'title_args'    => array($product)
    ));
    kickout('wpsc_product_inventory_metabox', $stock_inventory);

    // this is to make sure after upgrading to 3.8.9, products will have
    // "notify_when_none_left" enabled by default if "unpublish_when_none_left"
    // is enabled.
    if ( !isset( $product['_meta']['notify_when_none_left'] ) ) {
        $product['_meta']['notify_when_none_left'] = 0;
        if ( ! empty( $product['_meta']['unpublish_when_none_left'] ) )
            $product['_meta']['notify_when_none_left'] = 1;
    }

    if ( !isset( $product['_meta']['unpublish_when_none_left'] ) )
        $product['_meta']['unpublish_when_none_left'] = '';

    if ( ! empty( $product['_meta']['unpublish_when_none_left'] ) && ! isset( $product['_meta']['notify_when_none_left'] ) )

    $stock_inventory->add_field('wpsc_sku')
        ->set_name('meta[_wpsc_sku]')
        ->set_label( __('SKU', 'wpsc') )
        ->set_type('text_medium')
        ->set_value($product['_meta']['_wpsc_sku']);

    $stock_inventory->add_field('add_form_quantity_limited')
        ->set_name('meta[_wpsc_limited_stock]')
        ->set_label( __('Product has limited stock', 'wpsc') )
        ->set_class('limited_stock_checkbox')
        ->set_checkbox($product['meta']['_wpsc_']);

    $stock_inventory->add_field('qty')
        ->set_label( __('Quantity in Stock', 'wpsc') )
        ->set_number($min=1, $max=9999999999, $step=1, $float=false)
        ->set_width(12);

    $on_zero = $stock_inventory->add_fieldset( __('When stock reduces to zero:', 'wpsc') );
    $on_zero->add_field('notify_by_email')
        ->set_label( __('Notify site owner via email', 'wpsc') )
        ->set_checkbox(false);

    $on_zero->add_field('unpublish')
        ->set_label( __('Unpublish product from website', 'wpsc') );
}

function wpsc_product_tax_metabox($product){

    if ( !isset( $product['_meta']['_wpsc_custom_tax'] ) )
        $product['_meta']['_wpsc_custom_tax'] = '';

    if ( !isset( $product['_meta']['custom_tax'] ) ) {
        $product['_meta']['custom_tax'] = 0.00;
    }

    $product_tax = new WPSC_MetaBox('wpsc-product', 'wpsc_product_taxes_forms', array(
        'metabox_title'	    => __('Sales Tax', 'wpsc'),
		'location'	        => array('side', 'low'),
		'title_callback'	=> 'wpsc_product_tax_metabar',
        'title_args'        => array($product)
    ));

	$product_tax->add_field('tax_exempt')
		->set_label( __('Product is exempt from taxation', 'wpsc') )
		->set_checkbox(false);
	$product_tax->add_field('taxable_amount')
		->set_label( __('Taxable Amount', 'wpsc') )
		->set_number($min=1, $max=9999999999, $step=1, $float=false)
		->set_input_prefix( wpsc_get_currency_symbol() );
}

function wpsc_product_delivery_metabox($product){

	$product_delivery = new WPSC_MetaBox('wpsc-product', 'wpsc_product_delivery_forms', array(
        'metabox_title'	    => __('Product Delivery', 'wpsc'),
		'location'	        => array('normal', 'high'),
		'title_callback'	=> 'wpsc_delivery_metabar',
        'title_args'        => array($product)
    ));

	$product_delivery->add_panel('shipping', __('Shipping', 'wpec'))
			->add_panel('download', __('Downloads', 'wpsc'))
			->add_panel('personalization', __('Personalization', 'wpsc'))
			->add_panel('external-link', __('External Link', 'wpsc'));

	$shipping_panel = $product_delivery->get_panel('shipping');
		$shipping_panel->add_field('ship-to-cusomter')
			->set_label( __('Product will be shipped to customer', 'wpsc') )
			->set_checkbox(false);
		$shipping_panel->add_fieldset('ship-calculate')
			->set_callback('wpsc_show_product_shipping_options', $product);

	$downloads_panel = $product_delivery->get_panel('download');
	$downloads_panel->add_field('product_downloadable')
		->set_label( __('Product will be downloadable by customer', 'wpsc') )
		->set_checkbox(false);
	$downloads_panel->add_fieldset('list_downloadable_product')
		->set_callback('wpsc_list_downloadable_products', $product);

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
function wpsc_product_details_metabox($product){

	$product_details = new WPSC_MetaBox('wpsc-product', 'wpsc_product_details_forms', array(
		'metabox_title'	        => __('Product Details', 'wpsc'),
		'location'		        => array('normal', 'high'),
		'title_callback'		=> 'wpsc_product_details_metabar',
        'title_callback_args'   => array($product)
	));

	$panel = $product_details->add_panel('short-desc', __('Short Desciption', 'wpsc'));

		$panel->set_prompt( __('Short Descriptions are optional hand-crafted summaries of your content that can be used in your theme.', 'wpsc') )
			->add_field('addl_desc')
			->set_type('excerpt');

	$panel = $product_details->add_panel('image-gallery', __('Image Gallery', 'wpsc'));
		$panel->set_callback('wpsc_product_image_gallery');

	$panel = $product_details->add_panel('metadata', __('Metadata', 'wpsc'));
		$fieldset = $panel->add_fieldset('metadata_table', array('reorder' => true));
		$fieldset->add_field('meta_key')
			->set_label( __('Name', 'wpsc') )
			->set_text_input();
		$fieldset->add_field('meta_value')
			->set_label( __('Value', 'wpsc') )
			->set_textarea_input();
}

	function wpsc_product_details_metabar(){

	}
