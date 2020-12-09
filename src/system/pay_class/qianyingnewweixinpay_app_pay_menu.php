<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/8/25
 * Time: 22:13
 */

class qianyingnewweixinpay_app_pay
{
    public function get_payment_code($pay,$rule,$order_id)
    {

        $pay_info['post_url'] = SITE_URL . '/mapi/public/index.php/api/pay_api/pay_display_html?order_id='.$order_id.'&class_name='.$pay['class_name'];

        $pay_info['is_wap']  = 1;
        return $pay_info;
    }


    public function display_html($order_id){

        $order_info = db('user_charge_log') -> where('order_id','=',$order_id) -> find();
        if(!$order_info){
            echo '订单不存在！';
            exit;
        }

        $rule = db('user_charge_rule') -> find($order_info['refillid']);
        if(!$rule){
            echo '充值规则不存在！';
            exit;
        }


        $pay_channel_info = db('pay_menu') -> find($order_info['pay_type_id']);
        if(!$pay_channel_info){
            echo '支付渠道不存在！';
            exit;
        }


        $orderuid = '';       //此处传入您网站用户的用户名，方便在平台后台查看是谁付的款，强烈建议加上。可忽略。

        $goodsname = "虚拟币充值";
        $orderid = $order_id;    //每次有任何参数变化，订单号就变一个吧。
        $uid = $pay_channel_info['merchant_id'];//"此处填写平台的uid";
        $token = $pay_channel_info['public_key'];//"此处填写平台的Token";
        $return_url = 'http://www.demo.com/payreturn.php';
        $notify_url = SITE_URL . "/mapi/public/index.php/api/notify_api/qianyingnew_notify";
        $key = md5($goodsname. 2 . $notify_url . $orderid . $orderuid . $rule['money'] . $return_url . $token . $uid);
        //经常遇到有研发问为啥key值返回错误，大多数原因：1.参数的排列顺序不对；2.上面的参数少传了，但是这里的key值又带进去计算了，导致服务端key算出来和你的不一样。

        $param['goodsname'] = $goodsname;
        $param['istype'] = 2;
        $param['key'] = $key;
        $param['notify_url'] = $notify_url;
        $param['orderid'] = $orderid;
        $param['orderuid'] =$orderuid;
        $param['price'] = $rule['money'];
        $param['return_url'] = $return_url;
        $param['uid'] = $uid;


        $posturl = 'https://pay.qianyingnet.com/pay';
        $res = '<form id="my_form" action="'.$posturl.'" method="post">
    <input type="hidden" name="goodsname" value="'.$param['goodsname'].'">
    <input type="hidden" name="istype" value="'.$param['istype'].'">
    <input type="hidden" name="key" value="'.$param['key'].'">
	<input type="hidden" name="notify_url" value="'.$param['notify_url'].'">
    <input type="hidden" name="orderid" value="'.$param['orderid'].'">
    <input type="hidden" name="orderuid" value="'.$param['orderuid'].'">
    <input type="hidden" name="price" value="'.$param['price'].'">
    <input type="hidden" name="return_url" value="'.$param['return_url'].'">
	<input type="hidden" name="uid" value="'.$param['uid'].'">	
    </form>';
        $res .= '<script type="text/javascript">document.getElementById("my_form").submit();</script>';


        echo($res);exit;
    }

    public function notify($request){

        bugu_request_file(DOCUMENT_ROOT."/system/pay/qianyingnew/fun.php");

        $platform_trade_no = $_POST["platform_trade_no"];
        $orderid = $_POST["orderid"];
        $price = $_POST["price"];
        $realprice = $_POST["realprice"];
        $orderuid = $_POST["orderuid"];
        $key = $_POST["key"];

        //查询订单
        $order_info = db('user_charge_log') -> where('order_id','=', $orderid) -> find();

        $pay_channel_info = db('pay_menu') -> find($order_info['pay_type_id']);
        if(!$pay_channel_info){
            return jsonError("值回调成功,error:充值渠道不存在");
        }

        //校验传入的参数是否格式正确，略

        $token = $pay_channel_info['public_key'];

        $temps = md5($orderid . $orderuid . $platform_trade_no . $price . $realprice . $token);

        if ($temps != $key){
            return jsonError("key值不匹配");
        }else{
            //校验key成功，是自己人。执行自己的业务逻辑：加余额，订单付款成功，装备购买成功等等。
            pay_call_service($orderid);
        }
    }
}