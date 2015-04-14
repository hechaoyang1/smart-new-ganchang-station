<?php

/**
 * 商品分类展示
 * @author xucaibing
 *
 */
class NativegoodsApp extends MallbaseApp {
	function index() {
		// 获取广告
		$goodsadModel = &m ( 'goodsad' );
		$this->assign ( 'ads', $goodsadModel->get_list ( 2 ) );
		$goodsModel = &m ( 'goods' );
		// 获取土特产置顶商品
		$top_list = $goodsModel->get_list ( array (
				'conditions' => 'g.ttc=1',
				'limit' => 6 
		) );
		$this->assign ( 'top_list', $top_list );
		// 获取土特产
		/* 分页信息 */
		$page = $this->_get_page ( 40 );
		$goods_list = $goodsModel->get_list ( array (
				'conditions' => 'g.native=1',
				'limit' => $page ['limit'],
				'count' => true 
		) );
		foreach ($goods_list as &$good){
			$good['default_image'] = str_replace('small_', '', $good['default_image']);
		}
		$page ['item_count'] = $goods_mod->_last_query_count;
		$this->_format_page ( $page );
		$this->assign ( 'page_info', $page );
		$this->assign ( 'goods_list', $goods_list );
		$this->assign ( 'tab', 'ttc' );
		
		// 商品全部分类
		$this->assign('top_category', m('gcategory')->get_top_category());

		$this->import_resource(array(
		        'style' =>  'res:css/channel.css',
		));
		
		$this->display ( 'nativegoods.index.html' );
	}
}

?>
