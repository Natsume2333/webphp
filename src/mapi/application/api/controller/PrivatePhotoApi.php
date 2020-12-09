<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/2/28
 * Time: 16:26
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

class PrivatePhotoApi extends Base
{

    //获取图片
    public function select_photo()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $pid = intval(input('param.pid'));

        $user_info = check_login_token($uid, $token);

        $is_self = false;
        //查看是否是当前用户自己的私照
        $photo = db('user_pictures')->find($pid);
        if ($photo['uid'] == $uid) {
            $is_self = true;
        }

        if ($user_info['sex'] == 2 && !$is_self) {
            $result['code'] = 0;
            $result['msg'] = '同性之间不能查看私照！';
            return_json_encode($result);
        }

        $user_info = get_user_base_info($uid, ['vip_end_time'], 1);

        $pay_record = db('user_photo_buy')->where(['user_id' => $uid, 'p_id' => $pid])->find();  //查询私照
        if (!$pay_record && !$is_self && $user_info['vip_end_time'] < NOW_TIME) {
            $result['code'] = 10031;
            $result['msg'] = '';
            return_json_encode($result);
        }

        $private_photo = db('user_pictures')->find($pid);
        $result['img'] = $private_photo['img'];

        return_json_encode($result);

    }

    //私照支付金币
    public function pay_personal()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $pid = intval(input('param.pid'));

        if ($uid == 0 || empty($token)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return_json_encode($result);
        }

        $user_info = check_token($uid, $token);

        if (!$user_info) {
            $result['code'] = 10001;
            $result['msg'] = '登录信息失效';
            return_json_encode($result);
        }

        //账号是否被禁用
        if ($user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = "您因涉嫌违规，账号受限，请联系管理员!";
            return_json_encode($result);
        }

        $coin = db("user")->where("id=$uid")->find();                       //获取用户充值的金币

        $config = load_cache('config');    //获取私照的收费标准
        if ($coin['coin'] < $config['private_photos']) {
            $result['code'] = 0;
            $result['msg'] = "您的充值账户余额不足，请充值后购买";
            return_json_encode($result);
        }
        $phones = db("user_pictures")->where("id=$pid and status=1")->field("id,uid,img")->find();  //查询私照

        //扣费
        //$setDec = $this->record_setDec("user","id=$uid","coin",$config['private_photos']);
        $setDec = db("user")->where('id', '=', $uid)->setDec('coin', $config['private_photos']);

        if (!$setDec) {
            $result['code'] = 0;
            $result['msg'] = '扣费失败';
            return_json_encode($result);
        }

        //增加主播收益
        $income_total = host_income_commission(2, $config['private_photos'], $pid);
        db('user')->where(['id' => $phones['uid']])->inc('income_total', $income_total)->inc('income', $income_total)->update();


        //增加购买记录
        $record_id = db('user_photo_buy')->insertGetId(['user_id' => $uid, 'p_id' => $pid, 'create_time' => NOW_TIME]);
        if ($record_id) {
            add_charging_log($uid, $phones['uid'], 2, $config['private_photos'], $record_id, $income_total);

            $result['code'] = 1;
            $result['msg'] = "支付成功";
            $result['img'] = $phones['img'];
        }

        return_json_encode($result);
    }

    //上传私照
    public function private_photos_upload()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        if ($user_info['is_auth'] != 1) {
            $result['code'] = 0;
            $result['msg'] = "请认证后上传私照！";
            return_json_encode($result);
        } elseif ($user_info['sex'] == 1) {  //男性用户不能上传私照
            $result['code'] = 0;
            $result['msg'] = "无权限上传私照！";
            return_json_encode($result);
        }

        $img = request()->file();               //获取私照上传的图片
        $data = [];
        foreach ($img as $k => $v) {
            $uploads = oss_upload($v);      //单图片上传
            $data[$k]['uid'] = $uid;
            $data[$k]['addtime'] = time();
            $data[$k]['img'] = $uploads;
        }
        $res = Db::name('user_pictures')->insertAll($data);
        if ($res) {
            $result['code'] = 1;
            $result['msg'] = "上传成功，等待审核";
        }
        return_json_encode($result);
    }


    //私照
    public function pictures_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $id = intval(input('param.id'));
        $uid = input('param.uid');
        $limit = intval(input('param.page'));

        if ($id == $uid) {
            if ($limit) {
                $limit = $limit * 15;
            } else {
                $limit = 0;
            }
            $photo_list = Db::name('user_pictures')->where("uid=$id")->order('addtime desc')->limit($limit, 15)->select();
        } else {
            if ($limit || $limit == '0') {
                $limit = $limit * 15;
                //获取私照信息
                $photo_list = Db::name('user_pictures')->where("uid=$id and status=1")->limit($limit, 15)->select();
            } else {
                $photo_list = Db::name('user_pictures')->where("uid=$id and status=1")->select();
            }
            $result['pictures_count'] = Db::name('user_pictures')->where("uid=$id and status=1")->count();           //统计主播私照

            //处理图片模糊状态
            foreach ($photo_list as &$v) {
                //获取查询私照是否支付观看过
                $user = Db::name("user_photo_buy")->where("p_id=" . $v['id'] . " and user_id=$uid")->find();
                if (empty($user)) {
                    $v['img'] = $v['img'] . "?imageMogr2/auto-orient/blur/40x50";    //私照加密
                    $v['watch'] = '1';
                } else {
                    $v['watch'] = '0';
                }
            }
        }

        //处理图片模糊状态
        foreach ($photo_list as &$v) {
            $v['img2'] = $v['img'] . "?imageMogr2/auto-orient/blur/40x50";    //私照加密
        }

        $result['list'] = $photo_list;
        return_json_encode($result);
    }

    //删除私照
    public function del_photo()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $pid = intval(input('param.pid'));

        if ($uid == 0 || empty($token)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return_json_encode($result);
        }

        $user_info = check_token($uid, $token);

        if (!$user_info) {
            $result['code'] = 10001;
            $result['msg'] = '登录信息失效';
            return_json_encode($result);
        }

        //账号是否被禁用
        if ($user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = "您因涉嫌违规，账号受限，请联系管理员!";
            return_json_encode($result);
        }


        $photo = db('user_pictures')->where('uid', '=', $uid)->where('id', '=', $pid)->find();

        $res = db('user_pictures')->where('uid', '=', $uid)->where('id', '=', $pid)->delete();
        if (!$res) {
            $result['code'] = 0;
            $result['msg'] = '删除失败';
        }

        oss_del_file(get_oss_file_path($photo['img']));
        return_json_encode($result);
    }

}