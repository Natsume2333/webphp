<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/20 0020
 * Time: 上午 10:38
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use QcloudApi;
use think\Db;

class AgentController extends AdminBaseController
{
    //代理管理
    public function index()
    {

        /**搜索条件**/

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('agent_login') and !$this->request->param('agent_id') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            session("agent", null);
        } else if (empty($p)) {
            $data['user_login'] = $this->request->param('agent_login');
            $data['agentid'] = $this->request->param('agent_id');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            session("agent", $data);
        }

        $user_login = session("agent.user_login");
        $agentid = session("agent.agentid");

        $start_times = session("agent.start_time");
        $end_times = session("agent.end_time");

        $start_time = $start_times ? strtotime($start_times . " 00:00:00") : '0';

        $end_time = $end_times ? strtotime($end_times . " 23:59:59") : strtotime(date("Y-m-d", strtotime("-1 day")) . " 23:59:59");

        $id = cmf_get_current_admin_id();
        $where = "superior_id=" . $id . " or id=" . $id;

        if ($user_login) {
            $where .= " and agent_login like %$user_login%";
        }

        if ($agentid) {
            $where .= ' and id=' . $agentid;
        }


        $user = Db::name('agent')->where($where)->order("id DESC")->paginate(10);

        // 获取分页显示
        $page = $user->render();
        $user = $user->toArray();
        $agenttime = " and addtime >= $start_time and addtime <= $end_time";
        foreach ($user['data'] as &$v) {
            $vid = $v['channel_agent_link'];
            $statistical = Db::name('agent_statistical')->where("(channel_agent='$vid') or (channel='$vid')" . $agenttime)->field("sum(earnings) as earnings,sum(agent_earnings) as agent_earnings")->find();
            $v['earnings'] = $statistical['earnings'];
            $v['agent_earnings'] = $statistical['agent_earnings'];
        }
        $this->assign("page", $page);
        $this->assign("data", session("agent"));
        $this->assign("users", $user['data']);
        $this->assign("id", $id);
        return $this->fetch();
    }

    //添加代理
    public function add()
    {

        $this->assign("id", cmf_get_current_admin_id());
        return $this->fetch();
    }

    //提交代理
    public function addPost()
    {

        if ($this->request->isPost()) {
            $login = $_POST['agent_login'];
            $user = DB::name('agent')->where("agent_login ='$login'")->find();
            if ($user) {
                $this->error("添加失败,账号已存在！");
            }
            $_POST['agent_pass'] = cmf_password($_POST['agent_pass']);

            $agent = $this->agent_level($_POST['superior_id']);

            $_POST['agent_level'] = $agent['level'];

            if ($_POST['agent_level'] > 3) {
                $this->error("添加失败,上级代理是3级，不能成为下级代理！");
            }
            $_POST['channel'] = $agent['channel'];
            $_POST['channel_agent_link'] = $agent['channel'] . "_" . $_POST['channel_agent'];
            $_POST['addtime'] = time();
            $result = DB::name('agent')->insertGetId($_POST);
            $data = array(
                'agent_id' => $result,
            );
            $datas = array(
                'uid' => $result,
                'channel' => $agent['channel'],
                'channel_agent' => $_POST['channel_agent_link'],
                'money' => '0',
                'registered' => '0',
                'data_time' => date('Y-m-d'),
                'commission' => $_POST['commission'],
                'addtime' => time(),
            );
            DB::name('agent_statistical')->insert($datas);

            DB::name('agent_information')->insertGetId($data);
            if ($result !== false) {
                $this->success("添加成功！", url("agent/index"));
            } else {
                $this->error("添加失败！");
            }
        }
    }

    public function agent_level($id)
    {
        $id = $id ? $id : '0';
        $result = DB::name('agent')->where("id=" . $id)->find();
        if ($result) {
            $result['level'] = $result['agent_level'] + 1;
        } else {
            $result['level'] = '1';
        }
        return $result;
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
            $_POST['channel_agent_link'] = $_POST['channel'] . "_" . $_POST['channel_agent'];
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

    //用户充值列表
    public function userindex()
    {
        /**搜索条件**/

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('channel') and !$this->request->param('userid') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            session("userindex", null);
        } else if (empty($p)) {
            $data['channel'] = $this->request->param('channel');
            $data['userid'] = $this->request->param('userid');
            $data['start_time'] = $this->request->param('start_time');
            $data['end_time'] = $this->request->param('end_time');
            session("userindex", $data);
        }


        $userid = session("userindex.userid");
        $channel = session("userindex.channel");
        $start_times = session("userindex.start_time");
        $end_times = session("userindex.end_time");


        $start_time = $start_times ? strtotime($start_times) : '0';

        $end_time = $end_times ? strtotime($end_times) : time();

        $id = cmf_get_current_admin_id();

        $where = "a.status=1 and a.addtime >= $start_time and a.addtime <= $end_time and k.id =$id";
        $where .= $userid ? " and u.id =$userid" : '';
        $where .= $channel ? " and u.link_id ='$channel'" : '';

        $list = Db::name('user_charge_log')->alias("a")
            ->where($where)
            ->join("user u", "u.id=a.uid")
            ->join("agent k", "k.channel_agent_link=u.link_id")
            ->field("u.id,u.user_nickname,a.money,u.link_id,a.addtime")
            ->paginate(10);

        $sum = Db::name('user_charge_log')->alias("a")
            ->where($where)
            ->join("user u", "u.id=a.uid")
            ->join("agent k", "k.channel_agent_link=u.link_id")
            ->sum("money");
        // 获取分页显示
        $page = $list->render();
        $user = $list->toArray();

        $this->assign("page", $page);
        $this->assign("sum", $sum);
        $this->assign("data", session("userindex"));
        $this->assign("users", $user['data']);
        return $this->fetch();
    }

    //用户注册列表
    public function userlist()
    {
        /**搜索条件**/

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('channel') and !$this->request->param('userid') and !$this->request->param('type')) {
            session("userlist", null);
        } else if (empty($p)) {
            $data['channel'] = $this->request->param('channel');
            $data['userid'] = $this->request->param('userid');
            $data['type'] = $this->request->param('type') ? $this->request->param('type') : '0';  //注册类型
            session("userlist", $data);
        }
        $type = session("userlist.type");
        $userid = session("userlist.userid");
        $channel = session("userlist.channel");

        $id = cmf_get_current_admin_id();

        $name = Db::name('agent')->where("id=" . $id)->find();

        if ($name['agent_level'] == '1') {

            $where = "a.channel='".$name['channel']."'";
            //判断是否是下级代理推广的用户注册 type=1是本代理 type=2下级代理
            $where .= $type ? $type == '1' ? " and a.channel_agent_link='".$name['channel_agent_link']."'" : " and a.channel_agent_link  !='".$name['channel_agent_link']."'" : '';

        } elseif ($name['agent_level'] == '2') {
            $where = "a.channel='".$name['channel']."' and a.channel_agent_link='".$name['channel_agent_link']."'";
            //判断是否是下级代理推广的用户注册 type=1是本代理 type=2下级代理
        //     $where .= $type ? $type == '1' ? " and a.channel_agent_link='".$name['channel_agent_link']."'" : " and a.channel_agent_link  !='".$name['channel_agent_link']."'" : '';
        } else {
            $where = "a.channel='".$name['channel']."' and a.channel_agent_link='".$name['channel_agent_link']."'";
        }

        if ($userid) {
            $where .= " and u.id=$userid";
        }
        if ($channel) {
            $where .= " and a.channel_agent_link='$channel'";
        }
        //渠道号
        $list = Db::name('agent')->alias("a")
            ->join("user u", "u.link_id=a.channel_agent_link")
            ->where($where)
            ->field("u.*")
            ->order("u.create_time desc")
            ->paginate(10);

        // 获取分页显示
        $page = $list->render();
        $user = $list->toArray();

        $this->assign("page", $page);
        $this->assign("data", session("userlist"));
        $this->assign("users", $user['data']);
        return $this->fetch();
    }

    /*
     * 定时请求代理结算
     * $time 当前时间时间戳
     * 获取前一天的结算并记录
     *
     * */
    public function agent_settlement()
    {
        $time = time();
        $before = $time - 3600 * 24;
        $data = array('state' => 0, 'msg' => '结算失败');
        $list = Db::name('user_charge_log')->alias("a")
            ->where("u.link_id !=0 and a.status=1 and a.addtime >=$before and a.addtime <=$time")
            ->join("user u", "u.id=a.uid")
            ->field("a.uid,sum(a.money) as sum,u.link_id")
            ->group("uid")
            ->select();
        $list = $list->toArray();
        if ($list) {
            foreach ($list as $v) {
                $link = Db::name('agent_link')->where("channel=" . $v['link_id'])->find();

                $type = array(
                    'uid' => $v['uid'],
                    'money' => $v['sum'],
                    'addtime' => time(),
                    'channel' => $v['link_id'],
                    'beforetime' => $before,
                    'agent_id1' => $link['agent_id1'],
                    'agent_id2' => $link['agent_id2'],
                    'agent_id3' => $link['agent_id3'],
                    'divide_into1' => $link['divide_into1'],
                    'divide_into2' => $link['divide_into2'],
                    'divide_into3' => $link['divide_into3'],
                );
                $name = Db::name('agent_settlement')->insertGetId($type);
                if (!$name) {
                    return json_encode($data);
                    exit;
                }
            }
        }

        $data['state'] = '1';
        $data['msg'] = '结算成功';
        return json_encode($data);
        exit;

    }

    /*
            *代理渠道结算子渠道金额
            *
    */
    public function add_settlement()
    {
        $id = $this->request->param('id');
        $agent_earnings = $this->request->param('earnings');
        $users = Db::name('agent')->where("id='$id'")->find();
        $start_time = session("agent.start_time");
        $end_time = session("agent.end_time");

        if ($start_time == $end_time) {
            if ($end_time) {
                $wheres = " and data_time ='" . $end_time . "'";
            } else {
                $wheres = "";
            }

        } else {
            $wheres = " and data_time >='" . $start_time . "' and data_time <='" . $end_time . "'";
        }
        $where = "channel_agent='" . $users['channel_agent_link'] . "'" . $wheres;

        $agent_money = Db::name('agent_statistical')->field("sum(earnings) as earnings")->where($where)->find();

        if ($agent_money['earnings'] == $agent_earnings && $agent_earnings > 0) {
            $data = array(
                'money' => $agent_earnings,
                'agent_id' => $id,
                'level' => $users['superior_id'],
                'addtime' => time(),
                'status' => '1',
                'channel' => $users['channel_agent_link'],
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

}