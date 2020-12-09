<?php

use think\Log;
class huanshang_app_pay
{
    protected $gateway = "http://ad.xcd3589.com:29330/api/v1.0/convenience_pay";

    public function get_payment_code($pay, $rule, $order_id, $param)
    {
        Log::info('huanshang_pay_app_pay:get_payment_code 进入');
        $out_trade_no = $order_id;
        //商户号
        $merchant_no = $pay['merchant_id'];
        //密钥
        $key = $pay['private_key'];
        $notify = SITE_URL . "/mapi/public/index.php/api/hui_api/huanshang_notify";
        //请求的参数
        $data['request_id'] = time();
        $data['merchant_no'] = $merchant_no;
        $data['payment'] = $pay['app_id'];
        $data['total_fee'] = floatval($rule['money']*100);
        $data['order_ip'] = '1.1.1.1';
        $data['out_order_number'] = $out_trade_no;
        $data['order_title'] = 'hs';
        $data['order_desc'] = 'hspay';
        $data['notify_url'] = $notify;
        $data['return_url'] = 'www.kj000.com';

        $data['sign'] = $this->sign($data, $key);

        $posturl = $this->gateway;
        $response = $this->curlPost($data, $posturl);

        Log::info('huanshang_pay_app_pay:' . 'pay=' . json_encode($pay));
        Log::info('huanshang_pay_app_pay:' . 'rule=' . json_encode($rule));
        Log::info('huanshang_pay_app_pay:' . 'order_id=' . $order_id);
        Log::info('huanshang_pay_app_pay:' . 'response=' . $response);
        //$b = substr($response,0,4);


        $responsedata = json_decode($response, true);
        if ($responsedata['status'] == "000000") {
            $pay_info['post_url'] = $responsedata['content'];       //生成指定网址
            $pay_info['is_wap']  = 1;
            return $pay_info;
        }
    }

    public function curlPost($aPostData, $sUrl, $respondType = 1, $timeout = 5)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $sUrl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($aPostData));
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

        Log::info('huanshang_pay_app_notify 回调开始' . date("Y-m-d H:i:s"));
        Log::info('huanshang_pay_app_notify 回调开始' . 'request:' . json_encode($request));
        //require_once DOCUMENT_ROOT . '/system/pay/wechat/fun.php';
        //查询订单
        $order_info = db('user_charge_log')->where('order_id', '=', $request["out_order_number"])->find();

        $pay_info = db('pay_menu')->find($order_info['pay_type_id']);
        if (!$pay_info) {
            notify_log($request["out_order_number"], '0', '后台支付通道不存在');
            echo '';
            exit;
        }
        $key = $pay_info['private_key'];
        $data = array();
        $data['status'] = $request["status"];
        $data['status_desc'] = $request["status_desc"];
        $data['merchant_no'] = $request["merchant_no"];
        $data['total_fee'] = $request["total_fee"];
        $data['out_order_number'] = $request["out_order_number"];
        $data['payment'] = $request["payment"];
        $data['complete_at'] = $request['complete_at'];

        $sign = $request["sign"];

        $my_sign = $this->sign($data, $key);

        Log::info('huanshang_pay_app_notify:' . 'data=' . json_encode($data));
        Log::info('huanshang_pay_app_notify:' . 'my_sign=' . $my_sign);
        Log::info('huanshang_pay_app_notify:' . 'sign=' . $sign);
        $realmoney = floatval($data['total_fee']/100);
        $verify = $sign == $my_sign;
        Log::info('huanshang_pay_app_notify:' . '$verify:' . $verify);

        // file_put_contents(DOCUMENT_ROOT . "/public/sign" . date("Y-m-dHis") . ".txt", http_build_query($data) . '&key=' . $key);
        if ($verify) {
            if ($data['status'] == "SUCCESS") {

                //可在此处增加操作数据库语句，实现自动下发，也可在其他文件导入该php，写入数据库
                pay_call_service_1023($data['out_order_number'], $realmoney);
                //echo "商户收款成功，订单正常完成！";
                echo 'OK';
            }
        } else {
            Log::info('huanshang_pay_app_notify verify is false ');
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
        //$this->log_message('error', 'Pay_huanshangpay_model' . ' string: ' . $string);
        return md5($string);
    }
}