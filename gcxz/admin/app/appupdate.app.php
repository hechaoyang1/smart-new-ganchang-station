<?php
/**
 * CMS 公告系统
 * @author admin
 *
 */
class AppupdateApp extends BackendApp {
	
	/**
	 * index
	 * 公告列表
	 *
	 * @access public
	 * @return void
	 */
	public function index() {
		$this->display ( 'appupdate.add.form.html' );
	}
	public function add() {
		$pn = trim ( $_POST ['pn'] );
		$vc = trim ( $_POST ['vc'] );
		$md5 = trim ( $_POST ['md5'] );
		$file = $_FILES ['file'];
		
		$save_path = ROOT_PATH . "/data/files/appupdate";
		if (! file_exists ( $save_path )) {
			ecm_mkdir ( $save_path );
		}
		$extension = pathinfo ( $file ['name'], PATHINFO_EXTENSION );
		$rename = md5 ( $file ['name'] . microtime () );
		$file_path = $save_path . '/' . $rename . '.' . $extension;
		// 上传成功
		if (move_uploaded_file ( $file ['tmp_name'], $file_path )) {
			// 保存数据库
			$appModel = &m ( 'appurl' );
			$data ['pn'] = $pn;
			$data ['vc'] = $vc;
			$data ['md5'] = $md5;
			$data ['url'] =  "/data/files/appupdate/" . $rename . '.' . $extension;
			if ($appModel->add ( $data )) {
				$this->show_message ( '上传成功' );
				return;
			}
		}
		$this->show_warning ( '请重新上传' );
	}
}
?>