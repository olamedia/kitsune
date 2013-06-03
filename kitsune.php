<?php


class kitsuneResponse{
	public $code;
	public $headers;
	public $body;
}

class kitsune{
	protected $_globalHeaders = array();
	protected $_headers = array();
	protected $_cookies = array();
	protected $_protocol = 'HTTP/1.1';
	protected $_curl;
	protected $_response;
	protected $_useProxy = false;
	protected $_proxyHost = null;
	protected $_proxyPort = null;
	public function __construct(){
		$this->_curl = curl_init();
		$this->_response = new kitsuneResponse();
		$this->_initHeaders();
	}
	public function useProxy($host, $port){
		$this->_useProxy = true;
		$this->_proxyHost = $host;
		$this->_proxyPort = $port;
	}
	public function disableProxy(){
		$this->_useProxy = false;
	}
	public function setGlobalHeader($key, $value){
		if (null === $value){
			unset($this->_globalHeaders[strtolower($key)]);
		}else{
			$this->_globalHeaders[strtolower($key)] = $key.': '.$value;
		}
	}
	public function resetHeaders(){
		$this->_headers = array();
	}
	public function setHeader($key, $value){
		if (null === $value){
			unset($this->_headers[strtolower($key)]);
		}else{
			$this->_headers[strtolower($key)] = $key.': '.$value;
		}
	}
	protected function _getHeader($id, $default = null){
		return isset($this->_headers[$id])?$this->_headers[$id]:(isset($this->_globalHeaders[$id])?$this->_globalHeaders[$id]:$default);
	}
	protected function _initHeaders(){
		$this->setGlobalHeader('User-Agent', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.8.0.1) Gecko/20060111 Firefox/1.5.0.1');
		$this->setGlobalHeader('Accept', 'text/xml,application/xml,application/xhtml+xml,text/html;q=0.9,text/plain;q=0.8,image/png,*/*;q=0.5');
		$this->setGlobalHeader('Accept-Languages', 'ru-ru,ru;q=0.8,en-us;q=0.5,en;q=0.3');
		$this->setGlobalHeader('Accept-Encoding', 'none');
		$this->setGlobalHeader('Accept-Charset', 'windows-1251,utf-8;q=0.7,*;q=0.7');
		$this->setGlobalHeader('Keep-Alive', '300');
		$this->setGlobalHeader('Connection', 'keep-alive');
	}
	public function get($url){
		return $this->_open($url, 'GET');
	}
	public function post($url, $postvars = array()){
		return $this->_open($url, 'POST', $postvars);
	}
	public function getCode(){
		return $this->_response->code;
	}
	public function getHeaders(){
		return $this->_response->headers;
	}
	public function getBody(){
		return $this->_response->body;
	}
	public function getCookies(){
		return $this->_cookies;
	}
	//There is really a problem of transmitting $_POST data with curl in php 4+ at least.
	//This is improved encoding function by Alejandro Moreno to work properly with mulltidimensional arrays.
	protected function _postEncode($data, $keyprefix = "", $keypostfix = "") {
		assert( is_array($data) );
		$vars=null;
		foreach($data as $key=>$value) {
			if(is_array($value)) $vars .= data_encode($value, $keyprefix.$key.$keypostfix.urlencode("["), urlencode("]"));
			else $vars .= $keyprefix.$key.$keypostfix."=".urlencode($value)."&";
		}
		return $vars;
	}
	protected function _parse($response){
		// Split response into header and body sections
		list($response_headers, $response_body) = explode("\r\n\r\n", $response, 2);
		$response_header_lines = explode("\r\n", $response_headers);
		// First line of headers is the HTTP response code
		$http_response_line = array_shift($response_header_lines);
		if(preg_match('@^HTTP/[0-9]\.[0-9] ([0-9]{3})@',$http_response_line, $matches)) { $response_code = $matches[1]; }
		// put the rest of the headers in an array
		$response_header_array = array();
		foreach($response_header_lines as $header_line){
			list($header,$value) = explode(': ', $header_line, 2);
			@$response_header_array[$header] .= $value."\n";
		}
		return array($response_code, $response_header_array, $response_body);
	}
	protected function _open($url, $method = 'GET', $postvars = array()){
		$parsedurl = parse_url($url);
		curl_setopt($this->_curl, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($this->_curl, CURLOPT_CONNECTTIMEOUT, 300);
		//curl_setopt($this->curl, CURLOPT_LOW_SPEED_TIME, $this->opt_low_speed_time);
		//curl_setopt($this->curl, CURLOPT_TIMEOUT, $this->opt_timeout);
		curl_setopt($this->_curl, CURLOPT_FOLLOWLOCATION, 1);
		curl_setopt($this->_curl, CURLOPT_MAXREDIRS, 2);
		curl_setopt($this->_curl, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_1);
		curl_setopt($this->_curl, CURLOPT_USERAGENT, $this->_getHeader('user-agent', ''));
		curl_setopt($this->_curl, CURLOPT_REFERER, $this->_getHeader('referer', ''));
		curl_setopt($this->_curl, CURLOPT_URL, $url);
		curl_setopt($this->_curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($this->_curl, CURLOPT_HEADER, 1);
		if ($this->_useProxy){
			curl_setopt($this->_curl, CURLOPT_PROXY, $this->proxyHost);
			curl_setopt($this->_curl, CURLOPT_PROXYPORT, $this->proxyPort);
		}
		if ('POST' == $method){
			curl_setopt($this->_curl, CURLOPT_POST, 1);
			curl_setopt($this->_curl, CURLOPT_POSTFIELDS, substr($this->_postEncode($postvars), 0, -1) );
		}else{
			curl_setopt($this->_curl, CURLOPT_POST, 0);
		}
		$headers = array_values(array_replace($this->_globalHeaders, $this->_headers));
		if (count($this->_cookies)){
//			foreach($this->_cookies as $cookie) { 
				array_push($headers, "Cookie: ".implode('; ', $this->_cookies)); 
	//		}
		}
		if (count($headers)){
			curl_setopt($this->_curl, CURLOPT_HTTPHEADER, $headers);
		}
		//var_dump($headers);
		// request here
		$response = curl_exec($this->_curl);
		list($code, $headers, $body) = $this->_parse($response);
		$this->_response->code = $code;
		$this->_response->headers = $headers;
		$this->_response->body = $body;
		// set cookies
		//var_dump($this->_cookies);
		if (isset($this->_response->headers["Set-Cookie"])){
			$cookies = explode("\n", $this->_response->headers["Set-Cookie"]);
			foreach ($cookies as $fullvalue) {
				@list($key, $value) = explode('=', $fullvalue, 2);
				if (trim($value) == ''){
					unset($this->_cookies[$key]);
				}else{
					if (false !== strpos($value, ';')){
						@list($value, $params) = explode(';', $value, 2);
						$this->_cookies[$key] = $key.'='.$value;
					}
				}
			}
		}
		//var_dump($this->_cookies);
		$this->setHeader('Referer', $url);
	}
}


