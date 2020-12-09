<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/25 0025
 * Time: 下午 16:58
 */

namespace app\api\controller;

use Qiniu\Auth;
use Qiniu\Rtc\AppClient;
use think\Config;
use think\Db;
use VideoCallRedis;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ CUCKOO ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
//视频通话业务类
class VideoCallApi extends Base
{

    /**
     * @dw 手动删除通话记录
     * */
    public function del_video_call_record()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = get_input_param_int(input('param.uid'));
        $token = get_input_param_str('param.token');
        $channel_id = get_input_param_str('param.channel_id');

        $user_info = check_token($uid, $token);

        //删除通话记录
        db("video_call_record")->where('channel_id', '=', $channel_id)->delete();
        return_json_encode($result);
    }

    //检查通话是否存在
    public function check_video_call_exits()
    {
        $result = array('code' => 1, 'msg' => '');
        $channel_id = intval(input('param.channel_id'));

        //查询是否存在通话记录
        $call_record = db('video_call_record')->where(['channel_id' => $channel_id])->find();
        if (!$call_record) {
            $result['code'] = 0;
            $result['msg'] = '1.通话记录不存在';
            return_json_encode($result);
        }

        return_json_encode($result);
    }

    //获取通话消息信息
    public function get_video_call_info()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $id = intval(input('param.id'));

        $user_info = check_token($uid, $token);

        $video_call = db('video_call_record')->where('user_id', '=', $id)->find();
        if (!$video_call) {
            $result['code'] = 0;
            $result['msg'] = '通话已结束！';
            return_json_encode($result);
        }

        $call_user_info = get_user_base_info($id);
        $ext = array();
        $ext['type'] = 12;//type 12 一对一视频消息请求推送
        $sender['id'] = $id;
        $sender['user_nickname'] = $call_user_info['user_nickname'];
        $sender['avatar'] = $call_user_info['avatar'];
        $ext['channel'] = $video_call['channel_id'];//通话频道
        $ext['is_use_free'] = $video_call['is_free'];
        $ext['sender'] = $sender;

        $result['ext'] = $ext;
        return_json_encode($result);
    }

    //获取拨打的电话记录
    public function get_video_call_list()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = input('param.token');

        $user_info = check_login_token($uid, $token);

        $result['list'] = db('video_call_record')
            ->alias('v')
            ->where('v.call_be_user_id', '=', $user_info['id'])
            ->field('u.user_nickname,u.avatar,v.user_id,v.create_time')
            ->join('user u', 'v.user_id=u.id')
            ->select();

        foreach ($result['list'] as &$v) {
            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        return_json_encode($result);
    }

    //预约用户列表
    public function subscribe_user()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $to_user_id = intval(input('param.to_user_id'));

        $user_info = check_login_token($uid, $token);
        if ($user_info['sex'] != 1) {
            $result['code'] = 0;
            $result['msg'] = '女用户不能预约!';
        }

        if ($user_info['is_auth'] == 1) {
            $result['code'] = 0;
            $result['msg'] = '主播暂不能预约用户！';

            return_json_encode($result);
        }
        $video_call_subscribe = db('video_call_subscribe')
            ->where('user_id', '=', $uid)
            ->where('to_user_id', '=', $to_user_id)
            ->where('status', 'neq', 2)
            ->select();

        if ($video_call_subscribe) {
            $result['code'] = 0;
            $result['msg'] = '已经预约过了，请耐心等待回复！';
            return_json_encode($result);
        }

        //判断余额是否足够一分钟
        $user_coin = db('user')->where('id', '=', $uid)->value('coin');

        $config = load_cache('config');

        $to_user_info = get_user_base_info($to_user_id, ['custom_video_charging_coin'], 1);
        $to_user_level = get_level($to_user_id);

        $to_user_info['charging_coin'] = $config['video_deduction'];
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
            if (isset($to_user_info['custom_video_charging_coin']) && $to_user_level >= $config['custom_video_money_level'] && $to_user_info['custom_video_charging_coin'] > 0) {
                $to_user_info['charging_coin'] = $to_user_info['custom_video_charging_coin'];
            }
        }

        if ($user_coin < $to_user_info['charging_coin']) {
            $result['code'] = 10002;
            $result['msg'] = '余额不足！';
            return_json_encode($result);
        }

        //扣费
        $charging_coin_res = db('user')->where('id', '=', $uid)->setDec('coin', $to_user_info['charging_coin']);
        if (!$charging_coin_res) {
            $result['code'] = 0;
            $result['msg'] = '扣费失败！';
            return_json_encode($result);
        }

        //增加预约记录
        $video_call_subscribe_data = [
            'user_id' => $uid,
            'to_user_id' => $to_user_id,
            'create_time' => NOW_TIME,
            'coin' => $to_user_info['charging_coin'],
            'status' => 0,
        ];

        $insert_res = db('video_call_subscribe')->insert($video_call_subscribe_data);
        if (!$insert_res) {
            $result['code'] = 0;
            $result['msg'] = '预约失败！';
            return_json_encode($result);
        } else {
            $result['msg'] = '预约成功！';
        }

        return_json_encode($result);

    }

    //回拨打视频通话
    public function back_video_call()
    {

        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = input('param.token');
        //被呼叫人
        $id = intval(input('param.id'));

        $user_info = check_login_token($uid, $token, ['income', 'coin_system', 'custom_video_charging_coin', 'is_auth', 'coin_system']);

        //是否是自己
        if ($id == $uid) {
            $result['code'] = 0;
            $result['msg'] = "不能拨打自己！";
            return_json_encode($result);
        }

        if ($user_info['is_auth'] != 1) {
            $result['code'] = 0;
            $result['msg'] = "未认证，不能回拨电话！";
            return_json_encode($result);
        }

        //对方信息
        $to_user_info = get_user_base_info($id, ['custom_video_charging_coin', 'is_open_do_not_disturb', 'is_auth'], 1);

        //对方是否是主播
        if ($user_info['is_auth'] == 1 && $to_user_info['is_auth'] == 1) {
            $result['code'] = 0;
            $result['msg'] = "主播之间不能发起视频！";
            return_json_encode($result);
        }

        $result['data']['to_user_base_info'] = $to_user_info;

        //账号是否被禁用
        if ($to_user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = "对方因涉嫌违规，账号受限，无法进行操作!";
            return_json_encode($result);
        }

        $config = load_cache('config');

        //是否被对方拉黑
        $black = db('user_black')->where('user_id', '=', $id)->where('black_user_id', '=', $uid)->find();
        if ($black) {
            $result['code'] = 0;
            $result['msg'] = '您已被对方拉黑无法发起视频!';
            return_json_encode($result);
        }

        //判断对方是否开启勿扰
        if ($to_user_info['is_open_do_not_disturb'] == 1) {
            $result['code'] = 10019;
            $result['msg'] = "对方开启了勿扰模式！";
            return_json_encode($result);
        }

        if ($to_user_info['is_online'] != 1) {
            $result['code'] = 10017;
            $result['msg'] = '对方不在线！';
            return_json_encode($result);
        }

        //检查是否已经存在通话记录
        $is_exits_call_record = db("video_call_record")->whereOr('call_be_user_id', '=', $id)->whereOr('user_id', '=', $id)->whereOr('anchor_id', '=', $id)->select();
        if ($is_exits_call_record) {
            $result['code'] = 10018;
            $result['msg'] = '对方正在忙碌中!';
            return_json_encode($result);
        }

        require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
        $video_call_redis = new VideoCallRedis();
        $redis_res = $video_call_redis->do_call($uid, $id);
        if ($redis_res == 10001) {
            $result['code'] = 10018;
            $result['msg'] = '对方正在忙碌中！';
            return_json_encode($result);
        }

        //通话频道ID
        $channel_id = NOW_TIME . $uid . mt_rand(0, 1000);

        //拨打记录
        $call_record['user_id'] = $uid;
        $call_record['call_be_user_id'] = $id;
        $call_record['channel_id'] = $channel_id;
        $call_record['status'] = 0;
        $call_record['create_time'] = NOW_TIME;
        $call_record['anchor_id'] = $uid;

        //拨打记录
        db("video_call_record")->insert($call_record);
        //处理预约
//        db('video_call_subscribe')
//            ->where('user_id', '=', $id)
//            ->where('to_user_id', '=', $uid)
//            ->update(['status' => 2]);
        $result['data']['channel_id'] = $channel_id;
        $result['code'] = 1;

        return_json_encode($result);
    }


    //一键约爱
    public function one_key_video_call_1901017()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = input('param.token');

        $user_info = check_token($uid, $token, ['income', 'coin_system', 'custom_video_charging_coin', 'is_use_free_time']);

        //随机查找在线主播进行视频通话
        $emcee_id = get_rand_emcee($uid);

        if (!$emcee_id) {
            $result['code'] = 0;
            $result['msg'] = '主播们还未上线，请耐心等待！';
            return_json_encode($result);
        }

        $result['emcee_id'] = $emcee_id;
        return_json_encode($result);

    }

    //带有音频功能的拨打视频通话接口
    public function video_call_1215()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $id = intval(input('param.id')); //被呼叫人
        $call_type = intval(input('param.call_type'));

        $user_info = check_login_token($uid, $token, ['income', 'coin_system', 'custom_video_charging_coin']);

        $anchor_id = $user_info['is_auth'] == 1 ? $uid : $id;
        $result['data'] = ['anchor_id' => $anchor_id];
        //是否是自己
        if ($id == $uid) {
            $result['code'] = 0;
            $result['msg'] = "不能拨打自己!";
            return_json_encode($result);
        }

        //是否是同性
        $to_user_info = get_user_base_info($id, ['custom_video_charging_coin,is_open_do_not_disturb'], 1);
        if ($user_info['sex'] == $to_user_info['sex']) {
            $result['code'] = 0;
            $result['msg'] = "同性之间不能通话!";
            return_json_encode($result);
        }

        //判断对方是否开启勿扰
        if ($to_user_info['is_open_do_not_disturb'] == 1) {
            $result['code'] = 10019;
            $result['msg'] = "对方手机不在身边，请稍后再拨!";
            return_json_encode($result);
        }

        $config = load_cache('config');

        if ($to_user_info['is_online'] != 1) {
            $result['code'] = 10017;
            $result['msg'] = '对方手机不在身边，请稍后再拨!';
            return_json_encode($result);
        }

        //查询是否有自己的通话记录
        $self_video_call_record = db("video_call_record")->whereOr('call_be_user_id', '=', $uid)->whereOr('user_id', '=', $uid)->select();
        if ($self_video_call_record) {
            //db("video_call_record")->whereOr('call_be_user_id', '=', $uid)->delete();
            $result['code'] = 0;
            $result['msg'] = '存在未结束的通话记录！';
            return_json_encode($result);
        }

        //是否认证
        if ($user_info['sex'] == 2) {
            if (get_user_auth_status($uid) != 1) {
                $result['code'] = 0;
                $result['msg'] = "未认证，无法发起视频通话！";
                return_json_encode($result);
            }

        } else if (get_user_auth_status($id) != 1) {
            $result['code'] = 0;
            $result['msg'] = "主播未认证，无法发起视频通话！";
            return_json_encode($result);
        }

        //是否被对方拉黑
        $black1 = db('user_black')->where('user_id', '=', $id)->where('black_user_id', '=', $uid)->find();
        $black2 = db('user_black')->where('user_id', '=', $uid)->where('black_user_id', '=', $id)->find();
        if ($black1 || $black2) {
            $result['code'] = 0;
            $result['msg'] = '您已被对方拉黑无法发起视频!';
            return_json_encode($result);
        }

        //检查是否已经存在通话记录
        $is_exits_call_record = db("video_call_record")->whereOr('call_be_user_id', '=', $id)->whereOr('user_id', '=', $id)->whereOr('anchor_id', '=', $id)->find();
        if ($is_exits_call_record) {
            $result['code'] = 10018;
            $result['msg'] = '对方正在忙碌中!';
            return_json_encode($result);
        }

        $coin = $user_info['coin'] + $user_info['coin_system'];

        $emcee_id = $user_info['is_auth'] == 1 ? $uid : $id;

        //音频模式检查
        if (defined('OPEN_VOICE_CALL') && OPEN_VOICE_CALL == 1 && $call_type == 1) {
            $video_deduction = $config['voice_deduction'];
        } else {
            $video_deduction = get_video_call_fee($emcee_id,
                ($uid == $emcee_id ? $user_info['custom_video_charging_coin'] : $to_user_info['custom_video_charging_coin']));
        }

        if ($coin < $video_deduction && $user_info['is_auth'] != 1) {
            $result['msg'] = "余额不足,请先充值";
            $result['code'] = 10002;
            return_json_encode($result);
        }

        if ($to_user_info['coin'] < $video_deduction && $user_info['is_auth'] == 1) {
            $result['msg'] = "对方用户余额不足";
            $result['code'] = 0;
            return_json_encode($result);
        }

        //账号是否被禁用
        if ($to_user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = "对方因涉嫌违规，账号受限，无法进行操作!";
            return_json_encode($result);
        }

        require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
        $video_call_redis = new VideoCallRedis();
        $redis_res = $video_call_redis->do_call($uid, $emcee_id);
        if ($redis_res == 10001) {
            $result['code'] = 10018;
            $result['msg'] = '对方正在忙碌中！';
            return_json_encode($result);
        }

        //通话频道ID
        $channel_id = NOW_TIME . $uid . mt_rand(0, 1000);

        //拨打记录
        $call_record['user_id'] = $uid;
        $call_record['call_be_user_id'] = $id;
        $call_record['channel_id'] = $channel_id;
        $call_record['status'] = 0;
        $call_record['create_time'] = NOW_TIME;
        $call_record['type'] = $call_type;
        $call_record['anchor_id'] = $anchor_id;
        //拨打记录
        db("video_call_record")->insert($call_record);

        $result['code'] = 1;
        $result['data']['channel_id'] = $channel_id;
        $result['data']['to_user_base_info'] = get_user_base_info($id);

        return_json_encode($result);
    }


    //答复视频通话
    public function reply_video_call_0907()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = input('param.uid');
        $token = input('param.token');
        $id = intval(input('param.to_user_id')); //接收人
        $channel = input('param.channel'); //获取通道字符串
        $type = intval(input('param.type'));

        $user_info = check_login_token($uid, $token);

        //查询是否存在通话记录
        $call_record = db('video_call_record')->where(['channel_id' => $channel])->find();
        if (!$call_record) {
            $result['code'] = 0;
            $result['msg'] = '2.通话记录不存在';
            return_json_encode($result);
        }

        if ($type == 1) {
            //$message_status = "接通";
            $change_data['status'] = 1;
            //修改通话状态
            db('video_call_record')->where(['id' => $call_record['id']])->update($change_data);

        } else if ($type == 2) {
            //$message_status = "拒绝";
            $call_record['status'] = 2;
            $call_record['end_time'] = time();
            //拒绝接听电话删除通话记录
            db('video_call_record_log')->insert($call_record);
            db('video_call_record')->where(['id' => $call_record['id']])->delete();

            //删除拨打视频通话缓存记录
            require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
            $video_call_redis = new VideoCallRedis();
            $video_call_redis->del_call($call_record['user_id']);
            $video_call_redis->del_call($call_record['call_be_user_id']);

        }

        $result['data']['to_user_id'] = $id;
        $result['data']['channel'] = $channel;
        $result['data']['type'] = $type;

        return_json_encode($result);
    }

    //取消视频电话
    public function hang_up_video_call_0907()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $call_record = db('video_call_record')->where(['user_id' => $user_info['id']])->find();
        if ($call_record) {
            $call_record['status'] = 3;
            $call_record['end_time'] = NOW_TIME;
            //删除通话记录
            db('video_call_record')->where(['user_id' => $user_info['id']])->delete();
            //同步到日志
            db('video_call_record_log')->insert($call_record);
        }

        $data['channel_id'] = $call_record['channel_id'];
        $result['code'] = 1;
        $result['data'] = $data;

        //删除拨打视频通话缓存记录
        require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
        $video_call_redis = new VideoCallRedis();
        $video_call_redis->del_call($call_record['user_id']);
        $video_call_redis->del_call($call_record['call_be_user_id']);

        return_json_encode($result);
    }

    //结束视频通话
    public function end_video_call_0907()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));

        $user_info = check_login_token($uid, $token);

        //查询通话记录
        $video_call_record = db('video_call_record')
            ->where(['user_id' => $user_info['id'], 'call_be_user_id' => $to_user_id])
            ->whereOr(['call_be_user_id' => $user_info['id'], 'user_id' => $to_user_id])
            ->find();

        if (!$video_call_record) {
            $result['code'] = 0;
            $result['msg'] = '操作失败';
            return_json_encode($result);
        }

        $video_call_record['end_time'] = NOW_TIME;
        $video_call_record['status'] = 3;
        //通话时长
        $video_call_record['call_time'] = $video_call_record['end_time'] - $video_call_record['create_time'];

        //删除通话记录，添加日志记录
        db('video_call_record')->delete($video_call_record['id']);
        db('video_call_record_log')->insert($video_call_record);

        //是否点过赞
        $fabulous_record = Db::name('user_fabulous_record')
            ->where('user_id', '=', $uid)
            ->where('to_user_id', '=', $to_user_id)
            ->find();

        $result['fabulous'] = 1;
        if (!$fabulous_record) {
            $result['fabulous'] = 0;
        }

        $data['channel_id'] = $video_call_record['channel_id'];
        $result['code'] = 1;
        $result['data'] = $data;

        //删除拨打视频通话缓存记录
        require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
        $video_call_redis = new VideoCallRedis();
        $video_call_redis->del_call($video_call_record['anchor_id']);

        return_json_encode($result);
    }

    //视频通话计时扣费
    public function video_call_time_charging()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        check_login_token($uid, $token, ['custom_video_charging_coin', 'is_use_free_time']);

        //查询正在通话的记录
        $call_record = db('video_call_record')->where(['user_id' => $uid])->whereOr(['call_be_user_id' => $uid])->select();
        if (!$call_record) {
            $result['code'] = 10011;
            $result['msg'] = '3.通话记录不存在';
            return_json_encode($result);
        }

        $call_record = $call_record[0];

