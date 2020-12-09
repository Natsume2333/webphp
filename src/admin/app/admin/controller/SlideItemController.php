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
namespace app\admin\controller;

use think\Db;
use cmf\controller\AdminBaseController;
use app\admin\model\SlideItemModel;
use app\portal\model\PortalPostModel;
class SlideItemController extends AdminBaseController
{
    /**
     * 幻灯片页面列表
     * @adminMenu(
     *     'name'   => '幻灯片页面列表',
     *     'parent' => 'admin/Slide/index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面列表',
     *     'param'  => ''
     * )
     */
    public function index()
    {
        $id      = $this->request->param('slide_id');
        $slideId = !empty($id) ? $id : 1;
        $result  = Db::name('slideItem')->where(['slide_id' => $slideId])->select()->toArray();

        $this->assign('slide_id', $id);
        $this->assign('result', $result);
        return $this->fetch();
    }

    /**
     * 幻灯片页面添加
     * @adminMenu(
     *     'name'   => '幻灯片页面添加',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面添加',
     *     'param'  => ''
     * )
     */
    public function add()
    {
        $slideId = $this->request->param('slide_id');
        $this->assign('slide_id', $slideId);
        return $this->fetch();
    }

    /**
     * 幻灯片页面添加提交
     * @adminMenu(
     *     'name'   => '幻灯片页面添加提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面添加提交',
     *     'param'  => ''
     * )
     */
    public function addPost()
    {
        $data = $this->request->param();
        if($data['post']['type'] ==1){    //邀请链接
            $invite_friends ='http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/invitation_api/index/uid/'; //邀请好友 用户uid
            $data['post']['url']=$invite_friends;
            $data['post']['is_auth_info']=1;
        }elseif($data['post']['type'] ==2){
            $article['post_title']=$data['post_title'];
            $article['post_content']=$data['post_content'];
            $article['post_keywords']=$data['post_keywords'];

            $portal_category  = Db::name('portal_category')->where("name='轮播图'")->find();
            if(!$portal_category){
                 $this->error("轮播图文章分类参数错误");
            }
            $article['categories']=$portal_category['id'];//轮播图分类id
            
            if (empty($article['post_title'])) {
                $this->error("请输入文章标题");
            }
            if (empty($article['post_content'])) {
                $this->error("请输入文章内容");
            }
                $article['post_status']=1;
            $portalPostModel = new PortalPostModel();

            $portalPostModel->adminAddArticle($article, $article['categories']);

            $hookParam          = [
                'is_add'  => true,
                'article' => $article
            ];
            hook('portal_admin_after_save_article', $hookParam);

            $invite_friends ='http://'.$_SERVER['HTTP_HOST'].'/mapi/public/index.php/api/novice_guide_api/content/id/'.$portalPostModel->id; //文章列表 id
            $data['post']['url']=$invite_friends;
             $data['post']['article_id']=$portalPostModel->id;
        }

        Db::name('slideItem')->insert($data['post']);
        $this->success("添加成功！", url("slideItem/index", ['slide_id' => $data['post']['slide_id']]));
    }

    /**
     * 幻灯片页面编辑
     * @adminMenu(
     *     'name'   => '幻灯片页面编辑',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> true,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面编辑',
     *     'param'  => ''
     * )
     */
    public function edit()
    {
        $id     = $this->request->param('id');
        $result = Db::name('slideItem')->where(['id' => $id])->find();
        if($result['article_id']){
            $article=Db::name('portal_post')->where(['id' => $result['article_id']])->find();
            $article['post_content']=htmlspecialchars_decode($article['post_content']);
            $this->assign('article', $article);
        }
        $this->assign('result', $result);
        $this->assign('slide_id', $result['slide_id']);
        return $this->fetch();
    }

