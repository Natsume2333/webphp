<?php
// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

// 应用公共文件
use Qiniu\Auth;
use Qiniu\Rtc\AppClient;
use Qiniu\Storage\UploadManager;
use think\Config;
use think\Db;
use think\helper\Time;
use think\Log;

//获取input int参数
function get_input_param_int($param_name)
{
    return intval(input('param.' . $param_name));
}

//获取input str参数
function get_input_param_str($param_name)
{
    return trim(input('param.' . $param_name));
}

//获取视频通话价格
function get_video_call_fee($user_id = 0, $custom_video_charging_coin = 0)
{
    $config = load_cache('config');
    $video_deduction = $config['video_deduction'];
    if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
        $level = get_level($user_id);
        if ($level >= $config['custom_video_money_level'] && $custom_video_charging_coin != 0) {
            $video_deduction = $custom_video_charging_coin;
        }
    }

    return $video_deduction;
}

/**
 * @dw 邀请扣量
 * @param  $user_id 用户id
 * @return 0不扣量 1扣量
 * */
function get_bucket_invite($user_id, $type = 0)
{
    $user_info = get_user_base_info($user_id, ['create_time', 'invite_buckle_probability', 'invite_buckle_recharge_probability']);
    if (!$user_info) {
        return 0;
    }

    //邀请绑定扣单概率规则
    if ($type == 0) {
        $probability = $user_info['invite_buckle_probability'];

        if ($user_info['invite_buckle_probability'] == 0) {

            $day = (NOW_TIME - $user_info['create_time']) / (60 * 60 * 24);
            $day = intval($day);
            $rule = db('buckle_invite_rule')->where('upper_limit', '<=', $day)->where('lower_limit', '>=', $day)->order('upper_limit desc')->limit(1)->find();

            if (!$rule) {
                return 0;
            }
            $probability = $rule['probability'];
        }
    } else {
        //邀请充值扣单概率规则
        $probability = $user_info['invite_buckle_recharge_probability'];

        if ($user_info['invite_buckle_recharge_probability'] == 0) {

            $day = (NOW_TIME - $user_info['create_time']) / (60 * 60 * 24);
            $day = intval($day);
            $rule = db('buckle_invite_recharge_rule')->where('upper_limit', '<=', $day)->where('lower_limit', '>=', $day)->order('upper_limit desc')->limit(1)->find();

            if (!$rule) {
                return 0;
            }
            $probability = $rule['probability'];
        }
    }

    $arr = array(
        array('id' => 1, 'type' => '扣量', 'v' => $probability),
        array('id' => 0, 'type' => '不扣量', 'v' => 100 - $probability),
    );

    function get_rand($proArr)
    {
        $result = array();
        foreach ($proArr as $key => $val) {
            $arr[$key] = $val['v'];
        }
        // 概率数组的总概率
        $proSum = array_sum($arr);
        asort($arr);
        // 概率数组循环
        foreach ($arr as $k => $v) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $v) {
                $result = $proArr[$k];
                break;
            } else {
                $proSum -= $v;
            }
        }
        return $result;
    }

    return intval(get_rand($arr)['id']);

}

//录入设备信息
function device_info($os = '', $sdk = '', $app = '', $brand = '', $model = '', $uid)
{
    $user_info = db('device_info')->where("uid='$uid'")->find();
    $data = array(
        'os' => $os,
        'sdk_version' => $sdk,
        'app_version' => $app,
        'brand' => $brand,
        'model' => $model,
        'addtime' => time(),
    );
    if ($user_info) {
        $device_info = db('device_info')->where("id=" . $user_info['id'])->update($data);
    } else {
        $data['uid'] = $uid;
        $device_info = db('device_info')->insert($data);
    }

}

//测试自定义打招呼信息
function crontab_do_auto_talking_custom_tpl()
{

   
}

//对用户自动打招呼，自动发视频
function crontab_do_auto_talking()
{
    require_once(DOCUMENT_ROOT . '/system/im_common.php');
    $config = load_cache('config');
    if($config['is_open_auth_see_hi'] != 1){
        return;
    }

    //->where('is_open_auto_see_hi', '=', 1)
    $online_user = db('user')->where('coin', '=', 0)->where('is_online', '=', 1)->where('sex', '=', 1)->select();

    $auto_see_hi_msg_list = db('auto_talking_skill')->select();

    if (count($auto_see_hi_msg_list) > 0) {
        foreach ($online_user as $v) {

            $emcee = db('user')->where('is_online', '=', 1)->where('is_auth', '=', 1)->order('rand()')->find();
            if ($emcee) {
                $msg = $auto_see_hi_msg_list[rand(0, count($auto_see_hi_msg_list) - 1)]['content'];
                if (!empty($msg)) {
                    send_c2c_text_msg($emcee['id'], $v['id'], $msg);
                }
            }
        }
    }

//    $call_video_user = db('user')
//        ->where('is_online', '=', 1)
//        ->where('coin', '=', 0)
//        ->where('sex', '=', 1)
//        ->where('is_auth', '=', 0)
//        ->order('rand()')
//        ->limit(10)
//        ->select();
//
//
//    foreach ($call_video_user as $v) {
//        //查询是否有通话记录
//        $record = db('video_call_record')->where('user_id', '=', $v['id'])->whereOr('call_be_user_id', '=', $v['id'])->find();
//        if ($record) {
//            continue;
//        }
//
//        $emcee = db('user')->where('is_online', '=', 1)->where('sex', '=', 2)->where('is_auth', '=', 1)->order('rand()')->find();
//        if ($emcee) {
//            $ext = array();
//            $ext['type'] = 12;//type 12 一对一视频消息请求推送
//            $sender['id'] = $emcee['id'];
//            $sender['user_nickname'] = $emcee['user_nickname'];
//            $sender['avatar'] = $emcee['avatar'];
//            $ext['channel'] = 1111111;//通话频道
//            $ext['is_use_free'] = 0;
//            $ext['sender'] = $sender;
//            $ser = open_one_im_push($emcee['id'], $v['id'], $ext);
//            if ($ser['ActionStatus'] == 'OK') {
//                echo '拨打成功';
//            }
//        }
//    }

}

