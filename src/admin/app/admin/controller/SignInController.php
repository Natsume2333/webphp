<?php
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class SignInController extends AdminBaseController{
	//签到类型
	public function index(){
        $sign_in = Db::name('sign_in_list')
            ->order("sort desc")
            ->select();
        $sign_in_add=count($sign_in) < 7 ? 1 : 0;   //限制7天签到

        $this->assign("list", $sign_in);
        $this->assign("sign_in_add", $sign_in_add);
        return $this->fetch();
	}
	//增加签到类型页面
	public function add(){
		$id = $this->request->param('id', 0, 'intval');
		if($id){
			 $list = Db::name('sign_in_list')->where("id=$id")->find();
		}else{
			$list['status']=1;
		}

 		$this->assign("list", $list);
        return $this->fetch();
	}
	//添加签到类型
	public function addPost(){
		$param = $this->request->param();
        $id = $param['id'];
        $data = $param;
        $data['create_time'] = time();
        if($id){
            $result = Db::name("sign_in_list")->where("id=$id")->update($data);
        }else{
            $result = Db::name("sign_in_list")->insert($data);
        }
        if($result){
            $this->success("保存成功",url('sign_in/index'));
        }else{
            $this->error("保存失败");
        }
	}
	//删除签到类型
	public function delete(){
		$param = $this->request->param();
		$id = $param['id'];
		$result = Db::name("sign_in_list")->where("id=$id")->delete();
		 if($result){
            $this->success("删除成功",url('sign_in/index'));
        }else{
            $this->error("删除失败");
        }
	}
	//获取用户签到列表
	public function sign_list(){

		$p = $this->request->param('page');

        if ($this->request->param('uid') || $this->request->param('start_time') || $this->request->param('end_time') || empty($p)) {

            $data['uid'] = $this->request->param('uid');
            $data['start_time'] = $this->request->param('start_time') ? $this->request->param('start_time') : date('Y-m-d',time());
            $data['end_time'] = $this->request->param('end_time') ? $this->request->param('end_time') :date('Y-m-d',time());
           
            session("admin_sign_list", null);
            session("admin_sign_list",$data);

        }

        $uid = session("admin_sign_list.uid");

        $starttime = strtotime(session("admin_sign_list.start_time") . " 00:00:00");
        $endtime = strtotime(session("admin_sign_list.end_time") . " 23:59:59");

        $where = "s.create_time >=".$starttime." and s.create_time <= ".$endtime;

        if ($uid) {
            $where .= " and s.uid=".$uid;
        }

		$list = Db::name('sign_in_list')
		->alias('r')
		->join("user_sign_in s", "r.id=s.sign_id")
		->join("user u", "u.id=s.uid")
		->field('u.user_nickname,s.*')
		->where($where)
		->order("r.create_time DESC")
		->paginate(20, false, ['query' => request()->param()]);
		$data = $list->toArray();
		$page = $list->render();

		$this->assign('list', $data['data']);
		$this->assign('page', $page);
		$this->assign('requery',session("admin_sign_list"));
		return $this->fetch();
	}

}




?>
