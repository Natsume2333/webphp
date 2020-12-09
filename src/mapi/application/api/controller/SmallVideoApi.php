<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/18 0018
 * Time: 下午 16:21
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
class SmallVideoApi extends Base
{

    //小视频
    public function index()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $type = trim(input('param.type'));

        $order = "a.follow_num desc";
        if (empty($type)) {
            check_param($type);
        }
        if ($type == 'reference') {
            //推荐视频
            $where = "a.is_recommend=1 and a.type=1";
        } elseif ($type == 'latest') {
            //最新视频
            $where = "a.type=1";
            $order = "a.addtime desc";
        } elseif ($type == 'attention') {
            //关注视频
            $video = db('user_video')->alias('a')->field('a.*,c.user_nickname,c.avatar')->join('user_attention u', 'a.uid=u.attention_uid')
                ->join('user c', 'c.id=a.uid')
                ->where("a.type=1 and u.uid=$uid")
                ->order("a.addtime desc")
                ->page($page)
                ->select();

            $result['data'] = $video;
            return_json_encode($result);

        } else if ($type == 'free') {
            $where = "a.status=1";
        } else if ($type == 'private_') {
            $where = "a.status=2";
        } else {                          //附近的视频
            $lat = input('param.lat');
            $lng = input('param.lng');

            if (empty($lat)) {
                check_param($lat);
            }

            if (empty($lng)) {
                check_param($lng);
            }

            $squares = returnSquarePoint($lng, $lat);
            $where = "a.lat<>0 and a.lat>{$squares['right-bottom']['lat']} and a.lat<{$squares['left-top']['lat']} and a.lng>{$squares['left-top']['lng']} and
        a.lng<{$squares['right-bottom']['lng']} and a.type=1";
        }

        $video = Db::name('user_video')->alias('a')->field('a.*,u.user_nickname,u.avatar')->join('user u', 'a.uid=u.id')
            ->where($where)
            ->order($order)
            ->page($page)
            ->select();

        $result['data'] = $video;
        return_json_encode($result);
    }


    //视频分享次数加1
    public function share_number()
    {
        $result = array('code' => 0, 'msg' => '增加分享次数失败', 'data' => array());
        $id = input("param.id");
        if (empty($id)) {
            check_param($id);
        }
        $name = Db::name("user_video")->where("id=$id")->setInc("share");
        if ($name) {
            $result['code'] = '1';
            $result['msg'] = '增加分享次数成功';
        }
        return_json_encode($result);
    }
}