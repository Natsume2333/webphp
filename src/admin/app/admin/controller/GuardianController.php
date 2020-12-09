<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/30 0030
 * Time: 上午 10:54
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class GuardianController extends AdminBaseController
{
    //守护分类列表
    public function index(){
        $list = db('guardian')->order("sort DESC")->select();

        $this->assign('data', $list);

        return $this->fetch();
    }
    //编辑守护分类
    public function add(){
        $id = input('param.id');
        if ($id) {
            $name = Db::name("guardian")->where("id=$id")->find();
        }else{
            $name['status']=1;
        }
        $this->assign('guardian', $name);
        return $this->fetch();
    }
    //删除守护分类
    public function del(){
        $id=input('request.id');
        $list = db('guardian')->where("id=".$id)->delete();
        return $list? "1" : '2';
    }
    public function addPost()
    {
        $param = $this->request->param();

        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        if ($id) {
            $result = Db::name("guardian")->where("id=$id")->update($data);
        } else {
            $result = Db::name("guardian")->insert($data);
        }
        if ($result) {
            $this->success("保存成功", url('guardian/index'));
        } else {
            $this->error("保存失败");
        }
    }
    //用户充值守护记录
    public function recharge(){

        /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('uid') || $this->request->param('hostid') || $this->request->param('start_time') || $this->request->param('end_time')) {
            $data['uid'] = $this->request->param('uid') ?$this->request->param('uid') :'';
            $data['hostid'] = $this->request->param('hostid') ?$this->request->param('hostid') :'';
            $data['start_time'] = $this->request->param('start_time') ? $this->request->param('start_time') : '';
            $data['end_time'] =$this->request->param('end_time') ? $this->request->param('end_time') : '';

            session("Guardian_recharge", $data);
        } else if (empty($p)) {
            session("Guardian_recharge", null);
        }

        $uid = session("Guardian_recharge.uid");
        $hostid = session("Guardian_recharge.hostid");
        $start_time =session("Guardian_recharge.start_time")? strtotime(session("Guardian_recharge.start_time")." 00:00:00"):'';
        $end_time =session("Guardian_recharge.end_time") ? strtotime(session("Guardian_recharge.end_time")." 23:59:59"):'';

        $where="g.guardian_id > 0";
        if ($uid) {
            $where .=" and g.uid=".$uid;
        }
        if ($hostid) {
            $where .=" and g.hostid=".$hostid;
        }
        if($start_time){
            $where.=" and g.addtime >=".$start_time;
        }
        if($end_time){
            $where.=" and g.addtime <=".$end_time;
        }
        $coin = db('guardian_user_log')->alias('g')
            ->join("user u","u.id=g.uid")
            ->join("user h","h.id=g.hostid")
            ->join("guardian d","d.id=g.guardian_id")
            ->where($where)
            ->sum("g.coin");
        $list = db('guardian_user_log')->alias('g')
            ->join("user u","u.id=g.uid")
            ->join("user h","h.id=g.hostid")
            ->join("guardian d","d.id=g.guardian_id")
            ->field('u.user_nickname,h.user_nickname hname,g.*,d.title')
            ->where($where)
            ->order("g.addtime desc")
            ->paginate(20);

        $page = $list->render();
        $name = $list->toArray();
        $this->assign("requery",session("Guardian_recharge"));
        $this->assign('page', $page);
        $this->assign('data', $name['data']);
        $this->assign('coin', $coin);
        return $this->fetch();
    }
    //守护列表
    public function lists(){

        /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('uid') || $this->request->param('hostid')) {
            $data['uid'] = $this->request->param('uid') ?$this->request->param('uid') :'';
            $data['hostid'] = $this->request->param('hostid') ?$this->request->param('hostid') :'';
            session("Guardian_lists", $data);
        } else if (empty($p)) {
            session("Guardian_lists", null);
        }

        $uid = session("Guardian_lists.uid");
        $hostid = session("Guardian_lists.hostid");

        $where="g.endtime >=".time();
        if ($uid) {
            $where .=" and g.uid=".$uid;
        }
        if ($hostid) {
            $where .=" and g.hostid=".$hostid;
        }

        $list = db('guardian_user')->alias('g')
            ->join("user u","u.id=g.uid")
            ->join("user h","h.id=g.hostid")
            ->field('u.user_nickname,h.user_nickname hname,g.*')
            ->where($where)
            ->order("g.endtime asc")
            ->paginate(20);

        $page = $list->render();
        $name = $list->toArray();
        $this->assign("requery",session("Guardian_lists"));
        $this->assign('page', $page);
        $this->assign('data', $name['data']);

        return $this->fetch();
    }

}