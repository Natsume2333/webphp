<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/28
 * Time: 10:01
 */

class kuaijiealipay_app_pay
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
            'trade_amount'          => intval($rule['money']),
            'goods_name'            => "虚拟币充值",
            'goods_desc'            => "虚拟币充值",
            'user_ip'               => get_wx_ip(),
            'sign_type'             => 1
        );

        //签名
        $sign = local_sign($param, $key);

        $param['sign'] = $sign;

        $res = getdata("http://fe97396347.api.kj-pay.com/alipay/wap_pay", $param);

        $list = json_decode($res, true);
        //print_r($list);
        $pay_info['post_url'] = $list['data']['pay_url'];       //生成指定网址
        $pay_info['is_wap']  = 1;
        return $pay_info;
    }


    public function notify($request){

        require_once DOCUMENT_ROOT . '/system/pay/kuaijie/common.php';
        //查询订单
        $order_info = db('user_charge_log') -> where('order_id','=', $request["merchant_order_no"]) -> find();

        $pay_info = db('pay_menu') -> find($order_info['pay_type_id']);
        if(!$pay_info){
            notify_log( $request["merchant_order_no"],'0','充值回调成功,error:充值渠道不存在');
            echo 'success';
            exit;
        }
        $key = $pay_info['private_key'];
        $status = $request["status"];
        $msg = $request["msg"];
        $amount = $request["amount"];
        $merchant_order_no = $request["merchant_order_no"];
        $trade_no = $request["trade_no"];
        $payment_time = $request["payment_time"];
        $pay_channel = $request["pay_channel"];
        $pay_channel_name = $request["pay_channel_name"];
        //$attach = $request["attach"];
        $sign = $request["sign"];

        $sign_data = [
            'status' => $status,
            'msg' => $msg,
            'amount' => $amount,
            'merchant_order_no' => $merchant_order_no,
            'trade_no' => $trade_no,
            'payment_time' => $payment_time,
            'pay_channel' => $pay_channel,
            'pay_channel_name' => $pay_channel_name,
        ];

        $my_sign = local_sign($request, $key);

        file_put_contents(DOCUMENT_ROOT."/public/sign".date("Y-m-dHis").".txt",http_build_query($sign_data).'&key='.$key);
        if($my_sign == $sign){
            if($status == "Success"){
                //可在此处增加操作数据库语句，实现自动下发，也可在其他文件导入该php，写入数据库
                pay_call_service($merchant_order_no);
                //echo "商户收款成功，订单正常完成！";
            }
        }

        echo 'success';
        exit;
    }
}