//自动随机拨打视频给用户
function crontab_do_auto_call_video()
{
    require_once(DOCUMENT_ROOT . '/system/im_common.php');


    $config = load_cache('config');
    if ($config['is_open_auth_see_hi'] != 1) {
        return;
    }
    $lock = $GLOBALS['redis']->get('bogokj:auto_see_hi_lock');
    if ($lock) {
        echo '未到时间：' . ($config['auto_say_hi_interval_time'] - (NOW_TIME - $lock));
        return;
    }
    echo '进入执行任务';

    $GLOBALS['redis']->set('bogokj:auto_see_hi_lock', NOW_TIME, $config['auto_say_hi_interval_time']);

    require_once(DOCUMENT_ROOT . '/system/im_common.php');

    //查询在线的男性用户  -随机获取10个满足条件的用户
    $online_user = db('user')->where('is_online', '=', 1)->where('sex', '=', 1)
        ->where('is_auth', '=', 0)
        ->where('coin', '<=', $config['auto_say_hi_coin_limit'])
        ->order('rand()')
        ->limit(1)
        ->select();

    $day_end_time = mktime(23, 59, 59, date("m"), date("d"), date("Y"));
    //开始时间戳
    $day_start = mktime(0, 0, 0, date("m", NOW_TIME), date("d", NOW_TIME), date("Y", NOW_TIME));

    foreach ($online_user as $v) {
        //查询今日对该用户的打招呼次数
        $day_count = db('auto_msg_record')->where('to_user_id', '=', $v['id'])->where('create_time', '>=', $day_start)->count();
        if ($day_count >= $config['auto_say_hi_day_count']) {
            continue;
        }

        //随机查询一个主播
        $emcee = db('user')->where('sex', '=', 2)->where('is_online', '=', 1)->where('is_auth', '=', 1)->order('rand()')->find();

        //查询今日该主播对该用户的打招呼次数
        $emcee_day_count = db('auto_msg_record')->where('to_user_id', '=', $v['id'])->where('create_time', '>=', $day_start)
            ->where('user_id', '=', $emcee['id'])->count();

        if ($emcee_day_count >= $config['auto_say_hi_day_emcee_count']) {
            continue;
        }

        if ($emcee) {
            if ($emcee['id'] == $v['id']) {
                continue;
            }
            //查询是否有通话记录
            $record = db('video_call_record')->where('user_id', '=', $v['id'])->whereOr('call_be_user_id', '=', $v['id'])->find();
            if ($record) {
                continue;
            }

            $ext = array();
            $ext['type'] = 12;//type 12 一对一视频消息请求推送
            $sender['id'] = $emcee['id'];
            $sender['user_nickname'] = $emcee['user_nickname'];
            $sender['avatar'] = $emcee['avatar'];
            $ext['channel'] = 1111111;//通话频道
            $ext['is_use_free'] = 0;
            $ext['sender'] = $sender;
            $ser = open_one_im_push($emcee['id'], $v['id'], $ext);
            if ($ser['ActionStatus'] == 'OK') {
                echo '拨打成功';
            }
        }
    }

}

//获取最大的ID
function get_max_user_id($mobile, $field = 'mobile')
{
    $exits = db('mb_user')->where(array($field => $mobile))->find();
    if ($exits) {
        return $exits['id'];
    }

    //注册
    $id = db('mb_user')->insertGetId(array($field => $mobile));
    if (db('user')->find($id)) {
        $id = db('mb_user')->insertGetId(array($field => $mobile));
    }

    return $id;
}

//获取登录token
function get_login_token($id)
{
    $token = md5($id . NOW_TIME . '3DW123@#$$$$@@');
    return $token;
}

//填写代理渠道
function reg_full_agent_code($user_id, $agent_code)
{
    $user_info = db('user')->field('link_id')->find($user_id);
    if (empty($user_info['link_id']) && !empty($agent_code)) {
        db('user')->where('id', '=', $user_id)->setField('link_id', $agent_code);
    }
}

//检查token是否过期
function check_login_token($uid, $token, $field = array())
{
    if ($uid == 0 || empty($token)) {
        $result['code'] = 0;
        $result['msg'] = '登录参数错误';
        return_json_encode($result);
    }

    $user_info = check_token($uid, $token, $field);

    if (!$user_info) {
        $result['code'] = 10001;
        $result['msg'] = '登录信息失效';
        return_json_encode($result);
    }

    //账号是否被禁用
    if ($user_info['user_status'] == 0) {
        $result['code'] = 10002;
        $result['msg'] = "您因涉嫌违规，账号受限，请联系管理员!";
        return_json_encode($result);
    }

    return $user_info;
}

//获取是否关注
function get_attention($uid, $to_user_id)
{
    $is_attention = db('user_attention')->where("uid=$uid")->where('attention_uid', '=', $to_user_id)->find();
    return $is_attention ? 1 : 0;
}

//获取是否拉黑
function get_is_black($uid, $to_user_id)
{
    $is_black = db('user_black')->where('user_id', '=', $uid)->where('black_user_id', '=', $to_user_id)->find();
    return $is_black ? 1 : 0;
}

//获取鉴权视频链接
//function get_sign_video_url($key, $video_url)
//{
//
//    $parse_url_arr = parse_url($video_url);
//    $url_dir = substr($parse_url_arr['path'], 0, strrpos($parse_url_arr['path'], '/') + 1);
//    $t = dechex(time() + 60 * 60 * 24);
//    $us = rand_str();
//    $sign = md5($key . $url_dir . $t . $us);
//
//    $sign_video_url = $video_url . '?t=' . $t . '&us=' . $us . '&sign=' . $sign;
//
//    return $sign_video_url;
//}

//根据条件获取主播列表
function emcee_complete($list)
{

    $config = load_cache('config');
    foreach ($list as &$v) {

        //$v['evaluation'] = db('video_call_record_log')->where('anchor_id', '=', $v['id'])->where('is_fabulous', '=', 1)->count(); //好评百分比
        $level = $v['level'];
        //$v['level'] = $level;
        //$v['is_online'] = is_online($v['id'], $config['heartbeat_interval']); //获取主播是否登录

        //分钟扣费金额
        $v['charging_coin'] = $config['video_deduction'];
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {

            if (isset($v['custom_video_charging_coin']) && $level >= $config['custom_video_money_level'] && $v['custom_video_charging_coin'] > 0) {
                $v['charging_coin'] = $v['custom_video_charging_coin'];
            }
        }

        if (isset($v['custom_video_charging_coin'])) {
            unset($v['custom_video_charging_coin']);
        }
        $v['is_vip']=0;
        if($v['sex'] ==1 && isset($v['vip_end_time']) && $v['vip_end_time'] > NOW_TIME){
             $v['is_vip']=1;
        }
        //认证信息
//		$auth_info = db('auth_form_record')->where('user_id', '=', $v['id'])->find();
//		$v['sign'] = '';
//		if ($auth_info) {
//			$v['sign'] = $auth_info['sign'];
//		}
        $v['sign'] = $v['signature'];
    }

    return $list;
}

