<?php

/* 收货地址 address */
class CmsgroupModel extends BaseModel
{
    var $table  = 'cms_left';
    var $prikey = 'id';
    var $_name  = 'cms_left';
    var $_relation = array(
       // 一个公告属于一个分组
        'has_contents' => array(
            'model'             => 'cmscontent',
            'type'              => HAS_MANY,
            'foreign_key'       => 'gid',
        ),
     );
    
}

?>