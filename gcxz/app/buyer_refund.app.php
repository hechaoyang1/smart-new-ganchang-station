<?php

/* 退款流程 */
class Buyer_refundApp extends MemberbaseApp
{
    private $m;

    function __construct($params, $db)
    {
        $this->m = m ( 'refund' );
        parent::__construct ();
    }

    function index()
    {
        /* 获取退款列表 */
        $this->_get_refunds ();
        
        /* 当前位置 */
        // $this->_curlocal ( LANG::get ( 'member_center' ), 'index.php?app=member', LANG::get ( 'order_manage' ), 'index.php?app=seller_order', LANG::get ( 'order_list' ) );
        
        /* 当前用户中心菜单 */
        $type = (isset ( $_GET['type'] ) && $_GET['type'] != '') ? trim ( $_GET['type'] ) : 'refund';
        $this->_curitem ( 'my_rights' );
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
        $this->_config_seo ( 'title', Lang::get ( 'member_center' ) . ' - 退款维权' );
        /* 显示订单列表 */
        $this->display ( 'buyer_refund.list.html' );
    }

    /**
     * 退款单详细页
     */
    function detail()
    {
        $id = intval ( $_GET['id'] );
        if (!$id) {
            $this->show_warning ( '不存在的记录' );
            return;
        }
        $detail = $this->m->get ( array (
                'conditions' => 'id=' . $id . ' and user_id=' . $this->visitor->info['user_id'] 
        ) );
        if ($detail) {
            $detail['status'] = $this->m->get_status ( $detail['status'] );
            $detail['type'] = $this->m->convert_type ( $detail['type'] );
            $detail['log'] = unserialize ( $detail['log'] );
            $detail['goods'] = m ( 'refundgoods' )->getAll ( 'select * from ecm_refund_goods rg where refund_id=' . $id );
        }
        $this->_config_seo ( 'title', Lang::get ( 'member_center' ) . ' - ' . '退款详情' );
        $this->assign ( 'detail', $detail );
        $this->display ( 'seller_refund.detail.html' );
    }

    /**
     * 申请退款
     */
    function to_apply()
    {
        $order = m ( 'order' )->get ( array (
                'conditions' => 'order_id=' . intval ( $_GET['order_id'] ),
                'fields' => 'order_id,order_sn,status,buyer_id' 
        ) );
        // 权限校验
        if ($this->visitor->info['user_id'] != $order['buyer_id']) {
            $this->show_warning ( 'no_access' );
            return;
        }
        // 订单状态校验
        if ($order['status'] != ORDER_PENDED && $order['status'] != ORDER_ACCEPTED && $order['status'] != ORDER_REQUEST && $order['status'] != ORDER_RESPONSE && $order['status'] != ORDER_SHIPPED) {
        	$this->show_warning ( 'wrong_status' );
            return;
        }
        $this->assign ( 'order', $order );
        $this->display ( 'refund.form.html' );
    }

    /**
     * 保存申请
     */
    function save()
    {
        if (!$this->visitor->has_login) {
            $this->show_warning ( '你没有登录' );
        }
        $data['user_id'] = $this->visitor->info['user_id'];
        $data['order_id'] = intval ( $_POST['order_id'] );
        $data['uname'] = $this->visitor->info['user_name'];
        $data['info'] = t ( $_POST['info'] );
        $res = $this->m->apply ( $data, 1 );
        if ($res) {
            $this->show_message ( 'apply_success' );
        } else {
            $this->show_warning ( $this->m->getError () );
        }
    }

    function _get_refunds()
    {
        $page = $this->_get_page ();
        $condition = $this->_get_condition ();
        $sql = "SELECT rf.id, rf.order_id, rf. STATUS, rf.order_sn, rf.seller_name, rf.amount, rf.ctime,rf.status FROM ecm_refund rf WHERE {$condition} ORDER BY ctime DESC LIMIT {$page['limit']}";
        $refunds = $this->m->getAll ( $sql );
        foreach ( $refunds as &$v ) {
            $v['status_text'] = $this->m->get_status ( $v['status'] );
        }
        
        $page['item_count'] = $this->m->getOne ( "select count(1) from ecm_refund rf WHERE {$condition}" );
        $this->_format_page ( $page );
        $this->assign ( 'page_info', $page );
        $this->assign ( 'refunds', $refunds );
    }

    function _get_condition()
    {
        $user_id = $this->visitor->info['user_id'];
        // 买家 订单退款
        $condition = 'rf.user_id = ' . $user_id;
        $condition .= $this->_get_query_conditions ( array (
                array ( // 按下单时间搜索,起始时间
                        'field' => 'rf.ctime',
                        'name' => 'ctime_from',
                        'equal' => '>=',
                        'handler' => 'gmstr2time' 
                ),
                array ( // 按下单时间搜索,结束时间
                        'field' => 'rf.ctime',
                        'name' => 'ctime_to',
                        'equal' => '<=',
                        'handler' => 'gmstr2time_end' 
                ),
                array ( // 按订单号
                        'field' => 'rf.order_sn',
                        'name' => 'order_sn' 
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
                        'name' => 'refund',
                        'text' => '退款管理',
                        'url' => 'index.php?app=buyer_refund&amp;type=refund' 
                ),
                array (
                        'name' => 'returngoods',
                        'text' => '退货管理',
                        'url' => 'index.php?app=buyer_returngoods&amp;act=show_list&amp;type=returngoods' 
                ) 
        );
        return $array;
    }
}

?>
