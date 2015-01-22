<?php
/**
 *  结算中心处理退款
 * @author admin
 *
 */
class RefundApp extends BackendApp
{
    private $m;

    function __construct()
    {
        $this->RefundApp ();
    }

    function RefundApp()
    {
        $this->m = m ( 'refund' );
        parent::__construct ();
    }

    /**
     * index
     *
     * @access public
     * @return void
     */
    public function index()
    {
        $this->_get_status ();
        $condition = $this->_get_condition ( $_GET['cur'] );
        $page = $this->_get_page ();
        $sql = "SELECT rf.id, rf.order_id,rf.order_sn,rf.seller_name,rf.buyer_name,rf.type,rf.status,rf.amount FROM ecm_refund rf WHERE {$condition} ORDER BY ctime DESC LIMIT {$page['limit']}";
        $refunds = $this->m->getAll ( $sql );
        error_reporting ( E_ALL );
        foreach ( $refunds as &$v ) {
            $v['status_text'] = $this->m->get_status ( $v['status'] );
            $v['type_text'] = $this->m->convert_type( $v['type']);
        }
        $page['item_count'] = $this->m->getOne ( "select count(1) from ecm_refund rf WHERE {$condition}" );
        $this->_format_page ( $page );
        
        $this->assign ( 'cur', isset ( $_GET['cur'] ) ? $_GET['cur'] : 'all' );
        $this->assign ( 'page_info', $page );
        $this->assign ( 'refunds', $refunds );
        $this->display ( 'refund.index.html' );
    }

    /**
     * 退款
     */
    public function do_refund()
    {
        $id = intval ( $_GET['id'] );
        if (!$id) {
            $this->show_warning ( '不存在的退款单' );
            return;
        }
        $data['id'] = $id;
        $data['user_id'] = $this->visitor->info['user_id'];
        $data['user_name'] = $this->visitor->info['user_name'];
        $res = $this->m->refund ( $data );
        if ($res) {
            $this->show_message ( '退款成功', 'back_list', 'index.php?app=refund&amp;act=index&amp;cur=' . t ( $_GET['cur'] ) );
        } else {
            $this->show_warning ( $this->m->getError () );
        }
    }

    /**
     * 审批/关闭
     */
    public function do_approve()
    {
        if (!IS_POST) {
            $id = intval ( $_GET['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退款单' );
                return;
            }
            $refund = $this->m->get ( array (
                    'conditions' => 'id=' . $id,
                    'fields' => 'id,data,status,order_sn,type,buyer_name,seller_name,info,amount,log' 
            ) );
            $refund['log'] = unserialize ( $refund['log'] );
            $refund['type_text'] =$this->m->convert_type( $refund['type']);
            $this->assign ( 'data', $refund );
            $this->assign ( 'cur', isset ( $_GET['cur'] ) ? $_GET['cur'] : 'all' );
            $this->display ( 'refund.approve.html' );
        } else {
            $id = intval ( $_POST['id'] );
            if (!$id) {
                $this->show_warning ( '不存在的退款单' );
                return;
            }
            $approve = $_POST['approve'] == 1 ? true : false;
            $data['id'] = $id;
            $data['user_id'] = $this->visitor->info['user_id'];
            $data['user_name'] = $this->visitor->info['user_name'];
            $data['remark'] = t ( $_POST['remark'] );
            $res = $this->m->approve ( $data, $approve );
            if ($res) {
                $this->show_message ( '审批成功', 'back_list', 'index.php?app=refund&amp;act=index&amp;cur=' . t ( $_POST['cur'] ) );
            } else {
                $this->show_warning ( $this->m->getError () );
            }
        }
    }

    public function detail()
    {
        $id = intval ( $_GET['id'] );
        if (!$id) {
            $this->show_warning ( '不存在的退款单' );
            return;
        }
        $refund = $this->m->get ( array (
                'conditions' => 'id=' . $id,
                'fields' => 'id,data,status,order_sn,type,buyer_name,seller_name,info,amount,log' 
        ) );
        $refund['type_text'] = $this->m->convert_type( $refund['type']);
        $refund['status'] = $this->m->get_status ( $refund['status'] );
        $refund['log'] = unserialize ( $refund['log'] );
        $refund['goods'] = m ( 'refundgoods' )->getAll ( 'select * from ecm_refund_goods rg where refund_id=' . $id );
        $this->assign ( 'data', $refund );
        $this->assign ( 'cur', isset ( $_GET['cur'] ) ? $_GET['cur'] : 'all' );
        $this->display ( 'refund.detail.html' );
    }

    private function _get_status()
    {
        $status = array (
                array (
                        'value' => 'all',
                        'text' => '全部' 
                ),
                array (
                        'value' => 'audited',
                        'text' => '待审批' 
                ),
                array (
                        'value' => 'approved',
                        'text' => '待退款' 
                ),
                array (
                        'value' => 'refunded',
                        'text' => '已退款' 
                ),
                array (
                        'value' => 'closed',
                        'text' => '已关闭' 
                ) 
        );
        $this->assign ( 'status', $status );
    }

    private function _get_condition($status)
    {
        switch ($status) {
            case 'audited' :
                return 'STATUS=' . STATUS_AUDITD;
            case 'approved' :
                return 'STATUS=' . STATUS_APPROVED;
            case 'refunded' :
                return 'STATUS=' . STATUS_REFUNDED . ' or STATUS=' . STATUS_COMPLETED . ' or STATUS=' . STATUS_REFUNDCONFIRMED;
            case 'closed' :
                return 'STATUS=' . STATUS_CLOSED;
            case 'all' :
            default :
                return 'STATUS=' . STATUS_AUDITD . ' or STATUS=' . STATUS_APPROVED . ' or STATUS=' . STATUS_REFUNDED . ' or STATUS=' . STATUS_CLOSED;
        }
    }
}
?>