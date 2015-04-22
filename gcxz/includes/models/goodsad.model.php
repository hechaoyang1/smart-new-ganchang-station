<?php

/* 商品数据模型 */
class GoodsadModel extends BaseModel
{
    var $table  = 'goods_ad';
    var $prikey = 'id';
    var $alias  = 'gad';
    var $_name  = 'goods_ad';
    var $temp; // 临时变量
    
    var $_pos_value = array(
            'index_center' => 1,
            'ttc' => 2,
            'index_branch' => 3,
            'site_top' => 4,
            'index_footer' => 5,
            'first_floor' => 6,
            'second_floor' => 7,
            'third_floor' => 8,
            'fourth_floor' => 9,
            'fifth_floor' => 10,
            'sixth_floor' => 11,
            'seventh_floor' => 12,
            'eighth_floor' => 13,
            'ninth_floor' => 14,
            'tenth_floor' => 15,
            'eleventh_floor' => 16,
            'twelfth_floor' => 17,
            'thirteenth_floor' => 18
    );
    
    var $_position = array(
            1 => '首页中心',
            2 => '土特产',
            3 => '首页分会场',
            4 => '全站头部广告位',
            5 => '首页底部广告位',
            6 => '一楼广告位',
            7 => '二楼广告位',
            8 => '三楼广告位',
            9 => '四楼广告位',
            10 => '五楼广告位',
            11 => '六楼广告位',
            12 => '七楼广告位',
            13 => '八楼广告位',
            14 => '九楼广告位',
            15 => '十楼广告位',
            16 => '十一楼广告位',
            17 => '十二楼广告位',
            18 => '十三楼广告位',
    );
    
    function get_position()
    {
        return $this->_position;
    }
    
    function get_pos_value()
    {
        return $this->_pos_value;
    }

    
    function get_list($position = 1, $limit = null, $conditions = '1=1')
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
        
        if(isset($limit))
        {
            $params['limit'] = $limit;
        }

		return $this->find($params);
    }
}

?>