<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/20 0020
 * Time: 上午 10:38
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class AgentController extends AdminBaseController
{
    //代理管理
    public function index()
    {
        $where = '';
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('agent_login') and !$this->request->param('agent_id') and !$this->request->param('agent_level')) {
            session("admin_agent", null);
            $data['agent_level'] = '1';
            session("admin_agent", $data);

        } else if (empty($p)) {
            $data['agent_login'] = $this->request->param('agent_login');
            $data['agent_id'] = $this->request->param('agent_id');
            $data['agent_level'] = $this->request->param('agent_level');
            $data['status'] = $this->request->param('status');

            session("admin_agent", $data);
        }

        $user_login = session("admin_agent.agent_login");
        $agentid = session("admin_agent.agent_id");
        $agent_level = session("admin_agent.agent_level");

        $where = [];
        if ($user_login) {
            $where['agent_login'] = ['like', "%$user_login%"];
        }
        if ($agent_level) {
            $where['agent_level'] = $agent_level;
        }

        $where['superior_id'] = '0';

        if ($agentid) {
            $where['id'] = $agentid;
        }

        $users = Db::name('agent')
            ->where($where)
            ->order("id DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $users->render();
        $name = $users->toArray();
        $config = load_cache('config');
        $registered_one = 0;
        $money_one = 0;
        foreach ($name['data'] as &$v) {

            $vid = $v['superior_id'];

            $users = Db::name('agent')->where("id=$vid")->find(); //上级代理
            $v['buckle_quantity'] = $v['buckle_quantity'] ? $v['buckle_quantity'] : $config['agent_earnings'];
            if ($users) {
                $v['agent_user'] = $users['agent_login'];
                $v['agent_id'] = $users['id'];
                $where = "channel='" . $users['channel'] . "' and channel_agent='" . $v['channel_agent_link'] . "'";
            } else {
                $this->get_conversion($v);
                $v['agent_user'] = '超级管理员';
                $v['agent_id'] = '1';
                $where = "channel='" . $v['channel'] . "'";
            }
            //获取注册总数
            $sid = $v['id'];
            $registered_one = 0;

            $time = date("Y-m-d", time());
  
            $agent_money = Db::name('agent_statistical')->field("sum(money) as money,sum(registered) as registered,sum(agent_earnings) as agent_earnings,sum(earnings) as earnings")->where($where)->find();
 
            $agent_money_one = Db::name('agent_statistical')->field("sum(money) as money,sum(registered) as registered")->where($where . " and data_time='" . $time."'")->find();

            $v['registered'] = $agent_money['registered'];
            $v['money'] = $agent_money['money'];
            $v['agent_earnings'] = $agent_money['agent_earnings'];
            $v['earnings'] = $agent_money['earnings'];

            $v['registered_one'] = $agent_money_one['registered'];
            $v['money_one'] = $agent_money_one['money'];
            $money_one += $agent_money_one['money'];
            $registered_one += $agent_money_one['registered'];
        }

        $baseUrl="http://".$_SERVER['HTTP_HOST'];
        $this->assign("baseUrl", $baseUrl);

        $this->assign("page", $page);
        $this->assign("money_one", $money_one);
        $this->assign("registered_one", $registered_one);
        $this->assign("users", $name['data']);
        $this->assign("data", session("admin_agent"));
        return $this->fetch();
    }

    //代理管理详情
    public function details()
    {
        $where = '';
        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('agent_login') and !$this->request->param('superior_id') and !$this->request->param('agent_id') and !$this->request->param('agent_level') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            session("admin_agent", null);
            $data['start_time'] = date('Y-m-d', time());
            $data['end_time'] = date('Y-m-d', time() + 3600 * 24);
            $data['agent_level'] = '1';
            session("admin_agent", $data);

        } else if (empty($p)) {

            $data['agent_login'] = $this->request->param('agent_login');
            $data['superior_id'] = $this->request->param('superior_id');
            $data['agent_id'] = $this->request->param('agent_id');

            $data['agent_level'] = $this->request->param('agent_level');
            $data['status'] = $this->request->param('status');
            if ($this->request->param('start_time')) {
                $data['start_time'] = $this->request->param('start_time');
            } else {
                $data['start_time'] = date('Y-m-d', time());
            }
            if ($this->request->param('end_time')) {
                $data['end_time'] = $this->request->param('end_time');
            } else {
                $data['end_time'] = date('Y-m-d', time() + 3600 * 24);
            }
            session("admin_agent", $data);
        }

        $user_login = session("admin_agent.agent_login");
        $superior_id = session("admin_agent.superior_id");
        $agentid = session("admin_agent.agent_id");
        $start_time = session("admin_agent.start_time");
        $end_time = session("admin_agent.end_time");
        $agent_level = session("admin_agent.agent_level");

        if ($user_login) {
            $where['agent_login'] = ['like', "%$user_login%"];
        }
        if ($agent_level) {
            $where['agent_level'] = $agent_level;
        }
        if ($superior_id) {
           $channel = Db::name('agent')->field("channel")->where("id=".$superior_id)->find();
            $where['channel'] = $channel['channel'];
        } else {
            $where['superior_id'] = '0';
        }
        if ($agentid) {
          
            $where['id'] = $agentid;
        }

        if ($start_time == $end_time) {
            $wheres = " and data_time ='" . $end_time . "'";
        } else {
            $wheres = " and data_time >='" . $start_time . "' and data_time <'" . $end_time . "'";
        }
        
        $users = Db::name('agent')
            ->where($where)
            ->order("id DESC")
            ->paginate(10, false, ['query' => request()->param()]);
    //var_dump($users);exit;
        // 获取分页显示
        $page = $users->render();
        $name = $users->toArray();
        
        foreach ($name['data'] as &$v) {

            $vid = $v['superior_id'];
            $users = Db::name('agent')->where("id=$vid")->find(); //上级代理

            if ($users) {
                $v['agent_user'] = $users['agent_login'];
                $v['agent_id'] = $users['id'];
                $where = "channel='" . $users['channel'] . "' and channel_agent='" . $v['channel_agent_link'] . "'" . $wheres;
            } else {
            
                $this->get_conversion($v);
                $v['agent_user'] = '无';
                $v['agent_id'] = '';
                $where = "channel='" . $v['channel'] . "'" . $wheres;
            }
         
            //获取注册总数
            $sid = $v['id'];
            if($superior_id){
              $where.=" and uid=".$sid;
            }
            $agent_money = Db::name('agent_statistical')->field("sum(money) as money,sum(registered) as registered,sum(agent_earnings) as agent_earnings,sum(earnings) as earnings")->where($where)->find();
          
            $v['registered'] = $agent_money['registered'];
            $v['money'] = $agent_money['money'];
            $v['agent_earnings'] = $agent_money['agent_earnings'];
            $v['earnings'] = $agent_money['earnings'];
        }

        $this->assign("page", $page);
        $this->assign("users", $name['data']);
        $this->assign("data", session("admin_agent"));
        return $this->fetch();
    }

    /*
     *  渠道代理收益列表
     * */
    public function earnings()
    {
        $where = '';
        /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('channel_link') || $this->request->param('channel') || $this->request->param('start_time') || $this->request->param('end_time') || $this->request->param('uid') || $this->request->param('type')) {

            $data['start_time'] = $this->request->param('start_time') ? $this->request->param('start_time') : date('Y-m-d', time());
            $data['end_time'] = $this->request->param('end_time') ? $this->request->param('end_time') : date('Y-m-d', time() + 3600 * 24);
            $data['channel_link'] = $this->request->param('channel_link') ? $this->request->param('channel_link') : '';
            $data['channel'] = $this->request->param('channel') ? $this->request->param('channel') : '';
            $data['uid'] = $this->request->param('uid') ? $this->request->param('uid') : '';
            $data['type'] = $this->request->param('type') ? $this->request->param('type') : '0';
            session("admin_agent", $data);

        } else if (empty($p)) {


            $data['start_time'] = date('Y-m-d', time());

            $data['end_time'] = date('Y-m-d', time() + 3600 * 24);
            $data['type'] = '0';
            session("admin_agent", $data);
        }


        $agentid = session("admin_agent.uid");
        $start_time = strtotime(session("admin_agent.start_time") . " 00:00:00");
        $end_time = strtotime(session("admin_agent.end_time") . " 23:59:59");
        $channel_link = session("admin_agent.channel_link");
        $channel = session("admin_agent.channel");
        $type = session("admin_agent.type");

        if ($agentid) {
            $where['a.uid'] = $agentid;
        }
        if ($channel_link) {
            $where['a.channel_link'] = $channel_link;
        }
        if ($channel) {
            $where['g.channel'] = $channel;
        }
        if ($type) {
            $where['a.type'] = $type;
        }

        $where['a.addtime'] = array('between', array($start_time, $end_time));

        $users = Db::name('agent_order_log')->alias("a")
            ->where($where)
            ->field("u.user_nickname,e.id as agentid,e.agent_login as agentname,a.*,g.channel")
            ->join("agent g", "g.channel_agent_link=a.channel_link")
            ->join("agent e", "e.id=g.superior_id")
            ->join("user u", "u.id=a.uid")
            ->order("a.addtime DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $users->render();
        $name = $users->toArray();
        $money = Db::name('agent_order_log')->alias("a")
            ->where($where)
            ->field("sum(a.money) as money,sum(agent_money) as agent_money")
            ->join("agent g", "g.channel_agent_link=a.channel_link")
            ->join("agent e", "e.id=g.superior_id")
            ->join("user u", "u.id=a.uid")
            ->find();
        //  var_dump($users);exit;
        $this->assign("page", $page);
        $this->assign("money", $money);
        $this->assign("users", $name['data']);
        $this->assign("data", session("admin_agent"));
        return $this->fetch();
    }

    //添加代理
    public function add()
    {
        $roles = Db::name('role')->where(['status' => 1])->order("id DESC")->select();
        $this->assign("roles", $roles);
        return $this->fetch();
    }

    /*
     *   渠道统计封装
    */
    public function get_conversion($agent)
    {

        $where = "channel='" . $agent['channel'] . "'";

        if ($agent['agent_level'] != '1') {

            $where .= " and channel_agent='" . $agent['channel_agent'] . "'";
            $agent_list[] = $agent;
            $superior_id = Db::name('agent')->where("id=" . $agent['superior_id'])->find();
            $agent_earnings = $superior_id['commission'];
        } else {
            $superior_id = Db::name('agent')->where("superior_id=" . $agent['id'])->select();
            $agent_list = $superior_id;
            $agent_list[] = $agent;
            $agent_earnings = $agent['commission'];
        }

        $endtimes = Db::name('agent_statistical')->where($where)->order("id desc")->find();

        $c = date('Y-m-d');

        if ($endtimes['data_time'] == $c || $endtimes == null) {
            $time[] = $c;
        } else {
            //获取的不是今天时间
            $begin = $endtimes['data_time'];
            $end = $c;
            $begintime = strtotime($begin);
            $endtime = strtotime($end);
            for ($start = $begintime; $start <= $endtime; $start += 24 * 3600) {
                $time[] = date("Y-m-d", $start);
            }
        }

        foreach ($time as $v) {
            //循环向数据中插入值
            foreach ($agent_list as $cc) {

                $this->get_agent_list($v, $c, $cc['channel'], $cc['channel_agent_link'], $cc['id'], $cc['commission'], $agent_earnings);
            }
        }

    }

    /*
    *  获取代理渠道的用户统计入库
    */
    public function get_agent_list($datas, $c, $channel, $channel_agent, $uid, $commission, $agent_earnings)
    {
        $starttime = strtotime($datas . " 00:00:00");
        $endtime = strtotime($datas . " 23:59:59");
        $channel_link = $channel_agent;
        $user = Db::name('user')->where("link_id='$channel_link'")->select();

        $user_count = Db::name('user')->where("create_time >='" . $starttime . "' and create_time <= '" . $endtime . "' and link_id='$channel_link'")->count();

        $sum = 0;
        $agent_earnings_money = 0;
        if ($user) {
            foreach ($user as $v) {
                $vid = $v['id'];
                $settlement = Db::name('agent_order_log')->where("addtime >='$starttime' and addtime <= '$endtime' and uid='$vid' and type=1")->field("sum(money) as money,sum(agent_money) as agent_money")->find();
                $sum += $settlement['money'];
                $agent_earnings_money += $settlement['agent_money'];
            }

        }

        $earnings = round($commission * $agent_earnings_money / 100, 2);

        $data = array(
            'uid' => $uid,
            'channel' => $channel,
            'channel_agent' => $channel_agent,
            'money' => $sum,
            'registered' => $user_count ? $user_count : '0',
            'data_time' => $datas,
            'commission' => $commission,
            'earnings' => $earnings,
            'agent_commission' => $agent_earnings,
            'agent_earnings' => $agent_earnings_money,

            'addtime' => time(),
        );

        //判断是否统计过（主要是当天的数据）
        $endtimes = Db::name('agent_statistical')->where("uid='$uid' and channel='$channel' and channel_agent='$channel_agent' and data_time='$datas'")->find();
        if ($endtimes) {
            $settlement = Db::name('agent_statistical')->where("uid='$uid' and channel='$channel' and channel_agent='$channel_agent' and data_time='$datas'")->update($data);
        } else {
            $settlement = Db::name('agent_statistical')->insert($data);
        }

    }

    //提交代理
    public function addpost()
    {
        if ($this->request->isPost()) {
            $login = $_POST['agent_login'];
            $user = DB::name('agent')->where("agent_login ='$login'")->find();
            if ($user) {
                $this->error("添加失败,账号已存在！");
            }
            $_POST['agent_pass'] = cmf_password($_POST['agent_pass']);

            $_POST['agent_level'] = '1';

            $_POST['addtime'] = time();
            $_POST['channel_agent_link'] = $_POST['channel']."_".$_POST['channel'];
           $_POST['channel_agent'] = $_POST['channel'];

            $result = DB::name('agent')->insertGetId($_POST);

            $datas = array(
                'agent_id' => $result,
            );

            $data = array(
                'uid' => $result,
                'channel' => $_POST['channel'],
                'channel_agent' =>$_POST['channel'],
                'money' => '0',
                'registered' => '0',
                'data_time' => date('Y-m-d'),
                'commission' => $_POST['commission'],
                'agent_earnings' => 0,
                'addtime' => time(),
            );
    
            DB::name('agent_statistical')->insert($data);
            DB::name('agent_information')->insertGetId($datas);
            if ($result !== false) {
                $this->success("添加成功！", url("agent/index"));
            } else {
                $this->error("添加失败！");
            }
        }
    }

    public function agent_level($id)
    {
        $result = DB::name('agent')->where("id=" . $id)->find();
        if ($result) {
            $level = $result['agent_level'] + 1;

        } else {
            $level = '1';
        }
        return $level;
    }

    /**
     * 账号编辑
     */
    public function edit()
    {
        $id = $this->request->param('id', 0, 'intval');

        $user = DB::name('agent')->where(["id" => $id])->find();
        $this->assign($user);
        return $this->fetch();
    }

    /**
     * 账号编辑提交
     */
    public function editPost()
    {
        if ($this->request->isPost()) {

            if (empty($_POST['agent_pass'])) {
                unset($_POST['agent_pass']);
            } else {
                $_POST['agent_pass'] = cmf_password($_POST['agent_pass']);
            }
            $login = $_POST['agent_login'];
            $user = DB::name('agent')->where("agent_login ='$login' and id !=" . $_POST['id'])->find();
            if ($user) {
                $this->error("修改失败,账号已存在！");
            }

            $result = DB::name('agent')->update($_POST);
            if ($result !== false) {
                $this->success("保存成功！");
            } else {
                $this->error("保存失败！");
            }
        }
    }

    /**
     * 删除账号
     */
    public function delete()
    {
        $id = $this->request->param('id', 0, 'intval');

        if (Db::name('agent')->delete($id) !== false) {
            $this->success("删除成功！");
        } else {
            $this->error("删除失败！");
        }
    }

    /**
     * 停用账号
     */
    public function ban()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('agent')->where(["id" => $id])->setField('status', '0');
            if ($result !== false) {
                $this->success("封号成功！", url("agent/index"));
            } else {
                $this->error('封号失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 启用账号
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if (!empty($id)) {
            $result = Db::name('agent')->where(["id" => $id])->setField('status', '1');
            if ($result !== false) {
                $this->success("解封成功！", url("agent/index"));
            } else {
                $this->error('解封失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /*
     * 代理提现记录
     *
    */
    public function withdrawal()
    {
        $where = '';
        /**搜索条件**/

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('start_time') and !$this->request->param('end_time') and !$this->request->param('agent_id') and !$this->request->param('channel')) {
            session("admin_withdrawal", null);
        } else if (empty($p)) {

            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            $data['agent_id'] = $this->request->param('agent_id');
            $data['channel'] = $this->request->param('channel');
            session("admin_withdrawal", $data);
        }

        $start_times = session("admin_withdrawal.start_time");
        $end_times = session("admin_withdrawal.end_time");
        $agentid = session("admin_withdrawal.agent_id");

        $channel = session("admin_withdrawal.channel");

        $start_time = $start_times ? strtotime($start_times) : '0';

        $end_time = $end_times ? strtotime($end_times) : time();

        $where['a.addtime'] = array('between', array($start_time, $end_time));
        $where['a.level'] = '0';
        if ($channel) {
            $where['u.channel'] = $channel;
        }

        if ($agentid) {
            $where['u.id'] = $agentid;
        }

        $withdrawal = Db::name('agent_withdrawal')->alias("a")
            ->join("agent u", "u.id=a.agent_id")
            ->where($where)
            ->field("u.agent_login,u.agent_level,a.*")
            ->order("a.addtime DESC")
            ->paginate(10, false, ['query' => request()->param()]);
        $sum = Db::name('agent_withdrawal')->alias("a")
            ->join("agent u", "u.id=a.agent_id")
            ->where($where)
            ->order("a.addtime DESC")
            ->sum("money");
        $page = $withdrawal->render();
        $name = $withdrawal->toArray();

        $this->assign("sum", $sum);
        $this->assign("page", $page);
        $this->assign("list", $name['data']);
        $this->assign("data", session("admin_withdrawal"));
        return $this->fetch();
    }

    //修改提现状态
    public function addwithdrawal()
    {
        $id = $this->request->param('id');
        $status = $this->request->param('status');
        $data = array(
            'status' => $status,
        );
        $result = Db::name('agent_withdrawal')->where("id=$id")->update($data);
        if ($result) {
            $this->success("操作成功！");
        } else {
            $this->success("操作失败！");
        }
    }

    //代理推广链接
    public function link()
    {
        $where = '';
        /**搜索条件**/

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('agent_id') and !$this->request->param('channel') and $this->request->param('status') == null) {
            session("admin_link", null);
            session("admin_link.status", 1);
        } else if (empty($p)) {

            $data['agent_id'] = $this->request->param('agent_id');
            $data['channel'] = $this->request->param('channel');
            $data['status'] = $this->request->param('status');

            session("admin_link", $data);

        }

        $agentid = session("admin_link.agent_id");
        $channel = session("admin_link.channel");
        $status = session("admin_link.status");

        if ($agentid) {
            $where['a.id'] = $agentid;
        }
        if ($channel) {
            $where['l.channel'] = $channel;
        }
        if ($status >= 0) {
            $where['a.status'] = $status;
        }
        $users = Db::name('agent_link')->alias("l")
            ->join("agent a", "a.id=l.agent_id1")
            ->field("l.*,a.agent_login,a.superior_id,a.status")
            ->where($where)
            ->order("id DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $users->render();
        $name = $users->toArray();

        foreach ($name['data'] as &$v) {

            $vid = $v['superior_id'];
            $users = Db::name('agent')->where("id=$vid")->find(); //上级代理
            if ($users) {
                $v['agent_user'] = $users['agent_login'];
                $v['agent_id'] = $users['id'];
            } else {
                $v['agent_user'] = '超级管理员';
                $v['agent_id'] = '1';
            }

        }

        $this->assign("page", $page);

        $this->assign("users", $name['data']);
        $this->assign("data", session("admin_link"));
        return $this->fetch();
    }


    //注册详情
    public function registered()
    {
        /**搜索条件**/

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('channel') and !$this->request->param('channel_agent_link') and !$this->request->param('starttime') and !$this->request->param('endtime') and !$this->request->param('user_id')) {
            session("admin_registered", null);
        } else if (empty($p)) {
            $data['channel'] = $this->request->param('channel');
            $data['channel_agent_link'] = $this->request->param('channel_agent_link');
            $data['starttime'] = $this->request->param('starttime');
            $data['endtime'] = $this->request->param('endtime');
            $data['user_id'] = $this->request->param('user_id');

            session("admin_registered", $data);
        }

        $channel_agent_link = session("admin_registered.channel_agent_link");
        $channel = session("admin_registered.channel");
        $user_id = session("admin_registered.user_id");
        $starttime = session("admin_registered.starttime") ? strtotime(session("admin_registered.starttime") . ":00") : '';
        $endtime = session("admin_registered.endtime") ? strtotime(session("admin_registered.endtime") . ":59") : time();
        $where = "u.link_id !='0'";
        if ($channel_agent_link) {
            $where = "u.link_id='$channel_agent_link'";
        }

        if ($user_id) {
            $where .= " and u.id='$user_id'";
        }

        //$agent = db('agent') -> where("a.channel='$channel'") -> find();
        
        //渠道号
        $list = Db::name('user')
            ->alias('u')
            ->where($where)
            ->field("u.*")
            ->order("u.create_time desc")
            ->paginate(20, false, ['query' => request()->param()]);
        
        // 获取分页显示
        $page = $list->render();
        $user = $list->toArray();

        $vwhere = "addtime <= '$endtime'";

        if ($starttime) {
            $vwhere .= " and addtime >='$starttime'";
        }
        foreach ($user['data'] as &$v) {
            $uid = $v['id'];
            $v['summoney'] = Db::name('agent_order_log')->where($vwhere . " and uid='$uid' and type=1")->sum("money");
            $v['channel'] = $channel;
            //  $v['summoney'] = Db::name('user_charge_log')->where($vwhere . " and uid='$uid' and status=1")->sum("money");
        }
        $this->assign("page", $page);
        $this->assign("data", session("admin_registered"));
        $this->assign("users", $user['data']);
        return $this->fetch();
    }

    /*
    * 结算代理渠道金额
    *
    */
    public function add_settlement()
    {
        $id = $this->request->param('id');
        $agent_earnings = $this->request->param('agent_earnings');
        $users = Db::name('agent')->where("id='$id'")->find();
        $start_time = session("admin_agent.start_time");
        $end_time = session("admin_agent.end_time");

        if ($start_time == $end_time) {
            $wheres = " and data_time ='" . $end_time . "'";
        } else {
            $wheres = " and data_time >='" . $start_time . "' and data_time <='" . $end_time . "'";
        }
        $where = "channel='" . $users['channel'] . "'" . $wheres;

        $agent_money = Db::name('agent_statistical')->field("sum(agent_earnings) as agent_earnings")->where($where)->find();
        if ($agent_money['agent_earnings'] == $agent_earnings && $agent_earnings > 0) {
            $data = array(
                'money' => $agent_earnings,
                'agent_id' => $id,
                'addtime' => time(),
                'status' => '1',
                'updatetime' => time(),
                'channel' => $users['channel'],
            );
            $user = Db::name("agent_withdrawal")->insert($data);
            if ($user) {
                $this->success("结算成功");
            } else {
                $this->error("结算失败");
            }
        } else {
            $this->error("金额不正确");
        }
    }

    /*
     *  代理详情
     * */
    public function information()
    {
        $id = $this->request->param('id');
        $user_name = Db::name('agent')->field("agent_login")->where("id=$id")->find();
        if ($user_name) {
            $user = Db::name('agent_information')->where("agent_id=$id")->find();
            $result = $user;

        }
        $result['agent_login'] = $user_name['agent_login'];
        //  var_dump($id);exit;
        $this->assign("users", $result);
        return $this->fetch();
    }
}