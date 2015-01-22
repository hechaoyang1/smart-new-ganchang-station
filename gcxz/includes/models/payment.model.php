<?php

/* 支付方式 payment */
class PaymentModel extends BaseModel
{
    var $table  = 'payment';
    var $prikey = 'payment_id';
    var $_name  = 'payment';
    /**
     *    获取已启用的
     *
     *    @author    Garbin
     *    @param     int $store_id
     *    @return    array
     */
    function get_enabled()
    {
        return $this->find(array(
            'conditions'    => "enabled=1",
            'order'         => 'sort_order',
        ));
    }

    /*---------对内置支付方式的操作---------*/

    /**
     *    获取内置支付方式
     *
     *    @author    Garbin
     *    @param     array $withe_list 白名单
     *    @return    array
     */
    function get_builtin($white_list = null)
    {
        static $payments = null;
        if ($payments === null)
        {
            $payment_dir = ROOT_PATH . '/includes/payments';
            $dir = dir($payment_dir);
            $payments = array();
            while (false !== ($entry = $dir->read()))
            {
                /* 隐藏文件，当前目录，上一级，排除 */
                if ($entry{0} == '.')
                {
                    continue;
                }

                if (is_array($white_list) && !in_array($entry, $white_list))
                {
                    continue;
                }

                /* 获取支付方式信息 */
                $payments[$entry] = $this->get_builtin_info($entry);
            }
        }
        if (is_array($payments))
        {
            uksort($payments, "cmp_payment");
        }

        return $payments;
    }

    /**
     *    获取内置支付方式的配置信息
     *
     *    @author    Garbin
     *    @param     string $code
     *    @return    array
     */
    function get_builtin_info($code)
    {
        Lang::load(lang_file('payment/' . $code));
        $payment_path = ROOT_PATH . '/includes/payments/' . $code . '/payment.info.php';

        return include($payment_path);
    }

    /**
     *    启用内置支付方式
     *
     *    @author    Garbin
     *    @param     string $code
     *    @return    bool
     */
    function enable_builtin($code)
    {
        
        return $this->save_white_list($white_list);
    }

    /**
     *    禁用内置支付方式
     *
     *    @author    Garbin
     *    @param     string $code
     *    @return    void
     */
    function disable_builtin($code)
    {
        $white_list = $this->get_white_list();
        $index = array_search($code, $white_list);
        if (false !== $index)
        {
            unset($white_list[$index]);

            return $this->save_white_list($white_list);
        }

        return false;
    }

    /**
     *    判断指定code的payment是否在白名单中
     *
     *    @author    Garbin
     *    @param     string $code
     *    @return    bool
     */
    function in_white_list($code)
    {
        return $this->db->query("select 1 from ecm_payment where payment_code='$code'");
    }
}

/* 比较函数，实现支付方式排序 */
function cmp_payment($a, $b)
{
    if ($b == 'alipay')
    {
        return 1;
    }
    elseif ($b == 'tenpay2' && $a != 'alipay')
    {
        return 1;
    }
    elseif ($b == 'tenpay' && $a != 'alipay' && $a != 'tenpay2')
    {
        return 1;
    }
    else
    {
        return -1;
    }
}

?>