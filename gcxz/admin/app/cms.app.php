<?php
/**
 * CMS 公告系统
 * @author admin
 *
 */
class CmsApp extends BackendApp {
    private $status_apply=0;
	function __construct()
    {
        $this->CmsApp();
    }

    function CmsApp()
    {
        parent::__construct();
    }

	/**
	 * index
	 * 公告列表
	 *
	 * @access public
	 * @return void
	 */
	public function index() {
	    $m=m('cmscontent');
	    $page = $this->_get_page();
	    $sql='select cc.id,cc.title,cc.create_time,cc.uid,cc.gid,cc.is_top,cc.sort,cl.title as group_name from ecm_cms_content cc left join ecm_cms_left cl on cc.gid=cl.id order by cc.create_time desc limit '.$page['limit'];
	    $contents =$m->db->getAll($sql);
	    foreach ($contents as &$v){
	        $v['is_top']=$v['is_top']==1?'是 &nbsp;&nbsp;&nbsp;&nbsp;排序:&nbsp;'.$v['sort']:'否';
	    }
	    $page['item_count'] = $m->db->getOne('select count(1) from ecm_cms_content');
	    $this->_format_page($page);
	    $this->assign('page_info', $page);
		$this->assign ( "contents", $contents );
		$this->display ('cms.content.index.html');
	}
	/**
	 * 增加/修改/展示详细页面
	 */
	public function addcontent() {
	    //保存
	    if(IS_POST){
	        $id=isset($_POST['id']) ? trim($_POST['id']) : '';
    		$map ['is_top'] = intval($_POST ['is_top']);
    		$map ['sort'] = intval($_POST ['sort']);
    		$map ['title'] =  $_POST ['title'];
    		$map ['content'] =  $_POST ['content'];
    		$map ['uid'] = $this->visitor->user_id;
    		if(!$id){
    		    $map ['create_time'] = time ();
    		}
    		$map ['gid'] = $_POST ['gid'];
    		if($id){
    		    $res = m('cmscontent')->edit ($id, $map);
    		}else{
    		    $res = m('cmscontent')->add ( $map );
    		}
			$this->show_message($id?'update_content_success':'add_content_success',
            'back_list',        'index.php?app=cms',
            'edit_again',    'index.php?app=cms' . '&amp;act=addcontent&amp;id=' . ($id?$id:$res));
	    }else{
	        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
	        if($id){
	            $m=m('cmscontent');
	            $this->assign('content',$m->get_info($id));
	        }
            //栏目
            $groups=m('cmsgroup')->db->getAll('select id,title from ecm_cms_left');
            $this->assign('groups',$groups);
	        /* 导入jQuery的表单验证插件 */
	        $this->import_resource(array(
	                'script' => 'jquery.plugins/jquery.validate.js,ueditor/ueditor.config.js,ueditor/ueditor.all.min.js'
	        ));
	        $this->display('cms.content.form.html');
	    }
	}
	/**
	 * 删除公告
	 */
	public function delete() {
	    $id = isset($_GET['id']) ? trim($_GET['id']) : '';
	    if (!$id)
	    {
	        $this->show_warning('no_notice_to_drop');
	        return;
	    }
	    $ids = explode(',', $id);
	    m('cmscontent')->drop($ids);
	    $this->show_message('drop_ok');
	}
	/**
	 * 栏目列表
	 */
	public function groups(){

	    $m=m('cmsgroup');
	    $page = $this->_get_page();
	    $sql='select id,title from ecm_cms_left order by create_time limit '.$page['limit'];
	    $contents =$m->db->getAll($sql);
	    $page['item_count'] = $m->db->getOne('select count(1) from ecm_cms_left');
	    $this->_format_page($page);
	    $this->assign('page_info', $page);
	    $this->assign ( "groups", $contents );
	    $this->display ('cms.group.index.html');
	    
	}
	/**
 	 * 预览/新增/修改栏目
	 */
	public function addgroup() {
	    //保存
	    if(IS_POST){
	        $id=isset($_POST['id']) ? trim($_POST['id']) : '';
	        $map ['title'] =  $_POST ['title'];
	        if(!$id){
	            $map ['uid'] = $this->visitor->user_id;
	            $map['create_time']=time();
	        }
	        if($id){
	            $res = m('cmsgroup')->edit ($id, $map);
	        }else{
	            $res = m('cmsgroup')->add ( $map );
	        }
	        $this->show_message($id?'update_group_success':'add_content_success',
	                'back_list',        'index.php?app=cms&amp;act=groups',
	                'edit_again',    'index.php?app=cms' . '&amp;act=addgroup&amp;id=' . ($id?$id:$res));
	    }else{
	        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
	        if($id){
	            $m=m('cmsgroup');
	            $this->assign('group',$m->get_info($id));
	        }
	        /* 导入jQuery的表单验证插件 */
	        $this->import_resource(array(
	                'script' => 'jquery.plugins/jquery.validate.js'
	        ));
	        $this->display('cms.group.form.html');
	    }
	    
	}
	/**
	 * 删除公告
	 */
	public function delete_group() {
	    $id = isset($_GET['id']) ? trim($_GET['id']) : '';
	    if (!$id)
	    {
	        $this->show_warning('no_group_to_drop');
	        return;
	    }
	    $ids = explode(',', $id);
	    m('cmsgroup')->drop($ids);
	    $this->show_message('drop_ok');
	}
}
?>