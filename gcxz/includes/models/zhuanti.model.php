<?php

/* 收货地址 address */
class ZhuantiModel extends BaseModel {
	var $table = 'zhuanti';
	var $prikey = 'id';
	var $_name = 'zhuanti';
	var $alias = 'z';
	var $code = array (
			'fcg' => '逢场购' 
	);
	function get_code_name($code) {
		$name=$this->code[$code];
		return $name?$name:false;
	}
	function get_codes(){
		return $this->code;
	}
}

?>