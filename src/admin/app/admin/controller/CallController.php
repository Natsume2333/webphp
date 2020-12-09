<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/16 0016
 * Time: 下午 17:55
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
class CallController extends AdminBaseController {
    //预约管理
    public function index() {

        /**搜索条件**/
        $p = $this->request->param('page');
        if (empty($p) and $this->request->param('status')<0 and  !$this->request->param('to_user_id') and !$this->request->param('user_id') and !$this->request->param('start_time') and !$this->request->param('end_time')) {
            session("admin_call", null);
            $data['status'] = '-1';
            session("admin_call", $data);
        } else if (empty($p)) {

            $data['status'] = $this->request->param('status') >= '0' ? $this->request->param('status') :'-1';
            $data['to_user_id'] = $this->request->param('to_user_id') ? $this->request->param('to_user_id') :'';
            $data['user_id'] = $this->request->param('user_id') ?$this->request->param('user_id') :'';
            $data['start_time'] = $this->request->param('start_time') ?$this->request->param('start_time') :'';
            $data['end_time'] = $this->request->param('end_time') ?$this->request->param('end_time') :'';
            session("admin_call", $data);
        }

        $status = session("admin_call.status");

        $to_user_id = session("admin_call.to_user_id");

        $user_id = session("admin_call.user_id");
        $start_time = session("admin_call.start_time");
        $end_time = session("admin_call.end_time");

        $where= 'a.coin >0';

        if ($status !='-1') {
            $where .= " and a.status=".$status;
        }
        if ($to_user_id) {
            $where .= " and a.to_user_id=".$to_user_id;
        }
        if ($user_id) {
            $where .= " and a.user_id=".$user_id;
        }
        if ($start_time) {
            $where .= " and a.create_time >=".strtotime($start_time);
        }
        if ($end_time) {
            $where .= " and a.create_time <=".strtotime($end_time);
        }

        $users = Db::name('video_call_subscribe')->alias("a")
            ->where($where)
            ->field("u.user_nickname uname,e.user_nickname as tname,a.*")
            ->join("user e", "e.id=a.to_user_id")
            ->join("user u", "u.id=a.user_id")
            ->order("a.create_time DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $users->render();
        $name = $users->toArray();

        $this->assign("page", $page);
        $this->assign("users", $name['data']);
        $this->assign("data", session("admin_call"));
        return $this->fetch();
    }
    //退款
    public function refund(){
        $id=$this->request->param('id');

        $subscribe = Db::name('video_call_subscribe')->where("id='$id' and status=0")->find();
        $status = Db::name('video_call_subscribe')->where("id='$id' and status=0")->update(array('status'=>3));
        if($status){
            Db::name('user')->where("id=".$subscribe['to_user_id'])->setInc('coin',$subscribe['coin']);
            $this->success("退款成功");
        }else{
            $this->error("退款失败！");
        }
    }
}