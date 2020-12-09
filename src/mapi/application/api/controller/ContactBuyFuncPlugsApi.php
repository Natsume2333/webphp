<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2019/5/19
 * Time: 20:45
 */

namespace app\api\controller;

//购买联系方式插件
class ContactBuyFuncPlugsApi extends PlugsBaseApi
{

    //绑定联系方式设定价格
    public function bind_contact()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => array());
        
        $user_id = get_input_param_int('uid');
        $token = get_input_param_str('token');
        $type = get_input_param_str('type') ?  get_input_param_str('type'):'';
        
        //$user_id = 100186;
        //$token = '168e43586873c26609ee4229013f5857';

        $user_info = check_login_token($user_id, $token, ['wx_number', 'qq_number', 'phone_number'
            , 'wx_price', 'qq_price', 'wx_price', 'phone_price']);
        if($type){
            $result['data']= $user_info;
            return_json_encode($result);
        }else{
            $this->assign('uid', $user_id);
            $this->assign('token', $token);
            $this->assign('user_info', $user_info);
            return $this->fetch();
        }
      
    }

    //购买
    public function buy_contact()
    {

        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $user_id = get_input_param_int('uid');
        $token = get_input_param_str('token');

        $user_info = check_login_token($user_id, $token);

        $to_user_id = get_input_param_int('to_user_id');
        $type = get_input_param_str('type');
//        $buy_record = db('buy_contact_record')->where('user_id=' . $user_id . ' and to_user_id=' . $to_user_id . ' and type="'.$type.'"')->find();
//        if (!$buy_record) {
//            return_json_encode($result);
//        }

        //查询余额是否足够
        $to_user_info = get_user_base_info($to_user_id, ['wx_number', 'qq_number', 'phone_number'
            , 'wx_price', 'qq_price', 'phone_price']);

        if ($type != 'wx' && $type != 'qq' && $type != 'phone') {
            $result['msg'] = '参数错误！';
            return_json_encode($result);
        }

        $content = '';
        $type_host = '';
        $price = 0;
        $number = '';
        if ($type == 'wx') {
            $price = $to_user_info['wx_price'];
            if (empty($to_user_info['wx_number'])) {
                $result['msg'] = '对方未设置微信号';
                return_json_encode($result);
            }
            $content = '购买微信';
            $type_host = 8;
            $number = $to_user_info['wx_number'];
        }

        if ($type == 'qq') {
            $price = $to_user_info['qq_price'];
            if (empty($to_user_info['qq_number'])) {
                $result['msg'] = '对方未设置QQ号';
                return_json_encode($result);
            }
            $content = '购买QQ';
            $type_host = 8;
            $number = $to_user_info['qq_number'];
        }

        if ($type == 'phone') {
            $price = $to_user_info['phone_price'];
            if (empty($to_user_info['phone_number'])) {
                $result['msg'] = '对方未设置手机号';
                return_json_encode($result);
            }
            $content = '购买手机号码';
            $type_host = 8;
            $number = $to_user_info['phone_number'];
        }

        if ($price > $user_info['coin']) {
            $result['code'] = 10017;
            $result['msg'] = '余额不足';
            return_json_encode($result);
        }

        //减少金额
        del_coin($user_id, $price);
        $price1 = host_income_commission($type_host,$price,$to_user_id);
        $price1 = floor( $price1 );
        add_income($to_user_id, $price1);

        //添加购买记录
        $table_id = db('buy_contact_record')->insert(['user_id' => $user_id, 'to_user_id' => $to_user_id, 'type' => $type, 'coin' => $price1, 'create_time' => NOW_TIME]);
        //var_dump($table_id);die;
        //添加消费记录
        add_charging_log($user_id, $to_user_id, self::consume_buy_contact_type, $price, $table_id, $price1, $content);
        $result['number'] = $number;
        $result['code'] = 1;
        return_json_encode($result);
    }

    //点击查看
    public function select_contact()
    {
        $result = array('code' => 0, 'msg' => '');

        $user_id = get_input_param_int('uid');
        $token = get_input_param_str('token');

        $user_info = check_login_token($user_id, $token);

        $to_user_id = get_input_param_int('to_user_id');
        $type = get_input_param_str('type');

        //查询余额是否足够
        $to_user_info = get_user_base_info($to_user_id, ['wx_number', 'qq_number', 'phone_number'
            , 'wx_price', 'qq_price', 'phone_price']);
        $price = 0;
        $content = '';
        if ($type == 'wx') {
            $price = $to_user_info['wx_price'];
            if (empty($to_user_info['wx_number'])) {
                $result['msg'] = '对方未设置微信号';
                return_json_encode($result);
            }
            $content = $to_user_info['wx_number'];
        }

        if ($type == 'qq') {
            $price = $to_user_info['qq_price'];
            if (empty($to_user_info['qq_number'])) {
                $result['msg'] = '对方未设置QQ号';
                return_json_encode($result);
            }
            $content = $to_user_info['qq_number'];
        }

        if ($type == 'phone') {
            $price = $to_user_info['phone_price'];
            if (empty($to_user_info['phone_number'])) {
                $result['msg'] = '对方未设置手机号';
                return_json_encode($result);
            }
            $content = $to_user_info['phone_number'];
        }

        //查询是否购买
        $buy_record = db('buy_contact_record')->where('user_id=' . $user_id . ' and to_user_id=' . $to_user_id . ' and type="' . $type . '"')->find();
        $result['code'] = 1;
        if (!$buy_record) {

            $result['price'] = $price;
            return_json_encode($result);
        }

        
        $result['number'] = $content;
        return_json_encode($result);

    }

    //保存
    public function save_contact()
    {

        $result = array('code' => 0, 'msg' => '', 'data' => array());

        $user_id = get_input_param_int('uid');
        $token = get_input_param_str('token');

        //$user_id = 100186;
        //$token = '168e43586873c26609ee4229013f5857';

        $user_info = check_login_token($user_id, $token);

        $data['wx_number'] = get_input_param_str('wx_number');
        $data['wx_price'] = get_input_param_int('wx_price');
        $data['qq_number'] = get_input_param_str('qq_number');
        $data['qq_price'] = get_input_param_int('qq_price');
        $data['phone_number'] = get_input_param_str('phone_number');
        $data['phone_price'] = get_input_param_int('phone_price');
        if (empty($data['wx_number'])) {
            $result['msg'] = '微信账号不能为空!';
            return_json_encode($result);
        }

        if ($data['wx_price'] == 0) {
            $result['msg'] = '微信价格不能为0!';
            return_json_encode($result);
        }

        if (empty($data['qq_number'])) {
            $result['msg'] = 'qq账号不能为空!';
            return_json_encode($result);
        }

        if ($data['qq_price'] == 0) {
            $result['msg'] = 'qq价格不能为0!';
            return_json_encode($result);
        }

        if (empty($data['phone_number'])) {
            $result['msg'] = '手机账号不能为空!';
            return_json_encode($result);
        }

        if ($data['phone_price'] == 0) {
            $result['msg'] = '手机价格不能为0!';
            return_json_encode($result);
        }

        db('user')->where('id=' . $user_id)->update($data);

        $result['msg'] = '保存成功！';
        $result['code'] = 1;
        return_json_encode($result);
    }
}