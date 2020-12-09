<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Dean <zxxjjforever@163.com>
// +----------------------------------------------------------------------

$global_config = require_once $_SERVER['DOCUMENT_ROOT'].'/public/config.php';
return [
    // 应用调试模式
    'app_debug' => $global_config['DE_BUG'],
    // 应用Trace
    'app_trace' => true,

];