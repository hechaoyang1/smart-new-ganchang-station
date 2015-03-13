<?php
/* 申请退款/待审批 */
define ( 'STATUS_APPLY', 0 );
/* 卖家审核通过/待结算中心审核 */
define ( 'STATUS_AUDITD', 1 );
/* 结算中心已审批 */
define ( 'STATUS_APPROVED', 2 );
/* 结算中心已退款 */
define ( 'STATUS_REFUNDED', 3 );
/* 卖家确认已退款 */
define ( 'STATUS_REFUNDCONFIRMED', 4 );
/* 关闭申请 */
define ( 'STATUS_CLOSED', 5 );
/* 已完成 */
define ( 'STATUS_COMPLETED', 6 );
/* 收货地址 address */
class RefundModel extends BaseModel
{
    var $error;
    var $table = 'refund';
    var $prikey = 'id';
    var $_name = 'refund';
    var $alias = 'rf';
    var $_relation = array (
            // 一个商品对应一条商品统计记录
            'has_order' => array (
                    'model' => 'order',
                    'type' => BELONGS_TO,
                    'foreign_key' => 'order_id',
                    'dependent' => false 
            ) 
    );

    /**
     * 申请退款
     *
     * @param unknown $param            
     * @param number $type            
     * @return boolean Ambigous boolean, unknown>
     */
    public function apply($param = array(), $type = 1)
    {
        if (!$param['order_id']) {
            $this->error = '参数错误';
            return false;
        }
        if (!$param['user_id']) {
            $this->error = '参数错误';
            return false;
        }
        
        $order = m ( 'order' )->get ( array (
                'conditions' => 'order_id=' . $param['order_id'],
                'fields' => 'seller_id,buyer_id,buyer_name,seller_name,status,order_amount,order_sn' 
        ) );
        // 订单状态校验，只有已付款未发货的订单才可申请退款
        if ($type == 1 && $order['status'] != ORDER_PENDED && $order['status'] != ORDER_ACCEPTED) {
            $this->error = '订单状态不能退款';
            return false;
        }
        // 重复性校验，不能重复申请退款
        if ($type == 1) {
            $has_one = $this->get ( array (
                    'conditions' => 'order_id=' . $param['order_id'] . ' and type=' . $type,
                    'fields' => 'id' 
            ) );
            if ($has_one) {
                $this->error = '该订单已经申请退款';
                return false;
            }
        }
        // 是本人的订单或者退货申请后卖家发起退款
        if (!empty ( $order ) && (($type == 1 && $order['buyer_id'] == $param['user_id']) || ($type == 2 && $order['seller_id'] == $param['seller_id']))) {
            $data['user_id'] = $param['user_id'];
            $data['order_id'] = $param['order_id'];
            $data['order_sn'] = $order['order_sn'];
            $data['amount'] = $type == 1 ? $order['order_amount'] : $param['amount'];
            $data['seller_id'] = $order['seller_id'];
            $data['seller_name'] = $order['seller_name'];
            $data['buyer_name'] = $order['buyer_name'];
            $data['return_goods_id'] = $param['return_goods_id'];
            $data['status'] = $type == 1 ? STATUS_APPLY : STATUS_AUDITD;
            $data['info'] = $param['info'];
            $data['type'] = $type;
            $data['ctime'] = time ();
            $data['log'] = $this->create_log ( array (), $data['user_id'], $order['buyer_name'], '申请退款' );
            $res = $this->add ( $data );
            // 修改订单状态为“申请退款”
            if ($res) {
                /* 直接退款 */
                if ($type == 1) {
                    $this->db->query ( "INSERT INTO ecm_refund_goods(refund_id,goods_id,goods_name,price,quantity,goods_image) SELECT {$res},goods_id,goods_name,price,quantity,goods_image FROM ecm_order_goods where order_id={$data['order_id']}" );
                    return m ( 'order' )->edit ( $param['order_id'], array (
                            'status' => ORDER_REFUNDED 
                    ) );
                    /* 退货退款 */
                } else {
                    return $this->db->query ( "INSERT INTO ecm_refund_goods(refund_id,goods_id,goods_name,price,quantity,goods_image) SELECT {$res}, og.goods_id, og.goods_name, og.price, og.quantity, og.goods_image FROM ecm_return_goods rg LEFT JOIN ecm_order_goods og ON rg.order_goods_id = og.rec_id WHERE rg.id={$data['return_goods_id']}" );
                }
            }
            return false;
        } else {
            $this->error = '非法操作';
            return false;
        }
        return false;
    }

    /**
     * 卖家审核
     *
     * @param unknown $id            
     * @param unknown $seller_id            
     * @param unknown $seller_name            
     * @return boolean
     */
    public function seller_audit($id, $seller_id, $seller_name, $param = array())
    {
        $refund = $this->get ( array (
                'conditions' => 'id=' . $id,
                'fields' => 'seller_id,status,type,log' 
        ) );
        if (empty ( $refund ) || $refund['seller_id'] != $seller_id) {
            $this->error = '非法操作';
            return false;
        }
        if ($refund['status'] != STATUS_APPLY) {
            $this->error = '该退款已经处理过';
            return false;
        }
        $refund['log'] = unserialize ( $refund['log'] );
        $log = $this->create_log ( $refund['log'], $seller_id, $seller_name, '卖家审核通过:' . $param['remark'] );
        return $this->edit ( 'id=' . $id, array (
                'status' => STATUS_AUDITD,
                'log' => $log 
        ) );
    }

