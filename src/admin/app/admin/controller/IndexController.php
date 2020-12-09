<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use app\admin\model\AdminMenuModel;

class IndexController extends AdminBaseController
{

    public function _initialize()
    {
        $adminSettings = cmf_get_option('admin_settings');
        if (empty($adminSettings['admin_password']) || $this->request->path() == $adminSettings['admin_password']) {
            $adminId = cmf_get_current_admin_id();
            if (empty($adminId)) {
                session("__LOGIN_BY_CMF_ADMIN_PW__", 1);//设置后台登录加密码
            }
        }

        parent::_initialize();
    }

    /**
     * 后台首页
     */
    public function index()
    {
        $adminMenuModel = new AdminMenuModel();
        $menus = $adminMenuModel->menuTree();
        //  var_dump($menus);exit;
        $this->assign("menus", $menus);

        $admin = Db::name("user")->where('id', cmf_get_current_admin_id())->find();

        $this->assign('admin', $admin);

        return $this->fetch();
    }

    //未读的消息 认证
    public function message_index()
    {
        //视频审核统计
        $data['user_video'] = db("user_video")->where("type=0")->count();
        //私照审核统计
        $data['user_pictures'] = db("user_pictures")->where("status=0")->count();
        //封面图审核统计
        $data['user_img'] = db("user_img")->where("status=0")->count();
        //信息认证审核统计
        $data['auth_record'] = db("auth_form_record")->where("status=0")->count();

        echo json_encode($data);
        exit;
    }

}

