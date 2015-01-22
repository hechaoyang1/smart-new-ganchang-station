<?php

/* 店铺 store */
class SupplierModel extends BaseModel
{

    var $table = 'supplier';

    var $prikey = 'id';

    var $alias = 's';

    var $_name = 'supplier';

    var $_relation = array(
            // 一个店铺签约的供应商
            'has_one_relation' => array(
                    'model' => 'storesupplier',
                    'type' => BELONGS_TO,
                    'foreign_key' => 'id',
                    'reverse' => 'has_supplier'
            )
    );
}

?>
