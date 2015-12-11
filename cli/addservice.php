<?php 
/**
 * the following example will create a new service
 * jframe addmodel -namespace Demo/Model -class Product
 * 
 * to create multiple services
 * jframe addmodel -namespace Demo/Model -alass "Product Customer Order"
 * the above will crate a Product, Customer, and Order php files
 */
 
use JFrame\Util;

if(!is_file('./config/databases.php')) $this->setError('Please run command from project root');
require_once(PATH_JFRAME.'/lib/Util.php');

$ns = ($ns = $this->opt('namespace')) ? $ns : $this->getResponse('namespace');

if(!is_dir(Util::path("modules/$ns"))) $this->setError("Namespace path not found $ns");

$ns = str_replace('/','\\', $ns);
$class = ($class = $this->opt('class')) ? $class : $this->getResponse("classname; for multiple classnames seperate with space");

foreach(explode(' ', $class) as $cls){
	$cls = trim($cls);
	if(!$cls) continue;
	$scriptPath = Util::path('modules/' . $ns . '/' . $cls . '.php');
	if(file_exists($scriptPath)){
		$override = $this->getResponse("{$cls}.php already exists. Override? [y/n]");
		if($override == 'n') continue;
		if($override != 'y' && $override !='yes') continue;
	}

	$file = fopen($scriptPath, 'w');
	$txt = "<?php
	
namespace $ns{

	class $cls extends \JFrame\Service{
	
	}
}

?>
";
	fwrite($file, $txt);
	fclose($file);
	$this->write("Service created ($cls)");
	
}