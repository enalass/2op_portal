<?php
defined('BASEPATH') OR exit('No direct script access allowed');


class Conexionpacs
{
	

	protected $ci;

	protected $base_url;

	protected $user;

	protected $password;

	public function __construct()
	{
        $this->ci =& get_instance();
        $this->base_url = "URL";
        $this->user = "user";
        $this->password = "pass";
        
	}

	public function sendClient(){
		$url = $this->base_url . "api";


		$datos = array(
			"login" => $this->user
			,"pass" => $this->password
			,"parameters" => array("full_data"=>"true")
		);

		$body = json_encode($datos);

		$respuesta = $this->postCURL($url,$body);

		return $respuesta;
	}

	public function sendOrder($order){
		$url = $this->base_url . "register_order";
		$postField = "login={$this->user}&pass={$this->password}&parameters=";

		$postField.=json_encode($order);

		$respuesta = $this->postCURLOrder($url,$postField);

		return $respuesta;
	}

	public function consultaCURL($url){
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_CUSTOMREQUEST => 'GET',
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_URL => $url,
		    CURLOPT_HTTPHEADER => array(
		    	'Content-Type: application/x-www-form-urlencoded'
		    )
		));
		$response = curl_exec($ch);

		return $response;
	}

	public function postCURL($url,$body){
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_CUSTOMREQUEST => 'POST',
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_URL => $url,
		    CURLOPT_ENCODING => 'gzip',
		    CURLOPT_HTTPHEADER => array(
		    	'Content-Type: application/x-www-form-urlencoded'
		    	,'Content-Length: ' . strlen("login=USER&pass=PASS&parameters={\"full_data\":\"true\"}")
		    	,'Cache-Control: no-cache'
		    	,'Accept: */*'
		    	,'Connection: keep-alive'
		    	,'Accept-Encoding: gzip, deflate, br'
		    ),
		    CURLOPT_POST => true,
		    CURLOPT_POSTFIELDS => "login=USER&pass=PASS&&parameters={\"full_data\":\"true\"}",
		));
		$response = curl_exec($ch);

		return $response;
	}

	public function postCURLOrder($url,$postField){
		$ch = curl_init();
		curl_setopt_array($ch, array(
			CURLOPT_CUSTOMREQUEST => 'POST',
		    CURLOPT_RETURNTRANSFER => true,
		    CURLOPT_URL => $url,
		    CURLOPT_ENCODING => 'gzip',
		    CURLOPT_HTTPHEADER => array(
		    	'Content-Type: application/x-www-form-urlencoded'
		    	,'Content-Length: ' . strlen($postField)
		    	,'Cache-Control: no-cache'
		    	,'Accept: */*'
		    	,'Connection: keep-alive'
		    	,'Accept-Encoding: gzip, deflate, br'
		    ),
		    CURLOPT_POST => true,
		    CURLOPT_POSTFIELDS => $postField,
		));
		$response = curl_exec($ch);

		return $response;
	}

}