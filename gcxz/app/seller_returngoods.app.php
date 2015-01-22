<?php

/* 卖家退货流程 */
class seller_returngoodsApp extends MemberbaseApp
{
    private $m;

    function __construct($params, $db)
    {
        $this->m = m ( 'returngoods' );
        parent::__construct ();
    }

    function index()
    {
        /* 获取退款列表 */
        $this->_get_return ();
        
        /* 当前位置 */
        // $this->_curlocal ( LANG::get ( 'member_center' ), 'index.php?app=member', LANG::get ( 'order_manage' ), 'index.php?app=seller_order', LANG::get ( 'order_list' ) );
        
        /* 当前用户中心菜单 */
        $type = (isset ( $_GET['type'] ) && $_GET['type'] != '') ? trim ( $_GET['type'] ) : 'all';
        $this->_curitem ( 'return_manage' );
        $this->_curmenu ( $type );
        $this->import_resource ( array (
                'script' => array (
                        array (
                                'path' => 'dialog/dialog.js',
                                'attr' => 'id="dialog_js"' 
                        ),
                        array (
                                'path' => 'jquery.ui/jquery.ui.js',
                                'attr' => '' 
                        ),
                        array (
                                'path' => 'jquery.ui/i18n/' . i18n_code () . '.js',
                                'attr' => '' 
                        ),
                        array (
                                'path' => 'jquery.plugins/jquery.validate.js',
                                'attr' => '' 
                        ) 
                ),
                'style' => 'jquery.ui/themes/ui-lightness/jquery.ui.css' 
        ) );
        $this->_config_seo ( 'title', Lang::get ( 'member_center' ) . ' - ' . Lang::get ( 'return_manage' ) );
        /* 显示订单列表 */
        $this->display ( 'seller_return.index.html' );
    }

    /**
     * 退货单详细页
     */
    function detail()
    {
        $id = intval ( $_GET['id'] );
        if (!$id) {
            $this->show_warning ( '不存在的记录' );
            return;
        }
        $sql = "SELECT id, rg.return_order_sn, o.order_sn, rg.buyer_name, rg.quantity, rg.status, og.goods_name, og.goods_image, og.price, og.goods_id, rg.remark, rg.log, rg.ctime,rg.status FROM ecm_return_goods rg LEFT JOIN ecm_order_goods og ON rg.order_goods_id = og.rec_id LEFT JOIN ecm_order o ON rg.order_id = o.order_id WHERE rg.id=$id and rg.seller_id={$this->visitor->get ( 'manage_store' )}  limit 1";
        $detail = $this->m->getAll ( $sql );
        if ($detail) {
            $detail = $detail[0];
            $detail['status_text'] = $this->m->convert_status ( $detail['status'] );
            $detail['log'] = unserialize ( $detail['log'] );
        }
        $this->_config_seo ( 'title', Lang::get ( 'member_center' ) . ' - ' . '退货详情' );
        $this->assign ( 'detail', $detail );
        $this->display ( 'seller_returngoods.detail.html' );
    }

    function _get_return()
    {
        $page = $this->_get_page ();
        $seller_id = $this->visitor->get ( 'manage_store' );
        $condition = $this->_get_condition ();
        $sql = "SELECT id, return_order_sn,rg.source_type, rg.buyer_name, rg.quantity, rg.status, og.goods_name, og.price, og.goods_id FROM ecm_return_goods rg LEFT JOIN ecm_order_goods og ON rg.order_goods_id = og.rec_id  where {$condition} order by rg.ctime desc LIMIT {$page['limit']}";
        $returns = $this->m->getAll ( $sql );
        foreach ( $returns as &$v ) {
            $v['status_text'] = $this->m->convert_status ( $v['status'] );
        }
        $page['item_count'] = $this->m->getOne ( "select count(1) from ecm_return_goods rg WHERE {$condition}" );
        $this->_format_page ( $page );
        $this->assign ( 'page_info', $page );
        $this->assign ( 'returns', $returns );
    }

    /**
     * 卖家审核通过
     */
    function audit()
    {
        $id = intval ( $_POST['id'] );
        if (!$id) {
            $this->show_warning ( '不存在的退款单' );
            return;
        }
        $seller_id = $this->visitor->get ( 'manage_store' );
        $param['id'] = $id;
        $param['audit'] = intval ( $_POST['audit'] );
        $param['seller_id'] =$seller_id;
        $param['user_id'] = $this->visitor->info['user_id'];
        $param['user_name'] = $this->visitor->info['user_name'];
        $param['remark'] = t ( $_POST['remark'] );
        $ret = $this->m->audit ( $param );
        if ($ret) {
            $this->show_message ( '审核成功' );
        } else {
            $this->show_warning ( $this->m->getError () );
        }
    }

