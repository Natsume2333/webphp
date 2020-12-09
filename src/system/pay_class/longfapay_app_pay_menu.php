<?php
use think\Log;
class longfapay_app_pay
{
    protected $name = '隆发支付';
    protected $gateway = "http://pay.longfapay.com:88/api/pay";
   // private $pay_public_key;
   // private $remit_public_key;
   // private $private_key_a;

    public function get_payment_code($pay, $rule, $order_id)
    {
            //require_once DOCUMENT_ROOT . '/system/pay/kuaijie/common.php';
            //商户订单号，商户网站订单系统中唯一订单号，必填

        Log::info('longfapay_app_pay： 开始下单 '.date("Y-m-d H:i:s"));
            $out_trade_no = $order_id;

            //商户号
            $merchant_no = $pay['merchant_id'];
            //密钥
            $md5key = $pay['md5_key'];
            $public_key = $pay['public_key'];


            //$keys = explode('|', $private_key);
           // $key_a = $keys['0'];
            //$key_b = $keys['1'];
            $type = $pay['pay_type']; //代用渠道编码
            //请求的参数
            //$Rsa = new Rsa();
            $data = array();
            $data['merchNo'] = "".$merchant_no;
            $data['netwayType'] = "".$type;
            $data['randomNo'] = "".time();
            $data['orderNo'] = "".$out_trade_no;
            $data['amount'] = "".floatval($rule['money'] * 100);
            $data['goodsName'] = '虚拟币充值';
            $data['notifyUrl'] = SITE_URL . "/mapi/public/index.php/api/hui_api/longfa_notify";;
            $data['notifyViewUrl'] = 'http://www.kj-pay.com';

            $data['sign'] = $this->sign($data, $md5key);

            Log::info(date("Y-m-d H:i:s").'longfapay_app_pay： '.'sign= '.$data['sign']);
            Log::info(date("Y-m-d H:i:s").'longfapay_app_pay： '.'data= '.json_encode($data));
            $pubStr = $this->pubStr($public_key);
           // Log::info(date("Y-m-d H:i:s").'longfapay_app_pay： '.'$pubStr= '.$pubStr);
            $json = $this->json_encode_ex($data);


            $dataStr = $this->encode_pay($json, $pubStr);
            $version = 'V3.6.0.0';
            $param['data'] = $dataStr;
            $param['merchNo'] = $merchant_no;
            $param['version'] = $version;
            //'data=' . urlencode($dataStr) . '&merchNo=' . $merchant_no . '&version=' . $version;
           // Log::info(date("Y-m-d H:i:s").'longfapay_app_pay： '.'$param= '.json_encode($param));

            $res = $this->curlPost($this->gateway, $param);
            //save_log('pay_longfapay_pay'.'res:'.$res);
            $list = json_decode($res, true);
            Log::info(date("Y-m-d H:i:s").'longfapay_app_pay： '.'$res = '.$res);
            //print_r($list);
            $sign_a = $list['sign'];
            $verify = $this->verify($list, $md5key, $sign_a);
            //save_log('error' . 'pay_longfapay_pay' . 'verify:' . $verify);
            if ($verify && $list['stateCode'] == "00") {
                $pay_info['post_url'] = $list['qrcodeUrl'];       //生成指定网址
                $pay_info['is_wap'] = 1;

                return $pay_info;
                //save_log('pay_longfapay_pay'.'pay_info'.json_encode($pay_info));
            } else {
                $pay_info['code'] = "0";
                $pay_info['msg'] = '充值失败！';
                return $pay_info;
                //echo "错误代码：" . $list['stateCode'] . ' 错误描述:' . $list['msg'];
            }
    }


