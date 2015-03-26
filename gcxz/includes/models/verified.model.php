<?php
/* 注册 */
define ( 'MSGKEY_REG', 'SMS_REGISTER' );
/**
 * 认证模型，处理各种账号验证，如短信验证
 * @author admin
 *
 */
class VerifiedModel extends BaseModel
{
    var $table = 'verified_code';
    var $prikey = 'id';
    var $_name = 'verified_code';

    public function sendSms($phone, $msg)
    {
        return true;
    }

    public function saveCode($userkey, $code, $msgkey)
    {
        $map['userkey'] = $userkey;
        $map['code'] = $code;
        $map['msgkey'] = $msgkey;
        $map['ctime'] = time ();
        return $this->add ( $map );
    }

    /**
     * 校验验证码
     *
     * @param unknown $userkey            
     * @param unknown $code            
     * @param unknown $type            
     */
    function checkCode($userkey, $code, $type)
    {
        // 短信验证码校验
        if (!$code) {
            $ret['code'] = 0;
            $ret['msg'] = '无效的验证码';
            return $ret;
        }
        $vcode = $this->get ( array (
                'conditions' => "userkey='{$userkey}' and msgkey='{$type}' and code='{$code}' and used=0",
                'fields' => 'ctime',
                'limit' => 1 
        ) );
        if (empty ( $vcode )) {
            $ret['code'] = 0;
            $ret['msg'] = '错误的验证码';
            return $ret;
        }
        // 短信验证码有效时间30分钟
        if ($vcode['ctime'] + 1800 < time ()) {
            $ret['code'] = 0;
            $ret['msg'] = '验证码已过期';
            return $ret;
        }
        return true;
    }
    /**
     * 标记验证码已使用
     * @param unknown $userkey
     * @param unknown $type
     */
    function sign_used($userkey,$type){
        return $this->edit("userkey='{$userkey}' and msgkey='{$type}' and used=0", array('used'=>1));
    }
}

?>
