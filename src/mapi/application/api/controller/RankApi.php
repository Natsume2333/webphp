<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/2/28
 * Time: 20:19
 */

namespace app\api\controller;
// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

class RankApi extends Base
{

    //魅力排行榜
    public function charm_rank_list(){

        $result = array('code' => 1,'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $type = trim(input('param.type'));

        $user_info = check_login_token($uid,$token);

        $param = array('rank_name'=>$type,'table'=>'','page'=>1,'page_size'=>10,'cache_time'=>60);
        $list = load_cache('rank_charm',$param);
        $result['order_num'] = '未上榜';

        $config = load_cache('config');
        $i = 1;
        foreach ($list as &$v){
            $v['is_online'] = is_online($v['id'],$config['heartbeat_interval']);
            //用户的排行
            if($v['id'] == $uid){
                $result['order_num'] = $i;
            }
            $v['order_num'] = $i;
            $i++;
        }
        $result['list'] = $list;
        $result['list'] = $list;

        return_json_encode($result);
    }


    //财富排行榜
    public function wealth_rank_list(){

        $result = array('code' => 1,'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $type = trim(input('param.type'));

        $user_info = check_login_token($uid,$token);

        $param = array('rank_name'=>$type,'table'=>'','page'=>1,'page_size'=>10,'cache_time'=>60);
        $list = load_cache('rank_wealth',$param);
        $result['order_num'] = '未上榜';
        $config = load_cache('config');

        $i = 1;
        foreach ($list as &$v){
            $v['is_online'] = is_online($v['id'],$config['heartbeat_interval']);
            //用户的排行
            if($v['id'] == $uid){
                $result['order_num'] = $i;
            }
            $v['order_num'] = $i;
            $i++;
        }
        $result['list'] = $list;

        return_json_encode($result);
    }

    //个人贡献排行榜
    public function user_contribution_rank(){

        $result = array('code' => 1,'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $to_user_id = trim(input('param.to_user_id'));

        $user_info = check_login_token($uid,$token);

        $param = array('user_id'=>$to_user_id,'table'=>'','page'=>1,'page_size'=>50,'cache_time'=>60);
        $list = load_cache('user_rank_contribution',$param);
        $result['order_num'] = '未上榜';
        $config = load_cache('config');

        $i = 1;
        foreach ($list as &$v){
            $v['is_online'] = is_online($v['id'],$config['heartbeat_interval']);
            //用户的排行
            if($v['id'] == $uid){
                $result['order_num'] = $i;
            }
            $i++;
        }
        $result['list'] = $list;

        return_json_encode($result);
    }
}