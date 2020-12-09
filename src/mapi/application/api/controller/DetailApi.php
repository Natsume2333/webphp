<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/4
 * Time: 20:48
 */

namespace app\api\controller;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

class DetailApi extends Base
{
  
/*app*/
//收入和支出明细
public function app_index(){
    $result = array('code' => 1, 'msg' => '','data'=>[]);
    $uid = intval(input('param.uid'));
    $token = trim(input('param.token'));
    $type = intval(input('param.type'));
    $page = intval(input('param.page')) ? intval(input('param.page')) : 1;
    $date = input('param.date');
    $p=($page -1)*20;
    
    $user_info = check_login_token($uid, $token);

    //判断所有还是聊币还是积分
    if ($type == 2) {
        //聊币：送礼物打电话的是聊币
        $where = 'l.user_id=' . $uid;
       $join='l.to_user_id=u.id';
      $money="l.coin";
    } else if($type == 1) {
        //积分：接受的是积分 ；师傅是：男的奖励聊币女的奖励积分
        $where = 'l.to_user_id=' . $uid;
        $join='l.user_id=u.id';
       $money="l.profit";
    }
    if($date){
       $starttime=strtotime($date);
       $endtime=strtotime($date."-".date('t',$starttime)." 23:59:59");
        $where.=" and l.create_time>=".$starttime." and l.create_time <=".$endtime;
    }
    //查询本用户的所有记录
   /* $record_list = db("user_consume_log")->alias("l")->where($where)
        ->field("l.coin,l.profit,l.content,l.create_time,l.table_id,l.type,l.user_id,l.to_user_id,u.user_nickname")
        ->join('user u', $join, 'LEFT')
        ->order("l.create_time desc")
        ->limit($p, 20)
        ->select();*/
    $record_list = db("user_consume_log")->alias("l")->where($where)
        ->field("l.coin,l.profit,l.content,l.create_time,l.table_id,l.type,l.user_id,l.to_user_id,u.user_nickname")
        ->join('user u', $join, 'LEFT')
        ->order("l.create_time desc")
        ->limit($p, 20)
        ->select();
   //查询本用户的所有记录
    $sum = db("user_consume_log")->alias("l")->where($where)
        ->field("l.coin,l.profit,l.content,l.create_time,l.table_id,l.type,l.user_id,l.to_user_id,u.user_nickname")
        ->join('user u', $join, 'LEFT')
        ->sum($money);
  
      $result['statistical']=$sum;
     $result['data']=$record_list;

     return_json_encode($result);

    }
   //充值明细
    public function app_recharge(){
        $result = array('code' => 1, 'msg' => '','data'=>[]);
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page')) ? intval(input('param.page')) : 1;
        $p=($page -1)*20;
        $date = input('param.date');
      
        $where = 'c.uid = ' . $uid." and c.status=1";
      
       if($date){
           $starttime=strtotime($date);
           $endtime=strtotime($date."-".date('t',$starttime)." 23:59:59");
            $where.=" and  c.addtime>=".$starttime." and   c.addtime <=".$endtime;
        }
      
        $list = Db("user_charge_log")->alias('c')
            ->join('pay_menu p', 'c.pay_type_id=p.id', 'LEFT')
            ->join('user u', 'u.id=c.uid', 'LEFT')
            ->field('c.coin,c.addtime,c.status,c.type')
            ->order('c.addtime desc')
            ->where($where)
            ->limit($p, 20)
            ->select();
      
         $sum = Db("user_charge_log")->alias('c')
            ->join('pay_menu p', 'c.pay_type_id=p.id', 'LEFT')
            ->join('user u', 'u.id=c.uid', 'LEFT')
            ->where($where)
            ->sum("c.coin");
      
     
         $result['statistical']=$sum;
       $result['data']=$list;
        return_json_encode($result);
    }

 //提现明细
    public function app_withdrawal(){
        
        $result = array('code' => 1, 'msg' => '','data'=>[]);
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page')) ? intval(input('param.page')) : 1;
        $p=($page -1)*20;
        $date = input('param.date');
      
        $where = 'user_id = ' . $uid;
      
       if($date){
           $starttime=strtotime($date);
           $endtime=strtotime($date."-".date('t',$starttime)." 23:59:59");
            $where.=" and  create_time>=".$starttime." and   create_time <=".$endtime;
        }
      
      
        $list = Db("user_cash_record")->order('create_time desc')->field("income,status,create_time,money")
            ->where($where)
            ->limit($p, 20)
            ->select();
       $sum = Db("user_cash_record")->order('create_time desc')->field("income,status,create_time,money")
            ->where($where)
            ->sum("money");
      
       $result['statistical']=$sum;
        $result['data']=$list;    
        return_json_encode($result);
    }

    /*h5*/
    
    public function _initialize()
    {
        $config = db('config')->where("code='currency_name'")->field("val")->find();
        $this->assign('config', $config);
    }

