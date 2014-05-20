<?php
/**
 * @author Luca Gioppo
 * @created 28/11/2013
 */

include_once('easy.curl.class.php');

class Wso2API{
	private $curl;
	private $curl_options;
	private $api_server;
	private $api_user;
	private $api_password;
	private $isLoggedIn = false;
	
	
	private $login_path = '/publisher/site/blocks/user/login/ajax/login.jag';
	private $create_api_path = '/publisher/site/blocks/item-add/ajax/add.jag';
	private $status_api_path = '/publisher/site/blocks/life-cycles/ajax/life-cycles.jag';
	private $get_api_url = '/publisher/site/blocks/listing/ajax/item-list.jag';
	private $get_all_api_url = '/publisher/site/blocks/listing/ajax/item-list.jag';
	private $get_api_swagger_url1 = '/registry/resource/_system/governance/apimgt/applicationdata/api-docs/';
	private $get_api_swagger_url2 = '/api-doc.json';


	private $debug = false;

	public $error_message = '';
	public $error_code = 0;
	public $response = null;

	function __construct($api_server, $user = 'admin', $password = 'admin', $debug = false){
		$headers[] = "Accept: */*";
		$headers[] = "Connection: Keep-Alive";
		$agent            = "Nokia-Communicator-WWW-Browser/2.0 (Geos 3.0 Nokia-9000i)";
		$cookie_file_path = "cookie.txt";
		$this->curl_options = array(CURLOPT_HTTPHEADER => $headers,
								   CURLOPT_SSL_VERIFYHOST=>0,
								   CURLOPT_SSL_VERIFYPEER=>false,
								   CURLOPT_COOKIEFILE=>$cookie_file_path,
								   CURLOPT_COOKIEJAR=>$cookie_file_path,
								   CURLOPT_FOLLOWLOCATION=>1,
								   CURLOPT_RETURNTRANSFER=>1,
								   CURLOPT_USERAGENT=>$agent,
								   CURLOPT_HEADER=>0);
		$this->api_server = $api_server;
		$this->api_user = $user;
		$this->api_password = $password;
		$this->debug = $debug;
		$this->curl =  new cURL($api_server);
		$this->curl->options($this->curl_options);
		
		
		
	}
	
	public function login($user = '', $password = ''){
		if ($this->debug) error_log(print_r('user: '.$user, TRUE)); 
		if ($this->debug) error_log(print_r('password: '.$password, TRUE)); 
		if(!empty($user)) $this->api_user = $user;
		if(!empty($password)) $this->api_password = $password;
		$login_url = $this->api_server . $this->login_path;
		$login_post = array('action'=>'login',
							'username'=>$this->api_user,
							'password'=>$this->api_password);
		$login_ret = $this->curl->post($login_url, 
										$login_post,
										$this->curl_options);
		// two possible errors cURL and API manager
		if ($this->curl->error_code){
			$this->error_message = 'Login: '.$this->curl->error_string . ' - ' . $login_url . ' - ' . print_r($login_post,true);
			$this->error_code = $this->curl->error_code;
		}else{
			// have to interpret the return code to understand if the API returned error
			$response = json_decode($login_ret);
			if ($this->debug) error_log('login response: '.print_r($response, TRUE));
			if (!empty($response)){
			if ($response->{'error'}){
				$this->error_message = $response->{'message'};
				echo 'error in login';
				return false;
			}
			$this->isLoggedIn = true;
			}
		}
		$this->error_code = $this->curl->error_code;
		return true;
	}
	
	public function create_api($params, $resources, $autopublish = false){
		// if not logged in log with the standard data
		if(!$this->isLoggedIn){
			$login_result = $this->login();
			if (!$login_result){
				return $login_result;
			}
		}
		if (empty($params['name'])){
			$this->error_message = 'You have to define API name';
			return false;
		}
		if (empty($params['version'])){
			$this->error_message = 'You have to define API version';
			return false;
		}
		if (!is_array($resources)){
			$this->error_message = 'You have to define API resources';
			return false;
		}
		
		$create_api_url = $this->api_server . $this->create_api_path;
		
		$create_api_post = array('action'=>'addAPI',
							  'name'=>$params['name'],
							  'visibility'=>$params['visibility'],
							  'version'=>$params['version'],
							  'description'=>(empty($params['description'])?'':$params['description']),
							  'endpointType'=>$params['endpointType'],
							  'http_checked'=>(empty($params['http'])?'':'http'),
							  'https_checked'=>(empty($params['https'])?'':'https'),
							  'endpoint'=>(empty($params['endpoint'])?'':$params['endpoint']),
							  'wsdl'=>(empty($params['wsdl'])?'':$params['wsdl']),
							  'wadl'=>(empty($params['wadl'])?'':$params['wadl']),
							  'tags'=>(empty($params['tags'])?'':$params['tags']),
							  'tier'=>$params['tier'],
							  'bizOwner'=>(empty($params['bizOwner'])?'':$params['bizOwner']),
							  'thumbUrl'=>$params['thumbUrl'],
							  'context'=>$params['context'],
							  'tiersCollection'=>$params['tiersCollection']);
		$create_api_post['resourceCount'] = count($resources) - 1;
		foreach ($resources as $i => $value) {
			$create_api_post['resourceMethod-'.$i] = $value['resourceMethod'];
			$create_api_post['resourceMethodAuthType-'.$i] = $value['resourceMethodAuthType'];
			$create_api_post['resourceMethodThrottlingTier-'.$i] = $value['resourceMethodThrottlingTier'];
			$create_api_post['uriTemplate-'.$i] = $value['uriTemplate'];
		}
		$create_api_ret = $this->curl->post($create_api_url, 
											$create_api_post,
											$this->curl_options);

		// two possible errors cURL and API manager
		if ($this->curl->error_code){
			$this->error_message = 'Create API: '.$this->curl->error_string . ' - ' . $create_api_url . ' - ' . print_r($create_api_post,true);
		}else{
			// have to interpret the return code to understand if the API returned error
			$response = json_decode($create_api_ret);
			if ($response->{'error'}){
				$this->error_message = $response->{'message'};
				return false;
			}
		}
		
		// manage the autopublish option
		if ($autopublish) {
			$status_api_ret = $this->change_api_status('PUBLISHED', $params['provider'], $params['name'], $params['version'], true);
			// maybe only return $status....
			if (!$status_api_ret){
				return $status_api_ret;
			}
		}
		return true;
	}
	
