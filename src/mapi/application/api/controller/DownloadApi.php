<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/14 0014
 * Time: 下午 14:47
 */
namespace app\api\controller;
use app\api\controller\Base;
use think\Db;
// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class DownloadApi extends Base{
    //分享下载页面
    public function index(){

//        if(strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone')||strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')){
//            $download=db("config")->where("code='ios_download_url'")->find();
//        }else if(strpos($_SERVER['HTTP_USER_AGENT'], 'Android')){
//            $download=db("config")->where("code='android_download_url'")->find();
//        }else{
//            $download=db("config")->where("code='android_download_url'")->find();
//        }
//        $this->assign('download',$download['val']);

        $config = load_cache('config');
        $url = SITE_URL;
        $download_background = $url.'/admin/public/upload/'.$config['download_background'];
        $this->assign('download_background',$download_background);
        $this->assign('download_log',$config['download_log']);
        $this->assign('system_name',$config['system_name']);
        $this->assign('openinstall_key',$config['openinstall_key']);
        $this->assign('android_download_url',$config['android_download_url']);
        $this->assign('ios_download_url',$config['ios_download_url']);
        $this->assign('invite_code',$_GET['invite_code']);
        return $this->fetch();
    }
}