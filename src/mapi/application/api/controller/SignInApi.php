<?php
namespace app\api\controller;

use QcloudApi;
use think\Db;
use think\helper\Time;

class SignInApi extends Base
{
        //是否开启签到功能
    public function is_sign_in(){
        $result = array('code' => 1, 'msg' => '','is_sign_in'=>'0');
	
        $config = load_cache('config');
        if($config['open_sign_in'] !=1){
             $result['is_sign_in']= '2';  //签到功能已关闭
            return_json_encode($result);
        }
        $uid = input('param.uid');
        $token = input('param.token');
        if (empty($uid) || empty($token)) {
            $result['msg']= '传参错误';
           return_json_encode($result);
        }
        $user_info = Db::name("user")->where("id=$uid and token='$token'")->field("sex")->find();
        if (empty($user_info)) {
            $result['msg']= '登录过期，请重新登录！';
            return_json_encode($result);
        }
         //获取最后一次签到的时间 
        $user_sign_in = Db::name('user_sign_in')->where("uid=".$uid)->order("create_time desc")->find();
        if($user_sign_in['create_time'] >= strtotime(date('Y-m-d',time())." 00:00:00")){
            $result['is_sign_in']= '1';  //今日已签到
            return_json_encode($result);
        }
        return_json_encode($result);
    }
  //签到列表
    public function index(){
         $root = array();
        $uid = input('param.uid');
        $token = input('param.token');
        if (empty($uid) || empty($token)) {
            echo '传参错误';
            exit;
        }
        $user_info = Db::name("user")->where("id=$uid")->field("sex")->find();
        if (empty($user_info)) {
            echo '登录过期，请重新登录！';
            exit;
        }
        //获取签到的列表
        $list = Db::name('sign_in_list')->where("status=1")->order("sort desc")->select();
        
        $day=count($list);                                                         //获取签到奖励天数
       
        $user_sign_in_one = Db::name('user_sign_in')->where("uid=".$uid)->order("create_time desc")->find();
       $user_sign_in =[];
       if($user_sign_in_one){
             $limit=$user_sign_in_one['day'];
             $date = strtotime(date('Y-m-d', strtotime('-'.$day.' days'))." 00:00:00");  //获取签到的开始时间
             $count = Db::name('user_sign_in')->where("uid=".$uid." and create_time >".$date)->count();

             if(($day - $user_sign_in_one['day'])!=1 || $count == $user_sign_in_one['day'] ){     //判断倒数第二天

                 $user_sign_in = Db::name('user_sign_in')->where("uid=".$uid." and create_time >".$date)->order("create_time desc")->limit($limit)->select();
             }
            
       }
   
        $array=[];
        $is_day_sign=0;            //今天是否签到过
        foreach($user_sign_in as $v){
            $array[]=$v['sign_id'];
            if($v['create_time'] >= strtotime(date('Y-m-d',time())." 00:00:00")){  //获取最后一次签到的时间 
                 $is_day_sign=1;   
            }
        }
        foreach($list as &$v){
            $v['is_receive']= in_array($v['id'],$array) ? 1 :0;
        }
        
        $this->assign('list', $list);
        $this->assign('is_day_sign', $is_day_sign);
        $this->assign('uid', $uid);
        $this->assign('token', $token);
        $this->assign('day', $day);
        return $this->fetch();
    }
    //用户签到
    public function receive_sign(){
        $result = array('code' => 0, 'msg' => '签到失败');
        $uid = input('param.uid');
        $token = trim(input('param.token'));
        if (empty($uid) || empty($token)) {
            $result['msg']= '传参错误';
           return_json_encode($result);
        }
       
        
        $user_info = Db::name("user")->where("id=$uid")->field("sex,token")->find();
       
        if (empty($user_info)) {
             $result['msg']= '用户签到失败';
            return_json_encode($result);
        }
      
         //获取签到的列表
        $list = Db::name('sign_in_list')->where("status=1")->order("sort desc")->select();
        $day=count($list);                                                         //获取签到奖励天数
       
        //获取最后一次签到的时间 
        $user_sign_in = Db::name('user_sign_in')->where("uid=".$uid)->order("create_time desc")->find();
        if($user_sign_in['create_time'] >= strtotime(date('Y-m-d',time())." 00:00:00")){
            $result['msg']= '今日已签到';
            return_json_encode($result);
        }
       
        $is_receive=1;                    //没有签到过
        $diamonds=$list[0]['diamonds'];   //默认第一天
        $score=$list[0]['score'];           //默认第一天
        $sign_id=$list[0]['id'];            //默认第一天
        $is_last_day=0;                    //是否是最后一天
      
        if($user_sign_in){
            $date=floor((time() - $user_sign_in['create_time'])/86400);    //$date查询出已签到过的到今天相差几天
             
            if($date < $day){      //是否超出签到奖励范围
                for($i=0;$i < $day;$i++){
                    if($list[$i]['id'] == $user_sign_in['sign_id']){
                        
                        $is_receive_day=$i  + $date;                      //获取签到的天数 
                    
                        if(($is_receive_day + 1)< $day ){                 //判断当天签到数据
                         
                            $diamonds=$list[$i]['diamonds'];
                            $score=$list[$i]['score'];
                            $sign_id=$list[$i]['id'];
                            $is_receive=$is_receive_day;
                        }else{                                          //最后一天签到领取大奖
                            $start_time = strtotime(date('Y-m-d', strtotime('-'.$day.' days'))." 00:00:00");  //获取签到的开始时间
                            $count = Db::name('user_sign_in')->where("uid=".$uid." and create_time >".$start_time)->count();
                            
                            if(($day - intval($count)) ==1 ){    //获取连续签到的数量 判断是否是最后一天领取权限
                                $diamonds=$list[$i]['diamonds'];
                                $score=$list[$i]['score'];
                                $sign_id=$list[$i]['id'];
                                $is_last_day=1;                    //是最后一天
                            }
                        }
                    }
                }
            }
        }
    
        $data=array(
            'uid'          =>$uid,
            'sign_id'      =>$sign_id,
            'diamonds'     =>$diamonds,
            'score'        =>$score,
            'create_time'  =>time(),
            'create_y'     =>date('Y',time()),
            'create_m'     =>date('m',time()),
            'create_d'     =>date('d',time()),
            'day'          =>$is_receive,
        );    
      
        $result_user = Db::name("user_sign_in")->insert($data);       //增加签到数据
        if($result_user){
            //增加用户钻石
            Db::name('user')->where("id=$uid")->Inc("coin",$diamonds)->Inc("score",$score)->update();
            $result['code']= 1;
            $result['msg']= '';
            $result['is_last_day']= $is_last_day;
            $result['rewards']= "钻石+".$diamonds.",积分+".$score;
        }

        return_json_encode($result);   
    }

}