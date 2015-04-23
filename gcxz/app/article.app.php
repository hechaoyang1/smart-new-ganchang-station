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
		$acategory = $this->_acategory_mod->db->getAll ( "SELECT ag.cate_id, ag.cate_name, a.title, a.article_id FROM ecm_article a LEFT JOIN ecm_acategory ag ON a.cate_id = ag.cate_id JOIN ecm_acategory agp ON ag.parent_id = agp.cate_id AND agp.`code` = 'help' WHERE a.if_show ORDER BY ag.sort_order, ag.cate_id, a.sort_order" );
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
	function system()
	{
		$code = empty($_GET['code']) ? '' : trim($_GET['code']);
		if (!$code)
		{
			$this->show_warning('no_such_article');
			return;
		}
		$article = $this->_article_mod->get("code='" . $code . "'");
		if (!$article)
		{
			$this->show_warning('no_such_article');
			return;
		}
		if ($article['link']){ //外链文章跳转
			header("HTTP/1.1 301 Moved Permanently");
			header('location:'.$article['link']);
			return;
		}
	
		/*当前位置*/
		$curlocal[] =array('text' => $article['title']);
		$this->_curlocal($curlocal);
		/*文章分类*/
		$acategories = $this->_get_acategory('');
		/* 新文章 */
		$new = $this->_get_article('new');
		$new_articles = $new['articles'];
		$this->assign('acategories', $acategories);
		$this->assign('new_articles', $new_articles);
		$this->assign('article', $article);
	
		$this->_config_seo('title', $article['title'] . ' - ' . Conf::get('site_title'));
		$this->display('article.view.html');
	
	}
	
	function _get_article_curlocal($cate_id)
	{
		$parents = array();
		if ($cate_id)
		{
			$acategory_mod = &m('acategory');
			$acategory_mod->get_parents($parents, $cate_id);
		}
		foreach ($parents as $category)
		{
			$curlocal[] = array('text' => $category['cate_name'], 'ACC' => $category['code'], 'url' => 'index.php?app=article&amp;cate_id=' . $category['cate_id']);
		}
		return $curlocal;
	}
	function _get_acategory($cate_id)
	{
		$acategories = $this->_acategory_mod->get_list($cate_id);
		if ($acategories){
			unset($acategories[$this->_ACC[ACC_SYSTEM]]);
			return $acategories;
		}
		else
		{
			$parent = $this->_acategory_mod->get($cate_id);
			if (isset($parent['parent_id']))
			{
				return $this->_get_acategory($parent['parent_id']);
			}
		}
	}
	function _get_article($type='')
	{
		$conditions = '';
		$per = '';
		switch ($type)
		{
			case 'new' : $sort_order = 'add_time DESC,sort_order ASC';
			$per=5;
			break;
			case 'all' : $sort_order = 'sort_order ASC,add_time DESC';
			$per=10;
			break;
		}
		$page = $this->_get_page($per);   //获取分页信息
		!empty($this->_cate_ids)&& $conditions = ' AND cate_id ' . db_create_in($this->_cate_ids);
		$articles = $this->_article_mod->find(array(
				'conditions'  => 'if_show=1 AND store_id=0 AND code = ""' . $conditions,
				'limit'   => $page['limit'],
				'order'   => $sort_order,
				'count'   => true   //允许统计
		)); //找出所有符合条件的文章
		$page['item_count'] = $this->_article_mod->getCount();
		foreach ($articles as $key => $article)
		{
			$articles[$key]['target'] = $article[link] ? '_blank' : '_self';
		}
		return array('page'=>$page, 'articles'=>$articles);
	}
}

?>
