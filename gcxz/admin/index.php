<?php
error_reporting(0);

// 当前文件名
define('_PHP_FILE_', rtrim($_SERVER["SCRIPT_NAME"],'/'));

// 网站URL根目录
$_root = dirname(_PHP_FILE_);
$_root=(($_root=='/' || $_root=='\\')?'':rtrim($_root,'/'));
if(strlen($_root)){
   $_root=substr($_root, 0,strripos($_root, '/admin')) ;
}
define('__ROOT__', $_root );
define('SITE_DOMAIN'	,	strip_tags($_SERVER['HTTP_HOST']));
define('SITE_URL'		,	'http://'.SITE_DOMAIN.__ROOT__);
/* 应用根目录 */
define('APP_ROOT', dirname(__FILE__));          //该常量只在后台使用
define('ROOT_PATH', dirname(APP_ROOT));   //该常量是ECCore要求的
define('IN_BACKEND', true);
include(ROOT_PATH . '/eccore/ecmall.php');

/* 定义配置信息 */
ecm_define(ROOT_PATH . '/data/config.inc.php');

/* 启动ECMall */
ECMall::startup(array(
    'default_app'   =>  'default',
    'default_act'   =>  'index',
    'app_root'      =>  APP_ROOT . '/app',
    'external_libs' =>  array(
        ROOT_PATH . '/includes/global.lib.php',
        ROOT_PATH . '/includes/libraries/time.lib.php',
        ROOT_PATH . '/includes/ecapp.base.php',
        ROOT_PATH . '/includes/plugin.base.php',
        APP_ROOT . '/app/backend.base.php',
    ),
));

?>