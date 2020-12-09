<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/12/13
 * Time: 00:14
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;

class AutoTalkingController extends AdminBaseController
{

    public function custom_msg_list()
    {

        $talking = db('custom_auto_msg c');

        $list = $talking
            ->join('user u', 'c.user_id=u.id')
            ->field('u.user_nickname,c.*')->order("create_time DESC")->paginate(20, false, ['query' => request()->param()]);
        $lists = $list->toArray();
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $lists['data']);
        $this->assign('page', $page);
        // 渲染模板输出
        return $this->fetch();

    }

    //删除话术
    public function del_custom_msg()
    {
        $id = input('param.id');
        db('custom_auto_msg')->delete($id);
        echo 1;
        exit;
    }

    //审核话术
    public function adopt_custom_msg()
    {
        $id = input('param.id');
        db('custom_auto_msg')->where('id', $id)->setField('status', 1);
        echo 1;
        exit;
    }

    public function index()
    {
        $talking = db('auto_talking_skill');

        $list = $talking->order("create_time DESC")->paginate(20, false, ['query' => request()->param()]);
        $lists = $list->toArray();
        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $lists['data']);
        $this->assign('page', $page);

        if (defined('OPEN_AUTO_SEE_HI_PLUGS') && OPEN_AUTO_SEE_HI_PLUGS == 1) {
            $open_custom_plugs = 1;
        }else{
            $open_custom_plugs = 0;
        }

        $this->assign('open_custom_plugs', $open_custom_plugs);
        // 渲染模板输出
        return $this->fetch();
    }

    public function add_talking()
    {

        return $this->fetch();
    }

    //添加话术
    public function add_talking_post()
    {

        $param = $this->request->param();
        $param['create_time'] = time();

        $result = db("auto_talking_skill")->insert($param);
        if ($result) {
            $this->success("保存成功");
        } else {
            $this->error("保存失败");
        }
    }

    //删除话术
    public function del()
    {
        $id = input('param.id');
        db('auto_talking_skill')->delete($id);
        echo 1;
        exit;
    }

}