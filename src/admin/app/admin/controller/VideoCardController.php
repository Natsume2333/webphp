<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/8 0008
 * Time: 上午 9:00
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class VideoCardController extends AdminBaseController
{
    /**
     * 充值视频列表
     */
    public function index()
    {
        $level = Db::name("user_video_coin")->order("orderno asc")->select();
        $this->assign('level', $level);
        return $this->fetch();
    }

    /**
     * 充值视频添加
     */
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("user_video_coin")->where("id=$id")->find();
            $this->assign('level', $name);
        } else {
            $this->assign('level', array('type' => 0));
        }
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("user_video_coin")->where("id=$id")->update($data);
        } else {
            $result = Db::name("user_video_coin")->insert($data);
        }
        if ($result) {
            $this->success("保存成功", url('VideoCard/index'));
        } else {
            $this->error("保存失败");
        }
    }

    //删除视频类型
    public function del()
    {
        $param = request()->param();
        $result = Db::name("user_video_coin")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    //修改视频排序
    public function upd()
    {

        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("user_video_coin")->where("id=$k")->update(array('orderno' => $v));
            if ($status) {
                $data = $status;
            }
        }
        if ($data) {
            $this->success("排序成功");
        } else {
            $this->success("排序失败");
        }
    }

}
