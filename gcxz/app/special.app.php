<?php
/**
 * 专题
 * @author liaoy
 *
 */
class SpecialApp extends MallbaseApp {
	var $m;
	function __construct() {
		$this->m = m ( 'region' );
		parent::__construct ();
	}
	function index() {
		$region_id = intval ( $_GET ['rid'] );
		if (! $region_id) {
			// 默认展示整个仁寿
			$region_id = 511421;
		}
		// 地区数据
		$data = $this->_get_regions ( $region_id );
		// 置顶商品
		$goods = $this->m->db->getAll ( "SELECT g.goods_name, g.default_image,g.goods_id,r.region_id FROM ecm_region_goods rg LEFT JOIN ecm_goods g ON rg.goods_id = g.goods_id LEFT JOIN ecm_region r ON rg.region_id = r.region_id WHERE rg.type=2 and r.parent_id = {$region_id}" );
		$imgs = $this->m->db->getAll ( "SELECT bg_image,description FROM ecm_region_bg_image where region_id={$region_id}" );
		$file = ROOT_PATH . "/themes/mall/default/styles/default/svg/m_{$region_id}.svg";
		if (file_exists ( $file )) {
			$map = file_get_contents ($file);
			$this->assign ( "map", $map );
		}
		$this->assign("more",count($data)>10);
		$this->assign ( "regions", $data );
		$this->assign ( "goods", $goods );
		$this->assign ( "imgs", $imgs );
		$this->display ( "ttc.html" );
	}
	
	/**
	 * 获得下级地区列表 ajax
	 */
	function get_regions() {
		$region_id = intval ( $_GET ['rid'] );
		if (! $region_id) {
			echo json_encode ( array (
					'code' => 0 
			) );
			return;
		}
		$data = $this->_get_regions ( $region_id );
		echo json_encode ( array (
				'code' => 1,
				'data' => $data 
		) );
	}
	/**
	 * 推荐商品列表
	 */
	function tj() {
		$region_id = intval ( $_GET ['rid'] );
		if (! $region_id) {
			echo 0;
			return;
		}
		$goods = $this->m->db->getAll ( "SELECT g.goods_name, g.default_image, g.goods_id, g.unit, g.price FROM ecm_region_goods rg LEFT JOIN ecm_goods g ON rg.goods_id = g.goods_id LEFT JOIN ecm_goods_spec gs ON g.goods_id = gs.goods_id WHERE rg.type = 1 AND rg.region_id = {$region_id} order by rg.sort_order asc limit 3" );
		if (! $goods) {
			echo 0;
			return;
		}
		$region = $this->m->db->getAll ( "select rd.description,rd.default_image,r.region_name,r.region_id from ecm_region_description rd left join ecm_region r on rd.region_id =r.region_id where rd.region_id={$region_id} limit 1" );
		$region = current ( $region );
		$this->assign ( "goods", $goods );
		$this->assign ( "region", $region );
		$this->display ( "tc.html" );
	}
	/**
	 * 获得下级地区列表
	 *
	 * @param unknown $rid        	
	 * @return Ambigous <boolean, unknown>
	 */
	function _get_regions($rid) {
		$cache_server = & cache_server ();
		$data = $cache_server->get ( 'regions_pid_' . $rid );
		if ($data === false) {
			$data = $this->m->db->getAll ( "select r.region_id,r.region_name from ecm_region r left join ecm_region_description rd on rd.region_id =r.region_id where r.parent_id={$rid} and rd.if_show=1" );
			// 缓存一天
			if ($data) {
				$cache_server->set ( 'regions_pid_' . $rid, $data, 24 * 60 * 60 );
			}
		}
		return $data;
	}
}

?>
