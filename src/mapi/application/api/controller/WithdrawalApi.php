<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/14 0014
 * Time: 上午 10:59
 */

namespace app\api\controller;

use think\helper\Time;
use app\api\controller\Base;

class WithdrawalApi extends Base
{

    /**
     * @dw 获取提现规则
     * */
    public function get_cash_rule()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['income']);

        $result['income'] = $user_info['income'];

        $config = load_cache('config');
        $result['list'] = db('user_earnings_withdrawal')->where("status=1")->order("sort asc")->select();
        foreach ($result['list'] as &$v) {
            $v['money'] = number_format($v['coin'] / $config['integral_withdrawal']);
        }
        $pays = db('user_cash_account')->where('uid=' . $uid)->find();
        if ($pays) {
            $result['is_bind_pay'] = 1;
        } else {
            $result['is_bind_pay'] = 0;
        }

        return_json_encode($result);
    }

    //申请提现
    public function request_user_apply()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $id = intval(input('param.id'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['income']);

        if ($user_info['is_auth'] != 1) {
            $result['msg'] = '认证并审核通过才可以提现！';
            return_json_encode($result);
        }

        //账号是否被禁用
        if ($user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = "您因涉嫌违规，账号受限，请联系管理员!";
            return_json_encode($result);
        }

        $pays = db('user_cash_account')->where('uid=' . $uid)->find();
        if ($pays) {
            if (empty($pays['pay']) && empty($pays['wx'])) {
                $result['msg'] = "请绑定账户";
                return_json_encode($result);
            }
        } else {
            $result['msg'] = "请绑定账户";
            return_json_encode($result);
        }

        $list = db('user_earnings_withdrawal')->where("status=1 and id=$id")->find();
        if (!$list) {
            return_json_encode($result);
        }
        if ($list['coin'] > $user_info['income']) {
            $result['msg'] = "余额不足";
            return_json_encode($result);
        }

        $config = load_cache('config');

        //查询是否超过当日最大提现次数
        $day_time = Time::today();
        $day_cash_num = db('user_cash_record')->where('user_id', '=', $uid)->where('create_time >' . $day_time[0])->count();

        if ($day_cash_num == $config['cash_day_limit']) {
            $result['msg'] = '每日最大提现次数为' . $day_cash_num . '！';
            return_json_encode($result);
        }
        $money = round($list['coin'] / $config['integral_withdrawal']);
        //扣除剩余提现额度
        $inc_income = db('user')->where('id', '=', $uid)->setDec('income', $list['coin']);
        if ($inc_income) {
            //添加提现记录
            $record = ['gathering_name' => $pays['name'], 'gathering_number' => $pays['pay'] ? $pays['pay'] : $pays['wx'], 'user_id' => $uid, 'paysid' => $pays['id'], 'income' => $list['coin'], 'money' => $money, 'create_time' => NOW_TIME];
            db('user_cash_record')->insert($record);
            $result['msg'] = "提现成功，等待审核";
            $result['code'] = 1;

        }
        return_json_encode($result);
    }

    //显示主播收益提现规则
    public function index()
    {
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['income']);

        $config = load_cache('config');
        $list = db('user_earnings_withdrawal')->where("status=1")->order("sort asc")->select();
        foreach ($list as &$v) {
            $v['money'] = number_format($v['coin'] / $config['integral_withdrawal']);
        }
        $pays = db('user_cash_account')->where('uid=' . $uid)->find();
        if ($pays) {
            $type = 1;
        } else {
            $type = 0;
        }
        $this->assign('type', $type);
        $this->assign('user_info', $user_info);

        $this->assign('uid', $uid);
        $this->assign('token', $token);
        $this->assign('list', $list);
        return $this->fetch();
    }

    //申请提现
    public function user_apply()
    {
        $root = array("status" => 0, "msg" => "参数错误");
        $id = intval(input('param.id'));
        $uid = intval(input('param.uid'));

        $user_info = db('user')->field("income,is_auth,user_status")->where(['id' => $uid])->find();

        if ($user_info['is_auth'] != 1) {
            $root['msg'] = '认证并审核通过才可以提现！';
            return $root;
        }

        //账号是否被禁用
        if ($user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = "您因涉嫌违规，账号受限，请联系管理员!";
            return_json_encode($result);
        }

        $pays = db('user_cash_account')->where('uid=' . $uid)->find();
        if ($pays) {
            if (empty($pays['pay']) && empty($pays['wx'])) {
                $root['msg'] = "请绑定账户";
                return $root;
            }
        } else {
            $root['msg'] = "请绑定账户";
            return json($root);
        }

        $list = db('user_earnings_withdrawal')->where("status=1 and id=$id")->find();
        if (!$list) {
            return $root;
        }
        if ($list['coin'] > $user_info['income']) {
            $root['msg'] = "余额不足";
            return $root;
        }

        $config = load_cache('config');

        //查询是否超过当日最大提现次数
        $day_time = Time::today();
        $day_cash_num = db('user_cash_record')->where('user_id', '=', $uid)->where('create_time >' . $day_time[0])->count();

        if ($day_cash_num == $config['cash_day_limit']) {
            $root['msg'] = '每日最大提现次数为' . $day_cash_num . '！';
            return $root;
        }
        $money = round($list['coin'] / $config['integral_withdrawal']);
        //扣除剩余提现额度
        $inc_income = db('user')->where('id', '=', $uid)->setDec('income', $list['coin']);
        if ($inc_income) {
            //添加提现记录
            $record = ['gathering_name' => $pays['name'], 'gathering_number' => $pays['pay'] ? $pays['pay'] : $pays['wx'], 'user_id' => $uid, 'paysid' => $pays['id'], 'income' => $list['coin'], 'money' => $money, 'create_time' => NOW_TIME];
            db('user_cash_record')->insert($record);
            $root['msg'] = "提现成功，等待审核";
            $root['status'] = 1;

        }

        return $root;
    }
}