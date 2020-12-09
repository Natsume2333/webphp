<?php

namespace app\api\controller;

use think\Db;
use think\helper\Time;

class FindFriendsApi extends Base
{
	//滑动觅友喜欢和不喜欢
	public function add_user_like(){
		$result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $touid = intval(input('param.touid'));
        $type = input('param.type');
        $user_info = check_login_token($uid, $token,['lat','lng']);
        $to_user_info = get_user_base_info($touid);
        if(empty($to_user_info)){
            $result['code'] = 0;
            $result['msg'] = '用户不存在';
            return_json_encode($result);
            exit;
        }

        $user_like_info = db('user_like')->where(['uid'=>$uid,'touid'=>$touid,'type'=>$type])->find();
        $data = [
            'uid'=>$uid,
            'touid'=>$touid,
            'edittime'=>time(),
            'type'=>$type,
        ];
        if(empty($user_like_info) && $type==1){
            $no_like = db('user_like')->where(['uid'=>$uid,'touid'=>$touid,'type'=>2])->find();
            if(empty($no_like)){
                $data['addtime'] = time();
                $res = db('user_like')->insert($data);
                        //喜欢加关注
                $attention = db('user_attention')->where("uid=$uid and attention_uid=$touid")->find();
	            if (!$attention) {
	                $data = array(
	                  'uid' => $uid,
	                  'attention_uid' => $touid,
	                  'addtime' => NOW_TIME
	                );
	                $atte = db('user_attention')->insert($data);
	            }
                
            }else{
                $updata = ['type'=>1,'edittime'=>time(),];
                $res = db('user_like')->where('id',$no_like['id'])->update($updata);
            }
            
        }else if(empty($user_like_info) && $type==2){

           $res = db('user_like')->where("uid=".$uid." and touid=".$touid." and type=1")->delete();
        }
        return_json_encode($result);
	}
	//觅友我喜欢和喜欢我的列表
	public function get_like(){
		$result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $type = intval(input('param.type')) ==1 ? 1:2;    //1我喜欢的 2喜欢我的
        $user_info = check_login_token($uid, $token,['lat','lng']);
      	
        $join=$type ==1 ? 'u.id=l.touid' : 'u.id=l.uid';
        $field='u.id,u.user_nickname,u.avatar,u.sex,u.level,u.lat,u.lng';
        $where=$type ==1 ? "l.uid=".$uid : "l.touid=".$uid;

        //喜欢的人和被喜欢的人
        $user = db('user_like')
            ->alias('l')
            ->join('user u',$join)
            ->field($field)
            ->where($where)
            ->select();

        foreach ($user as &$v) {
           $v['distance'] =round(distance($user_info['lat'], $user_info['lng'], $v['lat'], $v['lng'])*0.001,2). '公里';
  
        }
        $result['data'] = $user;

        return_json_encode($result);


	}
}
?>
