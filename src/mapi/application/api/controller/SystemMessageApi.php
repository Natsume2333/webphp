<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/23 0023
 * Time: 下午 14:53
 */

namespace app\api\controller;

use think\Db;
use cmf\controller\ApiController;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class SystemMessageApi extends Base
{

    //获取系统消息列表
    public function get_system_message()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['last_remove_message_time']);

        if ($user_info['is_auth'] == '1') {
            $is_auth = '-1';
        } else {
            $is_auth = '-2';
        }
        $where = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "') and messageid !=0";
        $message_log = db("user_message_log")->where($where)->order('addtime desc')->select();
        $list = array();
        foreach ($message_log as $k => $v) {

            $list[$k]['id'] = $v['id'];
            $id = $v['messageid'];
            if ($v['type'] == 1) {
                //后台管理员审核操作
                $message_list = db("user_message")->where("id=$id")->find();
                $list[$k]['title'] = $message_list['title'] . ':' . $v['messagetype'];
                $list[$k]['url'] = '';
            } else {
                //后台系统消息
                $message_list = Db::name("user_message_all")->where("id=$id")->find();
                $list[$k]['title'] = $message_list['title'];
                $list[$k]['url'] = $message_list['url'];
            }

            //内容
            $list[$k]['centent'] = $message_list['centent'];
            //时间
            $list[$k]['addtime'] = date('Y-m-d', $v['addtime']);

        }

        $result['list'] = $list;
        return_json_encode($result);

    }

    /**
     *    h5 页面我的消息
     */
    public function index()
    {
        $uid = intval(input("param.uid"));

        if ($uid == 0) {
            echo '页面访问错误';
            exit;
        }
        $user_info = Db::name("user")->where("id=$uid")->field("last_remove_message_time,is_auth")->find();

        if ($user_info['is_auth'] == '1') {
            $is_auth = '-1';
        } else {
            $is_auth = '-2';
        }
        $where = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "')";
        $message_log = Db::name("user_message_log")->where($where)->order('addtime desc')->select();

        db('user')->where("id=$uid")->update(array('last_remove_message_time' => NOW_TIME));

        $data = array();
        foreach ($message_log as $k => $v) {

            $data[$k]['id'] = $v['id'];
            $id = $v['messageid'];
            if ($v['type'] == 1) {
                //后台管理员审核操作
                $message_list = Db::name("user_message")->where("id=$id")->find();

                $data[$k]['title'] = $message_list['centent'];
                $data[$k]['url'] = '';
            } elseif ($v['type'] == 2) {
                //后台系统消息
                $message_list = Db::name("user_message_all")->where("id=$id")->find();

                $data[$k]['title'] = $message_list['title'];
                $data[$k]['url'] = $message_list['url'];
            } else {
                $message_list = Db::name("user")->where("id=" . $v['uid'])->find();
                $data[$k]['title'] = $message_list['user_nickname'] . "的消息";
                $data[$k]['url'] = '';
            }

            $data[$k]['centent'] = $v['messagetype'];         //内容
            $data[$k]['addtime'] = $v['addtime'];            //时间

        }

        $this->assign("message", $data);
        return $this->fetch();
    }

    /*
     * 获取未读信息的数量
     * */
    public function unread_messages()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['last_remove_message_time']);

        $last_time = $user_info['last_remove_message_time'];
        if ($user_info['is_auth'] == '1') {
            $is_auth = '-1';
        } else {
            $is_auth = '-2';
        }

        $where = "(touid='" . $uid . "' or touid=0 or touid='" . $is_auth . "') and addtime > $last_time";
        $count = Db::name("user_message_log")->where($where)->count();
        //未读消息数量
        $result['sum'] = $count;

        //未处理预约消息数量
   //     $result['un_handle_subscribe_num'] = db('video_call_subscribe')->where('to_user_id=' . $uid . ' and status=0')->count();
         $result['un_handle_subscribe_num']=db('video_call_subscribe')->alias('v')
                ->field("g.commission,g.type,g.id")
                ->join('user u', 'u.id=v.to_user_id')
                ->where("u.id=".$uid." and v.status=0 and u.sex =2")
                ->count("v.id"); 
        return_json_encode($result);
    }

}