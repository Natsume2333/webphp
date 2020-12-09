<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/8 0008
 * Time: 上午 9:32
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use QcloudApi;
use think\Db;
use app\admin\model\AudioAuditModel;
use think\Log;

class AuditController extends AdminBaseController
{

    //用户封面图审核
    public function user_thumb()
    {

        $where = [];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['uid'] = intval($request['uid']);
        }

        if (isset($request['status']) && intval($request['status']) != -1) {
            $where['status'] = intval($request['status']);
        } else {
            $request['status'] = '-1';
        }
        if (input('request.end_time') > 0 && input('request.start_time')) {
            $where['addtime'] = ['between', [strtotime(input('request.start_time')), strtotime(input('request.end_time'))]];
        }

        $usersQuery = Db::name('user_img');

        $list = $usersQuery->where($where)->order("addtime DESC")->paginate(20, false, ['query' => request()->param()]);
        $lists = $list->toArray();
        // print_r($lists);exit;
        foreach ($lists['data'] as &$v) {

            $find = Db::name("user")->where("id=" . $v['uid'])->find();
            $invite = db('invite_record')
                ->where('invite_user_id',$v['uid'])
                ->find();
            if($invite){
               $v['invite_id'] = $invite['user_id'];
            }else{
               $v['invite_id'] = 0;
            }
            
            if ($find) {
                $v['user_nickname'] = $find['user_nickname'];
                $v['mobile'] = $find['mobile'];
                $v['sex'] = $find['sex'];
            } else {
                $v['user_nickname'] = '';
                $v['mobile'] = '';
                $v['sex'] = '';
            }
        }
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $lists['data']);
        $this->assign('request', $request);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    //审核用户上传的封面
    public function user_thumb_upd()
    {

        $id = input('param.id');
        $uid = input('param.uid');
        $type = input('param.type');
        $center = input('param.center');
        $result = Db::name("user_img")->where("id=$id and uid=$uid")->update(array("status" => $type));

        if ($result) {
            if ($type == 1) {
                push_msg_user(9, $uid, 1, $center);
            } else {
                push_msg_user(10, $uid, 1, $center);
            }
            $this->success('操作成功！');
        } else {
            $this->success("操作失败");
        }

    }

    //小视频审核列表
    public function index()
    {


        if (request()->post()) {
            session('audit', request()->post());
            Log::info(date("Y-m-d H:i:s"). '1');

        }

        if (!request()->get('page') and !request()->post()) {
            $data['status'] = '';
            $data['type'] = '-1';
            session('audit', $data);


        }

        if (request()->get('uid')) {
            session('audit.uid', request()->get('uid'));
            Log::info(date("Y-m-d H:i:s"). '3');

        }

        //$where = " 1=1 ";

        $where = session('audit.type') >=0 ? "  a.type=" . session('audit.type') : "";
        if($where && session('audit.uid') ) {
            $where.= " and ";
        }
        $where .= session('audit.uid') ? "  a.uid=" . session('audit.uid') : '';
        if($where && session('audit.status') ) {
            $where.= " and ";
        }
        $where .= session('audit.status') ? "  a.status=" . session('audit.status') : "";
        if($where && session('audit.start_time') ) {
            $where.= " and ";
        }
        $where .= session('audit.start_time') ? "  a.addtime >=" . strtotime(session('audit.start_time')) : "";
        if($where && session('audit.end_time')) {
            $where.= " and ";
        }
        $where .= session('audit.end_time') ? "  a.addtime <=" . strtotime(session('audit.end_time')) : "";




        $user = Db::name("user_video")
            ->alias("a")
            ->join("user b", "b.id=a.uid")
            ->field("a.*,b.user_nickname,b.avatar")
            ->where($where)
            ->order('addtime desc')
            ->paginate(20, false, ['query' => request()->param()]);

        $lists = $user->toArray();

        $config = load_cache('config');
        $key = $config['tencent_video_sign_key'];

        foreach ($lists['data'] as &$v) {
            //获取视频地址
            $v['video_url'] = get_sign_video_url($key, $v['video_url']);
            //视频总付费
            $v['total_income'] = db('user_video_buy')->where('videoid', '=', $v['id'])->sum('coin');
        }
        $this->assign('user', $lists['data']);
        $this->assign('request', session('audit'));
        $this->assign('page', $user->render());


        return $this->fetch();
    }

    //小视频审核验证类型
    public function upd()
    {

        $id = input('param.id', 0, 'intval');
        $uid = input('param.uid', 0, 'intval');
        $type = input('param.type', 0, 'intval');
        $center = input('param.center');
        //$sta = $type =='1' ? "审核通过" : "审核不通过，请重新上传";

        $name = Db::name("user_video")->where("id=$id and uid=$uid")->update(array("type" => $type));
        $usera['id'] = $uid;
        if ($name) {
            //$message=Db::name("user_message")->where("id=3")->find();
            if ($type == 1) {
                push_msg_user(9, $uid, 1, $center);
            } else {
                push_msg_user(10, $uid, 1, $center);
            }
            $this->success('操作成功！');
        } else {
            $this->success("操作失败");
        }
    }

    //推荐视频
    public function recommend()
    {
        $id = input('param.id', 0, 'intval');
        $is_recommend = input('param.is_recommend', 0, 'intval');

        db('user_video')->where(['id' => $id])->setField('is_recommend', $is_recommend);

        $this->success('操作成功');
    }

    //私照审核列表
    public function photos()
    {
        if (request()->post()) {
            session('audit1', request()->post());
        }
        if (!request()->get('page') and !request()->post()) {
            $data['status'] = '-1';
            session('audit1', $data);

          //  session('audit1', null);
        }

        $where = session('audit1.status') >=0 ? "a.status=" . session('audit1.status') : "a.id >0";
        $where .= session('audit1.uid') ? " and a.uid=" . session('audit1.uid') : '';
        if (session('audit1.end_time') > 0 && session('audit1.start_time')) {
            $where['addtime'] = ['between', [strtotime(session('audit1.start_time')), strtotime(session('audit1.end_time'))]];
        }

        $user = Db::name("user_pictures")
            ->alias("a")
            ->join("user b", "b.id=a.uid")
            ->field("a.*,b.user_nickname,b.avatar,b.sex,b.mobile")
            ->where($where)
            ->order('addtime desc')
            ->paginate(20, false, ['query' => request()->param()]);

        $lists = $user->toArray();
        $config = load_cache('config'); //获取私照的收费标准
        $phonesCoin = $config['private_photos'];
        $array = [];
        foreach ($lists['data'] as $value) {
            $phonesCount = db('user_photo_buy')->where('p_id', $value['id'])->count();
            $invite = db('invite_record')
                ->where('invite_user_id',$value['uid'])
                ->find();
            if($invite){
               $value['invite_id'] = $invite['user_id']; 
            }else{
                $value['invite_id'] = 0;
            }

            if ($phonesCount == 0) {
                $value['phone_coin'] = 0;
            } else {
                $value['phone_coin'] = $phonesCount * $phonesCoin;
            }
            $array[] = $value;
        }

        $this->assign('user', $array);
        $this->assign('request', session('audit1'));
        $this->assign('page', $user->render());
        return $this->fetch();
    }

    //私照审核验证类型
    public function photos_upd()
    {

        $id = input('param.id', 0, 'intval');
        $uid = input('param.uid', 0, 'intval');
        $type = input('param.type', 0, 'intval');
        $center = input('param.center');
        //$sta = $type =='1' ? "审核通过" : "审核不通过，请重新上传";

        $user = Db::name("user_pictures")->where("id=$id and uid=$uid")->update(array("status" => $type));
        //$usera['id']=$uid;
        if ($user) {
            //$message = Db::name("user_message")->where("id=4")->find();
            if ($type == 1) {
                push_msg_user(7, $uid, 1, $center);
            } else {
                push_msg_user(8, $uid, 1, $center);
            }
            $this->success('操作成功！');
        } else {
            $this->success("操作失败");
        }
    }

    //删除视频
    public function del_video()
    {

        $id = input('param.id');
        $video = db('user_video')->find($id);
        if (!$video) {
            $this->error('视频记录不存在');
            exit;
        }

//		require_once DOCUMENT_ROOT . '/system/qcloudapi_sdk/src/QcloudApi/QcloudApi.php';
//
//		$puc_config = load_cache('config');
//		$config = array('SecretId' => $puc_config['tencent_api_secret_id'],
//			'SecretKey' => $puc_config['tencent_api_secret_key'],
//			'RequestMethod' => 'GET',
//			'DefaultRegion' => 'gz');
//
//		$cvm = QcloudApi::load(QcloudApi::MODULE_VOD, $config);
//
//		$package = array('fileId' => $video['video_id'], 'priority' => 0);
//
//		$a = $cvm->DeleteVodFile($package);
//		// $a = $cvm->generateUrl('DescribeInstances', $package);
//
//		if ($a === false) {
//			$error = $cvm->getError();
//			//echo "Error code:" . $error->getCode() . ".\n";
//			//echo "message:" . $error->getMessage() . ".\n";
//			//echo "ext:" . var_export($error->getExt(), true) . ".\n";
//			$this->error('Error message' . $error->getMessage());
//			exit;
//		} else {
//
//			//删除视频
//			db('user_video')->delete($id);
//		}
        //删除视频
        db('user_video')->delete($id);
        $this->success('操作成功');

    }

    //修改小视频的价格
    public function account()
    {
        $id = input('param.id');
        $coin = intval(input('param.coin'));
        $root = array('status' => 0, 'msg' => '参数错误');
        if ($coin) {
            $result = Db::name("user_video")->where("id=$id")->update(array("coin" => $coin));
            if ($result) {
                $root['status'] = '1';
                $root['msg'] = '修改成功';
            } else {
                $root['msg'] = '修改失败';
            }
        }
        echo json_encode($root);
        exit;
    }

    //删除封面
    public function user_thumb_del()
    {
        $id = input('param.id');
        $uid = input('param.uid');

        $result = Db::name("user_img")->where("id=$id and uid=$uid")->delete();
        if ($result) {
            push_msg_user(10, $uid, 1);

            $this->success('操作成功！');
        } else {
            $this->success("操作失败");
        }
    }

    //删除私照
    public function photos_del()
    {
        $id = input('param.id');
        $uid = input('param.uid');

        $user = Db::name("user_pictures")->where("id=$id and uid=$uid")->delete();

        if ($user) {
            push_msg_user(8, $uid, 1);
            $this->success('操作成功！');
        } else {
            $this->success("操作失败");
        }
    }

    //一键审核
    public function photosall()
    {
        $request = request()->param();
        if (empty($request['id'])) {
            return $this->redirect('/admin/public/index.php/admin/audit/photos');
        } else {
            $id = $request['id'];
            $type = $request['type'];
            if ($type == 1) {
                foreach ($id as $key => $val) {
                    $user = Db::name("user_pictures")->where("id=$val")->update(array("status" => 1));
                }
                if ($user) {
                    $this->success('操作成功！');
                } else {
                    $this->success("操作失败");
                }
            } else if ($type == 2) {
                foreach ($id as $key => $val) {
                    $user = Db::name("user_pictures")->where("id=$val")->update(array("status" => 2));
                }
                if ($user) {
                    $this->success('操作成功！');
                } else {
                    $this->success("操作失败");
                }
            }
        }
    }

    public function thumball()
    {
        $request = request()->param();
        if (empty($request['id'])) {
            return $this->redirect('/admin/public/index.php/admin/audit/user_thumb');
        } else {
            $id = $request['id'];
            $type = $request['type'];
            if ($type == 1) {
                foreach ($id as $key => $val) {
                    $user = Db::name("user_img")->where("id=$val")->update(array("status" => 1));
                }
                if ($user) {
                    $this->success('操作成功！');
                } else {
                    $this->success("操作失败");
                }
            } else if ($type == 2) {
                foreach ($id as $key => $val) {
                    $user = Db::name("user_img")->where("id=$val")->update(array("status" => 2));
                }
                if ($user) {
                    $this->success('操作成功！');
                } else {
                    $this->success("操作失败");
                }
            }
        }
    }

    public function videoall()
    {
        $request = request()->param();
        if (empty($request['id'])) {
            return $this->redirect('/admin/public/index.php/admin/audit/index');
        } else {
            $id = $request['id'];
            $type = $request['type'];
            if ($type == 1) {
                foreach ($id as $key => $val) {
                    $user = Db::name("user_video")->where("id=$val")->update(array("type" => 1));
                }
                if ($user) {
                    $this->success('操作成功！');
                } else {
                    $this->success("操作失败");
                }
            } else if ($type == 2) {
                foreach ($id as $key => $val) {
                    $user = Db::name("user_video")->where("id=$val")->update(array("type" => 2));
                }
                if ($user) {
                    $this->success('操作成功！');
                } else {
                    $this->success("操作失败");
                }
            }
        }
    }

    //语音列表
    public function audio_list(){
        $model = new AudioAuditModel();
        $user = db('audio_audit')
                ->alias('a')
                ->join('user u','u.id=a.uid')
                ->field('u.user_nickname,u.sex,u.mobile,a.*')
                ->order("a.addtime desc")
                ->paginate(10);
        $list = [];
        foreach ($user as $key => $value) {
            
            //邀请人ID
            $invite = db('invite_record')->where('invite_user_id',$value['uid'])->find();
            $value['invite_uid'] = $invite['user_id'];
            $list[] = $value;
        }
        $this->assign('user',$list);
        $this->assign('request', 1);
        $this->assign('page', $user->render());
        return $this->fetch();
    }

    //打招呼列表
    public function hello_list(){
        $user = db('hi_audit')
                ->alias('a')
                ->join('user u','u.id=a.uid')
                ->field('u.user_nickname,u.sex,u.mobile,a.*')
                ->paginate(10);
        $this->assign('user',$user);
        $this->assign('request', session('audit'));
        $this->assign('page', $user->render());
        return $this->fetch();
    }

    //审核语音
    function audio_post(){
        $model = new AudioAuditModel();
        $request = request()->param();
        $res = $model->post_a($request['id'],$request['type']);
        if ($res) {
            $this->success('操作成功！');
        } else {
            $this->success("操作失败");
        }
    }

    //删除语音
    function audio_del(){
        $model = new AudioAuditModel();
        $request = request()->param();
        $res = $model->del_a($request['id']);
        if ($res) {
            $this->success('操作成功！');
        } else {
            $this->success("操作失败");
        }
    }

    function hello_post(){
        $request = request()->param();
        $res = db('hi_audit')->where(['id'=>$request['id']])->update(['status'=>$request['type']]);
        if ($res) {
            $this->success('操作成功！');
        } else {
            $this->success("操作失败");
        }
    }

    function hello_del(){
        $request = request()->param();
        $res = db('hi_audit')->where(['id'=>$request['id']])->delete();
        if ($res) {
            $this->success('操作成功！');
        } else {
            $this->success("操作失败");
        }
    }
}
