<?php 

namespace JFrame{
	ini_set('session.use_trans_sid', 0);
	ini_set('session.use_only_cookies', 1);
	ini_set('session.cookie_httponly', 1);
	
	class Session{

		function __construct(){
			$this->app = App::instance();
		}
		
		function started(){
			return session_id();
		}
		
		function start(){
			if($this->started()) return false;
			session_name($this->session_name());
			session_id($this->session_id());
			session_start();
		}
		
		function restart(){
			$this->destroy();
			$this->start();
		}
		
		function destroy(){
			$session_name = $this->session_name();
			$this->clearCookie($session_name);
			session_unset();
			if($this->started()) session_destroy();
		}
		
		private function session_id(){
			$hash = $this->app->config('hash');
			$timeout = $this->app->config('session_timeout');
			if(!isset($_COOKIE[$this->session_name()])){
				$session_id = md5($hash . microtime(true));
				setcookie($this->session_name(), $session_id, time() + ($timeout * 60), '/');
				return $session_id;
			}else{
				$session_id = $_COOKIE[$this->session_name()];
				setcookie($this->session_name(), $session_id, time() + ($timeout * 60), '/');
				return $session_id;
			}
		}
		
		private function session_name(){
			return md5($this->app->config('application'));
		}
		
		private function clearCookie($key){
			setcookie($key, "", time() - 3600, '/');
			if(!isset($_COOKIE[$key])) return false;
			unset($_COOKIE[$key]);
		}
		
		public function get($var, $default=null){
			if(!$this->started()) $this->start();
			return Vars::getFrom($_SESSION, $var, $default);
		}
		
		public function set($var, $val){
			
		}
	}
}
?>