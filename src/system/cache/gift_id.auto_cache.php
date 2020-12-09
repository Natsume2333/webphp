<?php

class gift_id_auto_cache{

	public function load($param)
	{
        $id = intval($param['id']);
        $key = "gift_id:".$id;
        $gift = $GLOBALS['redis']->get($key);
		if($gift === false)
		{
            $gift = db('gift') -> where('id','=',$id) -> find();
			
			$GLOBALS['redis']->set($key,json_encode($gift),60,true);
		}

		if(!is_array($gift)){
            $gift = json_decode($gift,true);
        }
		return $gift;
	}
	
	public function rm($param)
	{
        $id = intval($param['id']);
        $key = "gift_id:".$id;
		$GLOBALS['redis']->rm($key);
	}

}
?>