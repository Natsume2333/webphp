<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/23 0023
 * Time: 上午 11:04
 */

namespace app\user\controller;

use cmf\controller\AdminBaseController;
use think\Db;
use think\helper\Time;

class ReferenceController extends AdminBaseController
{

    //推荐用户
    public function reference()
    {
        $user = Db::name("user_reference")->order('orderno desc')->paginate(10);
        $lists = $user->toArray();
        foreach ($lists['data'] as &$v) {
            $uid = $v['uid'];
            $users = Db::name("user")->where("id=$uid")->find();
            $v['user_nickname'] = $users['user_nickname'];
            $v['user_status'] = $users['user_status'];
            $v['mobile'] = $users['mobile'];
        }
        $this->assign('user', $lists['data']);

        $this->assign('page', $user->render());
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
                $user['id'] = $id;
                $this->success('操作成功！');
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
            $user['id'] = $id;
            $message = Db::name("user_message")->where("id=2")->find();
            $this->success('操作成功！');

        } else {
            $this->error('数据传入失败！');
        }
    }

    //排序
    public function upd()
    {

        $param = request()->param();

        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("user_reference")->where("id=$k")->update(array('orderno' => $v));
        }

        if ($status) {
            $this->success("排序成功");
        } else {
            $this->success("排序失败");
        }
    }

    //查询用户活跃数
    public function analysis()
    {

        $limit = 10;
        $p = input('param.page') > 1 ? input('param.page') * $limit - ($limit - 1) : '1';  //获取序号的顺序
        $user = Db::name("app_analyze")->field("count(id) as sum,day")->order('addtime desc')->group("day")->paginate($limit);

        $lists = $user->toArray();

        foreach ($lists['data'] as &$v) {
            $day = $v['day'];
            $count = Db::name("app_analyze")->field("count_user,registered")->where("day='$day'")->order('count_user desc')->find();
            $v['count_user'] = $count['count_user'];     //用户总数
            $v['registered'] = $count['registered'];     //当日注册数
        }

        $this->assign('user', $lists['data']);

        $this->assign('page', $user->render());
        $this->assign('p', $p);
        return $this->fetch();
    }

    //查询主播在线时长统计
    public function emcee_online_count()
    {
        $start_time = strtotime(input('param.start_time'));
        $end_time = strtotime(input('param.end_time'));
        $uid = input('param.uid');
       // var_dump($uid);die;
        $limit = 20;
        if ($uid != 0) {
            $user_where = "is_auth=1 and id=" . $uid;
        } else {
            $user_where = "is_auth=1";
        }
        $list = db("user")->field('id,user_nickname,avatar')->where($user_where)->paginate($limit);

        $day_time = Time::today();
        $data_list = $list->toArray()['data'];
       // var_dump($day_time);die;
        foreach ($data_list as $k=>$v) {
            if ($start_time != 0 && $end_time != 0) {
                //echo $start_time;
                $where = 'user_id=' . $v['id'] . ' and up_online_time>' . $start_time . ' and offline_time< ' . $end_time;
            } else {
                $where = 'user_id=' . $v['id'] . ' and up_online_time>' . $day_time[0];
            }
            $online_time = db('online_record')->where($where)->sum('time');
            //var_dump($online_time);
            $data_list[$k]['online_time'] = secs_to_str($online_time);
        }

        $this->assign('list', $data_list);
        $this->assign('page', $list->render());
        return $this->fetch();
    }
}