//根据条件获取主播列表 x
function user_info_complete($list)
{

    $config = load_cache('config');
    foreach ($list as &$v) {

        $level = get_level($v['id']);
        $v['level'] = $level;

        //分钟扣费金额
        $v['charging_coin'] = $config['video_deduction'];
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {

            if (isset($v['custom_video_charging_coin']) && $level >= $config['custom_video_money_level'] && $v['custom_video_charging_coin'] > 0) {
                $v['charging_coin'] = $v['custom_video_charging_coin'];
            }
        }

        if (isset($v['custom_video_charging_coin'])) {
            unset($v['custom_video_charging_coin']);
        }

        //$v['sign'] = $v['signature'];

        //认证信息
        $auth_info = db('auth_form_record')->field('height')->where('user_id', '=', $v['id'])->find();

        if ($auth_info) {
            $v['height'] = $auth_info['height'] . 'CM';
        }

        $v['vip_price'] = $v['charging_coin'] / 2;
    }

    return $list;
}

//注册邀请业务处理
function reg_invite_service($uid, $invite_code)
{
    $invite_data['user_id'] = 0;
    $invite_data['invite_code'] = '';

    if (!empty($invite_code)) {

        $invite_code = db('invite_code')->where('invite_code', '=', $invite_code)->find();
        if ($invite_code && $invite_code['user_id'] != 0) {
            //是否扣量
            $is_buckle = get_bucket_invite($invite_code['user_id']);
            if ($is_buckle) {
                //增加扣量记录
                $invite_deduction_record = [
                    'user_id' => $invite_code['user_id'],
                    'invite_user_id' => $uid,
                    'create_time' => NOW_TIME,
                ];
                db('invite_reg_deduction_record')->insert($invite_deduction_record);
                return 0;
            }
        }

        if ($invite_code && $invite_code['user_id'] != $uid) {
            $invite_data['user_id'] = $invite_code['user_id'];
            $invite_data['invite_code'] = $invite_code['invite_code'];
            $invite_data['invite_user_id'] = $uid;
            $invite_data['create_time'] = NOW_TIME;
        }
    }

    //添加邀请记录
    return db('invite_record')->insert($invite_data);

}


//随机获取一个空闲主播
function get_rand_emcee($uid, $max_count = 5, $count = 0)
{
    if ($max_count == $count) {
        return 0;
    }
    $monitor = db('user')
        ->where('is_open_do_not_disturb', 'neq', 1)
        ->where('is_auth', '=', 1)
        ->where('is_online', '=', 1)
        ->where('id', '<>', $uid)
        ->limit(1)
        ->order('rand()')
        ->find();
    if (!$monitor) {
        return 0;
    }
    $is_call = db('video_call_record')->where('anchor_id', '=', $monitor['id'])->find();
    if ($is_call) {
        $count++;
        get_rand_emcee($uid, $max_count, $count);
    } else {
        return $monitor['id'];
    }
}

/**
 * 记录错误日志
 * @param 日志内容 $res
 */
function save_log($res)
{
    $err_date = date("Ym", time());

    $err_info['msg'] = "错误：" . $res['msg'];
    $err_info['error'] = $res['error'];
    $err_info['post'] = $_POST;
    $err_info['get'] = $_GET;

    $request_url = 'http://' . $_SERVER['SERVER_NAME'] . ':' . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
    $err_info['url'] = $request_url;

    //$address = '/var/log/error';
    $address = 'public/errorlog';
    if (!is_dir($address)) {
        mkdir($address, 0700, true);
    }
    $address = $address . '/' . $err_date . '_error.log';

    $error_date = date("Y-m-d H:i:s", time());
    if (!empty($_SERVER['HTTP_REFERER'])) {
        $file = $_SERVER['HTTP_REFERER'];
    } else {
        $file = $_SERVER['REQUEST_URI'];
    }

    $res_real = "$error_date\t$file\t\n";
    error_log($res_real, 3, $address);
    $res = var_export($err_info, true);
    $res = $res . "\r\n";
    error_log($res, 3, $address);
}

//参数检查
function check_param($param)
{
    $result['code'] = 0;
    $result['msg'] = "传参错误：" . $param;
    $data = array('msg' => "传参错误", 'error' => $param);

    return_json_encode($result);
}

//封装json_encode()
function return_json_encode($result)
{
    json($result)->send();
    //echo json_encode($result);
    exit;
}

//获取配置信息
function get_config()
{
    $config_res = db('config')->select();
    //var_dump($config_res);exit;
    $config = array_reduce($config_res, function (&$config, $v) {
        $config[$v['code']] = $v;
        return $config;
    });

    return $config;
}

//检测token
function check_token($user_id, $token, $field = [])
{

    $base_field = 'user_nickname,id,coin,sex,avatar,user_status,level,address,signature,is_auth';
    if (is_array($field) && count($field) > 0) {
        $base_field .= ',' . implode(',', $field);
    }

    $user_info = db('user')
        ->field($base_field)
        ->where(['id' => $user_id, 'token' => $token])
        ->find();

    return $user_info;
}


/**
 * @dw 增加主播收益
 * @param $to_user_id 主播ID
 * @param $income 收益数量
 * */

function add_income($to_user_id = 0, $income = 0)
{
    if ($to_user_id == 0 || $income == 0) {
        return 0;
    }

    $res = db('user')->where('id', '=', $to_user_id)
        ->inc('income', $income)
        ->inc('income_total', $income)
        ->update();

    return $res;
}

/**
 * @dw 扣除用户余额
 * @param $to_user_id 用户ID
 * @param $income 金币数量
 * */
function del_coin($to_user_id = 0, $coin = 0)
{
    if ($to_user_id == 0 || $coin == 0) {
        return 0;
    }

    $res = db('user')->where(['id' => $to_user_id])->setDec('coin', $coin);

    return $res;
}

/**
 * @dw主播收益提成比例
 * @param type 消费类型
 * @param coin 消费的金额
 * @param uid  主播id
 * @param type=1 一对一购买视频分成比例  host_bay_video_proportion
 * @param type =2 购买私照分成比例  host_bay_phone_proportion
 * @param type =3 赠送礼物分成比例  host_bay_gift_proportion
 * @param type=4 一对一视频通话 host_one_video_proportion
 * @param type=5私信消息分成比例  host_direct_messages
 * @param type=8购买联系方式分成比例  host_bay_contact_proportion
 * @return 返回的是主播的收益
 */
