<?php
/* 申请退货/待审批 */
define ( 'RETURN_APPLY', 11 );
/* 卖家审核通过 */
define ( 'RETURN_AUDITD', 1 );
/* 买家已邮寄 */
define ( 'RETURN_MAILED', 2 );
/* 已收到货 */
define ( 'RETURN_RECEIPTED', 3 );
/* 验货OK */
define ( 'RETURN_EXAMINE_OK', 4 );
/* 验货不通过 拒绝退货 */
define ( 'RETURN_EXAMINE_REJECT', 5 );
/* 关闭申请 */
define ( 'RETURN_CLOSED', 6 );
/* 已完成 */
define ( 'RETURN_COMPLETED', 7 );
class ReturngoodsModel extends BaseModel
{
    var $table = 'return_goods';
    var $prikey = 'id';
    var $_name = 'return_goods';
    var $alias = 'rg';
    var $error = '';

    function apply($param)
    {
        
        /* 是否申请过 */
        $exists = $this->get ( array (
                'conditions' => "order_id={$param['order_id']} and order_goods_id={$param['rec_id']}",
                'fields' => 'id' 
        ) );
        if ($exists) {
            $this->error = '该商品已经申请过退货';
            return false;
        }
        $order_goods = m ( 'ordergoods' )->getAll ( "select og.rec_id,og.quantity,og.goods_id,og.source_type,g.service_type from ecm_order_goods og left join ecm_goods g on og.goods_id=g.goods_id where og.rec_id={$param['rec_id']} and og.order_id={$param['order_id']} limit 1" );
        $order_goods = current ( $order_goods );
        if (empty ( $order_goods )) {
            $this->error = '商品记录不存在';
            return false;
        }
        if ($order_goods['service_type'] == 2) {
            $this->error = '该商品不支持退货';
            return false;
        }
        $order = m ( 'order' )->get ( array (
                'conditions' => 'order_id=' . $param['order_id'],
                'fields' => 'status,buyer_id,seller_id,buyer_name,seller_name' 
        ) );
        // 权限校验
        if ($param['user_id'] != $order['buyer_id']) {
            $this->error = '没有权限';
            return false;
        }
        // 订单状态校验 已发货、已完成
        if ($order['status'] != ORDER_FINISHED) {
            $this->error = '该订单状态不能退货';
            return false;
        }
        $data['return_order_sn'] = $this->_gen_return_sn ();
        $data['user_id'] = $param['user_id'];
        $data['order_id'] = $param['order_id'];
        $data['seller_id'] = $order['seller_id'];
        $data['seller_name'] = $order['seller_name'];
        $data['buyer_name'] = $order['buyer_name'];
        $data['order_goods_id'] = $param['rec_id'];
        $data['quantity'] = $order_goods['quantity'];
        $data['status'] = RETURN_APPLY;
        $data['source_type'] = $order_goods['source_type'];
        $data['remark'] = t ( $_POST['remark'] );
        $data['log'] = $this->_create_log ( array (), $data['user_id'], $data['buyer_name'], '申请退货' );
        $data['ctime'] = gmtime ();
        $ret = $this->add ( $data );
        if (!ret) {
            $this->error = '申请失败';
        }
        return $ret;
    }

    public function _create_log($log = array(), $user_id, $user_name, $mark = '')
    {
        $log[] = array (
                'user_id' => $user_id,
                'name' => $user_name,
                'time' => gmtime (),
                'mark' => $mark 
        );
        return serialize ( $log );
    }

    /**
     * 生成退货单单号
     */
    private function _gen_return_sn()
    {
        
        /* 选择一个随机的方案 */
        mt_srand ( ( double ) microtime () * 1000000 );
        $timestamp = gmtime ();
        $y = date ( 'y', $timestamp );
        $z = date ( 'z', $timestamp );
        $return_sn = 'RT' . $y . str_pad ( $z, 3, '0', STR_PAD_LEFT ) . str_pad ( mt_rand ( 1, 99999 ), 5, '0', STR_PAD_LEFT );
        
        $orders = $this->find ( "return_order_sn='$return_sn'" );
        if (empty ( $orders )) {
            /* 否则就使用这个单号 */
            return $return_sn;
        }
        
        /* 如果有重复的，则重新生成 */
        return $this->_gen_return_sn ();
    }

    /**
     * 快递邮寄
     *
     * @param unknown $param            
     */
    public function mail($param)
    {
        $return = $this->get ( array (
                'conditions' => 'id=' . $param['id'],
                'fields' => 'id,user_id,order_id,return_order_sn,quantity,order_goods_id,status,source_type,remark,log' 
        ) );
        // 权限校验
        if ($param['user_id'] != $return['user_id']) {
            $this->error = 'no_access';
            return false;
        }
        // 状态校验
        if ($return['status'] != RETURN_AUDITD) {
            $this->error = 'wrong_status';
            return false;
        }
        $data['status'] = RETURN_MAILED;
        $data['express_sn'] = $param['express_sn'];
        $data['log'] = $this->_create_log ( unserialize ( $return['log'] ), $param['user_id'], $param['user_name'], '邮寄商品' . ($param['remark'] ? ':' . $param['remark'] : '') );
        $ret = $this->edit ( 'id=' . $param['id'], $data );
        /* 共享商品卖家审核后,通知仓库验货 */
        if ($return['source_type'] == 2 && $ret) {
            $goods_num = m ( 'ordergoods' )->get ( array (
                    'conditions' => 'rec_id=' . $return['order_goods_id'],
                    'fields' => 'goods_number' 
            ) );
            $query = "&order_id={$return['order_id']}&return_order_sn={$return['return_order_sn']}&goods_number={$goods_num['goods_number']}&quantity={$return['quantity']}&invoice_no={$param['express_sn']}&remark={$return['remark']}";
            sendPost ( WMS_URL . 'retreatOrder', $query );
        }
        return $ret;
    }

