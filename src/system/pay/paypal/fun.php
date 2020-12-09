<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/10/4
 * Time: 12:23
 */

//获取PayPal的token
function pay_pal_access_token($type = '') {

    $config = load_cache('config');
    if ($type == 1) {
        $clientId = $config['pay_pal_client_id'];
        $secret = $config['pay_pal_secret'];
        // 生产环境
        $url = "https://api.paypal.com/v1/oauth2/token";
    } else {
        $clientId = $config['pay_pal_client_id'];
        $secret = $config['pay_pal_secret'];
        // 沙箱环境
        $url = "https://api.sandbox.paypal.com/v1/oauth2/token";
    }
    $ch = curl_init ();
    curl_setopt ( $ch, CURLOPT_URL, $url );
    curl_setopt ( $ch, CURLOPT_HEADER, false );
    curl_setopt ( $ch, CURLOPT_SSL_VERIFYPEER, false );
    curl_setopt ( $ch, CURLOPT_POST, true );
    curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, true );
    curl_setopt ( $ch, CURLOPT_USERPWD, $clientId . ":" . $secret );
    curl_setopt ( $ch, CURLOPT_POSTFIELDS, "grant_type=client_credentials" );
    $result = curl_exec ( $ch );
    curl_close ( $ch );
    if (empty ( $result )) {
        return false;
    } else {
        return json_decode ( $result, true );
    }
}


// 获取curl订单信息
function pay_pal_get_curl_order($orderid, $token, $type = '') {
    if (empty ( $orderid ) || empty ( $token )) {
        return false;
    }

    if ($type == 1) {
        // 生产环境
        // $URL = "https://api.paypal.com/v1/checkout/orders/$orderid";
        // $URL = "https://api.paypal.com/v1/payments/orders/$orderid";
        $URL = "https://api.paypal.com/v1/payments/payment/$orderid";
    } else {
        // 沙箱环境
        // $URL = "https://api.sandbox.paypal.com/v1/checkout/orders/$orderid";
        // $URL = "https://api.sandbox.paypal.com/v1/payments/orders/$orderid";
        $URL = "https://api.sandbox.paypal.com/v1/payments/payment/$orderid";
    }
    $curl = curl_init ();
    curl_setopt ( $curl, CURLOPT_SSL_VERIFYHOST, '0' );
    curl_setopt ( $curl, CURLOPT_SSL_VERIFYPEER, '0' );

    curl_setopt_array ( $curl, array (
        CURLOPT_URL => $URL,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => "GET",
        CURLOPT_HTTPHEADER => array (
            "Authorization: Bearer {$token}",
            "Content-Type:application/json"
        )
    ) );
    $response = curl_exec ( $curl );
    curl_close ( $curl );
    return json_decode ( $response, true );
}