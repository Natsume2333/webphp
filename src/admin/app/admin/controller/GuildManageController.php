<?php

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\helper\Time;

/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2019/2/27
 * Time: 13:40
 */
class GuildManageController extends AdminBaseController
{

    public function index()
    {
        $list = db('guild')->alias('g')->order('g.create_time desc')->select()->toArray();
        $this->assign('list', $list);

        return $this->fetch();
    }
    //删除公会
    public function del(){
        $id = input('param.id');
        $list = db('guild')->where("id=$id")->delete();
        echo $list ? 1:0;
    }
    //增加工会列表
    public function add(){
        $id = input('param.id');
        if($id){
            $list = db('guild')->where("id=$id")->find();
        }else{
            $list['status']=1;
            $list['type']=1;
            $list['logo']='';
            $list['rules']='';
        }
        $rules=explode(",", $list['rules']);
        $guild = db('guild_rule')->select()->toArray();
        foreach ($guild as &$v) {
            $v['type']=in_array($v['id'],$rules) ? 1:0;
        }

        $this->assign('rule', $guild);
        $this->assign('list', $list);
        return $this->fetch();
    }
    //增加工会
    public function addPost(){
        $param = $this->request->param();
        $id = $param['id'];
        $data = $param['post'];
        $rule_ar = isset($param['rule']) ? $param['rule'] :'';
        if($param['psd']){
            $data['psd'] = cmf_password($param['psd']);
        }
        $rule ='';
        if($rule_ar){
             $str='';
            foreach ($rule_ar as $v) {
                 $str=$v.",";
            }
            $rule=substr($str,0,strlen($str)-1); 
        }
       
        $data['rules']=$rule;

        $data['create_time'] = time();
        if ($id) {
            $result = db("guild")->where("id=$id")->update($data);
        } else {
            if(empty($param['psd']) || strlen($param['psd']) < 6){
                $this->error("请输入密码6位以上的密码");
            }
            $result = db("guild")->insert($data);
        }
        if ($result) {
            $this->success("保存成功", url('guild_manage/index'));
        } else {
            $this->error("保存失败");
        }
    }
    //审核
    public function auditing()
    {
        $id = input('param.id');
        $status = input('param.status');

        db('guild')->where('id=' . $id)->setField('status', $status);

        $this->success("操作成功");
    }

    //查看公会信息
    public function select_guild_info()
    {

        $id = input('param.id');
        $guild = db('guild')->where('status=1 and id=' . $id)->find();
        $guild_join_list = db('guild_join')->where('status=1 and guild_id=' . $id)->select();
        
        $guild_info=$guild;
        //总人数
        $guild_info['num'] = count($guild_join_list);
       
        //今日总收益
        $day_time = Time::today();
        $guild_info['day_income'] = db('guild_log')->where('guild_id='.$id." and addtime >".$day_time[0])->sum("guild_earnings");
        //未审核人数
        $guild_info['auditing_num'] = db('guild_join')->where('id=' . $id . ' and status=0')->count();
        //总人数
        $guild_info['num'] = db('guild_join')->where('guild_id=' . $id . ' and status=1')->count();

        $this->assign('data', $guild_info);

        return $this->fetch();
    }

