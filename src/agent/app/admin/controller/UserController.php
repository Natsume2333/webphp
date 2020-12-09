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

class UserController extends AdminBaseController
{

    /**
     *   代理个人中心修改资料
     */
    public function index()
    {
        $adminId = cmf_get_current_admin_id();

        $users = Db::name('agent')->where("id=$adminId")->find();

        $name  =   Db::name('agent_information')->where("agent_id=".$users['id'])->find();

        $name['agent_login']=$users['agent_login'];

        $name['agent_pass']=$users['agent_pass'];

        $name['superior_id']=$users['superior_id'];

        $name['status']=$users['status'];

        $name['addtime']=$users['addtime'];

        $name['id']=$users['id'];

        $this->assign("users", $name);

        return $this->fetch();
    }


    /**
     * 代理个人信息修改提交
     */
    public function userInfoPost()
    {
        if ($this->request->isPost()) {

            $data   = $this->request->post();

            $id = cmf_get_current_admin_id();

            $user=Db::name('agent')->where("id=$id")->find();

            $pass=$data['agent_pass'];

            if($pass != $user['agent_pass']){
                //修改密码
                $name['agent_pass']=cmf_password($pass);

                $name['id']=$id;

                Db::name('agent')->update($name);

            }

            if(strlen($data['mobile'])!=11 and !empty($data['mobile'])){

                $this->error("保存失败,手机号格式不正确！");

            }

            if(empty($data['email'])){

                $this->error("保存失败,邮箱格式不正确！");

            }

            $type=array();

            $data['email'] ? $type['email']=$data['email'] : '';

            $data['mobile'] ? $type['mobile']=$data['mobile'] : '';

            $data['qq'] ? $type['qq']=$data['qq'] : '';

            $data['wx'] ? $type['wx']=$data['wx'] : '';

            $data['identity_card'] ? $type['identity_card']=$data['identity_card'] : '';

            $data['name'] ? $type['name']=$data['name'] : '';

            $data['pay'] ? $type['pay']=$data['pay'] : '';

            $data['pay_name'] ? $type['pay_name']=$data['pay_name'] : '';
            $agent_information= Db::name('agent_information')->where("agent_id=$id")->find();
            if($agent_information){
                $create_result    = Db::name('agent_information')->where("agent_id=$id")->update($type);
            }else{
                $type['agent_id']=$id;
                $create_result    = Db::name('agent_information')->insert($type);
            }


            if ($create_result !== false) {

                $this->success("保存成功！");
            } else {

                $this->error("保存失败！");
            }
        }
    }

