<?php

/**
 * 商品分类展示
 * @author xucaibing
 *
 */
class InterfaceApp extends BaseApp {
	private $mod_uploadedfile;
	
	/**
	 * 下载订单
	 */
	function downOrder() {
		$limit = $_POST ['num'] ? ' limit ' . intval ( $_POST ['num'] ) : '';
		// 获取所有订单
		$orderSql = 'SELECT DISTINCT
						o.order_id,
						o.order_sn,
						o.seller_name,
						o.add_time,
						o.buyer_name,
						e.consignee,
						e.phone_mob,
						e.shipping_name,
						CONCAT(e.region_name,e.address) address,
						o.payment_name,
						s.tel store_tel,
						s.address,
						o.order_amount
					FROM
						`ecm_order_goods` og
					LEFT JOIN ecm_order o ON og.order_id = o.order_id
					LEFT JOIN ecm_store s ON o.seller_id = s.store_id
					LEFT JOIN ecm_order_extm e ON o.order_id = e.order_id
					WHERE
						o.`status` = 20
					AND og.source_type = 2' . $limit;
		$orderModel = &m ( 'order' );
		// 查找符合要求的订单
		$orders = $orderModel->getAll ( $orderSql );
		$orderGoodsModel = &m ( 'ordergoods' );
		foreach ( $orders as &$order ) {
			// 查找订单下的商品
			$order ['goods'] = array_values ( $orderGoodsModel->find ( array (
					'conditions' => 'order_id=' . $order ['order_id'],
					'fields' => 'price,quantity,goods_number' 
			) ) );
			// 更改订单已被下载的状态
			// $orderModel->edit ( $order ['order_id'], array (
			// 'status' => ORDER_REQUEST
			// ) );
		}
		if ($orders) {
			$result ['code'] = 1;
			$result ['msg'] = 'OK';
			$result ['data'] = $orders;
		} else {
			$result ['code'] = 0;
			$result ['msg'] = '暂时没有订单';
		}
		exit ( json_encode ( $result ) );
	}
	
