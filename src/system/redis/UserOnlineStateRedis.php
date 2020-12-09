<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/8/13
 * Time: 13:54
 */

class UserOnlineStateRedis
{

    public $key_female = "online_user_list:female:";
    public $key_male = "online_user_list:male:";

    public function change_state($uid, $action)
    {
        $user_base_info = get_user_base_info($uid);
        $key = $user_base_info['sex'] == 1 ? $this->key_male : $this->key_female;
        if ($action == 'Login') {
            db('user')->where('id', '=', $uid)->setField('is_online', 1);
            $is_exits = $GLOBALS['redis']->hGet($key, $uid);
            if ($is_exits) {
                return 10000;
            }
            $GLOBALS['redis']->hSet($key, $uid, NOW_TIME);
        } else if ($action == 'Logout' || $action == 'Disconnect' || $action == 'TimeOut') {

            db('user')->where('id', '=', $uid)->setField('is_online', 0);
            $GLOBALS['redis']->hDel($key, $uid);
            //防止腾讯云错误回调进行接口校验
//            require_once DOCUMENT_ROOT . '/system/im_common.php';
//            $ser = im_check_user_online_state($uid);
//            if (count($ser['QueryResult']) > 0 && $ser['QueryResult'][0]['State'] != 'Online' && $ser['QueryResult'][0]['To_Account'] == $uid) {
//                db('user')->where('id', '=', $uid)->setField('is_online', 0);
//                $GLOBALS['redis']->hDel($key, $uid);
//            } else {
//                db('user')->where('id', '=', $uid)->setField('is_online', 1);
//                $is_exits = $GLOBALS['redis']->hGet($key, $uid);
//                if ($is_exits) {
//                    return 10000;
//                }
//                $GLOBALS['redis']->hSet($key, $uid, NOW_TIME);
//            }
            return 10000;
        }
        return 10001;
    }

    //判断用户是否在线
    public function is_online($user_id)
    {
        $user_info = get_user_base_info($user_id);
        if ($user_info['sex'] == 1) {
            $res = $GLOBALS['redis']->hGet($this->key_male, $user_id);
        } else {
            $res = $GLOBALS['redis']->hGet($this->key_female, $user_id);
        }
        return $res;
    }

    //获取在线女性用户
    public function get_female_online_user()
    {

    }

    //获取所有咱先用户数量
    public function get_online_user_count()
    {

        $female_male_count = $GLOBALS['redis']->hLen($this->key_female);
        $male_count = $GLOBALS['redis']->hLen($this->key_male);

        return $female_male_count + $male_count;
    }

    //获取女性在线数量
    public function get_female_online_count()
    {

        $female_male_count = $GLOBALS['redis']->hLen($this->key_female);

        return $female_male_count;
    }

    //获取男性在线数量
    public function get_male_online_count()
    {

        $male_count = $GLOBALS['redis']->hLen($this->key_male);

        return $male_count;
    }
}