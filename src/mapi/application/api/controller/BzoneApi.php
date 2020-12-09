<?php

namespace app\api\controller;

use BuguPush;
use FontLib\Table\Type\name;
use think\App;
use think\Db;
use think\Request;

// +----------------------------------------------------------------------
// | 山东布谷鸟网络科技一对一视频商业系统 [ CUCKOO ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015~2018 http://www.bogokj.com All rights reserved.
// +----------------------------------------------------------------------
// | Creative Commons
// +----------------------------------------------------------------------
// | Author: weipeng <1403102936@qq.com>
// +----------------------------------------------------------------------

class BzoneApi extends Base
{

    //发布动态
    public function add_dynamic_new()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //文本内容
        $content = trim(input('param.msg_content'));
        $is_audio = intval(input('param.is_audio'));
        $audio_url = trim(input('param.audio_url'));
        $video_url = trim(input('param.video_url'));
        $cover_url = trim(input('param.cover_url'));

        $lat = trim(input('param.lat'));
        $lng = trim(input('param.lng'));

        $duration = trim(input('param.duration'));    //语音时长


        $city = trim(input('param.city'));

        $config = load_cache('config');
        $dirty_word = explode(",", $config['dirty_word']);    //藏字库

        if (empty($content) && $is_audio == 0) {
            $result['code'] = 0;
            $result['msg'] = '图片，音频，内容至少需要填写一项';
            return_json_encode($result);
        }

        $user_info = check_login_token($uid, $token);

        $content = strlen($content) == 0 ? "" : $content;
        if (strlen($content) > 100) {
            $result['code'] = 0;
            $result['msg'] = '内容长度超出限制！';
            return_json_encode($result);
        }

        //藏字库判断
        foreach ($dirty_word as $v) {
            //内容
            if (!empty($v) && stristr($content, $v)) {
                $result['code'] = 0;
                $result['msg'] = "动态中不能带有" . $v . "的字";
                return_json_encode($result);
            }
        }

        $data = [
            'uid' => $uid,
            'is_audio' => $is_audio,
            'msg_content' => $content,
            'audio_file' => $audio_url,
            'video_url' => $video_url,
            'cover_url' => $cover_url,
            'publish_time' => NOW_TIME,
            'city' => $city,
            'lat' => $lat,
            'lng' => $lng,
            'duration' => $duration,
        ];

        $zid = db('bzone')->insertGetId($data);

        if (!$zid) {
            $result['code'] = 0;
            $result['msg'] = '发布失败';
            return_json_encode($result);
        }

        $param_list = input('param.');

        //上传图片
        foreach ($param_list as $k => $v) {
            if (strpos($k, 'file') !== false) {
                $img_info['zone_id'] = $zid;
                $img_info['addtime'] = NOW_TIME;
                $img_info['img'] = $v;
                db('bzone_images')->insertGetId($img_info);
            }
        }

