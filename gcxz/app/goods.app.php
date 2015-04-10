<?php

/* 商品 */
class GoodsApp extends StorebaseApp
{
    var $_goods_mod;
    function __construct()
    {
        $this->GoodsApp();
    }
    function GoodsApp()
    {
        parent::__construct();
        $this->_goods_mod =& m('goods');
    }

    function index()
    {
        /* 参数 id */
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }

        /* 可缓存数据 */
        $data = $this->_get_common_info($id);
        if ($data === false)
        {
            return;
        }
        else if($data['goods']['source_type'] == 3)
        {
            header("Location: ".$data['goods']['foreign_url']);
            exit;
        }
        else
        {
            $this->_assign_common_info($data);
        }

        /* 更新浏览次数 */
        $this->_update_views($id);

        //是否开启验证码
        if (Conf::get('captcha_status.goodsqa'))
        {
            $this->assign('captcha', 1);
        }

        // 商品分类
        $this->assign('top_category', m('gcategory')->get_top_category());
        
        $this->assign('guest_comment_enable', Conf::get('guest_comment'));
        $this->import_resource(array(
        		'style' => 'res:css/goodsDetailed.css',
        		'script' => 'search_goods.js',
        ));
        $this->display('goods.index.html');
    }

    /* 商品评论 */
    function comments()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }

        $data = $this->_get_common_info($id);
        if ($data === false)
        {
            return;
        }
        else
        {
            $this->_assign_common_info($data);
        }

        /* 赋值商品评论 */
        $data = $this->_get_goods_comment($id, 10);
        $this->_assign_goods_comment($data);

        $this->display('goods.comments.html');
    }

    /* 销售记录 */
    function saleslog()
    {
        $id = empty($_GET['id']) ? 0 : intval($_GET['id']);
        if (!$id)
        {
            $this->show_warning('Hacking Attempt');
            return;
        }

        $data = $this->_get_common_info($id);
        if ($data === false)
        {
            return;
        }
        else
        {
            $this->_assign_common_info($data);
        }

        /* 赋值销售记录 */
        $data = $this->_get_sales_log($id, 10);
        $this->_assign_sales_log($data);

        $this->display('goods.saleslog.html');
    }
    function qa()
    {
        $goods_qa =& m('goodsqa');
         $id = intval($_GET['id']);
         if (!$id)
         {
            $this->show_warning('Hacking Attempt');
            return;
         }
        if(!IS_POST)
        {
            $data = $this->_get_common_info($id);
            if ($data === false)
            {
                return;
            }
            else
            {
                $this->_assign_common_info($data);
            }
            $data = $this->_get_goods_qa($id, 10);
            $this->_assign_goods_qa($data);

            //是否开启验证码
            if (Conf::get('captcha_status.goodsqa'))
            {
                $this->assign('captcha', 1);
            }
            $this->assign('guest_comment_enable', Conf::get('guest_comment'));
            /*赋值产品咨询*/
            $this->display('goods.qa.html');
        }
        else
        {
            /* 不允许游客评论 */
            if (!Conf::get('guest_comment') && !$this->visitor->has_login)
            {
                $this->show_warning('guest_comment_disabled');

                return;
            }
            $content = (isset($_POST['content'])) ? trim($_POST['content']) : '';
            //$type = (isset($_POST['type'])) ? trim($_POST['type']) : '';
            $email = (isset($_POST['email'])) ? trim($_POST['email']) : '';
            $hide_name = (isset($_POST['hide_name'])) ? trim($_POST['hide_name']) : '';
            if (empty($content))
            {
                $this->show_warning('content_not_null');
                return;
            }
            //对验证码和邮件进行判断

            if (Conf::get('captcha_status.goodsqa'))
            {
                if (base64_decode($_SESSION['captcha']) != strtolower($_POST['captcha']))
                {
                    $this->show_warning('captcha_failed');
                    return;
                }
            }
            if (!empty($email) && !is_email($email))
            {
                $this->show_warning('email_not_correct');
                return;
            }
            $user_id = empty($hide_name) ? $_SESSION['user_info']['user_id'] : 0;
            $conditions = 'g.goods_id ='.$id;
            $goods_mod = & m('goods');
            $ids = $goods_mod->get(array(
                'fields' => 'store_id,goods_name',
                'conditions' => $conditions
            ));
            extract($ids);
            $data = array(
                'question_content' => $content,
                'type' => 'goods',
                'item_id' => $id,
                'item_name' => addslashes($goods_name),
                'store_id' => $store_id,
                'email' => $email,
                'user_id' => $user_id,
                'time_post' => gmtime(),
            );
            if ($goods_qa->add($data))
            {
                header("Location: index.php?app=goods&act=qa&id={$id}#module\n");
                exit;
            }
            else
            {
                $this->show_warning('post_fail');
                exit;
            }
        }
    }

    /**
     * 取得公共信息
     *
     * @param   int     $id
     * @return  false   失败
     *          array   成功
     */
    function _get_common_info($id)
    {
        $cache_server =& cache_server();
        $key = 'page_of_goods_' . $id;
        $data = $cache_server->get($key);
        $cached = true;
        if ($data === false)
        {
            $cached = false;
            $data = array('id' => $id);

            /* 商品信息 */
            $goods = $this->_goods_mod->get_info($id);
            if (!$goods || $goods['if_show'] == 0 || $goods['closed'] == 1 || $goods['state'] != 1)
            {
                $this->show_warning('goods_not_exist');
                return false;
            }
            $goods['tags'] = $goods['tags'] ? explode(',', trim($goods['tags'], ',')) : array();

            $data['goods'] = $goods;

            /* 店铺信息 */
            if (!$goods['store_id'])
            {
                $this->show_warning('store of goods is empty');
                return false;
            }
            $this->set_store($goods['store_id']);
            $data['store_data'] = $this->get_store_data();

            /* 当前位置 */
            $data['cur_local'] = $this->_get_curlocal($goods['cate_id']);
            $data['goods']['related_info'] = $this->_get_related_objects($data['goods']['tags']);
            /* 分享链接 */
            $data['share'] = $this->_get_share($goods);
            
            $cache_server->set($key, $data, 1800);
        }
        if ($cached)
        {
            $this->set_store($data['goods']['store_id']);
        }

        return $data;
    }

    function _get_related_objects($tags)
    {
        if (empty($tags))
        {
            return array();
        }
        $tag = $tags[array_rand($tags)];
        $ms =& ms();

        return $ms->tag_get($tag);
    }

    /* 赋值公共信息 */
    function _assign_common_info($data)
    {
        /* 商品信息 */
        $goods = $data['goods'];
        $this->assign('goods', $goods);
        $this->assign('sales_info', sprintf(LANG::get('sales'), $goods['sales'] ? $goods['sales'] : 0));
        $this->assign('comments', sprintf(LANG::get('comments'), $goods['comments'] ? $goods['comments'] : 0));

        /* 店铺信息 */
        $this->assign('store', $data['store_data']);

        /* 浏览历史 */
        $this->assign('goods_history', $this->_get_goods_history($data['id']));

        /* 默认图片 */
        $this->assign('default_image', Conf::get('default_goods_image'));

        /* 当前位置 */
        $this->_curlocal($data['cur_local']);

        /* 配置seo信息 */
        $this->_config_seo($this->_get_seo_info($data['goods']));

        /* 商品分享 */
        $this->assign('share', $data['share']);

        // 获取热卖商品
        $data['hotGoods'] = $this->_get_hot_goods($goods['store_id'], 3);
        //商品热卖
        $this->assign('hotGoods', $data['hotGoods']);

        // 商品全部分类
        $gcategory_list=$this->_get_site_gcategory();
        $this->assign('gcategory_list',$gcategory_list);
        /* 当前位置 */
        $this->_curlocal ( $this->_get_goods_curlocal ( $data['goods']['cate_id'] ) );
        
        /* 赋值销售记录 */
//         $this->_assign_sales_log($this->_get_sales_log($_GET['id'], 50));

        /* 赋值月销量*/
//         $this->assign('sales_num',$this->_get_sales_log_num($_GET['id']));
        
        /* 赋值商品评论数量*/
        $this->assign('comments_num',$this->_get_goods_comment_num($_GET['id']));
        
        /* 赋值商品评论 */
        $this->_assign_goods_comment($this->_get_goods_comment($_GET['id'], 1));
        $this->assign('id', $_GET['id']);
        
        //赋值购物车数量
        $this->assign('cart_num', $this->get_cart_num());
        
        $this->import_resource(array(
            'script' => 'jquery.jqzoom.js',
            'style' => 'res:jqzoom.css'
        ));
    }

    /* 取得浏览历史 */
    function _get_goods_history($id, $num = 9)
    {
        $goods_list = array();
        $goods_ids  = ecm_getcookie('goodsBrowseHistory');
        $goods_ids  = $goods_ids ? explode(',', $goods_ids) : array();
        if ($goods_ids)
        {
            $rows = $this->_goods_mod->find(array(
                'conditions' => $goods_ids,
                'fields'     => 'goods_name,default_image',
            ));
            foreach ($goods_ids as $goods_id)
            {
                if (isset($rows[$goods_id]))
                {
                    empty($rows[$goods_id]['default_image']) && $rows[$goods_id]['default_image'] = Conf::get('default_goods_image');
                    $goods_list[] = $rows[$goods_id];
                }
            }
        }
        $goods_ids[] = $id;
        if (count($goods_ids) > $num)
        {
            unset($goods_ids[0]);
        }
        ecm_setcookie('goodsBrowseHistory', join(',', array_unique($goods_ids)));

        return $goods_list;
    }
    
    function _get_sales_log_num($goods_id){
    	$order_goods_mod =& m('ordergoods');
    	$sales_list = $order_goods_mod->get(array(
    			'conditions' => "goods_id = '$goods_id' AND status = '" . ORDER_FINISHED . "'",
    			'join'  => 'belongs_to_order',
    			'fields'=> 'count(buyer_id) count',
    	));
    	
    	return $sales_list['count'];
    }

    /* 取得销售记录 */
    function _get_sales_log($goods_id, $num_per_page)
    {
        $data = array();

        $page = $this->_get_page($num_per_page);
        $order_goods_mod =& m('ordergoods');
        $sales_list = $order_goods_mod->find(array(
            'conditions' => "goods_id = '$goods_id' AND status = '" . ORDER_FINISHED . "'",
            'join'  => 'belongs_to_order',
            'fields'=> 'buyer_id, buyer_name, add_time, anonymous, goods_id, specification, price, quantity, evaluation',
            'count' => true,
            'order' => 'add_time desc',
            'limit' => $page['limit'],
        ));
        $data['sales_list'] = $sales_list;

        $page['item_count'] = $order_goods_mod->getCount();
        $this->_format_page($page);
        $data['page_info'] = $page;
        $data['more_sales'] = $page['item_count'] > $num_per_page;

        return $data;
    }
    
    /**
     * ajax获取商品评论
     */
    function ajax_goods_sales_log(){
    	$page = $_GET['page']?$_GET['page']:1;
    	$goods_id = $_GET['goods_id'];
    
    	$page = $this->_get_page(10);
        $order_goods_mod =& m('ordergoods');
        $sales_list = $order_goods_mod->find(array(
            'conditions' => "goods_id = '$goods_id' AND status = '" . ORDER_FINISHED . "'",
            'join'  => 'belongs_to_order',
            'fields'=> 'buyer_id, buyer_name, add_time, anonymous, goods_id, specification, price, quantity, evaluation',
            'count' => true,
            'order' => 'add_time desc',
            'limit' => $page['limit'],
        ));
    	$page['item_count'] = $order_goods_mod->getCount();
    	$this->_format_page($page);
    
    	$this->assign('sales_list',$sales_list);
    	$this->assign('page_info',$page);
    	$this->display('goods.saleslog.html');
    }
    

    /* 赋值销售记录 */
    function _assign_sales_log($data)
    {
        $this->assign('sales_list', $data['sales_list']);
        $this->assign('page_info',  $data['page_info']);
        $this->assign('more_sales', $data['more_sales']);
    }

    function _get_goods_comment_num($goods_id){
    	$order_goods_mod =& m('ordergoods');
    	$comments = $order_goods_mod->get(array(
    			'conditions' => "goods_id = '$goods_id' AND evaluation_status = '1'",
    			'join'  => 'belongs_to_order',
    			'fields'=> 'count(buyer_id) count',
    	));
    	return $comments['count'];
    }
    /* 取得商品评论 */
    function _get_goods_comment($goods_id, $num_per_page)
    {
        $data = array();

        $page = $this->_get_page($num_per_page);
        $order_goods_mod =& m('ordergoods');
        $comments = $order_goods_mod->find(array(
            'conditions' => "goods_id = '$goods_id' AND evaluation_status = '1'",
            'join'  => 'belongs_to_order',
            'fields'=> 'buyer_id, buyer_name, anonymous, FROM_UNIXTIME(evaluation_time) time, comment, evaluation',
            'count' => true,
            'order' => 'evaluation_time desc',
            'limit' => $page['limit'],
        ));
        $data['comments'] = $comments;

        $page['item_count'] = $order_goods_mod->getCount();
        $this->_format_page($page);
        $data['page_info'] = $page;
        $data['more_comments'] = $page['item_count'] > $num_per_page;
		if ($page ['item_count'] > 0) {
			
			// 获取好评率 中评率 和 差评率
			$sql = 'SELECT
					count(0) count
				FROM
					`ecm_order` o
				RIGHT JOIN ecm_order_goods og ON o.order_id = og.order_id
				WHERE
					og.goods_id = ' . $goods_id . '
				AND o.evaluation_status = 1 AND og.evaluation=';
			$eval_good = $order_goods_mod->getAll ( $sql . '3' );
			$eval_normal = $order_goods_mod->getAll ( $sql . '2' );
			$eval_bad = $order_goods_mod->getAll ( $sql . '1' );
			$data ['eval_good'] = intval ( $eval_good [0] ['count'] );
			$data ['eval_normal'] = intval ($eval_normal [0] ['count']);
			$data ['eval_bad'] = intval ($eval_bad [0] ['count']);
			$data ['good_percent'] = ceil ( $data ['eval_good'] * 100 / $page ['item_count'] );
			$data ['normal_percent'] = ceil ( $data ['eval_normal'] * 100 / $page ['item_count'] );
			$data ['bad_percent'] = $data ['eval_bad'] > 0 ? (100 - $data ['good_percent'] - $data ['normal_percent']) : 0;
		} else {
			$data ['eval_good'] = 0;
			$data ['eval_normal'] = 0;
			$data ['eval_bad'] = 0;
			$data ['good_percent'] = 0;
			$data ['normal_percent'] = 0;
			$data ['bad_percent'] = 0;
		}
		return $data;
    }
    
    /**
     * ajax获取商品评论
     */
    function ajax_goods_comments(){
    	 $evaluation = $_GET['evaluation'];
    	 $page = $_GET['page']?$_GET['page']:1;
    	 $goods_id = $_GET['goods_id'];
    	 
    	 $page = $this->_get_page(10);
    	 $order_goods_mod =& m('ordergoods');
    	 if ($evaluation == 0) {
    	 	$condition = 'goods_id = '.$goods_id.' AND evaluation_status = 1 AND evaluation in (1,2,3)';
    	 } else {
    	 	$condition =  'goods_id = '.$goods_id.' AND evaluation_status = 1 AND evaluation ='. $evaluation;
    	 }
    	 $comments = $order_goods_mod->find(array(
    	 		'conditions' => $condition,
    	 		'join'  => 'belongs_to_order',
    	 		'fields'=> 'buyer_id, buyer_name, anonymous, FROM_UNIXTIME(evaluation_time) time, comment, evaluation',
    	 		'count' => true,
    	 		'order' => 'evaluation_time desc',
    	 		'limit' => $page['limit'],
    	 ));
    	 $page['item_count'] = $order_goods_mod->getCount();
    	 $this->_format_page($page);
    	 
    	 $this->assign('goods_comments',$comments);
    	 $this->assign('page_info',$page);
    	 $this->display('goods.comments.html');
    }

    /* 赋值商品评论 */
    function _assign_goods_comment($data)
    {
        $this->assign('goods_comments', $data['comments']);
        $this->assign('page_info',      $data['page_info']);
        $this->assign('more_comments',  $data['more_comments']);
        $this->assign('good_percent', $data['good_percent']);
        $this->assign('normal_percent', $data['normal_percent']);
        $this->assign('bad_percent', $data['bad_percent']);
        $this->assign('eval_good', $data['eval_good']);
        $this->assign('eval_normal', $data['eval_normal']);
        $this->assign('eval_bad', $data['eval_bad']);
    }

    /* 取得商品咨询 */
    function _get_goods_qa($goods_id,$num_per_page)
    {
        $page = $this->_get_page($num_per_page);
        $goods_qa = & m('goodsqa');
        $qa_info = $goods_qa->find(array(
            'join' => 'belongs_to_user',
            'fields' => 'member.user_name,question_content,reply_content,time_post,time_reply',
            'conditions' => '1 = 1 AND item_id = '.$goods_id . " AND type = 'goods'",
            'limit' => $page['limit'],
            'order' =>'time_post desc',
            'count' => true
        ));
        $page['item_count'] = $goods_qa->getCount();
        $this->_format_page($page);

        //如果登陆，则查出email
        if (!empty($_SESSION['user_info']))
        {
            $user_mod = & m('member');
            $user_info = $user_mod->get(array(
                'fields' => 'email',
                'conditions' => '1=1 AND user_id = '.$_SESSION['user_info']['user_id']
            ));
            extract($user_info);
        }

        return array(
            'email' => $email,
            'page_info' => $page,
            'qa_info' => $qa_info,
        );
    }
	/**
	 * ajax获取产品咨询
	 */
	function ajax_goods_qa() {
		$data = $this->_get_goods_qa ( $_GET ['goods_id'], 10 );
		$this->_assign_goods_qa ( $data );
		/*赋值产品咨询*/
		$this->display('goods.qa.html');
	}
  

    /* 赋值商品咨询 */
    function _assign_goods_qa($data)
    {
        $this->assign('email',      $data['email']);
        $this->assign('page_info',  $data['page_info']);
        $this->assign('qa_info',    $data['qa_info']);
    }

    /* 更新浏览次数 */
    function _update_views($id)
    {
        $goodsstat_mod =& m('goodsstatistics');
        $goodsstat_mod->edit($id, "views = views + 1");
    }

    /**
     * 取得当前位置
     *
     * @param int $cate_id 分类id
     */
    function _get_curlocal($cate_id)
    {
        $parents = array();
        if ($cate_id)
        {
            $gcategory_mod =& bm('gcategory');
            $parents = $gcategory_mod->get_ancestor($cate_id, true);
        }

        $curlocal = array(
            array('text' => LANG::get('all_categories'), 'url' => url('app=category')),
        );
        foreach ($parents as $category)
        {
            $curlocal[] = array('text' => $category['cate_name'], 'url' => url('app=search&cate_id=' . $category['cate_id']));
        }
        $curlocal[] = array('text' => LANG::get('goods_detail'));

        return $curlocal;
    }

    function _get_share($goods)
    {
        $m_share = &af('share');
        $shares = $m_share->getAll();
        $shares = array_msort($shares, array('sort_order' => SORT_ASC));
        $goods_name = ecm_iconv(CHARSET, 'utf-8', $goods['goods_name']);
        $goods_url = urlencode(SITE_URL . '/' . str_replace('&amp;', '&', url('app=goods&id=' . $goods['goods_id'])));
        $site_title = ecm_iconv(CHARSET, 'utf-8', Conf::get('site_title'));
        $share_title = urlencode($goods_name . '-' . $site_title);
        foreach ($shares as $share_id => $share)
        {
            $shares[$share_id]['link'] = str_replace(
                array('{$link}', '{$title}'),
                array($goods_url, $share_title),
                $share['link']);
        }
        return $shares;
    }
    
    function _get_seo_info($data)
    {
        $seo_info = $keywords = array();
        $seo_info['title'] = $data['goods_name'] . ' - ' . Conf::get('site_title');        
        $keywords = array(
            $data['brand'],
            $data['goods_name'],
            $data['cate_name']
        );
        $seo_info['keywords'] = implode(',', array_merge($keywords, $data['tags']));        
        $seo_info['description'] = sub_str(strip_tags($data['description']), 10, true);
        return $seo_info;
    }
    /**
     * 获取店铺热卖商品
     * @param unknown $id
     */
    function _get_hot_goods($id,$limit){
    	$sql = 'SELECT
					g.goods_id,
					g.price,
					g.goods_name,
					g.default_image
				FROM
					ecm_goods g
				LEFT JOIN ecm_goods_statistics gs ON g.goods_id = gs.goods_id
				WHERE
					g.store_id = '.$id.' AND g.if_show=1 AND closed = 0 
				ORDER BY
					gs.sales DESC
				LIMIT '.$limit;
    	$result = $this->_goods_mod->db->getAll($sql);
    	return $result;
    }
    function _get_goods_curlocal($cate_id)
    {
    	$parents = array();
    	if ($cate_id)
    	{
    		$gcategory_mod =& bm('gcategory');
    		$parents = $gcategory_mod->get_ancestor($cate_id, true);
    	}
    
    	$curlocal = array(
    			array('text' => LANG::get('all_categories'), 'url' => "javascript:dropParam('cate_id')"),
    	);
    	foreach ($parents as $category)
    	{
    		$curlocal[] = array('text' => $category['cate_name'], 'url' => "javascript:replaceParam('cate_id', '" . $category['cate_id'] . "')");
    	}
    	unset($curlocal[count($curlocal) - 1]['url']);
    
    	return $curlocal;
    }
    
    function  getComment(){
		$eval = $_GET ['eval'];
		$goods_id = $_GET ['goods_id'];
		$order_goods_mod = & m ( 'ordergoods' );
		if ($eval == 0) {
			$condition = 'goods_id = '.$goods_id.' AND evaluation_status = 1 AND evaluation in (1,2,3)';
		} else {
			$condition =  'goods_id = '.$goods_id.' AND evaluation_status = 1 AND evaluation ='. $eval;
		}
		$comments = $order_goods_mod->find ( array (
				'conditions' =>$condition,
				'join' => 'belongs_to_order',
				'fields' => 'buyer_id, buyer_name, anonymous, FROM_UNIXTIME(evaluation_time) time, comment, evaluation',
				'order' => 'evaluation_time desc',
				'limit' => 50 
		) );
		
		$this->json_result ( array (
				'comments' => array_values($comments) 
		) );
    	
    }
}

?>
