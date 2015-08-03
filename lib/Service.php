<?php 

namespace JFrame {
	
	class Service {
		public $message;
		public $errors = array();
		protected $db;
		protected $response;
		
		function __construct(){
			$this->db = DB::getInstance();
			$this->response = new Response;
		}
		
		protected function setError($msg=false){
			$this->message = $msg;
			$this->errors[] = $msg;
			return false;
		}
		public function getResponse(){
			return $this->response;
		}
		protected function method(){
			return strtolower($_SERVER['REQUEST_METHOD']);
		}
	}
}
?>