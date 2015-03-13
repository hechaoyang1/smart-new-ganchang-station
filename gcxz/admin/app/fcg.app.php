<?php
/**
 *    商品管理控制器
 */
class FcgApp extends BackendApp
{
    var $_fcg_mod;

    function __construct()
    {
        $this->FcgApp();
    }
    function FcgApp()
    {
        parent::BackendApp();

        $this->_fcg_mod =& m('fcg');
    }

    function fixed()
    {
        $this->assign('mode', 'fixed');
        
        $goods_list = $this->_fcg_mod->find(array(
                'conditions' => 'type = 1',
                'order' => 'id asc'
        ));
        foreach ($goods_list as $key => $brand)
        {
            $brand['picture']&&$goods_list[$key]['picture'] = dirname(site_url()) . '/' . $brand['picture'];
        }
        $this->assign('fixed_list', $goods_list);
        
        /* 导入jQuery的表单验证插件 */
        $this->import_resource(array(
                'script' => 'jqtreetable.js,inline_edit.js',
                'style'  => 'res:style/jqtreetable.css'
        ));
        $this->display('fcg.fixed.html');
    }
    
    function dynamic()
    {
        $page = $this->_get_page();
        $goods_list = $this->_fcg_mod->find(array(
                'conditions' => 'type = 2',
                'order' => 'id asc'
        ));
        foreach ($goods_list as $key => $brand)
        {
            $brand['picture']&&$goods_list[$key]['picture'] = dirname(site_url()) . '/' . $brand['picture'];
        }
        $this->assign('fixed_list', $goods_list);
        
        /* 导入jQuery的表单验证插件 */
        $this->import_resource(array(
                'script' => 'jqtreetable.js,inline_edit.js',
                'style'  => 'res:style/jqtreetable.css'
        ));
        $this->_format_page($page);
        $this->assign('page_info', $page);
        $this->display('fcg.dynamic.html');
    }
    
    function index()
    {
        $conditions = $this->_get_query_conditions(array(array(
                'field' => 'name',
                'equal' => 'LIKE',
                'assoc' => 'AND',
                'name'  => 'name',
                'type'  => 'string',
            ),
            array(
                'field' => 'url',
                'equal' => 'LIKE',
                'assoc' => 'AND',
                'name' => 'url',
                'type' => 'string',
            ),
        ));;

        //更新排序
        if (isset($_GET['sort']) && isset($_GET['order']))
        {
            $sort  = strtolower(trim($_GET['sort']));
            $order = strtolower(trim($_GET['order']));
            if (!in_array($order,array('asc','desc')))
            {
             $sort  = 'id';
             $order = 'asc';
            }
        }
        else
        {
             $sort  = 'id';
             $order = 'asc';
        }

        $page = $this->_get_page();
        $goods_list = $this->_fcg_mod->find(array(
            'conditions' => "1 = 1" . $conditions,
            'count' => true,
            'order' => "$sort $order",
            'limit' => $page['limit'],
        ));
        foreach ($goods_list as $key => $brand)
        {
            $brand['path']&&$goods_list[$key]['path'] = dirname(site_url()) . '/' . $brand['path'];
        }
        $this->assign('goods_list', $goods_list);

        $page['item_count'] = $this->_fcg_mod->getCount();
        /* 导入jQuery的表单验证插件 */
        $this->import_resource(array(
                'script' => 'jqtreetable.js,inline_edit.js',
                'style'  => 'res:style/jqtreetable.css'
        ));
        $this->_format_page($page);
        $this->assign('filtered', $conditions? 1 : 0); //是否有查询条件
        $this->assign('page_info', $page);

        // 第一级分类
        $this->display('fcg.index.html');
    }
    
