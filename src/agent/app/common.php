<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/19
 * Time: 8:59
 */

/*
 * 推送用户私照审核认证结果消息
 * */
function push_private_photo_auth_result_msg()
{


}

/*
 * 推送所有人可以收到的系统消息
 * */
function push_msg($msg_id, $user_id, $type)
{

    $msg = db("user_message_all")->find($msg_id);
    if ($msg) {
        $data = [
            'uid' => 0,
            'touid' => $user_id,
            'messageid' => $msg['id'],
            'messagetype' => $msg['centent'],
            'type' => $type,
            'status' => 1,
            'addtime' => NOW_TIME,
        ];
        return db('user_message_log')->insert($data);
    }
    return false;
}


/*
 * 推送单用户系统消息
 * */
function push_msg_user($msg_id, $user_id, $type)
{

    $msg = db("user_message")->find($msg_id);
    if ($msg) {
        $data = [
            'uid' => 0,
            'touid' => $user_id,
            'messageid' => $msg['id'],
            'messagetype' => $msg['centent'],
            'type' => $type,
            'status' => 1,
            'addtime' => NOW_TIME,
        ];
        return db('user_message_log')->insert($data);
    }
    return false;
}

/*
*   渠道统计封装
*  */
function get_conversion($agent)
{

    $where = "channel='" . $agent['channel'] . "'";

    if ($agent['agent_level'] != '1') {

        $where .= " and channel_agent='" . $agent['channel_agent'] . "'";
        $agent_list[] = $agent;
        $superior_id = db('agent')->where("id=" . $agent['superior_id'])->find();
        $agent_earnings = $superior_id['commission'];

    } else {

        $superior_id = db('agent')->where("superior_id=" . $agent['id'])->select();

        $agent_list = $superior_id;
        $agent_list[] = $agent;
        $agent_earnings = $agent['commission'];
    }

    $endtimes = db('agent_statistical')->where($where)->order("id desc")->find();

    $c = date('Y-m-d');

    if ($endtimes['data_time'] == $c || $endtimes == null) {
        $time[] = $c;
    } else {
        //获取的不是今天时间
        $begin = $endtimes['data_time'];
        $end = $c;
        $begintime = strtotime($begin);
        $endtime = strtotime($end);

        for ($start = $begintime; $start <= $endtime; $start += 24 * 3600) {

            $time[] = date("Y-m-d", $start);
        }
    }

    foreach ($time as $v) {    //循环向数据中插入值
        foreach ($agent_list as $cc) {

            get_agent_list($v, $c, $cc['channel'], $cc['channel_agent_link'], $cc['id'], $cc['commission'], $agent_earnings);
        }
    }

}

/*
*  获取代理渠道的用户统计入库
 */
function get_agent_list($datas, $c, $channel, $channel_agent, $uid, $commission, $agent_earnings)
{
    $starttime = strtotime($datas . " 00:00:00");
    $endtime = strtotime($datas . " 23:59:59");
    $channel_link = $channel_agent;
    $user = db('user')->where("link_id='$channel_link'")->select();
    $user_count = db('user')->where("create_time >='$starttime' and create_time <= '$endtime' and link_id='$channel_link'")->count();
    $sum = 0;
    $agent_earnings_money = 0;
    if ($user) {
        foreach ($user as $v) {
            $vid = $v['id'];
            $settlement = db('agent_order_log')->where("addtime >='$starttime' and addtime <= '$endtime' and uid='$vid' and type=1")->field("sum(money) as money,sum(agent_money) as agent_money")->find();
            $sum += $settlement['money'];
            $agent_earnings_money += $settlement['agent_money'];
        }
    }

    $earnings = round($commission * $agent_earnings_money / 100, 2);

    $data = array(
        'uid' => $uid,
        'channel' => $channel,
        'channel_agent' => $channel_agent,
        'money' => $sum,
        'registered' => $user_count ? $user_count : '0',
        'data_time' => $datas,
        'commission' => $commission,
        'earnings' => $earnings,
        'agent_commission' => $agent_earnings,
        'agent_earnings' => $agent_earnings_money,
        'addtime' => time(),
    );
    $endtimes = db('agent_statistical')->where("uid='$uid' and channel='$channel' and channel_agent='$channel_agent' and data_time='$datas'")->find();

    if ($endtimes) {
        $settlement = db('agent_statistical')->where("uid='$uid' and channel='$channel' and channel_agent='$channel_agent' and data_time='$datas'")->update($data);

    } else {
        $settlement = db('agent_statistical')->insert($data);
    }

}