	/**
	 * 退订单
	 */
	function retreatOrder() {
		$param = '&order_id=73&return_order_sn=152349492348&goods_number=1111&quantity=1';
		sendPost ( 'http://192.168.30.47:8080/wms/api/retreatOrder', $param );
	}
	/**
	 * 修改退单状态
	 */
	function updateRetreatOrderStatus() {
		$sn = $_POST ['return_order_sn'];
		$data ['status'] = $_POST ['status'];
		$_POST ['reason'] && $data ['remark'] = $_POST ['reason'];
		$returnGoodsModel = &m ( 'returngoods' );
		if (! $returnGoodsModel->edit ( 'return_order_sn="' . $sn . '"', $data )) {
			$result ['code'] = 0;
			$result ['msg'] = '修改退单状态失败';
			exit ( json_encode ( $result ) );
		}
		$mark = '验货失败,原因:' . $_POST ['reason'];
		// 如果验货通过
		if (RETURN_EXAMINE_OK == $data ['status']) {
			$mark = '验货通过';
			$sql = 'SELECT
						g.id return_goods_id,
						g.user_id,
						g.seller_id,
						g.seller_name,
						g.buyer_name,
						g.remark info,
						g.order_id,
						g.quantity,
						o.order_sn,
						og.price,
						og.goods_id,
						og.price * g.quantity amount,
						og.goods_name,
						og.goods_image
					FROM
						`ecm_return_goods` g
					LEFT JOIN ecm_order o ON g.order_id = o.order_id
					LEFT JOIN ecm_order_goods og ON g.order_goods_id = og.rec_id
					WHERE
						g.return_order_sn = "' . $sn . '"';
			// 生成退款单
			$info = $returnGoodsModel->getAll ( $sql );
			if (! $info) {
				$result ['code'] = 0;
				$result ['msg'] = '查询退单失败';
				exit ( json_encode ( $result ) );
			}
			$refund = &m ( 'refund' );
			$refundData ['user_id'] = $info [0] ['user_id'];
			$refundData ['seller_id'] = $info [0] ['seller_id'];
			$refundData ['seller_name'] = $info [0] ['seller_name'];
			$refundData ['buyer_name'] = $info [0] ['buyer_name'];
			$refundData ['info'] = $info [0] ['info'];
			$refundData ['order_id'] = $info [0] ['order_id'];
			$refundData ['order_sn'] = $info [0] ['order_sn'];
			$refundData ['amount'] = $info [0] ['amount'];
			$refundData ['return_goods_id'] = $info [0] ['return_goods_id'];
			$refundData ['status'] = STATUS_AUDITD;
			$refundData ['type'] = 2;
			$refundData ['ctime'] = time ();
			$refun_id = $refund->add ( $refundData );
			if (! $refun_id) {
				$result ['code'] = 0;
				$result ['msg'] = '添加退款单失败';
				exit ( json_encode ( $result ) );
			}
			$refundgoods = &m ( 'refundgoods' );
			$goodsData ['refund_id'] = $refun_id;
			$goodsData ['goods_id'] = $info [0] ['goods_id'];
			$goodsData ['goods_name'] = $info [0] ['goods_name'];
			$goodsData ['price'] = $info [0] ['price'];
			$goodsData ['goods_image'] = $info [0] ['goods_image'];
			$goodsData ['quantity'] = $info [0] ['quantity'];
			if (! $refundgoods->add ( $goodsData )) {
				$result ['code'] = 0;
				$result ['msg'] = '添加退款单货物表失败';
				exit ( json_encode ( $result ) );
			}
		}
		
		// 记录日志
		$logInfo = $returnGoodsModel->get ( array (
				'conditions' => 'return_order_sn="' . $sn . '"',
				'fields' => 'log' 
		) );
		$log = $logInfo ['log'];
		$log = $returnGoodsModel->_create_log ( unserialize ( $log ), '', '仓库', $mark );
		$returnGoodsModel->edit ( 'return_order_sn="' . $sn . '"', array (
				'log' => $log 
		) );
		
		$result ['code'] = 1;
		$result ['msg'] = 'OK';
		exit ( json_encode ( $result ) );
	}
	/**
	 * 修改订单状态
	 */
	function updateOrderStatus() {
		// 获取订单状态参数
		$param = str_replace ( '\\', '', $_POST ['data'] );
		$data = json_decode ( $param );
		$result ['code'] = 0;
		if (is_array ( $data )) {
			$orderModel = &m ( 'order' );
			$errorArray = array ();
			foreach ( $data as $order ) {
				$editData ['status'] = $order->status;
				// 如果有物流方式
				if ($order->invoice_no) {
					// 修改运单号
					$editData ['invoice_no'] = $order->invoice_no;
				}
				// 如果修改失败
				if (! $orderModel->edit ( $order->order_id, $editData )) {
					array_push ( $errorArray, $order->order_id );
				} else {
					$info = $orderModel->get ( array (
							'conditions' => 'order_id=' . $order->order_id,
							'fields' => 'status' 
					) );
					/* 记录订单操作日志 */
					$order_log = & m ( 'orderlog' );
					$order_log->add ( array (
							'order_id' => $order->order_id,
							'operator' => 'interface',
							'order_status' => $info ['status'],
							'changed_status' => $order->status,
							'log_time' => gmtime () 
					) );
				}
			}
			$result ['code'] = 1;
			$result ['msg'] = 'OK';
			$result ['data'] = $errorArray;
			exit ( json_encode ( $result ) );
		} else {
			$result ['msg'] = '参数不合法.';
			exit ( json_encode ( $result ) );
		}
	}
	