    function add()
    {
        if (!IS_POST)
        {
            /* 显示新增表单 */
            $brand = array(
                'sort' => 255,
            );
            $yes_or_no = array(
                1 => Lang::get('yes'),
                0 => Lang::get('no'),
            );
            $this->import_resource(array(
                'script' => 'jquery.plugins/jquery.validate.js'
            ));
            $this->assign('yes_or_no', $yes_or_no);
            $this->assign('brand', $brand);
            $this->display('fcg.form.html');
        }
        else
        {
            $data = array();
            $data['name']     = $_POST['name'];
            $data['description'] = $_POST['description'];
            
            if (!$brand_id = $this->_fcg_mod->add($data))  //获取brand_id
            {
                $this->show_warning($this->_fcg_mod->get_error());

                return;
            }

            /* 处理上传的图片 */
            $logo       =   $this->_upload_logo($brand_id);
            if ($logo === false)
            {
                return;
            }
            $logo && $this->_fcg_mod->edit($brand_id, array('picture' => $logo)); //将logo地址记下

            $this->show_message('添加成功',
                'back_list',    'index.php?app=fcg&amp;act=dynamic',
                '继续添加', 'index.php?app=fcg&amp;act=add'
            );
        }
    }
    
    function edit()
    {
        $brand_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
        if (!$brand_id)
        {
            $this->show_warning('no_such_brand');
            return;
        }
        if (!IS_POST)
        {
            $find_data     = $this->_fcg_mod->find($brand_id);
            if (empty($find_data))
            {
                $this->show_warning('no_such_brand');
    
                return;
            }
            $brand    =   current($find_data);
            if ($brand['picture'])
            {
                $brand['picture']  =   dirname(site_url()) . "/" . $brand['picture'];
            }
            /* 显示新增表单 */
            $yes_or_no = array(
                    1 => Lang::get('yes'),
                    0 => Lang::get('no'),
            );
            $this->import_resource(array(
                    'script' => 'jquery.plugins/jquery.validate.js'
            ));
            $this->assign('yes_or_no', $yes_or_no);
            $this->assign('brand', $brand);
            $this->display('fcg.form.html');
        }
        else
        {
            $data = array();
            $data['name']     = $_POST['name'];
            $data['description'] = $_POST['description'];
            $logo               =   $this->_upload_logo($brand_id);
            $logo && $data['picture'] = $logo;
            if ($logo === false)
            {
                return;
            }
            
            $rows=$this->_fcg_mod->edit($brand_id, $data);
            if ($this->_fcg_mod->has_error())
            {
                $this->show_warning($this->_fcg_mod->get_error());
    
                return;
            }
    
            $this->show_message('修改成功',
                    'back_list',        'index.php?app=fcg&amp;act=fixed',
                    '再次编辑',    'index.php?app=fcg&amp;act=edit&amp;id=' . $brand_id);
        }
    }
    
    function drop()
    {
        $brand_ids = isset($_GET['id']) ? trim($_GET['id']) : '';
        if (!$brand_ids)
        {
            $this->show_warning('no_such_brand');
    
            return;
        }
        $brand_ids=explode(',',$brand_ids);
        $this->_fcg_mod->drop($brand_ids);
        if ($this->_fcg_mod->has_error())    //删除
        {
            $this->show_warning($this->_fcg_mod->get_error());
    
            return;
        }
    
        $this->show_message('删除成功');
    }
    
    //异步修改数据
    function ajax_col()
    {
        $id     = empty($_GET['id']) ? 0 : intval($_GET['id']);
        $column = empty($_GET['column']) ? '' : trim($_GET['column']);
        $value  = isset($_GET['value']) ? trim($_GET['value']) : '';
        $data   = array();
    
        if (in_array($column ,array('name', 'url', 'sort')))
        {
            $data[$column] = $value;
            $this->_fcg_mod->edit($id, $data);
            if(!$this->_fcg_mod->has_error())
            {
                echo ecm_json_encode(true);
            }
        }
        else
        {
            return ;
        }
        return ;
    }

    /**
     *    处理上传标志
     *
     *    @author    Hyber
     *    @param     int $brand_id
     *    @return    string
     */
    function _upload_logo($brand_id)
    {
        $file = $_FILES['picture'];
        if ($file['error'] == UPLOAD_ERR_NO_FILE) // 没有文件被上传
        {
            return '';
        }
        import('uploader.lib');             //导入上传类
        $uploader = new Uploader();
        $uploader->allowed_type(IMAGE_FILE_TYPE); //限制文件类型
        $uploader->addFile($_FILES['picture']);//上传logo
        if (!$uploader->file_info())
        {
            $this->show_warning($uploader->get_error() , 'go_back', 'index.php?app=fcg&amp;act=edit&amp;id=' . $brand_id);
            return false;
        }
        /* 指定保存位置的根目录 */
        $uploader->root_dir(ROOT_PATH);
    
        /* 上传 */
        if ($file_path = $uploader->save('data/files/mall/fcg', $brand_id))   //保存到指定目录，并以指定文件名$brand_id存储
        {
            return $file_path;
        }
        else
        {
            return false;
        }
    }
    
