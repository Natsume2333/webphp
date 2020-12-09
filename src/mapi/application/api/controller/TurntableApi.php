<?php

namespace app\api\controller;

use think\App;
use think\Db;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ CUCKOO ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

//转盘游戏
class TurntableApi extends Base
{
    //转盘首页
    public function index()
    {
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $res = db('turntable')->order('orderon')->limit(0,11)->select();
        $config = load_cache('config');
        $coin = $config['turntable_game_coin'];
        $name=[];
        $url = SITE_URL;
        foreach ($res as &$v) {

            if($v['gift_id'] !=0){
                $gift = db('gift')->where('id='.$v['gift_id'])->field("name,img")->find();

                $v['name']=$gift['name'];
                $v['img']= $url.'/admin/public/upload/'.$gift['img'];
            }else{
                 $v['name']='未中奖';
                $v['img']='/mapi/public/static/img/S9VGZU.png';
            }
            $name[]='';//$v['name']
           

        }     
           //转盘游戏规则
         $rules = '';
        $category = db('portal_category')->where("name='转盘规则'")->find();
        if ($category) {
            $sex_type = db('portal_category_post')->where("category_id=" . $category['id'])->find();
            $oldTagIds = db('portal_post')->where("id=" . $sex_type['post_id'])->find();
            $rules = html_entity_decode($oldTagIds['post_content']);
        }

        $this->assign(['list' => $res, 'uid' => $uid, 'token' => $token, 'sum' => $coin,'name'=>json_encode($name),'rules' => $rules]);
        return $this->fetch();
    }

    //抽奖
    public function turn_post()
    {
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $user_info = check_login_token($uid, $token);

        $config = load_cache('config');
        $coin = $config['turntable_game_coin'];

        //需要的金币数
        $need_coin = $coin;

        if ($user_info['coin'] < $need_coin) {
            $result['code'] = 0;
            $result['msg'] = '金币不足请充值';
            return_json_encode($result);
        }

        //用户扣费
        $res = del_coin($uid, $need_coin);

        if ($res) {
            $gift = db('turntable')->order('orderon')->select();
            foreach ($gift as $key => $val) {
                $arr[$val['id']] = $val['probability'];
            }
            $rid = $this->get_rand($arr);
            //插入抽奖记录
            $data = [
                'user_id' => $uid,
                'turntable_id' => $rid,
                'addtime' => time(),
            ];
            $user_turn = db('userTurntable')->insert($data);

            //查询是否在和主播通话
            $video_call_record = db('video_call_record')->where('user_id=' . $uid . ' or call_be_user_id=' . $uid)->find();
            //$video_call_record['anchor_id'] = 100190;
            if ($video_call_record) {
                //计算主播收益
                $income = host_income_commission(7, $need_coin, $video_call_record['anchor_id']);
                //增加主播收益
                add_income($video_call_record['anchor_id'], $income);
                //增加扣费明细
                add_charging_log($uid, $video_call_record['anchor_id'], 7, $need_coin, $user_turn, $income, '');
            }

            if ($user_turn) {
                $turn = db('turntable')->where('id', $rid)->select();
                if ($turn[0]['gift_id'] == 0) {
                    $result['code'] = 1;
                    $result['msg'] = '很遗憾您未中奖';
                    $result['data'] = [
                        'id' => $rid,
                        'order' => $turn[0]['orderon'],
                        'msg' => '未中奖',
                        'img' => '/mapi/public/static/img/S9VGZU.png',
                    ];
                    return_json_encode($result);
                } else {
                    $bag = db('user_bag')->where(['uid' => $uid, 'giftid' => $turn[0]['gift_id']])->find();
                    if (empty($bag)) {
                        $bagData = [
                            'uid' => $uid,
                            'giftid' => $turn[0]['gift_id'],
                            'giftnum' => 1,
                        ];
                        db('user_bag')->insert($bagData);
                    } else {
                        $bagData = [
                            'giftnum' => $bag['giftnum'] + 1,
                        ];
                        db('user_bag')->where(['uid' => $uid, 'giftid' => $turn[0]['gift_id']])->update($bagData);
                    }
                    $giftfind = db('gift')->where('id', $turn[0]['gift_id'])->select();
                    $result['code'] = 1;
                    $result['msg'] = '恭喜您抽中' . $giftfind[0]['name'];
                    $result['data'] = [
                        'id' => $rid,
                        'order' => $turn[0]['orderon'],
                        'name' => $giftfind[0]['name'],
                        'img' => $giftfind[0]['img'],
                    ];
                    return_json_encode($result);
                }
            }
        }
    }

    public function get_rand($proArr)
    {
        $result = '';
        //概率数组的总概率精度
        $proSum = array_sum($proArr);
        //概率数组循环
        foreach ($proArr as $key => $proCur) {
            $randNum = mt_rand(1, $proSum);
            if ($randNum <= $proCur) {
                $result = $key;
                break;
            } else {
                $proSum -= $proCur;
            }
        }
        unset($proArr);
        return $result;
    }

}