    /**
     * 卖家确认付款
     *
     * @param unknown $param            
     */
    public function seller_confirm($param = array())
    {
        $refund = $this->get ( array (
                'conditions' => 'id=' . $param['id'],
                'fields' => 'seller_id,order_id,status,type,return_goods_id,log' 
        ) );
        if (empty ( $refund ) || $refund['seller_id'] != $param['user_id']) {
            $this->error = '非法操作';
            return false;
        }
        if ($refund['status'] != STATUS_REFUNDED) {
            $this->error = '该次退款还未完成';
            return false;
        }
        $refund['log'] = unserialize ( $refund['log'] );
        $log = $this->create_log ( $refund['log'], $param['user_id'], $param['user_name'], '卖家确认已退款:' . $param['remark'] );
        $res = $this->edit ( 'id=' . $param['id'], array (
                'status' => STATUS_COMPLETED,
                'log' => $log 
        ) );
        /* 直接退款成功后修改订单状态为已取消 */
        if ($refund['type'] == 1 && $res) {
            return m ( 'order' )->edit ( $refund['order_id'], array (
                    'status' => ORDER_CANCELED 
            ) );
            /* 退货退款后修改退货单状态为已完成 */
        } else if ($refund['type'] == 2) {
            $returnmodel = m ( 'returngoods' );
            $return = $returnmodel->get ( array (
                    'conditions' => 'id=' . $refund['return_goods_id'],
                    'fields' => 'log' 
            ) );
            m ( 'order' )->edit ( $refund['order_id'], array (
                    'status' => ORDER_FINISHED 
            ) );
            $returnmodel->edit ( $refund['return_goods_id'], array (
                    'status' => RETURN_COMPLETED,
                    'log' => $returnmodel->_create_log ( unserialize ( $return['log'] ), $param['user_id'], $param['user_name'], '卖家确认已退款:' . $param['remark'] ) 
            ) );
        }
        return $res;
    }

    /**
     * 卖家关闭退款单
     *
     * @param unknown $param            
     */
    public function seller_close($param = array())
    {
        $refund = $this->get ( array (
                'conditions' => 'id=' . $param['id'],
                'fields' => 'seller_id,status,type,order_id,log,type' 
        ) );
        if (empty ( $refund ) || $refund['seller_id'] != $param['user_id']) {
            $this->error = '非法操作';
            return false;
        }
        if ($refund['status'] != STATUS_APPLY) {
            $this->error = '该退款已经处理过';
            return false;
        }
        $refund['log'] = unserialize ( $refund['log'] );
        $log = $this->create_log ( $refund['log'], $param['user_id'], $param['user_name'], '卖家关闭:' . $param['remark'] );
        $ret = $this->edit ( 'id=' . $param['id'], array (
                'status' => STATUS_CLOSED,
                'log' => $log 
        ) );
        if ($ret) {
            return m ( 'order' )->edit ( $refund['order_id'], array (
                    'status' => ORDER_ACCEPTED 
            ) );
        }
        return $ret;
    }

    /**
     * 结算中心审核
     *
     * @param unknown $id            
     * @param unknown $oper_id            
     * @param unknown $oper_name            
     */
    public function approve($param = array(), $approve = true)
    {
        $refund = $this->get ( array (
                'conditions' => 'id=' . $param['id'],
                'fields' => 'status,log,order_id,type' 
        ) );
        if ($refund['status'] != STATUS_AUDITD) {
            $this->error = '该退款状态不能审核';
            return false;
        }
        $refund['log'] = unserialize ( $refund['log'] );
        $log = $this->create_log ( $refund['log'], $param['user_id'], $param['user_name'], ($approve ? '审批通过:' : '关闭退款:') . $param['remark'] );
        $ret = $this->edit ( 'id=' . $param['id'], array (
                'status' => $approve ? STATUS_APPROVED : STATUS_CLOSED,
                'log' => $log 
        ) );
        if ($ret && !$approve && $refund['type'] == 1) {
            return m ( 'order' )->edit ( $refund['order_id'], array (
                    'status' => ORDER_ACCEPTED 
            ) );
        }
        return $ret;
    }

    /**
     * 退款
     *
     * @param unknown $param            
     * @return boolean
     */
    public function refund($param = array())
    {
        $refund = $this->get ( array (
                'conditions' => 'id=' . $param['id'],
                'fields' => 'status,log' 
        ) );
        if ($refund['status'] != STATUS_APPROVED) {
            $this->error = '该退款还没审批通过';
            return false;
        }
        $refund['log'] = unserialize ( $refund['log'] );
        $log = $this->create_log ( $refund['log'], $param['user_id'], $param['user_name'], '已退款' );
        return $this->edit ( 'id=' . $param['id'], array (
                'status' => STATUS_REFUNDED,
                'log' => $log 
        ) );
    }

    public function getError()
    {
        return $this->error;
    }

    private function create_log($log = array(), $user_id, $user_name, $mark = '')
    {
        $log[] = array (
                'user_id' => $user_id,
                'name' => $user_name,
                'time' => time (),
                'mark' => $mark 
        );
        return serialize ( $log );
    }

    public function get_status($status = 0)
    {
        switch ($status) {
            case STATUS_APPLY :
                return '已提交';
            case STATUS_AUDITD :
                return '卖家已审核';
            case STATUS_APPROVED :
                return '已审批';
            case STATUS_REFUNDED :
                return '已退款';
            case STATUS_REFUNDCONFIRMED :
                return '卖家确认退款';
            case STATUS_CLOSED :
                return '已关闭';
            case STATUS_COMPLETED :
                return '已完成';
            default :
                return '已关闭';
        }
    }

    public function convert_type($type)
    {
        switch ($type) {
            case 1 :
                return '直接退款';
            case 2 :
                return '退货退款';
            default :
                return '其他';
        }
    }
}

?>