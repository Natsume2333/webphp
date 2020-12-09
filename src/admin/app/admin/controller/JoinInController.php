<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/19
 * Time: 10:34
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;

class JoinInController extends AdminBaseController
{

    public function index(){

        $list = db('join_in')
            ->alias('j')
            ->join("user u","u.id=j.user_id")
            ->field('u.user_nickname,u.mobile,j.*')
            ->order("j.create_time DESC")
            ->paginate(20);
        //echo db() -> getLastSql();exit;

        $data = $list->toArray();

        $page = $list->render();
        //dump($data);exit;

        $this->assign('data',$data['data']);
        $this->assign('page', $page);
        return $this -> fetch();
    }
    //合作类型
    public function cooperation(){

        $list = db('join_in_type')->order("sort desc")->select();

        $this->assign('data',$list);
        return $this -> fetch();
    }
    //添加合作类型
    public function add_cooperation(){
          $id = input('param.id');
        if($id){
            $list = db('join_in_type')->where("id=".$id)->find();
        }else{
            $list['status']=1;
        }
        $this->assign('list',$list);
        return $this -> fetch();

    }
    //修改合作类型
    public function upd_cooperation(){
        $param = $this->request->param();

        $id = $param['id'];
        $data = $param['post'];
        $data['addtime'] = time();
        $data['sort']=$data['sort'] ? intval($data['sort']) : 0;
        if(empty($data['name'])){
            $this->error("请输入合作类型名称");
        }

        if ($id) {
            $result = db("join_in_type")->where("id=$id")->update($data);
        } else {
            $result = db("join_in_type")->insert($data);
        }
        if ($result) {
            $this->success("保存成功");
        } else {
            $this->error("保存失败");
        }

    }
    //删除合作类型
    public function del_cooperation(){
        $id = input('param.id');
       
        $list = db('join_in_type')->where("id=".$id)->delete();
        if ($list) {
            $this->success("删除成功");
        } else {
            $this->error("删除失败");
        }
    }
}