    /**
     * 卖家审核
     *
     * @param unknown $param            
     */
    public function audit($param)
    {
        $return = $this->get ( array (
                'conditions' => 'id=' . $param['id'],
                'fields' => 'id,seller_id,status,log' 
        ) );
        // 权限校验
        if ($param['seller_id'] != $return['seller_id']) {
            $this->error = 'no_access';
            return false;
        }
        // 状态校验 已申请
        if ($return['status'] != RETURN_APPLY) {
            $this->error = 'wrong_status';
            return false;
        }
        $data['status'] = $param['audit'] == 1 ? RETURN_AUDITD : RETURN_CLOSED;
        $msg = $param['audit'] == 1 ? '审核通过' : ('关闭申请' . ($param['remark'] ? ':' . $param['remark'] : ''));
        $data['log'] = $this->_create_log ( unserialize ( $return['log'] ), $param['user_id'], $param['user_name'], $msg );
        return $this->edit ( 'id=' . $param['id'], $data );
    }

    /**
     * 卖家收货
     *
     * @param unknown $param            
     */
    public function receive($param)
    {
        $return = $this->get ( array (
                'conditions' => 'id=' . $param['id'],
                'fields' => 'id,seller_id,status,source_type,log' 
        ) );
        // 权限校验
        if ($param['seller_id'] != $return['seller_id'] || $return['source_type'] != 1) {
            $this->error = 'no_access';
            return false;
        }
        // 状态校验
        if ($return['status'] != RETURN_MAILED) {
            $this->error = 'wrong_status';
            return false;
        }
        $data['status'] = RETURN_RECEIPTED;
        $data['log'] = $this->_create_log ( unserialize ( $return['log'] ), $param['user_id'], $param['user_name'], '收到退货' . ($param['remark'] ? ':' . $param['remark'] : '') );
        
        return $this->edit ( 'id=' . $param['id'], $data );
    }

    /**
     * 卖家验货
     *
     * @param unknown $param            
     */
    public function examine($param)
    {
        $return = $this->get ( array (
                'conditions' => 'id=' . $param['id'],
                'fields' => 'id,user_id,order_id,order_goods_id,quantity,seller_id,status,source_type,log' 
        ) );
        // 权限校验 卖家 自营
        if ($param['seller_id'] != $return['seller_id'] || $return['source_type'] != 1) {
            $this->error = 'no_access';
            return false;
        }
        // 状态校验
        if ($return['status'] != RETURN_RECEIPTED) {
            $this->error = 'wrong_status';
            return false;
        }
        $data['status'] = $param['examine'] == 1 ? RETURN_EXAMINE_OK : RETURN_EXAMINE_REJECT;
        $msg = $param['examine'] == 1 ? '验货通过' . ($param['remark'] ? ':' . $param['remark'] : '') : '验货不通过' . ($param['remark'] ? ':' . $param['remark'] : '');
        $data['log'] = $this->_create_log ( unserialize ( $return['log'] ), $param['user_id'], $param['user_name'], $msg );
        $ret = $this->edit ( 'id=' . $param['id'], $data );
        /* 审核通过后生成退款单 申请退款 */
        if ($ret && $param['examine'] == 1) {
            $ordergoods = m ( 'ordergoods' )->get ( array (
                    'conditions' => 'rec_id=' . $return['order_goods_id'],
                    'fields' => 'price' 
            ) );
            $rparam['user_id'] = $return['user_id'];
            $rparam['order_id'] = $return['order_id'];
            $rparam['seller_id'] = $param['seller_id'];
            $rparam['return_goods_id'] = $return['id'];
            $rparam['amount'] = $ordergoods['price'] * $return['quantity'];
            $rparam['info'] = '退货退款';
            $rm = m ( 'refund' );
            $ret = $rm->apply ( $rparam, 2 );
            if (!ret) {
                $this->error = $rm->getError ();
            }
            return $ret;
        }
        return $ret;
    }

    public function convert_status($status = 0)
    {
        switch ($status) {
            case RETURN_APPLY :
                return '已申请';
            case RETURN_AUDITD :
                return '已审核';
            case RETURN_CLOSED :
                return '已关闭';
            case RETURN_MAILED :
                return '买家已邮寄';
            case RETURN_EXAMINE_OK :
                return '验货通过';
            case RETURN_EXAMINE_REJECT :
                return '验货不通过';
            case RETURN_RECEIPTED :
                return '卖家已收货';
            case RETURN_COMPLETED :
                return '退货完成';
        }
    }

    public function getError()
    {
        return $this->error;
    }
}

?>