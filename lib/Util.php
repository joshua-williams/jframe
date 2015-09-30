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
		
		public static function encrypt($key, $plaintext){
			$td = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');
			$iv = mcrypt_create_iv(mcrypt_enc_get_iv_size($td), MCRYPT_RAND);
			mcrypt_generic_init($td, $key, $iv);
			$crypttext = mcrypt_generic($td, $plaintext);
			mcrypt_generic_deinit($td);
			return base64_encode($iv.$crypttext);
		}
		
		public static function decrypt($key, $crypttext){
			$crypttext = base64_decode($crypttext);
			$plaintext = '';
			$td        = mcrypt_module_open(MCRYPT_RIJNDAEL_256, '', MCRYPT_MODE_CBC, '');
			$ivsize    = mcrypt_enc_get_iv_size($td);
			$iv        = substr($crypttext, 0, $ivsize);
			$crypttext = substr($crypttext, $ivsize);
			if ($iv) {
				mcrypt_generic_init($td, $key, $iv);
				$plaintext = mdecrypt_generic($td, $crypttext);
			}
			return trim($plaintext);
		}
		
		public static function generateKey($max_length=20){
			return ($random = substr(md5(rand()),0,$max_length));
		}
		
		public static function str2alias($str){
			if(!is_string($str)) return false;
			return preg_replace('/[\-]+/', '-', preg_replace('/[^0-9a-zA-Z]/', '-', $str));
		}
	}
}

?>