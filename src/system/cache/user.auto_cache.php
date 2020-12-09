<?php

class user_auto_cache{

	public function load($param,$is_real = false)
	{

	    $key = 'uid' . $param['user_id'];
        $user_info = $GLOBALS['redis']->get($key);
		if($user_info === false || $is_real)
		{
            $user_info = get_user_base_info($param['user_id'],'level,coin,income,income_total');
            $level_data = get_grade_level($param['user_id']);
            $user_info['level'] = $level_data['levelname'];

			$GLOBALS['redis']->set($key,json_encode($user_info),60,true);
		}

		if(!is_array($user_info)){
            $user_info = json_decode($user_info,true);
        }
		return $user_info;
	}
	
	public function rm($param)
	{
        $key = 'uid' . $param['user_id'];
		$GLOBALS['redis']->rm($key);
	}
}
?>