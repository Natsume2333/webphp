<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/2/26
 * Time: 14:42
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
use QcloudApi;
use think\Db;
use think\helper\Time;

class ShortVideoApi extends Base
{
    //获取上传视频sign
    public function get_upload_sign()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');

        $user_info = check_login_token($uid, $token);

        //检查是否认证
        $auth_status = db('user_auth_video')->where('user_id=' . $uid . ' and status=1')->find();
        if (!$auth_status) {
            $result['code'] = 0;
            $result['msg'] = '未认证无法上传视频！';
            return_json_encode($result);
        } elseif ($auth_status['sex'] == 1) {   //男性用户不能发视频
            $result['code'] = 0;
            $result['msg'] = '无权限上传视频！';
            return_json_encode($result);
        }


        //账号是否被禁用
        if ($user_info['user_status'] == 0) {
            $result['code'] = 0;
            $result['msg'] = "您因涉嫌违规，账号受限，请联系管理员!";
            return_json_encode($result);
        }

        $config = load_cache('config');
        // 确定APP的云API密钥
        $secret_id = $config['tencent_api_secret_id'];
        $secret_key = $config['tencent_api_secret_key'];

        // 确定签名的当前时间和失效时间
        $current = NOW_TIME;
        $expired = $current + 86400;  // 签名有效期：1天

        // 向参数列表填入参数
        $arg_list = array(
            "secretId" => $secret_id,
            "currentTimeStamp" => $current,
            "expireTime" => $expired,
            "random" => rand());

        // 计算签名
        $orignal = http_build_query($arg_list);
        $result['sign'] = base64_encode(hash_hmac('SHA1', $orignal, $secret_key, true) . $orignal);

        return_json_encode($result);
    }

    //视频费用检查
    public function video_coin_check()
    {
        $result = array('code' => 1, 'msg' => '');

        $money = intval(input('param.money'));

        $config = load_cache('config');
        //是否在合理范围内
        $range = explode('-', $config['video_coin_range']);
        if ($money > 0 && count($range) == 2) {

            if ($money < $range[0] || $money > $range[1]) {
                $result['code'] = 0;
                $result['msg'] = '短视频收费范围为' . $config['video_coin_range'] . '，请重新设置';
                return_json_encode($result);
            }
        }

        return_json_encode($result);
    }

    //添加短视频记录
    public function add_video()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $money = intval(input('param.money'));
        $video_id = input('param.video_id');
        $video_url = input('param.video_url');
        $cover_url = input('param.cover_url');
        $lng = input('param.lng');
        $lat = input('param.lat');
        $status = intval(input('param.status'));
        $title = input('param.title');

        $user_info = check_login_token($uid, $token);

        //检查是否认证
        //$auth_status = db('user_auth_video')->where('user_id', '=', $uid)->where('status', '=', 1)->find();
//        if($auth_status['status'] != 1){
//            $result['code'] = 0;
//            $result['msg'] = '未认证无法上传视频！';
//            return_json_encode($result);
//        }

        //检查当日上传视频次数限制
        $time = Time::today();
        $today_upload_count = db('user_video')->where('uid', '=', $uid)->where('addtime', 'between', [$time[0], $time[1]])->count();

        $config = load_cache('config');
        if ($today_upload_count >= $config['upload_short_video_day_max_count']) {
            $result['code'] = 0;
            $result['msg'] = '超过每日上传次数(' . $config['upload_short_video_day_max_count'] . '次)';
            return_json_encode($result);
        }

        if (empty($video_url)) {
            $result['code'] = 10101;
            $result['msg'] = '视频url为空';
            return_json_encode($result);
        }

        if (empty($cover_url)) {
            $result['code'] = 10102;
            $result['msg'] = '封面url为空';
            return_json_encode($result);
        }

        if (empty($video_id)) {
            $result['code'] = 10103;
            $result['msg'] = '视频ID为空';
            return_json_encode($result);
        }


        if (empty($title)) {
            $result['code'] = 0;
            $result['msg'] = '标题不能为空';
            return_json_encode($result);
        }

        $title = emoji_encode($title);

        if ($status == 2 && $money == 0) {

            $result['code'] = 0;
            $result['msg'] = '收费金额不能为0';
            return_json_encode($result);
        }

        $data = [
            'uid' => $uid,
            'title' => $title,
            'video_url' => $video_url,
            'img' => $cover_url,
            'coin' => $money,
            'addtime' => NOW_TIME,
            'status' => $status,
            'lng' => $lng,
            'lat' => $lat,
            'video_id' => $video_id,
        ];

        $res = Db::name('user_video')->insert($data);

        return_json_encode($result);

    }

    //删除视频
    public function del_video()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $video_id = input('param.video_id');

        $user_info = check_login_token($uid, $token);

        $video = Db::name('user_video')->where('uid', '=', $uid)->where('id', '=', $video_id)->find();
        if (!$video) {
            $result['code'] = 0;
            $result['msg'] = '删除失败';
            return_json_encode($result);
        }