    //公会用户列表
    public function join_list()
    {
        $id = input('param.id');

        $data_list = db('guild_join')->alias('g')->join('user u', 'g.user_id=u.id')->where('g.guild_id=' . $id)
            ->field('g.*,u.user_nickname,u.avatar')->order('g.create_time desc')->paginate(20, false);
        $list = [];
        foreach ($data_list as $key => $val) {
            //礼物价值
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
        $this->assign('list', $list);
        $this->assign('page', $data_list->render());

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
    //公会提现
    public function withdrawal(){
          /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('id') || $this->request->param('status') || $this->request->param('start_time') || $this->request->param('end_time')) {
            $data['id'] = $this->request->param('id') ?$this->request->param('id') :'';
            $data['status'] = $this->request->param('status') || $this->request->param('status')=='0' ?$this->request->param('status') :'-1';
            $data['start_time'] = $this->request->param('start_time') ? $this->request->param('start_time') : '';
            $data['end_time'] =$this->request->param('end_time') ? $this->request->param('end_time') : '';

            session("guildmanage_withdrawal", $data);
        } else if (empty($p)) {
            $data['status'] ='-1';
            session("guildmanage_withdrawal", $data);
        }

        $id = session("guildmanage_withdrawal.id");
        $status = session("guildmanage_withdrawal.status");
        $start_time =session("guildmanage_withdrawal.start_time")? strtotime(session("guildmanage_withdrawal.start_time")." 00:00:00"):'';
        $end_time =session("guildmanage_withdrawal.end_time") ? strtotime(session("guildmanage_withdrawal.end_time")." 23:59:59"):'';

        $where="l.id >0";
        $where.=$id ? " and l.gid=".$id:'';
        $where.=$status !='-1' ? " and l.status=".$status:'';
        $where.=$start_time ? " and l.addtime >=".$start_time:'';
        $where.=$end_time ? " and l.addtime <=".$end_time:'';

        $data_list = db('guild_withdrawal_log')->alias('l')
            ->join('guild g', 'g.id=l.gid')
            ->where($where)
            ->field('g.name,l.*')
            ->order('l.addtime desc')
            ->paginate(20, false);

        $this->assign('data', session("guildmanage_withdrawal"));
        $this->assign('list', $data_list);
        $this->assign('page', $data_list->render());
         return $this->fetch();
    }   
    //操作公会提现
    public function upd_withdrawal(){
        $id=$this->request->param('id');
        $status=$this->request->param('status') ==1 ? 1:2 ;
        $guild = db("guild_withdrawal_log")->where("id=$id")->find();
        if(!$guild){
            $this->error("参数错误");
        }
        $result = db("guild_withdrawal_log")->where("id=$id")->update(array("status"=>$status));
        if(!$result){
            $this->error("操作失败");
        }
        if($status ==2){
            db("guild")->where("id=".$guild['gid'])->setInc("earnings",$guild['coin']);
        }
        $this->error("操作成功");
    }
    //公会收益记录
    public function earnings_log(){
          /**搜索条件**/
        $p = $this->request->param('page');
        if ($this->request->param('id') || $this->request->param('uid') || $this->request->param('hid') || $this->request->param('start_time') || $this->request->param('end_time')) {
            $data['id'] = $this->request->param('id') ?$this->request->param('id') :'';
            $data['hid'] = $this->request->param('hid') ?$this->request->param('hid') :'';
            $data['uid'] = $this->request->param('uid') ?$this->request->param('uid') :'';
            $data['start_time'] = $this->request->param('start_time') ? $this->request->param('start_time') : '';
            $data['end_time'] =$this->request->param('end_time') ? $this->request->param('end_time') : '';

            session("guildmanage_earnings_log", $data);
        } else if (empty($p)) {
            session("guildmanage_earnings_log", null);
        }

        $id = session("guildmanage_earnings_log.id");        //公会id
        $hid = session("guildmanage_earnings_log.hid");     //主播id
        $uid = session("guildmanage_earnings_log.uid");     //用户id
        $start_time =session("guildmanage_earnings_log.start_time")? strtotime(session("guildmanage_earnings_log.start_time")." 00:00:00"):'';
        $end_time =session("guildmanage_earnings_log.end_time") ? strtotime(session("guildmanage_earnings_log.end_time")." 23:59:59"):'';

        $where="l.id >0";
        $where.=$id ? " and l.guild_id=".$id:'';
        $where.=$hid  ? " and l.user_id=".$hid:'';
        $where.=$uid  ? " and u.id=".$uid:'';
        $where.=$start_time ? " and l.addtime >=".$start_time:'';
        $where.=$end_time ? " and l.addtime <=".$end_time:'';

        $data_list = db('guild_log')->alias('l')
            ->join('user_consume_log c', 'c.id=l.consume_log')
            ->join('guild g', 'g.id=l.guild_id')
            ->join('user u', 'u.id=c.user_id')
            ->join('user h', 'h.id=l.user_id')
            ->where($where)
            ->field('c.user_id as uid,c.coin as ucoin,c.content,l.*,g.name as gname,u.user_nickname as uname,h.user_nickname as hname')
            ->order('l.addtime desc')
            ->paginate(20, false);

        $this->assign('data', session("guildmanage_earnings_log"));
        $this->assign('list', $data_list);
        $this->assign('page', $data_list->render());
        return $this->fetch();
    }
    //通过和拒绝主播加入公会
    public function auditing_join(){
        $id = $this->request->param('id');
        $status = intval($this->request->param('status')) ==1 ? 1:2;
        $list = db('guild_join')->where("id=".$id)->update(array('status'=>$status));
        if($list){
            $this->success("操作成功");
        }else{
            $this->error("操作失败");
        }
    }


}