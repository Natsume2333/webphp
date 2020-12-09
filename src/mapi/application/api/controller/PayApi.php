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

class PayApi extends Base
{

    //充值金币
    public function pay_vip()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $pid = intval(input('param.pid'));
        $rule_id = intval(input('param.rid'));//充值规则ID

        $user_info = check_login_token($uid, $token);

        //充值渠道
        $pay_type = db('pay_menu')->find($pid);

        if (!$pay_type) {
            $result['code'] = 0;
            $result['msg'] = '充值类型不存在';
            return_json_encode($result);
        }

        //充值规则
        $rule = db('vip_rule')->find($rule_id);
        if (!$rule) {
            $result['code'] = 0;
            $result['msg'] = '购买规则不存在';
            return_json_encode($result);
        }

        $notice_id = NOW_TIME . $uid;//订单号码
        $order_info = [
            'uid' => $uid,
            'money' => $rule['money'],
            'coin' => 0,
            'refillid' => $rule_id,
            'addtime' => NOW_TIME,
            'status' => 0,
            'type' => 7777777,
            'order_id' => $notice_id,
            'pay_type_id' => $pid,
        ];

        //增加订单记录
        db('user_charge_log')->insert($order_info);

        $class_name = $pay_type['class_name'];
        //echo $class_name;exit;
        //echo DOCUMENT_ROOT."/system/pay_class/".$class_name."_menu.php";exit;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $pay = $o->get_payment_code($pay_type, $rule, $notice_id);

        $result['pay'] = $pay;