//        require_once DOCUMENT_ROOT . '/system/qcloudapi_sdk/src/QcloudApi/QcloudApi.php';
//
//        $puc_config = load_cache('config');
//        $config = array('SecretId' => $puc_config['tencent_api_secret_id'],
//            'SecretKey' => $puc_config['tencent_api_secret_key'],
//            'RequestMethod' => 'GET',
//            'DefaultRegion' => 'gz');
//
//        $cvm = QcloudApi::load(QcloudApi::MODULE_VOD, $config);
//
//        $package = array('fileId' => $video['video_id'], 'priority' => 0);
//
//        $a = $cvm->DeleteVodFile($package);
//        // $a = $cvm->generateUrl('DescribeInstances', $package);
//
//        if ($a === false) {
//            $error = $cvm->getError();
//            //echo "Error code:" . $error->getCode() . ".\n";
//            //echo "message:" . $error->getMessage() . ".\n";
//            //echo "ext:" . var_export($error->getExt(), true) . ".\n";
//            $result['code'] = 0;
//            $result['msg'] = 'Error message' . $error->getMessage();
//            return_json_encode($result);
//        } else {
//
//            //删除视频
//            db('user_video')->where('uid', '=', $uid)->where('id', '=', $video_id)->delete();
//
//        }
        //删除视频
        db('user_video')->where('uid', '=', $uid)->where('id', '=', $video_id)->delete();

        return_json_encode($result);

    }

    //获取视频信息
    public function get_video()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = input('param.uid');
        $token = input('param.token');
        $video_id = input('param.video_id');

       $user_info = check_login_token($uid, $token);

        $video = db('user_video')->find($video_id);

        if($uid != $video['uid'] && $user_info['sex']==2){
            $result['code'] = 0;
            $result['msg'] = '同性之间不能观看视频！';
            return_json_encode($result);
        }

        if (!$video) {
            $result['code'] = 0;
            $result['msg'] = '获取视频信息错误';
            return_json_encode($result);
        }

        $user_info = get_user_base_info($uid, ['vip_end_time','host_one_video_proportion'], 1);
      
        //付费视频
        if ($video['status'] == 2 && $video['uid'] != $uid && $user_info['vip_end_time'] < NOW_TIME) {

            $pay_record = db('user_video_buy')
                ->where('uid', '=', $uid)
                ->where('videoid', '=', $video_id)
                ->find();
            if (!$pay_record) {

                $result['code'] = 10020;
                $result['msg'] = '付费视频请先购买';
                return_json_encode($result);
            }
        }

        $config = load_cache('config');

        $key = $config['tencent_video_sign_key'];

        //获取视频地址
        $video_url = $video['video_url'];
        
        /*if($user_info['host_one_video_proportion']){
            $result['coin'] =$user_info['host_one_video_proportion'];   //一对一视频通话
        }else{
            db('user')->where(['id' => $uid])->update(array('host_one_video_proportion'=>$config['video_deduction']));
              $result['coin'] =$config['video_deduction'];
        }*/
		$result['coin'] =!empty($user_info['host_one_video_proportion']) ? $user_info['host_one_video_proportion'] : 0;
        $result['video_url'] = get_sign_video_url($key, $video_url);
          $result['share'] = $video['share'];   //分享数
        //是否关注
        $result['is_follow'] = 0;
        $follow_record = db('user_video_attention')->where('uid', '=', $uid)->where('videoid', '=', $video_id)->find();
        if ($follow_record) {
            $result['is_follow'] = 1;
        }
        //获取视频点赞数
        $result['follow_num'] = db("user_video_attention")->where("videoid=$video_id")->count();
        //获取主播关注总数
        $result['host_count'] = db("user_attention")->where("attention_uid=" . $video['uid'])->count();

        //当前视频主播的信息
        $emcee_user_info = get_user_base_info($video['uid']);
        $result['avatar'] = $emcee_user_info['avatar'];
        $result['user_nickname'] = $emcee_user_info['user_nickname'];

        //观看数量+1
        db('user_video')->where(['id' => $video_id])->setInc('viewed', 1);
        return_json_encode($result);

    }

    //付费视频
    public function buy_video()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $video_id = input('param.video_id');

        $user_info = check_login_token($uid, $token);

        //查询视频是否存在
        $video = Db::name('user_video')->find($video_id);
        if (!$video) {
            $result['code'] = 0;
            $result['msg'] = '视频不存在';
            return_json_encode($result);
        }

        if ($user_info['coin'] < $video['coin']) {

            $result['code'] = 10002;
            $result['msg'] = '余额不足';
            return_json_encode($result);
        }

        //扣费购买视频
        $charge_res = db('user')->where('id', '=', $uid)->setDec('coin', $video['coin']);

        if (!$charge_res) {
            $result['code'] = 0;
            $result['msg'] = '扣费失败';
            return_json_encode($result);
        }

        //增加主播收益
        $income_total = host_income_commission(1, $video['coin'], $video['uid']);
        db('user')->where(['id' => $video['uid']])->inc('income', $income_total)->inc('income_total', $income_total)->update();

        //购买记录
        $video_pay_record = ['uid' => $uid, 'toid' => $video['uid'], 'videoid' => $video_id, 'coin' => $video['coin'], 'type' => 1, 'addtime' => time()];

        $buy_record_id = db('user_video_buy')->insertGetId($video_pay_record);

        add_charging_log($uid, $video['uid'], 1, $video['coin'], $buy_record_id, $income_total);

      

        return_json_encode($result);

    }

    //获取自己的视频
    public function get_video_list()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $p = intval(input('page'));

        $user_info = check_login_token($uid, $token);

        //查询该用户的视频列表
        $video_list = db('user_video')->where('uid', '=', $uid)->order('addtime desc')->page($p)->select();
        foreach ($video_list as &$v) {
            $v['title'] = emoji_decode($v['title']);
        }
        $result['list'] = $video_list;

        return_json_encode($result);
    }

    //获取其他用户的视频
    public function get_other_user_video_list()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));
        $p = intval(input('page'));

        $user_info = check_login_token($uid, $token);

        //查询该用户的视频列表
        $video_list = db('user_video')->where('type', '=', 1)->where('uid', '=', $to_user_id)->order('addtime desc')->page($p)->select();
        foreach ($video_list as &$v) {
            $v['title'] = emoji_decode($v['title']);
        }
        $result['list'] = $video_list;

        return_json_encode($result);
    }

    //视频点赞
    public function follow_video()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $video_id = intval(input('param.video_id'));

        $user_info = check_login_token($uid, $token);

        //查询视频是否存在
        $video = db('user_video')->find($video_id);
        if (!$video) {
            $result['code'] = 0;
            $result['msg'] = '视频不存在';
            return_json_encode($result);
        }

        //是否点过赞
        $follow_record = db('user_video_attention')->where('uid', '=', $uid)->where('videoid', '=', $video_id)->find();
        if ($follow_record) {
            $result['code'] = 0;
            $result['msg'] = '已经点赞过';
            return_json_encode($result);
        }

        //添加点赞记录
        $data = [
            'uid' => $uid,
            'videoid' => $video_id,
            'touid' => $video['uid'],
            'addtime' => time(),
        ];

        //点赞数量+1
        db('user_video')->where('id', '=', $video_id)->setInc('follow_num', 1);
        db('user_video_attention')->insert($data);
        return_json_encode($result);

    }


}