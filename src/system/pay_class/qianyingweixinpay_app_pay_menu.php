<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/28
 * Time: 10:01
 */

class qianyingweixinpay_app_pay
{
    public function get_payment_code($pay,$rule,$order_id)
    {

        $shop_id = $pay['merchant_id'];         //商户ID，商户在千应官网申请到的商户ID
        $bank_Type = 102;   //充值渠道，101表示支付宝快速到账通道
        $bank_payMoney = intval($rule['money']);     //充值金额
        $orderid = $order_id;                  //商户的订单ID，【请根据实际情况修改】
        $callbackurl = SITE_URL . "/mapi/public/index.php/api/notify_api/qianying_notify";        //商户的回掉地址，【请根据实际情况修改】
        $gofalse = "http://www.qianyingnet.com/pay";                    //订单二维码失效，需要重新创建订单时，跳到该页
        $gotrue = "http:/www.qianyingnet.com/";                         //支付成功后，跳到此页面
        $key = $pay['public_key'];                      //密钥
        $posturl = 'http://www.qianyingnet.com/pay/';                   //千应api的post提交接口服务器地址

        $charset = "utf-8";                                              //字符集编码方式
        $token = "wu";                                                 //自定义传过来的值 千应平台会返回原值
        $parma ='uid='.$shop_id.'&type='.$bank_Type.'&m='.$bank_payMoney.'&orderid='.$orderid.'&callbackurl='.$callbackurl;     //拼接$param字符串
        $parma_key = md5($parma . $key);                                 //md5加密
        $pay_info['post_url'] = $posturl."?".$parma."&sign=".$parma_key."&gofalse=".$gofalse."&gotrue=".$gotrue."&charset=".$charset."&token=".$token;       //生成指定网址
        $pay_info['is_wap']  = 1;
        return $pay_info;
    }


    public function notify($request){

        //查询订单
        $order_info = db('user_charge_log') -> where('order_id','=', $request["oid"]) -> find();

        $pay_info = db('pay_menu') -> find($order_info['pay_type_id']);
        if(!$pay_info){
            notify_log( $request["oid"],'0','充值回调成功,error:充值渠道不存在');
            echo 'fail';
            exit;
        }
        $key = $pay_info['public_key'];          //商户密钥，千应官网注册时密钥
        $orderid = $request["oid"];        //订单号
        $status = $request["status"];      //处理结果：【1：支付完成；2：超时未支付，订单失效；4：处理失败，详情请查看msg参数；5：订单正常完成（下发成功）；6：补单；7：重启网关导致订单失效；8退款】
        $money = $request["m1"];            //实际充值金额
        $sign = $request["sign"];          //签名，用于校验数据完整性
        $orderidMy = $request["oidMy"];    //千应录入时产生流水号，建议保存以供查单使用
        $orderidPay = $request["oidPay"];  //收款方的订单号（例如支付宝交易号）;
        $completiontime = $request["time"];//千应处理时间
        $attach = $request["token"];       //上行附加信息
        $param="oid=".$orderid."&status=".$status."&m=".$money.$key;  //拼接$param

        $paramMd5 = md5($param);          //md后加密之后的$param

        if(strcasecmp($sign,$paramMd5)==0){
            if($status == "1" || $status == "5" || $status == "6"){

                //可在此处增加操作数据库语句，实现自动下发，也可在其他文件导入该php，写入数据库
                pay_call_service($orderid);
                echo "商户收款成功，订单正常完成！";
            }
            else if($status == "4"){
                echo "订单处理失败，因为";
            }
            else if ($status == "8")
            {
                echo "订单已经退款！";
            }
        }else{
            echo "签名无效，视为无效数据!";
        }
    }
}