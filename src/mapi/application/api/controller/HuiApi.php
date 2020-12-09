<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/19
 * Time: 11:23
 */

namespace app\api\controller;
// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

use think\Log;

class HuiApi extends Base
{
    //充值金币
    public function longfa_notify()
    {
        // file_put_contents(DOCUMENT_ROOT."/public/log/".date("Y-m-dHis").".txt",print_r($_REQUEST,true));
        ///日志路径 mapi/runtime/log/201910
        Log::info('hello log...');
        $class_name = "longfapay_app_pay";
        //echo $class_name;exit;
        //echo DOCUMENT_ROOT."/system/pay_class/".$class_name."_menu.php";exit;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $o->notify($_REQUEST);
    }
    public function jinka_notify()
    {
        // file_put_contents(DOCUMENT_ROOT."/public/log/".date("Y-m-dHis").".txt",print_r($_REQUEST,true));
        ///日志路径 mapi/runtime/log/201910
        Log::info('hello log...');
        $class_name = "jinka_app_pay";
        //echo $class_name;exit;
        //echo DOCUMENT_ROOT."/system/pay_class/".$class_name."_menu.php";exit;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $o->notify($_REQUEST);
    }
    public function qilin_notify()
    {
        // file_put_contents(DOCUMENT_ROOT."/public/log/".date("Y-m-dHis").".txt",print_r($_REQUEST,true));
        ///日志路径 mapi/runtime/log/201910
        Log::info('hello log...');
        $class_name = "qilin_app_pay";
        //echo $class_name;exit;
        //echo DOCUMENT_ROOT."/system/pay_class/".$class_name."_menu.php";exit;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $o->notify($_REQUEST);
    }
    public function mao_notify()
    {
       // file_put_contents(DOCUMENT_ROOT."/public/log/".date("Y-m-dHis").".txt",print_r($_REQUEST,true));
        ///日志路径 mapi/runtime/log/201910
        Log::info('hello log...');
        $class_name = "mao_app_pay";
        //echo $class_name;exit;
        //echo DOCUMENT_ROOT."/system/pay_class/".$class_name."_menu.php";exit;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $o->notify($_REQUEST);
    }
    public function huanshang_notify()
    {
        // file_put_contents(DOCUMENT_ROOT."/public/log/".date("Y-m-dHis").".txt",print_r($_REQUEST,true));
        ///日志路径 mapi/runtime/log/201910
        Log::info('hello log...');
        $class_name = "huanshang_app_pay";
        //echo $class_name;exit;
        //echo DOCUMENT_ROOT."/system/pay_class/".$class_name."_menu.php";exit;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $o->notify($_REQUEST);
    }
}