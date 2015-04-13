<?php
define ( 'MAX_LAYER', 6 );

/* 地区控制器 */
class TtcApp extends BackendApp {
	
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
            /* 是否存在 */
            $id = empty($_GET['rid']) ? 0 : intval($_GET['rid']);
            $store = $this->_region_model->get(array('conditions' => "region_id = $id"));
            if (! $store) {
                $this->show_warning('地区不存在');
                return;
            }
            $this->assign('store', $store);

            $conditions = empty($_GET['binded']) ? "rg.id is null" : "rg.id is not null";
            $conditions .= " and g.region_id = $id and rg.type = 2";
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

            $sql = "select COUNT(*) as c from ecm_goods g left join ecm_store s on g.store_id = s.store_id left join (select * from ecm_region_goods where fcg_id=$id) rg on g.goods_id = rg.goods_id where $conditions";
            $page['item_count'] = $this->_rg_model->getOne($sql);
            $this->_format_page($page);
            $this->assign('page_info', $page);
    
            // 第一级分类
            $cate_mod =& bm('gcategory', array('_store_id' => 0));
            $this->assign('gcategories', $cate_mod->get_options(0, true));
            $this->import_resource(array('script' => 'mlselection.js,inline_edit.js'));
    
    		$this->display ( 'ttc_goods.index.html' );
        }
    
	}
	
	function adGoods() {
        if (! IS_POST) {
            /* 是否存在 */
            $id = empty($_GET['rid']) ? 0 : intval($_GET['rid']);
            $store = $this->_region_model->get(array('conditions' => "region_id = $id"));
            if (! $store) {
                $this->show_warning('地区不存在');
                return;
            }
            $this->assign('store', $store);

            $conditions = empty($_GET['binded']) ? "rg.id is null" : "rg.id is not null";
            $conditions .= " and g.region_id = $id and type = 1";
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

            $sql = "select COUNT(*) as c from ecm_goods g left join ecm_store s on g.store_id = s.store_id left join (select * from ecm_region_goods where fcg_id=$id) rg on g.goods_id = rg.goods_id where $conditions";
            $page['item_count'] = $this->_rg_model->getOne($sql);
            $this->_format_page($page);
            $this->assign('page_info', $page);
    
            // 第一级分类
            $cate_mod =& bm('gcategory', array('_store_id' => 0));
            $this->assign('gcategories', $cate_mod->get_options(0, true));
            $this->import_resource(array('script' => 'mlselection.js,inline_edit.js'));
    
    		$this->display ( 'ttc_goods.index.html' );
        }
    
	}
	
}

?>
