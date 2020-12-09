<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/5/16
 * Time: 22:41
 */

//$config = load_cache('config');
//$sdkappid = $config['tencent_sdkappid'];

//查询用户在线状态
function im_check_user_online_state($user_id){

    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
    $api = createTimAPI();
    $ret = $api->check_online_status($user_id);
    return $ret;
}

//IM禁言
function im_shut_up($user_id,$time){

    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
    $api = createTimAPI();
    $ret = $api->shut_up($user_id, $time);
    return $ret;
}

//type 14 一对一视频消息挂断推送
function end_video_call($user_id,$to_user_id,$video_call_info){

    $ext['type'] = 14;
    $sender['id'] = $user_id;
    $sender['user_nickname'] = '';
    $sender['avatar'] = '';
    $ext['channel'] = $video_call_info['channel_id']; //通话频道
    $ext['sender'] = $sender;
    $ext['reply_type'] = 1;

    $ser = open_one_im_push($user_id,$to_user_id, $ext);
}

//type 13 一对一视频消息挂断推送
function huang_video_call($user_id,$to_user_id,$video_call_info){

    $ext['type'] = 13;
    $sender['id'] = $user_id;
    $sender['user_nickname'] = '';
    $sender['avatar'] = '';
    $ext['channel'] = $video_call_info['channel_id']; //通话频道
    $ext['sender'] = $sender;
    $ext['reply_type'] = 2;

    $ser = open_one_im_push($user_id,$to_user_id, $ext);
}

//发送文本消息
function send_c2c_text_msg($user_id,$to_user_id,$msg){

    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
    $api = createTimAPI();
    $api -> openim_send_msg($user_id,$to_user_id,$msg);

}

//修改IM用户资料信息
function update_im_user_info($account_id){

    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');

    $api = createTimAPI();

    $user_info = get_user_base_info($account_id,1);
    #构造高级接口所需参数
    $profile_list = array();
    $profile_nick = array(
        "Tag" => "Tag_Profile_IM_Nick",
        "Value" => $user_info['user_nickname']
    );
    $profile_avatar = array(
        "Tag" => "Tag_Profile_IM_Image",
        "Value" => $user_info['avatar']
    );
    array_push($profile_list, $profile_nick);
    array_push($profile_list, $profile_avatar);

    $ret = $api->profile_portrait_set2((string)$account_id, $profile_list);

    return $ret;
}


function open_one_im_push($account_id,$receiver,$ext)
{
    require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');

    $msg_content = array();
    //创建array 所需元素
    $msg_content_elem = array(
        'MsgType' => 'TIMCustomElem',       //自定义类型
        'MsgContent' => array(
            'Data' => json_encode($ext),
            'Desc' => '',
        )
    );
    array_push($msg_content, $msg_content_elem);

    $api = createTimAPI();

    $ret = $api->openim_send_msg2($account_id, $receiver, $msg_content);
    //dump($msg_content);exit;
    return $ret;
}

/**
 * 批量发消息(高级接口)
 * @param array $account_list 接收消息的用户id集合
 * @param array $msg_content 消息内容, php构造示例:
 *
 *   $msg_content = array();
 *   //创建array 所需元素
 *   $msg_content_elem = array(
 *       'MsgType' => 'TIMTextElem',       //文本??型
 *       'MsgContent' => array(
 *       'Text' => "hello",                //hello 为文本信息
 *      )
 *   );
 *   //将创建的元素$msg_content_elem, 加入array $msg_content
 *   array_push($msg_content, $msg_content_elem);
 *
 * @return array 通过解析REST接口json返回包得到的关联数组, 其中包含成功与否、及错误提示(如果有错误)等字段
 */
function open_all_im_push($account_list, $ext){

    require_once(DOCUMENT_ROOT . '/system/tim/TimRestApi.php');

    $msg_content = array();
    //创建array 所需元素
    $msg_content_elem = array(
        'MsgType' => 'TIMCustomElem',       //自定义类型
        'MsgContent' => array(
            'Data' => json_encode($ext),
            'Desc' => '',
        )
    );
    array_push($msg_content, $msg_content_elem);

    $api = createTimAPI();
    $ret = $api->openim_batch_sendmsg2($account_list, $msg_content);
    return $ret;

}