function host_income_commission($type, $coin = 0, $uid = 0)
{

    switch ($type) {
        case 1:
            $filed = 'host_bay_video_proportion';
            break;
        case 2:
            $filed = 'host_bay_phone_proportion';
            break;
        case 3:
            $filed = 'host_bay_gift_proportion';
            break;
        case 4:
            $filed = 'host_one_video_proportion';
            break;
        case 5:
            $filed = 'host_direct_messages';
            break;
        case 6:
            $filed = 'host_guardian_proportion';
            break;
        case 7:
            $filed = 'host_turntable_ratio';
            break;
        case 8:
            $filed = 'host_bay_contact_proportion';
            break;
        default:
            $filed = '';
    }

    if (empty($filed)) {
        return 0;
    }

    $host = db('user')->where('id', '=', $uid)->field($filed)->find();

    $ratio = 0;
    if ($host && isset($host[$filed]) && $host[$filed] != 0) {
        $ratio = $host[$filed];
    } else {
        $config = load_cache('config');
        if ($config && isset($config[$filed]) && $config[$filed] != 0) {
            $ratio = $config[$filed];
        }
    }

    $invite_coin = round($coin * $ratio, 2);   //主播收益
   

    return $invite_coin;
}
/*
*公会收益记录
*uid 主播id  table_id记录表id type 1礼物表2通话3私聊   coin主播总收益(包括公会收益) host_coin(主播实际收益)
*/
function add_guild_log($uid,$table_id,$type,$coin,$host_coin,$log_id){
   //获取是否有公会提成
    $guild=db('guild')->alias('g')->field("g.commission,g.type,g.id")->join('guild_join u', 'u.guild_id=g.id')->where("u.user_id=".$uid." and u.status=1")->find();
	
    $insert_id =0;
    if($guild){
        $guild_coin=round($guild['commission']*$coin,2);//公会提成
        
        $data = array(
            'user_id'          =>$uid,
            'table_log'        =>$table_id,
            'type'             =>$type,
            'guild_id'         =>$guild['id'],
            'host_earnings'    =>$host_coin,
            'guild_earnings'   =>$guild_coin,
            'guild_commission' =>$guild['commission'],
            'guild_type'       =>$guild['type'],
            'addtime'          => NOW_TIME,
            'consume_log'      =>$log_id,
        );
        $insert_id = db('guild_log')->insertGetId($data);
        
        if($insert_id){
            db('guild')->where(['id' => $guild['id']])->inc('earnings', $guild_coin)->inc('total_earnings', $guild_coin)->update();
        }
    }
    
    return $insert_id;
}
//公会提成
function sel_guild_log($uid,$invite_coin){

    //获取是否有公会提成
    $guild=db('guild')->alias('g')->field("g.commission")->join('guild_join u', 'u.guild_id=g.id')->where("u.user_id=".$uid." and g.type=2 and u.status=1")->find();

    $invite_coin=$guild ? round($guild['commission']*$invite_coin,2): 0;//公会提成

    return $invite_coin;
}


//添加消费总记录
function add_charging_log($user_id, $to_user_id, $type, $coin, $table_id, $profit, $content = '')
{
    $data = [
        'user_id' => $user_id,
        'to_user_id' => $to_user_id,
        'coin' => $coin,
        'table_id' => $table_id,
        'type' => $type,
        'create_time' => NOW_TIME,
        'profit' => $profit,
    ];

    if ($type == 0) {
        $data['content'] = $content;
    } elseif ($type == 1) {
        $data['content'] = '视频购买消费';
    } elseif ($type == 2) {
        $data['content'] = '私照够买消费';
    } elseif ($type == 3) {
        $data['content'] = '赠送礼物消费';
    } elseif ($type == 4) {
        $data['content'] = '通话计时消费';
    } elseif ($type == 5) {
        $data['content'] = '私信消息付费';
    } elseif ($type == 6) {
        $data['content'] = '购买守护消费';
    } elseif ($type == 7) {
        $data['content'] = '转盘抽奖消费';
    } elseif ($type == 8) {
        $data['content'] = $content;
    }

    //增加消费记录
    $insert_id = db('user_consume_log')->insertGetId($data);
    if ($insert_id) {
        //邀请收益分成
        invite_back_now($profit, $to_user_id, $insert_id);
    }

    return $insert_id;
}

//获取用户基本信息 1是不缓存 0是缓存
function get_user_base_info($user_id, $field = array(), $cache = 1)
{

//    $base_field = 'id,avatar,user_nickname,sex,level,coin,user_status';
    //    if(is_array($field) && count($field) > 0){
    //        $base_field .= ',' . implode(',',$field);
    //    }
    //    $user_info = db('user') -> field($base_field) -> find($user_id);

    $user_info = load_cache('user_info', ['user_id' => $user_id, 'field' => $field, 'cache' => $cache]);
    return $user_info;
}

/**
 * @dw 获取用户认证信息
 * @return 成功返回认证状态,未提交返回-1
 * */
function get_user_auth_status($user_id)
{
    $config = load_cache('config');
    //获取认证状态
    if (isset($config['auth_type']) && $config['auth_type'] == 1) {
        //是否提交认证
        $auth_record = db('user_auth_video')->where('user_id', '=', $user_id)->find();
    } else {
        //是否提交认证
        $auth_record = db('auth_form_record')->where('user_id', '=', $user_id)->find();
    }
    return $auth_record ? $auth_record['status'] : -1;

}

/**
 * @dw 获取用户认证信息
 * @return 成功返回认证状态,未提交返回-1
 * */
function get_user_video_auth_status($user_id)
{
    //是否提交认证
    $auth_record = db('user_auth_video')->where('user_id', '=', $user_id)->find();

    return $auth_record ? $auth_record['status'] : 0;
}

//获取等级
function get_level($user_id)
{
    $user_info = get_user_base_info($user_id);
    //男性用户
    if ($user_info['sex'] == 1) {
        $where = "user_id=" . $user_id;
        //获取消费记录
        $total = db('user_consume_log')->where($where)->sum("coin");
        $level[] = db('level')->where("level_up <=" . $total)->order("level_up desc")->find();
        $level[] = db('level')->where("level_up >" . $total)->order("level_up asc")->find();
     
    } else {

        $where = "to_user_id=" . $user_id;
        //获取收益记录
        $total = db('user_consume_log')->where($where)->sum("profit");
        $level[] = db('level')->where("level_up_female <=" . $total)->order("level_up_female desc")->find();
        $level[] = db('level')->where("level_up_female >" . $total)->order("level_up_female asc")->find();
     
    }

    if (count($level) > 0 && $level[0]['level_name'] > $user_info['level']) {
        $level_name =$level[0]['level_name'] > 0 ? $level[0]['level_name'] : 1;

        db("user")->where("id=" . $user_id)->update(array('level' => $level_name));
        $user_level = $level[0]['level_name'];
    } else {
        $user_level = $user_info['level'];
    }

    return $user_level ? $user_level :1;
}

