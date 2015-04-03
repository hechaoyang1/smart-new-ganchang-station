<?php

/* 买家退货流程 */
class Buyer_returngoodsApp extends MemberbaseApp
{
    private $m;

    function __construct()
    {
        $this->m = m ( 'returngoods' );
        parent::__construct ();
    }

    function index()
    {
        $model_order = & m ( 'order' );
        $order_id = isset ( $_GET['order_id'] ) ? intval ( $_GET['order_id'] ) : 0;
        $order_info = $model_order->get ( array (
                'fields' => "*, order.add_time as order_add_time",
                'conditions' => "order_id={$order_id} AND buyer_id=" . $this->visitor->get ( 'user_id' ),
                'join' => 'belongs_to_store' 
        ) );
        // 订单状态校验 已发货、已完成
        if ($order_info['status'] != ORDER_FINISHED) {
            $this->show_warning ( 'wrong_status' );
            return;
        }
        if (!$order_info) {
            $this->show_warning ( 'no_such_order' );
            return;
        }
        $this->import_resource ( array (
                'script' => array (
                        array (
                                'path' => 'dialog/dialog.js',
                                'attr' => 'id="dialog_js"' 
                        ),
                        array (
                                'path' => 'jquery.ui/jquery.ui.js',
                                'attr' => '' 
                        ) 
                ),
                'style' => 'jquery.ui/themes/ui-lightness/jquery.ui.css' 
        ) );
        /* 团购信息 */
        if ($order_info['extension'] == 'groupbuy') {
            $groupbuy_mod = &m ( 'groupbuy' );
            $group = $groupbuy_mod->get ( array (
                    'join' => 'be_join',
                    'conditions' => 'order_id=' . $order_id,
                    'fields' => 'gb.group_id' 
            ) );
            $this->assign ( 'group_id', $group['group_id'] );
        }
        
        /* 当前位置 */
        $this->_curlocal ( LANG::get ( 'member_center' ), 'index.php?app=member', LANG::get ( 'my_order' ), 'index.php?app=buyer_order', LANG::get ( 'view_order' ) );
        
        /* 当前用户中心菜单 */
        $this->_curitem ( 'my_order' );
        
        $this->_config_seo ( 'title', Lang::get ( 'member_center' ) . ' - ' . Lang::get ( 'order_detail' ) );
        
        /* 调用相应的订单类型，获取整个订单详情数据 */
        $order_type = & ot ( $order_info['extension'] );
        $order_detail = $order_type->get_order_detail ( $order_id, $order_info );
        $rec_ids = array ();
        foreach ( $order_detail['data']['goods_list'] as $key => $goods ) {
            empty ( $goods['goods_image'] ) && $order_detail['data']['goods_list'][$key]['goods_image'] = Conf::get ( 'default_goods_image' );
            $return_goods = $this->m->get ( array (
                    'conditions' => "order_goods_id ={$goods['rec_id']}",
                    'fields' => 'id,status ' 
            ) );
            $order_detail['data']['goods_list'][$key]['return_status'] = $return_goods['status'];
            $order_detail['data']['goods_list'][$key]['return_id'] = $return_goods['id'];
        }
        $this->assign ( 'order', $order_info );
        $this->assign ( $order_detail['data'] );
        $this->display ( 'buyer_return.index.html' );
    }

    /**
     * 我的退货列表
     */
    function show_list()
    {
        /* 获取退款列表 */
        $this->_get_returns ();
        
        /* 当前位置 */
        // $this->_curlocal ( LANG::get ( 'member_center' ), 'index.php?app=member', LANG::get ( 'order_manage' ), 'index.php?app=seller_order', LANG::get ( 'order_list' ) );
        
        /* 当前用户中心菜单 */
        $type = (isset ( $_GET['type'] ) && $_GET['type'] != '') ? trim ( $_GET['type'] ) : 'returngoods';
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
        $this->display ( 'buyer_return.list.html' );
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
        $sql = "SELECT id, rg.return_order_sn,rg.express_sn, o.order_sn, rg.buyer_name, rg.quantity, rg.status, og.goods_name, og.goods_image, og.price, og.goods_id, rg.remark, rg.log, rg.ctime,rg.status FROM ecm_return_goods rg LEFT JOIN ecm_order_goods og ON rg.order_goods_id = og.rec_id LEFT JOIN ecm_order o ON rg.order_id = o.order_id WHERE rg.id=$id and rg.user_id={$this->visitor->info['user_id']}  limit 1";
        $detail = $this->m->getAll ( $sql );
        if ($detail) {
            $detail = $detail[0];
            $detail['status_text'] = $this->m->convert_status ( $detail['status'] );
            $detail['log'] = unserialize ( $detail['log'] );
        }
        $this->_config_seo ( 'title', Lang::get ( 'member_center' ) . ' - ' . '退货详情' );
        $this->assign ( 'detail', $detail );
        $this->display ( 'buyer_returngoods.detail.html' );
    }

