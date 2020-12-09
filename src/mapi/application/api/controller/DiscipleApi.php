<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/31 0031
 * Time: 下午 17:40
 */

namespace app\api\controller;


use think\Db;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------
class DiscipleApi extends Base
{
    /**
     * h5 页面收徒榜近七日
     */
    public function index()
    {
        $uid = input("param.uid");
        //  $uid="100097";
        $time = time();
        $p = "0";
        $week = strtotime("-1 week");   //获取一周前的时间
        $cal = $this->teacher_pm($uid, $time, $week);
        $tud = $this->teacher_ph($uid, $time, $week, $p);
        if (empty($cal)) {
            $cal = 0;
        }
        $name = array(
            'p' => $p,
            'uid' => $uid
        );
        $this->assign("tudCount", count($tud));
        $this->assign("cal", $cal);
        $this->assign("tud", $tud);
        $this->assign("name", $name);
        return $this->fetch();
    }

    //师徒排行榜
    public function teacher_ph($uid, $time, $week, $p)
    {
        $cal = Db::name("user_call_log")->alias("a")->field("sum(a.coin+a.zgiftcoin) as sum,b.user_nickname,b.avatar,a.touid")
            ->join("user b", "b.id=a.touid")
            ->where("a.teacherid=$uid and a.endtime>'$week' and a.endtime<'$time'")
            ->group("a.touid")
            ->order("sum desc")
            ->limit($p, 10)
            ->select();
        return $cal;

    }

    //获取是否的总贡献值
    public function teacher_pm($uid, $time, $week)
    {

        $cal = Db::name("user_call_log")->field("sum(coin+zgiftcoin) as sum")
            ->where("touid=$uid and endtime>'$week' and endtime<'$time'")
            ->find();

        return $cal['sum'];

    }

    //分页
    public function pages()
    {
        $page = input("param.page");
        $p = ($page + 1) * 10;
        $uid = input("param.uid");
        $time = time();
        $week = strtotime("-1 week");   //获取一周前的时间
        $data['cal'] = $this->teacher_ph($uid, $time, $week, $p);
        $data['page'] = $p;
        echo json_encode($data);
        exit;

    }

}