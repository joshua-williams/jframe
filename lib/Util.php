<?php

namespace JFrame{
	
	class Util{
		
		public static function path($path){
			$ds = (DS == '/') ? '\\' : '/';
			return str_replace($ds, DS, $path);
		}
		public static function toObject(&$var){
			if(is_array($var)) $var = (object) $var;
			if(!is_object($var)) return $var;
			foreach($var as &$v){
				if(is_array($v)) $v = (object) $v;
				if(is_object($v)) self::toObject($v);
			}
			return $var;
		}
		
		public static function toArray(&$var){
			if(is_object($var)) $var = (array) $var;
			if(!is_array($var)) return $var;
			foreach($var as &$v){
				if(is_object($v)) $v = (array) $v;
				if(is_array($v)) self::toArray($v);
			}
			return $var;
		}
	}
}

?>