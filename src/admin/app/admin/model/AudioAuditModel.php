<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <thinkcmf@126.com>
// +----------------------------------------------------------------------
namespace app\admin\model;

use think\Model;

class AudioAuditModel extends Model
{

    public function audio_sel()
    {
        $data = $this
        		->alias('a')
        		->join('user u','u.id=a.uid')
        		->field('u.user_nickname,sex,a.*')
        		->paginate(10);
        return $data;
    }

    public function post_a($id,$type){
    	$data = $this->where(['id'=>$id])->update(['status'=>$type]);
    	return $data;
    }

    public function del_a($id){
    	$data = $this->where(['id'=>$id])->delete();
    	return $data;
    }

}