<?php

/**
 *    支付网关通知接口
 *
 *    @author    Garbin
 *    @usage    none
 */
class PaynotifyApp extends MallbaseApp
{

    /**
     * 支付完成后返回的URL，在此只进行提示，不对订单进行任何修改操作,这里不严格验证，不改变订单状态
     *
     * @author Garbin
     * @return void
     */
    function index()
    {
        // 这里是支付宝，财付通等当订单状态改变时的通知地址
        $order_id = isset ( $_GET['order_id'] ) ? t ( $_GET['order_id'] ) : 0; // 哪个订单
        if (!$order_id) {
            /* 无效的通知请求 */
            $this->show_warning ( 'forbidden' );
            
            return;
        }
        
        /* 获取订单信息 */
        $model_order = & m ( 'order' );
        $order_infos = $model_order->find ( array (
                'conditions' => "order_id in($order_id)" 
        ) );
        if (empty ( $order_infos )) {
            /* 没有该订单 */
            $this->show_warning ( 'forbidden' );
            
            return;
        }
        $order_info = current ( $order_infos );
        $model_payment = & m ( 'payment' );
        $payment_info = $model_payment->get ( "payment_code='{$order_info['payment_code']}'" );
        if (empty ( $payment_info )) {
            /* 没有指定的支付方式 */
            $this->show_warning ( 'no_such_payment' );
            
            return;
        }
        
        /* 调用相应的支付方式 */
        $payment = $this->_get_payment ( $order_info['payment_code'], $payment_info );
        
        /* 获取验证结果 */
        $notify_result = $payment->verify_notify ( $order_infos );
        if ($notify_result === false) {
            /* 支付失败 */
            $this->show_warning ( $payment->get_error () );
            
            return;
        }
        // ODO 临时在此也改变订单状态为方便调试，实际发布时应把此段去掉，订单状态的改变以notify为准
//        $this->_change_order_status($order_id, $order_info['extension'], $notify_result);
        
        /* 只有支付时会使用到return_url，所以这里显示的信息是支付成功的提示信息 */
//         $this->_curlocal ( LANG::get ( 'pay_successed' ) );
//         $this->assign ( 'order', $order_info );
//         $this->assign ( 'payment', $payment_info );
//         $this->display ( 'paynotify.index.html' );
        
        //支付完成后重新查询一次，获取支付时间等数据
        $id = $order_info['order_id'];
        $order_info = $model_order->get ( array (
               'conditions' => "order_alias.order_id = $id" ,
               'join'       => 'has_orderextm'
        ) );
        $order_info['order_sn'] = t ( $_GET['order_sn'] );
        $order_info['order_add_time'] = date('Y-m-d H:i:s', $order_info['add_time']);
        $order_info['pay_time'] = date('Y-m-d H:i:s', $order_info['pay_time']);
        $this->assign('order', $order_info);
        $this->display ( 'pay.finish.html' );
    }

    /**
     * 支付完成后，外部网关的通知地址，在此会进行订单状态的改变，这里严格验证，改变订单状态
     *
     * @author Garbin
     * @return void
     */
    function notify()
    {
        // 这里是支付宝，财付通等当订单状态改时的通知地址
        $order_id = 0;
        if (isset ( $_POST['order_id'] )) {
            $order_id = t ( $_POST['order_id'] );
        } else {
            $order_id = t ( $_GET['order_id'] );
        }
        if (!$order_id) {
            /* 无效的通知请求 */
            $this->show_warning ( 'no_such_order' );
            return;
        }
        
        /* 获取订单信息 */
        $model_order = & m ( 'order' );
        $order_infos = $model_order->find ( array (
                'conditions' => "order_id in($order_id)" 
        ) );
        if (empty ( $order_infos )) {
            /* 没有该订单 */
            $this->show_warning ( 'no_such_order' );
            return;
        }
        
        $order_info = current ( $order_infos );
        $model_payment = & m ( 'payment' );
        $payment_info = $model_payment->get ( "payment_code='{$order_info['payment_code']}'" );
        if (empty ( $payment_info )) {
            /* 没有指定的支付方式 */
            $this->show_warning ( 'no_such_payment' );
            return;
        }
        
        /* 调用相应的支付方式 */
        $payment = $this->_get_payment ( $order_info['payment_code'], $payment_info );
        
        /* 获取验证结果 */
        $notify_result = $payment->verify_notify ( $order_infos, true );
        if ($notify_result === false) {
            /* 支付失败 */
            $payment->verify_result ( false );
            return;
        }
        
        // 改变订单状态
        $this->_change_order_status ( $order_id, $order_info['extension'], $notify_result );
        $payment->verify_result ( true );
        
        if ($notify_result['target'] == ORDER_PENDED) {
            /* 发送邮件给卖家，提醒付款成功 */
            $model_member = & m ( 'member' );
            $seller_info = $model_member->get ( $order_info['seller_id'] );
            
            $mail = get_mail ( 'toseller_online_pay_success_notify', array (
                    'order' => $order_info 
            ) );
            $this->_mailto ( $seller_info['email'], addslashes ( $mail['subject'] ), addslashes ( $mail['message'] ) );
            
            /* 同步发送 */
            $this->_sendmail ( true );
        }
    }
    
    /**
     * 付款完成
     */
    function finish()
    {
        $order_ids = isset($_GET['order_id']) ? $_GET['order_id'] : 0;
        $ids = json_decode($order_ids);
        
        if (!$ids)
        {
            $this->show_warning('没有订单信息');
            return;
        }
        
        $model_order =& m('order');
        $og_model =& m('ordergoods');
        foreach ($ids as $order_id)
        {
            $order_info = $model_order->get(array(
                    'fields'        => "*, order.add_time as order_add_time",
                    'conditions'    => "order_alias.order_id={$order_id} AND buyer_id=" . $this->visitor->get('user_id'),
                    'join'          => 'has_orderextm',
            ));
            if (!$order_info)
            {
                $this->show_warning('没有找到订单号：'.$order_id);
                return;
            }
            //检测订单状态
            $share = $og_model->isShareGoods($order_id);
            if($share)
            {
                $status   =   ORDER_ACCEPTED;
            }
            else
            {
                $status   =   ORDER_PENDED;
            }
            if($order_info['status'] == $status)
            {
                $order_info['message'] = '支付成功!';
            }
            else 
            {
                $order_info['message'] = '支付失败!';
            }
        }
        $order_info['order_ids'] = implode(',', $ids);
        $order_info['order_add_time'] = date('Y-m-d H:i:s', $order_info['order_add_time']);
        $order_info['pay_time'] = date('Y-m-d H:i:s', $order_info['pay_time']);
        $this->assign('order', $order_info);
        
        // 商品分类
		$gcategory_mod = m('gcategory');
		$top_category = GcategoryModel::$top_category;
		$top_cate_ids = $gcategory_mod->get_top_cate_ids();
		$this->assign('top_category',$top_category);
		$this->assign('top_cate_ids',$top_cate_ids);
        $this->display ( 'pay.finish.html' );
    }

    /**
     * 改变订单状态
     *
     * @author Garbin
     * @param int $order_id            
     * @param string $order_type            
     * @param array $notify_result            
     * @return void
     */
    function _change_order_status($order_id, $order_type, $notify_result)
    {
        /* 将验证结果传递给订单类型处理 */
        $order_type = & ot ( $order_type );
        $order_type->respond_notify ( $order_id, $notify_result ); // 响应通知
    }
}

?>
