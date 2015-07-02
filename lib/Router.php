<?php

namespace JFrame{
	
	class Router{
		private $app;
		private $method;
		private $uri;
		
		function __construct($app){
			$this->app = $app;
			$this->method = strtolower($_SERVER['REQUEST_METHOD']);
		}
		public function route(){
			$this->uri = $_SERVER['REQUEST_URI'];
			$route = $this->resolveStaticRoutes();
			return $route;
		}
		
		private function resolveStaticRoutes(){
			$routes = $this->app->get('routes');
			$modules = $this->app->get('modules');
			$segments = explode('/', trim($this->uri, '/'));
			foreach($routes as $r){
				$variables = array();
				$_segments = explode('/', trim($r['uri'], '/'));
				// make sure route method property matches request method
				$method = strtolower(Vars::getFrom($r, 'method', 'get'));
				if(($method != '*') && $this->method !== $method){ continue;}
				
				if(count($segments) != count($_segments)) continue;
				
				for($a=0; $a<count($segments); $a++){
					$seg = $segments[$a];
					$_seg = $_segments[$a];
					if(preg_match('/:(\w+)/', $_seg, $match)){
						$regex = Vars::getFrom($r, "validate." . $match[1]);
						if($regex){
							if(!preg_match("/$regex/", $seg)){
								continue 2;
							}
						}
						$variables[$match[1]] = $seg;
					}else{
						if($seg != $_seg) continue 2;
					}
				}
				foreach($variables as $key=>$val){
					Vars::set($key, $val);
				}
				return new Route($r);
			}
		}
		
		private function resolveSystemRoutes(){
			/*
			 * MATCH SEGMENT 1 TO DEFAULT MODULE CONTROLLER
			 * segment 1 == default module controller name
			 * segment 1 == default module controller alias
			 * MATCH SEGMENT 1 TO DEFAULT MO
			 * segment 1 == module alias
			 */
			if($defaultModule = $this->app->get('defaultModule')){
				$modules = $this->app->get('modules');
				$class = $defaultModule . '\\Module';
				die('<xmp>'.print_r($class,1));
			}
		}
		
		private function resolveRoute(){
			
		}
	}

	class Route{
		private $method;
		private $module;
		private $controller;
		private $callback;
		private $validation;
		
		function __construct(Array $properties = array()){
			foreach($properties as $key=>$val){
				if(property_exists($this, $key)) $this->{$key} = $val;
			}
		}
		
		function get($property, $default=null){
			if(!property_exists($this, $property)) return $default;
			return $this->{$property};
		}
	}

}

?>