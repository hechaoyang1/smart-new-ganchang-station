<?php

/* 地区 region */
class RegionbgModel extends BaseModel {
	var $table = 'region_bg_image';
	var $prikey = 'id';
	var $_name = 'rbi';
	function get_info($id) {
		return $this->db->getAll ( 'SELECT
										*
									FROM
										ecm_region
									WHERE
										region_id = ' . $id );
	}
	function get_List($id) {
		return $this->db->getAll ( 'SELECT
										rbi.*, re.region_name
									FROM
										ecm_region_bg_image rbi
									LEFT JOIN ecm_region re ON rbi.region_id = re.region_id
									WHERE
										re.region_id = ' . $id );
	}
}

?>
