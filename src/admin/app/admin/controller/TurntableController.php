<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/20 0020
 * Time: 上午 11:02
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class TurntableController extends AdminBaseController {
	/**
	 * 转盘列表
	 */
	public function index() {
		$gift = db('gift')->select();
		$res = db('turntable')->order('orderon')->select();
		$this->assign(['turn' => $res, 'gift' => $gift]);
		return $this->fetch();
	}
	/**
	 * 转盘礼物添加
	 */
	public function add() {
		$gift = db('gift')->order('orderno')->select();
		$this->assign('gift', $gift);
		return $this->fetch();
	}
	public function addPost() {
		$param = $this->request->param();
		$data['gift_id'] = $param['type'];
		$data['num'] = $param['num'];
		$data['orderon'] = $param['orderno'];
		$data['probability'] = $param['gl'];
		$data['addtime'] = time();

		$result = Db::name("turntable")->insert($data);
		if ($result) {
			$this->success("保存成功", url('turntable/index'));
		} else {
			$this->error("保存失败");
		}
	}

	//修改
	public function edit() {
		$id = request()->param('id');
		$turn = db('turntable')->where('id', $id)->select();
		$gift = db('gift')->order('orderno')->select();
		$this->assign(['turn' => $turn, 'gift' => $gift]);
		return $this->fetch();
	}

	public function editPost() {
		$param = $this->request->param();
		$data['gift_id'] = $param['type'];
		$data['num'] = $param['num'];
		$data['orderon'] = $param['orderno'];
		$data['probability'] = $param['gl'];
		$data['addtime'] = time();
		$id = $param['id'];
		$result = db('turntable')->where('id', $id)->update($data);
		if ($result) {
			$this->success("修改成功", url('turntable/index'));
		} else {
			$this->error("保存失败");
		}
	}
	//删除
	public function del() {
		$param = request()->param();
		$result = Db::name("turntable")->where("id=" . $param['id'])->delete();
		return $result ? '1' : '0';exit;
	}

	//修改排序
	public function upd() {
		$param = request()->param();
		$data = '';
		foreach ($param['listorders'] as $k => $v) {
			$status = Db::name("turntable")->where("id=$k")->update(array('orderon' => $v));
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

	//抽奖记录
	public function record_list() {
		if (request()->param('status') == -1) {
			$where = [];
			$refill = [
				'status' => -1,
			];
		} else if (request()->param('status') == 1) {
			$where = ['tu.gift_id' => 0];
			$refill = [
				'status' => 1,
			];
		} else if (request()->param('status') == 2) {
			$where = 'tu.gift_id != 0';
			$refill = [
				'status' => 0,
			];
		} elseif (empty(request()->param('status'))) {
			$where = [];
			$refill = [
				'status' => -1,
			];
		}

		$list = db('user_turntable')
			->alias('t')
			->join('user u', 't.user_id = u.id')
			->join('turntable tu', 't.turntable_id = tu.id')
			->field('t.id,t.user_id,t.status,t.addtime,tu.gift_id')
			->where($where)->paginate(10, false, ['query' => request()->param()]);
		$gift = db('gift')->select();
		$this->assign(['list' => $list, 'page' => $list->render(), 'gift' => $gift, 'refill' => $refill]);
		return $this->fetch();
	}
    /**
     * 转盘礼物添加
     */
    public function giftAdd() {

        return $this->fetch();
    }

    public function addGiftPost(){

            $param = $this->request->param();
            $data['gift_id'] = $param['type'];
            $data['score'] = $param['num'];
            $data['imgurl'] = $param['img'];
            $data['winning_probability'] = $param['gl'];
            $data['ctime'] = time();
            $result = Db::name("gift_score")->insert($data);
            if ($result) {
                $this->success("保存成功", url('turntable/setgift'));
            } else {
                $this->error("保存失败");
            }

    }

    public function editgift(){

        $id = request()->param('id');
        $turn = db('gift_score')->where('id', $id)->select();
        $this->assign(['turn' => $turn]);
        return $this->fetch();

    }

    public function editGiftPost() {
        $param = $this->request->param();
        $data['gift_id'] = $param['type'];
        $data['score'] = $param['num'];
        $data['imgurl'] = $param['img'];
        $data['winning_probability'] = $param['gl'];
        $data['ctime'] = time();
        $id = $param['id'];
        $result = db('gift_score')->where('id', $id)->update($data);
        if ($result) {
            $this->success("修改成功", url('turntable/setgift'));
        } else {
            $this->error("保存失败");
        }
    }
    //删除
    public function delGift() {
        $param = request()->param();
        $result = Db::name("gift_score")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';exit;
    }
	public function setgift(){

        $res = db('gift_score')->order('id')->select();
        $this->assign(['turn' => $res]);
        return $this->fetch();

    }

    public function lottery_info(){
        $res = db('lottery_configure')->find();
        $this->assign(['data' => $res]);
        return $this->fetch();
    }

    public function editLotteryPost(){

        $param = $this->request->param();
        $data['id'] = 1;
        $data['bgimgurl'] = $param['img'];
        $data['ctime'] = time();
        $result = db('lottery_configure')->insert($data,true,true);
        if ($result) {
            $this->success("设置成功", url('turntable/setgift'));
        } else {
            $this->error("设置失败");
        }

    }
}
