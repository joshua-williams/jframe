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
						//echo "$k <xmp>".print_r($var,1).'</xmp>';
						if(!isset($var[$k])) return $default;
						$var = $var[$k];
					}
				}
			}
			
		}
		public static function getEscape($var,$default=false,$method=false){
			switch($method){
				case 'get':
					if(!isset($_GET[$var])) return $default;
					return ($rtn = mysql_real_escape_string($_GET[$var]))? $rtn : $default;
				case 'post':
					if(!isset($_POST[$var])) return $default;
					return ($rtn = mysql_real_escape_string($_POST[$var]))? $rtn : $default;
				default:
					if(isset($_POST[$var])) return mysql_real_escape_string($_POST[$var]);
					if(isset($_GET[$var])) return mysql_real_escape_string($_GET);
					return $default;
			}
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