    function bindGoods()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (! IS_POST) {
            /* 是否存在 */
            $store = $this->_fcg_mod->get(array('conditions' => "id = $id"));
            if (! $store) {
                $this->show_warning('专题产品未找到');
                return;
            }
            $this->assign('store', $store);

            $conditions = empty($_GET['binded']) ? "fg.id is null" : "fg.id is not null";
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
            $sql = "select g.*,s.store_name from ecm_goods g left join ecm_store s on g.store_id = s.store_id left join (select * from ecm_fcg_goods where fcg_id=$id) fg on g.goods_id = fg.goods_id where $conditions limit $limit";
            $goods_list = $this->_fcg_mod->getAll($sql);
            $this->assign('goods_list', $goods_list);

            $sql = "select COUNT(*) as c from ecm_goods g left join ecm_store s on g.store_id = s.store_id left join (select * from ecm_fcg_goods where fcg_id=$id) fg on g.goods_id = fg.goods_id where $conditions";
            $page['item_count'] = $this->_fcg_mod->getOne($sql);
            $this->_format_page($page);
            $this->assign('page_info', $page);
    
            // 第一级分类
            $cate_mod =& bm('gcategory', array('_store_id' => 0));
            $this->assign('gcategories', $cate_mod->get_options(0, true));
            $this->import_resource(array('script' => 'mlselection.js,inline_edit.js'));
    
            $this->display('fcg_goods.index.html');
        }
    }

    /**
     * 绑定商品
     */
    function bind()
    {
        $id = empty($_GET['fid']) ? 0 : intval($_GET['fid']);
        $sid = empty($_GET['gid']) ? 0 : intval($_GET['gid']);
        if (! IS_POST) {
            $data = array('fcg_id' => $id, 'goods_id' => $sid);
            m('fcggoods')->add($data);
            //
            $ret_page = isset($_GET['ret_page']) ? intval($_GET['ret_page']) : 1;
            $this->show_message('绑定成功！',
                'back_list',    "index.php?app=fcg&act=bindGoods&id=$id&page=$ret_page"
            );
        }
    }

    /**
     * 批量绑定
     */
    function batch_bind()
    {
        $sid = isset($_GET['fid']) ? trim($_GET['fid']) : '';
        $id = isset($_GET['id']) ? trim($_GET['id']) : '';
    
        if (!$sid)
        {
            $this->show_warning('缺少专题产品信息！');
            return;
        }
        if (!$id)
        {
            $this->show_warning('没选择商品！');
            return;
        }
    
        $ids = explode(',', $id);
        foreach ($ids as $id)
        {
            $data = array('fcg_id' => $sid, 'goods_id' => $id);
            m('fcggoods')->add($data);
        }
        $this->show_message('批量绑定成功！');
    }
    
    /**
     * 解绑供应商
     */
    function unbind(){
        $id = empty($_GET['fid']) ? 0 : intval($_GET['fid']);
        $sid = empty($_GET['gid']) ? 0 : intval($_GET['gid']);
        if (! IS_POST) {
            $data = "fcg_id=$id and goods_id=$sid";
            m('fcggoods')->drop($data);
            //
            $ret_page = isset($_GET['ret_page']) ? intval($_GET['ret_page']) : 1;
            $this->show_message('解绑成功！',
                    'back_list',    "index.php?app=fcg&act=bindGoods&id=$id&binded=1&page=$ret_page"
            );
        }
    }
    
    /**
     * 批量解绑供应商
     */
    function batch_unbind()
    {
        $sid = isset($_GET['fid']) ? trim($_GET['fid']) : '';
        $id = isset($_GET['id']) ? trim($_GET['id']) : '';
    
        if (!$sid)
        {
            $this->show_warning('缺少专题产品信息！');
            return;
        }
        if (!$id)
        {
            $this->show_warning('没选择商品！');
            return;
        }
    
        $data = "fcg_id=$sid and goods_id in ($id)";
        m('fcggoods')->drop($data);
    
        $this->show_message('批量解绑成功！');
    }
}

?>
