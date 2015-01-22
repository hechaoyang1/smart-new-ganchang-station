<?php

/* 商品数据模型 */
class FcggoodsModel extends BaseModel
{
    var $table  = 'fcg_goods';
    var $prikey = 'id';
    var $alias  = 'fcggd';
    var $_name  = 'fcg_goods';
    var $temp; // 临时变量

    function get_goods_ids($fcg_id)
    {
        $ids = array();        
        $list = $this->find(array('conditions' => "fcg_id=$fcg_id"));
        foreach ($list as $fg)
        {
            $ids[] = intval($fg['goods_id']);
        }
        
        return json_encode($ids);
    }
}

?>