    //我的明细
    public function defaults()
    {
        $uid = input("param.uid");

        if (empty($uid)) {
            echo '用户uid传参错误';
            exit;
        }

        //总收益 积分：接受的是积分 ；师傅是：男的奖励聊币女的奖励积分
        $where = 'to_user_id=' . $uid;
        $profit = db("user_consume_log")->where($where)->sum("profit");

        //总消费 聊币：送礼物打电话的是聊币
        $where2 = 'user_id=' . $uid;
        $coin = db("user_consume_log")->where($where2)->sum("coin");

        $user = Db("user")->field("coin,user_nickname")->where("id=" . $uid)->find();

        $data = array(
            'user_nickname' => $user['user_nickname'],
            'coin' => $user['coin'],
            'income' => $profit,
            'spending' => $coin,
            'uid' => $uid,
        );
        $this->assign('list', $data);

        return $this->fetch();
    }

    //我的明细
    public function index()
    {
        $uid = input("param.uid");
        $type = intval(input('param.type'));

        if (empty($uid)) {
            echo '用户uid传参错误';
            exit;
        }

        $record_list = $this->getListData(0, $type, $uid);

        $this->assign('p', 0);
        $this->assign('uid', $uid);
        $this->assign('data', $record_list);
        $this->assign('type', $type);
        return $this->fetch();
    }

    //分页
    public function pages()
    {
        $page = input("param.page");

        $p = ($page + 1) * 20;
        $uid = input("param.uid");
        $record_list = $this->getListData($p, session("detail"), $uid);

        echo json_encode($record_list);
        exit;

    }

