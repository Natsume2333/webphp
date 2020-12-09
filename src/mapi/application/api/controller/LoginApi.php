<?php

namespace app\api\controller;

use AlibabaCloud\Client\Exception\ClientException;
use AlibabaCloud\Client\Exception\ServerException;
use think\Cache;
use think\captcha\Captcha;
use think\Db;
use AlibabaCloud\Client\AlibabaCloud;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ CUCKOO ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
// 登录相关

class LoginApi extends Base
{

    //三方登录
    public function auth_login()
    {

        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $plat_id = trim(input('param.plat_id'));
        $invite_code = trim(input('param.invite_code'));
        $agent_code = trim(input('param.agent'));
        $uuid = trim(input('param.uuid'));
        $login_type = intval(input('param.login_way')) ? intval(input('param.login_way')) :1;   //1手机号 2qq 3微信

        //是否开启设备注册限制
        if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {

            //查询是否该设备已注册
            if (empty($uuid)) {
                $result['code'] = 0;
                $result['msg'] = "缺少设备ID!";
                return_json_encode($result);
            }
        }

        //第三方ID会有为空的情况处理一下
        if (empty($plat_id)) {
            $result['code'] = 0;
            $result['msg'] = "plat_id ID为空!";
            return_json_encode($result);
        }

        $user_info = db('user')->where("plat_id='$plat_id'")->find();

        //登录
        if ($user_info) {
            //生成token
            $token = get_login_token($user_info['id']);
            //更新的信息
            $data = array('token' => $token, 'last_login_time' => NOW_TIME, 'last_login_ip' => request()->ip(0, false),'login_way'=>$login_type);
            $update_res = db('user')->where("id=" . $user_info['id'])->update($data);

            if (!$update_res) {
                $result['code'] = 0;
                $result['msg'] = "登录失败!";
                return_json_encode($result);
            }
             //拉黑时间到取消拉黑状态
            $shielding_time=0;
            if($user_info['user_status'] == 0 && $user_info['shielding_time'] > 0 && $user_info['shielding_time'] < NOW_TIME){
                $data['shielding_time']  =0;
                $data['user_status']  =1;
                db('user')->where('id ='. $user_info['id']." and user_type=2")->update($data);
                $shielding_time=1;
            }

            if ($user_info['user_status'] != 0 || $shielding_time==1) {
                $result['code'] = 1;
                $result['msg'] = "登录成功!";

                $result['data'] = array(
                    'id' => $user_info['id'],
                    'token' => $token,
                    'sex' => $user_info['sex'],
                    'user_nickname' => $user_info['user_nickname'],
                    'avatar' => $user_info['avatar'],
                    'address' => $user_info['address'],
                    'is_reg_perfect' => $user_info['is_reg_perfect'],
                );

                $signature = load_cache('usersign', ['id' => $user_info['id']]);
                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }

                require_once DOCUMENT_ROOT . '/system/im_common.php';
                update_im_user_info($user_info['id']);

                $result['data']['user_sign'] = $signature['usersign'];


            } else {
                 if ($user_info['user_status'] == 0 && $user_info['shielding_time'] < NOW_TIME) {
                    $result['msg'] = "用户禁止登陆!";
                } else {
                    $result['msg'] = "您的账号已拉黑!";
                }
            }

        } else {

            //是否开启设备注册限制
            if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {
                //查询是否该设备已注册
                $is_device_reg = db('user')->where('device_uuid', '=', $uuid)->find();
                if ($is_device_reg) {
                    $result['code'] = 0;
                    $result['msg'] = "该设备达到注册上限!";
                    return_json_encode($result);
                }
            }

            //注册
            //$id = get_max_user_id($plat_id, 'plat_id');
            $token = get_login_token($plat_id);

            $data = array(
                'user_type' => 2,
                'user_nickname' => '新注册用户-' . rand(88888, 99999),
                'create_time' => NOW_TIME,
                'last_login_time' => NOW_TIME,
                'sex' => 0,
                'avatar' => SITE_URL . '/image/headicon.png',
                'token' => $token,
                'address' => "外太空",
                'plat_id' => $plat_id,
                'login_type' => 1,
                'device_uuid' => $uuid,
                'login_way'=>$login_type,
            );

            $reg_result = db('user')->insertGetId($data);
            if ($reg_result) {
                $result['data'] = array(
                    'id' => $reg_result,
                    'token' => $token,
                    'is_reg_perfect' => 0
                );

                $signature = load_cache('usersign', ['id' => $reg_result]);
                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }

                //添加渠道代理
                reg_full_agent_code($reg_result, $agent_code);

                //添加邀请码
                create_invite_code_0910($reg_result);

                //注册邀请奖励业务
                reg_invite_service($reg_result, $invite_code);

                require_once DOCUMENT_ROOT . '/system/im_common.php';
                update_im_user_info($user_info['id']);

                $result['data']['user_sign'] = $signature['usersign'];

                $result['code'] = 1;
                $result['msg'] = "注册成功!";

            } else {
                $result['code'] = 0;
                $result['msg'] = "注册失败，请重新注册！";
            }
        }


        $os = trim(input('param.os'));
        $sdk = trim(input('param.sdk_version'));
        $app = trim(input('param.app_version'));
        $brand = trim(input('param.brand'));
        $model = trim(input('param.model'));

        device_info($os, $sdk, $app, $brand, $model, $user_info['id']);
        return_json_encode($result);
    }


    //登陆注册
    public function do_login()
    {
    	
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = input('param.mobile');
        $code = intval(input('param.code'));
        $address = trim(input('param.address'));
        $invite_code = trim(input('param.invite_code'));
        $agent_code = trim(input('param.agent'));
        $uuid = trim(input('param.uuid'));

        //是否开启设备注册限制
        if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {

            //查询是否该设备已注册
            if (empty($uuid)) {
                $result['code'] = 0;
                $result['msg'] = "缺少设备ID!";
                return_json_encode($result);
            }
        }

        if (!is_numeric($mobile) || strlen($mobile) != 11) {
            $result['code'] = 0;
            $result['msg'] = '手机号码不正确！';
            return_json_encode($result);
        }

        if ($code == 0) {
            $result['code'] = 0;
            $result['msg'] = '验证码错误！';
            return_json_encode($result);
        }

        $config = load_cache('config');

        /*
         * 苹果上架审核
         * */
        if ($mobile == '13246579813' && $code == 111111 && $config['is_grounding'] == 1) {
            $ver = 1;
        } else {
            $ver = db('verification_code')->where("code='$code' and account='$mobile' and expire_time > " . NOW_TIME)->find();
        }
        if (!$ver) {

            $result['code'] = 0;
            $result['msg'] = "验证码错误，请重新获取！";
            return_json_encode($result);

        } else {

            $user_info = db('user')->where("mobile='$mobile'")->find();

            //登录
            if ($user_info) {
                //生成token
                $token = get_login_token($user_info['id']);
                //更新的信息
                $data = array('token' => $token, 'last_login_time' => NOW_TIME, 'last_login_ip' => request()->ip(0, false),);
                $update_res = db('user')->where("id=" . $user_info['id'])->update($data);

                if (!$update_res) {
                    $result['code'] = 0;
                    $result['msg'] = "登录失败!";
                }
               
            //拉黑时间到取消拉黑状态
            $shielding_time=0;
            if($user_info['user_status'] == 0 && $user_info['shielding_time'] > 0 && $user_info['shielding_time'] < NOW_TIME){
                $data['shielding_time']  =0;
                $data['user_status']  =1;
                db('user')->where('id ='. $user_info['id']." and user_type=2")->update($data);
                $shielding_time=1;
            }

            if ($user_info['user_status'] != 0 || $shielding_time==1) {
            	
                    $result['code'] = 1;
                    $result['msg'] = "登录成功!";

                    $result['data'] = array(
                        'id' => $user_info['id'],
                        'token' => $token,
                        'sex' => $user_info['sex'],
                        'user_nickname' => $user_info['user_nickname'],
                        'avatar' => $user_info['avatar'],
                        'address' => $user_info['address'],
                        'is_reg_perfect' => $user_info['is_reg_perfect'],
                    );
				
                    $signature = load_cache('usersign', ['id' => $user_info['id']]);
                    
                    if ($signature['status'] != 1) {
                        $result['code'] = 0;
                        $result['msg'] = $signature['error'];
                        return_json_encode($result);
                    }
						
                    require_once DOCUMENT_ROOT . '/system/im_common.php';
                    update_im_user_info($user_info['id']);
					
                    $result['data']['user_sign'] = $signature['usersign'];
                } else {
                    if ($user_info['user_status'] == 0 && $user_info['shielding_time'] < NOW_TIME) {
                        $result['msg'] = "用户禁止登陆!";
                    } else {
                        $result['msg'] = "您的账号已拉黑!";
                    }
                }

            } else {

                //是否开启设备注册限制
                if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {
                    //查询是否该设备已注册
                    $is_device_reg = db('user')->where('device_uuid', '=', $uuid)->find();
                    if ($is_device_reg) {
                        $result['code'] = 0;
                        $result['msg'] = "该设备达到注册上限!";
                        return_json_encode($result);
                    }
                }

                //检查IP是否超过注册量
                $client_ip = request()->ip();
                $ip_log = db('ip_reg_log')->where(['ip' => $client_ip])->find();
                if ($ip_log && $ip_log['count'] >= IP_REG_MAX_COUNT) {
                    $result['code'] = 0;
                    $result['msg'] = "IP注册量超过限制！";
                    return_json_encode($result);
                }

                //生成ID
                //$id = get_max_user_id($mobile);
                //生成token
                $token = get_login_token($mobile);

                $data = array(
                    'user_type' => 2,
                    'user_nickname' => '新注册用户-' . rand(88888, 99999),
                    'create_time' => NOW_TIME,
                    'last_login_time' => NOW_TIME,
                    'sex' => 0,
                    'avatar' => SITE_URL . '/image/headicon.png',
                    'mobile' => $mobile,
                    'token' => $token,
                    'address' => $address ? $address : "外太空",
                    'device_uuid' => $uuid,
                );

                $reg_result = db('user')->insertGetId($data);

                if ($reg_result) {
                    $result['data'] = array(
                        'id' => $reg_result,
                        'token' => $token,
                        'is_reg_perfect' => 0
                    );

                    $signature = load_cache('usersign', ['id' => $reg_result]);
                    if ($signature['status'] != 1) {
                        $result['code'] = 0;
                        $result['msg'] = $signature['error'];
                        return_json_encode($result);
                    }
                    $result['data']['user_sign'] = $signature['usersign'];

                    //增加注册IP记录
                    if ($ip_log) {
                        db('ip_reg_log')->where(['ip' => $client_ip])->setInc('count', 1);
                    } else {
                        db('ip_reg_log')->insert(['ip' => $client_ip, 'count' => 1]);
                    }

                    //添加渠道代理
                    reg_full_agent_code($reg_result, $agent_code);
                    //添加邀请码
                    create_invite_code_0910($reg_result);
                    reg_invite_service($reg_result, $invite_code);

                    require_once DOCUMENT_ROOT . '/system/im_common.php';
                    update_im_user_info($reg_result);

                    $result['code'] = 1;
                    $result['msg'] = "注册成功!";
                } else {
                    $result['code'] = 0;
                    $result['msg'] = "注册失败，请重新注册！";
                }
            }
        }

        $os = trim(input('param.os'));
        $sdk = trim(input('param.sdk_version'));
        $app = trim(input('param.app_version'));
        $brand = trim(input('param.brand'));
        $model = trim(input('param.model'));

        device_info($os, $sdk, $app, $brand, $model, $user_info['id']);

        return_json_encode($result);
    }

    //密码注册
    public function do_registered()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = input('param.mobile');
        $code = intval(input('param.code'));
        $password = trim(input('param.password'));
        $invite_code = trim(input('param.invite_code'));
        $agent_code = trim(input('param.agent'));
        $uuid = trim(input('param.uuid'));

        //是否开启设备注册限制
        if (defined('OPEN_DEVICE_REG_LIMIT') && OPEN_DEVICE_REG_LIMIT == 1) {

            //查询是否该设备已注册
            if (empty($uuid)) {
                $result['code'] = 0;
                $result['msg'] = "缺少设备ID!";
                return_json_encode($result);
            }
        }

        if (!is_numeric($mobile) || strlen($mobile) != 11) {
            $result['code'] = 0;
            $result['msg'] = '手机号码不正确！';
            return_json_encode($result);
        }

        if ($code == 0) {
            $result['code'] = 0;
            $result['msg'] = '验证码错误！';
            return_json_encode($result);
        }

        $config = load_cache('config');

        /*
         * 苹果上架审核
         * */
        if ($mobile == '13246579813' && $code == 111111 && $config['is_grounding'] == 1) {
            $ver = 1;
        } else {
            $ver = db('verification_code')->where("code='$code' and account='$mobile' and expire_time > " . NOW_TIME)->find();
        }
        if (!$ver) {

            $result['code'] = 0;
            $result['msg'] = "验证码错误，请重新获取！";
            return_json_encode($result);

        } else {

            $user_info = db('user')->where("mobile='$mobile'")->find();

            //登录
            if ($user_info) {
                $result['code'] = 0;
                $result['msg'] = "手机号码已注册!";
                return_json_encode($result);

            } else {

                //检查IP是否超过注册量
                $client_ip = request()->ip();
                $ip_log = db('ip_reg_log')->where(['ip' => $client_ip])->find();
                if ($ip_log && $ip_log['count'] >= IP_REG_MAX_COUNT) {
                    $result['code'] = 0;
                    $result['msg'] = "IP注册量超过限制！";
                    return_json_encode($result);
                }

                //生成ID
                $id = get_max_user_id($mobile);
                //生成token
                $token = get_login_token($id);

                $data = array(
                    'id' => $id,
                    'user_type' => 2,
                    'user_nickname' => '新注册用户-' . rand(88888, 99999),
                    'create_time' => NOW_TIME,
                    'last_login_time' => NOW_TIME,
                    'sex' => 1,
                    'avatar' => SITE_URL . '/image/headicon.png',
                    'mobile' => $mobile,
                    'token' => $token,
                    'address' => "外太空",
                    'user_pass' => md5($password),
                    'device_uuid' => $uuid,
                );

                $reg_result = db('user')->insert($data);

                if ($reg_result) {
                    $result['data'] = array(
                        'id' => $id,
                        'token' => $token,
                        'is_reg_perfect' => 0
                    );

                    $signature = load_cache('usersign', ['id' => $id]);
                    if ($signature['status'] != 1) {
                        $result['code'] = 0;
                        $result['msg'] = $signature['error'];
                        return_json_encode($result);
                    }
                    $result['data']['user_sign'] = $signature['usersign'];

                    //增加注册IP记录
                    if ($ip_log) {
                        db('ip_reg_log')->where(['ip' => $client_ip])->setInc('count', 1);
                    } else {
                        db('ip_reg_log')->insert(['ip' => $client_ip, 'count' => 1]);
                    }

                    //添加渠道代理
                    reg_full_agent_code($id, $agent_code);
                    //添加邀请码
                    create_invite_code_0910($id);
                    reg_invite_service($id, $invite_code);

                    require_once DOCUMENT_ROOT . '/system/im_common.php';
                    update_im_user_info($id);

                    $result['code'] = 1;
                    $result['msg'] = "注册成功!";
                } else {
                    $result['code'] = 0;
                    $result['msg'] = "注册失败，请重新注册！";
                }
            }
        }

        $os = trim(input('param.os'));
        $sdk = trim(input('param.sdk_version'));
        $app = trim(input('param.app_version'));
        $brand = trim(input('param.brand'));
        $model = trim(input('param.model'));

        device_info($os, $sdk, $app, $brand, $model, $user_info['id']);

        return_json_encode($result);
    }

    //密码修改
    public function upd_pass()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = input('param.mobile');
        $code = intval(input('param.code'));
        $password = trim(input('param.password'));

        if (!is_numeric($mobile) || strlen($mobile) != 11) {
            $result['code'] = 0;
            $result['msg'] = '手机号码不正确！';
            return_json_encode($result);
        }

        if ($code == 0) {
            $result['code'] = 0;
            $result['msg'] = '验证码错误！';
            return_json_encode($result);
        }

        $config = load_cache('config');

        /*
         * 苹果上架审核
         * */
        if ($mobile == '13246579813' && $code == 111111 && $config['is_grounding'] == 1) {
            $ver = 1;
        } else {
            $ver = db('verification_code')->where("code='$code' and account='$mobile' and expire_time > " . NOW_TIME)->find();
        }
        if (!$ver) {

            $result['code'] = 0;
            $result['msg'] = "验证码错误，请重新获取！";
            return_json_encode($result);

        } else {

            $user_info = db('user')->where("mobile='$mobile'")->find();
            if ($user_info) {
                $data = array(
                    'password' => md5($password),
                );
                $update_res = db('user')->where("id=" . $user_info['id'])->update($data);
                if ($update_res) {
                    $result['code'] = 1;
                    $result['msg'] = "修改成功，请重新登录！";
                    return_json_encode($result);
                } else {
                    $result['code'] = 0;
                    $result['msg'] = "修改失败！";
                    return_json_encode($result);
                }
            } else {
                $result['code'] = 0;
                $result['msg'] = "手机号码错误！";
                return_json_encode($result);
            }

        }


        return_json_encode($result);
    }

    //密码登陆
    public function do_mobile_pass()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = input('param.mobile');
        $password = trim(input('param.password'));

        if (!is_numeric($mobile) || strlen($mobile) != 11) {
            $result['code'] = 0;
            $result['msg'] = '手机号码不正确！';
            return_json_encode($result);
        }
        $user_info = db('user')->where("mobile='$mobile'")->find();

        //登录
        if ($user_info) {
            //生成token
            if ($user_info['user_pass'] != md5($password)) {
                $result['code'] = 0;
                $result['msg'] = '密码不正确！';
                return_json_encode($result);
                exit;
            }
            $token = get_login_token($user_info['id']);
            //更新的信息
            $data = array('token' => $token, 'last_login_time' => NOW_TIME, 'last_login_ip' => request()->ip(0, false),);
            $update_res = db('user')->where("id=" . $user_info['id'])->update($data);

            if (!$update_res) {
                $result['code'] = 0;
                $result['msg'] = "登录失败!";
            }
            //拉黑时间到取消拉黑状态
            $shielding_time=0;
            if($user_info['user_status'] == 0 && $user_info['shielding_time'] > 0 && $user_info['shielding_time'] < NOW_TIME){
                $data['shielding_time']  =0;
                $data['user_status']  =1;
                db('user')->where('id ='. $user_info['id']." and user_type=2")->update($data);
                $shielding_time=1;
            }

            if ($user_info['user_status'] != 0 || $shielding_time==1) {
                $result['code'] = 1;
                $result['msg'] = "登录成功!";

                $result['data'] = array(
                    'id' => $user_info['id'],
                    'token' => $token,
                    'sex' => $user_info['sex'],
                    'user_nickname' => $user_info['user_nickname'],
                    'avatar' => $user_info['avatar'],
                    'address' => $user_info['address'],
                    'is_reg_perfect' => $user_info['is_reg_perfect'],
                );

                $signature = load_cache('usersign', ['id' => $user_info['id']]);
                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }

                require_once DOCUMENT_ROOT . '/system/im_common.php';
                update_im_user_info($user_info['id']);

                $result['data']['user_sign'] = $signature['usersign'];
            } else {
                 if ($user_info['user_status'] == 0 && $user_info['shielding_time'] < NOW_TIME) {
                    $result['msg'] = "用户禁止登陆!";
                } else {
                    $result['msg'] = "您的账号已拉黑!";
                }
            }

        } else {
            $result['code'] = 0;
            $result['msg'] = '手机号码不存在！';
            return_json_encode($result);
            exit;
        }


        $os = trim(input('param.os'));
        $sdk = trim(input('param.sdk_version'));
        $app = trim(input('param.app_version'));
        $brand = trim(input('param.brand'));
        $model = trim(input('param.model'));

        device_info($os, $sdk, $app, $brand, $model, $user_info['id']);

        return_json_encode($result);
    }

    //完善注册信息
    public function perfect_reg_info()
    {


        $result = array('code' => 0, 'msg' => '');
        $one = request()->file('avatar');   //获取头像
        $sex = intval(request()->param('sex'));
        $user_nickname = trim(request()->param('user_nickname'));
        $uid = request()->param('uid');
        $address = trim(request()->param('address'));
        $token = trim(request()->param('token'));
        $user_info = check_login_token($uid, $token,['is_reg_perfect']);


        if ($sex == 0) {
            $result['msg'] = "请选择性别";
            return_json_encode($result);
        }
        if (empty($user_nickname)) {
            $result['msg'] = "用户名不能为空";
            return_json_encode($result);
        }

        $upload_one = SITE_URL . '/image/headicon.png';
        if ($one) {
            $upload_one = oss_upload($one);      //单图片上传
        }
       /* else {
            $result['msg'] = "请上传头像";
            return_json_encode($result);
        }*/
        $exits_name = db('user')->where("user_nickname='$user_nickname'")->find();
        if ($exits_name) {
            $result['code'] = 0;
            $result['msg'] = "用户名重复，请重新输入用户名";
            return_json_encode($result);
        }

        $config = load_cache('config');

        //注册赠送积分或钻石
        $give_coin = $config['system_coin_registered'];
        $give_income = $config['system_coin_registered_women'];
        if ($sex == 1) {
            $give_income = 0;
        } else {
            $give_coin = 0;
        }

        //防止刷接口
        if($user_info['is_reg_perfect'] == 1){
            $give_coin = 0;
            $give_income = 0;
        }

        $data = array(
            'user_nickname' => $user_nickname,
            'sex' => $sex == 1 ? $sex : 2,
            'avatar' => $upload_one,
            'address' => $address ? $address : "外太空",
            'is_reg_perfect' => 1,
            'income' => $give_income,
            'income_total' => $give_income,
            'coin' => $give_coin,
        );

        $update_res = db('user')->where("id=$uid")->update($data);

        //更新IM信息
        require_once DOCUMENT_ROOT . '/system/im_common.php';
        update_im_user_info($uid);

        if ($update_res) {
            //男用户完善信息后奖励
            if ($sex == 1) {
                reg_invite_perfect_info_service($uid, 1);
            }
            $result['data'] = $data;
            $result['code'] = 1;
            $result['msg'] = "";
        }
        return_json_encode($result);
    }

    //分享注册
    public function share_reg()
    {

        $invite_code = input('param.invite_code');

        $this->assign('invite_code', $invite_code);
        return $this->fetch();
    }

    //分享注册账号
    public function share_reg_insert()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $mobile = input('param.mobile');
        $code = intval(input('param.code'));
        $invite_code = trim(input('param.invite_code'));

        $config = load_cache('config');
        $ver = db('verification_code')->where("code='$code' and account='$mobile' and expire_time > " . NOW_TIME)->find();
        if (!$ver) {
            $result['code'] = 0;
            $result['msg'] = "验证码错误，请重新获取！";
        } else {

            $sdk_app_id = $config['tencent_sdkappid'];
            $user_info = db('user')->where("mobile='$mobile'")->find();

            if ($user_info) {
                $result['code'] = 0;
                $result['msg'] = '该手机已被注册!';
                return_json_encode($result);
            }
            //  注册
            $id = db('mb_user')->insertGetId(array('mobile' => $mobile));
            $token = md5($mobile . $id . NOW_TIME);
            $data = array(
                'id' => $id,
                'user_type' => 2,
                'user_nickname' => '新注册用户-' . rand(88888, 99999),
                'create_time' => NOW_TIME,
                'last_login_time' => NOW_TIME,
                'sex' => 1,
                'avatar' => SITE_URL . '/image/headicon.png',
                'mobile' => $mobile,
                'token' => $token,
                'address' => "外太空",
            );

            $reg_result = db('user')->insert($data);
            if ($reg_result) {

                $result['data'] = array(
                    'id' => $id,
                    'token' => $token,
                    'sdkappid' => $sdk_app_id,
                    'is_reg_perfect' => 0
                );

                $signature = load_cache('usersign', ['id' => $id]);
                if ($signature['status'] != 1) {
                    $result['code'] = 0;
                    $result['msg'] = $signature['error'];
                    return_json_encode($result);
                }
                $result['data']['user_sign'] = $signature['usersign'];

                //添加邀请码
                create_invite_code_0910($id);
                reg_invite_service($id, $invite_code);

                require_once DOCUMENT_ROOT . '/system/im_common.php';
                update_im_user_info($id);

                $result['code'] = 1;
                $result['msg'] = "注册成功!";
            } else {
                $result['code'] = 0;
                $result['msg'] = "注册失败，请重新注册！";
            }
        }

        return_json_encode($result);
    }

    //分享注册成功
    public function share_reg_success()
    {
        $config = load_cache('config');

        $this->assign('ios_download_url', $config['ios_download_url']);
        $this->assign('android_download_url', $config['android_download_url']);
        return $this->fetch();
    }

    //发送验证码
    public function code()
    {
        $result = array('code' => 0, 'msg' => '', 'data' => array());
        $mobile = input('param.mobile');
        if (empty($mobile)) {
            check_param($mobile);
        }

//        $img_code = get_input_param_str('img_code');
//
//        if (empty($img_code)) {
//            $result['code'] = 0;
//            $result['msg'] = '图形验证码错误！';
//            return_json_encode($result);
//        }
//
//
//        $save_code = $GLOBALS['redis']->get('img_code:' . $mobile);
//        if ($save_code != $img_code) {
//            $result['code'] = 0;
//            $result['msg'] = '图形验证码错误！';
//            return_json_encode($result);
//        }


        //TODO 限制 每个ip 的发送次数
        $code = get_verification_code($mobile);

        if (!$code) {
            $result['msg'] = "验证码发送过多,请明天再试!";
            //$data = array('msg'=>$result['msg'],'error'=>$code);
            //save_log($data);
            return_json_encode($result);
        }


        //互亿无线   message_type 0互亿无线 1天瑞短信  2阿里云短信
        //http://api.isms.ihuyi.com/webservice/isms.php?method=Submit

        //是否开启短信
        $config = load_cache('config');
        if ($config['system_sms_open'] == 1) {

            $result['smUuid'] = '';
            if ($config['message_type'] == 0) {
                $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";

                $post_data = "account=" . $config['system_sms_key'] . "&password=" . $config['system_sms_id'] . "&mobile=" . $mobile . "&content=" . rawurlencode("您的验证码是：" . $code . "。请不要把验证码泄露给其他人。");
                //密码可以使用明文密码或使用32位MD5加密
                $gets = xml_to_array(post($post_data, $target));

                if ($gets['SubmitResult']['code'] == 2) {

                    $result['code'] = 1;
                    $result['msg'] = '验证码已经发送成功!';
                } else {
                    $result['code'] = 0;
                    $result['msg'] = $gets['SubmitResult']['msg'];
                }

            } elseif ($config['message_type'] == 1) {
                // tianrui_accesskey   tianrui_secret tianrui_sign tianrui_templateId
                $target = "http://api.1cloudsp.com/api/v2/single_send";

                $post_data = "accesskey=" . $config['tianrui_accesskey'] . "&secret=" . $config['tianrui_secret'] . "&sign=" . $config['tianrui_sign'] . "&templateId=" . $config['tianrui_templateId'] . "&mobile=" . $mobile . "&content=" . $code;
                //密码可以使用明文密码或使用32位MD5加密

                $gets = json_decode(post($post_data, $target), true);

                if ($gets['code'] == 0) {
                    $result['code'] = 1;
                    $result['msg'] = '验证码已经发送成功!';
                    $result['smUuid'] = $gets['smUuid'];
                } else {
                    $result['code'] = 0;
                    $result['msg'] = $gets['msg'];
                    $result['smUuid'] = $gets['smUuid'];
                }
            } else {
                require_once DOCUMENT_ROOT . "/system/sms/aliopenapi-sdk/vendor/autoload.php";
                //阿里云短信发送
                try {
                    AlibabaCloud::accessKeyClient($config['aliyun_name'], $config['aliyun_name_psd'])
                        ->regionId('cn-hangzhou')
                        ->asGlobalClient();
                    $results = AlibabaCloud::rpcRequest()
                        ->product('Dysmsapi')
                        ->version('2017-05-25')
                        ->action('SendSms')
                        ->method('POST')
                        ->options([
                            'query' => [
                                'RegionId' => 'cn-hangzhou',
                                'SignName' => "{$config['aliyun_name_signature']}",
                                'PhoneNumbers' => $mobile,
                                'TemplateCode' => $config['aliyun_name_template_id'],
                                'TemplateParam' => '{"code":"' . $code . '"}',
                            ],
                        ])
                        ->request();
                } catch (ClientException $e) {
                    echo $e->getErrorMessage() . PHP_EOL;
                } catch (ServerException $e) {
                    echo $e->getErrorMessage() . PHP_EOL;
                }
                $return_data = $results->toArray();

                $result['code'] = $return_data['Code'] == 'OK' ? 1 : 0;
                $result['msg'] = $return_data['Message'] == 'OK' ? '验证码已经发送成功!' : $return_data['Message'];
            }
        } else {
            $code = '123456';
            $result['msg'] = '验证码已经发送成功!';
            $result['code'] = 1;
            $result['smUuid'] = '';
        }
        verification_code_log($mobile, $code, $result);

        return_json_encode($result);
    }

    //获取图形验证码
    public function get_img_code()
    {

        $mobile = get_input_param_str('mobile');

        $config = [
            // 验证码字体大小
            'fontSize' => 30,
            // 验证码位数
            'length' => 3,
            // 关闭验证码杂点
            'useNoise' => false,
        ];

        $captcha = new Captcha($config);
        return $captcha->entry($mobile);
    }

    public function share_reg_new()
    {
        $invite_code = input('param.invite_code');

        $invite_code_info = db('invite_code')->where('invite_code', '=', $invite_code)->find();
        $user_info = get_user_base_info($invite_code_info['user_id']);

        $this->assign('user_info', $user_info);
        $this->assign('invite_code', $invite_code);
        return $this->fetch();
    }


    //完善注册信息
    public function perfect_reg_info_190708()
    {

        $result = array('code' => 0, 'msg' => '');
        $avatar = trim(request()->post('avatar'));   //获取头像
        $sex = intval(request()->post('sex'));
        $user_nickname = trim(request()->post('user_nickname'));
        $uid = request()->post('uid');
        $address = trim(request()->post('address'));
        $token = trim(request()->post('token'));

        $user_info = check_login_token($uid, $token);

        if ($sex == 0) {
            $result['msg'] = "请选择性别";
            return_json_encode($result);
        }
        if (empty($user_nickname)) {
            $result['msg'] = "用户名不能为空";
            return_json_encode($result);
        }

        if (empty($avatar)) {
            $result['msg'] = "请上传头像";
            return_json_encode($result);
        }

        $exits_name = db('user')->where("user_nickname='$user_nickname'")->find();
        if ($exits_name) {
            $result['code'] = 0;
            $result['msg'] = "用户名重复，请重新输入用户名";
            return_json_encode($result);
        }

        $config = load_cache('config');

        //注册赠送积分或钻石
        $give_coin = $config['system_coin_registered'];
        $give_income = $config['system_coin_registered_women'];
        if ($sex == 1) {
            $give_income = 0;
        } else {
            $give_coin = 0;
        }

        $data = array(
            'user_nickname' => $user_nickname,
            'sex' => $sex == 1 ? $sex : 2,
            'avatar' => $avatar,
            'address' => $address ? $address : "外太空",
            'is_reg_perfect' => 1,
            'income' => $give_income,
            'income_total' => $give_income,
            'coin' => $give_coin,

        );

        $update_res = db('user')->where("id=$uid")->update($data);

        //更新IM信息
        require_once DOCUMENT_ROOT . '/system/im_common.php';
        update_im_user_info($uid);

        if ($update_res) {
            //男用户完善信息后奖励
            if ($sex == 1) {
                reg_invite_perfect_info_service($uid, 1);
            }
            $result['data'] = $data;
            $result['code'] = 1;
            $result['msg'] = "";
        }
        return_json_encode($result);
    }
}
