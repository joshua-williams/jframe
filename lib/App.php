<?php

namespace JFrame{
	if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
	if(!defined('PATH_JFRAME')) define('JFRAME_PATH', dirname(__DIR__));
	
	// autoload jframe library
	require_once(__DIR__ . DS . 'Util.php');
	spl_autoload_register(function($class){
		if(preg_match('/^JFrame/', $class)){
			$path = preg_replace('/^JFrame\\\/', '', $class);
			$path = __DIR__ . DS . Util::path($path) . '.php';
			if(!file_exists($path)) return;
			require_once($path);
		}
	});
	
	class App{
		private static $_instance;
		private $initialized = false;
		private $config;
		private $viewPath = array('default'=>array());
		private $viewExtension = 'html';
		private $modules = array();
		private $route;
		private $events = array();
		private $routes = array();
		
		function __construct(Array $config = array()){
			// set site url constant
			$site_url = Vars::getFrom($config, 'site_url', 'http://'. $_SERVER['HTTP_HOST']);
			$site_url_ssl = Vars::getFrom($config, 'site_url_ssl', 'https://'. $_SERVER['HTTP_HOST']);
			define('SITE_URL', $site_url);
			define('SITE_URL_SSL', $site_url_ssl);
		
			/* set view path(s)
			if(isset($config['viewPath'])){
				$path = $config['viewPath'];
				if(is_string($path) && is_dir($path)){
					$this->viewPath['default'][] = $path;
				}elseif(is_array($path)){
					foreach($path as $alias=>$p){
						if(!is_dir($p)) continue;
						if(preg_match('/[a-zA-Z0-9\_]+/', $alias)){
							$this->viewPath[$alias] = $p;
						}
					}
				}
			}*/
			$this->loadConfig($config);
			$this->loadEvents();
			$this->loadEventListeners();
			App::instance($this);
		}
		
		public static function instance($app=null){
			if($app===null){
				return self::$_instance;
			}elseif(is_object($app)){
				if(get_class($app) == 'JFrame\App'){
					self::$_instance = $app;
				}
			}
		}
		private function loadModule($namespace){
			if(!is_string($namespace)){
				return false;
			}
			if($this->moduleLoaded($namespace)) return false;
			$modDir = $this->config['path'] . DS . 'modules' . DS . $namespace;
			if(!is_dir($modDir)){
				if($this->debug){
					die("Module directory not found: $modDir");
				}
				exit;
			}
			$modPath = $modDir . DS . 'Module.php';
			if(!is_file($modPath)){
				if($this->debug){
					die("Module path not found: $modPath");
				}
				exit;
			}
			require_once($modPath);
			$class = "$namespace\Module";
			if(!class_exists($class)){
				if($this->debug){
					die("Module class not found $class");
				}
				exit;
			}
			$module = new $class;
			$this->modules[$namespace] = &$module;
			// add modules directory to loader path
			Loader::addPath($this->config['path'] . '/modules');
			return $module;
		}

		public function get($property, $default=null){
			if(!isset($this->{$property})) return $default;
			return $this->{$property};
		}
		
		private function moduleLoaded($namespace){
			foreach($this->modules as $module){
				$class = get_class($module);
				if(preg_match('/^'.$namespace.'\\\/', $class)){ return true; }
			}
			return false;
		}
		
		public function init(){
			$this->initialized = true;
			// makde sure application path exists
			if(!is_dir($this->config['path'])){
				if($this->config['debug']) echo 'Application path not found: ' . $this->config['path'];
				exit;
			}
			// route to controller
			$router = new Router($this);
			if(!$route = $router->route()){
				die('404 not found');
			}
			
			$this->dispatchEvent('Router.Route', array('route'=>$route));
			$namespace = $route->get('module');
			if($controller = $route->get('controller')){
				$ctrlResponse = false;
				$this->route = $route;
				$ctrlClass = "$namespace\Controller\\$controller";
				if($ctrl = Loader::get($ctrlClass)){
					if($callback = $route->get('callback')){
						if(is_callable("$ctrlClass::$callback")){
							$ctrlResponse = $ctrl->{$callback}();
						}
					}
				}
			}
		}
		
		public function getModuleByAlias($alias){
			foreach($this->modules as $module){
				if($module->get('alias') === $alias) return $module;
			}
			return false;
		}
		
		public function getDefaultModule(){
			if(!$this->config('defaultModule')) return false;
			if(!isset($this->modules[$this->config('defaultModule')])) return false;
			return $this->modules[$this->config('defaultModule')];
		}
		
