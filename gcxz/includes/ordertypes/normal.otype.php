<?php

/**
 *    普通订单类型
 *
 *    @author    Garbin
 *    @usage    none
 */
class NormalOrder extends BaseOrder
{
    var $_name = 'normal';

    /**
     *    查看订单
     *
     *    @author    Garbin
     *    @param     int $order_id
     *    @param     array $order_info
     *    @return    array
     */
    function get_order_detail($order_id, $order_info)
    {
        if (!$order_id)
        {
            return array();
        }

        /* 获取商品列表 */
        $data['goods_list'] =   $this->_get_goods_list($order_id);

        /* 配关信息 */
        $data['order_extm'] =   $this->_get_order_extm($order_id);

        /* 支付方式信息 */
        if ($order_info['payment_id'])
        {
            $payment_model      =& m('payment');
            $payment_info       =  $payment_model->get("payment_id={$order_info['payment_id']}");
            $data['payment_info']   =   $payment_info;
        }

        /* 订单操作日志 */
        $data['order_logs'] =   $this->_get_order_logs($order_id);

        return array('data' => $data);
    }

    /* 显示订单表单 */
    function get_order_form($store_id)
    {
        $data = array();
        $template = 'order.form.html';

        $visitor =& env('visitor');

        /* 获取我的收货地址 */
        $data['my_address']         = $this->_get_my_address($visitor->get('user_id'));
        $data['addresses']          =   ecm_json_encode($data['my_address']);
        $data['regions']            = $this->_get_regions();

        /* 配送方式 */
        $data['shipping_methods']   = $this->_get_shipping_methods($store_id);
        if (empty($data['shipping_methods']))
        {
            $this->_error('no_shipping_methods');

            return false;
        }
        $data['shippings']          = ecm_json_encode($data['shipping_methods']);
        foreach ($data['shipping_methods'] as $shipping)
        {
            $data['shipping_options'][$shipping['shipping_id']] = $shipping['shipping_name'];
        }

        return array('data' => $data, 'template' => $template);
    }

