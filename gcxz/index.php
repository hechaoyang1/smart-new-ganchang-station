<?php
error_reporting(0);

// 当前文件名
define('_PHP_FILE_', rtrim($_SERVER["SCRIPT_NAME"],'/'));

// 网站URL根目录
$_root = dirname(_PHP_FILE_);
define('__ROOT__',  (($_root=='/' || $_root=='\\')?'':rtrim($_root,'/')));

define('SITE_DOMAIN'	,	strip_tags($_SERVER['HTTP_HOST']));
define('SITE_URL'		,	'http://'.SITE_DOMAIN.__ROOT__);

define('ROOT_PATH', dirname(__FILE__));
include(ROOT_PATH . '/eccore/ecmall.php');

/* 定义配置信息 */
ecm_define(ROOT_PATH . '/data/config.inc.php');

/* 启动ECMall */
ECMall::startup(array(
    'default_app'   =>  'default',
    'default_act'   =>  'index',
    'app_root'      =>  ROOT_PATH . '/app',
    'external_libs' =>  array(
        ROOT_PATH . '/includes/global.lib.php',
        ROOT_PATH . '/includes/libraries/time.lib.php',
        ROOT_PATH . '/includes/ecapp.base.php',
        ROOT_PATH . '/includes/plugin.base.php',
        ROOT_PATH . '/app/frontend.base.php',
        ROOT_PATH . '/includes/subdomain.inc.php',
    ),
));
?>
