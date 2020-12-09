<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/5/18
 * Time: 09:22
 */


use think\App;

header("Content-Type:text/html; charset=utf-8");
require_once './system/define.php';
require_once './mapi/thinkphp/base.php';

// 2. 执行应用
App::initCommon();

$config = db('config') -> select();
dump($config);
exit;


$json = $GLOBALS['HTTP_RAW_POST_DATA'];
$post = json_decode($json,true);
if ($post['CallbackCommand'] == 'C2C.CallbackBeforeSendMsg'){
    if($post['MsgBody'][0]['MsgType'] == 'TIMTextElem'){

        require_once './system/umeng/BuguPush.php';

        $push = new BuguPush('5afa96c4f29d983e09000070','axpsgwhp3c5mnwvpgpi4kny6jixc2ml1');

        $custom = [
            'action' => 1,
            'user_id' => $post['From_Account']
        ];
        $push -> sendAndroidCustomizedcast('go_app',$post['To_Account'],'buguniao','私信消息','你有新的消息',$post['MsgBody'][0]['MsgContent']['Text'],json_encode($custom));
    }
}


file_put_contents('./im_callback.txt',$json);