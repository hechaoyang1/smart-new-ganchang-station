<?php

/**
 *    通联支付方式插件
 *
 *    @author    Garbin
 *    @usage    none
 */
class AllinpayPayment extends BasePayment
{
    /* 支付宝网关 */
    var $_gateway = 'http://ceshi.allinpay.com/gateway/index.do';
    var $_code = 'allinpay';

    function BasePayment($payment_info = array())
    {
        $this->_info = $payment_info;
        // TODO 通联相关配置信息，这是个临时方案
        $this->_config = include (ROOT_PATH . '/includes/payments/allinpay/payment.cfg.php');
    }

    /**
     * 获取支付表单
     *
     * @author Garbin
     * @param array $order_infos
     *            待支付的订单信息，必须包含总费用
     * @return array
     */
    function get_payform($order_infos)
    {
        $order_ids = array ();
        /* 所有订单总价 */
        $amount = 0;
        foreach ( $order_infos as &$v ) {
            $amount += $v['order_amount'];
            $order_ids[] = $v['order_id'];
        }
        $order_ids = implode ( ',', $order_ids );
        $param['inputCharset'] = $this->_config['inputCharset'];
        $param['pickupUrl'] = $this->_create_return_url ( $order_ids,$order_infos );
        $param['receiveUrl'] = $this->_create_notify_url ( $order_ids );
        $param['version'] = $this->_config['version'];
        $param['signType'] = $this->_config['signType'];
        $param['merchantId'] = $this->_config['merchantId'];
        $order_sn_time = $this->_get_trade_sn_and_order_time ( $order_infos );
        /* 商户系统订单号 */
        $param['orderNo'] = $order_sn_time['orderNo'];
        /* 订单总金额 单位分 */
        $param['orderAmount'] = intval ( $amount * 100 );
        /* 时间戳 */
        $param['orderDatetime'] = $order_sn_time['orderDatetime'];
        $goods_info = $this->_get_goods_info ( $order_ids );
        /* 商品名称 */
        $param['productName'] = $goods_info['goods_name'];
        /* 商品数量 */
        $param['productNum'] = $goods_info['quanlity'];
        $param['payType'] = $this->_config['payType'];
        $param['tradeNature'] = $this->_config['tradeNature'];
        $param['key'] = $this->_config['key'];
        $param['signMsg'] = $this->_get_sign ( $param );
        unset ( $param['key'] );
        return $this->_create_payform ( 'POST', $param );
    }

    /**
     * 获得订单商品信息
     *
     * @param unknown $order_ids            
     * @return multitype:string Ambigous <number, unknown>
     */
    function _get_goods_info($order_ids)
    {
        $goods = m ( 'ordergoods' )->find ( array (
                'conditions' => "order_id in($order_ids)",
                'fields' => 'quantity,goods_name' 
        ) );
        $goods_name = '';
        $goods_quanlity = 0;
        foreach ( $goods as &$v ) {
            $goods_name .= $v['goods_name'] . '|';
            $goods_quanlity += $v['quantity'];
        }
        $goods_name = substr ( $goods_name, 0, -1 );
        if (strlen ( $goods_name ) > 255) {
            $goods_name = substr ( $goods_name, 0, 252 ) . '...';
        }
        return array (
                'goods_name' => $goods_name,
                'quanlity' => $goods_quanlity 
        );
    }

    /**
     * 生成商户订单号
     */
    function _get_trade_sn_and_order_time($order_infos)
    {
        $order_info = current ( $order_infos );
        $out_trade_sn = 'PN' . date ( 'YmdHis' ) . $order_info['buyer_id'] . str_pad ( mt_rand ( 1, 99999 ), 5, '0', STR_PAD_LEFT );
        $model_order = & m ( 'order' );
        $order_time = date ( 'YmdHis' );
        $orders = $model_order->find ( 'out_trade_sn=' . $out_trade_sn );
        /* 重复性校验 */
        if (empty ( $orders )) {
            /* 将此数据写入订单中 */
            $order_ids = array ();
            foreach ( $order_infos as &$v ) {
                $order_ids[] = $v['order_id'];
            }
            $order_ids = implode ( ',', $order_ids );
            $model_order->edit ( "order_id in ($order_ids)", array (
                    'out_trade_sn' => $out_trade_sn,
                    'order_date_time' => $order_time 
            ) );
            return array (
                    'orderNo' => $out_trade_sn,
                    'orderDatetime' => $order_time 
            );
        }
        return $this->_get_trade_sn_and_order_time ( $order_infos );
    }

    /**
     * 获取通知地址
     *
     * @author Garbin
     * @param int $store_id            
     * @param int $order_id            
     * @return string
     */
    function _create_notify_url($order_ids)
    {
        return SITE_URL . "/index.php?app=paynotify&act=notify&order_id={$order_ids}";
    }

    /**
     * 获取返回地址
     *
     * @author Garbin
     * @param int $store_id            
     * @param int $order_id            
     * @return string
     */
    function _create_return_url($order_ids,$order_infos)
    {
        $order_sn=array();
        foreach ($order_infos as &$v){
            $order_sn[]=$v['order_sn'];
        }
        return SITE_URL . "/index.php?app=paynotify&order_id={$order_ids}&order_sn=".implode(',', $order_sn);
    }

