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
use think\Log;

class RefillController extends AdminBaseController
{

    public function add_recharge()
    {

        return $this->fetch();
    }

    public function add_recharge_post()
    {

        $user_id = input('param.user_id');
        $type = input('param.type');
        $action = input('param.action');
        $count = intval(input('param.count'));

        $data = array(
            'uid' => $user_id,
            'addtime' => NOW_TIME,
        );

        if ($action == 1) {
            if ($type == 1) {
                db('user')->where('id', '=', $user_id)->setInc('coin', $count);
            } else if ($action == 2) {
                db('user')->where('id', '=', $user_id)->setInc('income', $count);
                db('user')->where('id', '=', $user_id)->setInc('income_total', $count);
            } else {
                db('user')->where('id', '=', $user_id)->setInc('invitation_coin', $count);
            }
        } else {
            if ($type == 1) {
                db('user')->where('id', '=', $user_id)->setDec('coin', $count);
            } else if ($action == 2) {
                db('user')->where('id', '=', $user_id)->setDec('income', $count);
                db('user')->where('id', '=', $user_id)->setDec('income_total', $count);
            } else {
                db('user')->where('id', '=', $user_id)->setDec('invitation_coin', $count);
            }
        }

        $data['coin'] = $count;
        $data['user_type'] = $type;
        $data['type'] = $action;
        $data['operator'] = cmf_get_current_admin_id();
        db('recharge_log')->insert($data);

        $this->success("保存成功", url('refill/recharge'));

    }

    /**
     * 充值列表
     */
    public function index()
    {
        $num = input('num');
        $nummoney = 0;
        if (empty($num)) {
            $where = [];
        } else if ($num == 0) {
            $where = [];
        } else {
            $nummoney = db('user_charge_log')->where(['refillid' => $num])->sum('money');
            $where = ['id' => $num];
        }
        $list = Db::name("user_charge_rule")->where($where)->order("orderno asc")->select();
        $lists = Db::name("user_charge_rule")->order("orderno asc")->select();
        $this->assign(['list' => $list, 'lists' => $lists]);
        $this->assign('nummoney', $nummoney);
        return $this->fetch();
    }