//获取用户等级
function get_grade_level($user_id)
{
    $user_info = get_user_base_info($user_id);
    //男性用户
    if ($user_info['sex'] == 1) {
        $where = "user_id=" . $user_id;
        //获取消费记录
        $total = db('user_consume_log')->where($where)->sum("coin");
        $level[] = db('level')->where("level_up <=" . $total)->order("level_up desc")->find();
        $level[] = db('level')->where("level_up >" . $total)->order("level_up asc")->find();
    } else {
        $where = "to_user_id=" . $user_id;
        //获取消费记录
        $total = db('user_consume_log')->where($where)->sum("profit");
        $level[] = db('level')->where("level_up_female <=" . $total)->order("level_up_female desc")->find();
        $level[] = db('level')->where("level_up_female >" . $total)->order("level_up_female asc")->find();
    }
 
    if (count($level) > 0 && $level[0]['level_name'] != $user_info['level']) {
         $level_name =$level[0]['level_name'] > 0 ? $level[0]['level_name'] : 1;

        db("user")->where("id=" . $user_id)->update(array('level' => $level_name));
        $data['level_name'] = $level[0]['level_name'];
        // 获取提成比例
        $data['split'] = $level[0]['split'];
    } else {
        $data['level_name'] = $user_info['level'];
        $data['split'] = 0;
    }

    //获取充值金币和消费金币总数
    $data['msum'] = $total; // 获取提成比例

    if (count($level) > 1) {
        // 获取下一个级别
        $data['down_name'] = $level[1]['level_name'];

         if ($user_info['sex'] == 2) {
            $data['spread'] = $level[1]['level_up_female'] - $total;
            // 进度 单位%                                   // 获取下一个级别
            $data['progress'] =$total > 0 && $level[1]['level_up_female'] > 0 ? round(100 * ($total / $level[1]['level_up_female'])) : 0; // 进度 单位%
        } else {
            $data['spread'] = $level[1]['level_up'] - $total;
            // 进度 单位%                                   // 获取下一个级别
            $data['progress'] =$total > 0 && $level[1]['level_up'] > 0 ?  round(100 * ($total / $level[1]['level_up'])) :0 ; // 进度 单位%
        }
    } else {
        $data['down_name'] = '99999';
        $data['progress'] = '0%';
        $data['spread'] = 0;
    }
    return $data;
}


//用户是否在线
function is_online($user_id, $heartbeat_interval)
{
//    $key = 'online:'.$user_id;
    //    $time = $GLOBALS['redis'] -> get($key);
    //
    //    $type = 0;
    //    if($time){
    //        $invite_time = NOW_TIME - $time;
    //        if($invite_time < $heartbeat_interval){
    //            $type = 1;                      //在线
    //        }
    //    }
    include_once DOCUMENT_ROOT . '/system/redis/UserOnlineStateRedis.php';

    $user_redis = new UserOnlineStateRedis();
    $res = $user_redis->is_online($user_id);
    return $res ? 1 : 0;
}

//更新心跳时间
function update_heartbeat($user_id)
{
    $key = 'online:' . $user_id;

    $time = $GLOBALS['redis']->set($key, NOW_TIME);
    $data = [
        'user_id' => $user_id,
        'monitor_time' => NOW_TIME,
    ];

    $monitor = db('monitor')->where('user_id', '=', $user_id)->find();
    if ($monitor) {
        db('monitor')->where('user_id', '=', $user_id)->update(['monitor_time' => NOW_TIME]);
    } else {
        //增加记录
        db('monitor')->insert($data);
    }

}

//生成邀请码
function create_invite_code_0910($user_id)
{
    //获取邀请码
    $invite_code = db('invite_code')->where('user_id', '=', $user_id)->find();

    if (!$invite_code) {
        //生成邀请码;
        db('invite_code')->insert(['user_id' => $user_id, 'invite_code' => $user_id]);
        $invite_code = $user_id;
    } else {
        $invite_code = $invite_code['invite_code'];
    }

    return $invite_code;
}

//生成邀请码
function create_invite_code()
{

    $code = rand_str(6);
    $res = db('invite_code')->where('invite_code', '=', $code)->find();
    if ($res) {
        create_invite_code();
    } else {
        return $code;
    }
}

/**
 * @dw 女性用户收益返现业务
 * @param $total_coin 主播收益总积分
 * @param $uid 主播ID
 * @param $log_id 消费记录ID
 * *@author 魏鹏
 */
function invite_back_now($total_coin, $uid, $log_id)
{
    //增加收益分成
    $invite_record = db('invite_record')->where('invite_user_id', '=', $uid)->find();

    if ($invite_record && $invite_record['user_id'] != 0) {
        //分成比例
        $config = load_cache('config');
        $invite_income = $total_coin * $config['invite_income_ratio_female'];

        if ($invite_income > 0) {
            //增加邀请人收益
            $money = round($invite_income / $config['invitation_exchange'], 2);

            $record = [
                'user_id' => $invite_record['user_id'],
                'invite_user_id' => $uid,
                'c_id' => $log_id,
                'income' => $invite_income,
                'invite_code' => $invite_record['invite_code'],
                'create_time' => NOW_TIME,
                'total_coin' => $total_coin,
                'money' => $money,
                'type' => 1,
            ];

            db('invite_profit_record')->insert($record);
            db('user')->where('id', '=', $invite_record['user_id'])->inc('invitation_coin', $money)->update();
        }
    }
}

/**
 * @dw 男性用户充值返现业务
 * @param $total_money 充值金额
 * @param $uid 用户ID
 * @param $log_id 充值记录ID
 * *@author 魏鹏
 */
function invite_back_now_recharge($total_money, $uid, $order_id)
{
    $user_info = get_user_base_info($uid);
    //增加收益分成
    $invite_record = db('invite_record')->where('invite_user_id', '=', $uid)->find();

    if ($invite_record && $invite_record['user_id'] != 0 && $user_info['sex'] == 1) {

        //获取充值扣单概率
        if (get_bucket_invite($invite_record['user_id'], 1) == 0) {
            //分成比例
            $config = load_cache('config');
            $invite_income = round($total_money * $config['invite_income_ratio'], 2);

            if ($invite_income > 0) {
                //增加邀请人收益
                $record = [
                    'user_id' => $invite_record['user_id'],
                    'invite_user_id' => $uid,
                    'c_id' => 0,
                    'income' => 0,
                    'invite_code' => $invite_record['invite_code'],
                    'create_time' => NOW_TIME,
                    'total_coin' => $total_money,
                    'money' => $invite_income,
                    'type' => 2,
                    'order_id' => $order_id,
                ];

                db('invite_profit_record')->insert($record);
                db('user')->where('id', '=', $invite_record['user_id'])->inc('invitation_coin', $invite_income)->update();
            }
        } else {
            //扣量记录
            $invite_deduction_record = [
                'user_id' => $invite_record['user_id'],
                'invite_user_id' => $uid,
                'order_id' => $order_id,
                'create_time' => NOW_TIME,
                'money' => $total_money,
            ];
            db('invite_recharge_deduction_record')->insert($invite_deduction_record);
        }
    }
}

