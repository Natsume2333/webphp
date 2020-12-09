<?php
use think\Log;
class mao_app_pay
{
    protected $gateway = "http://pay.study997.com/Pay_Index.html";

    public function get_payment_code($pay,$rule,$order_id)
    {

        //require_once DOCUMENT_ROOT . '/system/pay/kuaijie/common.php';
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $order_id;

        //商户号
        $merchant_no = $pay['merchant_id'];
        //密钥
        $key = $pay['md5_key'];

        //请求的参数
        $data['pay_memberid'] = $merchant_no;
        $data['pay_orderid'] = $out_trade_no;
        $data['pay_applydate'] = date("Y-m-d H:i:s");
        $data['pay_bankcode'] = $pay['pay_type'];

        $data['pay_notifyurl'] = SITE_URL . "/mapi/public/index.php/api/hui_api/mao_notify";
        $data['pay_callbackurl'] = "http://www.kj000.com";

        $data['pay_amount'] = number_format($rule['money'],'2','.','');
        $data['pay_md5sign'] = $this->sign($data,$key);
        $data['pay_productname'] = '虚拟币';
        $posturl = $this->gateway;
        $response = $this->curlPost($data,$posturl);
        Log::info('mao_pay_app_pay:'.'pay='.json_encode($pay));
        Log::info('mao_pay_app_pay:'.'rule='.json_encode($rule));
        Log::info('mao_pay_app_pay:'.'order_id='.$order_id);
        Log::info('mao_pay_app_pay:'.'response='.$response);

        $responsedata = json_decode($response,true);
        if ($responsedata['status'] == "success"){
            $pay_info['post_url'] = $responsedata['data']['payUrl'];       //生成指定网址
            $pay_info['is_wap']  = 1;
            return $pay_info;
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
    public  function curlPost($aPostData, $sUrl, $respondType = 1, $timeout = 5) {
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

        Log::info('mao_pay_app_notify 回调开始'.date("Y-m-d H:i:s"));
        Log::info('mao_pay_app_notify 回调开始'.'request:'.json_encode($request));
        //require_once DOCUMENT_ROOT . '/system/pay/wechat/fun.php';
        //查询订单
        $order_info = db('user_charge_log')->where('order_id', '=', $request["orderid"])->find();

        $pay_info = db('pay_menu')->find($order_info['pay_type_id']);
        if (!$pay_info) {
            notify_log($request["orderid"], '0', '后台支付通道不存在');
            echo '';
            exit;
        }
        $key = $pay_info['md5_key'];
        $data = array();
        $data['memberid'] = $request["memberid"];
        $data['orderid'] = $request["orderid"];
        $data['amount'] = $request["amount"];
        $data['transaction_id'] = $request["transaction_id"];
        $data['datetime'] = $request["datetime"];
        $data['returncode'] = $request["returncode"];

        $sign = $request["sign"];

        $my_sign = $this->sign($data, $key);

        Log::info('mao_pay_app_notify:'.'data='.json_encode($data));
        Log::info('mao_pay_app_notify:'.'my_sign='.$my_sign);
        Log::info('mao_pay_app_notify:'.'sign='.$sign);
        $realmoney = floatval($data['amount']);
        $verify = $sign == $my_sign;
        Log::info('mao_pay_app_notify:'.'$verify:'.$verify);

        // file_put_contents(DOCUMENT_ROOT . "/public/sign" . date("Y-m-dHis") . ".txt", http_build_query($data) . '&key=' . $key);
        if ($verify) {
            if ($data['returncode'] == "00") {

                //可在此处增加操作数据库语句，实现自动下发，也可在其他文件导入该php，写入数据库
                pay_call_service_1023($data['orderid'],$realmoney);
                //echo "商户收款成功，订单正常完成！";
                echo 'OK';
                     }
        } else {
            Log::info('mao_pay_app_notify verify is false ');
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