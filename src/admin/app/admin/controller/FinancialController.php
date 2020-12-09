<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/24 0024
 * Time: 下午 16:50
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class FinancialController extends AdminBaseController
{
        //查询财务报表
        public function index(){
            $where = [];
            if (!input('request.page')) {
                session('Financial', null);
            }
            if (input('request.starttime') || input('request.endtime')) {
                session('Financial', input('request.'));
            }
            $starttime=session('Financial.starttime');
            $endtime=session('Financial.endtime') ? session('Financial.endtime') : date("Y-m-d");
            if($starttime){
                $where['time'] = array('between', array($starttime,$endtime));
            }
            $this->business_day();
            $financial = db("admin_financial")->where($where)->order("time desc")->paginate(20);

            $this->assign('financial', $financial);
            $this->assign('request', session('Financial'));
            $this->assign('page', $financial->render());
            return $this->fetch();

        }

        //获取今天的营业
    public function business_day(){
        $data['time']=date("Y-m-d");
        //获取当天
        $beginToday=mktime(0,0,0,date('m'),date('d'),date('Y'));
        $endToday=mktime(0,0,0,date('m'),date('d')+1,date('Y'))-1;
        $where1="status=1 and addtime >=".$beginToday." and addtime <".$endToday;
        $where2="status =1 and updatetime >=".$beginToday." and updatetime <".$endToday;
        //今天收入的金额(充值记录)
        $data['income'] = db("user_charge_log")->where($where1)->sum("money");
        //今天支出的金额 (提现记录)
        //invite_cash_record    邀请收益提现  agent_withdrawal 代理提现  user_cash_record  用户主播提现
        $invite_cash_record= db("invite_cash_record")->where($where2)->sum("coin");
        $agent_withdrawal = db("agent_withdrawal")->where($where2)->sum("money");
        $user_cash_record = db("user_cash_record")->where($where2)->sum("money");

        $invite_cash_record=$invite_cash_record ? $invite_cash_record :'0';
        $agent_withdrawal=$agent_withdrawal ?$agent_withdrawal :'0';
        $user_cash_record= $user_cash_record ? $user_cash_record :'0';
        $data['income'] = $data['income'] ? $data['income'] :'0';
        $data['spending']=$invite_cash_record + $agent_withdrawal + $user_cash_record;

        $data['invite_record']=$invite_cash_record;
        $data['host_record']=$user_cash_record;
        $data['agent_record']=$agent_withdrawal;

        if($data['income'] >= $data['spending']){
            $data['type']=1;
            $data['statistical']= $data['income'] - $data['spending'];
        }else{
            $data['type']=2;
            $data['statistical']= $data['spending'] - $data['income'];
        }
        $data['addtime']=time();

        $financial = db("admin_financial")->where("time = '".$data['time']."'")->find();

        if($financial){
            db('admin_financial')->where("time='".$data['time']."'")->update($data);
        }else{
            db('admin_financial')->insert($data);

        }

    }

}