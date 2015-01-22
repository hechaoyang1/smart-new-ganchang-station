<?php

/* 店铺 store */
class StoresupplierModel extends BaseModel
{

    var $table = 'store_supplier';

    var $prikey = 'id';

    var $alias = 'ss';

    var $_name = 'store_supplier';

    var $_relation = array(
            // 一个会员拥有一个店铺，id相同
            'has_supplier' => array(
                    'model' => 'supplier', // 模型的名称
                    'type' => HAS_ONE, // 关系类型
                    'foreign_key' => 'supplier_id', // 外键名
                    'dependent' => true //依赖
            )// 依赖
    );
}

?>
