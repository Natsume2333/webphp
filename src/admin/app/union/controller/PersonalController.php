<?php
namespace app\union\controller;

use think\Validate;
use cmf\controller\UnionBaseController;
use app\union\model\UserModel;

class PersonalController extends UnionBaseController
{
    //首页数据
    public function home(){
        $id = session('union.id');
        $guild = db('guild')->where('status=1 and id=' . $id)->find();
        $guild_join_list = db('guild_join')->where('status=1 and guild_id='.$id)->select();

        //总人数
        $guild_info['num'] = count($guild_join_list);
        //总收益
        $guild_info['total_earnings'] =$guild['total_earnings'];
         //剩余收益
        $guild_info['earnings'] =$guild['earnings'];
        //提成
        $guild_info['commission']=$guild['commission'];
        //今日总收益
        $day_time =strtotime(date("Y-m-d",time()));
        $guild_info['day_income'] = db('guild_log')->where('guild_id='.$id." and addtime >".$day_time)->sum("guild_earnings");
        //未审核人数
        $guild_info['auditing_num'] = db('guild_join')->where('id=' . $id . ' and status=0')->count();
        //总人数
        $guild_info['num'] = db('guild_join')->where('guild_id=' . $id . ' and status=1')->count();

        $this->assign('list', $guild_info);
        return $this->fetch();
        
    }
    //个人资料
    public function index(){
        $id = session('union.id');
        $where='id=' . $id;
        $list = db('guild')->where($where)->find();
        $this->assign('list', $list);
        return $this->fetch();
    }
    //保存个人资料
    public function add_index(){
        $id = session('union.id');
        $name=input("name");
        $introduce=input("introduce");
        $logo   = input("logo");
        $tel   = input("tel");
        $psd   = input("psd");
        $account_type   = input("account_type");
        $cash_account   = input("cash_account");
        $account_name   = input("account_name");

        if(empty($name)){
           $this->error('昵称不能为空');
        }
        if(empty($introduce)){
            $this->error('请输入简介');
        }
         if(empty($tel)){
            $this->error('请输入手机号');
        }
        if(empty($logo)){
            $this->error('请上传头像');
        }
          if(empty($logo)){
            $this->error('请上传头像');
        }
        if(empty($logo)){
            $this->error('请上传头像');
        }
         if(empty($cash_account)){
            $this->error('请绑定提现账户');
        }
         if(empty($account_name)){
            $this->error('请绑定提现账户名称');
        }

        $data=array(
            'name'=>$name,
            'introduce'=>$introduce,
            'logo'=>$logo,
            'tel'=>$tel,
            'account_type'=>$account_type,
            'cash_account' =>$cash_account,
            'account_name'=>$account_name,
        );
        if(!empty($psd)){
            if(strlen($psd) < 6){
                $this->error("请输入密码6位以上的密码");
            }else{
                $data['psd']=cmf_password($psd);
            }
        }

        $requery=db('guild')->where('id ='.$id)->update($data);
        if($requery){
            $this->success('保存成功');
        }else{
             $this->error('保存失败');
        }
    }
    //提现列表
    public function withdrawal(){
        $id = session('union.id');
        $where='id=' . $id;
        $list = db('guild')->where($where)->find();
        $least_guild_withdrawal = db('config')->where("code='least_guild_withdrawal' and status=1")->field("val")->find();
        $guild_exch = db('config')->where("code='guild_exchange' and status=1")->field("val")->find();
        $cash_day_l = db('config')->where("code='cash_day_limit' and status=1")->field("val")->find();
        $data['least_guild_withdrawal'] = $least_guild_withdrawal['val'];
        $data['guild_exchange'] =$guild_exch['val'];
        $data['cash_day_limit'] =$cash_day_l['val'];

         $list['can_carry']=$list['earnings']<=0 ||$guild_exch['val'] <=0 ? 0 :round($list['earnings']/$guild_exch['val'],2);
        $this->assign('list', $list);
        $this->assign('data', $data);
        return $this->fetch();
    }
    //提现操作
    public function guild_withdrawal(){
        $root=array('code'=>0,'msg'=>'');
        $id = session('union.id');
        $coin=intval(input("coin"));
        $list = db('guild')->where("id=".$id)->find();
        $least_guild_withdrawal = db('config')->where("code='least_guild_withdrawal' and status=1")->field("val")->find();
        $guild_exch = db('config')->where("code='guild_exchange' and status=1")->field("val")->find();
        $cash_day_l = db('config')->where("code='cash_day_limit' and status=1")->field("val")->find();
        
        if($list['earnings'] <=0){
             $root['msg']='提现账户余额不足';
            echo json_encode($root);exit;
        }
        $can_carry=round($list['earnings']/$guild_exch['val'],2);

        if(!$list['account_name']){
            $root['msg']='请绑定账户';
            echo json_encode($root);exit;
        }
        if($coin > $can_carry){
            $root['msg']='提现金额错误';
            echo json_encode($root);exit;
        }
        if($coin < $least_guild_withdrawal['val']){
            $root['msg']='每次最低提现'.$least_guild_withdrawal['val']."元";
            echo json_encode($root);exit;
        }
      
        $day_time =strtotime(date("Y-m-d",time()));
        $guild_log = db('guild_withdrawal_log')->where("gid=".$id." and addtime >= ".$day_time)->count();


        if($guild_log > intval($cash_day_l['val'])){
            $root['msg']='每日只提现'.$cash_day_l['val']."次";
            echo json_encode($root);exit;
        }
        $earnings=$coin*$guild_exch['val'];
        $data=array(
            'gid'  =>$id,
            'coin' =>$earnings,
            'money' =>$coin,
            'status' =>0,
            'addtime' =>time(),
            'account_name'=>$list['account_name'],
            'cash_account'=>$list['cash_account'],
            'account_type'=>$list['account_type'],
        );

        $guild = db('guild')->where(['id' => $id])->setDec('earnings', $earnings);
        if(!$guild){
            $root['msg']='提现失败';
            echo json_encode($root);exit;
        }
        db('guild_withdrawal_log')->insert($data);
       
        $root['code']=1;
        $root['msg']='提现成功,等待审核';
        echo json_encode($root);exit;

    }
    //提现记录
    public function withdrawal_log(){
        $id = session('union.id');

        if(!input("page")){
            $gets['status']=input("status") || input("status")=='0'? input("status") :'-1';
            $gets['start_time']=input("start_time");
            $gets['end_time']=input("end_time");
            session("union_withdrawal_log",$gets);
        }
        $data['status']=session("union_withdrawal_log.status");
        $data['start_time']=session("union_withdrawal_log.start_time");
        $data['end_time']=session("union_withdrawal_log.end_time");
        $where="gid =". $id;
        $where.= $data['status'] != '-1' ? " and status=".$data['status']:'';
        $where.= $data['start_time'] ? " and addtime >=".strtotime($data['start_time']." 00:00:00"):'';
        $where.= $data['end_time'] ? " and addtime <=".strtotime($data['end_time']." 24:00:00"):'';

        $list=db('guild_withdrawal_log')->where($where)->order("addtime desc") ->paginate(12, false);

        $this->assign('result', $data);
        $this->assign('list', $list);
        $this->assign('page', $list->render());

        return $this->fetch();
    }
    //公会收益记录
    public function earnings_log(){
        $id = session('union.id');

        if(!input("page")){
            $gets['uid']=input("uid") ? input("uid") :'';
            $gets['hid']=input("hid") ? input("hid") :'';
            $gets['start_time']=input("start_time");
            $gets['end_time']=input("end_time");
            session("union_earnings_log",$gets);
        }
        $data['uid']=session("union_earnings_log.uid");
        $data['hid']=session("union_earnings_log.hid");
        $data['start_time']=session("union_earnings_log.start_time");
        $data['end_time']=session("union_earnings_log.end_time");


        $where="l.guild_id =". $id;
        $where.= $data['uid'] ? " and u.id=".$data['uid']:'';
        $where.= $data['hid'] ? " and l.user_id=".$data['hid']:'';
        $where.= $data['start_time'] ? " and addtime >=".strtotime($data['start_time']." 00:00:00"):'';
        $where.= $data['end_time'] ? " and addtime <=".strtotime($data['end_time']." 24:00:00"):'';


        $data_list = db('guild_log')->alias('l')
            ->join('user_consume_log c', 'c.id=l.consume_log')
            ->join('user u', 'u.id=c.user_id')
            ->join('user h', 'h.id=l.user_id')
            ->where($where)
            ->field('c.user_id as uid,c.coin as ucoin,c.content,l.*,u.user_nickname as uname,h.user_nickname as hname')
            ->order('l.addtime desc')
            ->paginate(20, false);

        $this->assign('result', $data);
        $this->assign('list', $data_list);
        $this->assign('page', $data_list->render());
        return $this->fetch();
    }
}

?>