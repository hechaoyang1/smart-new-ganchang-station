<?php

/* 商品数据模型 */
class FcgModel extends BaseModel
{
    var $table  = 'fcg';
    var $prikey = 'id';
    var $alias  = 'fcg';
    var $_name  = 'fcg';
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
                'order' => 'sort asc'
        );
        
        if(!empty($limit))
        {
            $params['limit'] = $limit;
        }

		return $this->find($params);
    }
}

?>