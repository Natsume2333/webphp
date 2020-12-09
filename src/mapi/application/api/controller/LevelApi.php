<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/4
 * Time: 20:31
 */

namespace app\api\controller;

use think\Db;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class LevelApi extends Base
{
    //等级
    public function app_index()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $result['level_my'] = get_grade_level($uid);
        //等级列表
        $result['level'] = load_cache('level');

        return_json_encode($result);
    }

    /*
    * 主播收费标准
    * */
    public function app_fee()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['custom_video_charging_coin']);

        //收费列表
        $fee = Db::name("host_fee")->order("sort asc")->select();
        foreach ($fee as &$v) {
            if ($v['level'] == '0') {
                $v['name'] = "所有用户";
            } else {
                $level = Db::name("level")->where("level_name=" . $v['level'])->find();
                $v['name'] = "LV" . $level['level_name'] . "主播可选";
            }
            $v['type'] = $v['coin'] == $user_info['custom_video_charging_coin'] ? 1 : 0;
        }

        $data = $fee;
        $result['data'] = $data;

        return_json_encode($result);
    }

    /*
     * 主播修改收费标准
     * */
    public function app_fee_add()
    {
        $result = array('code' => 1, 'msg' => '修改成功');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $id = intval(input('param.id'));
        $user_info = check_login_token($uid, $token, ['custom_video_charging_coin']);

        $level_my = get_grade_level($uid);
        //收费列表
        $fee = $data = Db::name("host_fee")->where("id=$id")->find();
        if (!$fee) {
            $result['code'] = '0';
            $result['msg'] = '参数错误';
            return_json_encode($result);
        }
        if ($level_my['level_name'] < $fee['level']) {
            $result['code'] = '0';
            $result['msg'] = '等级太低，请升级等级后在选择';
            return_json_encode($result);
        }
        $user = db('user')->where("id=$uid and custom_video_charging_coin='" . $fee['coin'] . "'")->find();

        if (!$user) {
            $res = db('user')->where('id', $uid)->update(['custom_video_charging_coin' => $fee['coin']]);
            if ($res) {
                $result['msg'] = '修改成功';

            } else {
                $result['code'] = '0';
                $result['msg'] = '修改失败';
            }
        }
        return_json_encode($result);
    }


    /**
     * h5 页面 等级
     */
    public function index()
    {
        $uid = input('param.uid');
        $token = input('param.token');
        if (empty($uid) || empty($token)) {
            echo '传参错误';
            exit;
        }

        $user_info = Db::name("user")->where("id=$uid and token='$token'")->field("sex")->find();
        if (empty($user_info)) {
            echo '登录过期，请重新登录！';
            exit;
        }

        $level_my = get_grade_level($uid);
        //等级列表
        $level = load_cache('level');

        //var_dump($Level);exit;
        $this->assign('level_my', $level_my);
        $this->assign('level', $level);
        $this->assign('name', $user_info);

        return $this->fetch();
    }

    /*
     * 主播收费标准
     * */
    public function fee()
    {
        $uid = input('param.uid');
        $token = input('param.token');
        //   $uid=100163;
        //   $token='ff290e2b28cd3921fc569674126f7ee6';
        if (empty($uid) || empty($token)) {
            echo '传参错误';
            exit;
        }

        $user_info = db("user")->where("id=$uid and token='$token'")->field("custom_video_charging_coin,id,is_auth")->find();
        if (!$user_info) {
            echo '登录过期，请重新登录！';
            exit;
        }

        $level_my = get_grade_level($uid);
        //  var_dump($level_my);exit;["level_name"]

        //收费列表
        $fee = $data = Db::name("host_fee")->order("sort asc")->select();
        foreach ($fee as &$v) {
            if ($v['level'] == '0') {
                $v['name'] = "所有用户";
            } else {
                $level = Db::name("level")->where("levelid=" . $v['level'])->find();
                $v['name'] = "LV" . $level['level_name'] . "主播可选";
            }
        }

        $config = load_cache('config');

        $this->assign('currency_name', $config['currency_name']);
        $this->assign('config', $config);
        $this->assign('level_my', $level_my);
        $this->assign('fee', $fee);
        $this->assign('user_info', $user_info);
        return $this->fetch();
    }

    /*
     * 主播修改
     * */
    public function fee_add()
    {
        $data = array('status' => 0, 'error' => '');
        $uid = input('param.uid');
        $id = input('param.id');
        $name = Db::name("user")->where("id=$uid ")->field("custom_video_charging_coin,id")->find();
        if (empty($name)) {

            $data['error'] = '暂无用户数据';
            return $data;
            exit;
        }
        $level_my = get_grade_level($uid);
        //收费列表
        $fee = $data = Db::name("host_fee")->where("id=$id")->find();
        if ($level_my < $fee['level']) {
            $data['error'] = '等级太低，请升级等级后在选择';
            return $data;
            exit;
        }
        if (!db('user')->where("id=$uid and custom_video_charging_coin='" . $fee['coin'] . "'")->find()) {
            $res = db('user')->where('id', $uid)->update(['custom_video_charging_coin' => $fee['coin']]);
            if (!$res) {
                $data['error'] = '修改失败';
                return $data;
                exit;
            }
        }
        $data['status'] = '1';
        $data['error'] = '';
        return $data;
        exit;

    }
}