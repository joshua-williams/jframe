<?php
namespace JFrame{
	if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
	if(!defined('PATH_JFRAME')) define('PATH_JFRAME', dirname(__DIR__));
	
	class CLI{
		protected $cmd;
		protected $path;
		
		function __construct(){
			$this->cmd = $this->getCmd();
			if(!$this->cmd) $this->setError('Invalid command');
			$method = get_class($this).'::'.$this->cmd;
			if(!is_callable($method)) $this->setError("$this->cmd is an invalid command");
			$ref = new \ReflectionClass(get_class($this));
			$this->path = dirname(dirname($ref->getFileName()));
			$this->{$this->cmd}();
		}

		function getCmd(){
			if(!isset($_SERVER['argv'])) return false;
			$args = $_SERVER['argv'];
			if(!isset($args[1])) return null;
			return $args[1];
		}
		function getResponse($prompt){
			fwrite(STDOUT, $prompt.': ');
			return trim(fgets(STDIN));
		}
		
		function opt_exists($var,$default=null){
			if(!isset($_SERVER['argv'])) return false;
			$args = $_SERVER['argv'];
			foreach($args as $key=>$val){
				$_var = preg_replace('/^[\-]+/', '', $val);
				if($var != $_var) continue;
				return true;
			}
			return false;
		}

		function opt($var,$default=null){
			if(!isset($_SERVER['argv'])) return false;
			$args = $_SERVER['argv'];
			foreach($args as $key=>$val){
				$_var = preg_replace('/^[\-]+/', '', $val);
				if($var != $_var) continue;
				if(!isset($args[($key+1)])) return $default;
				return $args[($key+1)];
			}
			return $default;
		}
		
		function write($msg){
			fwrite(STDOUT, $msg.PHP_EOL);
		}
		
		function setError($msg=''){
			fwrite(STDOUT, $msg.PHP_EOL);
			exit;
		}

	}
}
?>