    /**
     *    提交生成订单，外部告诉我要下的单的商品类型及用户填写的表单数据以及商品数据，我生成好订单后返回订单ID
     *
     *    @author    Garbin
     *    @param     array $data
     *    @return    int
     */
    function submit_order($data)
    {
        /* 释放goods_info和post两个变量 */
        extract($data);
        /* 处理订单基本信息 */
        $base_info = $this->_handle_order_info($goods_info, $post);
        if (!$base_info)
        {
            /* 基本信息验证不通过 */

            return 0;
        }
        /* 处理订单收货人信息 */
        $consignee_info = $this->_handle_consignee_info($goods_info, $post);

        if (!$consignee_info)
        {
            /* 收货人信息验证不通过 */
            return 0;
        }
        /* 验证支付方式 */
        $payment_model =& m('payment');
        $payment_info  = $payment_model->get("payment_code = '{$post['payment_code']}' ");
        if (!$payment_info)
        {
        	/* 插入基本信息失败 */
        	$this->_error ( 'no_such_payment' );
        	return 0;
        }
        
		
        //获取拆分后的订单
        $orders = $base_info['orders'];
        $orderIds = array();
        /* 至此说明订单的信息都是可靠的，可以开始入库了 */
        foreach ($orders as $order){
        	/* 插入订单基本信息 */
        	//暂时不考虑优惠等情况
        	$order ['discount'] = 0;
			$order ['order_sn'] = $this->_gen_order_sn ();
			$order ['type'] = $base_info ['type'];
			$order ['extension'] = $base_info ['extension'];
			$order ['seller_id'] = $base_info ['seller_id'];
			$order ['seller_name'] = $base_info ['seller_name'];
			$order ['buyer_id'] = $base_info ['buyer_id'];
			$order ['buyer_name'] = $base_info ['buyer_name'];
			$order ['buyer_email'] = $base_info ['buyer_email'];
			$order ['status'] = $base_info ['status'];
			$order ['add_time'] = $base_info ['add_time'];
			if('cod' == $payment_info['payment_code']){
				$order ['pay_time'] = gmtime ();
			}
			$order ['discount'] = $base_info ['discount'];
			$order ['anonymous'] = $base_info ['anonymous'];
			$order ['postscript'] = $base_info ['postscript'];
			$order ['payment_id']    =  $payment_info['payment_id'];
			$order ['payment_code']  =  $payment_info['payment_code'];
			$order ['payment_name']  =  $payment_info['payment_name'];
			$shipping_fee = $this->_get_shipping_fee ( $order ['goods'], $consignee_info ['shipping_id'] );
			//计算goods_amount
			$order ['goods_amount'] = 0;
			foreach ( $order ['goods'] as $goodValue ) {
				$order ['goods_amount'] += $goodValue['price']*$goodValue['quantity'];
			}
        	//加上商品的运费
        	$order['order_amount'] = $order ['goods_amount'] + $shipping_fee;
			$order_model = & m ( 'order' );
			$order_id = $order_model->add ( $order );
			if (! $order_id) {
				$order_model->drop ( $orderIds );
				/* 插入基本信息失败 */
				$this->_error ( 'create_order_failed' );
				return 0;
			}
			array_push($orderIds, $order_id);
				/* 插入收货人信息 */
			$consignee_info ['order_id'] = $order_id;
			$consignee_info ['shipping_fee'] = $shipping_fee;
			$order_extm_model = & m ( 'orderextm' );
			$order_extm_model->add ( $consignee_info );
			
			/* 插入商品信息 */
			$goods_items = array ();
			foreach ( $order ['goods'] as $key => $value ) {
				$goods_items [] = array (
						'order_id' => $order_id,
						'goods_id' => $value ['goods_id'],
						'goods_name' => $value ['goods_name'],
						'spec_id' => $value ['spec_id'],
						'specification' => $value ['specification'],
						'price' => $value ['price'],
						'quantity' => $value ['quantity'],
						'goods_image' => $value ['goods_image'] ,
						'source_type' => $value ['source_type'],
						'goods_number' => $value ['sku']
				);
			}
			$order_goods_model = & m ( 'ordergoods' );
			$good_id = $order_goods_model->add ( addslashes_deep ( $goods_items)); //防止二次注入
			if(!$good_id){
				$order_model->drop ( $orderIds );
				$order_goods_model -> drop ( $orderIds );
				/* 插入基本信息失败 */
				$this->_error ( 'create_order_failed' );
				return 0;
			}
        }
        return $orderIds;
//         /* 至此说明订单的信息都是可靠的，可以开始入库了 */

//         /* 插入订单基本信息 */
//         //订单总实际总金额，可能还会在此减去折扣等费用
//         $base_info['order_amount']  =   $base_info['goods_amount'] + $consignee_info['shipping_fee'] - $base_info['discount'];
        
//         /* 如果优惠金额大于商品总额和运费的总和 */
//         if ($base_info['order_amount'] < 0)
//         {
//             $base_info['order_amount'] = 0;
//             $base_info['discount'] = $base_info['goods_amount'] + $consignee_info['shipping_fee'];
//         }
//         $order_model =& m('order');
//         $order_id    = $order_model->add($base_info);

//         if (!$order_id)
//         {
//             /* 插入基本信息失败 */
//             $this->_error('create_order_failed');

//             return 0;
//         }

//         /* 插入收货人信息 */
//         $consignee_info['order_id'] = $order_id;
//         $order_extm_model =& m('orderextm');
//         $order_extm_model->add($consignee_info);

//         /* 插入商品信息 */
//         $goods_items = array();
//         foreach ($goods_info['items'] as $key => $value)
//         {
//             $goods_items[] = array(
//                 'order_id'      =>  $order_id,
//                 'goods_id'      =>  $value['goods_id'],
//                 'goods_name'    =>  $value['goods_name'],
//                 'spec_id'       =>  $value['spec_id'],
//                 'specification' =>  $value['specification'],
//                 'price'         =>  $value['price'],
//                 'quantity'      =>  $value['quantity'],
//                 'goods_image'   =>  $value['goods_image'],
//             );
//         }
//         $order_goods_model =& m('ordergoods');
//         $order_goods_model->add(addslashes_deep($goods_items)); //防止二次注入

//         return $order_id;
    }
}

?>