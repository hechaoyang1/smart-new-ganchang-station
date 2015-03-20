<?php
/**
 *    商品管理控制器
 */
class GoodsadApp extends BackendApp
{
    var $_goods_mod;
    
    var $_position = array(1 => '首页', 2 => '土特产', 3 => '首页分会场');

    function __construct()
    {
        $this->GoodsadApp();
    }
    function GoodsadApp()
    {
        parent::BackendApp();

        $this->_goods_mod =& m('goodsad');
    }

    /* 商品列表 */
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
            array(
                'field' => 'position',
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
        $goods_list = $this->_goods_mod->find(array(
            'conditions' => "1 = 1" . $conditions,
            'count' => true,
            'order' => "$sort $order",
            'limit' => $page['limit'],
        ));
        foreach ($goods_list as $key => $brand)
        {
            $brand['path']&&$goods_list[$key]['path'] = dirname(site_url()) . '/' . $brand['path'];
            $goods_list[$key]['position'] = $this->_position[$brand['position']];
        }
        $this->assign('goods_list', $goods_list);
        
        $this->assign('positions', $this->_position);

        $page['item_count'] = $this->_goods_mod->getCount();
        /* 导入jQuery的表单验证插件 */
        $this->import_resource(array(
                'script' => 'jqtreetable.js,inline_edit.js',
                'style'  => 'res:style/jqtreetable.css'
        ));
        $this->_format_page($page);
        $this->assign('filtered', $conditions? 1 : 0); //是否有查询条件
        $this->assign('page_info', $page);

        // 第一级分类
        $this->display('goodsad.index.html');
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
            $this->assign('positions', $this->_position);
            $this->display('goodsad.form.html');
        }
        else
        {
            $data = array();
            $data['name']     = $_POST['name'];
            $data['url'] = $_POST['url'];
            $data['sort']     = $_POST['sort'];
            $data['remark']    = $_POST['remark'];
            $data['position'] = $_POST['position'];;
            
            if (!$brand_id = $this->_goods_mod->add($data))  //获取brand_id
            {
                $this->show_warning($this->_goods_mod->get_error());

                return;
            }

            /* 处理上传的图片 */
            $logo       =   $this->_upload_logo($brand_id);
            if ($logo === false)
            {
                return;
            }
            $logo && $this->_goods_mod->edit($brand_id, array('path' => $logo)); //将logo地址记下

            $this->show_message('添加成功',
                'back_list',    'index.php?app=goodsad',
                '继续添加', 'index.php?app=goodsad&amp;act=add'
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
            $find_data     = $this->_goods_mod->find($brand_id);
            if (empty($find_data))
            {
                $this->show_warning('no_such_brand');
    
                return;
            }
            $brand    =   current($find_data);
            if ($brand['path'])
            {
                $brand['path']  =   dirname(site_url()) . "/" . $brand['path'];
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
            $this->assign('positions', $this->_position);
            $this->display('goodsad.form.html');
        }
        else
        {
            $data = array();
            $data['name']     = $_POST['name'];
            $data['url'] = $_POST['url'];
            $data['sort']     = $_POST['sort'];
            $data['remark']    = $_POST['remark'];
            $data['position'] = $_POST['position'];
            $logo               =   $this->_upload_logo($brand_id);
            $logo && $data['path'] = $logo;
            if ($logo === false)
            {
                return;
            }
            
            $rows=$this->_goods_mod->edit($brand_id, $data);
            if ($this->_goods_mod->has_error())
            {
                $this->show_warning($this->_goods_mod->get_error());
    
                return;
            }
    
            $this->show_message('修改成功',
                    'back_list',        'index.php?app=goodsad',
                    '再次编辑',    'index.php?app=goodsad&amp;act=edit&amp;id=' . $brand_id);
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
        $this->_goods_mod->drop($brand_ids);
        if ($this->_goods_mod->has_error())    //删除
        {
            $this->show_warning($this->_goods_mod->get_error());
    
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
            $this->_goods_mod->edit($id, $data);
            if(!$this->_goods_mod->has_error())
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
        $file = $_FILES['logo'];
        if ($file['error'] == UPLOAD_ERR_NO_FILE) // 没有文件被上传
        {
            return '';
        }
        import('uploader.lib');             //导入上传类
        $uploader = new Uploader();
        $uploader->allowed_type(IMAGE_FILE_TYPE); //限制文件类型
        $uploader->addFile($_FILES['logo']);//上传logo
        if (!$uploader->file_info())
        {
            $this->show_warning($uploader->get_error() , 'go_back', 'index.php?app=goodsad&amp;act=edit&amp;id=' . $brand_id);
            return false;
        }
        /* 指定保存位置的根目录 */
        $uploader->root_dir(ROOT_PATH);
    
        /* 上传 */
        if ($file_path = $uploader->save('data/files/mall/goodsad', $brand_id))   //保存到指定目录，并以指定文件名$brand_id存储
        {
            return $file_path;
        }
        else
        {
            return false;
        }
    }
    
}

?>
