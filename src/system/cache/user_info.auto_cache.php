<?php

class user_info_auto_cache{

	public function load($param)
	{
        $user_id = intval($param['user_id']);

        $base_field = 'id,avatar,user_nickname,sex,level,coin,user_status,is_auth,is_online,link_id,signature';
        if(is_array($param['field']) && count($param['field']) > 0){
            $base_field .= ',' . implode(',',$param['field']);
        }

        $key = "user_info:".$user_id.md5($base_field);

        $user_base_info = $GLOBALS['redis']->get($key);

		if($user_base_info === false || $param['cache'] == 1)
		{
            $user_base_info = db('user') -> field($base_field) -> find($user_id);
			
			$GLOBALS['redis']->set($key,json_encode($user_base_info),60,true);
		}

		if(!is_array($user_base_info)){
            $user_base_info = json_decode($user_base_info,true);
        }

		return $user_base_info;
	}
	
	public function rm($param)
	{
        $id = intval($param['user_id']);
        $key = "user_info:".$id;
		$GLOBALS['redis']->rm($key);
	}

}
?>