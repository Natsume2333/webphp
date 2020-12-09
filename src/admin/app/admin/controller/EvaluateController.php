<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/8/11
 * Time: 09:53
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;

class EvaluateController extends AdminBaseController
{
    public function index()
    {
        $list = db('evaluate_label')->select();

        $this->assign('list', $list);
        return $this->fetch();
    }

    public function add()
    {
        $id = intval(input('param.id'));
        if ($id != 0) {
            $data = db('evaluate_label')->find($id);
        } else {
            $data['type'] = 1;
        }
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function del()
    {

        $id = intval(input('param.id'));
        db('evaluate_label')->delete($id);

        echo '1';
    }

    public function addPost()
    {
        $label_name = input('param.label_name');
        $type = intval(input('param.type'));
        $orderno = input('param.orderno');
        $id = intval(input('param.id'));

        $data['label_name'] = $label_name;
        $data['orderno'] = $orderno;
        $data['create_time'] = time();
        $data['type'] = $type;

        if ($id != 0) {
            db('evaluate_label')->where('id', '=', $id)->update($data);
        } else {
            db('evaluate_label')->insert($data);
        }

        $this->success('操作成功！');
    }

    public function list_order()
    {

        $param = request()->param();
        $data = '';
        foreach ($param['list_orders'] as $k => $v) {
            $status = db("evaluate_label")->where("id=$k")->update(array('orderno' => $v));
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

   //形象标签
    public function image_label()
    {
        $list = db('user_image_label')->select();
        $this->assign('list', $list);
        return $this->fetch();
    }

    public function add_image_label()
    {
        $id = intval(input('param.id'));
        if ($id != 0) {
            $data = db('user_image_label')->find($id);
        } else {
            $data['type'] = 1;
        }
        $this->assign('data', $data);
        return $this->fetch();
    }

    public function del_image_label()
    {

        $id = intval(input('param.id'));
        db('user_image_label')->delete($id);
        echo '1';
    }

    public function addPost_image_label()
    {
        $name = input('param.name');
        $type = intval(input('param.type'));
        $sort = input('param.sort');
        $id = intval(input('param.id'));

        $data['name'] = $name;
        $data['sort'] = $sort;
        $data['type'] = $type;

        if ($id != 0) {
            db('user_image_label')->where('id', '=', $id)->update($data);
        } else {
            db('user_image_label')->insert($data);
        }

        $this->success('操作成功！');
    }

    public function list_order_image_label()
    {

        $param = request()->param();
        $data = '';
        foreach ($param['list_orders'] as $k => $v) {
            $status = db("user_image_label")->where("id=$k")->update(array('sort' => $v));
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