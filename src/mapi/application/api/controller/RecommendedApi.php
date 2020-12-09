<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/24 0024
 * Time: 上午 10:01
 */

namespace app\api\controller;

use think\Db;
use UserOnlineStateRedis;
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
class RecommendedApi extends Base
{
    //第一次进入app推荐主播，推荐为随机
    public function recommend_list()
    {
        $result = ['code' => 1, 'msg' => ''];

        $where = ['reference' => 1, 'sex' => 2];

        $user_list = db('user')
            ->where($where)
            ->field('user_nickname,avatar,id,sex')
            ->select();
        $result['list'] = $user_list;
        return_json_encode($result);
    }

    //一键关注
    public function follows()
    {
        $result = ['code' => 1, 'msg' => ''];
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $live_uid = trim(input('param.liveuid'));
        $live_uid_list = explode('&', $live_uid);

        $user_info = check_login_token($uid, $token);

        if (empty($live_uid)) {
            $result['code'] = 0;
            $result['msg'] = '缺少必要的参数';
            return_json_encode($result);
        }

        $insert_data = [];
        foreach ($live_uid_list as $key => $val) {
            $insert_data[] = [
                'uid' => $uid,
                'attention_uid' => intval($val),
                'addtime' => NOW_TIME,
            ];
        }

        $res = db('user_attention')->insertAll($insert_data);
        $result['msg'] = '';
        return_json_encode($result);
    }

    //以下废弃
    //首页推荐用户
    public function recommend_user()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));

        $user_info = get_user_base_info($uid);
        $sex = 1;
        if ($user_info['sex'] == 1) {
            $sex = 2;
        }

        $config = load_cache('config');
        $video = db('user')
            ->alias('a')
            ->field('a.id,a.sex,a.user_nickname,a.avatar,a.level')
            ->join('monitor m', 'a.id=m.user_id', 'left')
            ->where("a.user_status!=0 and a.sex=$sex and a.reference=1 and a.is_online=1")
            ->order("m.monitor_time desc")
            ->page($page)
            ->select();

        foreach ($video as &$v) {
            //好评百分比
            $v['evaluation'] = db('video_call_record_log')->where('anchor_id', '=', $v['id'])->where('is_fabulous', '=', 1)->count();
            $level = get_level($v['id']);
            $v['level_name'] = $level;
            //获取主播是否登录
            $v['is_online'] = is_online($v['id'], $config['heartbeat_interval']);
        }

        $result['online_emcee_count'] = 0;
        $result['online_emcee'] = [];
        //获取在线主播人数
        if ($page == 1) {
            require_once DOCUMENT_ROOT . '/system/redis/UserOnlineStateRedis.php';
            $result['online_emcee'] = db('user')->alias('u')->field('u.id,u.avatar')->where('u.is_online', '=', 1)->where('u.sex', '=', 2)->limit(3)->select();
            $user_online_state_redis = new UserOnlineStateRedis();
            $result['online_emcee_count'] = $user_online_state_redis->get_female_online_count();

        }
        $result['data'] = $video;
        return_json_encode($result);
    }

    //首页轮播图
    public function shuffling()
    {

        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $shuffling = intval(input('param.shuffling'));

        $img = db('slide_item')->where("slide_id=$shuffling and status=1")->order("list_order desc")->field('id,image,title,url')->select();
        if ($img) {
            $result['code'] = '1';
            $result['data'] = $img;
        } else {
            $result['msg'] = "暂无图片";
        }

        return_json_encode($result);
    }

}