    public function notify($request)
    {

        //require_once DOCUMENT_ROOT . '/system/pay/kuaijie/common.php';
        //require_once DOCUMENT_ROOT . '/mapi/application/common.php';
        //查询订单
        Log::info(date("Y-m-d H:i:s").'longfapay_notify : '."回调开始");
        $order_info = db('user_charge_log')->where('order_id', '=', $request["orderNo"])->find();

        $pay_info = db('pay_menu')->find($order_info['pay_type_id']);
        if (!$pay_info) {
            notify_log($request["orderNo"], '0', '充值回调成功,error:充值渠道不存在');
            echo 'success';
            exit;
        }
        Log::info(date("Y-m-d H:i:s").'longfapay_notify : '.'request : '.json_encode($request));
        $md5key = $pay_info['md5_key'];
        $private_key = $pay_info['private_key'];
       // $keys = explode('|',$private_key);
        //$key_a = $keys['0'];
        //$key_b = $keys['1'];
        $priStr = $this->priStr($private_key);
            //Log::info(date("Y-m-d H:i:s").'longfapay_notify : '.'request = '.$request);
            $data = $request['data'];
            $data= $this->decode($data,$priStr);
            Log::info(date("Y-m-d H:i:s").'longfapay_notify : '.'data = '.$data);
           // save_log('pay_longfapay_notify'.'data'.$data);
            $param = $this->json_decode_ex($data);
            $amount = floatval($param['amount']/100);
            $signature = $param['sign'];
            $verify = $this->verify($param,$md5key,$signature);
            //file_put_contents(DOCUMENT_ROOT . "/public/sign" . date("Y-m-dHis") . ".txt", http_build_query($param) . '&key=' . $md5key);
            if ($verify){
                Log::info(date("Y-m-d H:i:s").'longfapay_notify : '.'$verify = '.$verify);
                pay_call_service_1023($param['orderNo'],$amount);
                echo 'SUCCESS';
                exit;
            } else{
            Log::info('longfapay_app_notify verify is false '.date("Y-m-d H:i:s"));
            exit();
            }
          //  save_log('pay_longfapay_notify'.'verify:'.$verify);
    }
    public  function curlPost($url,$data){
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; MSIE 5.01; Windows NT 5.0)');
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_AUTOREFERER, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        Log::info(date("Y-m-d H:i:s").'longfapay_notify : '.'$data: '.http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $tmpInfo = curl_exec($ch);
        if (curl_errno($ch)) {
            return curl_error($ch);
        }
        return $tmpInfo;
    }


    public function sign($data,$key){
        ksort($data);
        $sign = strtoupper(md5($this->json_encode_ex($data) . $key));
        //save_log('pay_longfapay_model'.'sign:'.$sign);
        Log::info(date("Y-m-d H:i:s").'longfapay_app_pay:  '.'signstr = '.$this->json_encode_ex($data) . $key);
        return $sign;
    }
    public function verify($params, $key, $signature)
    {
        unset($params["sign"]);
        $sign = $this->sign($params, $key);
        //$this->log_message('error', 'Pay_longfapay_model' . 'sign: ' . $sign);
        //$this->log_message('error', 'Pay_longfapay_model' . 'signature: ' . $signature);

        return strtolower($sign) == strtolower($signature);
    }
    public function json_encode_ex($value){
        if (version_compare(PHP_VERSION,'5.4.0','<')){
            $str = json_encode($value);
            $str = preg_replace_callback("#\\\u([0-9a-f]{4})#i","replace_unicode_escape_sequence",$str);
            $str = stripslashes($str);
            return $str;
        }else{
            return json_encode($value,320);
        }
    }

    public function json_decode_ex($value){
        return json_decode($value,true);
    }

    public function replace_unicode_escape_sequence($match) {
        return mb_convert_encoding(pack('H*', $match[1]), 'UTF-8', 'UCS-2BE');
    }
    public function encode_pay($data,$public_key){#加密
        $pu_key = $public_key;
        if ($pu_key == false){
            echo "打开密钥出错";
            die;
        }
        $encryptData = '';
        $crypto = '';
        foreach (str_split($data, 117) as $chunk) {
            openssl_public_encrypt($chunk, $encryptData, $pu_key);
            $crypto = $crypto . $encryptData;
        }

        $crypto = base64_encode($crypto);
        return $crypto;

    }



    public function decode($data,$private_key){
        $pr_key = $private_key;
        if ($pr_key == false){
            echo "打开密钥出错";
            die;
        }
        $data = base64_decode($data);
        $crypto = '';
        foreach (str_split($data, 128) as $chunk) {
            openssl_private_decrypt($chunk, $decryptData, $pr_key);
            $crypto .= $decryptData;
        }
        return $crypto;
    }
    public function pubStr($public_key){
        //$pay_public_key = "";
        $pay_public_key = "-----BEGIN PUBLIC KEY-----\r\n";
        foreach (str_split($public_key,64) as $str){
            $pay_public_key = $pay_public_key . $str . "\r\n";
        }
        $pay_public_key = $pay_public_key . "-----END PUBLIC KEY-----";
        return $pay_public_key;
    }
    public function priStr($private_key){
        $private_key_a = "-----BEGIN RSA PRIVATE KEY-----\r\n";
        foreach (str_split($private_key,64) as $str){
            $private_key_a = $private_key_a . $str . "\r\n";
        }
        $private_key_a = $private_key_a . "-----END RSA PRIVATE KEY-----";
        return $private_key_a;

    }
    public function callback_to_array($json,$key)
    {
        $array = $this->json_decode_ex($json);
        $sign_string = $array['sign'];
        ksort($array);
        $sign_array = array();
        foreach ($array as $k => $v) {
            if ($k !== 'sign') {
                $sign_array[$k] = $v;
            }
        }

        $md5 = strtoupper(md5($this->json_encode_ex($sign_array) . $key));
        if ($md5 == $sign_string) {
            return $sign_array;
        } else {
            $result = array();
            $result['payStateCode'] = '99';
            $result['msg'] = '返回签名验证失败';
            return $result;
        }
    }

}