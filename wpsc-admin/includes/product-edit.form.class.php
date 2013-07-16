<?php

class WPSC_MetaBox {

	private $panels = array();
	private $classes = array();
    private $product_formats = array();
    private $callback_args = null;

	function __construct($post_type, $box_name, $args=array()){

		$this->box_name = $box_name;
		$this->post_type = $post_type;

		foreach($args as $arg => $arg_value){
			$meth = 'set_'.$arg;
			if( is_callable ( array($this, $meth), false ) ){
				$this->{$meth}($arg_value);
			}
		}

		$title = $this->metabox_title;
		$metabar_info = apply_filters('metabar_info_'.$this->box_name, '', $this);
		if( !empty($metabar_info) )
			$title .= '<span class="metabar_info">'.$metabar_info.'</span>';

		add_meta_box($box_name, $title, array($this, 'render_metabox'), $this->post_type, $this->context, $this->priority);
		add_filter("postbox_classes_{$this->post_type}_{$box_name}", array($this, 'add_postbox_classes') );

		$this->add_panel('default');
	}

    function supports($formats = array()){
        $valid_formats = array('standard', 'digital', 'group', 'physical', 'variation');

        if( is_array($formats) ){
            foreach( $formats as $format ){
                if( in_array($format, $valid_formats) ) $this->product_formats[] = $format;
            }
        } else {
            if( in_array($formats, $valid_formats) ) $this->product_formats[] = $formats;
        }
    }

	function set_metabox_title($title){
		$this->metabox_title = $title;
	}

	function set_location($where){
		$this->context = (in_array ( $where[0], array('normal', 'side', 'advanced'))) ? $where[0] : 'advanced';
		$this->priority = (in_array ( $where[1], array('high', 'core', 'default', 'low'))) ? $where[1] : 'default';
	}

	function set_title_callback($callback){
		if( is_callable($callback) )
			add_filter('metabar_info_'.$this->box_name, $callback, 10, 2);
	}

    function set_title_args($args){
        $this->title_args = $args;
    }

    function get_title_args($field=null){

        if( !is_null($field) && isset($this->title_args[$field]) ) return $this->title_args[$field];

        return false;
    }

    function add_panel($panel_name, $panel_label=''){
		$this->panels[$panel_name] = new WPSC_MetaBox_Panel($panel_label);

		if( isset($this->panels['default']) && $panel_name != 'default') unset($this->panels['default']);

		return $this->panels[$panel_name];
	}

	function get_panel($panel_name){
		if( isset($this->panels[$panel_name]) )
			return $this->panels[$panel_name];

		return false;
	}

	function add_fieldset($set_name){
		return $this->panels['default']->add_fieldset($set_name);
	}

	function add_field($name){
		return $this->panels['default']->add_field($name);
	}

	function add_options(){
	}

	function render_metabox(){
kickout('WPSC_MetaBox-'.$this->box_name, $this);

		$show_tabs = false;
		if( count($this->panels) > 1 ){
			$show_tabs = true;
			$this->classes[] = 'wpec-metabox-tabs';
		}
		printf('<div id="wpec_metabox_%s" class="%s wpec_metabox">', esc_attr($this->box_name), esc_attr( implode(' ', $this->classes) ) );

		if( $show_tabs ){
			echo '<ul>';
			foreach($this->panels as $panel => $panel_frame ){
				printf('<li><a href="#%s-%s">%s</a></li>',
					esc_attr($this->box_name),
					esc_attr($panel),
					esc_html($panel_frame->label)
				);
			}
			echo '</ul>';
		}

		foreach($this->panels as $panel => $panel_frame){
            $panel_frame->render_panel($panel, $this->box_name);
		}
		printf("</div><!-- END {%s} -->", esc_html($this->box_name) );
	}

