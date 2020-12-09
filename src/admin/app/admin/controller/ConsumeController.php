<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/8 0008
 * Time: 上午 10:55
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ConsumeController extends AdminBaseController {

	//消费记录
	public function index() {
		$where = [];
		if (!input('request.page')) {
			session('consume_index', null);
		}
		if (input('request.uid') || input('request.touid') || input('request.start_time') || input('request.end_time') || input('request.type') > 0) {
			session('consume_index', input('request.'));
			if (session('consume_index.type') == null) {
				session('consume_index.type', 0);
			}
		}
		if (session('consume_index.uid')) {
			$where['a.user_id'] = intval(session('consume_index.uid'));
		}
		if (session('consume_index.touid')) {
			$where['a.to_user_id'] = intval(session('consume_index.touid'));
		}
		if (session('consume_index.end_time') && session('consume_index.start_time')) {
			$where['a.create_time'] = ['between', [strtotime(session('consume_index.start_time')), strtotime(session('consume_index.end_time'))]];
		}
		if (session('consume_index.type') > 0) {
			$where['a.type'] = session('consume_index.type');
		}

		$user = db("user_consume_log")
			->alias("a")
			->join("user b", "b.id=a.user_id")
			->join("user c", "c.id=a.to_user_id")
			->field("a.*,b.user_nickname as uname,c.user_nickname as toname")
			->where($where)
			->order('create_time desc')
			->paginate(20, false, ['query' => request()->param()]);

		$lists = $user->toArray();
		$total = Db::name("user_consume_log")->alias("a")
            ->join("user b", "b.id=a.user_id")
            ->join("user c", "c.id=a.to_user_id")
            ->where($where)->sum('a.coin');

		$this->assign('total', $total);
		$this->assign('data', $lists['data']);
		$this->assign('request', session('consume_index'));
		$this->assign('page', $user->render());
		return $this->fetch();
	}
    //查看本次通话的记录
    public function select_call(){

    	$id=input('request.id');
    	//获取拨打视频通话记录表
    	$call = Db::name("user_consume_log")
    	->alias("a")->field("g.id")
        ->join("video_call_record_log v", "v.id=a.table_id")
        ->join("user_gift_log g", "g.channel_id=v.channel_id")
        ->where("a.table_id=".$id)
        ->select();

      	$time = Db::name("video_call_record_log")->field("call_time")->where("id=".$id)->find();

        $where_id='';
        foreach ($call as $v) {
        	$where_id.=$v['id'].",";
        }
        $where_in=rtrim($where_id, ','); 
        if($where_in){
        	$where="a.table_id in(".$id.",".$where_in.")";
    	}else{
    		$where="a.table_id =".$id;
    	}
		$user = db("user_consume_log")
		->alias("a")
		->join("user b", "b.id=a.user_id")
		->join("user c", "c.id=a.to_user_id")
		->field("a.*,b.user_nickname as uname,c.user_nickname as toname")
		->where($where)
		->order('a.create_time desc')
		->select()
		->toArray();
		//invite_profit_record
		$money=0;
		$profit=0;
		$coin=0;
		foreach($user as &$v){
			$v['create_time']=date('Y-m-d H:i',$v['create_time']);
			$invite = db("invite_profit_record")
			->alias("i")
			->join("user c", "c.id=i.user_id")
			->field("i.money,c.user_nickname as cname,c.id")
			->where("i.c_id=".$v['id']." and invite_user_id=".$v['to_user_id'])
			->find();

			$v['cname']= $invite ? $invite['cname'] : '';
			$v['money']= $invite ? $invite['money'] : '';
			$v['cid']= $invite ? intval($invite['id']) : '';
			if($v['money']){
				$money=$money + $v['money'];
			}
			$profit=$profit + $v['profit'];
			$coin=$coin + $v['coin'];
		}

		$data['coin']=$coin;
		$data['profit']=$profit;
		$data['money']=$money;
		$data['time']=secs_to_str($time['call_time']);
		$data['user']=$user;
		echo json_encode($data);
    }
	/*导出*/
	public function export() {

		$title = '会员列表';

		$where = [];
		if (!isset($_REQUEST['type']) || $_REQUEST['type'] == '-1' || $_REQUEST['type'] == 0) {
			$_REQUEST['type'] = '-1';
		} else {
			$where['a.type'] = $_REQUEST['type'];
		}

		if (isset($_REQUEST['uid']) && $_REQUEST['uid'] != '') {
			$where['a.user_id'] = intval($_REQUEST['uid']);
		}

		if (isset($_REQUEST['touid']) && $_REQUEST['touid'] != '') {
			$where['a.to_user_id'] = intval($_REQUEST['touid']);
		}
		if ($_REQUEST['end_time'] && $_REQUEST['start_time']) {
			$where['a.create_time'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
		}
		$user = db("user_consume_log")
			->alias("a")
			->join("user b", "b.id=a.user_id")
			->join("user c", "c.id=a.to_user_id")
			->field("a.*,b.user_nickname as uname,c.user_nickname as toname")
			->where($where)
			->order('create_time desc')
			->paginate();

		$lists = $user->toArray();
		if ($lists['data'] != null) {
			$type = array(0 => '其他消费', 1 => '视频消费', 2 => '私照消费', 3 => '礼物消费', 4 => '一对一视频消费', 5 => '私信消息付费', 6 => '购买守护消费');
			foreach ($lists['data'] as $k => $v) {

				$dataResult[$k]['user_id'] = $v['user_id'] ? $v['user_id'] : '暂无数据';
				$dataResult[$k]['uname'] = $v['uname'] ? $v['uname'] : '暂无数据';
				$dataResult[$k]['toname'] = $v['toname'] ? $v['toname'] : '暂无';
				$dataResult[$k]['to_user_id'] = $v['to_user_id'] ? $v['to_user_id'] : '暂无';
				$dataResult[$k]['coin'] = $v['coin'] ? $v['coin'] : '0';
				$dataResult[$k]['content'] = $v['content'] ? $v['content'] : '暂无';
				$dataResult[$k]['type'] = $type[$v['type']] ? $type[$v['type']] : $type[0];
				$dataResult[$k]['create_time'] = $v['create_time'] ? date('Y-m-d h:i', $v['create_time']) : '暂无';

			}

			$str = "消费用户ID,消费用户,收益用户,收益用户ID,消费(收益金币),消费说明,消费类型,消费时间";

			$this->excelData($dataResult, $str, $title);
			exit();
		} else {
			$this->error("暂无数据");
		}

	}

}