<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/12/29 0029
 * Time: 上午 9:24
 */
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ValidationController extends AdminBaseController {
        //短信验证码
    public function code(){
        //validation
       // $target = "http://106.ihuyi.cn/webservice/sms.php?method=Submit";
        $target = "http://106.ihuyi.com/webservice/sms.php?method=GetNum";
        $config = load_cache('config');
        $post_data = "account=" . $config['system_sms_key'] . "&password=" . $config['system_sms_id'];
        //密码可以使用明文密码或使用32位MD5加密
        $SMS=0;
        $gets = xml_to_array(post($post_data, $target));
        if ($gets['GetNumResult']['code'] == 2) {
            $SMS=$gets['GetNumResult']['num'];
        }

        $p = $this->request->param('page');
        if (empty($p) and !$this->request->param('mobile')) {
            session("Validation",null);
        } else if (empty($p)) {
            $data['mobile'] = $this->request->param('mobile');
            session("Validation",$data);
        }
        $where='';
        $where.=session("Validation.mobile") ?  " account like '%".session("Validation.mobile")."%' ":'';

        $users = Db::name('verification_code')->where($where) ->order("send_time DESC")
            ->paginate(10, false, ['query' => request()->param()]);

        $page = $users->render();
        $name = $users->toArray();

        $this->assign("page", $page);
        $this->assign("users", $name['data']);
        $this->assign("sms", $SMS);
        $this->assign("data", session("Validation"));
        return $this->fetch();
    }

}