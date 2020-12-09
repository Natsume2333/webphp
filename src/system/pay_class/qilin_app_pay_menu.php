<?php

use think\Log;
class qilin_app_pay
{
    protected $gateway = "http://j1.kylinpay.vip/channel/common/mail_interface";

    public function get_payment_code($pay, $rule, $order_id)
    {

        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $order_id;

        //商户号
        $merchant_no = $pay['merchant_id'];
        //密钥
        $key = $pay['md5_key'];

        //请求的参数
        $data['return_type'] = 'json';
        $data['api_code'] = $merchant_no;
        $data['is_type'] = $pay['pay_type'];
        $data['price'] = number_format($rule['money'], '2', '.', '');
        $data['order_id'] = $out_trade_no;
        $data['time'] = time();
        $data['mark'] = '虚拟币';
        $data['return_url'] = 'http://www.kj000.com';
        $data['notify_url'] = SITE_URL . "/mapi/public/index.php/api/hui_api/qilin_notify";

        $data['sign'] = $this->sign($data, $key);
       // $data['pay_productname'] = '虚拟币';
        $posturl = $this->gateway;
        $response = $this->curlPost($data, $posturl);
        Log::info('qilin_pay_app_pay:' . 'pay=' . json_encode($pay));
        Log::info('qilin_pay_app_pay:' . 'rule=' . json_encode($rule));
        Log::info('qilin_pay_app_pay:' . 'order_id=' . $order_id);
        Log::info('qilin_pay_app_pay:' . 'response=' . $response);
        /**
         * {"price":"30.00",
         * "real_price":"30.00",
         * "is_type":"alipayangel",
         * "order_id":"1572083121100323",
         * "mark":"虚拟币",
         * "paysapi_id":"IA26831213715245",
         * "payurl":"http:\/\/fa6f37aed0c34962a1d63765f0d2146b.koudaibot.com\/wechatpay\/jfinal\/pay\/1191026173905546286-02688e5a5f641f4813024c652b878ef0",
         * "messages":{"returncode":"SUCCESS","returnmsg":"付款即时到账，未到账可联系我们"}}
        [ error ] [8]未定义数组索引: returnmsg
         */
        $responsedata = json_decode($response, true);
        if (array_key_exists("payurl",$responsedata)){
            if ($responsedata['messages']['returncode'] == 'SUCCESS') {
                $pay_info['post_url'] = $responsedata['payurl'];       //生成指定网址
                $pay_info['is_wap'] = 1;
                return $pay_info;
            }else{
                $result['code'] = 0;
                $result['msg'] = '校验失败，充值失败';
                return_json_encode($result);
            }
        }else{
            $result['code'] = 0;
            $result['msg'] = $responsedata['returnmsg']."，请选择其他通道进行充值";
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



        Log::info('qilin_pay_app_notify 回调开始' . date("Y-m-d H:i:s"));


        $input=file_get_contents('php://input');
        Log::info("qilin_pay_app_notify"."返回数据：".$input);
        //F('input',$input);
        $file=json_decode($input,true);

        Log::info('qilin_pay_app_notify 回调开始' . '$file:' . json_encode($file));
        //require_once DOCUMENT_ROOT . '/system/pay/wechat/fun.php';
        //查询订单
        $order_info = db('user_charge_log')->where('order_id', '=', $file["order_id"])->find();

        $pay_info = db('pay_menu')->find($order_info['pay_type_id']);
        if (!$pay_info) {
            notify_log($file["order_id"], '0', '后台支付通道不存在');
            echo '';
            exit;
        }

        /*
        {"api_code":"62303230",
         * "paysapi_id":"IA27673217956161",
         * "order_id":"1572167321100323",
         * "is_type":"wechatangelwap",
         * "price":"100.00",
         * "real_price":"100.00",
         * "mark":"虚拟币",
         * "code":1,
         * "sign":"FC5ECA1920E04247A3D4FA6B73B1F6B8"}
         */
        $key = $pay_info['md5_key'];
        $data = array();
        $data['paysapi_id'] = $file["paysapi_id"];
        $data['order_id'] = $file["order_id"];
        $data['is_type'] = $file["is_type"];
        $data['price'] = $file["price"];
        $data['real_price'] = $file["real_price"];
        $data['mark'] = $file["mark"];
        $data['code'] = $file['code'];
        $data['api_code'] = $pay_info['merchant_id'];


        $sign = $file["sign"];

        $my_sign = $this->sign($data, $key);

        Log::info('qilin_pay_app_notify:' . 'data=' . json_encode($data));
        Log::info('qilin_pay_app_notify:' . 'my_sign=' . $my_sign);
        Log::info('qilin_pay_app_notify:' . 'sign=' . $sign);
        $realmoney = floatval($data['real_price']);
        $verify = $sign == $my_sign;
        Log::info('qilin_pay_app_notify:' . '$verify:' . $verify);

        // file_put_contents(DOCUMENT_ROOT . "/public/sign" . date("Y-m-dHis") . ".txt", http_build_query($data) . '&key=' . $key);
        if ($verify && $data['code'] == "1") {

                //可在此处增加操作数据库语句，实现自动下发，也可在其他文件导入该php，写入数据库
                pay_call_service_1023($data['order_id'], $realmoney);
                //echo "商户收款成功，订单正常完成！";
                echo 'SUCCESS';
        } else {
            Log::info('qilin_pay_app_notify verify is false ');
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
        //$string = rtrim($string, '&');//去掉最后一个&符号
        $string = $string . "key=" . $key;
        //$this->log_message('error', 'Pay_maopay_model' . ' string: ' . $string);
        return strtoupper(md5($string));
    }
}