<?php

namespace JFrame {
	class Vars {
	
		public static function get($var,$default=NULL,$method=NULL){
			switch($method){
				case 'get':
					if(!isset($_GET[$var])) return $default;
					 return $_GET[$var];
				case 'post':
					if(!isset($_POST[$var])) return $default;
					return $_POST[$var];
				default:
					if(isset($_POST[$var])) return $_POST[$var];
					if(isset($_GET[$var])) return $_GET[$var];
					return $default;
			}
		}
		
		public static function getFrom($var, $key, $default=null){
			if(!is_array($var) && !is_object($var)) return $default;
			$var = (array) $var;
			$keys = explode('.', $key);
			if($keys){
				for($a=0; $a<count($keys); $a++){
					$k = $keys[$a];
					if($a== count($keys)-1){
						if(!is_array($var) && !is_object($var)) return $default;
						$var = (array) $var;
						return (isset($var[$k])) ? $var[$k] : $default;
					}else{
						if(!is_array($var) && !is_object($var)) return $default;
						$var = (array) $var;
						if(!isset($var[$k])) return $default;
						$var = $var[$k];
					}
				}
			}
		}
		/**
		 * @param mixed $source An array or object to set the
		 * @param string $property period seperated property
		 * @param mixed $value
		 */
		public static function setTo(&$source, $property, $value){
			if(!is_array($source) && !is_object($source)) return false;
			$parts = explode('.', $property);
			if(count($parts) == 1){
				switch(gettype($source)){
					case 'array': return $source[$parts[0]] = $value; break;
					case 'object': return $source->{$parts[0]} = $value; break;
				}
			}
			switch(gettype($source)){
				case 'array': 
					$source[$parts[0]] = array(); 
					$prop = &$source[$parts[0]];
					break;
				case 'object': 
					$source->{$parts[0]} = array(); 
					$prop = &$source->{$parts[0]};
					break;
			}
		
			for($a=1; $a<count($parts); $a++){
				$prop[$parts[$a]] = array();
				$prop = &$prop[$parts[$a]];
			}
			$prop = $value;
			return $source;
		}
	
		public static function set($var,$val=false,$method='get'){
			switch($method){
				case 'get': $_GET[$var] = $val; break;
				case 'post': $_POST[$var] = $val; break;
			}
		}
	
		public static function parseQuery($str){
			$start = ($pos = strpos($str, '?')) ? $pos + 1 : 0;
			$str = substr($str, $start);
			$parts = explode('&', $str);
			foreach($parts as $part){
				$pts = explode('=', $part);
				switch(count($pts)){
					case 1: $rtn[$pts[0]] = false; break;
					case 2: $rtn[$pts[0]] = $pts[1]; break;
				}
			}
			return isset($rtn) ? $rtn : array();
		}
	}
	
	
}
?>
