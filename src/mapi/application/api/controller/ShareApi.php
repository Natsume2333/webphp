<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10 0010
 * Time: 上午 10:28
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
class ShareApi extends Base
{

    //分享小视频
    public function index()
    {
        $id = intval(input('param.id'));
        $invite_code = intval(input('param.invite_code'));
        $config = load_cache('config');

        $user = Db::name('user_video')
            ->alias('a')
            ->field('a.*,u.user_nickname,u.avatar')
            ->join('user u', 'a.uid=u.id')
            ->where("a.id=$id and a.type=1")
            ->find();

        $user['video_url'] = get_sign_video_url($config['tencent_video_sign_key'], $user['video_url']);

        $video = Db::name('user_video')
            ->alias('a')
            ->field('a.*,u.user_nickname,u.avatar')
            ->join('user u', 'a.uid=u.id')
            ->where("a.type=1 and a.id !=$id")
            ->order("a.follow_num desc")
            ->page(0, 4)
            ->select();

        $this->assign('video', $video);
        $this->assign('invite_code', $invite_code);
        $this->assign('system_name', $config['system_name']);
        $this->assign('user', $user);
        return $this->fetch();
    }


}