<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/12/13
 * Time: 00:14
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;

class CashRuleController extends AdminBaseController
{

    public function index()
    {
        $cash_rule = db('cash_rule');

        $list = $cash_rule->order("create_time DESC")->paginate(20, false, ['query' => request()->param()]);
        $lists = $list->toArray();
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $lists['data']);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    public function add_cash_rule()
    {

        return $this->fetch();
    }

    //添加话术
    public function add_cash_rule_post()
    {

        $param = $this->request->param();
        $param['create_time'] = time();

        $result = db("cash_rule")->insert($param);
        if ($result) {
            $this->success("保存成功");
        } else {
            $this->error("保存失败");
        }
    }

    //删除话术
    public function del()
    {
        $id = input('param.id');
        db('cash_rule')->delete($id);
        $this->success("操作成功!");
    }

}