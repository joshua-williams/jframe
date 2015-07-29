<?php

namespace JFrame{
	define('DS', DIRECTORY_SEPARATOR);
	define('JFRAME_PATH', dirname(__DIR__));
	
	class App{
		private static $_instance;
		private $config;
		private $path;
		private $viewPath = array('default'=>array());
		private $viewExtension = 'html';
		private $modules = array();
		private $defaultModule;
		private $debug = false;
		private $route;
		private $routes = array();
		private $segmentOffset = 0;
		private $templateEngine = false;
		
		function __construct(Array $config = array()){
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
			// debug settings
			if(isset($config['debug'])){
				$this->debug = ($config['debug']) ? true : false;
			}
			// path settings
			if(isset($config['path']) && is_string($config['path'])){
				if(is_dir($config['path'])){
					$this->path = $config['path'];
					chdir($this->path);
				}else{
					if($this->debug){
						die('Applicaton path not found '. $config['path']);
					}
					exit;
				}
			}else{
				$this->path = getcwd();
			}
			//default module settings
			if(isset($config['defaultModule'])){
				$this->defaultModule = $config['defaultModule'];
			}
			// module settings
			if(isset($config['modules'])){
				if(is_array($config['modules'])){
					foreach($config['modules'] as $namespace){
						$this->loadModule($namespace);
					}
				}else{
					if($this->debug){
						die('Config error: array expected in modules');
					}
					exit;
				}
			}
			// route settings
			if(isset($config['routes']) && is_array($config['routes'])){
				foreach($config['routes'] as $route){
					if(!is_array($route)) continue;
					if(!isset($route['uri'])) continue;
					if(!$this->defaultModule && !isset($route['module'])) continue;
					
					$this->routes[] = $route;
				}
			}
			// segment offset settings
			if(isset($config['segmentOffset']) && is_numeric($config['segmentOffset'])){
				$this->segmentOffset = $config['segmentOffset'];
			}
			// set view path(s)
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
			}
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
			$modDir = $this->path . DS . 'modules' . DS . $namespace;
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
			$this->modules[$namespace] = new $class;
			// add modules directory to loader path
			Loader::addPath($this->path . '/modules');
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
			$router = new Router($this);
			if(!$route = $router->route()){
				die('404 not found');
			}
			
			$namespace = ($route->get('module')) ? $route->get('module') : $this->defaultModule;
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
			if($view = $route->get('view')){
				//die('<xmp>'.print_r($route,1));
			}
		}
		
		public function getModuleByAlias($alias){
			foreach($this->modules as $module){
				if($module->get('alias') === $alias) return $module;
			}
			return false;
		}
		
		public function getDefaultModule(){
			if(!$this->defaultModule) return false;
			if(!isset($this->modules[$this->defaultModule])) return false;
			return $this->modules[$this->defaultModule];
		}
		
		public static function getConfig($config, $format='array'){
			if(file_exists("config/$config.php")){
				$config =  include("config/$config.php");
				if( !$config || $config === 1 || !is_array($config)) return false;
			}elseif(file_exists("config/$config.json")){
				$config = json_decode(file_get_contents("config/$config.json"));
				if(!$config) return false;
			}else{
				die('not foundt');
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