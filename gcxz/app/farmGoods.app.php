<?php

/**
 * 商品分类展示
 * @author xucaibing
 *
 */
class FarmGoodsApp extends MallbaseApp {
	function index() {
		// 获取一级分类
		$cate1 = $_GET ['cate1'];
		$cate2 = $_GET ['cate2'];
		$regionId = $_GET ['areaId'];
		// $salesOrder = $_GET ['salesOrder'];
		$timeOrder = $_GET ['timeOrder'];
		$priceOrder = $_GET ['priceOrder'];
		
		// 商品分类
		$gcategory_list=$this->_get_site_gcategory();
		$this->assign('gcategory_list',$gcategory_list);
		
		// 获取农业品一级分类
		$gcateModel = &bm ( 'gcategory' );
		$regionModel = &m ( 'region' );
		$condition = " if_show=1";
		if ($cate1 && intval ( $cate1 ) > 0) {
			$condition .= " and g.cate_id_2=" . $cate1;
			// 根据选中的一级分类获取二级分类
			$cateArr2 = $gcateModel->get_options ( $cate1, true );
			$nameArr1 = $regionModel->db->getAll ( 'select cate_name from ecm_gcategory where cate_id=' . $cate1 );
			$this->assign ( 'cate1', $cate1 );
			$this->assign ( 'cateName1', $nameArr1 [0] ['cate_name'] );
			$this->assign ( 'cateArr2', $cateArr2 );
		}
		if ($cate2 && intval ( $cate2 ) > 0) {
			$condition .= " and g.cate_id_3=" . $cate2;
			$nameArr2 = $regionModel->db->getAll ( 'select cate_name from ecm_gcategory where cate_id=' . $cate2 );
			$this->assign ( 'cate2', $cate2 );
			$this->assign ( 'cateName2', $nameArr2 [0] ['cate_name'] );
		}
		if ($regionId && intval ( $regionId ) > 0) {
			$condition .= " and g.region_id=" . $regionId;
			$nameArr = $regionModel->db->getAll ( 'select region_name from ecm_region where region_id=' . $regionId );
			$this->assign ( 'regionId', $regionId );
			$this->assign ( 'regionName', $nameArr [0] ['region_name'] );
		}
		
		if ($timeOrder) {
			$orderStr = 'g.add_time desc';
			$this->assign ( 'timeOrder', 1 );
		}
		else if ($priceOrder) {
			$orderStr = 'g.price '.$priceOrder;
			$this->assign ( 'priceOrder', $priceOrder=='asc'?'up':'down' );
		}else{
			$orderStr = 'goods_statistics.sales desc';
			$this->assign ( 'salesOrder', 1 );
		}
		// 获取仁寿区域
		$areaSql = 'SELECT * FROM `ecm_region` where parent_id=511421 ORDER BY sort_order';
		$areaArr = $regionModel->db->getAll ( $areaSql );
		$sql = 'SELECT cate_id FROM ecm_gcategory WHERE cate_name = \'农业品\' AND store_id = 0 AND parent_id = 0 LIMIT 1';
		$firstGcate = $gcateModel->db->getAll ( $sql );
		$fristGcateId = $firstGcate ? $firstGcate [0] ['cate_id'] : 0;
		$cateArr1 = $gcateModel->get_options ( $fristGcateId, true );
		
		// 获取筛选商品
		$goodModel = & m ( 'goods' );
		$page = $this->_get_page ( 12 );
		$goods = $goodModel->find ( array (
				'join' => 'has_goodsstatistics,belongs_to_region',
				'conditions' => $condition,
				'limit' => $page ['limit'],
				'order' => $orderStr,
				'count' => true 
		) );
		$page ['item_count'] = $goodModel->getCount ();
		$this->_format_page ( $page );
		$this->assign ( 'page_info', $page );
		$this->assign ( 'cateArr1', $cateArr1 );
		$this->assign ( 'areaArr', $areaArr );
		$this->assign ( 'goods', $goods );
		$this->assign ( 'tab', 'farmGoods' );
		
		$this->display ( 'farmgoods.index.html' );
	}
}

?>