    /**
     * 充值添加
     */
    public function add()
    {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("user_charge_rule")->where("id=$id")->find();
            $this->assign('rule', $name);
        } else {
            $this->assign('rule', array('type' => 0));
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
            $result = Db::name("user_charge_rule")->where("id=$id")->update($data);
        } else {
            $result = Db::name("user_charge_rule")->insert($data);
        }
        if ($result) {
            $this->success("保存成功", url('refill/index'));
        } else {
            $this->error("保存失败");
        }
    }

    //删除类型
    public function del()
    {
        $param = request()->param();
        $result = Db::name("user_charge_rule")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
    }

    //修改排序
    public function upd()
    {

        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("user_charge_rule")->where("id=$k")->update(array('orderno' => $v));
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

    //充值记录
    public function log_index()
    {

        $where = [];
        //dump($_REQUEST);
        if (!isset($_REQUEST['status']) || $_REQUEST['status'] == '-1') {
            $_REQUEST['status'] = '-1';
        } else {
            $where['c.status'] = $_REQUEST['status'];
        }
          if (!isset($_REQUEST['pay_type_id']) || $_REQUEST['pay_type_id'] == '-1') {
            $_REQUEST['pay_type_id'] = '-1';
        } else {
            $where['c.pay_type_id'] = $_REQUEST['pay_type_id'];
        }
        if (isset($_REQUEST['end_time']) && $_REQUEST['end_time'] != '' && isset($_REQUEST['start_time']) && $_REQUEST['start_time'] != '') {
            $where['c.addtime'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
        }

        if (isset($_REQUEST['uid']) && $_REQUEST['uid'] != '') {
            $where['c.uid'] = intval($_REQUEST['uid']);
        }

        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $where['c.order_id'] = intval($_REQUEST['order_id']);
        }

        if (isset($_REQUEST['os']) && $_REQUEST['os'] != '') {
            $where['c.os'] = $_REQUEST['os'];
        }

        $pageWhere = ['query' => request()->param()];

        $list = Db::name("user_charge_log")
            ->alias('c')
            ->join('pay_menu p', 'c.pay_type_id=p.id', 'LEFT')
            ->join('user u', 'u.id=c.uid', 'LEFT')
            ->field('c.*,p.pay_name,u.user_nickname')
            ->order('c.addtime desc')
            ->where($where)
            ->paginate(20, false, $pageWhere);

        $result = array();
        foreach ($list as &$v) {
            if ($v['type'] == 11111111) {
                $v['pay_name'] = 'PayPal';
            } else if ($v['type'] == 7777777) {
                $v['pay_name'] = $v['pay_name'] . '（VIP充值）';
            }
            $result[] = $v;
        }

        //总充值
        $total_money = db('user_charge_log')->alias('c')->where($where)->sum('money');
        $pay_menu = db('pay_menu')->where("status=1")->select();

        $this->assign('pay_menu', $pay_menu);
        $this->assign('total_money', $total_money);
        $this->assign('refill', $_REQUEST);
        $this->assign('list', $result);
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    //支付渠道列表
    public function pay_menu()
    {

        $list = db('pay_menu')->select();
        
        $lists = $list->toArray();
        foreach ($lists as &$v){
            $v['total_pay'] = db('user_charge_log')->where("pay_type_id = {$v['id']} and status = 1")->sum('money');
            unset($v);
        }
        
        $this->assign('list', $lists);
        // dump($this);
        // exit();
        return $this->fetch();
    }

    //添加充值渠道
    public function add_pay_menu()
    {
        return $this->fetch();
    }

    //添加充值渠道
    public function add_pay_menu_post()
    {

        $param = $this->request->param();
        $data = $param['post'];


        $result = Db::name("pay_menu")->insert($data);

        if ($result) {
            $this->success("保存成功", url('refill/pay_menu'));
        } else {
            $this->error("保存失败");
        }
    }

    //编辑充值渠道
    public function edit_pay_menu()
    {

        $id = input('param.id');

        $data = db('pay_menu')->find($id);
        $this->assign('data', $data);
        return $this->fetch();
    }

    //编辑支付渠道
    public function edit_pay_menu_post()
    {
        $param = $this->request->param();
        
        $id = $param['id'];
        $data = $param['post'];
		        
        if ($id) {
        	$sql = "UPDATE `cmf_pay_menu` SET `pay_name`='".$data['pay_name']."',`merchant_id`='".$data['merchant_id']."',`public_key`='".$data['public_key']."',`private_key`='".$data['private_key']."',`status`='".$data['status']."',`class_name`='".$data['class_name']."',`app_id`='".$data['app_id']."',`icon`='".$data['icon']."',`pay_type`='".$data['pay_type']."',`pay_class`='".$data['pay_class']."',`orderno`='".$data['orderno']."',`md5_key`='".$data['md5_key']."' WHERE id = ".$id;
        	$result = DB::name('pay_menu')->execute($sql);
        	// $result = Db::name("pay_menu")->where(array("id"=>$id))->save($data);
        } else {
            $result = Db::name("pay_menu")->insert($data);
        }
        if ($result) {
            $this->success("保存成功", url('refill/pay_menu'));
        } else {
            $this->error("保存失败");
        }
    }

    //删除类型
    public function del_pay_menu()
    {
        $param = request()->param();
        $result = Db::name("pay_menu")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    //查询手动充值记录
    public function recharge()
    {
        if (!input("param.page")) {
            $data['type'] = 0;
            $data['user_type'] = 0;
            session('refill_recharge', $data);
        }
        if (isset($_REQUEST['uid']) || isset($_REQUEST['type']) || isset($_REQUEST['user_type'])) {
            session('refill_recharge', $_REQUEST);
        }

        $uid = session('refill_recharge.uid') ? session('refill_recharge.uid') : '';
        $type = session('refill_recharge.type') ? session('refill_recharge.type') : '';
        $user_type = session('refill_recharge.user_type') ? session('refill_recharge.user_type') : '';
        $where = 'r.id >0 ';
        if ($uid) {
            $where .= " and r.uid=" . $uid;
        }
        if ($type) {
            $where .= " and r.type=" . $type;
        }
        if ($user_type > 0) {
            $where .= " and r.user_type=" . $user_type;
        }
        $where .= session('refill_recharge.end_time') ? " and r.addtime <=" . strtotime(session('refill_recharge.end_time')) : '';
        $where .= session('refill_recharge.start_time') ? " and r.addtime >=" . strtotime(session('refill_recharge.start_time')) : '';
        $pageWhere = ['query' => request()->param()];
        $list = Db::name('recharge_log')->alias("r")
            ->join("user u", "u.id=r.uid")
            ->where($where)
            ->field('u.user_nickname,r.*')
            ->order('r.addtime desc')
            ->paginate(20, false, $pageWhere);

        $this->assign("list", $list);
        $this->assign("page", $list->render());
        $this->assign('recharge', session('refill_recharge'));
        return $this->fetch();
    }

    /*导出*/
    public function export()
    {
        $title = '充值记录';

        $where = [];
        //dump($_REQUEST);
        if (!isset($_REQUEST['status']) || $_REQUEST['status'] == '-1') {
            $_REQUEST['status'] = '-1';
        } else {
            $where['c.status'] = $_REQUEST['status'];
        }

        if (isset($_REQUEST['end_time']) && $_REQUEST['end_time'] != '' && isset($_REQUEST['start_time']) && $_REQUEST['start_time'] != '') {
            $where['c.addtime'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
        }

        if (isset($_REQUEST['uid']) && $_REQUEST['uid'] != '') {
            $where['c.uid'] = intval($_REQUEST['uid']);
        }

        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $where['c.order_id'] = intval($_REQUEST['order_id']);
        }

        $list = Db::name("user_charge_log")
            ->alias('c')
            ->join('pay_menu p', 'c.pay_type_id=p.id', 'LEFT')
            ->join('user u', 'u.id=c.uid', 'LEFT')
            ->field('c.*,p.pay_name,u.user_nickname')
            ->order('c.addtime desc')
            ->where($where)
            ->paginate();

        $lists = $list->toArray();
        if ($lists['data'] != null) {

            foreach ($lists['data'] as $k => $v) {
                if ($v['type'] == 11111111) {
                    $v['pay_name'] = 'PayPal';
                }
                if ($v['status'] == '1') {
                    $status = '成功';
                } else {
                    $status = '失败';
                }
                $dataResult[$k]['uid'] = $v['uid'] ? $v['uid'] : '暂无数据';
                $dataResult[$k]['user_nickname'] = $v['user_nickname'] ? $v['user_nickname'] : '暂无数据';
                $dataResult[$k]['order_id'] = $v['order_id'] ? $v['order_id'] : '暂无';
                $dataResult[$k]['money'] = $v['money'] ? $v['money'] : '暂无';
                $dataResult[$k]['pay_pal_money'] = $v['pay_pal_money'] ? $v['pay_pal_money'] : '0';
                $dataResult[$k]['coin'] = $v['coin'] ? $v['coin'] : '暂无';
                $dataResult[$k]['pay_name'] = $v['pay_name'] ? $v['pay_name'] : '暂无';
                $dataResult[$k]['addtime'] = $v['addtime'] ? date('Y-m-d h:i', $v['addtime']) : '暂无';
                $dataResult[$k]['status'] = $status;

            }

            $str = "消费用户ID,消费用户,订单号,充值金额(元),PayPal(USD),金币数,充值方式,添加时间,充值状态";

            $this->excelData($dataResult, $str, $title);
            exit();
        } else {
            $this->error("暂无数据");
        }

    }

    public function ref_data()
    {
        $where = [];
        //dump($_REQUEST);
        if (!isset($_REQUEST['status']) || $_REQUEST['status'] == '-1') {
            $_REQUEST['status'] = '-1';
        } else {
            $where['c.status'] = $_REQUEST['status'];
        }

        if (isset($_REQUEST['end_time']) && $_REQUEST['end_time'] != '' && isset($_REQUEST['start_time']) && $_REQUEST['start_time'] != '') {
            $where['c.addtime'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
        }

        if (isset($_REQUEST['uid']) && $_REQUEST['uid'] != '') {
            $where['c.uid'] = intval($_REQUEST['uid']);
        }

        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $where['c.order_id'] = intval($_REQUEST['order_id']);
        }

        $list = Db::name("user_charge_log")
            ->alias('c')
            ->join('pay_menu p', 'c.pay_type_id=p.id', 'LEFT')
            ->join('user u', 'u.id=c.uid', 'LEFT')
            ->field('c.*,p.pay_name,u.user_nickname')
            ->order('c.addtime desc')
            ->where($where)
            ->select();

        $count = Db::name("user_charge_log")
            ->alias('c')
            ->join('pay_menu p', 'c.pay_type_id=p.id', 'LEFT')
            ->join('user u', 'u.id=c.uid', 'LEFT')
            ->field('c.*,p.pay_name,u.user_nickname')
            ->order('c.addtime desc')
            ->where($where)
            ->count();

        $result = array();
        foreach ($list as &$v) {
            if ($v['type'] == 11111111) {
                $v['pay_name'] = 'PayPal';
            }
            $result[] = $v;
        }

        $data = [
            "total" => $count,
            "rows" => $result,
        ];
        echo json_encode($data);
    }

}
