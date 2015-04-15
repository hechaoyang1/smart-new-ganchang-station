<?php
/**
 * 专题
 * @author liaoy
 *
 */
class SpecialApp extends MallbaseApp {
	var $m;
	var $renshou = 511421;
	function __construct() {
		$this->m = m ( 'region' );
		parent::__construct ();
	}
	function index() {
		$region_id = intval ( $_GET ['rid'] );
		if (! $region_id) {
			// 默认展示整个仁寿
			$region_id = $this->renshou;
		}
		// 地区数据
		$data = $this->_get_regions ( $region_id );
		// 置顶商品
		$goods = $this->m->db->getAll ( "SELECT g.goods_name, g.default_image,g.goods_id,r.region_id FROM ecm_region_goods rg LEFT JOIN ecm_goods g ON rg.goods_id = g.goods_id LEFT JOIN ecm_region r ON rg.region_id = r.region_id join ecm_region_description rd on r.region_id=rd.region_id and rd.if_show=1 WHERE rg.type=2 and r.parent_id = {$region_id}" );
		$imgs = $this->m->db->getAll ( "SELECT bg_image,description FROM ecm_region_bg_image where region_id={$region_id}" );
		$file = ROOT_PATH . "/themes/mall/default/styles/default/svg/m_{$region_id}.svg";
		if (file_exists ( $file )) {
			$map = file_get_contents ( $file );
			$this->assign ( "map", $map );
		}
		$this->_cur_top ( $region_id );
		$this->assign ( "more", count ( $data ) > 10 );
		$this->assign ( "regions", $data );
		$this->assign ( "regionsjson", json_encode ( $data ) );
		$this->assign ( "goods", $goods );
		$this->assign("region_id",$region_id);
		$this->assign ( "imgs", $imgs );
		if ($region_id == $this->renshou) {
			$this->assign ( "is_area", true );
		}
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
		$goods = $this->m->db->getAll ( "SELECT g.goods_name, g.default_image, g.goods_id, g.unit, g.price FROM ecm_region_goods rg LEFT JOIN ecm_goods g ON rg.goods_id = g.goods_id LEFT JOIN ecm_goods_spec gs ON g.goods_id = gs.goods_id LEFT JOIN ecm_region_description rd ON rg.region_id = rd.region_id WHERE rg.region_id ={$region_id} AND rd.if_show = 1 AND rg.type = 1 order by rg.sort_order asc " );
		if (! $goods) {
			echo 0;
			return;
		}
		if(count($goods)>3){
			$this->assign ( "has_more", true );
		}
		$ids = getSubByKey($goods,'goods_id');
		foreach ($ids as &$id){
			$id=intval($id);
		}
		$goods = array_slice($goods, 0,3);
		$region = $this->m->db->getAll ( "select rd.description,rd.default_image,r.region_name,r.region_id from ecm_region_description rd left join ecm_region r on rd.region_id =r.region_id where rd.region_id={$region_id} limit 1" );
		$region = current ( $region );
		$this->assign ( "goods", $goods );
		$this->assign ( "ids", json_encode($ids) );
		$this->assign ( "region", $region );
		$this->display ( "tc.html" );
	}
	/**
	 * 面包屑
	 */
	function _cur_top($rid) {
		if ($rid == $this->renshou) {
			$tops [] = array (
					"rid" => $this->renshou,
					"name" => '仁寿县',
					'end' => true 
			);
			$this->assign ( 'top', $tops );
			return;
		}
		$data = $this->m->db->getAll ( "SELECT r1.region_id as r1, r1.region_name as n1, r2.region_id as r2, r2.region_name as n2, r3.region_id as r3, r3.region_name as n3 FROM ecm_region r1 LEFT JOIN ecm_region r2 ON r1.parent_id = r2.region_id LEFT JOIN ecm_region r3 ON r2.parent_id = r3.region_id WHERE r1.region_id = $rid" );
		$data = current ( $data );
		$tops [] = array (
				"rid" => $data ['r1'],
				"name" => $data ['n1'],
				'end' => true 
		);
		$tops [] = array (
				"rid" => $data ['r2'],
				"name" => $data ['n2'] 
		);
		if ($data ['r2'] == $this->renshou) {
			$tops = array_reverse ( $tops );
			$this->assign ( 'top', $tops );
			return;
		}
		$tops [] = array (
				"rid" => $data ['r3'],
				"name" => $data ['n3'],
				'end' => true 
		);
		$tops = array_reverse ( $tops );
		$this->assign ( 'top', $tops );
	}
	/**
	 * 获得下级地区列表
	 *
	 * @param unknown $rid        	
	 * @return Ambigous <boolean, unknown>
	 */
	function _get_regions($rid) {
		// $cache_server = & cache_server ();
		// $data = $cache_server->get ( 'regions_pid_' . $rid );
		// if ($data === false) {
		$data = $this->m->db->getAll ( "select r.region_id,r.region_name,rd.if_show from ecm_region r left join ecm_region_description rd on rd.region_id =r.region_id where r.parent_id={$rid} and rd.if_show=1" );
		// 缓存一天
		// if ($data) {
		// $cache_server->set ( 'regions_pid_' . $rid, $data, 24 * 60 * 60 );
		// }
		// }
		return $data;
	}
}

?>
