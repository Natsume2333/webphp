<?php
/**
 * 配置文件
 */

$db_data = require_once DOCUMENT_ROOT.'/public/db.php';

return [
    // 数据库类型
    'type'     => 'mysql',
    // 服务器地址
    'hostname' => $db_data['hostname'],
    // 数据库名
    'database' => $db_data['database'],
    // 用户名
    'username' => $db_data['username'],
    // 密码
    'password' => $db_data['password'],
    // 端口
    'hostport' => $db_data['hostport'],
    // 数据库编码默认采用utf8
    'charset'  => 'utf8mb4',
    // 数据库表前缀
    'prefix'   => 'cmf_',
    "authcode" => 'OvJIeCuO1AhIof5foR',
    //#COOKIE_PREFIX#
];
