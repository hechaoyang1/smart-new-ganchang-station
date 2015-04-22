<?php

define('UPLOAD_DIR', 'data/files/mall/settings');

/**
 *    基本设置控制器
 *
 *    @author    Hyber
 *    @usage    none
 */
class IndexsettingApp extends BackendApp
{
    function __construct()
    {
        $this->IndexsettingApp();
    }

    function IndexsettingApp()
    {
        parent::BackendApp();
        $_POST = stripslashes_deep($_POST);
    }
    
    function index()
    {
        if (!IS_POST)
        {
            $picture = '/data/files/mall/settings/xztc.png';
            if(!file_exists(ROOT_PATH.$picture))
            {
                $picture = '/themes/mall/default/styles/default/img/wa.png';
            }
            $this->assign('picture', $picture);
            $this->display('setting.index_setting.html');
        }
        else
        {
            
            if($this->_upload_image())
            {
                $this->show_message('保存成功', 'back_list', 
                        'index.php?app=indexsetting');
            }
        }
    }
    
    function _upload_image()
    {
        $file = $_FILES['xztc_logo'];
        if ($file['error'] == UPLOAD_ERR_NO_FILE) // 没有文件被上传
        {
            return '';
        }
        import('uploader.lib');             //导入上传类
        $uploader = new Uploader();
        $uploader->allowed_type('png'); //限制文件类型
        $uploader->addFile($_FILES['xztc_logo']);//上传logo
        if (!$uploader->file_info())
        {
            $this->show_warning($uploader->get_error() , 'go_back', 'index.php?app=indexsetting');
            return false;
        }
        /* 指定保存位置的根目录 */
        $uploader->root_dir(ROOT_PATH);
    
        /* 上传 */
        if ($file_path = $uploader->save('data/files/mall/settings', 'xztc'))   //保存到指定目录，并以指定文件名$brand_id存储
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
