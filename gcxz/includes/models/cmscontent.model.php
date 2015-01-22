<?php

/* 收货地址 address */
class CmscontentModel extends BaseModel
{
    var $table  = 'cms_content';
    var $prikey = 'id';
    var $_name  = 'cms_content';
    var $alias='cc';
    var $_relation = array(
       // 一个公告属于一个分组
        'belong_to_group' => array(
            'model'             => 'cmsgroup',
            'type'              => BELONGS_TO,
            'foreign_key'       => 'gid',
        ),
     );
    
    public function getList($typeId,$limit=10){
        $sql='select cc.id,cc.title,cc.create_time,cc.uid,cc.gid,cc.is_top,cc.sort from ecm_cms_content cc where gid='.$typeId.' order by is_top desc,sort asc,create_time desc limit 0,'.$limit;
        return $this->getAll($sql);
    }
    
    /**
     * 新闻列表
     * @param number $limit
     */
    public function getNewsList($limit=10){
        return $this->getList(1,$limit=10);
    }

    /**
     * 公告列表
     * @param number $limit
     */
    public function getNoticeList($limit=10){
        return $this->getList(2,$limit=10);
    }
}

?>