	/**
	 * 同步供应商信息
	 */
	function syncSupplier() {
		// 接收所有参数
		$status = $_POST ['status'];
		$id = $_POST ['id'];
		$result ['code'] = 0;
		if(!isset($id)){
			$result ['msg'] = '供应商id为必填字段';
			exit ( json_encode ( $result ) );
		}
		$supModel = &m ( 'supplier' );
		// 新增
		if ($status == 1) {
			$data ['id'] = $id;
			$data ['name'] = $_POST ['name'];
			$data ['contacts'] = $_POST ['contacts'];
			$data ['tel'] = $_POST ['tel'];
			$data ['company_tel'] = $_POST ['company_tel'];
			if (! $supModel->add ( $data )) {
				$result ['msg'] = '添加供应商失败,请重试';
				exit ( json_encode ( $result ) );
			}
		} else if ($status == 2) { // 删除
			$supModel->drop ( $id );
		} else if ($status == 3) { // 修改
			$_POST ['name'] && $data ['name'] = $_POST ['name'];
			$_POST ['contacts'] && $data ['contacts'] = $_POST ['contacts'];
			$_POST ['tel'] && $data ['tel'] = $_POST ['tel'];
			$_POST ['company_tel'] && $data ['company_tel'] = $_POST ['company_tel'];
			if ($supModel->edit ( $id, $data )) {
				$result ['msg'] = '修改供应商失败,请重试';
				exit ( json_encode ( $result ) );
			}
		}
		$result ['code'] = 1;
		$result ['msg'] = 'OK';
		exit ( json_encode ( $result ) );
	}
	
	/**
	 * 同步商品信息
	 */
	function syncGood() {
		// 接收所有参数
		$status = $_POST ['status'];
		$id = $_POST ['id'];
		$result ['code'] = 0;
		if(!isset($id)){
			$result ['msg'] = '商品id为必填字段';
			exit ( json_encode ( $result ) );
		}
		$cGoodsModel = &m ( 'commongoods' );
		$gModel = & bm ( 'gcategory', array (
				'_store_id' => 0 
		) );
		// 新增商品
		if ($status == 1) {
			$data ['id'] = $id;
			$_POST ['goods_name'] && $data ['goods_name'] = $_POST ['goods_name'];
			$_POST ['description'] && $data ['description'] = $_POST ['description'];
			if ($_POST ['cate_id']) {
				$cate_id = $_POST ['cate_id'];
				/* 是否存在 */
				$gcategory = $gModel->get ( array (
						'conditions' => "ref_id = '$cate_id'",
						'fields' => 'this.*' 
				) );
				if (! $gcategory) {
					$result ['msg'] = '该分类不存在';
					exit ( json_encode ( $result ) );
				}
				
				$data ['cate_id'] = $gcategory ['cate_id'];
			}
			$_POST ['brand'] && $data ['brand'] = $_POST ['brand'];
			($_POST ['if_show'] || $_POST ['if_show'] === '0') && $data ['if_show'] = $_POST ['if_show'];
			($_POST ['close'] || $_POST ['close'] === '0') && $data ['close'] = $_POST ['close'];
			$_POST ['close_reason'] && $data ['close_reason'] = $_POST ['close_reason'];
			$_POST ['price'] && $data ['price'] = $_POST ['price'];
			$_POST ['service_type'] && $data ['service_type'] = $_POST ['service_type'];
			$_POST ['supplier_id'] && $data ['supplier_id'] = $_POST ['supplier_id'];
			$_POST ['article_number'] && $data ['goods_number'] = $_POST ['article_number'];
			$_POST ['spec'] && $data ['spec'] = str_replace ( '\\', '', $_POST ['spec'] );
			// 如果id重复 直接修改
			if ($cGoodsModel->get ( $id )) {
				$cGoodsModel->edit ( $id, $data );
			} else if (! $cGoodsModel->add ( $data )) {
				$result ['msg'] = '保存失败,请重试';
				exit ( json_encode ( $result ) );
			}
		} else if ($status == 2) {
			$cGoodsModel->drop ( $id );
		} else if ($status == 3) {
			$_POST ['goods_name'] && $data ['goods_name'] = $_POST ['goods_name'];
			$_POST ['description'] && $data ['description'] = $_POST ['description'];
			if ($_POST ['cate_id']) {
				$cate_id = $_POST ['cate_id'];
				/* 是否存在 */
				$gcategory = $gModel->get ( array (
						'conditions' => "ref_id = '$cate_id'",
						'fields' => 'this.*' 
				) );
				if (! $gcategory) {
					$result ['msg'] = '该分类不存在';
					exit ( json_encode ( $result ) );
				}
				
				$data ['cate_id'] = $gcategory ['cate_id'];
			}
			$_POST ['brand'] && $data ['brand'] = $_POST ['brand'];
			($_POST ['if_show'] || $_POST ['if_show'] === '0') && $data ['if_show'] = $_POST ['if_show'];
			($_POST ['close'] || $_POST ['close'] === '0') && $data ['close'] = $_POST ['close'];
			$_POST ['close_reason'] && $data ['close_reason'] = $_POST ['close_reason'];
			$_POST ['price'] && $data ['price'] = $_POST ['price'];
			$_POST ['service_type'] && $data ['service_type'] = $_POST ['service_type'];
			$_POST ['supplier_id'] && $data ['supplier_id'] = $_POST ['supplier_id'];
			$_POST ['article_number'] && $data ['goods_number'] = $_POST ['article_number'];
			$_POST ['spec'] && $data ['spec'] = str_replace ( '\\', '', $_POST ['spec'] );
			if ($cGoodsModel->edit ( $id, $data )) {
				$result ['msg'] = '保存失败,请重试';
				exit ( json_encode ( $result ) );
			}
		}
		
		$result ['code'] = 1;
		$result ['msg'] = 'OK';
		exit ( json_encode ( $result ) );
	}
	