    /**
     * 返回通知结果
     *
     * @author Garbin
     * @param array $order_info            
     * @param bool $strict            
     * @return array
     */
    function verify_notify($order_infos, $strict = false)
    {
        if (empty ( $order_infos )) {
            $this->_error ( 'order_info_empty' );
            
            return false;
        }
        
        /* 初始化所需数据 */
        $notify = $this->_get_notify ();
        
        /* 验证通知是否可信 */
        $sign_result = $this->_verify_sign ( $notify );
        if (!$sign_result) {
            /* 若本地签名与网关签名不一致，说明签名不可信 */
            $this->_error ( 'sign_inconsistent' );
            return false;
        }
        
        /* ----------通知验证结束---------- */

        /*----------本地验证开始----------*/
        /* 验证与本地信息是否匹配 */
        /* 这里不只是付款通知，有可能是发货通知，确认收货通知 */
        $amount = 0;
        foreach ( $order_infos as $order_info ) {
            if ($order_info['out_trade_sn'] != $notify['orderNo']) {
                /* 通知中的订单与欲改变的订单不一致 */
                $this->_error ( 'order_inconsistent' );
                
                return false;
            }
            $amount += $order_info['order_amount'];
        }
        if (intval ( $amount * 100 ) != $notify['payAmount']) {
            /* 支付的金额与实际金额不一致 */
            $this->_error ( 'price_inconsistent' );
            return false;
        }
        // 至此，说明通知是可信的，订单也是对应的，可信的
        
        /* 按通知结果返回相应的结果 */
        switch ($notify['payResult']) {
            case '1' : // 已付款
                
                $order_status = ORDER_PENDED;
                break;
            
            case '0' : // 未付款
                
                $order_status = ORDER_PENDING;
                break;
            default :
                $this->_error ( 'undefined_status' );
                return false;
                break;
        }
        return array (
                'target' => $order_status 
        );
    }

    /**
     * 查询通知是否有效
     *
     * @author Garbin
     * @param string $notify_id            
     * @return string
     */
    function _query_notify($notify_id)
    {
        $query_url = "http://notify.alipay.com/trade/notify_query.do?partner={$this->_config['alipay_partner']}&notify_id={$notify_id}";
        
        return (ecm_fopen ( $query_url, 60 ) === 'true');
    }

    /**
     * 获取签名字符串
     *
     * @author Garbin
     * @param array $params            
     * @return string
     */
    function _get_sign($params)
    {
        $signkey = $params['key'];
        /* 去除不参与签名的数据 */
        unset ( $params['key'] );
        $sign = '';
        foreach ( $params as $key => $value ) {
            $sign .= "{$key}={$value}&";
        }
        $sign .= 'key=' . $signkey;
        return strtoupper ( md5 ( $sign ) );
    }

    /**
     * 验证签名是否可信
     *
     * @author Garbin
     * @param array $notify            
     * @return bool
     */
    function _verify_sign($notify)
    {
        $notify['merchantId'] && $param['merchantId'] = $this->_config['merchantId'];
        $notify['version'] && $param['version'] = $notify['version'];
        $notify['language'] && $param['language'] = $notify['language'];
        ($notify['signType'] || $notify['signType'] === '0') && $param['signType'] = $notify['signType'];
        ($notify['payType'] || $notify['payType'] === '0') && $param['payType'] = $notify['payType'];
        $notify['issuerId'] && $param['issuerId'] = $notify['issuerId'];
        $notify['paymentOrderId'] && $param['paymentOrderId'] = $notify['paymentOrderId'];
        $notify['orderNo'] && $param['orderNo'] = $notify['orderNo'];
        $notify['orderDatetime'] && $param['orderDatetime'] = $notify['orderDatetime'];
        $notify['orderAmount'] && $param['orderAmount'] = $notify['orderAmount'];
        $notify['payDatetime'] && $param['payDatetime'] = $notify['payDatetime'];
        $notify['payAmount'] && $param['payAmount'] = $notify['payAmount'];
        $notify['ext1'] && $param['ext1'] = $notify['ext1'];
        $notify['ext2'] && $param['ext2'] = $notify['ext2'];
        $notify['payResult'] && $param['payResult'] = $notify['payResult'];
        $notify['errorCode'] && $param['errorCode'] = $notify['errorCode'];
        $notify['returnDatetime'] && $param['returnDatetime'] = $notify['returnDatetime'];
        $param['key'] = $this->_config['key'];
        return $notify['signMsg'] === $this->_get_sign ( $param );
        // $bufSignSrc = '';
        // foreach ( $param as $key => $value ) {
        // $bufSignSrc .= "{$key}={$value}&";
        // }
        // $bufSignSrc = substr ( $bufSignSrc, 0, -1 );
        
        // echo $bufSignSrc;
        // // 验签
        // // 解析publickey.txt文本获取公钥信息
        // $publickeyfile = ROOT_PATH . '/includes/payments/allinpay/publickey.txt';
        // $publickeycontent = file_get_contents ( $publickeyfile );
        // $publickeyarray = explode ( PHP_EOL, $publickeycontent );
        // $publickey = explode ( '=', $publickeyarray[0] );
        // $modulus = explode ( '=', $publickeyarray[1] );
        // echo $publickey[1];
        // echo $modulus[1];
        // require_once ROOT_PATH . '/includes/payments/allinpay/php_rsa.php';
        
        // $keylength = 1024;
        // // 验签结果
        // $verifyResult = rsa_verify ( $bufSignSrc, $notify['signMsg'], $publickey[1], $modulus[1], $keylength, "sha1" );
        // return $verifyResult;
    }
}

?>