<?php 

$module = ($mod = $this->opt('namespace')) ? $mod : $this->getResponse('Please enter module namespace');
$dir = (is_dir('modules')) ? 'modules' : $this->getResponse("Please enter module path");
if(!is_dir($dir)) $this->setError("$dir path does not exist.");
if(file_exists($dir.DS.$module)) $this->setError('Module already exists at '.$dir);

$dir = preg_replace('/\\$/', '', preg_replace('/\/$/','', $dir)) . DS . $module;


foreach(array($dir, $dir.DS.'Controller', $dir.DS.'Service', $dir.DS.'views') as $directory){
	mkdir($directory);
}
// ADD MODULE
$content = "<?php
namespace $module{
	
	class Module extends \JFrame\Module{
	
	}
}
?>";

$file = fopen("$dir/Module.php", 'w');
fwrite($file, $content);
fclose($file);

// ADD CONTROLLER
$content = "<?php
namespace $module\Controller{
	
	class $module extends \JFrame\Controller{
		
		public function index(){
		
		}
	}
}
?>";
$file = fopen($dir.DS."Controller".DS."$module.php", 'w');
fwrite($file, $content);
fclose($file);
$this->write("$module has been created");
?>