<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/2/20
 * Time: 17:09
 */

namespace app\api\controller;


use think\App;
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
class LiveApi extends Base
{
    //创建直播
    public function start_live(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid,$token);
    }

    //直播刷新心跳
    public function update_live()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        if ($uid == 0 || empty($token)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return_json_encode($result);
        }

        $user_info = check_token($uid, $token);

        if (!$user_info) {
            $result['code'] = 0;
            $result['msg'] = '登录信息失效';
            return_json_encode($result);
        }

        //账号是否被禁用
        if ($user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = "您因涉嫌违规，账号受限，请联系管理员!";
            return_json_encode($result);
        }

        $config = get_config();

        //查询是否已有直播记录
        $live_record = Db::name('video_live_list')->where(['user_id' => $uid])->find();
        if ($live_record) {

            $result['push_url'] = $live_record['push_url'];
            //更新直播最后心跳时间
            Db::name('video_live_list')->where(['user_id' => $uid])->update(['last_heart_time' => NOW_TIME]);

            $result['push_url'] = $live_record['push_url'];
        } else {

            $stream = $uid . '_' . NOW_TIME;
            $push_url = 'rtmp://' . $config['live_push_url']['val'] . '/' . $config['live_push_node']['val'] . '/' . $stream;
            $pull_url = 'rtmp://' . $config['live_pull_url']['val'] . '/' . $config['live_push_node']['val'] . '/' . $stream;

            $live = [
                'user_id' => $uid,
                'push_url' => $push_url,
                'pull_url' => $pull_url,
                'last_heart_time' => NOW_TIME,
                'create_time' => NOW_TIME
            ];

            //插入新的直播记录
            Db::name('video_live_list')->insert($live);
            $result['push_url'] = $push_url;
        }

        return_json_encode($result);

    }

    //随机获取视频
    public function rand_video_live_list()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $count = intval(input('param.count'));

        if ($uid == 0 || empty($token)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return_json_encode($result);
        }

        if ($count > 2) {
            $result['code'] = 0;
            $result['msg'] = '数量超出限制';
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

        //echo 'left join ' . config('database.prefix') . 'user as u on u.id=l.user_id' ;exit;
        $list_count = Db::name('video_live_list')->count();
        $start = rand(0, $list_count - 1);
        if ($start < 0) {
            $start = 0;
        }
        $now_time = NOW_TIME;

        //测试不区分男女和自己
        if (App::$debug) {
            $live_list = Db::name('video_live_list')
                ->alias('l')
                ->join(config('database.prefix') . 'user u', 'u.id=l.user_id')
                ->field('l.pull_url,u.id,u.user_nickname,u.avatar')
                ->limit($start, $count)
                ->select();
        } else {

            $config = load_cache('config');
            //心跳最小时间
            $time = $now_time - $config['tab_live_heart_time'] - 10;
            if ($time < 0) {
                //默认时间如果后台未设置
                $time = $now_time - 40;
            }
            $live_list = Db::name('video_live_list')
                ->alias('l')
                ->join(config('database.prefix') . 'user u', 'u.id=l.user_id')
                ->field('l.pull_url,u.id,u.user_nickname,u.avatar')
                ->where('u.sex', 'neq', $user_info['sex'])
                ->where('u.id', 'neq', $uid)
                ->where('l.last_heart_time', '>', $time)
                ->limit($start, $count)
                ->select();
        }

        //是否关注
        foreach ($live_list as &$v) {

            $attention = db('user_attention')->where("uid=$uid and attention_uid=" . $v['id'])->find();
            $v['is_follow'] = 0;
            if ($attention) {
                $v['is_follow'] = 1;
            }
        }


        $result['list'] = $live_list;

        return_json_encode($result);
    }

}