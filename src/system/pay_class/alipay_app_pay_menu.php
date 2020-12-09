<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/21
 * Time: 23:51
 */

class alipay_app_pay
{

    public function get_payment_code($pay,$rule,$notice_id)
    {
        $order_id = $notice_id;
        $good_name = $rule['name'];
        $money = $rule['money'];
        $good_name = '虚拟币充值';
        require_once DOCUMENT_ROOT . '/system/pay/alipay/AopSdk.php';

        $aop = new AopClient;
        $aop->gatewayUrl = "https://openapi.alipay.com/gateway.do";
        $aop->appId = trim($pay['app_id']);
        $aop->rsaPrivateKey = trim($pay['private_key']);//'请填写开发者私钥去头去尾去回车，一行字符串';
        $aop->format = "json";
        $aop->charset = "UTF-8";
        $aop->signType = "RSA2";
        $aop->alipayrsaPublicKey = trim($pay['public_key']);//'请填写支付宝公钥，一行字符串';
        //实例化具体API对应的request类,类名称和接口名称对应,当前调用接口名称：alipay.trade.app.pay
        $request = new AlipayTradeAppPayRequest();
        //SDK已经封装掉了公共参数，这里只需要传入业务参数
        $bizcontent = "{\"body\":\"$good_name\","
            . "\"subject\": \"$good_name\","
            . "\"out_trade_no\": \"$order_id\","
            . "\"timeout_express\": \"30m\","
            . "\"total_amount\": \"$money\","
            . "\"product_code\":\"QUICK_MSECURITY_PAY\""
            . "}";
        $request->setNotifyUrl(SITE_URL . "/mapi/public/index.php/api/notify_api/alipay_notify");
        $request->setBizContent($bizcontent);
        //这里和普通的接口调用不同，使用的是sdkExecute
        $response = $aop->sdkExecute($request);
        //htmlspecialchars是为了输出到页面时防止被浏览器将关键参数html转义，实际打印到日志以及http传输不会有这个问题
        $pay_info['pay_info'] =  $response;//就是orderString 可以直接给客户端请求，无需再做处理。
        $pay_info['is_wap']  = 0;
        $pay_info['type'] = 1;
        //file_put_contents('./alipay.txt',$response);
        return $pay_info;
    }

    public function notify($request){

        //查询订单
        $order_info = db('user_charge_log') -> where('order_id','=', $request["out_trade_no"]) -> find();

        $pay_info = db('pay_menu') -> find($order_info['pay_type_id']);
        if(!$pay_info){
            notify_log( $request["merchant_order_no"],'0','充值回调成功,error:充值渠道不存在');
            echo 'success';
            exit;
        }

        //file_put_contents(DOCUMENT_ROOT."/public/2ealipay_".date("Y-m-dHis").".txt",print_r($_REQUEST,true));
        require_once DOCUMENT_ROOT . '/system/pay/alipay/AopSdk.php';

        $app_id = $pay_info['app_id'];

        $aop = new \AopClient;
        $aop->alipayrsaPublicKey = $pay_info['public_key'];
        $flag = $aop->rsaCheckV1($request, NULL, "RSA2");

        //file_put_contents(DOCUMENT_ROOT."/public/参数打印".date("Y-m-dHis").".txt",'结果:' . $flag .'---appid:'.($request('app_id') == $app_id));
        if($request['app_id'] == $app_id && $request['trade_status'] == 'TRADE_SUCCESS'){
            pay_call_service($request["out_trade_no"]);
        }

        //file_put_contents(DOCUMENT_ROOT."/public/sign".date("Y-m-dHis").".txt",http_build_query($sign_data).'&key='.$key);


        echo 'success';
        exit;
    }
}