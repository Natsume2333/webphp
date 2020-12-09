<?php
ini_set('arg_separator.output','&');
use think\Log;
class kefuonline_app_pay
{




    public function get_payment_code($pay, $rule, $order_id, $param)
    {
        /* $pay_info['post_url'] = SITE_URL . '/mapi/public/index.php/api/pay_api/pay_display_html_param?os=' . $param['os'] . '&sdk_version=' . $param['sdk_version'] . '&app_version=' . $param['app_version'] . '&brand=' . $param['brand'] . '&model=' . $param['model'] . '&uid=' . $param['uid'] . '&token=' . $param['token'] . '&pid=' . $param['pid'] . '&rid=' . $param['rid'];
        $pay_info['is_wap'] = 1;
        return $pay_info;
    }
    public function pay_display_html_param($pay, $rule, $order_id, $param)
    {
        Log::info('huanshang_pay_app_pay:pay_display_html_param 进入');
        $t_script = '<script>window.location.href=\"' . $pay['public_key'] . '\";</script>';
        echo $t_script;
        exit(); */

            //urlencode($pay['public_key']);
        //()

        $url = str_replace("&amp;","&",$pay['public_key']);

        Log::info( "eeeeeeeettt:".$url);

        $pay_info['post_url'] =  $url; //生成指定网址
        $pay_info['is_wap'] = 1;
        return $pay_info;

    }



}