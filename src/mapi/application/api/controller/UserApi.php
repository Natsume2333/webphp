<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/2/22
 * Time: 23:33
 */

namespace app\api\controller;

use think\Db;
use think\helper\Time;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class UserApi extends Base
{

    //用户是否绑定手机号码
    public function is_binding_mobile()
    {
        $result = array('code' => 1, 'msg' => '', 'status' => '1');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['mobile']);
        $config = load_cache('config');

        $result['is_force_binding_mobile'] = $config['is_binding_mobile'];
        if ($user_info['mobile']) {
            $result['is_binding_mobile'] = 1;      //已绑定
        } else {
            $result['is_binding_mobile'] = 0;      //未绑定手机号
        }
        return_json_encode($result);
    }

    //绑定手机号
    public function binging_mobile()
    {
        $result = array('code' => 0, 'msg' => '绑定手机号码成功！');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $mobile = trim(input('param.mobile'));
        $code = trim(input('param.code'));
        if (!is_numeric($mobile) || strlen($mobile) != 11) {
            $result['msg'] = '手机号码不正确！';
            return_json_encode($result);
        }
        if ($code == 0) {
            $result['code'] = 0;
            $result['msg'] = '验证码错误！';
            return_json_encode($result);
        }
        $ver = db('verification_code')->where("code='$code' and account='$mobile' and expire_time > " . NOW_TIME)->find();
        if (!$ver) {
            $result['code'] = 0;
            $result['msg'] = "验证码错误，请重新获取！";
            return_json_encode($result);
        }
        $data = db('user')->where("id=$uid and token='" . $token . "'")->update(array("mobile" => $mobile));
        if (!$data) {
            $result['msg'] = '绑定手机号码失败！';
            return_json_encode($result);
        }
        $result['code'] = 1;
        return_json_encode($result);
    }

    //获取形象标签列表
    public function image_labels()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $user_info = check_login_token($uid, $token, ['image_label']);
        $type = $user_info['sex'] == 1 ? 0 : 1;
        $list = db('evaluate_label')->where("type=" . $type)->select();