		private function loadEvents(){
			$this->events['Router'] = array(
				'Route' => array()
			);
			foreach($this->modules as $module){
				if(!$events = $module->events()) continue;
				if(!is_array($events)) continue;
				$namespace = $module->get('namespace');
				foreach($events as $event){
					if(!isset($this->events[$namespace])){
						$this->events[$namespace] = array();
					}
					$this->events[$namespace][$event] = array();
				}
			}
		}
		
		private function loadEventListeners(){
			foreach($this->modules as $module){
				$listeners = $module->eventListeners();
				if(!is_array($listeners)) continue;
				foreach($listeners as $event=>$listener){
					$parts = explode('.', $event);
					if(count($parts) != 2) continue;
					$namespace = $parts[0];
					$evt = $parts[1];
					if(!isset($this->events[$namespace])) continue;
					if(!isset($this->events[$namespace][$evt])) continue;
					if(!is_object($listener)) continue;
					if(get_class($listener) != 'Closure') continue;
					$this->events[$namespace][$evt][] = $listener;
				}
			}
		}
		public function dispatchEvent($event, Array $data=array()){
			if(!is_array($data)) return false;
			if(!is_string($event)) return false;
			$parts = explode('.', $event);
			if(count($parts) != 2) return false;
			$namespace = $parts[0];
			$evt = $parts[1];
			if(!isset($this->events[$namespace])) return false;
			if(!isset($this->events[$namespace][$evt])) return false;
			foreach($this->events[$namespace][$evt] as $callback){
				$event = new Event($data);
				$callback($event);
				if($event->preventDefault()){
					break;
				}
			}
		}
		
		public function config($property){
			if(!is_string($property)) return null;
			if(!isset($this->config[$property])) return null;
			return $this->config[$property];
		}
		
		private function loadConfig(Array $config = array()){
			$this->config = array(
				'debug' => false,
				'path' => rtrim(dirname(getcwd())),
				'application' => Vars::getFrom($_SERVER, 'HTTP_HOST', 'Application'),
				'site_url' => false,
				'site_url_ssl' => false,
				'sesson_timeout' => 30,
				'form_timeout' => 10,
				'hash' => 'application_hash',
				'enc_key' => 'my_encryption_key',
				'templateEngine' => false,
				'segmentOffset' => 0,
				'modules' => false,
				'defaultModule' => false,
			);
			// merge static config with default config
			$path = rtrim(Vars::getFrom($config, 'path', $this->config['path']));
			$configPath = $path . DS . 'config' . DS . 'config.php';
			$cnf = (file_exists($configPath)) ? include($configPath) : false;
			if(is_array($cnf)){
				foreach($this->config as $key=>$val){
					if(!isset($cnf[$key]) || !is_string($cnf[$key])) continue;
					$this->config[$key] = $cnf[$key];
				}
			}
			// merge user defined config into application config overriding static config
			foreach($this->config as $key=>$val){
				if(!isset($config[$key]) || !is_string($config[$key])) continue;
				$this->config[$key] = $val;
			}
			// load modules
			if(is_array($this->config['modules'])){
				foreach($this->config['modules'] as $module){
					if(!is_string($module)) continue;
					$this->loadModule($module);
				}
			}elseif(is_string($this->config['modules'])){
				$modules = explode(',', $this->config['modules']);
				foreach($modules as $module){
					$this->loadModule(trim($module));
				}
			}
			// route settings
			if(isset($config['routes']) && is_array($config['routes'])){
				foreach($config['routes'] as $route){
					$this->addRoute($route);
				}
			}
			$routePath = $this->config['path'] . DS . 'config' . DS . 'routes.php';
			if(is_file($routePath)){
				if(($routes = include($routePath)) && (is_array($routes))){
					foreach($routes as $route){
						$this->addRoute($route);
					}
				}
			}
		}
		/**
		 * @desc Static routes will be overwritten by routes passed in application construct
		 * @param Array $route [uri, module, controller, callback]
		 */
		public function addRoute(Array $route){
			if(!isset($route['uri'])) return;
			if(!$this->config['defaultModule'] && !isset($route['module'])) return;
			foreach($this->routes as $key=>$_route){
				if($_route['uri'] == $route['uri']){
					return;
				}
			}
			$this->routes[] = $route;
		}
		
		public static function getConfig($config, $format='array'){
			if(file_exists("config/$config.php")){
				$config =  include("config/$config.php");
				if( !$config || $config === 1 || !is_array($config)) return false;
			}elseif(file_exists("config/$config.json")){
				$config = json_decode(file_get_contents("config/$config.json"));
				if(!$config) return false;
			}else{
				return false;
			}
			switch(strtolower($format)){
				case 'object':  Util::toObject($config); break;
				case 'array': default: Util::toArray($config); break;
			}
			return $config;
		}
	}
	
}

?>