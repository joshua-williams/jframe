<?php
namespace JFrame{
	class Response {
		private $data = array();
		private $type;
		private $errors = array();
		private $warnings = array();
		private $messages = array();
		private $successes = array();
		private $return;
		
		function __construct(Array $response = array()){
			if(isset($_SERVER['HTTP_REFERER'])){
				$this->return = $_SERVER['HTTP_REFERER'];
			}elseif($app = App::instance()){
				$this->return = $app->config('site_url');
			}
			$this->set($response);
		}
		
		function setData($prop, $value=null){
			Vars::setTo($this->data, $prop, $value);
		}
		
		function getData($prop=null, $default=null){
			return Vars::getFrom($this->data, $prop, $default);
		}
		
		function set($prop, $val=false){
			switch(gettype($prop)){
				case 'object': case 'array':
					$prop = (array) $prop;
					foreach($prop as $k=>$v){
						if(property_exists($this, $k)) $this->{$k} = $v;
						switch($k){
							case 'message': $this->setMessage($v); break;
							case 'success': $this->setSuccess($v); break;
							case 'warning': $this->setWarning($v); break;
							case 'error': $this->setError($v); break;
						}
					}
					break;
				default:
					switch($prop){
						case 'message': $this->setMessage($v); break;
						case 'success': $this->setSuccess($v); break;
						case 'warning': $this->setWarning($v); break;
						case 'error': $this->setError($v); break;
						default: if(property_exists($this, $prop)) $this->{$prop} = $val;
					}
			}
		}
		public function get($prop, $default=null){
			return (property_exists($this,$prop)) ? $this->{$prop} : $default;
		}
		
		public function prop($prop, $default=false){
			if(!is_string($prop) && !is_numeric($prop)) return $default;
			if(!isset($this->data[$prop])) return $default;
			return $this->data[$prop];
		}
		
		public function properties(){
			$properties =  get_object_vars($this);
			return $properties;
		}
		function hasErrors(){
			return $this->getErrors();
		}
		function getErrors(){
			return $this->errors;
		}
		
		function getWarnings(){
			return $this->warnings;
		}
		function setReturn($url){
			if(!is_string($url)) return false;
			$this->return = $url;
		}
		function setMessage($message){
			switch(gettype($message)){
				case 'string':
					$this->messages[] = $message;
					break;
				case 'object': case 'array':
					$this->messages = array_merge($this->messages, (array)$message);
					break;
			}
			return $this;
		}

		function setSuccess($message){
			switch(gettype($message)){
				case 'string':
					$this->successes[] = $message;
					break;
				case 'object': case 'array':
					$this->successes = array_merge($this->successes, (array)$message);
					break;
			}
			return $this;
		}

		function setError($message){
			switch(gettype($message)){
				case 'string':
					$this->errors[] = $message;
					break;
				case 'object': case 'array':
					$this->errors = array_merge($this->errors, (array)$message);
					break;
			}
			return $this;
		}
		
		function setWarning($message){
			switch(gettype($message)){
				case 'string':
					$this->warnings[] = $message;
					break;
				case 'object': case 'array':
					$this->warnings = array_merge($this->warnings, (array)$message);
					break;
			}
			return $this;
		}
		
		public function json(){
			return json_encode(array(
				'errors' => $this->errors,
				'warnings' => $this->warnings,
				'messages' => $this->messages,
				'successes' => $this->successes
			));
		}
	}
}
?>