/*
*  首页统计数据
* */
function admin_index_list($agent)
{
    //获取当天
    $beginToday = mktime(0, 0, 0, date('m'), date('d'), date('Y'));
    $endToday = mktime(0, 0, 0, date('m'), date('d') + 1, date('Y')) - 1;
    //获取昨天
    $beginYesterday = mktime(0, 0, 0, date('m'), date('d') - 1, date('Y'));
    $endYesterday = mktime(0, 0, 0, date('m'), date('d'), date('Y')) - 1;
    //支付
    $data['charge'] = admin_index_charge($beginToday, $beginYesterday, $agent);
    //注册
    $data['registered'] = admin_index_registered($beginToday, $endToday, $beginYesterday, $endYesterday, $agent);

    //结算
    if ($agent['agent_level'] == 1) {
        $data['settlement'] = agent_settlement($beginToday, $endToday, $beginYesterday, $endYesterday, $agent);
    }


    return $data;
}
//充值统计
function admin_index_charge($beginToday, $beginYesterday, $agent)
{

    $where1 = "data_time ='" . date("Y-m-d", $beginToday)."'";
    $where2 = "data_time ='" . date("Y-m-d", $beginYesterday)."'";


    if ($agent['agent_level'] == 1) {
        $channel = "channel='" . $agent['channel'] . "'";
        //获取子渠道统计
        $channel_z = " ";

        //今天
        $data['day_log'] = agent_charge($where1 . " and " . $channel);
        //昨天
        $data['Yesterday_log'] = agent_charge($where2 . " and " . $channel);
        //所有的
        $data['total_log'] = agent_charge($channel);

        //今天和昨天 支付金额比较
        $data['day_than'] = day_Yesterday_than(intval($data['day_log']['money']), intval($data['Yesterday_log']['money']));

        //今天和昨天 总收益比较
        $data['day_user_than'] = day_Yesterday_than(intval($data['day_log']['agent_earnings']), intval($data['Yesterday_log']['agent_earnings']));

    } else {
        $channel = "channel_agent='" . $agent['channel_agent_link'] . "'";
        $channel_z = " ";
    }

    //今天
    $data['day_log_channel'] = agent_charge($where1 . " and " . $channel . $channel_z);
    //昨天
    $data['Yesterday_log_channel'] = agent_charge($where2 . " and " . $channel . $channel_z);
    //总数
    $data['total_log_channel'] = agent_charge($channel . $channel_z);
    //今天和昨天 子渠道比较
    $data['day_than_channel'] = day_Yesterday_than(intval($data['day_log_channel']['money']), intval($data['Yesterday_log_channel']['money']));
    //今天和昨天 总收益比较
    $data['day_ordersum_than'] = day_Yesterday_than(intval($data['day_log_channel']['earnings']), intval($data['Yesterday_log_channel']['earnings']));


    return $data;
}

//代理注册统计
function admin_index_registered($beginToday, $endToday, $beginYesterday, $endYesterday, $agent)
{

    $where1 = "create_time >=" . $beginToday . " and create_time <" . $endToday;
    $where2 = "create_time >=" . $beginYesterday . " and create_time <" . $endYesterday;

    if($agent['agent_level'] == 1){
        $where = " u.link_id like '".$agent['channel']."%'";
        $channel = " u.link_id='" . $agent['channel_agent_link']."'";

        //今天
        $data['day_agent'] = agent_registered($where1 . " and " . $where);
        //昨天
        $data['Yesterday_agent'] = agent_registered($where2 . " and " . $where);
        //总数
        $data['total_agent'] = agent_registered($where);
        //今天和昨天 代理比较
        $data['agent_registered_than'] = day_Yesterday_than(intval($data['day_agent']), intval($data['Yesterday_agent']));
    }else{
        $where = '';
        $channel = " u.link_id='" . $agent['channel_agent_link']."'";
    }


    //今天
    $data['day_agent_channel'] = agent_registered($where1 . " and " . $channel);
    //昨天
    $data['Yesterday_agent_channel'] = agent_registered($where2 . " and " . $channel);
    //总数
    $data['total_agent_channel'] = agent_registered($channel);
    //今天和昨天 代理比较
    $data['agent_registered_than_channel'] = day_Yesterday_than(intval($data['day_agent_channel']), intval($data['Yesterday_agent_channel']));
    return $data;
}

/*获取渠道支付金额
 * */
function agent_charge($where)
{
    $data = db("agent_statistical")->field("sum(money) as money,sum(agent_earnings) as agent_earnings,sum(earnings) as earnings")->where($where)->find();
    return $data;
}

/*
 * 获取代理注册统计
 * */
function agent_registered($where)
{
    $data = db('user')->alias("u")
        ->join("agent a", "u.link_id=a.channel_agent_link")
        ->where($where)
        ->order("u.create_time desc")
        ->count("u.id");
    return $data;
}

//今日结算
function agent_settlement($beginToday, $endToday, $beginYesterday, $endYesterday, $agent)
{


    $where1 = "addtime >=" . $beginToday . " and addtime <" . $endToday;
    $where2 = "addtime >=" . $beginYesterday . " and addtime <" . $endYesterday;
    $agent_id = "agent_id=" . $agent['id'] . " or level=" . $agent['id'];
    //今天结算
    $data['day_settlement'] = db("agent_withdrawal")->field("sum(money) as money")->where($where1 . " and (" . $agent_id . ")")->find();
    //昨天结算
    $data['Yesterday_settlement'] = db("agent_withdrawal")->field("sum(money) as money")->where($where2 . " and  (" . $agent_id . ")")->find();
    //总数结算
    $data['total_settlement'] = db("agent_withdrawal")->field("sum(money) as money")->where($agent_id)->find();
    return $data;
}

//今日和昨日比较
function day_Yesterday_than($day, $Yesterday)
{
    if ($day > $Yesterday) {
        $data['type'] = 1;
        if ($Yesterday != '0') {
            $data['than'] = round($day / $Yesterday * 100);
        } else {
            $data['than'] = $day;
        }
    } else {
        $data['type'] = 2;
        if ($day != '0') {
            $data['than'] = round($Yesterday / $day * 100);
        } else {
            $data['than'] = $Yesterday;
        }
    }
    return $data;

}

