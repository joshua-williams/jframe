<?php

namespace JFrame{
	use \DEBUG;
	use \App;
	class Loader{
		private static $paths = array();
		private static $loaded = array();
		private static $instances = array();
		
		public static function load($class){
			$path = Util::path("$class.php");
			if(isset(self::$loaded[$class])) return true;
			foreach(self::$paths as $basePath){
				$filePath = "$basePath/$path";
				if(!is_file($filePath)) continue;
				self::$loaded[$class] = $filePath;
				require_once("$basePath/$path");
				return true;
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
		
		public static function addPath($path){
			if(!is_string($path)) return false;
			if(in_array($path, self::$paths)) return false;
			self::$paths[] = $path;
		}
	}
}
?>