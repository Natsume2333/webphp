<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/14 0014
 * Time: 上午 10:34
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class InvitationController extends AdminBaseController {
    //邀请提现规则
    public function index(){
        $list = Db::name("user_invitation_withdrawal")->order("sort asc")->select()->toarray();

        $this->assign('list',$list);
        return $this->fetch();
    }

    /**
     * 提现添加
     */
    public function add() {
        $id = input('param.id');
        if ($id) {
            $name = Db::name("user_invitation_withdrawal")->where("id=$id")->find();
            $this->assign('rule', $name);
        } else {
            $this->assign('rule', array('status' => 1));
        }
        return $this->fetch();
    }
    
    public function addPost() {
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("user_invitation_withdrawal")->where("id=$id")->update($data);
        } else {
            $result = Db::name("user_invitation_withdrawal")->insert($data);
        }
        if ($result) {
            $this->success("保存成功", url('Invitation/index'));
        } else {
            $this->error("保存失败");
        }
    }
    //删除类型
    public function del() {
        $param = request()->param();
        $result = Db::name("user_invitation_withdrawal")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
    }
    //修改排序
    public function upd() {

        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("user_invitation_withdrawal")->where("id=$k")->update(array('sort' => $v));
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
    
    public function invite_list(){
        $where = '';
        if(!empty($this->request->param('uid')) && !empty($this->request->param('nickname'))){
            $where['i.user_id'] = $this->request->param('user_id');
            $where['u.user_nickname'] = ['like',['%'.$this->request->param('user_nickname'.'%')]];
        }
        if(!empty($this->request->param('uid'))){
            $where['i.user_id'] = $this->request->param('user_id');
            
        }

        if(!empty($this->request->param('nickname'))){
            $where['u.user_nickname'] = ['like',['%'.$this->request->param('user_nickname'.'%')]];
        }
        if(!empty($this->request->param('start_time')) && !empty($this->request->param('end_time'))){
            $where['i.create_time'] = ['between',[$this->request->param('start_time'),$this->request->param('end_time')]];
        }

        
        $res = db('invite_record')
                ->alias('i')
                ->join('user u','u.id=i.user_id')
                ->field('i.*,u.user_nickname,u.user_status')
                ->group('user_id')
                ->where($where)
                ->select();
        $list = [];
        foreach($res as $val){
            //充值收益
            $where['user_id'] = $val['user_id'];
            $where['type'] = 2;
            $val['money'] = db('invite_profit_record')->where($where)->sum('money');
            //主播收益
            $val['auth_money'] = db('invite_profit_record')->where(['type'=>1,'user_id'=>$val['user_id']])->sum('money');
            //二级收益
            $val['er_money'] = db('invite_profit_record')->where(['user_id'=>$val['invite_user_id']])->sum('money');
            $list[] = $val;
        }
        $this->assign('list',$list);
        return $this->fetch();
    }

    //拉黑
    public function edit_black(){
        $id = input('param.id');
        $userInfo = db('user')->field('user_status')->find($id);
        if($userInfo['user_status']==0){
            $res = db('user')->where('id',$id)->update(['user_status'=>2]);
        }else{
            $res = db('user')->where('id',$id)->update(['user_status'=>0]);
        }

        if ($res) {
            $this->success("操作成功");
        } else {
            $this->success("操作失败");
        }
    }
    //邀请背景图
    public function picture(){
        $list = Db::name("invite_bg_img")->order("sort asc")->select()->toarray();
        $url = SITE_URL;
        $lists = [];
        foreach ($list as $k=>$v) {
            $v['img'] = $url.'/admin/public/upload/'.$v['img'];
            $lists[] = $v;
        }
        $this->assign('list',$lists);
        return $this->fetch();
    }
    //编辑图片
    public function add_picture(){
           $id = input('param.id');
        if ($id) {
            $name = Db::name("invite_bg_img")->where("id=$id")->find();
            $url = SITE_URL;
            $name['img'] =  $url.'/admin/public/upload/'.$name['img'];
            $this->assign('rule', $name);
        } else {
            $this->assign('rule', array('status' => 1,'img'=>''));
        }
        return $this->fetch();
    }
    //保存图片
    public function picture_addPost(){
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        if(!$data['img']){
            $this->error("请上传图片");
        }
        if ($id) {
            $result = Db::name("invite_bg_img")->where("id=$id")->update($data);
        } else {
            $result = Db::name("invite_bg_img")->insert($data);
        }
        if ($result) {
            $this->success("保存成功", url('Invitation/picture'));
        } else {
            $this->error("保存失败");
        }
    }
    //保存排序图片
    public function upd_picture(){
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("invite_bg_img")->where("id=$k")->update(array('sort' => $v));
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
}