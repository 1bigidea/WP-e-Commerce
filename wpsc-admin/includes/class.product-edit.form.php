<?php


class WPEC_MetaBox {

	function __construct(){
		$this->add_panel('default');
	}

	function add_panel($panel_name){
		$panels[$panel_name] = new WPEC_MetaBox_Panel();
	}
	function add_field($name){
		$panel['default'] = new WPEC_MetaBox_Field($name);
	}

	function add_options(){
	}

	function do_MetaBox(){}

}

class WPEC_MetaBox_Panel {

	function add_field(){
	}

	function do_panel(){
	}

}

function WPEC_MetaBox_FieldSet{

}

class WPEC_MetaBox_Field {
	function __construct($name=null){
	}

	function label($label=null){
		$this->label = $label;
	}

	function type($fieldtype='text'){
		$this->type = $fieldtype;
	}

	function checkbox($default=false){
		$this->type('checkbox');
		$this->default = $default;
	}

	function input_prefix($string){
		$this->input_prefix = $string;
	}
}