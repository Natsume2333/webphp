<?php

use think\helper\Time;

/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/4
 * Time: 22:20
 */

class user_rank_contribution_auto_cache
{
    private $key = "rank:contribution";
    public function load($param)
    {
        $user_id = trim($param['user_id']);
        $table = trim($param['table']);
        $page = intval($param['page']);
        $page_size = intval($param['page_size']);
        $cache_time = trim($param['cache_time']);
        $this->key .= $user_id . '_' . $page;

        $key_bf = $this->key.'_bf';
        $list = $GLOBALS['redis']->get($this->key);

        $db_prefix = config('database.prefix');
        if ($list === false) {
            $is_ok =  $GLOBALS['redis']->set_lock($this->key);
            if(!$is_ok){
                $list = $GLOBALS['redis']->get($key_bf,true);
            }else{

                $list = db('user_consume_log')
                    -> alias('c')
                    -> join($db_prefix . 'user u','c.user_id=u.id')
                    -> field('u.id,u.user_nickname,u.sex,u.avatar,u.address,sum(c.coin) as total')
                    -> group('c.user_id')
                    -> where('c.to_user_id','=',$user_id)
                    -> page($page,$page_size)
                    -> order('total desc')
                    -> select();

                $GLOBALS['redis']->set($this->key, $list, $cache_time, true);//缓存时间 1800秒
                $GLOBALS['redis']->set($key_bf, $list, 86400, true);//备份
            }
        }

        if(!is_array($list)){
          $list = json_decode($list,true);
        }
        
        foreach ($list as &$v){

            $level = get_level($v['id']);
            $v['level'] = 0;
            if($level){
                $v['level'] = $level;
            }
        }

        if(!is_array($list)){
            $list = json_decode($list,true);
        }
        if ($list == false) $list = array();

        return $list;
    }

    public function rm($param)
    {
        $GLOBALS['redis']->rm($this->key);
    }

    public function clear_all()
    {
        $GLOBALS['redis']->rm($this->key);
    }
}