<?php

class BindsupplierApp extends BackendApp
{

    var $_store_mod;

    function __construct ()
    {
        $this->AdminApp();
    }

    function AdminApp ()
    {
        parent::__construct();
        $this->_store_mod = & m('store');
    }

    function index ()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (! IS_POST) {
            /* 是否存在 */
            $store = $this->_store_mod->get_info($id);
            if (! $store) {
                $this->show_warning('store_empty');
                return;
            }
            $this->assign('store', $store);
            
            $conditions = empty($_GET['binded']) ? "ss.id is null" : "ss.id is not null";
            $owner_name = trim($_GET['owner_name']);
            if ($owner_name)
            {
                $filter = " AND (name LIKE '%{$owner_name}%' OR contacts LIKE '%{$owner_name}%') ";
            }
            $this->assign('filter', $filter);

            //更新排序
            if (isset($_GET['sort']) && isset($_GET['order']))
            {
                $sort  = strtolower(trim($_GET['sort']));
                $order = strtolower(trim($_GET['order']));
                if (!in_array($order,array('asc','desc')))
                {
                    $sort  = 'sort_order';
                    $order = '';
                }
            }
            else
            {
                $sort  = 'supplier_id';
                $order = 'desc';
            }
            
            $conditions .= $filter;
            $page = $this->_get_page();
            $limit = $page['limit'];
            $sql = "select s.* from ecm_supplier s left join (select * from ecm_store_supplier where store_id=$id) ss on s.id = ss.supplier_id where $conditions limit $limit";
            $suppliers = m('supplier')->getAll($sql);
            
            $this->assign('suppliers', $suppliers);
            /* 导入jQuery的表单验证插件 */
            $this->import_resource(
                    array(
                            'script' => 'jquery.plugins/jquery.validate.js,mlselection.js'
                    ));
            $this->assign('enabled_subdomain', ENABLED_SUBDOMAIN);
            $this->display('bind.index.html');
        }
    }
       
    /**
     * 绑定供应商
     */
    function bind(){
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        $sid = empty($_GET['sid']) ? 0 : intval($_GET['sid']);
        if (! IS_POST) {
            $data = array('store_id' => $id, 'supplier_id' => $sid);
            m('storesupplier')->add($data);
            //
            $ret_page = isset($_GET['ret_page']) ? intval($_GET['ret_page']) : 1;
            $this->show_message('绑定成功！',
                'back_list',    "index.php?app=bindsupplier&act=index&id=$id&page=$ret_page"
            );
        }
    }
    
    /**
     * 批量绑定
     */
    function batch_bind()
    {
        //店铺id
        $sid = isset($_GET['sid']) ? trim($_GET['sid']) : '';
        //供应商ids
        $id = isset($_GET['id']) ? trim($_GET['id']) : '';
        
        if (!$sid)
        {
            $this->show_warning('缺少店铺信息！');
            return;
        }
        if (!$id)
        {
            $this->show_warning('没选择供应商！');
            return;
        }
        
        $ids = explode(',', $id);
        foreach ($ids as $id)
        {
            $data = array('store_id' => $sid, 'supplier_id' => $id);
            m('storesupplier')->add($data);
        }
        $this->show_message('批量绑定成功！');
    }
    
    /**
     * 解绑供应商
     */
    function unbind(){
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        $sid = empty($_GET['sid']) ? 0 : intval($_GET['sid']);
        if (! IS_POST) {
            $data = "store_id=$id and supplier_id=$sid";
            m('storesupplier')->drop($data);
            //
            $ret_page = isset($_GET['ret_page']) ? intval($_GET['ret_page']) : 1;
            $this->show_message('解绑成功！',
                'back_list',    "index.php?app=bindsupplier&act=index&id=$id&binded=1&page=$ret_page"
            );
        }
    }
    
    /**
     * 批量解绑供应商
     */
    function batch_unbind()
    {
        //店铺id
        $sid = isset($_GET['sid']) ? trim($_GET['sid']) : '';
        //供应商ids
        $id = isset($_GET['id']) ? trim($_GET['id']) : '';
        
        if (!$sid)
        {
            $this->show_warning('缺少店铺信息！');
            return;
        }
        if (!$id)
        {
            $this->show_warning('没选择供应商！');
            return;
        }
        
        $data = "store_id=$sid and supplier_id in ($id)";
        m('storesupplier')->drop($data);
        
        $this->show_message('批量解绑成功！');
    }
    
}