//七牛删除图片
function oss_del_list($data)
{
    require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';

    $qiniu_config = get_qiniu_config();

    // 需要填写你的 Access Key 和 Secret Key
    $accessKey = $qiniu_config['accessKey'];
    $secretKey = $qiniu_config['secretKey'];
    // 要上传的空间
    $bucket = $qiniu_config['bucket'];
    // 构建鉴权对象
    $auth = new Auth($accessKey, $secretKey);
    $config = new \Qiniu\Config();
    $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);

    //每次最多不能超过1000个
    $ops = $bucketManager->buildBatchDelete($bucket, $data);
    list($ret, $err) = $bucketManager->batch($ops);

    if ($err !== null) {
        return false;
    } else {

        //返回图片的完整URL
        return $ret;
    }
}

//七牛批量删除
function oss_del_file($data)
{
    require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';

    $qiniu_config = get_qiniu_config();
    // 需要填写你的 Access Key 和 Secret Key
    $accessKey = $qiniu_config['accessKey'];
    $secretKey = $qiniu_config['secretKey'];
    // 要上传的空间
    $bucket = $qiniu_config['bucket'];
    // 构建鉴权对象
    $auth = new Auth($accessKey, $secretKey);
    $config = new \Qiniu\Config();
    $bucketManager = new \Qiniu\Storage\BucketManager($auth, $config);
    $result = $bucketManager->delete($bucket, $data);

    //var_dump($result);exit;
    if (!$result) {
        return true;
    } else {
        return false;
    }

}
//上传到本地图片
function local_upload($file){

    $fileoriginal=$file['tmp_name'];
    $key = date('YmdHis').$file['name'];
    
    $img_ext = substr(strrchr($key, '.'), 1);
    $ext = strtolower($img_ext);
    $img=['jpeg','png','jpg','mp4'];
    if(!in_array($ext, $img)){
        $result['code'] = 0;
        $result['msg'] = '格式不正确';
        return_json_encode($result);
    }

    $filepath = 'upload/'.date('Ymd')."/";
     if (!is_dir($filepath)) {
        mkdir($filepath, 0700, true);
    }
    $url = 'http://' . $_SERVER['HTTP_HOST'] ."/mapi/public/".$filepath.$key;

    if( @fopen( $url, 'r' ) ){
            return $url; //文件存在
    }else{
        if(move_uploaded_file($fileoriginal,$filepath.$key)){
            //返回图片的完整URL
            return $url;
        }else{
           return false;
        }
    }
}
//七牛上传 $file文件
function oss_upload($file)
{
	  // 要上传图片的本地路径
	    $filePath = $file->getRealPath();
	    $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION); //后缀
	    // 上传到七牛后保存的文件名
	    $key = substr(md5($file->getRealPath()), 0, 5) . date('YmdHis') . rand(0, 9999) . '.' . $ext;
                
      $option_value = db('option')->where("option_name ='storage'")->value('option_value');
         if (!empty($option_value)) {
            $optionValue = json_decode($option_value, true);
            if($optionValue["type"] == 'Qiniu'){
               
               $qiniu_config = get_qiniu_config();
              
               
                require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';
                // 需要填写你的 Access Key 和 Secret Key
                $accessKey = $qiniu_config['accessKey'];
                $secretKey = $qiniu_config['secretKey'];
                // 构建鉴权对象
                $auth = new Auth($accessKey, $secretKey);
                // 要上传的空间
                $bucket = $qiniu_config['bucket'];
                $domain = $qiniu_config['domain'];
                $token = $auth->uploadToken($bucket);

                // 初始化 UploadManager 对象并进行文件的上传
                $uploadMgr = new UploadManager();

                // 调用 UploadManager 的 putFile 方法进行文件的上传
                list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
                if ($err !== null) {
                    return false;
                } else {
                    $url = $domain . '/' . $ret['key'];
                    //返回图片的完整URL
                    return $url;
                }

            }
        }
		 $filepaths = 'upload/'.date('Ymd')."/";
     if (!is_dir($filepaths)) {
        mkdir($filepaths, 0700, true);
    }
    $url = 'http://' . $_SERVER['HTTP_HOST'] ."/mapi/public/".$filepaths.$key;

    if( @fopen( $url, 'r' ) ){
            return $url; //文件存在
    }else{
        if(move_uploaded_file($filePath,$filepaths.$key)){
            //返回图片的完整URL
            return $url;
        }else{
           return false;
        }
    }
 
      
}
//定时清理离线用户
function crontab_do_end_live()
{
    $config = load_cache('config');

    //时间
    $time = NOW_TIME - $config['heartbeat_interval'] - 10; //偏移量5秒
    $time_out_user = db('monitor')->where('monitor_time', '<', $time)->select();

    $out_id_array = [];
    foreach ($time_out_user as $v) {

        $out_id_array[] = $v['user_id'];
        //删除心跳
        $key = 'online:' . $v['user_id'];
        $GLOBALS['redis']->del('del', $key);
    }

    if (count($out_id_array) > 0) {

        $ids = implode(',', $out_id_array);
        //删除所有超时心跳
        db('monitor')->where('user_id', 'in', $ids)->delete();
    }

    db('video_live_list')->where('last_heart_time', '<', $time)->delete();

}

//定时清理超时电话
function crontab_do_end_call()
{

    $config = load_cache('config');
    //查询
    $list = db('video_call_record')->where(['status' => 0])->select();
    foreach ($list as $v) {
        $time = NOW_TIME - $v['create_time'];
        if ($time > $config['video_call_time_out']) {
            //删除超时电话记录
            db('video_call_record')->delete($v['id']);
        }
    }

    //查询
    $list = db('video_call_record')->where(['status' => 1])->select();
    foreach ($list as $v) {
        $time = NOW_TIME - $v['create_time'];
        if ($time > 60 * 60 * 5) {
            //删除超时电话记录
            $v['status'] = 4;
            $v['end_time'] = NOW_TIME;

            db('video_call_record_log')->insert($v);
            db('video_call_record')->delete($v['id']);
        }
    }

}

