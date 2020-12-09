<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/8/11
 * Time: 09:53
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;

class VisualizeController extends AdminBaseController
{
    public function index()
    {
        $list = db('visualize_table')->select();

        $this->assign('list', $list);
        return $this->fetch();
    }

    public function add()
    {
        $id = intval(input('param.id'));
        if ($id != 0) {
            $data = db('visualize_table')->find($id);
             $this->assign('data', $data);
        } 
        return $this->fetch();
    }

    public function del()
    {

        $id = intval(input('param.id'));
        db('visualize_table')->delete($id);

        echo '1';
    }

    public function addPost()
    {
        $label_name = input('param.visualize_name');
        $visualize_color = input('param.visualize_color');
        $sort = input('param.sort');
        $id = intval(input('param.id'));

        $data['visualize_name'] = $label_name;
        $data['sort'] = $sort;
        $data['addtime'] = time();
        $data['visualize_color'] = $visualize_color;

        if ($id != 0) {
            db('visualize_table')->where('id', '=', $id)->update($data);
        } else {
            db('visualize_table')->insert($data);
        }

        $this->success('操作成功！');
    }

    public function list_order()
    {

        $param = request()->param();
        $data = '';
        foreach ($param['sort'] as $k => $v) {
            $status = db("visualize_table")->where("id=$k")->update(array('sort' => $v));
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