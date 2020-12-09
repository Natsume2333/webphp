<?php


namespace app\api\controller;


class CustomSayFuncPlugsApi extends Base
{

    /**
     * @dw 添加自定话术
     * */
    public function add_custom_msg()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        //$user_info = check_login_token($uid, $token);

        $msg_str = get_input_param_str('msg_str');
        $list_kv = explode('-', $msg_str);

        $custom_msg_array = [];
        foreach ($list_kv as $v) {
            if (empty($v)) {
                continue;
            }
            $kv = explode(':', $v);
            $custom_msg_array[] = $kv;
            if(empty($kv[1])){
                db('custom_auto_msg')->where('user_id', $uid)->where('id', $kv[0])->delete();
                continue;
            }

            if ($kv[0] == 0) {
                $add_msg = ['user_id' => $uid, 'msg' => $kv[1], 'create_time' => NOW_TIME];
                db('custom_auto_msg')->insert($add_msg);
            } else {
                db('custom_auto_msg')->where('user_id', $uid)->where('id', $kv[0])->update(['msg' => $kv[1]]);
            }
        }

        return_json_encode($result);
    }

    /**
     * @dw 获取自定义话术列表
     * */
    public function get_custom_msg_list()
    {

        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        //$user_info = check_login_token($uid, $token);

        $result['list'] = db('custom_auto_msg')->where('user_id', $uid)->select();
        return_json_encode($result);
    }

}