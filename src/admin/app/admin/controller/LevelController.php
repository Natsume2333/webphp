<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/20 0020
 * Time: 上午 11:02
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class LevelController extends AdminBaseController
{
    /**
     * 等级列表
     */
    public function level_index()
    {
        $level = Db::name("level")->select();
        $this->assign('level', $level);
        return $this->fetch();
    }

    /**
     * 等级添加
     */
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("level")->where("levelid=$id")->find();
            $this->assign('level', $name);
        }
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        $id = $param['levelid'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("level")->where("levelid=$id")->update($data);
        } else {
            $result = Db::name("level")->insert($data);
        }
        if ($result) {
            $this->success("保存成功", url('level/level_index'));
        } else {
            $this->error("保存失败");
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $result = Db::name("level")->where("levelid=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    public function star_index()
    {

        $list = db('user_star_level')->select();

        $this->assign('list', $list);

        return $this->fetch();
    }

    public function add_star()
    {

        return $this->fetch();
    }

    public function add_star_post()
    {

        $param = $this->request->param();

        $param['create_time'] = time();
        $result = db("user_star_level")->insert($param);

        if ($result) {
            $this->success("保存成功", url('level/star_index'));
        } else {
            $this->error("保存失败");
        }
    }

    public function edit_star()
    {

        $id = input('param.id');
        $data = db("user_star_level")->find($id);

        $this->assign('level', $data);
        return $this->fetch();

    }

    //编辑星级
    public function edit_star_post()
    {

        $id = input('param.id');

        $param = $this->request->param();

        $result = db("user_star_level")->where('id', '=', $id)->update($param);

        if ($result) {
            $this->success("保存成功", url('level/star_index'));
        } else {
            $this->error("保存失败");
        }
    }

    //主播收费列表
    public function fee()
    {
        $list = db('host_fee')->order("sort asc")->select()->toarray();
        foreach ($list as &$v) {
            if ($v['level'] == '0') {
                $v['name'] = "所有用户";
            } else {
                $level = Db::name("level")->where("level_name=" . $v['level'])->find();
                $v['name'] = "LV" . $level['level_name'] . "主播";
            }
        }

        $this->assign('list', $list);

        return $this->fetch();
    }

    //添加修改
    public function add_fee()
    {
        $id = input('param.id');
        if ($id) {
            $data = db("host_fee")->find($id);
        } else {
            $data['level'] = 0;
        }
        $level = db("level")->select();
        $this->assign('fee', $data);
        $this->assign('level', $level);
        return $this->fetch();
    }

    //操作收费数据
    public function upd_fee()
    {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("host_fee")->where("id=$id")->update($data);
        } else {
            $result = Db::name("host_fee")->insert($data);
        }
        if ($result) {
            $this->success("保存成功", url('level/fee'));
        } else {
            $this->error("保存失败");
        }
    }

    //删除
    public function del_fee()
    {
        $param = request()->param();
        $result = Db::name("host_fee")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    //修改排序
    public function upd()
    {
        $param = request()->param();
        $data = '';

        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("host_fee")->where("id=$k")->update(array('sort' => $v));
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
