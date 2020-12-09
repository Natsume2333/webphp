<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/18
 * Time: 23:06
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;

class WithdrawalsManageController extends AdminBaseController {

	public function index(){

		$where = [];

		if (!input('request.page')) {
			session('withdrawals_index', null);
		}
		/*
		if (input('request.mobile') || input('request.id') || input('request.name') || input('request.pay') || input('request.status') >= '0' || input('request.start_time') || input('request.end_time')) {
			session('withdrawals_index', input('request.'));
		}*/

        if (input('request.mobile') || input('request.id') || input('request.name') || input('request.status') >= '0' || input('request.start_time') || input('request.end_time') || input('request.bank_account') || input('request.bank_cardno')) {
            session('withdrawals_index', input('request.'));
        }

		if (session('withdrawals_index.mobile')) {
			$where['u.mobile'] = session('withdrawals_index.mobile');
		}
		if (session('withdrawals_index.status') >='0' && session('withdrawals_index.status') !='-1') {
			$where['r.status'] = session('withdrawals_index.status');
		}
		if (session('withdrawals_index.id')) {
			$where['r.user_id'] = session('withdrawals_index.id');
		}
		if (session('withdrawals_index.name')) {
			$where['u.user_nickname'] = session('withdrawals_index.name');
		}

        if (session('withdrawals_index.bank_account')) {
            $where['c.bank_account'] = session('withdrawals_index.bank_account');
        }
        if (session('withdrawals_index.bank_cardno')) {
            $where['c.bank_cardno'] = session('withdrawals_index.bank_cardno');
        }


		/*
		if (session('withdrawals_index.pay')) {
			$where['r.gathering_number'] = session('withdrawals_index.pay');
		}*/


		if (session('withdrawals_index.end_time') && session('withdrawals_index.start_time')) {
			$where['r.create_time'] = ['between', [strtotime(session('withdrawals_index.start_time')), strtotime(session('withdrawals_index.end_time'))]];
		}
        $money = db('user_cash_record')
            ->alias('r')
            ->join("user u", "u.id=r.user_id")
            ->join("user_cash_account c", "c.id=r.paysid")
            ->where($where)->sum("r.money");
        $income = db('user_cash_record')
            ->alias('r')
            ->join("user u", "u.id=r.user_id")
            ->join("user_cash_account c", "c.id=r.paysid")
            ->where($where)->sum("r.income");

        /*---------------------------------------------------------
		$list = db('user_cash_record')
			->alias('r')
			->join("user u", "u.id=r.user_id")
            ->join("user_cash_account c", "c.id=r.paysid")
			->field('u.user_nickname,u.mobile,r.*,c.pay,c.wx')
			->where($where)
			->order("r.create_time DESC")
			->paginate(20, false, ['query' => request()->param()]);
        */
		//echo db() -> getLastSql();exit;

        $list = db('user_cash_record')
            ->alias('r')
            ->join("user u", "u.id=r.user_id")
            ->join("user_cash_account c", "c.id=r.paysid")
            ->field('u.user_nickname,u.mobile,r.*,c.pay, c.bank_account, c.bank_cardno, c.bank_name, c.bank_addr')
            ->where($where)
            ->order("r.create_time DESC")
            ->paginate(20, false, ['query' => request()->param()]);


		$data = $list->toArray();

		$page = $list->render();
		//dump($data);exit;

		$this->assign('request', session('withdrawals_index'));
		$this->assign('data', $data['data']);
		$this->assign('page', $page);
        $this->assign('money', $money);
        $this->assign('income', $income);
		return $this->fetch();
	}

	//通过审核
	public function adopt_cash() {

		$id = input('param.id');

         db('user_cash_record')->where("id=$id")->update(array('status' => 1,'updatetime'=>time()));
		$this->success('操作成功');
	}

	//拒绝审核
	public function refuse_cash() {

		$id = input('param.id');

		$record = db('user_cash_record')->where('id', '=', $id)->find();
		//返还提现金额
		db('user')->where('id', '=', $record['user_id'])->setInc('income', $record['income']);
		//修改提现状态
		db('user_cash_record')->where('id', '=', $id)->setField('status', 2);
		$this->success('操作成功');
	}

	//删除
	public function del() {

		$id = input('param.id');

		db('user_cash_record')->where('id', '=', $id)->delete();
		$this->success('操作成功');
	}
	//*导出*/
	public function export() {

		$where = [];
		if (isset($_REQUEST['mobile']) && $_REQUEST['mobile'] != '') {
			$where['u.mobile'] = $_REQUEST['mobile'];
		}

		if (isset($_REQUEST['status']) && $_REQUEST['status'] != '' && $_REQUEST['status'] != '-1') {
			$where['r.status'] = $_REQUEST['status'];
		} else {

			$_REQUEST['r.status'] = 0;
		}

		if (isset($_REQUEST['id']) && $_REQUEST['id']) {
			$where['r.user_id'] = $_REQUEST['id'];
		}
		if (isset($_REQUEST['name']) && $_REQUEST['name']) {
			$where['u.user_nickname'] = $_REQUEST['name'];
		}
		if (isset($_REQUEST['pay']) && $_REQUEST['pay']) {
			$where['r.gathering_number'] = $_REQUEST['pay'];
		}

		if ($_REQUEST['end_time'] && $_REQUEST['start_time']) {
			$where['r.create_time'] = ['between', [strtotime($_REQUEST['start_time']), strtotime($_REQUEST['end_time'])]];
		}

		$list = db('user_cash_record')
			->alias('r')
			->join("user u", "u.id=r.user_id")
			->field('u.user_nickname,u.mobile,r.*')
			->where($where)
			->order("r.create_time DESC")
			->paginate();
	
		$lists = $list->toArray();
		if ($lists['data'] != null) {

			$statuses = array('0' => "未审核", "1" => "审核通过", "2" => "拒绝提现");
			foreach ($lists['data'] as $k => $v) {

				$money = $v['money'];


				$dataResult[$k]['user_id'] = $v['user_id'] ? $v['user_id'] : '暂无数据';
				$dataResult[$k]['user_nickname'] = $v['user_nickname'] ? $v['user_nickname'] : '暂无数据';
				$dataResult[$k]['mobile'] = $v['mobile'] ? $v['mobile'] : '暂无';
				$dataResult[$k]['income'] = $v['income'] ? $v['income'] : '0';
				$dataResult[$k]['money'] = $money ? $money : '0';
				$dataResult[$k]['gathering_name'] = $v['gathering_name'] ? $v['gathering_name'] : '暂无';
				$dataResult[$k]['gathering_number'] = $v['gathering_number'] ? $v['gathering_number'] : '暂无';
				$dataResult[$k]['create_time'] = $v['create_time'] ? date('Y-m-d h:i', $v['create_time']) : '暂无';
				$dataResult[$k]['status'] = $statuses[$v['status']] ? $statuses[$v['status']] : '暂无';

			}

			$str = "会员ID,会员名,手机号,提现数量,提现金额,提现姓名,提现支付账号,提交时间,状态";
			$title = "会员提现列表";

			$this->excelData($dataResult, $str, $title);
			exit();
		} else {
			$this->error("暂无数据");
		}

	}
}
	?>