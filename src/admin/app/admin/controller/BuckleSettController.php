<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2019/2/27
 * Time: 20:42
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;

class BuckleSettController extends AdminBaseController
{
    //邀请扣量规则列表
    public function buckle_invite_index()
    {
        $list = db('buckle_invite_rule')->select()->toArray();

        $this->assign('list', $list);
        // 渲染模板输出
        return $this->fetch();
    }

    public function add_buckle_invite_rule()
    {
        $id = intval(input('param.id'));
        if ($id != 0) {
            $data = db('buckle_invite_rule')->find($id);
            $this->assign('data', $data);
        }

        return $this->fetch();
    }

    //增加邀请扣量规则
    public function add_buckle_invite_rule_post()
    {
        $param = $this->request->param();

        $param['create_time'] = NOW_TIME;
        if (isset($param['id']) && db('buckle_invite_rule')->find($param['id'])) {
            $id = $param['id'];
            unset($param['id']);
            db('buckle_invite_rule')->where('id=' . $id)->update($param);
        } else {
            db('buckle_invite_rule')->insert($param);
        }

        $this->success("保存成功");
    }

    //删除扣量规则
    public function del_buckle_invite_rule()
    {
        $id = input('param.id');
        db('buckle_invite_rule')->delete($id);

        echo 1;
        exit;
    }


    //邀请充值扣量规则列表
    public function buckle_invite_recharge_index()
    {
        $list = db('buckle_invite_recharge_rule')->select()->toArray();

        $this->assign('list', $list);
        // 渲染模板输出
        return $this->fetch();
    }

    public function add_buckle_invite_recharge_rule()
    {
        $id = intval(input('param.id'));
        if ($id != 0) {
            $data = db('buckle_invite_recharge_rule')->find($id);
            $this->assign('data', $data);
        }

        return $this->fetch();
    }

    //增加邀请扣量规则
    public function add_buckle_invite_recharge_rule_post()
    {
        $param = $this->request->param();

        $param['create_time'] = NOW_TIME;
        if (isset($param['id']) && db('buckle_invite_recharge_rule')->find($param['id'])) {
            $id = $param['id'];
            unset($param['id']);
            db('buckle_invite_recharge_rule')->where('id=' . $id)->update($param);
        } else {
            db('buckle_invite_recharge_rule')->insert($param);
        }

        $this->success("保存成功");
    }

    //删除扣量规则
    public function del_buckle_invite_recharge_rule()
    {
        $id = input('param.id');
        db('buckle_invite_recharge_rule')->delete($id);

        echo 1;
        exit;
    }
}