<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/28
 * Time: 10:01
 */

class kuaijieweixinpay_app_pay
{
    public function get_payment_code($pay,$rule,$order_id)
    {

        require_once DOCUMENT_ROOT . '/system/pay/kuaijie/common.php';
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $order_id;

        //商户号
        $merchant_no = $pay['merchant_id'];
        //密钥
        $key         = $pay['private_key'];

        //请求的参数
        $param = array(
            'merchant_no'           => $merchant_no,
            'merchant_order_no'     => $out_trade_no,
            //异步通知的地址
            'notify_url'            => SITE_URL . "/mapi/public/index.php/api/notify_api/kuaijie_notify",

            //同步跳转的地址
            //'return_url'            => 'http://www.kj-pay.com',

            'start_time'            => date('YmdHis'),
            'trade_amount'          => number_format($rule['money'], 2),
            'goods_name'            => "虚拟币充值",
            'goods_desc'            => "虚拟币充值",
            'user_ip'               => get_wx_ip(),
            'pay_sence'             => '{"type":"Wap","wap_url":"https://www.kk30.com","wap_name":"快快网络"}',
            'sign_type'             => 1
        );

        //签名
        $sign = local_sign($param, $key);

        $param['sign'] = $sign;

        $res = getdata("http://fe97396347.api.kj-pay.com/wechar/wap_pay", $param);

        $list = json_decode($res, true);
        //print_r($list);
        $pay_info['post_url'] = $list['data']['pay_url'];       //生成指定网址
        $pay_info['is_wap']  = 1;
        return $pay_info;
    }


}