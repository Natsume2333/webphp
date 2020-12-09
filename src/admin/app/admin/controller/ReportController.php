<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/12 0012
 * Time: 上午 11:18
 */
namespace app\admin\controller;
use cmf\controller\AdminBaseController;
use think\Db;

class ReportController extends AdminBaseController {
	//获取举报用户分类
	public function index() {
		$type = Db::name("user_report_type")->select()->toArray();
		$this->assign('report', $type);
		return $this->fetch();
	}
    /*用户举报列表*/
    public function reportlist() {

        $p = $this->request->param('page');
        if ($this->request->param('status')>=0 || $this->request->param('uid')) {
            $data['status'] = ($this->request->param('status')>=0) && ($this->request->param('status') !='') ? $this->request->param('status') : '-1';
            $data['uid'] = $this->request->param('uid') ?$this->request->param('uid') :'';
            session("reportlist", $data);
        } else if (empty($p)) {
            $data['status']='-1';
            session("reportlist", $data);
        }

        $uid = session("reportlist.uid");
        $status= session("reportlist.status");
        $where = 'id >0 ';

        if ($status >= 0) {
            $where .=' and status='.$status;
        }
        if ($uid != '') {
            $where .=' and uid='.$uid;
        }

        $report = Db::name("user_report")
            ->where($where)
            ->paginate(20, false, ['query' => request()->param()]);

        $list = $report->toArray();
        //获取上传的图片
        foreach ($list['data'] as &$v) {
            $vid = $v['id'];

            //举报类型
            $report_type = db('user_report_type')->find($v['reporttype']);
            $v['title'] = $report_type['title'];

            //举报人
            $user_info = db('user')->field('user_nickname')->find($v['uid']);
            $v['bname'] = $user_info['user_nickname'];

            //被举报人
            $to_user_info = db('user')->field('user_nickname')->find($v['reportid']);
            $v['cname'] = $to_user_info['user_nickname'];

            $v['img'] = Db::name("user_report_img")->where("report=$vid")->field("id,img")->select()->toArray();
        }

        //dump($list['data']);exit;
        $this->assign('page', $report->render());
        $this->assign('request', session("reportlist"));
        $this->assign('report', $list['data']);
        return $this->fetch();
    }
	/**
	 * 获取举报用户分类添加
	 */
	public function add() {
		$id = input('param.id');
		if ($id) {
			$name = Db::name("user_report_type")->where("id=$id")->find();
			$this->assign('report', $name);
		}
		return $this->fetch();
	}
	//举报用户分类添加
	public function addPost() {
		$param = $this->request->param();
		$id = $param['id'];
		$data = $param['post'];
		$data['addtime'] = time();
		if ($id) {
			$result = Db::name("user_report_type")->where("id=$id")->update($data);
		} else {
			$result = Db::name("user_report_type")->insert($data);
		}
		if ($result) {
			$this->success("保存成功", url('report/index'));
		} else {
			$this->error("保存失败");
		}
	}
	//删除
	public function del() {
		$param = request()->param();
		$result = Db::name("user_report_type")->where("id=" . $param['id'])->delete();
		return $result ? '1' : '0';
		exit;
	}
	//修改排序
	public function upd() {
		$param = request()->param();
		$data = '';
		foreach ($param['listorders'] as $k => $v) {
			$status = Db::name("user_report_type")->where("id=$k")->update(array('orderno' => $v));
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

	//标记为已处理
	public function report_list_upd() {

		$id = input('param.id');

		db('user_report')->where('id=' . $id)->setField('status', 1);

		$this->success('操作成功');
	}

}