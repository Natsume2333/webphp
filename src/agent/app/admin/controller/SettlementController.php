<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/21 0021
 * Time: 上午 10:25
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use QcloudApi;
use think\Db;

class SettlementController extends AdminBaseController
{
    //结算记录
    public function index()
    {
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('start_time') and !$this->request->param('end_time') and !$this->request->param('channel_agent')) {
            session("Settlement", null);
        } else if (empty($p)) {

            $data['channel_agent'] = $this->request->param('channel_agent');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            session("Settlement", $data);
        }

        $start_times = session("Settlement.start_time");
        $end_times = session("Settlement.end_time");
        $channel_agent = session("Settlement.channel_agent");


        $start_time = $start_times ? strtotime($start_times) : '0';

        $end_time = $end_times ? strtotime($end_times) : time();

        $id = cmf_get_current_admin_id();

        $where = 'a.addtime >=' . $start_time . ' and a.addtime <=' . $end_time;
        $where .= ' and a.agent_id=' . $id . ' or a.level=' . $id;
        if ($channel_agent) {
            $where .= " and a.channel='" . $channel_agent . "'";
        }
        $list = Db::name('agent_withdrawal')->alias("a")
            ->field("a.*,b.agent_login")
            ->join("agent b", "b.id=a.agent_id")
            ->order("a.addtime desc")
            ->where($where)
            ->paginate(10);

        // 获取分页显示
        $page = $list->render();

        $user = $list->toArray();

        //统计获取的总金额
        $count = Db::name('agent_withdrawal')->alias("a")
            ->join("agent b", "b.id=a.agent_id")
            ->order("a.addtime desc")
            ->where($where)
            ->sum("money");

        $data = array(

            'count' => $count ? $count : '0',

            'end_time' => $end_times,

            'start_time' => $start_times,
        );

        $this->assign("page", $page);

        $this->assign("data", $data);

        $this->assign("users", $user['data']);

