<?php

/* 退款流程 */
class seller_refundApp extends MemberbaseApp
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
        $type = (isset ( $_GET['type'] ) && $_GET['type'] != '') ? trim ( $_GET['type'] ) : 'all';
        $this->_curitem ( 'refund_manage' );
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
        $this->_config_seo ( 'title', Lang::get ( 'member_center' ) . ' - ' . Lang::get ( 'refund_manage' ) );
        /* 显示订单列表 */
        $this->display ( 'seller_refund.index.html' );
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
                'conditions' => 'id=' . $id . ' and seller_id=' . $this->visitor->get ( 'manage_store' ) 
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

    function _get_refunds()
    {
        $page = $this->_get_page ();
        $seller_id = $this->visitor->get ( 'manage_store' );
        $condition = $this->_get_condition ();
        $sql = "SELECT rf.id, rf.order_id,rf.status,rf.order_sn,rf.buyer_name,rf.amount,oe.phone_tel,oe.phone_mob FROM ecm_refund rf LEFT JOIN ecm_order_extm oe on rf.order_id=oe.order_id WHERE {$condition} ORDER BY ctime DESC LIMIT {$page['limit']}";
        $refunds = $this->m->getAll ( $sql );
        foreach ( $refunds as &$v ) {
            $v['status_text'] = $this->m->get_status ( $v['status'] );
        }
        
        $page['item_count'] = $this->m->getOne ( "select count(1) from ecm_refund rf WHERE {$condition}" );
        $this->_format_page ( $page );
        $this->assign ( 'page_info', $page );
        $this->assign ( 'refunds', $refunds );
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
            $this->show_warning ( '无权操作' );
            return;
        }
        // 订单状态校验
        if ($order['status'] != ORDER_PENDED && $order['status'] != ORDER_ACCEPTED && $order['status'] != ORDER_REQUEST && $order['status'] != ORDER_RESPONSE && $order['status'] != ORDER_SHIPPED) {
            $this->show_warning ( '该订单已经申请退款' );
            return;
        }
        $this->assign ( 'order', $order );
        $this->display ( 'buyer_refund.apply.html' );
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
        $data['info'] = t ( $_POST['info'] );
        $res = $this->m->apply ( $data, 1 );
        if ($res) {
            $this->pop_warning ( 'ok' );
        } else {
            $this->pop_warning ( $this->m->getError () );
        }
    }

    /**
     * 卖家审核通过
     */
    function audit()
    {
        if (!IS_POST) {
            $id = intval ( $_GET['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退款单' );
                return;
            }
            $refund = $this->m->get ( array (
                    'conditions' => 'id=' . $id,
                    'fields' => 'id,data,order_sn,amount,status,info' 
            ) );
            $this->assign ( 'refund', $refund );
            $this->display ( 'seller_refund.audit.html' );
        } else {
            $id = intval ( $_POST['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退款单' );
                return;
            }
            $seller_id = $this->visitor->get ( 'manage_store' );
            $ret = $this->m->seller_audit ( $id, $seller_id, $this->visitor->info['user_name'], array (
                    'remark' => t ( $_POST['remark'] ) 
            ) );
            if ($ret) {
                $this->pop_warning ( 'ok' );
            } else {
                $this->pop_warning ( $this->m->getError () );
            }
        }
    }

    /**
     * 确认已退款
     */
    function confirm()
    {
        if (!IS_POST) {
            $id = intval ( $_GET['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退款单' );
                return;
            }
            $refund = $this->m->get ( array (
                    'conditions' => 'id=' . $id,
                    'fields' => 'id,data,order_sn,status,info' 
            ) );
            $this->assign ( 'refund', $refund );
            $this->display ( 'seller_refund.confirm.html' );
        } else {
            $id = intval ( $_POST['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退款单' );
                return;
            }
            $seller_id = $this->visitor->get ( 'manage_store' );
            $ret = $this->m->seller_confirm ( array (
                    'id' => $id,
                    'user_id' => $seller_id,
                    'user_name' => $this->visitor->info['user_name'],
                    'remark' => t ( $_POST['remark'] ) 
            ) );
            if ($ret) {
                $this->pop_warning ( 'ok' );
            } else {
                $this->pop_warning ( $this->m->getError () );
            }
        }
    }

    /**
     * 卖家关闭
     */
    function close()
    {
        if (!IS_POST) {
            $id = intval ( $_GET['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退款单' );
                return;
            }
            $refund = $this->m->get ( array (
                    'conditions' => 'id=' . $id,
                    'fields' => 'id,data,order_sn,status,info' 
            ) );
            $this->assign ( 'refund', $refund );
            $this->display ( 'seller_refund.close.html' );
        } else {
            $id = intval ( $_POST['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退款单' );
                return;
            }
            $seller_id = $this->visitor->get ( 'manage_store' );
            $ret = $this->m->seller_close ( array (
                    'id' => $id,
                    'user_id' => $seller_id,
                    'user_name' => $this->visitor->info['user_name'],
                    'remark' => t ( $_POST['remark'] ) 
            ) );
            if ($ret) {
                $this->pop_warning ( 'ok' );
            } else {
                $this->pop_warning ( $this->m->getError () );
            }
        }
    }

    function _get_condition()
    {
        !$_GET['type'] && $_GET['type'] = 'all';
        switch ($_GET['type']) {
            case 'appled' :
                $status = STATUS_APPLY;
                break;
            case 'refunded' :
                $status = STATUS_REFUNDED;
                break;
            case 'other' :
                $status = STATUS_AUDITD . ',' . STATUS_APPROVED . ',' . STATUS_REFUNDCONFIRMED . ',' . STATUS_CLOSED . ',' . STATUS_COMPLETED;
                break;
            case 'all' :
            default :
                $status = '';
                break;
        }
        $seller_id = $this->visitor->get ( 'manage_store' );
        $condition = 'rf.seller_id = ' . $seller_id . ($status !== '' ? ' AND rf. STATUS in (' . $status . ')' : '');
        $condition .= $this->_get_query_conditions ( array (
                array ( // 按买家名称搜索
                        'field' => 'rf.buyer_name',
                        'name' => 'buyer_name',
                        'equal' => 'LIKE' 
                ),
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
                        'name' => 'all',
                        'url' => 'index.php?app=seller_refund&amp;type=all' 
                ),
                array (
                        'name' => 'appled',
                        'url' => 'index.php?app=seller_refund&amp;type=appled' 
                ),
                array (
                        'name' => 'refunded',
                        'url' => 'index.php?app=seller_refund&amp;type=refunded' 
                ),
                array (
                        'name' => 'other',
                        'url' => 'index.php?app=seller_refund&amp;type=other' 
                ) 
        );
        return $array;
    }
}

?>