//        $list = db('user_image_label')->where("type=" . $type)->select();
//        $image_label = explode("-", $user_info['image_label']);
        foreach ($list as &$v) {
            $v['checked'] = 0;
//            if (in_array($v['name'], $image_label)) {
//                $v['checked'] = 1;      //是否选中 是
//            }
        }
        $result['data'] = $list;
        return_json_encode($result);
    }

    //修改形象标签
    public function upd_image_labels()
    {
        $result = array('code' => 1, 'msg' => '', 'image_label' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $image_label_json = trim(input('param.image_label'));    //形象标签id  一位数组
        $user_info = check_login_token($uid, $token, ['image_label']);

        $type = $user_info['sex'] == 1 ? 0 : 1;
        $list = db('evaluate_label')->where("type=" . $type)->select();

        $name = '';
        $image_label = explode(",", $image_label_json);

        if (!is_array($image_label)) {
            $result['code'] = 0;
            $result['msg'] = '传参错误';
            return_json_encode($result);
        }
        foreach ($list as $v) {
            if (in_array($v['id'], $image_label)) {
                $name .= $v['label_name'] . "-";
            }
        }

        $label = substr($name, 0, -1);

        if ($user_info['image_label'] == $label) {
            $result['image_label'] = $label;
            return_json_encode($result);
        }
        //var_dump($user_info);die;
        //更新修改信息
        $data = db('user')->where("id=$uid")->update(array("image_label" => $label));
        if (!$data) {
            $result['code'] = 0;
            $result['msg'] = '修改失败';
            return_json_encode($result);
        }

        $result['image_label'] = $label;

        return_json_encode($result);
    }

    //提交认证信息 最新ui
    public function app_request_submit_auth_info()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        if ($user_info['sex'] == 1) {
            $result['code'] = 0;
            $result['msg'] = '男性不能认证！';
            return_json_encode($result);
        }
        $is_auth = db('auth_form_record')->where('user_id', '=', $uid)->find();
        if ($is_auth && $is_auth['status'] != 2) {
            $result['code'] = 0;
            $result['msg'] = '已经提交过认证信息，请勿重复提交!';
            return_json_encode($result);
        }
        $nickname = trim(input('param.nickname'));
        $auth_id_card_img_url1 = trim((input(('param.auth_id_card_img_url1'))));
        $auth_id_card_img_url2 = trim((input(('param.auth_id_card_img_url2'))));
        if (empty($nickname)) {
            $result['code'] = 0;
            $result['msg'] = '请输入昵称';
            return_json_encode($result);
        }
        if (empty($auth_id_card_img_url1)) {
            $result['code'] = 0;
            $result['msg'] = '请上传身份证正面';
            return_json_encode($result);
        }
        if (empty($auth_id_card_img_url2)) {
            $result['code'] = 0;
            $result['msg'] = '请上传身份证反面';
            return_json_encode($result);
        }
        $insert_data = [
            'user_nickname' => $nickname,
            'user_id' => $uid,
            'status' => 0,
            'auth_id_card_img_url1' => $auth_id_card_img_url1,
            'auth_id_card_img_url2' => $auth_id_card_img_url2,
            'create_time' => NOW_TIME,
        ];
        if ($is_auth['status'] == 2) {
            db('auth_form_record')->where('user_id', '=', $uid)->delete();
        }
        if (!$is_auth || $is_auth['status'] == 2) {
            $res = db('auth_form_record')->insert($insert_data);
        } else {

            $res = db('auth_form_record')->where("id=" . $is_auth['id'])->update($insert_data);
        }

        if (!$res) {
            $result['code'] = 0;
            $result['msg'] = '提交失败，请稍后重试！';
        }
        return_json_encode($result);
    }


    //修改用户信息
    public function app_update_user_info()
    {

        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $id = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $del = input("param.del");
        $sex = intval(input("param.sex"));
        $user_nickname = trim(input('param.user_nickname'));
        $sign = trim(input('param.sign'));

        $config = load_cache('config');
        $dirty_word = explode(",", $config['dirty_word']);    //藏字库

        $auth = [];
        if (trim((input(('param.phone'))))) {
            $auth['phone'] = trim((input(('param.phone'))));
        }
        if (trim((input(('param.height'))))) {
            $auth['height'] = trim((input(('param.height'))));
        }
        if (trim((input(('param.weight'))))) {
            $auth['weight'] = trim((input(('param.weight'))));
        }
        if (trim((input(('param.constellation'))))) {
            $auth['constellation'] = trim((input(('param.constellation'))));
        }
        if (trim((input(('param.city'))))) {
            $auth['city'] = trim((input(('param.city'))));
        }
        if (trim((input(('param.introduce'))))) {
            $auth['introduce'] = trim((input(('param.introduce'))));
        }

        if (trim((input(('param.self_label'))))) {
            $auth['sign'] = trim((input(('param.self_label'))));

        }
        $user_info = check_login_token($id, $token);

        $delete_ids = explode('|', $del);
        if (count($delete_ids) > 0) {

            $delete_img_list = Db::name('user_img')->where('uid', '=', $id)->where('id', 'in', $delete_ids)->find();

            if ($delete_img_list) {
                $delete_img_path_list = [];
                foreach ($delete_img_list as $k => $v) {
                    $file_name = parse_url($v['img'])['path'];
                    $delete_img_path_list[] = substr($file_name, 1, strlen($file_name));

                }
                $oss_delete_res = oss_del_list($delete_img_path_list);
                if ($oss_delete_res) {
                    //删除轮播图
                    Db::name('user_img')->where('uid', '=', $id)->where('id', 'in', $delete_ids)->delete();
                }
            }
        }
        //修改认证内容
        if (count($auth) > 0) {
            db('auth_form_record')->where("user_id=$id")->update($auth);
        }
        //修改昵称
        if (!empty($user_nickname)) {
            $all_name = Db::name('user')->where("user_nickname='$user_nickname' and id!=$id")->find();
            if ($all_name) {
                $result['code'] = 0;
                $result['msg'] = "用户名重复，请重新输入用户名";
                return_json_encode($result);
            }
            $data['user_nickname'] = $user_nickname;
        }

        $data['sex'] = $sex;

        //修改签名
        if (!empty($sign)) {
            $data['signature'] = $sign;
        }

        //上传头像
        $avatar = request()->file('avatar'); //获取头像
        if ($avatar) {
            $upload_one = oss_upload($avatar); //单图片上传
            $data['avatar'] = $upload_one;
        }
        //藏字库判断
        foreach ($dirty_word as $v) {
            //昵称 
            if (stristr($user_nickname, $v)) {
                $result['code'] = 0;
                $result['msg'] = "昵称中不能带有" . $v . "的字";
                return_json_encode($result);
            }
            //签名
            if (stristr($sign, $v)) {
                $result['code'] = 0;
                $result['msg'] = "个性签名中不能带有" . $v . "的字";
                return_json_encode($result);
            }
            if (trim((input(('param.self_label'))))) {
                $auth['sign'] = trim((input(('param.self_label'))));
                if (stristr($auth['sign'], $v)) {
                    $result['code'] = 0;
                    $result['msg'] = "签名中不能带有" . $v . "的字";
                    return_json_encode($result);
                }
            }
        }

        $new_image = array();
        for ($i = 0; $i < 6; $i++) {
            $img = request()->file('img' . $i);
            if ($img) {
                $new_image[$i] = $img;
            }
        }

        if ($new_image) {
            $all_img = Db::name('user_img')->where("uid=$id")->count();
            if ((count($new_image) + $all_img) > 6) {
                $result['code'] = 0;
                $result['msg'] = "多图片添加失败,请删除后在添加";
                return_json_encode($result);
            }

            foreach ($new_image as $v) {
                $uploads = oss_upload($v); //单图片上传
                $upload_all['img'][]['img'] = $uploads;
            }
        }

        //更新修改信息
        db('user')->where("id=$id and token='$token'")->update($data);
        //echo db('user') -> getLastSql();exit;

        if ($new_image && isset($upload_all)) {

            foreach ($upload_all['img'] as &$v) {
                $v['uid'] = $id;
                $v['addtime'] = NOW_TIME;
                $data['img'][]['img'] = $v['img'];
            }
            $all_img = Db::name('user_img')->insertAll($upload_all['img']); //添加轮播图
            if (!$all_img) {
                $result['code'] = 0;
                $result['msg'] = "用户信息保存失败";
                return_json_encode($result);
            }
        }

        require_once DOCUMENT_ROOT . '/system/im_common.php';
        update_im_user_info($id);

        $result['code'] = 1;
        $result['msg'] = "修改成功";
        $result['data'] = $data;
        return_json_encode($result);

    }

    //显示修改的用户信息
    public function app_edit_user_info()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = input('param.uid');
        $token = input('param.token');

        $user_info = check_login_token($uid, $token, ['last_change_name_time']);

        //获取认证信息
        $user_auth_info = db('auth_form_record')->where('user_id', '=', $uid)->find();

        $data = array(
            'sex' => $user_info['sex'],
            'user_nickname' => $user_info['user_nickname'],
            'avatar' => $user_info['avatar'],
            'is_change_name' => 0,

        );

        $data['sign'] = $user_info['signature'];

        $data['phone'] = $user_auth_info ? $user_auth_info['phone'] : '';
        $data['height'] = $user_auth_info ? $user_auth_info['height'] : '';
        $data['weight'] = $user_auth_info ? $user_auth_info['weight'] : '';
        $data['constellation'] = $user_auth_info ? $user_auth_info['constellation'] : '';
        $data['city'] = $user_auth_info ? $user_auth_info['city'] : '';
        $data['image_label'] = $user_auth_info ? $user_auth_info['image_label'] : '';
        $data['introduce'] = $user_auth_info ? $user_auth_info['introduce'] : '';
        $data['self_label'] = $user_auth_info ? $user_auth_info['sign'] : '';

        //是否可以修改昵称
        if ((NOW_TIME - $user_info['last_change_name_time']) > (30 * 24 * 60 * 60)) {
            $data['is_change_name'] = 1;
        }
        $data['img'] = db('user_img')->where('uid', '=', $uid)->select(); //获取轮播图
        $result['data'] = $data;
        return_json_encode($result);
    }


    //判断是否认证
    public function is_auth()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));

        $user_info = check_login_token($uid, $token);
        $result['is_auth'] = $user_info['is_auth'];
        return_json_encode($result);
    }

    //判断是否是VIP
    public function request_is_vip()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));

        $user_info = get_user_base_info($uid, ['vip_end_time'], 1);

        $result['is_vip'] = 0;
        if ($user_info['vip_end_time'] > NOW_TIME) {
            $result['is_vip'] = 1;
        }

        return_json_encode($result);
    }

    //修改签名
    public function request_do_edit_sign()
    {
        $result = array('code' => 1, 'msg' => '');

        $sign = trim(input('param.sign'));
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));

        $user_info = check_login_token($uid, $token);
        $config = load_cache('config');
        $dirty_word = explode(",", $config['dirty_word']);    //藏字库

        if (empty($sign)) {
            $result['code'] = 0;
            $result['msg'] = '签名不能为空！';
            return_json_encode($result);
        }
        //藏字库判断
        foreach ($dirty_word as $v) {
            //签名
            if (stristr($sign, $v)) {
                $result['code'] = 0;
                $result['msg'] = "个性签名中不能带有" . $v . "的字";
                return_json_encode($result);
            }
        }
        db('auth_form_record')->where('user_id', '=', $uid)->setField('sign', $sign);

        return_json_encode($result);
    }


    //获取认证信息和评价信息
    public function get_user_page_user_info()
    {
        $result = array('code' => 1, 'msg' => '');

        $to_user_id = intval(input('param.to_user_id'));
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $page = intval(input(('param.page')));

        $user_info = check_login_token($uid, $token);
        $to_user_info = get_user_base_info($to_user_id, ['height', 'weight', 'constellation', 'city', 'image_label', 'introduce', 'sex']);

        $result['height'] = $to_user_info['height'];
        $result['weight'] = $to_user_info['weight'];
        $result['constellation'] = $to_user_info['constellation'];
        $result['city'] = $to_user_info['city'];
        $result['introduce'] = $to_user_info['introduce'];
        $result['signature'] = $to_user_info['signature'];
        $result['sex'] = $to_user_info['sex'];

        $result['image_label'] = [];

        if (!empty($to_user_info['image_label'])) {
            $self_label_array = explode('-', $to_user_info['image_label']);
            foreach ($self_label_array as $k => $v2) {
                if (empty($v2)) {
                    unset($self_label_array[$k]);
                }
            }
            $result['image_label'] = $self_label_array;
        }

        //获取评价列表
        $result['evaluate_list'] = db('user_evaluate_record')->alias('e')
            ->join('user u', 'e.user_id=u.id')
            ->field('u.user_nickname,u.avatar,e.label_name,u.sex')
            ->where('e.to_user_id', '=', $to_user_id)
            ->order('e.create_time desc')
            ->page($page)
            ->select();

        foreach ($result['evaluate_list'] as &$v) {
            $v['label_list'] = [];
            if (!empty($v['label_name'])) {
                $label_array = explode('-', $v['label_name']);
                foreach ($label_array as $k => $v2) {
                    if (empty($v2)) {
                        unset($label_array[$k]);
                    }
                }
                $v['label_list'] = $label_array;
            }
        }

        return_json_encode($result);
    }

    //获取新用户主页信息
    public function get_user_page_info()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $to_user_id = intval(input('param.to_user_id'));
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));

        $user_info = check_login_token($uid, $token);

        $to_user_filed = ['address', 'custom_video_charging_coin'];

        if (defined('OPEN_BUY_CONTACT_PLUGS') && OPEN_BUY_CONTACT_PLUGS == 1) {
            $to_user_filed[] = 'wx_number';
            $to_user_filed[] = 'qq_number';
            $to_user_filed[] = 'phone_number';
        }

        $user = get_user_base_info($to_user_id, $to_user_filed);
        $level = get_level($to_user_id);

        $data = array(
            'id' => $to_user_id,
            'sex' => $user['sex'],
            'user_nickname' => $user['user_nickname'],
            'avatar' => $user['avatar'],
            'address' => $user['address'],
            'user_status' => $user['is_auth'],
            'level' => $level,
            'signature' => $user['signature'],
        );

        $data['attention'] = 1;
        if ($to_user_id != $uid) {
            $data['attention'] = get_attention($uid, $to_user_id); //获取是否关注
        }

        //是否拉黑
        $data['is_black'] = get_is_black($uid, $to_user_id);

        $config = load_cache('config');

        //获取主播私照
        $private_photo_list = db('user_pictures')->where("uid=$to_user_id and status=1")->field("img,id")->limit(0, 15)->select();

        //处理图片模糊状态
        foreach ($private_photo_list as &$v) {
            //获取查询私照是否支付观看过
            $buy_record = db("user_photo_buy")->where("p_id=" . $v['id'] . " and user_id=$uid")->find();
            if (!$buy_record) {
                $v['img'] = $v['img'] . "?imageMogr2/auto-orient/blur/40x50"; //私照加密
                $v['watch'] = 1;
            } else {
                $v['watch'] = 0;
            }
        }