        return_json_encode($result);
    }


    //发布动态
    public function add_dynamic()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        //文本内容
        $content = input('param.msg_content');
        $is_audio = input('param.is_audio');

        $file_list = request()->file(); //获取私照上传的文件

        if (empty($content) && $is_audio == 0 && $file_list == null) {
            $result['code'] = 0;
            $result['msg'] = '图片，音频，内容至少需要填写一项';
            return_json_encode($result);
        }

        $user_info = check_login_token($uid, $token);
        $config = load_cache('config');
        $dirty_word = explode(",", $config['dirty_word']);    //藏字库
        //如果is_audio == 1，第一个文件是音频
        if (count($file_list) > 0 && $is_audio == 1) {
            $file_count = 0;
            $temp_file_list = array();
            $audio_data = [];
            foreach ($file_list as $k => $v) {
                if ($file_count == 0) {
                    $audio_data = $v;
                } else {
                    $temp_file_list[$k] = $v;
                }
                $file_count++;
            }

            $audio_path = oss_upload($audio_data); //单图片上传

            $file_list = $temp_file_list;
        } else {
            $audio_path = "";
        }
        $content = strlen($content) == 0 ? "" : $content;
        //藏字库判断
        foreach ($dirty_word as $v) {
            //内容
            if (stristr($content, $v)) {
                $result['code'] = 0;
                $result['msg'] = "动态中不能带有" . $v . "的字";
                return_json_encode($result);
            }
        }
        $publish_time = time();
        $data = [
            'uid' => $uid,
            'is_audio' => $is_audio,
            'msg_content' => $content,
            'audio_file' => $audio_path,
            'publish_time' => $publish_time,
        ];
        //上传图片
        $zid = Db::name('bzone')->insertGetId($data);
        if ($file_list != null) {
            $data = [];
            foreach ($file_list as $k => $v) {
                $uploads = oss_upload($v); //单图片上传
                $data['zone_id'] = $zid;
                $data['addtime'] = time();
                $data['img'] = $uploads;
                Db::name('bzone_images')->insertGetId($data);
            }
        }

        if ($zid) {
            return_json_encode($result);
        }
        $result['code'] = 0;
        $result['msg'] = '发布失败';
        return_json_encode($result);
    }

    //评论
    public function add_dynamic_reply()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));

        $content = input('param.body');
        //动态id
        $zone_id = input('param.zone_id');

        $user_info = check_login_token($uid, $token);
        $config = load_cache('config');
        $dirty_word = explode(",", $config['dirty_word']);    //藏字库
        if (empty($content) || empty($zone_id)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return_json_encode($result);
        }
        //藏字库判断
        foreach ($dirty_word as $v) {
            //内容
            if (stristr($content, $v)) {
                $result['code'] = 0;
                $result['msg'] = "评论中不能带有" . $v . "的字";
                return_json_encode($result);
            }
        }
        $content = emoji_encode($content);

        //查询该动态信息
        $zone = db('bzone')->find($zone_id);
        if (!$zone) {
            $result['code'] = 0;
            $result['msg'] = '动态不存在！';
            return_json_encode($result);
        }

        $data = [
            'uid' => $uid,
            'body' => $content,
            'zone_id' => $zone_id,
            'addtime' => time(),
        ];
        $res = Db::name('bzone_reply')->insertGetId($data);
        if ($res) {
            //发送动态评论推送
            require_once DOCUMENT_ROOT . '/system/umeng/BuguPush.php';
            $config = load_cache('config');
            $push = new BuguPush($config['umengapp_key'], $config['umeng_message_secret']);
            $push->sendAndroidCustomizedcast('go_app', $zone['uid'], 'buguniao', '动态消息', '有人评论了你的动态快去查看吧', $content, json_encode([]));
            $push->sendIOSCustomizedcast('go_app', $zone['uid'], 'buguniao', '动态消息', '有人评论了你的动态快去查看吧', $content, json_encode([]));

            $result['msg'] = '发布成功';
            return_json_encode($result);
        }

        $result['code'] = 0;
        $result['msg'] = '服务器繁忙';
        return_json_encode($result);

    }

    //获取评论
    public function get_reply_list()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $zone_id = intval(input('param.zone_id'));

        $user_info = check_login_token($uid, $token);

        $list = Db::name('bzone_reply')->where('zone_id = ' . $zone_id)->order('id')->select();
        //循环下从缓存中取出用户数据
        $temp_list = array();
        foreach ($list as $k => $v) {
            $user_info = get_user_base_info($v['uid']);
            $v['addtime'] = time_trans($v['addtime']);
            $v['body'] = emoji_decode($v['body']);
            $temp_list[$k] = $v;
            $temp_list[$k]['userInfo'] = $user_info;
        }

        $list = $temp_list;
        $result['list'] = $list;
        return_json_encode($result);
    }

    //动态点赞
    public function request_like()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $zone_id = intval(input('param.zone_id'));

        $user_info = check_login_token($uid, $token);

        //查询下是否已经点了喜欢按钮
        $is_like = Db::name('bzone_like')->where(['uid' => $uid, 'zone_id' => $zone_id])->find();
       
        if ($is_like) {
            //取消点赞
            Db::name('bzone_like')->where(['uid' => $uid, 'zone_id' => $zone_id])->delete();
        } else {
            //点赞
            $data = [
                'uid' => $uid,
                'zone_id' => $zone_id,
                'addtime' => time(),
            ];
            //var_dump($data);die;
            Db::name('bzone_like')->insert($data);
        }
        return_json_encode($result);

    }

    //删除查询
    public function del_dynamic()
    {
        $result = array('code' => 1, 'msg' => '');
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $zone_id = intval(input('param.zone_id'));

        $user_info = check_login_token($uid, $token);

        //先删除所有评论
        Db::name('bzone_reply')->where('zone_id = ' . $zone_id)->delete();
        //删除所有点赞
        Db::name('bzone_like')->where('zone_id = ' . $zone_id)->delete();
        //最后删除动态
        Db::name('bzone')->where('id = ' . $zone_id)->delete();

        return_json_encode($result);
    }

    //获取动态列表
    public function get_list_3x()
    {
        $result = array('code' => 1, 'msg' => '', 'list' => []);
        $uid = intval(input('param.uid'));
        $token = trim(input('param.token'));
        $page = intval(input('param.page'));
        $type = intval(input('param.type'));
        $action = trim(input('param.action'));
        $to_user_id = intval(input('param.to_user_id'));
		
        $user_info = check_login_token($uid, $token, ['lat', 'lng']);
        
        

        $field = 'b.*,count(r.zone_id) comment_count';
        if ($action == 'ou') {
            //其他用户
            $list = db('bzone')->field($field)->alias('b')->join('__BZONE_REPLY__ r', 'b.id = r.zone_id', 'left')
                ->order('b.id desc')->group('b.id')->where('b.uid=' . $to_user_id)->page($page)->select();

        } else if ($action == 'att') {

            //获取关注列表id
            $attention_user = db('user_attention')->where("uid=$uid")->page($page)->select();
            if (!$attention_user) {
                return_json_encode($result);
            }

            $attention_ids = array();
            array_walk($attention_user, function ($value, $key) use (&$attention_ids) {
                $attention_ids[] = $value['attention_uid'];
            });
            //关注的
            $list = db('bzone')->field($field)->alias('b')
                ->join('__BZONE_REPLY__ r', 'b.id = r.zone_id', 'left')
                ->where('b.uid', 'in', $attention_ids)
                ->order('b.id desc')
                ->group('b.id')
                ->page($page)
                ->select();

        } else if ($action == 'near') {   //附近
            $lat = $user_info['lat'];
            $lng = $user_info['lng'];
            if ($lat&&$lat!='0.0') {
                //以当前用户经纬度为中心,查询5000米内的其他用户
                $y = 5000 / 110852; //纬度的范围
                $x = 5000 / (111320 * cos($lat)); //经度的范围

                if($x<0){
                    $x=-$x;
                }
                if($y<0){
                    $y=-$y;
                }

                $where = " b.lat >= ($lat-$y) and b.lat <= ($lat+$y) and b.lng >= ($lng-$x) and b.lng <= ($lng+$x)";

                $list = db('bzone')->field($field)->alias('b')->join('__BZONE_REPLY__ r', 'b.id = r.zone_id', 'left')
                    ->where($where)
                    ->order('b.publish_time desc')->group('b.id')->page($page)->select();

                foreach ($list as &$v) {
                    $v['distance'] = '距离' . distance($lat, $lng, $v['lat'], $v['lng']) . '米内';
                }
            } else {
                $list = [];
            }
           
        } else {
            $field .= ",count(l.id) as lcount";
            //全部
            $list = db('bzone')->field($field)->alias('b')->join('__BZONE_REPLY__ r', 'b.id = r.zone_id', 'left')->join('bzone_like l', 'l.zone_id =b.id', 'left')
                ->order('b.publish_time desc,comment_count desc,lcount desc')->group('b.id')->page($page)->select();

        }

		//var_dump($list);die;
        $new_list = array();
        foreach ($list as $k => $v) {
            //查询图片
            $images_list = db('bzone_images')->where('zone_id = ' . $v['id'])->field('img')->select();
            if (count($images_list) > 0) {
                $images = [];
                foreach ($images_list as $k1 => $v1) {
                    $images[] = $v1['img'];
                }
                $images_list = $images;
            }
            //var_dump($v['id']);
            $bzone_user_info = get_user_base_info($v['uid']);
            //查询我是否喜欢了，暂时先这样写
            $is_like = db('bzone_like')->where(['uid' => $uid, 'zone_id' => $v['id']])->select();
            $likes = db('bzone_like')->where(['zone_id' => $v['id']])->select();

            $replys = db('bzone_reply')->where(['zone_id' => $v['id']])->select();

            $v['userInfo'] = $bzone_user_info;
            $v['originalPicUrls'] = $images_list;
            $v['thumbnailPicUrls'] = $images_list;
            $v['publish_time'] = time_trans_10($v['publish_time']);
            $v['is_like'] = $is_like == null ? "0" : "1";
            $v['like_count'] = count($likes);
            $v['lcount'] = count($replys);
            $v['comment_count'] = count($replys);
            if ($bzone_user_info) {
                $new_list[$k] = $v;
            }
        }
        if ($type == 2) {
            $result['list'] = $new_list;
        } else {
            $result['data']['list'] = $new_list;
        }

        return_json_encode($result);
    }
}