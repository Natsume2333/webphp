<?php

    require_once(DOCUMENT_ROOT . '/system/tim/TimRestApi.php');
	/**
	 * sdkappid 是app的sdkappid
	 * identifier 是用户帐号
	 * private_pem_path 为私钥在本地位置
	 * server_name 是服务类型
	 * command 是具体命令
	 */
	 
	function createTimAPI(){

		$m_config =  load_cache("config");//参数
		$sdkappid = $m_config['tencent_sdkappid'];
		$identifier = $m_config['tencent_identifier'];
		// $tencent_sha_key = $m_config['tencent_sha_key'];
		$tencent_sha_key = 0;
		$ret = load_cache("usersign", array("id"=>$identifier));
        
		if ($ret['status'] == 1){
			$private_pem_path = DOCUMENT_ROOT."/system/tim/private_key";
			if (!file_exists($private_pem_path)&&function_exists('log_err_file')) {
				log_err_file(array(__FILE__,__LINE__,__METHOD__,'system/tim/private_key,不存在'));
			}

			$api = createRestAPI();
			$api->init($sdkappid, $identifier,$tencent_sha_key);
			$api->set_user_sig($ret['usersign']);
			
			return $api;
			
		}else{
			return $ret;
		}
	}
	
	/*
	* signature为获取私钥脚本，详情请见 账号登录集成 http://avc.qcloud.com/wiki2.0/im/
	*/
	function get_signature(){
		if(is_64bit()){
			if(PATH_SEPARATOR==':'){
				$signature = "signature/linux-signature64";
			}else{
				$signature = "signature\\windows-signature64.exe";
			}
		}else{
			if(PATH_SEPARATOR==':')
			{
				$signature = "signature/linux-signature32";
			}else{
				$signature = "signature\\windows-signature32.exe";
			}
		}
		return DOCUMENT_ROOT."/system/tim/".$signature;
	}


?>
