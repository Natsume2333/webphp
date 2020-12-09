<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/20 0020
 * Time: 下午 15:11
 */

namespace app\api\controller;

use think\Db;
use \app\api\controller\Base;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
//个人中心主播信息
class PersonalApi extends Base
{

    //获取评价列表
    public function get_evaluate_list()
    {

        $result = array('code' => 1, 'msg' => '');
        $to_user_id = intval(input('param.to_user_id'));
        $page = intval(input('param.page'));

        //获取评价列表
        $result['evaluate_list'] = db('user_evaluate_record')->alias('e')
            ->join('user u', 'e.user_id=u.id')
            ->field('u.user_nickname,u.avatar,e.label_name')
            ->where('e.to_user_id', '=', $to_user_id)
            ->order('e.create_time desc')
            ->page($page)
            ->select();

        foreach ($result['evaluate_list'] as &$v) {

            $v['label_list'] = [];
            if (!empty($v['label_name'])) {
                $label_array = explode('-', $v['label_name']);
                foreach ($label_array as $k => $v2) {
                    if (empty($v2)) {
                        unset($label_array[$k]);
                    }
                }
                $v['label_list'] = $label_array;
            }
        }

        return_json_encode($result);

    }

    //获取用户主页信息
    public function get_user_page_info()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $id = intval(input('param.id'));
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));

        $user_info = check_token($uid, $token);

        $user = db('user')->where("id=$id")->find();

        $level_name = get_level($id);
        $data = array(
            'id' => $id,
            'sex' => $user['sex'],
            'user_nickname' => $user['user_nickname'],
            'avatar' => $user['avatar'],
            'address' => $user['address'],
            'user_status' => get_user_auth_status($id),
            'teacher' => '',
            'level' => $level_name,
            'max_level' => $level_name,
            'signature'=>$user['signature'],
        );
        $data['attention'] = 1;
        if ($id != $uid) {
            $is_att = db('user_attention')->where('uid', '=', $uid)->where('attention_uid', '=', $id)->find();         //获取是否关注
            if (!$is_att) {
                $data['attention'] = 0;
            }
        }

        //是否拉黑
        $data['is_black'] = 0;
        $black_record = db('user_black')->where('user_id', '=', $uid)->where('black_user_id', '=', $id)->find();
        if ($black_record) {
            $data['is_black'] = 1;
        }

        $config = load_cache('config');
        //$heartbeat_interval = db("config")->where("code='heartbeat_interval'")->find();

        $gift_list = db('user_gift_log')
            ->alias('l')
            ->join('gift g', 'l.gift_id=g.id')
            ->field('g.*')
            ->where('l.to_user_id', '=', $id)
            ->group('gift_id')
            ->select();


        //获取主播视频
        $video_list = db('user_video')->where("uid=$id")->where('type', '=', 1)->field("status,img,video_url,title,coin,viewed,uid,id,follow_num")->limit(0, 10)->select();
        //获取主播私照
        $private_photo_list = db('user_pictures')->where("uid=$id and status=1")->field("img,id")->limit(0, 15)->select();

        //处理图片模糊状态
        foreach ($private_photo_list as &$v) {
            //获取查询私照是否支付观看过
            $buy_record = db("user_photo_buy")->where("p_id=" . $v['id'] . " and user_id=$uid")->find();
            if (!$buy_record) {
                $v['img'] = $v['img'] . "?imageMogr2/auto-orient/blur/40x50";    //私照加密
                $v['watch'] = 1;
            } else {
                $v['watch'] = 0;
            }
        }

        if ($id == $uid) {
            $gift_count = db('user_gift_log')->where('user_id', '=', $id)->sum('gift_count');
            $data['gift_count'] = $gift_count;                   //统计收到的礼物
        } else {
            $gift_count = db('user_gift_log')->where('to_user_id', '=', $id)->sum('gift_count');
            $data['gift_count'] = $gift_count;                   //统计收到的礼物
        }
        $data['gift'] = $gift_list;                          //统计收到的礼物
        $data['video_count'] = count($video_list);                //统计主播视频
        $data['video'] = $video_list;                     //主播视频10条
        $data['pictures_count'] = count($private_photo_list);           //统计主播私照
        $data['pictures'] = $private_photo_list;                     //统计主播私照

        $data['online'] = $user['is_online'];          //是否在线0不在1在
        $data['is_online'] = $data['online'];          //是否在线0不在1在

        $attention_fans_count = db('user_attention')->where("attention_uid=$id")->count();
        $attention_count = db('user_attention')->where("uid=$id")->count();
        //通话时长
        $call_time = db('video_call_record_log')
            ->where('user_id', '=', $id)
            ->whereOr('call_be_user_id', '=', $id)
            ->sum('call_time');

        if ($call_time) {
            $call_time = secs_to_str(abs($call_time));
        } else {
            $call_time = '0';
        }

        //好评比
        $evaluation = db('video_call_record_log')->where('is_fabulous', '=', 1)->where('anchor_id', '=', $id)->count();   //获取评价总数

        //主页轮播图
        $user_image = db('user_img')->where("uid=$id")->where("status=1")->field("id,img")->order("addtime desc")->limit(6)->select();

        //点赞总数
        $fabulous_count = db('user_fabulous_record')->where('to_user_id', '=', $id)->count();

        //获取通话付费价格
        $user_level = get_level($id);

        $data['video_deduction'] = $config['video_deduction'];
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
            //判断用户等级是否符合规定
            if ($user_level >= $config['custom_video_money_level'] && $user['custom_video_charging_coin'] != 0) {
                $data['video_deduction'] = $user['custom_video_charging_coin'];
            }
        }

        $data['attention_fans'] = $attention_fans_count;    //获取关注人数
        $data['attention_all'] = $attention_count;      //获取粉丝人数
        $data['call'] = $call_time;                          //通话总时长
        $data['evaluation'] = $evaluation;             //好评百分比
        $data['teacher'] = [];             //获取收徒榜
        $data['img'] = $user_image;                      //主播轮播图
        $data['give_like'] = $fabulous_count;       //获取点赞数

        $result['data'] = $data;
        return_json_encode($result);
    }

    //获取用户基础信息
    public function get_user_base_info()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $to_user_id = input('param.to_user_id');

        $user_info = check_login_token($uid, $token);

        $result['user_info'] = get_user_base_info($to_user_id, ['id', 'user_nickname', 'sex', 'avatar']);

        return_json_encode($result);
    }

    //私信
    public function private_chat()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $to_user_id = input('param.to_user_id');

        $user_info = check_login_token($uid, $token);

        $to_user_info = get_user_base_info($to_user_id);

        if ($user_info['sex'] == $to_user_info['sex']) {
            $result['code'] = 0;
            $result['msg'] = "同性无法私信!";
            return_json_encode($result);
        }

        $config = load_cache('config');
        $result['is_pay'] = 0;
        //返回是否需要按条扣费
        //dump($to_user_info);exit;
        if ($user_info['sex'] == 1 && $to_user_info['sex'] == 2 && $config['is_open_chat_pay'] == 1) {
            $result['is_pay'] = 1;
            $result['pay_coin'] = $config['private_chat_money'];
        }

        //是否被对方拉黑
        $black = db('user_black')->where('user_id', '=', $to_user_id)->where('black_user_id', '=', $uid)->find();
        if ($black) {
            $result['code'] = 0;
            $result['msg'] = '您已被对方拉黑无法发起视频!';
            return_json_encode($result);
        }

        $attention = db('user_attention')->where("uid=$uid and attention_uid=".$to_user_id)->find();

        $result['follow'] = $attention ? 1: 0;    //是否关注

        //女性是否认证
        $result['sex'] = $user_info['sex'];
        $result['is_auth'] = get_user_auth_status($uid);

        $result['user_info'] = get_user_base_info($to_user_id);

        return_json_encode($result);
    }


    //关注和取消
    public function click_attention()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = input('param.uid');
        $token = input('param.token');
        $id = input('param.id');

        $user_info = check_login_token($uid, $token);

        $attention = db('user_attention')->where("uid=$uid and attention_uid=$id")->find();
        if ($attention) {
            $result['msg'] = "取消关注成功";
            $atte = db('user_attention')->where("uid=$uid and attention_uid=$id")->delete();
            if (!$atte) {
                $result['code'] = 0;
                $result['msg'] = "取消关注失败";
            }

            $result['follow'] = 0;
        } else {
            $data = array(
                'uid' => $uid,
                'attention_uid' => $id,
                'addtime' => NOW_TIME
            );
            $atte = db('user_attention')->insert($data);
            if (!$atte) {
                $result['code'] = 0;
                $result['msg'] = "关注失败";
            }

            $result['follow'] = 1;
        }
        return_json_encode($result);

    }

    //拉黑用户
    public function black_user()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $to_user_id = input('param.to_user_id');

        $user_info = check_login_token($uid, $token);

        $record = db('user_black')->where('user_id', '=', $uid)->where('black_user_id', '=', $to_user_id)->find();
        if ($record) {
            db('user_black')->where('user_id', '=', $uid)->where('black_user_id', '=', $to_user_id)->delete();

        } else {
            $data = [
                'user_id' => $uid,
                'black_user_id' => $to_user_id,
                'create_time' => NOW_TIME
            ];
            db('user_black')->insert($data);
        }

        return_json_encode($result);
    }
}
