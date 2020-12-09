<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/7/25
 * Time: 17:45
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
class InviteManageController extends AdminBaseController
{
    //邀请充值扣量记录
    public function invite_recharge_deduction_log()
    {
        $where = [];
        if (isset($_REQUEST['order_id']) && $_REQUEST['order_id'] != '') {
            $where['order_id'] = intval($_REQUEST['order_id']);
        }

        if (isset($_REQUEST['user_id']) && $_REQUEST['user_id'] != '') {
            $where['user_id'] = intval($_REQUEST['user_id']);
        }

        if (isset($_REQUEST['invite_user_id']) && $_REQUEST['invite_user_id'] != '') {
            $where['invite_user_id'] = intval($_REQUEST['invite_user_id']);
        }

        $list = db('invite_recharge_deduction_record')
            ->where($where)
            ->order('create_time desc')
            ->paginate(20, false, ['query' => request()->param()]);

        $data = $list->toArray()['data'];
        $this->assign('request', $_REQUEST);
        $this->assign('list', $data);
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    //邀请注册扣量记录
    public function invite_reg_deduction_log()
    {
        $where = [];
        if (isset($_REQUEST['user_id']) && $_REQUEST['user_id'] != '') {
            $where['user_id'] = intval($_REQUEST['user_id']);
        }

        if (isset($_REQUEST['invite_user_id']) && $_REQUEST['invite_user_id'] != '') {
            $where['invite_user_id'] = intval($_REQUEST['invite_user_id']);
        }

        $list = db('invite_reg_deduction_record')
            ->where($where)
            ->order('create_time desc')
            ->paginate(20, false, ['query' => request()->param()]);

        $data = $list->toArray()['data'];
        $this->assign('request', $_REQUEST);
        $this->assign('list', $data);
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    //邀请收益记录
    public function income_index()
    {

        $where = [];

        if (!input('request.page')) {
            session('income_index', null);
        }
        if (input('request.invite_code') || input('request.user_id') || input('request.invite_user_id') || input('request.c_id') || input('request.start_time') || input('request.end_time')) {
            session('income_index', input('request.'));
        }
        if (session('income_index.invite_code')) {
            $where['i.invite_code'] = session('income_index.invite_code');
        }
        if (session('income_index.user_id')) {
            $where['i.user_id'] = session('income_index.user_id');
        }
        if (session('income_index.invite_user_id')) {
            $where['i.invite_user_id'] = session('income_index.invite_user_id');
        }
        if (session('income_index.c_id')) {
            $where['i.c_id'] = session('income_index.c_id');
        }

        if (session('income_index.end_time') && session('income_index.start_time')) {
            $where['i.create_time'] = ['between', [strtotime(session('income_index.start_time')), strtotime(session('income_index.end_time'))]];
        }

        $list = db('invite_profit_record')
            ->alias('i')
            ->join('user u', 'i.user_id=u.id')
            ->field('u.user_nickname,i.*')
            ->where($where)
            ->order('i.create_time desc')
            ->paginate(20, false, ['query' => request()->param()]);

        $data = $list->toArray()['data'];
        foreach ($data as &$v) {
            //$user_info = db('user') -> where('id','=',$v['user_id']) -> field('user_nickname') -> find();
            $invite_user_info = db('user')->where('id', '=', $v['invite_user_id'])->field('user_nickname')->find();
            //$v['user_nickname'] = $user_info['user_nickname'];
            $v['invite_user_nickname'] = $invite_user_info['user_nickname'];
        }

        $this->assign('request', session('income_index'));
        $this->assign('list', $data);
        $this->assign('page', $list->render());
        return $this->fetch();
    }

    //邀请记录
    public function invite_record_index()
    {

        $where_i = [];
        $where_add_time = [];
        $where_create_time = [];
        if (isset($_REQUEST['user_id']) && $_REQUEST['user_id'] != '') {
            $where_i['i.user_id'] = $_REQUEST['user_id'];
        }

        if (isset($_REQUEST['end_time']) && $_REQUEST['end_time'] != '' && isset($_REQUEST['start_time']) && $_REQUEST['start_time'] != '') {
            $where_i['i.create_time'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
            $where_add_time['addtime'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
            $where_create_time['create_time'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
        }


        $list = db('invite_record')
            ->alias('i')
            ->join('user u', 'i.user_id=u.id')
            ->field('u.user_nickname,i.*,u.invitation_coin,u.sex')
            ->group('i.user_id')
            ->order('i.create_time desc')
            ->where($where_i)
            ->paginate(20, false, ['query' => request()->param()]);

        $data = $list->toArray()['data'];

        foreach ($data as &$v) {
            //付费人数
            $invite_user_list = db('invite_record')->where('user_id', '=', $v['user_id'])->where($where_create_time)->select()->toArray();
            $invite_user_ids = [];
            foreach ($invite_user_list as $v2) {
                if ($v2['invite_user_id'] == $v['user_id']) {
                    continue;
                }
                $invite_user_ids[] = $v2['invite_user_id'];
            }
            //总付费次数
            $v['total_pay_count'] = db('user_charge_log')->where('uid', 'in', $invite_user_ids)->where('status=1')->where($where_add_time)->group('uid')->count();
            //dump($invite_user_ids);
            //echo  '<br/>';
            //echo db('user_charge_log') -> getLastSql() . '<br/>';
            //总付费金额
            $v['total_pay_money'] = db('user_charge_log')->where('uid', 'in', $invite_user_ids)->where('status=1')->where($where_add_time)->sum('money');
            //邀请总人数
            $v['invite_count'] = count($invite_user_list);
            $v['invite_total_income'] = db('invite_profit_record')->where('user_id', '=', $v['user_id'])->where($where_create_time)->sum('money');
            $v['cash_total'] = db('invite_cash_record')->where("status !=2  and uid=" . $v['user_id'])->where($where_add_time)->sum("coin");
            //邀请男性数量
            $v['invite_male_count'] = db('invite_record')->alias('i')
                ->join('user u', 'i.invite_user_id=u.id')
                ->where('i.user_id', '=', $v['user_id'])
                ->where('u.sex', '=', 1)
                ->where($where_i)
                ->count();
            //邀请女性数量
            $v['invite_female_count'] = $v['invite_count'] - $v['invite_male_count'];

            //arpu
            $v['arpu'] = ($v['total_pay_money'] == 0 || $v['invite_male_count'] == 0) ? 0 : ($v['total_pay_money'] / $v['invite_male_count']);
            $v['arpu'] = round($v['arpu'], 2) . '元';
        }

        $this->assign('list', $data);
        $this->assign('page', $list->render());
        $this->assign('request', $_REQUEST);
        return $this->fetch();
    }

    /*
     * 邀请提现记录
     */
    public function withdrawal()
    {

        $where = [];
        if (!input("param.page")) {
            session("withdrawal", null);
        }
        if (isset($_REQUEST['uid']) || isset($_REQUEST['start_time']) || isset($_REQUEST['end_time']) || isset($_REQUEST['status'])) {
            session("withdrawal", $_REQUEST);
        }
        $uid = session("withdrawal.uid") ? session("withdrawal.uid") : '';
        $start_time = session("withdrawal.start_time") ? session("withdrawal.start_time") : '';
        $end_time = session("withdrawal.end_time") ? session("withdrawal.end_time") : '';
        $status = (session("withdrawal.status") != '-1') && (session("withdrawal.status") != '') ? session("withdrawal.status") : '';
        if ($uid) {
            $where['i.uid'] = $uid;
        }

        if ($start_time) {
            $starttime = strtotime($start_time . ":00");
            $where['i.addtime'] = ['> time', $starttime];
        }
        if ($end_time) {
            $endtime = strtotime($end_time . ":59");
            $where['i.addtime'] = ['< time', $endtime];
        }
        if ($status || $status == '0') {
            $where['i.status'] = $status;
        }
        $money = db('invite_cash_record')
            ->alias('i')
            ->join('user u', 'i.uid=u.id')
            ->join('user_cash_account c', 'i.pay=c.id')
            ->field('u.user_nickname,i.*,c.pay,c.wx,c.name')
            ->where($where)
            ->sum("i.coin");

        $list = db('invite_cash_record')
            ->alias('i')
            ->join('user u', 'i.uid=u.id')
            ->join('user_cash_account c', 'i.pay=c.id')
            ->field('u.user_nickname,i.*,c.pay,c.wx,c.name')
            ->order('i.addtime desc')
            ->where($where)
            ->paginate(10, false, ['query' => request()->param()]);

        $data = $list->toArray();

        $this->assign('list', $data['data']);
        $this->assign('money', $money);
        $this->assign('page', $list->render());
        $this->assign('request', session("withdrawal"));
        return $this->fetch();
    }

    /*
     *  操作数据库提现
    */

    public function operation()
    {
        $id = input("param.id");
        $type = input("param.type");

        $root = array('msg' => '参数错误', 'status' => 0);
        if (!$id) {
            echo json_encode($root);
            exit;
        }

        $list = db('invite_cash_record')->where("id=$id")->update(array('status' => $type, 'addtime' => time()));

        if ($list) {
            $user = db('invite_cash_record')->where("id=$id")->field("uid,coin")->find();
            if ($type == 2) {
                db('user')->where('id=' . $user['uid'])->inc('invitation_coin', $user['coin'])->update();
            }
            $root['msg'] = '操作成功';
            $root['status'] = '1';
        } else {
            $root['msg'] = '操作失败';
        }
        echo json_encode($root);
        exit;
    }

    /*导出*/
    public function export()
    {
        $where = [];
        if (isset($_REQUEST['invite_code']) && $_REQUEST['invite_code'] != '') {
            $where['i.invite_code'] = $_REQUEST['invite_code'];
        }

        if (isset($_REQUEST['user_id']) && $_REQUEST['user_id'] != '') {
            $where['i.user_id'] = $_REQUEST['user_id'];
        }

        if (isset($_REQUEST['invite_user_id']) && $_REQUEST['invite_user_id'] != '') {
            $where['i.invite_user_id'] = $_REQUEST['invite_user_id'];
        }
        if ($_REQUEST['c_id']) {
            $where['i.c_id'] = $_REQUEST['c_id'];
        }
        if ($_REQUEST['end_time'] && $_REQUEST['start_time']) {
            $where['i.create_time'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
        }

        $list = db('invite_profit_record')
            ->alias('i')
            ->join('user u', 'i.user_id=u.id')
            ->field('u.user_nickname,i.*')
            ->where($where)
            ->order('i.create_time desc')
            ->paginate();

        $lists = $list->toArray();

        if ($lists['data'] != null) {
            // print_r($lists);exit;
            foreach ($lists['data'] as $k => $v) {

                $invite_user_info = db('user')->where('id', '=', $v['invite_user_id'])->field('user_nickname')->find();
                //$v['user_nickname'] = $user_info['user_nickname'];
                if ($v['type'] == '1') {
                    $name = "收益奖励";
                } elseif ($v['type'] == '2') {
                    $name = "充值奖励";
                } else {
                    $name = "其他";
                }

                $dataResult[$k]['user_id'] = $v['user_id'] ? $v['user_id'] : '暂无数据';
                $dataResult[$k]['user_nickname'] = $v['user_nickname'] ? $v['user_nickname'] : '暂无数据';
                $dataResult[$k]['invite_user_nickname'] = $invite_user_info['user_nickname'] ? $invite_user_info['user_nickname'] : '暂无';
                $dataResult[$k]['invite_user_id'] = $v['invite_user_id'] ? $v['invite_user_id'] : '暂无';
                $dataResult[$k]['total_coin'] = $v['total_coin'] ? $v['total_coin'] . "(" . $name . ")" : '0';
                $dataResult[$k]['income'] = $v['income'] ? $v['income'] : '0';
                $dataResult[$k]['money'] = $v['money'] ? $v['money'] : '0';
                $dataResult[$k]['order_id'] = $v['order_id'] ? $v['order_id'] : '无';
                $dataResult[$k]['c_id'] = $v['c_id'] ? $v['c_id'] : '无';
                $dataResult[$k]['create_time'] = $v['create_time'] ? date('Y-m-d h:i', $v['create_time']) : '暂无';

            }

            $str = "邀请用户ID,邀请用户,被邀请用户,被邀请用户ID,总收益或充值,收益积分数量,收益人民币,充值订单,消费记录,收益时间";
            $title = "邀请收益记录列表";
            $this->excelData($dataResult, $str, $title);
            exit();
        } else {
            $this->error("暂无数据");
        }

    }

    /*邀请收益提现导出*/
    public function withdrawal_export()
    {
        $where = [];
        if (!input("param.page")) {
            session("withdrawal", null);
        }
        if (isset($_REQUEST['uid']) || isset($_REQUEST['start_time']) || isset($_REQUEST['end_time']) || isset($_REQUEST['status'])) {
            session("withdrawal", $_REQUEST);
        }
        $uid = session("withdrawal.uid") ? session("withdrawal.uid") : '';
        $start_time = session("withdrawal.start_time") ? session("withdrawal.start_time") : '';
        $end_time = session("withdrawal.end_time") ? session("withdrawal.end_time") : '';
        $status = (session("withdrawal.status") != '-1') && (session("withdrawal.status") != '') ? session("withdrawal.status") : '';
        if ($uid) {
            $where['i.uid'] = $uid;
        }

        if ($start_time) {
            $starttime = strtotime($start_time . ":00");
            $where['addtime'] = ['> time', $starttime];
        }
        if ($end_time) {
            $endtime = strtotime($end_time . ":59");
            $where['addtime'] = ['< time', $endtime];
        }
        if ($status || $status == '0') {
            $where['i.status'] = $status;
        }

        $list = db('invite_cash_record')
            ->alias('i')
            ->join('user u', 'i.uid=u.id')
            ->field('u.user_nickname,i.*')
            ->order('i.addtime desc')
            ->where($where)
            ->paginate();

        $lists = $list->toArray();

        if ($lists['data'] != null) {

            foreach ($lists['data'] as $k => $v) {

                if ($v['status'] == '1') {
                    $name = "已提现";
                } elseif ($v['type'] == '2') {
                    $name = "提现失败";
                } else {
                    $name = "审核中";
                }

                $dataResult[$k]['uid'] = $v['uid'] ? $v['uid'] : '暂无数据';
                $dataResult[$k]['user_nickname'] = $v['user_nickname'] ? $v['user_nickname'] : '暂无数据';
                $dataResult[$k]['coin'] = $v['coin'] ? $v['coin'] : '暂无';
                $dataResult[$k]['pay'] = $v['pay'] ? $v['pay'] : '暂无';
                $dataResult[$k]['name'] = $name;
                $dataResult[$k]['addtime'] = $v['addtime'] ? date('Y-m-d h:i', $v['addtime']) : '暂无';

            }

            $str = "提现用户ID,提现用户,提现金额,支付宝账号,状态,申请时间";
            $title = "邀请收益提现列表";
            $this->excelData($dataResult, $str, $title);
            exit();
        } else {
            $this->error("暂无数据");
        }

    }


    /*
     * 增加邀请关联
     * */
    public function relation()
    {
        return $this->fetch();
    }

    /*
     * 查询邀请
     * */
    public function sel()
    {
        $data = array('status' => 0, 'msg' => '邀请失败，请重新邀请');
        $invite_user_id = $_REQUEST['invite_user_id'];
        $user_id = $_REQUEST['user_id'];

        if (!$invite_user_id) {
            $data['msg'] = "请输入被邀请人ID";
            return $data;
        }

        if (!$user_id) {
            $data['msg'] = "请输入邀请人ID";
            return $data;
        }

        $invite_user_info = db('user')->where("id=" . $invite_user_id)->find();

        if (!$invite_user_info) {
            $data['msg'] = "被邀请人ID不存在";
            return $data;
        }

        $user = db('user')->where("id=" . $user_id)->find();

        if (!$user) {
            $data['msg'] = "邀请人ID不存在";
            return $data;
        }

        if ($invite_user_info['link_id'] != 0) {
            $data['msg'] = "用户ID已在被渠道邀请";
            return $data;
        }

        $invite_record = db('invite_record')->where("invite_user_id=" . $invite_user_id)->find();

        if ($invite_record) {
            $data['status'] = 2;
            $data['msg'] = "被邀请人已经存在邀请关系上级ID：" . $invite_record['user_id'] . ",是否更改邀请人？";
            return $data;
        }

        $invite_new_data = array(
            'user_id' => $user_id,
            'invite_user_id' => $invite_user_id,
            'invite_code' => $user_id,
            'create_time' => time(),
        );
        $record = db('invite_record')->insert($invite_new_data);
        if ($record) {
            $data['status'] = "1";
            $data['msg'] = "邀请成功";
        }

        return $data;
    }

    /*
     * 更改邀请人
     * */
    public function sel_upd()
    {
        $invite_user_id = $_REQUEST['invite_user_id'];
        $user_id = $_REQUEST['user_id'];
        $data = array('status' => 0, 'msg' => '更改邀请失败，请重新邀请');
        $name = array(
            'user_id' => $user_id,
            'invite_code' => $user_id,
            'create_time' => time(),
        );
        $record = db('invite_record')->where("invite_user_id=" . $invite_user_id)->update($name);
        if ($record) {
            $data['status'] = 1;
            $data['msg'] = "更改成功";
        }
        return $data;
    }

    //每日邀请
    public function invite_day(){
        $resAll = db('invite_record')->select();
        $res = Db::query("SELECT FROM_UNIXTIME(create_time,'%Y-%m-%d') as name , count(*) as value FROM cmf_invite_record GROUP BY name ");
        foreach ($res as $key => $value) {
            $coin = 0;
            foreach ($resAll as $val) {
                $time = date('Y-m-d',$val['create_time']);
                if($value['name']==$time){
                    $where['addtime'] = ['between',[strtotime($time),strtotime($time)+86400]];
                    $where['uid'] = $val['invite_user_id']; 
                    $coin+= db('user_charge_log')->where($where)->sum('money');
                }
            }

            $res[$key]['coin'] = $coin;
        }
        $this->assign('list',$res);
        return $this->fetch();
    }
}