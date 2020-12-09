<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/29
 * Time: 11:12
 */

namespace app\api\controller;

use alipay_app_pay;
use kuaijiealipay_app_pay;
use qianyingalipay_app_pay;
use qianyingnewalipay_app_pay;
use think\Exception;
use wechat_app_pay;
use mao_app_pay;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class NotifyApi extends Base
{
    //回调测试
   /* public function test(){

        pay_call_service('1544861030100822');
    }*/
    //千应支付回调
    public function qianying_notify(){

        require_once DOCUMENT_ROOT."/system/pay_class/qianyingalipay_app_pay_menu.php";
        $o = new qianyingalipay_app_pay();
        file_put_contents(DOCUMENT_ROOT."/public/ealipay_".date("Y-m-dHis").".txt",print_r($_REQUEST,true));
        $o->notify($_REQUEST);
    }


    //快接支付回调
    public function kuaijie_notify(){

        require_once DOCUMENT_ROOT."/system/pay_class/kuaijiealipay_app_pay_menu.php";
        $o = new kuaijiealipay_app_pay();
        file_put_contents(DOCUMENT_ROOT."/public/ealipay_".date("Y-m-dHis").".txt",print_r($_REQUEST,true));
        $o->notify($_REQUEST);
    }

    //官方支付宝回调
    public function alipay_notify(){

        require_once DOCUMENT_ROOT."/system/pay_class/alipay_app_pay_menu.php";
        $o = new alipay_app_pay();
        file_put_contents(DOCUMENT_ROOT."/public/ealipay_".date("Y-m-dHis").".txt",print_r($_REQUEST,true));
        $o->notify($_REQUEST);
    }
    //官方微信回调
    public function wechatpay_notify(){
		$class_name="wechat_app_pay";
		bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        // require_once DOCUMENT_ROOT."/system/pay_class/wechat_app_pay_menu.php";
        // $o = new wechat_app_pay();
        file_put_contents(DOCUMENT_ROOT."/public/ealipay_".date("Y-m-dHis").".txt",print_r($_REQUEST,true));
        $o->notify($_REQUEST);
    }

    public function qianyingnew_notify(){
        require_once DOCUMENT_ROOT."/system/pay_class/qianyingnewalipay_app_pay_menu.php";
        $o = new qianyingnewalipay_app_pay();
        file_put_contents(DOCUMENT_ROOT."/public/ealipay_".date("Y-m-dHis").".txt",print_r($_REQUEST,true));
        $o->notify($_REQUEST);
    }



    /***
     * mao支付回调方法
     */
    public function mao_notify(){


            //require_once DOCUMENT_ROOT.'/system/pay/kuaijie/common.php';
            //require_once DOCUMENT_ROOT."/system/pay_class/mao_app_pay_menu.php";
            //bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/mao_app_pay_menu.php");
            //require_once DOCUMENT_ROOT."/system/pay_class/mao_app_pay_menu.php";
           //$class_name = "mao_app_pay";
           // bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" ."mao_app_pay". "_menu.php");


            $o = new mao_app_pay();
            echo "eee".get_class($o)."ee";
            $o->notify($_REQUEST);
            //file_put_contents(DOCUMENT_ROOT."/public/ealipay_".date("Y-m-dHis").".txt",print_r($_REQUEST,true));

    }

    public function notify(){

    }
}