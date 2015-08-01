#! /usr/bin/php5
<?php
DEFINE('PATH_JFRAME', dirname(__DIR__));
require_once(PATH_JFRAME . '/lib/CLI.php');

class CLI extends \JFrame\CLI{
	
	function install(){
		require_once(PATH_JFRAME . '/cli/install.php');
	}
	
	function addmodule(){
		require_once(PATH_JFRAME . '/cli/addmodule.php');
	}
	
	public function dbsetup(){
		require_once(PATH_JFRAME . '/lib/Vars.php');
		require_once(PATH_JFRAME . '/cli/dbsetup.php');
	}
	
	public function dbconnect(){
		require_once(PATH_JFRAME . '/lib/Vars.php');
		require_once(PATH_JFRAME . '/cli/dbconnect.php');
	}
	private function getPath(){
		if($this->path) return $this->path;
	}
	
}

new CLI();
?>
