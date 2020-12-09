<?php
/**
 *
 *
 * Date: 2019/09/13
 * Time: 11:04
 */

namespace app\api\controller;

use think\Db;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.buguniaokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class NoticeApi extends Base
{

    //公告
    public function index()
    {
        $uid = input("param.uid");

        if (empty($uid)) {
            echo '用户uid传参错误';
            exit;
        }
        $res = Db::name('user')->where('id', $uid)->select();
        $is_auth = $res[0]['is_auth'];

        $notice = $this->selectd();
        if ($is_auth == 1) {
            $type = 2;
        } else if ($is_auth == 0) {
            $type = 1;
        } else {
            $type = 0;
        }

        $this->assign(['notice' => $notice, 'type' => $type]);
        return $this->fetch();
    }

    public function content()
    {
        $id = request()->param('id');
        $data = Db::name('notice')->where('id', $id)->select();
        $this->assign('data', $data[0]);
        return $this->fetch();
    }

    public function selectd()
    {
        return Db::name('notice')->where(['status' => 1])->select();
    }
}