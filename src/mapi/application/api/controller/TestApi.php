<?php
/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2019/2/20
 * Time: 15:49
 */

namespace app\api\controller;

use Qiniu\Auth;
use Qiniu\Rtc\AppClient;
use think\Config;

class TestApi extends Base
{
    public function test163()
    {
        $sign = get163yunSing(100231);
        if ($sign['code'] != 200) {
            $result['code'] = 0;
            $result['msg'] = 'sign获取失败 错误code:¬' . $sign['code'];
            return_json_encode($result);
        }

        return_json_encode($sign);

    }

    public function testQiniu()
    {

        require_once DOCUMENT_ROOT . '/system/qiniu/autoload.php';
        // 需要填写你的 Access Key 和 Secret Key
        $accessKey = Config::get('qiniu.accessKey');
        $secretKey = Config::get('qiniu.secretKey');

        // 构建鉴权对象
        $auth = new Auth($accessKey, $secretKey);
        $app_client = new AppClient($auth);
        $sign = $app_client->appToken("e9sbe5vlz", "test101372", "101372", NOW_TIME + 30, 'user');

    }
}