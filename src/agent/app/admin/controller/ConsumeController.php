<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/2/8 0008
 * Time: 上午 10:55
 */

namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use think\Db;

class ConsumeController extends AdminBaseController
{

    //消费记录
    public function index()
    {
        if (request()->post()) {
            session('Consumme', request()->post());
        }
        if (!request()->get('page') and !request()->post()) {
            session('Consumme', null);
            session('Consumme.type', 4);
        }

        $where = session('Consumme.type') ? "a.type=" . session('Consumme.type') : "";
        $where .= session('Consumme.uid') ? " and a.user_id=" . session('Consumme.uid') : '';
        $where .= session('Consumme.touid') ? " and a.to_user_id=" . session('Consumme.touid') : '';

        if (session('Consumme.type') == 4) {
            $user = Db::name("user_consume_log")
                ->alias("a")
                ->join("user b", "b.id=a.user_id")
                ->join("user c", "c.id=a.to_user_id")
                ->join("video_call_record_log v", "v.id=a.table_id")
                ->field("a.id,a.user_id,a.type,a.table_id,a.create_time,a.to_user_id,a.content,b.user_nickname as uname,c.user_nickname as toname,sum(a.coin) as coin,sum(a.profit) as profit")
                ->where($where)
                ->group("v.channel_id")
                ->order('create_time desc')
                ->paginate(20);
        } else {
            $user = Db::name("user_consume_log")
                ->alias("a")
                ->join("user b", "b.id=a.user_id")
                ->join("user c", "c.id=a.to_user_id")
                ->field("a.*,b.user_nickname as uname,c.user_nickname as toname")
                ->where($where)
                ->order('create_time desc')
                ->paginate(20);
        }

        $lists = $user->toArray();

        $type = session('Consumme.type') ? session('Consumme.type') : "type=0";

        $total = Db::name("user_consume_log")->sum('coin');

        $this->assign('total', $total);
        $this->assign('data', $lists['data']);
        $this->assign('type', $type);
        $this->assign('request', session('Consumme'));
        $this->assign('page', $user->render());
        return $this->fetch();
    }

}