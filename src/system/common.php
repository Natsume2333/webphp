<?php
/**
 * Created by PhpStorm.
 * User: weipeng  kj
 * Date: 2018/3/4
 * Time: 22:14
 */


//获取七牛存储配置
function get_qiniu_config()
{

    $qiniu_config = db('plugin')->where('name', '=', 'Qiniu')->find();
    if ($qiniu_config) {
        $qiniu_config = json_decode($qiniu_config['config'], true);
        $qiniu_config['domain'] = 'http://' . $qiniu_config['domain'];
    } else {
        $qiniu_config = [
            'accessKey' => '',
            'secretKey' => '',
            'bucket' => '',
            'domain' => '',
        ];
    }
    return $qiniu_config;
}

//计算经纬度之间的距离
function distance($lat1, $lon1, $lat2, $lon2)
{
    $R = 6371393; //地球平均半径,单位米
    $dlat = deg2rad($lat2 - $lat1);
    $dlon = deg2rad($lon2 - $lon1);
    $a = pow(sin($dlat / 2), 2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * pow(sin($dlon / 2), 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    $d = $R * $c;
    return round($d);
}

//完善资料查询邀请人进行奖励
function reg_invite_perfect_info_service($uid, $sex)
{
    $config = load_cache('config');
    $invite_record = db('invite_record')->where('invite_user_id', '=', $uid)->find();
    //查询是否已经有过奖励
    $exits = db('invite_profit_record')->where('user_id', '=', $invite_record['user_id'])->where('invite_user_id', '=', $uid)->where('c_id', '=', 6)->find();
    if ($exits) {
        return;
    }

    $reward = $sex == 1 ? $config['invite_reg_reward_man'] : $config['invite_reg_reward_female'];

    //如果管理员填写空会报错
    if (empty($reward)) {
        $reward = 0;
    }

    if ($invite_record) {
        $record = [
            'user_id' => $invite_record['user_id'],
            'invite_user_id' => $uid,
            'c_id' => 6,
            'income' => 0,
            'invite_code' => $invite_record['invite_code'],
            'create_time' => NOW_TIME,
            'money' => $reward,
            'type' => 0,
        ];

        db('invite_profit_record')->insert($record);
        db('user')->where('id', '=', $invite_record['user_id'])->inc('invitation_coin', $reward)->update();
    }
}

function get_format_money($num)
{
    $pat = '/(\d+\.\d{5})\d*/';
    return preg_replace($pat, "\${1}", $num);
}

//获取鉴权视频链接
function get_sign_video_url($key, $video_url)
{

    $parse_url_arr = parse_url($video_url);
    $url_dir = substr($parse_url_arr['path'], 0, strrpos($parse_url_arr['path'], '/') + 1);
    $t = dechex(time() + 60 * 60 * 24);
    $us = rand_str();
    $sign = md5($key . $url_dir . $t . $us);

    $sign_video_url = $video_url . '?t=' . $t . '&us=' . $us . '&sign=' . $sign;

    return $sign_video_url;
}


function load_cache($key, $param = array(), $is_real = true)
{
    $file = DOCUMENT_ROOT . "/system/cache/" . $key . ".auto_cache.php";
    require_once $file;
    $class = $key . "_auto_cache";

    $obj = new $class;
    $result = $obj->load($param, $is_real);
    return $result;
}


//PHP把秒转换成小时数和分钟 ：时间转换
function secs_to_str($secs)
{
    $r = '';
    if ($secs >= 3600) {
        $hours = floor($secs / 3600);
        $secs = $secs % 3600;
        $r = $hours . ' 时';
        if ($hours <> 1) {
            $r .= 's';
        }
        if ($secs > 0) {
            $r .= ', ';
        }
    }
    if ($secs >= 60) {
        $minutes = floor($secs / 60);
        $secs = $secs % 60;
        $r .= $minutes . ' 分';
        if ($minutes <> 1) {
            $r .= '';
        }
        if ($secs > 0) {
            $r .= '';
        }
    }
    $r .= $secs;
    if ($secs <> 1) {
        $r .= '秒';
    }
    return $r;
}

function get_oss_file_path($path)
{
    $file_name = parse_url($path)['path'];
    $file_name = substr($file_name, 1, strlen($file_name));
    return $file_name;
}

function bugu_request_file($path)
{

    require_once $path;
}


//生成随机字符串
function rand_str($len = 8)
{
    $chars = [
        "a", "b", "c", "d", "e", "f", "g", "h", "i", "j", "k",
        "l", "m", "n", "o", "p", "q", "r", "s", "t", "u", "v",
        "w", "x", "y", "z", "A", "B", "C", "D", "E", "F", "G",
        "H", "I", "J", "K", "L", "M", "N", "O", "P", "Q", "R",
        "S", "T", "U", "V", "W", "X", "Y", "Z", "0", "1", "2",
        "3", "4", "5", "6", "7", "8", "9"
    ];
    $charsLen = count($chars) - 1;
    shuffle($chars);    // 将数组打乱
    $output = "";
    for ($i = 0; $i < $len; $i++) {
        $output .= $chars[mt_rand(0, $charsLen)];
    }
    return $output;
}

function time_trans($the_time)
{
    $now_time = time();
    $show_time = $the_time;

    $dur = $now_time - $show_time;

    if ($dur < 60) {
        return $dur . '秒前';
    } else if ($dur < 3600) {
        return floor($dur / 60) . '分钟前';
    } else if ($dur < 86400) {
        return floor($dur / 3600) . '小时前';
    } else if ($dur < 259200) {
        //3天内
        return floor($dur / 86400) . '天前';
    } else {
        return date('Y-m-d', $the_time);
    }
}

//获取10小时内的时间
function time_trans_10($the_time)
{
    $now_time = time();
    $show_time = $the_time;

    $dur = $now_time - $show_time;

    if ($dur < 60) {
        return $dur . '秒前';
    } else if ($dur < 3600) {
        return floor($dur / 60) . '分钟前';
    } else if ($dur < 36000) {
        return floor($dur / 3600) . '小时前';
    } else {
        return '';
    }
}

//对emoji表情转义
function emoji_encode($str)
{
    $strEncode = '';

    $length = mb_strlen($str, 'utf-8');

    for ($i = 0; $i < $length; $i++) {
        $_tmpStr = mb_substr($str, $i, 1, 'utf-8');
        if (strlen($_tmpStr) >= 4) {
            $strEncode .= '[[EMOJI:' . rawurlencode($_tmpStr) . ']]';
        } else {
            $strEncode .= $_tmpStr;
        }
    }

    return $strEncode;
}

//对emoji表情转反义
function emoji_decode($str)
{
    $strDecode = preg_replace_callback('|\[\[EMOJI:(.*?)\]\]|', function ($matches) {
        return rawurldecode($matches[1]);
    }, $str);

    return $strDecode;
}