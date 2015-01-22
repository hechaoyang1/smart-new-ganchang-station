<?php

/* CMS 新闻公告系统 */
class CmsApp extends MallbaseApp
{
    function index()
    {
        $m=m('cmscontent');
        $groupm=m('cmsgroup');
        $top_new=$groupm->get(array(
                'conditions'=>'title=\'热点资讯\'',
                'fields'=>'id,title'
        ));
        $top_house=$groupm->get(array(
                'conditions'=>'title=\'房产信息\'',
                'fields'=>'id,title'
        ));
        $top_recu=$groupm->get(array(
                'conditions'=>'title=\'本地招聘\'',
                'fields'=>'id,title'
        ));
        $top_trans=$groupm->get(array(
                'conditions'=>'title=\'二手交易\'',
                'fields'=>'id,title'
        ));
        $top_travel=$groupm->get(array(
                'conditions'=>'title=\'旅游\'',
                'fields'=>'id,title'
        ));
        
        $news=$this->getList($top_new['id'],18);
        $houses=$this->getList($top_house['id'],8,true);
        $trans=$this->getList($top_trans['id'],8,true);
        $travel=$this->getList($top_travel['id'],8);
        $recus=$this->getList($top_recu['id'],8,true);
        
        $this->assign('top_new',$top_new);
        $this->assign('top_house',$top_house);
        $this->assign('top_recu',$top_recu);
        $this->assign('top_trans',$top_trans);
        $this->assign('top_travel',$top_travel);
        $this->assign('news',$news);
        $this->assign('houses',$houses);
        $this->assign('trans',$trans);
        $this->assign('travel',$travel);
        $this->assign('recus',$recus);

        // 商品分类
        $gcategory_list=$this->_get_site_gcategory();
        $this->assign('gcategory_list',$gcategory_list);
        
        $this->assign('tab','zxzx');
        $this->display('cms.index.html');
    }
    function detail(){
        $id=intval($_GET['id']);
        if(!$id){
            $this->show_warning('empty');
            return;
        }
        $content=m('cmscontent')->get('id='.$id);
        if(!$content){
            $this->show_warning('empty');
            return;
        }
        $content['create_time']=date('Y-m-d');
        $this->assign('content',$content);
        $this->display('cms.detail.html');
    }
    
     private function getList($type,$limit=10,$date=false){
        static $m;
        if(!$m){
            $m=m('cmscontent');
        }
        $data=$m->getList($type,$limit);
        if($date){
            foreach ($data as &$v){
                $v['create_time']=date('n-j',$v['create_time']);
            }
        }
        return $data;
    } 
}

?>