function log_result($word)
{
    if (is_array($word)) {
        $word = var_export($word, true);
    }

    $file = DOCUMENT_ROOT . "/public/notify_url.log";
    $fp = fopen($file, "a");
    flock($fp, LOCK_EX);
    fwrite($fp, "执行日期：" . strftime("%Y-%m-%d-%H：%M：%S", time()) . "\n" . $word . "\n\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}

//支付通用回调方法
function pay_call_service_1023($notice_sn,$real_money)
{
    //订单信息
    $order_info = db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->find();
    Log::info( date("Y-m-d H:i:s").' $order_info:'.json_encode($order_info));

    if ($order_info) {
        //充值VIP
        if ($order_info['type'] == 7777777) {
            $vip_rule = db('vip_rule')->find($order_info['refillid']);
            $vip_time = $vip_rule['day_count'] * 60 * 60 * 24;
            $user_info = get_user_base_info($order_info['uid'], ['vip_end_time']);

            if ($user_info['vip_end_time'] > NOW_TIME) {
                db('user')->where('id', '=', $user_info['id'])->setInc('vip_end_time', $vip_time);
            } else {
                $vip_time = NOW_TIME + $vip_time;
                db('user')->where('id', '=', $user_info['id'])->setField('vip_end_time', $vip_time);
            }
            //修改订单状态
            db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->setField('status', 1);

            $config = load_cache('config');

            //发送群频道通知
            $broadMsg['type'] = 778;
            $sender['id'] = $user_info['id'];
            $sender['user_nickname'] = $user_info['user_nickname'];
            $sender['avatar'] = $user_info['avatar'];
            $broadMsg['channel'] = 'all'; //通话频道
            $broadMsg['sender'] = $sender;
            $msg_str = '土豪' . $user_info['user_nickname'] . '开通了尊贵VIP';
            $broadMsg['vip_info']['send_msg'] = $msg_str;
            #构造rest API请求包
            $msg_content = array();
            //创建$msg_content 所需元素
            $msg_content_elem = array(
                'MsgType' => 'TIMCustomElem',       //定义类型为普通文本型
                'MsgContent' => array(
                    'Data' => json_encode($broadMsg)    //转为JSON字符串
                )
            );

            //将创建的元素$msg_content_elem, 加入array $msg_content
            array_push($msg_content, $msg_content_elem);

            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
            $api = createTimAPI();

            $ret = $api->group_send_group_msg2($config['tencent_identifier'], $config['acquire_group_id'], $msg_content);

        } else {
            //充值金币
            $rule = db('user_charge_rule')->find($order_info['refillid']);
            $flag = false;
            Log::info('$rule'.json_encode($rule));
            if($rule){
	            $d = $rule['money'] += 1;
		        $e = $rule['money'] -= 1;
		        if ($d >= $real_money && $e <= $real_money) {
		            $flag =  true;
		        }
            }else{
            	//充值规则不存在
                db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->setField('status', 2);
                notify_log($notice_sn, $order_info['uid'], '充值回调成功,error:充值规则不存在');
            }
            
            if ($flag && $order_info['status'] == 0) {
                $coin = $rule['coin'] + $rule['give'];
                //增加用户钻石
                db('user')->where('id', '=', $order_info['uid'])->setInc('coin', $coin);
                db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->setField('status', 1);
                //邀请奖励分成
                invite_back_now_recharge($order_info['money'], $order_info['uid'], $order_info['id']);
                //增加代理用户分成数据
                agent_order_recharge($order_info['money'], $order_info['uid'], $order_info['id']);
                //增加回调信息
                notify_log($notice_sn, $order_info['uid'], '充值回调成功,success:增加钻石成功');
            } 
        }


    } else {
        //订单信息不存在

        notify_log($notice_sn, 0, '充值回调成功,error:订单信息不存在');
    }

}

//支付通用回调方法
function pay_call_service($notice_sn)
{
    //订单信息
    $order_info = db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->find();

    if ($order_info) {
        //充值VIP
        if ($order_info['type'] == 7777777) {
            $vip_rule = db('vip_rule')->find($order_info['refillid']);
            $vip_time = $vip_rule['day_count'] * 60 * 60 * 24;
            $user_info = get_user_base_info($order_info['uid'], ['vip_end_time']);

            if ($user_info['vip_end_time'] > NOW_TIME) {
                db('user')->where('id', '=', $user_info['id'])->setInc('vip_end_time', $vip_time);
            } else {
                $vip_time = NOW_TIME + $vip_time;
                db('user')->where('id', '=', $user_info['id'])->setField('vip_end_time', $vip_time);
            }
            //修改订单状态
            db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->setField('status', 1);

            $config = load_cache('config');

            //发送群频道通知
            $broadMsg['type'] = 778;
            $sender['id'] = $user_info['id'];
            $sender['user_nickname'] = $user_info['user_nickname'];
            $sender['avatar'] = $user_info['avatar'];
            $broadMsg['channel'] = 'all'; //通话频道
            $broadMsg['sender'] = $sender;
            $msg_str = '土豪' . $user_info['user_nickname'] . '开通了尊贵VIP';
            $broadMsg['vip_info']['send_msg'] = $msg_str;
            #构造rest API请求包
            $msg_content = array();
            //创建$msg_content 所需元素
            $msg_content_elem = array(
                'MsgType' => 'TIMCustomElem',       //定义类型为普通文本型
                'MsgContent' => array(
                    'Data' => json_encode($broadMsg)    //转为JSON字符串
                )
            );

            //将创建的元素$msg_content_elem, 加入array $msg_content
            array_push($msg_content, $msg_content_elem);

            require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
            $api = createTimAPI();

            $ret = $api->group_send_group_msg2($config['tencent_identifier'], $config['acquire_group_id'], $msg_content);

        } else {
            //充值金币
            $rule = db('user_charge_rule')->find($order_info['refillid']);
            if ($rule) {
                $coin = $rule['coin'] + $rule['give'];
                //增加用户钻石
                db('user')->where('id', '=', $order_info['uid'])->setInc('coin', $coin);
                db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->setField('status', 1);
                //邀请奖励分成
                invite_back_now_recharge($order_info['money'], $order_info['uid'], $order_info['id']);
                //增加代理用户分成数据
                agent_order_recharge($order_info['money'], $order_info['uid'], $order_info['id']);
                //增加回调信息
                notify_log($notice_sn, $order_info['uid'], '充值回调成功,success:增加钻石成功');
            } else {
                //充值规则不存在
                db('user_charge_log')->where('order_id', '=', $notice_sn)->where('status', '=', 0)->setField('status', 2);
                notify_log($notice_sn, $order_info['uid'], '充值回调成功,error:充值规则不存在');
            }
        }


    } else {
        //订单信息不存在

        notify_log($notice_sn, 0, '充值回调成功,error:订单信息不存在');
    }

}

function notify_log($order_id, $user_id, $content)
{
    db('pay_notify_log')->insert(['order_id' => $order_id, 'user_id' => $user_id, 'content' => $content, 'create_time' => NOW_TIME]);
}

function post($curlPost, $url)
{
    $curl = curl_init();
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_HEADER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_NOBODY, true);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $curlPost);

    $return_str = curl_exec($curl);

    curl_close($curl);
    return $return_str;
}

//xml解析
function xml_to_array($xml)
{
    $reg = "/<(\w+)[^>]*>([\\x00-\\xFF]*)<\\/\\1>/";

    if (preg_match_all($reg, $xml, $matches)) {

        $count = count($matches[0]);
        for ($i = 0; $i < $count; $i++) {
            $subxml = $matches[2][$i];
            $key = $matches[1][$i];
            if (preg_match($reg, $subxml)) {
                $arr[$key] = xml_to_array($subxml);
            } else {
                $arr[$key] = $subxml;
            }
        }
    }
    return $arr;
}

