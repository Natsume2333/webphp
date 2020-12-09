<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 老猫 <zxxjjforever@163.com>
// +----------------------------------------------------------------------
namespace app\admin\controller;

use cmf\controller\AdminBaseController;
use app\admin\model\ThemeModel;
use think\Db;
use think\Validate;
use tree\Tree;

class WebController extends AdminBaseController
{
    /**
     * 首页管理
     * @adminMenu(
     *     'name'   => '首页管理',
     *     'parent' => 'admin/Web/default',
     *     'display'=> true,
     *     'hasView'=> true,
     *     'order'  => 20,
     *     'icon'   => '',
     *     'remark' => '首页管理',
     *     'param'  => ''
     * )
     */
    public function default1()
    {
        $data = DB::name('homeIndex')->order('orders')->select();
        $this->assign(['data'=>$data]);
        return $this->fetch('/web/index/default');
    }

    public function indexAdd()
    {
    	return $this->fetch('/web/index/add');
    }

    public function indexRunAdd()
    {
        $request = request()->param();
        $data = [
            'name'=>$request['post']['name'],
            'content'=>$request['post']['content'],
            'orders'=>$request['post']['orderno'],
            'color'=>$request['post']['coin'],
            'img'=>$request['post']['img'],
            'addtime'=>time(),
        ];
        $res = DB::name('homeIndex')->insert($data);
        if ($res) {
            $this->success("保存成功", url('web/default1'));
        } else {
            $this->error("保存失败");
        }
    }

    public function indexEdit()
    {
        $id = request()->param('id');
        $res = DB('homeIndex')->where('id',$id)->select();
        $this->assign(['res'=>$res[0]]);
        return $this->fetch('/web/index/edit');
    }

    public function indexRunEdit()
    {
        $request = request()->param();
        if(empty($request['post']['img'])){
            $data = [
                'name'=>$request['post']['name'],
                'content'=>$request['post']['content'],
                'orders'=>$request['post']['orderno'],
                'color'=>$request['post']['coin'],
                'addtime'=>time(),
            ];
        }else{
           $data = [
                'name'=>$request['post']['name'],
                'content'=>$request['post']['content'],
                'orders'=>$request['post']['orderno'],
                'color'=>$request['post']['coin'],
                'img'=>$request['post']['img'],
                'addtime'=>time(),
            ]; 
        }
        $res = DB::name('homeIndex')->where('id',$request['id'])->update($data);
        if ($res) {
            $this->success("修改成功", url('web/default1'));
        } else {
            $this->error("修改失败");
        }
    }

    //修改排序
    public function upd()
    {
        $param = request()->param();
        $data = '';
        foreach ($param['listorders'] as $k => $v) {
            $status = Db::name("homeIndex")->where("id=$k")->update(array('orders' => $v));
            if ($status) {
                $data = $status;
            }
        }

        if ($data) {
            $this->success("排序成功", url('web/default1'));
        } else {
            $this->error("排序失败");
        }
    }

    //删除
    public function del()
    {
        $param = request()->param();
        $result = Db::name("homeIndex")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    //图片
    public function imgList()
    {
        $res = DB::name('homeImg')->select();
        $this->assign('data',$res);
        return $this->fetch('web/img/default');
    }

    public function imgAdd()
    {
        return $this->fetch('web/img/add');
    }

    public function imgEdit()
    {
        $id = request()->param('id');
        $res = DB('homeImg')->where('id',$id)->select();
        $this->assign(['res'=>$res[0]]);
        return $this->fetch('/web/img/edit');
    }

    public function imgRunEdit()
    {
        $request = request()->param();
        if(empty($request['img'])){
            $data = [
                'type'=>$request['type'],
                'addtime'=>time(),
            ];
        }else{
           $data = [
                'type'=>$request['type'],
                'img'=>$request['img'],
                'addtime'=>time(),
            ]; 
        }
        $res = DB::name('homeImg')->where('id',$request['id'])->update($data);
        if ($res) {
            $this->success("修改成功", url('web/imgList'));
        } else {
            $this->error("修改失败");
        }
    }

    public function imgRunAdd()
    {
        $request = request()->param();
        
        $data = [
            'type'=>$request['type'],
            'img'=>$request['img'],
            'addtime'=>time(),
        ];
        $res = DB::name('homeImg')->insert($data);
        if ($res) {
            $this->success("保存成功", url('web/imgList'));
        } else {
            $this->error("保存失败");
        }
    }

    public function imgDel()
    {
        $param = request()->param();
        $result = Db::name("homeImg")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }

    //联系我们
    public function contact()
    {
        $phone = DB::name('homeContact')->select();
        $this->assign(['data'=>$phone]);
        return $this->fetch('web/contact/default');
    }

    public function contactAdd()
    {
        return $this->fetch('web/contact/add');
    }

    public function contactRunAdd()
    {
        $request = request()->param();
        $phone = DB::name('homeContact')->where('c_phone',$request['phone'])->find();
        if($phone){
            return $this->error('联系人已存在');
        }else{
            $data = [
                'name'  =>  $request['name'],
                'c_phone'  =>  $request['phone'],
                'email'  =>  $request['email'],
                'addtime'  =>  time(),
            ];
            $res = DB::name('homeContact')->insert($data);
            if ($res) {
                return $this->success('添加成功',url('web/contact'));
            }else{
                return $this->error('添加失败');
            }
        } 
    }

    public function contactEdit()
    {
        $id = request()->param('id');
        $res = DB::name('homeContact')->where('id',$id)->select();
        $this->assign('res',$res[0]);
        return $this->fetch('web/contact/edit');
    }

    public function contactRunEdit()
    {
        $request = request()->param();
         $id = request()->param('id');
        $data = [
            'name'  =>  $request['name'],
            'c_phone'  =>  $request['phone'],
            'email'  =>  $request['email'],
            'addtime'  =>  time(),
        ];
        $res = DB::name('homeContact')->where('id',$id)->update($data);
        if ($res) {
            return $this->success('修改成功',url('web/contact'));
        }else{
            return $this->error('修改失败');
        }
    }

    public function contactDel()
    {
        $param = request()->param();
        $result = Db::name("homeContact")->where("id=" . $param['id'])->delete();
        return $result ? '1' : '0';
        exit;
    }
}