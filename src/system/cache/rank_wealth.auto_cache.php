<?php

use think\helper\Time;

/**
 * Created by PhpStorm.
 * User: weipeng
 * Date: 2018/3/4
 * Time: 22:20
 */

class rank_wealth_auto_cache
{
    private $key = "rank:wealth";
    public function load($param)
    {
        $rank_name = trim($param['rank_name']);
        $table = trim($param['table']);
        $page = intval($param['page']);
        $page_size = intval($param['page_size']);
        $cache_time = trim($param['cache_time']);
        $this->key .= $rank_name . '_' . $page;

        $key_bf = $this->key.'_bf';
        $list = $GLOBALS['redis']->get($this->key);

        $db_prefix = config('database.prefix');
        if ($list === false) {
            $is_ok =  $GLOBALS['redis']->set_lock($this->key);
            if(!$is_ok){
                $list = $GLOBALS['redis']->get($key_bf,true);
            }else{
                if($rank_name=='day'){//day
                    $day_time = Time::today();

                    $list = db('user_consume_log')
                        -> alias('c')
                        -> join($db_prefix . 'user u','c.user_id=u.id')
                        -> field('u.id,u.user_nickname,u.sex,u.avatar,u.address,u.is_online,sum(c.coin) as total')
                        -> group('c.user_id')
                        -> where('c.create_time','between time',[$day_time[0],$day_time[1]])
                        -> where('u.sex',1)
                        -> page($page,$page_size)
                        -> order('total desc')
                        -> select();
                }elseif($rank_name=='week'){//month
                    //当前日期
                    $sdefaultDate = date("Y-m-d");

                    //$first =1 表示每周星期一为开始日期 0表示每周日为开始日期
                    $first=1;

                    //获取当前周的第几天 周日是 0 周一到周六是 1 - 6
                    $w=date('w',strtotime($sdefaultDate));

                    //获取本周开始日期，如果$w是0，则表示周日，减去 6 天
                    $now_week_start = strtotime("$sdefaultDate -".($w ? $w - $first : 6).' days');

                    $list = db('user_consume_log')
                        -> alias('c')
                        -> join($db_prefix . 'user u','c.user_id=u.id')
                        -> field('u.id,u.user_nickname,u.sex,u.avatar,u.address,u.is_online,sum(c.coin) as total')
                        -> group('c.user_id')
                        -> where('c.create_time','between time',[$now_week_start,NOW_TIME])
                        -> where('u.sex',1)
                        -> page($page,$page_size)
                        -> order('total desc')
                        -> select();
                }else{//all

                    $list = db('user_consume_log')
                        -> alias('c')
                        -> join($db_prefix . 'user u','c.user_id=u.id')
                        -> field('u.id,u.user_nickname,u.sex,u.avatar,u.address,u.is_online,sum(c.coin) as total')
                        -> group('c.user_id')
                        -> where('u.sex',1)
                        -> page($page,$page_size)
                        -> order('total desc')
                        -> select();
                }

                foreach ($list as &$v){

                    $level = get_level($v['id']);
                    $v['level'] = 0;
                    if($level){
                        $v['level'] = $level;
                    }
                }

                if($rank_name=='day'){
                    $GLOBALS['redis']->set($this->key, $list, $cache_time, true);//缓存时间 1800秒
                    $GLOBALS['redis']->set($key_bf, $list, 86400, true);//备份
                }elseif($rank_name=='week'){
                    $GLOBALS['redis']->set($this->key, $list, $cache_time, true);//缓存时间 28800秒 8h
                    $GLOBALS['redis']->set($key_bf, $list, 86400, true);//备份
                }else{
                    $GLOBALS['redis']->set($this->key, $list, $cache_time, true);//缓存时间 86400秒 24h
                    $GLOBALS['redis']->set($key_bf, $list, 86400, true);//备份
                }
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