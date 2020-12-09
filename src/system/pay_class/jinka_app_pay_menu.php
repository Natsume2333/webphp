<?php

use think\Log;
class jinka_app_pay
{

    protected $gateway = "http://47.244.3.97/api/pay/create";

    public function get_payment_code($pay, $rule, $order_id)
    {
        Log::info('jinka_app_pay:'.'订单开始');
        Log::info('jinka_pay_app_pay:' . 'pay=' . json_encode($pay));
        Log::info('jinka_pay_app_pay:' . 'rule=' . json_encode($rule));
        Log::info('jinka_pay_app_pay:' . 'order_id=' . $order_id);

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $order_id;

        //商户号
        $merchant_no = $pay['merchant_id'];
        //密钥
        $key = $pay['md5_key'];

        //请求的参数
        $data = array();
        $data['coopId'] = $merchant_no;
        $data['outOrderNo'] = $out_trade_no;
        $data['subject'] = '虚拟币';
        $data['money'] = floatval($rule['money']*100);
        $data['notifyUrl'] = SITE_URL . "/mapi/public/index.php/api/hui_api/jinka_notify";
        $data['pathType'] = $pay['pay_type'];


        $data['sign'] = $this->sign($data, $key);
        // $data['pay_productname'] = '虚拟币';
        $posturl = $this->gateway;
        $response = $this->curlPost($data, $posturl);

        Log::info('jinka_pay_app_pay:' . 'response=' . $response);

        $responsedata = json_decode($response, true);
        if ($responsedata['code'] == '0') {
                $pay_info['post_url'] = $responsedata['payurl'];       //生成指定网址
                $pay_info['is_wap'] = 1;
                return $pay_info;
            } else {
                $result['code'] = 0;
                $result['msg'] = $responsedata['msg'].'请求失败';
                return_json_encode($result);
    }


        /*
        $res = '<form id="my_form" action="'.$posturl.'" method="post">
    <input type="hidden" name="pay_memberid" value="'.$data['pay_memberid'].'">
    <input type="hidden" name="pay_orderid" value="'. $data['pay_orderid'].'">
    <input type="hidden" name="pay_applydate" value="'.$data['pay_applydate'].'">
	<input type="hidden" name="pay_bankcode" value="'. $data['pay_bankcode'].'">
    <input type="hidden" name="pay_notifyurl" value="'.$data['pay_notifyurl'].'">
    <input type="hidden" name="pay_callbackurl" value="'.$data['pay_callbackurl'].'">
    <input type="hidden" name="pay_amount" value="'.$data['pay_amount'].'">
    <input type="hidden" name="pay_md5sign" value="'.$data['pay_md5sign'].'">
	<input type="hidden" name="pay_productname" value="'.$data['pay_productname'].'">
    </form>';
        $res .= '<script type="text/javascript">document.getElementById("my_form").submit();</script>';


        echo $res;
        exit;
        */

    }

    public function curlPost($aPostData, $sUrl, $respondType = 1, $timeout = 5)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($aPostData));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, (int)$timeout);
        curl_setopt($ch, CURLOPT_USERAGENT,
            'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/536.11 (KHTML, like Gecko) Chrome/20.0.1132.47 Safari/536.11');// 添加浏览器内核信息，解决403问题 add by ben 2017/10/25
        $response = curl_exec($ch);
        curl_close($ch);

        return $response;
    }

    public function notify($request)
    {

        Log::info('jinka_pay_app_notify 回调开始' . date("Y-m-d H:i:s"));
        Log::info('jinka_pay_app_notify 回调开始' . 'request:' . json_encode($request));
        //require_once DOCUMENT_ROOT . '/system/pay/wechat/fun.php';
        //查询订单
        $order_info = db('user_charge_log')->where('order_id', '=', $request["outOrderNo"])->find();

        $pay_info = db('pay_menu')->find($order_info['pay_type_id']);
        if (!$pay_info) {
            notify_log($request["outOrderNo"], '0', '后台支付通道不存在');
            echo '';
            exit;
        }
        $key = $pay_info['md5_key'];
        $data = array();
        $data['code'] = $request["code"];
        $data['orderNo'] = $request["orderNo"];
        $data['outOrderNo'] = $request["outOrderNo"];
        $data['subject'] = $request["subject"];
        $data['money'] = $request["money"];
        $data['orderStatus'] = $request["orderStatus"];
        $data['pathType'] = $request['pathType'];
        $data['payTime'] = $request['payTime'];

        $sign = $request["sign"];

        $my_sign = $this->sign($data, $key);

        Log::info('jinka_pay_app_notify:' . 'data=' . json_encode($data));
        Log::info('jinka_pay_app_notify:' . 'my_sign=' . $my_sign);
        Log::info('jinka_pay_app_notify:' . 'sign=' . $sign);
        $realmoney = floatval($data['money']/100);
        $verify = $sign == $my_sign;
        Log::info('jinka_pay_app_notify:' . '$verify:' . $verify);

        // file_put_contents(DOCUMENT_ROOT . "/public/sign" . date("Y-m-dHis") . ".txt", http_build_query($data) . '&key=' . $key);
        if ($verify && $data['code'] == '0') {
            if ($data['orderStatus'] == "1") {

                //可在此处增加操作数据库语句，实现自动下发，也可在其他文件导入该php，写入数据库
                pay_call_service_1023($data['outOrderNo'], $realmoney);
                //echo "商户收款成功，订单正常完成！";
                echo 'success';
                exit();
            }
        } else {
            Log::info('jinka_pay_app_notify verify is false ');
            exit();
        }
    }

    public function sign($params, $key)
    {
        unset($params["sign"]);
        ksort($params);
        $string = '';
        foreach ($params as $k => $value) {
            if ($value !== "") {
                $string .= $k . '=' . $value . '&';
            }
        }
        $string = rtrim($string, '&');//去掉最后一个&符号
        $string = $string . $key;
        //$this->log_message('error', 'Pay_maopay_model' . ' string: ' . $string);
        return strtolower(md5($string));
    }
}