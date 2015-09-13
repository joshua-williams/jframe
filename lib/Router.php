<?php

namespace JFrame{
	
	class Router{
		private $app;
		private $method;
		private $uri;
		
		function __construct($app){
			$this->app = $app;
			$this->method = strtolower($_SERVER['REQUEST_METHOD']);
			$this->uri = preg_replace('/\?.*$/', '', trim($_SERVER['REQUEST_URI'], '/'));
		}
		public function route(){
			if($route = $this->resolveStaticRoutes()) return $route;
			$route = $this->resolveSystemRoutes();
			return $route;
		}
		
		private function resolveStaticRoutes(){
			$routes = $this->app->get('routes');
			$modules = $this->app->get('modules');
			$segments = explode('/', trim($this->uri, '/'));
			foreach($routes as $r){
				if(DEFINED('DEBUG_ROUTER')) echo $r['uri'] . '<br>';
				$validate = Vars::getFrom($r, 'validate');
				$variables = array();
				$_segments = explode('/', trim($r['uri'], '/'));
				// make sure route method property matches request method
				$method = strtolower(Vars::getFrom($r, 'method', 'get'));
				if(($method != '*') && $this->method !== $method){ continue;}
				
				if(count($segments) != count($_segments)) continue;
				
				for($a=0, $b=0; $a<count($segments); $a++){
					$seg = $segments[$a];
					$_seg = $_segments[$a];
					if(preg_match('/:(\w+)/', $_seg, $match)){
						$b++;
						if($validate){
							if(is_string($validate)){
								$regex = $validate;
							}elseif(is_array($validate)){
								if(!isset($validate[$b-1])) continue 2;
								$regex = $validate[$b-1];
							}else{
								continue 2;
							}
							$pattern = "/$regex/";
							if(!preg_match($pattern, $seg)) continue 2;
						}
						$varName = $match[1];
						$variables[$varName] = $seg;
					}else{
						if($seg != $_seg) continue 2;
					}
				}
				
				foreach($variables as $key=>$val){
					Vars::set($key, $val);
				}
				$r['module'] = Vars::getFrom($r, 'module', $this->app->config('default_module'));
				$route = new Route($r);
				return $route;
			}
		}
		
		private function getModule(){
			
		}
		private function resolveSystemRoutes(){
			$defaultModule = $this->app->config('default_module');
			$modules = $this->app->get('modules');
			$ext = $this->app->get('viewExtension');
			if(!$modules) return false;
			/*
			 * MATCH SEGMENT 1 TO DEFAULT MODULE CONTROLLER
			 * segment 1 == default module controller name
			 * segment 1 == default module controller alias
			 * MATCH SEGMENT 1 TO DEFAULT MO
			 * segment 1 == module alias
			 */
			$segment = explode('/', trim($this->uri, '/'));
			switch(count($segment)){
				case 1:
					if($segment[0]){
						
						if(!$module = $this->app->getModuleByAlias($segment[0])){
							if(!$module = $this->app->getDefaultModule()) return false;
						}
						return new Route(array(
							'module' => $module->get('namespace'),
							'controller' => $module->get('defaultController'),
							'callback' => $segment[0],
						));
					}else{
						// if segment 1 is empty (home page)
						if(!$defaultModule) return false;
						if(!isset($modules[$defaultModule])) return false;
						$module = $modules[$defaultModule];
						if(!$ctrl = $module->get('defaultController')){
							$ctrl = $module->get('namespace');
						}
						$route = new Route(array(
							'module' => $module->get('namespace'),
							'controller' => $ctrl,
							'callback' => 'index',
							'view' => "index.$ext",
						));
						return $route;
					}
					break;
			}
			return false;
		}
		
		private function resolveRoute(){
			
		}
	}

	class Route{
		private $method;
		private $module;
		private $controller;
		private $callback;
		private $validate;
		private $view;
		
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