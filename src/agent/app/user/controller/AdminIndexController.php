<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: Powerless < wzxaini9@gmail.com>
// +----------------------------------------------------------------------

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class AdminIndexController extends AdminBaseController
{

    //本站用户列表
    public function index()
    {
        $where   = [];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['id'] = intval($request['uid']);
        }
        if (isset($request['reference']) && $request['reference'] != '-1') {
            $where['reference'] = intval($request['reference']);
        }else{
            $request['reference'] = '-1';
        }

        if (isset($request['user_status']) && intval($request['user_status']) != -1) {
            $where['user_status'] = intval($request['user_status']);
        }else{
            $request['user_status'] = '-1';
        }

        if (isset($request['sex']) && intval($request['sex']) != -1) {
            $where['sex'] = intval($request['sex']);
        }else{
            $request['sex'] = '-1';
        }

        if (isset($request['order']) && intval($request['order']) != -1) {
            $order = 'income_total';
        }else{
            $order = 'create_time';
            $request['order'] = '-1';
        }

        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];

            $keywordComplex['user_login|user_nickname|user_email|mobile']    = ['like', "%$keyword%"];
        }
        $usersQuery = Db::name('user');

        $list = $usersQuery->whereOr($keywordComplex)->where($where)->order("$order DESC")->paginate(20);
        $lists=$list->toArray();
       // print_r($lists);exit;
        foreach($lists['data'] as &$v){
            $uid=$v['id'];
            $find=Db::name("user_reference")->where("uid=$uid")->find();
            if($find){
                $v['reference'] ='1';
            }else{
                $v['reference'] ='0';
            }
        }

        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $lists['data']);
        $this->assign('request', $request);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();
    }

    /**
     * 本站用户拉黑
     * @adminMenu(
     *     'name'   => '本站用户拉黑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户拉黑',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $result = Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 0);
            if ($result) {
                $user['id']=$id;
                $message = Db::name("user_message")->where("id=2")->find();

                $this->success("操作成功");

            } else {
                $this->error('会员拉黑失败,会员不存在,或者是管理员！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 本站用户启用
     * @adminMenu(
     *     'name'   => '本站用户启用',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '本站用户启用',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            Db::name("user")->where(["id" => $id, "user_type" => 2])->setField('user_status', 1);
            $user['id']=$id;
            $this->success('操作成功！');

        } else {
            $this->error('数据传入失败！');
        }
    }
    //推荐用户
    public function reference(){
        $id = input('param.id', 0, 'intval');
        $type = input('param.type', 0, 'intval');
        if ($id) {
            $user['id']=$id;
            if($type =='1'){
                $data=array(
                    'uid'  =>$id,
                    'addtime' =>time()
                );
                Db::name("user_reference")->insert($data);
                db('user') -> where('id','=',$id) -> setField('reference',1);
                //$stat="已成为推荐用户";
            }else{
                Db::name("user_reference")->where("uid=$id")->delete();
                db('user') -> where('id','=',$id) -> setField('reference',0);
                //$stat="已取消推荐";
            }
            $this->success('操作成功！');
        } else {
            $this->error('数据传入失败！');
        }
    }

    //账户管理
    public function account(){

        $id = input('param.id', 0, 'intval');
        $coin = intval(input('param.coin'));


        if($coin > 0){
            db('user') -> where('id','=',$id) -> setInc('coin',$coin);
        }else{

            db('user') -> where('id','=',$id) -> setDec('coin',abs($coin));
        }

        echo json_encode(['code' => 1]);
        exit;
    }

    //编辑用户信息
    public function edit(){

        $id = input('param.id', 0, 'intval');
        $user_info = db('user') -> find($id);
        if(!$user_info){
            $this->error('用户不存在');
            exit;
        }

        $this->assign('data',$user_info);
        // 渲染模板输出
        return $this->fetch();
    }

    //编辑信息保存
    public function edit_post(){

        $id = input('param.id', 0, 'intval');
        $user_nickname = input('param.user_nickname');
        $avatar = input('param.avatar');

        if(empty($user_nickname)){
            $this->error('昵称不能为空');
            exit;
        }

        $data = ['user_nickname' => $user_nickname,'avatar' => $avatar];
        db('user') -> where('id','=',$id) -> update($data);
        $this->success('操作成功');
    }
}
