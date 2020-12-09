<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/8/27
 * Time: 11:33
 */


//返回错误
function jsonError($message = '',$url=null)
{
    $return['msg'] = $message;
    $return['data'] = '';
    $return['code'] = -1;
    $return['url'] = $url;
    return json_encode($return);
}

//返回正确
function jsonSuccess($message = '',$data = '',$url=null)
{
    $return['msg']  = $message;
    $return['data'] = $data;
    $return['code'] = 1;
    $return['url'] = $url;
    return json_encode($return);
}