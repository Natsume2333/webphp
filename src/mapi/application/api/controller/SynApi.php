<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/29
 * Time: 0:25
 */

namespace app\api\controller;


use think\App;

class SynApi extends Base
{

    public function clear_all(){
        if(App::$debug){
            //删除用户数据
            db('user') -> where('id','neq',1) -> delete();
            db('user_attention') -> where('1=1') -> delete();
            db('user_auth_video') -> where('1=1') -> delete();
            db('auth_form_record') -> where('1=1') -> delete();
            db('user_black') -> where('1=1') -> delete();
            db('user_cash_record')-> where('1=1')  -> delete();
            db('user_charge_log') -> where('1=1') -> delete();
            db('user_consume_log')-> where('1=1')  -> delete();
            db('user_fabulous_record') -> where('1=1') -> delete();
            db('user_favorite')-> where('1=1')  -> delete();
            db('user_gift_log') -> where('1=1') -> delete();
            db('user_identity') -> where('1=1') -> delete();
            db('user_img') -> where('1=1') -> delete();
            db('user_message_all') -> where('1=1') -> delete();
            db('user_message_log')-> where('1=1')  -> delete();
            db('user_photo_buy')-> where('1=1')  -> delete();
            db('user_pictures') -> where('1=1') -> delete();
            db('user_reference')-> where('1=1')  -> delete();
            db('user_report')-> where('1=1')  -> delete();
            db('user_report_img')-> where('1=1')  -> delete();
            db('user_report_type')-> where('1=1')  -> delete();
            db('user_video')-> where('1=1')  -> delete();
            db('user_video_attention')-> where('1=1')  -> delete();
            db('user_video_buy')-> where('1=1')  -> delete();
            db('user_video_coin')-> where('1=1')  -> delete();
            db('invite_code')-> where('1=1')  -> delete();
            db('invite_profit_record')-> where('1=1')  -> delete();
            db('invite_record')-> where('1=1')  -> delete();
            db('monitor')-> where('1=1')  -> delete();


            db('agent')-> where('1=1')  -> delete();
            db('agent_information')-> where('1=1')  -> delete();
            // db('agent_link')-> where('1=1')  -> delete();
            db('agent_order_log')-> where('1=1')  -> delete();
            // db('agent_profit_record')-> where('1=1')  -> delete();
            // db('agent_settlement')-> where('1=1')  -> delete();
            // db('agent_user')-> where('1=1')  -> delete();
            db('agent_withdrawal')-> where('1=1')  -> delete();

            db('bzone_images')-> where('1=1')  -> delete();
            db('bzone')-> where('1=1')  -> delete();
            db('bzone_like')-> where('1=1')  -> delete();
            db('bzone_reply')-> where('1=1')  -> delete();


        }
    }

}