//        $is_free = 0;
//        //查询用户是否有免费试用
//        if (defined('OPEN_FIRST_FREE') && OPEN_FIRST_FREE == 1) {
//            if ($call_record['is_free'] == 1 && $user_info['is_use_free_time'] == 0) {
//                $is_free = 1;
//                //修改用户免费状态为已使用过
//                db('user')->where('id', '=', $uid)->setField('is_use_free_time', 1);
//            }
//        }

        $config = load_cache('config');

        $to_user_info = get_user_base_info($call_record['anchor_id'], ['custom_video_charging_coin'], 1);

        //判断是否是主播和用户(用户扣费使用)
        if ($uid == $call_record['anchor_id']) {
            $deduction_id = $uid == $call_record['user_id'] ? $call_record['call_be_user_id'] : $call_record['user_id'];
        } else {
            $deduction_id = $uid;
        }

        //音频模式检查
        if (defined('OPEN_VOICE_CALL') && OPEN_VOICE_CALL == 1 && isset($call_record['type']) && $call_record['type'] == 1) {
            $charging_coin = $config['voice_deduction'];
        } else {
            $charging_coin = get_video_call_fee($call_record['anchor_id'], $to_user_info['custom_video_charging_coin']);
        }

        $result['charging_coin'] = $charging_coin;

        //查询上次扣费时间到当前是否满足一分钟
        $last_where = [
            'user_id' => $uid,
            'to_user_id' => $call_record['anchor_id'],
            'channel_id' => $call_record['channel_id'],
        ];
        $last_charge_record = db('video_charging_record')->where($last_where)->order('create_time desc')->find();
        if ($last_charge_record && NOW_TIME - $last_charge_record['create_time'] < 55) {
            return_json_encode($result);
        }

        // 启动事务
        Db::startTrans();
        try {
            $charging_coin_res = 0;

            //判断是否有预约
            $where_call = "status = 0 and ((user_id=" . $call_record['call_be_user_id'] . " and  to_user_id =" . $call_record['user_id'] . ") or (user_id=" . $call_record['user_id'] . " and  to_user_id =" . $call_record['call_be_user_id'] . "))";

            $is_has_subscribe = db('video_call_subscribe')->where($where_call)->find();


            //修改预约状态
            if ($is_has_subscribe) {
                $charging_coin = $is_has_subscribe['coin'];

                db('video_call_subscribe')->where($where_call)->setField('status', 2);
            }

            //不免费扣除用户余额
            //if (!$is_has_subscribe && $is_free != 1) {

            if (!$is_has_subscribe) {
                $charging_coin_res = db('user')->where(['id' => $deduction_id])->setDec('coin', $charging_coin);
            }

            //if (!$is_has_subscribe && !$is_free && !$charging_coin_res) {
            if (!$is_has_subscribe && !$charging_coin_res) {
                // 提交事务
                Db::commit();
                $result['msg'] = "余额不足！";
                $result['code'] = 10002;
                return_json_encode($result);
            }

            //增加总消费记录
            if ($charging_coin_res || $is_has_subscribe) {

                $income_totals = host_income_commission(4, $charging_coin, $call_record['anchor_id']);
                //公会提成
                $guild_coin = sel_guild_log($call_record['anchor_id'], $income_totals);
                $income_total = $income_totals - $guild_coin;

                //增加主播收益
                db('user')->where(['id' => $call_record['anchor_id']])->inc('income', $income_total)->inc('income_total', $income_total)->update();
                $deduction = db('user')->field("coin")->where("id=" . $deduction_id)->find();
                //查询用户是否需要续费
                $balance = $deduction['coin'] - $charging_coin;
                if ($balance <= $charging_coin) {
                    $result['msg'] = "余额不足，是否续费！";
                    $result['code'] = 10013;
                }

                //增加视频扣费记录
                $data = [
                    'user_id' => $deduction_id,
                    'to_user_id' => $call_record['anchor_id'],
                    'coin' => $charging_coin,
                    'profit' => $income_total,
                    'create_time' => NOW_TIME,
                    'channel_id' => $call_record['channel_id'],
                ];

                $table_id = db('video_charging_record')->insertGetId($data);

                //增加总扣费记录
                $log_id = add_charging_log($deduction_id, $call_record['anchor_id'], 4, $charging_coin, $call_record['id'], $income_total);

                //增加公会收益
                add_guild_log($call_record['anchor_id'], $table_id, 2, $income_totals, $income_total, $log_id);

            }

            // 提交事务
            Db::commit();
        } catch (\Exception $e) {
            $result['msg'] = $e -> getMessage();
            // $result['msg'] = "余额不足！";
            $result['code'] = 10002;
            // 回滚事务
            Db::rollback();
        }

        return_json_encode($result);

    }

    //获取结束收益信息
    public function get_video_call_end_info()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $channel_id = trim(input('param.channel_id'));

        $user_info = check_login_token($uid, $token);

        //查询通话记录
        $video_call_record = db('video_call_record_log')->where(['channel_id' => $channel_id])->find();

        if (!$video_call_record) {
            $result['msg'] = "4.通话记录不存在！";
            $result['code'] = 10002;
            return_json_encode($result);
        }

        if ($uid == $video_call_record['anchor_id']) {

            $video_sum_field = 'profit';
            $gift_sum_field = 'profit';
            $where_video_total = 'channel_id=' . $video_call_record['channel_id'] . ' and to_user_id=' . $user_info['id'];
            $where_gift_total = 'channel_id=' . $video_call_record['channel_id'] . ' and to_user_id=' . $user_info['id'];
            //是否点过赞
            $result['is_follow'] = 1;
        } else {
            $video_sum_field = 'coin';
            $gift_sum_field = 'gift_coin';
            $where_video_total = 'channel_id=' . $video_call_record['channel_id'] . ' and user_id=' . $user_info['id'];
            $where_gift_total = 'channel_id=' . $video_call_record['channel_id'] . ' and user_id=' . $user_info['id'];

            $attention = db('user_attention')->where("uid=$uid and attention_uid=" . $video_call_record['anchor_id'])->find();
            $result['is_follow'] = 0;
            if ($attention) {
                $result['is_follow'] = 1;
            }
        }

        //视频总收入
        $video_total = db('video_charging_record')->where($where_video_total)->sum($video_sum_field);
        //礼物总收入
        $gift_total = db('user_gift_log')->where($where_gift_total)->sum($gift_sum_field);

        $result['video_count'] = $video_total;
        $result['gift_count'] = $gift_total;
        $result['total_count'] = $video_total + $gift_total;

        return_json_encode($result);

    }

    //定时查看是否通话超时
    public function check_time_out()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $channel_id = trim(input('param.channel_id'));

        $user_info = check_login_token($uid, $token);

        //查询视频记录
        $config = load_cache('config');
        $video_record = db('video_call_record')->where('channel_id', '=', $channel_id)->find();
        $time = NOW_TIME - $video_record['create_time'];
        if ($time > $config['video_call_time_out']) {
            //超时
            $result['status'] = 4;
        } else {
            $result['status'] = 0;
        }

        return_json_encode($result);
    }

    //是否需要扣费
    public function is_need_charging()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $to_user_id = intval(input('param.to_user_id'));

        $user_info = get_user_base_info($uid);

        $result['is_need_charging'] = 0;
        if ($user_info['is_auth'] != 1) {
            $result['is_need_charging'] = 1;
        }

        $config = load_cache('config');
        $result['video_deduction'] = $config['video_deduction'];

        $emcee_id = $user_info['is_auth'] == 1 ? $uid : $to_user_id;

        $emcee_info = get_user_base_info($emcee_id, ['custom_video_charging_coin']);
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
            $emcee_level = get_level($emcee_id);
            //判断用户等级是否符合规定
            if ($emcee_level >= $config['custom_video_money_level'] && $emcee_info['custom_video_charging_coin'] != 0) {
                $result['video_deduction'] = $emcee_info['custom_video_charging_coin'];
            }
        }

        //视频分辨率
        $result['resolving_power'] = $config['phone_resolving_power'];

        //是否有免费试看
        $result['free_time'] = 0;
