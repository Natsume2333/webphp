<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/31 0031
 * Time: 上午 9:25
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
class RechargeApi extends Base
{
    //充值金币
    public function get_recharge_page_data(){

        $result = array('code'=>0,'msg'=>'充值金币','data'=>array());

        $list = db('user_charge_rule') -> where('type','=',1) -> field('id,name,money,coin,give,pay_pal_money') -> order("orderno asc") ->select();

        $pay_list  = db('pay_menu') -> field('id,pay_name,icon') -> where('status','=',1) ->order("orderno asc")->select();

        $result['code'] = 1;
        $result['list'] = $list;
        $result['pay_list'] = $pay_list;

        return_json_encode($result);
    }
}