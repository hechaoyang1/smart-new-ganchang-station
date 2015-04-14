<?php
define ( 'MAX_LAYER', 6 );

/* 地区控制器 */
class TtcApp extends BackendApp {
	
    var $operat_types = array('top' => 2, 'ad' => 1);
    
    var $_region_model;
    var $_rg_model;
    

    function __construct()
    {
        $this->FcgApp();
    }
    function FcgApp()
    {
        parent::BackendApp();
        $this->_region_model = & m('region');
        $this->_rg_model =& m('regiongoods');
    }
    
	/**
	 * 配置地区商品
	 */
	function topGoods() {
        if (! IS_POST) {
            $type = $this->operat_types['top'];
            $this->_query_data($type);

    	    $this->assign('type', $type);
    	    $this->display ( 'ttc_goods.index.html' );
        }
    
	}
	
	function adGoods() {
        if (! IS_POST) {
            $type = $this->operat_types['ad'];
            $this->_query_data($type);

    	    $this->assign('type', $type);
    	    $this->display ( 'ttc_ad_goods.index.html' );
        }
    
	}
	
	
	function _query_data($type) {
	    /* 是否存在 */
	    $id = empty($_GET['rid']) ? 0 : intval($_GET['rid']);
	    $region = $this->_region_model->get(array('conditions' => "region_id = $id"));
	    if (! $region) {
	        $this->show_warning('地区不存在');
	        return;
	    }
	    $this->assign('region', $region);
	    
	    $conditions = empty($_GET['binded']) ? "rg.id is null" : "rg.id is not null and rg.type = $type";
	    $conditions .= " and g.region_id = $id";
	    $conditions .= $this->_get_query_conditions(array(
	            array(
	                    'field' => 'goods_name',
	                    'equal' => 'like',
	            ),
	            array(
	                    'field' => 'store_name',
	                    'equal' => 'like',
	            )
	    ));
	    
	    //更新排序
	    if (isset($_GET['sort']) && isset($_GET['order']))
	    {
	        $sort  = strtolower(trim($_GET['sort']));
	        $order = strtolower(trim($_GET['order']));
	        if (!in_array($order,array('asc','desc')))
	        {
	            $sort  = 'goods_id';
	            $order = 'desc';
	        }
	    }
	    else
	    {
	        $sort  = 'goods_id';
	        $order = 'desc';
	    }
	    
	    $page = $this->_get_page();
	    $limit = $page['limit'];
	    $sql = "select g.*,s.store_name from ecm_goods g left join ecm_store s on g.store_id = s.store_id left join (select * from ecm_region_goods where region_id=$id) rg on g.goods_id = rg.goods_id where $conditions limit $limit";
	    $goods_list = $this->_rg_model->getAll($sql);
	    $this->assign('goods_list', $goods_list);
	    
	    $sql = "select COUNT(*) as c from ecm_goods g left join ecm_store s on g.store_id = s.store_id left join (select * from ecm_region_goods where region_id=$id) rg on g.goods_id = rg.goods_id where $conditions";
	    $page['item_count'] = $this->_rg_model->getOne($sql);
	    $this->_format_page($page);
	    $this->assign('page_info', $page);
	    
	    // 第一级分类
	    $cate_mod =& bm('gcategory', array('_store_id' => 0));
	    $this->assign('gcategories', $cate_mod->get_options(0, true));
	    $this->import_resource(array('script' => 'mlselection.js,inline_edit.js'));
	}
	
	/**
	 * 绑定商品
	 */
	function bind()
	{
	    $rid = empty($_GET['rid']) ? 0 : intval($_GET['rid']);
	    $gid = empty($_GET['gid']) ? 0 : intval($_GET['gid']);
	    $type = empty($_GET['type']) ? 0 : intval($_GET['type']);
	    if (! IS_POST) {
	        $act = 'adGoods';
	        if($type == $this->operat_types['top'])
	        {
	            $act = 'topGoods';
	            //清除之前设置的置顶商品
	            $condition = "region_id = $rid and type = $type";
	            $this->_rg_model->drop($condition);
	        }
	        //添加
	        $data = array('region_id' => $rid, 'goods_id' => $gid, 'type' => $type);
	        $this->_rg_model->add($data);
	        //
	        $ret_page = isset($_GET['ret_page']) ? intval($_GET['ret_page']) : 1;
	        $this->show_message('绑定成功！',
	                'back_list',    "index.php?app=ttc&act=$act&rid=$rid&page=$ret_page"
	        );
	    }
	}
	
	/**
	 * 批量绑定
	 */
	function batch_bind()
	{
	    $rid = isset($_GET['rid']) ? trim($_GET['rid']) : '';
	    $type = empty($_GET['type']) ? 0 : intval($_GET['type']);
	    $id = isset($_GET['id']) ? trim($_GET['id']) : '';
	
	    if (!$rid)
	    {
	        $this->show_warning('缺少地区信息！');
	        return;
	    }
	    if (!$id)
	    {
	        $this->show_warning('没选择商品！');
	        return;
	    }
	
	    $ids = explode(',', $id);
	    foreach ($ids as $gid)
	    {
	        $data = array('region_id' => $rid, 'goods_id' => $gid, 'type' => $type);
	        $this->_rg_model->add($data);
	    }
	    $this->show_message('批量绑定成功！');
	}
	
	/**
	 * 解绑
	 */
	function unbind(){
	    $rid = empty($_GET['rid']) ? 0 : intval($_GET['rid']);
	    $gid = empty($_GET['gid']) ? 0 : intval($_GET['gid']);
	    $type = empty($_GET['type']) ? 0 : intval($_GET['type']);
	    if (! IS_POST) {
            $condition = "region_id = $rid and type = $type and goods_id = $gid";
	        $this->_rg_model->drop($condition);
	        //
	        $act = 'adGoods';
	        if($type == $this->operat_types['top'])
	        {
	            $act = 'topGoods';
	        }
	        $ret_page = isset($_GET['ret_page']) ? intval($_GET['ret_page']) : 1;
	        $this->show_message('解绑成功！',
	                'back_list',    "index.php?app=ttc&act=$act&rid=$rid&binded=1&page=$ret_page"
	        );
	    }
	}
	
	/**
	 * 批量解绑
	 */
	function batch_unbind()
	{
	    $rid = empty($_GET['rid']) ? 0 : intval($_GET['rid']);
	    $type = empty($_GET['type']) ? 0 : intval($_GET['type']);
	    $id = isset($_GET['id']) ? trim($_GET['id']) : '';
	
	    if (!$rid)
	    {
	        $this->show_warning('缺少地区信息！');
	        return;
	    }
	    if (!$id)
	    {
	        $this->show_warning('没选择商品！');
	        return;
	    }

	    $condition = "region_id = $rid and type = $type and goods_id in ($id)";
	    $this->_rg_model->drop($condition);
	
	    $this->show_message('批量解绑成功！');
	}
}

?>
