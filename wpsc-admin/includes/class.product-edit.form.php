<?php

class WPEC_MetaBox {

	private $panels = array();

	function __construct($box_name, $args){

		$this->box_name = $box_name;

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

		add_meta_box($box_name, $title, array($this, 'render_metabox'), 'wpsc-product', $this->context, $this->priority);
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
}