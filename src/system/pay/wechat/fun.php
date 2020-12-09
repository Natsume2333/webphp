<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/7/10
 * Time: 15:52
 */

function sign($param,$key){

    $sign = "";
    foreach($param as $k => $v){
        $sign .= $k."=".$v."&";
    }

    $sign .= "key=".$key;
    $sign = strtoupper(md5($sign));
    return $sign;

}


function checkSign($sign1,$sign2){
    return trim($sign1) == trim($sign2);
}

function xmlToArray($xmlStr){
    $msg = array();
    $postStr = $xmlStr;
    $msg = (array)simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);

    return $msg;
}


function returnInfo($type,$msg){

    if($type == "SUCCESS"){
        return $returnXml = "<xml><return_code><![CDATA[{$type}]]></return_code></xml>";
    }else{
        return $returnXml = "<xml><return_code><![CDATA[{$type}]]></return_code><return_msg><![CDATA[{$msg}]]></return_msg></xml>";
    }
}