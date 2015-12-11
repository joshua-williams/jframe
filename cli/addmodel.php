<?php 

/**
 *  example usage
 *  jframe addmodel -namespace Demo/Model -class "Namespace\Model\Products
 */

if(!is_file('./config/databases.php')) $this->setError('Please run command from project root');
use \JFrame\Util;
use \JFrame\Vars;

require_once(PATH_JFRAME.'/lib/DB.php');
require_once(PATH_JFRAME.'/lib/Util.php');
require_once(PATH_JFRAME.'/lib/Vars.php');
$config = include('./config/databases.php');

if(!is_array($config) || !isset($config['default'])) $this->setError('Please set up default database connection');
$db = new \JFrame\DB($config['default']);
if(!$db->has_connection) $this->setError('Failed to connect to database');

$ns = ($ns = $this->opt('namespace')) ? $ns : $this->getResponse('namespace');

if(!is_dir(Util::path("modules/$ns"))) $this->setError("Namespace path not found $ns");

$ns = str_replace('/','\\', $ns);
$class = ($class = $this->opt('classname')) ? $class : $this->getResponse("classname; for multiple classnames seperate with space");
$databaseName = $db->loadResult("select database()");
$tables = $db->loadAssocList("SHOW TABLES");

foreach(explode(' ', $class) as $cls){
	$cls = trim($cls);
	if(!$cls) continue;
	$scriptPath = Util::path('modules/' . $ns . '/' . $cls . '.php');
	if(file_exists($scriptPath)){
		$override = $this->getResponse("{$cls}.php already exists. Override? [y/n]");
		if($override == 'n') continue;
		if($override != 'y' && $override !='yes') continue;
	}

	$tableData = false;
	$tableName = ($tableName = $this->opt('table')) ? $tableName : $this->getResponse('table name for ' . $cls);
	
	foreach($tables as $table){
		if($table["Tables_in_$databaseName"] == $tableName){
			$tableData = $db->loadObjectList("DESC $tableName");
			break;
		}
	}
	
	if(!$tableData){
		$this->write("table $tableName does not exist in $databaseName");
		continue;
	}
	
	$ns = str_replace('/', '\\', $ns);
	$class = str_replace('/','\\', $class);
	$defaultValue = $this->getResponse("property default values. leave blank for none");
	switch(trim($defaultValue)){
		case "false": $value = " = false"; break;
		case "null": $value = " = null"; break;
		case "": $value = ""; break;
		default: $value = " = '$defaultValue'";
	}
	$txt = "<?php
	
	namespace $ns{
	
	class $cls extends \JFrame\Model{
";
	foreach($tableData as $data){
		$txt.=chr(9).chr(9) . "protected $$data->Field{$value};".chr(10);
	}
	$txt.="
	}
}
	
?>";
	$file = fopen($scriptPath, "w");
	fwrite($file, $txt);
	fclose($file);
	
	$this->write("$cls model has been created");
	

}

