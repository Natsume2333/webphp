<?php
namespace app\union\controller;

use think\Validate;
use cmf\controller\UnionBaseController;
use app\union\model\UserModel;

class CommentController extends UnionBaseController
{
		//首页
	public function index(){
		
		$id = session('union.id');
		$uid=input("id");
        $status=input("status") != null ? input("status") :'-1';

		$where='g.guild_id=' . $id;
		$where.=$uid ? " and g.user_id=".$uid :'';
        $where.=$status !='-1' ? " and g.status=".$status :'';

      
        $data_list = db('guild_join')->alias('g')->join('user u', 'g.user_id=u.id')->where($where)
            ->field('g.*,u.user_nickname,u.avatar,u.custom_video_charging_coin')->order('g.create_time desc')->paginate(20, false);
        $list = [];
        foreach ($data_list as $key => $val) {
            //礼物收益
            $val['gift_coin'] =db('guild_log')->where('user_id='.$val['user_id']." and type=1 and guild_id=".$id)->sum("host_earnings");
            ///接听率
            $val['answer_rate'] = $this->answer_rate($val['user_id']);
            //通话收入
            $val['video_coin']   =db('guild_log')->where('user_id='.$val['user_id']." and type=2 and guild_id=".$id)->sum("host_earnings");
            //私信收入
            $val['private_coin'] = db('guild_log')->where('user_id='.$val['user_id']." and type=3 and guild_id=".$id)->sum("host_earnings");
            //总收益
            $val['guild_earnings'] = db('guild_log')->where('user_id='.$val['user_id']." and guild_id=".$id)->sum("guild_earnings");
            $list[] = $val;
        }


        $this->assign('status', $status);
        $this->assign('id', $uid);
        $this->assign('list', $list);
        $this->assign('page', $data_list->render());

		return $this->fetch();
	}
    //查看自定义价格
    public function sel_video_coin(){
        $root=array('code'=>0,'msg'=>'','list'=>array());
        $hid=input("id");
        $coin=input("coin");

        $rule_status=$this->rules('comment','upd_video');   //获取视频收费价格权限
        if(count($rule_status) != 1){
            echo json_encode($root);exit;
        }

        $user = db('user')->field("level,custom_video_charging_coin")->where("id=".$hid)->find();

        $list = db('host_fee')->where("level <=".$user['level'])->order("sort asc")->select()->toarray();
        if(count($list) >0){
            foreach ($list as &$v) {
                $v['type']=0;
                if ($v['level'] == '0') {
                    $v['name'] = "所有用户";
                } else {
                    $level = db("level")->where("level_name=" . $v['level'])->find();
                    $v['name'] = "LV" . $level['level_name'] . "主播";
                }
                if($v['coin'] == $user['custom_video_charging_coin']){
                     $v['type']=1;
                }
            }
            $root['code']=1;
        }
        
        $root['list']=$list;

        echo json_encode($root);
        exit;

    }
	//修改主播状态
	public function host_status(){
		$id = session('union.id');
		$jid=input("id");
		$status=input("status");
        $data['status']  =$status;  
        $requery=db('guild_join')->where('id ='. $jid." and guild_id=".$id)->update($data);
         
        return $requery ? 1 : 0;
	}
    //修改主播收费价格
    public function upd_video(){
        $id = session('union.id');
        $hid=input("id");
        $fid=input("coin");
        $requery=db('guild_join')->where('user_id ='. $hid." and guild_id=".$id)->find();
        $fee = db('host_fee')->where("id =".$fid)->find();
        $rule_status=$this->rules('comment','upd_video');   //获取视频收费价格权限
        if(count($rule_status) == 1 && $requery){
            $data['custom_video_charging_coin']=$fee['coin'];
            $sert=db('user')->where('id ='. $hid)->update($data);
            if($sert){
                return 1;exit;
            }
        }
        return 0;exit;

    }
	//删除主播
	public function host_del(){
		$id = session('union.id');
		$jid=input("id");

        $requery=db('guild_join')->where('id ='. $jid." and guild_id=".$id)->delete();

        return $requery ? 1 : 0;
	}
    //主播详情
    public function gift(){
        $id = session('union.id');

        if(!input("page")){
            $gets['to_user_id']=input("to_user_id");
            $gets['user_id']=input("user_id");
            $gets['start_time']=input("start_time");
            $gets['end_time']=input("end_time");
            session("union_gift",$gets);
        }
        $data['to_user_id']=session("union_gift.to_user_id");
        $data['user_id']=session("union_gift.user_id");
        $data['start_time']=session("union_gift.start_time");
        $data['end_time']=session("union_gift.end_time");


        $guild=db('guild_join')->where('user_id ='. $data['to_user_id']." and guild_id=".$id)->find();
        if(!$guild){
            $this->error("主播不存在公会中");
        }
        $where="g.to_user_id=".$data['to_user_id']." and l.type=1";

        $where.= $data['user_id'] ? " and g.user_id=".$data['user_id'] : '';
        $where.= $data['start_time'] ? " and g.create_time >=".strtotime($data['start_time']." 00:00:00") : '';
        $where.= $data['end_time'] ? " and g.create_time <".strtotime($data['end_time']." 24:00:00") : '';

        //礼物
        $list = db('user_gift_log')->alias('g')
            ->join('guild_log l', 'l.table_log=g.id')
            ->join('user u', 'g.to_user_id=u.id')
            ->join('user t', 'g.user_id=t.id')
            ->where($where)
            ->field('g.*,u.user_nickname as uname,t.user_nickname as tname,l.guild_earnings')
            ->order('g.create_time desc')
            ->paginate(12, false);

        $this->assign('result', $data);
        $this->assign('list', $list);
        $this->assign('page', $list->render());

        return $this->fetch();
    }
    //打字收入(私聊)
    public function messages(){
        $id = session('union.id');

        if(!input("page")){
            $gets['to_user_id']=input("to_user_id");
            $gets['user_id']=input("user_id");
            $gets['start_time']=input("start_time");
            $gets['end_time']=input("end_time");
            session("union_messages",$gets);
        }
        $data['to_user_id']=session("union_messages.to_user_id");
        $data['user_id']=session("union_messages.user_id");
        $data['start_time']=session("union_messages.start_time");
        $data['end_time']=session("union_messages.end_time");


        $guild=db('guild_join')->where('user_id ='. $data['to_user_id']." and guild_id=".$id)->find();
        if(!$guild){
            $this->error("主播不存在公会中");
        }
        $where="g.to_user_id=".$data['to_user_id']." and l.type=3";

        $where.= $data['user_id'] ? " and g.user_id=".$data['user_id'] : '';
        $where.= $data['start_time'] ? " and g.create_time >=".strtotime($data['start_time']." 00:00:00") : '';
        $where.= $data['end_time'] ? " and g.create_time <".strtotime($data['end_time']." 24:00:00") : '';

        //礼物
        $list = db('user_private_chat_log')->alias('g')
            ->join('guild_log l', 'l.table_log=g.id')
            ->join('user u', 'g.to_user_id=u.id')
            ->join('user t', 'g.user_id=t.id')
            ->where($where)
            ->field('g.*,u.user_nickname as uname,t.user_nickname as tname,l.guild_earnings')
            ->order('g.create_time desc')
            ->paginate(12, false);

        $this->assign('result', $data);
        $this->assign('list', $list);
        $this->assign('page', $list->render());

        return $this->fetch();
    }
   //视频收益
    public function video(){
        $id = session('union.id');

        if(!input("page")){
            $gets['to_user_id']=input("to_user_id");
            $gets['user_id']=input("user_id");
            $gets['start_time']=input("start_time");
            $gets['end_time']=input("end_time");
            session("union_video",$gets);
        }
        $data['to_user_id']=session("union_video.to_user_id");
        $data['user_id']=session("union_video.user_id");
        $data['start_time']=session("union_video.start_time");
        $data['end_time']=session("union_video.end_time");

        $guild=db('guild_join')->where('user_id ='. $data['to_user_id']." and guild_id=".$id)->find();
        if(!$guild){
            $this->error("主播不存在公会中");
        }
        $where="g.to_user_id=".$data['to_user_id']." and l.type=2";

        $where.= $data['user_id'] ? " and g.user_id=".$data['user_id'] : '';
        $where.= $data['start_time'] ? " and g.create_time >=".strtotime($data['start_time']." 00:00:00") : '';
        $where.= $data['end_time'] ? " and g.create_time <".strtotime($data['end_time']." 24:00:00") : '';

        //视频查询
        $list = db('video_charging_record')->alias('g')
            ->join('guild_log l', 'l.table_log=g.id')
            ->join('user u', 'g.to_user_id=u.id')
            ->join('user t', 'g.user_id=t.id')
            ->where($where)
            ->field('g.*,u.user_nickname as uname,t.user_nickname as tname,l.guild_earnings')
            ->order('g.create_time desc')
            ->paginate(12, false);

        $this->assign('result', $data);
        $this->assign('list', $list);
        $this->assign('page', $list->render());

        return $this->fetch();
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
    //规则权限  $controller 控制器  $methods 方法
    public function rules($controller='',$methods=''){
        $id = session('union.id');
        //规则权限
        $guild = db('guild')->field("rules")->where("id=$id")->find();
        $rule=[];

        if($guild['rules']){
            $rules=explode(",", $guild['rules']);
          
            $wheres=$controller !=''&& $methods!='' ? " and controller='".$controller."' and methods='".$methods."'" :'';
            foreach ($rules as$v) {

                $where="id=".$v.$wheres;

                $rule_val=db('guild_rule')->where($where)->find();
                if($rule_val){
                    $rule[]=$rule_val;
                }
            }
        }
     
        return $rule;            
    }
}

?>