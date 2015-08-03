<?php

namespace JFrame{
	
	class Module{
		protected $namespace;
		protected $alias;
		protected $templateEngine;
		protected $defaultController;
		
		public function __construct(){
			preg_match('/^\w+/', get_class($this), $match);
			$this->namespace = $match[0];
			if(!$this->defaultController || !is_string($this->defaultController)){
				$this->defaultController = $this->namespace;
			}
		}
		public function config($var){
			if(is_string($var)){
				if(!property_exists($this, $var)) return null;
				$this->{$var} = $val;
			}
		}
		
		public function get($var, $default=null){
			if(property_exists($this, $var)) return $this->{$var};
			return $default;
		}
		
		public function getController($ctrl){
			$class = $this->namespace . '\Controller\\' . $ctrl;
			$ctrl = Loader::getInstance($class);
			die('<xmp>'.print_r($ctrl,1));
		}
		
		public function events(){
			return array();
		}
		
		public function eventListeners(){
			return array();
		}
	}
}

?>