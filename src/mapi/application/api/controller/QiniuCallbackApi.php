<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2019/6/20
 * Time: 23:23
 */

/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/2/28
 * Time: 16:26
 */

namespace app\api\controller;


use think\Config;
use think\Db;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

class QiniuCallbackApi extends Base
{

    //七牛云图片审核回调
    public function callback()
    {
        $request_data = file_get_contents("php://input");
        $data_array = json_decode($request_data, true);
        $label = $data_array['items'][0]['result']['result']['scenes']['pulp']['result']['label'];

        if (strpos($data_array['inputKey'], 'screenshot_img') !== false) {
            $img_name_info = explode('_', $data_array['inputKey']);
            $user_id = $img_name_info[count($img_name_info) - 2];

            if ($label == 'pulp') {

                // 要上传的空间
                $bucket = Config::get('qiniu.bucket');
                $domain = Config::get('qiniu.DOMAIN');

                //插入违规记录表
                $insert_record = [
                    'user_id' => $user_id,
                    'img_url' => $domain . '/' . $data_array['inputKey'],
                    'create_time' => NOW_TIME
                ];

                $config = load_cache('config');

                $ext = array();

                //查询违规记录
                $count = db('screenshots_foul_record')->where('user_id=' . $user_id)->count();
                if ($count > ($config['violation_max_count'] - 1)) {
                    $insert_record['handle'] = 1;
                    $ext['action'] = 0;
                    $ext['msg_content'] = '系统检测到您视频涉嫌违规，因发现两次违规系统自动做封号处理！';

                    //封号操作 分钟
                    $time = $config['violation_black_time'];
                    $end_time = $time * 60 + NOW_TIME;
                    db('user')->where('id=' . $user_id)->setField('shielding_endtime', $end_time);
                    db('user')->where('id=' . $user_id)->setField('user_status', 0);
                } else {
                    $insert_record['handle'] = 0;
                    $ext['action'] = 1;
                    $ext['msg_content'] = '系统检测到您视频涉嫌违规，请调整，再次检测到将做封号处理';
                }

                db('screenshots_foul_record')->insert($insert_record);

                $ext['type'] = 25; //type 25 关闭视频通话

                $config = load_cache('config');
                require_once DOCUMENT_ROOT . '/system/im_common.php';
                $ser = open_one_im_push($config['tencent_identifier'], $user_id, $ext);
            }

            file_put_contents('./qiniu_callback.txt', $label . '&' . $data_array['inputKey'] . '&' . $user_id);
        }
    }

}