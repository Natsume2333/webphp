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

class GiftController extends AdminBaseController
{
    /**
     * 礼物列表
     */
    public function index()
    {
        $gift = Db::name("gift")->select();
        $url = SITE_URL;
        $list = [];
        foreach ($gift as $k=>$v) {
            $v['img'] = $url.'/admin/public/upload/'.$v['img'];
            $list[] = $v;
        }
        $this->assign('gift', $list);
        return $this->fetch();
    }

    /**
     * 礼物添加
     */
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $gift = Db::name("gift")->where("id=$id")->find();
            $url = SITE_URL;
            $gift['img'] =  $url.'/admin/public/upload/'.$gift['img'];
        } else {
            $gift['type'] = 1;
            $gift['is_all_notify'] = 1;
            $gift['img'] = null;
        }

        $this->assign('gift', $gift);
        return $this->fetch();
    }

    public function addPost()
    {
        $param = $this->request->param();
        //  print_r($param);exit;
        $id = $param['id'];
        $data = $param['post'];
        $data['img'] = $param['post']['img'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("gift")->where("id=$id")->update($data);
        } else {
            $result = Db::name("gift")->insert($data);
        }
        if ($result) {
            $this->success("保存成功", url('gift/index'));
        } else {
            $this->error("保存失败");
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $result = Db::name("gift")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    //修改排序
    public function upd()
    {
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("gift")->where("id=$k")->update(array('orderno' => $v));
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

    /**
     *更新排序
     */
    private function listSort($model)
    {
        if (!is_object($model)) {
            return false;
        }
        $pk = $model->getPk(); //获取主键
        $ids = $_POST['listorders'];
        foreach ($ids as $key => $r) {
            $data['sort'] = $r;
            $model->where(array($pk => $key))->save($data);
        }
        return true;
    }
}
