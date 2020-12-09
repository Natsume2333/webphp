<?php

class usersign_auto_cache{
	public function load($param)
	{
		$m_config =  load_cache("config");//参数
		$sdkappid = $m_config['tencent_sdkappid'];
		
		$id = trim($param['id']);
		
		$key = "usersign:".$sdkappid.":".$id;
		
		$root = $GLOBALS['redis']->get($key);
		
		$open_usersig_cache = intval($m_config['open_usersig_cache']);

	
		if($root === false||!$open_usersig_cache)
		{
			$private_pem_path = DOCUMENT_ROOT."/system/tim/private_key";
			
			$root = array();
		
			if(!file_exists($private_pem_path))
			{
				$root['error'] = "私钥文件不存在:".$private_pem_path;
				$root['status'] = 0;
				
			}else if ($id == ''){
				$root['error'] = "参数id不能为空";
				$root['status'] = 0;
			}else{
				require_once(DOCUMENT_ROOT.'/system/tim/TimApi.php');
				require_once(DOCUMENT_ROOT.'/system/tim/TimRestApi.php');
				

				$identifier = $m_config['tencent_identifier'];
                // $tencent_sha_key = $m_config['tencent_sha_key'];
				$tencent_sha_key=0;
				$api = createRestAPI();
				$api->init($sdkappid, $identifier,$tencent_sha_key);
				
				
				//var_dump($api);
				
				$signature = get_signature();
				$expiry_after = 86400 * 30;//30天有效期
				$ret = $api->generate_user_sig((string)$id, $expiry_after, $private_pem_path, $signature);
				
				if($ret == null || strstr($ret[0], "failed")){
					$root['error'] = $sdkappid.":获取usrsig失败, 请确保:".$signature." 文件有执行的权限.";
					$root['status'] = 0;
				}else{
					$root['usersign'] = $ret[0];
					$root['status'] = 1;
					
					$GLOBALS['redis']->set($key,$root,$expiry_after - 60);
					
					//$expiry_after = NOW_TIME + 86400;
					//$GLOBALS['db']->query("update ".DB_PREFIX."user set usersig = '".$ret[0]."',expiry_after=".$expiry_after." where id = '".$id."'");
				}
			}
		}
        if(!is_array($root)){
            $root = json_decode($root,true);
        }
		return $root;
	}
	
	public function rm($param)
	{
		$m_config =  load_cache("config");//参数
		$sdkappid = $m_config['tencent_sdkappid'];
		
		$id = trim($param['id']);
		$key = "usersign:".$sdkappid.":".$id;
		
		$GLOBALS['redis']->rm($key);
	}
	
	public function clear_all($param)
	{
		$m_config =  load_cache("m_config");//参数
		$sdkappid = $m_config['tencent_sdkappid'];
		
		$id = trim($param['id']);
		$key = "usersign:".$sdkappid.":".$id;
		
		$GLOBALS['redis']->rm($key);
	}
}
?>