<?php

/* 店铺 store */
class CommonGoodsModel extends BaseModel
{

    var $table = 'common_goods';

    var $prikey = 'id';

    var $alias = 'cg';

    var $_name = 'common_goods';

    
    function find_goods($store_id)
    {
        //查询店铺合作的供应商
        $sql = "SELECT g.*, cate.cate_name, s.name AS supplier_name FROM ecm_common_goods g LEFT JOIN ecm_gcategory cate ON g.cate_id = cate.cate_id LEFT JOIN ecm_supplier s ON s.id = g.supplier_id WHERE g.supplier_id IN (SELECT supplier_id FROM ecm_store_supplier WHERE store_id=$store_id)";
        return $this->getAll($sql);
    }
}

?>
