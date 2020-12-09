<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/20 0020
 * Time: 上午 11:02
 */

namespace app\admin\controller;

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class MessageController extends AdminBaseController {
	/**
	 *   系统消息个人
	 */
	public function index() {
		$Message = Db::name("user_message")->select();
		//  print_($Message);exit;
		$this->assign('gift', $Message);
		return $this->fetch();
	}

	/**
	 * 添加消息个人
	 */
	public function add() {
		$id = input('param.id');
		if ($id) {
			$name = Db::name("user_message")->where("id=$id")->find();
			$this->assign('message', $name);
		}

		return $this->fetch();
	}

	//保存消息个人
	public function addPost() {
		$param = $this->request->param();
		//  print_r($param);exit;
		$id = $param['id'];
		$data = $param['post'];
		$data['addtime'] = time();
		if ($id) {
			$result = Db::name("user_message")->where("id=$id")->update($data);
		} else {
			$result = Db::name("user_message")->insert($data);
		}
		if ($result) {
			$this->success("保存成功", url('message/index'));
		} else {
			$this->error("保存失败");
		}
	}

	/**
	 *   系统消息所有人
	 */
	public function all() {
		$Message = Db::name("user_message_all")->select();
		//  print_($Message);exit;
		$this->assign('gift', $Message);
		return $this->fetch();
	}

	/**
	 * 添加消息所有人
	 */
	public function add_all() {
		$id = input('param.id');
		if ($id) {
			$name = Db::name("user_message_all")->where("id=$id")->find();
			$this->assign('message', $name);
		}

		return $this->fetch();
	}

	//保存消息所有人
	public function addPost_all() {
		$param = $this->request->param();
		//  print_r($param);exit;
		$id = $param['id'];
		$data = $param['post'];
		$data['addtime'] = time();
		if ($id) {
			$result = Db::name("user_message_all")->where("id=$id")->update($data);
		} else {
			$result = Db::name("user_message_all")->insert($data);
		}
		if ($result) {
			$this->success("保存成功", url('message/all'));
		} else {
			$this->error("保存失败");
		}
	}

	//消息推送记录
	public function charge() {

		$where = [];
		if (isset($_REQUEST['type']) && $_REQUEST['type'] != '' && $_REQUEST['type'] != '-1') {
			$where['a.type'] = $_REQUEST['type'];
		} else {
			$_REQUEST['type'] = '-1';
		}

		if (isset($_REQUEST['status']) && $_REQUEST['status'] != '' && $_REQUEST['status'] != '-1') {
			$where['a.status'] = $_REQUEST['status'];
		} else {
			$_REQUEST['status'] = '-1';
		}

		if (isset($_REQUEST['uid']) && $_REQUEST['uid'] != '') {
			$where['a.uid'] = $_REQUEST['uid'];
		}
		if (isset($_REQUEST['touid']) && $_REQUEST['touid'] != '') {
			$where['a.touid'] = $_REQUEST['touid'];
		}

		$user = Db::name("user_message_log")
			->alias("a")
			->where($where)
			->order('a.addtime desc')
			->paginate(20, false, ['query' => request()->param()]);

		$lists = $user->toArray();

		foreach ($lists['data'] as &$v) {
            $uid = $v['touid'];
            if($uid >0){
                //个人和管理员推送时获取被推送人的名称
                $users = Db::name("user")->where("id=$uid")->find();
                $v['toname'] = $users['user_nickname'];
            }else{
                if($uid =='-1'){
                    $v['toname'] = "已认证用户";
                    $v['touid']=0;
                }elseif($uid =='-2'){
                    $v['toname'] = "未认证用户";
                    $v['touid']=0;
                }else{
                    $v['toname'] = "所有用户";
                }
            }

			if ($_REQUEST['type'] == 1) {
				$mid = $v['messageid'];
				$users = Db::name("user_message")->where("id=$mid")->find();
				$v['messagetype'] = $users['title'] . $v['messagetype'];
			}
		}
		//print_r($lists['data']);exit;
		$this->assign('user', $lists['data']);
		$this->assign('request', $_REQUEST);
		$this->assign('page', $user->render());
		return $this->fetch();
	}
    //推送消息
    public function add_charge(){

		return $this->fetch();
    }
     //推送消息操作
    public function push_charge(){
    	$param = $this->request->param();
    	$touid=$param['touid'];
    	$centent=$param['centent'];

    	if(!$centent){
			$this->error("请输入消息内容");
		}

		$data=array(
			'touid'=>$touid,
			'messagetype'=>$centent,
			'type'  =>1,
			'addtime'=>time(),
			'status'  =>1,
		);

		if(!$touid){
			$data['touid']=0;
		}else{
			$users = Db::name("user")->where("id=$touid")->find();
			if(!$users){
				$this->error("请输入用户不存在");
			}
			
		}
		
		$results=Db::name("user_message_log")->insert($data);
		
 		
 		if($results){
 			$this->success("推送成功");
 		}else{
 			$this->error("推送成功");
 		}

    }

	//推送数据
	public function push_all() {
		$id = input("param.id");
        $type = input("param.type");
        $name = input("param.name");
        if($type==2){
            $userid=$name ? $name :0;
        }elseif($type==3){
            $userid='-1';
        }elseif($type==4){
            $userid='-2';
        }else{
            $userid=0;
        }
		$res = push_msg($id,$userid, 2);

		if ($res) {
			echo 1;
			exit;
		}

		echo 0;
		exit;
	}

}
