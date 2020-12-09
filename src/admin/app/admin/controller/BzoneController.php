<?php
/**
 * Created by PhpStorm.
 * User: yth
 * Date: 2018/11/02
 * Time: 上午 11:00
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class BzoneController extends AdminBaseController
{

    //删除动态
    public function bzone_del()
    {
        $param = request()->param();
        $bzone_data = Db::name("bzone")->where("id=" . $param['id'])->find();
        if ($bzone_data) {
            db('bzone_images')->where('zone_id=' . $bzone_data['id'])->delete();
            db('bzone_reply')->where('zone_id=' . $bzone_data['id'])->delete();
        }
        $result = db('bzone')->delete($bzone_data['id']);
        echo $result ? '1' : '0';
        exit;
    }

    /**
     * 动态列表
     */
    public function index()
    {
        $request = request()->param();
        if (empty($request['uid'])) {
            $where = [];
            $refill = [
                'uid' => '',
            ];

        } else {
            $where = ['b.uid' => $request['uid']];
            $refill = [
                'uid' => $request['uid'],
            ];
        }
        $resbzone = db('bzone')->alias("b")
                    ->join('user u', 'u.id = b.uid')
                    ->field("b.*,u.user_nickname")
                    ->where($where)
                    ->order("publish_time desc")
                    ->paginate(10, false, ['query' => request()->param()]);
        $bzone = [];
        foreach ($resbzone as $val) {
            $bzimg = db('bzone_images')->where('zone_id', $val['id'])->select()->toarray();
            $bzlike = db('bzone_like')->where('zone_id', $val['id'])->count();
            $bzreply = db('bzone_reply')->where('zone_id', $val['id'])->count();
            $val['bzlikenum'] = $bzlike;
            $val['bzreplynum'] = $bzreply;
            $val['bzimg'] = $bzimg ? $bzimg :'';
            $val['video_url'] = $val['video_url'] ?  $val['video_url']  :'';
            $bzone[] = $val;
        }

        $this->assign(['list' => $bzone, 'refill' => $refill, 'page' => $resbzone->render()]);
        return $this->fetch();
    }
    /*
     * 获取图片查看
     * */
    public function bzone_img(){

        $param = $this->request->param();
        $id = $param['id'];
        $bzimg = db('bzone_images')->where('zone_id', $id)->select()->toarray();
        $join=[];
        foreach($bzimg as $k=>$v){
            $join[$k]['alt']   ='';
            $join[$k]['pid']   =$v['id'];
            $join[$k]['src']   =$v['img'];
            $join[$k]['thumb'] =$v['img'];
        }
        $data=array(
            'title'=>'',
            'id' =>$id,
            'start'=>0,
            'data'=>$join
        );
        return $data;
    }
    /**
     * 转盘礼物添加
     */
    public function add()
    {
        $gift = db('gift')->order('orderno')->select();
        $this->assign('gift', $gift);
        return $this->fetch();
    }

    public function addPost()
    {
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
    public function edit()
    {
        $id = request()->param('id');
        $turn = db('turntable')->where('id', $id)->select();
        $gift = db('gift')->order('orderno')->select();
        $this->assign(['turn' => $turn, 'gift' => $gift]);
        return $this->fetch();
    }

    public function editPost()
    {
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
    public function del()
    {
        $param = request()->param();
        $result = Db::name("turntable")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    public function num()
    {
        $res = db('turntableNum')->where('id', 1)->update(['num' => request()->param('money')]);
        if ($res) {
            $this->success("保存成功", url('turntable/index'));
        } else {
            $this->error("保存失败");
        }
    }

    //修改排序
    public function upd()
    {
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
    /**
     *更新排序
     */
    /* private function listSort($model)
            {
                if (!is_object($model) )
                {
                    return false;
                }
                $pk = $model->getPk(); //获取主键
                $ids = $_POST['listorders'];
                foreach ($ids as $key => $r)
                {
                    $data['sort'] = $r;
                    $model->where(array($pk => $key))->save($data);
                }
                return true;
    */

    //抽奖记录
    public function record_list()
    {
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
        } else if (empty(request()->param('status'))) {
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
    //查看评论
    public function bzone_reply(){
        $id=request()->param('id');
        $uid=request()->param('uid') ? request()->param('uid') :'';
        $refill['uid']=$uid;
        $refill['id']=$id;
        $where="b.zone_id=".$id;
        if($uid){
            $where.=" and b.uid=".$uid;
        }
        $list=db('bzone_reply')->alias("b")
                ->join('bzone z', 'z.id = b.zone_id')
                ->join('user u', 'u.id = z.uid')
                ->join('user s', 's.id = b.uid')
                ->field("b.*,z.msg_content,z.uid as zid,u.user_nickname as zname,s.user_nickname")
                ->where($where)
                ->paginate(10, false, ['query' => request()->param()]);

        $this->assign(['list' => $list, 'page' => $list->render(),'refill' => $refill]);
        return $this->fetch();
    }
    //删除评论
    public function bzone_reply_del(){
        $param = request()->param();

        $result = db('bzone_reply')->delete($param['id']);
        echo $result ? '1' : '0';
        exit;
    }
}
