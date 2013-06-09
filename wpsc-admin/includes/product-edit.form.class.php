<?php

class WPEC_MetaBox {

	private $panels = array();

	function __construct($post_type, $box_name, $args){

		$this->box_name = $box_name;
		$this->post_type = $post_type;

		foreach($args as $arg => $arg_value){
			$meth = 'set_'.$arg;
			if( is_callable ( array($this, $meth), false ) ){
				$this->{$meth}($arg_value);
			}
		}

		$title = $this->metabox_title;
		$metabar_info = apply_filters('metabar_info_'.$this->box_name, '');
		if( !empty($metabar_info) )
			$title .= '<span class="metabar_info">'.$metabar_info.'</span>';

		add_meta_box($box_name, $title, array($this, 'render_metabox'), $this->post_type, $this->context, $this->priority);
		add_filter("postbox_classes_{$pagename}_{$box_name}", array($this, 'add_postbox_classes') );

		$this->add_panel('default');
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
			add_filter('metabar_info_'.$this->box_name, $callback);
	}

	function add_panel($panel_name){
		$this->panels[$panel_name] = new WPEC_MetaBox_Panel();

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
kickout('WPSC_MetaBox', $this);
		echo 'hello World';
	}

	function add_postbox_classes($classes=array()){
		return $classes;
	}
}

class WPEC_MetaBox_Panel {

	function add_field($field_name){
		$the_field = new WPEC_MetaBox_Field();
		$this->fields[$field_name] = $the_field;

		return $the_field;
	}

	function add_fieldset($field_name){
		$the_fieldset = new WPEC_MetaBox_FieldSet();
		$this->fields[$field_name] = $the_fieldset;

		return $the_fieldset;
	}

	function set_callback($func){
		if( is_callable($func) )
			$this->callback = $func;

		return $this;
	}

	function do_panel(){
	}

}

class WPEC_MetaBox_FieldSet{

	function __construct($args=array()){
		$this->args = $args;
	}

	function set_callback($func){
		if( is_callable($func) )
			$this->callback = $func;

		return $this;
	}

	function add_field($name){
		$this->fields[$name] = new WPEC_MetaBox_Field();

		return $this->fields[$name];
	}

}

class WPEC_MetaBox_Field {
	function __construct(){
	}

	function set_label($label=null){
		$this->label = $label;

		return $this;
	}

	function set_type($fieldtype='text', $args=array()){
		$this->set_type = array($fieldtype, $args);

		return $this;
	}

