<?php

/* 商品数据模型 */
class GoodsadModel extends BaseModel
{
    var $table  = 'goods_ad';
    var $prikey = 'id';
    var $alias  = 'gad';
    var $_name  = 'goods_ad';
    var $temp; // 临时变量

    
    function get_list($position = 1, $limit, $conditions = '1=1')
    {
        if(empty($conditions))
        {
            $conditions = "position = $position";         
        }
        else 
        {
            $conditions .= ' AND position = '.$position;
        }
        
        $params = array(
                'conditions' => "$conditions",
                'order' => 'sort asc, ctime desc'
        );
        
        if(!empty($limit))
        {
            $params['limit'] = $limit;
        }

		return $this->find($params);
    }
}

?>