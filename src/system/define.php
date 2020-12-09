<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/2
 * Time: 9:21
 */


define('DOCUMENT_ROOT',$_SERVER['DOCUMENT_ROOT']);

define('NOW_TIME',time());

define("SITE_URL",'http://' . $_SERVER['HTTP_HOST']);

define('OPEN_CUSTOM_VIDEO_CHARGE_COIN',1);//是否开启自定义分钟扣费金额

define("IP_REG_MAX_COUNT",100); //每个IP只能注册的数量

define("IS_TEST",0); //是否是测试模式

define('OPEN_STAR',0);//是否开启星级模式

define('OPEN_INVITE',0);//是否开启邀请模块

define('OPEN_VIDEO_CHAT',0);//是否开启视频聊

define('OPEN_PAY_PAL',0);//是否开启PayPal支付

define('OPEN_SANDBOX',1);//是否是沙盒环境

define('OPEN_FIRST_FREE',0);//开启第一分钟免费

define('OPEN_VOICE_CALL',1);//开启音频通话

define('OPEN_DEVICE_REG_LIMIT',0);//开启设备注册限制

define('OPEN_CUSTOM_AUTO_REPLY',0);//是否开启自定义回复  （未完成功能，若干年后）

define('OPEN_BUY_CONTACT_PLUGS',0);//是否开启购买联系方式插件

define('OPEN_AUTO_SEE_HI_PLUGS',0);//是否开启自动打招呼插件

define('OPEN_SIGN_IN',1);//是否开启每日签到功能模块

define('OPEN_THE_UNION',1);//是否开启工会模块





