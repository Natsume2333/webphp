<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/8/17
 * Time: 01:31
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;
use think\Db;

class VipController extends AdminBaseController
{
    public function index(){
        $list = db('vip_rule') -> select();

        $this->assign('list',$list);
        return $this->fetch();
    }

    public function add(){
        $id = input('param.id');
        if ($id) {

            $name = Db::name("vip_rule")->where("id=$id")->find();
          //  var_dump($name);die;
          /*  $url = SITE_URL;
            $name['img'] =  $url.'/admin/public/upload/'.$name['img'];*/
        }else{
            $name['icon']='';
        }
        $this->assign('data', $name);
        return $this->fetch();

    }

    public function add_post(){

        $param = $this->request->param();
        $id = $param['id'];
        $data = $param;
        $data['create_time'] = time();
        if($id){
            $result = Db::name("vip_rule")->where("id=$id")->update($data);
        }else{
            $result = Db::name("vip_rule")->insert($data);
        }
        if($result){
            $this->success("保存成功",url('vip/index'));
        }else{
            $this->error("保存失败");
        }
    }

    public function del(){
        $id = input('param.id');
        $result = Db::name('vip_rule') -> delect($id);
        return $result ? '1' : '0';
        exit;
    }

    public function rule(){
        $list = db('vip_rule_details')->order("sort desc") -> select();
        $url = SITE_URL;
        $lists = [];
        foreach ($list as $k=>$v) {
            $v['img'] = $url.'/admin/public/upload/'.$v['img'];
            $lists[] = $v;
        }
        $this->assign('list',$lists);
        return $this->fetch();
    }
    public function addrule(){
        $id = input('param.id');
        if ($id) {
            $name = Db::name("vip_rule_details")->where("id=$id")->find();
            $url = SITE_URL;
            $name['img'] =  $url.'/admin/public/upload/'.$name['img'];
        }else{
            $name['img']='';
        }
        $this->assign('data', $name);
        return $this->fetch();

    }

    public function add_post_rule(){

        $param = $this->request->param();
        $id = $param['id'];
        $data = $param;
        if($data['img'] ==''){
            $this->error("请上传图标");
        }
        if($data['center'] ==''){
            $this->error("请输入vip规则详情内容");
        }
        $data['addtime'] = time();
        if($id){
            $result = Db::name("vip_rule_details")->where("id=$id")->update($data);
        }else{
            $result = Db::name("vip_rule_details")->insert($data);
        }
        if($result){
            $this->success("保存成功",url('vip/index'));
        }else{
            $this->error("保存失败");
        }
    }

    public function delrule(){
        $id = input('param.id');
        $result = Db::name('vip_rule_details') -> delete($id);

        return $result ? '1' : '0';
        exit;
    }

}