	/**
	 * 同步商品分类信息
	 */
	function syncGcategory() {
		// 接收所有参数
		$status = $_POST ['status'];
		$id = $_POST ['id'];
		$parent_id = $_POST ['parent_id'];
		$gModel = & bm ( 'gcategory', array (
				'_store_id' => 0 
		) );
		$result ['code'] = 0;
		if(!isset($id)){
			$result ['msg'] = '分类id为必填字段';
			exit ( json_encode ( $result ) );
		}
		// 新增
		if ($status == 1) {
			if ($parent_id) {
				/* 检查名称是否已存在 */
				$parent = $gModel->get ( array (
						'conditions' => "ref_id = '$parent_id'",
						'fields' => 'this.*' 
				) );
				if (! $parent) {
					$result ['msg'] = '该分类parent_id不存在';
					exit ( json_encode ( $result ) );
				}
			}
			$data ['ref_id'] = $id;
			$data ['cate_name'] = $_POST ['cate_name'];
			$data ['parent_id'] = $parent ['cate_id'] ? $parent ['cate_id'] : 0;
			$data ['if_show'] = $_POST ['if_show'] == 2 ? 0 : 1;
			// 如果该分类名称已存在 关联ref_id
			if (! $gModel->unique ( trim ( $_POST ['cate_name'] ), $data ['parent_id'] )) {
				$gModel->edit ( 'cate_name="' . $data ['cate_name'] . '" and parent_id=' . $data ['parent_id'], $data );
			} else {
				/* 保存 */
				$cate_id = $gModel->add ( $data );
				if (! $cate_id) {
					$result ['msg'] = '保存失败,请重试';
					exit ( json_encode ( $result ) );
				}
			}
		} else if ($status == 2) { // 删除分类
		                           // 根据ref_id 找到主键id
			$info = $gModel->get ( array (
					'conditions' => "ref_id = '$id'",
					'fields' => 'this.cate_id' 
			) );
			if ($info && ! $gModel->drop ( $info ['cate_id'] )) {
				$result ['msg'] = '删除失败,请重试';
				exit ( json_encode ( $result ) );
			}
		} else if ($status == 3) { // 修改分类
			/* 是否存在 */
			$gcategory = $gModel->get ( array (
					'conditions' => "ref_id = '$id'",
					'fields' => 'this.*' 
			) );
			if (! $gcategory) {
				$result ['msg'] = '该分类不存在';
				exit ( json_encode ( $result ) );
			}
			if ($gcategory ['cate_name'] != trim ( $_POST ['cate_name'] ) && ! $gModel->unique ( trim ( $_POST ['cate_name'] ), $_POST ['parent_id'] )) {
				$result ['msg'] = '该分类名称已存在，请您重新输入';
				exit ( json_encode ( $result ) );
			}
			if ($_POST ['cate_name']) {
				$data ['cate_name'] = $_POST ['cate_name'];
			}
			if ($_POST ['parent_id'] || $_POST ['parent_id'] === '0') {
				$data ['parent_id'] = intval ( $_POST ['parent_id'] );
			}
			if ($_POST ['if_show']) {
				$data ['if_show'] = $_POST ['if_show'] == 2 ? 0 : 1;
			}
			// 保存
			$rows = $gModel->edit ( $gcategory ['cate_id'], $data );
			if ($gModel->has_error ()) {
				$result ['msg'] = '更新失败,请重试';
				exit ( json_encode ( $result ) );
			}
			/* 如果改变了上级分类，更新商品表中相应记录的cate_id_1到cate_id_4 */
			if ($gcategory ['parent_id'] != $data ['parent_id']) {
				// 执行时间可能比较长，所以不限制了
				_at ( set_time_limit, 0 );
				
				// 清除商城商品分类缓存
				$cache_server = & cache_server ();
				$cache_server->delete ( 'goods_category_0' );
				
				// 取得当前分类的所有子孙分类（包括自身）
				$cids = $gModel->get_descendant_ids ( $gcategory ['cate_id'], true );
				
				// 找出这些分类中有商品的分类
				$mod_goods = & m ( 'goods' );
				$mod_gcate = & $gModel;
				$sql = "SELECT DISTINCT cate_id FROM {$mod_goods->table} WHERE cate_id " . db_create_in ( $cids );
				$cate_ids = $mod_goods->getCol ( $sql );
				
				// 循环更新每个分类的cate_id_1到cate_id_4
				foreach ( $cate_ids as $cate_id ) {
					$cate_id_n = array (
							0,
							0,
							0,
							0 
					);
					$ancestor = $mod_gcate->get_ancestor ( $cate_id, true );
					for($i = 0; $i < 4; $i ++) {
						isset ( $ancestor [$i] ) && $cate_id_n [$i] = $ancestor [$i] ['cate_id'];
					}
					$sql = "UPDATE {$mod_goods->table} " . "SET cate_id_1 = '{$cate_id_n[0]}', cate_id_2 = '{$cate_id_n[1]}', cate_id_3 = '{$cate_id_n[2]}', cate_id_4 = '{$cate_id_n[3]}' " . "WHERE cate_id = '$cate_id'";
					$mod_goods->db->query ( $sql );
				}
			}
		}
		$result ['code'] = 1;
		$result ['msg'] = 'OK';
		exit ( json_encode ( $result ) );
	}
	/**
	 * app端验证用户登录注册
	 */
	function login() {
		$user = $_POST ['user'];
		$password = $_POST ['password'];
		$model_member = & m ( 'member' );
		$info = $model_member->get ( "user_name='{$user}'" );
		if ($info ['password'] != md5 ( $password )) {
			$result ['code'] = 0;
			$result ['msg'] = '密码验证失败';
		} else {
			$result ['code'] = 1;
			$result ['msg'] = 'OK';
			$result ['user_id'] = intval ( $info ['user_id'] );
		}
		exit ( json_encode ( $result ) );
	}
	
