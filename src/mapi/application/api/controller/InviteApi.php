<?php

namespace app\api\controller;

use app\api\controller\Base;
use think\helper\Time;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class InviteApi extends Base
{

    //获取邀请码和邀请记录
    public function get_my_invite_page()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));

        $user_info = check_login_token($uid, $token);

        //查询邀请码
        $invite_code = db('invite_code')->where('user_id', '=', $uid)->find();
        $invite_code = $invite_code['invite_code'];
        if (!$invite_code) {
            //生成邀请码
            $invite_code = create_invite_code();
            db('invite_code')->insert(['user_id' => $uid, 'invite_code' => $invite_code]);
        }

        //邀请码
        $result['invite_code'] = $invite_code;

        $result['invite_user_count'] = 0;
        $result['income_total'] = 0;
        $result['day_income_total'] = 0;

        //邀请记录
        $invite_user_list = db('invite_record')->alias('i')
            ->field('u.avatar,u.id,u.user_nickname')
            ->join(config('database.prefix') . 'user u', 'i.invite_user_id=u.id')
            ->where('i.user_id', '=', $uid)
            ->order('create_time desc')
            ->page($page, 20)
            ->select();
        //echo db('invite_record') ->getLastSql();exit;

        foreach ($invite_user_list as &$v) {
            //用户奖励总数
            $v['income_total'] = db('invite_profit_record')->where('user_id', '=', $uid)->where('invite_user_id', '=', $v['id'])->sum('income');

        }

        //首页或者刷新进行统计数据查询
        if ($page == 1) {
            //总收益
            $income_total = db('invite_profit_record')->where('user_id', '=', $uid)->sum('income');

            //今日收益
            $day_time = Time::today();
            $day_income_total = db('invite_profit_record')
                ->where('user_id', '=', $uid)
                ->where('create_time', '>', $day_time[0])
                ->where('create_time', '<', $day_time[1])
                ->sum('income');

            //总邀请人数
            $invite_user_count = db('invite_record')->where('user_id', '=', $uid)->count();
            $result['invite_user_count'] = $invite_user_count;
            $result['income_total'] = $income_total;
            $result['day_income_total'] = $day_income_total;
        }

        $result['invite_user_list'] = $invite_user_list;

        return_json_encode($result);
    }

    //是否填写过邀请码
    public function is_full_invite_code()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $result['is_full'] = 0;
        //是否填写邀请码
        $record = db('invite_record')->where('invite_user_id', '=', $uid)->find();
        if ($record) {
            $result['is_full'] = 1;
        }

        return_json_encode($result);
    }

    //提交邀请码
    public function full_invite_code()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));

        $token = trim(input('param.token'));
        $invite_code = trim(input('param.invite_code'));
        $type = intval(input('param.type'));

        $user_info = check_login_token($uid, $token);

        //查询是否填写过邀请码
        $record = db('invite_record')->where('invite_user_id', '=', $uid)->where("invite_code !=''") ->find();

        if (!empty($record)) {
            $result['code'] = 0;
            $record['msg'] = '已经填写过邀请码';
            return_json_encode($result);
        }


        //判断邀请码是否存在
        if ($type == 1) {
            $invite_code_record = db('invite_code')->where('invite_code', '=', $invite_code)->find();

            if (!$invite_code_record) {
                $result['code'] = 0;
                $result['msg'] = '邀请码不存在';
                return_json_encode($result);
            }

            if ($invite_code_record['user_id'] == $uid) {
                $result['code'] = 0;
                $result['msg'] = '不能填写自己的邀请码';
                return_json_encode($result);
            }
           $sex = $user_info['sex'];
            //邀请码为int类型下没问题
            $res = reg_invite_service($uid, $invite_code);
            if ($res && intval($invite_code) != 0 && $sex == 1) {

                //男用户完善信息后奖励
                $res = reg_invite_perfect_info_service($uid, 1);
                if($res){
                    $result['code'] = 200;
                    $result['msg'] = '完成邀请';
                    return_json_encode($result);
                }
            }

        } else {
            //邀请码数据
            $invite_data = [
                'invite_user_id' => $uid,
                'create_time' => NOW_TIME,
                'user_id' => 0,
                'invite_code' => '',
            ];
            //添加邀请记录
            db('invite_record')->insert($invite_data);
        }
        return_json_encode($result);


    }

    //获取邀请码
    public function get_invite_code()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        //查询邀请码
        $invite_code = db('invite_code')->where('user_id', '=', $uid)->find();
        $invite_code = $invite_code['invite_code'];
        if (!$invite_code) {
            //生成邀请码
            $invite_code = create_invite_code();
            db('invite_code')->insert(['user_id' => $uid, 'invite_code' => $invite_code]);
        }

        //邀请码
        $result['invite_code'] = $invite_code;

        return_json_encode($result);

    }


}