    function render_metabox_prompt($panel_frame, $panel){
        echo '<p class="'.apply_filters("wpec_metabox_{$panel}_panel_classes", 'wpec-metabox-prompt').'">';
        echo apply_filters("wpec_metabox_{$panel}_prompt", $panel_frame['prompt']);
    }

	function add_postbox_classes($classes=array()){
		return $classes;
	}
}

class WPSC_MetaBox_Panel {

	function __construct($panel_label){
		$this->label = $panel_label;
	}

	function set_label($label_value){
		$this->label = $label_value;
	}

	function add_field($field_name){
		$this->fields[$field_name] = new WPSC_MetaBox_Field($field_name);

		return $this->fields[$field_name];
	}

	function add_fieldset($field_name){
		$this->fields[$field_name] = new WPSC_MetaBox_FieldSet();

		return $this->fields[$field_name];
	}

	function set_prompt($prompt){
		$this->prompt = $prompt;

		return $this;
	}

	function set_callback($func){
		if( is_callable($func) )
			$this->callback = $func;

		return $this;
	}

    function render_panel($panel, $metabox){
kickout('render_panel_'.$panel, $this);
        printf('<div id="%s-%s">', esc_attr($metabox), esc_attr($panel));
        do_action('wpsc_metabox_before_'.$panel);

        if( isset($this->fields) ) $this->render_fields($panel, $metabox);

        do_action('wpsc_metabox_after_'.$panel);

        if( isset($panel_frame['prompt']) ) $this->render_metabox_prompt($panel_frame, $panel);

        echo '</div>';
    }

    function render_fields($panel, $metabox){
        foreach($this->fields as $field_name => $field){
kickout('render_fields_panel', $field_name, $field);
            $field->render_field($field_name, $panel, $metabox);
            echo '<br clear="all"><br />';
        }
    }

}

class WPSC_MetaBox_FieldSet{

    private $callback_arg = null;

	function __construct($args=array()){
		$this->args = $args;
	}

	function set_callback($func, $arg=null){
		if( is_callable($func) )
			$this->callback = $func;

        if( !is_null($arg) )
            $this->callback_arg = $arg;

		return $this;
	}

    function get_callback_arg(){
        return $this->callback_arg;
    }

	function add_field($name){
		$this->fields[$name] = new WPSC_MetaBox_Field($name);

		return $this->fields[$name];
	}

    function render_field($name, $panel, $metabox){
kickout('render_fieldset_field_'.$name, $this);
        printf('<div id="%s-%s-%s" class="fieldset-%3$s">', esc_attr($metabox), esc_attr($panel), esc_attr($name));
        do_action('wpsc_fieldset_before_'.$name, $panel, $metabox);

        if( isset($this->fields) ) $this->render_fields($panel, $metabox);
        if( isset($this->callback) ) call_user_func($this->callback, $this, $panel, $metabox);

        do_action('wpsc_fieldset_after_'.$name, $panel, $metabox);
        echo '</div>';
    }

    function render_fields($panel, $metabox){
        foreach($this->fields as $field_name => $field){
            printf('<div class="wpec_fieldset-%s">', esc_attr($field_name) );
            $field->render_field($field_name, $panel, $metabox);
            echo '</div>';
        }
    }

}

class WPSC_MetaBox_Field {
	function __construct($id){
        $this->id = $id;
	}

    function set_name($name){
        $this->name = $name;

        return $this;
    }

	function set_label($label=null){
		$this->label = $label;

		return $this;
	}

    function set_meta($meta){
        $this->meta = $meta;

        return $this;
    }

	function set_type($fieldtype='text', $args=array()){
		$this->type = $fieldtype;
        $this->type_args = $args;

		return $this;
	}

	function set_text_input($args = array()){
		return $this->set_type('text', $args);
	}

	function set_checkbox($value){
        $this->meta = (bool) $value;
		return $this->set_type('checkbox');
	}

    function set_money($value=0){
        $this->value = $value;
        return $this->set_type('text_money');
    }

