<?php

namespace JFrame{
	
	class Config extends Model{
		protected $debug;
		protected $path;
		protected $modules = array();
		protected $defaultModule;
		protected $routes;
		
		public function __construct(Array $params = array()){
			
		}
		
		public function getRoutes(){
			return $this->routes;
		}
	}
}

?>