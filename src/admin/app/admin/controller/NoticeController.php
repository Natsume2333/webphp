<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/13
 * Time: 上午:09:20
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class NoticeController extends AdminBaseController
{
    /**
     * 公告列表
     */
    public function index()
    {
        $notice = Db::name("notice")->order('type')->paginate(5);
        $this->assign(['notice' => $notice, 'page' => $notice->render()]);
        return $this->fetch();
    }

    /**
     * 公告添加
     */
    public function add()
    {
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        $data = [
            'name' => $param['title'],
            'type' => $param['type'],
            'content' => htmlspecialchars_decode($param['content']),
            'status' => $param['status'],
            'create_time' => time(),
        ];
        $result = Db::name("notice")->insert($data);
        if ($result) {
            $this->success("保存成功", url('notice/index'));
        } else {
            $this->error("保存失败");
        }
    }

    public function edit()
    {
        $id = request()->param('id');
        $data = Db::name('notice')->where('id', $id)->select();
        $this->assign(['data' => $data]);
        return $this->fetch();
    }

    public function editPost()
    {
        $param = $this->request->param();
        $data = [
            'name' => $param['title'],
            'type' => $param['type'],
            'content' => htmlspecialchars_decode($param['content']),
            'status' => $param['status'],
            'create_time' => time(),
        ];
        $result = Db::name("notice")->where('id', $param['id'])->update($data);
        if ($result) {
            $this->success("修改成功", url('notice/index'));
        } else {
            $this->error("保存失败");
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $result = Db::name("notice")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    //发布
    public function release()
    {
        $param = request()->param();
        $data = [
            'status' => 1
        ];
        $result = Db::name("notice")->where('id', $param['id'])->update($data);
        return $result ? '1' : '0';
    }

    public function releasestop()
    {
        $param = request()->param();
        $data = [
            'status' => 2
        ];
        $result = Db::name("notice")->where('id', $param['id'])->update($data);
        return $result ? '1' : '0';
    }
}