    /**
     * 卖家验货
     */
    function examine()
    {
        if (IS_POST) {
            $id = intval ( $_POST['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退货单' );
                return;
            }
            $seller_id = $this->visitor->get ( 'manage_store' );
            $param['id'] = $id;
            $param['seller_id'] = $seller_id;
            $param['user_id'] = $this->visitor->info['user_id'];
            $param['examine'] = t ( $_POST['examine'] );
            $param['user_name'] = $this->visitor->info['user_name'];
            $param['remark'] = t ( $_POST['remark'] );
            $ret = $this->m->examine ( $param );
            if ($ret) {
                $this->pop_warning ( 'ok', 'seller_return_examine' );
            } else {
                $this->pop_warning ( $this->m->getError () );
            }
        } else {
            $id = intval ( $_GET['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退货单' );
                return;
            }
            $detail = $this->m->get ( array (
                    'conditions' => 'id=' . $id,
                    'fields' => 'return_order_sn' 
            ) );
            $this->assign ( 'detail', $detail );
            $this->display ( 'seller_return.examine.html' );
        }
    }

    /**
     * 卖家收货
     */
    function receive()
    {
        if (IS_POST) {
            $id = intval ( $_POST['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退货单' );
                return;
            }
            $seller_id = $this->visitor->get ( 'manage_store' );
            $param['id'] = $id;
            $param['seller_id'] = $seller_id;
            $param['user_id'] = $this->visitor->info['user_id'];
            $param['user_name'] = $this->visitor->info['user_name'];
            $param['remark'] = t ( $_POST['remark'] );
            $ret = $this->m->receive ( $param );
            if ($ret) {
                $this->pop_warning ( 'ok', 'seller_return_receive' );
            } else {
                $this->pop_warning ( $this->m->getError () );
            }
        } else {
            
            $id = intval ( $_GET['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退货单' );
                return;
            }
            $detail = $this->m->get ( array (
                    'conditions' => 'id=' . $id,
                    'fields' => 'return_order_sn' 
            ) );
            $this->assign ( 'detail', $detail );
            $this->display ( 'seller_return.receive.html' );
        }
    }

    function _get_condition()
    {
        !$_GET['type'] && $_GET['type'] = 'all';
        switch ($_GET['type']) {
            /* 待审核 */
            case 'appled' :
                $status = RETURN_APPLY;
                break;
            /* 待收货 */
            case 'mailed' :
                $status = RETURN_MAILED;
                $source_type = 1;
                break;
            /* 待验货 */
            case 'received' :
                $status = RETURN_RECEIPTED;
                $source_type = 1;
                break;
            case 'other' :
                $status = RETURN_AUDITD . ',' . RETURN_EXAMINE_OK . ',' . RETURN_EXAMINE_REJECT . ',' . RETURN_CLOSED . ',' . RETURN_COMPLETED;
                break;
            case 'all' :
            default :
                $status = '';
                break;
        }
        $seller_id = $this->visitor->get ( 'manage_store' );
        $condition = ' rg.seller_id = ' . $seller_id . ($status !== '' ? ' AND rg. STATUS in (' . $status . ')' : '') . (isset ( $source_type ) ? ' and rg.source_type=' . $source_type : '');
        $condition .= $this->_get_query_conditions ( array (
                array ( // 按买家名称搜索
                        'field' => 'rg.buyer_name',
                        'name' => 'buyer_name',
                        'equal' => 'LIKE' 
                ),
                array ( // 按下单时间搜索,起始时间
                        'field' => 'rg.ctime',
                        'name' => 'ctime_from',
                        'equal' => '>=',
                        'handler' => 'gmstr2time' 
                ),
                array ( // 按下单时间搜索,结束时间
                        'field' => 'rg.ctime',
                        'name' => 'ctime_to',
                        'equal' => '<=',
                        'handler' => 'gmstr2time_end' 
                ),
                array ( // 按订单号
                        'field' => 'rg.return_order_sn',
                        'name' => 'return_order_sn' 
                ) 
        ) );
        $this->assign ( 'type', $_GET['type'] );
        return $condition;
    }
    /* 三级菜单 */
    function _get_member_submenu()
    {
        $array = array (
                array (
                        'name' => 'all',
                        'text' => '全部',
                        'url' => 'index.php?app=seller_returngoods&amp;type=all' 
                ),
                array (
                        'name' => 'appled',
                        'text' => '待审核',
                        'url' => 'index.php?app=seller_returngoods&amp;type=appled' 
                ),
                array (
                        'name' => 'mailed',
                        'text' => '待收货',
                        'url' => 'index.php?app=seller_returngoods&amp;type=mailed' 
                ),
                array (
                        'name' => 'received',
                        'text' => '待验货',
                        'url' => 'index.php?app=seller_returngoods&amp;type=received' 
                ),
                array (
                        'name' => 'other',
                        'text' => '其他',
                        'url' => 'index.php?app=seller_returngoods&amp;type=other' 
                ) 
        );
        return $array;
    }
}

?>
