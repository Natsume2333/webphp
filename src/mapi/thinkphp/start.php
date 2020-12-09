<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------

namespace think;
use system\RedisPackage;

// ThinkPHP 引导文件
// 1. 加载基础文件

require __DIR__ . '/base.php';

//全局常量
require_once $_SERVER['DOCUMENT_ROOT'] . '/system/define.php';

//redis
require_once DOCUMENT_ROOT . '/system/RedisPackage.php';
$redis = new RedisPackage();

//加载前后台公共函数
require_once DOCUMENT_ROOT . '/system/common.php';

// 2. 执行应用
App::run()->send();
