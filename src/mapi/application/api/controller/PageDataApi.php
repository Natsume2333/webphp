<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/3
 * Time: 10:59
 */

namespace app\api\controller;

use think\helper\Time;
use UserOnlineStateRedis;

// +----------------------------------------------------------------------
// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class PageDataApi
{
    //首页轮播图
    public function shuffling()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $shuffling = intval(input('param.shuffling'));

        $where = "slide_id=$shuffling and status=1";
        $filed = 'id,image,title,url,is_auth_info';
        $order = "list_order desc";

        $img = db('slide_item')->where($where)->order($order)->field($filed)->select();

        $result['data'] = $img;

        return_json_encode($result);
    }
    //觅友列表
    public function find_friends(){
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $page = intval(input('param.page'));
        $user_info = get_user_base_info($uid);
        $where="u.user_status!=0";
        if ($user_info['sex'] == 2) {
            $where .= " and u.sex=1";
        } else {
            $where .= " and u.sex=2";
        }
      
        $field = 'u.id,u.sex,u.user_nickname,u.avatar,u.level,u.is_online,u.custom_video_charging_coin,u.signature,u.vip_end_time,u.province,u.city,u.birthday';
        $order = "u.is_online desc,u.sort desc,u.level desc";
      
        $user_list = db('user')->alias('u')->field($field)->where($where)->order($order)->page($page)->select();

        foreach ($user_list as &$v) {
            $v['user_img'] = Db('user_img')->where("uid=".$v['id']." and status=1")->select();
            $is_att = db('user_attention')->where('uid='.$uid.' and attention_uid='.$v['id'])->find();         //获取是否关注
            $v['is_focus'] =$is_att ? 1:0;           //是否关注过
        }
        $result['data'] = emcee_complete($user_list);
        return_json_encode($result);

    }
    //首页推荐用户
    public function recommend_user()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));

        $config = load_cache('config');

        //是否展示离线用户
        if ($config['is_show_offline_user'] == 1) {
            $where = "a.user_status!=0 and a.id!=1 and a.reference=1 and a.is_auth=1";
        } else {
            $where = "a.user_status!=0 and a.id!=1 and a.reference=1 and a.is_auth=1 and a.is_online=1";
        }
        $where = "a.user_status!=0 and a.id!=1 and a.reference=1 and a.is_auth=1";
        //字段
        $field = 'a.id,a.sex,a.user_nickname,a.avatar,a.level,a.custom_video_charging_coin,a.signature,a.is_online,a.address,a.vip_end_time';
        //排序
        $order = "g.orderno desc";

        $sorts = db('hot_sort')->where("status=1")->order("sort desc")->select();
        foreach ($sorts as $k => $v) {
           switch ($v['fields']){
            case 'is_online':
                $order.=",a.is_online desc";
              break;  
            case 'level':
                $order.=",a.level desc";
                break;
            case 'income_total':
               $order.=",a.income_total desc";
               break;
            case 'distance':   //距离
                $user = db('user')->where("id=".$uid)->find();

                $lat = $user['lat'];
                $lng = $user['lng'];
                if($lat){
                   $order.=",distance asc";
                     $field.=',(2 * 6378.137* ASIN(SQRT(POW(SIN(PI()*(111.86141967773438-a.lng)/360),2)+COS(PI()*33.07078170776367/180)* COS(a.lat * PI()/180)*POW(SIN(PI()*(33.07078170776367-a.lat)/360),2))))  as distance';
                }
              break; 
            case 'call_length':     //通话时长排序
                $order.=",call_length desc";
                $field.=",sum(o.time) as call_length";
              break;
            case 'fans':   //粉丝排序
                //$order.=",fans desc";
                //$field.=",sum(t.id) as fans";
				$field.=",0 as fans";
              break;
            default:
              $order.='';
            }
        }

        $list=db('user')->alias("a")
        ->join('online_record o', 'o.user_id=a.id','LEFT')
        //->join('(select * from cmf_user_attention where uid in (select id from cmf_user where is_online=1)) t', 't.attention_uid=a.id','LEFT')
        ->join('user_reference g', 'g.uid=a.id')
        ->field($field)
        ->where($where)
        ->order($order)
        ->group("a.id")
        ->page($page)
        ->select();


        $list = emcee_complete($list);

        $result['online_emcee_count'] = 0;
        $result['online_emcee'] = [];

        //获取在线主播人数
        $result['online_emcee'] = db('user')->alias('u')->field('u.id,u.avatar')->where('u.is_online', '=', 1)->where('u.sex', '=', 2)->where('u.is_auth', '=', 1)->limit(3)->select();
        $result['online_emcee_count'] = db('user')->where('is_online', '=', 1)->where('is_auth', '=', 1)->count();
        $result['data'] = $list;

        return_json_encode($result);
    }


    //获取我关注的主播
    public function request_get_follow_emcee_list()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));

        //获取关注列表id
        $attention_user = db('user_attention')->where("uid=$uid")->page($page)->select();
        if (!$attention_user) {
            return_json_encode($result);
        }

        $attention_ids = array();
        array_walk($attention_user, function ($value, $key) use (&$attention_ids) {
            $attention_ids[] = $value['attention_uid'];
        });

        $field = 'u.id,u.user_nickname,u.sex,u.user_status,u.avatar,u.address,u.is_online,u.custom_video_charging_coin,u.signature,u.level,u.vip_end_time';
        $order = 'u.income,u.coin desc';

        //->where('u.is_online', '=', 1)
        $user_list = db('user')->alias('u')->field($field)->where('u.user_status', 'neq', 0)->where('u.id', 'in', $attention_ids)
            ->order($order)
            ->page($page)
            ->select();

        $result['data'] = emcee_complete($user_list);
        return_json_encode($result);

    }

    //随机获取一键约爱的主播
    public function about_love()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $list = db('user')
            ->where('is_open_do_not_disturb !=1 and is_auth =1 and is_online=1 and sex=2 and id !=' . $uid)
            ->field("user_nickname,avatar,id")
            ->limit(10)
            ->order('rand()')
            ->select();
        $count = db('user')
            ->where('is_open_do_not_disturb !=1 and is_auth =1 and is_online=1 and sex=2 and id !=' . $uid)
            ->count();
        $data = [];
        $i = 0;

        foreach ($list as $k => $v) {
            $is_call = db('video_call_record')->where('anchor_id=' . $v['id'] . ' and status  > 1')->find();
            if (!$is_call) {
                $data[] = $v;
                if ($i == 2) {
                    break;
                }
                $i++;
            }
        }

        $result['data'] = $data;
        $result['count'] = $count;
        return_json_encode($result);
    }

    //首页在线
    public function request_get_index_online()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $page = intval(input('param.page'));

        $user_info = get_user_base_info($uid);

        if ($user_info['is_auth'] == 1) {
            $where = "u.user_status!=0 and u.is_online=1 and u.is_auth=0 and u.sex=1";
        } else {
            $where = "u.user_status!=0 and u.is_online=1 and u.is_auth=1 and u.sex=2";
        }

        $field = 'u.id,u.sex,u.user_nickname,u.avatar,u.level,u.is_online,u.custom_video_charging_coin,u.signature,u.vip_end_time';
        $order = "u.is_online desc,u.sort desc,u.level desc";

        $user_list = db('user')->alias('u')->field($field)->where($where)->order($order)->page($page)->select();

        $result['data'] = emcee_complete($user_list);
        return_json_encode($result);
    }

    //新人列表
    public function get_news_user_list()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $page = intval(input('param.page'));

        $user_info = get_user_base_info($uid);

        $time = Time::dayToNow(6);
        $config = load_cache('config');

        if ($user_info['is_auth'] == 1) {
            $where = 'u.user_status!=0 and u.create_time>' . $time[0] . ' and u.sex=1 ' . 'and u.is_auth=0';
        } else {
            $where = 'u.user_status!=0 and u.create_time>' . $time[0] . ' and u.sex=2 ' . 'and u.is_auth=1';
        }

        //是否展示离线用户
        if ($config['is_show_offline_user'] != 1) {
            $where .= ' and u.is_online=1';
        }

        $filed = 'u.id,u.user_nickname,u.sex,u.avatar,u.level,u.address,u.custom_video_charging_coin,u.signature,u.is_online,u.vip_end_time';
        $order = 'u.is_online desc,u.create_time desc';

        $user_list = db('user')->alias('u')->where($where)->field($filed)->order($order)->page($page)->select();

        $result['data'] = emcee_complete($user_list);

        return_json_encode($result);
    }

    //3x首页推荐接口
    public function recommend_user_3x()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));

        $config = load_cache('config');

        //是否展示离线用户
        if ($config['is_show_offline_user'] == 1) {
            $where = "a.user_status!=0 and a.id!=1 and a.reference=1 and a.is_auth=1";
        } else {
            $where = "a.user_status!=0 and a.id!=1 and a.reference=1 and a.is_auth=1 and a.is_online=1";
        }

        //字段
        $field = 'a.id,a.sex,a.user_nickname,a.avatar,a.level,a.custom_video_charging_coin,a.signature,a.is_online,a.address,a.vip_end_time';
        //排序
        $order = "a.sort desc";
     
        $sorts = db('hot_sort')->where("status=1")->order("sort desc")->select();
        foreach ($sorts as $k => $v) {
           switch ($v['fields']){
            case 'is_online':
                $order.=",a.is_online desc";
              break;  
            case 'level':
                $order.=",a.level desc";
                break;
            case 'income_total':
               $order.=",a.income_total desc";
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
                $field.=",sum(t.id) as fans";
              break;
            default:
              $order.='';
            }
        }
      

        $list=db('user')->alias("a")
        ->join('online_record o', 'o.user_id=a.id','LEFT')
        ->join('(select * from cmf_user_attention where uid in (select id from cmf_user where is_online=1)) t', 't.attention_uid=a.id','LEFT')
        ->field($field)
        ->where($where)
        ->order($order)
        ->group("a.id")
        ->page($page)
        ->select();

    //    $list = db('user')->alias('a')->field($field)->where($where)->order($order)->page($page)->select();

        $list = user_info_complete($list);

        $result['online_emcee_count'] = 0;
        $result['online_emcee'] = [];

        //获取在线主播人数
        $result['online_emcee'] = db('user')->alias('u')->field('u.id,u.avatar')->where('u.is_online', '=', 1)->where('u.is_auth', '=', 1)->limit(3)->select();

        $result['online_emcee_count'] = db('user')->where('is_online', '=', 1)->where('is_auth', '=', 1)->count();
        $result['data'] = emcee_complete($list);

        return_json_encode($result);
    }


    //附近的人
    public function nearby_user()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
	


        $user_info = check_login_token($uid, $token, ['lat', 'lng']);
        $lat = $user_info['lat'];
        $lng = $user_info['lng'];
        if($lat){
            if ($user_info['sex'] == 1) {
                $where = 'sex=2';
            } else {
                $where = 'sex=1';
            }
            $config = load_cache('config');
            
            //以当前用户经纬度为中心,查询5000米内的其他用户
            // $y =$config['latitude_range'] > 0 ? $config['latitude_range'] / 110852 : 0 ; //纬度的范围
            // $x =$config['latitude_range'] > 0 ? $config['latitude_range'] / (111320 * cos($lat)) : 0 ; //经度的范围
			
			$y = 5000 / 110852; //纬度的范围
            $x = 5000 / (111320 * cos($lat)); //经度的范围
            
            if($x<0){
                $x=-$x;
            }
            if($y<0){
                $y=-$y;
            }
			
            $where .= " and lat >= ($lat-$y) and lat <= ($lat+$y) and lng >= ($lng-$x) and lng <= ($lng+$x)";

            $nearby = db('user')
                ->where($where)
                ->field('id,user_nickname,avatar,sex,address,signature,lat,lng,level,vip_end_time,is_online')
                ->order("last_login_time desc")
                ->page($page)
                ->select();

            foreach ($nearby as $key=>$v) {
                $nearby[$key]['distance'] = '距离' . $this->distance($lat, $lng, $v['lat'], $v['lng']) . '米内';
            }
        
            $result['data'] = emcee_complete($nearby);
        }

        return_json_encode($result);
    }
    
    function distance($lat1, $lng1, $lat2, $lng2, $miles = true)
	{
	 $pi80 = M_PI / 180;
	 $lat1 *= $pi80;
	 $lng1 *= $pi80;
	 $lat2 *= $pi80;
	 $lng2 *= $pi80;
	 $r = 6372.797; // mean radius of Earth in km
	 $dlat = $lat2 - $lat1;
	 $dlng = $lng2 - $lng1;
	 $a = sin($dlat/2)*sin($dlat/2)+cos($lat1)*cos($lat2)*sin($dlng/2)*sin($dlng/2);
	 $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
	 $km = $r * $c;
	 return ($miles ? ($km * 0.621371192) : $km);
	}


    //获取地区主播
    public function get_area_list()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $page = intval(input('param.page'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $city = trim(input('param.city'));
        $province = trim(input('param.province'));

        $user_info = check_login_token($uid, $token);

        $config = load_cache('config');

        //是否展示离线用户
        if ($config['is_show_offline_user'] == 1) {
            $where = "a.user_status!=0 and id!=1 and a.is_auth=1";
        } else {
            $where = "a.user_status!=0 and id!=1 and a.is_auth=1 and a.is_online=1";
        }

        if (!empty($province)) {
            $where .= " and a.province = '{$province}'";
        }

        if (!empty($city)) {
            $where .= " and a.city = '{$city}'";
        }

        //字段
        $field = 'a.province,a.city,a.id,a.sex,a.user_nickname,a.avatar,a.level,a.custom_video_charging_coin,a.signature,a.is_online,a.vip_end_time';
        //排序
        $order = "a.province,a.city,a.sort desc,a.is_online desc,a.level desc,a.income_total desc";

        $list = db('user')->alias('a')->field($field)->where($where)->order($order)->page($page)->select();

        $list = emcee_complete($list);

        $result['data'] = $list;
        return_json_encode($result);
    }

    //TODO 获取星级主播列表
    public function request_get_star_emcee_list()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $level_id = intval(input('param.level_id'));
        $page = intval(input('param.page'));

        //查询等级
        $level = db('user_star_level')->find($level_id);

        $user_list = db('user')
            ->alias('u')
            ->field('u.id,u.user_nickname,u.sex,u.user_status,u.avatar,u.address,u.custom_video_charging_coin,u.signature,u.vip_end_time')
            ->where('u.income_total', '>=', $level['min_income'])
            ->where('u.income_total', '<=', $level['max_income'])
            ->where('u.sex', '=', 2)
            ->order('u.income desc')
            ->page($page)
            ->select();

        $result['data'] = emcee_complete($user_list);
        return_json_encode($result);
    }

    //搜索
    public function request_search()
    {
        $result = array('code' => 1, 'msg' => '');
        $key_word = trim(input('param.key_word'));
        $uid = intval(input('param.uid'));
        $user_search = db('user_search')->where("key_word='".$key_word."' and uid=".$uid)->find();
        if(!$user_search){
            $data=array(
                'uid'      =>$uid,
                'key_word' =>$key_word,
                'addtime'  =>NOW_TIME,
            );
            db('user_search')->insert($data);
        }

        $result['list'] = db('user')->field('avatar,id,user_nickname,address,city')
            ->where('user_status', 'neq', 0)
            ->where('id', 'like', '%' . $key_word . '%')
            ->whereOr('user_nickname', 'like', '%' . $key_word . '%')->select();
      
        return_json_encode($result);
    }
    //获取搜索记录
    public function search_log(){
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $result['list'] = db('user_search')->field("key_word")->where("uid=".$uid)->select();
        
        return_json_encode($result);
    }
    //获取评价标签列表
    public function request_get_evaluate_list()
    {
        $result = array('code' => 1, 'msg' => '', 'list' => array());
        $uid = intval(input('param.uid'));

        $user_info = get_user_base_info($uid);

        $type = $user_info['sex'] == 2 ? 1 : 0;
        $result['list'] = db('evaluate_label')->where('type', '=', $type)->select();
        return_json_encode($result);
    }


    //视频聊页面其他数据
    public function request_get_video_chat_page_data()
    {
        $result = array('code' => 1, 'msg' => '');

        $img = db('slide_item')->where("slide_id=4 and status=1")->order("list_order desc")->field('id,image,title,url')->select();
        $result['slide'] = $img;

        return_json_encode($result);
    }

    //获取财富页相关信息
    public function get_wealth_page_info()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['score', 'coin_system', 'income']);
        //用户聊币月
        $result['coin'] = $user_info['coin'];
        $config = load_cache('config');
        //分成比例
        $result['split'] = ($config['invite_income_ratio'] * 100);
        //总收益
        $result['income'] = $user_info['income'];

        return_json_encode($result);
    }

    //获取视频页面信息
    public function get_video_page_info()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $video_id = intval(input('param.video_id'));
        $user_info = check_login_token($uid, $token);
        $result['user_info'] = get_user_base_info($video_id);
        return_json_encode($result);
    }

    //获取礼物列表
    public function get_gift_list()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $gift_list = load_cache('gift');
        $result['list'] = $gift_list;
        return_json_encode($result);
    }

    //获取背包礼物列表
    public function get_bag_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $bagList = db('user_bag')->alias('u')->join('gift g', 'u.giftid=g.id')->field('u.giftnum,g.img,g.id')
            ->where('u.uid = ' . $uid . ' and giftnum > 0')
            ->select();

        $result['list'] = $bagList;
        return_json_encode($result);

    }

    //我的收益
    public function get_user_income_page_info()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));

        $user_info = check_login_token($uid, $token, ['score', 'coin_system', 'income']);

        $config = load_cache('config');

        $result['income'] = $user_info['income'];
        $result['money'] = 0;
        if ($result['income'] > 0) {
            $result['money'] = number_format($result['income'] / $config['integral_withdrawal'], 2);
        }

        $result['list'] = db('user_cash_record')->where('user_id', '=', $uid)->page($page)->order('create_time desc')->select();

        foreach ($result['list'] as &$v) {
            $v['create_time'] = date('Y年m月d日', $v['create_time']);

            if ($v['status'] == 0) {
                $v['status'] = '审核中';
            } else if ($v['status'] == 1) {
                $v['status'] = '提现成功';
            } else {
                $v['status'] = '提现失败，请联系客服！';
            }
        }
        return_json_encode($result);
    }

    //举报类型
    public function get_report_type()
    {
        $result = array('code' => 1, 'msg' => '', 'list' => []);

        $result['list'] = db('user_report_type')->select();
        return_json_encode($result);

    }

    //加盟合作
    public function join_in()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $type = trim(input('param.type')); //加盟方式
        $contact = trim(input('param.contact')); //联系方式

        $user_info = check_login_token($uid, $token, ['score', 'coin_system', 'income']);

        $is_exits = db('join_in')->where('user_id', '=', $uid)->find();
        if ($is_exits) {
            $result['msg'] = '已经提交,请耐心等待';
            $result['code'] = 0;
            return_json_encode($result);
        }
        if (!$type) {
            $result['msg'] = '请选择合作类型';
            $result['code'] = 0;
            return_json_encode($result);
        }
        /*$join_in_type = db('join_in_type')->where('id=' . $type)->find();
        if (!$join_in_type) {
            $result['msg'] = '合作类型参数错误';
            $result['code'] = 0;
            return_json_encode($result);
        }*/

        $data = ['type' => $type, 'contact' => $contact, 'user_id' => $uid, 'create_time' => NOW_TIME];
        db('join_in')->insert($data);

        return_json_encode($result);

    }

    //加盟合作类型
    public function join_in_type()
    {

        $result = array('code' => 1, 'msg' => '');

        $data = db('join_in_type')->field("id,name")->where('status=1')->order("sort desc")->select();

        $result['data'] = $data;

        return_json_encode($result);

    }

    //黑名单
    public function black_list()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $p = intval(input('param.page'));

        $user_info = check_login_token($uid, $token);

        $list = db('user_black')
            ->alias('b')
            ->field('u.user_nickname,u.id,u.avatar')
            ->join('user u', 'u.id=b.black_user_id')
            ->where('b.user_id', '=', $uid)
            ->page($p)
            ->select();

        $result['list'] = $list;

        return_json_encode($result);
    }

    //视频聊获取在线男性用户
    public function get_online_user()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $config = load_cache('config');
        //时间
        $time = NOW_TIME - $config['heartbeat_interval'] - 5; //偏移量5秒

        //查询在线的三个用户
        $online_user = db('monitor')
            ->alias('m')
            ->field('u.id,u.user_nickname,u.avatar,u.sex')
            ->join('user u', 'm.user_id=u.id')
            ->where('monitor_time', '>', $time)
            ->where('m.user_id', 'neq', $uid)
            ->where('u.sex', '=', 1)
            ->limit(3)
            ->select();

        foreach ($online_user as &$v) {
            $v['is_follow'] = 0;
            $focus = db('user_attention')->where("uid=$uid and attention_uid=" . $v['id'])->find();
            if ($focus) {
                $v['is_follow'] = 1;
            }
            $v['is_online'] = is_online($v['id'], $config['heartbeat_interval']); //获取主播是否登录
        }

        $result['list'] = $online_user;
        $result['online_count'] = db('monitor')->where('monitor_time', '>', $time)->count();

        return_json_encode($result);

    }

    //预约列表
    public function get_my_subscribe_user_list()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $action = intval(input('param.action'));

        $user_info = check_login_token($uid, $token);

        //我预约的
        $where = '';
        if ($action == 1) {
            $where = 'v.user_id=' . $uid;
            $join_users="v.to_user_id=u.id";
        } else if ($action == 2) {
            $where = 'v.to_user_id=' . $uid;
            $join_users="v.user_id=u.id";
        }

        //预约我的
        $video_call_subscribe_list = db('video_call_subscribe')
            ->alias('v')
            ->where($where)
            ->join('user u', $join_users)
            ->field('v.*,u.avatar,u.user_nickname,u.is_online')
            ->order('v.create_time desc')
            ->select();

        foreach ($video_call_subscribe_list as &$v) {
            $time = intval($v['create_time']) + 24*60*60;
            $v['type']= $action == 1 && $time < NOW_TIME && $v['status'] == 0 ? 1 : 0;  //预约用户是否超过24小时

            $v['create_time'] = date('Y-m-d H:i', $v['create_time']);
            if ($v['status'] == 0) {
                $v['status_msg'] = '等待回拨';
            } else if ($v['status'] == 1) {
                $v['status_msg'] = '主播拒绝';
            } else if ($v['status'] == 2) {
                $v['status_msg'] = '已拨打完成';
            } else if ($v['status'] == 3) {
                $v['status_msg'] = '已超时';
            }
        }

        $result['list'] = $video_call_subscribe_list;

        return_json_encode($result);
    }

    //获取一键打招呼列表
    public function one_key_see_hi_list()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $page = intval(input('param.page'));

        $user_info = check_login_token($uid, $token);

        $config = load_cache('config');

        $where = "a.user_status!=0 and id!=1 and a.reference=1 and a.is_auth=1";

        //字段
        $field = 'a.id,a.sex,a.user_nickname,a.avatar,a.level,a.custom_video_charging_coin,a.signature,a.is_online';
        //排序
        $order = "a.sort desc,a.is_online desc,a.level desc,a.income_total desc";

        $list = db('user')->alias('a')->field($field)->where($where)->order($order)->page($page)->select();

        $result['list'] = $list;
        return_json_encode($result);
    }

    //一键打招呼操作
    public function request_one_key_see_hi()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = input('param.token');
        $page = intval(input('param.page'));

        $user_info = check_login_token($uid, $token);

        $where = "a.user_status!=0 and id!=1 and a.reference=1 and a.is_auth=1";

        //字段
        $field = 'a.id,a.sex,a.user_nickname,a.avatar,a.level,a.custom_video_charging_coin,a.signature,a.is_online';
        //排序
        $order = "a.sort desc,a.is_online desc,a.level desc,a.income_total desc";

        $list = db('user')->alias('a')->field($field)->where($where)->order($order)->page($page)->select();

        require_once DOCUMENT_ROOT . '/system/im_common.php';
        foreach ($list as $v) {
            send_c2c_text_msg($uid, $v['id'], 'Hi,美女！');
        }

        return_json_encode($result);

    }


    //TODO 获取金币打赏规则
    public function get_coin_reward_rule()
    {
        $result = array('code' => 1, 'msg' => '', 'list' => array());
        $result['list'] = db('reward_coin_rule')->field('id,reward_coin_num')->select();

        return_json_encode($result);
    }

}