//        if($result['is_need_charging'] == 1){
//            $result['free_time'] = $config['free_video_time'];
//        }

        $result['free_time'] = $config['free_video_time'];

        return_json_encode($result);
    }

    //是否需要扣费
    public function is_need_charging_qiniu()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $to_user_id = intval(input('param.to_user_id'));
        $channel_id = trim(input('param.channel_id'));

        $user_info = get_user_base_info($uid);

        $result['is_need_charging'] = 0;
        if ($user_info['is_auth'] != 1) {
            $result['is_need_charging'] = 1;
        }

        $config = load_cache('config');
        $result['video_deduction'] = $config['video_deduction'];

        $emcee_id = $user_info['is_auth'] == 1 ? $uid : $to_user_id;
        $emcee_info = get_user_base_info($emcee_id, ['custom_video_charging_coin']);
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
            $emcee_level = get_level($emcee_id);
            //判断用户等级是否符合规定
            if ($emcee_level >= $config['custom_video_money_level'] && $emcee_info['custom_video_charging_coin'] != 0) {
                $result['video_deduction'] = $emcee_info['custom_video_charging_coin'];
            }
        }
        $config = load_cache('config');
        $result['resolving_power'] = $config['phone_resolving_power'];


        /*----------------生成七牛RoomToken---------------------------start*/
        require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Config::get('qiniu.accessKey');
        $secretKey = Config::get('qiniu.secretKey');

        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        $app_client = new AppClient($auth);
        $result['room_token'] = trim($app_client->appToken("dtoanlepg", $channel_id, (string)$uid, (NOW_TIME + 3600), 'user'));
        /*----------------生成七牛RoomToken---------------------------end*/

        return_json_encode($result);
    }


    //通话满意度点赞
    public function video_fabulous()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $channel_id = trim(input('param.channel_id'));

        $user_info = check_login_token($uid, $token);

        $record = db('video_call_record_log')->where('channel_id', '=', $channel_id)->find();
        if (!$record) {

            return_json_encode($result);
        }

        db('video_call_record_log')->where('channel_id', '=', $channel_id)->setField('is_fabulous', 1);

        return_json_encode($result);
    }

    //获取实时的通话金额、礼物金额、消费金额
    public function get_video_call_time_info()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $channel_id = trim(input('param.channel_id'));

        if (empty($uid) || empty($channel_id)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return_json_encode($result);
        }

        //查询通话记录
        $video_call_record = db('video_call_record')
            ->where(['channel_id' => $channel_id])
            ->find();

        //判断扣费人,查询用户余额
        //$user = get_user_base_info($video_call_record['user_id']);

        $to_user_id = $video_call_record['user_id'] == $uid ? $video_call_record['call_be_user_id'] : $video_call_record['user_id'];

        $coin = db('user')->where(['id' => $to_user_id])->sum('coin');

        //查询消费数量
        $total_coin = db('video_charging_record')->where('channel_id', '=', $video_call_record['channel_id'])->sum('coin');

        $gift_total = db('user_gift_log')
            ->where('channel_id', '=', $video_call_record['channel_id'])
            ->sum('gift_coin');

        $result['total'] = $total_coin + $gift_total;
        $result['video_call_total_coin'] = $total_coin;
        $result['gift_total_coin'] = $gift_total;
        $result['user_coin'] = $coin;

        return_json_encode($result);
    }

}
