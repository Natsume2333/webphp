<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/1/29 0029
 * Time: 下午 15:36
 */
namespace app\api\controller;
use app\api\controller\Base;
class FeedbackApi extends Base
{
       /*app*/
      //提价表单
    public function app_buy(){
        $result = array('code' => 0, 'msg' => '上传失败');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $content=trim(input('param.centent'));
        $tel=trim(input('param.tel'));
        
        $img =input('param.img'); //获取图
        $imgs=explode(",",$img);
    
        if (count($imgs) > 4) {
            $result['msg'] = '图片数量最多4张';
            return_json_encode($result);
          }
         if(count($imgs) > 0){
            foreach ($imgs as $k => $v) {
                    $name['img'.$k] = $v;
            }
         }

      
        $user_info = check_login_token($uid, $token);

        if(strlen($content) < 10){
            $result['msg']="请填写10个字以上的描述，以便我们提供更好的帮助";
           return_json_encode($result);
        }
        if(strlen($tel) != 11){
            $result['msg']="手机号不正确";
           return_json_encode($result);
        }
    
     
        $name['content']=$content;
        $name['tel']=$tel;
        $name['uid']=$uid;
        $name['addtime']=time();

        //添加邀请记录
        $feedback=db('feedback')->insert($name);
          
        if($feedback){
            $result['code']=1;
            $result['msg']="提交成功，感谢您的意见";
        }

      return_json_encode($result);

    }



/*h5*/
    public function index() {
        $result = array('code' => 1, 'msg' => '提交失败');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

    //    $uid='100163';
     //   $token='ff290e2b28cd3921fc569674126f7ee6';

        if ($uid == 0 || empty($token)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return_json_encode($result);
        }

        $user_info = check_token($uid, $token);

        if (!$user_info) {
            $result['code'] = 10001;
            $result['msg'] = '登录信息失效';
            return_json_encode($result);
        }
        $data['uid']= $uid;
        $data['token']= $token;
        session('Feedback',$data);
        return $this->fetch();
    }
    //提价表单
    public function buy(){
        $uid=session('Feedback.uid');
        $content=input('param.centent');
        $tel=input('param.tel');
        $data=array('status'=>0,'msg'=>'上传失败');
        if(strlen($content) < 10){
            $data['msg']="请填写10个字以上的描述，以便我们提供更好的帮助";
            echo json_encode($data);exit;
        }
        if(strlen($tel) != 11){
            $data['msg']="手机号不正确";
            echo json_encode($data);exit;
        }
        $name['content']=$content;
        $name['tel']=$tel;
        $img= request()->file(); //获取私照上传的文件
        if($img['img']){
            $audio_path=$this->feedback_img($img['img']);
            if(count($audio_path) > 0){
                for($i=0;$i<count($audio_path);$i++){
                    $name['img'.$i]=$audio_path[$i];
                }
            }
        }

        $name['uid']=$uid;
        $name['addtime']=time();
        //添加邀请记录
        $result=db('feedback')->insert($name);
        if($result){
            $data['status']=1;
            $data['msg']="提交成功，感谢您的意见";
        }

        echo json_encode($data);exit;

    }

    private function feedback_img($img){
        if(count($img) > 0){
            $audio_path=[];
            foreach ($img as $k => $v) {
                $audio_path[] = oss_upload($v); //单图片上传
            }
        }
        return $audio_path;
    }
}