//        if ($to_user_id == $uid) {
//            $gift_count = db('user_gift_log')->where('user_id', '=', $to_user_id)->sum('gift_count');
//            //统计收到的礼物
//            $data['gift_count'] = $gift_count;
//
//            $gift_list = db('user_gift_log')
//                ->alias('l')
//                ->join('gift g', 'l.gift_id=g.id')
//                ->field('g.*')
//                ->where('l.user_id', '=', $to_user_id)
//                ->group('gift_id')
//                ->select();
//        } else {
//            $gift_count = db('user_gift_log')->where('to_user_id', '=', $to_user_id)->sum('gift_count');
//            //统计收到的礼物
//            $data['gift_count'] = $gift_count;
//
//            $gift_list = db('user_gift_log')
//                ->alias('l')
//                ->join('gift g', 'l.gift_id=g.id')
//                ->field('g.*')
//                ->where('l.to_user_id', '=', $to_user_id)
//                ->group('gift_id')
//                ->select();
//        }


        $gift_count = db('user_gift_log')->where('to_user_id', '=', $to_user_id)->sum('gift_count');
        //统计收到的礼物
        $data['gift_count'] = $gift_count;

        $gift_list = db('user_gift_log')
            ->alias('l')
            ->join('gift g', 'l.gift_id=g.id')
            ->field('g.*')
            ->where('l.to_user_id', '=', $to_user_id)
            ->group('gift_id')
            ->select();

        //统计收到的礼物
        $data['gift'] = $gift_list;
        //统计主播私照
        $data['pictures_count'] = count($private_photo_list);
        //统计主播私照
        $data['pictures'] = $private_photo_list;
        //是否在线0不在1在
        //$data['online'] = is_online($to_user_id, $config['heartbeat_interval']);
        $data['is_online'] = $user['is_online'];

        $attention_fans_count = db('user_attention')->where("attention_uid=$to_user_id")->count();
        $attention_count = db('user_attention')->where("uid=$to_user_id")->count();
        //通话时长
        $call_time = db('video_call_record_log')
            ->where('user_id', '=', $to_user_id)
            ->whereOr('call_be_user_id', '=', $to_user_id)
            ->sum('call_time');

        if ($call_time) {
            $call_time = secs_to_str(abs($call_time));
        } else {
            $call_time = '0';
        }

        //好评比
        $evaluation = db('video_call_record_log')->where('is_fabulous', '=', 1)->where('anchor_id', '=', $to_user_id)->count(); //获取评价总数

        //主页轮播图
        $user_image = db('user_img')->where("uid=$to_user_id")->where("status=1")->field("id,img")->order("addtime desc")->limit(6)->select();

        //点赞总数
        $fabulous_count = db('user_fabulous_record')->where('to_user_id', '=', $to_user_id)->count();

        //视频通话价格
        $data['video_deduction'] = $config['video_deduction'];
        if (defined('OPEN_CUSTOM_VIDEO_CHARGE_COIN') && OPEN_CUSTOM_VIDEO_CHARGE_COIN == 1) {
            //判断用户等级是否符合规定
            if ($level >= $config['custom_video_money_level'] && $user['custom_video_charging_coin'] != 0) {
                $data['video_deduction'] = $user['custom_video_charging_coin'];
            }
        }

        //音频通话价格
        $data['voice_deduction'] = 0;
        if (defined('OPEN_VOICE_CALL') && OPEN_VOICE_CALL == 1 && isset($config['voice_deduction'])) {
            $data['voice_deduction'] = $config['voice_deduction'];
        }

        //获取守护主播的列表
        $guardian_user_list = db('guardian_user')->alias('i')
            ->join('user u', 'u.id = i.uid')
            ->join('user_gift_log a', "i.uid = a.user_id and i.hostid=a.to_user_id and a.create_time >= i.starttime and a.create_time <= i.endtime", 'LEFT')
            ->field('i.*,u.user_nickname,u.avatar,sum(a.gift_coin * a.gift_count) as gift_count')
            ->where("i.endtime >=" . NOW_TIME . " and i.hostid=" . $to_user_id)
            ->group("i.uid")
            ->order('gift_count desc')
            ->limit(3)
            ->select();

        $data['guardian_user_list'] = $guardian_user_list; //获取守护主播的列表
        $data['attention_fans'] = $attention_fans_count; //获取关注人数
        $data['attention_all'] = $attention_count; //获取粉丝人数
        $data['call'] = $call_time; //通话总时长
        $data['evaluation'] = $evaluation; //好评百分比
        $data['img'] = $user_image; //主播轮播图
        $data['give_like'] = $fabulous_count; //获取点赞数

        //是否显示底部按钮
        $data['is_visible_bottom_btn'] = 1;
        if ($user_info['sex'] == $user['sex'] || $user_info['id'] == $user['id']) {
            $data['is_visible_bottom_btn'] = 0;
        }

        if (defined('OPEN_BUY_CONTACT_PLUGS') && OPEN_BUY_CONTACT_PLUGS == 1) {
            $data['wx_number'] = $user['wx_number'];
            $data['qq_number'] = $user['qq_number'];
            $data['phone_number'] = $user['phone_number'];
        }

        $result['data'] = $data;
        return_json_encode($result);
    }

    //提交认证信息
    public function request_submit_auth_info()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        if ($user_info['sex'] == 1) {
            $result['code'] = 0;
            $result['msg'] = '男性不能认证！';
            return_json_encode($result);
        }

        $is_auth = db('auth_form_record')->where('user_id', '=', $uid)->find();
        if ($is_auth && $is_auth['status'] != 2) {
            $result['code'] = 0;
            $result['msg'] = '已经提交过认证信息，请勿重复提交!';
            return_json_encode($result);
        }

        if ($is_auth['status'] == 2) {
            db('auth_form_record')->where('user_id', '=', $uid)->delete();
        }

        $nickname = trim(input('param.nickname'));
        $phone = trim((input(('param.phone'))));
        $height = intval((input(('param.height'))));
        $weight = trim((input(('param.weight'))));
        $constellation = trim((input(('param.constellation'))));
        $city = trim((input(('param.city'))));
        $introduce = trim((input(('param.introduce'))));

        $sign = trim((input(('param.self_label'))));
        $auth_id_card_img_url1 = trim((input(('param.auth_id_card_img_url1'))));
        $auth_id_card_img_url2 = trim((input(('param.auth_id_card_img_url2'))));

        if (empty($nickname)) {
            $result['code'] = 0;
            $result['msg'] = '请输入昵称';
            return_json_encode($result);
        }

        if (strlen($phone) != 11) {
            $result['code'] = 0;
            $result['msg'] = '请输入手机号码';
            return_json_encode($result);
        }

        if ($height == 0) {
            $result['code'] = 0;
            $result['msg'] = '请选择身高';
            return_json_encode($result);
        }

        if ($weight == 0) {
            $result['code'] = 0;
            $result['msg'] = '请选择体重';
            return_json_encode($result);
        }

        if (empty($constellation)) {
            $result['code'] = 0;
            $result['msg'] = '请选择星座';
            return_json_encode($result);
        }

        if (empty($city)) {
            $result['code'] = 0;
            $result['msg'] = '请选择所在城市';
            return_json_encode($result);
        }


        if (empty($introduce)) {
            $result['code'] = 0;
            $result['msg'] = '请填写自我介绍';
            return_json_encode($result);
        }

        if (empty($sign)) {
            $result['code'] = 0;
            $result['msg'] = '请输入个性签名';
            return_json_encode($result);
        }

        if (empty($auth_id_card_img_url1)) {
            $result['code'] = 0;
            $result['msg'] = '请上传身份证正面';
            return_json_encode($result);
        }

        if (empty($auth_id_card_img_url2)) {
            $result['code'] = 0;
            $result['msg'] = '请上传身份证反面';
            return_json_encode($result);
        }

        $insert_data = [
            'user_nickname' => $nickname,
            'phone' => $phone,
            'height' => $height,
            'weight' => $weight,
            'constellation' => $constellation,
            'city' => $city,
            'introduce' => $introduce,
            'sign' => $sign,
            'user_id' => $uid,
            'status' => 0,
            'auth_id_card_img_url1' => $auth_id_card_img_url1,
            'auth_id_card_img_url2' => $auth_id_card_img_url2,
            'create_time' => NOW_TIME,
        ];

        $res = db('auth_form_record')->insert($insert_data);
        if (!$res) {
            $result['code'] = 0;
            $result['msg'] = '提交失败，请稍后重试！';
        }

        return_json_encode($result);
    }

    //评价
    public function request_submit_evaluate()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());

        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));
        $label_str = trim(input('param.label_str'));
        $channel_id = trim(input('param.channel_id'));

        $user_info = check_login_token($uid, $token);

        $data = ['user_id' => $uid, 'to_user_id' => $to_user_id, 'label_name' => $label_str, 'create_time' => NOW_TIME, 'channel_id' => $channel_id];

        db('user_evaluate_record')->insert($data);

        return_json_encode($result);
    }

    /*
    * 更新用户城市
    */
    public function refresh_city()
    {
        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $province = trim(input('param.province'));
        $city = trim(input('param.city'));
        $lat = get_input_param_str('lat');
        $lng = get_input_param_str('lng');
        $city_name=isset($province) ? $province.$city : $city;
        $address['province'] = $province;
        $address['city'] = $city;
        $address['lat'] = $lat;
        $address['lng'] = $lng;
        $address['address'] = $city_name;

        db('user')->where('id', '=', $uid)->update($address);
        return_json_encode($result);
    }


    /**
     * 会话列表根据id返回用户信息
     * */
    public function get_conversation_user_info()
    {
        $result = array('code' => 1, 'msg' => '');

        $ids = input("param.ids");

        $id_array = explode(',', $ids);
        if (count($id_array) > 0) {
            $list = db('user')
                ->whereIn('id', $id_array)
                ->field('id,avatar,user_nickname')
                ->select();

            $result['list'] = $list;
        }

        return_json_encode($result);
    }


    //对用户点赞
    public function fabulous()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));

        $user_info = check_login_token($uid, $token);

        $record = db('user_fabulous_record')
            ->where('user_id', '=', $uid)
            ->where('to_user_id', '=', $to_user_id)
            ->find();

        if (!$record) {
            $data = [
                'user_id' => $uid,
                'to_user_id' => $to_user_id,
                'create_time' => time(),
            ];
            db('user_fabulous_record')->insert($data);
        }

        return_json_encode($result);
    }

    //获取用户中心数据
    public function get_user_center_info()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token, ['is_open_do_not_disturb', 'vip_end_time', 'is_open_auto_see_hi']);

        //等级
        $level = get_level($user_info['id']);

        $data = array(
            'sex' => $user_info['sex'],
            'user_nickname' => $user_info['user_nickname'],
            'avatar' => $user_info['avatar'],
            'coin' => $user_info['coin'],
            'user_status' => $user_info['user_status'], //2未验证1验证
            'level' => $level,
            'is_open_do_not_disturb' => $user_info['is_open_do_not_disturb'],
            'is_open_auto_see_hi' => $user_info['is_open_auto_see_hi'],
            'is_vip' => 0,
            'signature' => $user_info['signature'],
            'is_auth' => $user_info['is_auth'],
        );

        //vip
        if ($user_info['vip_end_time'] > NOW_TIME) {
            $data['is_vip'] = 1;
        }

        $data['user_auth_status'] = get_user_auth_status($uid);

        //$data['split'] = ($config['invite_income_ratio'] * 100) . '%'; //获取分成比例
        $data['split'] = 0;

        $fans_count = db('user_attention')->where("attention_uid=$uid")->count();
        $follow_count = db('user_attention')->where("uid=$uid")->count();
        $data['attention_fans'] = $fans_count; //获取关注人数
        $data['attention_all'] = $follow_count; //获取粉丝人数

        //充值规则
        //$charging_rule = Db::name("user_charge_rule")->field("id,money,coin")->order("orderno asc")->limit(3)->select();

        $data['pay_coin'] = []; //获取购买聊币类型
        //  var_dump($data['coin']);exit;

        //是否有公会
        $data['is_president'] = 0;
        if (db('guild')->where('user_id=' . $uid . ' and status=1')->find()) {
            $data['is_president'] = 1;
        }
        $result['data'] = $data;
        return_json_encode($result);
    }

    //获取粉丝列表
    public function get_fans_list()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $page = intval(input('param.page'));

        //统计用户粉丝总数
        $result['count'] = db('user_attention')->where("attention_uid=$uid")->count();
        $attention = db('user_attention')->alias("a")
            ->join("user u", "a.uid=u.id")
            ->where("a.attention_uid=$uid")
            ->field("u.id,u.avatar,u.user_nickname,u.sex,u.level")
            ->order("addtime desc")
            ->page($page)
            ->select();

        foreach ($attention as &$v) {
            $v['focus'] = 0;
            $focus = db('user_attention')->where("uid=$uid and attention_uid=" . $v['id'])->find();
            if ($focus) {
                $v['focus'] = 1;
            }
        }
        $result['data'] = $attention;
        return_json_encode($result);
    }

    //获取关注用户列表
    public function get_follow_list()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $page = intval(input('param.page'));

        //统计用户关注
        $result['count'] = db('user_attention')->where("uid=$uid")->count();
        $attention = db('user_attention')->alias("a")
            ->join("user u", "a.attention_uid=u.id")
            ->where("a.uid=$uid")
            ->field("u.id,u.avatar,u.user_nickname,u.sex,u.level")
            ->order("addtime desc")
            ->page($page)
            ->select();

        foreach ($attention as &$v) {
            $v['focus'] = 0;
            $focus = db('user_attention')->where("uid=$uid and attention_uid=" . $v['id'])->find();
            if ($focus) {
                $v['focus'] = 1;
            }
        }

        $result['data'] = $attention;
        return_json_encode($result);
    }

    //显示修改的用户信息
    public function edit_user_info()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = input('param.uid');
        $token = input('param.token');

        $user_info = check_login_token($uid, $token, ['last_change_name_time', 'height', 'weight', 'constellation', 'introduce', 'image_label']);

        //获取认证信息
        $user_auth_info = db('auth_form_record')->where('user_id', '=', $uid)->find();

        $data = array(
            'sex' => $user_info['sex'],
            'user_nickname' => $user_info['user_nickname'],
            'avatar' => $user_info['avatar'],
            'is_change_name' => 0,
            'height' => $user_info['height'],
            'weight' => $user_info['weight'],
            'constellation' => $user_info['constellation'],
            'introduce' => $user_info['introduce'],
            'image_label' => $user_info['image_label'],
        );

        $data['sign'] = $user_info['signature'];

        //是否可以修改昵称
        if ((NOW_TIME - $user_info['last_change_name_time']) > (30 * 24 * 60 * 60)) {
            $data['is_change_name'] = 1;
        }
        $data['img'] = db('user_img')->where('uid', '=', $uid)->select(); //获取轮播图
        $result['data'] = $data;
        return_json_encode($result);
    }

    //修改用户信息
    public function update_user_info()
    {

        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $id = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $del = input("param.del");
        $sex = intval(input("param.sex"));
        $user_nickname = trim(input('param.user_nickname'));
        $sign = trim(input('param.sign'));

        $user_info = check_login_token($id, $token);
        $config = load_cache('config');
        $dirty_word = explode(",", $config['dirty_word']);    //藏字库

        $delete_ids = explode('|', $del);
        if (count($delete_ids) > 0) {

            $delete_img_list = Db::name('user_img')->where('uid', '=', $id)->where('id', 'in', $delete_ids)->find();

            if ($delete_img_list) {
                $delete_img_path_list = [];
                foreach ($delete_img_list as $k => $v) {
                    $file_name = parse_url($v['img'])['path'];
                    $delete_img_path_list[] = substr($file_name, 1, strlen($file_name));

                }
                $oss_delete_res = oss_del_list($delete_img_path_list);
                if ($oss_delete_res) {
                    //删除轮播图
                    Db::name('user_img')->where('uid', '=', $id)->where('id', 'in', $delete_ids)->delete();
                }
            }
        }

        //修改昵称
        if (!empty($user_nickname)) {
            $all_name = Db::name('user')->where("user_nickname='$user_nickname' and id!=$id")->find();
            if ($all_name) {
                $result['code'] = 0;
                $result['msg'] = "用户名重复，请重新输入用户名";
                return_json_encode($result);
            }
            $data['user_nickname'] = $user_nickname;
        }

        $data['sex'] = $sex;

        //修改签名
        if (!empty($sign)) {
            $data['signature'] = $sign;
        }
        //藏字库判断
        foreach ($dirty_word as $v) {
            //昵称
            if (stristr($user_nickname, $v)) {
                $result['code'] = 0;
                $result['msg'] = "昵称中不能带有" . $v . "的字";
                return_json_encode($result);
            }
            //签名
            if (stristr($sign, $v)) {
                $result['code'] = 0;
                $result['msg'] = "个性签名中不能带有" . $v . "的字";
                return_json_encode($result);
            }
        }
        //上传头像
        $avatar = request()->file('avatar'); //获取头像
        if ($avatar) {
            $upload_one = oss_upload($avatar); //单图片上传
            $data['avatar'] = $upload_one;
        }

        $new_image = array();
        for ($i = 0; $i < 6; $i++) {
            $img = request()->file('img' . $i);
            if ($img) {
                $new_image[$i] = $img;
            }
        }

        if ($new_image) {
            $all_img = Db::name('user_img')->where("uid=$id")->count();
            if ((count($new_image) + $all_img) > 6) {
                $result['code'] = 0;
                $result['msg'] = "多图片添加失败,请删除后在添加";
                return_json_encode($result);
            }

            foreach ($new_image as $v) {
                $uploads = oss_upload($v); //单图片上传
                $upload_all['img'][]['img'] = $uploads;
            }
        }

        //更新修改信息
        db('user')->where("id=$id and token='$token'")->update($data);
        //echo db('user') -> getLastSql();exit;

        if ($new_image && isset($upload_all)) {

            foreach ($upload_all['img'] as &$v) {
                $v['uid'] = $id;
                $v['addtime'] = NOW_TIME;
                $data['img'][]['img'] = $v['img'];
            }
            $all_img = Db::name('user_img')->insertAll($upload_all['img']); //添加轮播图
            if (!$all_img) {
                $result['code'] = 0;
                $result['msg'] = "用户信息保存失败";
                return_json_encode($result);
            }
        }

        require_once DOCUMENT_ROOT . '/system/im_common.php';
        update_im_user_info($id);

        $result['code'] = 1;
        $result['msg'] = "修改成功";
        $result['data'] = $data;
        return_json_encode($result);

    }


    //修改用户信息
    public function update_user_info_190708()
    {

        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $id = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $del = input("param.del");
        $sex = intval(input("param.sex"));
        $user_nickname = trim(input('param.user_nickname'));
        $sign = trim(input('param.sign'));
        //上传头像
        $avatar = trim(input('param.avatar'));

        $user_info = check_login_token($id, $token);

        $config = load_cache('config');
        $dirty_word = explode(",", $config['dirty_word']);    //藏字库

        $delete_img_list = Db::name('user_img')->where('uid', '=', $id)->select();

        if ($delete_img_list) {
            $delete_img_path_list = [];
            foreach ($delete_img_list as $k => $v) {
                $file_name = parse_url($v['img'])['path'];
                $delete_img_path_list[] = substr($file_name, 1, strlen($file_name));

            }
            $oss_delete_res = oss_del_list($delete_img_path_list);
            if ($oss_delete_res) {
                //删除轮播图
                Db::name('user_img')->where('uid', '=', $id)->delete();
            }
        }

        //修改昵称
        if (!empty($user_nickname)) {

            $all_name = Db::name('user')->where("user_nickname='$user_nickname' and id!=$id")->find();

            if ($all_name) {
                $result['code'] = 0;
                $result['msg'] = "用户名重复，请重新输入用户名";
                return_json_encode($result);
            }

            $data['user_nickname'] = $user_nickname;
        }

        $data['sex'] = $sex;

        //修改签名
        if (!empty($sign)) {
            $data['signature'] = $sign;
        }

        if (!empty($avatar)) {
            $data['avatar'] = $avatar;
        }
        //藏字库判断

        foreach ($dirty_word as $v) {
            //昵称 
            if (stristr($user_nickname, $v)) {
                $result['code'] = 0;
                $result['msg'] = "昵称中不能带有" . $v . "的字";
                return_json_encode($result);
            }
            //签名
            if (stristr($sign, $v)) {
                $result['code'] = 0;
                $result['msg'] = "个性签名中不能带有" . $v . "的字";
                return_json_encode($result);
            }
        }
        $new_image = array();
        for ($i = 0; $i < 6; $i++) {
            $img = trim(input('param.img' . $i));
            if ($img) {
                $new_image[$i] = $img;
            }
        }

        if ($new_image) {
            $all_img = Db::name('user_img')->where("uid=$id")->count();
            if ((count($new_image) + $all_img) > 6) {
                $result['code'] = 0;
                $result['msg'] = "多图片添加失败,请删除后在添加";
                return_json_encode($result);
            }

            foreach ($new_image as $v) {
                $upload_all['img'][]['img'] = $v;
            }
        }

        $height = intval((input(('param.height'))));
        $weight = trim((input(('param.weight'))));
        $constellation = trim((input(('param.constellation'))));
        $introduce = trim((input(('param.introduce'))));


        $data['height'] = $height;
        $data['weight'] = $weight;
        $data['constellation'] = $constellation;

        $data['introduce'] = $introduce;

        //更新修改信息
        db('user')->where("id=$id and token='$token'")->update($data);
        //echo db('user') -> getLastSql();exit;

        if ($new_image && isset($upload_all)) {

            foreach ($upload_all['img'] as &$v) {
                $v['uid'] = $id;
                $v['addtime'] = NOW_TIME;
                $data['img'][]['img'] = $v['img'];
            }
            $all_img = Db::name('user_img')->insertAll($upload_all['img']); //添加轮播图
            if (!$all_img) {
                $result['code'] = 0;
                $result['msg'] = "用户信息保存失败";
                return_json_encode($result);
            }
        }

        require_once DOCUMENT_ROOT . '/system/im_common.php';
        update_im_user_info($id);

        $result['code'] = 1;
        $result['msg'] = "修改成功";
        $result['data'] = $data;
        return_json_encode($result);

    }


    //删除形象图片
    public function del_image()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $img_id = intval(input('param.id'));
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $ser = db('user_img')->where("uid=$uid and id=$img_id")->find();
        $file_name = parse_url($ser['img'])['path'];
        $file_name = substr($file_name, 1, strlen($file_name));
        //var_dump($file_name);exit;
        $set = oss_del_file($file_name);

        if ($set) {
            $ser = Db::name('user_img')->where("uid=$uid and id=$img_id")->delete(); //删除轮播图
            if ($ser) {
                $result['code'] = 1;
            }
        }

        return_json_encode($result);
    }

    //视频认证
    public function video_auth()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $video_id = intval(input('param.video_id'));
        $video_url = trim(input('param.video_url'));
        $cover_url = trim(input('param.cover_url'));

        $user_info = check_login_token($uid, $token);

        if ($user_info['sex'] == 1) {
            $result['code'] = 0;
            $result['msg'] = '男性不能认证！';
            return_json_encode($result);
        }

        $auth_status = get_user_auth_status($uid);
        if ($auth_status != 2) {
            $result['code'] = 0;
            $result['msg'] = '已经提交认证,请勿重复提交';
            return_json_encode($result);

        } else if ($auth_status == 2) {
            //添加视频认证记录
            $auth_video_data = ['status' => 0, 'video_id' => $video_id, 'video_url' => $video_url, 'cover_url' => $cover_url, 'create_time' => time()];
            db('user_auth_video')->where(['user_id' => $uid])->update($auth_video_data);
        } else {
            //添加视频认证记录
            $auth_video_data = ['user_id' => $uid, 'video_id' => $video_id, 'video_url' => $video_url, 'cover_url' => $cover_url, 'create_time' => time()];
            db('user_auth_video')->insert($auth_video_data);
        }

        return_json_encode($result);
    }

    //申请提现
    public function cash_income()
    {

        $result = array('code' => 1, 'msg' => '', 'data' => array());
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $name = trim(input('param.name'));
        $number = trim(input('param.number'));

        $user_info = check_login_token($uid, $token, ['income']);

        //检查是否认证
        $auth_status = get_user_auth_status($uid);
        if ($auth_status != 1) {
            $result['code'] = 0;
            $result['msg'] = '认证并审核通过才可以提现！';
            return_json_encode($result);
        }

        $config = load_cache('config');
        if ($user_info['income'] < $config['min_cash_income']) {
            $result['code'] = 0;
            $result['msg'] = '最低提现额度为' . $config['min_cash_income'] . '积分';
            return_json_encode($result);
        }

        //查询是否超过当日最大提现次数
        $day_time = Time::today();
        $day_cash_num = db('user_cash_record')->where('user_id', '=', $uid)->where('create_time>' . $day_time[0])->count();
        if ($day_cash_num == $config['cash_day_limit']) {
            $result['code'] = 0;
            $result['msg'] = '每日最大提现次数为' . $day_cash_num . '！';
            return_json_encode($result);
        }
        $pays = db('user_cash_account')->where("uid=" . $uid . " and name='" . $name . "'")->find();
        if (!$pays) {
            $result['code'] = 0;
            $result['msg'] = '请绑定账号';
            return_json_encode($result);
        }
        //扣除剩余提现额度
        $inc_income = db('user')->where('id', '=', $uid)->setField('income', 0);
        if ($inc_income) {

            //添加提现记录
            $record = ['gathering_name' => $name, 'gathering_number' => $number, 'user_id' => $uid, 'income' => $user_info['income'], 'paysid' => $pays['id'], 'create_time' => NOW_TIME];
            db('user_cash_record')->insert($record);
        } //$record = ['gathering_name' => $pays['name'], 'gathering_number' => $pays['pay'] ? $pays['pay'] : $pays['wx'], 'user_id' => $uid,'paysid'=>$pays['id'], 'income' => $list['coin'], 'money' => $money, 'create_time' => NOW_TIME];


        return_json_encode($result);
    }

    //举报用户
    public function do_report_user()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = intval(input('param.to_user_id'));
        $type = trim(input('param.type'));
        $content = trim(input('param.content'));

        $user_info = check_login_token($uid, $token);

        //添加记录
        $report_record = [
            'uid' => $uid,
            'reportid' => $to_user_id,
            'reporttype' => $type,
            'content' => $content,
            'addtime' => NOW_TIME,
        ];

        $log_id = db('user_report')->insertGetId($report_record);

        $img = request()->file(); //获取举报图
        if (count($img) > 3) {
            $result['code'] = 0;
            $result['msg'] = '图片数量最多3张';
            return_json_encode($result);
        }

        $data = [];
        foreach ($img as $k => $v) {
            $uploads = oss_upload($v); //单图片上传
            if ($uploads) {
                $data[$k]['report'] = $log_id;
                $data[$k]['addtime'] = NOW_TIME;
                $data[$k]['img'] = $uploads;
            }
        }
        //举报截图
        db('user_report_img')->insertAll($data);

        return_json_encode($result);

    }

    //修改按时付费价格
    public function change_video_line_money()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $coin = intval(input('param.coin'));

        $user_info = check_login_token($uid, $token);

        if ($user_info['is_auth'] != 1) {
            $result['code'] = 0;
            $result['msg'] = '认证后可以自定义金额!';
            return_json_encode($result);
        }

        if ($coin <= 0) {
            $result['code'] = 0;
            $result['msg'] = '数量必须大于0!';
            return_json_encode($result);
        }

        $user_level = get_level($uid);

        $config = load_cache('config');
        //判断用户等级是否符合规定
        if ($user_level < $config['custom_video_money_level']) {
            $result['code'] = 0;
            $result['msg'] = '等级不符合要求，最低为:' . $config['custom_video_money_level'];
            return_json_encode($result);
        }

        //是否在合理范围内
        $range = explode('-', $config['video_call_coin_range']);
        if (count($range) == 2) {

            if ($coin < $range[0] || $coin > $range[1]) {
                $result['code'] = 0;
                $result['msg'] = '视频聊收费范围为' . $config['video_call_coin_range'] . '，请重新设置';
                return_json_encode($result);
            }
        }

        //修改自定义扣费金额
        db('user')->where('id', '=', $uid)->setField('custom_video_charging_coin', $coin);

        return_json_encode($result);
    }

    /*
    * 免打扰设置
    */
    public function request_set_do_not_disturb()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $type = intval(input('param.type'));

        $user_info = check_login_token($uid, $token);

        if ($type == 1) {
            db('user')->where('id', '=', $uid)->setField('is_open_do_not_disturb', 1);
        } else {
            db('user')->where('id', '=', $uid)->setField('is_open_do_not_disturb', 0);
        }
        return_json_encode($result);

    }


    //返回该用户的礼物柜
    public function request_get_gift_cabinet()
    {
        $result = array('code' => 1, 'msg' => '');
        $to_user_id = intval(input('param.to_user_id'));

        $gift = db('gift')->select();
        foreach ($gift as &$g) {
            $g['gift_count'] = db('user_gift_log')
                ->where('to_user_id', '=', $to_user_id)
                ->where('gift_id', '=', $g['id'])
                ->sum('gift_count');
        }
        $result['gift_list'] = $gift;

        return_json_encode($result);
    }

    /*
    * 自动打招呼设置
    */
    public function request_set_auto_see_hi()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $type = intval(input('param.type'));

        $user_info = check_login_token($uid, $token);

        if ($type == 1) {
            db('user')->where('id', '=', $uid)->setField('is_open_auto_see_hi', 1);
        } else {
            db('user')->where('id', '=', $uid)->setField('is_open_auto_see_hi', 0);
        }
        return_json_encode($result);

    }


    //获取自动打招呼模板消息
    public function request_get_auto_smg_tpl_info()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token);

        $info = db('custom_auto_msg')->where('user_id', '=', $uid)->find();
        //->file('auto_msg1,auto_msg2,auto_msg3,auto_msg4,auto_msg5,reply_msg1,reply_msg2,reply_msg3,reply_msg4,reply_msg5,province,city')

        if (!$info) {
            $tpl_info = [
                'auto_msg1' => '',
                'auto_msg2' => '',
                'auto_msg3' => '',
                'auto_msg4' => '',
                'auto_msg5' => '',
                'reply_msg1' => '',
                'reply_msg2' => '',
                'reply_msg3' => '',
                'reply_msg4' => '',
                'reply_msg5' => '',
                'province' => '',
                'city' => '',
            ];
        } else {
            $tpl_info = $info;
        }

        $result['data'] = $tpl_info;
        return_json_encode($result);
    }

    //保存自动打招呼模板
    public function request_save_auto_smg_tpl()
    {

        $result = array('code' => 1, 'msg' => '');

        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $user_info = check_login_token($uid, $token);

        $auto_msg1 = trim(input(('param.auto_msg1')));
        $auto_msg2 = trim(input(('param.auto_msg2')));
        $auto_msg3 = trim(input(('param.auto_msg3')));
        $auto_msg4 = trim(input(('param.auto_msg4')));
        $auto_msg5 = trim(input(('param.auto_msg5')));


        $auto_reply_msg1 = trim(input(('param.reply_msg1')));
        $auto_reply_msg2 = trim(input(('param.reply_msg2')));
        $auto_reply_msg3 = trim(input(('param.reply_msg3')));
        $auto_reply_msg4 = trim(input(('param.reply_msg4')));
        $auto_reply_msg5 = trim(input(('param.reply_msg5')));

        $province = trim(input(('param.province')));
        $city = trim(input(('param.city')));


        $update_data = [
            'auto_msg1' => $auto_msg1,
            'auto_msg2' => $auto_msg2,
            'auto_msg3' => $auto_msg3,
            'auto_msg4' => $auto_msg4,
            'auto_msg5' => $auto_msg5,
            'reply_msg1' => $auto_reply_msg1,
            'reply_msg2' => $auto_reply_msg2,
            'reply_msg3' => $auto_reply_msg3,
            'reply_msg4' => $auto_reply_msg4,
            'reply_msg5' => $auto_reply_msg5,
            'province' => $province,
            'city' => $city,
        ];

        $exits = db('custom_auto_msg')->where('user_id', '=', $uid)->find();
        if (!$exits) {
            $update_data['user_id'] = $uid;
            $update_data['create_time'] = NOW_TIME;
            db('custom_auto_msg')->insert($update_data);
        } else {
            db('custom_auto_msg')->where('user_id=' . $uid)->update($update_data);
        }

        return_json_encode($result);
    }


    //取消预约状态(超时的 24小时)
    public function cancel_video_call()
    {
        $result = array('code' => 0, 'msg' => '取消预约失败,请联系客服');
        $uid = intval(input('param.uid'));
        $token = trim(input(('param.token')));
        $id = intval(input('param.id'));                 //预约表id
        $user_info = check_login_token($uid, $token);

        $video_call = db('video_call_subscribe')->where('id=' . $id . " and user_id=" . $uid)->find();
        if (!$video_call || $video_call['status'] != 0) {
            $result['msg'] = '用户暂无预约';
            return_json_encode($result);
        }
        $time = intval($video_call['create_time']) + 24 * 60 * 60;
        if ($time > NOW_TIME) {
            $result['msg'] = '用户预约的时间未到';
            return_json_encode($result);
        }

        $status = db('video_call_subscribe')->where('id=' . $id)->update(['status' => 3]);
        if ($status) {
            db('user')->where('id=' . $uid)->setInc('coin', $video_call['coin']);
            $result['code'] = 1;
            $result['msg'] = '';
        }
        return_json_encode($result);

    }
}