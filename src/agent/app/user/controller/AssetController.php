<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: kane <chengjin005@163.com>
// +----------------------------------------------------------------------
namespace app\user\controller;

use cmf\controller\AdminBaseController;
use cmf\lib\Upload;
use think\config;
//引入七牛云的相关文件
use Qiniu\Auth as Auth;
use Qiniu\Storage\BucketManager;
use Qiniu\Storage\UploadManager;
use think\db;
/**
 * 附件上传控制器
 * Class Asset
 * @package app\asset\controller
 */
class AssetController extends AdminBaseController
{
    public function _initialize()
    {
        $adminId = cmf_get_current_admin_id();
        $userId  = cmf_get_current_user_id();
        if (empty($adminId) && empty($userId)) {
            exit("非法上传！");
        }
    }

    /**
     * webuploader 上传
     */
    public function webuploader()
    {
        if ($this->request->isPost()) {

            $uploader = new Upload();

            $result = $uploader->upload();

               // var_dump($result);
            if ($result === false) {
                $this->error($uploader->getError());
            } else {

                   $this->success("上传成功!", '', $result);


            }

        } else {
            $uploadSetting = cmf_get_upload_setting();

            $arrFileTypes = [
                'image' => ['title' => 'Image files', 'extensions' => $uploadSetting['file_types']['image']['extensions']],
                'video' => ['title' => 'Video files', 'extensions' => $uploadSetting['file_types']['video']['extensions']],
                'audio' => ['title' => 'Audio files', 'extensions' => $uploadSetting['file_types']['audio']['extensions']],
                'file'  => ['title' => 'Custom files', 'extensions' => $uploadSetting['file_types']['file']['extensions']]
            ];

            $arrData = $this->request->param();
            if (empty($arrData["filetype"])) {
                $arrData["filetype"] = "image";
            }

            $fileType = $arrData["filetype"];

            if (array_key_exists($arrData["filetype"], $arrFileTypes)) {
                $extensions                = $uploadSetting['file_types'][$arrData["filetype"]]['extensions'];
                $fileTypeUploadMaxFileSize = $uploadSetting['file_types'][$fileType]['upload_max_filesize'];
            } else {
                $this->error('上传文件类型配置错误！');
            }

            $this->assign('filetype', $arrData["filetype"]);
            $this->assign('extensions', $extensions);
            $this->assign('upload_max_filesize', $fileTypeUploadMaxFileSize * 1024);
            $this->assign('upload_max_filesize_mb', intval($fileTypeUploadMaxFileSize / 1024));
            $maxFiles  = intval($uploadSetting['max_files']);
            $maxFiles  = empty($maxFiles) ? 20 : $maxFiles;
            $chunkSize = intval($uploadSetting['chunk_size']);
            $chunkSize = empty($chunkSize) ? 512 : $chunkSize;
            $this->assign('max_files', $arrData["multi"] ? $maxFiles : 1);
            $this->assign('chunk_size', $chunkSize); //// 单位KB
            $this->assign('multi', $arrData["multi"]);
            $this->assign('app', $arrData["app"]);

            return $this->fetch(":webuploader");

        }
    }
//    //七牛上传 $file文件
//    function qiniu_Upload($file){
//        //$file = request()->file('file');
//        // 要上传图片的本地路径
//        $filePath="upload/".$file['filepath'];
//
//     //   $filePath = $file->getRealPath();
//     //   $ext = pathinfo($file->getInfo('name'), PATHINFO_EXTENSION);  //后缀
//        // 上传到七牛后保存的文件名
//       // $key=substr($file['preview_url'],strpos($file['preview_url'],'.com/')+1);
//        $array=explode('com/', $file['preview_url']);
//        $key="'".$array[1]."'";
//      //  $key =substr(md5($file->getRealPath()) , 0, 5). date('YmdHis') . rand(0, 9999) . '.' . $ext;
//        require_once APP_PATH . '../simplewind/vendor/qiniu/autoload.php';
//        // 需要填写你的 Access Key 和 Secret Key
//        $asse=Db::name("plugin")->where("has_admin=0 and status=1 and name='Qiniu'")->field("config")->find();
//      //  json_encode($asse);
//        $asse=json_decode($asse['config'],true);
//     //   var_dump($asse['accessKey']);exit;
//
//        $accessKey =$asse['accessKey'];
//
//        $secretKey = $asse['secretKey'];
//        // 构建鉴权对象
//        $auth = new Auth($accessKey, $secretKey);
//        // 要上传的空间
//        $bucket = trim($asse['bucket']);
//        $domain = $asse['domain'];
//
//        $token = $auth->uploadToken($bucket);
//
//        // 初始化 UploadManager 对象并进行文件的上传
//        $uploadMgr = new UploadManager();
//
//        // 调用 UploadManager 的 putFile 方法进行文件的上传
//        list($ret, $err) = $uploadMgr->putFile($token, $key, $filePath);
//
//        if ($err !== null) {
//           // $result=array("err"=>0,"msg"=>$err,"data"=>"");
//              return '';
//        } else {
////            $result=array("err"=>1,"msg"=>"上传完成","data"=>array('filepath'=>$domain .'/'. $ret['key']));
//            $result=array('filepath'=>$domain .'/'. $ret['key'],'name'=>$ret['key']);
//
//            //返回图片的完整URL
//            return $result;
//        }
//    }

}