    /**
     * 申请退货
     */
    function view()
    {
        $exists = $this->m->find ( 'order_id=' . intval ( $_GET['order_id'] ) . ' and order_goods_id=' . intval ( $_GET['rec_id'] ) );
        if ($exists) {
            header ( 'Location:index.php?app=buyer_returngoods&order_id=' . intval ( $_GET['order_id'] ) );
            return;
        }
        $order = m ( 'order' )->get ( array (
                'conditions' => 'order_id=' . intval ( $_GET['order_id'] ),
                'fields' => 'status,buyer_id' 
        ) );
        $goods=m('goods')->getAll('select g.service_type from ecm_order_goods og left join ecm_goods g on og.goods_id=g.goods_id where og.rec_id='.intval ( $_GET['rec_id'] ).' limit 1');
        $goods=current($goods);
        if($goods['service_type']==2){
            $this->show_warning ( '该商品不支持退货' );
            return;
        }
        // 权限校验
        if ($this->visitor->info['user_id'] != $order['buyer_id']) {
            $this->show_warning ( 'no_access' );
            return;
        }
        // 订单状态校验 已发货、已完成
        if ($order['status'] != ORDER_FINISHED) {
            $this->show_warning ( 'wrong_status' );
            return;
        }
        $this->assign ( 'order_id', intval ( $_GET['order_id'] ) );
        $this->assign ( 'rec_id', intval ( $_GET['rec_id'] ) );
        $this->display ( 'buyer_return.view.html' );
    }

    /**
     * 保存申请
     */
    function apply()
    {
        $order_id = intval ( $_POST['order_id'] );
        $rec_id = intval ( $_POST['rec_id'] );
        $data['user_id'] = $this->visitor->info['user_id'];
        $data['order_id'] = $order_id;
        $data['rec_id'] = $rec_id;
        $res = $this->m->apply ( $data );
        if ($res) {
            $this->show_message ( '申请成功，请耐心等待卖家审核。', 'back_list', "index.php?app=buyer_returngoods&order_id={$data['order_id']}" );
        } else {
            $this->show_warning ( $this->m->getError () );
        }
    }

    /**
     * 快递信息
     */
    public function express()
    {
        if (IS_POST) {
            $param['express_sn'] = t ( $_POST['express_sn'] );
            if (empty ( $param['express_sn'] )) {
                $this->pop_warning ( '请填写快递单号' );
                return;
            }
            $id = intval ( $_POST['id'] );
            $param['id'] = $id;
            $param['user_id'] = $this->visitor->info['user_id'];
            $param['user_name'] = $this->visitor->info['user_name'];
            $param['remark'] = t ( $_POST['remark'] );
            $res = $this->m->mail ( $param );
            if ($res) {
                $this->pop_warning ( 'ok' );
            } else {
                $this->pop_warning ( '提交失败' );
            }
        } else {
            $id = intval ( $_GET['id'] );
            $data = $this->m->get ( array (
                    'conditions' => 'id=' . $id,
                    'fields' => 'id,user_id,status' 
            ) );
            // 权限校验
            if ($this->visitor->info['user_id'] != $data['user_id']) {
                $this->show_warning ( 'no_access' );
                return;
            }
            // 状态校验
            if ($data['status'] != RETURN_AUDITD) {
                $this->show_warning ( 'wrong_status' );
                return;
            }
            $this->assign ( 'id', $data['id'] );
            $this->display ( 'buyer_return.mail.html' );
        }
    }

    function _get_returns()
    {
        $page = $this->_get_page ();
        $condition = $this->_get_condition ();
        $sql = "SELECT rg.id, rg.return_order_sn, og.goods_name,og.price, rg.status,rg.quantity, rg.ctime FROM ecm_return_goods rg LEFT JOIN ecm_order_goods og ON rg.order_goods_id = og.rec_id WHERE {$condition} ORDER BY ctime DESC LIMIT {$page['limit']}";
        $returns = $this->m->getAll ( $sql );
        foreach ( $returns as &$v ) {
            $v['status_text'] = $this->m->convert_status ( $v['status'] );
        }
        
        $page['item_count'] = $this->m->getOne ( "select count(1) from ecm_return_goods WHERE {$condition}" );
        $this->_format_page ( $page );
        $this->assign ( 'page_info', $page );
        $this->assign ( 'returns', $returns );
    }

    function _get_condition()
    {
        $user_id = $this->visitor->info['user_id'];
        // 买家 订单退款
        $condition = 'user_id = ' . $user_id;
        $condition .= $this->_get_query_conditions ( array (
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
