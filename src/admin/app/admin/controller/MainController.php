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
use app\admin\model\Menu;

class MainController extends AdminBaseController
{

    public function _initialize()
    {
        parent::_initialize();
    }

    /**
     *  后台欢迎页
     */
    public function index()
    {
        $dashboardWidgets = [];
        $widgets          = cmf_get_option('admin_dashboard_widgets');

        $defaultDashboardWidgets = [
            '_SystemCmfHub'           => ['name' => 'CmfHub', 'is_system' => 1],
            '_SystemMainContributors' => ['name' => 'MainContributors', 'is_system' => 1],
            '_SystemContributors'     => ['name' => 'Contributors', 'is_system' => 1],
            '_SystemCustom1'          => ['name' => 'Custom1', 'is_system' => 1],
            '_SystemCustom2'          => ['name' => 'Custom2', 'is_system' => 1],
            '_SystemCustom3'          => ['name' => 'Custom3', 'is_system' => 1],
            '_SystemCustom4'          => ['name' => 'Custom4', 'is_system' => 1],
            '_SystemCustom5'          => ['name' => 'Custom5', 'is_system' => 1],
        ];

        if (empty($widgets)) {
            $dashboardWidgets = $defaultDashboardWidgets;
        } else {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    $dashboardWidgets['_System' . $widget['name']] = ['name' => $widget['name'], 'is_system' => 1];
                } else {
                    $dashboardWidgets[$widget['name']] = ['name' => $widget['name'], 'is_system' => 0];
                }
            }

            foreach ($defaultDashboardWidgets as $widgetName => $widget) {
                $dashboardWidgets[$widgetName] = $widget;
            }


        }

        $dashboardWidgetPlugins = [];

        $hookResults = hook('admin_dashboard');

        if (!empty($hookResults)) {
            foreach ($hookResults as $hookResult) {
                if (isset($hookResult['width']) && isset($hookResult['view']) && isset($hookResult['plugin'])) { //验证插件返回合法性
                    $dashboardWidgetPlugins[$hookResult['plugin']] = $hookResult;
                    if (!isset($dashboardWidgets[$hookResult['plugin']])) {
                        $dashboardWidgets[$hookResult['plugin']] = ['name' => $hookResult['plugin'], 'is_system' => 0];
                    }
                }
            }
        }


        $smtpSetting = cmf_get_option('smtp_setting');

        //充值总数
     //   $total_money = db('user_charge_log') -> where('status','=',1) -> sum('money');
        //总注册用户数量
    //    $total_reg_user = db('user') -> count();
        //正在通话
    //    $video_call_count = db('video_call_record') -> count();
        //充值总单数
   //     $total_recharge_count = db('user_charge_log') -> count();

        $config = load_cache('config');
        //获取总在线人数
  //      $online_count = db('user') -> where('is_online','=', 1) -> count();

        //注册趋势
    //    $register = Db::query("SELECT FROM_UNIXTIME(create_time,'%Y-%m-%d') as name , count(*) as value FROM cmf_user GROUP BY name ");
    //    $invite = Db::query("SELECT FROM_UNIXTIME(addtime,'%Y-%m-%d') as name , SUM(money) as value FROM cmf_user_charge_log GROUP BY name ");

    //    $dataregister = json_encode($register);
    //    $invites = json_encode($invite);

     //   $this->assign('register',$dataregister);
        //Android充值
        $where['addtime'] = ['between',[strtotime(time()),strtotime(time())+86400]];
        db('user_charge_log')->where($where)->sum('money');
        //IOS充值

        $admin_index_list=admin_index_list();
        $this->assign('admin_index_list',$admin_index_list);
        $this->assign('dashboard_widgets', $dashboardWidgets);
        $this->assign('dashboard_widget_plugins', $dashboardWidgetPlugins);
        $this->assign('has_smtp_setting', empty($smtpSetting) ? false : true);

        return $this->fetch();
    }

    public function dashboardWidget()
    {
        $dashboardWidgets = [];
        $widgets          = $this->request->param('widgets/a');
        if (!empty($widgets)) {
            foreach ($widgets as $widget) {
                if ($widget['is_system']) {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 1]);
                } else {
                    array_push($dashboardWidgets, ['name' => $widget['name'], 'is_system' => 0]);
                }
            }
        }

        cmf_set_option('admin_dashboard_widgets', $dashboardWidgets, true);

        $this->success('更新成功!');

    }

}
