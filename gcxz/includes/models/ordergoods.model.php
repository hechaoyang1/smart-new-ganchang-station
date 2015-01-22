<?php

/* 订单商品 ordergoods */
class OrdergoodsModel extends BaseModel
{
    var $table  = 'order_goods';
    var $prikey = 'rec_id';
    var $_name  = 'ordergoods';
    var $_relation = array(
        // 一个订单商品只能属于一个订单
        'belongs_to_order' => array(
            'model'         => 'order',
            'type'          => BELONGS_TO,
            'foreign_key'   => 'order_id',
            'reverse'       => 'has_ordergoods',
        ),
    );
    
    /**
     * 判断订单的商品是否是共享仓
     * @param unknown $order_id
     * @return true：是，false：否 
     */
    function isShareGoods($order_id)
    {
        //同一订单下的商品来源一样的， 所以即使有多个商品，也只取第一个商品的来源类型即可
        $order_goods = $this->get(array('conditions'=>"order_id = $order_id"));
        return $order_goods['source_type'] == 2 ? true : false;
    }
}

?>