	/**
	 * 查询分类
	 */
	function getCategory() {
	}
	/**
	 * app升级
	 */
	function updateApp() {
		$pn = $_POST ['pn'];
		$vc = $_POST ['vc'];
		$appModel = &m ( 'appurl' );
		$data = $appModel->get ( array (
				'conditions' => 'pn="' . $pn . '" AND vc>' . $vc,
				'fields' => 'pn,vc,md5,url appURL',
				'order' => 'id desc' 
		) );
		if ($data) {
			$result ['code'] = 1;
			$result ['msg'] = 'OK';
			$data['appURL'] = SITE_URL .$data['appURL'];
			$result ['appObject'] = array (
					$data 
			);
		} else {
			$result ['code'] = 0;
			$result ['msg'] = 'No inquiry into the upgrade package';
		}
		exit ( json_encode ( $result ) );
	}
	
	/**
	 * 添加商品
	 */
	function addGood() {
		$user_id = intval($_POST ['user_id']);
		$userprivModel = &m ( 'userpriv' );
		$info = $userprivModel->find ( array (
				'conditions' => 'user_id=' . $user_id,
				'fields' => 'store_id' 
		) );
		if (! $info) {
			$result ['code'] = 0;
			$result ['msg'] = '你还没有自己的店铺,无法创建商品';
			exit ( json_encode ( $result ) );
		}
		// 获取店铺id
		$store_id = $info [$user_id] ['store_id'];
		$data ['store_id'] = $store_id;
		$_POST ['goods_name'] && $data ['goods_name'] = $_POST ['goods_name'];
		// $_POST ['cateArr'] && $data ['cate_id'] = $_POST ['cateArr'];
		// $data ['cate_id'] = $_POST ['locaCateArr'];
		$_POST ['brand'] && $data ['brand'] = $_POST ['brand'];
		$_POST ['tags'] && $data ['tags'] = $_POST ['tags'];
		$_POST ['price'] && $data ['price'] = $_POST ['price'];
		$_POST ['price_original'] && $data ['price1'] = $_POST ['price_original'];
		($_POST ['is_show'] || $_POST ['is_show'] == '0') && $data ['if_show'] = 0;
		$_POST ['service_type'] && $data ['service_type'] = $_POST ['service_type'];
		($_POST ['is_recommended'] || $_POST ['is_recommended'] == '0') && $data ['recommended'] = $_POST ['is_recommended'];
		$_POST ['spec_name_1'] && $data ['spec_name_1'] = $_POST ['spec_name_1'];
		$_POST ['spec_name_2'] && $data ['spec_name_2'] = $_POST ['spec_name_2'];
		$_POST ['spec'] && $data ['spec'] = $_POST ['spec'];
		
		$goodModel = &bm ( 'goods' );
		$goodModel->_store_id = $store_id;
		// 添加商品
		$goods_id = $goodModel->add ( $data );
		if (! $goods_id) {
			$result ['code'] = 0;
			$result ['msg'] = '添加商品基本信息失败';
			exit ( json_encode ( $result ) );
		}
		// 添加商品规格
		$spec ['goods_id'] = $goods_id;
		$spec ['price'] = $data ['price'];
		$spec ['price1'] = $data ['price1'];
		$spec ['stock'] = $_POST ['stock'];
		$spec ['sku'] = $_POST ['sku'];
		$specModel = &m ( 'goodsspec' );
		$spec_id = $specModel->add ( $spec );
		if (! $spec_id) {
			$result ['code'] = 0;
			$result ['msg'] = '添加商品规格信息失败';
			exit ( json_encode ( $result ) );
		}
		$images = $_FILES ['file'];
		if (is_array ( $images )) {
			require (ROOT_PATH . '/includes/libraries/image.func.php');
			$this->mod_uploadedfile = &m ( 'uploadedfile' );
			$names = $images ['name'];
			$paths = array ();
			if (is_array ( $names )) {
				foreach ( $images ['name'] as $k => $image ) {
					if (empty ( $image ))
						continue;
					$imageData ['store_id'] = $store_id;
					$imageData ['goods_id'] = $goods_id;
					$imageData ['name'] = $images ['name'] [$k];
					$imageData ['tmp_name'] = $images ['tmp_name'] [$k];
					$imageData ['mime'] = $images ['type'] [$k];
					$imageData ['size'] = $images ['size'] [$k];
					if (! $this->saveImg ( $imageData, $paths )) {
						$goodModel->drop ( $goods_id );
						$result ['code'] = 0;
						$result ['msg'] = '图片上传失败';
						exit ( json_encode ( $result ) );
					}
				}
			} else {
				// 添加商品图片
				$imageData ['store_id'] = $store_id;
				$imageData ['goods_id'] = $goods_id;
				$imageData ['name'] = $names;
				$imageData ['tmp_name'] = $images ['tmp_name'];
				$imageData ['mime'] = $images ['type'];
				$imageData ['size'] = $images ['size'];
				if (! $this->saveImg ( $imageData, $paths )) {
					$goodModel->drop ( $goods_id );
					$result ['code'] = 0;
					$result ['msg'] = '图片保存失败';
					exit ( json_encode ( $result ) );
				}
			}
			// 重新编辑商品图片地址
			$editData ['default_image'] = $paths [0] ['thumbnail'];
			$editData ['default_spec'] = $spec_id;
			$goodModel->edit ( $goods_id, $editData );
			$goods_image_model = &m ( 'goodsimage' );
			foreach ( $paths as $path ) {
				$goods_image_data ['goods_id'] = $goods_id;
				$goods_image_data ['image_url'] = $path ['image_url'];
				$goods_image_data ['thumbnail'] = $path ['thumbnail'];
				$goods_image_data ['file_id'] = $path ['file_id'];
				$goods_image_data ['sort_order'] = 255;
				$goods_image_model->add ( $goods_image_data );
			}
		}
		$result ['code'] = 1;
		$result ['msg'] = 'OK';
		exit ( json_encode ( $result ) );
	}
	private function saveImg($imageData, &$paths) {
		$extension = pathinfo ( $imageData ['name'], PATHINFO_EXTENSION );
		$rename = md5 ( $imageData ['name'] . microtime () );
		$save_path = 'data/files/store_' . $imageData ['store_id'] . '/goods_' . (time () % 200);
		if (! file_exists ( $save_path )) {
			ecm_mkdir ( $save_path );
		}
		$file_path = $save_path . '/' . $rename . '.' . $extension;
		// 上传成功
		if (move_uploaded_file ( $imageData ['tmp_name'], $file_path )) {
			// 保存到upload_file表中
			$upload_file = array (
					'store_id' => $imageData ['store_id'],
					'file_type' => $imageData ['mime'],
					'file_size' => $imageData ['size'],
					'file_name' => $imageData ['name'],
					'file_path' => $file_path,
					'belong' => 2,
					'item_id' => $imageData ['goods_id'],
					'add_time' => gmtime () 
			);
			$file_id = $this->mod_uploadedfile->add ( $upload_file );
			if (! $file_id) {
				$result ['code'] = 0;
				$result ['msg'] = '添加图片数据失败';
				exit ( json_encode ( $result ) );
			}
			/* 生成缩略图 */
			$thumbnail = dirname ( $file_path ) . '/small_' . basename ( $file_path );
			make_thumb ( ROOT_PATH . '/' . $file_path, ROOT_PATH . '/' . $thumbnail, 300, 300, 85 );
			$paths [] = array (
					'image_url' => $file_path,
					'thumbnail' => $thumbnail,
					'sort_order' => 255,
					'file_id' => $file_id 
			);
			return true;
		}
		return false;
	}
}

?>
