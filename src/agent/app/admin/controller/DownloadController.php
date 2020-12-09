<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/23 0023
 * Time: 下午 15:32
 */

namespace app\admin\controller;

use cmf\controller\BaseController;
use think\Db;


class DownloadController extends BaseController
{
    //代理推广下载页面
    public function index()
    {
        $agent = input("param.agent");    //获取代理的渠道号

        if (strpos($_SERVER['HTTP_USER_AGENT'], 'iPhone') || strpos($_SERVER['HTTP_USER_AGENT'], 'iPad')) {
            $download = db("config")->where("code='ios_download_url'")->find();
        } else if (strpos($_SERVER['HTTP_USER_AGENT'], 'Android')) {
            $download = db("config")->where("code='android_download_url'")->find();
        } else {
            $download = db("config")->where("code='android_download_url'")->find();
        }

        $this->assign('agent', $agent);

        $this->assign('download', $download['val']);

        $config = load_cache('config');
        $this->assign('openinstall_key',$config['openinstall_key']);
        return view();
    }

    public function _initializeView()
    {
        $cmfAdminThemePath = config('cmf_admin_theme_path');
        $cmfAdminDefaultTheme = config('cmf_admin_default_theme');

        $themePath = "{$cmfAdminThemePath}{$cmfAdminDefaultTheme}";

        $root = cmf_get_root();

        //使cdn设置生效
        $cdnSettings = cmf_get_option('cdn_settings');
        if (empty($cdnSettings['cdn_static_root'])) {
            $viewReplaceStr = [
                '__ROOT__' => $root,
                '__TMPL__' => "{$root}/{$themePath}",
                '__STATIC__' => "{$root}/static",
                '__WEB_ROOT__' => $root
            ];
        } else {
            $cdnStaticRoot = rtrim($cdnSettings['cdn_static_root'], '/');
            $viewReplaceStr = [
                '__ROOT__' => $root,
                '__TMPL__' => "{$cdnStaticRoot}/{$themePath}",
                '__STATIC__' => "{$cdnStaticRoot}/static",
                '__WEB_ROOT__' => $cdnStaticRoot
            ];
        }

        $viewReplaceStr = array_merge(config('view_replace_str'), $viewReplaceStr);
        config('template.view_base', "$themePath/");
        config('view_replace_str', $viewReplaceStr);
    }
}