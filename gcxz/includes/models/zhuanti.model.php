<?php
define ( 'ZT_FCG', 'fcg' );
/* 收货地址 address */
class ZhuantiModel extends BaseModel {
	var $table = 'zhuanti';
	var $prikey = 'id';
	var $_name = 'zhuanti';
	var $alias = 'z';
	var $code = array (
			ZT_FCG => '逢场购' 
	);
	function get_code_name($code) {
		$name = $this->code [$code];
		return $name ? $name : false;
	}
	function get_codes() {
		return $this->code;
	}
	function get_page($code) {
		$page = $this->find ( array (
				'conditions' => "type_code='" . ZT_FCG . "'",
				'fields' => 'page',
				'limit' => 1 
		) );
		if(empty($page)){
			return false;
		}
		$page=current($page);
		return $page['page'];
	}
}

?>