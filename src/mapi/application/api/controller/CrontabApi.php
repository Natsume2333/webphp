<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/19
 * Time: 11:33
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

class CrontabApi extends Base
{

    public function service_crontab()
    {
        crontab_do_end_live();
        crontab_do_end_call();

        //清除所有过期的心跳
        $config = load_cache('config');

        //时间
        $time = NOW_TIME - $config['heartbeat_interval'] - 60;//偏移量5秒
        db('monitor')->where('monitor_time', '<', $time)->delete();
    }


    /**
     * @dw 自动打招呼定时任务
     * */
    public function service_see_hi_crontab()
    {
        if (defined('OPEN_AUTO_SEE_HI_PLUGS') && OPEN_AUTO_SEE_HI_PLUGS == 1) {
            crontab_do_auto_talking_custom_tpl();
        }else{
            crontab_do_auto_talking();
        }
    }

    //获取昨天的营业
    public function business_day()
    {

        $data['time'] = date("Y-m-d", strtotime("-1 day"));
        //获取昨天
        $beginToday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
        $endToday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;

        $where1 = "status=1 and addtime >=" . $beginToday . " and addtime <" . $endToday;
        $where2 = "status =1 and updatetime >=" . $beginToday . " and updatetime <" . $endToday;
        //昨天收入的金额(充值记录)
        $data['income'] = db("user_charge_log")->where($where1)->sum("money");
        //昨天支出的金额 (提现记录)
        //invite_cash_record    邀请收益提现  agent_withdrawal 代理提现  user_cash_record  用户主播提现
        $invite_cash_record = db("invite_cash_record")->where($where2)->sum("coin");
        $agent_withdrawal = db("agent_withdrawal")->where($where2)->sum("money");
        $user_cash_record = db("user_cash_record")->where($where2)->sum("money");

        $invite_cash_record = $invite_cash_record ? $invite_cash_record : '0';
        $agent_withdrawal = $agent_withdrawal ? $agent_withdrawal : '0';
        $user_cash_record = $user_cash_record ? $user_cash_record : '0';
        $data['income'] = $data['income'] ? $data['income'] : '0';
        $data['spending'] = $invite_cash_record + $agent_withdrawal + $user_cash_record;

        $data['invite_record'] = $invite_cash_record;
        $data['host_record'] = $user_cash_record;
        $data['agent_record'] = $agent_withdrawal;

        if ($data['income'] >= $data['spending']) {
            $data['type'] = 1;
            $data['statistical'] = $data['income'] - $data['spending'];
        } else {
            $data['type'] = 2;
            $data['statistical'] = $data['spending'] - $data['income'];
        }
        $data['addtime'] = time();

        $financial = db("admin_financial")->where("time = '" . $data['time'] . "'")->find();

        if ($financial) {
            db('admin_financial')->where("time='" . $data['time'] . "'")->update($data);
        } else {
            db('admin_financial')->insert($data);
        }

    }


}