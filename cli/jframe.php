#! /usr/bin/php5
<?php
DEFINE('PATH_JFRAME', dirname(__DIR__));
require_once(PATH_JFRAME . '/lib/CLI.php');

class CLI extends \JFrame\CLI{
	
	function install(){
		require_once(PATH_JFRAME . '/cli/install.php');
	}
	
	function addmodule(){
		
	}
	
	private function getPath(){
		if($this->path) return $this->path;
	}
	
}

new CLI();
?>