//获取经纬度
function returnSquarePoint($lng, $lat, $distance = 0.5)
{
    define('EARTH_RADIUS', '6371'); //地球半径，平均半径为6371km
    $dlng = 2 * asin(sin($distance / (2 * EARTH_RADIUS)) / cos(deg2rad($lat)));
    $dlng = rad2deg($dlng);

    $dlat = $distance / EARTH_RADIUS;
    $dlat = rad2deg($dlat);

    return array(
        'left-top' => array('lat' => $lat + $dlat, 'lng' => $lng - $dlng),
        'right-top' => array('lat' => $lat + $dlat, 'lng' => $lng + $dlng),
        'left-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng - $dlng),
        'right-bottom' => array('lat' => $lat - $dlat, 'lng' => $lng + $dlng),
    );
}

//获取验证码数字
function get_verification_code($account, $length = 6)
{
    if (empty($account)) {
        return false;
    }

    $verificationCodeQuery = db('verification_code');
    $config = load_cache('config');
    $currentTime = NOW_TIME;
    $maxCount = $config['system_sms_sum'];
    $findVerificationCode = $verificationCodeQuery->where("'account'=" . $account)->find();
    $result = false;
    if (empty($findVerificationCode)) {
        $result = true;
    } else {
        $sendTime = $findVerificationCode['send_time'];
        $todayStartTime = strtotime(date('Y-m-d', $currentTime));
        if ($sendTime < $todayStartTime) {
            $result = true;
        } else if ($findVerificationCode['count'] < $maxCount) {
            $result = true;
        }
    }

    if ($result) {
        switch ($length) {
            case 4:
                $result = rand(1000, 9999);
                break;
            case 6:
                $result = rand(100000, 999999);
                break;
            case 8:
                $result = rand(10000000, 99999999);
                break;
            default:
                $result = rand(100000, 999999);
        }
    }

    return $result;
}

/*
 *  代理渠道用户充值记录表
 * */

function agent_order_recharge($total_money, $uid, $order_id)
{
    $user_info = get_user_base_info($uid);
    if ($user_info['link_id']) {
        $config = load_cache('config');

        $agent_earnings = intval($config['agent_earnings']);

        $count = db('agent_order_log')->where("channel_link='" . $user_info['link_id'] . "'")->count();
        $agent = db('agent')->where("channel_agent_link='" . $user_info['link_id'] . "'")->find();

        if ($agent['agent_level'] != '1') {

            $agent_one = db('agent')->where("id='" . $agent['superior_id'] . "'")->find();
            $agent['commission'] = $agent_one['commission'];
            if ($agent_one['buckle_quantity'] > 0) {
                $agent_earnings = $agent_one['buckle_quantity'];
            }
        } else {
            if ($agent['buckle_quantity'] > 0) {
                $agent_earnings = $agent['buckle_quantity'];
            }
        }

        $agent_money = round($agent['commission'] * $total_money / 100, 2);
        $data = array(
            'order_id' => $order_id,
            'channel_link' => $user_info['link_id'],
            'uid' => $uid,
            'money' => $total_money,
            'agent_commission' => $agent['commission'],
            'agent_money' => $agent_money,
            'type' => 1,
            'addtime' => time(),
        );

        $count = $count + 1;
        if ($count >= $agent_earnings && $agent_earnings != 0 && $count % $agent_earnings == 0) {
            $data['type'] = 2;
        }

        db('agent_order_log')->insert($data);
    }

}

/**
 * 更新手机或邮箱验证码发送日志
 * @param string $account 手机或邮箱
 * @param string $code 验证码
 * @param int $expireTime 过期时间
 * @param  $result  返回值
 * @return boolean
 */
function verification_code_log($account, $code, $result, $expireTime = 0)
{
    $currentTime = NOW_TIME;
    $expireTime = $expireTime > $currentTime ? $expireTime : $currentTime + 30 * 60;
    $verificationCodeQuery = db('verification_code');
    $findVerificationCode = $verificationCodeQuery->where('account', $account)->order("id desc")->find();

    $count = 1;
    $todayStartTime = strtotime(date("Y-m-d")); //当天0点
    if ($findVerificationCode && $findVerificationCode['send_time'] > $todayStartTime) {
        //获取当天的条数
        $count = $findVerificationCode['count'] + 1;
    }

    $result = $verificationCodeQuery
        ->insert([
            'account' => $account,
            'send_time' => $currentTime,
            'code' => $code,
            'count' => $count,
            'expire_time' => $expireTime,
            'status' => $result['code'] == 1 ? 1 : 2,
            'msg' => $result['msg'],
            'smUuid' => $result['smUuid'] ? $result['smUuid'] : '',
        ]);
    return $result;
}

//获取随机码
function get_ran_code($len)
{
    $code = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $rand = $code[rand(0, 25)]
        . strtoupper(dechex(date('m')))
        . date('d') . substr(time(), -5)
        . substr(microtime(), 2, 5)
        . sprintf('%02d', rand(0, 99));
    for (
        $a = md5($rand, true),
        $s = '0123456789ABCDEFGHIJKLMNOPQRSTUV',
        $d = '',
        $f = 0;
        $f < $len;
        $g = ord($a[$f]),
        $d .= $s[($g ^ ord($a[$f + $len])) - $g & 0x1F],
        $f++
    ) ;
    return $d;
}

//获取163 sign
function get163yunSing($user_id)
{
    include_once DOCUMENT_ROOT . '/system/163yun/ServerAPI.php';

    $AppKey = '4efd2a2a1656f79a87f6ae2d2f32a13b';
    $AppSecret = '856e31123d88';
    // $p = new ServerAPI($AppKey,$AppSecret,'fsockopen');		//fsockopen伪造请求
    $p = new ServerAPI($AppKey, $AppSecret, 'curl');        //php curl库
    $p->createUserId($user_id);
    return $data = $p->updateUserToken($user_id);

}


//生成七牛RoomToken
function getQiniuRoomToken($channel_id, $uid)
{
    $qiniu_config = get_qiniu_config();
    /*----------------生成七牛RoomToken---------------------------start*/
    require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';
    // 需要填写你的 Access Key 和 Secret Key
    $accessKey = $qiniu_config('accessKey');
    $secretKey = $qiniu_config('secretKey');

    // 构建鉴权对象
    $auth = new Auth($accessKey, $secretKey);
    $app_client = new AppClient($auth);
    return trim($app_client->appToken("e9teck2b8", $channel_id, (string)$uid, (time() + 3600), 'user'));
    /*----------------生成七牛RoomToken---------------------------end*/
}

/*** add 2019/10/27
 * @param $parsed_url
 * @return string
 */
function  unparse_url($parsed_url) {
    $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
    $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
    $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
    $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
    $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass']  : '';
    $pass     = ($user || $pass) ? "$pass@" : '';
    $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
    $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
    $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
    return "$scheme$user$pass$host$port$path".urlencode($query.$fragment);
}