	public function delete_api(){
	}
	
	public function change_api_status($status, $provider, $name, $version, $publishtogataway){
		// if not logged in log with the standard data
		if(!$this->isLoggedIn){
			$login_result = $this->login();
			if ($login_result > 0){
				return $login_result;
			}
		}
		
		if (!is_bool($publishtogataway)){
			$this->error_message = 'publishtogateway has to be boolean';
			return false;
		}
		$status_api_url = $this->api_server . $this->status_api_path;
		
		$status_api_post = array('action'=>'updateStatus',
								'name'=>$name,
								'version'=>$version,
								'provider'=>$provider,
								'status'=>$status,
								'publishToGateway'=>$publishtogataway);
		$publish_api_ret = $this->curl->post($status_api_url, 
											 $status_api_post,
											 $this->curl_options);

		// two possible errors cURL and API manager
		if ($this->curl->error_code){
			$this->error_message = 'Status change: '.$this->curl->error_string;
		}else{
			// have to interpret the return code to understand if the API returned error
			$response = json_decode($publish_api_ret);
			if ($response->{'error'}){
				$this->error_message = $response->{'message'};
				return false;
			}
		}
		return true;
	}
	
	public function get_api_info($apiprovider, $apiname, $apiversion){
		// if not logged in log with the standard data
		if(!$this->isLoggedIn){
			$login_result = $this->login();
			if (!$login_result){
				return $login_result;
			}
		}

	
		$get_api_url = $this->api_server . $this->get_api_url;

		$call_api_post = array('action'  => "getAPI",
								'name' => $apiname,
								'version' => $apiversion,
								'provider' => $apiprovider);

	
		$published_api_ret = $this->curl->post($get_api_url, 
											 $call_api_post,
											 $this->curl_options);
		
		// two possible errors cURL and API manager
		if ($this->curl->error_code){
			$this->error_message = 'Get API info: '.$this->curl->error_string;
		}else{
			// have to interpret the return code to understand if the API returned error
			$this->response = json_decode($published_api_ret);
			if ($this->debug) error_log('Get all APi list response: : '.print_r($this->response, TRUE)); 
			if ($this->response->{'error'}){
				$this->error_message = $this->response->{'message'};
				return false;
			}
		}
		return true;
	
	}	

 
	public function get_all_api_list(){
		// if not logged in log with the standard data
		if(!$this->isLoggedIn){
			$login_result = $this->login();
			if (!$login_result){
				return $login_result;
			}
		}

		$get_api_url = $this->api_server . $this->get_all_api_url . '?action=getAllAPIs';
		$call_api_post = array('action'  => "getAllAPIs",);
		if ($this->debug) error_log('get_api_url: '.print_r($get_api_url, TRUE)); 
		$published_api_list_ret = $this->curl->get($get_api_url, 
											 $this->curl_options);

		// two possible errors cURL and API manager
		if ($this->curl->error_code){
			$this->error_message = 'Get all API list: '.$this->curl->error_string;
		}else{
			// have to interpret the return code to understand if the API returned error
			$this->response = json_decode($published_api_list_ret);
			if ($this->debug) error_log('Get all APi list response: : '.print_r($this->response, TRUE)); 
			if (!empty($this->response)){
				if (!empty($this->response->{'error'})){
					$this->error_message = $this->response->{'message'};
					return false;
				}
			}
		}
		return true;
	}	
	
	
	public function get_api_swagger($apiname, $apiversion){
		// if not logged in log with the standard data
		if(!$this->isLoggedIn){
			$login_result = $this->login();
			if (!$login_result){
				return $login_result;
			}
		}

		$get_swagger_url = $this->api_server . $this->get_api_swagger_url1 .$apiname . '-' .$apiversion . $this->get_api_swagger_url2;
	
		$swagger_api_ret = $this->curl->get($get_swagger_url, 
											 $this->curl_options);
		if ($this->debug) error_log('get_api_swagger_url: '.print_r($get_swagger_url, TRUE)); 
		if ($this->debug) error_log('get_api_swagger: '.print_r($swagger_api_ret, TRUE)); 
		// two possible errors cURL and API manager
		if ($this->curl->error_code){
			$this->error_message = 'Get all API list: '.$this->curl->error_string;
		}else{
			// have to interpret the return code to understand if the API returned error
			$this->response = json_decode($swagger_api_ret);
			if ($this->debug) error_log('Get all APi list response: : '.print_r($this->response, TRUE)); 
			if (!empty($this->response)){
				if (!empty($this->response->{'error'})){
					$this->error_message = $this->response->{'message'};
					return false;
				}
			}
		}
		return true;
	}	

}

?>