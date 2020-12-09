<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/10
 * Time: 14:32
 */

namespace app\api\controller;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class DealApi extends Base
{

    //金币打赏
    public function coin_reward()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));
        $r_id = intval(input('param.r_id'));

        $user_info = check_login_token($uid, $token);

        //检查规则是否存在
        $reward_rule = db('reward_coin_rule')->find($r_id);
        if (!$reward_rule) {
            $result['code'] = 0;
            $result['msg'] = '打赏规则错误！';
            return_json_encode($result);
        }

        //减少用户的金额
        $dec_result = db('user')->where('id', '=', $uid)->setDec('coin', $reward_rule['reward_coin_num']);

        if (!$dec_result) {
            $result['code'] = 0;
            $result['msg'] = '余额不足，请充值！';
            return_json_encode($result);
        }

        $log_data = [
            'user_id' => $uid,
            'to_user_id' => $to_user_id,
            'reward_count' => $reward_rule['reward_coin_num'],
            'create_time' => NOW_TIME,
        ];

        $res = db('reward_coin_log')->insert($log_data);
        if (!$res) {
            $result['code'] = 0;
            $result['msg'] = '打赏失败！';
            return_json_encode($result);
        }

        //给主播增加收入
        db('user')->where('id', '=', $to_user_id)
            ->inc('income', $reward_rule['reward_coin_num'])
            ->inc('income_total', $reward_rule['reward_coin_num'])
            ->update();

        return_json_encode($result);

    }

    //私聊付费
    public function request_private_chat_pay()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = input('param.uid');
        $token = input('param.token');
        $to_user_id = input('param.to_user_id');

        $user_info = check_login_token($uid, $token, ['vip_end_time']);

      /*  //如果开通VIP直接可以发言不用付费
        if ($user_info['vip_end_time'] > NOW_TIME) {
            return_json_encode($result);
        }*/

        $config = load_cache('config');
        $charging_coin = $config['private_chat_money'];
        // 启动事务
        db()->startTrans();
        try {
            $charging_coin_res = db('user')->where(['id' => $uid])->setDec('coin', $charging_coin);
            
            if ($charging_coin_res) {
                //增加主播收益
                $income_totals = host_income_commission(5, $charging_coin, $to_user_id);
                //公会提成
                $guild_coin=sel_guild_log($to_user_id,$income_totals);
                $income_total=$income_totals -$guild_coin;

                db('user')->where(['id' => $to_user_id])->inc('income', $income_total)->inc('income_total', $income_total)->update();

                //增加私信付费记录
                $private_chat_log = [
                    'user_id' => $uid,
                    'to_user_id' => $to_user_id,
                    'coin' => $charging_coin,
                    'create_time' => NOW_TIME,
                ];
                $table_id = db('user_private_chat_log')->insertGetId($private_chat_log);

                //增加总消费记录
                $log_id=add_charging_log($uid, $to_user_id, 5, $charging_coin, $table_id, $income_total);
				
                //增加公会收益
                add_guild_log($to_user_id,$table_id,3,$income_totals,$income_total,$log_id);
                
            } else {

                $result['msg'] = "余额不足";
                $result['code'] = 10002;
            }

            // 提交事务
            db()->commit();
        } catch (\Exception $e) {

            $result['msg'] = "余额不足";
            $result['code'] = 10002;
            // 回滚事务
            db()->rollback();
        }

        return_json_encode($result);

    }

    //赠送背包礼物
    public function send_bag_gift()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));
        $channel = trim(input('param.channel'));

        $gid = intval(input('param.gid'));
        $count = intval(input('param.count'));

        if ($count == 0) {
            $result['code'] = 0;
            $result['msg'] = '礼物数量必须大于0';
            return_json_encode($result);
        }
        $user_info = check_login_token($uid, $token);

        $gift = load_cache('gift_id', ['id' => $gid]);
        if (!$gift) {
            $result['code'] = 0;
            $result['msg'] = '礼物信息不存在';
            return_json_encode($result);
        }

        $to_user_info = get_user_base_info($to_user_id);
        if ($to_user_info['is_auth'] != 1) {
            $result['code'] = 0;
            $result['msg'] = '对方未认证！';
            return_json_encode($result);
        }

        $bag = db('user_bag')->where(['uid' => $uid, 'giftid' => $gid])->find();

        if ($bag['giftnum'] < $count) {
            $result['code'] = 0;
            $result['msg'] = '礼物数量不足';
            return_json_encode($result);
        }

        $charging_coin = $count * $gift['coin'];

        // 启动事务
        db()->startTrans();
        try {
            $charging_coin_res = db('user_bag')->where(['uid' => $uid, 'giftid' => $gid])->setDec('giftnum', $count);

            if ($charging_coin_res) {
                $result['giftnum'] = $bag['giftnum'] - $count;
                //增加主播收益
                $income_totals = host_income_commission(3, $charging_coin, $to_user_id);
                //公会提成
                $guild_coin=sel_guild_log($to_user_id,$income_totals);
                $income_total=$income_totals -$guild_coin;

                db('user')->where(['id' => $to_user_id])->inc('income', $income_total)->inc('income_total', $income_total)->update();

                //增加送礼物记录
                $gift_log = [
                    'user_id' => $uid,
                    'to_user_id' => $to_user_id,
                    'gift_id' => $gift['id'],
                    'gift_name' => $gift['name'],
                    'gift_count' => $count,
                    'channel_id' => $channel,
                    'gift_coin' => $charging_coin,
                    'create_time' => NOW_TIME,
                    'profit' => $income_total,

                ];
                $table_id = db('user_gift_log')->insertGetId($gift_log);
               
                //增加总消费记录
                $log_id=add_charging_log($uid, $to_user_id, 3, $charging_coin, $table_id, $income_total);
                 //增加公会收益
                add_guild_log($to_user_id,$table_id,1,$income_totals,$income_total,$log_id);

                $result['send'] = $this->deal_send($uid, $to_user_id, $count, $channel, $user_info, $gift,$income_total);
                //全频道广播
                if(isset($gift['is_all_notify']) && $gift['is_all_notify'] == 1){
                    $this->push_all_gift_msg($user_info,$to_user_info,$count,$gift['name'],$gift['img']);
                }
            } else {

                $result['msg'] = "礼物数量不足";
                $result['code'] = 10002;
            }

            // 提交事务
            db()->commit();
        } catch (\Exception $e) {

            $result['msg'] = "礼物数量不足";
            $result['code'] = 10002;
            // 回滚事务
            db()->rollback();
        }

        return_json_encode($result);

    }

    //赠送礼物
    public function send_gift()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));
        $channel = trim(input('param.channel'));

        $gid = intval(input('param.gid'));
        $count = intval(input('param.count'));

        if ($count == 0) {
            $result['code'] = 0;
            $result['msg'] = '礼物数量必须大于0';
            return_json_encode($result);
        }

        $user_info = check_login_token($uid, $token);

        $gift = load_cache('gift_id', ['id' => $gid]);
        if (!$gift) {
            $result['code'] = 0;
            $result['msg'] = '礼物信息不存在';
            return_json_encode($result);
        }

        $to_user_info = get_user_base_info($to_user_id);
        if ($to_user_info['is_auth'] != 1) {
            $result['code'] = 0;
            $result['msg'] = '对方未认证！';
            return_json_encode($result);
        }

        $charging_coin = $count * $gift['coin'];

        if($user_info['coin'] < $charging_coin){
            $result['msg'] = "余额不足";
            $result['code'] = 10002;
            return_json_encode($result);
        }

        // 启动事务
        db()->startTrans();
        try {
            $charging_coin_res = db('user')->where(['id' => $uid])->setDec('coin', $charging_coin);

            if ($charging_coin_res) {
                $result['coin'] = $user_info['coin'] - $charging_coin;
                //增加主播收益
                $income_totals = host_income_commission(3, $charging_coin, $to_user_id);
                 //公会提成
                $guild_coin=sel_guild_log($to_user_id,$income_totals);
                $income_total=$income_totals -$guild_coin;

                db('user')->where(['id' => $to_user_id])->inc('income', $income_total)->inc('income_total', $income_total)->update();
                //增加送礼物记录
                $gift_log = [
                    'user_id' => $uid,
                    'to_user_id' => $to_user_id,
                    'gift_id' => $gift['id'],
                    'gift_name' => $gift['name'],
                    'gift_count' => $count,
                    'channel_id' => $channel,
                    'gift_coin' => $charging_coin,
                    'create_time' => NOW_TIME,
                    'profit' => $income_total,

                ];
                $table_id = db('user_gift_log')->insertGetId($gift_log);

               
                //增加总消费记录
               $log_id=  add_charging_log($uid, $to_user_id, 3, $charging_coin, $table_id, $income_total);
                 //增加公会收益
               add_guild_log($to_user_id,$table_id,1,$income_totals,$income_total,$log_id);

                $result['send'] = $this->deal_send($uid, $to_user_id, $count, $channel, $user_info, $gift,$income_total);
                //全频道广播
                if(isset($gift['is_all_notify']) && $gift['is_all_notify'] == 1){
                    $this->push_all_gift_msg($user_info,$to_user_info,$count,$gift['name'],$gift['img']);
                }
            } else {
                $result['msg'] = "余额不足";
                $result['code'] = 10002;
            }

            // 提交事务
            db()->commit();
        } catch (\Exception $e) {

            $result['msg'] = "操作失败，服务端出现错误！";
            $result['code'] = 10002;
            // 回滚事务
            db()->rollback();
        }

        return_json_encode($result);

    }

    //发送全局礼物消息
    private function push_all_gift_msg($send_user_info, $to_user_info, $count, $gift_name, $gift_icon)
    {

        $config = load_cache('config');

        $broadMsg['type'] = 777;
        $sender['id'] = $send_user_info['id'];
        $sender['user_nickname'] = $send_user_info['user_nickname'];
        $sender['avatar'] = $send_user_info['avatar'];
        $broadMsg['channel'] = 'all'; //通话频道
        $broadMsg['sender'] = $sender;
        $broadMsg['send_gift_info']['send_user_nickname'] = $send_user_info['user_nickname'];
        $broadMsg['send_gift_info']['send_user_id'] = $send_user_info['id'];
        $broadMsg['send_gift_info']['send_user_avatar'] = $send_user_info['avatar'];
        $broadMsg['send_gift_info']['send_to_user_id'] = $to_user_info['id'];
        $broadMsg['send_gift_info']['send_to_user_nickname'] = $to_user_info['user_nickname'];
        $broadMsg['send_gift_info']['send_to_user_avatar'] = $to_user_info['avatar'];
        $broadMsg['send_gift_info']['gift_icon'] = $gift_icon;

        $msg_str = $send_user_info['user_nickname'] . '送了' . $count . '个' . $gift_name . '给' . $to_user_info['user_nickname'];
        $broadMsg['send_gift_info']['send_msg'] = $msg_str;

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

        return $ret;

    }

    public function deal_send($user_id, $to_user_id, $num, $channel, $user_info, $gift,$income)
    {
        $total_coin = $gift['coin'] * $num;
        //$root['from_msg'] = $user_info['user_nickname'] . "送给你 " . $num . "个" . $gift['name'];
        $root['from_msg'] = "送出 " . $num . "个" . $gift['name'];
        $root['from_score'] = "你的经验值+" . $total_coin;
        $root['to_ticket'] = intval($total_coin);
        $root['to_diamonds'] = $gift['coin']; //可获得的：钻石数；只有红包时，才有
        $root['to_user_id'] = $to_user_id;
        $root['prop_icon'] = $gift['img'];
        $root['status'] = 1;
        $root['prop_id'] = $gift['id'];
        $root['to_msg'] = "收到" . $num . "个" . $gift['name'] . ",获得" . $income . '收益';
        $root['total_ticket'] = 0; //用户总的：印票数

        return $root;
    }
}