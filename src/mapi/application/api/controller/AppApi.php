<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/1
 * Time: 15:20
 */

namespace app\api\controller;

use Qiniu\Auth;
use think\Config;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class AppApi extends Base
{

    //获取七牛上传token
    public function get_qiniu_upload_token()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['image_label']);

        $qiniu_config = get_qiniu_config();

        require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = $qiniu_config['accessKey'];
        $secretKey = $qiniu_config['secretKey'];
        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        // 要上传的空间
        $result['bucket'] = $qiniu_config['bucket'];
        $result['domain'] = $qiniu_config['domain'];
        $result['token'] = $auth->uploadToken($result['bucket']);

        return_json_encode($result);
    }

    //配置文件
    public function config()
    {
		
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $config = load_cache('config');
        
        //心跳
        $data['heartbeat'] = $config['heartbeat_interval'];
        $data['group_id'] = $config['acquire_group_id'];
        $data['sdkappid'] = $config['tencent_sdkappid'];
        $data['accountType'] = $config['accountType_one'];
        $data['private_photos'] = $config['private_photos'];
        $data['app_qgorq_key'] = $config['app_qgorq_key'];
        $data['video_deduction'] = $config['video_deduction'];
        $data['tab_live_heart_time'] = $config['tab_live_heart_time'];
        $data['system_message'] = $config['system_message'];
        $data['currency_name'] = $config['currency_name'];
		

        /*       $data['system_message_color'] = $config['system_message_color'];//系统消息文本颜色，
               $data['text_message_name_color'] = $config['text_message_name_color'];//文字消息名称颜色，
               $data['text_message_text_color'] = $config['text_message_text_color'];//文字消息文本颜色，
               $data['name_color_gift_message'] = $config['name_color_of_the_gift_message'];//礼物消息名称颜色，
               $data['text_color_gift_message'] = $config['text_color_of_the_gift_message'];//礼物消息文本颜色

       */
        /*---------0608新增-----------*/
        $data['is_open_chat_pay'] = $config['is_open_chat_pay'];
        $data['private_chat_money'] = $config['private_chat_money'];
        $data['video_call_msg_alert'] = $config['video_call_msg_alert'];
        /*--------------------*/

        /*----------1004新增----------------*/

        $data['open_login_qq'] = $config['open_login_qq'];
        $data['open_login_wx'] = $config['open_login_wx'];
        $data['open_login_facebook'] = $config['open_login_facebook'];

        $data['open_pay_pal'] = 0;
        $data['pay_pal_client_id'] = '';
        if (defined('OPEN_PAY_PAL') && OPEN_PAY_PAL == 1) {
            $data['open_pay_pal'] = 1;
            $data['pay_pal_client_id'] = $config['pay_pal_client_id'];
        }

        $data['open_sandbox'] = 0;
        if (defined('OPEN_SANDBOX') && OPEN_SANDBOX == 1) {
            $data['open_sandbox'] = 1;
        }

        $data['open_auto_see_hi_plugs'] = 0;
        if (defined('OPEN_AUTO_SEE_HI_PLUGS') && OPEN_AUTO_SEE_HI_PLUGS == 1) {
            $data['open_auto_see_hi_plugs'] = 1;
        }


        /*----------1017新增----------------*/
        $data['share_title'] = $config['share_title'];
        $data['share_content'] = $config['share_content'];

        /*---------------------------------*/

        //短视频上传时长限制（秒）
        $data['upload_short_video_time_limit'] = $config['upload_short_video_time_limit'];

        $data['upload_certification'] = $config['upload_certification'];

        //鉴黄设置
        $data['is_open_check_huang'] = 0;
        if (isset($config['is_open_check_huang'])) {
            $data['is_open_check_huang'] = $config['is_open_check_huang'];
        }

        $data['check_huang_rate'] = 10;
        if (isset($config['check_huang_rate']) && $config['check_huang_rate'] > 0) {
            $data['check_huang_rate'] = $config['check_huang_rate'];
        }
        //上传到七牛或本地 0本地 1七牛
        $data['upload_type'] =0;
        $option_value = db('option')->where("option_name ='storage'")->value('option_value');
         if (!empty($option_value)) {
            $optionValue = json_decode($option_value, true);
            if($optionValue["type"] == 'Qiniu'){
                $data['upload_type'] =1;
            }
        }
        //认证类型
        if (isset($config['auth_type'])) {
            $data['auth_type'] = $config['auth_type'];
        }
        //邀请提现规则
        $data['invitation_withdrawal_rules'] = $config['invitation_withdrawal_rules'];
        //收益提现规则
        $data['earnings_withdrawal_rules'] = $config['earnings_withdrawal_rules'];

        //布谷科技美颜SDK密钥
        if (isset($config['bogokj_beauty_sdk_key'])) {
            $data['bogokj_beauty_sdk_key'] = $config['bogokj_beauty_sdk_key'];
        }

        //是否开启了查看联系方式插件
        $data['open_select_contact'] = 0;
        if (defined('OPEN_BUY_CONTACT_PLUGS') && OPEN_BUY_CONTACT_PLUGS == 1) {
            $data['open_select_contact'] = 1;
        }

        //分享主域名
        $data['share_url_domain'] = $config['share_url_domain'];

        //客户端h5链接
        $data['app_h5'] = array(
            'newbie_guide' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/novice_guide_api/index', //新手引导
            'my_level' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/level_api/index', //我的等级 用户uid   token
            'invite_friends' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/invite_api/index', //邀请好友 用户uid
            'disciple_contribution' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/disciple_api/index', //徒弟贡献榜 用户uid token
            'my_detail' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/detail_api/defaults', //我的明细 	用户uid 不填是所有的 1聊币2积分
            'system_message' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/system_message_api/index',
            'about_me' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/novice_guide_api/content/id/6.html', //关于我们
            'invite_reg_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/login_api/share_reg_new', //邀请注册链接
            'private_clause_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/novice_guide_api/content/id/7.html', //隐私条款
            'invite_share_menu' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/invitation_api/index', //邀请收益
            'online_custom_service' => 'http://' . $_SERVER['HTTP_HOST'] . ':9010/client', //客服地址
            'vip_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/vip_api/index',
            'notice_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/notice_api/index',
            'turntable_url' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/turntable_api/index',
            'user_withdrawal' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/withdrawal_api/index',
            //收费设置规格
            'user_fee' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/level_api/fee',
            //意见反馈
            'user_feedback' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/feedback_api/index',
            //我的守护
            'my_guardian' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/guardian_api/guardian',
            //守护主播列表(传值hostid 主播id)
            'guardian_list' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/guardian_api/index',
            //设置联系方式
            'set_contact' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/contact_buy_func_plugs_api/bind_contact',
            //签到  (传值uid token)
            'sign_in' => 'http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/Sign_In_api/index',


        );

        //查询开屏广告
        //$splash = db('slide_item')->find(6);
        $splash = db('slide_item')->where('slide_id=3')->find();
        
        $data['splash_url'] = '';
        $data['splash_img_url'] = '';
        $data['splash_content'] = '';

        if ($splash) {
            $data['splash_url'] = $splash['url'];
            $data['splash_img_url'] = $splash['image'];
            $data['splash_content'] = $splash['content'];
        }

        //是否开启了自定义金额
        $data['open_custom_video_charge_coin'] = 0;
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
            $data['open_custom_video_charge_coin'] = 1;
        }

        //是否开启邀请模块
        $data['open_invite'] = 0;
        if (defined('OPEN_INVITE') && OPEN_INVITE == 1) {
            $data['open_invite'] = 1;
        }


        //是否开启签到功能
        $data['is_open_sign_in'] = 0;
        if (defined('OPEN_SIGN_IN') && OPEN_SIGN_IN == 1) {
            $data['is_open_sign_in'] = 1;
        }
        //是否开启工会模块
        $data['is_open_union'] = 0;
        if (defined('OPEN_THE_UNION') && OPEN_THE_UNION == 1) {
            $data['is_open_union'] = 1;
        }

        /*--------0816新增----------*/
        $data['open_video_chat'] = 0;
        if (defined('OPEN_VIDEO_CHAT') && OPEN_VIDEO_CHAT == 1) {
            $data['open_video_chat'] = 1;
        }

        //脏字库
        $data['dirty_word'] = '';
        if ($config['dirty_word']) {
            $data['dirty_word'] = $config['dirty_word'];
        }

        //客服QQ
        $data['custom_service_qq'] = $config['custom_service_qq'];

        //版本控制
        $data['android_download_url'] = $config['android_download_url'];
        $data['android_app_update_des'] = $config['android_app_update_des'];
        $data['android_version'] = $config['android_version'];

        $data['ios_download_url'] = $config['ios_download_url'];
        $data['ios_app_update_des'] = $config['ios_app_update_des'];
        $data['ios_version'] = $config['ios_version'];

        $data['is_force_upgrade'] = $config['is_force_upgrade'];

        //是否开启ios上架审核
        $data['is_ios_base'] = $config['is_grounding'];

        //是否强制绑定手机号
        $data['is_binding_mobile'] = $config['is_binding_mobile'];

        //查询首页星级列表
        if (defined('OPEN_STAR') && OPEN_STAR == 1) {
            $data['star_level_list'] = db('user_star_level')->field('level_name,id')->select();
        }
        
       

        require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
        
        //创建群组
        $api = createTimAPI();
        #构造高级接口所需参数
        $info_set = array(
            'group_id' => $config['acquire_group_id'],
            'introduction' => null,
            'notification' => null,
            'face_url' => null,
            'max_member_num' => 500,
        );
        $mem_list = array();
        $ret = $api->group_create_group2('ChatRoom',$config['acquire_group_id'],$config['tencent_identifier'],$info_set,$mem_list);
        // 创建在线广播大群
        $ret = $api->full_group_create($config['acquire_group_id'], '');
		
        $result['data'] = $data;
        return_json_encode($result);
    }

    //心跳间隔时间 Redis 存储
    public function interval()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = input('param.uid');
        $token = input('param.token');

        //update_heartbeat($uid);
        check_login_token($uid, $token);
        return_json_encode($result);
    }

    public function test2()
    {
        $user_id = 100191;

        require_once(DOCUMENT_ROOT . '/system/tim/TimApi.php');
        $api = createTimAPI();

    }

    public function test()
    {
        $config = load_cache('config');

        $user_info = get_user_base_info(1);
        //发送群频道通知
        $broadMsg['type'] = 778;
        $sender['id'] = $user_info['id'];
        $sender['user_nickname'] = $user_info['user_nickname'];
        $sender['avatar'] = $user_info['avatar'];
        $broadMsg['channel'] = 'all'; //通话频道
        $broadMsg['sender'] = $sender;
        $msg_str = '土豪' . $user_info['user_nickname'] . '开通了尊贵VIP';
        $broadMsg['s']['send_msg'] = $msg_str;
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

        exit;
    }
    //上传图片返回图片路径(本地上传)
    public function local_upload(){
        $result = array('code' => 1, 'msg' => '');
        //上传到七牛或本地 0本地 1七牛
    /*    $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token);*/
        $option_value = db('option')->where("option_name ='storage'")->value('option_value');
         if (!empty($option_value)) {
            $optionValue = json_decode($option_value, true);
            if($optionValue["type"] == 'Qiniu'){
                $result['code'] = 0;
                $result['msg'] = '上传第三方失败';
                return_json_encode($result);
            }
        }

        $file_list =  $_FILES['img']; 
        $data=local_upload($file_list);
        if($data){
            $result['url'] = $data;
        }else{
            $result['code'] = 0;
            $result['msg'] = '上传失败';
        }
        return_json_encode($result);
    }
}