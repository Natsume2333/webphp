<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class AnchorController extends AdminBaseController
{
    /**
     * 主播统计
     */
    public function index()
    {    
        $where = '';
        if(!empty($this->request->param('uid'))){
            $where['id'] = $this->request->param('uid');
        }
        $user = db('user')->where('is_auth',1)->field('id,user_nickname,income_total')->where($where)->paginate(10);
        $user_list = [];
        foreach($user as $key=>$val){
            //视频通话数量
            $videoWhere_a = 'type = 0 and status = 2 and user_id = '.$val['id'];
            $videoWhere_b = 'type = 0 and status = 3 and user_id = '.$val['id'];
            $videoWhere_c = 'type = 0 and status = 2 and call_be_user_id = '.$val['id'];
            $videoWhere_d = 'type = 0 and status = 3 and call_be_user_id = '.$val['id'];
            $videoCount = db('video_call_record_log')->where($videoWhere_a)->count();
            $videoCount += db('video_call_record_log')->where($videoWhere_b)->count();
            $videoCount += db('video_call_record_log')->where($videoWhere_c)->count();
            $videoCount += db('video_call_record_log')->where($videoWhere_d)->count();
            $val['video_count'] = $videoCount;
            //语音
            $audioWhere_a = 'type = 1 and status = 2 and user_id = '.$val['id'];
            $audioWhere_b = 'type = 1 and status = 3 and user_id = '.$val['id'];
            $audioWhere_c = 'type = 1 and status = 2 and call_be_user_id = '.$val['id'];
            $audioWhere_d = 'type = 1 and status = 3 and call_be_user_id = '.$val['id'];
            $audioCount = db('video_call_record_log')->where($audioWhere_a)->count();
            $audioCount += db('video_call_record_log')->where($audioWhere_b)->count();
            $audioCount += db('video_call_record_log')->where($audioWhere_c)->count();
            $audioCount += db('video_call_record_log')->where($audioWhere_d)->count();
            $val['audio_count'] = $audioCount;
            //私信收益
            $private_coin = db('user_private_chat_log')->where('to_user_id',$val['id'])->sum('coin');
            $val['private_coin']=$private_coin;
            //邀请人数
            $invite_count = db('invite_record')->where('user_id',$val['id'])->count();
            $val['invite_count'] = $invite_count;
            $user_list[]= $val;
        }

        $this->assign('list', $user_list);
        $this->assign('page', $user->render());
        return $this->fetch();
    }

    //主播列表
    public function anchor_list(){
        $where = '';
        if(!empty($this->request->param('uid')) && empty($this->request->param('nickname'))){
            $where['id'] = $this->request->param('uid');
        }

        if(empty($this->request->param('uid')) && !empty($this->request->param('nickname'))){
            $where['user_nickname'] =['like',['%'.$this->request->param('nickname').'%']];
        }
        if(!empty($this->request->param('uid')) && !empty($this->request->param('nickname'))){
            $where['id'] = $this->request->param('uid');
            $where['user_nickname'] =['like',['%'.$this->request->param('nickname').'%']];
        }

        $where['is_auth'] = 1;
        $user = db('user')->where($where)->paginate(10,false,['query' => request()->param()]);
        $list = [];
        foreach ($user as $key => $value) {
            //接听率
            $value['answer_rate'] = $this->answer_rate($value['id']);

            //短视频收益
            $video_sum = Db::name("user_consume_log")->where("to_user_id =".$value['id']." and type=1")->sum('profit');
            $value['video_sum'] = $video_sum ? $video_sum : 0;
            //私照收益
            $photos_sum = Db::name("user_consume_log")->where("to_user_id =".$value['id']." and type=2")->sum('profit');
            $value['photos_sum'] = $photos_sum ? $photos_sum : 0;
            //礼物收益
            $gift_sum = Db::name("user_consume_log")->where("to_user_id =".$value['id']." and type=3")->sum('profit');
            $value['gift_sum'] = $gift_sum ? $gift_sum : 0;
            //通话收益
            $call_sum = Db::name("user_consume_log")->where("to_user_id =".$value['id']." and type=4")->sum('profit');
            $value['call_sum'] = $call_sum ? $call_sum : 0;
            //私信收益
            $messages_sum = Db::name("user_consume_log")->where("to_user_id =".$value['id']." and type=5")->sum('profit');
            $value['messages_sum'] = $messages_sum ? $messages_sum : 0;
            //守护收益
            $guardian_sum = Db::name("user_consume_log")->where("to_user_id =".$value['id']." and type=6")->sum('profit');
            $value['guardian_sum'] = $guardian_sum ? $guardian_sum : 0;
            //游戏收益
            $game_sum = Db::name("user_consume_log")->where("to_user_id =".$value['id']." and type=7")->sum('profit');
            $value['game_sum'] = $game_sum ? $game_sum : 0;
            //购买联系方式收益
            $contact_sum = Db::name("user_consume_log")->where("to_user_id =".$value['id']." and type=8")->sum('profit');
            $value['contact_sum'] = $contact_sum ? $contact_sum : 0;
            //邀请人ID
            $invite = db('invite_record')->where('invite_user_id',$value['id'])->find();
            $value['invite_uid'] = $invite['user_id'];
            $list[] = $value;
        }

        $this->assign('list',$list);
        $this->assign('page',$user->render());
        return $this->fetch();
    }

    //人气主播
    public function reference_list(){
        $where = 'u.is_auth=1 and u.reference=1';
        if(!empty($this->request->param('uid'))){
            $where.=" and u.id=".$this->request->param('uid');
        }
        if(!empty($this->request->param('nickname'))){
            $where.=" and u.user_nickname like '%".$this->request->param('nickname')."%'";
        }

        $order='';
        $field='u.*';
        $sorts = db('hot_sort')->where("status=1")->order("sort desc")->select();

        foreach ($sorts as $k => $v) {
           switch ($v['fields']){
            case 'is_online':
                $order.=",u.is_online desc";
              break;  
            case 'level':
                $order.=",u.level desc";
                break;
            case 'income_total':
               $order.=",u.income_total desc";
               break;
            case 'distance':   //总后台没有距离
            //  $order.=",distance asc";
              break; 
            case 'call_length':     //通话时长排序
                $order.=",call_length desc";
                $field.=",sum(o.time) as call_length";
              break;
            case 'fans':   //粉丝排序
                $order.=",fans desc";
                $field.=",sum(a.id) as fans";
              break;
            default:
              $order.='';
            }
        }
        if($order){
            $order=substr($order, 1);
        }

        $user=db('user')->alias("u")
        ->join('online_record o', 'o.user_id=u.id','LEFT')
        ->join('user_attention a', 'a.attention_uid=u.id','LEFT')
        ->field($field)
        ->where($where)
        ->order($order)
        ->group("u.id")
        ->paginate(10,false,['query' => request()->param()]);
 
        $list = [];
        foreach ($user as $key => $value) {
            //接听率
            $value['answer_rate'] = $this->answer_rate($value['id']);
            
            //邀请人ID
            $invite = db('invite_record')->where('invite_user_id',$value['id'])->find();
            $value['invite_uid'] = $invite['user_id'];
            $list[] = $value;
        }

        $this->assign('list',$list);
        $this->assign('page',$user->render());

        return $this->fetch();
    }

    public function add_black(){
        $request = request()->param();
        $id = $request['id'];
        $userInfo = db('user')->field('user_status')->find($id);
        if($userInfo['user_status']==0){
            $res = db('user')->where(['id'=>$id])->update(['user_status'=>2]);
        }else{
            $res = db('user')->where(['id'=>$id])->update(['user_status'=>0]);
        }
        if ($res !== false) {
            $this->success("操作成功！");
        } else {
            $this->error("操作失败！");
        }
    }

    public function add_reference(){

        $request = request()->param();
        $id = $request['id'];
        $userInfo = db('user')->field('reference')->find($id);
        if($userInfo['reference']==0){
              $data = array(
                    'uid' => $id,
                    'addtime' => time(),
                );
                Db::name("user_reference")->insert($data);
            $res = db('user')->where(['id'=>$id])->update(['reference'=>1]);
        }else{
             Db::name("user_reference")->where("uid=$id")->delete();
            $res = db('user')->where(['id'=>$id])->update(['reference'=>0]);
        }
        if ($res !== false) {
            $this->success("操作成功！");
        } else {
            $this->error("操作失败！");
        }
    }

    //排序
    public function reference_order(){
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("user")->where("id=$k")->update(array('orderno' => $v));
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

    //接听率
    public function answer_rate($id){
        $answer_yes = db('video_call_record_log')
            ->where('user_id', '=', $id)
            ->where('status', '=', 1)
            ->whereOr('call_be_user_id', '=', $id)
            ->count();
        $answer = db('video_call_record_log')
            ->where('user_id', '=', $id)
            ->whereOr('call_be_user_id', '=', $id)
            ->count();
        if($answer_yes==0 || $answer==0){
            return 0;
        }else{
            return round($answer_yes/$answer,2)*100;
        }
    }

    //设备封禁
    public function equipment_closures(){
        $res = db('equipment_closures')
                ->alias('e')
                ->join('user u','u.id=e.uid')
                ->field('u.user_nickname,u.sex,e.*')
                ->paginate(10);
        $list = [];
        foreach ($res as $key => $value) {
            
            //邀请人ID
            $invite = db('invite_record')->where('invite_user_id',$value['uid'])->find();
            $value['invite_uid'] = $invite['user_id'];
            $list[] = $value;
        }
        $this->assign('list',$list);
        $this->assign('page',$res->render());
        return $this->fetch();
    }


    public function del_closures(){
        $res = db('equipment_closures')->delete(request()->param('id'));
        if ($res) {
            $this->success("删除成功");
        } else {
            $this->success("删除失败");
        }
    }

       //人气主播排序
    public function anchor_sort(){
      
        $sort = db('hot_sort')->order("sort desc")->select();
      

        $this->assign('list',$sort);

        return $this->fetch();
    }
    //修改人气排序
    public function anchor_sort_upd(){
        $param = request()->param();
        if($param['type'] ==1){
             $data = '';
            foreach ($param['listorders'] as $k => $v) {
                $status = Db::name("hot_sort")->where("id=$k")->update(array('sort' => $v));
                if ($status) {
                    $data = $status;
                }
            }

            if ($data) {
                $this->success("排序成功");
            } else {
                $this->success("排序失败");
            }
        }else{
            $status=$param['status'] ==1 ? 2:1; 
            if(!$param['id']){
                 $this->success("操作失败");
            }
            $data = Db::name("hot_sort")->where("id=".$param['id'])->update(array('status' => $status));
            if ($data) {
                $this->success("操作成功");
            } else {
                $this->success("操作失败");
            }
        }
       
    }

}
