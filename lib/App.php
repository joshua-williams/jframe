<?php

namespace JFrame{
	if(!defined('DS')) define('DS', DIRECTORY_SEPARATOR);
	if(!defined('PATH_JFRAME')) define('JFRAME_PATH', dirname(__DIR__));

	require_once(__DIR__ . DS . 'Util.php');
	spl_autoload_register(function($class){
        // autoload jframe library
		if(preg_match('/^JFrame/', $class)){
			$path = preg_replace('/^JFrame\\\/', '', $class);
			$path = __DIR__ . DS . Util::path($path) . '.php';
			if(!file_exists($path)) return;
			require_once($path);
		}else{
            // autoload module class
            $path = getcwd() . DS . 'modules' . DS . Util::path($class) . '.php';
            if(!file_exists($path)) return;
            require_once($path);
        }
	});
	
	class App{
		private static $_instance;
		private $initialized = false;
		private $isFormSubmission;
		private $config;
		private $viewExtension = 'html';
		private $route;
		private $routes = array();
		private $modules = array();
		private $events = array();
		private $viewPath = array('default'=>array());
		
		function __construct(Array $config = array()){
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
			chdir($this->config('path'));
			$this->loadEvents();
			$this->loadEventListeners();
		}

		public function init(){
			$this->initialized = true;
			App::instance($this);
			$this->session = new Session();
			$this->session->start();
			
			$this->processForm();
			
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

		private function processForm(){
			$encKey = ($k = $this->config('enc_key')) ? $k : ' ';
			$tokenName = md5($this->config('hash') . 'submit');
			if(!$tokenValue = Vars::get($tokenName)) return false;
			$this->isFormSubmission = true;
			$json = Util::decrypt($encKey, $tokenValue);
			if(!$data = json_decode($json)) return false;
			$formTimeout = $this->config('form_timeout');
				
			if($formTimeout && is_numeric($formTimeout)){
				$timeLapsed = time() - $data->time;
				$maxTime = $formTimeout * 60;
				// make sure the form has not lapsed
				if($timeLapsed > $maxTime){
					$this->redirect(Vars::getFrom($_SERVER, 'HTTP_REFERER', $this->config('site_url')));
				}
			}
			
			// make sure session variable matches what is submitted
			$sessionKey = md5($data->form);
			if(!$sessionToken = $this->session->get("token.form.$sessionKey")){
				$this->redirect(Vars::getFrom($_SERVER, 'HTTP_REFERER', $this->config('site_url')));
			}
			if(($sessionToken['form'] != $data->form) || ($sessionToken['time'] != $data->time)){
				$this->redirect(Vars::getFrom($_SERVER, 'HTTP_REFERER', $this->config('site_url')));
			}
			if(!$form = Loader::get($data->form)) return false;
				
			$response = $form->action();
			if(is_object($response) && get_class($response) == 'JFrame\Response'){
				$return = ($r = $response->get('return')) ? $r : $this->config('site_url');
				$this->session->set('flashMessage', $response);
				$this->redirect($return);
			}else{
				$this->redirect($this->config('site_url'));
			}
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
				if($this->config('debug')){
					die("Module directory not found: $modDir");
				}
				exit;
			}
			$modPath = $modDir . DS . 'Module.php';
			if(!is_file($modPath)){
				if($this->config('debug')){
					die("Module path not found: $modPath");
				}
				exit;
			}
			require_once($modPath);
			$class = "$namespace\Module";
			if(!class_exists($class)){
				if($this->config('debug')){
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
		
		
		public function getModuleByAlias($alias){
			foreach($this->modules as $module){
				if($module->get('alias') === $alias) return $module;
			}
			return false;
		}
		
		public function getDefaultModule(){
			if(!$this->config('default_module')) return false;
			if(!isset($this->modules[$this->config('default_module')])) return false;
			return $this->modules[$this->config('default_module')];
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
				'template_engine' => false,
				'segment_offset' => 0,
				'modules' => false,
				'default_module' => false,
			);
			
			// merge static config with default config
			$path = rtrim(Vars::getFrom($config, 'path', $this->config['path']));
			$configPath = $path . DS . 'config' . DS . 'config.php';
			$cnf = (file_exists($configPath)) ? include($configPath) : false;
			if(is_array($cnf)){
				foreach($this->config as $key=>$val){
					if(!isset($cnf[$key])) continue;
					if(!is_numeric($cnf[$key]) && !is_string($cnf[$key]) && !is_bool($cnf[$key])) continue;
					$this->config[$key] = $cnf[$key];
				}
			}
			// merge user defined config into application config overriding static config
			foreach($config as $key=>$val){
				if(!isset($this->config[$key])) continue;
				if($key == 'modules'){
					if(is_array($val)){
						$this->config['modules'] = $val;
					}else{
						$this->config['modules'] = $val;
					}
					continue;
				}
				$this->config[$key] = $val;
			}
			// without any modules defined the application can do nothing so die.
			if(!$this->config['modules']){
				if($this->config('debug')) echo "There are not modules defined in this application.";
				exit;
			}
			// modules config property support comma seperated string or array of modules
			if(is_string($this->config['modules'])){
				$modules = array();
				foreach(explode(',', $this->config['modules']) as $module){
					$modules[] = trim($module);
				}
				$this->config['modules'] = $modules;
			}
			
			// makde sure application path exists
			if(!is_dir($this->config['path'])){
				if($this->config('debug')) echo 'Application path not found: ' . $this->config['path'];
				exit;
			}
			
			// load modules
			if(is_array($this->config['modules'])){
				foreach($this->config['modules'] as $module){
					if(!is_string($module)) continue;
					$this->loadModule($module);
				}
			}
			
			// if routes are defined in user config it will load those routes
			// $config[routes] could equal false then can be added with $app->addRoute before $app->init method is called
			if(isset($config['routes'])){
				if(is_array($config['routes'])){
					foreach($config['routes'] as $route){
						$this->addRoute($route);
					}
				}
			}else{
				// if $config[routes] are not set it will try to load from config/routes.php
				$routePath = $this->config['path'] . DS . 'config' . DS . 'routes.php';
				if(is_file($routePath)){
					if(($routes = include($routePath)) && (is_array($routes))){
						foreach($routes as $route){
							$this->addRoute($route);
						}
					}
				}
			}
		}
		/**
		 * @desc Static routes will be overwritten by routes passed in application construct. This can be called on application instance before initialization.
		 * @param Array $route [uri, module, controller, callback]
		 */
		public function addRoute(Array $route){
			if(!isset($route['uri'])) return;
			if(!$this->config['default_module'] && !isset($route['module'])) return;
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
		
		public function redirect($url){
			$file = fopen('log/log.txt', 'a+');
			fwrite($file, time()  . ' | Redirect' . chr(10));
			fclose($file);
			header("Location: $url");
			exit;
		}
	}
	
}

?>