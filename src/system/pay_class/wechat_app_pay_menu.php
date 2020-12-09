<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/6/22
 * Time: 17:34
 */

class wechat_app_pay
{
    public function get_payment_code($pay,$rule,$notice_id)
    {
        $order_id = $notice_id;
        $good_name = $rule['name'];
        $money = $rule['money'];
        $good_name = '虚拟币充值';

        header("Content-type: text/html; charset=utf-8");

        require_once DOCUMENT_ROOT . '/system/pay/wechat/fun.php';

        $noceStr = md5(rand(100,1000).time());//获取随机字符串
        $mch_id = $pay['merchant_id'];
        $key = trim($pay['private_key']);
        $money = $money * 100;

        $paramarr = array(
            "appid"       =>    trim($pay['app_id']),
            "body"        =>    $good_name,//说明内容
            "mch_id"      =>    $mch_id,
            "nonce_str"   =>    $noceStr,
            "notify_url"  =>    SITE_URL . "/mapi/public/index.php/api/notify_api/wechatpay_notify",
            "out_trade_no"=>    $order_id,
            "total_fee"   =>    $money,
            "trade_type"  =>    "APP"
        );
       
        $sign = sign($paramarr,$key);//生成签名
        $paramarr['sign'] = $sign;
        $paramXml = "<xml>";
        foreach($paramarr as $k => $v){
            $paramXml .= "<" . $k . ">" . $v . "</" . $k . ">";

        }
        $paramXml .= "</xml>";
        //file_put_contents("./test.txt", "");
        //file_put_contents("./test.txt", $paramXml);
        $ch = curl_init ();
        @curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // 跳过证书检查
        @curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, true);  // 从证书中检查SSL加密算法是否存在
        @curl_setopt($ch, CURLOPT_URL, "https://api.mch.weixin.qq.com/pay/unifiedorder");
        @curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        @curl_setopt($ch, CURLOPT_POST, 1);
        @curl_setopt($ch, CURLOPT_POSTFIELDS, $paramXml);
        @$resultXmlStr = curl_exec($ch);
        if(curl_errno($ch)){
            print curl_error($ch);
        }
        curl_close($ch);
        file_put_contents("./test2.txt", "");
        file_put_contents("./test2.txt", $resultXmlStr);
        $result = xmlToArray($resultXmlStr);
        $time2 = time();
        $prepayid = $result['prepay_id'];
        $sign = "";
        $noceStr = md5(rand(100,1000).time());//获取随机字符串
        $paramarr2 = array(
            "appid"     =>  trim($pay['app_id']),
            "noncestr"  =>  $noceStr,
            "package"   =>  "Sign=WXPay",
            "partnerid" =>  $mch_id,
            "prepayid"  =>  $prepayid,
            "timestamp" =>  $time2

        );
        $paramarr2["sign"] = sign($paramarr2,$key);//生成签名
        $pay_info['is_wap']  = 0;
        $pay_info['type'] = 2;
        $pay_info['pay_info'] = $paramarr2;
        //file_put_contents('./alipay.txt',$response);
        return $pay_info;

    }

    public function notify($request){

        libxml_disable_entity_loader(true);

        require_once DOCUMENT_ROOT . '/system/pay/wechat/fun.php';
        $xmlInfo = $GLOBALS['HTTP_RAW_POST_DATA'];

        $arrayInfo = xmlToArray($xmlInfo);

        //查询订单
        $order_info = db('user_charge_log') -> where('order_id','=', $arrayInfo["out_trade_no"]) -> find();

        $pay_info = db('pay_menu') -> find($order_info['pay_type_id']);

        /* 测试数据打印log数组转字符串=============== */
        $test = "";
        foreach($arrayInfo as $k => $v){
            $test .= $k.":".$v."\t\r\n";
        }

        //file_put_contents(DOCUMENT_ROOT."/public/wechat_ealipay_".date("Y-m-dHis").".txt",print_r($arrayInfo,true));
        if($arrayInfo['return_code'] == "SUCCESS"){

            $wxSign = $arrayInfo['sign'];
            unset($arrayInfo['sign']);
            $arrayInfo['appid']  = $pay_info['app_id'];
            $arrayInfo['mch_id'] = $pay_info['merchant_id'];
            ksort($arrayInfo);//按照字典排序参数数组
            $sign = sign($arrayInfo,$pay_info['private_key']);//生成签名

            //file_put_contents(DOCUMENT_ROOT."/public/wechat2_ealipay_".date("Y-m-dHis").".txt",$sign . '----' . $wxSign);
            if(checkSign($wxSign,$sign)){
                pay_call_service($arrayInfo["out_trade_no"]);
                returnInfo("SUCCESS","OK");
            }else{
                returnInfo("FAIL","签名失败");
            }
        }else{
            returnInfo("FAIL","签名失败");
        }
    }
}