	function set_number($min=null, $max=null, $step=1, $float=false){

		if( $float )
			return $this->set_type('number', array(
				'min'	=> (float) $min,
				'max'	=> (float) $max,
				'step'	=> (float) $step,
				'float'	=> $float
			));
		else
			return $this->set_type('number', array(
				'min'	=> (int) $min,
				'max'	=> (int) $max,
				'step'	=> (int) $step,
				'float'	=> $float
			));

		return $this;
	}

	function set_textarea_input(){
		return $this->set_type('textarea');
	}

	function set_width($size){
		$this->size = (int) $size;

		return $this;
	}

	function set_input_prefix($string){
		$this->input_prefix = $string;

		return $this;
	}

	function set_callback($func){
		if( is_callable($func) )
			$this->callback($func);

		return $this;
	}

	// Show fields
	function render_field($name, $panel, $metabox) {
        global $post;

kickout('render_field_'.$name, $this);
        // Set up blank or default values for empty ones
        if ( !isset( $this->label ) ) $this->label = '';
        if ( !isset( $this->name) ) $this->name = $this->id;
        if ( !isset( $this->placeholder) ) $this->placeholder = '';
        if ( !isset( $this->desc ) ) $this->desc = '';
        if ( !isset( $this->std ) ) $this->std = '';
        if ( 'file' == $this->type && !isset( $this->allow ) ) $this->allow = array( 'url', 'attachment' );
        if ( 'file' == $this->type && !isset( $field['save_id'] ) )  $field['save_id']  = false;
        if ( 'multicheck' == $this->type ) $this->multiple = true;

//        $meta = get_post_meta( $post->ID, $this->id, 'multicheck' != $this->type /* If multicheck this can be multiple values */ );

        if ( isset($this->label) && $this->type != 'checkbox' ) {
            echo '<label for="', $this->id, '">', esc_html($this->label), '</label>';
        }

        switch ( $this->type ) {

            case 'text':
                echo '<input type="text" name="', $this->name, '" id="', $this->id, '" value="', '' !== $this->meta ? $this->meta : $this->std, '" />','<p class="wpec_metabox_description">', $this->desc, '</p>';
                break;

            case 'text_small':
                echo '<input class="wpec_text_small" type="text" name="', $this->name, '" id="', $this->id, '" value="', '' !== $this->meta ? $this->meta : $this->std, '" /><span class="wpec_metabox_description">', $this->desc, '</span>';
                break;

            case 'text_medium':
                echo '<input class="wpec_text_medium" type="text" name="', $this->name, '" id="', $this->id, '" value="', '' !== $this->meta ? $this->meta : $this->std, '" /><span class="wpec_metabox_description">', $this->desc, '</span>';
                break;

            case 'text_date':
                echo '<input class="wpec_text_small wpec_datepicker" type="text" name="', $this->name, '" id="', $this->id, '" value="', '' !== $this->meta ? $this->meta : $this->std, '" /><span class="wpec_metabox_description">', $this->desc, '</span>';
                break;

            case 'text_date_timestamp':
                echo '<input class="wpec_text_small wpec_datepicker" type="text" name="', $this->name, '" id="', $this->id, '" value="', '' !== $this->meta ? date( 'm\/d\/Y', $this->meta ) : $this->std, '" /><span class="wpec_metabox_description">', $this->desc, '</span>';
                break;

            case 'text_datetime_timestamp':
                echo '<input class="wpec_text_small wpec_datepicker" type="text" name="', $this->name, '[date]" id="', $this->id, '_date" value="', '' !== $this->meta ? date( 'm\/d\/Y', $this->meta ) : $this->std, '" />';
                echo '<input class="wpec_timepicker text_time" type="text" name="', $this->name, '[time]" id="', $this->id, '_time" value="', '' !== $this->meta ? date( 'h:i A', $this->meta ) : $this->std, '" /><span class="wpec_metabox_description" >', $this->desc, '</span>';
                break;

            case 'text_time':
                echo '<input class="wpec_timepicker text_time" type="text" name="', $this->name, '" id="', $this->id, '" value="', '' !== $this->meta ? $this->meta : $this->std, '" /><span class="wpec_metabox_description">', $this->desc, '</span>';
                break;

            case 'text_money':
                echo '<input class="wpec_text_money" type="text" name="', $this->name, '" id="', $this->id, '" value="', esc_attr($this->value), '" /><span class="wpec_metabox_description">', $this->desc, '</span>';
                break;

            case 'colorpicker':
                $this->meta = '' !== $this->meta ? $this->meta : $this->std;
                $hex_color = '(([a-fA-F0-9]){3}){1,2}$';
                if ( preg_match( '/^' . $hex_color . '/i', $this->meta ) ) // Value is just 123abc, so prepend #.
                    $this->meta = '#' . $this->meta;
                elseif ( ! preg_match( '/^#' . $hex_color . '/i', $this->meta ) ) // Value doesn't match #123abc, so sanitize to just #.
                    $this->meta = "#";
                echo '<input class="wpec_colorpicker wpec_text_small" type="text" name="', $this->name, '" id="', $this->id, '" value="', $this->meta, '" /><span class="wpec_metabox_description">', $this->desc, '</span>';
                break;

            case 'textarea':
                echo '<textarea name="', $this->name, '" id="', $this->id, '" cols="60" rows="10">', '' !== $this->meta ? $this->meta : $this->std, '</textarea>','<p class="wpec_metabox_description">', $this->desc, '</p>';
                break;

            case 'textarea_small':
                echo '<textarea name="', $this->name, '" id="', $this->id, '" cols="60" rows="4">', '' !== $this->meta ? $this->meta : $this->std, '</textarea>','<p class="wpec_metabox_description">', $this->desc, '</p>';
                break;

            case 'textarea_code':
                echo '<textarea name="', $this->name, '" id="', $this->id, '" cols="60" rows="10" class="wpec_textarea_code">', '' !== $this->meta ? $this->meta : $this->std, '</textarea>','<p class="wpec_metabox_description">', $this->desc, '</p>';
                break;

            case 'select':
                if( empty( $this->meta ) && !empty( $this->std ) ) $this->meta = $this->std;
                echo '<select name="', $this->name, '" id="', $this->id, '">';
                foreach ($this->options as $option) {
                    echo '<option value="', $option['value'], '"', $this->meta == $option['value'] ? ' selected="selected"' : '', '>', $option['name'], '</option>';
                }
                echo '</select>';
                echo '<p class="wpec_metabox_description">', $this->desc, '</p>';
                break;

            case 'radio_inline':
                if( empty( $this->meta ) && !empty( $this->std ) ) $this->meta = $this->std;
                echo '<div class="wpec_radio_inline">';
                $i = 1;
                foreach ($this->options as $option) {
                    echo '<div class="wpec_radio_inline_option"><input type="radio" name="', $this->name, '" id="', $this->id, $i, '" value="', $option['value'], '"', $this->meta == $option['value'] ? ' checked="checked"' : '', ' /><label for="', $this->id, $i, '">', $option['name'], '</label></div>';
                    $i++;
                }
                echo '</div>';
                echo '<p class="wpec_metabox_description">', $this->desc, '</p>';
                break;

            case 'radio':
                if( empty( $this->meta ) && !empty( $this->std ) ) $this->meta = $this->std;
                echo '<ul>';
                $i = 1;
                foreach ($this->options as $option) {
                    echo '<li><input type="radio" name="', $this->name, '" id="', $this->id, $i,'" value="', $option['value'], '"', $this->meta == $option['value'] ? ' checked="checked"' : '', ' /><label for="', $this->id, $i, '">', $option['name'].'</label></li>';
                    $i++;
                }
                echo '</ul>';
                echo '<p class="wpec_metabox_description">', $this->desc, '</p>';
                break;

            case 'checkbox':
                echo '<input type="hidden" name="', $this->name, '" value="0"/>'; // trick so that we have a returned value regardless
                echo '<input type="checkbox" name="', $this->name, '" id="', $this->id, '"', ' value="1"', $this->meta ? ' checked="checked"' : '', ' />';
                echo '<label for="', $this->id, '">', $this->label, '</label>';
                echo '<span class="wpec_metabox_description">', $this->desc, '</span>';
                break;

            case 'multicheck':
                echo '<ul>';
                $i = 1;
                foreach ( $this->options as $value => $name ) {
                    // Append `[]` to the name to get multiple values
                    // Use in_array() to check whether the current option should be checked
                    echo '<li><input type="checkbox" name="', $this->name, '[]" id="', $this->id, $i, '" value="', $value, '"', in_array( $value, $this->meta ) ? ' checked="checked"' : '', ' /><label for="', $this->id, $i, '">', $name, '</label></li>';
                    $i++;
                }
                echo '</ul>';
                echo '<span class="wpec_metabox_description">', $this->desc, '</span>';
                break;

            case 'title':
                echo '<h5 class="wpec_metabox_title">', $this->name, '</h5>';
                echo '<p class="wpec_metabox_description">', $this->desc, '</p>';
                break;

            case 'wysiwyg':
                wp_editor( $this->meta ? $this->meta : $this->std, $this->id, isset( $this->options ) ? $this->options : array() );
                echo '<p class="wpec_metabox_description">', $this->desc, '</p>';
                break;

            case 'taxonomy_select':
                echo '<select name="', $this->name, '" id="', $this->id, '">';
                $names= wp_get_object_terms( $post->ID, $this->taxonomy );
                $terms = get_terms( $this->taxonomy, 'hide_empty=0' );
                foreach ( $terms as $term ) {
                    if (!is_wp_error( $names ) && !empty( $names ) && !strcmp( $term->slug, $names[0]->slug ) ) {
                        echo '<option value="' . $term->slug . '" selected>' . $term->name . '</option>';
                    } else {
                        echo '<option value="' . $term->slug . '  ' , $this->meta == $term->slug ? $this->meta : ' ' ,'  ">' . $term->name . '</option>';
                    }
                }
                echo '</select>';
                echo '<p class="wpec_metabox_description">', $this->desc, '</p>';
                break;

            case 'taxonomy_radio':
                $names= wp_get_object_terms( $post->ID, $this->taxonomy );
                $terms = get_terms( $this->taxonomy, 'hide_empty=0' );
                echo '<ul>';
                foreach ( $terms as $term ) {
                    if ( !is_wp_error( $names ) && !empty( $names ) && !strcmp( $term->slug, $names[0]->slug ) ) {
                        echo '<li><input type="radio" name="', $this->name, '" value="'. $term->slug . '" checked>' . $term->name . '</li>';
                    } else {
                        echo '<li><input type="radio" name="', $this->name, '" value="' . $term->slug . '  ' , $this->meta == $term->slug ? $this->meta : ' ' ,'  ">' . $term->name .'</li>';
                    }
                }
                echo '</ul>';
                echo '<p class="wpec_metabox_description">', $this->desc, '</p>';
                break;

            case 'taxonomy_multicheck':
                echo '<ul>';
                $names = wp_get_object_terms( $post->ID, $this->taxonomy );
                $terms = get_terms( $this->taxonomy, 'hide_empty=0' );
                foreach ($terms as $term) {
                    echo '<li><input type="checkbox" name="', $this->name, '[]" id="', $this->id, '" value="', $term->name , '"';
                    foreach ($names as $name) {
                        if ( $term->slug == $name->slug ){ echo ' checked="checked" ';};
                    }
                    echo' /><label>', $term->name , '</label></li>';
                }
                echo '</ul>';
                echo '<span class="wpec_metabox_description">', $this->desc, '</span>';
            break;

            case 'file_list':
                echo '<input class="wpec_upload_file" type="text" size="36" name="', $this->name, '" value="" />';
                echo '<input class="wpec_upload_button button" type="button" value="Upload File" />';
                echo '<p class="wpec_metabox_description">', $this->desc, '</p>';
                    $args = array(
                            'post_type' => 'attachment',
                            'numberposts' => null,
                            'post_status' => null,
                            'post_parent' => $post->ID
                        );
                        $attachments = get_posts($args);
                        if ($attachments) {
                            echo '<ul class="attach_list">';
                            foreach ($attachments as $attachment) {
                                echo '<li>'.wp_get_attachment_link($attachment->ID, 'thumbnail', 0, 0, 'Download');
                                echo '<span>';
                                echo apply_filters('the_title', '&nbsp;'.$attachment->post_title);
                                echo '</span></li>';
                            }
                            echo '</ul>';
                        }
                    break;

            case 'file':
                $input_type_url = "hidden";
                if ( 'url' == $this->allow || ( is_array( $this->allow ) && in_array( 'url', $this->allow ) ) )
                    $input_type_url="text";
                echo '<input class="wpec_upload_file" type="' . $input_type_url . '" size="45" id="', $this->id, '" name="', $this->name, '" value="', $this->meta, '" />';
                echo '<input class="wpec_upload_button button" type="button" value="Upload File" />';
                echo '<input class="wpec_upload_file_id" type="hidden" id="', $this->id, '_id" name="', $this->id, '_id" value="', get_post_meta( $post->ID, $this->id . "_id",true), '" />';
                echo '<p class="wpec_metabox_description">', $this->desc, '</p>';
                echo '<div id="', $this->id, '_status" class="wpec_media_status">';
                    if ( $this->meta != '' ) {
                        $check_image = preg_match( '/(^.*\.jpg|jpeg|png|gif|ico*)/i', $this->meta );
                        if ( $check_image ) {
                            echo '<div class="img_status">';
                            echo '<img src="', $this->meta, '" alt="" />';
                            echo '<a href="#" class="wpec_remove_file_button" rel="', $this->id, '">Remove Image</a>';
                            echo '</div>';
                        } else {
                            $parts = explode( '/', $this->meta );
                            for( $i = 0; $i < count( $parts ); ++$i ) {
                                $title = $parts[$i];
                            }
                            echo 'File: <strong>', $title, '</strong>&nbsp;&nbsp;&nbsp; (<a href="', $this->meta, '" target="_blank" rel="external">Download</a> / <a href="#" class="wpec_remove_file_button" rel="', $this->id, '">Remove</a>)';
                        }
                    }
                echo '</div>';
            break;

            case 'oembed':
                echo '<input class="wpec_oembed" type="text" name="', $this->name, '" id="', $this->id, '" value="', '' !== $this->meta ? $this->meta : $this->std, '" />','<p class="wpec_metabox_description">', $this->desc, '</p>';
                echo '<p class="wpec-spinner spinner"></p>';
                echo '<div id="', $this->id, '_status" class="wpec_media_status ui-helper-clearfix embed_wrap">';
                    if ( $this->meta != '' ) {
                        $check_embed = $GLOBALS['wp_embed']->run_shortcode( '[embed]'. esc_url( $this->meta ) .'[/embed]' );
                        if ( $check_embed ) {
                            echo '<div class="embed_status">';
                            echo $check_embed;
                            echo '<a href="#" class="wpec_remove_file_button" rel="', $this->id, '">Remove Embed</a>';
                            echo '</div>';
                        } else {
                            echo 'URL is not a valid oEmbed URL.';
                        }
                    }
                echo '</div>';
                break;

            default:
                do_action('wpec_render_field_' . $this->type , $field, $this->meta, $panel, $metabox);
        }

	}

}
