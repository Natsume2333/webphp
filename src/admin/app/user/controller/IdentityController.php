<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/23 0023
 * Time: 上午 11:04
 */

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class IdentityController extends AdminBaseController
{

    //修改信息认证状态
    public function change_status_auth_info()
    {

        $id = input('param.id', 0, 'intval');
        $uid = input('param.uid', 0, 'intval');
        $type = input('param.type', 0, 'intval');
        $center = input('param.center');

        $auth_info = db("auth_form_record")->where('id', '=', $id)->where('status', '=', 0)->find();
        if (!$auth_info) {
            $this->error("操作失败：记录不存在！");
            exit;
        }

        $res = db("auth_form_record")->where('id', '=', $id)->update(array("status" => $type));

        if ($res) {
            //审核通过对邀请女性上级进行奖励
            if ($type == 1) {
                reg_invite_perfect_info_service($uid, 2);
            }

            //推送模板消息
            if ($type == 1) {
                db('user')->where('id=' . $auth_info['user_id'])->setField('is_auth', 1);
                push_msg_user(5, $uid, 1, $center);
            } else {
                push_msg_user(6, $uid, 1, $center);
            }

            $this->success("操作成功");
        } else {
            $this->success("操作失败");
        }
    }

    //信息认证列表
    public function auth_info_list()
    {

        $where = [];
        if (isset($_REQUEST['uid']) && $_REQUEST['uid'] != '') {
            $where['u.id'] = $_REQUEST['uid'];
        } else {
            $_REQUEST['uid'] = '';
        }

        if (isset($_REQUEST['status']) && $_REQUEST['status'] != '') {
            $where['a.status'] = $_REQUEST['status'];
        } else {
            $_REQUEST['status'] = -1;
        }
        if (input("request.end_time") && input("request.start_time")) {
            $where['a.create_time'] = ['between', [strtotime(input("request.start_time")), strtotime(input("request.end_time"))]];
        }
        
        $auth_list = db('user')->alias('u')->join('auth_form_record a', 'u.id=a.user_id')->field('u.user_nickname,u.sex,a.*')->where($where)->order('create_time desc')->paginate(20, false, ['query' => request()->param()]);
        $lists = $auth_list->toArray();
        foreach($lists['data'] as $key=>$val){
            $invite = db('invite_record')
                ->alias('i')
                ->join('user u','u.id=i.user_id')
                ->where('invite_user_id',$val['user_id'])
                ->find();
            if($invite){
                $lists['data'][$key]['invite_name'] = $invite['user_nickname'];
                $lists['data'][$key]['invite_id'] = $invite['user_id'];
            }else{
                $lists['data'][$key]['invite_name'] = 0;
                $lists['data'][$key]['invite_id'] = 0;
            }
            
        }
        $this->assign('list', $lists['data']);
        $this->assign('page', $auth_list->render());
        $this->assign('request', $_REQUEST);
        return $this->fetch();
    }

    //身份验证
    public function index()
    {
        if (request()->post()) {
            session('identity', request()->post());
        }
        if (!request()->get('page') and !request()->post()) {
            session('identity', null);
        }

        $where = session('identity.status') ? "status=" . session('identity.status') : "status=0";
        $where .= session('identity.uid') ? " and user_id=" . session('identity.uid') : '';

        $user = Db::name("user_auth_video")->where($where)->order('create_time desc')->paginate(20, false, ['query' => request()->param()]);
        $lists = $user->toArray();

        $config = load_cache('config');
        foreach ($lists['data'] as &$v) {
            $uid = $v['user_id'];
            $users = db("user")->where("id=$uid")->find();
            $v['user_nickname'] = $users['user_nickname'];
            $v['avatar'] = $users['avatar'];
            $v['video_url'] = get_sign_video_url($config['tencent_video_sign_key'], $v['video_url']);
        }

        $this->assign('user', $lists['data']);
        $this->assign('request', session('identity'));
        $this->assign('page', $user->render());
        return $this->fetch();
    }

    //审核验证类型
    public function upd()
    {
        $id = input('param.id', 0, 'intval');
        $uid = input('param.uid', 0, 'intval');
        $type = input('param.type', 0, 'intval');
        $center = input('param.center');

        $auth_info = db("user_auth_video")->where('id', '=', $id)->where('status', '=', 0)->find();

        if (!$auth_info) {
            $this->error("操作失败:记录不存在！");
            exit;
        }

        $res = db("user_auth_video")->where('id', '=', $id)->update(array("status" => $type));

        if ($res) {
            //审核通过对邀请女性上级进行奖励
            if ($type == 1) {
                db('user')->where('id=' . $auth_info['user_id'])->setField('is_auth', 1);
                reg_invite_perfect_info_service($uid, 2);
            }

            //推送模板消息
            if ($type == 1) {
                push_msg_user(5, $uid, 1, $center);
            } else {
                push_msg_user(6, $uid, 1, $center);
            }

            $this->success("操作成功");
        } else {
            $this->success("操作失败");
        }
    }
    //删除认证
    public function del(){
        $id = input('param.id', 0, 'intval');
        $uid = input('param.uid', 0, 'intval');
        $auth_info = db("auth_form_record")->where('id', '=', $id)->delete();
        if (!$auth_info) {
            $this->error("操作失败！");
            exit;
        }
        db('user')->where('id=' . $uid)->setField('is_auth', 0);
        $this->success("操作成功");
    }

}
