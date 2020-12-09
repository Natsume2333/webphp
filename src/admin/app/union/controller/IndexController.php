<?php
namespace app\union\controller;

use cmf\controller\UnionBaseController;
use think\Db;

class IndexController extends UnionBaseController
{

    /**
     * 工会用户首页(公开)
     */
    public function index(){
        $user = session('union');
        if (empty($user)) {
            session('union',null);
            $this->error("查无此人！", url('login/index'));
        }
        $this->assign($user);
      
        return $this->fetch(":index");

    }

    /**
     * 退出登录
    */
    public function logout()
    {
        session("union", null);//只有前台用户退出   
        echo exit('<script>top.location.href="'.url('login/index').'"</script>');
    }

}
