<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/7/20 0020
 * Time: 上午 10:38
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class SmsController extends AdminBaseController
{
    //短信发送管理
    public function index()
    {
        /**搜索条件**/
        $p = $this->request->param('page');
       
        /**搜索条件**/
        $account = $this->request->param('account');

        $where='';
        if ($account) {
            $where.="account like '%".$account."%'";
        }

        $users = Db::name('verification_code')
            ->order("id DESC")->where($where)
            ->paginate(15, false, ['query' => request()->param()]);

        // 获取分页显示
        $page = $users->render();
        $name = $users->toArray();
      
        $this->assign("page", $page);
        $this->assign("users", $name['data']);
        return $this->fetch();
    }

}