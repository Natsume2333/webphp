<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2019/2/24
 * Time: 21:24
 */

namespace app\api\controller;


// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ CUCKOO ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
// 公会插件类

use think\helper\Time;

class GuildApi extends Base
{

    //修改公会提成
    public function save_user_ratio()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));

        $host_bay_video_proportion = trim(input('param.host_bay_video_proportion'));
        $host_bay_phone_proportion = trim(input('param.host_bay_phone_proportion'));
        $host_bay_gift_proportion = trim(input('param.host_bay_gift_proportion'));
        $host_one_video_proportion = trim(input('param.host_one_video_proportion'));
        $host_direct_messages = trim(input('param.host_direct_messages'));

        $user_info = check_login_token($uid, $token);

        $guild = db('guild')->where('user_id=' . $uid . ' and status=1')->find();
        if (!$guild) {
            $result['code'] = 0;
            $result['msg'] = '没有权限操作！';
            return_json_encode($result);
        }

        $guild_join = db('guild_join')->where('user_id=' . $to_user_id . ' and status=1')->find();
        if (!$guild_join) {
            $result['code'] = 0;
            $result['msg'] = '没有权限操作！';
            return_json_encode($result);
        }

        $config = load_cache('config');

        if (isset($config['guild_max_video_ratio']) && $host_bay_video_proportion > $config['guild_max_video_ratio']) {
            $result['code'] = 0;
            $result['msg'] = '短视频比例大于后台设置的比例';
            return_json_encode($result);

        }

        if (isset($config['guild_max_videoline_ratio']) && $host_one_video_proportion > $config['guild_max_videoline_ratio']) {
            $result['code'] = 0;
            $result['msg'] = '视频通话比例大于后台设置的比例';
            return_json_encode($result);

        }

        if (isset($config['guild_max_photo_ratio']) && $host_bay_phone_proportion > $config['guild_max_photo_ratio']) {
            $result['code'] = 0;
            $result['msg'] = '私照比例大于后台设置的比例';
            return_json_encode($result);

        }

        if (isset($config['guild_max_chat_ratio']) && $host_direct_messages > $config['guild_max_chat_ratio']) {
            $result['code'] = 0;
            $result['msg'] = '私信比例大于后台设置的比例';
            return_json_encode($result);

        }

        if (isset($config['guild_max_gift_ratio']) && $host_bay_gift_proportion > $config['guild_max_gift_ratio']) {
            $result['code'] = 0;
            $result['msg'] = '礼物比例大于后台设置的比例';
            return_json_encode($result);

        }

        $update_data = [
            'host_bay_video_proportion' => $host_bay_video_proportion,
            'host_bay_phone_proportion' => $host_bay_phone_proportion,
            'host_bay_gift_proportion' => $host_bay_gift_proportion,
            'host_one_video_proportion' => $host_one_video_proportion,
            'host_direct_messages' => $host_direct_messages,
        ];

        db('user')->where('id=' . $to_user_id)->update($update_data);
        return_json_encode($result);

    }

    //获取公会下主播的提成比例
    public function get_user_ratio()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));

        $user_info = check_login_token($uid, $token);

        $guild = db('guild')->where('user_id=' . $uid . ' and status=1')->find();
        if (!$guild) {
            $result['code'] = 0;
            $result['msg'] = '没有权限操作！';
            return_json_encode($result);
        }

        $guild_join = db('guild_join')->where('user_id=' . $to_user_id . ' and status=1')->find();
        if (!$guild_join) {
            $result['code'] = 0;
            $result['msg'] = '没有权限操作！';
            return_json_encode($result);
        }

        $config = load_cache('config');

        $to_user_info = get_user_base_info($to_user_id, ['host_bay_video_proportion', 'host_bay_phone_proportion', 'host_bay_gift_proportion', 'host_one_video_proportion', 'host_direct_messages']);

        //视频分成比例
        $result['data']['host_bay_video_proportion'] = $to_user_info['host_bay_video_proportion'];
        //私照分成比例
        $result['data']['host_bay_phone_proportion'] = $to_user_info['host_bay_phone_proportion'];
        //礼物分成比例
        $result['data']['host_bay_gift_proportion'] = $to_user_info['host_bay_gift_proportion'];
        //通话分成比例
        $result['data']['host_one_video_proportion'] = $to_user_info['host_one_video_proportion'];
        //私信分成比例
        $result['data']['host_direct_messages'] = $to_user_info['host_direct_messages'];

        if ($to_user_info['host_bay_video_proportion'] == 0) {
            $result['data']['host_bay_video_proportion'] = $config['host_bay_video_proportion'];
        }

        if ($to_user_info['host_bay_phone_proportion'] == 0) {
            $result['data']['host_bay_phone_proportion'] = $config['host_bay_phone_proportion'];
        }

        if ($to_user_info['host_bay_gift_proportion'] == 0) {
            $result['data']['host_bay_gift_proportion'] = $config['host_bay_gift_proportion'];
        }

        if ($to_user_info['host_one_video_proportion'] == 0) {
            $result['data']['host_one_video_proportion'] = $config['host_one_video_proportion'];
        }

        if ($to_user_info['host_direct_messages'] == 0) {
            $result['data']['host_direct_messages'] = $config['host_direct_messages'];
        }

        $result['data']['guild_max_video_ratio'] = $config['guild_max_video_ratio'];
        $result['data']['guild_max_videoline_ratio'] = $config['guild_max_videoline_ratio'];
        $result['data']['guild_max_chat_ratio'] = $config['guild_max_chat_ratio'];
        $result['data']['guild_max_photo_ratio'] = $config['guild_max_photo_ratio'];
        $result['data']['guild_max_gift_ratio'] = $config['guild_max_gift_ratio'];

        return_json_encode($result);

    }


    //审核工会
    public function audition()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $action = trim(input('param.action'));
        $id = intval(input('param.id'));

        $user_info = check_login_token($uid, $token);

        $join_record = db('guild_join')->find($id);

        if (!$join_record) {
            $result['code'] = 0;
            $result['msg'] = '操作记录不存在！';
            return_json_encode($result);
        }

        $guild = db('guild')->where('user_id=' . $uid . ' and id=' . $join_record['guild_id'])->find();
        if (!$guild) {
            $result['code'] = 0;
            $result['msg'] = '权限不足！';
            return_json_encode($result);
        }

        if ($join_record['status'] != 0) {
            $result['code'] = 0;
            $result['msg'] = '已审核！';
            return_json_encode($result);
        }

        //通过审核
        if ($action == 'agree') {
            db('guild_join')->where('id=' . $id)->setField('status', 1);
        }

        if ($action == 'refuse') {
            db('guild_join')->where('id=' . $id)->setField('status', 2);
        }

        return_json_encode($result);
    }

    //获取自己的公会信息
    public function guild_info()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $guild_info = db('guild')->where('user_id=' . $uid)->where('status=1')->find();// . ' and status=1'

        if (!$guild_info) {
            $result['code'] = 0;
            $result['msg'] = '公会信息不存在！';
            return_json_encode($result);
        }

        $guild_join_list = db('guild_join')->where('status=1 and guild_id=' . $guild_info['id'])->select();

        //总人数
        $guild_info['num'] = count($guild_join_list);

        $user_id_list = [];
        //总收益
        foreach ($guild_join_list as $v) {
            $user_id_list[] = $v['user_id'];
        }

        $guild_info['total_profit'] = db('user_consume_log')->where('to_user_id', 'in', $user_id_list)->count('profit');

        $day_time = Time::today();
        //今日总收益
        $guild_info['day_total_profit'] = db('user_consume_log')
            ->where('create_time', '>', $day_time[0])->where('to_user_id', 'in', $user_id_list)->count('profit');

        $result['data'] = $guild_info;

        return_json_encode($result);

    }

    //公会待审核主播列表
    public function guild_apply_user_list()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $guild_id = intval(input('param.guild_id'));
        $page = intval(input('param.page'));

        $user_info = check_login_token($uid, $token);

        $filed = 'u.user_nickname,u.avatar,u.id,g.status,g.id as guild_id';
        $result['list'] = db('guild_join')->field($filed)->alias('g')->join('user u', 'g.user_id=u.id', 'left')
            ->where('g.guild_id=' . $guild_id . ' and g.status=0')->page($page)->select();

        return_json_encode($result);
    }

    //公会主播列表
    public function guild_user_list()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $guild_id = intval(input('param.guild_id'));
        $page = intval(input('param.page'));

        $user_info = check_login_token($uid, $token);

        $filed = 'u.income_total,u.level,u.user_nickname,u.avatar,u.id,g.status,g.id as guild_id';
        $result['list'] = db('guild_join')->field($filed)->alias('g')->join('user u', 'g.user_id=u.id', 'left')
            ->where('g.guild_id=' . $guild_id . ' and g.status=1')->page($page)->select();

        return_json_encode($result);
    }

    //申请加入公会
    public function join_guild()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $guild_id = intval(input('param.guild_id'));

        $user_info = check_login_token($uid, $token);

        if ($user_info['is_auth'] != 1) {
            $result['code'] = 0;
            $result['msg'] = '未认证无法申请公会！';
            return_json_encode($result);
        }

        //查询公会是否存在
        $guild = db('guild')->where('id=' . $guild_id)->find();// . ' and status=1'
        if (!$guild) {
            $result['code'] = 0;
            $result['msg'] = '公会信息不存在！';
            return_json_encode($result);
        }

        //判断是否是公会会长
        $self_guild = db('guild')->where('user_id=' . $uid)->find();
        if ($self_guild) {
            $result['code'] = 0;
            $result['msg'] = '已创建公会，无法申请加入其它公会！';
            return_json_encode($result);
        }

        //查询是否加入了公会
        $join_record = db('guild_join')->where('user_id=' . $uid . ' and status!=2')->find();
        if ($join_record) {
            $result['code'] = 0;
            $result['msg'] = '已经加入公会，请勿重复加入！';
            return_json_encode($result);
        }

        $join_data = [
            'user_id' => $uid,
            'guild_id' => $guild_id,
            'create_time' => NOW_TIME
        ];

        db('guild_join')->insert($join_data);
        return_json_encode($result);

    }

    //公会列表
    public function guild_list()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));

        $user_info = check_login_token($uid, $token);

        $order = 'create_time desc';
        $list = db('guild')->page($page)->order($order)->select();

        foreach ($list as &$v) {
            $v['num'] = db('guild_join')->where('guild_id=' . $v['id'] . ' and status=1')->count();
        }

        $result['list'] = $list;
        return_json_encode($result);
    }

    //公会管理
    public function create_guild()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $name = trim(input('name'));
        $logo_img = trim(input('logo_img'));
        $introduce = trim(input('introduce'));

        $user_info = check_login_token($uid, $token);

        if (mb_strlen($name) > 10) {
            $result['code'] = 0;
            $result['msg'] = '名称长度不能大于10个字符！';
            return_json_encode($result);
        }

        if (mb_strlen($introduce) > 100) {
            $result['code'] = 0;
            $result['msg'] = '介绍长度不能大于100个字符！';
            return_json_encode($result);
        }

        if (empty($logo_img)) {
            $result['code'] = 0;
            $result['msg'] = '请上传形象图！';
            return_json_encode($result);
        }

        $record = db('guild')->where('user_id=' . $uid)->find();

        if ($record && $record['status'] != 2) {
            $result['code'] = 0;
            $result['msg'] = '请勿重复创建！';
            return_json_encode($result);
        }

        $guild_data = ['user_id' => $uid, 'name' => $name, 'introduce' => $introduce, 'logo' => $logo_img, 'create_time' => NOW_TIME];
        db('guild')->insert($guild_data);

        return_json_encode($result);

    }

    //查询收益记录
    public function select_income_log()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = input('param.token');

        $to_user_id = trim(input('param.to_user_id'));
        $start_time = trim(input('param.start_time'));
        $end_time = trim(input('param.end_time'));
        $page = intval(input('param.page'));

        $user_info = check_login_token($uid, $token);

        $result['list'] = db('user_consume_log')->alias('l')->field('l.*,u.user_nickname')->join('user u', 'l.to_user_id=u.id', 'left')->where('l.create_time', '>', $start_time)->where('l.create_time', '<', $end_time)->where('l.to_user_id', '=', $to_user_id)
            ->page($page)->order('l.create_time desc')->select();

        foreach ($result['list'] as &$v) {
            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);
        }
        return_json_encode($result);

    }


}