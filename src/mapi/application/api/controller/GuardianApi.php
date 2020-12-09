<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/30 0030
 * Time: 上午 11:32
 */

namespace app\api\controller;

use app\api\controller\Base;

class GuardianApi extends Base
{
/*app*/
    //获取守护列表前三
    public function get_guardian_list_order()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));

        $user_info = check_login_token($uid, $token);

        $where = "i.endtime >=" . NOW_TIME . " and i.hostid=" . $to_user_id;
        //获取守护主播的列表
        $list = db('guardian_user')->alias('i')
            ->join('user u', 'u.id = i.uid')
            ->join('user_gift_log a', "i.uid = a.user_id and i.hostid=a.to_user_id and a.create_time >= i.starttime and a.create_time <= i.endtime", 'LEFT')
            ->field('i.*,u.user_nickname,u.avatar,sum(a.gift_coin * a.gift_count) as gift_count')
            ->where($where)
            ->group("i.uid")
            ->order('gift_count desc')
            ->limit(3)
            ->select();
        $result['data']=$list;
        return_json_encode($result);

    }

    //获取我守护的主播列表
    public function app_guardian() {

        $result = array('code' => 1, 'msg' => '','data'=>[]);
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page')) ? intval(input('param.page')) : 1;
        $p=($page -1)*20;

        $user_info = check_login_token($uid, $token);

        $where = "i.endtime >=" . NOW_TIME . " and i.uid=" . $uid;
        //获取守护主播的列表
        $list = db('guardian_user')->alias('i')
            ->join('user u', 'u.id = i.hostid', 'LEFT')
            ->join('user_gift_log a', "i.uid = a.user_id and i.hostid=a.to_user_id and a.create_time >= i.starttime and a.create_time <= i.endtime", 'LEFT')
            ->field("i.hostid,u.user_nickname,u.avatar,sum(a.gift_coin * a.gift_count) as gift_count,i.endtime")
            ->where($where)
            ->group("i.hostid")
            ->order("i.addtime desc")
            ->limit($p,20)
            ->select();

        $result['data']=$list;

        return_json_encode($result);

    }
  //获取守护一个主播的列表
    public function app_index()
    {
        $result = array('code' => 1, 'msg' => '','data'=>[]);
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $hostid = trim(input('param.hostid'));;   //主播id

        $user_info = check_login_token($uid, $token);

        $where = "i.endtime >=" . NOW_TIME . " and i.hostid=" . $hostid;
        //获取守护主播的列表
        $list = db('guardian_user')->alias('i')
            ->join('user u', 'u.id = i.uid')
            ->join('user_gift_log a', "i.uid = a.user_id and i.hostid=a.to_user_id and a.create_time >= i.starttime and a.create_time <= i.endtime", 'LEFT')
            ->field('i.uid,u.user_nickname,u.avatar,sum(a.gift_coin * a.gift_count) as gift_count,u.level')
            ->where($where)
            ->group("i.uid")
            ->order('gift_count desc')
            ->limit(20)
            ->select();
        foreach ($list as &$v){
            $v['gift_count']=intval($v['gift_count']) >0 ? $v['gift_count'] :0;
        }
        //获取本用户是否守护主播
        $list_user = db('guardian_user')->alias('i')
            ->join('user u', 'u.id = i.uid', 'LEFT')
            ->join('user_gift_log a', "i.uid = a.user_id and i.hostid=a.to_user_id and a.create_time >= i.starttime and a.create_time <= i.endtime", 'LEFT')
            ->field("i.uid,sum(a.gift_coin * a.gift_count) as gift_count")
            ->where($where . " and i.uid=" . $uid)
            ->group("i.uid")
            ->find();
        $list_user['gift_count']=intval($list_user['gift_count']) >0 ? $list_user['gift_count'] :0;
        $list_user['is_open']=intval($list_user['gift_count']) >0 ? 1 :0;
        $data['user']=$list_user;
        $data['list']=$list;
        $result['data']=$data;

        return_json_encode($result);
    }

    //显示购买守护列表
    public function app_buy(){
        $result = array('code' => 1, 'msg' => '','data'=>[]);

        $hostid = intval(input('param.hostid'));  //主播id
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $where = "i.endtime >=" . NOW_TIME . " and i.hostid=" . $hostid . "  and i.uid=" . $uid;

        $list = db('guardian')->where('status=1')->order("sort desc")->select();

        $list_user = db('guardian_user')->alias('i')
            ->join('user u', 'u.id = i.uid', 'LEFT')
            ->field("i.*")->where($where)->group("i.uid")->find();

        $end_time = '';
        if ($list_user) {
            $end_time =date('Y-m-d H:i',$list_user['endtime']);
        }
        $user = db('user')->where('id='.$hostid)->field("user_nickname,coin")->find();
      
        $data['user_nickname']=$user['user_nickname'];
        $data['coin'] = $user_info['coin'];
        $data['time']=$end_time;
        $data['list']=$list;
        $result['data']=$data;

        return_json_encode($result);
    }

    //购买守护
    public function app_buy_add()
    {
        $result = array('code' => 0, 'msg' => '参数错误','data'=>[]);
      
        $id = intval(input('param.id'));    //守护id
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $hostid = intval(input('param.hostid'));  //主播id

        $user_info = check_login_token($uid, $token);

        $guardian = db('guardian')->where('id=' . $id . " and status=1")->find();
        if (!$guardian) {
           return_json_encode($result);
        }
        $host = db('user')->where('id=' . $hostid . " and user_status !=0")->field("id")->find();
        if (!$host) {
          return_json_encode($result);
        }
        $user = db('user')->where('id=' . $uid . " and user_status !=0")->field("coin")->find();
        if (!$user) {
           return_json_encode($result);
        }

        if ($user['coin'] < $guardian['coin']) {
            $result['msg'] = "余额不足，请充值!";
           return_json_encode($result);
        }

        //score 积分
        $coin = db('user')->where('id=' . $uid)->Dec("coin", $guardian['coin'])->inc('score', $guardian['coin'])->update();

        if ($coin) {         //扣除用户金额

            $day = $guardian['day'] * 24 * 60 * 60;
            $user_log = array(
                'uid' => $uid,
                'hostid' => $hostid,
                'guardian_id' => $id,
                'coin' => $guardian['coin'],
                'day' => $guardian['day'],
                'addtime' => time(),
            );
            $guardian_user_log = db('guardian_user_log')->insertGetId($user_log);
            $buy_record = array(
                'uid' => $uid,
                'hostid' => $hostid,
                'starttime' => NOW_TIME,
                'endtime' => NOW_TIME + $day,
                'guardian_id' => $id,
                'guardian_user_log_id' => $guardian_user_log,
                'addtime' => NOW_TIME,
            );
            $guardian_user = db('guardian_user')->where('uid=' . $uid . " and hostid=" . $hostid)->find();
            if ($guardian_user) {
                if ($guardian_user['endtime'] < NOW_TIME) {
                    $result_status = db('guardian_user')->where('id=' . $guardian_user['id'])->update($buy_record);
                } else {
                    $buy_record['endtime'] = $guardian_user['endtime'] + $day;
                    $result_status = db('guardian_user')->where('id=' . $guardian_user['id'])->update($buy_record);
                }
            } else {
                $result_status = db('guardian_user')->insert($buy_record);
            }
            if ($result_status) {
                $income_total = host_income_commission(6, $guardian['coin'], $hostid);
            
                db('user')->where(['id' => $hostid])->inc('income', $income_total)->inc('income_total', $income_total)->update();
                //增加总消费记录
                add_charging_log($uid, $hostid, 6, $guardian['coin'], $result_status, $income_total);

                $result['code'] = 1;
                $result['msg'] = "购买成功";
            } else {
                $result['msg'] = "购买失败";
            }
        }

        return_json_encode($result);
    }
 //查看守护特权
    public function app_privilege()
    {
        $result = array('code' => 1, 'msg' => '','data'=>[]);
        $portal = db("portal_post")
            ->where("post_title='守护特权' and post_type=1 and post_status=1")
            ->field("post_title,post_content,published_time")
            ->find();
        $portal['post_content'] = htmlspecialchars_decode($portal['post_content']);
        $result['data']=$portal;
        return_json_encode($result);
    }






















    /*h5*/
    public function guardian()
    {
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $data['uid'] = $uid;
        $data['token'] = $token;

        $where = "i.endtime >=" . NOW_TIME . " and i.uid=" . $uid;
        //获取守护主播的列表
        $list = db('guardian_user')->alias('i')
            ->join('user u', 'u.id = i.hostid', 'LEFT')
            ->join('user_gift_log a', "i.uid = a.user_id and i.hostid=a.to_user_id and a.create_time >= i.starttime and a.create_time <= i.endtime", 'LEFT')
            ->field("i.*,u.user_nickname,u.avatar,sum(a.gift_coin * a.gift_count) as gift_count")
            ->where($where)
            ->group("i.hostid")
            ->order("i.addtime desc")
            ->limit(20)
            ->select();

        $this->assign('user_info', $user_info);
        $this->assign('list', $list);
        $this->assign('user', $data);
        return $this->fetch();
    }

    //获取守护一个主播的列表
    public function index()
    {
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $hostid = trim(input('param.hostid'));;   //主播id

        $user_info = check_login_token($uid, $token);

        $data['uid'] = $uid;
        $data['token'] = $token;
        $data['hostid'] = $hostid;


        $where = "i.endtime >=" . NOW_TIME . " and i.hostid=" . $hostid;
        //获取守护主播的列表
        $list = db('guardian_user')->alias('i')
            ->join('user u', 'u.id = i.uid')
            ->join('user_gift_log a', "i.uid = a.user_id and i.hostid=a.to_user_id and a.create_time >= i.starttime and a.create_time <= i.endtime", 'LEFT')
            ->field('i.*,u.user_nickname,u.avatar,sum(a.gift_coin * a.gift_count) as gift_count')
            ->where($where)
            ->group("i.uid")
            ->order('gift_count desc')
            ->limit(20)
            ->select();
        //获取本用户是否守护主播
        $list_user = db('guardian_user')->alias('i')
            ->join('user u', 'u.id = i.uid', 'LEFT')
            ->join('user_gift_log a', "i.uid = a.user_id and i.hostid=a.to_user_id and a.create_time >= i.starttime and a.create_time <= i.endtime", 'LEFT')
            ->field("i.*,sum(a.gift_coin * a.gift_count) as gift_count")
            ->where($where . " and i.uid=" . $uid)
            ->group("i.uid")
            ->find();


        $this->assign('list_user', $list_user);
        $this->assign('user_info', $user_info);
        $this->assign('list', $list);
        $this->assign('user', $data);
        return $this->fetch();
    }

    //购买守护列表
    public function buy()
    {
        $hostid = intval(input('param.hostid'));  //主播id
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $list_host = db('user')->where('id=' . $hostid)->field("user_nickname,id")->find();

        $where = "i.endtime >=" . NOW_TIME . " and i.hostid=" . $hostid . "  and i.uid=" . $uid;

        $list = db('guardian')->where('status=1')->order("sort desc")->select();

        $list_user = db('guardian_user')->alias('i')
            ->join('user u', 'u.id = i.uid', 'LEFT')
            ->field("i.*")->where($where)->group("i.uid")->find();

        $end_time = NOW_TIME;
        if ($list_user) {
            $end_time = $list_user['endtime'];
        }
        foreach ($list as &$v) {
            $day = $v['day'];
            $date = $end_time + $day * 24 * 60 * 60;
            $v['date'] = date("Y年m月d日", $date);
        }

        $data['uid'] = $uid;
        $data['token'] = $token;
        $data['hostid'] = $hostid;

        $this->assign('list_host', $list_host);
        $this->assign('list_user', $list_user);
        $this->assign('user_info', $user_info);
        $this->assign('list', $list);
        $this->assign('user', $data);
        return $this->fetch();
    }

    //购买守护
    public function buy_add()
    {
        $data = array('status' => 0, 'msg' => '参数错误');
        $id = intval(input('param.id'));    //守护id

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $hostid = intval(input('param.hostid'));  //主播id

        if ($uid == 0 || empty($token)) {
            echo json_encode($data);
            exit;
        }
        $user_info = db('user')->where('id', '=', $uid)->where('token', '=', $token)->find();
        if (!$user_info) {
            $data['msg'] = '登录信息错误！';
            echo json_encode($data);
            exit;
        }

        $guardian = db('guardian')->where('id=' . $id . " and status=1")->find();
        if (!$guardian) {
            echo json_encode($data);
            exit;
        }
        $host = db('user')->where('id=' . $hostid . " and user_status !=0")->field("id")->find();
        if (!$host) {
            echo json_encode($data);
            exit;
        }
        $user = db('user')->where('id=' . $uid . " and user_status !=0")->field("coin")->find();
        if (!$user) {
            echo json_encode($data);
            exit;
        }
        if ($user['coin'] < $guardian['coin']) {
            $data['msg'] = "余额不足，请充值!";
            echo json_encode($data);
            exit;
        }
        //score 积分
        $coin = db('user')->where('id=' . $uid)->Dec("coin", $guardian['coin'])->inc('score', $guardian['coin'])->update();

        if ($coin) {         //扣除用户金额

            $day = $guardian['day'] * 24 * 60 * 60;

            $user_log = array(
                'uid' => $uid,
                'hostid' => $hostid,
                'guardian_id' => $id,
                'coin' => $guardian['coin'],
                'day' => $guardian['day'],
                'addtime' => time(),
            );

            $guardian_user_log = db('guardian_user_log')->insertGetId($user_log);
            $buy_record = array(
                'uid' => $uid,
                'hostid' => $hostid,
                'starttime' => NOW_TIME,
                'endtime' => NOW_TIME + $day,
                'guardian_id' => $id,
                'guardian_user_log_id' => $guardian_user_log,
                'addtime' => NOW_TIME,
            );
            $guardian_user = db('guardian_user')->where('uid=' . $uid . " and hostid=" . $hostid)->find();
            if ($guardian_user) {
                if ($guardian_user['endtime'] < NOW_TIME) {
                    $result = db('guardian_user')->where('id=' . $guardian_user['id'])->update($buy_record);
                } else {
                    $buy_record['endtime'] = $guardian_user['endtime'] + $day;
                    $result = db('guardian_user')->where('id=' . $guardian_user['id'])->update($buy_record);
                }
            } else {
                $result = db('guardian_user')->insert($buy_record);
            }
            if ($result) {
                $income_total = host_income_commission(6, $guardian['coin'], $hostid);
                db('user')->where(['id' => $hostid])->inc('income', $income_total)->inc('income_total', $income_total)->update();
                //增加总消费记录
                add_charging_log($uid, $hostid, 6, $guardian['coin'], $result, $income_total);

                $data['status'] = 1;
                $data['msg'] = "购买成功";
            } else {
                $data['msg'] = "购买失败";
            }
        }

        echo json_encode($data);
        exit;
    }

    //查看守护特权
    public function privilege()
    {
        $portal = db("portal_post")
            ->where("post_title='守护特权' and post_type=1 and post_status=1")
            ->find();
        $portal['post_content'] = htmlspecialchars_decode($portal['post_content']);
        $this->assign('portal', $portal);
        return $this->fetch();
    }


}