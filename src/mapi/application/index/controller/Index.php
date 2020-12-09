<?php
namespace app\index\controller;
use think\Controller;

class Index extends Controller
{
    public function index()
    {
    	$this->dataList();
    	$imga = $this->img(2);
    	$imgb = $this->img(1);
    	$imgc = $this->img(3);
    	$imgd = $this->img(4);
    	$pca = $this->config('pc_icon');
    	$pcb = $this->config('pc_phone');
    	$this->contact();
    	$this->assign(['img'=>$imga,'imgb'=>$imgb,'imgc'=>$imgc,'imgd'=>$imgd,'pca'=>$pca,'pcb'=>$pcb]);
        return $this->fetch('/index');
    }

    public function dataList()
    {
    	$res = db('homeIndex')->order('orders')->select();
    	return $this->assign(['data'=>$res]);
    }

    public function img($type)
    {
    	$res = db('homeImg')->where('type',$type)->select();
    	return $res;
    }

    public function contact()
    {
    	$res = db('homeContact')->select();
    	return $this->assign('contact',$res);
    }

    public function config($code)
    {
    	$res = db('config')->where('code',$code)->select();
    	return $res;
    }

    public function homeIndex()
    {
        $pc_icon = $this->config('pc_icon');//备案信息
//        $pc_phone = $this->config('pc_phone');//站长手机号
        $system_name = $this->config('system_name');//网站名称
        $android_download_url = $this->config('android_download_url');//安卓下载地址
        $ios_download_url = $this->config('ios_download_url');//苹果下载地址
//        $down_pic = $this->img(2);//下载的二维码
        $logo = $this->img(1);//logo
        $wx_pic = $this->img(3);//微信二维码
//        $wb_pic = $this->img(4);//微博二维码

        $data = array(
            'pc_icon'=>empty($pc_icon[0]['val'])?'':$pc_icon[0]['val'],
//            'pc_phone'=>empty($pc_phone[0]['val'])?'':$pc_phone[0]['val'],
//            'down_pic'=>empty($down_pic[0]['img'])?'':$down_pic[0]['img'],
            'logo'=>empty($logo[0]['img'])?'':$logo[0]['img'],
            'wx_pic'=>empty($wx_pic[0]['img'])?'':$wx_pic[0]['img'],
//            'wb_pic'=>empty($wb_pic[0]['img'])?'':$wb_pic[0]['img'],
            'system_name'=>empty($system_name[0]['val'])?'':$system_name[0]['val'],
            'android_download_url'=>empty($android_download_url[0]['val'])?'':$android_download_url[0]['val'],
            'ios_download_url'=>empty($ios_download_url[0]['val'])?'':$ios_download_url[0]['val'],

        );


        $this->assign('data',$data);

        return $this->fetch('/homeindex');
    }

    public function mobileHomeIndex(){
        $system_name = $this->config('system_name');//网站名称
        $android_download_url = $this->config('android_download_url');//安卓下载地址
        $ios_download_url = $this->config('ios_download_url');//苹果下载地址
        $data = array(
            'system_name'=>empty($system_name[0]['val'])?'':$system_name[0]['val'],
            'android_download_url'=>empty($android_download_url[0]['val'])?'':$android_download_url[0]['val'],
            'ios_download_url'=>empty($ios_download_url[0]['val'])?'':$ios_download_url[0]['val'],
        );
        $this->assign('data',$data);
        return $this->fetch('/mobilehomeindex');

    }
    //游戏首页
    public function game(){
        $user_id = input('user_id');
        $imagebg_res = db('lottery_configure')->find();
        $gift_info = db('gift_score')->select();
        foreach ($gift_info as $v){
            $res[$v['gift_id']]['imgurl'] = $v['imgurl'];
        }
        $this->assign('data',array('user_id'=>$user_id,'bgimgurl'=>empty($imagebg_res['bgimgurl'])?url('static/image/game_start.png',false,false):$imagebg_res['bgimgurl'],'gift_info'=>$res));
        return $this->fetch('/game');
    }
    //抽奖
    public function gamePost(){
        $user_id = input('user_id');
        $data = array();
        $res = db('gift_score')->select();
        $prize = $this->probable($res);

        $data['code'] = 1;
        $data['data']['gift_id'] = empty($prize['gift_id'])?0:$prize['gift_id'];
        $data['data']['score'] = empty($prize['score'])?0:$prize['score'];

        db('winning_log')->insert(array('user_id'=>$user_id,'score'=>$data['data']['score'],'ctime'=>time()));
        echo json_encode($data);
        exit;

    }
    /**
     * 计算奖品概率以及获奖奖品
     */
    private function probable($goods){

        foreach($goods as $key=>$value){
            $probable[$value['gift_id']] = $value['winning_probability'];
            $prize_info[$value['gift_id']] = $value;
        }
        if(empty($probable)){
            return false;
        }
        $total = array_sum($probable);
        $rand_num = mt_rand(1,$total);
        $tmp = 0;
        foreach($probable as $k=>$val){
            $tmp += $val;
            if($rand_num<=$tmp){
                //return $k;
                return $prize_info[$k];
            }
        }
    }

}