    public function getListData($page, $type, $uid)
    {

        session("detail", $type);

        //判断所有还是聊币还是积分
        if ($type == 1) {
            //聊币：送礼物打电话的是聊币
            $where = 'user_id=' . $uid;
        } else if ($type == 2) {
            //积分：接受的是积分 ；师傅是：男的奖励聊币女的奖励积分
            $where = 'to_user_id=' . $uid;
        }

        //查询本用户的所有记录
        $record_list = db("user_consume_log")->where($where)
            ->field("sum(coin) as coin,sum(profit) as profit,content,max(create_time) as create_time,table_id,type,user_id,to_user_id")
            ->order("create_time desc")
            ->limit($page, 20)
            ->group("type")
            ->select();

        foreach ($record_list as &$v) {

            if ($v['type'] == 4) {
                //视频通话
                $name = "视频通话收益";
            } elseif ($v['type'] == 3) {
                //礼物
                $name = '礼物收益';
            } elseif ($v['type'] == 2) {
                //私照
                $name = "私照收益";
            } elseif ($v['type'] == 1) {
                //视频
                $name = "小视频收益";
            } elseif ($v['type'] == 7) {
                //转盘抽奖
                $name = "转盘抽奖收益";
            } else {
                //系统赠送
                $name = $v['content'];
            }
            $v['name'] = $name;
            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);

            $where_name['id'] = $type == 1 ? $v['to_user_id'] : $v['user_id'];

            $user = db("user")->field("user_nickname")->where($where_name)->find();
            $v['user_nickname'] = $user['user_nickname'];
        }
        return $record_list;
    }

    //扣费和收益明细
    public function particulars()
    {

        $uid = input("param.uid");
        $type = input("param.type");
        $table = input("param.table");

        $record_list = $this->get_particulars(0, $type, $uid, $table);

        $this->assign('uid', $uid);
        $this->assign('p', 0);
        $this->assign('type', $type);
        $this->assign('table', $table);
        $this->assign('data', $record_list);
        return $this->fetch();
    }

    //扣费和收益明细分页
    public function particulars_page()
    {
        $page = input("param.page");
        $uid = input("param.uid");
        $type = input("param.type");
        $table = input("param.table");
        $p = ($page + 1) * 20;

        $record_list = $this->get_particulars($p, $type, $uid, $table);

        echo json_encode($record_list);
        exit;
    }

    public function get_particulars($page, $type, $uid, $table)
    {
        //查询本用户的所有记录
        //判断所有还是聊币还是积分

        if ($type == 1) {
            //聊币：送礼物打电话的是聊币
            $where = 'user_id=' . $uid;
        } else {
            //积分：接受的是积分 ；师傅是：男的奖励聊币女的奖励积分
            $where = 'to_user_id=' . $uid;
        }
        $where .= " and type=" . $table;
        $record_list = db("user_consume_log")->where($where)
            ->field("*")
            ->order("create_time desc")
            ->limit($page, 20)
            ->select();

        foreach ($record_list as &$v) {
            $v['create_time'] = date('Y-m-d H:i:s', $v['create_time']);

            $where_name['id'] = $type == 1 ? $v['to_user_id'] : $v['user_id'];

            $user = Db("user")->field("user_nickname")->where($where_name)->find();
            $v['user_nickname'] = $user['user_nickname'];
        }
        return $record_list;
    }

    //充值记录
    public function recharge()
    {
        $uid = input("param.uid");
        $page = input("param.page") ? input("param.page") : '';
        if ($page) {
            $p = $page * 20;
        } else {
            $p = 0;
        }

        if (empty($uid)) {
            echo '用户uid传参错误';
            exit;
        }

        $where = 'c.uid = ' . $uid;
        $list = Db("user_charge_log")
            ->alias('c')
            ->join('pay_menu p', 'c.pay_type_id=p.id', 'LEFT')
            ->join('user u', 'u.id=c.uid', 'LEFT')
            ->field('c.*,p.pay_name,u.user_nickname')
            ->order('c.addtime desc')
            ->where($where)
            ->limit($p, 20)
            ->select();

        $result = array();
        foreach ($list as &$v) {
            if ($v['type'] == 11111111) {
                $v['pay_name'] = 'PayPal';
            }
            $result[] = $v;
        }

        if ($p != '0') {

            echo json_encode($result);
            exit;
        } else {
            $this->assign('result', $result);
            $this->assign('uid', $uid);
            $this->assign('p', 1);
            return $this->fetch();
        }

    }

    //通话记录
    public function tellog()
    {
        $uid = input("param.uid");
        $page = input("param.page") ? input("param.page") : '';
        if ($page) {
            $p = $page * 20;
        } else {
            $p = 0;
        }

        if (empty($uid)) {
            echo '用户uid传参错误';
            exit;
        }
        $where = 'v.call_be_user_id = ' . $uid;
        $list = Db("video_call_record_log")
            ->alias('v')
            ->join('user u', 'u.id=v.user_id', 'LEFT')
            ->field('v.*,u.user_nickname')
            ->order('v.end_time desc')
            ->where($where)
            ->limit($p, 20)
            ->select();
        foreach ($list as &$v) {
            $vid = $v['id'];
            $channel_id = $v['channel_id'];
            $v['profit'] = db("user_gift_log")->where("channel_id=" . $channel_id)->sum("profit");
            $v['tel_sum'] = db("user_consume_log")->where("table_id=" . $vid . " and type=4")->sum("profit");
            if ($p != '0') {
                $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
                $v['profit'] = $v['profit'] ? $v['profit'] : '0';
                $v['tel_sum'] = $v['tel_sum'] ? $v['tel_sum'] : '0';
            }
        }
        if ($p != '0') {
            echo json_encode($list);
            exit;
        } else {
            $this->assign('list', $list);
            $this->assign('p', 1);
            $this->assign('uid', $uid);

            return $this->fetch();
        }

    }

    //通话记录礼物收益和通话记录收益
    public function earnings()
    {
        $uid = input("param.uid");
        if (empty($uid)) {
            echo '用户uid传参错误';
            exit;
        }
        $type = input("param.type");
        $id = input("param.id");
        $page = input("param.page") ? input("param.page") : '';
        if ($page) {
            $p = $page * 20;
        } else {
            $p = 0;
        }
        $tel = Db("video_call_record_log")->where("id=$id and anchor_id=" . $uid)->find();
        if ($tel) {
            if ($type == 1) {
                $list = db("user_consume_log")->alias('v')
                    ->where("v.table_id=" . $id . " and v.type=4 and v.to_user_id=" . $uid)
                    ->join('user u', 'u.id=v.user_id', 'LEFT')
                    ->field('v.*,u.user_nickname')
                    ->order('v.create_time desc')
                    ->limit($p, 20)
                    ->select();
            } else {
                $list = db("user_gift_log")->alias('v')
                    ->where("v.channel_id=" . $tel['channel_id'] . " and to_user_id=" . $uid)
                    ->join('user u', 'u.id=v.user_id', 'LEFT')
                    ->field('v.*,u.user_nickname')
                    ->order('v.create_time desc')
                    ->limit($p, 20)
                    ->select();
            }
            if ($p != '0') {
                foreach ($list as &$v) {
                    $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
                    $v['profit'] = $v['profit'] ? $v['profit'] : '0';
                    $v['tel_sum'] = $v['tel_sum'] ? $v['tel_sum'] : '0';
                }
            }
            if ($p != '0') {

                echo json_encode($list);
                exit;
            } else {
                $this->assign('list', $list);
                $this->assign('p', 1);
                $this->assign('uid', $uid);
                $this->assign('type', $type);
                return $this->fetch();
            }
        } else {
            echo '传参错误';
            exit;
        }

    }

    //提现记录
    public function withdrawal()
    {
        $uid = input("param.uid");
        $page = input("param.page") ? input("param.page") : '';
        if ($page) {
            $p = $page * 20;
        } else {
            $p = 0;
        }

        if (empty($uid)) {
            echo '用户uid传参错误';
            exit;
        }

        $where = 'user_id = ' . $uid;
        $list = Db("user_cash_record")->order('create_time desc')
            ->where($where)
            ->limit($p, 20)
            ->select();

        if ($p != '0') {
            foreach ($list as &$v) {
                $v['create_time'] = date("Y-m-d H:i:s", $v['create_time']);
            }
        }
        if ($p != '0') {
            echo json_encode($list);
            exit;
        } else {
            $this->assign('result', $list);
            $this->assign('uid', $uid);
            $this->assign('p', 1);
            return $this->fetch();
        }

    }

}