	function set_checkbox($default=false){
		return $this->set_type('checkbox', array('default' => $default));

		return $this;
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
		$this->set_type('textarea');

		return $this;
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
	function render_field() {
		global $post;

		// Use nonce for verification
		echo '<input type="hidden" name="wp_meta_box_nonce" value="', wp_create_nonce( basename(__FILE__) ), '" />';
		echo '<table class="form-table wpec_metabox">';

		foreach ( $this->_meta_box['fields'] as $field ) {
			// Set up blank or default values for empty ones
			if ( !isset( $field['name'] ) ) $field['name'] = '';
			if ( !isset( $field['desc'] ) ) $field['desc'] = '';
			if ( !isset( $field['std'] ) ) $field['std'] = '';
			if ( 'file' == $field['type'] && !isset( $field['allow'] ) ) $field['allow'] = array( 'url', 'attachment' );
			if ( 'file' == $field['type'] && !isset( $field['save_id'] ) )  $field['save_id']  = false;
			if ( 'multicheck' == $field['type'] ) $field['multiple'] = true;

			$meta = get_post_meta( $post->ID, $field['id'], 'multicheck' != $field['type'] /* If multicheck this can be multiple values */ );

			echo '<tr>';

			if ( $field['type'] == "title" ) {
				echo '<td colspan="2">';
			} else {
				if( $this->_meta_box['show_names'] == true ) {
					echo '<th style="width:18%"><label for="', $field['id'], '">', $field['name'], '</label></th>';
				}
				echo '<td>';
			}

			switch ( $field['type'] ) {

				case 'text':
					echo '<input type="text" name="', $field['id'], '" id="', $field['id'], '" value="', '' !== $meta ? $meta : $field['std'], '" />','<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					break;
				case 'text_small':
					echo '<input class="wpec_text_small" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', '' !== $meta ? $meta : $field['std'], '" /><span class="wpsc_metabox_description">', $field['desc'], '</span>';
					break;
				case 'text_medium':
					echo '<input class="wpec_text_medium" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', '' !== $meta ? $meta : $field['std'], '" /><span class="wpsc_metabox_description">', $field['desc'], '</span>';
					break;
				case 'text_date':
					echo '<input class="wpec_text_small wpec_datepicker" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', '' !== $meta ? $meta : $field['std'], '" /><span class="wpsc_metabox_description">', $field['desc'], '</span>';
					break;
				case 'text_date_timestamp':
					echo '<input class="wpec_text_small wpec_datepicker" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', '' !== $meta ? date( 'm\/d\/Y', $meta ) : $field['std'], '" /><span class="wpsc_metabox_description">', $field['desc'], '</span>';
					break;

				case 'text_datetime_timestamp':
					echo '<input class="wpec_text_small wpec_datepicker" type="text" name="', $field['id'], '[date]" id="', $field['id'], '_date" value="', '' !== $meta ? date( 'm\/d\/Y', $meta ) : $field['std'], '" />';
					echo '<input class="wpec_timepicker text_time" type="text" name="', $field['id'], '[time]" id="', $field['id'], '_time" value="', '' !== $meta ? date( 'h:i A', $meta ) : $field['std'], '" /><span class="wpsc_metabox_description" >', $field['desc'], '</span>';
					break;
				case 'text_time':
					echo '<input class="wpec_timepicker text_time" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', '' !== $meta ? $meta : $field['std'], '" /><span class="wpsc_metabox_description">', $field['desc'], '</span>';
					break;
				case 'text_money':
					echo '$ <input class="wpec_text_money" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', '' !== $meta ? $meta : $field['std'], '" /><span class="wpsc_metabox_description">', $field['desc'], '</span>';
					break;
				case 'colorpicker':
					$meta = '' !== $meta ? $meta : $field['std'];
					$hex_color = '(([a-fA-F0-9]){3}){1,2}$';
					if ( preg_match( '/^' . $hex_color . '/i', $meta ) ) // Value is just 123abc, so prepend #.
						$meta = '#' . $meta;
					elseif ( ! preg_match( '/^#' . $hex_color . '/i', $meta ) ) // Value doesn't match #123abc, so sanitize to just #.
						$meta = "#";
					echo '<input class="wpec_colorpicker wpec_text_small" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', $meta, '" /><span class="wpsc_metabox_description">', $field['desc'], '</span>';
					break;
				case 'textarea':
					echo '<textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="10">', '' !== $meta ? $meta : $field['std'], '</textarea>','<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					break;
				case 'textarea_small':
					echo '<textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="4">', '' !== $meta ? $meta : $field['std'], '</textarea>','<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					break;
				case 'textarea_code':
					echo '<textarea name="', $field['id'], '" id="', $field['id'], '" cols="60" rows="10" class="wpec_textarea_code">', '' !== $meta ? $meta : $field['std'], '</textarea>','<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					break;
				case 'select':
					if( empty( $meta ) && !empty( $field['std'] ) ) $meta = $field['std'];
					echo '<select name="', $field['id'], '" id="', $field['id'], '">';
					foreach ($field['options'] as $option) {
						echo '<option value="', $option['value'], '"', $meta == $option['value'] ? ' selected="selected"' : '', '>', $option['name'], '</option>';
					}
					echo '</select>';
					echo '<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					break;
				case 'radio_inline':
					if( empty( $meta ) && !empty( $field['std'] ) ) $meta = $field['std'];
					echo '<div class="wpec_radio_inline">';
					$i = 1;
					foreach ($field['options'] as $option) {
						echo '<div class="wpec_radio_inline_option"><input type="radio" name="', $field['id'], '" id="', $field['id'], $i, '" value="', $option['value'], '"', $meta == $option['value'] ? ' checked="checked"' : '', ' /><label for="', $field['id'], $i, '">', $option['name'], '</label></div>';
						$i++;
					}
					echo '</div>';
					echo '<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					break;
				case 'radio':
					if( empty( $meta ) && !empty( $field['std'] ) ) $meta = $field['std'];
					echo '<ul>';
					$i = 1;
					foreach ($field['options'] as $option) {
						echo '<li><input type="radio" name="', $field['id'], '" id="', $field['id'], $i,'" value="', $option['value'], '"', $meta == $option['value'] ? ' checked="checked"' : '', ' /><label for="', $field['id'], $i, '">', $option['name'].'</label></li>';
						$i++;
					}
					echo '</ul>';
					echo '<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					break;
				case 'checkbox':
					echo '<input type="checkbox" name="', $field['id'], '" id="', $field['id'], '"', $meta ? ' checked="checked"' : '', ' />';
					echo '<span class="wpsc_metabox_description">', $field['desc'], '</span>';
					break;
				case 'multicheck':
					echo '<ul>';
					$i = 1;
					foreach ( $field['options'] as $value => $name ) {
						// Append `[]` to the name to get multiple values
						// Use in_array() to check whether the current option should be checked
						echo '<li><input type="checkbox" name="', $field['id'], '[]" id="', $field['id'], $i, '" value="', $value, '"', in_array( $value, $meta ) ? ' checked="checked"' : '', ' /><label for="', $field['id'], $i, '">', $name, '</label></li>';
						$i++;
					}
					echo '</ul>';
					echo '<span class="wpsc_metabox_description">', $field['desc'], '</span>';
					break;
				case 'title':
					echo '<h5 class="wpec_metabox_title">', $field['name'], '</h5>';
					echo '<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					break;
				case 'wysiwyg':
					wp_editor( $meta ? $meta : $field['std'], $field['id'], isset( $field['options'] ) ? $field['options'] : array() );
			        echo '<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					break;
				case 'taxonomy_select':
					echo '<select name="', $field['id'], '" id="', $field['id'], '">';
					$names= wp_get_object_terms( $post->ID, $field['taxonomy'] );
					$terms = get_terms( $field['taxonomy'], 'hide_empty=0' );
					foreach ( $terms as $term ) {
						if (!is_wp_error( $names ) && !empty( $names ) && !strcmp( $term->slug, $names[0]->slug ) ) {
							echo '<option value="' . $term->slug . '" selected>' . $term->name . '</option>';
						} else {
							echo '<option value="' . $term->slug . '  ' , $meta == $term->slug ? $meta : ' ' ,'  ">' . $term->name . '</option>';
						}
					}
					echo '</select>';
					echo '<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					break;
				case 'taxonomy_radio':
					$names= wp_get_object_terms( $post->ID, $field['taxonomy'] );
					$terms = get_terms( $field['taxonomy'], 'hide_empty=0' );
					echo '<ul>';
					foreach ( $terms as $term ) {
						if ( !is_wp_error( $names ) && !empty( $names ) && !strcmp( $term->slug, $names[0]->slug ) ) {
							echo '<li><input type="radio" name="', $field['id'], '" value="'. $term->slug . '" checked>' . $term->name . '</li>';
						} else {
							echo '<li><input type="radio" name="', $field['id'], '" value="' . $term->slug . '  ' , $meta == $term->slug ? $meta : ' ' ,'  ">' . $term->name .'</li>';
						}
					}
					echo '</ul>';
					echo '<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					break;
				case 'taxonomy_multicheck':
					echo '<ul>';
					$names = wp_get_object_terms( $post->ID, $field['taxonomy'] );
					$terms = get_terms( $field['taxonomy'], 'hide_empty=0' );
					foreach ($terms as $term) {
						echo '<li><input type="checkbox" name="', $field['id'], '[]" id="', $field['id'], '" value="', $term->name , '"';
						foreach ($names as $name) {
							if ( $term->slug == $name->slug ){ echo ' checked="checked" ';};
						}
						echo' /><label>', $term->name , '</label></li>';
					}
					echo '</ul>';
					echo '<span class="wpsc_metabox_description">', $field['desc'], '</span>';
				break;
				case 'file_list':
					echo '<input class="wpec_upload_file" type="text" size="36" name="', $field['id'], '" value="" />';
					echo '<input class="wpec_upload_button button" type="button" value="Upload File" />';
					echo '<p class="wpsc_metabox_description">', $field['desc'], '</p>';
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
					if ( 'url' == $field['allow'] || ( is_array( $field['allow'] ) && in_array( 'url', $field['allow'] ) ) )
						$input_type_url="text";
					echo '<input class="wpec_upload_file" type="' . $input_type_url . '" size="45" id="', $field['id'], '" name="', $field['id'], '" value="', $meta, '" />';
					echo '<input class="wpec_upload_button button" type="button" value="Upload File" />';
					echo '<input class="wpec_upload_file_id" type="hidden" id="', $field['id'], '_id" name="', $field['id'], '_id" value="', get_post_meta( $post->ID, $field['id'] . "_id",true), '" />';
					echo '<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					echo '<div id="', $field['id'], '_status" class="wpec_media_status">';
						if ( $meta != '' ) {
							$check_image = preg_match( '/(^.*\.jpg|jpeg|png|gif|ico*)/i', $meta );
							if ( $check_image ) {
								echo '<div class="img_status">';
								echo '<img src="', $meta, '" alt="" />';
								echo '<a href="#" class="wpec_remove_file_button" rel="', $field['id'], '">Remove Image</a>';
								echo '</div>';
							} else {
								$parts = explode( '/', $meta );
								for( $i = 0; $i < count( $parts ); ++$i ) {
									$title = $parts[$i];
								}
								echo 'File: <strong>', $title, '</strong>&nbsp;&nbsp;&nbsp; (<a href="', $meta, '" target="_blank" rel="external">Download</a> / <a href="#" class="wpec_remove_file_button" rel="', $field['id'], '">Remove</a>)';
							}
						}
					echo '</div>';
				break;
				case 'oembed':
					echo '<input class="wpec_oembed" type="text" name="', $field['id'], '" id="', $field['id'], '" value="', '' !== $meta ? $meta : $field['std'], '" />','<p class="wpsc_metabox_description">', $field['desc'], '</p>';
					echo '<p class="wpec-spinner spinner"></p>';
					echo '<div id="', $field['id'], '_status" class="wpec_media_status ui-helper-clearfix embed_wrap">';
						if ( $meta != '' ) {
							$check_embed = $GLOBALS['wp_embed']->run_shortcode( '[embed]'. esc_url( $meta ) .'[/embed]' );
							if ( $check_embed ) {
								echo '<div class="embed_status">';
								echo $check_embed;
								echo '<a href="#" class="wpec_remove_file_button" rel="', $field['id'], '">Remove Embed</a>';
								echo '</div>';
							} else {
								echo 'URL is not a valid oEmbed URL.';
							}
						}
					echo '</div>';
					break;

				default:
					do_action('wpec_render_field_' . $field['type'] , $field, $meta);
			}

			echo '</td>','</tr>';
		}
		echo '</table>';
	}

}