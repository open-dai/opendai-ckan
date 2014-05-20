<?php
/**
 * @author Luca Gioppo
 * @created 28/11/2013
 */
include('wso2api.class.php');
 
 class Ckan_converter{
	private $wso2api;
	private $debug = false;
 
 	function __construct($api_server, $user = 'admin', $password = 'admin', $debug = false){
		$this->wso2api = new Wso2API($api_server,$user, $password, $debug);
		$this->debug = $debug;
		if ($this->debug){
			error_log(print_r('api_server: '.$api_server, TRUE));
			error_log(print_r('user: '.$user, TRUE));
			error_log(print_r('password: '.$password, TRUE));
		}; 
	}

	
	function getPackages(){
		$apis = array();
		
		$ret = $this->wso2api->get_all_api_list();
		if ($ret){
			foreach ($this->wso2api->response->apis as $value){
				if ($value->status == 'PUBLISHED'){
					$finishname = $value->name . ':' . $value->version . ':' . $value->provider;
					$apis[] = $finishname;
				}
			}
		}else{
			echo 'Error message' . $this->wso2api->error_message;
			echo 'Error message code' . $this->wso2api->error_code;
		}

		return $apis;
	}
	
	function getAPI($apiname, $apiversion, $apiprovider){
		// variables for holding the whole API data
		$thePackage;
		$theAPI;
		$swagger;
		
		// getting all the API to get the correct one
		$retList = $this->wso2api->get_all_api_list();
		if ($retList){
			foreach ($this->wso2api->response->apis as $value){
				if (($value->name == $apiname)&&($value->version == $apiversion)&&($value->provider == $apiprovider)){
					$thePackage = $value;
					if ($this->debug) error_log('Response: ' . print_r($thePackage, TRUE)); 
				}
			}
		}else{
			echo 'Error message' . $this->wso2api->error_message;
			echo 'Error message code' . $this->wso2api->error_code;
		}
		
		// getting the API detail
		$retDetail = $this->wso2api->get_api_info($apiprovider, $apiname, $apiversion);
		if ($retDetail){
			if ($this->debug) error_log('API: ' . print_r($this->wso2api->response, TRUE)); 
			$theAPI = $this->wso2api->response;
		}else{
			echo 'Error message' . $this->wso2api->error_message;
			echo 'Error message code' . $this->wso2api->error_code;
		}
		
		// getting swagger
		$retSwagger = $this->wso2api->get_api_swagger($apiname, $apiversion);
		if ($retSwagger){
			if ($this->debug) error_log('Swagger: ' . print_r($this->wso2api->response, TRUE)); 
			$swagger = $this->wso2api->response;
		}else{
			echo 'Error message' . $this->wso2api->error_message;
			echo 'Error message code' . $this->wso2api->error_code;
		}
		
		$ckanapi = array();
		$ckanapi["license_title"]=$swagger->info->license;
		$ckanapi["mantainer"]=$theAPI->api->techOwner;
		$ckanapi["mantainer_email"]=$theAPI->api->techOwnerMail;
		$ckanapi["id"]=$apiname .':'. $apiversion .':'. $apiprovider;
		$ckanapi["metadata_created"]=date ("d-m-Y H:i:s", $theAPI->api->lastUpdated/1000);
		$ckanapi["relationships"]=array();
		$ckanapi["license"]=$swagger->info->license;
		$ckanapi["metadata_modified"]=date ("d-m-Y H:i:s", $theAPI->api->lastUpdated/1000);
		$ckanapi["author"]=$theAPI->api->bizOwner;
		$ckanapi["author_email"]=$theAPI->api->bizOwnerMail;
		$ckanapi["version"]=$apiversion;
		$ckanapi["licence_id"]='';
		$ckanapi["type"]=null;
		$ckanapi["resources"]=array();
		$ckanapi["tags"]=split(',', $theAPI->api->tags);
		$ckanapi["tracking_summary"]=array("total"=>0,"summary"=>0);
		$ckanapi["groups"]='';
		$ckanapi["name"]=$apiname;
		$ckanapi["isopen"]=true;
		$ckanapi["notes_rendered"]='';
		$ckanapi["url"]=$swagger->basePath . '/' . $apiname . '/' . $apiversion;
		$ckanapi["ckan_url"]='';
		$ckanapi["notes"]=$theAPI->api->description;
		$ckanapi["title"]=$apiname;
		$ckanapi["ratings_average"]=null;
		$ckanapi["extras"]=array("release-notes"=>'',"unpublished"=>false,"publish-date"=>'');
		$ckanapi["rating_count"]='';
		$ckanapi["revision_id"]='';
		return $ckanapi;
	}
 
 }
 
 
 ?>