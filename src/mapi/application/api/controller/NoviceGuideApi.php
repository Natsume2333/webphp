<?php
// +----------------------------------------------------------------------
// | ThinkCMF [ WE CAN DO IT MORE SIMPLE ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013-2017 http://www.thinkcmf.com All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 小夏 < 449134904@qq.com>
// +----------------------------------------------------------------------
namespace app\api\controller;


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

class NoviceGuideApi extends Base
{
    /**
     *新手引导
     */
    public function app_index()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => []);

        $data['category'] = Db::name("portal_category")
            ->where('status=1 and parent_id=0')
            ->field("id,name,more")
            ->order("list_order asc")
            ->limit(0, 5)
            ->select();
        foreach ($data['category'] as &$v) {
            $name = json_decode($v['more'], true);
            $v['thumbnail'] = $name['thumbnail'];
        }

        $field = "b.id,b.post_title";
        $data['portal'] = $this->portal($data['category'][0]['id'], $field);
        $result['data'] = $data;
        return_json_encode($result);
    }

    /*
    *   获取分类列表
    */
    public function app_classify()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => []);
        $id = intval(input('param.id'));

        $portal = Db::name("portal_category_post")->alias('a')
            ->where("a.category_id=" . $id . " and a.status=1 and b.post_type=1 and b.post_status=1 and b.delete_time=0")
            ->join("portal_post b", "b.id=a.post_id")
            ->field("b.post_title,b.id,b.published_time")
            ->order("a.list_order asc")
            ->select();
        $result['data'] = $portal;
        return_json_encode($result);
    }

    /*
     * 详情页面
     *
     */
    public function app_content()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => []);
        $id = intval(input('param.id'));
        if (empty($id)) {
            $result['code'] = 0;
            $result['msg'] = '参数错误';
            return_json_encode($result);
        }
        $portal = $this->content_get($id);
        $portal['post_content'] = htmlspecialchars_decode($portal['post_content']);
        $result['data'] = $portal;
        return_json_encode($result);
    }

    /*
     * 关于我们
     */
    public function app_about()
    {
        $result = array('code' => 1, 'msg' => '', 'data' => []);
        $portals = Db::name("portal_category_post")->alias('a')
            ->where("b.name='关于我们' and a.status=1 and b.status=1")
            ->join("portal_category b", "b.id=a.category_id ")
            ->field("a.post_id")
            ->find();
        if (!isset($portals)) {
            $result['code'] = 0;
            $$result['msg'] = "暂无数据";
            return_json_encode($result);
        }
        $portal = $this->content_get($portals['post_id']);
        $portal['post_content'] = htmlspecialchars_decode($portal['post_content']);
        $result['data'] = $portal;
        return_json_encode($result);
    }


    /**
     * h5 页面 新手引导
     */
    public function index()
    {
        $category = Db::name("portal_category")
            ->where('status=1 and parent_id=0')
            ->field("id,name")
            ->order("list_order asc")
            ->limit(0, 5)
            ->select();

        $field = "b.id,b.post_title";
        $portal = $this->portal($category[0]['id'], $field);

        $this->assign('category', $category);
        $this->assign('portal', $portal);
        return $this->fetch();
    }

    //获取文件种类
    public function portal($id, $field)
    {
        $portal = Db::name("portal_category_post")->alias('a')
            ->where("a.category_id=" . $id . " and a.status=1 and b.post_type=1 and b.post_status=1")
            ->join("portal_post b", "b.id=a.post_id")
            ->field($field)
            ->order("list_order asc")
            ->select();
        return $portal;
    }

    //获取文件内容
    public function content_get($id)
    {
        $portal = Db::name("portal_post")
            ->where("id=" . $id . " and post_type=1 and post_status=1")
            ->find();
        return $portal;
    }

    /*
     * h5 更多问题
     *
     */
    public function species()
    {
        $id = intval(input('param.id'));
        $type = intval(input('param.type'));

        $category = [];
        $portal = [];
        if ($type) {
            $category = Db::name("portal_category")->where('status=1 and parent_id=' . $id)
                ->field("id,name")
                ->order("list_order asc")
                ->select();

        } else {
            $field = "b.id,b.post_title,post_content";

            $portal = $this->portal($id, $field);

        }

        $sert = Db::name("portal_category")->where("id=$id")->find();

        $this->assign('portal', $portal);
        $this->assign('category', $category);
        $this->assign('sert', $sert);
        $this->assign('type', $type);
        return $this->fetch();
    }

    /*
     * h5 详情页面
     *
     */
    public function content()
    {
        $id = input('param.id');
        if (empty($id)) {
            $this->error("传参错误");
        }
        $portal = $this->content_get($id);
        $portal['post_content'] = htmlspecialchars_decode($portal['post_content']);
        $this->assign('portal', $portal);
        return $this->fetch();
    }
}
