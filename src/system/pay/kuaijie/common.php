<?php
/**
 * +----------------------------------------------------------------------------
 * 快接支付-微信WAP支付(H5)的PHP-demo示例 （注：其他接口的请求方式和签名规则都是通用的。）
 * +----------------------------------------------------------------------------
 * @author gd <464364696@qq.com>
 * @version v0.1.0 Build 2018.03.08
 * +------------------------------------------------------------------------------
 */

/**
 * 获取IP地址
 * return ip
 */
function get_wx_ip(){
    $ip = "unknown";
    if(isset($_SERVER['HTTP_X_FORWARDED_FOR'])){
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    }elseif(isset($_SERVER['HTTP_CLIENT_IP'])){
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    }elseif(isset($_SERVER['REMOTE_ADDR'])){
        $ip = $_SERVER['REMOTE_ADDR'];
    }elseif(getenv("REMOTE_ADDR")){
        $ip = getenv("REMOTE_ADDR");
    }
    return $ip;
}

/**
 * 除去数组中的空值和签名参数
 * @param $para 签名参数组
 * return 去掉空值与签名参数后的新签名参数组
 */
function paraFilters($para) {
    $para_filter = array();
    while (list ($key, $val) = each ($para)) {
        if($key == "sign" || $val == "")continue;
        else	$para_filter[$key] = $para[$key];
    }
    return $para_filter;
}

/**
 * 对数组排序
 * @param $para 排序前的数组
 * return 排序后的数组
 */
function argSorts($para) {
    ksort($para);
    reset($para);
    return $para;
}

/**
 * 签名验证-快接支付
 * $datas 数据数组
 * $key 密钥
 */
function local_sign($datas = array(), $key = ''){
    $str = urldecode(http_build_query(argSorts(paraFilters($datas))));
    $sign = md5($str."&key=".$key);
    return $sign;
}

/**
 * 签名验证-快接支付
 * $datas 数据数组
 * $key 密钥
 */
function getdata($url, $param){
    $content = '';
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER,true);
    curl_setopt($ch, CURLOPT_HEADER, false);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    curl_setopt($ch, CURLOPT_POSTFIELDS, $param);

    curl_setopt($ch, CURLOPT_TIMEOUT,6);
    $content = curl_exec($ch);
    curl_close($ch);

    return $content;
}

/*
*=============================================================================================================================
*以下预下单成功响应的json数据
*{
*	data :
*	{
*		trade_no : "K201710281509354698545982",
*		pay_url : "https://wx.tenpay.com/cgi-bin/mmpayweb-bin/checkmweb?prepay_id=wx20171206192330fa68120fe20858600673&package=3508966682&redirect_url=http%3A%2F%2F%2Fh5pay.640game.com%2Fpeipeiuser%2Fhtml%2FpayOK.html",
*		sign : "007321008c37b2a5810166bf185a1694"
*	},
*	info : "预交易下单成功",
*	status : "1"
*}
*注:需要对data里面的字段进行签名验证（sign除外）保证数据的准确性和安全性。签名的方法和请求的方法一致，就不详情去写了......
*=============================================================================================================================
*/