    //代理推广链接
    public function link(){
        /**搜索条件**/

        $agentid = $this->request->param('agent_id');

        $channel = $this->request->param('channel');

        $adminId = cmf_get_current_admin_id();

        $user=Db::name('agent')->where("id=$adminId")->find();   //获取本用户信息级别

        if ($agentid) {

            $where="(agent_id1=$agentid or agent_id2=$agentid or agent_id3=$agentid)";

        }else{

            $where="(agent_id1=$adminId or agent_id2=$adminId or agent_id3=$adminId)";

        }
        if($channel){

            $where.=" and channel=".$channel;

        }

        $name=Db::name('agent_link')->where($where)->select();

        $name=$name->toArray();

        foreach ($name as &$v){

            if($user['agent_level'] =='1'){    //一级代理

                $v['vid']=$v['agent_id2'];

                $v['earnings'] =$v['divide_into1'];

                $v['next_earnings']=$v['divide_into2'];

            }elseif($user['agent_level'] =='2'){//2级代理

                $v['vid']=$v['agent_id3'];

                $v['earnings'] =$v['divide_into2'];

                $v['next_earnings']=$v['divide_into3'];

            }else{                               //3级代理

                $v['vid']='0';

                $v['earnings'] =$v['divide_into3'];

                $v['next_earnings']='0';

            }

            if($v['vid'] !='0'){

                $user_name=Db::name('agent')->where("id=".$v['vid'])->find();

                if($user_name){

                    $v['agent_name']=$user_name['agent_login'];

                    $v['agent_name_id']=$user_name['id'];

                }else{

                    $v['vid']='0';

                }

            }

        }

        $this->assign("users", $name);

        $this->assign("level", $user['agent_level']);

        return $this->fetch();
    }
    //编辑分成
    public function linkedit(){

        $id = $this->request->param('id');

        $adminId = cmf_get_current_admin_id();

        $user=Db::name('agent')->where("id=$adminId")->find();   //获取本用户信息级别

        $name=Db::name('agent_link')->where("id=$id")->find();

        if($user['agent_level'] =='1'){    //一级代理

            $name['vid']=$name['agent_id2'];

            $name['earnings'] =$name['divide_into1'];

            $name['next_earnings']=$name['divide_into2'];

        }elseif($user['agent_level'] =='2'){//2级代理

            $name['vid']=$name['agent_id3'];

            $name['earnings'] =$name['divide_into2'];

            $name['next_earnings']=$name['divide_into3'];

        }else{                               //3级代理

            $name['vid']='0';

            $name['earnings'] =$name['divide_into3'];

            $name['next_earnings']='0';

        }

        if($name['vid'] !='0'){

            $user_name=Db::name('agent')->where("id=".$name['vid'])->find();

            if($user_name){

                $name['agent_name_id']=$user_name['id'];

            }else{

                $name['vid']='0';
            }
        }
        $this->assign("users", $name);

        return $this->fetch();
    }
    //修改分成
    public function link_upd(){

        if ($this->request->isPost()) {

            $data   = $this->request->post();

            $id       = cmf_get_current_admin_id();

            $user=Db::name('agent')->where("id=$id")->find();

            $where['id']=$data['id'];

            $where['channel']=$data['channel'];

            if($user['agent_level'] =='1'){    //一级代理

                $type['agent_id2']=$data['agent_name_id'];

                $type['divide_into2']=$data['next_earnings'];

            }elseif($user['agent_level'] =='2'){//2级代理

                $type['agent_id3']=$data['agent_name_id'];

                $type['divide_into3']=$data['next_earnings'];

            }else{                               //3级代理

                $type['agent_id3']=0;

                $type['divide_into3']=0;
            }

            $username=Db::name('agent_link')->where($where)->find();

            if($username){

                $usertype=Db::name('agent_link')->where($where)->update($type);

                if($usertype){

                    $this->success("保存成功！");

                } else {

                    $this->error("保存失败！");
                }
            }else{

                $this->error("参数错误！");
            }

        }
    }
    //代理列表
    public function agentlist(){

        $id       = cmf_get_current_admin_id();

        $user=Db::name('agent')->where("superior_id=$id")->order("id DESC")->paginate(10);


        // 获取分页显示
        $page = $user->render();
        $user=$user->toArray();

        foreach ($user['data'] as &$v){
            $vid=$v['id'];
            $list=Db::name('agent_link')->where("agent_id1=$vid or agent_id2=$vid or agent_id3=$vid")->select();
            $lists=$list->toArray();
            $str='';
            $v['list']='';
            if($lists){
                foreach ($lists as $vv){
                    $str.=$vv['channel'].",";
                }
                $str=rtrim($str, ',');
                $v['list']=$str;
            }

        }

        $this->assign("users", $user['data']);
        $this->assign("page", $page);

        return $this->fetch();
    }
    /*
     * 公告列表
     * */
    public function announcement(){

        $user=Db::name('portal_post')->alias("p")
                                ->where("c.category_id=13")
                                ->field("p.post_title,p.id,p.published_time")
                                ->join("portal_category_post c","c.post_id=p.id")
                                ->order("p.published_time DESC")
                                ->paginate(10);

        $page=$user->render();
        $this->assign("users", $user);
        $this->assign("page", $page);
        return $this->fetch();
    }
    /*
    * 公告列表
    * */
    public function details(){
        $id = $this->request->param('id');
        $user=Db::name('portal_post')->where("id=$id")->field("post_content") ->find();
        $user['post_content']=htmlspecialchars_decode($user['post_content']);
        $this->assign("users", $user);
        return $this->fetch();
    }
    /*
     *  渠道用户信息
     * */
    public function information(){
        $id = $this->request->param('id');
        $user_name=Db::name('agent')->field("agent_login")->where("id=$id")->find();
        if($user_name){
            $user=Db::name('agent_information')->where("agent_id=$id")->find();
            $result=$user;

        }
        $result['agent_login']=$user_name['agent_login'];
      //  var_dump($id);exit;
        $this->assign("users", $result);
        return $this->fetch();
    }
}