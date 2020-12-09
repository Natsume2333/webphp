<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/5/19
 * Time: 17:15
 */

require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidBroadcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidFilecast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidGroupcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidUnicast.php');
require_once(dirname(__FILE__) . '/' . 'notification/android/AndroidCustomizedcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSBroadcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSFilecast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSGroupcast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSUnicast.php');
require_once(dirname(__FILE__) . '/' . 'notification/ios/IOSCustomizedcast.php');

class BuguPush
{
    protected $appkey = NULL;
    protected $appMasterSecret = NULL;
    protected $timestamp = NULL;
    protected $validation_token = NULL;

    function __construct($key, $secret)
    {
        $this->appkey = $key;
        $this->appMasterSecret = $secret;
        $this->timestamp = strval(time());
    }

    function sendAndroidCustomizedcast($after_open = 'go_app',$alias,$alias_type,$ticker,$title,$text,$custom = '') {
        try {
            $customizedcast = new AndroidCustomizedcast();
            $customizedcast->setAppMasterSecret($this->appMasterSecret);
            $customizedcast->setPredefinedKeyValue("appkey",           $this->appkey);
            $customizedcast->setPredefinedKeyValue("timestamp",        $this->timestamp);
            // Set your alias here, and use comma to split them if there are multiple alias.
            // And if you have many alias, you can also upload a file containing these alias, then
            // use file_id to send customized notification.
            $customizedcast->setPredefinedKeyValue("alias",            $alias);
            // Set your alias_type here
            $customizedcast->setPredefinedKeyValue("alias_type",       $alias_type);
            $customizedcast->setPredefinedKeyValue("ticker",           $ticker);
            $customizedcast->setPredefinedKeyValue("title",            $title);
            $customizedcast->setPredefinedKeyValue("text",             $text);
            if(!empty($custom)){

                $customizedcast->setPredefinedKeyValue("custom",           $custom);
            }
            $customizedcast->setPredefinedKeyValue("after_open",      $after_open);
            //print("Sending customizedcast notification, please wait...\r\n");
            $customizedcast->send();
            //print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            //print("Caught exception: " . $e->getMessage());
        }
    }


    function sendIOSCustomizedcast($after_open = 'go_app',$alias,$alias_type,$ticker,$title,$text,$custom = '') {

        $config = load_cache('config');
        $this -> appkey = $config['umengapp_ios_key'];
        $this -> appMasterSecret = $config['umengapp_ios_secret'];
        try {
            $customizedcast = new IOSCustomizedcast();
            $customizedcast->setAppMasterSecret("f2segetqdezrikfs05k1hcyfwxgxzwbw");
            $customizedcast->setPredefinedKeyValue("appkey",           "5afd26e98f4a9d45440000a7");
            $customizedcast->setPredefinedKeyValue("timestamp",        $this->timestamp);

            // Set your alias here, and use comma to split them if there are multiple alias.
            // And if you have many alias, you can also upload a file containing these alias, then
            // use file_id to send customized notification.
            $customizedcast->setPredefinedKeyValue("alias", $alias);
            // Set your alias_type here
            $customizedcast->setPredefinedKeyValue("alias_type", $alias_type);
            $senddata['title'] = $title;
            //$senddata['subtitle'] = $text;
            $senddata['body'] = $text;
            $customizedcast->setPredefinedKeyValue("alert", $senddata);
            $customizedcast->setPredefinedKeyValue("badge", 0);
            $customizedcast->setPredefinedKeyValue("sound", "chime");
            // Set 'production_mode' to 'true' if your app is under production mode
            $customizedcast->setPredefinedKeyValue("production_mode", "false");
            //print("Sending customizedcast notification, please wait...\r\n");

            $customizedcast->send();
            file_put_contents("./pushdebug.txt","1");
            //print("Sent SUCCESS\r\n");
        } catch (Exception $e) {
            //print("Caught exception: " . $e->getMessage());
        }
    }


}