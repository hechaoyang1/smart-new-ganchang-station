<?php

/* 地区 region */
class RegiondescriptionModel extends BaseModel {
	var $table = 'region_description';
	var $prikey = 'id';
	var $_name = 'region_des';
	
	function get_info($id) {
		$result = $this->db->getAll ( 'SELECT
								erd.id,re.region_id,re.region_name,erd.description,erd.default_image
							FROM
								ecm_region_description erd
							RIGHT JOIN ecm_region re ON erd.region_id = re.region_id
							WHERE
								re.region_id = '.$id );
		return $result [0];
	}
}

?>
