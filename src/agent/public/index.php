<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Author: 老猫 <zxxjjforever@163.com>
// +----------------------------------------------------------------------

// [ 入口文件 ]

$global_config = require_once $_SERVER['DOCUMENT_ROOT'].'/public/config.php';

// 调试模式开关
use system\RedisPackage;

define("APP_DEBUG", $global_config['DE_BUG']);

// 定义CMF根目录,可更改此目录
define('CMF_ROOT', __DIR__ . '/../');

// 定义应用目录
define('APP_PATH', CMF_ROOT . 'app/');


// 定义CMF核心包目录
define('CMF_PATH', CMF_ROOT . 'simplewind/cmf/');

// 定义插件目录
define('PLUGINS_PATH', __DIR__ . '/plugins/');

// 定义扩展目录
define('EXTEND_PATH', CMF_ROOT . 'simplewind/extend/');
define('VENDOR_PATH', CMF_ROOT . 'simplewind/vendor/');

// 定义应用的运行时目录
define('RUNTIME_PATH', CMF_ROOT . 'data/runtime/');

// 定义CMF 版本号
define('THINKCMF_VERSION', '5.0.170927');

// 加载框架基础文件
require CMF_ROOT . 'simplewind/thinkphp/base.php';

//全局常量
require_once $_SERVER['DOCUMENT_ROOT'] . '/system/define.php';

//加载前后台公共函数
require_once DOCUMENT_ROOT . '/system/common.php';

//redis
require_once DOCUMENT_ROOT . '/system/RedisPackage.php';
$redis = new RedisPackage();


// 执行应用
\think\App::run()->send();