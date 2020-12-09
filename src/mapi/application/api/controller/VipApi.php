<?php

namespace app\api\controller;

use app\api\controller\Base;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST BOGO ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

/**
 * Created by 山东布谷鸟网络科技有限公司.
 * User: weipeng
 * Date: 2018/8/17
 * Time: 00:14
 */
class VipApi extends Base
{

    public function get_vip_page_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['vip_end_time']);

        $vip_day = intval(($user_info['vip_end_time'] - NOW_TIME) / (60 * 60 * 24));
        if ($vip_day < 0) {
            $vip_day = '未开通';
        }
        $result['vip_time'] = $vip_day;
        //支付方式
        $result['pay_list'] = db('pay_menu')->field('id,pay_name,icon')->where('status', '=', 1)->select();
        $result['vip_rule'] = db('vip_rule')->select();

        foreach ($result['vip_rule'] as &$v) {
            if ($v['money'] != 0 && $v['day_count'] != 0) {
                $day_money = $v['money'] / $v['day_count'];
            } else {
                $day_money = $v['money'];
            }
            $v['day_money'] = '¥' . round($day_money, 2);
        }

        //查询特权列表
        $result['detail_list'] = db('vip_rule_details')->select();

        return_json_encode($result);
    }

    public function index()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $user_info = get_user_base_info($uid, ['vip_end_time'], 1);
        //系统金币单位名称
        $config = load_cache('config');
        $coin_name = $config['currency_name'];
        $vip_day = intval(($user_info['vip_end_time'] - time()) / (60 * 60 * 24));

        $list = db('vip_rule')->select();
        foreach ($list as &$v) {
            $names = $v['money'] / $v['day_count'];
            $v['mean'] = round($names, 2);
        }
        $this->assign('user_info', $user_info);
        $this->assign('vip_day', $vip_day > 0 ? $vip_day : 0);
        $this->assign('uid', $uid);
        $this->assign('token', $token);
        $this->assign('list', $list);
        $this->assign('coin_name', $coin_name);
        return $this->fetch();
    }

    //购买会员
    public function buy_vip()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $vip_id = intval(input('param.id'));

        $user_info = check_login_token($uid, $token);

        $rule = db('vip_rule')->find($vip_id);

        if (!$rule) {
            $result['code'] = 0;
            $result['msg'] = '规则不存在！';
            return_json_encode($result);
        }

        $user_info = get_user_base_info($uid, ['vip_end_time'], 1);

        if ($user_info['coin'] < $rule['money']) {

            $result['code'] = 0;
            $result['msg'] = '余额不足！';
            return_json_encode($result);
        }

        $res = db('user')->where('id', '=', $uid)->setDec('coin', $rule['money']);
        if (!$res) {

            $result['code'] = 0;
            $result['msg'] = '购买失败！';
            return_json_encode($result);
        }

        $vip_time = $rule['day_count'] * 60 * 60 * 24;
        if ($user_info['vip_end_time'] > time()) {

            db('user')->where('id', '=', $uid)->setInc('vip_end_time', $vip_time);
        } else {

            $vip_time = time() + $vip_time;
            db('user')->where('id', '=', $uid)->setField('vip_end_time', $vip_time);
        }

        return_json_encode($result);
    }
}