    /**
     * 幻灯片页面编辑
     * @adminMenu(
     *     'name'   => '幻灯片页面编辑提交',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面编辑提交',
     *     'param'  => ''
     * )
     */
    public function editPost()
    {
        $data = $this->request->param();
        
        $data['post']['image'] =cmf_asset_relative_url($data['post']['image']);

        $slideItem  = Db::name('slideItem')->where("id=".$data['post']['id'])->find();
 
         if($data['post']['type'] ==1){    //邀请链接
            if($slideItem['type'] !=$data['post']['type']){
                $invite_friends ='http://' . $_SERVER['HTTP_HOST'] . '/mapi/public/index.php/api/invitation_api/app_index'; //邀请好友 用户uid
                $data['post']['url']=$invite_friends;
                $data['post']['is_auth_info']=1;
            }
           
        }elseif($data['post']['type'] ==2){
            $article['post_title']=$data['post_title'];
            $article['post_content']=$data['post_content'];
            $article['post_keywords']=$data['post_keywords'];
            $article['id']=$data['article_id'];

            $portal_category  = Db::name('portal_category')->where("name='轮播图'")->find();
            if(!$portal_category){
                 $this->error("轮播图文章分类参数错误");
            }
            $article['categories']=strval($portal_category['id']);//轮播图分类id
            
            if (empty($article['post_title'])) {
                $this->error("请输入文章标题");
            }
            if (empty($article['post_content'])) {
                $this->error("请输入文章内容");
            }
            $article['post_status']=1;
            $portalPostModel = new PortalPostModel();

            if($slideItem['type'] !=$data['post']['type']){   //添加
                
                 $portalPostModel->adminAddArticle($article, $article['categories']);

                $invite_friends ='http://'.$_SERVER['HTTP_HOST'].'/mapi/public/index.php/api/novice_guide_api/content/id/'.$portalPostModel->id; //文章列表 id
                $data['post']['url']=$invite_friends;
                $data['post']['article_id']=$portalPostModel->id;
              
             }else{            //修改

                $portalPostModel->adminEditArticle($article, $article['categories']);
                $invite_friends = 'http://'.$_SERVER['HTTP_HOST'].'/mapi/public/index.php/api/novice_guide_api/content/id/'.$article['id']; //文章列表 id
              //  var_dump($article['id']);exit;
                $data['post']['url']=$invite_friends;
                $data['post']['article_id']=$article['id'];
                
             }

            $hookParam          = [
                    'is_add'  => true,
                    'article' => $article
                ];
            hook('portal_admin_after_save_article', $hookParam);

        }
      
        Db::name('slideItem')->update($data['post']);

        $this->success("保存成功！", url("SlideItem/index", ['slide_id' => $data['post']['slide_id']]));

    }

    /**
     * 幻灯片页面删除
     * @adminMenu(
     *     'name'   => '幻灯片页面删除',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面删除',
     *     'param'  => ''
     * )
     */
    public function delete()
    {
        $id     = $this->request->param('id', 0, 'intval');

        $slideItem = Db::name('slideItem')->find($id);

        $result = Db::name('slideItem')->delete($id);
        if ($result) {
            //删除图片。
//            if (file_exists("./upload/".$slideItem['image'])){
//                @unlink("./upload/".$slideItem['image']);
//            }
            $this->success("删除成功！", url("SlideItem/index",["slide_id"=>$slideItem['slide_id']]));
        } else {
            $this->error('删除失败！');
        }

    }

    /**
     * 幻灯片页面隐藏
     * @adminMenu(
     *     'name'   => '幻灯片页面隐藏',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面隐藏',
     *     'param'  => ''
     * )
     */
    public function ban()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $rst = Db::name('slideItem')->where(['id' => $id])->update(['status' => 0]);
            if ($rst) {
                $this->success("幻灯片隐藏成功！");
            } else {
                $this->error('幻灯片隐藏失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 幻灯片页面显示
     * @adminMenu(
     *     'name'   => '幻灯片页面显示',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面显示',
     *     'param'  => ''
     * )
     */
    public function cancelBan()
    {
        $id = $this->request->param('id', 0, 'intval');
        if ($id) {
            $result = Db::name('slideItem')->where(['id' => $id])->update(['status' => 1]);
            if ($result) {
                $this->success("幻灯片启用成功！");
            } else {
                $this->error('幻灯片启用失败！');
            }
        } else {
            $this->error('数据传入失败！');
        }
    }

    /**
     * 幻灯片页面排序
     * @adminMenu(
     *     'name'   => '幻灯片页面排序',
     *     'parent' => 'index',
     *     'display'=> false,
     *     'hasView'=> false,
     *     'order'  => 10000,
     *     'icon'   => '',
     *     'remark' => '幻灯片页面排序',
     *     'param'  => ''
     * )
     */
    public function listOrder()
    {
        $slideItemModel = new  SlideItemModel();
        parent::listOrders($slideItemModel);
        $this->success("排序更新成功！");
    }
}