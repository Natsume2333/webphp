<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/14 0014
 * Time: 上午 10:34
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class EarningsController extends AdminBaseController {
    //收益提现规则
    public function index(){

        $list = Db::name("user_earnings_withdrawal")->order("sort asc")->select()->toarray();
        $config = load_cache('config');
        foreach($list as &$v){
            $v['money'] = number_format($v['coin'] / $config['integral_withdrawal']);
        }

        $this->assign('list',$list);
        return $this->fetch();
    }
    /**
     * 提现添加
     */
    public function add() {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("user_earnings_withdrawal")->where("id=$id")->find();
            $this->assign('rule', $name);
        } else {
            $this->assign('rule', array('status' => 1));
        }
        return $this->fetch();
    }
    public function addPost() {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("user_earnings_withdrawal")->where("id=$id")->update($data);
        } else {
            $result = Db::name("user_earnings_withdrawal")->insert($data);
        }
        if ($result) {
            $this->success("保存成功", url('Earnings/index'));
        } else {
            $this->error("保存失败");
        }
    }
    //删除类型
    public function del() {
        $param = request()->param();
        $result = Db::name("user_earnings_withdrawal")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
    }
    //修改排序
    public function upd() {

        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("user_earnings_withdrawal")->where("id=$k")->update(array('sort' => $v));
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