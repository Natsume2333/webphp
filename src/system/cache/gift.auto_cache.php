<?php

class gift_auto_cache
{
    private $key = "gift:list";

    public function load($param, $is_real = true)
    {
        $gift_list = $GLOBALS['redis']->get($this->key);

        if ($gift_list === false) {
            $gift_list = db('gift')->order("orderno asc")->select();
            $url = SITE_URL;
          /*  foreach ($gift_list as &$v) {
                $v['img'] =  $url.'/admin/public/upload/'.$v['img'];
            }*/
            $GLOBALS['redis']->set($this->key, json_encode($gift_list), 60, true);
        }
        //var_dump($gift_list);die;
        if (!is_array($gift_list)) {
            $gift_list = json_decode($gift_list, true);
        }

        return $gift_list;
    }

    public function rm($param)
    {
        $GLOBALS['cache']->rm($this->key);
    }

    public function clear_all()
    {
        $GLOBALS['cache']->rm($this->key);
    }
}

?>