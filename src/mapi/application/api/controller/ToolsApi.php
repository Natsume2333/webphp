<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2019/2/20
 * Time: 15:49
 */

namespace app\api\controller;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ CUCKOO ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

use AipImageCensor;

class ToolsApi extends Base
{
    //判断图片是否合规
    public function check_img_compliance(){

        $result = array('code' => 1, 'msg' => '');

        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file('image');

        // 移动到框架应用根目录/public/uploads/ 目录下
        if ($file) {
            $file_info = $file->validate(['ext' => 'png'])->move(ROOT_PATH . 'public' . DS . 'uploads' . DS . 'check_h');
            if ($file_info) {
                // 输出 20160820/42a79759f284b767dfcb2a0197904287.jpg
                $img_path = '/public' . DS . 'uploads' . DS . 'check_h/' . $file_info->getSaveName();
            } else {
                // 上传失败获取错误信息
                $result['code'] = 0;
                $result['msg'] = $file->getError();
                return_json_encode($result);
            }
        }else{
            $result['msg'] = '图片上传错误!';
            $result['code'] = 0;
            return_json_encode($result);
        }

        require_once(DOCUMENT_ROOT . '/system/baidu_api/AipImageCensor.php');

        // 你的 APPID AK SK
        $APP_ID = '15594063';
        $API_KEY = '1f3xenn2xbNCQIbuk40zvKQX';
        $SECRET_KEY = 'drqc8p1LETzGMTYbXevP4Ik1j4y0ve0V';

        //调用鉴黄接口
        $client = new AipImageCensor($APP_ID, $API_KEY, $SECRET_KEY);

        $check_result = $client->imageCensorUserDefined(file_get_contents($img_path));

        if($check_result['conclusion'] == '合规'){
            return_json_encode($result);
        }

        if($check_result['type'] == 1 && $check_result['probability'] > 0.9){
            $result['code'] = 10099;
            $result['msg'] = '内容包含色情内容！';
            return_json_encode($result);
        }

        return_json_encode($result);
    }
}