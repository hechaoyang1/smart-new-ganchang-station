<?php

/* 地区 region */
class RegiondescriptionModel extends BaseModel {
	var $table = 'region_description';
	var $prikey = 'id';
	var $_name = 'region_des';
	function get_info($id) {
		return $this->db->getAll ( 'SELECT
								erd.id,re.region_id,re.region_name,erd.description,erd.default_image,re.sort_order,erd.if_show
							FROM
								ecm_region_description erd
							RIGHT JOIN ecm_region re ON erd.region_id = re.region_id
							WHERE
								re.region_id = ' . $id );
	}
	function get_List($id) {
		return $this->db->getAll ( 'SELECT
								erd.id,re.region_id,re.region_name,erd.description,erd.default_image,re.sort_order,erd.if_show
							FROM
								ecm_region_description erd
							RIGHT JOIN ecm_region re ON erd.region_id = re.region_id
							WHERE
								re.parent_id = ' . $id );
	}
}

?>
