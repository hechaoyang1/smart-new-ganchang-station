<?php

class DefaultApp extends MallbaseApp
{
    function index()
    {
        $this->assign('index', 1); // 标识当前页面是首页，用于设置导航状态
        $this->assign('icp_number', Conf::get('icp_number'));

      
		
		$recom_mod =& m('recommend');
		$img_goods_list = $recom_mod->get_recommended_goods($this->options['img_recom_id'], 3, true, $this->options['img_cate_id']);
		
		/*今日特惠*/
		$zx_goods_list = $recom_mod->get_recommended_goods(1, 1, true);
		$this->assign('zx_goods_list', $zx_goods_list);
		
		/*新品上架*/
		$xp_goods_list = $recom_mod->get_recommended_goods(2, 4, true);
		$this->assign('xp_goods_list', $xp_goods_list);
		
		
		//首页商品
		$gcategory_list=$this->_get_store_gcategory();
		foreach ($gcategory_list as $key => $gcategory)
        {
			$gcategory_list[$key]['ph_goods']=$this->_get_new_goods($gcategory['id'],5,1);
			$gcategory_list[$key]['jx_goods']=$this->_get_new_goods($gcategory['id'],4,2);
			foreach ($gcategory['children'] as $key1=> $data_z)
			{
				$gcategory_mod =& m('gcategory');
				$fine=$gcategory_mod->get(array(
					'conditions' => "cate_id =".$data_z['id'],
					'fields'        =>'is_fine',
				));
				$gcategory_list[$key]['children'][$key1]['if_fine']=$fine['is_fine'];
				$gcategory_list[$key]['children'][$key1]['zc_goods']=$this->_get_new_goods($data_z['id'],8,0);		
			}
			
		}
		$this->assign('gcategory_list',$gcategory_list);
		
        $this->_config_seo(array(
            'title' => Lang::get('mall_index') . ' - ' . Conf::get('site_title'),
        ));
        $this->assign('page_description', Conf::get('site_description'));
        $this->assign('page_keywords', Conf::get('site_keywords'));
		
        $this->display('index.html');
    }
	
	/* 取得最新商品 */
    function _get_new_goods($cate_id,$num,$order)
    {
		if($order==1){
		  $order="recommended desc,g.add_time desc";
		  $conditions .=" and is_red=1";
		}
		if($order==0){
		  $order="sales desc";	
		}
		
		$gcategory_mod  =& bm('gcategory');
        $cate_id_layer   = $gcategory_mod->get_layer($cate_id, true);
		$conditions .= " AND g.cate_id_{$cate_id_layer} = '" . $cate_id . "'";
		if($order==2){
		 $order="recommended desc,g.add_time desc";
		 $conditions .=" and is_fine=1";
		}
        $goods_mod =& m('goods');
        $goods_list = $goods_mod->get_list(array(
            'conditions' => "closed = 0 AND if_show = 1".$conditions,
            'order'      => $order,
            'limit'      => $num,
        ));
        foreach ($goods_list as $key => $goods)
        {
            empty($goods['default_image']) && $goods_list[$key]['default_image'] = Conf::get('default_goods_image');
        }

        return $goods_list;
    }

    
}

?>
