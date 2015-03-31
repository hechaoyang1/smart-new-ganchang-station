<?php

class DefaultApp extends MallbaseApp
{
    function index()
    {
        $this->assign('index', 1); // 标识当前页面是首页，用于设置导航状态
        $this->assign('icp_number', Conf::get('icp_number'));

// 		$recom_mod =& m('recommend');
// 		$img_goods_list = $recom_mod->get_recommended_goods($this->options['img_recom_id'], 3, true, $this->options['img_cate_id']);
		
// 		/*今日特惠*/
// 		$zx_goods_list = $recom_mod->get_recommended_goods(1, 1, true);
// 		$this->assign('zx_goods_list', $zx_goods_list);
		
// 		/*新品上架*/
// 		$xp_goods_list = $recom_mod->get_recommended_goods(2, 4, true);
// 		$this->assign('xp_goods_list', $xp_goods_list);
		
		
		//首页商品
// 		$gcategory_list=$this->_get_store_gcategory();
// 		foreach ($gcategory_list as $key => $gcategory)
//         {
// 			$gcategory_list[$key]['ph_goods']=$this->_get_new_goods($gcategory['id'],5,1);
// 			$gcategory_list[$key]['jx_goods']=$this->_get_new_goods($gcategory['id'],4,2);
// 			foreach ($gcategory['children'] as $key1=> $data_z)
// 			{
// 				$gcategory_mod =& m('gcategory');
// 				$fine=$gcategory_mod->get(array(
// 					'conditions' => "cate_id =".$data_z['id'],
// 					'fields'        =>'is_fine',
// 				));
// 				$gcategory_list[$key]['children'][$key1]['if_fine']=$fine['is_fine'];
// 				$gcategory_list[$key]['children'][$key1]['zc_goods']=$this->_get_new_goods($data_z['id'],8,0);		
// 			}
			
// 		}
// 		$this->assign('gcategory_list',$gcategory_list);
		
        $this->_config_seo(array(
            'title' => Lang::get('mall_index') . ' - ' . Conf::get('site_title'),
        ));
        $this->assign('page_description', Conf::get('site_description'));
        $this->assign('page_keywords', Conf::get('site_keywords'));
        
        
        //incito start
		$goods_mod =& m('goods');
		$gcategory_mod =& bm('gcategory');
        // 商品分类
// 		$gcategory_list=$this->_get_site_gcategory();
// 		$this->assign('gcategory_list',$gcategory_list);
		//轮播的广告商品
		$ad_goods_list = m('goodsad')->get_list(1, 8);
		$this->assign('ad_goods_list',$ad_goods_list);
		//小站特色
		$fine_list = $goods_mod->get_list(array(
            'conditions' => "closed = 0 AND if_show = 1 AND is_fine=1",
            'order'      => 'views desc,g.add_time desc',
            'limit'      => 12,
        ));
		$this->assign('fine_list',$fine_list);
		//楼层Q
		$top_category = $gcategory_mod->get_top_category();
		foreach ($top_category as $floor=>$category)
		{
	        $cate_id = $category['cate_id'];
		    if(!empty($cate_id))
		    {
		        $goods_list = $this->_get_new_goods($cate_id, 8, 1);
		        $top_cate_goods[$floor] = $goods_list;
		        $top_cate_children[$floor] = $gcategory_mod->get_children($cate_id, true);
		    }
		}
		$this->assign('top_category',$top_category);
		$this->assign('top_cate_goods',$top_cate_goods);
		$this->assign('top_cate_children',$top_cate_children);
		
		//分会场
	    $list = m('goodsad')->get_list(3, 13);
	    foreach ($list as $id=>$goods)
	    {
	        $sub_places[$goods['sort']] = $goods;
	    }
		$this->assign('sub_places',$sub_places);
		
        //赋值购物车数量
        $this->assign('cart_num', $this->get_cart_num());
		
// 		//新闻动态
// 		$newsList = m('cmscontent')->getNewsList(9);
// 		$this->assign('newsList', $newsList);
		
        $this->display('index.html');
    }
	
	/* 取得最新商品 */
    function _get_new_goods($cate_id,$num,$order)
    {
		if($order==1){
		  $order="views desc,g.add_time desc";
		  $conditions .=" and is_red=1";
		}
		else if($order==2){
		 $order="views desc,g.add_time desc";
		 $conditions .=" and is_fine=1";
		}
		else if($order==0){
		  $order="sales desc";	
		}
		
		$gcategory_mod  =& bm('gcategory');
        $cate_id_layer   = $gcategory_mod->get_layer($cate_id, true);
		$conditions .= " AND g.cate_id_{$cate_id_layer} = '" . $cate_id . "'";
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

    function zxzx()
    {
		$this->assign('tab', 'zxzx');
        // 商品分类
		$gcategory_list=$this->_get_site_gcategory();
		$this->assign('gcategory_list',$gcategory_list);
		
        $this->display('zxzx.html');
    }

    function bfzx()
    {
		$this->assign('tab', 'bfzx');
        // 商品分类
		$gcategory_list=$this->_get_site_gcategory();
		$this->assign('gcategory_list',$gcategory_list);
		
        $this->display('bfzx.html');
    }

    function fcg()
    {
		$this->assign('tab', 'fcg');
        // 商品分类
		$this->assign('top_category', m('gcategory')->get_top_category());
		
		$_fcg_mod = m('fcg');
		$_fcg_goods_mod = m('fcggoods');
		//固定区域
		$fixed_list = $_fcg_mod->find(array(
		        'conditions' => 'type = 1',
		        'order' => 'id asc'
		));
		foreach ($fixed_list as $key => & $fcg)
		{
		    $fcg['goods_ids'] = $_fcg_goods_mod->get_goods_ids($fcg['id']);
		    $fcg['ctime'] = date('Y-m-d', strtotime($fcg['ctime']));
		}
		$this->assign('fixed_list', $fixed_list);
		//动态
		$dynamic_list = $_fcg_mod->find(array(
		        'conditions' => 'type = 2',
		        'order' => 'id asc'
		));
		foreach ($dynamic_list as $key => & $fcg)
		{
		    $fcg['goods_ids'] = $_fcg_goods_mod->get_goods_ids($fcg['id']);
		    $fcg['ctime'] = date('Y-m-d', strtotime($fcg['ctime']));
		}
		$this->assign('dynamic_list', $dynamic_list);
		
        $this->display('fcg.html');
    }
}

?>
