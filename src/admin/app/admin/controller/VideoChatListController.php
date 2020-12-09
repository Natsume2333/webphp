<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/6/18
 * Time: 22:45
 */

namespace app\admin\controller;


use cmf\controller\AdminBaseController;

class VideoChatListController extends AdminBaseController
{

    public function index(){

        $list = db('video_live_list')
            ->order("last_heart_time DESC")
            ->paginate(20);

        $data = $list->toArray();
        $page = $list->render();

        foreach ($data['data'] as &$v){
            $base_field = 'id,avatar,user_nickname,sex,level,coin';
            $user_info = db('user') -> field($base_field) -> find($v['user_id']);
            $v['user_info'] = $user_info;

        }

        $this->assign('list',$data['data']);
        $this->assign('page', $page);
        return $this -> fetch();
    }

    //查看视频
    public function select_video(){

        $id = input('param.id');
        $video = db('video_live_list')
            ->find($id);


        $this->assign('video',$video);

        return $this -> fetch();
    }
}