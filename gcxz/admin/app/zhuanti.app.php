<?php
/**
 * 专题管理
 * @author admin
 *
 */
class ZhuantiApp extends BackendApp {
    private $status_apply=0;
	function __construct()
    {
        $this->ZhuantiApp();
    }

    function ZhuantiApp()
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
	    $m=m('zhuanti');
// 	    $page = $this->_get_page();
	    $lists =$m->find();
	    foreach ($lists as &$v){
	    	$v['ctime']=date('Y-n-d H:i:s',$v['ctime']);
	    	$v['last_modify_time']=date('Y-n-d H:i:s',$v['last_modify_time']);
	        $v['type_name']=$m->get_code_name($v['type_code']);
	    }
	    $this->assign('list', $lists);
		$this->display ('zhuanti.index.html');
	}
	/**
	 * 增加/修改/展示详细页面
	 */
	public function addcontent() {
	    //保存
	    $m=m('zhuanti');
	    if(IS_POST){
	        $id=isset($_POST['id']) ? trim($_POST['id']) : '';
    		$map ['page'] = $_POST['page'];
    		$map ['type_code'] =  $_POST ['type_code'];
    		$map ['last_modify_time'] =  time();
    		if($id){
    		    $res = $m->edit ($id, $map);
    		}else{
    			$find=$m->find(array('conditions'=>"type_code='{$map ['type_code']}'",'limit'=>1));
    			if($find){
    				$this->show_warning('该专题已存在');
    				return;
    			}
    			$map['uid'] = $this->visitor->info['user_id'];
    			$map['ctime']=$map ['last_modify_time'];
    		    $res = $m->add ( $map );
    		}
			$this->show_message($id?'修改成功':'添加成功',
            'back_list',        'index.php?app=zhuanti',
            '重新编辑',    'index.php?app=zhuanti' . '&amp;act=addcontent&amp;id=' . ($id?$id:$res));
	    }else{
	        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
	        if($id){
	            $this->assign('content',$m->get_info($id));
	        }
            //栏目
            $this->assign('codes',$m->get_codes());
	        /* 导入jQuery的表单验证插件 */
	        $this->import_resource(array(
	                'script' => 'jquery.plugins/jquery.validate.js,ueditor/ueditor.config.js,ueditor/ueditor.all.min.js'
	        ));
	        $this->display('zhuanti.form.html');
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
	    m('zhuanti')->drop($ids);
	    $this->show_message('删除成功');
	}
}
?>