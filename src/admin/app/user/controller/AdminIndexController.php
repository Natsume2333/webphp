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
        $where = [];
        $request = input('request.');
        if (!input('request.page')) {
            session('admin_index', null);
        }
        if (input('request.uid') || input('request.reference') || input('request.is_auth') || input('request.user_status') || input('request.sex') || input('request.order') || input('request.keyword') || input('request.start_time') || input('request.end_time') || input('request.start_time2') || input('request.end_time2') || input('request.is_online')) {
            session('admin_index', input('request.'));
        }

        if (session('admin_index.uid')) {
            $where['id'] = session('admin_index.uid');
        }
        if (session('admin_index.reference') && session('admin_index.reference') != '-1') {
            $where['reference'] = intval(session('admin_index.reference'));
        }

        if (session('admin_index.is_auth') >= '0') {
            $where['is_auth'] = intval(session('admin_index.is_auth'));
        }

        if (session('admin_index.user_status') >= '0') {
            $where['user_status'] = intval(session('admin_index.user_status')) == '0' ? '0' : ['<>', 0];
        }

        if (session('admin_index.sex') >= '0') {
            $where['sex'] = intval(session('admin_index.sex'));
        }
        if (session('admin_index.end_time') && session('admin_index.start_time')) {
            $where['create_time'] = ['between', [strtotime(session('admin_index.start_time')), strtotime(session('admin_index.end_time'))]];
        }
        if (session('admin_index.end_time2') && session('admin_index.start_time2')) {
            $where['last_login_time'] = ['between', [strtotime(session('admin_index.start_time2')), strtotime(session('admin_index.end_time2'))]];
        }
        if (session('admin_index.is_online') >= '0') {
            $where['is_online'] = intval(session('admin_index.is_online'));
        }
        if (session('admin_index.order') && intval(session('admin_index.order')) != -1) {
            if (session('admin_index.order') == 1) {
                $order = 'income_total';
            } elseif (session('admin_index.order') == 2) {
                $order = 'coin';
            } else {
                $order = 'level';
            }

        } else {
            $order = 'create_time';
        }

        $keywordComplex = [];
        if (session('admin_index.keyword')) {
            $keyword = session('admin_index.keyword');

            $keywordComplex['user_login|user_nickname|user_email|mobile'] = ['like', "%$keyword%"];
        }
        $usersQuery = Db::name('user');

        $list = $usersQuery->whereOr($keywordComplex)->where($where)->order("$order DESC")->paginate(20, false, ['query' => request()->param()]);
        $lists = $list->toArray();
        // var_dump($lists);
        foreach ($lists['data'] as &$v) {
            if ($v['vip_end_time'] <= time()) {
                $v['vip_end_time'] = '无';
            } else {
                $v['vip_end_time'] = date('Y-m-d H:i', $v['vip_end_time']);
            }
            $uid = $v['id'];
            $find = Db::name("user_reference")->where("uid=$uid")->find();
            if ($find) {
                $v['reference'] = '1';
            } else {
                $v['reference'] = '0';
            }
            $user = Db::name("invite_record")->alias("a")->join("user u", "u.id=a.user_id")
                ->field('u.user_nickname,a.user_id')->where("a.invite_user_id=$uid")->find();
            $attention = Db::name("user_attention")->where("uid=$uid")->count();
            $fans = Db::name("user_attention")->where("attention_uid=$uid")->count();

            //    $invite_coin = Db::name("invite_profit_record")->where("user_id=$uid")->sum("money");
            $cash_record = Db::name("invite_cash_record")->where('uid=' . $uid . ' and status !=2')->sum("coin");
            $v['invite_withdrawal'] = $cash_record;
            $where_money['r.status'] = 1;
            $where_money['r.user_id'] = $uid;
            /* $v['income'] = db('user_cash_record')
                 ->alias('r')
                 ->join("user u", "u.id=r.user_id")
                 ->join("user_cash_account c", "c.id=r.paysid")
                 ->where($where_money)->sum("r.income");*/
            $v['money'] = db('user_cash_record')
                ->alias('r')
                ->join("user u", "u.id=r.user_id")
                ->join("user_cash_account c", "c.id=r.paysid")
                ->where($where_money)->sum("r.money");


            $v['invite_user_name'] = $user['user_nickname'] ? $user['user_nickname'] : '';
            $v['invite_user_id'] = $user['user_id'] ? $user['user_id'] : '';
            $v['attention'] = $attention ? $attention : '0';
            $v['fans'] = $fans ? $fans : '0';
            //     $v['invite_coin'] = $invite_coin ? $invite_coin : '0';

            //付费概率
            //总单
            $total_order = db('user_charge_log')->where('uid=' . $v['id'])->count();
            //成功单
            $success_order = db('user_charge_log')->where('uid=' . $v['id'] . ' and status=1')->count();
            $v['recharge_probability'] = 0;
            if ($success_order != 0) {
                $v['recharge_probability'] = ($success_order / $total_order * 100);
            }

            $v['device_info'] = db("device_info")->where("uid={$v['id']}")->find();

            //是否禁封设备号
              $device = db('equipment_closures')->where('device_uuid', $v['device_uuid'])->find();
              $v['is_device']= $device ? 2:1;
        }

        // 获取分页显示
        $page = $list->render();
        $this->assign('list', $lists['data']);
        $this->assign('request', session('admin_index'));
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
                $user['id'] = $id;
                $message = Db::name("user_message")->where("id=2")->find();

                require_once DOCUMENT_ROOT . '/system/im_common.php';
                im_shut_up($id, 4294967295);
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
            $user['id'] = $id;
            require_once DOCUMENT_ROOT . '/system/im_common.php';
            im_shut_up($id, time());

            $this->success('操作成功！');
        } else {
            $this->error('数据传入失败！');
        }
    }
    /*
    *拉黑和开启用户
    */
    public function ban_type(){
        $id = input('param.id', 0, 'intval');
        $day = input('param.day', 0, 'intval');
        $hours = input('param.hours', 0, 'intval');
        $minutes = input('param.minutes', 0, 'intval');
        $seconds = input('param.seconds', 0, 'intval');
        if ($id) {
             $user['id'] = $id;
            require_once DOCUMENT_ROOT . '/system/im_common.php';
            $time=0;
            $time+= $day ==0 ? 0 : 60*60*24*$day;
            $time+= $hours ==0 ? 0 : 60*60*$hours;
            $time+= $minutes ==0 ? 0 : 60*$minutes;
            $time+= $seconds ==0 ? 0 : $seconds;

            $type= $time ==0 ? 1:0;
            $end_time=$time ==0 ? 0 : NOW_TIME + $time;
            $data['shielding_time']  =$end_time;
            $data['user_status']  =$type;
            
            db('user')->where('id ='. $id." and user_type=2")->update($data);
           
            im_shut_up($id, $time);

            echo json_encode(['code' => 1, 'msg' => '操作成功']);
        } else {
              echo json_encode(['code' =>0, 'msg' => '操作失败']);
        }
    }
    //推荐用户
    public function reference()
    {
        $id = input('param.id', 0, 'intval');
        $type = input('param.type', 0, 'intval');
        if ($id) {
            $user['id'] = $id;
            if ($type == '1') {
                $data = array(
                    'uid' => $id,
                    'addtime' => time(),
                );
                Db::name("user_reference")->insert($data);
                db('user')->where('id', '=', $id)->setField('reference', 1);
                //$stat="已成为推荐用户";
            } else {
                Db::name("user_reference")->where("uid=$id")->delete();
                db('user')->where('id', '=', $id)->setField('reference', 0);
                //$stat="已取消推荐";
            }
            $this->success('操作成功！');
        } else {
            $this->error('数据传入失败！');
        }
    }

    //账户管理
    public function account()
    {

        $id = input('param.id', 0, 'intval');
        $coin = intval(input('param.coin'));
        $user = db('user')->where('id', '=', $id)->find();
        $data = array(
            'uid' => $id,
            'coin' => abs($coin),
            'addtime' => time(),
        );
        if ($coin > 0) {
            if ($user['sex'] == '1') {
                db('user')->where('id', '=', $id)->setInc('coin', $coin);
                $data['user_type'] = 1;
            } else {
                db('user')->where('id', '=', $id)->setInc('income', $coin);
                db('user')->where('id', '=', $id)->setInc('income_total', $coin);
                $data['user_type'] = 2;
            }
            $data['type'] = 1;
        } else {
            if ($user['sex'] == '1') {
                db('user')->where('id', '=', $id)->setDec('coin', abs($coin));
                $data['user_type'] = 1;
            } else {
                db('user')->where('id', '=', $id)->setDec('income', abs($coin));
                db('user')->where('id', '=', $id)->setDec('income_total', abs($coin));
                $data['user_type'] = 2;
            }
            $data['type'] = 2;
        }
        $data['operator'] = cmf_get_current_admin_id();
        db('recharge_log')->insert($data);
        echo json_encode(['code' => 1]);
        exit;
    }

    //VIP时间设置
    public function vipSet()
    {
        $id = input('param.id', 0, 'intval');

        $vip_end_time = input('param.vip_end_time') ? strtotime(input('param.vip_end_time')) : 0;

        $user = db('user')->where('id', '=', $id)->find();
        $vip_log = array('user_id' => $id, 'admin_id' => $_SESSION['think']['ADMIN_ID'], 'vip_time' => $vip_end_time, 'ctime' => time());
        if ($vip_end_time == 0 && $user['vip_end_time'] == 0) {
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
            exit;
        }
        if (empty($vip_end_time) || $vip_end_time <= 0) {
            $data = array(
                'vip_end_time' => 0,
            );
        } else {
            $time = ($user['vip_end_time'] < time()) ? time() : $user['vip_end_time'];
            $data = array(
                'vip_end_time' => abs($vip_end_time),
            );
        }
        $res = db('user')->where('id', '=', $id)->update($data);

        if ($res) {
            echo json_encode(['code' => 1, 'msg' => '操作成功']);
            $vip_log['state'] = 1;
        } else {
            echo json_encode(['code' => 0, 'msg' => '操作失败']);
            $vip_log['state'] = 0;
        }
        db('user_vip_set_log')->insert($vip_log);
        exit;
    }

    public function userVipSetLog()
    {
        $pageWhere = ['query' => request()->param()];
        $where = [];

        $list = Db::name("user_vip_set_log")
            ->alias('c')
            ->join('user u', 'c.user_id=u.id', 'LEFT')
            ->join('user a', 'c.admin_id=a.id', 'LEFT')
            ->field('c.*,u.user_nickname as uname,a.user_nickname as aname')
            ->order('c.ctime desc')
            ->where($where)
            ->paginate(20, false, $pageWhere);

        // $list = Db::name("user_vip_set_log") ->select();
        $this->assign('list', $list);
        $this->assign('page', $list->render());
        return $this->fetch();


    }

    //排序
    public function upd()
    {

        $param = request()->param();
        foreach ($param['listorders'] as $k => $v) {

            Db::name("user")->where("id=$k")->update(array('sort' => $v));
        }

        $this->success("排序成功");

    }

    //编辑用户信息
    public function edit()
    {

        $id = input('param.id', 0, 'intval');
        $user_info = db('user')->find($id);
        if (!$user_info) {
            $this->error('用户不存在');
            exit;
        }

        $user = Db::name("invite_record")->where("invite_user_id=$id")->find();
        if ($user) {
            $user_info['invite_id'] = $user['user_id'];
        } else {
            $user_info['invite_id'] = '无';
            $user_info['custom_video_charging_coin'] = '0';
        }
        $fee = Db::name("host_fee")->order("sort asc")->select();
        $this->assign('fee', $fee);
        $this->assign('data', $user_info);

        // 渲染模板输出
        return $this->fetch();
    }

    //编辑信息保存
    public function edit_post()
    {

        $id = input('param.id', 0, 'intval');
        $user_nickname = input('param.user_nickname');
        $avatar = input('param.avatar');
   //     $sex = input('param.sex');
        $invite_id = input('param.invite_id');
        $custom_video_charging_coin = input('param.custom_video_charging_coin');

        $host_bay_video_proportion = input('param.host_bay_video_proportion');
        $host_bay_phone_proportion = input('param.host_bay_phone_proportion');
        $host_bay_gift_proportion = input('param.host_bay_gift_proportion');
        $host_one_video_proportion = input('param.host_one_video_proportion');
        $host_direct_messages = input('param.host_direct_messages');
        $host_guardian_proportion = input('param.host_guardian_proportion');
        $is_online = input('param.is_online');
        $is_auth = input('param.is_auth');
        $invite_buckle_probability = intval(input('param.invite_buckle_probability'));

        if (empty($user_nickname)) {
            $this->error('昵称不能为空');
            exit;
        }
        if ($invite_id) {
            $user = db('invite_code')->alias('i')->field("i.*")->join('user u', 'i.user_id = u.id')->where("u.id='$invite_id'")->find();
            if ($user) {
                $invite = Db::name("invite_record")->where("invite_user_id='$id'")->find();
                $data = array(
                    'user_id' => $invite_id,
                    'invite_user_id' => $id,
                    'invite_code' => $user['invite_code'],
                    'create_time' => time(),
                );
                if ($invite) {
                    db('invite_record')->where('invite_user_id', '=', $id)->update($data);
                } else {
                    db('invite_record')->insert($data);
                }
            }
        }

        $data = array(
            'user_nickname' => $user_nickname,
            'avatar' => $avatar,
        //    'sex' => $sex,
            'host_bay_video_proportion' => $host_bay_video_proportion,
            'host_bay_phone_proportion' => $host_bay_phone_proportion,
            'host_bay_gift_proportion' => $host_bay_gift_proportion,
            'host_one_video_proportion' => $host_one_video_proportion,
            'host_direct_messages' => $host_direct_messages,
            'host_guardian_proportion' => $host_guardian_proportion,
            'is_online' => $is_online,
            'invite_buckle_probability' => $invite_buckle_probability,
            'is_auth' => $is_auth,
        );
        if ($custom_video_charging_coin) {
            $data['custom_video_charging_coin'] = $custom_video_charging_coin;
        }
        db('user')->where('id', '=', $id)->update($data);
        $this->success('操作成功');
    }

    public function invitation()
    {
        $where = [];
        $uid = input('id');
        $sex = input('sex');

        if (empty($sex)) {
            $where['i.user_id'] = $uid;
        } else if ($sex == 0) {
            $where['i.user_id'] = $uid;
        } else if ($sex == 1) {
            $where['i.user_id'] = $uid;
            $where['u.sex'] = $sex;
        } else if ($sex == 2) {
            $where['i.user_id'] = $uid;
            $where['u.sex'] = $sex;
        }

        $invite = db('invite_record')
            ->alias('i')
            ->join('user u', 'i.invite_user_id = u.id')
            ->where($where)
            ->field('u.id,u.sex,u.user_nickname,i.user_id')
            ->select();
        $cc = [];
        foreach ($invite as $val) {
            $count = db('invite_profit_record')->where(['invite_user_id' => $val['id'], 'user_id' => $val['user_id']])->sum('money');
            $val['count'] = $count;
            $cc[] = $val;
        }

        $numpeo = db('invite_record')->where('user_id', $uid)->count();
        $mnum = db('invite_record')
            ->alias('i')
            ->join('user u', 'i.invite_user_id = u.id')
            ->where(["i.user_id" => $uid, 'sex' => 1])
            ->count();
        $wnum = db('invite_record')
            ->alias('i')
            ->join('user u', 'i.invite_user_id = u.id')
            ->where(["i.user_id" => $uid, 'sex' => 2])
            ->count();
        $nummoney = db('invite_profit_record')->where(['user_id' => $uid])->sum('money');

        $data = [
            'mnum' => $mnum,
            'wnum' => $wnum,
            'numpeo' => $numpeo,
            'nummoney' => $nummoney,
            'uid' => $uid,
        ];
        $this->assign($data);
        $this->assign('invite', $cc);
        // 渲染模板输出
        return $this->fetch();
    }

    /*导出*/
    public function export()
    {

        $where = [];
        $request = input('request.');

        if (!empty($request['uid'])) {
            $where['id'] = intval($request['uid']);
        }
        if (isset($request['reference']) && $request['reference'] != '-1') {
            $where['reference'] = intval($request['reference']);
        }
        if (isset($request['is_auth']) && $request['is_auth'] != '-1') {
            $where['is_auth'] = intval($request['is_auth']);
            if (intval($request['is_auth']) == '1') {
                $title = '主播列表';
            } else {
                $title = '用户列表';
            }
        } else {
            $title = '会员列表';
        }

        if (isset($request['user_status']) && intval($request['user_status']) != -1) {
            $where['user_status'] = intval($request['user_status']);
        }
        if (isset($request['is_online']) && intval($request['is_online']) >= 0) {
            $where['is_online'] = intval($request['is_online']);
        }

        if (isset($request['sex']) && intval($request['sex']) != -1) {
            $where['sex'] = intval($request['sex']);
        }

        if (isset($request['order']) && intval($request['order']) != -1) {
            $order = 'income_total';
        } else {
            $order = 'create_time';

        }
        if ($request['end_time'] && $request['start_time']) {
            $where['create_time'] = ['between', [strtotime($request['start_time']), strtotime($request['end_time'])]];
        }
        if ($request['end_time2'] && $request['start_time2']) {
            $where['last_login_time'] = ['between', [strtotime($request['start_time2']), strtotime($request['end_time2'])]];
        }

        $keywordComplex = [];
        if (!empty($request['keyword'])) {
            $keyword = $request['keyword'];
            $keywordComplex['user_login|user_nickname|user_email|mobile'] = ['like', "%$keyword%"];
        }
        $usersQuery = Db::name('user');

        $list = $usersQuery->whereOr($keywordComplex)->where($where)->order("$order DESC")->paginate();
        $lists = $list->toArray();
        if ($lists['data'] != null) {
            // print_r($lists);exit;
            foreach ($lists['data'] as $k => $v) {
                $uid = $v['id'];
                $find = Db::name("user_reference")->where("uid=$uid")->find();
                if ($find) {
                    $v['reference'] = '1';
                } else {
                    $v['reference'] = '0';
                }
                $user = Db::name("invite_profit_record")->alias("a")->join("user u", "u.id=a.user_id")
                    ->field('u.user_nickname,a.user_id')->where("a.invite_user_id=$uid")->find();

                $dataResult[$k]['id'] = $uid ? $uid : '暂无数据';
                $dataResult[$k]['user_nickname'] = $v['user_nickname'] ? $v['user_nickname'] : '暂无数据';
                $dataResult[$k]['sex'] = $v['sex'] == '1' ? '男' : '女';
                $dataResult[$k]['mobile'] = $v['mobile'] ? $v['mobile'] : '暂无';
                $dataResult[$k]['coin'] = $v['coin'] ? $v['coin'] : '0';
                $dataResult[$k]['income'] = $v['income'] ? $v['income'] : '0';
                $dataResult[$k]['income_total'] = $v['income_total'] ? $v['income_total'] : '0';
                $dataResult[$k]['invite_user_name'] = $user['user_nickname'] ? $user['user_nickname'] : '无';
                $dataResult[$k]['create_time'] = $v['create_time'] ? date('Y-m-d h:i', $v['create_time']) : '暂无';

            }

            $str = "ID,会员,性别,手机号,余额,收益,累计收益,邀请人,注册时间";

            $this->excelData($dataResult, $str, $title);
            exit();
        } else {
            $this->error("暂无数据");
        }
    }

    //添加用户
    public function addUser()
    {
        return $this->fetch();
    }

    //查看用户关注和粉丝
    public function attention()
    {
        $id = input('request.id');
        $type = input('request.type');
        $root = array('status' => 0, 'msg' => '暂无数据', 'data' => []);
        if ($type == '2') {
            $where = "a.uid=$id";
            $attention = Db::name("user_attention")->alias("a")->field("a.*,u.user_nickname")->join('user u', 'a.attention_uid = u.id')->where($where)->select();
        } else {

            $attention = Db::name("user_attention")->alias("a")->field("a.*,u.user_nickname")->join('user u', 'a.uid = u.id')->where("a.attention_uid=" . $id)->select();
            //  var_dump(Db::name("user_attention")->getLastSql());exit;
        }

        $html = '';

        if ($attention) {
            foreach ($attention as $v) {
                $sid = $type == '2' ? $v['attention_uid'] : $v['uid'];
                $html .= '<tr><td>' . $sid . '</td><td style="width:150px;overflow:hidden;">' . $v['user_nickname'] . '</td><td>' . date("Y-m-d", $v['addtime']) . '</td></tr>';
            }
            $root['status'] = '1';
            $root['data'] = $html;
        }
        if ($html == '') {
            $root['status'] = '0';
        }
        echo json_encode($root);
    }

    public function addUserPost()
    {
        $user_login = input('user_login');
        $user_nickname = input('user_nickname');
        $signature = input('signature');
        $sex = input('sex');
        $avatar = input('avatar');
        /*$ava = preg_replace("/'/","", $avatar);
                    dump($ava);
        */
        $login = db('user')->where('user_login', $user_login)->find();
        $nickname = db('user')->where('user_nickname', $user_nickname)->find();
        if (!empty($login)) {
            $this->error("用户名已存在");
        } else if (!empty($nickname)) {
            $this->error("昵称已存在");
        } else if (empty($sex)) {
            $this->error("性别不能为空");
        } elseif (empty($avatar)) {
            $this->error("头像不能为空");
        } else {
            $data = [
                'user_login' => $user_login,
                'user_nickname' => $user_nickname,
                'signature' => $signature,
                'sex' => $sex,
                'avatar' => $avatar,
                'create_time' => time(),
            ];
            $res = db('user')->insert($data);
            if ($res) {
                $this->success("添加成功");
            } else {
                $this->error("添加失败");
            }
        }

    }


    //禁用头像
    public function edit_img()
    {
        $id = input('param.id', 0, 'intval');
        if ($id) {
            $user['id'] = $id;
            $config = load_cache('config');

            $res = db("user")->where('id', '=', $id)->update(array("avatar" => $config['user_avatar']));

            if ($res) {
                $this->success('操作成功！');
            } else {
                $this->error('操作失败！');
            }

        } else {
            $this->error('数据传入失败！');
        }
    }

    //封禁设备
    public function add_closures()
    {
        $request = request()->param();
        if(!isset($request['device_uuid'])){
           $this->success('暂无设备号！');
        }
        $device = db('equipment_closures')->where('device_uuid', $request['device_uuid'])->select();

        if (count($device) > 0) {
          //  $this->success('该设备已封禁！');
             $res = db('equipment_closures')->where('device_uuid', $request['device_uuid'])->delete();
        }else{
            $data = [
                'uid' => $request['uid'],
                'device_uuid' => $request['device_uuid'],
                'addtime' => time(),
            ];
            $res = db('equipment_closures')->insert($data);
         }

        if ($res) {
            $this->success('操作成功！');
        } else {
            $this->error('操作失败！');
        }
    }

    //取消主播认证
    public function cancel_auth()
    {
        $id = input('param.id', 0, 'intval');
        $root['status'] = 1;
        $root['msg'] = '';
        if ($id) {
            //改变认证状态
            $res = db("user")->where('id', '=', $id)->update(array("is_auth" => 0));
            //删除认证资料
            db('auth_form_record')->where('user_id', '=', $id)->delete();
            if (!$res) {
                $root['status'] = 0;
                $root['msg'] = '操作失败！';
            }
        } else {
            $root['status'] = 0;
            $root['msg'] = '数据传入失败！';
        }

        echo json_encode($root);
        exit;
    }
}