        return_json_encode($result);
    }

    //充值金币
    public function pay()
    {

        $result = array('code' => 1, 'msg' => '');
        $os = intval(input('param.os'));
        $sdk_version = intval(input('param.sdk_version'));
        $app_version = intval(input('param.app_version'));
        $brand = intval(input('param.brand'));
        $model = intval(input('param.model'));


        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $pid = intval(input('param.pid'));
        $rule_id = intval(input('param.rid'));//充值规则ID

        $param['os'] = $os;
        $param['sdk_version'] = $sdk_version;
        $param['app_version'] = $app_version;
        $param['brand'] = $brand;
        $param['model'] = $model;
        $param['uid'] = $uid;
        $param['token'] = $token;
        $param['pid'] = $pid;
        $param['rid'] = $rule_id;

        $user_info = check_login_token($uid, $token);
		
        //充值渠道
        $pay_type = db('pay_menu')->find($pid);
		
        if (!$pay_type) {
            $result['code'] = 0;
            $result['msg'] = '充值类型不存在';
            return_json_encode($result);
        }

        //充值规则
        $rule = db('user_charge_rule')->find($rule_id);
        if (!$rule) {
            $result['code'] = 0;
            $result['msg'] = '充值规则不存在';
            return_json_encode($result);
        }

        $notice_id = NOW_TIME . $uid;//订单号码


        $t_os = "";
        if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
            $t_os = 'ios';
        } else {
            $t_os = 'android';
        }

        $order_info = [
            'uid' => $uid,
            'money' => $rule['money'],
            'coin' => $rule['coin'] + $rule['give'],
            'refillid' => $rule_id,
            'addtime' => NOW_TIME,
            'status' => 0,
            'type' => $pid,
            'order_id' => $notice_id,
            'pay_type_id' => $pid,
            'os'=>$t_os
        ];

        //增加订单记录
        db('user_charge_log')->insert($order_info);

        $class_name = $pay_type['class_name'];
        
        //echo $class_name;exit;
        //echo DOCUMENT_ROOT."/system/pay_class/".$class_name."_menu.php";exit;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $pay = $o->get_payment_code($pay_type, $rule, $notice_id, $param);

        $result['pay'] = $pay;

        return_json_encode($result);
    }

    public function pay_display_html()
    {

        $order_id = $_REQUEST['order_id'];
        $class_name = $_REQUEST['class_name'];

        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $o->display_html($order_id);
    }

    public function pay_display_html_param()
    {

        $result = array('code' => 1, 'msg' => '');
        $os = intval(input('param.os'));
        $sdk_version = intval(input('param.sdk_version'));
        $app_version = intval(input('param.app_version'));
        $brand = intval(input('param.brand'));
        $model = intval(input('param.model'));


        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $pid = intval(input('param.pid'));
        $rule_id = intval(input('param.rid'));//充值规则ID


        $param['os'] = $os;
        $param['sdk_version'] = $sdk_version;
        $param['app_version'] = $app_version;
        $param['brand'] = $brand;
        $param['model'] = $model;
        $param['uid'] = $uid;
        $param['token'] = $token;
        $param['pid'] = $pid;
        $param['rid'] = $rule_id;

        $user_info = check_login_token($uid, $token);

        //充值渠道
        $pay_type = db('pay_menu')->find($pid);

        if (!$pay_type) {
            $result['code'] = 0;
            $result['msg'] = '充值类型不存在';
            return_json_encode($result);
        }

        //充值规则
        $rule = db('user_charge_rule')->find($rule_id);
        if (!$rule) {
            $result['code'] = 0;
            $result['msg'] = '充值规则不存在';
            return_json_encode($result);
        }

        $notice_id = NOW_TIME . $uid;//订单号码
        $order_info = [
            'uid' => $uid,
            'money' => $rule['money'],
            'coin' => $rule['coin'] + $rule['give'],
            'refillid' => $rule_id,
            'addtime' => NOW_TIME,
            'status' => 0,
            'type' => $pid,
            'order_id' => $notice_id,
            'pay_type_id' => $pid,
        ];

        //增加订单记录
        db('user_charge_log')->insert($order_info);

        $class_name = $pay_type['class_name'];
        //echo $class_name;exit;
        //echo DOCUMENT_ROOT."/system/pay_class/".$class_name."_menu.php";exit;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay_class/" . $class_name . "_menu.php");
        $o = new $class_name;
        $pay = $o->pay_display_html_param($pay_type, $rule, $notice_id, $param);
    }

    //PayPal获取token
    public function check_pay_pal_order_status()
    {

        $result = array('code' => 1, 'msg' => '');

        $order_id = trim($_REQUEST['order_id']);
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $r_id = intval(input('param.r_id'));

        $test_type = OPEN_SANDBOX == 1 ? 0 : 1;
        bugu_request_file(DOCUMENT_ROOT . "/system/pay/paypal/fun.php");
        $token_data = pay_pal_access_token($test_type);

        if (!$token_data) {
            $result['code'] = 0;
            $result['msg'] = '获取PayPal验证Token错误！';
            return_json_encode($result);
        }

        $pay_result = pay_pal_get_curl_order($order_id, $token_data['access_token'],$test_type);
        if (!$pay_result) {
            $result['code'] = 0;
            $result['msg'] = '未查询到订单！';
            return_json_encode($result);
        }

        $rule = db('user_charge_rule')->find($r_id);
        if (!$rule) {
            $result['code'] = 0;
            $result['msg'] = '充值规则查询错误！';
            return_json_encode($result);
        }

        $exits = db('user_charge_log')->where('order_id', '=', $order_id)->find();
        if ($exits) {
            $result['code'] = 0;
            $result['msg'] = '已处理的订单！';
            return_json_encode($result);
        }

        $notice_id = $order_id;//订单号码
        $order_info = [
            'uid' => $uid,
            'money' => $rule['money'],
            'coin' => $rule['coin'] + $rule['give'],
            'refillid' => $r_id,
            'addtime' => NOW_TIME,
            'status' => 1,
            'type' => 11111111,
            'order_id' => $notice_id,
            'pay_type_id' => 1111111,
            'pay_pal_money' => $pay_result['transactions'][0]['amount']['total']
        ];

        if ($pay_result['state'] != 'approved') {
            $result['code'] = 0;
            $result['msg'] = '充值失败！';
            return_json_encode($result);
        }

        //增加订单记录
        $add_log = db('user_charge_log')->insertGetId($order_info);
        if ($add_log) {

            $coin = $rule['coin'] + $rule['give'];
            //增加用户钻石
            db('user')->where('id', '=', $order_info['uid'])->setInc('coin', $coin);
            //邀请奖励分成
            invite_back_now_recharge($order_info['money'], $order_info['uid'], $add_log);
            //增加代理用户分成数据
            agent_order_recharge($order_info['money'], $order_info['uid'], $add_log);

        }

        return_json_encode($result);

    }
}