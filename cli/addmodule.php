<?php 

$module = ($mod = $this->opt('namespace')) ? $mod : $this->getResponse('Please enter module namespace');
if(file_exists($module)) $this->setError('Module already exists');
foreach(array(
	$module,
	$module . DS . 'Controller',
	$module . DS . 'Service',
	$module . DS . 'views'
) as $dir){
	mkdir($dir);
}

// ADD MODULE
$content = "<?php
namespace $module{
	
	class Module extends \JFrame\Module{
	
	}
}
?>";
$file = fopen("$module/Module.php", 'w');
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
$file = fopen("$module/Controller/$module.php", 'w');
fwrite($file, $content);
fclose($file);
$this->write("$module has been created");
?>