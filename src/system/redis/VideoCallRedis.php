<?php


/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/8/12
 * Time: 20:24
 */

class VideoCallRedis
{
    public $key = "video_call_record:";
    public function do_call($uid,$to_user_id){

        $key = $this -> key . $to_user_id;
        $is_exits = $GLOBALS['redis']->get($key);
        if($is_exits){
            return 10001;
        }

        $GLOBALS['redis']->set($key,$uid,10);
        return 10000;
    }

    public function del_call($uid){

        $key = $this -> key . $uid;

        $GLOBALS['redis']->del('del',$key);
    }

}