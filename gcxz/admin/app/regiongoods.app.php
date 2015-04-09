<?php
define ( 'MAX_LAYER', 6 );

/* 地区控制器 */
class RegiongoodsApp extends BackendApp {
	
	/**
	 * 配置地区商品
	 */
	function index() {
		/* 取得仁寿地区 */
		$regionModel = & m ( 'region' );
		$regions = &m ( 'regiondescription' )->get_info ( 511421 );
		foreach ( $regions as $key => $val ) {
			$regions [$key] ['switchs'] = 0;
			if ($regionModel->get_list ( $val ['region_id'] )) {
				$regions [$key] ['switchs'] = 1;
			}
		}
		$this->assign ( 'regions', $regions );
		
		$this->assign ( 'max_layer', MAX_LAYER );
		
		$this->import_resource ( array (
				'script' => 'inline_edit.js,jqtreetable.js',
				'style' => 'res:style/jqtreetable.css' 
		) );
		$this->display ( 'region.goods.html' );
	}
	
	/**
	 * 配置地区商品
	 */
	function editDescription() {
		$id = empty ( $_REQUEST ['id'] ) ? 0 : intval ( $_REQUEST ['id'] );
		$rdModel = & m ( 'regiondescription' );
		/* 是否存在 */
		$result = $rdModel->get_info ( $id );
		$region = $result [0];
		if (! IS_POST) {
			if (! $region) {
				$this->show_warning ( 'region_empty' );
				return;
			}
			$this->assign ( 'region', $region );
			
			$this->display ( 'region.description.form.html' );
		} else {
			$image = $this->_upload_top_image ( $id );
			if ($image === false) {
				$this->show_warning ( '保存图片失败', 'go_back', 'index.php?app=regiongoods&amp;act=editDescription&amp;id=' . $id );
				return;
			}
			$data = array (
					'region_id' => $id,
					'description' => $_POST ['description'],
					'default_image' => $image 
			);
			if ($region ['description'] === NULL) {
				$rdModel->add ( $data );
			} else {
				$rdModel->edit ( $region ['id'], $data );
			}
			$this->show_message ( '保存成功', 'go_back', 'index.php?app=regiongoods&amp;act=editDescription&amp;id=' . $id );
		}
	}
	
	/**
	 * 处理上传标志
	 *
	 * @author Hyber
	 * @param int $brand_id        	
	 * @return string
	 */
	function _upload_top_image($id) {
		$file = $_FILES ['default_image'];
		if ($file ['error'] == UPLOAD_ERR_NO_FILE) 		// 没有文件被上传
		{
			return '';
		}
		import ( 'uploader.lib' ); // 导入上传类
		$uploader = new Uploader ();
		$uploader->allowed_type ( IMAGE_FILE_TYPE ); // 限制文件类型
		$uploader->addFile ( $_FILES ['default_image'] ); // 上传logo
		if (! $uploader->file_info ()) {
			$this->show_warning ( $uploader->get_error (), 'go_back', 'index.php?app=regiongoods&amp;act=editDescription&amp;id=' . $id );
			exit ();
		}
		/* 指定保存位置的根目录 */
		$uploader->root_dir ( ROOT_PATH );
		
		/* 上传 */
		if ($file_path = $uploader->save ( 'data/files/mall/ttc', $id )) 		// 保存到指定目录，并以指定文件名$brand_id存储
		{
			return $file_path;
		} else {
			return false;
		}
	}
	
	/* 异步取下一级地区 */
	function ajax_cate() {
		if (! isset ( $_GET ['id'] ) || empty ( $_GET ['id'] )) {
			echo ecm_json_encode ( false );
			return;
		}
		$rdModel = & m ( 'regiondescription' );
		$cate = $rdModel->get_list ( $_GET ['id'] );
		foreach ( $cate as $key => $val ) {
			$child = $rdModel->get_list ( $val ['region_id'] );
			if (! $child || empty ( $child )) {
				$cate [$key] ['switchs'] = 0;
			} else {
				$cate [$key] ['switchs'] = 1;
			}
		}
		header ( "Content-Type:text/html;charset=" . CHARSET );
		echo ecm_json_encode ( array_values ( $cate ) );
		// $this->json_result($cate);
		return;
	}
	
	// 异步修改数据
	function ajax_col() {
		$id = empty ( $_GET ['id'] ) ? 0 : intval ( $_GET ['id'] );
		$column = empty ( $_GET ['column'] ) ? '' : trim ( $_GET ['column'] );
		$value = isset ( $_GET ['value'] ) ? trim ( $_GET ['value'] ) : '';
		$data = array ();
		
		if (in_array ( $column, array (
				'sort_order' 
		) )) {
			$data [$column] = $value;
			$regionModel = & m ( 'region' );
			$regionModel->edit ( $id, $data );
			if (! $regionModel->has_error ()) {
				echo ecm_json_encode ( true );
			}
		} else if ($column == 'if_show') {
			$rdModel = & m ( 'regiondescription' );
			$result = $rdModel->get_info ( $id );
			$region = $result [0];
			$data = array (
					'region_id' => $id,
					'description' => '' 
			);
			$data [$column] = $value;
			if ($region ['description'] === NULL) {
				$rdModel->add ( $data );
			} else {
				$rdModel->edit ( $region ['id'], $data );
			}
			echo ecm_json_encode ( true );
		}
		return;
	}
}

?>
