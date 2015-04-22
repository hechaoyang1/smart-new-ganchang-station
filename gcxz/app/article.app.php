<?php
class ArticleApp extends MallbaseApp {
	var $_article_mod;
	var $_acategory_mod;
	var $_ACC; // 系统文章cate_id数据
	var $_cate_ids; // 当前分类及子孙分类cate_id
	function __construct() {
		$this->ArticleApp ();
	}
	function ArticleApp() {
		parent::__construct ();
		$this->_article_mod = &m ( 'article' );
		$this->_acategory_mod = &m ( 'acategory' );
	}
	function index() {
		$acategory = $this->_acategory_mod->db->getAll ( 'SELECT ag.cate_id, ag.cate_name, a.title,a.article_id FROM ecm_article a LEFT JOIN ecm_acategory ag ON a.cate_id = ag.cate_id WHERE a.if_show AND (ag.`code` is null or ag.`code` =\'\') ORDER BY ag.sort_order, ag.cate_id, a.sort_order' );
// 		$aid = intval ( $_GET ['aid'] );
// 		if (! $aid) {
// 			$first=current($acategory);
// 			$aid=$first['article_id'];
// 		}
// 		$article = $this->_article_mod->db->getAll ( "SELECT article_id, content, add_time FROM ecm_article WHERE article_id = $aid AND if_show = 1 limit 1" );
// 		$article = current ( $article );
// 		$this->assign ( 'article', $article );
		// 分类分组
		$as = array ();
		foreach ( $acategory as $a ) {
			$as [$a ['cate_name']] [] = $a;
		}
		$this->import_resource ( array (
				'style' => 'res:css/article.css',
				'script' => 'res:js/article.js' 
		) );
		$this->assign ( 'tab', 'help' );
		$this->assign ( 'acategory', $as );
		$this->display ( "article.index.html" );
	}
	function view() {
		$aid = intval ( $_GET ['aid'] );
		$article = $this->_article_mod->db->getAll ( "SELECT article_id, content, add_time FROM ecm_article WHERE article_id = $aid AND if_show = 1 limit 1" );
		$article = current ( $article );
		if ($article) {
			echo $article ['content'];
		} else {
			echo 0;
		}
	}
}

?>
