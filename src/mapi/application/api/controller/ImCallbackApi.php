<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/5/21
 * Time: 10:20
 */

namespace app\api\controller;

use BuguPush;
use UserOnlineStateRedis;
use VideoCallRedis;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
header("Content-Type:text/html; charset=utf-8");

class ImCallbackApi extends Base
{

    public function callback()
    {

        require_once DOCUMENT_ROOT . '/system/umeng/BuguPush.php';

        $json = $GLOBALS['HTTP_RAW_POST_DATA'];
        $post = json_decode($json, true);

        file_put_contents('./im_callback.txt', $json);
        if ($post['CallbackCommand'] == 'C2C.CallbackAfterSendMsg') {
            if ($post['MsgBody'][0]['MsgType'] == 'TIMTextElem') {

                //普通私信消息
                $config = load_cache('config');
                $push = new BuguPush($config['umengapp_key'], $config['umeng_message_secret']);
                $custom = [
                    'action' => 1,
                    'user_id' => $post['From_Account']
                ];

//                if(!is_online($post['To_Account'],$config['heartbeat_interval'])){
//
//                    $push -> sendAndroidCustomizedcast('go_app',$post['To_Account'],'buguniao','私信消息','你有新的消息',$post['MsgBody'][0]['MsgContent']['Text'],json_encode($custom));
//                }
                $push->sendAndroidCustomizedcast('go_app', $post['To_Account'], 'buguniao', '私信消息', '你有新的消息', $post['MsgBody'][0]['MsgContent']['Text'], json_encode($custom));
                $push->sendIOSCustomizedcast('go_app', $post['To_Account'], 'buguniao', '私信消息', '你有新的消息', $post['MsgBody'][0]['MsgContent']['Text'], json_encode($custom));

                //自定义回复消息功能测试
                if (defined('OPEN_CUSTOM_AUTO_REPLY') && OPEN_CUSTOM_AUTO_REPLY == 1) {
                    require_once(DOCUMENT_ROOT . '/system/im_common.php');

                    //->where('is_online', '=', 1) 自动回复不需要在线
                    $emcee = db('user')->where('is_open_auto_see_hi', '=', 1)->where('is_auth', '=', 1)->where('id', '=', $post['To_Account'])->find();
                    if ($emcee) {
                        $auto_msg_record = db('custom_auto_msg')->where('user_id', '=', $emcee['id'])->find();
                        if ($auto_msg_record) {
                            $auto_msg_array = [];
                            foreach ($auto_msg_record as $k2 => $v2) {
                                if (strripos($k2, 'ply_msg') > 0 && !empty($v2)) {
                                    $auto_msg_array[] = $v2;
                                }
                            }

                            $msg = $auto_msg_array[rand(0, count($auto_msg_array) - 1)];
                            if (!empty($msg)) {
                                send_c2c_text_msg($post['To_Account'], $post['From_Account'], $msg);
                            }
                        }
                    }
                }

            } else if ($post['MsgBody'][0]['MsgType'] == 'TIMCustomElem') {

                $data = $post['MsgBody'][0]['MsgContent']['Data'];
                $data = json_decode($data, true);

                $config = load_cache('config');

                $push = new BuguPush($config['umengapp_key'], $config['umeng_message_secret']);

                if ($data['type'] == 23) {//赠送礼物
                    $custom = [
                        'action' => 1,
                        'user_id' => $post['From_Account']
                    ];
                    $push->sendAndroidCustomizedcast('go_app', $post['To_Account'], 'buguniao', '礼物消息', '收到礼物打赏', $data['to_msg'], json_encode($custom));
                    $push->sendIOSCustomizedcast('go_app', $post['To_Account'], 'buguniao', '礼物消息', '收到礼物打赏', $data['to_msg'], json_encode($custom));

                } elseif ($data['type'] == 12) {
                    $custom = [
                        'action' => 12,
                        'user_id' => $post['From_Account'],
                        'custom_data' => json_encode($data),
                    ];
                    $push->sendAndroidCustomizedcast('go_custom', $post['To_Account'], 'buguniao', '通话消息', '新的通话消息，点击查看', '用户：' . $data['sender']['user_nickname'], json_encode($custom));
                    $push->sendIOSCustomizedcast('go_custom', $post['To_Account'], 'buguniao', '通话消息', '新的通话消息，点击查看', '用户：' . $data['sender']['user_nickname'], json_encode($custom));

                }

            }

        } else if ($post['CallbackCommand'] == 'State.StateChange') {

            $user_id = $post['Info']['To_Account'];
            $action = $post['Info']['Action'];

            require_once DOCUMENT_ROOT . '/system/redis/UserOnlineStateRedis.php';

            $user_online_redis = new UserOnlineStateRedis();
            $user_online_redis->change_state($user_id, $action);


            if ($action == 'Logout') {
                $video_record = db('video_call_record')->whereOr(['user_id' => $user_id])->whereOr(['call_be_user_id' => $user_id])->whereOr(['anchor_id' => $user_id])->select();

                if ($video_record) {
                    db('video_call_record_log')->insert($video_record[0]);

                    require_once DOCUMENT_ROOT . '/system/redis/VideoCallRedis.php';
                    $video_call_redis = new VideoCallRedis();
                    $video_call_redis->del_call($video_record[0]['anchor_id']);
                    $video_call_redis->del_call($video_record[0]['user_id']);
                    $video_call_redis->del_call($video_record[0]['call_be_user_id']);

                    require_once DOCUMENT_ROOT . '/system/im_common.php';

                    if ($video_record[0]['status'] == 1) {
                        end_video_call($user_id, $video_record[0]['user_id'], $video_record[0]);
                        end_video_call($user_id, $video_record[0]['call_be_user_id'], $video_record[0]);
                    } else {
                        huang_video_call($user_id, $video_record[0]['user_id'], $video_record[0]);
                        huang_video_call($user_id, $video_record[0]['call_be_user_id'], $video_record[0]);
                    }


                }
                //删除通话记录
                db('video_call_record')->whereOr(['user_id' => $user_id])->whereOr(['call_be_user_id' => $user_id])->whereOr(['anchor_id' => $user_id])->delete();

                //查询没有下线的记录
                $online_record = db('online_record')->where('user_id', '=', $user_id)->where('offline_time', '=', 0)->find();
                if ($online_record) {
                    //更新下线时间
                    $online_time = NOW_TIME - $online_record['up_online_time'];
                    $update_online_data = ['offline_time' => NOW_TIME, 'time' => $online_time];
                    db('online_record')->where('id=' . $online_record['id'])->update($update_online_data);
                }

            } else {
                //增加上下线记录
                db('online_record')->insert(['user_id' => $user_id, 'up_online_time' => NOW_TIME]);
            }

        }

        echo json_encode(['ActionStatus' => 'OK', 'ErrorCode' => 0, 'ErrorInfo' => '']);

    }

}