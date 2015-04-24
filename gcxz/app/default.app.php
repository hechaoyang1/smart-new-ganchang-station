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
		//小站特色
		$xztc_pic = '/data/files/mall/settings/xztc.png';
		if(!file_exists(ROOT_PATH.$xztc_pic)){
		    $xztc_pic = '/themes/mall/default/styles/default/img/wa.png';
		}
		$this->assign('xztc_pic',$xztc_pic);

		$fine_list = $goods_mod->get_list(array(
            'conditions' => "closed = 0 AND if_show = 1 AND is_fine=1",
            'order'      => 'views desc,g.add_time desc',
            'limit'      => 15,
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
		
        $this->_assign_ad_data();
		
        $this->import_resource(array(
                'style' =>  'res:css/index.css',
        ));
		
        $this->display('index.html');
    }
    
    /**
     * 填充广告位数据
     */
    function _assign_ad_data()
    {
		$ad_mod = m('goodsad');
		$pos_value = $ad_mod->get_pos_value();
		//轮播的广告商品
		$ad_pos = $pos_value['index_center'];
		$ad_list = $ad_mod->get_list($ad_pos, 8);
		$this->assign('index_center_ad',$ad_list);
		//分会场
		$ad_pos = $pos_value['index_branch'];
	    $list = $ad_mod->get_list($ad_pos);
	    foreach ($list as $id=>$goods)
	    {
	        $sub_places[$goods['sort']] = $goods;
	    }
		$this->assign('sub_places',$sub_places);
		//底部广告
		$ad_pos = $pos_value['index_footer'];
		$ad_list = $ad_mod->get_list($ad_pos, 1);
		$this->assign('index_footer_ad',$ad_list);
		//楼层广告
        $ad_pos = $pos_value['first_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('first_floor_ad', $ad_list);

        $ad_pos = $pos_value['second_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('second_floor_ad', $ad_list);

        $ad_pos = $pos_value['third_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('third_floor_ad', $ad_list);

        $ad_pos = $pos_value['fourth_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('fourth_floor_ad', $ad_list);

        $ad_pos = $pos_value['fifth_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('fifth_floor_ad', $ad_list);

        $ad_pos = $pos_value['sixth_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('sixth_floor_ad', $ad_list);

        $ad_pos = $pos_value['seventh_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('seventh_floor_ad', $ad_list);

        $ad_pos = $pos_value['eighth_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('eighth_floor_ad', $ad_list);

        $ad_pos = $pos_value['ninth_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('ninth_floor_ad', $ad_list);

        $ad_pos = $pos_value['tenth_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('tenth_floor_ad', $ad_list);

        $ad_pos = $pos_value['eleventh_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('eleventh_floor_ad', $ad_list);

        $ad_pos = $pos_value['twelfth_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('twelfth_floor_ad', $ad_list);

        $ad_pos = $pos_value['thirteenth_floor'];
        $ad_list = $ad_mod->get_list($ad_pos);
        $this->assign('thirteenth_floor_ad', $ad_list);
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
    	$m=m("zhuanti");
		$this->assign('page', $m->get_page(ZT_FCG));
        $this->display('fcg.html');
    }
    
    function rspf()
    {
        $this->display('rspf.html');
    }
}

?>