        return $this->fetch();

    }

    //渠道统计
    public function conversion()
    {
        $id = cmf_get_current_admin_id();

        $agent = Db::name('agent')->where("id=$id")->find();
//var_dump($agent);exit;
        get_conversion($agent);   //插入统计的数据

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('channel_agent') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            session("conversion", null);
            $data['start_time'] = date('Y-m-d ', time());
            $data['end_time'] = date('Y-m-d ', time() + 3600 * 24);
            session("conversion", $data);
        } else if (empty($p)) {

            $data['channel_agent'] = $this->request->param('channel_agent');
            if ($this->request->param('start_time')) {
                $data['start_time'] = $this->request->param('start_time');
            } else {
                $data['start_time'] = date('Y-m-d', time());
            }
            if ($this->request->param('end_time')) {
                $data['end_time'] = $this->request->param('end_time');
            } else {
                $data['end_time'] = date('Y-m-d ', time() + 3600 * 24);
            }

            session("conversion", $data);
        }

        $channel_agent = session("conversion.channel_agent");
        $start_time = session("conversion.start_time");
        $end_time = session("conversion.end_time");

        $where="channel='".$agent['channel']."'";

        if ($start_time == $end_time) {
            $where .= " and data_time ='" . $end_time . "'";
        } else {
            $where .= " and data_time >='" . $start_time . "' and data_time <'" . $end_time . "'";
        }
        if ($channel_agent) {
            $where .= " and channel_agent ='$channel_agent'";
        }

        if ($agent['agent_level'] != '1') {
            $where .= " and channel_agent ='" . $agent['channel_agent_link'] . "'";
        }

        $agent_list = Db::name('agent_statistical')->field("data_time,channel_agent,sum(money) as money,sum(registered) as registered,sum(agent_earnings) as agent_earnings,sum(earnings) as earnings")->where($where)->order("data_time desc")->group("data_time desc")->paginate(10);
      //    var_dump(Db::name('agent_statistical')->getlastsql());exit;
        $agent_money = Db::name('agent_statistical')->field("sum(money) as money,sum(registered) as registered,sum(agent_earnings) as agent_earnings")->where($where)->find();

        
        // 获取分页显示
        $page = $agent_list->render();

        $user = $agent_list->toArray();

        $this->assign("agent_money", $agent_money);
        $this->assign("page", $page);
        $this->assign("agent", $agent);
        $this->assign("conversion", session("conversion"));
        $this->assign("users", $user['data']);

        return $this->fetch();
    }

    //子渠道统计
    public function childconversion()
    {
        $id = cmf_get_current_admin_id();

        $agent = Db::name('agent')->where("id=$id")->find();

        get_conversion($agent);   //插入统计的数据

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('channel_agent') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            session("conversion", null);
            $data['start_time'] = date('Y-m-d ', time());
            $data['end_time'] = date('Y-m-d ', time() + 3600 * 24);
            session("conversion", $data);
        } else if (empty($p)) {

            $data['channel_agent'] = $this->request->param('channel_agent');
            if ($this->request->param('start_time')) {
                $data['start_time'] = $this->request->param('start_time');
            } else {
                $data['start_time'] = date('Y-m-d ', time());
            }
            if ($this->request->param('end_time')) {
                $data['end_time'] = $this->request->param('end_time');
            } else {
                $data['end_time'] = date('Y-m-d ', time() + 3600 * 24);
            }

            session("conversion", $data);
        }

        $channel_agent = session("conversion.channel_agent");
        $start_time = session("conversion.start_time");
        $end_time = session("conversion.end_time");

        $where = "channel='" . $agent['channel'] . "'";

        if ($start_time == $end_time) {
            $where .= " and data_time ='" . $end_time . "'";
        } else {
            $where .= " and data_time >='" . $start_time . "' and data_time <'" . $end_time . "'";
        }
        if ($channel_agent) {
            $where .= " and channel_agent ='$channel_agent'";
        }

        if ($agent['agent_level'] != '1') {
            $where .= " and channel_agent ='" . $agent['channel_agent_link'] . "'";
        }

        $agent_list = Db::name('agent_statistical')->where($where)->order("data_time desc")->paginate(10);
        $agent_money = Db::name('agent_statistical')->field("sum(money) as money,sum(registered) as registered,sum(earnings) as earnings")->where($where)->find();

        // 获取分页显示
        $page = $agent_list->render();

        $user = $agent_list->toArray();

        $this->assign("agent_money", $agent_money);
        $this->assign("page", $page);
        $this->assign("agent", $agent);
        $this->assign("conversion", session("conversion"));
        $this->assign("users", $user['data']);

        return $this->fetch();
    }


    //申请提现
    public function withdrawal()
    {

        $id = cmf_get_current_admin_id();
        $user = Db::name('agent')->where("id=" . $id)->find();
        if ($user['agent_level'] == '1') {
            $type = 'divide_into1';
        } elseif ($user['agent_level'] == '2') {
            $type = 'divide_into2';
        } else {
            $type = 'divide_into3';
        }
        //获取代理的总收益
        $name = Db::name('agent_settlement')->where("agent_id1=$id or agent_id2=$id or agent_id3=$id")->select();
        $sum = '0';
        foreach ($name as $v) {
            $sum += round($v['money'] * $v[$type] / 100, 2);
        }

        //获取提现金额

        $tix = Db::name('agent_withdrawal')->where("agent_id=$id and status !=2")->sum("money");

        //获取剩余金额
        $residue = $sum - $tix;

        $data = array(
            'sum' => $sum,
            'residue' => $residue,
            'withdrawal' => $tix
        );
        $this->assign("data", $data);
        return $this->fetch();
    }

    //提现
    public function addwithdrawal()
    {
        $id = cmf_get_current_admin_id();

        $money = $this->request->param('money');
        $data = array(
            'money' => $money,
            'agent_id' => $id,
            'addtime' => time(),

        );
        $user = Db::name("agent_withdrawal")->insertGetId($data);
        if ($user) {
            echo json_encode("1");
        } else {
            echo json_encode("0");
        }

    }

    //显示提现记录
    public function withdrawalrecord()
    {
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('start_time') and !$this->request->param('end_time') and !$this->request->param('type')) {
            session("withdrawalrecord", null);
        } else if (empty($p)) {

            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            $data['type'] = $this->request->param('type');

            session("withdrawalrecord", $data);
        }

        $start_times = session("withdrawalrecord.start_time");
        $end_times = session("withdrawalrecord.end_time");
        $type = session("withdrawalrecord.type");


        $start_time = $start_times ? strtotime($start_times) : '0';

        $end_time = $end_times ? strtotime($end_times) : time();


        $id = cmf_get_current_admin_id();

        $where['agent_id'] = $id;

        $where['addtime'] = array('between', array($start_time, $end_time));

        $type >= '0' ? $where['status'] = $type : '';

        $user = Db::name("agent_withdrawal")->where($where)->order("addtime desc")->paginate(10);

        $money = Db::name("agent_withdrawal")->where($where)->sum("money");    //统计总提现金额

        $page = $user->render();

        $user = $user->toArray();

        $this->assign("page", $page);

        $this->assign("data", session("withdrawalrecord"));

        $this->assign("money", $money);

        $this->assign("users", $user['data']);

        return $this->fetch();
    }

    //显示代理提现记录
    public function agentwithdrawal()
    {
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('start_time') and !$this->request->param('end_time') and !$this->request->param('type')) {
            session("agentwithdrawal", null);
        } else if (empty($p)) {

            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            $data['type'] = $this->request->param('type');

            session("agentwithdrawal", $data);
        }

        $start_times = session("agentwithdrawal.start_time");
        $end_times = session("agentwithdrawal.end_time");
        $type = session("agentwithdrawal.type");


        $start_time = $start_times ? strtotime($start_times) : '0';

        $end_time = $end_times ? strtotime($end_times) : time();


        $id = cmf_get_current_admin_id();

        $where['a.superior_id'] = $id;

        $where['s.addtime'] = array('between', array($start_time, $end_time));

        $type >= '0' ? $where['s.status'] = $type : '';

        $user = Db::name("agent_withdrawal")->alias("s")
            ->where($where)
            ->join("agent a", "a.id=s.agent_id")
            ->field("s.*")
            ->order("s.addtime desc")
            ->paginate(10);

        $money = Db::name("agent_withdrawal")->alias("s")
            ->where($where)
            ->join("agent a", "a.id=s.agent_id")->sum("money");    //统计总提现金额

        $page = $user->render();

        $user = $user->toArray();

        $this->assign("page", $page);

        $this->assign("data", session("agentwithdrawal"));

        $this->assign("money", $money);

        $this->assign("users", $user['data']);

        return $this->fetch();
    }

    //修改提现状态
    public function agenttype()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $data = array(
            'status' => $status,
        );
        $name = Db::name("agent_withdrawal")->where("id=$id")->update($data);
        if ($name) {
            $this->success(lang('操作成功'));
        } else {
            $this->error(lang('操作失败'));
        }
    }

       //获取链接
    public function link()
    {

        $id = cmf_get_current_admin_id();
        $user = Db::name("agent")->where("id=$id")->find();
      
        $link = $user['channel_agent_link'];
        //短链接 /share/ 替换了   /agent/public/admin/download/index?agent=
        //正则 rewrite  "^/share/([a-z0-9_]{1,32})$" /agent/public/admin/download/index?agent=$1 last;  伪静态
        
        //通用落地页
        $user['link'] = "http://" . $_SERVER['HTTP_HOST'] . "/agent/public/admin/download/index?agent=" . $link;

        //QQ渠道链接推广
        $qq_link = Db::name("config")->where("code ='qq_link'")->find();

        $user['qq_link'] = $qq_link['val'] . "/agent/public/admin/download/index?agent=" . $link;
        //微信链接推广
        $wx_link = Db::name("config")->where("code ='wx_link'")->find();

        $user['wx_link'] = $wx_link['val'] . "/agent/public/admin/download/index?agent=" . $link;

        
        $this->assign("user", $user);

        return $this->fetch();
    }
}