<?php

namespace JFrame{
	use \DEBUG;
	
	class Loader{
		private static $paths = array();
		private static $loaded = array();
		private static $instances = array();
		
		public static function load($class){
			if(class_exists($class)) return true;
			// Check to see if namespace was registered in config[module_path]['User'] = '/path/to/module'
			$parts = explode('\\', $class);
			if(in_array($parts[0], array_keys(self::$paths), true)){
				$ns = $parts[0];
				if(in_array($ns, array_keys(self::$loaded), true)) return true;
				array_shift($parts);
				$path = self::$paths[$ns] . DS . Util::path(implode('\\', $parts)) . '.php';
				if(!is_file($path)) return false;
				require_once($path);
				self::$loaded[$class] = $path;
				return true;
			}else{
				// try to load according to psr3 spect in all registered paths
				$path = Util::path("$class.php");
				if(isset(self::$loaded[$class])) return true;
				foreach(self::$paths as $basePath){
					$filePath = "$basePath/$path";
					if(!is_file($filePath)) continue;
					self::$loaded[$class] = $filePath;
					require_once("$basePath/$path");
					return true;
				}
			}
			return false;
		}
		
		public static function get($class, $params=false){
			if(!self::load($class)) return false;
			if(!class_exists($class)) return false;
			$instance = ($params) ? new $class($params) : new $class;
			self::$instances[$class] = &$instance;
			return $instance;
		}

		public static function getInstance($class, $params=false){
			if(!isset(self::$instances[$class])){
				return self::get($class, $params);
			}
			return self::$instances[$class];
		}
		
		public static function addPath($path, $namespace=false){
			if(!is_string($path)) return false;
			if(in_array($path, self::$paths)) return false;
			if($namespace){
				self::$paths[$namespace] = $path;
			}else{
				self::$paths[] = $path;
			}
		}
	}
}
?>